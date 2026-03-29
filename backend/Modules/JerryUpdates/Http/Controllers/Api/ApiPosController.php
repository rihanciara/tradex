<?php

namespace Modules\JerryUpdates\Http\Controllers\Api;

use App\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ApiPosController extends Controller
{
    private function checkVercelApiMode()
    {
        // Get the system property for vercel api mode since it might be accessed without auth
        $vercel_mode = \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_vercel_api', '0');
        // If not set globally, we assume it's disabled for unauthenticated access. 
        // A better approach would be to pass a tenant identifier, but for a proof of concept we'll use a global fallback.
        if ($vercel_mode !== '1') {
             $vercel_mode_db = \App\System::getProperty('jerry_vercel_api');
             if($vercel_mode_db == '1') return true;
        }
        return $vercel_mode === '1';
    }

    /**
     * VERCEL NEXT.JS API: Initial load payload for POS.
     * Provides business settings, register status, and taxes.
     */
    public function init(Request $request)
    {
        if (!$this->checkVercelApiMode()) {
            return response()->json(['success' => false, 'msg' => 'Vercel API Mode is disabled.'], 403);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'msg' => 'Unauthenticated.'], 401);
        }
        $business_id = $user->business_id;

        $location_id = $request->get('location_id');
        if (!$location_id) {
            $location = BusinessLocation::where('business_id', $business_id)->first();
            $location_id = $location ? $location->id : null;
        }

        // 1. Business & Currency Settings
        $business = DB::table('business as b')
            ->leftJoin('currencies as c', 'b.currency_id', '=', 'c.id')
            ->where('b.id', $business_id)
            ->select(
                'b.name',
                'b.pos_settings',
                'c.code as currency_code',
                'c.symbol as currency_symbol',
                'c.thousand_separator',
                'c.decimal_separator'
            )
            ->first();

        $pos_settings = $business && $business->pos_settings ? json_decode($business->pos_settings, true) : [];

        // 2. Active Tax Rates
        $tax_rates = DB::table('tax_rates')
            ->where('business_id', $business_id)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'amount')
            ->get();

        // 3. Cash Register Status
        $open_register = DB::table('cash_registers')
            ->where('business_id', $business_id)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        // 4. Payment Methods
        $payment_methods = [
            ['id' => 'cash', 'label' => 'Cash'],
            ['id' => 'card', 'label' => 'Card'],
            ['id' => 'custom', 'label' => 'Custom'],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'business' => [
                    'name' => $business ? $business->name : '',
                    'currency_code' => $business ? $business->currency_code : 'USD',
                    'currency_symbol' => $business ? $business->currency_symbol : '$',
                    'thousand_separator' => $business ? $business->thousand_separator : ',',
                    'decimal_separator' => $business ? $business->decimal_separator : '.',
                ],
                'pos_settings' => $pos_settings,
                'location_id' => $location_id,
                'tax_rates' => $tax_rates,
                'payment_methods' => $payment_methods,
                'register' => [
                    'is_open' => $open_register ? true : false,
                    'register_id' => $open_register ? $open_register->id : null,
                ],
                'user' => [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                ]
            ]
        ]);
    }

    /**
     * VERCEL NEXT.JS API: Fetch ultra-fast, flattened product catalog for IndexedDB.
     * Bypasses slow ProductUtil ORM models for raw DB performance.
     */
    public function getCatalog(Request $request)
    {
        if (!$this->checkVercelApiMode()) {
            return response()->json(['success' => false, 'msg' => 'Vercel API Mode is disabled.'], 403);
        }

        // 1. Authentication & Tenant Scope
        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'msg' => 'Unauthenticated.'], 401);
        }
        $business_id = $user->business_id;
        $location_id = $request->get('location_id');

        if (!$location_id) {
            $location = BusinessLocation::where('business_id', $business_id)->first();
            $location_id = $location ? $location->id : null;
        }

        if (!$location_id) {
            return response()->json(['success' => false, 'msg' => 'No active location found'], 400);
        }

        // 2. Pagination for massive catalogs (50k+ items)
        $offset = (int) $request->get('offset', 0);
        $limit = (int) $request->get('limit', 1000);

        // 3. Raw, hyper-optimized SQL Query (Zero Eloquent Hydration Overhead)
        $query = DB::table('products as p')
            ->join('variations as v', 'p.id', '=', 'v.product_id')
            ->leftJoin('variation_location_details as vld', function ($join) use ($location_id) {
                $join->on('v.id', '=', 'vld.variation_id')
                     ->where('vld.location_id', '=', $location_id);
            })
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->where('p.business_id', $business_id)
            ->where('p.is_inactive', 0)
            ->where('p.not_for_selling', 0)
            ->whereNull('v.deleted_at')
            ->select([
                'p.id as product_id',
                'v.id as variation_id',
                'p.name as product_name',
                'v.name as variation_name',
                'p.type as product_type',
                'p.sku as product_sku',
                'v.sub_sku as variation_sku',
                'u.actual_name as unit',
                'u.allow_decimal',
                'b.name as brand',
                'c.name as category',
                'p.enable_stock',
                'p.enable_sr_no',
                'p.image as product_image',
                'v.default_sell_price as sell_price_exc_tax',
                'v.sell_price_inc_tax',
                'p.tax as tax_id',
                'p.tax_type',
                DB::raw('COALESCE(vld.qty_available, 0) as current_stock')
            ])
            ->orderBy('p.id', 'asc')
            ->orderBy('v.id', 'asc')
            ->offset($offset)
            ->limit($limit);

        $products = $query->get();

        return response()->json([
            'success' => true,
            'data' => $products,
            'count' => $products->count(),
            'offset' => $offset,
            'has_more' => $products->count() === $limit
        ]);
    }

    /**
     * VERCEL NEXT.JS API: Fetch customers for debounced search.
     */
    public function getCustomers(Request $request)
    {
        if (!$this->checkVercelApiMode()) {
            return response()->json(['success' => false, 'msg' => 'Vercel API Mode is disabled.'], 403);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'msg' => 'Unauthenticated.'], 401);
        }
        $business_id = $user->business_id;
        $search = $request->get('search');

        $query = DB::table('contacts')
            ->where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both']);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('mobile', 'like', '%' . $search . '%')
                  ->orWhere('contact_id', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $customers = $query->select('id', 'name', 'mobile', 'contact_id', 'email', 'balance')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    public function getTaxonomies(Request $request)
    {
        if (!$this->checkVercelApiMode()) {
            return response()->json(['success' => false, 'msg' => 'Vercel API Mode is disabled.'], 403);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'msg' => 'Unauthenticated.'], 401);
        }
        $business_id = $user->business_id;

        // Fetch Categories
        $categories = DB::table('categories')
            ->where('business_id', $business_id)
            ->where('category_type', 'product')
            ->whereNull('deleted_at')
            ->select('id', 'name', 'parent_id')
            ->orderBy('name', 'asc')
            ->get();

        // Fetch Brands
        $brands = DB::table('brands')
            ->where('business_id', $business_id)
            ->whereNull('deleted_at')
            ->select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'brands' => $brands
            ]
        ]);
    }

    /**
     * VERCEL NEXT.JS API: Process raw checkout payload.
     * Inserts Transaction, Sell Lines, and Payments bypassing heavy ORM.
     */
    public function checkout(Request $request)
    {
        if (!$this->checkVercelApiMode()) {
            return response()->json(['success' => false, 'msg' => 'Vercel API Mode is disabled.'], 403);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'msg' => 'Unauthenticated.'], 401);
        }
        $business_id = $user->business_id;
        
        $location_id = $request->get('location_id');
        if (!$location_id) {
            $location = BusinessLocation::where('business_id', $business_id)->first();
            $location_id = $location ? $location->id : null;
        }

        if (!$location_id) {
            return response()->json(['success' => false, 'message' => 'No active location found'], 400);
        }

        try {
            DB::beginTransaction();

            // 1. Generate Invoice No (Raw simplified version)
            $ref_count = DB::table('transactions')
                ->where('business_id', $business_id)
                ->where('type', 'sell')
                ->count() + 1;
            $invoice_no = 'INV-' . str_pad($ref_count, 4, '0', STR_PAD_LEFT);

            $now = Carbon::now()->toDateTimeString();
            $final_total = $request->get('final_total', 0);

            // Determine payment status
            $total_paid = 0;
            $payments = $request->get('payment', []);
            foreach ($payments as $p) {
                $total_paid += (float)$p['amount'];
            }
            
            $payment_status = 'due';
            if ($total_paid >= $final_total && $final_total > 0) {
                $payment_status = 'paid';
            } elseif ($total_paid > 0 && $total_paid < $final_total) {
                $payment_status = 'partial';
            }

            // 2. Insert Transaction
            $transaction_data = [
                'business_id' => $business_id,
                'location_id' => $location_id,
                'type' => 'sell',
                'status' => 'final',
                'payment_status' => $payment_status,
                'contact_id' => $request->get('customer_id'),
                'invoice_no' => $invoice_no,
                'transaction_date' => $now,
                'total_before_tax' => $final_total, // Simplification for raw API
                'final_total' => $final_total,
                'created_by' => $user->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $transaction_id = DB::table('transactions')->insertGetId($transaction_data);

            // 3. Insert Sell Lines
            $sell_lines = [];
            $items = $request->get('items', []);
            foreach ($items as $item) {
                $sell_lines[] = [
                    'transaction_id' => $transaction_id,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'unit_price_inc_tax' => $item['unit_price'],
                    'item_tax' => 0,
                    'tax_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if (!empty($sell_lines)) {
                DB::table('transaction_sell_lines')->insert($sell_lines);
            }

            // 4. Insert Payments
            $payments_data = [];
            foreach ($payments as $payment) {
                if ((float)$payment['amount'] > 0) {
                    $method = $payment['method'] == 'custom' ? 'other' : $payment['method'];
                    $payments_data[] = [
                        'transaction_id' => $transaction_id,
                        'business_id' => $business_id,
                        'amount' => $payment['amount'],
                        'method' => $method,
                        'paid_on' => $now,
                        'created_by' => $user->id,
                        'payment_for' => $request->get('customer_id'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            if (!empty($payments_data)) {
                DB::table('transaction_payments')->insert($payments_data);
            }

            // TODO: Ideally trigger events for stock update and accounting hooks
            // But this bypass achieves the raw speed requested for the API proof of concept.

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale added successfully',
                'transaction_id' => $transaction_id,
                'invoice_no' => $invoice_no
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Checkout failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
