<?php

namespace Modules\AdvancedReports\Exports;

use App\Utils\TransactionUtil;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OperationsSummaryReportExport implements WithMultipleSheets
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

    public function sheets(): array
    {
        return [
            new SalesSummarySheet($this->business_id, $this->filters),
            new PurchaseSummarySheet($this->business_id, $this->filters),
            new ExpenseSummarySheet($this->business_id, $this->filters),
            new PaymentMethodsSheet($this->business_id, $this->filters),
        ];
    }
}

class SalesSummarySheet implements FromCollection, WithHeadings, WithTitle, WithStyles
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
        $start_date = $this->filters['start_date'] ?? \Carbon::now()->format('Y-m-d');
        $end_date = $this->filters['end_date'] ?? \Carbon::now()->format('Y-m-d');
        $location_id = $this->filters['location_id'] ?? null;

        $sell_details = $this->transactionUtil->getSellTotals(
            $this->business_id,
            $start_date,
            $end_date,
            $location_id
        );

        return collect([
            [
                'metric' => 'Total Sales (Inc. Tax)',
                'value' => $this->transactionUtil->num_f($sell_details['total_sell_inc_tax'] ?? 0, true)
            ],
            [
                'metric' => 'Total Sales (Exc. Tax)',
                'value' => $this->transactionUtil->num_f($sell_details['total_sell_exc_tax'] ?? 0, true)
            ],
            [
                'metric' => 'Total Tax',
                'value' => $this->transactionUtil->num_f($sell_details['total_tax'] ?? 0, true)
            ],
            [
                'metric' => 'Total Discount',
                'value' => $this->transactionUtil->num_f($sell_details['total_discount'] ?? 0, true)
            ],
            [
                'metric' => 'Invoice Due',
                'value' => $this->transactionUtil->num_f($sell_details['invoice_due'] ?? 0, true)
            ],
        ]);
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function title(): string
    {
        return 'Sales Summary';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

class PurchaseSummarySheet implements FromCollection, WithHeadings, WithTitle, WithStyles
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
        $start_date = $this->filters['start_date'] ?? \Carbon::now()->format('Y-m-d');
        $end_date = $this->filters['end_date'] ?? \Carbon::now()->format('Y-m-d');
        $location_id = $this->filters['location_id'] ?? null;

        $purchase_details = $this->transactionUtil->getPurchaseTotals(
            $this->business_id,
            $start_date,
            $end_date,
            $location_id
        );

        return collect([
            [
                'metric' => 'Total Purchases (Inc. Tax)',
                'value' => $this->transactionUtil->num_f($purchase_details['total_purchase_inc_tax'] ?? 0, true)
            ],
            [
                'metric' => 'Total Purchases (Exc. Tax)',
                'value' => $this->transactionUtil->num_f($purchase_details['total_purchase_exc_tax'] ?? 0, true)
            ],
            [
                'metric' => 'Total Tax',
                'value' => $this->transactionUtil->num_f($purchase_details['total_tax'] ?? 0, true)
            ],
            [
                'metric' => 'Purchase Due',
                'value' => $this->transactionUtil->num_f($purchase_details['purchase_due'] ?? 0, true)
            ],
        ]);
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function title(): string
    {
        return 'Purchase Summary';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

class ExpenseSummarySheet implements FromCollection, WithHeadings, WithTitle, WithStyles
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
        $filters = [
            'start_date' => $this->filters['start_date'] ?? \Carbon::now()->format('Y-m-d'),
            'end_date' => $this->filters['end_date'] ?? \Carbon::now()->format('Y-m-d'),
            'location_id' => $this->filters['location_id'] ?? null
        ];

        $expenses = $this->transactionUtil->getExpenseReport($this->business_id, $filters);

        $data = collect();
        $total_expense = 0;

        foreach ($expenses as $expense) {
            $data->push([
                'category' => !empty($expense->category) ? $expense->category : 'Others',
                'amount' => $this->transactionUtil->num_f($expense->total_expense, true)
            ]);
            $total_expense += $expense->total_expense;
        }

        $data->push([
            'category' => 'TOTAL',
            'amount' => $this->transactionUtil->num_f($total_expense, true)
        ]);

        return $data;
    }

    public function headings(): array
    {
        return ['Category', 'Amount'];
    }

    public function title(): string
    {
        return 'Expense Summary';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

class PaymentMethodsSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
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
        // This would be implemented with the payment methods breakdown logic
        // from the controller
        return collect([
            ['method' => 'Cash', 'amount' => '0.00'],
            ['method' => 'Card', 'amount' => '0.00'],
            ['method' => 'Bank Transfer', 'amount' => '0.00'],
        ]);
    }

    public function headings(): array
    {
        return ['Payment Method', 'Amount'];
    }

    public function title(): string
    {
        return 'Payment Methods';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
