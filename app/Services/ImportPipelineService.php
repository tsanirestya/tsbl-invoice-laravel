<?php

namespace App\Services;

use App\Models\ImportAnomaly;
use App\Models\ImportRejection;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\TransactionImport;
use App\Models\TransactionImportRow;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportPipelineService
{
    private const VALID_TYPES  = ['HTL', 'TRD', 'TVL'];
    private const FUZZY_THRESHOLD = 80;

    /** Products keyed by uppercase dsi_code, loaded once per run */
    private array $productsByDsiCode = [];
    /** Aliases keyed by uppercase alias_name */
    private array $aliasesByName  = [];

    public function run(TransactionImport $import, string $filePath): void
    {
        $import->update(['status' => 'processing']);

        $this->loadProductLookups();

        $rawRows = $this->parseFile($filePath);

        $totalRows     = 0;
        $validCount    = 0;
        $anomalyCount  = 0;
        $rejectedCount = 0;

        DB::transaction(function () use ($import, $rawRows, &$totalRows, &$validCount, &$anomalyCount, &$rejectedCount) {
            foreach ($rawRows as $index => $raw) {
                $totalRows++;
                $normalized = $this->normalize($raw);

                // Step 2 — Filter & Reject
                $rejection = $this->checkRejection($normalized);
                if ($rejection) {
                    ImportRejection::create([
                        'import_id'        => $import->id,
                        'row_index'        => $index + 1,
                        'raw_data'         => $raw,
                        'rejection_reason' => $rejection,
                        'created_at'       => now(),
                    ]);
                    $rejectedCount++;
                    continue;
                }

                // Step 3 — Product Matching
                [$matchedProductId, $matchMethod] = $this->matchProduct($normalized['ticket_name'] ?? '');

                // Step 4 — Commission Calculation
                $product     = $matchedProductId ? Product::find($matchedProductId) : null;
                $pricing     = $this->calcPricing($normalized, $product);

                // Save row
                $row = TransactionImportRow::create([
                    'import_id'          => $import->id,
                    'uuid_key'           => (string) Str::uuid(),
                    'row_index'          => $index + 1,
                    'transaction_no'     => $normalized['transaction_no'] ?? null,
                    'date'               => $normalized['date'] ?? null,
                    'ticket_type'        => $normalized['ticket_type'] ?? null,
                    'ticket_name'        => $normalized['ticket_name'] ?? null,
                    'transaction_type'   => $normalized['transaction_type'] ?? null,
                    'time'               => $normalized['time'] ?? null,
                    'cashier'            => $normalized['cashier'] ?? null,
                    'payment_method'     => $normalized['payment_method'] ?? null,
                    'payment_details'    => $normalized['payment_details'] ?? null,
                    'unit_price'         => $normalized['unit_price'] ?? null,
                    'qty'                => $normalized['qty'] ?? 1,
                    'total_amount'       => $normalized['total_amount'] ?? null,
                    'remark'             => $normalized['remark'] ?? null,
                    'country'            => $normalized['country'] ?? null,
                    'nationality'        => $normalized['nationality'] ?? null,
                    'matched_product_id' => $matchedProductId,
                    'match_method'       => $matchMethod,
                    'publish_rate'       => $product?->publish_rate,
                    'nett_price'         => $product?->nett_price,
                    'komisi_rate'        => $product?->komisi,
                    'komisi_amount'      => $pricing['komisi_amount'],
                    'status'             => 'valid',
                    'created_at'         => now(),
                ]);

                // Step 4 — Detect & record anomalies
                $anomalies = $this->detectAnomalies($normalized, $matchedProductId, $matchMethod, $product, $pricing);

                if (!empty($anomalies)) {
                    foreach ($anomalies as $a) {
                        ImportAnomaly::create([
                            'import_row_id' => $row->id,
                            'anomaly_type'  => $a['type'],
                            'detail'        => $a['detail'],
                            'severity'      => ImportAnomaly::severityFor($a['type']),
                            'created_at'    => now(),
                        ]);
                    }
                    $row->update(['status' => 'anomaly']);
                    $anomalyCount++;
                } else {
                    $validCount++;

                    // Auto-fill product category from dsi_code on successful import row
                    if ($product && $product->dsi_code !== null && $product->category === null) {
                        $product->update([
                            'category' => $product->dsi_code === '0' ? '0' : substr($product->dsi_code, 0, 3),
                        ]);
                    }
                }
            }
        });

        $import->update([
            'status'       => 'reviewed',
            'total_rows'   => $totalRows,
            'valid_rows'   => $validCount,
            'anomaly_rows' => $anomalyCount,
            'rejected_rows'=> $rejectedCount,
            'processed_at' => now(),
        ]);
    }

    // --- Step 1: Parse ---

    private function parseFile(string $filePath): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            return $this->parseCsv($filePath);
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, false);

        if (empty($rows)) return [];

        $headers = array_map(fn($h) => strtolower(trim((string) $h)), array_shift($rows));

        $result = [];
        foreach ($rows as $row) {
            $assoc = array_combine($headers, array_pad($row, count($headers), null));
            // Skip completely empty rows
            if (empty(array_filter($assoc, fn($v) => $v !== null && $v !== ''))) continue;
            $result[] = $assoc;
        }

        return $result;
    }

    private function parseCsv(string $filePath): array
    {
        $handle  = fopen($filePath, 'r');
        $headers = null;
        $result  = [];

        while (($cols = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = array_map(fn($h) => strtolower(trim($h)), $cols);
                continue;
            }
            $assoc = array_combine($headers, array_pad($cols, count($headers), null));
            if (empty(array_filter($assoc, fn($v) => $v !== null && $v !== ''))) continue;
            $result[] = $assoc;
        }
        fclose($handle);

        return $result;
    }

    // --- Step 1: Normalize ---

    private function normalize(array $raw): array
    {
        // Flexible column name mapping
        $map = [
            'transaction_no'  => ['transaction_no', 'transaction no', 'no transaksi', 'trx no'],
            'date'            => ['date', 'tanggal', 'tgl'],
            'ticket_type'     => ['ticket_type', 'ticket type', 'tipe tiket', 'type'],
            'ticket_name'     => ['ticket_name', 'ticket name', 'nama tiket', 'name'],
            'transaction_type'=> ['transaction_type', 'transaction type', 'tipe transaksi'],
            'time'            => ['time', 'waktu', 'jam'],
            'cashier'         => ['cashier', 'kasir'],
            'payment_method'  => ['payment_method', 'payment method', 'metode bayar'],
            'payment_details' => ['payment_details', 'payment details', 'detail bayar'],
            'unit_price'      => ['unit_price', 'unit price', 'harga satuan', 'price'],
            'qty'             => ['qty', 'quantity', 'jumlah'],
            'total_amount'    => ['total_amount', 'total amount', 'total', 'jumlah total'],
            'remark'          => ['remark', 'keterangan', 'catatan'],
            'country'         => ['country', 'negara'],
            'nationality'     => ['nationality', 'kewarganegaraan'],
        ];

        $out = [];
        $rawLower = array_change_key_case($raw, CASE_LOWER);

        foreach ($map as $field => $candidates) {
            $out[$field] = null;
            foreach ($candidates as $c) {
                if (isset($rawLower[$c]) && $rawLower[$c] !== null && $rawLower[$c] !== '') {
                    $out[$field] = trim((string) $rawLower[$c]);
                    break;
                }
            }
        }

        // Uppercase ticket_type and ticket_name
        if ($out['ticket_type']) $out['ticket_type'] = strtoupper($out['ticket_type']);
        if ($out['ticket_name']) $out['ticket_name'] = strtoupper($out['ticket_name']);

        // Parse date
        if ($out['date']) {
            try {
                $out['date'] = Carbon::parse($out['date'])->format('Y-m-d');
            } catch (\Throwable) {
                $out['date'] = null;
            }
        }

        // Parse time
        if ($out['time']) {
            try {
                $out['time'] = Carbon::parse($out['time'])->format('H:i:s');
            } catch (\Throwable) {
                $out['time'] = null;
            }
        }

        // Numeric fields
        $out['unit_price']   = $out['unit_price']   ? (float) str_replace([',', ' '], ['', ''], $out['unit_price'])   : null;
        $out['qty']          = $out['qty']           ? max(1, (int) $out['qty']) : 1;
        $out['total_amount'] = $out['total_amount']  ? (float) str_replace([',', ' '], ['', ''], $out['total_amount']) : null;

        return $out;
    }

    // --- Step 2: Filter & Reject ---

    private function checkRejection(array $row): ?string
    {
        $type = $row['ticket_type'] ?? '';
        $name = $row['ticket_name'] ?? '';

        if (empty($type) && empty($name)) {
            return 'EMPTY_ROW';
        }

        if (!in_array($type, self::VALID_TYPES, true)) {
            return 'INVALID_TICKET_TYPE';
        }

        // 3-char prefix of ticket_name must match a valid type
        $prefix = strtoupper(substr($name, 0, 3));
        if (!in_array($prefix, self::VALID_TYPES, true)) {
            return 'NAME_PREFIX_MISMATCH';
        }

        return null;
    }

    // --- Step 3: Product Matching ---

    private function loadProductLookups(): void
    {
        // Match ticket_name against dsi_code (the DSI system code on each product)
        $this->productsByDsiCode = Product::where('is_active', true)
            ->whereNotNull('dsi_code')
            ->where('dsi_code', '!=', '')
            ->get(['id', 'dsi_code'])
            ->keyBy(fn($p) => strtoupper(trim($p->dsi_code)))
            ->toArray();

        $this->aliasesByName = ProductAlias::with('product:id,dsi_code,is_active')
            ->get()
            ->filter(fn($a) => $a->product?->is_active)
            ->keyBy(fn($a) => strtoupper($a->alias_name))
            ->toArray();
    }

    private function matchProduct(string $ticketName): array
    {
        $upper = strtoupper(trim($ticketName));

        // Layer 1 — Exact: ticket_name == dsi_code
        if (isset($this->productsByDsiCode[$upper])) {
            return [$this->productsByDsiCode[$upper]['id'], 'exact'];
        }

        // Layer 2 — Alias: manually mapped ticket_name variants → product
        if (isset($this->aliasesByName[$upper])) {
            return [$this->aliasesByName[$upper]['product']['id'], 'alias'];
        }

        // Layer 3 — Fuzzy against dsi_code (do NOT auto-accept — flag only)
        $best   = 0;
        $bestId = null;
        foreach ($this->productsByDsiCode as $dsiCode => $product) {
            similar_text($upper, $dsiCode, $pct);
            if ($pct > $best) {
                $best   = $pct;
                $bestId = $product['id'];
            }
        }

        if ($best >= self::FUZZY_THRESHOLD && $bestId) {
            return [$bestId, 'fuzzy'];
        }

        return [null, 'none'];
    }

    // --- Step 5: Commission Calculation ---

    private function calcPricing(array $row, ?Product $product): array
    {
        if (!$product) {
            return ['komisi_amount' => 0];
        }

        $unitPrice   = (float) ($row['unit_price'] ?? 0);
        $qty         = (int)   ($row['qty'] ?? 1);
        $publishRate = (float) $product->publish_rate;
        $nettPrice   = (float) $product->nett_price;
        $komisiRate  = (float) $product->komisi;

        if (abs($unitPrice - $publishRate) < 0.01) {
            return ['komisi_amount' => $komisiRate * $qty];
        }

        if (abs($unitPrice - $nettPrice) < 0.01) {
            return ['komisi_amount' => 0];
        }

        // Price mismatch — komisi pending approval (set to 0 by default, can be overridden)
        return ['komisi_amount' => 0];
    }

    // --- Step 4: Anomaly Detection ---

    private function detectAnomalies(array $row, ?int $matchedProductId, ?string $matchMethod, ?Product $product, array $pricing): array
    {
        $anomalies   = [];
        $ticketType  = $row['ticket_type'] ?? '';
        $ticketName  = $row['ticket_name'] ?? '';
        $namePrefix  = strtoupper(substr($ticketName, 0, 3));
        $unitPrice   = (float) ($row['unit_price'] ?? 0);

        // A. CATEGORY_MISMATCH: ticket_type is valid BUT name prefix is a different valid type
        if (in_array($ticketType, self::VALID_TYPES) && in_array($namePrefix, self::VALID_TYPES) && $namePrefix !== $ticketType) {
            $anomalies[] = [
                'type'   => 'CATEGORY_MISMATCH',
                'detail' => "ticket_type={$ticketType} but ticket_name prefix={$namePrefix}",
            ];
        }

        // B. REVERSE_MISMATCH: handled at filter stage — if it reaches here, name prefix is valid
        // (already rejected if name prefix not valid)

        // C. PRODUCT_NOT_FOUND
        if ($matchedProductId === null) {
            $anomalies[] = [
                'type'   => 'PRODUCT_NOT_FOUND',
                'detail' => "No product matched for: {$ticketName}",
            ];
        }

        // D. PRICE_MISMATCH
        if ($product && $unitPrice > 0) {
            $publishRate = (float) $product->publish_rate;
            $nettPrice   = (float) $product->nett_price;
            $noMatchRate = abs($unitPrice - $publishRate) >= 0.01;
            $noMatchNett = abs($unitPrice - $nettPrice) >= 0.01;

            if ($noMatchRate && $noMatchNett) {
                $anomalies[] = [
                    'type'   => 'PRICE_MISMATCH',
                    'detail' => "unit_price={$unitPrice}, publish_rate={$publishRate}, nett_price={$nettPrice}",
                ];
            }

            // E. SUSPICIOUS_PRICING
            if ($unitPrice < $nettPrice) {
                $anomalies[] = [
                    'type'   => 'SUSPICIOUS_PRICING',
                    'detail' => "unit_price={$unitPrice} below nett_price={$nettPrice}",
                ];
            }
        }

        // F. FUZZY_CANDIDATE
        if ($matchMethod === 'fuzzy') {
            $anomalies[] = [
                'type'   => 'FUZZY_CANDIDATE',
                'detail' => "Fuzzy match to product_id={$matchedProductId} — needs manual confirmation",
            ];
        }

        return $anomalies;
    }
}
