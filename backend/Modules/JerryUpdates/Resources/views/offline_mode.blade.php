{{-- Jerry Offline Mode — Online-First Hybrid POS Backup --}}
@if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_offline_mode') == '1')
<script type="text/javascript">
(function() {
    var path = window.location.pathname;
    // Only activate on POS pages
    if (path.indexOf("/pos/create") === -1 && path.indexOf("/sells/create") === -1 && path.indexOf("/pos/") === -1) {
        return;
    }

    // =============== CONFIG ===============
    var CURRENT_BIZ_ID = '{{ session()->get("user.business_id") ?? session()->get("business.id") ?? "0" }}';
    var DB_NAME = 'JerryOfflineDB_biz_' + CURRENT_BIZ_ID;
    var DB_VERSION = 2;
    var STORE_NAME = 'pending_sales';
    var ROW_CACHE_STORE = 'product_row_cache';
    var SYNC_RETRY_MS = 30000; // 30 seconds
    var AJAX_TIMEOUT = 10000;  // 10 second timeout
    var HEARTBEAT_URL = window.location.origin + '/login'; // lightweight endpoint check

    // =============== STATE ===============
    var db = null;
    var isOnline = navigator.onLine;
    var isSyncing = false;

    // =============== INDEXEDDB SETUP ===============
    function openDB(callback) {
        if (db) { if (callback) callback(db); return; }
        var request = indexedDB.open(DB_NAME, DB_VERSION);
        request.onupgradeneeded = function(e) {
            var idb = e.target.result;
            var oldVersion = e.oldVersion || 0;
            if (oldVersion < 1) {
                var store = idb.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
                store.createIndex('status', 'status', { unique: false });
                store.createIndex('timestamp', 'timestamp', { unique: false });
            }
            if (oldVersion < 2) {
                // Product row cache: keyed by a composite string "bizId_locId_variationId"
                var rowStore = idb.createObjectStore(ROW_CACHE_STORE, { keyPath: 'cache_key' });
                rowStore.createIndex('timestamp', 'timestamp', { unique: false });
            }
        };
        request.onsuccess = function(e) {
            db = e.target.result;
            if (callback) callback(db);
            updateStatusUI();
        };
        request.onerror = function(e) {
            console.error("JerryOffline: IndexedDB open failed", e);
        };
    }

    function addToQueue(saleData, callback) {
        openDB(function(db) {
            var tx = db.transaction(STORE_NAME, 'readwrite');
            var store = tx.objectStore(STORE_NAME);
            var record = {
                url: saleData.url,
                method: saleData.method || 'POST',
                data: saleData.data,
                business_id: CURRENT_BIZ_ID,
                timestamp: new Date().toISOString(),
                status: 'pending',
                retryCount: 0,
                customerName: extractCustomerName(),
                totalAmount: extractTotalAmount()
            };
            var req = store.add(record);
            req.onsuccess = function() {
                updateStatusUI();
                if (callback) callback(true);
            };
            req.onerror = function() {
                console.error("JerryOffline: Failed to queue sale");
                if (callback) callback(false);
            };
        });
    }

    function getPendingCount(callback) {
        openDB(function(db) {
            var tx = db.transaction(STORE_NAME, 'readonly');
            var store = tx.objectStore(STORE_NAME);
            var index = store.index('status');
            var req = index.count(IDBKeyRange.only('pending'));
            req.onsuccess = function() { callback(req.result); };
            req.onerror = function() { callback(0); };
        });
    }

    function getAllPending(callback) {
        openDB(function(db) {
            var tx = db.transaction(STORE_NAME, 'readonly');
            var store = tx.objectStore(STORE_NAME);
            var results = [];
            store.openCursor().onsuccess = function(e) {
                var cursor = e.target.result;
                if (cursor) {
                    if (cursor.value.status === 'pending') {
                        results.push(cursor.value);
                    }
                    cursor.continue();
                } else {
                    callback(results);
                }
            };
        });
    }

    function markSynced(id) {
        openDB(function(db) {
            var tx = db.transaction(STORE_NAME, 'readwrite');
            var store = tx.objectStore(STORE_NAME);
            var req = store.get(id);
            req.onsuccess = function() {
                var record = req.result;
                if (record) {
                    record.status = 'synced';
                    store.put(record);
                    updateStatusUI();
                }
            };
        });
    }

    function markFailed(id) {
        openDB(function(db) {
            var tx = db.transaction(STORE_NAME, 'readwrite');
            var store = tx.objectStore(STORE_NAME);
            var req = store.get(id);
            req.onsuccess = function() {
                var record = req.result;
                if (record) {
                    record.retryCount = (record.retryCount || 0) + 1;
                    record.status = 'pending'; // Keep as pending for retry
                    store.put(record);
                }
            };
        });
    }

    function deleteFromQueue(id, callback) {
        openDB(function(db) {
            var tx = db.transaction(STORE_NAME, 'readwrite');
            var store = tx.objectStore(STORE_NAME);
            store.delete(id);
            tx.oncomplete = function() {
                updateStatusUI();
                if (callback) callback();
            };
        });
    }

    function clearAllSynced(callback) {
        openDB(function(db) {
            var tx = db.transaction(STORE_NAME, 'readwrite');
            var store = tx.objectStore(STORE_NAME);
            store.openCursor().onsuccess = function(e) {
                var cursor = e.target.result;
                if (cursor) {
                    if (cursor.value.status === 'synced') {
                        cursor.delete();
                    }
                    cursor.continue();
                } else {
                    if (callback) callback();
                }
            };
        });
    }

    // =============== HELPERS ===============
    function extractCustomerName() {
        var sel = $('select#customer_id');
        if (sel.length && sel.find(':selected').length) {
            return sel.find(':selected').text().trim().substring(0, 40) || 'Walk-In';
        }
        return 'Walk-In';
    }

    function extractTotalAmount() {
        var el = $('input#final_total_input');
        if (el.length) {
            return el.val() || '0';
        }
        return '0';
    }

    function isPOSSaveURL(url) {
        if (!url) return false;
        return url.indexOf('/pos') !== -1 || url.indexOf('/sells') !== -1;
    }

    // =============== CONNECTIVITY (Passive Events) ===============
    window.addEventListener('online', function() {
        if (!isOnline) {
            isOnline = true;
            console.log("JerryOffline: Browser detected ONLINE event");
            updateStatusUI();
            setTimeout(function() { syncPendingSales(); }, 1000);
        }
    });

    window.addEventListener('offline', function() {
        if (isOnline) {
            isOnline = false;
            console.log("JerryOffline: Browser detected OFFLINE event");
            updateStatusUI();
        }
    });
    // =============== AJAX INTERCEPTOR ===============
    // Online-first: Wrap AJAX errors to catch network failures and queue offline
    $.ajaxPrefilter(function(options, originalOptions, jqXHR) {
        // Only intercept POST requests to POS/sells save URLs
        if (!options.type || options.type.toUpperCase() !== 'POST') return;
        if (!isPOSSaveURL(options.url)) return;
        // Skip our own sync requests
        if (options._jerrySync) return;

        var originalError = options.error;
        var originalSuccess = options.success;

        // Add timeout if not set
        if (!options.timeout) {
            options.timeout = AJAX_TIMEOUT;
        }

        // Wrap success to cache last receipt for offline use
        options.success = function(result) {
            if (result && result.receipt && result.receipt.html_content) {
                try {
                    localStorage.setItem('jerry_last_receipt_biz_' + CURRENT_BIZ_ID, JSON.stringify(result.receipt));
                } catch(e) {}
            }
            if (originalSuccess) originalSuccess.apply(this, arguments);
        };

        options.error = function(xhr, status, error) {
            // Network failure or timeout — queue offline
            if (status === 'timeout' || xhr.status === 0 || (status === 'error' && !isOnline)) {
                console.log("JerryOffline: Network failure detected, queuing sale offline...");
                
                // Capture receipt data BEFORE resetting the form
                var receiptData = captureOfflineReceipt();
                
                // IMMEDIATELY re-enable form so user can continue selling
                forceEnablePOS();
                
                // Queue sale in background
                addToQueue({
                    url: options.url,
                    method: options.type,
                    data: originalOptions.data
                }, function(success) {
                    if (success) {
                        toastr.warning(
                            '<i class="fas fa-wifi" style="margin-right:5px"></i> Sale saved offline! Will auto-sync when internet returns.',
                            'Offline Backup',
                            { timeOut: 6000, closeButton: true, progressBar: true }
                        );
                    } else {
                        toastr.error('Failed to save offline! Please try again.');
                    }
                });
                
                // Print offline receipt using cached template
                printOfflineReceipt(receiptData);
                
                // Close modals and reset form
                $('#modal_payment').modal('hide');
                setTimeout(function() {
                    if (typeof reset_pos_form === 'function') {
                        reset_pos_form();
                    }
                    forceEnablePOS();
                }, 300);
                
                isOnline = false;
                updateStatusUI();
                return; // Don't call original error handler
            }

            // Server-side error (not network) — let V12 handle normally
            if (originalError) {
                originalError(xhr, status, error);
            }
        };
    });

    // Force re-enable all POS form buttons and hide processing overlay
    function forceEnablePOS() {
        $('div.pos-processing').hide();
        $('#pos-save').removeAttr('disabled');
        $('div.pos-form-actions').find('button').removeAttr('disabled');
        $('.pos-express-finalize').removeAttr('disabled');
        $('button[type="submit"]').removeAttr('disabled');
    }

    // =============== SUPPRESS NON-CRITICAL AJAX ERRORS WHEN OFFLINE ===============
    // Override get_recent_transactions to not error when offline
    var _original_get_recent = window.get_recent_transactions;
    window.get_recent_transactions = function(status, element_obj) {
        if (!isOnline) {
            // Show offline message instead of making AJAX call
            if (element_obj && element_obj.length) {
                element_obj.html('<div style="text-align:center;padding:20px;color:#999;">' +
                    '<i class="fas fa-wifi-slash" style="font-size:24px;margin-bottom:8px;display:block"></i>' +
                    'Recent transactions unavailable offline' +
                '</div>');
            }
            return;
        }
        if (_original_get_recent) _original_get_recent(status, element_obj);
    };

    // Suppress AJAX errors for non-critical GET requests when offline
    $(document).ajaxError(function(event, xhr, settings) {
        if (!isOnline && settings.type === 'GET' && !settings._jerrySync) {
            // Silently ignore GET errors when offline
            event.stopImmediatePropagation();
            return false;
        }
    });

    // =============== SYNC ENGINE ===============
    function syncPendingSales() {
        if (isSyncing) return;
        if (!isOnline) return;

        getAllPending(function(sales) {
            if (sales.length === 0) return;

            isSyncing = true;
            updateStatusUI();
            syncNext(sales, 0);
        });
    }

    function syncNext(sales, index) {
        if (index >= sales.length) {
            isSyncing = false;
            clearAllSynced();
            updateStatusUI();
            return;
        }

        var sale = sales[index];
        if (sale.business_id && sale.business_id !== CURRENT_BIZ_ID) {
            // Strict isolation: do not sync a sale queued under a different business ID
            setTimeout(function() { syncNext(sales, index + 1); }, 100);
            return;
        }

        $('#jerry-offline-status-text').text('Syncing ' + (index + 1) + '/' + sales.length + '...');

        $.ajax({
            method: sale.method || 'POST',
            url: sale.url,
            data: sale.data,
            dataType: 'json',
            timeout: 15000,
            _jerrySync: true, // Flag to prevent re-interception
            beforeSend: function(xhr) {
                xhr._jerrySync = true;
            },
            success: function(result) {
                if (result.success == 1) {
                    markSynced(sale.id);
                    toastr.success(
                        '<i class="fas fa-cloud-upload-alt" style="margin-right:5px"></i> Offline sale synced! ' + (sale.customerName || ''),
                        'Sync Complete',
                        { timeOut: 4000 }
                    );

                    // User requested NOT to auto-print when syncing offline sales to prevent double-printing.
                    // The offline receipt was already printed during the transaction.
                } else {
                    markFailed(sale.id);
                    toastr.error(
                        'Failed to sync offline sale: ' + (result.msg || 'Unknown error'),
                        'Sync Error'
                    );
                }
                // Continue to next
                setTimeout(function() { syncNext(sales, index + 1); }, 1000);
            },
            error: function(xhr, status) {
                markFailed(sale.id);
                isSyncing = false;
                updateStatusUI();
                // Retry later
                setTimeout(function() { syncPendingSales(); }, SYNC_RETRY_MS);
            }
        });
    }

    // Active server heartbeat — the ONLY reliable way to detect real connectivity
    function checkServerReachability() {
        $.ajax({
            url: HEARTBEAT_URL,
            method: 'HEAD',
            timeout: 5000,
            cache: false,
            success: function() {
                if (!isOnline) {
                    isOnline = true;
                    console.log('JerryOffline: Server reachable — switching to ONLINE');
                    updateStatusUI();
                }
                // Auto-sync pending sales whenever server is reachable
                if (!isSyncing) {
                    getPendingCount(function(count) {
                        if (count > 0) {
                            console.log('JerryOffline: ' + count + ' pending sales found, starting sync...');
                            syncPendingSales();
                        }
                    });
                }
            },
            error: function() {
                if (isOnline) {
                    isOnline = false;
                    console.log('JerryOffline: Server unreachable — switching to OFFLINE');
                    updateStatusUI();
                }
            }
        });
    }

    @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_offline_heartbeat', '1') == '1')
    // Run heartbeat every 15 seconds
    setInterval(checkServerReachability, 15000);
    // Also run once on load after 10 seconds (avoids false-offline blip on page load)
    setTimeout(checkServerReachability, 10000);
    @else
    // Heartbeat disabled: rely solely on browser online/offline events
    window.addEventListener('online', function() { setIsOnline(true); });
    window.addEventListener('offline', function() { setIsOnline(false); });
    @endif

    // =============== STATUS BAR UI ===============
    function injectStatusBar() {
        if ($('#jerry-offline-statusbar').length) return;

        // Add CSS with Dark Mode support
        if ($('#jerry-offline-css').length === 0) {
            $('head').append(
                '<style id="jerry-offline-css">' +
                ':root { ' +
                    '--j-bg: #fff; --j-text: #333; --j-border: #e0e0e0; ' +
                    '--j-pill-on-bg: #e8f5e9; --j-pill-on-text: #2e7d32; --j-pill-on-border: #a5d6a7; ' +
                    '--j-pill-off-bg: #ffebee; --j-pill-off-text: #c62828; --j-pill-off-border: #ef9a9a; ' +
                    '--j-pill-sync-bg: #fff3e0; --j-pill-sync-text: #e65100; --j-pill-sync-border: #ffcc02; ' +
                    '--j-panel-bg: #fff; --j-item-border: #f5f5f5; --j-muted: #999; ' +
                '}' +
                // Dark mode overrides (assuming V12 admin LTE dark mode body class like .dark-mode or similar)
                '.dark-mode, body[data-theme="dark"], .skin-blue-light.dark-mode { ' +
                    '--j-bg: #1c1c1e; --j-text: #f5f5f7; --j-border: #38383a; ' +
                    '--j-pill-on-bg: rgba(48, 209, 88, 0.15); --j-pill-on-text: #30d158; --j-pill-on-border: rgba(48, 209, 88, 0.3); ' +
                    '--j-pill-off-bg: rgba(255, 69, 58, 0.15); --j-pill-off-text: #ff453a; --j-pill-off-border: rgba(255, 69, 58, 0.3); ' +
                    '--j-pill-sync-bg: rgba(255, 159, 10, 0.15); --j-pill-sync-text: #ff9f0a; --j-pill-sync-border: rgba(255, 159, 10, 0.3); ' +
                    '--j-panel-bg: #2c2c2e; --j-item-border: #38383a; --j-muted: #8e8e93; ' +
                '}' +
                // Apple-style Dark Mode Global Border Fixes for POS
                '.dark-mode .box, .dark-mode .box-body, .dark-mode .table-bordered, .dark-mode .table-bordered>tbody>tr>td, .dark-mode .table-bordered>thead>tr>th { border-color: var(--j-border) !important; }' +
                '.dark-mode .form-control, .dark-mode .input-group-addon { background-color: #2c2c2e !important; color: #f5f5f7 !important; border: 1px solid var(--j-border) !important; }' +
                '.dark-mode .select2-container--default .select2-selection--single, .dark-mode .select2-dropdown { background-color: #2c2c2e !important; border: 1px solid var(--j-border) !important; color: #f5f5f7 !important; }' +
                '.dark-mode .select2-container--default .select2-selection--single .select2-selection__rendered { color: #f5f5f7 !important; }' +
                '.dark-mode .products-window, .dark-mode .pos_product_div { border: 1px solid var(--j-border) !important; }' +
                '.dark-mode #pos-table-tbody tr { border-bottom: 1px solid var(--j-border) !important; }' +
                '.dark-mode hr { border-top-color: var(--j-border) !important; }' +
                /* Fix autocomplete dropdown missing borders and background in dark mode */
                '.dark-mode .ui-autocomplete { background-color: #2c2c2e !important; border: 1px solid var(--j-border) !important; color: #f5f5f7 !important; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }' +
                '.dark-mode .ui-menu-item { border-bottom: 1px solid var(--j-border) !important; color: #f5f5f7 !important; }' +
                '.dark-mode .ui-menu-item .ui-menu-item-wrapper.ui-state-active { background-color: rgba(255,255,255,0.1) !important; color: #fff !important; margin: 0 !important; }' +
                /* Stretch product section to utilize empty space and push totals to the absolute bottom */
                '.pos_product_div { height: calc(100vh - 380px) !important; overflow-y: auto; overflow-x: hidden; }' +
                '@@media (max-width: 768px) { .pos_product_div { height: auto !important; min-height: 35vh; } }' +
                '@@media (min-width: 1400px) { .pos_product_div { height: calc(100vh - 420px) !important; } }' +
                '@@keyframes jerryPulse { 0%,100%{opacity:1} 50%{opacity:0.4} } ' +
                '.jerry-pulse { animation: jerryPulse 1.5s ease-in-out infinite; }' +
                '#jerry-offline-statusbar { position:relative; z-index:99999; font-family:sans-serif; display:inline-flex; }' +
                '#jerry-offline-pill { display:flex; align-items:center; justify-content:center; gap:4px; padding:6px 12px; font-size:14px; font-weight:bold; border-radius:6px; cursor:pointer; transition:all 0.3s ease; text-decoration:none; border:2px solid var(--j-border); background:var(--j-bg); margin-bottom: 0; }' +
                '#jerry-offline-pill.state-online { color:#fff; background:#28b77b; border-color:#28b77b; }' +
                '#jerry-offline-pill.state-offline { color:#fff; background:#EF4B53; border-color:#EF4B53; }' +
                '#jerry-offline-pill.state-syncing { color:#fff; background:#ff9800; border-color:#ff9800; }' +
                '#jerry-offline-pill .jerry-pill-icon { font-size:14px; color:inherit !important; }' +
                '#jerry-offline-queue-panel { display:none; position:fixed; bottom:50px; right:12px; width:320px; background:var(--j-panel-bg); color:var(--j-text); border-radius:10px; box-shadow:0 4px 20px rgba(0,0,0,0.3); border:1px solid var(--j-border); max-height:300px; overflow-y:auto; z-index:99999; }' +
                '.jerry-q-header { padding:10px 14px; border-bottom:1px solid var(--j-border); display:flex; justify-content:space-between; align-items:center; }' +
                '.jerry-q-footer { padding:8px 14px; border-top:1px solid var(--j-border); text-align:right; }' +
                '.jerry-q-item { display:flex; align-items:center; justify-content:space-between; padding:8px; border-bottom:1px solid var(--j-item-border); font-size:12px; }' +
                '.jerry-q-item-text { color:var(--j-text); font-weight:bold; }' +
                '.jerry-q-item-time { color:var(--j-muted); margin-left:6px; }' +
                '.jerry-delete-queued { background:none; border:none; color:var(--j-muted); cursor:pointer; padding:4px; }' +
                '.jerry-delete-queued:hover { color:#f44336; }' +
                '</style>'
            );
        }

        var html = '' +
        '<div id="jerry-offline-statusbar">' +
            '<div id="jerry-offline-pill" class="state-online">' +
                '<i class="fas fa-wifi jerry-pill-icon" id="jerry-offline-dot"></i>' +
                '<span id="jerry-offline-status-text" style="font-size:11px;">Online</span>' +
                '<span id="jerry-offline-badge" style="background:#ff9800; color:#fff; padding:0px 5px; border-radius:8px; font-size:10px; display:none; min-width:14px; text-align:center; position:absolute; top:-4px; right:-4px;">0</span>' +
            '</div>' +
            @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_offline_big_shop') == '1')
            '<button id="jerry-cache-now-btn" title="Cache all products now for offline use" style="margin-left:6px; padding:5px 10px; font-size:11px; font-weight:bold; background:#ff9800; color:#fff; border:none; border-radius:6px; cursor:pointer; display:inline-flex; align-items:center; gap:4px;"><i class="fas fa-database"></i> <span id="jerry-cache-now-text">Cache Products</span></button>' +
            @endif
            '<div id="jerry-offline-queue-panel">' +
                '<div class="jerry-q-header">' +
                    '<strong style="font-size:13px;"><i class="fas fa-list"></i> Pending Sales</strong>' +
                    '<button id="jerry-sync-now-btn" class="btn btn-xs btn-success" style="font-size:11px;"><i class="fas fa-sync-alt"></i> Sync Now</button>' +
                '</div>' +
                '<div id="jerry-queue-list" style="padding:8px;"></div>' +
                '<div class="jerry-q-footer">' +
                    '<button id="jerry-clear-synced-btn" class="btn btn-xs btn-default" style="font-size:11px;"><i class="fas fa-broom"></i> Clear Synced</button>' +
                '</div>' +
            '</div>' +
        '</div>';

        // Insert into POS footer action bar (FIRST container only to prevent duplicates)
        var $actionBar = $('.pos-form-actions .tw-overflow-x-auto').first();
        if ($actionBar.length) {
            $actionBar.append(html);
        } else {
            $('body').append(html);
        }

        // Cache Now button (Big Shop Mode only) — manual pre-warm trigger
        $(document).on('click', '#jerry-cache-now-btn', function() {
            if (isPrewarming) {
                // Abort running pre-warm
                prewarmAborted = true;
                $(this).html('<i class="fas fa-database"></i> <span id="jerry-cache-now-text">Cache Products</span>').css('background', '#ff9800');
                return;
            }
            if (!isOnline) {
                toastr.warning('You are offline. Connect to internet first.');
                return;
            }
            var $btn = $(this);
            $btn.html('<i class="fas fa-spinner fa-spin"></i> <span id="jerry-cache-now-text">Starting...</span>').css('background', '#e67e22');
            toastr.info('Caching products in background. You can keep selling normally.', 'Cache Started', { timeOut: 4000 });
            prewarmRowCache(
                function(done, total) {
                    // onProgress
                    var pct = total > 0 ? Math.round(done / total * 100) : 0;
                    $('#jerry-cache-now-text').text(done + '/' + total + ' (' + pct + '%)');
                    if (!isPrewarming) {
                        $btn.html('<i class="fas fa-database"></i> <span id="jerry-cache-now-text">Cache Products</span>').css('background', '#ff9800');
                    }
                },
                function(done, total) {
                    // onDone
                    if (done === 0 && total === 0) {
                        toastr.success('All products already cached!', 'Cache Complete', { timeOut: 3000 });
                    } else {
                        toastr.success('Cached ' + done + ' products successfully.', 'Cache Complete', { timeOut: 4000 });
                    }
                    $btn.html('<i class="fas fa-database"></i> <span id="jerry-cache-now-text">Cache Products</span>').css('background', '#ff9800');
                }
            );
        });

        // Toggle queue panel
        $(document).on('click', '#jerry-offline-pill', function(e) {
            e.stopPropagation();
            var panel = $('#jerry-offline-queue-panel');
            if (panel.is(':visible')) {
                panel.slideUp(200);
            } else {
                refreshQueueList();
                panel.slideDown(200);
            }
        });

        // Close panel on outside click
        $(document).on('click', function() {
            $('#jerry-offline-queue-panel').slideUp(200);
        });
        $(document).on('click', '#jerry-offline-queue-panel', function(e) {
            e.stopPropagation();
        });

        // Sync Now button — actively check server before giving up
        $(document).on('click', '#jerry-sync-now-btn', function() {
            var $btn = $(this);
            $btn.prop('disabled', true).text('Checking...');
            $.ajax({
                url: HEARTBEAT_URL,
                method: 'HEAD',
                timeout: 5000,
                cache: false,
                success: function() {
                    isOnline = true;
                    updateStatusUI();
                    $btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Sync Now');
                    syncPendingSales();
                    toastr.info('Syncing pending sales...');
                },
                error: function() {
                    isOnline = false;
                    updateStatusUI();
                    $btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Sync Now');
                    toastr.warning('Server still unreachable — cannot sync yet.');
                }
            });
        });

        // Clear synced
        $(document).on('click', '#jerry-clear-synced-btn', function() {
            clearAllSynced(function() {
                refreshQueueList();
                toastr.info('Synced records cleared.');
            });
        });

        // Delete individual
        $(document).on('click', '.jerry-delete-queued', function() {
            var id = parseInt($(this).data('id'));
            if (confirm('Delete this queued sale? It will NOT be synced.')) {
                deleteFromQueue(id, function() {
                    refreshQueueList();
                    toastr.info('Queued sale removed.');
                });
            }
        });
    }

    function updateStatusUI() {
        getPendingCount(function(count) {
            var pill = $('#jerry-offline-pill');
            var dot = $('#jerry-offline-dot');
            var text = $('#jerry-offline-status-text');
            var badge = $('#jerry-offline-badge');

            if (!pill.length) return;

            // Strip existing states
            pill.removeClass('state-online state-offline state-syncing');

            if (isSyncing) {
                pill.addClass('state-syncing');
                dot.css({ background: '#ff9800' }).addClass('jerry-pulse');
                text.text('Syncing...');
                badge.text(count).show().css({ background: 'var(--j-bg)', color: 'var(--j-text)' });
            } else if (!navigator.onLine || !isOnline) {
                pill.addClass('state-offline');
                dot.css({ background: '#f44336' }).addClass('jerry-pulse');
                text.text('Offline');
                if (count > 0) {
                    badge.text(count).css({ background: '#f44336', color: '#fff' }).show();
                } else {
                    badge.hide();
                }
            } else {
                pill.addClass('state-online');
                dot.css({ background: '#4caf50' }).removeClass('jerry-pulse');
                if (count > 0) {
                    text.text('Online');
                    badge.text(count).css({ background: '#ff9800', color: '#fff' }).show();
                } else {
                    text.text('Online');
                    badge.hide();
                }
            }
        });
    }

    function refreshQueueList() {
        openDB(function(db) {
            var tx = db.transaction(STORE_NAME, 'readonly');
            var store = tx.objectStore(STORE_NAME);
            var list = [];
            store.openCursor(null, 'prev').onsuccess = function(e) {
                var cursor = e.target.result;
                if (cursor) {
                    list.push(cursor.value);
                    cursor.continue();
                } else {
                    renderQueueList(list);
                }
            };
        });
    }

    function renderQueueList(items) {
        var container = $('#jerry-queue-list');
        if (items.length === 0) {
            container.html('<p style="text-align:center; color:var(--j-muted); padding:16px 12px; font-size:12px; font-style:italic;">No queued sales</p>');
            return;
        }
        var html = '';
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var statusColor = item.status === 'synced' ? '#4caf50' : (item.status === 'pending' ? '#ff9800' : '#f44336');
            var statusIcon = item.status === 'synced' ? 'fa-check-circle' : 'fa-clock';
            var time = new Date(item.timestamp);
            var timeStr = time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            html += '<div class="jerry-q-item">' +
                '<div>' +
                    '<i class="fas ' + statusIcon + '" style="color:' + statusColor + '; margin-right:6px; font-size:14px;"></i>' +
                    '<span class="jerry-q-item-text">' + (item.customerName || 'Walk-In') + '</span>' +
                    '<span class="jerry-q-item-time">' + timeStr + '</span>' +
                    (item.retryCount > 0 ? ' <span style="color:#f44336; font-size:10px; margin-left:4px;">(retry ' + item.retryCount + ')</span>' : '') +
                '</div>' +
                '<div>' +
                    '<strong style="color:var(--j-text); margin-right:10px; font-size:11px;">' + (item.totalAmount || '') + '</strong>' +
                    (item.status === 'pending' ? '<button class="jerry-delete-queued" data-id="' + item.id + '" title="Delete"><i class="fas fa-trash"></i></button>' : '') +
                '</div>' +
            '</div>';
        }
        container.html(html);
    }

    // =============== OFFLINE RECEIPT ===============
    function captureOfflineReceipt() {
        var items = [];
        $('#pos_table tbody tr.product_row').each(function() {
            var name = $(this).find('td:first').text().trim().split('\n')[0].trim();
            var qty = 0;
            var qtyEl = $(this).find('.pos_quantity');
            if (qtyEl.length) qty = parseFloat(qtyEl.val()) || 1;
            var price = 0;
            var priceEl = $(this).find('.pos_unit_price_inc_tax');
            if (priceEl.length) price = parseFloat(priceEl.val()) || 0;
            var lineTotal = 0;
            var ltEl = $(this).find('.pos_line_total');
            if (ltEl.length) lineTotal = parseFloat(ltEl.val()) || 0;
            if (!lineTotal) lineTotal = qty * price;
            items.push({ name: name, qty: qty, price: price, total: lineTotal });
        });
        
        var totalText = $('span.price_total').text().trim() || '0';
        var customerText = $('select#customer_id option:selected').text().trim() || 'Walk-In';
        var paymentMethod = 'Cash';
        if ($('#modal_payment').is(':visible')) {
            var selPay = $('select.payment_types_dropdown:first');
            if (selPay.length) paymentMethod = selPay.find(':selected').text().trim() || 'Cash';
        }
        
        return {
            items: items,
            total: totalText,
            customer: customerText,
            paymentMethod: paymentMethod,
            timestamp: new Date().toLocaleString(),
            offlineId: 'OFF-' + Date.now()
        };
    }

    function printOfflineReceipt(data) {
        if (!data || !data.items || data.items.length === 0) return;
        
        // Try to use V12's receipt print mechanism with cached template
        var cachedReceipt = null;
        try {
            var raw = localStorage.getItem('jerry_last_receipt_biz_' + CURRENT_BIZ_ID);
            if (raw) cachedReceipt = JSON.parse(raw);
        } catch(e) {}
        
        if (cachedReceipt && cachedReceipt.html_content && typeof pos_print === 'function') {
            // Modify the cached receipt HTML to show current sale data
            var receiptHtml = cachedReceipt.html_content;
            
            // Build items table rows
            var itemRows = '';
            for (var i = 0; i < data.items.length; i++) {
                var item = data.items[i];
                itemRows += '<tr><td>' + item.name + '</td>' +
                    '<td style="text-align:right">' + item.qty + '</td>' +
                    '<td style="text-align:right">' + item.price.toFixed(2) + '</td>' +
                    '<td style="text-align:right">' + item.total.toFixed(2) + '</td></tr>';
            }
            
            // Build a receipt using the original template's style
            // Extract header (business info) from cached receipt up to the table
            var headerMatch = receiptHtml.match(/^([\s\S]*?)<table/i);
            var footerMatch = receiptHtml.match(/<\/table>([\s\S]*?)$/i);
            var headerHtml = headerMatch ? headerMatch[1] : '';
            var footerHtml = footerMatch ? footerMatch[1] : '';
            
            var offlineReceiptHtml = headerHtml +
                '<div style="text-align:center;padding:4px;margin:4px 0;border:1px dashed #f80;background:#fff8e1;font-weight:bold;font-size:11px">' +
                    '⚠ OFFLINE RECEIPT — PENDING SYNC<br>Ref: ' + data.offlineId +
                '</div>' +
                '<table width="100%" cellspacing="0" cellpadding="2">' +
                '<tr><th align="left">Item</th><th align="right">Qty</th><th align="right">Price</th><th align="right">Total</th></tr>' +
                itemRows +
                '<tr><td colspan="3" align="right" style="border-top:1px dashed #000;padding-top:4px"><strong>TOTAL:</strong></td>' +
                '<td align="right" style="border-top:1px dashed #000;padding-top:4px"><strong>' + data.total + '</strong></td></tr>' +
                '</table>' +
                '<div style="text-align:center;font-size:10px;margin-top:6px">Customer: ' + data.customer + ' | ' + data.paymentMethod + '</div>' +
                '<div style="text-align:center;font-size:10px;margin-top:2px">' + data.timestamp + '</div>' +
                footerHtml;
            
            // Use V12's own receipt print system
            var offlineReceipt = {
                html_content: offlineReceiptHtml,
                print_type: cachedReceipt.print_type || 'browser',
                is_enabled: true
            };
            pos_print(offlineReceipt);
            return;
        }
        
        // Fallback: basic receipt in the receipt_section div  
        var businessName = $('nav .logo-lg').text().trim() || $('.navbar-brand').text().trim() || 'POS';
        var html = '<div style="font-family:monospace;font-size:12px;width:280px;margin:0 auto;padding:10px">' +
            '<div style="text-align:center;font-weight:bold;font-size:14px">' + businessName + '</div>' +
            '<div style="text-align:center">' + data.timestamp + '</div>' +
            '<div style="text-align:center;padding:5px;margin:5px 0;border:1px dashed #f80;background:#fff8e1;font-weight:bold;font-size:11px">' +
                '⚠ OFFLINE — PENDING SYNC<br>Ref: ' + data.offlineId + '</div>' +
            '<hr style="border:none;border-top:1px dashed #000">' +
            '<div>Customer: ' + data.customer + '</div>' +
            '<div>Payment: ' + data.paymentMethod + '</div>' +
            '<hr style="border:none;border-top:1px dashed #000">' +
            '<table style="width:100%"><tr style="font-weight:bold"><td>Item</td><td style="text-align:right">Qty</td><td style="text-align:right">Price</td><td style="text-align:right">Total</td></tr>';
        for (var j = 0; j < data.items.length; j++) {
            var it = data.items[j];
            html += '<tr><td>' + it.name.substring(0, 20) + '</td><td style="text-align:right">' + it.qty + 
                '</td><td style="text-align:right">' + it.price.toFixed(2) + 
                '</td><td style="text-align:right">' + it.total.toFixed(2) + '</td></tr>';
        }
        html += '</table><hr style="border:none;border-top:1px dashed #000">' +
            '<div style="text-align:right;font-weight:bold;font-size:14px">TOTAL: ' + data.total + '</div>' +
            '<hr style="border:none;border-top:1px dashed #000">' +
            '<div style="text-align:center;font-size:10px;margin-top:8px">Will sync when internet is restored</div></div>';
        
        // Use V12's print mechanism
        $('#receipt_section').html(html);
        if (typeof __print_receipt === 'function') {
            __print_receipt('receipt_section');
        }
    }

    // =============== INIT ===============
    $(document).ready(function() {
        openDB(function() {
            injectStatusBar();
            updateStatusUI();
            if (isOnline) {
                setTimeout(function() { syncPendingSales(); }, 3000);
            }
        });

        // ============================================================
        // PRODUCT ROW CACHE — IndexedDB backed (no localStorage quota)
        // Handles 5000+ products without any QuotaExceededError.
        // Each variation is stored as one record keyed by:
        //   "{settingsHash}_b{bizId}_l{locId}_{variationId}"
        // ============================================================
        var ROW_CACHE_KEY_BASE = '{{ md5(\Modules\JerryUpdates\Utils\JerrySettings::get("jerry_pos_cart_show_pp") . \Modules\JerryUpdates\Utils\JerrySettings::get("jerry_pos_hide_qty_buttons") . \Modules\JerryUpdates\Utils\JerrySettings::get("jerry_pos_hide_unit_dropdown") . \Modules\JerryUpdates\Utils\JerrySettings::get("jerry_pos_cart_hide_images")) }}';
        var ROW_CACHE_MAX = {{ max(100, (int) (\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_offline_row_cache_max', '5000'))) }};
        var ROW_CACHE_EVICT_TO = Math.floor(ROW_CACHE_MAX * 0.8); // keep newest 80% on eviction

        function getRowIDBKey(variation_id) {
            var loc_id = $('#location_id').val() || '0';
            return ROW_CACHE_KEY_BASE + '_b' + CURRENT_BIZ_ID + '_l' + loc_id + '_' + variation_id;
        }

        // Write a product row to IndexedDB (fire-and-forget, no return value needed)
        function cacheRowResponse(variation_id, result) {
            openDB(function(db) {
                var tx = db.transaction(ROW_CACHE_STORE, 'readwrite');
                var store = tx.objectStore(ROW_CACHE_STORE);
                store.put({
                    cache_key: getRowIDBKey(variation_id),
                    html_content: result.html_content,
                    enable_sr_no: result.enable_sr_no || '0',
                    html_modifier: result.html_modifier || '',
                    timestamp: Date.now()
                });
                // Evict oldest rows if over cap (runs asynchronously, won't block UI)
                tx.oncomplete = function() { evictOldRowsIfNeeded(); };
            });
        }

        // Read a cached row; result passed to callback(entry|null)
        function getCachedRow(variation_id, callback) {
            openDB(function(db) {
                var tx = db.transaction(ROW_CACHE_STORE, 'readonly');
                var store = tx.objectStore(ROW_CACHE_STORE);
                var req = store.get(getRowIDBKey(variation_id));
                req.onsuccess = function() { callback(req.result || null); };
                req.onerror   = function() { callback(null); };
            });
        }

        // LRU eviction: delete oldest entries when over ROW_CACHE_MAX
        function evictOldRowsIfNeeded() {
            openDB(function(db) {
                var tx = db.transaction(ROW_CACHE_STORE, 'readonly');
                var store = tx.objectStore(ROW_CACHE_STORE);
                var countReq = store.count();
                countReq.onsuccess = function() {
                    var total = countReq.result;
                    if (total <= ROW_CACHE_MAX) return;
                    // Collect all keys sorted by timestamp ascending
                    var idx = store.index('timestamp');
                    var toDelete = [];
                    var needed = total - ROW_CACHE_EVICT_TO;
                    idx.openCursor(null, 'next').onsuccess = function(e) {
                        var cursor = e.target.result;
                        if (cursor && toDelete.length < needed) {
                            toDelete.push(cursor.value.cache_key);
                            cursor.continue();
                        } else {
                            if (toDelete.length === 0) return;
                            var delTx = db.transaction(ROW_CACHE_STORE, 'readwrite');
                            var delStore = delTx.objectStore(ROW_CACHE_STORE);
                            for (var d = 0; d < toDelete.length; d++) { delStore.delete(toDelete[d]); }
                            delTx.oncomplete = function() {
                                console.log('JerryOffline: Evicted ' + toDelete.length + ' old product rows from cache.');
                            };
                        }
                    };
                };
            });
        }

        // On load: delete any leftover localStorage row cache keys from the old implementation
        (function cleanupLegacyLocalStorageCache() {
            try {
                var toRemove = [];
                for (var i = 0; i < localStorage.length; i++) {
                    var k = localStorage.key(i);
                    if (k && (k.indexOf('jerry_product_row_cache_') === 0 || k.indexOf('jerry_prc_') === 0)) {
                        toRemove.push(k);
                    }
                }
                for (var j = 0; j < toRemove.length; j++) { localStorage.removeItem(toRemove[j]); }
                if (toRemove.length > 0) console.log('JerryOffline: Cleaned up ' + toRemove.length + ' legacy localStorage cache entries.');
            } catch(e) {}
        })();

        // Rewrite row_index numbers in cached HTML to match the current row count
        function reindexRowHtml(html, newIndex) {
            // Replace data-row_index="N"
            html = html.replace(/data-row_index="[^"]*"/g, 'data-row_index="' + newIndex + '"');
            // Replace products[N] with products[newIndex]
            html = html.replace(/products\[\d+\]/g, 'products[' + newIndex + ']');
            // Replace row_edit_product_price_modal_N
            html = html.replace(/row_edit_product_price_modal_\d+/g, 'row_edit_product_price_modal_' + newIndex);
            return html;
        }

        window.pos_product_row = function(variation_id, purchase_line_id, weighing_scale_barcode, quantity) {
            variation_id = variation_id || null;
            purchase_line_id = purchase_line_id || null;
            weighing_scale_barcode = weighing_scale_barcode || null;
            quantity = quantity || 1;

            // Check item addition method — increment existing row if applicable
            var item_addtn_method = 0;
            if (variation_id != null && $('#item_addition_method').length) {
                item_addtn_method = $('#item_addition_method').val();
            }
            if (item_addtn_method != 0) {
                var is_added = false;
                $('#pos_table tbody').find('tr').each(function() {
                    var row_v_id = $(this).find('.row_variation_id').val();
                    var enable_sr_no = $(this).find('.enable_sr_no').val();
                    var modifiers_exist = $(this).find('input.modifiers_exist').length > 0;
                    if (row_v_id == variation_id && enable_sr_no !== '1' && !modifiers_exist && !is_added) {
                        is_added = true;
                        var qty_element = $(this).find('.pos_quantity');
                        var qty = __read_number(qty_element);
                        __write_number(qty_element, qty + 1);
                        qty_element.change();
                        if (typeof round_row_to_iraqi_dinnar === 'function') round_row_to_iraqi_dinnar($(this));
                        if (!$('#__is_mobile').length) $('input#search_product').focus().select();
                    }
                });
                if (is_added) return;
            }

            // Collect form data
            var product_row = $('input#product_row_count').val();
            var location_id = $('input#location_id').val();
            var customer_id = $('select#customer_id').val();
            var is_direct_sell = $('input[name="is_direct_sale"]').length > 0 && $('input[name="is_direct_sale"]').val() == 1;
            var price_group = $('#price_group').length > 0 ? $('#price_group').val() : '';
            if ($('#default_price_group').length > 0 && price_group === '') price_group = $('#default_price_group').val();
            if ($('#types_of_service_price_group').length > 0 && $('#types_of_service_price_group').val()) price_group = $('#types_of_service_price_group').val();
            var is_draft = false;
            if ($('#status') && ($('#status').val()=='quotation' || $('#status').val()=='draft')) is_draft = true;
            var disable_qty_alert = $('#disable_qty_alert').length > 0;
            var is_sales_order = $('#sale_type').length && $('#sale_type').val() == 'sales_order';
            var is_serial_no = $('input[name="is_serial_no"]').length > 0 && $('input[name="is_serial_no"]').val() == 1;

            // Try server first (online-first) — async so the UI never freezes
            $.ajax({
                method: 'GET',
                url: '/sells/pos/get_product_row/' + variation_id + '/' + location_id,
                async: true,
                timeout: AJAX_TIMEOUT,
                data: {
                    product_row: product_row,
                    customer_id: customer_id,
                    is_direct_sell: is_direct_sell,
                    is_serial_no: is_serial_no,
                    price_group: price_group,
                    purchase_line_id: purchase_line_id,
                    weighing_scale_barcode: weighing_scale_barcode,
                    quantity: quantity,
                    is_sales_order: is_sales_order,
                    disable_qty_alert: disable_qty_alert,
                    is_draft: is_draft
                },
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        // Cache to IndexedDB for offline use
                        cacheRowResponse(variation_id, result);
                        pos_insert_product_row(result);
                    } else {
                        toastr.error(result.msg);
                        if (!$('#__is_mobile').length) $('input#search_product').focus().select();
                    }
                },
                error: function(xhr, status, error) {
                    // OFFLINE: Read from IndexedDB cache
                    console.log("JerryOffline: Server unreachable, reading IDB cache for variation " + variation_id);
                    getCachedRow(variation_id, function(cached) {
                        if (cached) {
                            var reindexedHtml = reindexRowHtml(cached.html_content, product_row);
                            var offlineResult = {
                                success: true,
                                html_content: reindexedHtml,
                                enable_sr_no: cached.enable_sr_no,
                                html_modifier: cached.html_modifier
                            };
                            pos_insert_product_row(offlineResult);
                            toastr.info('Product added from cache', 'Offline Mode', { timeOut: 2000 });
                        } else {
                            toastr.error('Product not in offline cache. Add it once while online first.');
                        }
                        isOnline = false;
                        updateStatusUI();
                    });
                }
            });
        };

        // =============== PRE-WARM ROW CACHE ===============
        // Big Shop Mode: pre-warm is DISABLED automatically.
        // The cashier must click "Cache Products" in the status bar to warm manually.
        // Standard Mode: auto-warms 10s after page load (fine for < ~2000 products).
        var BIG_SHOP_MODE = {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_offline_big_shop') == '1' ? 'true' : 'false' }};
        var isPrewarming = false;
        var prewarmAborted = false;

        function prewarmRowCache(onProgress, onDone) {
            if (!isOnline) return;
            if (isPrewarming) return; // already running
            if (typeof fullProductCache === 'undefined' || !fullProductCache.data) {
                if (onDone) onDone(0, 0);
                return;
            }
            var all = fullProductCache.data;
            var location_id = $('input#location_id').val();
            if (!all || all.length === 0) { if (onDone) onDone(0, 0); return; }

            isPrewarming = true;
            prewarmAborted = false;

            openDB(function(db) {
                var tx = db.transaction(ROW_CACHE_STORE, 'readonly');
                var store = tx.objectStore(ROW_CACHE_STORE);
                var existingKeys = {};
                store.openCursor().onsuccess = function(e) {
                    var cursor = e.target.result;
                    if (cursor) {
                        existingKeys[cursor.value.cache_key] = true;
                        cursor.continue();
                    } else {
                        var uncached = [];
                        for (var i = 0; i < all.length; i++) {
                            var vid = all[i].variation_id;
                            var key = ROW_CACHE_KEY_BASE + '_b' + CURRENT_BIZ_ID + '_l' + (location_id || '0') + '_' + vid;
                            if (!existingKeys[key]) uncached.push(vid);
                        }

                        // Protect against exceeding the offline limit during pre-warm
                        var currentCacheSize = Object.keys(existingKeys).length;
                        var roomLeft = ROW_CACHE_MAX - currentCacheSize;
                        
                        if (roomLeft <= 0) {
                            uncached = [];
                        } else if (uncached.length > roomLeft) {
                            uncached = uncached.slice(0, roomLeft);
                            console.log('JerryOffline: Limiting pre-warm to ' + roomLeft + ' items to respect ROW_CACHE_MAX');
                        }

                        if (uncached.length === 0) {
                            console.log('JerryOffline: Row cache already warm or at max capacity.');
                            isPrewarming = false;
                            if (onDone) onDone(0, 0); // 0 new
                            return;
                        }
                        console.log('JerryOffline: Pre-warming IDB row cache for ' + uncached.length + ' products...');
                        var idx = 0;
                        var total = uncached.length;
                        function fetchNext() {
                            if (prewarmAborted || !isOnline) {
                                console.log('JerryOffline: Pre-warm stopped at ' + idx + '/' + total);
                                isPrewarming = false;
                                if (onDone) onDone(idx, total);
                                return;
                            }
                            if (idx >= total) {
                                console.log('JerryOffline: Pre-warm complete. ' + total + ' products cached.');
                                isPrewarming = false;
                                if (onDone) onDone(total, total);
                                return;
                            }
                            var vid = uncached[idx];
                            $.ajax({
                                method: 'GET',
                                url: '/sells/pos/get_product_row/' + vid + '/' + location_id,
                                timeout: 8000,
                                data: { product_row: 0, customer_id: $('select#customer_id').val() },
                                dataType: 'json',
                                success: function(result) {
                                    if (result.success) cacheRowResponse(vid, result);
                                },
                                complete: function() {
                                    idx++;
                                    if (onProgress) onProgress(idx, total);
                                    setTimeout(fetchNext, 150); // stagger to avoid server hammering
                                }
                            });
                        }
                        fetchNext();
                    }
                };
            });
        }

        // Trigger pre-warm ONLY in Standard Mode (not Big Shop)
        if (!BIG_SHOP_MODE) {
            setTimeout(function() {
                if (isOnline) {
                    prewarmRowCache(); // silent auto-warm
                }
            }, 10000);
        }
    });

})();
</script>
@endif
