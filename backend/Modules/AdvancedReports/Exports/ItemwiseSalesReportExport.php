<?php

namespace Modules\AdvancedReports\Exports;

use App\TransactionSellLine;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ItemwiseSalesReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    protected $business_id;
    protected $filters;
    protected $transactionUtil;

    public function __construct($business_id, $filters = [])
    {
        $this->business_id = $business_id;
        $this->filters = $filters;
        $this->transactionUtil = new TransactionUtil();
    }

    public function collection()
    {
        $query = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->join('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->leftjoin('categories as cat', 'p.category_id', '=', 'cat.id')
            ->leftjoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->leftjoin('tax_rates as tr', 'transaction_sell_lines.tax_id', '=', 'tr.id')
            ->leftjoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->leftjoin('users as created_by', 't.created_by', '=', 'created_by.id')
            ->where('t.business_id', $this->business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNull('transaction_sell_lines.parent_sell_line_id')
            ->select([
                't.invoice_no',
                't.transaction_date',
                'c.name as customer_name',
                'c.supplier_business_name',
                'c.mobile as customer_mobile',
                'c.tax_number as customer_gstin',
                'bl.name as location_name',
                
                // Product information
                'p.name as product_name',
                'p.sku',
                DB::raw("CASE WHEN p.type = 'variable' THEN CONCAT(pv.name, ' - ', v.name) ELSE '' END as variation_name"),
                'cat.name as category_name',
                'cat.short_code as hsn_code',
                'b.name as brand_name',
                'u.short_name as unit_name',
                
                // Quantity and pricing
                DB::raw('(transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) as sold_qty'),
                'transaction_sell_lines.unit_price_before_discount as unit_price',
                'transaction_sell_lines.unit_price_inc_tax as unit_price_inc_tax',
                
                // Discounts
                'transaction_sell_lines.line_discount_amount',
                'transaction_sell_lines.line_discount_type',
                DB::raw('CASE 
                    WHEN transaction_sell_lines.line_discount_type = "percentage" THEN 
                        (transaction_sell_lines.unit_price_before_discount * transaction_sell_lines.line_discount_amount / 100) * (transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0))
                    ELSE 
                        transaction_sell_lines.line_discount_amount * (transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0))
                END as total_discount'),
                
                // Tax information
                'tr.name as tax_name',
                'tr.amount as tax_rate',
                'transaction_sell_lines.item_tax',
                DB::raw('transaction_sell_lines.item_tax * (transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) as total_tax'),
                
                // Totals
                DB::raw('(transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_before_discount as subtotal'),
                DB::raw('(transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_inc_tax as line_total'),
                
                // User information
                DB::raw("CONCAT(COALESCE(created_by.first_name, ''), ' ', COALESCE(created_by.last_name, '')) as created_by_name"),
            ]);

        // Apply filters
        $this->applyFilters($query);

        return $query->orderBy('t.transaction_date', 'desc')
                    ->orderBy('c.name')
                    ->get();
    }

    public function headings(): array
    {
        return [
            'Invoice No',
            'Date',
            'Customer Name',
            'Customer Business',
            'Customer Mobile',
            'Customer GSTIN',
            'Location',
            'Product Name',
            'SKU',
            'Variation',
            'Category',
            'HSN/SAC Code',
            'Brand',
            'Unit',
            'Qty Sold',
            'Unit Price (Exc Tax)',
            'Unit Price (Inc Tax)',
            'Discount Amount',
            'Tax Name',
            'Tax Rate (%)',
            'Tax Amount',
            'Subtotal (Exc Tax)',
            'Line Total (Inc Tax)',
            'Created By'
        ];
    }

    public function map($row): array
    {
        $customer_name = $row->customer_name;
        if (!empty($row->supplier_business_name)) {
            $customer_name = $row->supplier_business_name . ' - ' . $customer_name;
        }

        $product_name = $row->product_name;
        if (!empty($row->variation_name)) {
            $product_name = $product_name . ' (' . $row->variation_name . ')';
        }

        return [
            $row->invoice_no,
            \Carbon\Carbon::parse($row->transaction_date)->format('d-m-Y'),
            $customer_name,
            $row->supplier_business_name ?? '',
            $row->customer_mobile ?? '',
            $row->customer_gstin ?? '',
            $row->location_name ?? '',
            $product_name,
            $row->sku ?? '',
            $row->variation_name ?? '',
            $row->category_name ?? '',
            $row->hsn_code ?? '',
            $row->brand_name ?? '',
            $row->unit_name ?? '',
            $this->transactionUtil->num_f($row->sold_qty, false, null, true),
            $this->transactionUtil->num_f($row->unit_price, false),
            $this->transactionUtil->num_f($row->unit_price_inc_tax, false),
            $this->transactionUtil->num_f($row->total_discount ?? 0, false),
            $row->tax_name ?? '',
            $row->tax_rate ? $row->tax_rate . '%' : '',
            $this->transactionUtil->num_f($row->total_tax ?? 0, false),
            $this->transactionUtil->num_f($row->subtotal ?? 0, false),
            $this->transactionUtil->num_f($row->line_total ?? 0, false),
            trim($row->created_by_name) ?: ''
        ];
    }

    public function title(): string
    {
        return 'Itemwise Sales Report';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function applyFilters($query)
    {
        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('t.transaction_date', [$this->filters['start_date'], $this->filters['end_date']]);
        }

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('t.location_id', $permitted_locations);
        }

        if (!empty($this->filters['location_id'])) {
            $query->where('t.location_id', $this->filters['location_id']);
        }

        if (!empty($this->filters['customer_id'])) {
            $query->where('t.contact_id', $this->filters['customer_id']);
        }

        if (!empty($this->filters['category_id'])) {
            $query->where('p.category_id', $this->filters['category_id']);
        }

        if (!empty($this->filters['brand_id'])) {
            $query->where('p.brand_id', $this->filters['brand_id']);
        }

        if (!empty($this->filters['unit_id'])) {
            $query->where('p.unit_id', $this->filters['unit_id']);
        }

        if (!empty($this->filters['tax_rate_id'])) {
            $query->where('transaction_sell_lines.tax_id', $this->filters['tax_rate_id']);
        }

        if (!empty($this->filters['user_id'])) {
            $query->where('t.created_by', $this->filters['user_id']);
        }

        if (!empty($this->filters['product_filter'])) {
            $query->where(function($q) {
                $q->where('p.name', 'like', '%' . $this->filters['product_filter'] . '%')
                  ->orWhere('p.sku', 'like', '%' . $this->filters['product_filter'] . '%');
            });
        }

        if (!empty($this->filters['customer_filter'])) {
            $query->where(function($q) {
                $q->where('c.name', 'like', '%' . $this->filters['customer_filter'] . '%')
                  ->orWhere('c.supplier_business_name', 'like', '%' . $this->filters['customer_filter'] . '%')
                  ->orWhere('c.mobile', 'like', '%' . $this->filters['customer_filter'] . '%');
            });
        }
    }
}