<?php

namespace Modules\AdvancedReports\Exports;

use App\Transaction;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CustomerMonthlySalesExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $filters;
    protected $transactionUtil;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
        $this->transactionUtil = new TransactionUtil();
    }

    public function collection()
    {
        $business_id = $this->filters['business_id'];
        $year = $this->filters['year'];

        // Use the same working query from CSV export
        $query = Transaction::join('contacts as c', 'transactions.contact_id', '=', 'c.id')
            ->leftjoin('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
            ->leftjoin('transaction_sell_lines_purchase_lines as tspl', 'tsl.id', '=', 'tspl.sell_line_id')
            ->leftjoin('purchase_lines as pl', 'tspl.purchase_line_id', '=', 'pl.id')
            ->leftjoin('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->whereYear('transactions.transaction_date', $year)
            ->whereNull('tsl.parent_sell_line_id');

        // Skip filters for now since CSV works without them
        // $this->applyFilters($query);

        $monthlyData = $query->select([
            'c.id as customer_id',
            'c.name as customer_name',
            'c.supplier_business_name',
            DB::raw('MONTH(transactions.transaction_date) as month'),
            DB::raw('SUM((tsl.quantity - COALESCE(tsl.quantity_returned, 0)) * tsl.unit_price_inc_tax) as monthly_sales'),
            DB::raw('SUM((tsl.quantity - COALESCE(tsl.quantity_returned, 0)) * COALESCE(pl.purchase_price_inc_tax, v.default_purchase_price, tsl.unit_price_inc_tax * 0.7)) as monthly_purchase_cost')
        ])
            ->groupBy('c.id', 'c.name', 'c.supplier_business_name', DB::raw('MONTH(transactions.transaction_date)'))
            ->get();

        // Same transformation logic as CSV
        $customerData = [];
        foreach ($monthlyData as $data) {
            $customerId = $data->customer_id;

            if (!isset($customerData[$customerId])) {
                $customerName = $data->customer_name ?: 'Unknown';
                if ($data->supplier_business_name) {
                    $customerName = $data->supplier_business_name . ' - ' . $customerName;
                }

                $customerData[$customerId] = [
                    'customer_name' => $customerName,
                    'jan' => 0,
                    'feb' => 0,
                    'mar' => 0,
                    'apr' => 0,
                    'may' => 0,
                    'jun' => 0,
                    'jul' => 0,
                    'aug' => 0,
                    'sep' => 0,
                    'oct' => 0,
                    'nov' => 0,
                    'dec' => 0,
                    'total_sales' => 0,
                    'total_cost' => 0
                ];
            }

            $months = [
                1 => 'jan',
                2 => 'feb',
                3 => 'mar',
                4 => 'apr',
                5 => 'may',
                6 => 'jun',
                7 => 'jul',
                8 => 'aug',
                9 => 'sep',
                10 => 'oct',
                11 => 'nov',
                12 => 'dec'
            ];

            $monthKey = $months[$data->month] ?? 'jan';
            $sales = (float)($data->monthly_sales ?? 0);
            $cost = (float)($data->monthly_purchase_cost ?? 0);

            $customerData[$customerId][$monthKey] = $sales;
            $customerData[$customerId]['total_sales'] += $sales;
            $customerData[$customerId]['total_cost'] += $cost;
        }

        return collect(array_values($customerData));
    }

    public function headings(): array
    {
        return [
            'Customer',
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'May',
            'Jun',
            'Jul',
            'Aug',
            'Sep',
            'Oct',
            'Nov',
            'Dec',
            'Total Sales',
            'Gross Profit ($)',
            'Gross Profit (%)'
        ];
    }

    public function map($row): array
    {
        $total_sales = (float)($row['total_sales'] ?? 0);
        $total_cost = (float)($row['total_cost'] ?? 0);
        $gross_profit = $total_sales - $total_cost;
        $profit_percentage = $total_sales > 0 ? ($gross_profit / $total_sales) * 100 : 0;

        return [
            $row['customer_name'] ?? '',
            number_format($row['jan'] ?? 0, 2),
            number_format($row['feb'] ?? 0, 2),
            number_format($row['mar'] ?? 0, 2),
            number_format($row['apr'] ?? 0, 2),
            number_format($row['may'] ?? 0, 2),
            number_format($row['jun'] ?? 0, 2),
            number_format($row['jul'] ?? 0, 2),
            number_format($row['aug'] ?? 0, 2),
            number_format($row['sep'] ?? 0, 2),
            number_format($row['oct'] ?? 0, 2),
            number_format($row['nov'] ?? 0, 2),
            number_format($row['dec'] ?? 0, 2),
            number_format($total_sales, 2),
            number_format($gross_profit, 2),
            number_format($profit_percentage, 2) . '%'
        ];
    }

    public function title(): string
    {
        return 'Customer Monthly Sales ' . ($this->filters['year'] ?? date('Y'));
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Light blue header row
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['argb' => 'FFFFFFFF'] // White text
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF87CEEB'], // Light blue background
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
            // Data rows
            'A:P' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],
            // Currency columns alignment
            'B:P' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                ],
            ],
        ];
    }
}
