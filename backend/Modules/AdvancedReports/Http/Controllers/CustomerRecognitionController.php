<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\BusinessLocation;
use App\Contact;
use App\User;
use App\Variation;
use Modules\AdvancedReports\Entities\CustomerRecognitionSetting;
use Modules\AdvancedReports\Entities\CustomerEngagement;
use Modules\AdvancedReports\Entities\CustomerAward;
use Modules\AdvancedReports\Entities\AwardPeriod;
use Modules\AdvancedReports\Utils\CustomerRecognitionUtil;
use App\Utils\TransactionUtil;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerRecognitionController extends Controller
{
    protected $transactionUtil;
    protected $moduleUtil;
    protected $businessUtil;

    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, BusinessUtil $businessUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->businessUtil = $businessUtil;
    }

    /**
     * Display customer recognition main page
     */
    public function index()
    {
        if (!auth()->user()->can('customer_recognition.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Initialize settings if not exists
        $settings = CustomerRecognitionUtil::initializeBusinessSettings($business_id);

        // Get dropdowns for filters
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $customers = Contact::customersDropdown($business_id, false);
        $users = User::forDropdown($business_id, false, false, true);

        // Period types
        $period_types = [
            'weekly' => __('Weekly'),
            'monthly' => __('Monthly'),
            'yearly' => __('Yearly')
        ];

        // Winner count options
        $winner_counts = [
            1 => '1',
            3 => '3',
            5 => '5',
            10 => '10',
            15 => '15',
            20 => '20'
        ];

        // Status options
        $status_options = [
            'all' => __('All'),
            'active' => __('Active Period'),
            'finalized' => __('Finalized'),
            'awarded' => __('Awarded')
        ];

        return view('advancedreports::customer-recognition.index')
            ->with(compact(
                'business_locations',
                'customers', 
                'users',
                'period_types',
                'winner_counts',
                'status_options',
                'settings'
            ));
    }

public function getCustomerRecognitionData(Request $request)
{
    if (!auth()->user()->can('customer_recognition.view')) {
        abort(403, 'Unauthorized action.');
    }

    $business_id = $request->session()->get('user.business_id');
    $period_type = $request->get('period_type', 'monthly');
    $winner_count = $request->get('winner_count', 10);
    $status = $request->get('status', 'all');

    try {
        // Get period dates
        $dates = $this->getPeriodDatesFromRequest($request, $period_type);

        // Calculate current/live rankings
        $scores = CustomerRecognitionUtil::calculateCustomerScores(
            $business_id, 
            $period_type, 
            $dates['start'], 
            $dates['end']
        );

        // Get existing awards for this period
        $existing_awards = CustomerAward::where('business_id', $business_id)
            ->where('period_type', $period_type)
            ->where('period_start', $dates['start'])
            ->where('period_end', $dates['end'])
            ->get()
            ->keyBy('customer_id');

        // Apply filters
        if ($status === 'awarded') {
            $scores = $scores->filter(function ($score) use ($existing_awards) {
                return isset($existing_awards[$score['customer_id']]) && 
                       $existing_awards[$score['customer_id']]->is_awarded;
            });
        } elseif ($status === 'finalized') {
            $period = AwardPeriod::where('business_id', $business_id)
                ->where('period_type', $period_type)
                ->where('period_start', $dates['start'])
                ->where('is_finalized', true)
                ->first();
            
            if (!$period) {
                $scores = collect([]);
            }
        }

        // Limit results
        $scores = $scores->take($winner_count);

        // Convert to DataTables format
        return DataTables::of($scores)
            ->addColumn('action', function ($row) use ($period_type, $dates, $existing_awards) {
                $actions = '<div class="btn-group">';
                
                $actions .= '<button type="button" class="btn btn-info btn-xs view-customer-details" 
                    data-customer-id="' . $row['customer_id'] . '" 
                    data-period-type="' . $period_type . '"
                    data-period-start="' . $dates['start'] . '"
                    data-period-end="' . $dates['end'] . '">
                    <i class="fa fa-eye"></i> ' . __('messages.view') . '
                </button>';

                if (auth()->user()->can('customer_recognition.manage')) {
                    $award = $existing_awards->get($row['customer_id']);
                    if (!$award || !$award->is_awarded) {
                        $actions .= '<button type="button" class="btn btn-success btn-xs award-customer" 
                            data-customer-id="' . $row['customer_id'] . '"
                            data-rank="' . $row['rank_position'] . '"
                            data-customer-name="' . $row['customer_name'] . '">
                            <i class="fa fa-gift"></i> ' . __('Award') . '
                        </button>';
                    } else {
                        // Show Unaward button if already awarded
                        $actions .= '<button type="button" class="btn btn-warning btn-xs unaward-customer" 
                            data-award-id="' . $award->id . '"
                            data-customer-id="' . $row['customer_id'] . '"
                            data-customer-name="' . $row['customer_name'] . '"
                            data-award-description="' . ($award->gift_description ?? 'N/A') . '"
                            title="Remove award and restore stock">
                            <i class="fa fa-undo"></i> ' . __('Unaward') . '
                        </button>';
                    }
                }

                $actions .= '</div>';
                return $actions;
            })
            ->editColumn('customer_name', function ($row) {
                $display = '<div>';
                $display .= '<strong>' . $this->getRankSuffix($row['rank_position']) . ' - ';
                
                if (!empty($row['customer_business_name'])) {
                    $display .= $row['customer_business_name'] . '</strong><br>';
                    $display .= $row['customer_name'];
                } else {
                    $display .= $row['customer_name'] . '</strong>';
                }
                
                if (!empty($row['customer_mobile'])) {
                    $display .= '<br><small class="text-muted">' . $row['customer_mobile'] . '</small>';
                }
                
                $display .= '</div>';
                return $display;
            })
            ->editColumn('sales_total', function ($row) {
                return '<span class="text-success"><strong>' . 
                    $this->transactionUtil->num_f($row['sales_total'], true) . '</strong></span>';
            })
            ->addColumn('total_paid', function ($row) {
    $total_paid = $row['total_paid'] ?? $row['sales_total'];
    return '<span class="text-success"><strong>' . 
        $this->transactionUtil->num_f($total_paid, true) . '</strong></span>';
})
->addColumn('balance_due', function ($row) {
    $balance = $row['balance_due'] ?? 0;
    
    // Fix the color logic - show red if there's any balance due
    if ($balance > 0.01) {  // Use 0.01 to account for rounding
        $color = 'text-danger';
        $icon = '<i class="fa fa-exclamation-triangle"></i> ';
    } else {
        $color = 'text-success';
        $icon = '<i class="fa fa-check"></i> ';
    }
    
    return '<span class="' . $color . '"><strong>' . $icon . 
        $this->transactionUtil->num_f($balance, true) . '</strong></span>';
})
->addColumn('payment_percentage', function ($row) {
    $percentage = $row['payment_percentage'] ?? 100;
    $formatted_percentage = number_format($percentage, 1);
    
    // Fix the percentage color logic
    if ($percentage >= 99.9) {
        $color = 'success';
        $icon = '<i class="fa fa-check"></i>';
    } elseif ($percentage >= 90) {
        $color = 'warning'; 
        $icon = '<i class="fa fa-clock-o"></i>';
    } elseif ($percentage >= 50) {
        $color = 'warning';
        $icon = '<i class="fa fa-exclamation-triangle"></i>';
    } else {
        $color = 'danger';
        $icon = '<i class="fa fa-exclamation-circle"></i>';
    }
    
    return '<span class="label label-' . $color . '">' . $icon . ' ' . $formatted_percentage . '%</span>';
})
            ->editColumn('engagement_points', function ($row) {
                return '<span class="badge badge-info">' . $row['engagement_points'] . ' pts</span>';
            })
            ->editColumn('final_score', function ($row) {
                return '<span class="badge badge-primary"><strong>' . 
                    number_format($row['final_score'], 2) . '</strong></span>';
            })
            ->editColumn('transaction_count', function ($row) {
                return '<span class="badge badge-secondary">' . $row['transaction_count'] . '</span>';
            })
            ->editColumn('avg_transaction_value', function ($row) {
                return $this->transactionUtil->num_f($row['avg_transaction_value'], true);
            })
            ->addColumn('awarded_info', function ($row) use ($existing_awards) {
                $award = $existing_awards->get($row['customer_id']);
                
                if ($award && $award->is_awarded) {
                    $info = '<div class="text-success">';
                    $info .= '<strong>' . ($award->gift_description ?: 'Recognition Award') . '</strong><br>';
                    $info .= '<small>Awarded: ' . $award->awarded_date->format('d M Y') . '</small>';
                    if ($award->awardedBy) {
                        $info .= '<br><small>By: ' . $award->awardedBy->first_name . '</small>';
                    }
                    $info .= '</div>';
                    return $info;
                }
                
                return '<span class="text-muted">Not awarded</span>';
            })
            ->addColumn('rank_position', function ($row) {
                return $row['rank_position'];
            })
            // THIS IS THE KEY FIX - Add the new columns to rawColumns
            ->rawColumns([
                'action', 
                'customer_name', 
                'sales_total', 
                'total_paid',           // NEW
                'balance_due',          // NEW  
                'payment_percentage',   // NEW
                'engagement_points', 
                'final_score', 
                'transaction_count', 
                'awarded_info'
            ])
            ->make(true);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Error loading data: ' . $e->getMessage()], 500);
    }
}

    /**
     * Get summary data for cards
     */
    public function getSummary(Request $request)
    {
        if (!auth()->user()->can('customer_recognition.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            
            // Get current period winners for all period types
            $winners = [];
            $period_types = ['weekly', 'monthly', 'yearly'];
            
            foreach ($period_types as $type) {
                $dates = AwardPeriod::getPeriodDates($type);
                $scores = CustomerRecognitionUtil::calculateCustomerScores(
                    $business_id, 
                    $type, 
                    $dates['start'], 
                    $dates['end']
                );
                
                $winner = $scores->first();
                $winners[$type] = $winner ? [
                    'name' => $winner['customer_name'],
                    'business_name' => $winner['customer_business_name'],
                    'sales_total' => $winner['sales_total'],
                    'final_score' => $winner['final_score']
                ] : null;
            }
            
            // Get period statistics for the requested period type
            $period_type = $request->get('period_type', 'monthly');
            $dates = $this->getPeriodDatesFromRequest($request, $period_type);
            
            $scores = CustomerRecognitionUtil::calculateCustomerScores(
                $business_id, 
                $period_type, 
                $dates['start'], 
                $dates['end']
            );
            
            $summary = [
                'current_winners' => $winners,
                'period_statistics' => [
                    'total_participants' => $scores->count(),
                    'total_sales' => $scores->sum('sales_total'),
                    'total_engagement_points' => $scores->sum('engagement_points'),
                    'avg_score' => $scores->avg('final_score') ?: 0,
                    'top_score' => $scores->first()['final_score'] ?? 0,
                    'avg_transaction_value' => $scores->avg('avg_transaction_value') ?: 0
                ]
            ];

            return response()->json($summary);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Summary calculation failed'], 500);
        }
    }

/**
     * Get customer details for modal - ENHANCED VERSION
     * Replace this method in your CustomerRecognitionController.php
     */
    public function getCustomerDetails(Request $request, $customerId)
    {
        if (!auth()->user()->can('customer_recognition.view')) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            
            if (!$business_id) {
                return response()->json(['error' => 'Business ID not found in session'], 400);
            }

            $period_type = $request->get('period_type', 'monthly');
            $period_start = $request->get('period_start');
            $period_end = $request->get('period_end');

            // Get period dates if not provided
            if (!$period_start || !$period_end) {
                try {
                    $dates = $this->getPeriodDatesFromRequest($request, $period_type);
                    $period_start = $dates['start'];
                    $period_end = $dates['end'];
                } catch (\Exception $e) {
                    return response()->json(['error' => 'Invalid period type or dates'], 400);
                }
            }

            // Get customer info
            $customer = Contact::where('business_id', $business_id)
                ->where('id', $customerId)
                ->first();

            if (!$customer) {
                return response()->json(['error' => 'Customer not found'], 404);
            }

            // Get purchase details with error handling
            try {
                $details = CustomerRecognitionUtil::getCustomerPurchaseDetails(
                    $business_id,
                    $customerId,
                    $period_start,
                    $period_end
                );
            } catch (\Exception $e) {
                // Provide fallback data
                $details = [
                    'summary' => [
                        'total_transactions' => 0,
                        'total_amount' => 0,
                        'avg_transaction_value' => 0,
                        'first_transaction' => null,
                        'last_transaction' => null,
                        'total_products' => 0,
                        'total_engagement_points' => 0
                    ],
                    'top_products' => []
                ];
            }

            // Get customer ranking info with error handling
            $customer_score = null;
            try {
                $scores = CustomerRecognitionUtil::calculateCustomerScores(
                    $business_id, 
                    $period_type, 
                    $period_start, 
                    $period_end
                );

                $customer_score = $scores->where('customer_id', $customerId)->first();
            } catch (\Exception $e) {
                // Ignore score calculation errors
            }

            // Get customer engagements
            $engagements = CustomerEngagement::with(['recordedBy'])
                ->where('business_id', $business_id)
                ->where('customer_id', $customerId)
                ->orderBy('recorded_date', 'desc')
                ->limit(10) // Get latest 10 engagements
                ->get()
                ->map(function($engagement) {
                    return [
                        'id' => $engagement->id,
                        'engagement_type' => $engagement->engagement_type,
                        'engagement_type_name' => $engagement->engagement_type_name,
                        'platform' => $engagement->platform,
                        'platform_icon' => $engagement->platform_icon,
                        'platform_color' => $engagement->platform_color,
                        'reference_url' => $engagement->reference_url,
                        'verification_notes' => $engagement->verification_notes,
                        'points' => $engagement->points,
                        'recorded_by' => $engagement->recordedBy ? $engagement->recordedBy->first_name . ' ' . $engagement->recordedBy->surname : 'Unknown',
                        'recorded_date' => $engagement->recorded_date ? $engagement->recorded_date->format('d M Y') : 'Unknown',
                        'status' => $engagement->status
                    ];
                });

            // Prepare response data
            $response_data = [
                'success' => true,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name ?? 'Unknown',
                    'supplier_business_name' => $customer->supplier_business_name ?? '',
                    'contact_id' => $customer->contact_id ?? '',
                    'mobile' => $customer->mobile ?? '',
                    'email' => $customer->email ?? '',
                    'address_line_1' => $customer->address_line_1 ?? '',
                    'city' => $customer->city ?? '',
                    'state' => $customer->state ?? '',
                    'registered_date' => $customer->created_at ? $customer->created_at->format('d M Y') : 'Unknown'
                ],
                'ranking' => $customer_score ? [
                    'rank_position' => $customer_score['rank_position'],
                    'rank_suffix' => $this->getRankSuffix($customer_score['rank_position']),
                    'sales_total' => $customer_score['sales_total'],
                    'engagement_points' => $customer_score['engagement_points'],
                    'final_score' => $customer_score['final_score'],
                    'transaction_count' => $customer_score['transaction_count'],
                    'avg_transaction_value' => $customer_score['avg_transaction_value']
                ] : null,
                'purchase_details' => $details,
                'engagements' => $engagements,
                'engagement_stats' => [
                    'total_engagements' => $engagements->count(),
                    'total_points' => $engagements->sum('points'),
                    'recent_engagement' => $engagements->first()
                ],
                'period' => [
                    'type' => $period_type,
                    'start' => $period_start,
                    'end' => $period_end,
                    'label' => $this->getPeriodLabel($period_type, $period_start, $period_end)
                ]
            ];

            return response()->json($response_data);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error loading customer details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get engagement data for DataTables
     */
    public function getEngagementsData(Request $request)
    {
        if (!auth()->user()->can('customer_recognition.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        // Get filter parameters
        $period_type = $request->get('period_type', 'monthly');
        $location_id = $request->get('location_id');

        // Build query for engagements
        $query = CustomerEngagement::with(['customer', 'recordedBy'])
            ->where('business_id', $business_id);

        // Apply location filter if specified
        if (!empty($location_id)) {
            // If we need to filter by location, we'd need to join with contacts
            // For now, we'll include all engagements
            // Future enhancement: add location filtering based on customer's default location
        }

        // Apply date filtering based on period type if needed
        // For now, we'll show all engagements, but this could be filtered by date
        // Future enhancement: filter by current period dates

        $engagements = $query->orderBy('recorded_date', 'desc');

        return DataTables::of($engagements)
            ->addColumn('customer_name', function ($engagement) {
                if (!$engagement->customer) {
                    return 'Unknown Customer';
                }
                
                $display = '<div>';
                if (!empty($engagement->customer->supplier_business_name)) {
                    $display .= '<strong>' . $engagement->customer->supplier_business_name . '</strong><br>';
                    $display .= $engagement->customer->name;
                } else {
                    $display .= '<strong>' . $engagement->customer->name . '</strong>';
                }
                
                if (!empty($engagement->customer->mobile)) {
                    $display .= '<br><small class="text-muted">' . $engagement->customer->mobile . '</small>';
                }
                $display .= '</div>';
                
                return $display;
            })
            ->addColumn('engagement_type_name', function ($engagement) {
                return $engagement->engagement_type_name;
            })
            ->editColumn('platform', function ($engagement) {
                if (!$engagement->platform) {
                    return '-';
                }
                
                $icon = $engagement->platform_icon;
                $color = $engagement->platform_color;
                
                return '<span style="color: ' . $color . ';"><i class="fa ' . $icon . '"></i> ' . 
                       ucfirst($engagement->platform) . '</span>';
            })
            ->editColumn('reference_url', function ($engagement) {
                if (!$engagement->reference_url) {
                    return '-';
                }
                
                $displayUrl = strlen($engagement->reference_url) > 30 ? 
                    substr($engagement->reference_url, 0, 30) . '...' : 
                    $engagement->reference_url;
                    
                return '<a href="' . $engagement->reference_url . '" target="_blank" class="text-primary" title="' . $engagement->reference_url . '">' . 
                       $displayUrl . '</a>';
            })
            ->editColumn('verification_notes', function ($engagement) {
                if (!$engagement->verification_notes) {
                    return '-';
                }
                
                $displayText = strlen($engagement->verification_notes) > 50 ? 
                    substr($engagement->verification_notes, 0, 50) . '...' : 
                    $engagement->verification_notes;
                    
                return '<span title="' . htmlspecialchars($engagement->verification_notes) . '">' . 
                       $displayText . '</span>';
            })
            ->editColumn('points', function ($engagement) {
                return '<span class="badge bg-blue">' . $engagement->points . '</span>';
            })
            ->addColumn('recorded_by_name', function ($engagement) {
                return $engagement->recordedBy ? $engagement->recordedBy->first_name . ' ' . $engagement->recordedBy->surname : '-';
            })
            ->editColumn('recorded_date', function ($engagement) {
                return $engagement->recorded_date ? $engagement->recorded_date->format('Y-m-d') : '-';
            })
            ->editColumn('status', function ($engagement) {
                $statusClass = $engagement->status === 'verified' ? 'success' : 
                             ($engagement->status === 'pending' ? 'warning' : 'default');
                return '<span class="label label-' . $statusClass . '">' . 
                       ucfirst($engagement->status) . '</span>';
            })
            ->rawColumns(['customer_name', 'engagement_type_name', 'platform', 'reference_url', 'verification_notes', 'points', 'recorded_by_name', 'status'])
            ->make(true);
    }

// In CustomerRecognitionController.php - CONTROLLER METHOD
public function finalizePeriod(Request $request)
{
    if (!auth()->user()->can('customer_recognition.manage')) {
        abort(403, 'Unauthorized action.');
    }

    $request->validate([
        'period_type' => 'required|in:weekly,monthly,yearly',
        'winner_count' => 'integer|min:1|max:100'
    ]);

    try {
        // Extract from request
        $business_id = $request->session()->get('user.business_id');
        $period_type = $request->get('period_type');
        $winner_count = $request->get('winner_count', 10);
        $dates = $this->getPeriodDatesFromRequest($request, $period_type);

        // Call utility with error handling
        $period = CustomerRecognitionUtil::finalizePeriod(
            $business_id,
            $period_type,
            $dates['start'],
            $dates['end'],
            $winner_count,
            auth()->user()->id
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Period finalized successfully with ' . $period->winners_count . ' winners',
            'period_id' => $period->id,
            'winners_count' => $period->winners_count
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Period finalization failed',
            'message' => $e->getMessage()
        ], 500);
    }
}

public function awardCustomer(Request $request)
{
    if (!auth()->user()->can('customer_recognition.manage')) {
        abort(403, 'Unauthorized action.');
    }

    // Dynamic validation rules based on award type
    $rules = [
        'customer_id' => 'required|integer',
        'award_type' => 'required|in:manual,catalog',
        'notes' => 'nullable|string'
    ];

    if ($request->award_type === 'manual') {
        $rules['gift_description'] = 'required|string';
        $rules['gift_monetary_value'] = 'nullable|numeric|min:0';
    } elseif ($request->award_type === 'catalog') {
        $rules['catalog_item_id'] = 'required|integer';
        $rules['award_quantity'] = 'required|integer|min:1|max:999';
        // Note: We'll validate this is a valid product variation ID in the logic
    }

    $request->validate($rules);

    try {
        $business_id = $request->session()->get('user.business_id');
        $period_type = $request->get('period_type', 'monthly');
        $dates = $this->getPeriodDatesFromRequest($request, $period_type);

        // Find or create customer award record
        $award = CustomerAward::firstOrCreate([
            'business_id' => $business_id,
            'customer_id' => $request->customer_id,
            'period_type' => $period_type,
            'period_start' => $dates['start']
        ], [
            'period_end' => $dates['end'],
            'rank_position' => $request->get('rank_position', 1),
            'sales_total' => 0,
            'engagement_points' => 0,
            'final_score' => 0
        ]);

        // Update award with current data if not finalized
        if (!$award->period || !$award->period->is_finalized) {
            $scores = CustomerRecognitionUtil::calculateCustomerScores(
                $business_id, 
                $period_type, 
                $dates['start'], 
                $dates['end']
            );
            
            $customer_score = $scores->where('customer_id', $request->customer_id)->first();
            
            if ($customer_score) {
                $award->update([
                    'rank_position' => $customer_score['rank_position'],
                    'sales_total' => $customer_score['sales_total'],
                    'engagement_points' => $customer_score['engagement_points'],
                    'final_score' => $customer_score['final_score'],
                    'transaction_count' => $customer_score['transaction_count'],
                    'avg_transaction_value' => $customer_score['avg_transaction_value']
                ]);
            }
        }

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
        // First try to get from location filter, then session, then default
        $location_id = $request->get('location_id') ??
                      $request->session()->get('user.business_location_id') ??
                      \App\BusinessLocation::where('business_id', $business_id)->first()->id ?? null;

        // Award the customer
        $awarded = CustomerRecognitionUtil::awardCustomer(
            $award->id,
            $award_data,
            auth()->user()->id,
            $location_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Customer awarded successfully',
            'award' => $awarded
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

/**
 * Unaward (remove) a customer award and restore stock
 */
public function unawardCustomer(Request $request)
{
    if (!auth()->user()->can('customer_recognition.manage')) {
        abort(403, 'Unauthorized action.');
    }

    $request->validate([
        'award_id' => 'required|integer|exists:customer_awards,id',
        'confirmation' => 'required|string'
    ]);

    try {
        $business_id = $request->session()->get('user.business_id');
        
        // Find the award
        $award = CustomerAward::where('business_id', $business_id)
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
                    \App\VariationLocationDetails::where('variation_id', $award->catalog_item_id)
                        ->where('product_id', $variation->product_id)
                        ->where('location_id', $location_id)
                        ->increment('qty_available', $quantity); // Positive increment = restore
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

        return response()->json([
            'success' => true,
            'message' => 'Customer award removed successfully and stock restored (if applicable)',
            'award' => $award
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    /**
     * Record customer engagement
     */
    public function recordEngagement(Request $request)
    {
        if (!auth()->user()->can('customer_recognition.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'customer_id' => 'required|integer',
            'engagement_type' => 'required|in:youtube_follow,facebook_follow,instagram_follow,twitter_follow,content_share,review,google_review,referral,other',
            'points' => 'required|integer|min:0|max:10',
            'verification_notes' => 'required|string',
            'platform' => 'nullable|string',
            'reference_url' => 'nullable|url'
        ]);

        try {
            $business_id = $request->session()->get('user.business_id');

            $engagement = CustomerEngagement::create([
                'business_id' => $business_id,
                'customer_id' => $request->customer_id,
                'engagement_type' => $request->engagement_type,
                'points' => $request->points,
                'verification_notes' => $request->verification_notes,
                'platform' => $request->platform,
                'reference_url' => $request->reference_url,
                'recorded_by' => auth()->user()->id,
                'recorded_date' => Carbon::now()->format('Y-m-d'),
                'status' => 'verified'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Engagement recorded successfully',
                'engagement' => $engagement
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Get chart data for trends
     */
    public function getChartData(Request $request)
    {
        if (!auth()->user()->can('customer_recognition.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $period_type = $request->get('period_type', 'monthly');
            $months_back = $request->get('months_back', 12);

            $statistics = CustomerRecognitionUtil::getPeriodStatistics($business_id, $period_type, $months_back);

            return response()->json([
                'success' => true,
                'chart_data' => $statistics
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Chart data loading failed'], 500);
        }
    }

/**
 * Export customer recognition data - WITH CURRENCY FORMATTING
 */
public function export(Request $request)
{
    if (!auth()->user()->can('customer_recognition.export')) {
        abort(403, 'Unauthorized action.');
    }

    try {
        $business_id = $request->session()->get('user.business_id');
        $period_type = $request->get('period_type', 'monthly');
        $winner_count = $request->get('winner_count', 50);
        
        $dates = $this->getPeriodDatesFromRequest($request, $period_type);
        
        $scores = CustomerRecognitionUtil::calculateCustomerScores(
            $business_id, 
            $period_type, 
            $dates['start'], 
            $dates['end']
        );

        $filename = 'customer_recognition_' . $period_type . '_' . date('Y_m_d_H_i_s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($scores, $period_type, $dates) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Rank',
                'Customer Name',
                'Business Name',
                'Mobile',
                'Sales Total',
                'Transaction Count',
                'Avg Transaction Value',
                'Engagement Points',
                'Final Score',
                'Period Type',
                'Period Start',
                'Period End'
            ]);

            foreach ($scores as $score) {
                fputcsv($file, [
                    $score['rank_position'],
                    $score['customer_name'],
                    $score['customer_business_name'] ?? '',
                    $score['customer_mobile'] ?? '',
                    // Use TransactionUtil for proper currency formatting
                    $this->transactionUtil->num_f($score['sales_total'], false),
                    $score['transaction_count'],
                    $this->transactionUtil->num_f($score['avg_transaction_value'], false),
                    $score['engagement_points'],
                    number_format($score['final_score'], 2),
                    ucfirst($period_type),
                    $dates['start'],
                    $dates['end']
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Export failed'], 500);
    }
}

    /**
     * Helper method to get period dates from request
     */
    private function getPeriodDatesFromRequest($request, $period_type)
    {
        $custom_start = $request->get('custom_period_start');
        $custom_end = $request->get('custom_period_end');

        if ($custom_start && $custom_end) {
            return [
                'start' => $custom_start,
                'end' => $custom_end
            ];
        }

        return AwardPeriod::getPeriodDates($period_type);
    }

    /**
     * Helper method to get rank suffix
     */
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

    /**
     * Helper method to get period label
     */
    private function getPeriodLabel($period_type, $start_date, $end_date)
    {
        $start = Carbon::parse($start_date);
        $end = Carbon::parse($end_date);

        switch ($period_type) {
            case 'weekly':
                return 'Week of ' . $start->format('M d') . ' - ' . $end->format('M d, Y');
            case 'monthly':
                return $start->format('F Y');
            case 'yearly':
                return $start->format('Y');
            default:
                return $start->format('M d') . ' - ' . $end->format('M d, Y');
        }
    }
}