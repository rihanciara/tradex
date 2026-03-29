<script type="text/javascript">
    var jerryCurrentBizId = '{{ session()->get("user.business_id") ?? session()->get("business.id") ?? "0" }}';

    // ============ GLOBAL: Application Tour Control ============
    @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_disable_tour') == '1')
    try {
        localStorage.setItem('upos_app_tour_shown_biz_' + jerryCurrentBizId, 'true');
    } catch(e) {}
    if ($('style#jerry-hide-tour').length === 0) {
        $('<style id="jerry-hide-tour">#start_tour, .tour-backdrop, .popover[class*="tour-"] { display: none !important; }</style>').appendTo('head');
    }
    $(document).ready(function() {
        $('#start_tour').remove();
    });
    @endif

    // ============ GLOBAL: Low-End PC Optimization ============
    @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_low_end_pc') == '1')
    if ($('style#jerry-low-end-pc').length === 0) {
        $('<style id="jerry-low-end-pc">* { transition: none !important; animation: none !important; box-shadow: none !important; text-shadow: none !important; }</style>').appendTo('head');
    }
    $(document).ready(function() {
        if (typeof $ !== 'undefined' && $.fx) {
            $.fx.off = true; // Disable all jQuery slide/fade animations globally
        }
    });
    @endif

    @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_sell_tweaks') == '1')
    // F8 Global Shortcut
    $(document).on("keydown", function(e) {
        if (e.key === "F8" || e.keyCode === 119) {
            if ($("button[value=submit], button#pos-save, button#pos-finalize, button#submit-sell").length > 0) {
                e.preventDefault();
                $("button[value=submit], button#pos-save, button#pos-finalize, button#submit-sell").first().click();
                setTimeout(function(){ $("button#submit_action").first().click(); }, 100);
            }
        }
    });
    @endif

    // Re-eval function since UltimatePOS uses AJAX modals heavily
    function applyJerryTweaks() {
        var path = window.location.pathname;

        // ============ GLOBAL: Custom UI Translations ============
        @php
            $customTranslations = \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_custom_translations');
            $translationsArr = [];
            if (!empty($customTranslations)) {
                $translationsArr = json_decode($customTranslations, true) ?? [];
            }
        @endphp
        @if(!empty($translationsArr))
        var jerryTrans = {!! json_encode($translationsArr) !!};
        if (Object.keys(jerryTrans).length > 0) {
            // High-performance TextNode walker
            var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
                acceptNode: function(node) {
                    if (node.parentElement && (node.parentElement.tagName === 'SCRIPT' || node.parentElement.tagName === 'STYLE' || node.parentElement.tagName === 'TEXTAREA' || $(node.parentElement).hasClass('jerry-ignore-trans'))) {
                        return NodeFilter.FILTER_REJECT;
                    }
                    if (node.nodeValue.trim() === '') return NodeFilter.FILTER_REJECT;
                    return NodeFilter.FILTER_ACCEPT;
                }
            }, false);

            var nodesToUpdate = [];
            while (walker.nextNode()) { nodesToUpdate.push(walker.currentNode); }
            
            for (var i = 0; i < nodesToUpdate.length; i++) {
                var text = nodesToUpdate[i].nodeValue;
                var changed = false;
                for (var key in jerryTrans) {
                    if (text.includes(key)) {
                        // Use a globally case-sensitive replacement for the exact matched string
                        text = text.split(key).join(jerryTrans[key]);
                        changed = true;
                    }
                }
                if (changed) {
                    nodesToUpdate[i].nodeValue = text;
                }
            }
        }
        @endif


        @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_sell_tweaks') == '1')
        // 1. Sell Screen (Includes POS)
        if (path.indexOf("/sells/create") !== -1 || path.indexOf("/pos/create") !== -1 || path.indexOf("/sells/") !== -1 || window.location.hash.includes("#sell_modal") || $("form#add_pos_sell_form").length || $("form#add_sell_form").length) {
            $(".shipping_details, h4:contains('Shipping')").closest(".box").hide();
            $("select#invoice_scheme_id").closest("div.col-sm-4, div.col-md-4, div.col-md-3, .form-group").hide();
        }
        @endif

        // 1.5 Add Product Screen: Dynamic Category Create & Tax Default & Hiding
        if (path.indexOf("/products/create") !== -1 || path.indexOf("/products/") !== -1 || $("form#product_add_form").length || $("form#product_edit_form").length) {
            
            // --- JERRY TWEAKS: QUICK HIDE MIDDLE SECTION FIELDS ---
            @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_product_hide_middle') == '1')
            var hideMiddleFields = [
                "input#product_custom_field1", 
                "input#product_custom_field2", 
                "input#product_custom_field3", 
                "input#product_custom_field4",
                "input#weight",
                "input#enable_sr_no", // IMEI or Serial Number flag
                ".rack_details", // Racks/Rows/Positions grouping
                "textarea#product_description" // Product Description
            ];

            $.each(hideMiddleFields, function(index, selector) {
                var el = $(selector);
                if (el.length) {
                    if (selector === ".rack_details") {
                        el.hide();
                    } else if (el.closest(".form-group").closest('div[class^="col-"]').length) {
                        el.closest(".form-group").closest('div[class^="col-"]').hide();
                    } else {
                        el.closest(".form-group").hide();
                    }
                }
            });
            @endif
            // ------------------------------------------------------
            
            // Tax Type Default to Inclusive
            @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_product_tax_inclusive') == '1')
            if ($("form#product_add_form").length > 0 && $("select#tax_type").length > 0 && !$("select#tax_type").data("jerry-fixed")) {
                setTimeout(function() {
                    $("select#tax_type").val("inclusive").trigger("change").data("jerry-fixed", true);
                }, 500);
            }
            @endif

            // Purchase Price Default to 0
            @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_product_purchase_zero') == '1')
            if ($("form#product_add_form").length > 0 && $("input#single_dpp").length > 0 && !$("input#single_dpp").data("jerry-fixed")) {
                setTimeout(function() {
                    if ($("input#single_dpp").val() === "") {
                        $("input#single_dpp").val("0").trigger("change").data("jerry-fixed", true);
                    }
                }, 500);
            }
            @endif

            @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_product_tweaks') == '1')
            if ($("button.btn-add-product-category").length === 0 && $("select#category_id").length > 0) {
                // Ensure the modal container exists
                if ($(".category_modal").length === 0) {
                    $("body").append("<div class='modal fade category_modal' tabindex='-1' role='dialog' aria-labelledby='gridSystemModalLabel'></div>");
                }
                
                // Add the plus button natively styled via input-group wrapper
                var $select = $("select#category_id");
                var $formGroup = $select.closest(".form-group");
                if ($formGroup.find(".input-group").length === 0) {
                    $select.next(".select2-container").remove();
                    $select.wrap("<div class='input-group'></div>");
                    $select.closest(".input-group").append("<span class='input-group-btn'><button type='button' class='btn btn-default bg-white btn-flat btn-modal btn-add-product-category' title='Add Category' data-href='/taxonomies/create?type=product' data-container='.category_modal'><i class='fa fa-plus-circle text-primary fa-lg'></i></button></span>");
                    $select.select2();
                }

                // Handle the form submission natively to update dropdown
                $(document).off("submit.jerry_category").on("submit.jerry_category", ".category_modal form", function(e) {
                    e.preventDefault();
                    var $form = $(this);
                    $form.find('button[type="submit"]').attr('disabled', true);
                    
                    $.ajax({
                        method: "POST",
                        url: $form.attr("action"),
                        dataType: "json",
                        data: $form.serialize(),
                        success: function(result) {
                            if (result.success === true) {
                                $("div.category_modal").modal("hide");
                                toastr.success(result.msg);
                                
                                // Append the new category to the dropdown
                                if (result.data) {
                                    var newOption = new Option(result.data.name, result.data.id, true, true);
                                    $("select#category_id").append(newOption).trigger("change");
                                }
                            } else {
                                toastr.error(result.msg);
                            }
                            $form.find('button[type="submit"]').attr('disabled', false);
                        }
                    });
                });
            }
            @endif
        }

        @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_expense_tweaks') == '1')
        // 2. Expense Screen Modal
        if (path.indexOf("/expenses/create") !== -1 || $("form#expense_add_form").length) {
            if($("button.btn-add-expense-category").length === 0 && $("select#expense_category_id").length > 0) {
                var $catSelect = $("select#expense_category_id");
                var $catGroup = $catSelect.closest(".form-group");
                if ($catGroup.find(".input-group").length === 0) {
                    $catSelect.next(".select2-container").remove();
                    $catSelect.wrap("<div class='input-group'></div>");
                    $catSelect.closest(".input-group").append("<span class='input-group-btn'><button type='button' class='btn btn-default bg-white btn-flat btn-modal btn-add-expense-category' data-href='/expense-categories/create' data-container='.expense_category_modal'><i class='fa fa-plus-circle text-primary fa-lg'></i></button></span>");
                    $catSelect.select2();
                }
            }
        }
        @endif

        @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_contact_tweaks') == '1')
        // 3. Contact Screen Modal
        if ($("select#type").length || $("select#contact_type").length) {
            var type = $("select#type").val() || $("select#contact_type").val();
            if (type === "individual" || type === "customer" || type === "Individual" || type === "Customer") {
                $("input#supplier_business_name").closest(".form-group").parent().hide();
                $("input#supplier_business_name").closest(".col-md-3, .col-sm-4").hide();
            } else {
                $("input#supplier_business_name").closest(".form-group").parent().show();
                $("input#supplier_business_name").closest(".col-md-3, .col-sm-4").show();
            }
        }
        @endif

        @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_label_tweaks') == '1')
        // 4. Labels Screen
        if (path.indexOf("/labels/show") !== -1) {
            $("input#print_business_name").prop("checked", false);
            if ($("input#print_business_name").closest(".icheckbox_square-blue").hasClass("checked")) {
                $("input#print_business_name").iCheck("uncheck");
            }
            $("input#print_packing_date").prop("checked", false);
            if ($("input#print_packing_date").closest(".icheckbox_square-blue").hasClass("checked")) {
                $("input#print_packing_date").iCheck("uncheck");
            }
        }
        @endif
    }

    $(document).ready(function() {
        applyJerryTweaks();
        
        @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_sell_tweaks') == '1')
        // Defaults (Fire only once)
        var path = window.location.pathname;
        if (path.indexOf("/sells/create") !== -1 || path.indexOf("/pos/create") !== -1) {
            setTimeout(function(){
                $("select#status").val("final").trigger("change");
                $("input#discount_type").val("fixed");
                $("select[name='discount_type_modal']").val("fixed");
            }, 500);
        }
        @endif
    });

    $(document).ajaxComplete(function(event, xhr, settings) {
        applyJerryTweaks();
    });

    $(document).on("shown.bs.modal", function() {
        applyJerryTweaks();
    });

    @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_expense_tweaks') == '1')
    // Expense Screen Autocomplete Sync
    $(document).on("change keyup input", "input#final_total", function() {
        var form = $(this).closest("form");
        if (form.attr("id") === "expense_add_form" || window.location.pathname.indexOf("/expenses/") !== -1) {
            form.find("input.payment-amount, input#amount_0").val($(this).val()).trigger('change');
        }
    });
    @endif

    @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_contact_tweaks') == '1')
    // Contact Screen dynamic hide/show change event
    $(document).on("change", "select#type, select#contact_type", function() {
        applyJerryTweaks();
    });
    @endif
</script>


@if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_running_balance') == '1')
<script type="text/javascript">
    $(document).ajaxComplete(function(event, xhr, settings) {
        if ($("#ledger_table").length > 0 && !$("#ledger_table").data("balance-injected")) {
            $("#ledger_table").data("balance-injected", true);
            
            $("#ledger_table thead tr").find("th:eq(6)").after("<th class='text-center bg-info text-white'>Running Balance</th>");
            
            let final_balance_str = $(".table-condensed").last().find("tr").last().find(".align-right").text();
            
            let parseCurrency = function(str) {
                if (!str) return 0;
                let val = str.replace(/[^0-9.-]+/g,"");
                return parseFloat(val) || 0;
            };
            
            let formatCurrency = function(num) {
                if (typeof __number_f !== "undefined") {
                    return "<span class='display_currency'>" + __number_f(num) + "</span>";
                }
                return parseFloat(num).toFixed(2);
            };
            
            let current_balance = parseCurrency(final_balance_str);
            let rows = $("#ledger_table tbody tr").get().reverse();
            
            $(rows).each(function() {
                let debit_str = $(this).find("td:eq(5)").text();
                let credit_str = $(this).find("td:eq(6)").text();
                
                let row_bal = current_balance; 
                
                $(this).find("td:eq(6)").after("<td class='ws-nowrap align-right' style='font-weight:bold; background-color:rgba(0,0,0,0.03);'>" + formatCurrency(row_bal) + "</td>");
                
                let debit = parseCurrency(debit_str);
                let credit = parseCurrency(credit_str);
                
                current_balance = current_balance - debit + credit;
            });
            
            if (typeof __currency_convert_recursively !== "undefined") {
                __currency_convert_recursively($("#ledger_table"));
            }
        }
    });
</script>
@endif

@if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_cache') == '1')
<style>
#jerry-prewarm-bar-wrap {
    position: fixed; top: 0; left: 0; width: 100%; z-index: 99999;
    pointer-events: none;
}
#jerry-prewarm-bar {
    height: 3px;
    background: linear-gradient(90deg, #6366f1, #22d3ee);
    width: 0%; transition: width 0.3s ease;
    box-shadow: 0 0 8px #6366f1aa;
}
#jerry-prewarm-label {
    position: fixed; top: 4px; right: 10px;
    font-size: 11px; color: #6366f1;
    background: rgba(255,255,255,0.85);
    padding: 1px 6px; border-radius: 4px;
    z-index: 99999; pointer-events: none;
    display: none; font-family: monospace;
}
</style>
<script type="text/javascript">
var path = window.location.pathname;
if (path.indexOf("/pos/create") !== -1 || path.indexOf("/sells/create") !== -1 || path.indexOf("/pos/") !== -1) {
    // ---------------- CHUNKED PRODUCT CACHE (Speed Cache) ----------------
    window.fullProductCache = { data: [], ts: 0 };
    var FULL_CACHE_TTL = 24 * 60 * 60 * 1000; // 24 hours
    var JERRY_CHUNK_SIZE = 300;

    function getJerryPosCacheKey() {
        var biz_id = '{{ session()->get("user.business_id") ?? session()->get("business.id") ?? "0" }}';
        var loc_id = $('#location_id').val() || '0';
        return 'jerry_products_biz_' + biz_id + '_loc_' + loc_id;
    }

    function saveFullProductCache() {
        try { localStorage.setItem(getJerryPosCacheKey(), JSON.stringify(fullProductCache)); }
        catch(e) { localStorage.removeItem(getJerryPosCacheKey()); fullProductCache = { data: [], ts: 0 }; }
    }

    function clearFullProductCache() {
        fullProductCache = { data: [], ts: 0 };
        try { localStorage.removeItem(getJerryPosCacheKey()); } catch(e) {}
    }

    // ---- Progress Bar Helpers ----
    function jerryShowBar() {
        if ($('#jerry-prewarm-bar-wrap').length === 0) {
            $('body').append('<div id="jerry-prewarm-bar-wrap"><div id="jerry-prewarm-bar"></div></div><div id="jerry-prewarm-label">Loading products...</div>');
        }
        $('#jerry-prewarm-label').show();
    }
    function jerryUpdateBar(loaded, total) {
        var pct = total > 0 ? Math.min(100, Math.round((loaded / total) * 100)) : 10;
        $('#jerry-prewarm-bar').css('width', pct + '%');
        $('#jerry-prewarm-label').text('Cache: ' + loaded + '/' + total);
    }
    function jerryHideBar() {
        $('#jerry-prewarm-bar').css('width', '100%');
        setTimeout(function() {
            $('#jerry-prewarm-bar-wrap').fadeOut(400, function(){ $(this).remove(); });
            $('#jerry-prewarm-label').fadeOut(400, function(){ $(this).remove(); });
        }, 600);
    }

    // ---- Chunked Loader ----
    function preloadFullProductCache(callback) {
        var cacheKey = getJerryPosCacheKey();
        try {
            var raw = localStorage.getItem(cacheKey);
            if (raw) {
                fullProductCache = JSON.parse(raw) || { data: [], ts: 0 };
                if ((Date.now() - fullProductCache.ts) > FULL_CACHE_TTL) fullProductCache = { data: [], ts: 0 };
            }
        } catch(e) { clearFullProductCache(); }

        // Warm cache hit — return immediately, no bar
        if (fullProductCache.data && fullProductCache.data.length > 0) {
            if (callback) callback(fullProductCache.data);
            return;
        }

        // Cold load — fetch in chunks with progress bar
        var req_loc_id = $('#location_id').val() || '';
        jerryShowBar();
        
        var SPEED_CACHE_MAX = {{ (int) (\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_speed_cache_max', '20000')) }};

        $.getJSON('/jerryupdates/products/count', { location_id: req_loc_id }, function(res) {
            var total = res.total || 0;
            if (total === 0) { jerryHideBar(); if (callback) callback([]); return; }
            
            if (total > SPEED_CACHE_MAX) {
                console.warn('JerryUpdates: Product count (' + total + ') exceeds Speed Cache threshold (' + SPEED_CACHE_MAX + '). Safely aborting local cache and using server-side search.');
                if (typeof toastr !== 'undefined') {
                    toastr.info('Catalog size (' + total + ') exceeds safe memory threshold. Using server-side search.', 'Speed Cache Disabled', { timeOut: 5000 });
                }
                jerryHideBar();
                if (callback) callback([]);
                return;
            }

            var allData = [];
            var offset = 0;

            function fetchChunk() {
                $.getJSON('/jerryupdates/products/chunk', {
                    location_id: req_loc_id,
                    offset: offset,
                    limit: JERRY_CHUNK_SIZE
                }, function(chunk) {
                    if (chunk && chunk.length > 0) {
                        allData = allData.concat(chunk);
                        offset += chunk.length;
                        jerryUpdateBar(allData.length, total);
                    }

                    if (chunk && chunk.length === JERRY_CHUNK_SIZE && offset < total) {
                        // More chunks to load — continue in background
                        setTimeout(fetchChunk, 50);
                    } else {
                        // Done!
                        fullProductCache = { data: allData, ts: Date.now() };
                        saveFullProductCache();
                        jerryHideBar();
                        if (callback) callback(allData);
                    }
                }).fail(function() {
                    // On failure fall back gracefully — hide bar, try server search next time
                    jerryHideBar();
                });
            }

            fetchChunk();
        }).fail(function() {
            // Guard: Removed dangerous `/products/list` fallback that would crash PHP/MySQL on 500k products.
            jerryHideBar();
            console.error('JerryUpdates: Failed to get product count. Aborting Speed Cache to protect server.');
            if (callback) callback([]);
        });
    }

    // Auto-clear cache when location changes to fetch products for the new location
    $(document).on('change', '#location_id', function() {
        fullProductCache = { data: [], ts: 0 };
        preloadFullProductCache(function(data) {
            if (typeof updateRefreshBtnCount === 'function') {
                updateRefreshBtnCount(data.length);
            }
        });
    });

    $(document).on('click', '#clear-cache', function() {
        clearFullProductCache();
        toastr.success("Full product cache cleared! Data will reload from server on next search.");
    });

    $(document).ready(function() {
        setTimeout(function() {
            if ($('#search_product').length) {
                if ($('#search_product').data('ui-autocomplete')) {
                    $('#search_product').autocomplete("destroy");
                }

                $('#search_product').autocomplete({
                    delay: 250,
                    minLength: 2,
                    source: function(request, response) {
                        var price_group = $('#price_group').length > 0 ? $('#price_group').val() : '';
                        var search_fields = [];
                        $('.search_fields:checked').each(function(i){ search_fields[i] = $(this).val(); });
                        if (search_fields.length === 0) search_fields = ['name', 'sku', 'sub_sku'];

                        if (fullProductCache.data && fullProductCache.data.length > 0) {
                            var term = (request.term || '').toLowerCase();
                            var isNumeric = /^\d+$/.test(term);
                            let exactMatches = [], startsWithMatches = [], containsMatches = [];
                            
                            for (let i = 0; i < fullProductCache.data.length; i++) {
                                let p = fullProductCache.data[i];
                                let name = (p.name || "").toLowerCase();
                                let sku = (p.sku || "").toLowerCase();
                                let sub_sku = (p.sub_sku || "").toLowerCase();
                                
                                if (isNumeric) {
                                    if ((p.product_id && p.product_id.toString() === term) ||
                                        (p.variation_id && p.variation_id.toString() === term) ||
                                        sku === term || sub_sku === term) {
                                        exactMatches.push(p);
                                    }
                                    continue;
                                }
                                
                                if (name === term || sku === term || sub_sku === term) exactMatches.push(p);
                                else if (name.startsWith(term) || sku.startsWith(term) || sub_sku.startsWith(term)) startsWithMatches.push(p);
                                else if (name.includes(term) || sku.includes(term) || sub_sku.includes(term)) containsMatches.push(p);
                            }
                            let filtered = [...exactMatches, ...startsWithMatches, ...containsMatches].slice(0, 20);
                            
                            // Map price group if active
                            if (price_group) {
                                filtered = filtered.map(function(p) {
                                    var copy = Object.assign({}, p);
                                    if (copy.variation_group_price && typeof copy.variation_group_price === 'object' && copy.variation_group_price[price_group]) {
                                        copy.selling_price = copy.variation_group_price[price_group];
                                    }
                                    return copy;
                                });
                            }
                            
                            response(filtered);
                            return;
                        }

                        // Fallback to server
                        var customer_id = $('select#customer_id').val();
                        var is_direct_sell = $('input[name="is_direct_sale"]').length > 0 && $('input[name="is_direct_sale"]').val() == 1;
                        var disable_qty_alert = $('#disable_qty_alert').length ? true : false;
                        var is_sales_order = $('#sale_type').length && $('#sale_type').val() == 'sales_order' ? true : false;
                        var is_draft = $('#status') && ($('#status').val()=='quotation' || $('#status').val()=='draft') ? true : false;
                        var is_serial_no = $('input[name="is_serial_no"]').length > 0 && $('input[name="is_serial_no"]').val() == 1;

                        $.getJSON('/products/list', {
                            price_group: price_group,
                            location_id: $('input#location_id').val(),
                            term: request.term,
                            not_for_selling: 0,
                            search_fields: search_fields,
                            auto_add_single: {{ \Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_auto_add') == '1' ? 'true' : 'false' }},
                            product_row: $('input#product_row_count').val(),
                            customer_id: customer_id,
                            is_direct_sell: is_direct_sell,
                            is_serial_no: is_serial_no,
                            is_sales_order: is_sales_order,
                            disable_qty_alert: disable_qty_alert,
                            is_draft: is_draft
                        }, function(data) {
                            if (data.auto_add && data.row_data) {
                                if (data.row_data.success) {
                                    $('#search_product').val('');
                                    if (typeof pos_add_product_row_from_data === "function") pos_add_product_row_from_data(data.row_data);
                                }
                                response([{auto_added: true}]);
                            } else {
                                response(data.products || data);
                            }
                        });
                    },
                    response: function(event, ui) {
                        // Skip if auto-add already handled the product from server fallback
                        if (ui.content.length == 1 && ui.content[0].auto_added) {
                            return;
                        }
                    
                        var term = $(this).val().trim();
                        // ✅ Thufail Patch: Auto-select for numeric searches (barcode / ID) only
                        // Text search with 1 match: just show the dropdown, no auto-select.
                        @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_auto_select_patch') == '1')
                        if (/^\d+$/.test(term) && ui.content.length == 1) {
                        @else
                        if (ui.content.length == 1) {
                        @endif
                            ui.item = ui.content[0];
                            var is_overselling_allowed = $('input#is_overselling_allowed').length ? true : false;
                            var for_so = ($('#sale_type').length && $('#sale_type').val() == 'sales_order');
                            if ((ui.item.enable_stock == 1 && ui.item.qty_available > 0) || (ui.item.enable_stock == 0) || is_overselling_allowed || for_so) {
                                $(this).autocomplete('close');
                            }
                        }
                        
                        @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_no_match_toast') == '1')
                        // ✅ Search Error Spam Patch
                        if (ui.content.length == 0) {
                            if (typeof toastr !== 'undefined' && typeof LANG !== 'undefined' && LANG.no_products_found) {
                                toastr.error(LANG.no_products_found);
                                if (!$('#__is_mobile').length) {
                                    $('input#search_product').select();
                                }
                            }
                        }
                        @endif
                    },
                    focus: function(event, ui) {
                        if (ui.item.qty_available <= 0) return false;
                    },
                    select: function(event, ui) {
                        var searched_term = $(this).val();
                        var is_overselling_allowed = $('input#is_overselling_allowed').length ? true : false;
                        var for_so = ($('#sale_type').length && $('#sale_type').val() == 'sales_order');
                        var is_draft = ($('input#status') && ($('input#status').val()=='quotation' || $('input#status').val()=='draft'));
                        if (ui.item.enable_stock != 1 || ui.item.qty_available > 0 || is_overselling_allowed || for_so || is_draft) {
                            $(this).val(null);
                            var purchase_line_id = ui.item.purchase_line_id && searched_term == ui.item.lot_number ? ui.item.purchase_line_id : null;
                            if (typeof pos_product_row === "function") {
                                pos_product_row(ui.item.variation_id, purchase_line_id);
                            }
                        } else {
                            if (typeof LANG !== 'undefined' && LANG.out_of_stock) {
                                toastr.error(LANG.out_of_stock);
                            } else {
                                alert("Out of stock");
                            }
                        }
                    }
                }).autocomplete('instance')._renderItem = function(ul, item) {
                    // Skip rendering if this is the auto_added marker from fallback
                    if (item.auto_added) {
                        return $('<li style="display:none;">').appendTo(ul);
                    }
                    
                    var is_overselling_allowed = $('input#is_overselling_allowed').length ? true : false;
                    var for_so = ($('#sale_type').length && $('#sale_type').val() == 'sales_order');
                    var is_draft = ($('input#status') && ($('input#status').val()=='quotation' || $('input#status').val()=='draft'));
                    
                    if (item.enable_stock == 1 && item.qty_available <= 0 && !is_overselling_allowed && !for_so && !is_draft) {
                        var string = '<li class="ui-state-disabled">' + item.name;
                        if (item.type == 'variable') string += '-' + item.variation;
                        var selling_price = item.selling_price;
                        string += ' (' + item.sub_sku + ')<br> Price: ' + selling_price + ' (Out of stock)</li>';
                        return $(string).appendTo(ul);
                    } else {
                        var string = '<div>' + item.name;
                        if (item.type == 'variable') string += '-' + item.variation;
                        var selling_price = item.selling_price;
                        string += ' (' + item.sub_sku + ')<br> Price: ' + selling_price;
                        if (item.enable_stock == 1) {
                            // Only use __currency_trans_from_en if defined
                            var qty_available = (typeof __currency_trans_from_en !== "undefined") ? 
                                __currency_trans_from_en(item.qty_available, false, false, __currency_precision, true) : item.qty_available;
                            string += ' - ' + qty_available + item.unit;
                        }
                        string += '</div>';
                        return $('<li>').append(string).appendTo(ul);
                    }
                };
                
                preloadFullProductCache(function(data){
                    console.log("Full product cache explicitly loaded via JerryUpdates Module (" + data.length + " items).");
                    updateRefreshBtnCount(data.length);
                });

                // Inject Refresh Products button into POS header
                if ($('#jerry-refresh-products-btn').length === 0) {
                    var btnHtml = '<button type="button" id="jerry-refresh-products-btn" class="tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white hover:tw-bg-white/60 tw-cursor-pointer tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-md md:tw-w-8 tw-w-auto tw-h-8 tw-text-gray-600 pull-right" title="Refresh Products">' +
                        '<strong class="!tw-m-3"><i class="fas fa-sync-alt fa-lg tw-text-[#009EE4] !tw-text-sm" id="jerry-refresh-icon"></i><span class="tw-inline md:tw-hidden"> Reload Products</span></strong>' +
                    '</button>';
                    if ($('#pos_header_more_options').length > 0) {
                        $('#pos_header_more_options').append(btnHtml);
                    } else {
                        $('body').append(btnHtml);
                    }
                }

                // Inject 'All Sales' button into POS bottom action bar (Upgrade Safe)
                // Clean up duplicates first, then inject into FIRST container only
                while ($('#jerry-all-sales-btn').length > 1) { $('#jerry-all-sales-btn').last().remove(); }
                if ($('#jerry-all-sales-btn').length === 0) {
                    var allSalesHtml = '<a href="/sells" id="jerry-all-sales-btn" class="tw-font-bold tw-text-gray-700 tw-text-xs md:tw-text-sm tw-flex tw-flex-col tw-items-center tw-justify-center tw-gap-1 no-print">' +
                        '<i class="fa fa-backward tw-fa-lg tw-text-[#009EE4] !tw-text-sm"></i> ' + (typeof LANG !== 'undefined' && LANG.all_sales ? LANG.all_sales : 'All Sales') +
                    '</a>';
                    $('.pos-form-actions .tw-overflow-x-auto').first().prepend(allSalesHtml);
                }

            }
        }, 1000); // Wait 1 second to ensure V12 pos.js finished binding first
    });



        // Refresh Products button click handler
        $(document).on('click', '#jerry-refresh-products-btn', function(e) {
            e.preventDefault();
            var $icon = $('#jerry-refresh-icon');
            var $text = $('#jerry-refresh-text');
            
            $icon.addClass('fa-spin');
            $text.text('...');
            
            clearFullProductCache();
            
            var req_loc_id = $('#location_id').val() || '';
            $.getJSON('/products/list', { not_for_selling: 0, location_id: req_loc_id }, function(data) {
                fullProductCache = { data: data, ts: Date.now() };
                saveFullProductCache();
                
                $icon.removeClass('fa-spin');
                $text.text('Refresh');
                toastr.success(data.length + " products loaded into speed cache!");
            }).fail(function() {
                $icon.removeClass('fa-spin');
                $text.text('Refresh');
                toastr.error("Failed to refresh products. Check your connection.");
            });
        });

    function updateRefreshBtnCount(count) {
        $('#jerry-product-count').text(count);
    }
}
</script>
@endif


<script type="text/javascript">
    // ============ POS-ONLY LAYOUT OVERRIDES (Independent of autocomplete) ============
    $(document).ready(function() {
        var posPath = window.location.pathname;
        if (posPath.indexOf('/pos/create') !== -1 || posPath.indexOf('/sells/create') !== -1 || $('form#add_pos_sell_form').length) {
            // Exclusively hide the TradeX Copyright footer on the POS screen
            if ($('style#jerry-hide-footer').length === 0) {
                $('<style id="jerry-hide-footer">.main-footer { display: none !important; }</style>').appendTo('head');
            }

            // Force ultimate stretching of the product table and stick totals to the bottom
            // Note: avoid literal @-media in blade templates, use variable concatenation
            var atMedia = String.fromCharCode(64) + 'media';
            if ($('style#jerry-pos-layout-stretch').length === 0) {
                $('<style id="jerry-pos-layout-stretch">' +
                    '.pos_product_div { min-height: calc(100vh - 370px) !important; max-height: calc(100vh - 370px) !important; overflow-y: auto !important; }' +
                    '.pos-form-actions .tw-overflow-x-auto { overflow-x: hidden !important; }' +
                    'body.pos-template { overflow-x: hidden !important; }' +
                    atMedia + ' (max-width: 768px) { .pos_product_div { min-height: 40vh !important; max-height: 50vh !important; } }' +
                '</style>').appendTo('head');
            }

            @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_cart_hide_images') == '1')
            if ($('style#jerry-pos-cart-hide-images').length === 0) {
                $('<style id="jerry-pos-cart-hide-images">' +
                    'table#pos_table img { display: none !important; }' +
                '</style>').appendTo('head');
            }
            @endif

            @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_list_hide_images') == '1')
            if ($('style#jerry-pos-list-hide-images').length === 0) {
                $('<style id="jerry-pos-list-hide-images">' +
                    '.product_list .image-container { display: none !important; }' +
                    '.product_list .text_div { width: 100% !important; }' +
                '</style>').appendTo('head');
            }
            @endif



            @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_hide_qty_buttons') == '1')
            if ($('style#jerry-pos-compact-row').length === 0) {
                $('<style id="jerry-pos-compact-row">' +
                    'table#pos_table > tbody > tr > td { padding: 3px 2px !important; vertical-align: middle !important; font-size: 13px; }' +
                    'table#pos_table input.pos_quantity, table#pos_table input.pos_unit_price, table#pos_table input.pos_line_total { height: 26px !important; padding: 2px 4px !important; }' +
                    'table#pos_table .text-muted { line-height: 1.1 !important; margin-bottom: 0px !important; display: inline-block; }' +
                    'table#pos_table .quantity-down, table#pos_table .quantity-up { display: none !important; }' +
                    'table#pos_table .input-group-btn { display: none !important; }' +
                    'table#pos_table .input-number { display: flex; justify-content: center; }' +
                '</style>').appendTo('head');
            }
            @endif

            @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_hide_unit_dropdown') == '1')
            if ($('style#jerry-pos-hide-unit').length === 0) {
                $('<style id="jerry-pos-hide-unit">' +
                    'table#pos_table select.sub_unit { display: none !important; }' +
                '</style>').appendTo('head');
            }
            @endif

            @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_list_show_sku') == '0')
            if ($('style#jerry-pos-hide-list-sku').length === 0) {
                $('<style id="jerry-pos-hide-list-sku">' +
                    '.product_list .text_div small:nth-of-type(2) { display: none !important; }' +
                '</style>').appendTo('head');
            }
            @endif

            @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_list_show_stock') == '0')
            if ($('style#jerry-pos-hide-list-stock').length === 0) {
                $('<style id="jerry-pos-hide-list-stock">' +
                    '.product_list .text_div small:nth-of-type(3) { display: none !important; }' +
                '</style>').appendTo('head');
            }
            @endif

            // Dynamic DOM Injector for Purchase Price (PP) 
            setInterval(function() {
                @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_list_show_pp') == '1')
                $('.product_list .product_box:not(.jerry-list-pp-injected)').each(function() {
                    var $box = $(this);
                    $box.addClass('jerry-list-pp-injected');
                    var vid = $box.data('variation_id');
                    if (window.fullProductCache && window.fullProductCache.data) {
                        var p = window.fullProductCache.data.find(function(item) { return item.variation_id == vid; });
                        var pp = (p && (p.dpp_inc_tax || p.purchase_price)) ? parseFloat(p.dpp_inc_tax || p.purchase_price).toFixed(2) : '0.00';
                        $box.find('.text_div').append('<small class="text-muted jerry-pp-badge" style="white-space:nowrap; display:block;">PP: ' + pp + '</small>');
                    }
                });
                @endif

                @if(\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_pos_cart_show_pp') == '1')
                $('#pos_table tbody tr.product_row:not(.jerry-cart-pp-injected)').each(function() {
                    var $row = $(this);
                    $row.addClass('jerry-cart-pp-injected');
                    var vid = $row.find('input.row_variation_id').val();
                    if (window.fullProductCache && window.fullProductCache.data) {
                        var p = window.fullProductCache.data.find(function(item) { return item.variation_id == vid; });
                        var pp = (p && (p.dpp_inc_tax || p.purchase_price)) ? parseFloat(p.dpp_inc_tax || p.purchase_price).toFixed(2) : '0.00';
                        $row.find('td:first').append('<br><small class="text-muted jerry-pp-badge" style="white-space:nowrap;">PP: ' + pp + '</small>');
                    }
                });
                @endif
            }, 800);

            }, 800);

            // Make the 'Discount' text label clickable to open the discount modal
            $('.pos_form_totals b:contains("Discount"), .pos_form_totals b:contains("discount")').css('cursor', 'pointer').on('click', function() {
                $('#posEditDiscountModal').modal('show');
            });

            console.log('[JerryUpdates] POS layout overrides injected successfully.');
        }
    });
</script>
