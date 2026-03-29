<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Spatie\Activitylog\Models\Activity;
use App\Transaction;
use App\TransactionPayment;
use App\Contact;
use App\User;
use App\Business;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class AuditTrailController extends Controller
{
    protected $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display the audit trail report dashboard
     */
    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        $business_id = request()->session()->get('user.business_id');
        $business = Business::find($business_id);

        // Get users for filter
        $users = User::where('business_id', $business_id)
                    ->pluck('first_name', 'id')
                    ->prepend(__('lang_v1.all'), '');

        // Define transaction types for compliance tracking
        $transaction_types = [
            'contact' => __('report.contact'),
            'user' => __('report.user'),
            'sell' => __('sale.sale'),
            'purchase' => __('lang_v1.purchase'),
            'sales_order' => __('lang_v1.sales_order'),
            'purchase_order' => __('lang_v1.purchase_order'),
            'sell_return' => __('lang_v1.sell_return'),
            'purchase_return' => __('lang_v1.purchase_return'),
            'sell_transfer' => __('lang_v1.stock_transfer'),
            'stock_adjustment' => __('stock_adjustment.stock_adjustment'),
            'expense' => __('lang_v1.expense'),
            'payment' => __('lang_v1.payment'),
        ];

        // Define risk categories
        $risk_categories = [
            'high' => __('High Risk'),
            'medium' => __('Medium Risk'),
            'low' => __('Low Risk'),
        ];

        // Define audit event types
        $audit_events = [
            'created' => __('Created'),
            'updated' => __('Updated'),
            'deleted' => __('Deleted'),
            'restored' => __('Restored'),
            'login' => __('Login'),
            'logout' => __('Logout'),
            'failed_login' => __('Failed Login'),
        ];

        return view('advancedreports::audit-trail.index', compact(
            'users',
            'transaction_types',
            'risk_categories',
            'audit_events',
            'business'
        ));
    }

    /**
     * Get audit trail data for DataTables
     */
    public function getAuditData(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        
        $activities = Activity::with(['subject'])
                            ->leftjoin('users as u', 'u.id', '=', 'activity_log.causer_id')
                            ->where('activity_log.business_id', $business_id)
                            ->select(
                                'activity_log.*',
                                DB::raw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as created_by"),
                                'u.email as user_email',
                                DB::raw("CASE 
                                    WHEN activity_log.description IN ('deleted', 'transaction_deleted', 'payment_deleted') THEN 'high'
                                    WHEN activity_log.description IN ('updated', 'transaction_updated', 'payment_updated') THEN 'medium'
                                    ELSE 'low'
                                END as risk_level")
                            );

        // Apply filters
        if (!empty($request->start_date) && !empty($request->end_date)) {
            $start = $request->start_date;
            $end = $request->end_date;
            $activities->whereDate('activity_log.created_at', '>=', $start)
                      ->whereDate('activity_log.created_at', '<=', $end);
        }

        if (!empty($request->user_id)) {
            $activities->where('causer_id', $request->user_id);
        }

        if (!empty($request->subject_type)) {
            $subject_type = $request->subject_type;
            if ($subject_type == 'contact') {
                $activities->where('subject_type', Contact::class);
            } elseif ($subject_type == 'user') {
                $activities->where('subject_type', User::class);
            } elseif ($subject_type == 'payment') {
                $activities->where('subject_type', TransactionPayment::class);
            } elseif (in_array($subject_type, ['sell', 'purchase', 'sales_order', 'purchase_order', 
                     'sell_return', 'purchase_return', 'sell_transfer', 'expense', 'stock_adjustment'])) {
                $activities->where('subject_type', Transaction::class);
                $activities->whereHasMorph('subject', Transaction::class, function ($q) use ($subject_type) {
                    $q->where('type', $subject_type);
                });
            }
        }

        if (!empty($request->risk_level)) {
            $risk_level = $request->risk_level;
            if ($risk_level == 'high') {
                $activities->whereIn('description', ['deleted', 'transaction_deleted', 'payment_deleted']);
            } elseif ($risk_level == 'medium') {
                $activities->whereIn('description', ['updated', 'transaction_updated', 'payment_updated']);
            } else {
                $activities->whereNotIn('description', ['deleted', 'transaction_deleted', 'payment_deleted', 
                                                      'updated', 'transaction_updated', 'payment_updated']);
            }
        }

        $transaction_types = [
            'contact' => __('report.contact'),
            'user' => __('report.user'),
            'sell' => __('sale.sale'),
            'purchase' => __('lang_v1.purchase'),
            'sales_order' => __('lang_v1.sales_order'),
            'purchase_order' => __('lang_v1.purchase_order'),
            'sell_return' => __('lang_v1.sell_return'),
            'purchase_return' => __('lang_v1.purchase_return'),
            'sell_transfer' => __('lang_v1.stock_transfer'),
            'stock_adjustment' => __('stock_adjustment.stock_adjustment'),
            'expense' => __('lang_v1.expense'),
            'payment' => __('lang_v1.payment'),
        ];

        return Datatables::of($activities)
                        ->editColumn('created_at', '{{@format_datetime($created_at)}}')
                        ->addColumn('subject_type_formatted', function ($row) use ($transaction_types) {
                            $subject_type = '';
                            if ($row->subject_type == Contact::class) {
                                $subject_type = __('contact.contact');
                            } elseif ($row->subject_type == User::class) {
                                $subject_type = __('report.user');
                            } elseif ($row->subject_type == Transaction::class && !empty($row->subject->type)) {
                                $subject_type = isset($transaction_types[$row->subject->type]) ? $transaction_types[$row->subject->type] : '';
                            } elseif ($row->subject_type == TransactionPayment::class) {
                                $subject_type = __('lang_v1.payment');
                            }
                            return $subject_type;
                        })
                        ->addColumn('risk_badge', function ($row) {
                            $risk_class = '';
                            $risk_text = '';
                            switch ($row->risk_level) {
                                case 'high':
                                    $risk_class = 'label-danger';
                                    $risk_text = 'High Risk';
                                    break;
                                case 'medium':
                                    $risk_class = 'label-warning';
                                    $risk_text = 'Medium Risk';
                                    break;
                                default:
                                    $risk_class = 'label-success';
                                    $risk_text = 'Low Risk';
                                    break;
                            }
                            return '<span class="label ' . $risk_class . '">' . $risk_text . '</span>';
                        })
                        ->addColumn('transaction_details', function ($row) {
                            $html = '';
                            if (!empty($row->subject) && method_exists($row->subject, 'getTable')) {
                                if (!empty($row->subject->ref_no)) {
                                    $html .= __('purchase.ref_no') . ': ' . $row->subject->ref_no . '<br>';
                                }
                                if (!empty($row->subject->invoice_no)) {
                                    $html .= __('sale.invoice_no') . ': ' . $row->subject->invoice_no . '<br>';
                                }
                                if (isset($row->subject->final_total)) {
                                    $html .= __('sale.total') . ': ' . number_format($row->subject->final_total, 2) . '<br>';
                                }
                            }
                            
                            // Add compliance verification info
                            $update_note = $row->getExtraProperty('update_note');
                            if (!empty($update_note) && !is_array($update_note)) {
                                $html .= '<strong>Note:</strong> ' . $update_note . '<br>';
                            }
                            
                            return $html;
                        })
                        ->addColumn('user_info', function ($row) {
                            $html = $row->created_by;
                            if (!empty($row->user_email)) {
                                $html .= '<br><small class="text-muted">' . $row->user_email . '</small>';
                            }
                            return $html;
                        })
                        ->addColumn('compliance_status', function ($row) {
                            // Determine compliance status based on various factors
                            $status = 'compliant';
                            $status_class = 'label-success';
                            
                            if (in_array($row->description, ['deleted', 'transaction_deleted'])) {
                                $status = 'requires_review';
                                $status_class = 'label-warning';
                            }
                            
                            if (empty($row->causer_id)) {
                                $status = 'non_compliant';
                                $status_class = 'label-danger';
                            }
                            
                            $status_text = ucfirst(str_replace('_', ' ', $status));
                            return '<span class="label ' . $status_class . '">' . $status_text . '</span>';
                        })
                        ->rawColumns(['risk_badge', 'transaction_details', 'user_info', 'compliance_status'])
                        ->filterColumn('created_by', function($query, $keyword) {
                            $query->whereRaw("LOWER(CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) LIKE ?", ["%{$keyword}%"]);
                        })
                        ->make(true);
    }

    /**
     * Get audit trail summary for dashboard
     */
    public function getSummary(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        
        // Date range for analysis
        $start_date = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $end_date = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        
        // Total activities
        $total_activities = Activity::where('business_id', $business_id)
                                  ->whereDate('created_at', '>=', $start_date)
                                  ->whereDate('created_at', '<=', $end_date)
                                  ->count();
        
        // High risk activities
        $high_risk_activities = Activity::where('business_id', $business_id)
                                      ->whereDate('created_at', '>=', $start_date)
                                      ->whereDate('created_at', '<=', $end_date)
                                      ->whereIn('description', ['deleted', 'transaction_deleted', 'payment_deleted'])
                                      ->count();
        
        // Transaction modifications
        $transaction_modifications = Activity::where('business_id', $business_id)
                                           ->where('subject_type', Transaction::class)
                                           ->whereIn('description', ['updated', 'deleted'])
                                           ->whereDate('created_at', '>=', $start_date)
                                           ->whereDate('created_at', '<=', $end_date)
                                           ->count();
        
        // Unique active users
        $active_users = Activity::where('business_id', $business_id)
                              ->whereDate('created_at', '>=', $start_date)
                              ->whereDate('created_at', '<=', $end_date)
                              ->distinct('causer_id')
                              ->count();
        
        // Activities by type
        $activities_by_type = Activity::where('activity_log.business_id', $business_id)
                                    ->leftjoin('transactions as t', function($join) {
                                        $join->on('activity_log.subject_id', '=', 't.id')
                                             ->where('activity_log.subject_type', Transaction::class);
                                    })
                                    ->whereDate('activity_log.created_at', '>=', $start_date)
                                    ->whereDate('activity_log.created_at', '<=', $end_date)
                                    ->select(
                                        DB::raw("CASE 
                                            WHEN activity_log.subject_type = '" . Contact::class . "' THEN 'Contact'
                                            WHEN activity_log.subject_type = '" . User::class . "' THEN 'User'
                                            WHEN activity_log.subject_type = '" . TransactionPayment::class . "' THEN 'Payment'
                                            WHEN activity_log.subject_type = '" . Transaction::class . "' THEN COALESCE(t.type, 'Transaction')
                                            ELSE 'Other'
                                        END as type"),
                                        DB::raw('COUNT(*) as count')
                                    )
                                    ->groupBy('type')
                                    ->get();
        
        // Activities by risk level
        $activities_by_risk = Activity::where('activity_log.business_id', $business_id)
                                    ->whereDate('activity_log.created_at', '>=', $start_date)
                                    ->whereDate('activity_log.created_at', '<=', $end_date)
                                    ->select(
                                        DB::raw("CASE 
                                            WHEN description IN ('deleted', 'transaction_deleted', 'payment_deleted') THEN 'High Risk'
                                            WHEN description IN ('updated', 'transaction_updated', 'payment_updated') THEN 'Medium Risk'
                                            ELSE 'Low Risk'
                                        END as risk_level"),
                                        DB::raw('COUNT(*) as count')
                                    )
                                    ->groupBy('risk_level')
                                    ->get();
        
        // Daily activity trend
        $daily_activities = Activity::where('activity_log.business_id', $business_id)
                                  ->whereDate('activity_log.created_at', '>=', $start_date)
                                  ->whereDate('activity_log.created_at', '<=', $end_date)
                                  ->select(
                                      DB::raw('DATE(activity_log.created_at) as date'),
                                      DB::raw('COUNT(*) as count')
                                  )
                                  ->groupBy('date')
                                  ->orderBy('date')
                                  ->get();
        
        // Top active users
        $top_users = Activity::where('activity_log.business_id', $business_id)
                           ->leftjoin('users as u', 'u.id', '=', 'activity_log.causer_id')
                           ->whereDate('activity_log.created_at', '>=', $start_date)
                           ->whereDate('activity_log.created_at', '<=', $end_date)
                           ->select(
                               DB::raw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as user_name"),
                               DB::raw('COUNT(*) as activity_count')
                           )
                           ->groupBy('causer_id', 'user_name')
                           ->orderBy('activity_count', 'desc')
                           ->limit(5)
                           ->get();

        return [
            'total_activities' => $total_activities,
            'high_risk_activities' => $high_risk_activities,
            'transaction_modifications' => $transaction_modifications,
            'active_users' => $active_users,
            'activities_by_type' => $activities_by_type,
            'activities_by_risk' => $activities_by_risk,
            'daily_activities' => $daily_activities,
            'top_users' => $top_users,
        ];
    }

    /**
     * Export audit trail data
     */
    public function export(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        
        $activities = Activity::with(['subject'])
                            ->leftjoin('users as u', 'u.id', '=', 'activity_log.causer_id')
                            ->where('activity_log.business_id', $business_id);

        // Apply same filters as the main data method
        if (!empty($request->start_date) && !empty($request->end_date)) {
            $activities->whereDate('activity_log.created_at', '>=', $request->start_date)
                      ->whereDate('activity_log.created_at', '<=', $request->end_date);
        }

        if (!empty($request->user_id)) {
            $activities->where('causer_id', $request->user_id);
        }

        $data = $activities->select(
            'activity_log.created_at',
            'activity_log.description',
            'activity_log.subject_type',
            'activity_log.subject_id',
            'activity_log.event',
            'activity_log.properties',
            DB::raw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as created_by"),
            'u.email as user_email'
        )->get();

        $filename = 'audit_trail_' . date('Y_m_d_H_i_s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Date/Time',
                'Action',
                'Subject Type',
                'Subject ID',
                'Event',
                'User',
                'User Email',
                'Risk Level',
                'Details'
            ]);

            foreach ($data as $row) {
                // Determine risk level
                $risk_level = 'Low';
                if (in_array($row->description, ['deleted', 'transaction_deleted', 'payment_deleted'])) {
                    $risk_level = 'High';
                } elseif (in_array($row->description, ['updated', 'transaction_updated', 'payment_updated'])) {
                    $risk_level = 'Medium';
                }

                // Format subject type
                $subject_type = '';
                if ($row->subject_type == Contact::class) {
                    $subject_type = 'Contact';
                } elseif ($row->subject_type == User::class) {
                    $subject_type = 'User';
                } elseif ($row->subject_type == Transaction::class) {
                    $subject_type = 'Transaction';
                } elseif ($row->subject_type == TransactionPayment::class) {
                    $subject_type = 'Payment';
                }

                fputcsv($file, [
                    $row->created_at,
                    $row->description,
                    $subject_type,
                    $row->subject_id,
                    $row->event,
                    $row->created_by,
                    $row->user_email,
                    $risk_level,
                    $row->properties
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}