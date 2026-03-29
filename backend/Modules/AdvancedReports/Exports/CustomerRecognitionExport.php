<?php

namespace Modules\AdvancedReports\Exports;

use Modules\AdvancedReports\Utils\CustomerRecognitionUtil;
use Modules\AdvancedReports\Entities\AwardPeriod;
use App\Utils\TransactionUtil;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerRecognitionExport implements WithMultipleSheets
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
        $sheets = [];
        
        // Main rankings sheet
        $sheets[] = new CustomerRankingsSheet($this->business_id, $this->filters, $this->transactionUtil);
        
        // Award history sheet if there are awards
        $sheets[] = new AwardHistorySheet($this->business_id, $this->filters, $this->transactionUtil);
        
        // Engagement summary sheet
        $sheets[] = new EngagementSummarySheet($this->business_id, $this->filters, $this->transactionUtil);
        
        return $sheets;
    }
}

class CustomerRankingsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    protected $business_id;
    protected $filters;
    protected $transactionUtil;

    public function __construct($business_id, $filters, $transactionUtil)
    {
        $this->business_id = $business_id;
        $this->filters = $filters;
        $this->transactionUtil = $transactionUtil;
    }

    public function collection()
    {
        $period_type = $this->filters['period_type'] ?? 'monthly';
        
        // Get period dates
        if (!empty($this->filters['custom_period_start']) && !empty($this->filters['custom_period_end'])) {
            $period_start = $this->filters['custom_period_start'];
            $period_end = $this->filters['custom_period_end'];
        } else {
            $dates = AwardPeriod::getPeriodDates($period_type);
            $period_start = $dates['start'];
            $period_end = $dates['end'];
        }

        // Calculate customer scores
        $scores = CustomerRecognitionUtil::calculateCustomerScores(
            $this->business_id,
            $period_type,
            $period_start,
            $period_end
        );

        return $scores;
    }

    public function headings(): array
    {
        return [
            'Rank',
            'Customer Name',
            'Business Name',
            'Mobile',
            'Email',
            'Registered Date',
            'Sales Total',
            'Transaction Count',
            'Avg Transaction Value',
            'Engagement Points',
            'Final Score',
            'Period Type',
            'Period Start',
            'Period End',
            'First Purchase Date'
        ];
    }

    public function map($row): array
    {
        return [
            $row['rank_position'] ?? 0,
            $row['customer_name'] ?? '',
            $row['customer_business_name'] ?? '',
            $row['customer_mobile'] ?? '',
            '', // Email - would need to join with contacts table
            '', // Registered date - would need to join with contacts table
            $this->transactionUtil->num_f($row['sales_total'] ?? 0, false),
            $row['transaction_count'] ?? 0,
            $this->transactionUtil->num_f($row['avg_transaction_value'] ?? 0, false),
            $row['engagement_points'] ?? 0,
            number_format($row['final_score'] ?? 0, 2),
            ucfirst($row['period_type'] ?? ''),
            $row['period_start'] ?? '',
            $row['period_end'] ?? '',
            '' // First purchase date - would need additional query
        ];
    }

    public function title(): string
    {
        return 'Customer Rankings';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '3498DB']]],
            'A:O' => ['alignment' => ['horizontal' => 'center']],
            'A:A' => ['font' => ['bold' => true]],
            'G:K' => ['numberFormat' => ['formatCode' => '#,##0.00']],
        ];
    }
}

class AwardHistorySheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    protected $business_id;
    protected $filters;
    protected $transactionUtil;

    public function __construct($business_id, $filters, $transactionUtil)
    {
        $this->business_id = $business_id;
        $this->filters = $filters;
        $this->transactionUtil = $transactionUtil;
    }

    public function collection()
    {
        $query = \App\CustomerAward::with(['customer', 'catalogItem', 'awardedBy'])
            ->where('business_id', $this->business_id);

        // Apply period filter if specified
        if (!empty($this->filters['period_type'])) {
            $query->where('period_type', $this->filters['period_type']);
        }

        // Apply date filters
        if (!empty($this->filters['custom_period_start'])) {
            $query->where('period_start', '>=', $this->filters['custom_period_start']);
        }
        if (!empty($this->filters['custom_period_end'])) {
            $query->where('period_end', '<=', $this->filters['custom_period_end']);
        }

        return $query->orderBy('period_start', 'desc')
                    ->orderBy('rank_position', 'asc')
                    ->get();
    }

    public function headings(): array
    {
        return [
            'Period Type',
            'Period Start',
            'Period End',
            'Rank',
            'Customer Name',
            'Business Name',
            'Mobile',
            'Sales Total',
            'Engagement Points',
            'Final Score',
            'Award Status',
            'Award Type',
            'Award Description',
            'Award Value',
            'Awarded Date',
            'Awarded By'
        ];
    }

    public function map($row): array
    {
        return [
            ucfirst($row->period_type),
            $row->period_start ? $row->period_start->format('Y-m-d') : '',
            $row->period_end ? $row->period_end->format('Y-m-d') : '',
            $this->getRankSuffix($row->rank_position),
            $row->customer ? $row->customer->name : '',
            $row->customer ? $row->customer->supplier_business_name : '',
            $row->customer ? $row->customer->mobile : '',
            $this->transactionUtil->num_f($row->sales_total, false),
            $row->engagement_points,
            number_format($row->final_score, 2),
            $row->is_awarded ? 'Awarded' : 'Not Awarded',
            $row->award_type ? ucfirst($row->award_type) : '',
            $row->award_display_name ?? '',
            $this->transactionUtil->num_f($row->gift_monetary_value, false),
            $row->awarded_date ? $row->awarded_date->format('Y-m-d H:i') : '',
            $row->awardedBy ? $row->awardedBy->first_name . ' ' . $row->awardedBy->last_name : ''
        ];
    }

    public function title(): string
    {
        return 'Award History';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '28A745']]],
            'A:P' => ['alignment' => ['horizontal' => 'center']],
            'D:D' => ['font' => ['bold' => true]],
            'H:J' => ['numberFormat' => ['formatCode' => '#,##0.00']],
            'N:N' => ['numberFormat' => ['formatCode' => '#,##0.00']],
        ];
    }

    private function getRankSuffix($rank)
    {
        if ($rank % 100 >= 11 && $rank % 100 <= 13) return $rank . 'th';
        
        switch ($rank % 10) {
            case 1: return $rank . 'st';
            case 2: return $rank . 'nd';
            case 3: return $rank . 'rd';
            default: return $rank . 'th';
        }
    }
}

class EngagementSummarySheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    protected $business_id;
    protected $filters;
    protected $transactionUtil;

    public function __construct($business_id, $filters, $transactionUtil)
    {
        $this->business_id = $business_id;
        $this->filters = $filters;
        $this->transactionUtil = $transactionUtil;
    }

    public function collection()
    {
        $query = \App\CustomerEngagement::with(['customer', 'recordedBy'])
            ->where('business_id', $this->business_id)
            ->where('status', 'verified');

        // Apply date filters if specified
        if (!empty($this->filters['custom_period_start']) && !empty($this->filters['custom_period_end'])) {
            $query->whereBetween('recorded_date', [
                $this->filters['custom_period_start'],
                $this->filters['custom_period_end']
            ]);
        } elseif (!empty($this->filters['period_type'])) {
            // Use current period for the specified type
            $dates = AwardPeriod::getPeriodDates($this->filters['period_type']);
            $query->whereBetween('recorded_date', [$dates['start'], $dates['end']]);
        }

        return $query->orderBy('recorded_date', 'desc')
                    ->orderBy('points', 'desc')
                    ->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Customer Name',
            'Business Name',
            'Mobile',
            'Engagement Type',
            'Platform',
            'Points',
            'Verification Notes',
            'Reference URL',
            'Recorded By',
            'Status'
        ];
    }

    public function map($row): array
    {
        return [
            $row->recorded_date ? $row->recorded_date->format('Y-m-d') : '',
            $row->customer ? $row->customer->name : '',
            $row->customer ? $row->customer->supplier_business_name : '',
            $row->customer ? $row->customer->mobile : '',
            $row->engagement_type_name ?? ucfirst(str_replace('_', ' ', $row->engagement_type)),
            $row->platform ?? '',
            $row->points,
            $row->verification_notes ?? '',
            $row->reference_url ?? '',
            $row->recordedBy ? $row->recordedBy->first_name . ' ' . $row->recordedBy->last_name : '',
            ucfirst($row->status)
        ];
    }

    public function title(): string
    {
        return 'Engagement Summary';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'F39C12']]],
            'A:K' => ['alignment' => ['horizontal' => 'center']],
            'G:G' => ['font' => ['bold' => true], 'numberFormat' => ['formatCode' => '0']],
            'H:H' => ['alignment' => ['horizontal' => 'left'], 'wrapText' => true],
        ];
    }
}