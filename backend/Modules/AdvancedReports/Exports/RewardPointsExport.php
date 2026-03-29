<?php

namespace Modules\AdvancedReports\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use App\Contact;
use App\Transaction;
use DB;
use Carbon\Carbon;

class RewardPointsExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
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
        $exportData = collect();
        $export_type = $this->filters['export_type'] ?? 'customer_summary';
        $start_date = $this->filters['start_date'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $end_date = $this->filters['end_date'] ?? Carbon::now()->endOfMonth()->format('Y-m-d');

        if ($export_type === 'customer_summary') {
            $customers = $this->getCustomerSummaryData($start_date, $end_date);

            foreach ($customers as $customer) {
                $exportData->push([
                    'customer_name' => $customer['customer_name'],
                    'customer_mobile' => $customer['customer_mobile'] ?? '',
                    'total_earned_points' => $customer['total_earned_points'],
                    'total_redeemed_points' => $customer['total_redeemed_points'],
                    'current_balance' => $customer['current_balance'],
                    'liability_amount' => $customer['liability_amount'],
                    'total_transactions' => $customer['total_transactions'],
                    'redemption_transactions' => $customer['redemption_transactions'],
                    'first_earning_date' => $customer['first_earning_date'] ?? '',
                    'last_activity_date' => $customer['last_activity_date'] ?? '',
                    'status' => ucfirst($customer['status'])
                ]);
            }
        } else {
            $transactions = $this->getTransactionDetailsData($start_date, $end_date);

            foreach ($transactions as $transaction) {
                $exportData->push([
                    'invoice_no' => $transaction['invoice_no'],
                    'transaction_date' => $transaction['transaction_date'],
                    'customer_name' => $transaction['customer_name'],
                    'invoice_amount' => $transaction['invoice_amount'],
                    'points_earned' => $transaction['points_earned'],
                    'points_redeemed' => $transaction['points_redeemed'],
                    'points_value_redeemed' => $transaction['points_value_redeemed'],
                    'final_payable' => $transaction['final_payable'],
                    'transaction_type' => ucfirst($transaction['transaction_type'])
                ]);
            }
        }

        return $exportData;
    }

    public function headings(): array
    {
        $export_type = $this->filters['export_type'] ?? 'customer_summary';

        if ($export_type === 'customer_summary') {
            return [
                'Customer Name',
                'Mobile',
                'Total Earned Points',
                'Total Redeemed Points',
                'Current Balance',
                'Liability Amount (BDT)',
                'Total Transactions',
                'Redemption Transactions',
                'First Earning Date',
                'Last Activity Date',
                'Status'
            ];
        } else {
            return [
                'Invoice No',
                'Date',
                'Customer Name',
                'Invoice Amount',
                'Points Earned',
                'Points Redeemed',
                'Points Value Redeemed',
                'Final Payable',
                'Transaction Type'
            ];
        }
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 20,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 15,
        ];
    }

    public function title(): string
    {
        $export_type = $this->filters['export_type'] ?? 'customer_summary';
        return $export_type === 'customer_summary' ? 'Reward Points Customer Summary' : 'Reward Points Transaction Details';
    }

    private function getCustomerSummaryData($start_date, $end_date)
    {
        $query = DB::table('contacts as c')
            ->select([
                'c.id as customer_id',
                'c.name as customer_name',
                'c.mobile as customer_mobile',
                'c.created_at as customer_registered_date',
                'c.total_rp as current_balance',
                'c.total_rp_used as total_redeemed_points',
                'c.total_rp_expired as total_expired_points',
                DB::raw('(c.total_rp + c.total_rp_used) as total_earned_points'),
                DB::raw('COALESCE(period_earned.period_earned, 0) as period_earned_points'),
                DB::raw('COALESCE(period_redeemed.period_redeemed, 0) as period_redeemed_points'),
                DB::raw('COALESCE(period_earned.transaction_count, 0) as total_transactions'),
                DB::raw('COALESCE(period_redeemed.redemption_count, 0) as redemption_transactions'),
                DB::raw('COALESCE(period_earned.first_purchase, NULL) as first_earning_date'),
                DB::raw('COALESCE(period_earned.last_purchase, NULL) as last_activity_date')
            ])
            ->leftJoin(DB::raw('(
                SELECT
                    contact_id,
                    SUM(rp_earned) as period_earned,
                    COUNT(*) as transaction_count,
                    MIN(transaction_date) as first_purchase,
                    MAX(transaction_date) as last_purchase
                FROM transactions
                WHERE business_id = ' . $this->business_id . '
                    AND type = "sell"
                    AND status = "final"
                    AND contact_id IS NOT NULL
                    AND transaction_date BETWEEN "' . $start_date . '" AND "' . $end_date . '"
                    AND rp_earned > 0
                GROUP BY contact_id
            ) as period_earned'), 'c.id', '=', 'period_earned.contact_id')
            ->leftJoin(DB::raw('(
                SELECT
                    contact_id,
                    SUM(rp_redeemed) as period_redeemed,
                    COUNT(*) as redemption_count
                FROM transactions
                WHERE business_id = ' . $this->business_id . '
                    AND type = "sell"
                    AND status = "final"
                    AND rp_redeemed > 0
                    AND contact_id IS NOT NULL
                    AND transaction_date BETWEEN "' . $start_date . '" AND "' . $end_date . '"
                GROUP BY contact_id
            ) as period_redeemed'), 'c.id', '=', 'period_redeemed.contact_id')
            ->where('c.business_id', $this->business_id)
            ->where('c.type', 'customer')
            ->havingRaw('(total_earned_points > 0 OR total_redeemed_points > 0)')
            ->get();

        $data = [];
        foreach ($customers as $customer) {
            $liabilityAmount = $customer->current_balance * 1.0;

            $data[] = [
                'customer_id' => $customer->customer_id,
                'customer_name' => $customer->customer_name,
                'customer_mobile' => $customer->customer_mobile,
                'total_earned_points' => (int) $customer->total_earned_points,
                'total_redeemed_points' => (int) $customer->total_redeemed_points,
                'current_balance' => (int) $customer->current_balance,
                'liability_amount' => number_format($liabilityAmount, 2),
                'total_transactions' => (int) $customer->total_transactions,
                'redemption_transactions' => (int) $customer->redemption_transactions,
                'first_earning_date' => $customer->first_earning_date,
                'last_activity_date' => $customer->last_activity_date,
                'status' => $this->getCustomerStatus($customer->current_balance, $customer->last_activity_date)
            ];
        }

        return $data;
    }

    private function getTransactionDetailsData($start_date, $end_date)
    {
        $query = DB::table('transactions as t')
            ->select([
                't.id as transaction_id',
                't.invoice_no',
                't.transaction_date',
                'c.name as customer_name',
                'c.id as customer_id',
                't.final_total as invoice_amount',
                't.rp_earned as points_earned',
                't.rp_redeemed as points_redeemed',
                't.rp_redeemed_amount as points_value_redeemed',
                DB::raw('(t.final_total - COALESCE(t.rp_redeemed_amount, 0)) as final_payable')
            ])
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.business_id', $this->business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('c.type', 'customer')
            ->whereBetween('t.transaction_date', [$start_date, $end_date])
            ->whereNotNull('t.contact_id')
            ->where(function($q) {
                $q->where('t.rp_earned', '>', 0)
                  ->orWhere('t.rp_redeemed', '>', 0);
            })
            ->orderBy('t.transaction_date', 'desc')
            ->get();

        $data = [];
        foreach ($transactions as $transaction) {
            $data[] = [
                'transaction_id' => $transaction->transaction_id,
                'invoice_no' => $transaction->invoice_no,
                'transaction_date' => Carbon::parse($transaction->transaction_date)->format('Y-m-d H:i'),
                'customer_name' => $transaction->customer_name,
                'customer_id' => $transaction->customer_id,
                'invoice_amount' => number_format($transaction->invoice_amount, 2),
                'points_earned' => (int) $transaction->points_earned,
                'points_redeemed' => (int) $transaction->points_redeemed,
                'points_value_redeemed' => number_format($transaction->points_value_redeemed, 2),
                'final_payable' => number_format($transaction->final_payable, 2),
                'transaction_type' => $this->getTransactionType($transaction->points_earned, $transaction->points_redeemed)
            ];
        }

        return $data;
    }

    private function getCustomerStatus($balance, $lastActivity)
    {
        if ($balance <= 0) return 'inactive';

        $daysSinceActivity = Carbon::parse($lastActivity)->diffInDays(Carbon::now());

        if ($daysSinceActivity <= 30) return 'active';
        if ($daysSinceActivity <= 90) return 'moderate';
        return 'inactive';
    }

    private function getTransactionType($pointsEarned, $pointsRedeemed)
    {
        if ($pointsEarned > 0 && $pointsRedeemed > 0) return 'both';
        if ($pointsEarned > 0) return 'earned';
        if ($pointsRedeemed > 0) return 'redeemed';
        return 'none';
    }
}