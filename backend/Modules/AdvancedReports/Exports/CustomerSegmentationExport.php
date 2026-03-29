<?php

namespace Modules\AdvancedReports\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CustomerSegmentationExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $data;
    protected $analytics;

    public function __construct($data, $analytics)
    {
        $this->data = $data;
        $this->analytics = $analytics;
    }

    public function array(): array
    {
        $exportData = [];

        // Add summary section
        $exportData[] = ['CUSTOMER SEGMENTATION ANALYSIS REPORT'];
        $exportData[] = ['Generated On', date('Y-m-d H:i:s')];
        $exportData[] = [''];

        // Summary metrics
        $exportData[] = ['SUMMARY METRICS'];
        if (!empty($this->analytics['summary_cards'])) {
            $exportData[] = ['Total Customers', $this->analytics['summary_cards']['total_customers']];
            $exportData[] = ['Total Transactions', $this->analytics['summary_cards']['total_transactions']];
            $exportData[] = ['Total Revenue', number_format($this->analytics['summary_cards']['total_revenue'], 2)];
            $exportData[] = ['Average Order Value', number_format($this->analytics['summary_cards']['avg_order_value'], 2)];
            $exportData[] = ['Repeat Rate', $this->analytics['summary_cards']['repeat_rate'] . '%'];
        }
        $exportData[] = [''];

        // Customer segmentation details header
        $exportData[] = ['CUSTOMER SEGMENTATION DETAILS'];
        $exportData[] = ['Customer ID', 'Customer Name', 'Mobile', 'Email', 'City', 'State', 'Transaction Count', 'Total Spent', 'Avg Order Value', 'Value Tier', 'First Purchase', 'Last Purchase'];

        // Add the detailed customer data
        foreach ($this->data as $row) {
            $exportData[] = [
                $row['Customer ID'] ?? '',
                $row['Customer Name'] ?? '',
                $row['Mobile'] ?? '',
                $row['Email'] ?? '',
                $row['City'] ?? '',
                $row['State'] ?? '',
                $row['Transaction Count'] ?? '',
                $row['Total Spent'] ?? '',
                $row['Avg Order Value'] ?? '',
                $row['Value Tier'] ?? '',
                $row['First Purchase'] ?? '',
                $row['Last Purchase'] ?? ''
            ];
        }

        return $exportData;
    }

    public function headings(): array
    {
        return []; // We'll handle headings in the array method
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            4 => ['font' => ['bold' => true, 'color' => ['rgb' => '0066CC']]],
            11 => ['font' => ['bold' => true, 'color' => ['rgb' => '0066CC']]],
            12 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E6F3FF']]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,  // Customer ID
            'B' => 25,  // Customer Name
            'C' => 15,  // Mobile
            'D' => 25,  // Email
            'E' => 15,  // City
            'F' => 15,  // State
            'G' => 15,  // Transaction Count
            'H' => 15,  // Total Spent
            'I' => 15,  // Avg Order Value
            'J' => 12,  // Value Tier
            'K' => 15,  // First Purchase
            'L' => 15,  // Last Purchase
        ];
    }

    public function title(): string
    {
        return 'Customer Segmentation';
    }
}