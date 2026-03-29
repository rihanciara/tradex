---
title: V12 Offline POS Engine Architecture
description: Comprehensive documentation on implementing an upgrade-safe, offline-first hybrid POS fallback for Ultimate POS V12 using IndexedDB, AJAX interceptors, and HTML caching.
---

# V12 Offline POS Engine Architecture

This document details the advanced architecture used to create a 100% upgrade-safe, offline-capable POS system for Ultimate POS V12. This engine allows users to continue ringing up sales when the internet drops, queuing them locally, and automatically syncing them to the server when connection is restored.

## Core Design Philosophy: Upgrade Safety
Never modify core V12 JavaScript files like `public/js/pos.js`. The entire offline engine must be injected via a custom module (e.g., `JerryUpdates`) using view hooks (e.g., `pos.index.tool_box`).

## 1. The AJAX Interceptor (The Queueing Engine)
Instead of rewriting `$scope.savePOS()` or `$("#pos-save").click()`, we intercept the final jQuery AJAX call.

```javascript
$.ajaxPrefilter(function(options, originalOptions, jqXHR) {
    // Only intercept POST requests to POS/sells save URLs
    if (!options.type || options.type.toUpperCase() !== 'POST') return;
    if (options.url.indexOf('/pos') === -1) return;
    if (options._jerrySync) return; // Ignore our own background syncs

    var originalError = options.error;
    var originalSuccess = options.success;

    // Cache the last successful receipt template when online
    options.success = function(result) {
        if (result && result.receipt && result.receipt.html_content) {
            localStorage.setItem('jerry_last_receipt_template', JSON.stringify(result.receipt));
        }
        if (originalSuccess) originalSuccess.apply(this, arguments);
    };

    options.error = function(xhr, status, error) {
        // network failure, timeout, or manually triggered offline state
        if (status === 'timeout' || xhr.status === 0 || !navigator.onLine) {
            console.log("Network failure detected! Queuing sale offline.");
            
            var receiptData = captureOfflineReceipt(); // Custom function to extract DOM state
            
            // 🚨 CRITICAL: Force enable the POS form instantly so user isn't stuck waiting
            if (typeof enable_pos_form_actions === 'function') {
                enable_pos_form_actions();
                $('div.pos-processing').hide();
            }

            // Save payload to IndexedDB
            addToQueue({
                url: options.url,
                method: options.type,
                data: options.data,
                receiptData: receiptData,
                status: 'pending',
                timestamp: Date.now()
            });

            // Print the offline watermarked receipt
            printOfflineReceipt(receiptData);
            
            // Suppress the default V12 error toast
            return false;
        }
        // Normal server error (e.g. 500/400 validation error)
        if (originalError) originalError.apply(this, arguments);
    };
});
```

## 2. Server-Side HTML Row Caching (Visual Fidelity)
Ultimate POS renders complex product rows via Blade templates on the server. Trying to reconstruct this in JavaScript offline is extremely brittle.
**Solution:** Cache the raw HTML strings returned by the server when online, and replay them when offline.

```javascript
// Pre-warm the cache periodically when online
$.getJSON('/products/list', { not_for_selling: 0 }, function(data) {
    // Background query /pos/get_product_row for variations...
    // Store in localStorage: { variation_id_123: "<tr>...</tr>" }
});

// Override V12's native pos_product_row global renderer
var original_pos_product_row = window.pos_product_row;
window.pos_product_row = function(variation_id, purchase_line_id, barcode, qty) {
    if (!navigator.onLine) {
        var cachedHtml = getCachedRow(variation_id);
        if (cachedHtml) {
            var newIndex = getNewRowIndex();
            // Regex replace the data-row_index attributes in the cached HTML
            cachedHtml = cachedHtml.replace(/data-row_index="[^"]*"/g, 'data-row_index="' + newIndex + '"');
            $('#pos_table tbody').append(cachedHtml);
            calculate_total(); // Re-trigger V12 calculations
            return;
        }
    }
    // Online fallback
    return original_pos_product_row.apply(this, arguments);
};
```

## 3. The Offline Receipt Engine
When offline, the server cannot generate the receipt. We use the cached JSON template from the *last successful online sale*.

```javascript
function printOfflineReceipt(data) {
    var cached = localStorage.getItem('jerry_last_receipt_template');
    if (cached && typeof pos_print === 'function') {
        var receipt = JSON.parse(cached);
        
        // Regex inject the current sale's items, totals, and "⚠ PENDING SYNC" watermark into the HTML
        var newHtml = injectOfflineDataIntoTemplate(receipt.html_content, data);
        receipt.html_content = newHtml;
        
        // Use V12's native browser print logic
        pos_print(receipt);
    } else {
        // Ultimate fallback: Simple <table> layout
    }
}
```

## 4. Background Synchronization
When the internet is restored, an event listener triggers the replay engine.

```javascript
window.addEventListener('online', function() {
    console.log("Internet restored. Auto-syncing pending offline sales.");
    syncPendingSales();
});

function syncPendingSales() {
    getAllPending(function(sales) {
        syncNext(sales, 0);
    });
}

function syncNext(sales, index) {
    if (index >= sales.length) return; // Done
    var sale = sales[index];
    
    $.ajax({
        url: sale.url,
        method: sale.method,
        data: sale.data,
        _jerrySync: true, // Bypass interceptor
        success: function(resp) {
            markSynced(sale.id);
            // DO NOT call pos_print() here! The receipt was already printed offline.
            setTimeout(function() { syncNext(sales, index + 1); }, 1000); // 1s delay
        }
    });
}
```

By using `$.ajaxPrefilter`, `localStorage` HTML caching, and `IndexedDB` queuing, we turn a rigid server-side rendered application into a resilient Offline-First Progressive Web App without touching a single core V12 file.

---

## 5. Active Server Heartbeat (Reliable Connectivity Detection)

**CRITICAL LESSON:** Browser `online`/`offline` events (`window.addEventListener('online')`) are **extremely unreliable** — they only detect network adapter state, NOT actual server reachability. A user can be "online" per the browser but unable to reach the server.

**Solution:** Active heartbeat that pings the server every 15 seconds:

```javascript
function checkServerReachability() {
    $.ajax({
        url: HEARTBEAT_URL, // e.g., /login (lightweight page)
        method: 'HEAD',
        timeout: 5000,
        cache: false,
        success: function() {
            isOnline = true;
            updateStatusUI();
            // Auto-sync pending sales when server becomes reachable
            getPendingCount(function(count) {
                if (count > 0) syncPendingSales();
            });
        },
        error: function() {
            isOnline = false;
            updateStatusUI();
        }
    });
}
setInterval(checkServerReachability, 15000);
```

The "Sync Now" button also uses this pattern — it pings the server FIRST before attempting sync, instead of relying on the stale `isOnline` flag.

## 6. POS Footer Bar Integration

The offline status pill and Refresh Products button are injected directly into V12's POS footer action bar (`.pos-form-actions .tw-overflow-x-auto`) using the same Tailwind utility classes as the native icons (All Sales, Cash, Multiple Pay, etc.):

```javascript
// Status pill — matches footer icon layout
'<div id="jerry-offline-pill" class="state-online">' +
    '<i class="fas fa-wifi jerry-pill-icon"></i>' +
    '<span id="jerry-offline-status-text">Online</span>' +
'</div>'

// Refresh Products — same pattern
'<a href="#" id="jerry-refresh-products-btn" class="tw-font-bold tw-text-gray-700 tw-text-xs md:tw-text-sm tw-flex tw-flex-col tw-items-center tw-justify-center tw-gap-1 no-print">' +
    '<i class="fas fa-sync-alt tw-text-[#009EE4] !tw-text-sm"></i>' +
    '<span>Refresh</span>' +
'</a>'
```
