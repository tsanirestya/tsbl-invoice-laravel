<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class PartnerImportSeeder extends Seeder
{
    public function run(): void
    {
        $basePath = base_path('data awal');

        $this->importHotel("$basePath/HOTEL.xlsx");
        $this->importTourDesk("$basePath/TOUR DESK.xlsx");
        $this->importTravel("$basePath/TRAVEL.xlsx");
    }

    private function importHotel(string $path): void
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('HTL');
        $highestRow = $sheet->getHighestDataRow();

        $inserted = 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            $namaPartner = trim((string) $sheet->getCell("B$row")->getValue());

            if (empty($namaPartner)) continue;

            if (DB::table('partners')->where('nama_partner', $namaPartner)->exists()) continue;

            [$contractStart, $contractEnd] = $this->parseContractDate(
                (string) $sheet->getCell("P$row")->getValue()
            );

            // H: "account_no/BANK_NAME/account_name"
            [$bankAccountNo, $bankName, $bankAccountName] = $this->parseBankField(
                (string) $sheet->getCell("H$row")->getValue()
            );

            $limitCredit = $sheet->getCell("AA$row")->getValue();
            $limitCredit = is_numeric($limitCredit) ? (float) $limitCredit : 0;

            $notes = collect([
                trim((string) $sheet->getCell("X$row")->getValue()),
                trim((string) $sheet->getCell("AB$row")->getValue()),
                trim((string) $sheet->getCell("AC$row")->getValue()),
            ])->filter()->implode(' | ') ?: null;

            DB::table('partners')->insert([
                'partner_type'        => 'HOTEL',
                'nama_partner'        => $namaPartner,
                'category'            => trim((string) $sheet->getCell("C$row")->getValue()) ?: null,
                'nama_pt'             => trim((string) $sheet->getCell("D$row")->getValue()) ?: null,
                'channel'             => trim((string) $sheet->getCell("F$row")->getValue()) ?: null,
                'pic_tsbl'            => trim((string) $sheet->getCell("E$row")->getValue()) ?: null,
                'pic_partner'         => trim((string) $sheet->getCell("R$row")->getValue()) ?: null,
                'pic_partner_email'   => $this->extractEmail(
                    $sheet->getCell("S$row")->getValue(),
                    $sheet->getCell("T$row")->getValue()
                ),
                'pic_partner_phone'   => $this->extractPhone(
                    $sheet->getCell("T$row")->getValue(),
                    $sheet->getCell("S$row")->getValue(),
                    $sheet->getCell("V$row")->getValue()
                ),
                'address'             => trim((string) $sheet->getCell("W$row")->getValue()) ?: null,
                'bank_name'           => $bankName,
                'bank_account_no'     => $bankAccountNo,
                'bank_account_name'   => $bankAccountName,
                'payment_type'        => trim((string) $sheet->getCell("Z$row")->getValue()) ?: null,
                'limit_credit'        => $limitCredit,
                'contract_start'      => $contractStart,
                'contract_end'        => $contractEnd,
                'doc_akta_pendirian'  => $this->docFlag($sheet->getCell("I$row")->getValue()),
                'doc_akta_perubahan'  => $this->docFlag($sheet->getCell("J$row")->getValue()),
                'doc_surat_kuasa'     => $this->docFlag($sheet->getCell("K$row")->getValue()),
                'doc_ktp'             => $this->docFlag($sheet->getCell("L$row")->getValue()),
                'doc_nib'             => $this->docFlag($sheet->getCell("M$row")->getValue()),
                'doc_npwp'            => $this->docFlag($sheet->getCell("N$row")->getValue()),
                'notes'               => $notes,
                'is_active'           => true,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            $inserted++;
        }

        $this->command->info("HOTEL: $inserted partners imported.");
    }

    private function importTourDesk(string $path): void
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        $inserted = 0;

        for ($row = 4; $row <= $highestRow; $row++) {
            $namaPartner = trim((string) $sheet->getCell("B$row")->getValue());

            if (empty($namaPartner)) continue;

            if (DB::table('partners')->where('nama_partner', $namaPartner)->exists()) continue;

            [$contractStart, $contractEnd] = $this->parseContractDate(
                (string) $sheet->getCell("C$row")->getValue()
            );

            $paymentP = trim((string) $sheet->getCell("P$row")->getValue());
            $paymentQ = trim((string) $sheet->getCell("Q$row")->getValue());
            $paymentType = $paymentP ?: ($paymentQ ?: null);

            $ktp = $sheet->getCell("N$row")->getValue();

            DB::table('partners')->insert([
                'partner_type'       => 'TOURDESK',
                'nama_partner'       => $namaPartner,
                'category'           => trim((string) $sheet->getCell("G$row")->getValue()) ?: null,
                'channel'            => trim((string) $sheet->getCell("H$row")->getValue()) ?: null,
                'pic_tsbl'           => trim((string) $sheet->getCell("D$row")->getValue()) ?: null,
                'pic_partner'        => trim((string) $sheet->getCell("K$row")->getValue()) ?: null,
                'pic_partner_phone'  => trim((string) $sheet->getCell("J$row")->getValue()) ?: null,
                'address'            => trim((string) $sheet->getCell("I$row")->getValue()) ?: null,
                'bank_name'          => trim((string) $sheet->getCell("L$row")->getValue()) ?: null,
                'bank_account_no'    => trim((string) $sheet->getCell("M$row")->getValue()) ?: null,
                'doc_ktp'            => ($ktp == 1 || strtoupper((string)$ktp) === 'YES') ? 'YES' : null,
                'payment_type'       => $paymentType,
                'contract_start'     => $contractStart,
                'contract_end'       => $contractEnd,
                'is_active'          => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            $inserted++;
        }

        $this->command->info("TOUR DESK: $inserted partners imported.");
    }

    private function importTravel(string $path): void
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        $inserted = 0;

        for ($row = 5; $row <= $highestRow; $row++) {
            $namaPartner = trim((string) $sheet->getCell("B$row")->getValue());

            if (empty($namaPartner)) continue;

            if (DB::table('partners')->where('nama_partner', $namaPartner)->exists()) continue;

            [$contractStart, $contractEnd] = $this->parseContractDate(
                (string) $sheet->getCell("M$row")->getValue()
            );

            $limitCredit = $sheet->getCell("V$row")->getValue();
            $limitCredit = is_numeric($limitCredit) ? (float) $limitCredit : 0;

            $notes = collect([
                trim((string) $sheet->getCell("T$row")->getValue()),
                trim((string) $sheet->getCell("X$row")->getValue()),
            ])->filter()->implode(' | ') ?: null;

            DB::table('partners')->insert([
                'partner_type'        => 'TRAVEL',
                'nama_partner'        => $namaPartner,
                'category'            => trim((string) $sheet->getCell("D$row")->getValue()) ?: null,
                'channel'             => trim((string) $sheet->getCell("E$row")->getValue()) ?: null,
                'pic_tsbl'            => trim((string) $sheet->getCell("C$row")->getValue()) ?: null,
                'pic_partner'         => trim((string) $sheet->getCell("O$row")->getValue()) ?: null,
                'pic_partner_phone'   => trim((string) $sheet->getCell("P$row")->getValue()) ?: null,
                'pic_partner_email'   => trim((string) $sheet->getCell("Q$row")->getValue()) ?: null,
                'address'             => trim((string) $sheet->getCell("R$row")->getValue()) ?: null,
                'payment_type'        => trim((string) $sheet->getCell("W$row")->getValue()) ?: null,
                'limit_credit'        => $limitCredit,
                'contract_start'      => $contractStart,
                'contract_end'        => $contractEnd,
                'doc_akta_pendirian'  => $this->docFlag($sheet->getCell("F$row")->getValue()),
                'doc_akta_perubahan'  => $this->docFlag($sheet->getCell("G$row")->getValue()),
                'doc_surat_kuasa'     => $this->docFlag($sheet->getCell("H$row")->getValue()),
                'doc_ktp'             => $this->docFlag($sheet->getCell("I$row")->getValue()),
                'doc_nib'             => $this->docFlag($sheet->getCell("J$row")->getValue()),
                'doc_npwp'            => $this->docFlag($sheet->getCell("K$row")->getValue()),
                'notes'               => $notes,
                'is_active'           => true,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            $inserted++;
        }

        $this->command->info("TRAVEL: $inserted partners imported.");
    }

    private function extractEmail(mixed ...$candidates): ?string
    {
        foreach ($candidates as $val) {
            $v = trim((string)$val);
            if (filter_var($v, FILTER_VALIDATE_EMAIL)) return $v;
        }
        return null;
    }

    private function extractPhone(mixed ...$candidates): ?string
    {
        foreach ($candidates as $val) {
            $v = trim((string)$val);
            if ($v && !filter_var($v, FILTER_VALIDATE_EMAIL) && strlen($v) <= 30) return $v;
        }
        return null;
    }

    private function parseBankField(string $raw): array
    {
        $raw = trim($raw);
        if (empty($raw) || $raw === '-') return [null, null, null];

        $parts = array_map('trim', explode('/', $raw, 3));
        return [
            $parts[0] ?? null,
            $parts[1] ?? null,
            $parts[2] ?? null,
        ];
    }

    private function parseContractDate(string $raw): array
    {
        $raw = trim($raw);
        if (empty($raw)) return [null, null];

        // Try formats: "dd/mm/yyyy - dd/mm/yyyy", "dd-mm-yyyy s/d dd-mm-yyyy", etc.
        $separators = [' - ', ' s/d ', ' sd ', ' to ', ' – '];
        foreach ($separators as $sep) {
            if (str_contains($raw, $sep)) {
                [$start, $end] = explode($sep, $raw, 2);
                return [
                    $this->parseDate(trim($start)),
                    $this->parseDate(trim($end)),
                ];
            }
        }

        // Single date
        $parsed = $this->parseDate($raw);
        return [$parsed, null];
    }

    private function parseDate(string $raw): ?string
    {
        if (empty($raw)) return null;

        // Excel serial number
        if (is_numeric($raw)) {
            try {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$raw))->format('Y-m-d');
            } catch (\Throwable) {}
        }

        // Indonesian month-only format: "Februari 2026", "Desember 2026"
        $idMonths = [
            'januari' => 'January', 'februari' => 'February', 'maret' => 'March',
            'april' => 'April', 'mei' => 'May', 'juni' => 'June',
            'juli' => 'July', 'agustus' => 'August', 'september' => 'September',
            'oktober' => 'October', 'november' => 'November', 'desember' => 'December',
        ];
        $normalized = strtolower($raw);
        foreach ($idMonths as $id => $en) {
            if (str_contains($normalized, $id)) {
                $translated = str_ireplace($id, $en, $normalized);
                // Month-only: pick first day of month, or last day if it looks like an end
                try {
                    $dt = Carbon::parse($translated);
                    return $dt->format('Y-m-01');
                } catch (\Throwable) {}
            }
        }

        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd M Y', 'd F Y', 'j/n/Y', 'j-n-Y'];
        foreach ($formats as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $raw)->format('Y-m-d');
            } catch (\Throwable) {}
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function docFlag(mixed $val): ?string
    {
        if ($val == 1 || strtoupper((string)$val) === 'YES' || strtoupper((string)$val) === 'V') {
            return 'YES';
        }
        return null;
    }
}
