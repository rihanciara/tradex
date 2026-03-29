<?php

namespace Modules\AdvancedReports\Exports;

use App\Utils\TransactionUtil;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DailySummaryReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
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
        $start_date = $this->filters['start_date'] ?? \Carbon::now()->subDays(30)->format('Y-m-d');
        $end_date = $this->filters['end_date'] ?? \Carbon::now()->format('Y-m-d');
        $location_id = $this->filters['location_id'] ?? null;

        // Create date range array
        $dates = [];
        $current = \Carbon::parse($start_date);
        $end = \Carbon::parse($end_date);

        while ($current <= $end) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        $daily_data = collect();

        foreach ($dates as $date) {
            // Get sales for this date
            $sales = $this->getDailySales($date, $location_id);

            // Get purchases for this date
            $purchases = $this->getDailyPurchases($date, $location_id);

            // Get expenses for this date
            $expenses = $this->getDailyExpenses($date, $location_id);

            $daily_data->push([
                'date' => $date,
                'formatted_date' => \Carbon::parse($date)->format('d-m-Y'),
                'day_name' => \Carbon::parse($date)->format('l'),
                'total_sales' => $sales['total'] ?? 0,
                'sales_count' => $sales['count'] ?? 0,
                'total_purchases' => $purchases['total'] ?? 0,
                'purchases_count' => $purchases['count'] ?? 0,
                'total_expenses' => $expenses['total'] ?? 0,
                'expenses_count' => $expenses['count'] ?? 0,
                'net_profit' => ($sales['total'] ?? 0) - ($purchases['total'] ?? 0) - ($expenses['total'] ?? 0)
            ]);
        }

        return $daily_data;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Day',
            'Sales Count',
            'Total Sales',
            'Purchase Count',
            'Total Purchases',
            'Expense Count',
            'Total Expenses',
            'Net Profit'
        ];
    }

    public function map($row): array
    {
        return [
            $row['formatted_date'],
            $row['day_name'],
            $row['sales_count'],
            $this->transactionUtil->num_f($row['total_sales'], false),
            $row['purchases_count'],
            $this->transactionUtil->num_f($row['total_purchases'], false),
            $row['expenses_count'],
            $this->transactionUtil->num_f($row['total_expenses'], false),
            $this->transactionUtil->num_f($row['net_profit'], false)
        ];
    }

    public function title(): string
    {
        return 'Daily Summary Report';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function getDailySales($date, $location_id = null)
    {
        $sales_details = $this->transactionUtil->getSellTotals($this->business_id, $date, $date, $location_id);

        return [
            'total' => $sales_details['total_sell_inc_tax'] ?? 0,
            'count' => $sales_details['total_invoice'] ?? 0
        ];
    }

    private function getDailyPurchases($date, $location_id = null)
    {
        $purchase_details = $this->transactionUtil->getPurchaseTotals($this->business_id, $date, $date, $location_id);

        return [
            'total' => $purchase_details['total_purchase_inc_tax'] ?? 0,
            'count' => $purchase_details['total_purchase'] ?? 0
        ];
    }

    private function getDailyExpenses($date, $location_id = null)
    {
        $filters = [
            'start_date' => $date,
            'end_date' => $date,
            'location_id' => $location_id
        ];

        $expenses = $this->transactionUtil->getExpenseReport($this->business_id, $filters);
        $total_expense = 0;
        $count = 0;

        foreach ($expenses as $expense) {
            $total_expense += $expense->total_expense;
            $count++;
        }

        return [
            'total' => $total_expense,
            'count' => $count
        ];
    }
}

class DailyReportExport implements FromCollection, WithHeadings, WithTitle, WithStyles
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
        $end_date = $this->filters['end_date'] ?? \Carbon::now()->format('Y-m-d');
        $location_id = $this->filters['location_id'] ?? null;

        // Get all the data that would be shown in the daily report
        $sell_details = $this->transactionUtil->getSellTotals($this->business_id, $end_date, $end_date, $location_id);
        $purchase_details = $this->transactionUtil->getPurchaseTotals($this->business_id, $end_date, $end_date, $location_id);

        // Get expense details
        $expense_filters = [
            'start_date' => $end_date,
            'end_date' => $end_date,
            'location_id' => $location_id
        ];
        $expenses = $this->transactionUtil->getExpenseReport($this->business_id, $expense_filters);
        $today_expenses = 0;
        foreach ($expenses as $expense) {
            $today_expenses += $expense->total_expense;
        }

        return collect([
            ['Metric', 'Value'],
            ['Report Date', \Carbon::parse($end_date)->format('d-m-Y')],
            ['', ''],
            ['SALES SUMMARY', ''],
            ['Today Sales (Inc. Tax)', $this->transactionUtil->num_f($sell_details['total_sell_inc_tax'] ?? 0, true)],
            ['Today Sales (Exc. Tax)', $this->transactionUtil->num_f($sell_details['total_sell_exc_tax'] ?? 0, true)],
            ['Total Tax', $this->transactionUtil->num_f($sell_details['total_tax'] ?? 0, true)],
            ['Total Discount', $this->transactionUtil->num_f($sell_details['total_discount'] ?? 0, true)],
            ['Invoice Due', $this->transactionUtil->num_f($sell_details['invoice_due'] ?? 0, true)],
            ['', ''],
            ['PURCHASE SUMMARY', ''],
            ['Today Purchases (Inc. Tax)', $this->transactionUtil->num_f($purchase_details['total_purchase_inc_tax'] ?? 0, true)],
            ['Today Purchases (Exc. Tax)', $this->transactionUtil->num_f($purchase_details['total_purchase_exc_tax'] ?? 0, true)],
            ['Purchase Due', $this->transactionUtil->num_f($purchase_details['purchase_due'] ?? 0, true)],
            ['', ''],
            ['EXPENSE SUMMARY', ''],
            ['Today Expenses', $this->transactionUtil->num_f($today_expenses, true)],
            ['', ''],
            ['PROFIT ANALYSIS', ''],
            ['Today Profit', $this->transactionUtil->num_f(($sell_details['total_sell_inc_tax'] ?? 0) - ($purchase_details['total_purchase_inc_tax'] ?? 0) - $today_expenses, true)],
            ['Gross Profit Margin', number_format((($sell_details['total_sell_inc_tax'] ?? 0) > 0) ? ((($sell_details['total_sell_inc_tax'] ?? 0) - ($purchase_details['total_purchase_inc_tax'] ?? 0)) / ($sell_details['total_sell_inc_tax'] ?? 0) * 100) : 0, 2) . '%'],
        ]);
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Daily Report';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
            11 => ['font' => ['bold' => true]],
            16 => ['font' => ['bold' => true]],
            19 => ['font' => ['bold' => true]],
        ];
    }
}
