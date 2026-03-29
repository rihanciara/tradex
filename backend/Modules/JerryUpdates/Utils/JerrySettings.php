<?php

namespace Modules\JerryUpdates\Utils;

use App\Business;
use App\System;

class JerrySettings
{
    private const TENANT_DEFAULTS = [
        'jerry_dark_mode' => '0',
        'jerry_ledger_fix' => '0',
        'jerry_running_balance' => '0',
        'jerry_system_black_theme' => '0',
        'jerry_sell_tweaks' => '0',
        'jerry_expense_tweaks' => '0',
        'jerry_product_tweaks' => '0',
        'jerry_contact_tweaks' => '0',
        'jerry_label_tweaks' => '0',
        'jerry_invoice_upi' => '0',
        'jerry_product_hide_middle' => '0',
        'jerry_product_tax_inclusive' => '0',
        'jerry_product_purchase_zero' => '0',
        'jerry_pos_cache' => '0',
        'jerry_pos_cart_show_pp' => '0',
        'jerry_pos_cart_hide_images' => '0',
        'jerry_pos_list_show_pp' => '0',
        'jerry_pos_list_hide_images' => '0',
        'jerry_pos_hide_qty_buttons' => '0',
        'jerry_pos_hide_unit_dropdown' => '0',
        'jerry_pos_list_show_sku' => '1',
        'jerry_pos_list_show_stock' => '1',
        'jerry_offline_mode' => '0',
        'jerry_disable_tour' => '0',
        'jerry_account_hardening' => '0',
        'jerry_product_bulk_edit' => '0',
        'jerry_vercel_api' => '0',
    ];

    /**
     * Get a Jerry setting for the current business (tenant).
     * jerry_* keys are always tenant scoped and never resolved from global System properties.
     */
    public static function get(string $key, $default = null)
    {
        $common_settings = self::getSessionCommonSettings();
        if (is_array($common_settings) && array_key_exists($key, $common_settings)) {
            return $common_settings[$key];
        }

        // jerry_* toggles are strictly business-scoped in SaaS mode.
        if (str_starts_with($key, 'jerry_')) {
            if ($default !== null) {
                return $default;
            }

            return self::TENANT_DEFAULTS[$key] ?? null;
        }

        $system_value = System::getProperty($key);
        return $system_value !== null ? $system_value : $default;
    }

    /**
     * Update multiple Jerry settings for the current business (tenant).
     * Keeps session "business" in sync so changes apply immediately.
     */
    public static function setMany(array $settings): void
    {
        $business_id = session()->get('user.business_id') ?? session()->get('business.id');
        if (empty($business_id)) {
            // Strict SaaS: do not persist jerry_* to global System scope.
            // Non-jerry keys (if any) still use legacy fallback behavior.
            foreach ($settings as $key => $value) {
                if (str_starts_with($key, 'jerry_')) {
                    continue;
                }

                if (System::getProperty($key) !== null) {
                    System::setProperty($key, $value);
                } else {
                    System::addProperty($key, $value);
                }
            }
            return;
        }

        $business = Business::find($business_id);
        if (empty($business)) {
            return;
        }

        $common_settings = is_array($business->common_settings) ? $business->common_settings : [];
        foreach ($settings as $key => $value) {
            $common_settings[$key] = $value;
        }

        $business->common_settings = $common_settings;
        $business->save();

        self::syncSessionBusiness($common_settings);
    }

    private static function getSessionCommonSettings(): ?array
    {
        $business = session('business');
        if (empty($business)) {
            return null;
        }

        if (is_object($business)) {
            return is_array($business->common_settings) ? $business->common_settings : [];
        }

        if (is_array($business)) {
            return isset($business['common_settings']) && is_array($business['common_settings'])
                ? $business['common_settings']
                : [];
        }

        return null;
    }

    private static function syncSessionBusiness(array $common_settings): void
    {
        $business = session('business');
        if (empty($business)) {
            return;
        }

        if (is_object($business)) {
            $business->common_settings = $common_settings;
        } elseif (is_array($business)) {
            $business['common_settings'] = $common_settings;
        }

        session()->put('business', $business);
    }
}
