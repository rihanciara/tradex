---
title: V12 Upgrade-Safe Frontend Tweaks Architecture
description: Comprehensive documentation on how to inject custom Javascript, CSS, and UI modifications into Ultimate POS V12 frontend views (POS, Dashboard, Contact forms) without editing core blade or JS files.
---

# V12 Upgrade-Safe Frontend Tweaks Architecture

This document outlines the architecture used in the `JerryUpdates` module to implement dozens of frontend UI changes, new buttons, hidden fields, and API hooks purely through injection. This ensures 100% upgrade safety against future Ultimate POS core updates.

## Core Philosophy: The View Hook Pattern
Ultimate POS V12 provides "hooks" via the `moduleUtil`. Rather than modifying `resources/views/...`, we bind our custom module's Blade files to these hooks globally.

### 1. Registering the Hook (Backend)
In your module's controller/service provider (or a specific hook listener like `DataController`), return a generic "tweaks" Blade view whenever POS loads.

```php
// JerryUpdates/Http/Controllers/DataController.php
public function modify_pos_tool_box() {
    return view('jerryupdates::javascript_tweaks');
}

public function get_additional_script() {
    return [
       'additional_html' => view('jerryupdates::global_tweaks')->render(),
       'additional_css' => '<style>/* Custom unified CSS here */</style>'
    ];
}
```

### 2. The Universal Injection File (`javascript_tweaks.blade.php`)
This file acts as the "Brain" of the frontend. It runs on the client side, detects which page the user is currently on, checks if specific feature flags are enabled (via injected PHP variables), and applies jQuery DOM manipulations.

```html
<script type="text/javascript">
$(document).ready(function() {
    // Inject Configuration flags from backend
    var jerryConfig = {
        speedCache: {{ \App\System::getProperty('jerry_speed_cache') == '1' ? 'true' : 'false' }},
        offlineMode: {{ \App\System::getProperty('jerry_offline_mode') == '1' ? 'true' : 'false' }},
        posLabels: {{ \App\System::getProperty('jerry_pos_labels') == '1' ? 'true' : 'false' }}
    };

    // ----------------------------------------------------
    // Context Detectors
    // ----------------------------------------------------
    var is_pos_page = ($('#pos_table').length > 0);
    var is_contact_page = (window.location.href.indexOf('/contacts') !== -1);
    
    // ----------------------------------------------------
    // 1. POS Specific Injection (Delayed for Vue/pos.js)
    // ----------------------------------------------------
    if (is_pos_page) {
        setTimeout(function() {
            // A. Inject Custom Buttons into Action Bar
            if ($('#jerry-all-sales-btn').length === 0) {
                $('.pos-form-actions .tw-overflow-x-auto').prepend(
                    '<a href="/sells" id="jerry-all-sales-btn" class="tw-font-bold ...">All Sales</a>'
                );
            }

            // B. Hijack Core Plugins (e.g., Select2/Autocomplete styling)
            if (jerryConfig.speedCache) {
                // Initialize custom speed caching engine completely bypassing V12 AJAX lookups
                initSpeedCacheAutocomplete(); 
            }
            
            // C. Override Field Behaviors (e.g. converting Select to Hidden input)
            $('#discount_type, #rp_redeemed_modal').closest('div').hide();
        }, 1000); // 1-second delay ensures V12 finishes its initial DOM rendering
    }

    // ----------------------------------------------------
    // 2. Global Modal Interception (Contacts, Expenses)
    // ----------------------------------------------------
    // V12 largely uses AJAX modals. We listen for the 'shown.bs.modal' event.
    $(document).on('shown.bs.modal', '.contact_modal', function (e) {
        // e.g., Pre-fill specific custom fields or hide irrelevant ones
        $(this).find('input#custom_field1').val('Default Value');
        $(this).find('.shipping_custom_field').closest('.col-md-3').hide();
    });

    // ----------------------------------------------------
    // 3. Network Interception for Offline Mode ($.ajaxPrefilter)
    // ----------------------------------------------------
    if (jerryConfig.offlineMode) {
        $.ajaxPrefilter(function(options, originalOptions, jqXHR) {
            if (options.url.indexOf('/pos') !== -1 && options.type.toUpperCase() === 'POST') {
                options.error = function(xhr, status, error) {
                    if (status === 'timeout' || !navigator.onLine) {
                        queueSaleOffline(options.data);
                        enable_pos_form_actions(); // V12 core reset function
                        return false; // Suppress V12 error popups
                    }
                };
            }
        });
    }
});
</script>
```

## Key Principles of Frontend Injection

1.  **Never Edit Core Files:** If you want to change the color of the Cash button, write a CSS rule in `JerryUpdates::head_css` to override it. Do not edit `resources/views/sale_pos/...`.
2.  **Delayed Injection:** For dynamic screens like POS (which heavily use jQuery and Vue.js on page load), inject your tweaks inside a `setTimeout(..., 1000)`. This allows V12 to render completely before you manipulate the DOM.
3.  **Detect Context Safely:** Check for specific DOM elements (`$('#pos_table').length`) or URL paths (`window.location.href`) before running heavy logic to avoid polluting the global namespace on irrelevant pages.
4.  **Listen to Modal Events:** Since V12 relies heavily on Bootstrap modals loaded via AJAX, bind your tweaks to `$(document).on('shown.bs.modal', ...)` rather than executing them on `$(document).ready()`.
5.  **Graceful Failures:** Enclose complex DOM manipulations in `try/catch` or check if elements exist (`if ($('#el').length)`) so that if a V12 update renames an ID, your module simply fails silently instead of breaking the entire POS script execution.

By using this pattern, you can radically transform the POS UI, intercept network requests, and manage complex caching systems while keeping your module entirely decoupled from the core framework.

---

## CRITICAL: Blade `@` Symbol Escaping in JavaScript Strings

**This is the #1 most dangerous gotcha when writing CSS inside JavaScript strings in `.blade.php` files.**

Laravel's Blade engine parses ALL `@` symbols as potential directives. If you write `@media` or `@keyframes` inside a JavaScript string concatenation, Blade will attempt to parse it as a directive and **silently break the entire `<script>` block** — no error in the browser console, no error in Laravel logs. The JS simply never renders.

### Solutions:
```javascript
// ❌ BROKEN — Blade parses @media as a directive
'@media (max-width: 768px) { ... }' +

// ✅ FIX 1: Use @@  (Blade renders @@ as a literal @)
'@@media (max-width: 768px) { ... }' +

// ✅ FIX 2: Use String.fromCharCode(64) in JS
var atMedia = String.fromCharCode(64) + 'media';
atMedia + ' (max-width: 768px) { ... }' +
```

This also applies to `@keyframes`, `@font-face`, `@import`, and any other CSS at-rule.

---

## POS-Specific UI Tweaks (Injected via `javascript_tweaks.blade.php`)

These tweaks fire inside an independent `$(document).ready()` block that detects POS pages via URL path (`/pos/create` or `/sells/create`). They are NOT inside the autocomplete setTimeout block to avoid being blocked by unrelated JS errors.

### 1. Copyright Footer Removal (POS Only)
```javascript
$('<style id="jerry-hide-footer">.main-footer { display: none !important; }</style>').appendTo('head');
```
Hides the "TradeX V6.12 Copyright" footer exclusively on the POS screen. The footer remains visible on all other dashboard pages.

### 2. Full-Height Product Table (Viewport Stretch)
```javascript
$('<style id="jerry-pos-layout-stretch">' +
    '.pos_product_div { min-height: calc(100vh - 370px) !important; max-height: calc(100vh - 370px) !important; overflow-y: auto !important; }' +
'</style>').appendTo('head');
```
Forces the product row table to stretch vertically, filling all available screen space and pushing the Totals/Discount/Tax row firmly to the bottom above the action bar.

### 3. Discount Text Click-to-Edit
```javascript
$('.pos_form_totals b:contains("Discount")').css('cursor', 'pointer').on('click', function() {
    $('#posEditDiscountModal').modal('show');
});
```
Makes the "Discount" text label in the POS totals section clickable, opening the native V12 discount editing modal (`#posEditDiscountModal`).

### 4. Apple Dark Mode High-Contrast Borders
Injected via `offline_mode.blade.php` CSS block using Apple iOS dark mode colors:
- **Backgrounds:** `#1c1c1e` (elevated surface), `#000000` (base)
- **Borders:** `#38383a` (separator color)
- **Targets:** `.form-control`, `.select2`, `.ui-autocomplete`, `.pos_product_div`, `#pos-table-tbody tr`, `hr`, `.table-bordered`

This ensures nothing becomes invisible when users toggle the V12 dark theme.

---

## Manageable Core Overrides (Exceptions to Upgrade Safety)

While 95% of JerryUpdates tweaks are upgrade-safe, some highly specific features requested by the user **require editing core V12 files**. To mitigate the risk of these edits breaking during an upgrade, we wrap them in `\App\System::getProperty()` toggles. 

**If Ultimate POS is upgraded to a new version, these specific files will be overwritten and the tweaks will need to be manually re-applied.**

### 1. POS Purchase Price Display
- **Goal:** Display the Purchase Price directly below the Stock Quantity inside the POS cart row AND the POS Product Suggestion grid.
- **Core Files (Cart Row):** `app/Utils/ProductUtil.php` (Added `variations.dpp_inc_tax` to the `getDetailsFromVariation` query).
- **Core Files (Suggestion Grid):** `app/Http/Controllers/SellPosController.php` (Added `variations.dpp_inc_tax` to the `$products->select()` array).
- **Upgrade-Safe View Overrides:** The frontend Blade changes were moved to `custom_views/sale_pos/product_row.blade.php` and `custom_views/sale_pos/partials/product_list.blade.php`. These are 100% upgrade safe.
- **Toggle Feature Flags:** `jerry_pos_cart_show_pp` and `jerry_pos_list_show_pp`.
- **Re-application Guide:** If FWCV3 updates, you must only re-inject the `dpp_inc_tax` into the two Backend PHP queries (`ProductUtil` and `SellPosController`). You do NOT need to touch the Blade views, as `custom_views` are preserved.

### 2. Hiding POS Product Images
- **Goal:** Hide product images completely from both the product suggestion grid and the POS cart.
- **Implementation:** Completely Upgrade-Safe! This is injected natively via `javascript_tweaks.blade.php` applying `.display-none` to `.image-container` and `img`.
- **Toggle Feature Flag:** `jerry_pos_hide_images`
