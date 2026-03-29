<?php

namespace Modules\AdvancedReports\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Product;
use DB;

class StockReportExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithChunkReading
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Get the query for export - Optimized version
     */
    public function query()
    {
        $business_id = $this->filters['business_id'] ?? 1;
        $location_id = $this->filters['location_id'] ?? null;
        $category_id = $this->filters['category_id'] ?? null;
        $show_zero_stock = $this->filters['show_zero_stock'] ?? false;

        $query = Product::leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('variations', 'products.id', '=', 'variations.product_id')
            ->leftJoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
            ->leftJoin('business_locations as bl', 'vld.location_id', '=', 'bl.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->where('products.business_id', $business_id)
            ->where('products.type', '!=', 'modifier');

        // Apply filters
        if (!empty($location_id)) {
            $query->where('vld.location_id', $location_id);
        }

        if (!empty($category_id)) {
            $query->where('products.category_id', $category_id);
        }

        // Hide zero stock if requested
        if (!$show_zero_stock) {
            $query->where('vld.qty_available', '>', 0);
        }

        return $query->select([
            'products.id as product_id',
            'products.sku',
            'products.name as product_name',
            'variations.name as variation_name',
            'variations.sub_sku',
            'variations.id as variation_id',
            'categories.name as category_name',
            'bl.name as location_name',
            'units.short_name as unit_name',
            DB::raw('COALESCE(vld.qty_available, 0) as current_stock'),
            DB::raw('COALESCE(variations.default_purchase_price, 0) as purchase_price'),
            DB::raw('COALESCE(variations.default_sell_price, 0) as selling_price'),
            DB::raw('COALESCE(vld.qty_available, 0) * COALESCE(variations.default_purchase_price, 0) as stock_value_purchase'),
            DB::raw('COALESCE(vld.qty_available, 0) * COALESCE(variations.default_sell_price, 0) as stock_value_sale'),
            DB::raw('(COALESCE(vld.qty_available, 0) * COALESCE(variations.default_sell_price, 0)) - (COALESCE(vld.qty_available, 0) * COALESCE(variations.default_purchase_price, 0)) as potential_profit')
        ])->orderBy('products.name');
    }

    /**
     * Define the headings for the Excel file
     */
    public function headings(): array
    {
        return [
            'SKU',
            'Product Name',
            'Variation',
            'Category',
            'Location',
            'Unit',
            'Current Stock',
            'Purchase Price',
            'Selling Price',
            'Stock Value (Purchase)',
            'Stock Value (Sale)',
            'Potential Profit'
        ];
    }

    /**
     * Map the data for each row
     */
    public function map($row): array
    {
        return [
            $row->sub_sku ?: $row->sku,
            $row->product_name,
            $row->variation_name && $row->variation_name != 'DUMMY' ? $row->variation_name : '-',
            $row->category_name ?: '-',
            $row->location_name ?: '-',
            $row->unit_name ?: 'units',
            number_format($row->current_stock, 2),
            number_format($row->purchase_price, 2),
            number_format($row->selling_price, 2),
            number_format($row->stock_value_purchase, 2),
            number_format($row->stock_value_sale, 2),
            number_format($row->potential_profit, 2)
        ];
    }

    /**
     * Set chunk size for reading data
     */
    public function chunkSize(): int
    {
        return 500; // Process 500 records at a time
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E2E2'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],
            'A:L' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],
            'G:L' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                ],
            ],
        ];
    }
}
