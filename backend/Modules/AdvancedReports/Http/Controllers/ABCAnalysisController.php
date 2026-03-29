<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Brands;
use App\Product;
use DB;

class ABCAnalysisController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.abc_analysis')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);
        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::where('business_id', $business_id)->pluck('name', 'id');

        return view('advancedreports::abc-analysis.index', compact(
            'business_locations',
            'categories', 
            'brands'
        ));
    }

    public function analytics(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.abc_analysis')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $business_id = request()->session()->get('user.business_id');
        $location_ids = $request->get('location_ids', []);
        $category_ids = $request->get('category_ids', []);
        $brand_ids = $request->get('brand_ids', []);
        $analysis_type = $request->get('analysis_type', 'value'); // value, sales, or hybrid

        $data = [
            'abc_classification' => $this->getABCClassification($business_id, $location_ids, $category_ids, $brand_ids, $analysis_type),
            'abc_summary' => $this->getABCSummary($business_id, $location_ids, $category_ids, $brand_ids, $analysis_type),
            'revenue_contribution' => $this->getRevenueContributionAnalysis($business_id, $location_ids, $category_ids, $brand_ids, $analysis_type),
            'resource_allocation' => $this->getResourceAllocationRecommendations($business_id, $location_ids, $category_ids, $brand_ids, $analysis_type),
            'chart_data' => $this->getChartData($business_id, $location_ids, $category_ids, $brand_ids, $analysis_type)
        ];

        return response()->json($data);
    }

    private function getABCClassification($business_id, $location_ids = [], $category_ids = [], $brand_ids = [], $analysis_type = 'value')
    {
        $query = $this->buildBaseQuery($business_id, $location_ids, $category_ids, $brand_ids, $analysis_type);
        
        // Get all products with their values
        $products = $query->get();
        
        if ($products->isEmpty()) {
            return [];
        }

        // Sort by analysis value descending
        $products = $products->sortByDesc('analysis_value');
        
        // Calculate cumulative data for ABC classification
        $total_value = $products->sum('analysis_value');
        $total_count = $products->count();
        
        $classified_products = [];
        $cumulative_value = 0;
        $cumulative_percentage = 0;
        
        foreach ($products as $index => $product) {
            $cumulative_value += $product->analysis_value;
            $cumulative_percentage = ($cumulative_value / $total_value) * 100;
            
            // ABC Classification Logic (Standard Implementation):
            // A-Class: Items contributing to first 80% of cumulative value
            // B-Class: Items contributing to 80-95% of cumulative value  
            // C-Class: Items contributing to 95-100% of cumulative value
            if ($cumulative_percentage <= 80) {
                $grade = 'A';
                $class_description = 'High Value - Critical Items';
                $priority = 'High';
            } elseif ($cumulative_percentage <= 95) {
                $grade = 'B';
                $class_description = 'Medium Value - Important Items';
                $priority = 'Medium';
            } else {
                $grade = 'C';
                $class_description = 'Low Value - Standard Items';
                $priority = 'Low';
            }

            $classified_products[] = [
                'product_name' => $product->product_name,
                'variant_title' => $product->variation_name ?: 'Default',
                'variant_sku' => $product->variant_sku ?: $product->sku,
                'category' => $product->category_name ?: 'Uncategorized',
                'brand' => $product->brand_name ?: 'No Brand',
                'ending_quantity' => number_format($product->qty_available, 2),
                'unit_cost' => number_format($product->cost_price, 2),
                'unit_price' => number_format($product->selling_price, 2),
                'total_cost_value' => number_format($product->total_cost_value, 2),
                'total_selling_value' => number_format($product->total_selling_value, 2),
                'sales_quantity' => number_format($product->sales_quantity ?: 0, 2),
                'sales_revenue' => number_format($product->sales_revenue ?: 0, 2),
                'analysis_value' => number_format($product->analysis_value, 2),
                'abc_grade' => $grade,
                'class_description' => $class_description,
                'priority' => $priority,
                'cumulative_percentage' => number_format($cumulative_percentage, 2),
                'individual_percentage' => number_format(($product->analysis_value / $total_value) * 100, 2),
                'rank' => $index + 1
            ];
        }

        return $classified_products;
    }

    private function getABCSummary($business_id, $location_ids = [], $category_ids = [], $brand_ids = [], $analysis_type = 'value')
    {
        $classification = $this->getABCClassification($business_id, $location_ids, $category_ids, $brand_ids, $analysis_type);
        
        if (empty($classification)) {
            return [
                'total_products' => 0,
                'total_value' => 0,
                'grade_summary' => []
            ];
        }

        $summary = [
            'A' => ['count' => 0, 'value' => 0, 'percentage_items' => 0, 'percentage_value' => 0],
            'B' => ['count' => 0, 'value' => 0, 'percentage_items' => 0, 'percentage_value' => 0],
            'C' => ['count' => 0, 'value' => 0, 'percentage_items' => 0, 'percentage_value' => 0]
        ];

        $total_count = count($classification);
        $total_value = array_sum(array_map(function($value) {
            return floatval(str_replace(',', '', $value));
        }, array_column($classification, 'analysis_value')));

        foreach ($classification as $item) {
            $grade = $item['abc_grade'];
            $value = floatval(str_replace(',', '', $item['analysis_value']));
            
            $summary[$grade]['count']++;
            $summary[$grade]['value'] += $value;
        }

        // Calculate percentages
        foreach (['A', 'B', 'C'] as $grade) {
            $summary[$grade]['percentage_items'] = ($summary[$grade]['count'] / $total_count) * 100;
            $summary[$grade]['percentage_value'] = ($summary[$grade]['value'] / $total_value) * 100;
            $summary[$grade]['value'] = number_format($summary[$grade]['value'], 2);
            $summary[$grade]['percentage_items'] = number_format($summary[$grade]['percentage_items'], 1);
            $summary[$grade]['percentage_value'] = number_format($summary[$grade]['percentage_value'], 1);
        }

        return [
            'total_products' => $total_count,
            'total_value' => number_format($total_value, 2),
            'analysis_type' => ucfirst($analysis_type),
            'grade_summary' => $summary
        ];
    }

    private function getRevenueContributionAnalysis($business_id, $location_ids = [], $category_ids = [], $brand_ids = [], $analysis_type = 'value')
    {
        $classification = $this->getABCClassification($business_id, $location_ids, $category_ids, $brand_ids, $analysis_type);
        
        if (empty($classification)) {
            return [];
        }

        $contribution_analysis = [];
        $grades = ['A', 'B', 'C'];

        foreach ($grades as $grade) {
            $grade_items = array_filter($classification, function($item) use ($grade) {
                return $item['abc_grade'] === $grade;
            });

            $total_revenue = array_sum(array_map(function($item) {
                return floatval(str_replace(',', '', $item['sales_revenue']));
            }, $grade_items));

            $total_inventory_value = array_sum(array_map(function($item) {
                return floatval(str_replace(',', '', $item['total_selling_value']));
            }, $grade_items));

            $avg_turnover = 0;
            if (count($grade_items) > 0) {
                $turnovers = array_map(function($item) {
                    $inventory_value = floatval(str_replace(',', '', $item['total_selling_value']));
                    $sales_revenue = floatval(str_replace(',', '', $item['sales_revenue']));
                    return $inventory_value > 0 ? $sales_revenue / $inventory_value : 0;
                }, $grade_items);
                $avg_turnover = array_sum($turnovers) / count($turnovers);
            }

            $contribution_analysis[] = [
                'grade' => $grade,
                'item_count' => count($grade_items),
                'total_revenue' => number_format($total_revenue, 2),
                'total_inventory_value' => number_format($total_inventory_value, 2),
                'average_turnover' => number_format($avg_turnover, 2),
                'focus_strategy' => $this->getFocusStrategy($grade),
                'management_approach' => $this->getManagementApproach($grade)
            ];
        }

        return $contribution_analysis;
    }

    private function getResourceAllocationRecommendations($business_id, $location_ids = [], $category_ids = [], $brand_ids = [], $analysis_type = 'value')
    {
        $summary = $this->getABCSummary($business_id, $location_ids, $category_ids, $brand_ids, $analysis_type);
        
        if (empty($summary['grade_summary'])) {
            return [];
        }

        $recommendations = [];

        foreach (['A', 'B', 'C'] as $grade) {
            $grade_data = $summary['grade_summary'][$grade];
            
            $recommendations[$grade] = [
                'grade' => $grade,
                'item_count' => $grade_data['count'],
                'total_value' => $grade_data['value'],
                'focus_strategy' => $this->getFocusStrategy($grade),
                'management_approach' => $this->getManagementApproach($grade),
                'monitoring_frequency' => $this->getMonitoringFrequency($grade),
                'safety_stock_level' => $this->getSafetyStockRecommendation($grade),
                'procurement_priority' => $this->getProcurementPriority($grade),
                'storage_location' => $this->getStorageRecommendation($grade),
                'review_cycle' => $this->getReviewCycle($grade),
                'automation_level' => $this->getAutomationRecommendation($grade),
                'supplier_relationship' => $this->getSupplierStrategy($grade),
                'key_actions' => $this->getKeyActions($grade)
            ];
        }

        return $recommendations;
    }

    private function getChartData($business_id, $location_ids = [], $category_ids = [], $brand_ids = [], $analysis_type = 'value')
    {
        $summary = $this->getABCSummary($business_id, $location_ids, $category_ids, $brand_ids, $analysis_type);
        
        if (empty($summary['grade_summary'])) {
            return [
                'bar_chart' => [],
                'pie_chart' => [],
                'pareto_chart' => []
            ];
        }

        $bar_chart_data = [];
        $pie_chart_data = [];
        
        foreach (['A', 'B', 'C'] as $grade) {
            $grade_data = $summary['grade_summary'][$grade];
            
            $bar_chart_data[] = [
                'grade' => $grade,
                'count' => $grade_data['count'],
                'value' => floatval(str_replace(',', '', $grade_data['value'])),
                'percentage_items' => floatval($grade_data['percentage_items']),
                'percentage_value' => floatval($grade_data['percentage_value'])
            ];

            $pie_chart_data[] = [
                'label' => "Grade $grade",
                'value' => floatval($grade_data['percentage_value']),
                'count' => $grade_data['count'],
                'color' => $this->getGradeColor($grade)
            ];
        }

        // Pareto chart data (top 20 products by value)
        $classification = $this->getABCClassification($business_id, $location_ids, $category_ids, $brand_ids, $analysis_type);
        $pareto_data = array_slice($classification, 0, 20);

        return [
            'bar_chart' => $bar_chart_data,
            'pie_chart' => $pie_chart_data,
            'pareto_chart' => $pareto_data
        ];
    }

    private function buildBaseQuery($business_id, $location_ids = [], $category_ids = [], $brand_ids = [], $analysis_type = 'value')
    {
        $query = DB::table('products as p')
            ->join('variations as v', 'p.id', '=', 'v.product_id')
            ->join('variation_location_details as vld', 'v.id', '=', 'vld.variation_id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->leftJoin(DB::raw('(
                SELECT 
                    tsl.variation_id,
                    SUM(tsl.quantity) as sales_quantity,
                    SUM(tsl.quantity * tsl.unit_price_before_discount) as sales_revenue
                FROM transaction_sell_lines tsl
                JOIN transactions t ON tsl.transaction_id = t.id
                WHERE t.business_id = ' . $business_id . ' AND t.type = "sell" AND t.status = "final"
                    AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY tsl.variation_id
            ) as sales'), 'v.id', '=', 'sales.variation_id')
            ->select([
                'p.name as product_name',
                'p.sku as sku',
                'v.name as variation_name',
                'v.sub_sku as variant_sku',
                'v.default_purchase_price as cost_price',
                'v.default_sell_price as selling_price',
                'vld.qty_available',
                'c.name as category_name',
                'b.name as brand_name',
                'sales.sales_quantity',
                'sales.sales_revenue',
                DB::raw('(vld.qty_available * v.default_purchase_price) as total_cost_value'),
                DB::raw('(vld.qty_available * v.default_sell_price) as total_selling_value')
            ])
            ->where('p.business_id', $business_id)
            ->where('vld.qty_available', '>', 0);

        // Add analysis value calculation based on type
        switch ($analysis_type) {
            case 'sales':
                $query->addSelect(DB::raw('COALESCE(sales.sales_revenue, 0) as analysis_value'));
                break;
            case 'hybrid':
                $query->addSelect(DB::raw('((vld.qty_available * v.default_sell_price) * 0.6 + COALESCE(sales.sales_revenue, 0) * 0.4) as analysis_value'));
                break;
            default: // 'value'
                $query->addSelect(DB::raw('(vld.qty_available * v.default_sell_price) as analysis_value'));
                break;
        }

        // Apply filters
        if (!empty($location_ids)) {
            $query->whereIn('vld.location_id', $location_ids);
        }
        if (!empty($category_ids)) {
            $query->whereIn('p.category_id', $category_ids);
        }
        if (!empty($brand_ids)) {
            $query->whereIn('p.brand_id', $brand_ids);
        }

        return $query;
    }

    // Helper methods for recommendations
    private function getFocusStrategy($grade)
    {
        switch ($grade) {
            case 'A': return 'High attention, tight control, frequent monitoring';
            case 'B': return 'Medium attention, moderate control, periodic monitoring';
            case 'C': return 'Low attention, simple control, infrequent monitoring';
        }
    }

    private function getManagementApproach($grade)
    {
        switch ($grade) {
            case 'A': return 'Just-in-time ordering, multiple suppliers, close relationships';
            case 'B': return 'Economic order quantity, reliable suppliers, good relationships';
            case 'C': return 'Bulk ordering, single suppliers, standard relationships';
        }
    }

    private function getMonitoringFrequency($grade)
    {
        switch ($grade) {
            case 'A': return 'Daily';
            case 'B': return 'Weekly';
            case 'C': return 'Monthly';
        }
    }

    private function getSafetyStockRecommendation($grade)
    {
        switch ($grade) {
            case 'A': return 'Low safety stock (3-7 days)';
            case 'B': return 'Medium safety stock (1-2 weeks)';
            case 'C': return 'High safety stock (2-4 weeks)';
        }
    }

    private function getProcurementPriority($grade)
    {
        switch ($grade) {
            case 'A': return 'Highest priority - immediate attention';
            case 'B': return 'Medium priority - scheduled attention';
            case 'C': return 'Lowest priority - routine attention';
        }
    }

    private function getStorageRecommendation($grade)
    {
        switch ($grade) {
            case 'A': return 'Prime locations, easy access, organized';
            case 'B': return 'Good locations, reasonable access';
            case 'C': return 'Remote locations, bulk storage acceptable';
        }
    }

    private function getReviewCycle($grade)
    {
        switch ($grade) {
            case 'A': return 'Continuous review system';
            case 'B': return 'Periodic review (weekly/bi-weekly)';
            case 'C': return 'Periodic review (monthly/quarterly)';
        }
    }

    private function getAutomationRecommendation($grade)
    {
        switch ($grade) {
            case 'A': return 'High automation, real-time tracking';
            case 'B': return 'Medium automation, regular updates';
            case 'C': return 'Low automation, manual processes acceptable';
        }
    }

    private function getSupplierStrategy($grade)
    {
        switch ($grade) {
            case 'A': return 'Strategic partnerships, multiple sources';
            case 'B': return 'Preferred suppliers, backup sources';
            case 'C': return 'Transactional relationships, single source';
        }
    }

    private function getKeyActions($grade)
    {
        switch ($grade) {
            case 'A': return ['Minimize stockouts', 'Optimize inventory levels', 'Monitor demand closely', 'Strengthen supplier relationships'];
            case 'B': return ['Balance cost and service', 'Regular demand forecasting', 'Maintain good supplier relations', 'Periodic inventory reviews'];
            case 'C': return ['Minimize handling costs', 'Bulk purchasing', 'Simple inventory systems', 'Annual contract negotiations'];
        }
    }

    private function getGradeColor($grade)
    {
        switch ($grade) {
            case 'A': return '#d32f2f'; // Red for high priority
            case 'B': return '#f57c00'; // Orange for medium priority  
            case 'C': return '#388e3c'; // Green for low priority
            default: return '#757575'; // Grey
        }
    }

    public function export(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view') && !auth()->user()->can('AdvancedReports.abc_analysis')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $location_ids = $request->get('location_ids', []);
        $category_ids = $request->get('category_ids', []);
        $brand_ids = $request->get('brand_ids', []);
        $analysis_type = $request->get('analysis_type', 'value');

        try {
            $data = [
                'abc_classification' => $this->getABCClassification($business_id, $location_ids, $category_ids, $brand_ids, $analysis_type),
                'abc_summary' => $this->getABCSummary($business_id, $location_ids, $category_ids, $brand_ids, $analysis_type),
                'revenue_contribution' => $this->getRevenueContributionAnalysis($business_id, $location_ids, $category_ids, $brand_ids, $analysis_type)
            ];
        } catch (\Exception $e) {
            \Log::error('ABC Analysis export error: ' . $e->getMessage());
            abort(500, 'Error generating export data: ' . $e->getMessage());
        }

        $filename = "abc_analysis_report_" . date('Y-m-d') . ".csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // ABC Classification Detail
            fputcsv($file, ['ABC Analysis - Product Classification']);
            fputcsv($file, ['Product', 'Variant Title', 'Variant SKU', 'Category', 'ABC Grade', 'Ending Quantity', 'Total Cost Value', 'Total Selling Value', 'Cumulative %', 'Priority']);
            
            if (!empty($data['abc_classification'])) {
                foreach ($data['abc_classification'] as $item) {
                    fputcsv($file, [
                        $item['product_name'],
                        $item['variant_title'],
                        $item['variant_sku'],
                        $item['category'],
                        $item['abc_grade'],
                        $item['ending_quantity'],
                        '$' . $item['total_cost_value'],
                        '$' . $item['total_selling_value'],
                        $item['cumulative_percentage'] . '%',
                        $item['priority']
                    ]);
                }
            } else {
                fputcsv($file, ['No classification data available']);
            }

            fputcsv($file, []);
            
            // Summary Statistics
            fputcsv($file, ['ABC Analysis Summary']);
            fputcsv($file, ['Grade', 'Item Count', '% of Items', 'Total Value', '% of Value']);
            
            if (!empty($data['abc_summary']['grade_summary'])) {
                foreach (['A', 'B', 'C'] as $grade) {
                    $grade_data = $data['abc_summary']['grade_summary'][$grade];
                    fputcsv($file, [
                        "Grade $grade",
                        $grade_data['count'],
                        $grade_data['percentage_items'] . '%',
                        '$' . $grade_data['value'],
                        $grade_data['percentage_value'] . '%'
                    ]);
                }
            }

            fputcsv($file, []);
            fputcsv($file, ['Total Products: ' . ($data['abc_summary']['total_products'] ?? 0)]);
            fputcsv($file, ['Total Value: $' . ($data['abc_summary']['total_value'] ?? 0)]);
            fputcsv($file, ['Analysis Type: ' . ($data['abc_summary']['analysis_type'] ?? 'Value')]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}