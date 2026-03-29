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
use App\Business;
use DB;

class BusinessAnalyticsExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
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

        $businesses = Business::select([
            'business.*',
            DB::raw('(SELECT COUNT(*) FROM users WHERE business_id = business.id) as users_count'),
            DB::raw('(SELECT COUNT(*) FROM products WHERE business_id = business.id) as products_count'),
            DB::raw('(SELECT COUNT(*) FROM transactions WHERE business_id = business.id AND type = "sell") as sales_count'),
            DB::raw('(SELECT COUNT(*) FROM transactions WHERE business_id = business.id AND type = "purchase") as purchase_count'),
            DB::raw('(SELECT COUNT(*) FROM contacts WHERE business_id = business.id) as contacts_count'),
            DB::raw('(SELECT COUNT(*) FROM business_locations WHERE business_id = business.id) as locations_count')
        ])->get();

        foreach ($businesses as $business) {
            $totalRecords = $this->calculateTotalRecords($business->id);
            $actualSize = $this->calculateActualSize($business->id);
            $topTables = $this->getTopTables($business->id);
            $topTablesText = implode(', ', array_map(function($table) {
                return $table['name'] . ' (' . number_format($table['count']) . ')';
            }, $topTables));

            $exportData->push([
                'business_name' => $business->name,
                'created_at' => $business->created_at->format('Y-m-d'),
                'users_count' => (int) $business->users_count,
                'products_count' => (int) $business->products_count,
                'sales_count' => (int) $business->sales_count,
                'purchase_count' => (int) $business->purchase_count,
                'contacts_count' => (int) $business->contacts_count,
                'locations_count' => (int) $business->locations_count,
                'total_records' => $totalRecords,
                'actual_size_mb' => number_format($actualSize, 2),
                'top_tables' => $topTablesText
            ]);
        }

        return $exportData;
    }

    public function headings(): array
    {
        return [
            'Business Name',
            'Created At',
            'Users',
            'Products',
            'Sales',
            'Purchases',
            'Contacts',
            'Locations',
            'Total Records',
            'Size (MB)',
            'Top Tables'
        ];
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
            'A' => 30,
            'B' => 15,
            'C' => 10,
            'D' => 12,
            'E' => 10,
            'F' => 12,
            'G' => 12,
            'H' => 12,
            'I' => 15,
            'J' => 12,
            'K' => 40,
        ];
    }

    public function title(): string
    {
        return 'Business Analytics';
    }

    private function calculateTotalRecords($businessId)
    {
        $tables = [
            'users' => 'business_id',
            'products' => 'business_id',
            'transactions' => 'business_id',
            'transaction_sell_lines' => 'business_id',
            'contacts' => 'business_id',
            'business_locations' => 'business_id',
            'activity_log' => 'business_id',
            'purchase_lines' => 'business_id'
        ];

        $totalRecords = 0;

        foreach ($tables as $table => $column) {
            try {
                $count = DB::table($table)->where($column, $businessId)->count();
                $totalRecords += $count;
            } catch (\Exception $e) {
                // Skip tables that might not exist
                continue;
            }
        }

        return $totalRecords;
    }

    private function calculateActualSize($businessId)
    {
        // This is an approximation - in a real scenario you'd calculate based on actual row sizes
        $totalRecords = $this->calculateTotalRecords($businessId);

        // Estimate average row size (in bytes) - this varies by table but 512 bytes is reasonable average
        $avgRowSize = 512;

        // Convert to MB
        $sizeInMB = ($totalRecords * $avgRowSize) / (1024 * 1024);

        return $sizeInMB;
    }

    private function getTopTables($businessId)
    {
        $tables = [
            'Activity Log' => ['activity_log', 'business_id'],
            'Transactions' => ['transactions', 'business_id'],
            'Transaction Lines' => ['transaction_sell_lines', 'business_id'],
            'Purchase Lines' => ['purchase_lines', 'business_id'],
            'Products' => ['products', 'business_id'],
            'Contacts' => ['contacts', 'business_id']
        ];

        $tableCounts = [];

        foreach ($tables as $name => $config) {
            try {
                $count = DB::table($config[0])->where($config[1], $businessId)->count();
                if ($count > 0) {
                    $tableCounts[] = [
                        'name' => $name,
                        'count' => $count
                    ];
                }
            } catch (\Exception $e) {
                // Skip tables that might not exist
                continue;
            }
        }

        // Sort by count descending and take top 5
        usort($tableCounts, function($a, $b) {
            return $b['count'] - $a['count'];
        });

        return array_slice($tableCounts, 0, 5);
    }
}