<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceCreatorService
{
    public function __construct(
        private readonly InvoiceNumberGeneratorService $numberGenerator
    ) {}

    /**
     * Create a PROFORMA invoice linked to a reservation.
     */
    public function createProforma(array $data, array $items, int $createdBy): Invoice
    {
        return $this->create(Invoice::TYPE_PROFORMA, $data, $items, $createdBy);
    }

    /**
     * Create a FINAL invoice, optionally linked to a proforma (after reconciliation).
     */
    public function createFinal(array $data, array $items, int $createdBy, ?int $parentInvoiceId = null): Invoice
    {
        $data['parent_invoice_id'] = $parentInvoiceId;
        return $this->create(Invoice::TYPE_FINAL, $data, $items, $createdBy);
    }

    /**
     * Create a Credit Note referencing an existing final invoice.
     * Amount must not exceed parent invoice grand_total.
     */
    public function createCreditNote(Invoice $parentInvoice, array $data, array $items, int $createdBy): Invoice
    {
        if ($parentInvoice->invoice_type !== Invoice::TYPE_FINAL) {
            throw new InvalidArgumentException('Credit Note must reference a FINAL invoice.');
        }

        $lineTotal = collect($items)->sum('amount');
        if ($lineTotal > (float) $parentInvoice->grand_total) {
            throw new InvalidArgumentException('Credit Note amount cannot exceed parent invoice grand_total.');
        }

        $data['parent_invoice_id'] = $parentInvoice->id;
        return $this->create(Invoice::TYPE_CREDIT_NOTE, $data, $items, $createdBy);
    }

    /**
     * Create a Debit Note referencing an existing final invoice.
     */
    public function createDebitNote(Invoice $parentInvoice, array $data, array $items, int $createdBy): Invoice
    {
        if ($parentInvoice->invoice_type !== Invoice::TYPE_FINAL) {
            throw new InvalidArgumentException('Debit Note must reference a FINAL invoice.');
        }

        $data['parent_invoice_id'] = $parentInvoice->id;
        return $this->create(Invoice::TYPE_DEBIT_NOTE, $data, $items, $createdBy);
    }

    /**
     * Create a CANCELLATION invoice referencing the invoice being cancelled.
     */
    public function createCancellation(Invoice $replacedInvoice, int $createdBy): Invoice
    {
        if ($replacedInvoice->is_locked) {
            throw new InvalidArgumentException("Invoice #{$replacedInvoice->id} is locked — cannot cancel.");
        }

        $data = [
            'partner_id'         => $replacedInvoice->partner_id,
            'replaces_invoice_id'=> $replacedInvoice->id,
            'parent_invoice_id'  => $replacedInvoice->parent_invoice_id,
            'invoice_date'       => now()->toDateString(),
            'grand_total'        => 0,
            'subtotal'           => 0,
            'deposit'            => 0,
            'source_type'        => $replacedInvoice->source_type,
            'source_id'          => $replacedInvoice->source_id,
            'notes'              => "Cancellation of {$replacedInvoice->invoice_no}",
        ];

        return $this->create(Invoice::TYPE_CANCELLATION, $data, [], $createdBy);
    }

    private function create(string $type, array $data, array $items, int $createdBy): Invoice
    {
        return DB::transaction(function () use ($type, $data, $items, $createdBy) {
            $invoiceNo = $this->numberGenerator->generate($type);

            $invoice = Invoice::create(array_merge($data, [
                'invoice_no'   => $invoiceNo,
                'invoice_type' => $type,
                'created_by'   => $createdBy,
                'updated_by'   => $createdBy,
            ]));

            foreach ($items as $i => $item) {
                InvoiceItem::create(array_merge($item, [
                    'invoice_id' => $invoice->id,
                    'sort_order' => $item['sort_order'] ?? ($i + 1),
                ]));
            }

            return $invoice->load('items');
        });
    }
}
