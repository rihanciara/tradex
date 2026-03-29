<?php

namespace Modules\AdvancedReports\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use App\Contact;
use Illuminate\Support\Facades\DB;

class SupplierStockMovementExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithColumnFormatting
{
    protected $business_id;
    protected $filters;

    public function __construct($business_id, $filters = [])
    {
        $this->business_id = $business_id;
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Contact::where('business_id', $this->business_id)
            ->where('type', 'supplier')
            ->whereHas('products', function ($q) {
                $q->where('enable_stock', 1);
            })
            ->select([
                'id as supplier_id',
                'name as supplier_name',
                'supplier_business_name',
                'contact_id',
                'mobile'
            ]);

        // Apply filters
        if (!empty($this->filters['supplier_id'])) {
            $query->where('id', $this->filters['supplier_id']);
        }

        $suppliers = $query->get();

        $data = collect();

        foreach ($suppliers as $supplier) {
            $row = collect();

            // Supplier info
            $supplier_display = $supplier->supplier_business_name
                ? $supplier->supplier_business_name . ' (' . $supplier->supplier_name . ')'
                : $supplier->supplier_name;

            $row->push($supplier_display);

            // Today Stock
            $row->push($this->getTodayStockQty($supplier->supplier_id));
            $row->push($this->getTodayStockPurchaseValue($supplier->supplier_id));
            $row->push($this->getTodayStockSaleValue($supplier->supplier_id));

            // Total Sale
            $row->push($this->getTotalSaleQty($supplier->supplier_id));
            $row->push($this->getTotalSalePurchaseValue($supplier->supplier_id));
            $row->push($this->getTotalSaleSaleValue($supplier->supplier_id));

            // Total Purchase
            $row->push($this->getTotalPurchaseQty($supplier->supplier_id));
            $row->push($this->getTotalPurchasePurchaseValue($supplier->supplier_id));
            $row->push($this->getTotalPurchaseSaleValue($supplier->supplier_id));

            // Balance
            $row->push($this->getBalanceQty($supplier->supplier_id));
            $row->push($this->getBalancePurchaseValue($supplier->supplier_id));
            $row->push($this->getBalanceValue($supplier->supplier_id));

            // Profit
            $row->push($this->getProfitValue($supplier->supplier_id));

            $data->push($row);
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Supplier',
            'Today Stock Qty',
            'Today Stock Purchase Value',
            'Today Stock Sale Value',
            'Total Sale Qty',
            'Total Sale Purchase Value',
            'Total Sale Sale Value',
            'Total Purchase Qty',
            'Total Purchase Purchase Value',
            'Total Purchase Sale Value',
            'Balance Qty',
            'Balance Purchase Value',
            'Balance Value',
            'Profit Value'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ],
            // Data rows styling
            'A:N' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
            // Currency columns alignment
            'B:N' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT
                ]
            ]
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25, // Supplier
            'B' => 12, // Today Stock Qty
            'C' => 18, // Today Stock Purchase Value
            'D' => 18, // Today Stock Sale Value
            'E' => 12, // Total Sale Qty
            'F' => 18, // Total Sale Purchase Value
            'G' => 18, // Total Sale Sale Value
            'H' => 12, // Total Purchase Qty
            'I' => 18, // Total Purchase Purchase Value
            'J' => 18, // Total Purchase Sale Value
            'K' => 12, // Balance Qty
            'L' => 18, // Balance Purchase Value
            'M' => 15, // Balance Value
            'N' => 15, // Profit Value
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            'D' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            'F' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            'G' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            'I' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            'J' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            'L' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            'M' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            'N' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
        ];
    }

    public function title(): string
    {
        return 'Supplier Stock Movement';
    }

    // Helper methods with proper error handling and type casting
    private function getTodayStockQty($supplier_id)
    {
        try {
            $result = DB::table('products')
                ->join('variations', 'products.id', '=', 'variations.product_id')
                ->join('variation_location_details', 'variations.id', '=', 'variation_location_details.variation_id')
                ->where('products.business_id', $this->business_id)
                ->where('products.supplier_id', $supplier_id)
                ->where('products.enable_stock', 1)
                ->sum('variation_location_details.qty_available');

            return (float) ($result ?: 0);
        } catch (\Exception $e) {
            \Log::error('Export getTodayStockQty error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getTodayStockPurchaseValue($supplier_id)
    {
        try {
            $result = DB::table('products')
                ->join('variations', 'products.id', '=', 'variations.product_id')
                ->join('variation_location_details', 'variations.id', '=', 'variation_location_details.variation_id')
                ->where('products.business_id', $this->business_id)
                ->where('products.supplier_id', $supplier_id)
                ->where('products.enable_stock', 1)
                ->sum(DB::raw('variation_location_details.qty_available * COALESCE(variations.default_purchase_price, 0)'));

            return round((float) ($result ?: 0), 2);
        } catch (\Exception $e) {
            \Log::error('Export getTodayStockPurchaseValue error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getTodayStockSaleValue($supplier_id)
    {
        try {
            $result = DB::table('products')
                ->join('variations', 'products.id', '=', 'variations.product_id')
                ->join('variation_location_details', 'variations.id', '=', 'variation_location_details.variation_id')
                ->where('products.business_id', $this->business_id)
                ->where('products.supplier_id', $supplier_id)
                ->where('products.enable_stock', 1)
                ->sum(DB::raw('variation_location_details.qty_available * COALESCE(variations.sell_price_inc_tax, 0)'));

            return round((float) ($result ?: 0), 2);
        } catch (\Exception $e) {
            \Log::error('Export getTodayStockSaleValue error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getTotalSaleQty($supplier_id)
    {
        try {
            $query = DB::table('transaction_sell_lines')
                ->join('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
                ->join('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
                ->join('product_variations', 'variations.product_variation_id', '=', 'product_variations.id')
                ->join('products', 'product_variations.product_id', '=', 'products.id')
                ->where('transactions.business_id', $this->business_id)
                ->where('products.supplier_id', $supplier_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final');

            if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
                $query->whereBetween('transactions.transaction_date', [$this->filters['start_date'], $this->filters['end_date']]);
            }

            $result = $query->sum(DB::raw('COALESCE(transaction_sell_lines.quantity, 0) - COALESCE(transaction_sell_lines.quantity_returned, 0)'));
            return (float) ($result ?: 0);
        } catch (\Exception $e) {
            \Log::error('Export getTotalSaleQty error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getTotalSalePurchaseValue($supplier_id)
    {
        try {
            $query = DB::table('transaction_sell_lines')
                ->join('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
                ->join('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
                ->join('product_variations', 'variations.product_variation_id', '=', 'product_variations.id')
                ->join('products', 'product_variations.product_id', '=', 'products.id')
                ->where('transactions.business_id', $this->business_id)
                ->where('products.supplier_id', $supplier_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final');

            if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
                $query->whereBetween('transactions.transaction_date', [$this->filters['start_date'], $this->filters['end_date']]);
            }

            $result = $query->sum(DB::raw('(COALESCE(transaction_sell_lines.quantity, 0) - COALESCE(transaction_sell_lines.quantity_returned, 0)) * COALESCE(variations.default_purchase_price, 0)'));
            return round((float) ($result ?: 0), 2);
        } catch (\Exception $e) {
            \Log::error('Export getTotalSalePurchaseValue error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getTotalSaleSaleValue($supplier_id)
    {
        try {
            $query = DB::table('transaction_sell_lines')
                ->join('transactions', 'transaction_sell_lines.transaction_id', '=', 'transactions.id')
                ->join('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
                ->join('product_variations', 'variations.product_variation_id', '=', 'product_variations.id')
                ->join('products', 'product_variations.product_id', '=', 'products.id')
                ->where('transactions.business_id', $this->business_id)
                ->where('products.supplier_id', $supplier_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final');

            if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
                $query->whereBetween('transactions.transaction_date', [$this->filters['start_date'], $this->filters['end_date']]);
            }

            $result = $query->sum(DB::raw('(COALESCE(transaction_sell_lines.quantity, 0) - COALESCE(transaction_sell_lines.quantity_returned, 0)) * COALESCE(transaction_sell_lines.unit_price_inc_tax, 0)'));
            return round((float) ($result ?: 0), 2);
        } catch (\Exception $e) {
            \Log::error('Export getTotalSaleSaleValue error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getTotalPurchaseQty($supplier_id)
    {
        try {
            $query = DB::table('purchase_lines')
                ->join('transactions', 'purchase_lines.transaction_id', '=', 'transactions.id')
                ->join('variations', 'purchase_lines.variation_id', '=', 'variations.id')
                ->join('product_variations', 'variations.product_variation_id', '=', 'product_variations.id')
                ->join('products', 'product_variations.product_id', '=', 'products.id')
                ->where('transactions.business_id', $this->business_id)
                ->where('products.supplier_id', $supplier_id)
                ->where('transactions.type', 'purchase')
                ->where('transactions.status', 'received');

            if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
                $query->whereBetween('transactions.transaction_date', [$this->filters['start_date'], $this->filters['end_date']]);
            }

            $result = $query->sum(DB::raw('COALESCE(purchase_lines.quantity, 0) - COALESCE(purchase_lines.quantity_returned, 0)'));
            return (float) ($result ?: 0);
        } catch (\Exception $e) {
            \Log::error('Export getTotalPurchaseQty error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getTotalPurchasePurchaseValue($supplier_id)
    {
        try {
            $query = DB::table('purchase_lines')
                ->join('transactions', 'purchase_lines.transaction_id', '=', 'transactions.id')
                ->join('variations', 'purchase_lines.variation_id', '=', 'variations.id')
                ->join('product_variations', 'variations.product_variation_id', '=', 'product_variations.id')
                ->join('products', 'product_variations.product_id', '=', 'products.id')
                ->where('transactions.business_id', $this->business_id)
                ->where('products.supplier_id', $supplier_id)
                ->where('transactions.type', 'purchase')
                ->where('transactions.status', 'received');

            if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
                $query->whereBetween('transactions.transaction_date', [$this->filters['start_date'], $this->filters['end_date']]);
            }

            $result = $query->sum(DB::raw('(COALESCE(purchase_lines.quantity, 0) - COALESCE(purchase_lines.quantity_returned, 0)) * COALESCE(purchase_lines.purchase_price_inc_tax, 0)'));
            return round((float) ($result ?: 0), 2);
        } catch (\Exception $e) {
            \Log::error('Export getTotalPurchasePurchaseValue error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getTotalPurchaseSaleValue($supplier_id)
    {
        try {
            $query = DB::table('purchase_lines')
                ->join('transactions', 'purchase_lines.transaction_id', '=', 'transactions.id')
                ->join('variations', 'purchase_lines.variation_id', '=', 'variations.id')
                ->join('product_variations', 'variations.product_variation_id', '=', 'product_variations.id')
                ->join('products', 'product_variations.product_id', '=', 'products.id')
                ->where('transactions.business_id', $this->business_id)
                ->where('products.supplier_id', $supplier_id)
                ->where('transactions.type', 'purchase')
                ->where('transactions.status', 'received');

            if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
                $query->whereBetween('transactions.transaction_date', [$this->filters['start_date'], $this->filters['end_date']]);
            }

            $result = $query->sum(DB::raw('(COALESCE(purchase_lines.quantity, 0) - COALESCE(purchase_lines.quantity_returned, 0)) * COALESCE(variations.sell_price_inc_tax, 0)'));
            return round((float) ($result ?: 0), 2);
        } catch (\Exception $e) {
            \Log::error('Export getTotalPurchaseSaleValue error: ' . $e->getMessage());
            return 0;
        }
    }

    private function getBalanceQty($supplier_id)
    {
        $balance = $this->getTodayStockQty($supplier_id)
            + $this->getTotalPurchaseQty($supplier_id)
            - $this->getTotalSaleQty($supplier_id);

        return (float) $balance;
    }

    private function getBalancePurchaseValue($supplier_id)
    {
        $balance = $this->getTodayStockPurchaseValue($supplier_id)
            + $this->getTotalPurchasePurchaseValue($supplier_id)
            - $this->getTotalSalePurchaseValue($supplier_id);

        return round((float) $balance, 2);
    }

    private function getBalanceValue($supplier_id)
    {
        $balance = $this->getTodayStockSaleValue($supplier_id)
            + $this->getTotalPurchaseSaleValue($supplier_id)
            - $this->getTotalSaleSaleValue($supplier_id);

        return round((float) $balance, 2);
    }

    private function getProfitValue($supplier_id)
    {
        $profit = $this->getTotalSaleSaleValue($supplier_id)
            - $this->getTotalSalePurchaseValue($supplier_id);

        return round((float) $profit, 2);
    }
}
