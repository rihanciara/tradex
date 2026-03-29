<?php

namespace Modules\AdvancedReports\Exports;

use App\TransactionSellLine;
use App\Transaction;
use App\Product;
use App\Utils\TransactionUtil;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use DB;

class ProductReportExport extends DefaultValueBinder implements FromCollection, WithCustomValueBinder, WithStrictNullComparison, WithEvents, WithTitle
{
    private $request;
    private $business_id;
    private $transactionUtil;
    private $payment_types;
    private $options;
    private $currentRow = 1;
    private $sectionRows = [];

    public function __construct($request, $business_id, $options = [])
    {
        $this->request = $request;
        $this->business_id = $business_id;
        $this->transactionUtil = new TransactionUtil();
        $this->payment_types = $this->transactionUtil->payment_types(null, true, $business_id);
        
        $this->options = array_merge([
            'include_summary' => false,
            'include_weekly_sales' => false,
            'include_staff_performance' => false,
            'include_stock_valuation' => false,
            'include_purchase_summary' => false,
            'business_name' => 'Business'
        ], $options);
    }

    public function title(): string
    {
        return 'Product Performance Report';
    }

    /**
     * Build the complete export collection
     */
    public function collection()
    {
        $data = collect();
        $this->currentRow = 1;
        $this->sectionRows = [];
        
        // Add summary information if requested
        if ($this->options['include_summary']) {
            $summaryStart = $this->currentRow;
            $summaryData = $this->getSummarySection();
            $data = $data->merge($summaryData);
            $this->currentRow += $summaryData->count();
            $this->sectionRows['summary'] = ['start' => $summaryStart, 'end' => $this->currentRow - 1];
            
            $data->push([]); // Empty row
            $this->currentRow++;
        }
        
        // Add weekly sales summary if requested
        if ($this->options['include_weekly_sales']) {
            $weeklyStart = $this->currentRow;
            $weeklyData = $this->getWeeklySalesSection();
            $data = $data->merge($weeklyData);
            $this->currentRow += $weeklyData->count();
            $this->sectionRows['weekly_sales'] = ['start' => $weeklyStart, 'end' => $this->currentRow - 1];
            
            $data->push([]); // Empty row
            $this->currentRow++;
        }
        
        // Add staff performance if requested
        if ($this->options['include_staff_performance']) {
            $staffStart = $this->currentRow;
            $staffData = $this->getStaffPerformanceSection();
            $data = $data->merge($staffData);
            $this->currentRow += $staffData->count();
            $this->sectionRows['staff_performance'] = ['start' => $staffStart, 'end' => $this->currentRow - 1];
            
            $data->push([]); // Empty row
            $this->currentRow++;
        }
        
        // Add stock valuation if requested
        if ($this->options['include_stock_valuation']) {
            $stockStart = $this->currentRow;
            $stockData = $this->getStockValuationSection();
            $data = $data->merge($stockData);
            $this->currentRow += $stockData->count();
            $this->sectionRows['stock_valuation'] = ['start' => $stockStart, 'end' => $this->currentRow - 1];
            
            $data->push([]); // Empty row
            $this->currentRow++;
        }
        
        // Add purchase summary if requested
        if ($this->options['include_purchase_summary']) {
            $purchaseStart = $this->currentRow;
            $purchaseData = $this->getPurchaseSummarySection();
            $data = $data->merge($purchaseData);
            $this->currentRow += $purchaseData->count();
            $this->sectionRows['purchase_summary'] = ['start' => $purchaseStart, 'end' => $this->currentRow - 1];
            
            $data->push([]); // Empty row
            $this->currentRow++;
        }
        
        // Always add main product data
        $productStart = $this->currentRow;
        $productData = $this->getProductDataSection();
        $data = $data->merge($productData);
        $this->currentRow += $productData->count();
        $this->sectionRows['product_data'] = ['start' => $productStart, 'end' => $this->currentRow - 1];
        
        return $data;
    }

    /**
     * Register events for formatting
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $this->applyFormatting($event);
            },
        ];
    }

    /**
     * Apply Excel formatting
     */
    private function applyFormatting(AfterSheet $event)
    {
        $sheet = $event->sheet->getDelegate();
        
        // Apply formatting for each section
        foreach ($this->sectionRows as $sectionName => $range) {
            $this->formatSection($sheet, $sectionName, $range);
        }
        
        // Auto-size columns
        foreach (range('A', 'Z') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Additional columns if needed
        foreach (range('A', 'Z') as $first) {
            foreach (range('A', 'Z') as $second) {
                $col = $first . $second;
                if ($col <= 'AZ') { // Limit to AZ
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                if ($col == 'AZ') break 2; // Stop at AZ
            }
        }
    }

    /**
     * Format specific sections
     */
    private function formatSection($sheet, $sectionName, $range)
    {
        switch ($sectionName) {
            case 'weekly_sales':
                $this->formatWeeklySalesSection($sheet, $range);
                break;
            case 'staff_performance':
                $this->formatStaffPerformanceSection($sheet, $range);
                break;
            case 'stock_valuation':
                $this->formatStockValuationSection($sheet, $range);
                break;
            case 'purchase_summary':
                $this->formatPurchaseSummarySection($sheet, $range);
                break;
            case 'product_data':
                $this->formatProductDataSection($sheet, $range);
                break;
            default:
                $this->formatGenericSection($sheet, $range);
                break;
        }
    }

    /**
     * Format Weekly Sales Section
     */
    private function formatWeeklySalesSection($sheet, $range)
    {
        $titleRow = $range['start'];
        
        // Title formatting - find the maximum column used
        $maxCol = 'E'; // Default for weekly sales
        $sheet->getStyle("A{$titleRow}:{$maxCol}{$titleRow}")->applyFromArray([
            'font' => [
                'bold' => true, 
                'size' => 14, 
                'color' => ['rgb' => '000000']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID, 
                'startColor' => ['rgb' => '4DD0E1']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THICK
                ]
            ]
        ]);
        $sheet->mergeCells("A{$titleRow}:{$maxCol}{$titleRow}");
        
        // Find and format header row
        for ($row = $range['start'] + 1; $row <= $range['end']; $row++) {
            $cellValue = $sheet->getCell("A{$row}")->getValue();
            if ($cellValue === 'Week') {
                // This is the header row
                $sheet->getStyle("A{$row}:{$maxCol}{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID, 
                        'startColor' => ['rgb' => 'FFF3CD']
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_MEDIUM
                        ]
                    ]
                ]);
                break;
            }
        }
        
        // Format data rows
        for ($row = $range['start'] + 1; $row <= $range['end']; $row++) {
            $cellValue = $sheet->getCell("A{$row}")->getValue();
            if (strpos($cellValue, 'Week') === 0 && $cellValue !== 'Week') {
                // Data row - alternate colors
                $weekNum = substr($cellValue, -1);
                $isAlternate = ($weekNum % 2 == 0);
                $fillColor = $isAlternate ? 'F8F9FA' : 'FFFFFF';
                
                $sheet->getStyle("A{$row}:{$maxCol}{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID, 
                        'startColor' => ['rgb' => $fillColor]
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN
                        ]
                    ]
                ]);
            } elseif ($cellValue === 'TOTAL') {
                // Total row
                $sheet->getStyle("A{$row}:{$maxCol}{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID, 
                        'startColor' => ['rgb' => 'D1ECF1']
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_MEDIUM
                        ]
                    ]
                ]);
            }
        }
    }

    /**
     * Format Staff Performance Section  
     */
    private function formatStaffPerformanceSection($sheet, $range)
    {
        $titleRow = $range['start'];
        $maxCol = 'J'; // Staff performance has 10 columns
        
        // Title formatting
        $sheet->getStyle("A{$titleRow}:{$maxCol}{$titleRow}")->applyFromArray([
            'font' => [
                'bold' => true, 
                'size' => 14,
                'color' => ['rgb' => '000000']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID, 
                'startColor' => ['rgb' => '4DD0E1']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THICK
                ]
            ]
        ]);
        $sheet->mergeCells("A{$titleRow}:{$maxCol}{$titleRow}");
        
        // Find header row
        for ($row = $range['start'] + 1; $row <= $range['end']; $row++) {
            $cellValue = $sheet->getCell("A{$row}")->getValue();
            if ($cellValue === 'Staff Name') {
                // Header row
                $sheet->getStyle("A{$row}:{$maxCol}{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID, 
                        'startColor' => ['rgb' => 'FFF3CD']
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_MEDIUM
                        ]
                    ]
                ]);
                break;
            }
        }
        
        // Format data rows
        for ($row = $range['start'] + 1; $row <= $range['end']; $row++) {
            $cellValue = $sheet->getCell("A{$row}")->getValue();
            if (!empty($cellValue) && $cellValue !== 'Staff Name' && !strpos($cellValue, 'Date Range')) {
                if ($cellValue === 'TOTAL') {
                    // Total row
                    $sheet->getStyle("A{$row}:{$maxCol}{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID, 
                            'startColor' => ['rgb' => 'D1ECF1']
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_MEDIUM
                            ]
                        ]
                    ]);
                } else {
                    // Staff data row with colored weeks
                    $sheet->getStyle("A{$row}:{$maxCol}{$row}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN
                            ]
                        ]
                    ]);
                    
                    // Apply week-specific colors
                    $sheet->getStyle("B{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFE4B5'] // Week 1
                        ]
                    ]);
                    $sheet->getStyle("C{$row}:F{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E6F3FF'] // Weeks 2-5
                        ]
                    ]);
                    $sheet->getStyle("G{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'FFFACD'] // Total
                        ]
                    ]);
                    $sheet->getStyle("H{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E8F5E8'] // Purchase
                        ]
                    ]);
                    $sheet->getStyle("I{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F0F8FF'] // Profit
                        ]
                    ]);
                    $sheet->getStyle("J{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F5F5DC'] // Margin
                        ]
                    ]);
                }
            }
        }
    }

    /**
     * Format other sections generically
     */
    private function formatGenericSection($sheet, $range)
    {
        // Find the actual last column with data
        $lastCol = 'A';
        for ($row = $range['start']; $row <= min($range['start'] + 5, $range['end']); $row++) {
            $rowData = $sheet->rangeToArray("A{$row}:Z{$row}", null, true, false);
            $rowData = $rowData[0]; // Get first (and only) row
            
            for ($col = count($rowData) - 1; $col >= 0; $col--) {
                if (!empty($rowData[$col])) {
                    $lastCol = chr(65 + $col); // Convert 0,1,2... to A,B,C...
                    break 2; // Break both loops
                }
            }
        }
        
        // Basic formatting for the range
        for ($row = $range['start']; $row <= $range['end']; $row++) {
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ]
            ]);
            
            // Check if this is a title row (first row of section)
            if ($row == $range['start']) {
                $cellValue = $sheet->getCell("A{$row}")->getValue();
                if (strpos($cellValue, 'SUMMARY') !== false || 
                    strpos($cellValue, 'VALUATION') !== false || 
                    strpos($cellValue, 'PURCHASE') !== false ||
                    strpos($cellValue, 'PRODUCT') !== false) {
                    
                    // Title formatting
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'font' => [
                            'bold' => true, 
                            'size' => 14,
                            'color' => ['rgb' => '000000']
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID, 
                            'startColor' => ['rgb' => '4DD0E1']
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THICK
                            ]
                        ]
                    ]);
                    $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                }
            }
        }
    }

    private function formatStockValuationSection($sheet, $range)
    {
        // Similar formatting logic for stock valuation
        $this->formatGenericSection($sheet, $range);
    }

    private function formatPurchaseSummarySection($sheet, $range) 
    {
        // Similar formatting logic for purchase summary
        $this->formatGenericSection($sheet, $range);
    }

    private function formatProductDataSection($sheet, $range)
    {
        // Basic formatting for product data
        $this->formatGenericSection($sheet, $range);
    }

    /**
     * Custom value binder
     */
    public function bindValue(Cell $cell, $value)
    {
        if (is_numeric($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
            return true;
        }
        return parent::bindValue($cell, $value);
    }

    // ===========================================
    // DATA METHODS - Keep all your existing ones
    // ===========================================

    /**
     * Get summary section
     */
    private function getSummarySection()
    {
        $data = collect();
        
        $data->push(['PRODUCT PERFORMANCE REPORT SUMMARY']);
        $data->push([]);
        $data->push(['Business Name:', $this->options['business_name']]);
        
        if (!empty($this->request->start_date) && !empty($this->request->end_date)) {
            $data->push(['Date Range:', $this->request->start_date . ' to ' . $this->request->end_date]);
        }
        
        $data->push(['Export Date:', date('Y-m-d H:i:s')]);
        $data->push([]);
        
        $summary = $this->getSummaryData();
        
        if ($summary) {
            $data->push(['SUMMARY STATISTICS:']);
            $data->push(['Total Products:', $summary->total_products]);
            $data->push(['Total Transactions:', $summary->total_transactions]);
            $data->push(['Total Customers:', $summary->total_customers]);
            $data->push(['Total Quantity Sold:', $this->transactionUtil->num_f($summary->total_qty_sold, false, null, true)]);
            $data->push(['Total Sales Amount:', $this->transactionUtil->num_f($summary->total_sales_amount, true)]);
            $data->push(['Total Tax Amount:', $this->transactionUtil->num_f($summary->total_tax_amount, true)]);
        }
        
        return $data;
    }

    /**
     * Get weekly sales section
     */
    private function getWeeklySalesSection()
    {
        $data = collect();
        
        $data->push(['WEEKLY SALES SUMMARY REPORT']);
        if (!empty($this->request->start_date) && !empty($this->request->end_date)) {
            $startDate = date('d-m-Y', strtotime($this->request->start_date));
            $endDate = date('d-m-Y', strtotime($this->request->end_date));
            $data->push(["Date Range: $startDate To $endDate"]);
        }
        $data->push([]);
        
        $data->push(['Week', 'Total Sales Amount', 'Equivalent Purchase Value', 'Profit Earned', 'Profit Margin']);
        
        $weeklyData = $this->getWeeklySalesData();
        
        $totalSales = 0;
        $totalPurchase = 0;
        $totalProfit = 0;
        
        for ($week = 1; $week <= 5; $week++) {
            $weekData = $weeklyData->where('week_number', $week)->first();
            
            $sales = $weekData ? $weekData->total_sales_amt : 0;
            $purchase = $weekData ? $weekData->equivalent_purchase_value : 0;
            $profit = $weekData ? $weekData->profit_earned : 0;
            $margin = $sales > 0 ? round(($profit / $sales) * 100, 2) : 0;
            
            $data->push([
                "Week $week",
                $this->transactionUtil->num_f($sales, true),
                $this->transactionUtil->num_f($purchase, true),
                $this->transactionUtil->num_f($profit, true),
                $margin . '%'
            ]);
            
            $totalSales += $sales;
            $totalPurchase += $purchase;
            $totalProfit += $profit;
        }
        
        $totalMargin = $totalSales > 0 ? round(($totalProfit / $totalSales) * 100, 2) : 0;
        $data->push([
            'TOTAL',
            $this->transactionUtil->num_f($totalSales, true),
            $this->transactionUtil->num_f($totalPurchase, true),
            $this->transactionUtil->num_f($totalProfit, true),
            $totalMargin . '%'
        ]);
        
        return $data;
    }

    /**
     * Get staff performance section
     */
    private function getStaffPerformanceSection()
    {
        $data = collect();
        
        $data->push(['STAFF PERFORMANCE ANALYSIS']);
        if (!empty($this->request->start_date) && !empty($this->request->end_date)) {
            $startDate = date('d-m-Y', strtotime($this->request->start_date));
            $endDate = date('d-m-Y', strtotime($this->request->end_date));
            $data->push(["Date Range: $startDate To $endDate"]);
        }
        $data->push([]);
        
        $data->push([
            'Staff Name',
            'Week 1 (1st-7th)', 
            'Week 2 (8th-14th)', 
            'Week 3 (15th-21st)', 
            'Week 4 (22nd-28th)', 
            'Week 5 (29th-31st)',
            'Total Sales',
            'Equivalent Purchase Value',
            'Profit Earned',
            'Profit Margin'
        ]);
        
        $staffData = $this->getStaffPerformanceData();
        
        $totalWeeks = [0, 0, 0, 0, 0];
        $grandTotalSales = 0;
        $grandTotalPurchase = 0;
        $grandTotalProfit = 0;
        
        foreach ($staffData as $staffName => $data_item) {
            $weeks = $data_item['weeks'];
            $totalSales = $data_item['total_sales'];
            $totalPurchase = $data_item['total_purchase'];
            $totalProfit = $data_item['total_profit'];
            $profitMargin = $totalSales > 0 ? round(($totalProfit / $totalSales) * 100, 2) : 0;
            
            $data->push([
                strtoupper($staffName),
                $this->transactionUtil->num_f($weeks[0], true),
                $this->transactionUtil->num_f($weeks[1], true),
                $this->transactionUtil->num_f($weeks[2], true),
                $this->transactionUtil->num_f($weeks[3], true),
                $this->transactionUtil->num_f($weeks[4], true),
                $this->transactionUtil->num_f($totalSales, true),
                $this->transactionUtil->num_f($totalPurchase, true),
                $this->transactionUtil->num_f($totalProfit, true),
                $profitMargin . '%'
            ]);
            
            for ($i = 0; $i < 5; $i++) {
                $totalWeeks[$i] += $weeks[$i];
            }
            $grandTotalSales += $totalSales;
            $grandTotalPurchase += $totalPurchase;
            $grandTotalProfit += $totalProfit;
        }
        
        $grandProfitMargin = $grandTotalSales > 0 ? round(($grandTotalProfit / $grandTotalSales) * 100, 2) : 0;
        $data->push([
            'TOTAL',
            $this->transactionUtil->num_f($totalWeeks[0], true),
            $this->transactionUtil->num_f($totalWeeks[1], true),
            $this->transactionUtil->num_f($totalWeeks[2], true),
            $this->transactionUtil->num_f($totalWeeks[3], true),
            $this->transactionUtil->num_f($totalWeeks[4], true),
            $this->transactionUtil->num_f($grandTotalSales, true),
            $this->transactionUtil->num_f($grandTotalPurchase, true),
            $this->transactionUtil->num_f($grandTotalProfit, true),
            $grandProfitMargin . '%'
        ]);
        
        return $data;
    }

    /**
     * Get stock valuation section
     */
    private function getStockValuationSection()
    {
        $data = collect();
        
        $endDate = !empty($this->request->end_date) ? 
            strtoupper(date('d - M - Y', strtotime($this->request->end_date))) : 
            strtoupper(date('d - M - Y'));
            
        $data->push(["STOCK VALUATION AS AT $endDate"]);
        $data->push([]);
        
        $data->push([
            'Current Stock Value by Purchase Price',
            'Current Stock Value by Sales Price',
            'Potential Profit',
            'Profit Margin'
        ]);
        
        $stockData = $this->getStockValuationData();
        
        $data->push([
            $this->transactionUtil->num_f($stockData->current_stock_value_by_purchase_price ?? 0, true),
            $this->transactionUtil->num_f($stockData->current_stock_value_by_sales_price ?? 0, true),
            $this->transactionUtil->num_f($stockData->potential_profit ?? 0, true),
            ($stockData->profit_margin ?? 0) . '%'
        ]);
        
        return $data;
    }

    /**
     * Get purchase summary section
     */
    private function getPurchaseSummarySection()
    {
        $data = collect();
        
        $data->push(['PURCHASE SUMMARY']);
        if (!empty($this->request->start_date) && !empty($this->request->end_date)) {
            $startDate = date('d-m-Y', strtotime($this->request->start_date));
            $endDate = date('d-m-Y', strtotime($this->request->end_date));
            $data->push(["Date Range: $startDate To $endDate"]);
        }
        $data->push([]);
        
        $data->push(['Purchase Date', 'Invoice #', 'Supplier', 'Purchase Amount', 'Purchase Discount', 'Purchase Total']);
        
        $purchases = $this->getPurchaseSummaryData();
        
        $totalAmount = 0;
        foreach ($purchases as $purchase) {
            $data->push([
                date('d-m-Y', strtotime($purchase->purchase_date)),
                $purchase->invoice_no ?? '0',
                $purchase->supplier_name ?? 'Unknown Supplier',
                $this->transactionUtil->num_f($purchase->purchase_amt ?? 0, true),
                $this->transactionUtil->num_f($purchase->purchase_discount ?? 0, true),
                $this->transactionUtil->num_f($purchase->purchase_total ?? 0, true)
            ]);
            
            $totalAmount += $purchase->purchase_total ?? 0;
        }
        
        if ($purchases->count() > 0) {
            $data->push([
                '', '', '', '', 'TOTAL:',
                $this->transactionUtil->num_f($totalAmount, true)
            ]);
        } else {
            $data->push(['No purchase data available for selected period']);
        }
        
        return $data;
    }

    /**
     * Get main product data section
     */
    private function getProductDataSection()
    {
        $data = collect();
        
        $data->push(['PRODUCT PERFORMANCE DATA']);
        $data->push([]);
        
        $data->push([
            'Product Name', 'SKU', 'Brand', 'Unit', 'Category', 'Sub-Category', 'Barcode Type',
            'Manage Stock (1=Yes, 0=No)', 'Alert Quantity', 'Expires In', 'Expiry Period Unit',
            'Applicable Tax', 'Selling Price Tax Type', 'Product Type', 'Variation Name',
            'Variation Values', 'Variation SKUs', 'Purchase Price (Including Tax)',
            'Purchase Price (Excluding Tax)', 'Profit Margin %', 'Selling Price', 'Current Stock',
            'Stock Location', 'Weight', 'Enable IMEI/Serial (1=Yes, 0=No)', 'Not For Selling (1=Yes, 0=No)',
            'Product Description', 'Custom Field 1', 'Custom Field 2', 'Custom Field 3', 'Custom Field 4',
            'Product Image', 'Customer Name', 'Customer Contact ID', 'Customer Group', 'Invoice No.',
            'Sales Date', 'Week Number', 'Quantity Sold', 'Unit Price', 'Discount', 'Tax Amount',
            'Price Inc. Tax', 'Line Total', 'Payment Method', 'User Name', 'Location',
            'Actual Purchase Price', 'Actual Profit', 'Actual Profit Margin', 'Stock Turnover'
        ]);
        
        $products = $this->getProductData();
        
        foreach ($products as $row) {
            $data->push($this->mapProductRow($row));
        }
        
        return $data;
    }

    // Include all your existing data retrieval methods:
    // getSummaryData(), getWeeklySalesData(), getStaffPerformanceData(), 
    // getStockValuationData(), getPurchaseSummaryData(), getProductData(), 
    // mapProductRow(), applyFilters(), etc.
    
    // Copy ALL the remaining methods from your working version here...
    // (I'll skip them for space, but you need to copy them all)
    
    private function getSummaryData()
    {
        $query = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->join('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->leftjoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $this->business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNull('transaction_sell_lines.parent_sell_line_id');

        $this->applyFilters($query);

        return $query->select([
            DB::raw('COUNT(DISTINCT p.id) as total_products'),
            DB::raw('COUNT(DISTINCT t.id) as total_transactions'),
            DB::raw('COUNT(DISTINCT c.id) as total_customers'),
            DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as total_qty_sold'),
            DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as total_sales_amount'),
            DB::raw('SUM(transaction_sell_lines.item_tax) as total_tax_amount')
        ])->first();
    }

    private function getWeeklySalesData()
    {
        $query = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->leftjoin('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->where('t.business_id', $this->business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNull('transaction_sell_lines.parent_sell_line_id');

        $this->applyFilters($query);

        $start_date = $this->request->start_date ?: now()->startOfMonth()->format('Y-m-d');

        return $query->select([
            DB::raw("CASE 
                WHEN DATEDIFF(t.transaction_date, '$start_date') BETWEEN 0 AND 6 THEN 1
                WHEN DATEDIFF(t.transaction_date, '$start_date') BETWEEN 7 AND 13 THEN 2
                WHEN DATEDIFF(t.transaction_date, '$start_date') BETWEEN 14 AND 20 THEN 3
                WHEN DATEDIFF(t.transaction_date, '$start_date') BETWEEN 21 AND 27 THEN 4
                WHEN DATEDIFF(t.transaction_date, '$start_date') >= 28 THEN 5
                ELSE 1
            END as week_number"),
            DB::raw('SUM((transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_inc_tax) as total_sales_amt'),
            DB::raw('SUM(COALESCE(v.default_purchase_price, transaction_sell_lines.unit_price_inc_tax * 0.7) * (transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0))) as equivalent_purchase_value'),
            DB::raw('(SUM((transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)) * transaction_sell_lines.unit_price_inc_tax) - SUM(COALESCE(v.default_purchase_price, transaction_sell_lines.unit_price_inc_tax * 0.7) * (transaction_sell_lines.quantity - COALESCE(transaction_sell_lines.quantity_returned, 0)))) as profit_earned')
        ])
            ->groupBy('week_number')
            ->having('total_sales_amt', '>', 0)
            ->get();
    }

    private function getStaffPerformanceData()
    {
        $query = Transaction::join('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
            ->join('users as u', 'transactions.created_by', '=', 'u.id')
            ->where('transactions.business_id', $this->business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->whereNull('tsl.parent_sell_line_id')
            ->whereNotNull('transactions.created_by');

        $this->applyStaffFilters($query);

        $start_date = $this->request->start_date ?: now()->startOfMonth()->format('Y-m-d');

        $rawData = $query->select([
            DB::raw("CASE 
                WHEN TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) != '' 
                THEN TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')))
                WHEN TRIM(u.username) != '' 
                THEN TRIM(u.username)
                ELSE CONCAT('User ', u.id)
            END as staff_name"),
            DB::raw("CASE 
                WHEN DATEDIFF(transactions.transaction_date, '$start_date') BETWEEN 0 AND 6 THEN 1
                WHEN DATEDIFF(transactions.transaction_date, '$start_date') BETWEEN 7 AND 13 THEN 2
                WHEN DATEDIFF(transactions.transaction_date, '$start_date') BETWEEN 14 AND 20 THEN 3
                WHEN DATEDIFF(transactions.transaction_date, '$start_date') BETWEEN 21 AND 27 THEN 4
                WHEN DATEDIFF(transactions.transaction_date, '$start_date') >= 28 THEN 5
                ELSE 1
            END as week_number"),
            DB::raw('SUM((tsl.quantity - COALESCE(tsl.quantity_returned, 0)) * tsl.unit_price_inc_tax) as total_sales'),
            DB::raw('SUM((tsl.quantity - COALESCE(tsl.quantity_returned, 0)) * tsl.unit_price_inc_tax * 0.7) as equivalent_purchase_value'),
            DB::raw('SUM((tsl.quantity - COALESCE(tsl.quantity_returned, 0)) * tsl.unit_price_inc_tax * 0.3) as profit_earned')
        ])
            ->groupBy('u.id', 'staff_name', 'week_number')
            ->having('total_sales', '>', 0)
            ->get();

        $staffData = [];
        foreach ($rawData as $item) {
            $staffName = $item->staff_name;
            
            if (!isset($staffData[$staffName])) {
                $staffData[$staffName] = [
                    'weeks' => [0, 0, 0, 0, 0],
                    'total_sales' => 0,
                    'total_purchase' => 0,
                    'total_profit' => 0
                ];
            }
            
            $weekNum = intval($item->week_number);
            $sales = floatval($item->total_sales);
            $purchase = floatval($item->equivalent_purchase_value);
            $profit = floatval($item->profit_earned);
            
            if ($weekNum >= 1 && $weekNum <= 5) {
                $staffData[$staffName]['weeks'][$weekNum - 1] += $sales;
            }
            
            $staffData[$staffName]['total_sales'] += $sales;
            $staffData[$staffName]['total_purchase'] += $purchase;
            $staffData[$staffName]['total_profit'] += $profit;
        }

        return $staffData;
    }

    private function getStockValuationData()
    {
        $query = Product::join('variations as v', 'products.id', '=', 'v.product_id')
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->leftjoin('variation_location_details as vld', 'v.id', '=', 'vld.variation_id')
            ->where('products.business_id', $this->business_id)
            ->where('products.enable_stock', 1);

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('vld.location_id', $permitted_locations);
        }

        if (!empty($this->request->location_id)) {
            $query->where('vld.location_id', $this->request->location_id);
        }

        return $query->select([
            DB::raw('COALESCE(SUM(vld.qty_available * v.default_purchase_price), 0) as current_stock_value_by_purchase_price'),
            DB::raw('COALESCE(SUM(vld.qty_available * v.sell_price_inc_tax), 0) as current_stock_value_by_sales_price'),
            DB::raw('COALESCE(SUM(vld.qty_available * v.sell_price_inc_tax), 0) - COALESCE(SUM(vld.qty_available * v.default_purchase_price), 0) as potential_profit'),
            DB::raw('ROUND(((COALESCE(SUM(vld.qty_available * v.sell_price_inc_tax), 0) - COALESCE(SUM(vld.qty_available * v.default_purchase_price), 0)) / NULLIF(COALESCE(SUM(vld.qty_available * v.sell_price_inc_tax), 0), 0) * 100), 2) as profit_margin')
        ])->first();
    }

    private function getPurchaseSummaryData()
    {
        $query = Transaction::leftjoin('contacts as c', 'transactions.contact_id', '=', 'c.id')
            ->where('transactions.business_id', $this->business_id)
            ->where('transactions.type', 'purchase')
            ->where('transactions.status', 'received');

        $this->applyPurchaseFilters($query);

        return $query->select([
            'transactions.transaction_date as purchase_date',
            'transactions.ref_no as invoice_no',
            'c.name as supplier_name',
            'transactions.final_total as purchase_amt',
            'transactions.discount_amount as purchase_discount',
            'transactions.final_total as purchase_total'
        ])
            ->orderBy('transactions.transaction_date', 'desc')
            ->limit(100)
            ->get();
    }

    private function getProductData()
    {
        $purchase_price_subquery = $this->getPurchasePriceSubquery($this->business_id);

        $query = TransactionSellLine::join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->join('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->join('products as p', 'pv.product_id', '=', 'p.id')
            ->leftjoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftjoin('customer_groups as cg', 'c.customer_group_id', '=', 'cg.id')
            ->leftjoin('categories as cat', 'p.category_id', '=', 'cat.id')
            ->leftjoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
            ->leftjoin('users as created_by', 't.created_by', '=', 'created_by.id')
            ->leftjoin('tax_rates as tr', 'transaction_sell_lines.tax_id', '=', 'tr.id')
            ->leftjoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.business_id', $this->business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNull('transaction_sell_lines.parent_sell_line_id')
            ->with(['transaction.payment_lines']);

        $this->applyFilters($query);

        return $query->select([
            'p.name as product_name',
            'p.type as product_type',
            'p.sku as product_sku',
            'v.sub_sku as variation_sku',
            'pv.name as product_variation',
            'v.name as variation_name',
            'b.name as brand_name',
            'cat.name as category_name',
            'cat.short_code as category_code',
            'p.sub_category_id',
            'p.barcode_type',
            'p.enable_stock as manage_stock',
            'p.alert_quantity',
            'p.expiry_period',
            'p.expiry_period_type',
            'p.tax_type as selling_price_tax_type',
            'v.default_purchase_price as purchase_price_inc_tax',
            'v.dpp_inc_tax as purchase_price_exc_tax',
            'v.sell_price_inc_tax as selling_price',
            DB::raw('ROUND(((v.sell_price_inc_tax - v.default_purchase_price) / v.sell_price_inc_tax * 100), 2) as profit_margin_percent'),
            DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details vld WHERE vld.variation_id = v.id) as current_stock"),
            'p.weight',
            'p.product_custom_field1',
            'p.product_custom_field2',
            'p.product_custom_field3',
            'p.product_custom_field4',
            'p.not_for_selling',
            'p.enable_sr_no as enable_imei_serial',
            'p.product_description',
            'p.image as product_image',
            'c.name as customer_name',
            'c.supplier_business_name',
            'c.contact_id as customer_contact_id',
            'cg.name as customer_group',
            't.id as transaction_id',
            't.invoice_no',
            't.transaction_date',
            't.created_by as user_id',
            DB::raw('(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as sold_qty'),
            'transaction_sell_lines.unit_price_before_discount as unit_price',
            'transaction_sell_lines.unit_price_inc_tax as unit_price_inc_tax',
            'transaction_sell_lines.line_discount_amount as discount_amount',
            'transaction_sell_lines.line_discount_type as discount_type',
            'transaction_sell_lines.item_tax as tax_amount',
            'tr.name as tax_name',
            'tr.amount as tax_rate',
            'u.short_name as unit_name',
            DB::raw('((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as line_total'),
            DB::raw("CONCAT(COALESCE(created_by.first_name, ''), ' ', COALESCE(created_by.last_name, '')) as created_by_name"),
            'bl.name as location_name',
            DB::raw('WEEK(t.transaction_date, 1) as week_number'),
            DB::raw('YEAR(t.transaction_date) as year_number'),
            DB::raw("($purchase_price_subquery) as actual_purchase_price"),
            'transaction_sell_lines.id as sell_line_id',
            'v.id as variation_id'
        ])
            ->orderBy('t.transaction_date', 'desc')
            ->orderBy('t.id', 'desc')
            ->get();
    }

    private function mapProductRow($row)
    {
        $product = $row->product_name;
        if ($row->product_type == 'variable') {
            $product .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
        }

        $sku = $row->product_type == 'variable' ? $row->variation_sku : $row->product_sku;

        $customer = $row->customer_name ?? 'Walk-in Customer';
        if (!empty($row->supplier_business_name)) {
            $customer = $row->supplier_business_name . ', ' . $customer;
        }

        $discount = $row->discount_amount ?? 0;
        if ($row->discount_type == 'percentage') {
            $discount_display = $this->transactionUtil->num_f($discount) . '%';
        } else {
            $discount_display = $this->transactionUtil->num_f($discount, true);
        }

        $payment_method = '';
        if ($row->transaction && $row->transaction->payment_lines) {
            $methods = array_unique($row->transaction->payment_lines->pluck('method')->toArray());
            $count = count($methods);
            if ($count == 1) {
                $payment_method = $this->payment_types[$methods[0]] ?? '';
            } elseif ($count > 1) {
                $payment_method = 'Multiple Methods';
            }
        }

        $actual_purchase_price = $row->actual_purchase_price ?? $row->purchase_price_inc_tax ?? 0;
        $profit = ($row->unit_price_inc_tax - $actual_purchase_price) * $row->sold_qty;
        $profit_margin = $row->unit_price_inc_tax > 0 ?
            (($row->unit_price_inc_tax - $actual_purchase_price) / $row->unit_price_inc_tax) * 100 : 0;

        $stock_turnover = ($row->current_stock ?? 0) > 0 ?
            $row->sold_qty / ($row->current_stock ?? 1) : 0;

        return [
            $product,
            $sku,
            $row->brand_name ?? '',
            $row->unit_name ?? '',
            $row->category_name ?? '',
            $row->sub_category_id ?? '',
            $row->barcode_type ?? '',
            $row->manage_stock ? 1 : 0,
            $row->alert_quantity ?? '',
            $row->expiry_period ?? '',
            $row->expiry_period_type ?? '',
            ($row->tax_name ?? '') . ' (' . ($row->tax_rate ?? 0) . '%)',
            $row->selling_price_tax_type ?? '',
            $row->product_type ?? 'single',
            $row->product_type == 'variable' ? ($row->product_variation ?? '') : '',
            '', // variation_values
            '', // variation_skus
            $this->transactionUtil->num_f($row->purchase_price_inc_tax ?? 0, true),
            $this->transactionUtil->num_f($row->purchase_price_exc_tax ?? 0, true),
            $this->transactionUtil->num_f($row->profit_margin_percent ?? 0, false) . '%',
            $this->transactionUtil->num_f($row->selling_price ?? 0, true),
            $this->transactionUtil->num_f($row->current_stock ?? 0, false, null, true),
            $row->location_name ?? '',
            $row->weight ?? '',
            $row->enable_imei_serial ? 1 : 0,
            $row->not_for_selling ? 1 : 0,
            $row->product_description ?? '',
            $row->product_custom_field1 ?? '',
            $row->product_custom_field2 ?? '',
            $row->product_custom_field3 ?? '',
            $row->product_custom_field4 ?? '',
            $row->product_image ?? '',
            $customer,
            $row->customer_contact_id ?? '',
            $row->customer_group ?? '',
            $row->invoice_no,
            $this->transactionUtil->format_date($row->transaction_date, true),
            'Week ' . ($row->week_number ?? 1) . ' - ' . ($row->year_number ?? date('Y')),
            $this->transactionUtil->num_f($row->sold_qty, false, null, true),
            $this->transactionUtil->num_f($row->unit_price, true),
            $discount_display,
            $this->transactionUtil->num_f($row->tax_amount, true),
            $this->transactionUtil->num_f($row->unit_price_inc_tax, true),
            $this->transactionUtil->num_f($row->line_total, true),
            $payment_method,
            $row->created_by_name ?? '',
            $row->location_name ?? '',
            $this->transactionUtil->num_f($actual_purchase_price, true),
            $this->transactionUtil->num_f($profit, true),
            $this->transactionUtil->num_f($profit_margin, false) . '%',
            $this->transactionUtil->num_f($stock_turnover, false)
        ];
    }

    /**
     * Apply filters to query - flexible version
     */
    private function applyFilters($query, $tableAlias = 't')
    {
        if (!empty($this->request->start_date) && !empty($this->request->end_date)) {
            $query->whereBetween($tableAlias . '.transaction_date', [$this->request->start_date, $this->request->end_date]);
        }

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn($tableAlias . '.location_id', $permitted_locations);
        }

        if (!empty($this->request->location_id)) {
            $query->where($tableAlias . '.location_id', $this->request->location_id);
        }

        if (!empty($this->request->customer_id)) {
            $query->where($tableAlias . '.contact_id', $this->request->customer_id);
        }

        if (!empty($this->request->customer_group_id)) {
            $query->where('c.customer_group_id', $this->request->customer_group_id);
        }

        if (!empty($this->request->category_id)) {
            $query->where('p.category_id', $this->request->category_id);
        }

        if (!empty($this->request->brand_id)) {
            $query->where('p.brand_id', $this->request->brand_id);
        }

        if (!empty($this->request->unit_id)) {
            $query->where('p.unit_id', $this->request->unit_id);
        }

        if (!empty($this->request->user_id)) {
            $query->where($tableAlias . '.created_by', $this->request->user_id);
        }

        if (!empty($this->request->payment_method)) {
            $query->whereHas('transaction.payment_lines', function ($q) {
                $q->where('method', $this->request->payment_method);
            });
        }

        if (!empty($this->request->product_id)) {
            $query->where('p.id', $this->request->product_id);
        }
    }

    /**
     * Apply filters specifically for staff performance
     */
    private function applyStaffFilters($query)
    {
        if (!empty($this->request->start_date) && !empty($this->request->end_date)) {
            $query->whereBetween('transactions.transaction_date', [$this->request->start_date, $this->request->end_date]);
        }

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }

        if (!empty($this->request->location_id)) {
            $query->where('transactions.location_id', $this->request->location_id);
        }

        if (!empty($this->request->user_id)) {
            $query->where('transactions.created_by', $this->request->user_id);
        }
    }

    /**
     * Apply filters specifically for purchase summary
     */
    private function applyPurchaseFilters($query)
    {
        if (!empty($this->request->start_date) && !empty($this->request->end_date)) {
            $query->whereBetween('transactions.transaction_date', [$this->request->start_date, $this->request->end_date]);
        }

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }

        if (!empty($this->request->location_id)) {
            $query->where('transactions.location_id', $this->request->location_id);
        }
    }

    /**
     * Get purchase price subquery for profit calculations
     */
    private function getPurchasePriceSubquery($business_id)
    {
        return "SELECT AVG(purchase_lines.purchase_price_inc_tax) 
                FROM transaction_sell_lines_purchase_lines 
                JOIN purchase_lines ON transaction_sell_lines_purchase_lines.purchase_line_id = purchase_lines.id 
                JOIN transactions as pt ON purchase_lines.transaction_id = pt.id 
                WHERE transaction_sell_lines_purchase_lines.sell_line_id = transaction_sell_lines.id 
                AND pt.business_id = $business_id";
    }
}