<?php

namespace Modules\AdvancedReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\ExpenseCategory;
use App\Transaction;
use App\BusinessLocation;
use App\User;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\TransactionUtil;
use App\Utils\ModuleUtil;
use App\Utils\BusinessUtil;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\AdvancedReports\Exports\ExpenseMonthlyExport;

class ExpenseMonthlyController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $transactionUtil;
    protected $moduleUtil;
    protected $businessUtil;

    /**
     * Constructor
     */
    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, BusinessUtil $businessUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->businessUtil = $businessUtil;
    }

    /**
     * Display expense monthly report
     */
    public function index()
    {
        if (!auth()->user()->can('expense_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Get dropdowns for filters
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $expense_categories = ExpenseCategory::where('business_id', $business_id)->pluck('name', 'id');
        $expense_categories = collect($expense_categories)->prepend(__('lang_v1.all'), '');
        $users = User::forDropdown($business_id, false, false, true);

        return view('advancedreports::expense-monthly.index')
            ->with(compact(
                'business_locations',
                'expense_categories', 
                'users'
            ));
    }

    /**
     * Get expense monthly data for DataTables
     */
    public function getExpenseMonthlyData(Request $request)
    {
        if (!auth()->user()->can('expense_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $year = $request->get('year', date('Y'));

        try {
            // Build base query for expense data
            $query = Transaction::leftjoin('expense_categories as ec', 'transactions.expense_category_id', '=', 'ec.id')
                ->where('transactions.business_id', $business_id)
                ->whereIn('transactions.type', ['expense', 'expense_refund'])
                ->whereYear('transactions.transaction_date', $year);

            // Apply filters
            $this->applyFilters($query, $request);

            // Group by expense category and get monthly data
            $monthlyData = $query->select([
                'ec.id as category_id',
                'ec.name as category_name',
                DB::raw('MONTH(transactions.transaction_date) as month'),
                DB::raw('SUM(IF(transactions.type="expense_refund", -1 * transactions.final_total, transactions.final_total)) as monthly_expense')
            ])
                ->groupBy('ec.id', 'ec.name', DB::raw('MONTH(transactions.transaction_date)'))
                ->get();

            // Transform data into the required format
            $expenseData = [];
            foreach ($monthlyData as $data) {
                $categoryId = $data->category_id ?: 0; // Use 0 for uncategorized expenses
                $categoryName = $data->category_name ?: __('report.others');

                if (!isset($expenseData[$categoryId])) {
                    $expenseData[$categoryId] = [
                        'category_id' => $categoryId,
                        'category_name' => $categoryName,
                        'jan' => 0,
                        'feb' => 0,
                        'mar' => 0,
                        'apr' => 0,
                        'may' => 0,
                        'jun' => 0,
                        'jul' => 0,
                        'aug' => 0,
                        'sep' => 0,
                        'oct' => 0,
                        'nov' => 0,
                        'dec' => 0,
                        'total_expense' => 0
                    ];
                }

                $monthNames = [
                    1 => 'jan', 2 => 'feb', 3 => 'mar', 4 => 'apr',
                    5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'aug',
                    9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dec'
                ];

                $monthKey = $monthNames[$data->month];
                $expenseAmount = (float)$data->monthly_expense;
                
                $expenseData[$categoryId][$monthKey] = $expenseAmount;
                $expenseData[$categoryId]['total_expense'] += $expenseAmount;
            }

            // Convert to collection for DataTables
            $collection = collect(array_values($expenseData));

            return DataTables::of($collection)
                ->addColumn('action', function ($row) {
                    return '<button type="button" class="btn btn-info btn-xs view-category-details" 
                            data-category-id="' . $row['category_id'] . '">
                        <i class="fa fa-eye"></i> ' . __('messages.view') . '
                    </button>';
                })
                ->editColumn('jan', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['jan'], 2) . '</span>';
                })
                ->editColumn('feb', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['feb'], 2) . '</span>';
                })
                ->editColumn('mar', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['mar'], 2) . '</span>';
                })
                ->editColumn('apr', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['apr'], 2) . '</span>';
                })
                ->editColumn('may', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['may'], 2) . '</span>';
                })
                ->editColumn('jun', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['jun'], 2) . '</span>';
                })
                ->editColumn('jul', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['jul'], 2) . '</span>';
                })
                ->editColumn('aug', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['aug'], 2) . '</span>';
                })
                ->editColumn('sep', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['sep'], 2) . '</span>';
                })
                ->editColumn('oct', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['oct'], 2) . '</span>';
                })
                ->editColumn('nov', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['nov'], 2) . '</span>';
                })
                ->editColumn('dec', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        number_format($row['dec'], 2) . '</span>';
                })
                ->editColumn('total_expense', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true"><strong>' .
                        number_format($row['total_expense'], 2) . '</strong></span>';
                })
                ->rawColumns([
                    'action', 'jan', 'feb', 'mar', 'apr', 'may', 'jun',
                    'jul', 'aug', 'sep', 'oct', 'nov', 'dec', 'total_expense'
                ])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Expense Monthly Data Error: ' . $e->getMessage());
            return response()->json(['error' => 'Error loading expense monthly data'], 500);
        }
    }

    /**
     * Get summary statistics
     */
    public function getSummary(Request $request)
    {
        if (!auth()->user()->can('expense_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $year = $request->get('year', date('Y'));

            $query = Transaction::leftjoin('expense_categories as ec', 'transactions.expense_category_id', '=', 'ec.id')
                ->where('transactions.business_id', $business_id)
                ->whereIn('transactions.type', ['expense', 'expense_refund'])
                ->whereYear('transactions.transaction_date', $year);

            // Apply same filters
            $this->applyFilters($query, $request);

            $summary = $query->select([
                DB::raw('COUNT(DISTINCT ec.id) as total_categories'),
                DB::raw('COUNT(DISTINCT transactions.id) as total_transactions'),
                DB::raw('SUM(IF(transactions.type="expense_refund", -1 * transactions.final_total, transactions.final_total)) as total_expense'),
                DB::raw('AVG(IF(transactions.type="expense_refund", -1 * transactions.final_total, transactions.final_total)) as average_expense')
            ])->first();

            return response()->json([
                'total_categories' => (int)$summary->total_categories,
                'total_transactions' => (int)$summary->total_transactions,
                'total_expense' => $summary->total_expense,
                'average_expense' => $summary->average_expense,
                'average_per_category' => $summary->total_categories > 0 ? ($summary->total_expense / $summary->total_categories) : 0
            ]);
        } catch (\Exception $e) {
            \Log::error('Expense Monthly Summary Error: ' . $e->getMessage());
            return response()->json(['error' => 'Summary calculation failed'], 500);
        }
    }

    /**
     * Export expense monthly data
     */
public function export(Request $request)
{
    if (!auth()->user()->can('expense_report.view')) {
        abort(403, 'Unauthorized action.');
    }

    try {
        $business_id = $request->session()->get('user.business_id');
        $year = $request->get('year', date('Y'));

        // Get data directly without Excel processing
        $query = Transaction::leftjoin('expense_categories as ec', 'transactions.expense_category_id', '=', 'ec.id')
            ->where('transactions.business_id', $business_id)
            ->whereIn('transactions.type', ['expense', 'expense_refund'])
            ->whereYear('transactions.transaction_date', $year);

        // Apply filters
        $this->applyFilters($query, $request);

        $monthlyData = $query->select([
            'ec.id as category_id',
            'ec.name as category_name',
            DB::raw('MONTH(transactions.transaction_date) as month'),
            DB::raw('SUM(IF(transactions.type="expense_refund", -1 * transactions.final_total, transactions.final_total)) as monthly_expense')
        ])
            ->groupBy('ec.id', 'ec.name', DB::raw('MONTH(transactions.transaction_date)'))
            ->get();

        // Transform data
        $expenseData = [];
        foreach ($monthlyData as $data) {
            $categoryId = $data->category_id ?: 0;
            $categoryName = $data->category_name ?: __('report.others');

            if (!isset($expenseData[$categoryId])) {
                $expenseData[$categoryId] = [
                    'category_name' => $categoryName,
                    'jan' => 0, 'feb' => 0, 'mar' => 0, 'apr' => 0,
                    'may' => 0, 'jun' => 0, 'jul' => 0, 'aug' => 0,
                    'sep' => 0, 'oct' => 0, 'nov' => 0, 'dec' => 0,
                    'total_expense' => 0
                ];
            }

            $months = [
                1 => 'jan', 2 => 'feb', 3 => 'mar', 4 => 'apr',
                5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'aug',
                9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dec'
            ];

            $monthKey = $months[$data->month] ?? 'jan';
            $expense = (float)($data->monthly_expense ?? 0);

            $expenseData[$categoryId][$monthKey] = $expense;
            $expenseData[$categoryId]['total_expense'] += $expense;
        }

        // Create CSV content
        $filename = 'expense_monthly_' . $year . '_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($expenseData) {
            $file = fopen('php://output', 'w');

            // Add headers
            fputcsv($file, [
                'Category',
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
                'Total Expense'
            ]);

            // Add data
            foreach ($expenseData as $row) {
                fputcsv($file, [
                    $row['category_name'],
                    number_format($row['jan'], 2),
                    number_format($row['feb'], 2),
                    number_format($row['mar'], 2),
                    number_format($row['apr'], 2),
                    number_format($row['may'], 2),
                    number_format($row['jun'], 2),
                    number_format($row['jul'], 2),
                    number_format($row['aug'], 2),
                    number_format($row['sep'], 2),
                    number_format($row['oct'], 2),
                    number_format($row['nov'], 2),
                    number_format($row['dec'], 2),
                    number_format($row['total_expense'], 2)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    } catch (\Exception $e) {
        \Log::error('Expense Monthly Export error: ' . $e->getMessage());
        return response()->json(['error' => 'Export failed: ' . $e->getMessage()], 500);
    }
}

    /**
     * Get category details for a specific expense category
     */
    public function getCategoryDetails(Request $request, $categoryId)
    {
        if (!auth()->user()->can('expense_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $year = $request->get('year', date('Y'));

            // Get category info
            if ($categoryId == 0) {
                $category = (object)[
                    'id' => 0,
                    'name' => __('report.others'),
                    'description' => 'Uncategorized expenses'
                ];
            } else {
                $category = ExpenseCategory::where('business_id', $business_id)
                    ->where('id', $categoryId)
                    ->first();

                if (!$category) {
                    return response()->json(['error' => 'Category not found'], 404);
                }
            }

            // Get expense transactions for this category and year
            $query = Transaction::leftjoin('expense_categories as ec', 'transactions.expense_category_id', '=', 'ec.id')
                ->leftjoin('users as u', 'transactions.created_by', '=', 'u.id')
                ->where('transactions.business_id', $business_id)
                ->whereIn('transactions.type', ['expense', 'expense_refund'])
                ->whereYear('transactions.transaction_date', $year);

            if ($categoryId == 0) {
                $query->whereNull('transactions.expense_category_id');
            } else {
                $query->where('transactions.expense_category_id', $categoryId);
            }

            $expenses = $query->select([
                'transactions.id as transaction_id',
                'transactions.ref_no',
                'transactions.transaction_date',
                'transactions.final_total',
                'transactions.type',
                'transactions.additional_notes',
                'u.first_name',
                'u.last_name',
                DB::raw('MONTH(transactions.transaction_date) as month'),
                DB::raw('MONTHNAME(transactions.transaction_date) as month_name')
            ])
                ->orderBy('transactions.transaction_date', 'desc')
                ->limit(50)
                ->get();

            // Calculate summary data
            $totalAmount = $expenses->sum(function($expense) {
                return $expense->type == 'expense_refund' ? -$expense->final_total : $expense->final_total;
            });
            $totalTransactions = $expenses->count();
            $averagePerTransaction = $totalTransactions > 0 ? ($totalAmount / $totalTransactions) : 0;

            // Group by month for monthly summary
            $monthlySummary = $expenses->groupBy('month')->map(function ($monthExpenses, $month) {
                $monthTotal = $monthExpenses->sum(function($expense) {
                    return $expense->type == 'expense_refund' ? -$expense->final_total : $expense->final_total;
                });
                
                return [
                    'month' => $month,
                    'month_name' => $monthExpenses->first()->month_name,
                    'total_transactions' => $monthExpenses->count(),
                    'total_amount' => $monthTotal
                ];
            })->values();

            return response()->json([
                'success' => true,
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description ?? ''
                ],
                'expenses' => $expenses->map(function ($expense) {
                    $amount = $expense->type == 'expense_refund' ? -$expense->final_total : $expense->final_total;
                    return [
                        'transaction_id' => $expense->transaction_id,
                        'ref_no' => $expense->ref_no,
                        'transaction_date' => $expense->transaction_date,
                        'amount' => $amount,
                        'type' => $expense->type,
                        'additional_notes' => $expense->additional_notes,
                        'created_by' => trim($expense->first_name . ' ' . $expense->last_name),
                        'month' => $expense->month,
                        'month_name' => $expense->month_name
                    ];
                }),
                'monthly_summary' => $monthlySummary,
                'overall_summary' => [
                    'total_transactions' => $totalTransactions,
                    'total_amount' => $totalAmount,
                    'average_per_transaction' => $averagePerTransaction
                ],
                'year' => $year
            ]);
        } catch (\Exception $e) {
            \Log::error('Category Details Error: ' . $e->getMessage());
            return response()->json(['error' => 'Error loading category details: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, $request)
    {
        // Location filter
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }

        if (!empty($request->location_id)) {
            $query->where('transactions.location_id', $request->location_id);
        }

        // Category filter
        if (!empty($request->category_id)) {
            $query->where('transactions.expense_category_id', $request->category_id);
        }

        // User filter
        if (!empty($request->created_by)) {
            $query->where('transactions.created_by', $request->created_by);
        }

        // Expense for filter
        if (!empty($request->expense_for)) {
            $query->where('transactions.expense_for', $request->expense_for);
        }
    }

     /**
     * Print expense monthly report
     */
    public function print(Request $request)
    {
        if (!auth()->user()->can('expense_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $year = $request->get('year', date('Y'));

            // Get business name
            $business_name = \App\Business::find($business_id)->name ?? config('app.name');

            // Build query for expense data
            $query = Transaction::leftjoin('expense_categories as ec', 'transactions.expense_category_id', '=', 'ec.id')
                ->where('transactions.business_id', $business_id)
                ->whereIn('transactions.type', ['expense', 'expense_refund'])
                ->whereYear('transactions.transaction_date', $year);

            // Apply filters
            $this->applyFilters($query, $request);

            // Get monthly data
            $monthlyData = $query->select([
                'ec.id as category_id',
                'ec.name as category_name',
                DB::raw('MONTH(transactions.transaction_date) as month'),
                DB::raw('SUM(IF(transactions.type="expense_refund", -1 * transactions.final_total, transactions.final_total)) as monthly_expense')
            ])
                ->groupBy('ec.id', 'ec.name', DB::raw('MONTH(transactions.transaction_date)'))
                ->get();

            // Transform data
            $expenseData = [];
            foreach ($monthlyData as $data) {
                $categoryId = $data->category_id ?: 0;
                $categoryName = $data->category_name ?: __('report.others');

                if (!isset($expenseData[$categoryId])) {
                    $expenseData[$categoryId] = [
                        'category_name' => $categoryName,
                        'jan' => 0, 'feb' => 0, 'mar' => 0, 'apr' => 0,
                        'may' => 0, 'jun' => 0, 'jul' => 0, 'aug' => 0,
                        'sep' => 0, 'oct' => 0, 'nov' => 0, 'dec' => 0,
                        'total_expense' => 0
                    ];
                }

                $months = [
                    1 => 'jan', 2 => 'feb', 3 => 'mar', 4 => 'apr',
                    5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'aug',
                    9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dec'
                ];

                $monthKey = $months[$data->month] ?? 'jan';
                $expense = (float)($data->monthly_expense ?? 0);

                $expenseData[$categoryId][$monthKey] = $expense;
                $expenseData[$categoryId]['total_expense'] += $expense;
            }

            // Calculate summary
            $summary = [
                'total_categories' => count($expenseData),
                'total_transactions' => $monthlyData->count(),
                'total_expense' => array_sum(array_column($expenseData, 'total_expense')),
                'average_per_category' => count($expenseData) > 0 ? (array_sum(array_column($expenseData, 'total_expense')) / count($expenseData)) : 0
            ];

            // Prepare filters for display
            $filters = [];
            if ($request->location_id) {
                $location = \App\BusinessLocation::find($request->location_id);
                $filters['location_name'] = $location ? $location->name : '';
            }
            if ($request->category_id) {
                $category = \App\ExpenseCategory::find($request->category_id);
                $filters['category_name'] = $category ? $category->name : '';
            }
            if ($request->created_by) {
                $user = \App\User::find($request->created_by);
                $filters['created_by_name'] = $user ? $user->username : '';
            }

            return view('advancedreports::expense-monthly.print', compact(
                'expenseData',
                'summary',
                'business_name',
                'year',
                'filters'
            ));

        } catch (\Exception $e) {
            \Log::error('Expense Monthly Print Error: ' . $e->getMessage());
            return response()->view('errors.500', ['error' => 'Print failed: ' . $e->getMessage()], 500);
        }
    }
}