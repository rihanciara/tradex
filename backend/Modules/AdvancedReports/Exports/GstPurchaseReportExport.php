<?php

namespace Modules\AdvancedReports\Exports;

use App\PurchaseLine;
use App\TaxRate;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GstPurchaseReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    protected $business_id;
    protected $filters;
    protected $transactionUtil;
    protected $taxes;

    public function __construct($business_id, $filters = [])
    {
        $this->business_id = $business_id;
        $this->filters = $filters;
        $this->transactionUtil = new TransactionUtil();

        // Get taxes for this business
        $this->taxes = TaxRate::where('business_id', $business_id)
            ->where('is_tax_group', 0)
            ->select(['id', 'name', 'amount'])
            ->get()
            ->toArray();
    }

    public function collection()
    {
        $query = PurchaseLine::join('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->join('products as p', 'purchase_lines.product_id', '=', 'p.id')
            ->leftjoin('categories as cat', 'p.category_id', '=', 'cat.id')
            ->leftjoin('tax_rates as tr', 'purchase_lines.tax_id', '=', 'tr.id')
            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('t.business_id', $this->business_id)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received')
            ->select(
                'c.name as supplier',
                'c.supplier_business_name',
                'c.contact_id',
                'c.tax_number',
                't.id as transaction_id',
                't.ref_no',
                't.transaction_date as transaction_date',
                'p.name as product_name',
                'purchase_lines.pp_without_discount as unit_price',
                'purchase_lines.purchase_price as unit_price_after_discount',
                DB::raw('(purchase_lines.quantity - purchase_lines.quantity_returned) as purchase_qty'),
                'purchase_lines.discount_percent',
                'purchase_lines.item_tax',
                'tr.amount as tax_percent',
                'tr.is_tax_group',
                'purchase_lines.tax_id',
                'u.short_name as unit',
                DB::raw('((purchase_lines.quantity- purchase_lines.quantity_returned) * purchase_lines.purchase_price_inc_tax) as line_total')
            );

        // Apply filters
        $this->applyFilters($query);

        return $query->get();
    }

    public function headings(): array
    {
        $headings = [
            'Reference No',
            'Date',
            'Supplier',
            'Tax Number',
            'Product',
            'Quantity',
            'Unit',
            'Unit Price',
            'Taxable Value',
            'Discount',
            'Tax Rate',
            'Tax Amount',
            'Line Total'
        ];

        // Add dynamic tax columns
        foreach ($this->taxes as $tax) {
            $headings[] = $tax['name'] . ' (' . $tax['amount'] . '%)';
        }

        return $headings;
    }

    public function map($row): array
    {
        $supplier_name = $row->supplier;
        if (!empty($row->supplier_business_name)) {
            $supplier_name = $row->supplier_business_name . ', ' . $row->supplier;
        }

        $taxable_value = $row->unit_price_after_discount * $row->purchase_qty;

        $discount = !empty($row->discount_percent) ? $row->discount_percent : 0;
        if (!empty($discount)) {
            $discount = $row->unit_price * ($discount / 100);
        }

        $mapped = [
            $row->ref_no,
            \Carbon\Carbon::parse($row->transaction_date)->format('d-m-Y'),
            $supplier_name,
            $row->tax_number,
            $row->product_name,
            $this->transactionUtil->num_f($row->purchase_qty, false, null, true),
            $row->unit,
            $this->transactionUtil->num_f($row->unit_price, false),
            $this->transactionUtil->num_f($taxable_value, false),
            $this->transactionUtil->num_f($discount, false),
            $row->tax_percent ? $row->tax_percent . '%' : '',
            $this->transactionUtil->num_f($row->item_tax * $row->purchase_qty, false),
            $this->transactionUtil->num_f($row->line_total, false)
        ];

        // Add dynamic tax columns
        $group_taxes_array = TaxRate::groupTaxes($this->business_id);
        $group_taxes = [];
        foreach ($group_taxes_array as $group_tax) {
            foreach ($group_tax['sub_taxes'] as $sub_tax) {
                $group_taxes[$group_tax->id]['sub_taxes'][$sub_tax->id] = $sub_tax;
            }
        }

        foreach ($this->taxes as $tax) {
            $sub_tax_share = 0;
            if ($row->is_tax_group == 1 && array_key_exists($tax['id'], $group_taxes[$row->tax_id]['sub_taxes'])) {
                $sub_tax_share = $this->transactionUtil->calc_percentage($row->unit_price_after_discount, $group_taxes[$row->tax_id]['sub_taxes'][$tax['id']]->amount) * $row->purchase_qty;
            }
            $mapped[] = $sub_tax_share > 0 ? $this->transactionUtil->num_f($sub_tax_share, false) : '';
        }

        return $mapped;
    }

    public function title(): string
    {
        return 'GST Purchase Report';
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
            $query->where('t.transaction_date', '>=', $this->filters['start_date'])
                ->where('t.transaction_date', '<=', $this->filters['end_date']);
        }

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('t.location_id', $permitted_locations);
        }

        if (!empty($this->filters['location_id'])) {
            $query->where('t.location_id', $this->filters['location_id']);
        }

        if (!empty($this->filters['supplier_id'])) {
            $query->where('t.contact_id', $this->filters['supplier_id']);
        }
    }
}
