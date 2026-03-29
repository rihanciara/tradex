<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Business;
use App\User;
use App\Product;
use App\Transaction;
use App\Contact;
use App\BusinessLocation;
use DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Modules\AdvancedReports\Exports\BusinessAnalyticsExport;

class BusinessAnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        // Check if user is superadmin (using the same check as middleware)
        $administrator_list = config('constants.administrator_usernames');
        $is_superadmin = !empty(auth()->user()) && 
                        in_array(strtolower(auth()->user()->username), explode(',', strtolower($administrator_list)));
        
        if (!$is_superadmin) {
            abort(403, 'Unauthorized action. Superadmin access required.');
        }

        return view('advancedreports::business-analytics.index');
    }

    public function getBusinessAnalyticsData(Request $request)
    {
        // Check if user is superadmin
        $administrator_list = config('constants.administrator_usernames');
        $is_superadmin = !empty(auth()->user()) && 
                        in_array(strtolower(auth()->user()->username), explode(',', strtolower($administrator_list)));
        
        if (!$is_superadmin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            // Get all businesses with their analytics
            $businesses = Business::select([
                'business.*',
                DB::raw('(SELECT COUNT(*) FROM users WHERE business_id = business.id) as users_count'),
                DB::raw('(SELECT COUNT(*) FROM products WHERE business_id = business.id) as products_count'),
                DB::raw('(SELECT COUNT(*) FROM transactions WHERE business_id = business.id AND type = "sell") as sales_count'),
                DB::raw('(SELECT COUNT(*) FROM transactions WHERE business_id = business.id AND type = "purchase") as purchase_count'),
                DB::raw('(SELECT COUNT(*) FROM contacts WHERE business_id = business.id) as contacts_count'),
                DB::raw('(SELECT COUNT(*) FROM business_locations WHERE business_id = business.id) as locations_count')
            ])->get();

            $data = [];

            foreach ($businesses as $business) {
                // Calculate total records for this business
                $totalRecords = $this->calculateTotalRecords($business->id);
                
                // Calculate actual database size for this business
                $actualSize = $this->calculateActualSize($business->id);
                
                // Get top tables for this business
                $topTables = $this->getTopTables($business->id);
                
                // Determine status colors based on thresholds
                $recordsStatus = $this->getStatusColor($totalRecords, [50000, 100000]); // Green < 50k, Orange 50k-100k, Red > 100k
                $sizeStatus = $this->getStatusColor($actualSize, [100, 500]); // Green < 100MB, Orange 100-500MB, Red > 500MB
                
                $data[] = [
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
                    'top_tables' => $topTables,
                    'records_status' => $recordsStatus,
                    'size_status' => $sizeStatus,
                    'top_tables_status' => count($topTables) > 5 ? 'warning' : 'success'
                ];
            }

            return response()->json(['data' => $data]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load business analytics: ' . $e->getMessage()], 500);
        }
    }

    public function getSummary(Request $request)
    {
        // Check if user is superadmin
        $administrator_list = config('constants.administrator_usernames');
        $is_superadmin = !empty(auth()->user()) && 
                        in_array(strtolower(auth()->user()->username), explode(',', strtolower($administrator_list)));
        
        if (!$is_superadmin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $totalBusinesses = Business::count();
            
            // Calculate total database size
            $totalDbSize = $this->getTotalDatabaseSize();
            
            // Calculate total records across all businesses
            $totalRecords = $this->getTotalRecords();
            
            // Calculate average business size
            $avgBusinessSize = $totalBusinesses > 0 ? $totalDbSize / $totalBusinesses : 0;

            return response()->json([
                'total_businesses' => $totalBusinesses,
                'actual_db_size' => number_format($totalDbSize / 1024, 2) . ' GB', // Convert MB to GB
                'total_records' => number_format($totalRecords),
                'avg_business_size' => number_format($avgBusinessSize, 2) . ' MB'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load summary: ' . $e->getMessage()], 500);
        }
    }

    public function export(Request $request)
    {
        // Check if user is superadmin
        $administrator_list = config('constants.administrator_usernames');
        $is_superadmin = !empty(auth()->user()) && 
                        in_array(strtolower(auth()->user()->username), explode(',', strtolower($administrator_list)));
        
        if (!$is_superadmin) {
            abort(403, 'Unauthorized');
        }

        try {
            // Prepare filters
            $filters = [];

            $fileName = 'business-analytics-' . Carbon::now()->format('Y-m-d-H-i-s') . '.xlsx';

            return Excel::download(new BusinessAnalyticsExport(null, $filters), $fileName);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
        }
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

    private function getTotalDatabaseSize()
    {
        try {
            // Get database name
            $database = config('database.connections.mysql.database');
            
            // Query to get total database size in MB
            $result = DB::select("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = ?
            ", [$database]);
            
            return $result[0]->size_mb ?? 0;
        } catch (\Exception $e) {
            // Fallback: estimate based on record counts
            return Business::count() * 50; // Rough estimate of 50MB per business
        }
    }

    private function getTotalRecords()
    {
        $totalRecords = 0;
        
        $mainTables = [
            'users',
            'products', 
            'transactions',
            'contacts',
            'business_locations'
        ];

        foreach ($mainTables as $table) {
            try {
                $count = DB::table($table)->count();
                $totalRecords += $count;
            } catch (\Exception $e) {
                continue;
            }
        }

        return $totalRecords;
    }

    private function getStatusColor($value, $thresholds)
    {
        if ($value < $thresholds[0]) {
            return 'success'; // Green
        } elseif ($value < $thresholds[1]) {
            return 'warning'; // Orange
        } else {
            return 'danger'; // Red
        }
    }
}