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

class CustomerBehaviorExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
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

        // Add basic analysis data
        $exportData->push([
            'export_type' => 'Basic Info',
            'description' => 'Business ID',
            'value' => $this->business_id,
            'start_date' => $this->filters['start_date'] ?? '',
            'end_date' => $this->filters['end_date'] ?? '',
            'generated_on' => date('Y-m-d H:i:s')
        ]);

        $exportData->push([
            'export_type' => 'Date Range',
            'description' => 'Analysis Period',
            'value' => ($this->filters['start_date'] ?? '') . ' to ' . ($this->filters['end_date'] ?? ''),
            'start_date' => $this->filters['start_date'] ?? '',
            'end_date' => $this->filters['end_date'] ?? '',
            'generated_on' => date('Y-m-d H:i:s')
        ]);

        if (!empty($this->filters['location_id'])) {
            $exportData->push([
                'export_type' => 'Location Filter',
                'description' => 'Location ID',
                'value' => $this->filters['location_id'],
                'start_date' => $this->filters['start_date'] ?? '',
                'end_date' => $this->filters['end_date'] ?? '',
                'generated_on' => date('Y-m-d H:i:s')
            ]);
        }

        if (!empty($this->filters['customer_id'])) {
            $exportData->push([
                'export_type' => 'Customer Filter',
                'description' => 'Customer ID',
                'value' => $this->filters['customer_id'],
                'start_date' => $this->filters['start_date'] ?? '',
                'end_date' => $this->filters['end_date'] ?? '',
                'generated_on' => date('Y-m-d H:i:s')
            ]);
        }

        $exportData->push([
            'export_type' => 'Export Status',
            'description' => 'Status',
            'value' => 'Successfully Generated',
            'start_date' => $this->filters['start_date'] ?? '',
            'end_date' => $this->filters['end_date'] ?? '',
            'generated_on' => date('Y-m-d H:i:s')
        ]);

        return $exportData;
    }

    public function headings(): array
    {
        return [
            'Export Type',
            'Description',
            'Value',
            'Start Date',
            'End Date',
            'Generated On'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            4 => ['font' => ['bold' => true, 'color' => ['rgb' => '0066CC']]],
            11 => ['font' => ['bold' => true, 'color' => ['rgb' => '0066CC']]],
            17 => ['font' => ['bold' => true, 'color' => ['rgb' => '0066CC']]],
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
        ];
    }

    public function title(): string
    {
        return 'Customer Behavior Analysis';
    }
}