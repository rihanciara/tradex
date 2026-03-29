<?php

namespace Modules\JerryUpdates\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;

class JerryUpdatesController extends Controller
{
    /**
     * Display the JerryUpdates dashboard.
     * @return Renderable
     */
    public function index()
    {
        $this->ensureAdminUser();

        $moduleStatus = $this->getModuleStatus();
        $overrides = $this->getOverridesList();
        $bugFixes = $this->getBugFixesList();
        $technicalChecks = $this->getTechnicalChecks();
        
        // Fetch properties (defaulting to 0/false)
        $dark_mode_enabled = \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_dark_mode') ?? '0';
        $ledger_fix_enabled = \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_ledger_fix') == '1';
        $running_balance_enabled = \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_running_balance') == '1';
        $system_black_theme_enabled = \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_system_black_theme') == '1';
        $jerry_pos_cache_enabled = \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_cache') == '1';
        $jerry_offline_mode_enabled = \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_offline_mode') == '1';
        $jerry_account_hardening_enabled = \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_account_hardening', '0') == '1';

        // Load changelog
        $changelogPath = module_path('JerryUpdates', 'changelog.json');
        $changelogs = [];
        if (file_exists($changelogPath)) {
            $changelogs = json_decode(file_get_contents($changelogPath), true) ?? [];
        }

        return view('jerryupdates::index', compact('moduleStatus', 'overrides', 'bugFixes', 'technicalChecks', 'dark_mode_enabled', 'ledger_fix_enabled', 'running_balance_enabled', 'system_black_theme_enabled', 'changelogs', 'jerry_pos_cache_enabled', 'jerry_offline_mode_enabled', 'jerry_account_hardening_enabled'));
    }

    /**
     * Store toggle settings.
     */
    public function storeSettings(Request $request)
    {
        $this->ensureAdminUser();

        $dark_mode = $request->input('jerry_dark_mode', '0');
        $ledger_fix = $request->has('jerry_ledger_fix') ? '1' : '0';
        $system_black_theme = $request->has('jerry_system_black_theme') ? '1' : '0';

        // Custom view toggle properties
        $toggles = [
            'jerry_dark_mode' => $dark_mode,
            'jerry_ledger_fix' => $ledger_fix,
            'jerry_running_balance' => $request->has('jerry_running_balance') ? '1' : '0',
            'jerry_system_black_theme' => $system_black_theme,
            'jerry_sell_tweaks' => $request->has('jerry_sell_tweaks') ? '1' : '0',
            'jerry_expense_tweaks' => $request->has('jerry_expense_tweaks') ? '1' : '0',
            'jerry_product_tweaks' => $request->has('jerry_product_tweaks') ? '1' : '0',
            'jerry_contact_tweaks' => $request->has('jerry_contact_tweaks') ? '1' : '0',
            'jerry_label_tweaks' => $request->has('jerry_label_tweaks') ? '1' : '0',
            'jerry_invoice_upi' => $request->has('jerry_invoice_upi') ? '1' : '0',
            'jerry_product_hide_middle' => $request->has('jerry_product_hide_middle') ? '1' : '0',
            'jerry_product_tax_inclusive' => $request->has('jerry_product_tax_inclusive') ? '1' : '0',
            'jerry_product_purchase_zero' => $request->has('jerry_product_purchase_zero') ? '1' : '0',
            'jerry_pos_cache' => $request->has('jerry_pos_cache') ? '1' : '0',
            'jerry_pos_cart_show_pp' => $request->has('jerry_pos_cart_show_pp') ? '1' : '0',
            'jerry_pos_cart_hide_images' => $request->has('jerry_pos_cart_hide_images') ? '1' : '0',
            'jerry_pos_list_show_pp' => $request->has('jerry_pos_list_show_pp') ? '1' : '0',
            'jerry_pos_list_hide_images' => $request->has('jerry_pos_list_hide_images') ? '1' : '0',
            'jerry_pos_hide_qty_buttons' => $request->has('jerry_pos_hide_qty_buttons') ? '1' : '0',
            'jerry_pos_hide_unit_dropdown' => $request->has('jerry_pos_hide_unit_dropdown') ? '1' : '0',
            'jerry_pos_list_show_sku' => $request->has('jerry_pos_list_show_sku') ? '1' : '0',
            'jerry_pos_list_show_stock' => $request->has('jerry_pos_list_show_stock') ? '1' : '0',
            'jerry_pos_auto_add' => $request->has('jerry_pos_auto_add') ? '1' : '0',
            'jerry_pos_auto_select_patch' => $request->has('jerry_pos_auto_select_patch') ? '1' : '0',
            'jerry_pos_no_match_toast' => $request->has('jerry_pos_no_match_toast') ? '1' : '0',
            'jerry_offline_mode' => $request->has('jerry_offline_mode') ? '1' : '0',
            'jerry_offline_heartbeat' => $request->has('jerry_offline_heartbeat') ? '1' : '0',
            'jerry_offline_big_shop' => $request->has('jerry_offline_big_shop') ? '1' : '0',
            'jerry_offline_row_cache_max' => (string) max(100, min(100000, (int) $request->input('jerry_offline_row_cache_max', '5000'))),
            'jerry_speed_cache_max' => (string) max(1000, min(50000, (int) $request->input('jerry_speed_cache_max', '20000'))),
            'jerry_disable_tour' => $request->has('jerry_disable_tour') ? '1' : '0',
            'jerry_low_end_pc' => $request->has('jerry_low_end_pc') ? '1' : '0',
            'jerry_account_hardening' => $request->has('jerry_account_hardening') ? '1' : '0',
            'jerry_product_bulk_edit' => $request->has('jerry_product_bulk_edit') ? '1' : '0',
            'jerry_vercel_api' => $request->has('jerry_vercel_api') ? '1' : '0',
            'jerry_traditional_mode' => $request->has('jerry_traditional_mode') ? '1' : '0',
            'jerry_custom_translations' => $request->input('jerry_custom_translations', ''),
        ];

        // One-click enable all tweak toggles.
        if ($request->has('jerry_apply_all_tweaks')) {
            foreach ($toggles as $key => $value) {
                $toggles[$key] = '1';
            }
            // Keep dark mode deterministic when applying all.
            $toggles['jerry_dark_mode'] = $request->input('jerry_dark_mode', 'normal') ?: 'normal';
        }

        // Offline Mode requires Speed Cache — auto-enable it
        if ($toggles['jerry_offline_mode'] === '1') {
            $toggles['jerry_pos_cache'] = '1';
        }

        \Modules\JerryUpdates\Utils\JerrySettings::setMany($toggles);

        return redirect()->back()->with('status', [
            'success' => 1,
            'msg' => 'Settings saved successfully'
        ]);
    }

    /**
     * Clear specific Laravel caches.
     */
    public function clearCache(Request $request)
    {
        $this->ensureAdminUser();

        $type = $request->input('type');
        $msg = '';

        try {
            switch ($type) {
                case 'config':
                    \Illuminate\Support\Facades\Artisan::call('config:clear');
                    $msg = 'Configuration cache cleared successfully.';
                    break;
                case 'route':
                    \Illuminate\Support\Facades\Artisan::call('route:clear');
                    $msg = 'Routes cache cleared successfully.';
                    break;
                case 'view':
                    \Illuminate\Support\Facades\Artisan::call('view:clear');
                    $msg = 'Compiled views cleared successfully.';
                    break;
                case 'cache':
                    \Illuminate\Support\Facades\Artisan::call('cache:clear');
                    $msg = 'Application cache cleared successfully.';
                    break;
                case 'optimize':
                    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                    $msg = 'All caches (optimize:clear) cleared successfully.';
                    break;
                default:
                    throw new \Exception('Invalid cache type selected.');
            }

            return redirect()->back()->with('status', [
                'success' => 1,
                'msg' => $msg
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());
            
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => 'Failed to clear cache: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get current module status information.
     */
    private function getModuleStatus(): array
    {
        $modulePath = base_path('Modules/JerryUpdates');
        $moduleJson = json_decode(file_get_contents($modulePath . '/module.json'), true);
        $statusFile = base_path('modules_statuses.json');
        $statuses = file_exists($statusFile) ? json_decode(file_get_contents($statusFile), true) : [];

        return [
            'name' => 'Just Tweaks',
            'alias' => $moduleJson['alias'] ?? 'jerryupdates',
            'enabled' => $statuses['JerryUpdates'] ?? false,
            'priority' => $moduleJson['priority'] ?? 0,
            'base_version' => 'v12 (fwcv3)',
            'last_synced' => '2026-03-27',
        ];
    }

    public function runAccountingMigration()
    {
        $this->ensureAdminUser();

        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', [
                '--path' => 'database/migrations/2026_03_26_190000_harden_account_transactions_integrity.php',
                '--force' => true,
            ]);

            return redirect()->back()->with('status', [
                'success' => 1,
                'msg' => 'Accounting hardening migration executed successfully.',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Just Tweaks accounting migration failed: '.$e->getMessage());

            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => 'Failed to run accounting migration: '.$e->getMessage(),
            ]);
        }
    }

    private function getTechnicalChecks(): array
    {
        $checks = [
            [
                'label' => 'accounts.account_type_id',
                'required' => true,
                'status' => Schema::hasColumn('accounts', 'account_type_id') ? 'ok' : 'missing',
                'hint' => 'Used for account type joins in account listing.',
            ],
            [
                'label' => 'accounts.account_details',
                'required' => false,
                'status' => Schema::hasColumn('accounts', 'account_details') ? 'ok' : 'missing_optional',
                'hint' => 'Optional metadata column. Jerry fallback handles missing column.',
            ],
            [
                'label' => 'account_types table',
                'required' => true,
                'status' => Schema::hasTable('account_types') ? 'ok' : 'missing',
                'hint' => 'Required for account type hierarchy joins.',
            ],
            [
                'label' => 'account_transactions.transaction_payment_id unique',
                'required' => false,
                'status' => Schema::hasColumn('account_transactions', 'transaction_payment_id') ? 'ok' : 'missing_optional',
                'hint' => 'Hardening migration expects this linkage for deterministic sync.',
            ],
        ];

        return $checks;
    }

    /**
     * Get list of active overrides with descriptions.
     */
    private function getOverridesList(): array
    {
        $ledger_fix_enabled = \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_ledger_fix') == '1';
        $running_balance_enabled = \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_running_balance') == '1';
        $dark_mode_enabled = \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_dark_mode') == '1';
        $system_black_theme_enabled = \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_system_black_theme') == '1';
        $account_hardening_enabled = \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_account_hardening', '0') == '1';
        $traditional_mode_enabled = \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_traditional_mode', '1') == '1';

        return [
            [
                'class' => 'JerryTransactionUtil',
                'extends' => 'App\\Utils\\TransactionUtil',
                'method' => 'getLedgerDetails()',
                'description' => 'Overrides the ledger calculation to fix advance payment balance anomaly. Includes v12 hms_booking & gym_subscription support.',
                'status' => $ledger_fix_enabled ? 'active' : 'disabled',
                'type' => 'Service Binding',
                'binding' => 'TransactionUtil → JerryTransactionUtil',
            ],
            [
                'class' => 'JerryTransactionUtil',
                'extends' => 'App\\Utils\\TransactionUtil',
                'method' => '__transactionQuery()',
                'description' => 'Private helper for querying transactions by contact, date range and location. Identical to base — maintained for self-containment.',
                'status' => $ledger_fix_enabled ? 'synced' : 'disabled',
                'type' => 'Private Override',
                'binding' => null,
            ],
            [
                'class' => 'JerryTransactionUtil',
                'extends' => 'App\\Utils\\TransactionUtil',
                'method' => '__paymentQuery()',
                'description' => 'Private helper for querying payments by contact, date range and location. Identical to base — maintained for self-containment.',
                'status' => $ledger_fix_enabled ? 'synced' : 'disabled',
                'type' => 'Private Override',
                'binding' => null,
            ],
            [
                'class' => 'JerryUpdatesServiceProvider',
                'extends' => 'ServiceProvider',
                'method' => 'boot() → View::composer',
                'description' => 'Forces the user session theme_color to "black" dynamically across all layouts.app rendered pages.',
                'status' => $system_black_theme_enabled ? 'active' : 'disabled',
                'type' => 'View Injection',
                'binding' => null,
            ],
            [
                'class' => 'JerryAccountHardeningListener',
                'extends' => 'App Payment Events',
                'method' => 'onPaymentAdded/Updated/Deleted()',
                'description' => 'Enforces deterministic account-ledger sync, prevents orphan payment links, and enables SaaS-safe per-business hardening toggle.',
                'status' => $account_hardening_enabled ? 'active' : 'disabled',
                'type' => 'Event Listener Override',
                'binding' => 'TransactionPayment* Events',
            ],
            [
                'class' => 'jerry_pos_tweaks',
                'extends' => 'public/js/pos.js',
                'method' => '$.autocomplete()',
                'description' => 'Hijacks the POS Product Search DOM node and replaces it with Jerrys LocalStorage caching engine to eliminate AJAX lag. Originally from pos(3).js backups.',
                'status' => (\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_cache') == '1') ? 'active' : 'disabled',
                'type' => 'Javascript Override',
                'binding' => 'DOM Event Injection',
            ],
            [
                'class' => 'jerry_offline_mode',
                'extends' => 'public/js/pos.js',
                'method' => '$.ajaxPrefilter()',
                'description' => 'Online-first hybrid POS. Intercepts AJAX save failures and queues sales in IndexedDB. Auto-syncs when connectivity returns.',
                'status' => (\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_offline_mode') == '1') ? 'active' : 'disabled',
                'type' => 'Javascript Override',
                'binding' => 'AJAX Prefilter + IndexedDB',
            ],
            [
                'class' => 'jerry_pos_cart_images',
                'extends' => 'sale_pos/product_row',
                'method' => 'CSS Injection',
                'description' => 'Hides all product images from the POS cart rows for a denser layout.',
                'status' => (\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_cart_hide_images') == '1') ? 'active' : 'disabled',
                'type' => 'Javascript Override',
                'binding' => 'Dynamic DOM Style Append',
            ],
            [
                'class' => 'jerry_pos_list_images',
                'extends' => 'sale_pos/partials/product_list',
                'method' => 'CSS Injection',
                'description' => 'Hides all product images from the POS suggestion grid for a text-only layout.',
                'status' => (\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_list_hide_images') == '1') ? 'active' : 'disabled',
                'type' => 'Javascript Override',
                'binding' => 'Dynamic DOM Style Append',
            ],
            [
                'class' => 'sale_pos/product_row (Custom View)',
                'extends' => 'resources/views/sale_pos/product_row.blade.php',
                'method' => 'View Override',
                'description' => 'Handles PP injection, compact mode Qty button hiding, and Unit Dropdown minimizing from the POS cart row.',
                'status' => (\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_cart_show_pp') == '1' || \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_hide_qty_buttons') == '1' || \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_hide_unit_dropdown') == '1') ? 'active' : 'disabled',
                'type' => 'Blade Override',
                'binding' => 'custom_views/ Directory',
            ],
            [
                'class' => 'sale_pos/partials/product_list (Custom View)',
                'extends' => 'resources/views/sale_pos/partials/product_list.blade.php',
                'method' => 'View Override',
                'description' => 'Handles PP injection and dynamic SKU/Stock hiding in the right-side POS suggestion grid.',
                'status' => 'active',
                'type' => 'Blade Override',
                'binding' => 'custom_views/ Directory',
            ],
            [
                'class' => 'Dual-Mode Proxy Engine (Global)',
                'extends' => 'custom_views/* (All Files)',
                'method' => 'Blade Proxy Wrapper',
                'description' => 'Recursively applied to all overrides in custom_views/. Detects traditional_mode toggle to dynamically route between custom Blade and native V12 vendor files.',
                'status' => $traditional_mode_enabled ? 'Traditional active' : 'JS-UpgradeSafe active',
                'type' => 'Architecture Proxy',
                'binding' => 'Blade @if conditional',
            ],
        ];
    }

    /**
     * Get list of Jerry\'s bug fixes with details.
     */
    private function getBugFixesList(): array
    {
        $ledger_status = \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_ledger_fix') == '1' ? 'applied' : 'disabled via settings';
        return [
            [
                'id' => 'JERRY-001',
                'title' => 'Advance Payment in Balance Due',
                'severity' => 'high',
                'description' => 'The base code uses $total_transactions_paid for curr_due which excludes advance payments. Jerry uses $total_paid which correctly includes the advance payment offset.',
                'base_code' => '$curr_due = ... - $total_transactions_paid + ...',
                'jerry_code' => '$curr_due = ... - $total_paid + ...',
                'affected_line' => 'getLedgerDetails() → curr_due calculation',
                'status' => $ledger_status,
            ],
            [
                'id' => 'JERRY-002',
                'title' => 'Advance Payment in Overall Summary',
                'severity' => 'high',
                'description' => 'The base omits $overall_total_advance_payment from the overall customer paid total (has a comment suggesting it but doesn\'t implement it). Jerry adds it.',
                'base_code' => '$total_overall_paid_customer = ... + $overall_total_ob_paid;',
                'jerry_code' => '$total_overall_paid_customer = ... + $overall_total_ob_paid + $overall_total_advance_payment;',
                'affected_line' => 'getLedgerDetails() → overall paid calculation',
                'status' => $ledger_status,
            ],
            [
                'id' => 'JERRY-003',
                'title' => 'PHP 8 Signature Deprecation in getGrossProfit()',
                'severity' => 'medium',
                'description' => 'Fixed optional parameters order by making $permitted_locations optional in TransactionUtil::getGrossProfit().',
                'base_code' => '... $user_id = null, $permitted_locations)',
                'jerry_code' => '... $user_id = null, $permitted_locations = null)',
                'affected_line' => 'TransactionUtil::getGrossProfit()',
                'status' => 'applied',
            ],
            [
                'id' => 'JERRY-004',
                'title' => 'Legacy Schema Compatibility for Accounts Table',
                'severity' => 'high',
                'description' => 'Account listing query now conditionally joins/selects account_type fields only when columns/tables exist.',
                'base_code' => 'select accounts.account_type_id + join account_types unconditionally',
                'jerry_code' => 'Schema-aware select/join fallback with NULL aliases',
                'affected_line' => 'AccountController::index()',
                'status' => 'applied',
            ],
        ];
    }

    private function ensureAdminUser(): void
    {
        $business_id = session()->get('business.id') ?? session()->get('user.business_id');
        $is_admin = ! empty($business_id) && auth()->check() && auth()->user()->hasRole('Admin#'.$business_id);
        $is_owner = auth()->check() && auth()->id() == 1;

        if (! $is_admin && ! $is_owner) {
            abort(403, 'Admin access only.');
        }
    }

    /**
     * Return total product count for chunked prewarm.
     */
    public function getProductCount(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id', null);

        $query = \App\Product::join('variations', 'products.id', '=', 'variations.product_id')
            ->where('products.business_id', $business_id)
            ->where('products.not_for_selling', 0)
            ->where('products.type', '!=', 'modifier');

        if ($location_id) {
            $query->join('product_locations', function ($join) use ($location_id) {
                $join->on('products.id', '=', 'product_locations.product_id')
                     ->where('product_locations.location_id', $location_id);
            });
        }

        return response()->json(['total' => $query->count()]);
    }

    /**
     * Return a paginated chunk of products for the prewarm cache.
     */
    public function getProductChunk(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id', null);
        $offset       = (int) $request->get('offset', 0);
        $limit        = (int) $request->get('limit', 500);

        $productUtil = app(\App\Utils\ProductUtil::class);
        // Re-use the existing filterProduct with empty term to get all
        $result = $productUtil->filterProduct($business_id, '', $location_id, 0, null, [], ['name', 'sku', 'sub_sku']);

        // Slice for pagination
        $chunk = array_slice($result->toArray(), $offset, $limit);

        return response()->json($chunk);
    }
}
