<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\BusinessLocation;
use App\User;
use App\Variation;
use Modules\AdvancedReports\Entities\StaffAward;
use Modules\AdvancedReports\Entities\StaffPerformanceActivity;
use Modules\AdvancedReports\Entities\StaffAwardPeriod;
use App\Utils\TransactionUtil;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Modules\AdvancedReports\Utils\CustomerRecognitionUtil;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StaffRecognitionController extends Controller
{
    protected $transactionUtil;
    protected $moduleUtil;
    protected $businessUtil;
    protected $customerRecognitionUtil;

    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, BusinessUtil $businessUtil, CustomerRecognitionUtil $customerRecognitionUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->businessUtil = $businessUtil;
        $this->customerRecognitionUtil = $customerRecognitionUtil;
    }

    /**
     * Check if service staff module is enabled
     */
    private function checkServiceStaffModule()
    {
        $enabled_modules_raw = session('business.enabled_modules');
        
        // Handle different data types - could be array, string, or null
        if (empty($enabled_modules_raw)) {
            $enabled_modules = [];
        } elseif (is_array($enabled_modules_raw)) {
            $enabled_modules = $enabled_modules_raw;
        } elseif (is_string($enabled_modules_raw)) {
            // If it's a string, try to decode as JSON or split by comma
            $decoded = json_decode($enabled_modules_raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $enabled_modules = $decoded;
            } else {
                // Fallback: split by comma and trim whitespace
                $enabled_modules = array_map('trim', explode(',', $enabled_modules_raw));
            }
        } else {
            $enabled_modules = [];
        }
        
        if (!in_array('service_staff', $enabled_modules)) {
            return [
                'success' => false,
                'msg' => __('advancedreports::lang.service_staff_not_enabled')
            ];
        }
        return null;
    }

    /**
     * Display staff recognition main page
     */
    public function index()
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        // Check if service staff module is enabled
        $moduleCheck = $this->checkServiceStaffModule();
        if ($moduleCheck) {
            return view('advancedreports::partials.module_not_enabled', ['output' => $moduleCheck]);
        }

        $business_id = request()->session()->get('user.business_id');

        // Get dropdowns for filters
        $period_types = [
            'weekly' => __('advancedreports::lang.weekly'),
            'monthly' => __('advancedreports::lang.monthly'),
            'yearly' => __('advancedreports::lang.yearly')
        ];

        $winner_counts = [
            5 => '5 Staff',
            10 => '10 Staff',
            15 => '15 Staff',
            20 => '20 Staff',
            25 => '25 Staff',
            50 => '50 Staff'
        ];

        $status_options = [
            'all' => __('lang_v1.all'),
            'awarded' => __('advancedreports::lang.awarded'),
            'not_awarded' => __('advancedreports::lang.not_awarded')
        ];

        // Get business locations
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        // Get service staff
        $waiters = $this->transactionUtil->serviceStaffDropdown($business_id);

        return view('advancedreports::staff-recognition.index')
            ->with(compact(
                'period_types',
                'winner_counts', 
                'status_options',
                'business_locations',
                'waiters'
            ));
    }

    /**
     * Get staff recognition data for DataTables
     */
    public function getStaffRecognitionData(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        // Check if service staff module is enabled
        $moduleCheck = $this->checkServiceStaffModule();
        if ($moduleCheck) {
            return response()->json($moduleCheck, 403);
        }

        $business_id = request()->session()->get('user.business_id');
        
        // Get request parameters
        $period_type = $request->get('period_type', 'monthly');
        $winner_count = $request->get('winner_count', 10);
        $status = $request->get('status', 'all');
        $location_id = $request->get('location_id');

        // Get period dates
        $dates = $this->getPeriodDates($period_type);
        
        // Get service staff scores
        $scores = $this->calculateStaffScores($business_id, $period_type, $dates, $location_id);
        
        // Get existing awards
        $existing_awards = StaffAward::where('business_id', $business_id)
            ->where('period_type', $period_type)
            ->where('period_start', $dates['start'])
            ->get()
            ->keyBy('staff_id');

        // Apply status filter
        if ($status === 'awarded') {
            $scores = $scores->filter(function($score) use ($existing_awards) {
                $award = $existing_awards->get($score['staff_id']);
                return $award && $award->is_awarded;
            });
        } elseif ($status === 'not_awarded') {
            $scores = $scores->filter(function($score) use ($existing_awards) {
                $award = $existing_awards->get($score['staff_id']);
                return !$award || !$award->is_awarded;
            });
        }

        // Limit results
        $scores = $scores->take($winner_count);

        // Convert to DataTables format
        return DataTables::of($scores)
            ->addColumn('action', function ($row) use ($period_type, $dates, $existing_awards) {
                $actions = '<div class="btn-group">';
                
                $actions .= '<button type="button" class="btn btn-info btn-xs view-staff-details" 
                    data-staff-id="' . $row['staff_id'] . '" 
                    data-period-type="' . $period_type . '"
                    data-period-start="' . $dates['start'] . '"
                    data-period-end="' . $dates['end'] . '">
                    <i class="fa fa-eye"></i> ' . __('messages.view') . '
                </button>';

                if (auth()->user()->can('sales_representative.create')) {
                    $award = $existing_awards->get($row['staff_id']);
                    if (!$award || !$award->is_awarded) {
                        $actions .= '<button type="button" class="btn btn-success btn-xs award-staff" 
                            data-staff-id="' . $row['staff_id'] . '"
                            data-rank="' . $row['rank_position'] . '"
                            data-staff-name="' . $row['staff_name'] . '">
                            <i class="fa fa-gift"></i> ' . __('Award') . '
                        </button>';
                    } else {
                        $actions .= '<button type="button" class="btn btn-warning btn-xs unaward-staff" 
                            data-award-id="' . $award->id . '"
                            data-staff-id="' . $row['staff_id'] . '"
                            data-staff-name="' . $row['staff_name'] . '">
                            <i class="fa fa-undo"></i> ' . __('Unaward') . '
                        </button>';
                    }
                }

                $actions .= '</div>';
                return $actions;
            })
            ->editColumn('staff_name', function ($row) {
                $display = '<div>';
                $display .= '<strong>' . $this->getRankSuffix($row['rank_position']) . ' - ';
                $display .= $row['staff_name'] . '</strong>';
                
                if (!empty($row['staff_email'])) {
                    $display .= '<br><small class="text-muted">' . $row['staff_email'] . '</small>';
                }
                
                $display .= '</div>';
                return $display;
            })
            ->editColumn('sales_total', function ($row) {
                return '<span class="text-success"><strong>' . 
                    $this->transactionUtil->num_f($row['sales_total'], true) . '</strong></span>';
            })
            ->editColumn('avg_transaction_value', function ($row) {
                return $this->transactionUtil->num_f($row['avg_transaction_value'], true);
            })
            ->addColumn('awarded_info', function ($row) use ($existing_awards) {
                $award = $existing_awards->get($row['staff_id']);
                if ($award && $award->is_awarded) {
                    $info = '<span class="label label-success">Awarded</span><br>';
                    $info .= '<small>' . ($award->gift_description ?: 'Recognition Award') . '</small>';
                    return $info;
                }
                return '<span class="label label-default">Not Awarded</span>';
            })
            ->rawColumns(['staff_name', 'sales_total', 'avg_transaction_value', 'awarded_info', 'action'])
            ->make(true);
    }

    /**
     * Get activities data for DataTables
     */
    public function getActivitiesData(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        // Check if service staff module is enabled
        $moduleCheck = $this->checkServiceStaffModule();
        if ($moduleCheck) {
            return response()->json($moduleCheck, 403);
        }

        $business_id = request()->session()->get('user.business_id');
        
        $query = StaffPerformanceActivity::with(['staff', 'recordedBy'])
            ->where('business_id', $business_id)
            ->orderBy('recorded_date', 'desc');

        return DataTables::of($query)
            ->addColumn('staff_name', function ($activity) {
                return $activity->staff ? $activity->staff->first_name . ' ' . $activity->staff->surname : 'Unknown';
            })
            ->editColumn('activity_type', function ($activity) {
                return $activity->activity_type_name;
            })
            ->editColumn('points', function ($activity) {
                return '<span class="badge bg-blue">' . $activity->points . '</span>';
            })
            ->addColumn('recorded_by_name', function ($activity) {
                return $activity->recordedBy ? $activity->recordedBy->first_name . ' ' . $activity->recordedBy->surname : '-';
            })
            ->editColumn('recorded_date', function ($activity) {
                return $activity->recorded_date ? $activity->recorded_date->format('Y-m-d') : '-';
            })
            ->editColumn('status', function ($activity) {
                $statusClass = $activity->status === 'verified' ? 'success' : 
                             ($activity->status === 'pending' ? 'warning' : 'danger');
                return '<span class="label label-' . $statusClass . '">' . 
                       ucfirst($activity->status) . '</span>';
            })
            ->rawColumns(['staff_name', 'activity_type', 'points', 'recorded_by_name', 'status'])
            ->make(true);
    }

    /**
     * Calculate staff scores for the period
     */
    private function calculateStaffScores($business_id, $period_type, $dates, $location_id = null)
    {
        // Get service staff
        $staff = $this->transactionUtil->getServiceStaff($business_id, $location_id);
        
        $scores = collect();
        $rank = 1;

        foreach ($staff as $staffMember) {
            // Get sales data for this staff member
            $sales_data = $this->getStaffSalesData($staffMember->id, $dates, $location_id);
            
            // Get performance activities points
            $performance_points = StaffPerformanceActivity::where('business_id', $business_id)
                ->where('staff_id', $staffMember->id)
                ->where('recorded_date', '>=', $dates['start'])
                ->where('recorded_date', '<=', $dates['end'])
                ->where('status', 'verified')
                ->sum('points');

            // Calculate final score (sales performance + activity points)
            $final_score = ($sales_data['sales_total'] * 0.7) + ($performance_points * 0.3);

            $scores->push([
                'staff_id' => $staffMember->id,
                'staff_name' => trim($staffMember->first_name . ' ' . $staffMember->surname),
                'staff_email' => $staffMember->email,
                'rank_position' => $rank++,
                'sales_total' => $sales_data['sales_total'],
                'transaction_count' => $sales_data['transaction_count'],
                'avg_transaction_value' => $sales_data['avg_transaction_value'],
                'performance_points' => $performance_points,
                'final_score' => $final_score
            ]);
        }

        // Sort by final score descending and reassign ranks
        $scores = $scores->sortByDesc('final_score')->values();
        $scores = $scores->map(function ($score, $index) {
            $score['rank_position'] = $index + 1;
            return $score;
        });

        return $scores;
    }

    /**
     * Get sales data for a specific staff member
     */
    private function getStaffSalesData($staff_id, $dates, $location_id = null)
    {
        $query = DB::table('transactions as t')
            ->where('t.created_by', $staff_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$dates['start'], $dates['end']]);

        if ($location_id) {
            $query->where('t.location_id', $location_id);
        }

        $result = $query->selectRaw('
            COUNT(*) as transaction_count,
            COALESCE(SUM(final_total), 0) as sales_total,
            COALESCE(AVG(final_total), 0) as avg_transaction_value
        ')->first();

        return [
            'transaction_count' => $result->transaction_count ?? 0,
            'sales_total' => $result->sales_total ?? 0,
            'avg_transaction_value' => $result->avg_transaction_value ?? 0
        ];
    }

    /**
     * Get period dates based on type
     */
    private function getPeriodDates($period_type)
    {
        $now = Carbon::now();
        
        switch ($period_type) {
            case 'weekly':
                $start = $now->startOfWeek()->toDateString();
                $end = $now->endOfWeek()->toDateString();
                break;
            case 'yearly':
                $start = $now->startOfYear()->toDateString();
                $end = $now->endOfYear()->toDateString();
                break;
            case 'monthly':
            default:
                $start = $now->startOfMonth()->toDateString();
                $end = $now->endOfMonth()->toDateString();
                break;
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Get rank suffix
     */
    private function getRankSuffix($rank)
    {
        if ($rank % 100 >= 11 && $rank % 100 <= 13) {
            return $rank . 'th';
        }
        
        switch ($rank % 10) {
            case 1: return $rank . 'st';
            case 2: return $rank . 'nd';
            case 3: return $rank . 'rd';
            default: return $rank . 'th';
        }
    }

    /**
     * Award staff member
     */
    public function awardStaff(Request $request)
    {
        if (!auth()->user()->can('sales_representative.create')) {
            abort(403, 'Unauthorized action.');
        }

        // Check if service staff module is enabled
        $moduleCheck = $this->checkServiceStaffModule();
        if ($moduleCheck) {
            return response()->json($moduleCheck, 403);
        }

        // Dynamic validation rules based on award type
        $rules = [
            'staff_id' => 'required|exists:users,id',
            'award_type' => 'required|in:manual,catalog',
            'period_type' => 'required|in:weekly,monthly,yearly',
            'rank_position' => 'required|integer|min:1',
            'notes' => 'nullable|string'
        ];

        if ($request->award_type === 'manual') {
            $rules['gift_description'] = 'required|string';
            $rules['gift_monetary_value'] = 'nullable|numeric|min:0';
        } elseif ($request->award_type === 'catalog') {
            $rules['catalog_item_id'] = 'required|integer';
            $rules['award_quantity'] = 'required|integer|min:1|max:999';
        }

        $request->validate($rules);

        try {
            $business_id = $request->session()->get('user.business_id');
            $dates = $this->getPeriodDates($request->period_type);

            // Create or find award record
            $award = StaffAward::firstOrCreate([
                'business_id' => $business_id,
                'staff_id' => $request->staff_id,
                'period_type' => $request->period_type,
                'period_start' => $dates['start']
            ], [
                'period_end' => $dates['end'],
                'rank_position' => $request->rank_position,
            ]);

            // Prepare award data based on type
            $award_data = [
                'award_type' => $request->award_type,
                'notes' => $request->notes
            ];

            if ($request->award_type === 'catalog') {
                // Validate and get product information
                $variation = Variation::with(['product', 'product_variation'])
                    ->where('id', $request->catalog_item_id)
                    ->whereHas('product', function($query) use ($business_id) {
                        $query->where('business_id', $business_id);
                    })
                    ->first();

                if (!$variation) {
                    return response()->json(['error' => 'Invalid product selected'], 422);
                }

                // Get product details for the award
                $product = $variation->product;
                $selling_price = $variation->sell_price_inc_tax;
                
                // Get quantity (default to 1 if not provided)
                $quantity = $request->award_quantity ?? 1;
                
                // Create gift description from product including quantity
                $gift_description = $quantity . ' x ' . $product->name;
                if ($variation->product_variation) {
                    $gift_description .= ' - ' . $variation->product_variation->name;
                }
                $gift_description .= ' (' . $variation->sub_sku . ')';

                $award_data['catalog_item_id'] = $request->catalog_item_id; // Store variation ID
                $award_data['award_quantity'] = $quantity;
                $award_data['gift_description'] = $gift_description;
                $award_data['gift_monetary_value'] = $selling_price * $quantity; // Total value
            } else {
                $award_data['gift_description'] = $request->gift_description;
                $award_data['gift_monetary_value'] = $request->gift_monetary_value;
            }

            // Get location_id for stock deduction
            $location_id = $request->get('location_id') ?? 
                          $request->session()->get('user.business_location_id') ?? 
                          \App\BusinessLocation::where('business_id', $business_id)->first()->id ?? null;

            // Update award with award data
            $update_data = [
                'award_type' => $award_data['award_type'],
                'is_awarded' => true,
                'awarded_by' => auth()->user()->id,
                'awarded_date' => now(),
                'award_notes' => $award_data['notes'] ?? null
            ];

            if ($award_data['award_type'] === 'catalog') {
                $variation_id = $award_data['catalog_item_id']; // This is the product variation ID
                $update_data['catalog_item_id'] = $variation_id;
                $update_data['gift_description'] = $award_data['gift_description'];
                $update_data['gift_monetary_value'] = $award_data['gift_monetary_value'];
                $update_data['award_quantity'] = $award_data['award_quantity'] ?? 1;
                
                // Decrease product stock by specified quantity when awarded
                if ($location_id) {
                    $variation = Variation::find($variation_id);
                    if ($variation) {
                        // Get quantity to deduct (default to 1)
                        $quantity = $award_data['award_quantity'] ?? 1;
                        
                        // Check if stock management is enabled for this product
                        $product = \App\Product::find($variation->product_id);
                        if ($product && $product->enable_stock == 1) {
                            // Decrease stock by specified quantity (using negative value)
                            $affected = \App\VariationLocationDetails::where('variation_id', $variation_id)
                                ->where('product_id', $variation->product_id)
                                ->where('location_id', $location_id)
                                ->increment('qty_available', -$quantity); // Negative increment = decrease
                            
                            $update_data['stock_deducted'] = true;
                            
                            // Log for debugging
                            \Log::info('Staff Award Stock Deduction', [
                                'product_id' => $variation->product_id,
                                'variation_id' => $variation_id,
                                'location_id' => $location_id,
                                'quantity_deducted' => $quantity,
                                'rows_affected' => $affected,
                                'product_name' => $product->name,
                                'staff_name' => $award->staff->first_name ?? 'Unknown'
                            ]);
                        }
                    }
                }
            } else {
                $update_data['gift_description'] = $award_data['gift_description'];
                $update_data['gift_monetary_value'] = $award_data['gift_monetary_value'];
            }

            // Update the award
            $award->update($update_data);

            return response()->json([
                'success' => true,
                'message' => __('Staff member awarded successfully!'),
                'award' => $award
            ]);

        } catch (\Exception $e) {
            \Log::error('Award Staff Error: ' . $e->getMessage());
            \Log::error('Request data: ' . json_encode($request->all()));
            return response()->json([
                'success' => false,
                'message' => 'Error awarding staff: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unaward (remove) a staff award and restore stock
     */
    public function unawardStaff(Request $request)
    {
        if (!auth()->user()->can('sales_representative.create')) {
            abort(403, 'Unauthorized action.');
        }

        // Check if service staff module is enabled
        $moduleCheck = $this->checkServiceStaffModule();
        if ($moduleCheck) {
            return response()->json($moduleCheck, 403);
        }

        $request->validate([
            'award_id' => 'required|integer|exists:staff_awards,id',
            'confirmation' => 'required|string'
        ]);

        try {
            $business_id = $request->session()->get('user.business_id');
            
            // Find the award
            $award = StaffAward::where('business_id', $business_id)
                ->where('id', $request->award_id)
                ->where('is_awarded', true)
                ->firstOrFail();

            // Get location_id for stock restoration
            $location_id = $request->get('location_id') ?? 
                          $request->session()->get('user.business_location_id') ?? 
                          \App\BusinessLocation::where('business_id', $business_id)->first()->id ?? null;

            // Restore stock if it was a catalog (product) award and stock was deducted
            if ($award->award_type === 'catalog' && $award->stock_deducted && $location_id) {
                $variation = \App\Variation::find($award->catalog_item_id);
                if ($variation) {
                    // Check if stock management is enabled for this product
                    $product = \App\Product::find($variation->product_id);
                    if ($product && $product->enable_stock == 1) {
                        // Get quantity to restore (default to 1)
                        $quantity = $award->award_quantity ?? 1;
                        
                        // Restore stock by original quantity (using positive value)
                        $affected = \App\VariationLocationDetails::where('variation_id', $award->catalog_item_id)
                            ->where('product_id', $variation->product_id)
                            ->where('location_id', $location_id)
                            ->increment('qty_available', $quantity); // Positive increment = restore
                        
                        // Log for debugging
                        \Log::info('Staff Unaward Stock Restoration', [
                            'award_id' => $award->id,
                            'product_id' => $variation->product_id,
                            'variation_id' => $award->catalog_item_id,
                            'location_id' => $location_id,
                            'quantity_restored' => $quantity,
                            'rows_affected' => $affected,
                            'product_name' => $product->name,
                            'staff_name' => $award->staff->first_name ?? 'Unknown'
                        ]);
                    }
                }
            }

            // Remove the award (set is_awarded to false and clear award data)
            $award->update([
                'is_awarded' => false,
                'awarded_by' => null,
                'awarded_date' => null,
                'award_notes' => null,
                'stock_deducted' => false
            ]);

            \Log::info('Staff Unaward Success', [
                'award_id' => $award->id,
                'staff_id' => $award->staff_id,
                'gift_description' => $award->gift_description,
                'unaward_by' => auth()->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Staff award removed successfully and stock restored (if applicable)',
                'award' => $award
            ]);

        } catch (\Exception $e) {
            \Log::error('Unaward Staff Error: ' . $e->getMessage());
            \Log::error('Request data: ' . json_encode($request->all()));
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Record performance activity
     */
    public function recordActivity(Request $request)
    {
        if (!auth()->user()->can('sales_representative.create')) {
            abort(403, 'Unauthorized action.');
        }

        // Check if service staff module is enabled
        $moduleCheck = $this->checkServiceStaffModule();
        if ($moduleCheck) {
            return response()->json($moduleCheck, 403);
        }

        $request->validate([
            'staff_id' => 'required|exists:users,id',
            'activity_type' => 'required|in:punctuality,customer_service,upselling,teamwork,training_completion,cleanliness,other',
            'points' => 'required|integer|min:1|max:100',
            'description' => 'nullable|string|max:500',
            'verification_notes' => 'nullable|string|max:1000'
        ]);

        try {
            $business_id = $request->session()->get('user.business_id');

            StaffPerformanceActivity::create([
                'business_id' => $business_id,
                'staff_id' => $request->staff_id,
                'activity_type' => $request->activity_type,
                'points' => $request->points,
                'description' => $request->description,
                'verification_notes' => $request->verification_notes,
                'recorded_by' => auth()->id(),
                'recorded_date' => now()->toDateString(),
                'status' => 'verified'
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Performance activity recorded successfully!')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error recording activity: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary statistics for dashboard widgets
     */
    public function getSummary(Request $request)
    {
        if (!auth()->user()->can('AdvancedReports.view')) {
            abort(403, 'Unauthorized action.');
        }

        // Check if service staff module is enabled
        $moduleCheck = $this->checkServiceStaffModule();
        if ($moduleCheck) {
            return response()->json($moduleCheck, 403);
        }

        $business_id = $request->session()->get('user.business_id');
        $period_type = $request->input('period_type', 'monthly');
        $location_id = $request->input('location_id');

        try {
            // Get current period winners for all period types
            $winners = [];
            $period_types = ['weekly', 'monthly', 'yearly'];
            
            foreach ($period_types as $type) {
                $dates = $this->getPeriodDates($type);
                $scores = $this->calculateStaffScores($business_id, $type, $dates, $location_id);
                
                $winner = $scores->first();
                $winners[$type] = $winner ? [
                    'staff_name' => $winner['staff_name'],
                    'sales_total' => $winner['sales_total'],
                    'final_score' => $winner['final_score']
                ] : null;
            }

            // Get period statistics for the requested period type
            $dates = $this->getPeriodDates($period_type);
            $staff_scores = $this->calculateStaffScores($business_id, $period_type, $dates, $location_id);

            // Get service staff count
            $staff = $this->transactionUtil->getServiceStaff($business_id, $location_id);
            $total_staff = count($staff);

            // Get top performer
            $top_performer = $staff_scores->first();
            $top_performer_name = $top_performer ? $top_performer['staff_name'] : '-';
            $top_performer_score = $top_performer ? $top_performer['final_score'] : 0;

            // Calculate total sales from all staff
            $total_sales = $staff_scores->sum('sales_total');

            // Calculate average performance score
            $avg_performance = $staff_scores->count() > 0 ? $staff_scores->avg('final_score') : 0;

            // Get total performance activities count for the period
            $total_activities = StaffPerformanceActivity::where('business_id', $business_id)
                ->whereBetween('recorded_date', [$dates['start'], $dates['end']])
                ->count();

            // Calculate average transaction value
            $total_transactions = $staff_scores->sum('transaction_count');
            $avg_transaction = $total_transactions > 0 ? ($total_sales / $total_transactions) : 0;

            return response()->json([
                'success' => true,
                'current_winners' => $winners,
                'statistics' => [
                    'total_staff' => $total_staff,
                    'top_performer' => $top_performer_name,
                    'top_performer_score' => $top_performer_score,
                    'total_sales' => $total_sales,
                    'avg_performance' => $avg_performance,
                    'total_activities' => $total_activities,
                    'avg_transaction' => $avg_transaction
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Staff recognition summary error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading summary data: ' . $e->getMessage()
            ], 500);
        }
    }
}