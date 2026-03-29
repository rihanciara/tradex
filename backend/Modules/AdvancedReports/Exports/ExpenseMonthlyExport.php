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

class ExpenseMonthlyExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $business_id = $this->filters['business_id'];
        $year = $this->filters['year'];

        // Build query for expense data
        $query = Transaction::leftjoin('expense_categories as ec', 'transactions.expense_category_id', '=', 'ec.id')
            ->where('transactions.business_id', $business_id)
            ->whereIn('transactions.type', ['expense', 'expense_refund'])
            ->whereYear('transactions.transaction_date', $year);

        // Apply filters
        $this->applyFilters($query);

        $monthlyData = $query->select([
            'ec.id as category_id',
            'ec.name as category_name',
            DB::raw('MONTH(transactions.transaction_date) as month'),
            DB::raw('SUM(IF(transactions.type="expense_refund", -1 * transactions.final_total, transactions.final_total)) as monthly_expense')
        ])
            ->groupBy('ec.id', 'ec.name', DB::raw('MONTH(transactions.transaction_date)'))
            ->get();

        // Transform data into the required format
        $expenseData = [];
        foreach ($monthlyData as $data) {
            $categoryId = $data->category_id ?: 0;
            $categoryName = $data->category_name ?: __('report.others');

            if (!isset($expenseData[$categoryId])) {
                $expenseData[$categoryId] = [
                    'category_name' => $categoryName,
                    'jan' => 0, 'feb' => 0, 'mar' => 0, 'apr' => 0,
                    'may' => 0, 'jun' => 0, 'jul' => 0, 'aug' => 0,
                    'sep' => 0, 'oct' => 0, 'nov' => 0, 'dec' => 0,
                    'total_expense' => 0
                ];
            }

            $months = [
                1 => 'jan', 2 => 'feb', 3 => 'mar', 4 => 'apr',
                5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'aug',
                9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dec'
            ];

            $monthKey = $months[$data->month] ?? 'jan';
            $expense = (float)($data->monthly_expense ?? 0);

            $expenseData[$categoryId][$monthKey] = $expense;
            $expenseData[$categoryId]['total_expense'] += $expense;
        }

        return collect(array_values($expenseData));
    }

    public function headings(): array
    {
        return [
            'Category',
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
            'Total Expense'
        ];
    }

    public function map($row): array
    {
        return [
            $row['category_name'] ?? '',
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
            number_format($row['total_expense'] ?? 0, 2)
        ];
    }

    public function title(): string
    {
        return 'Expense Monthly Report ' . ($this->filters['year'] ?? date('Y'));
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['argb' => 'FFFFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE74C3C'], // Red background for expenses
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
            'A:N' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],
            // Currency columns alignment
            'B:N' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                ],
            ],
        ];
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query)
    {
        if (!empty($this->filters['location_id'])) {
            $query->where('transactions.location_id', $this->filters['location_id']);
        }

        if (!empty($this->filters['category_id'])) {
            $query->where('transactions.expense_category_id', $this->filters['category_id']);
        }

        if (!empty($this->filters['created_by'])) {
            $query->where('transactions.created_by', $this->filters['created_by']);
        }

        if (!empty($this->filters['expense_for'])) {
            $query->where('transactions.expense_for', $this->filters['expense_for']);
        }
    }
}