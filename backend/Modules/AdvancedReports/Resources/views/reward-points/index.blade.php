@extends('layouts.app')

@section('title', __('advancedreports::lang.reward_points_report'))

@section('content')
<section class="content-header">
    <h1>🎁 @lang('advancedreports::lang.reward_points_report')</h1>
    <p>@lang('advancedreports::lang.reward_points_description')</p>
</section>

<section class="content">
@if(isset($reward_points_not_enabled) && $reward_points_not_enabled)
    <div class="row">
        <div class="col-md-12">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-exclamation-triangle"></i>
                        @lang('advancedreports::lang.module_required')
                    </h3>
                </div>
                <div class="box-body">
                    <div class="alert alert-warning">
                        <h4><i class="icon fa fa-warning"></i> @lang('advancedreports::lang.alert')</h4>
                        @lang('advancedreports::lang.reward_points_not_enabled')
                    </div>

                    <div class="text-center">
                        <p class="lead">
                            <i class="fa fa-gift fa-3x text-warning"></i>
                        </p>
                        <h4>@lang('advancedreports::lang.module_required')</h4>
                        <p class="text-muted">
                            @lang('advancedreports::lang.enable_reward_points_instruction')
                        </p>

                        <div class="margin-top">
                            <a href="{{ url('/business/settings') }}" class="btn btn-primary btn-lg">
                                <i class="fa fa-cog"></i>
                                @lang('advancedreports::lang.go_to_settings')
                            </a>
                            <a href="{{ url('/home') }}" class="btn btn-default btn-lg">
                                <i class="fa fa-home"></i>
                                @lang('advancedreports::lang.go_to_home')
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <!-- Filters -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-filter"></i> @lang('report.filters')
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('report.date_range'):</label>
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </div>
                                    <input type="text" class="form-control" id="daterange-btn" readonly>
                                </div>
                                <input type="hidden" id="start_date" value="{{ date('Y-m-01') }}">
                                <input type="hidden" id="end_date" value="{{ date('Y-m-t') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('contact.customer'):</label>
                                <select class="form-control select2" id="customer_id" style="width: 100%;">
                                    <option value="" selected>@lang('advancedreports::lang.all_customers')</option>
                                    @foreach($customers as $customer_id => $customer_name)
                                    <option value="{{ $customer_id }}">{{ $customer_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.view_type'):</label>
                                <select class="form-control" id="view_type">
                                    <option value="customer_summary">@lang('advancedreports::lang.customer_summary')
                                    </option>
                                    <option value="transaction_details">
                                        @lang('advancedreports::lang.transaction_details')</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label><br>
                                <button type="button" class="btn btn-primary" id="apply_filters">
                                    <i class="fa fa-search"></i> @lang('advancedreports::lang.apply_filter')
                                </button>
                                <button type="button" class="btn btn-success" id="export_btn">
                                    <i class="fa fa-download"></i> @lang('advancedreports::lang.export')
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Widgets -->
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3 id="outstanding_liability">0</h3>
                    <p>@lang('advancedreports::lang.outstanding_liability_points')</p>
                </div>
                <div class="icon"><i class="fa fa-coins"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="liability_amount_bdt"><span class="display_currency" data-currency_symbol="true">0</span></h3>
                    <p>@lang('advancedreports::lang.liability_amount')</p>
                </div>
                <div class="icon"><i class="fa fa-money"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3 id="active_customers">0</h3>
                    <p>@lang('advancedreports::lang.active_customers_with_points')</p>
                </div>
                <div class="icon"><i class="fa fa-users"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="redemption_rate">0%</h3>
                    <p>@lang('advancedreports::lang.redemption_rate')</p>
                </div>
                <div class="icon"><i class="fa fa-percent"></i></div>
            </div>
        </div>
    </div>

    <!-- Additional Metrics -->
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-purple">
                <div class="inner">
                    <h3 id="total_points_issued">0</h3>
                    <p>@lang('advancedreports::lang.total_points_issued')</p>
                </div>
                <div class="icon"><i class="fa fa-gift"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-teal">
                <div class="inner">
                    <h3 id="total_points_redeemed">0</h3>
                    <p>@lang('advancedreports::lang.total_points_redeemed')</p>
                </div>
                <div class="icon"><i class="fa fa-shopping-cart"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-orange">
                <div class="inner">
                    <h3 id="points_redeemed_month">0</h3>
                    <p>@lang('advancedreports::lang.points_redeemed_this_month')</p>
                </div>
                <div class="icon"><i class="fa fa-calendar"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-maroon">
                <div class="inner">
                    <h3 id="avg_points_customer">0</h3>
                    <p>@lang('advancedreports::lang.avg_points_per_customer')</p>
                </div>
                <div class="icon"><i class="fa fa-calculator"></i></div>
            </div>
        </div>
    </div>

    <!-- Top Performers -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">🏆 Top Point Earners</h3>
                </div>
                <div class="box-body">
                    <div id="top_earners_list">
                        <p class="text-muted text-center">Loading...</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">🛒 Top Point Redeemers</h3>
                </div>
                <div class="box-body">
                    <div id="top_redeemers_list">
                        <p class="text-muted text-center">Loading...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Data Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title" id="table_title">
                        <i class="fa fa-table"></i> @lang('advancedreports::lang.customer_points_summary')
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-info btn-sm" id="refresh_data">
                            <i class="fa fa-refresh"></i> @lang('advancedreports::lang.refresh')
                        </button>
                        <button type="button" class="btn btn-success btn-sm" id="export_table">
                            <i class="fa fa-download"></i> @lang('advancedreports::lang.export_table')
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" id="print_table">
                            <i class="fa fa-print"></i> @lang('messages.print')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <!-- Customer Summary Table -->
                        <table class="table table-bordered table-striped" id="customer_summary_table"
                            style="display: none;">
                            <thead>
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Mobile</th>
                                    <th>Points Earned</th>
                                    <th>Points Redeemed</th>
                                    <th>Current Balance</th>
                                    <th>Liability</th>
                                    <th>Total Transactions</th>
                                    <th>Redemption Count</th>
                                    <th>Last Activity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>

                        <!-- Transaction Details Table -->
                        <table class="table table-bordered table-striped" id="transaction_details_table"
                            style="display: none;">
                            <thead>
                                <tr>
                                    <th>Invoice No</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Invoice Amount</th>
                                    <th>Points Earned</th>
                                    <th>Points Redeemed</th>
                                    <th>Points Value</th>
                                    <th>Final Payable</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="overlay" id="loading_overlay" style="display: none;">
                    <i class="fa fa-refresh fa-spin"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    /* Status badges */
    .status-active {
        background-color: #00a65a;
        color: white;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 11px;
    }

    .status-moderate {
        background-color: #f39c12;
        color: white;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 11px;
    }

    .status-inactive {
        background-color: #dd4b39;
        color: white;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 11px;
    }

    /* Transaction type badges */
    .type-earned {
        background-color: #00c0ef;
        color: white;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 10px;
    }

    .type-redeemed {
        background-color: #f39c12;
        color: white;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 10px;
    }

    .type-both {
        background-color: #00a65a;
        color: white;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 10px;
    }

    /* Top performers styling */
    .performer-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .performer-item:last-child {
        border-bottom: none;
    }

    .performer-name {
        font-weight: 500;
        color: #333;
    }

    .performer-points {
        font-weight: bold;
        color: #3c8dbc;
    }

    .performer-rank {
        background-color: #3c8dbc;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        margin-right: 10px;
    }

    /* Loading overlay */
    .overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
    }

    /* Widget value size adjustments */
    .small-box h3 {
        font-size: 28px !important;
        font-weight: bold;
        margin: 0;
        line-height: 1.2;
    }

    /* Responsive improvements */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 12px;
        }

        .small-box h3 {
            font-size: 20px !important;
        }
    }
</style>

@endsection

@section('javascript')
<script>
    $(document).ready(function() {
    let customerSummaryTable, transactionDetailsTable;
    let currentViewType = 'customer_summary';

    // Currency settings
    const currencySettings = {
        symbol: '{{ session("currency")["symbol"] ?? "$" }}',
        precision: {{ session('business.currency_precision') ?? 2 }},
        placement: '{{ session("business.currency_symbol_placement") ?? "before" }}'
    };

    // Initialize components
    initializeDateRangePicker();
    initializeSelect2();
    initializeDataTables();

    // Load initial data
    loadSummaryData();
    loadTableData();
    loadTopPerformers();
    
    // Event handlers
    $('#apply_filters').click(function() {
        loadSummaryData();
        loadTableData();
        loadTopPerformers();
    });
    
    $('#view_type').change(function() {
        currentViewType = $(this).val();
        updateTableTitle();
        switchTableView();
        loadTableData();
    });
    
    $('#refresh_data').click(function() {
        loadSummaryData();
        loadTableData();
        loadTopPerformers();
    });
    
    $('#export_btn, #export_table').click(function() {
        exportData();
    });
    
    $('#print_table').click(function() {
        printTable();
    });
    
    function initializeDateRangePicker() {
        $('#daterange-btn').daterangepicker({
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            startDate: moment().startOf('month'),
            endDate: moment().endOf('month'),
            format: 'YYYY-MM-DD'
        }, function(start, end) {
            $('#daterange-btn').val(start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
            $('#start_date').val(start.format('YYYY-MM-DD'));
            $('#end_date').val(end.format('YYYY-MM-DD'));
        });
        
        // Set initial values
        $('#daterange-btn').val(moment().startOf('month').format('YYYY-MM-DD') + ' to ' + moment().endOf('month').format('YYYY-MM-DD'));
    }
    
    function initializeSelect2() {
        $('#customer_id').select2({
            placeholder: "@lang('advancedreports::lang.all_customers')",
            allowClear: true,
            minimumInputLength: 1, // Require at least 1 character for search
            language: {
                inputTooShort: function() {
                    return "@lang('advancedreports::lang.type_to_search')";
                },
                searching: function() {
                    return "@lang('advancedreports::lang.searching')...";
                },
                noResults: function() {
                    return "@lang('advancedreports::lang.no_result')";
                }
            },
            escapeMarkup: function(markup) {
                return markup;
            }
        });
    }
    
    function initializeDataTables() {
        // Customer Summary DataTable
        customerSummaryTable = $('#customer_summary_table').DataTable({
            processing: true,
            ordering: true,
            searching: true,
            paging: true,
            info: true,
            responsive: true,
            pageLength: 25,
            order: [[4, 'desc']], // Order by current balance
            columnDefs: [
                {
                    targets: [2, 3, 4, 6, 7], // Numeric columns
                    className: 'text-right'
                },
                {
                    targets: [5], // Liability amount
                    className: 'text-right'
                }
            ],
            language: {
                processing: "Loading reward points data...",
                emptyTable: "No reward points data available",
                zeroRecords: "No matching records found"
            }
        });
        
        // Transaction Details DataTable
        transactionDetailsTable = $('#transaction_details_table').DataTable({
            processing: true,
            ordering: true,
            searching: true,
            paging: true,
            info: true,
            responsive: true,
            pageLength: 25,
            order: [[1, 'desc']], // Order by date
            columnDefs: [
                {
                    targets: [3, 4, 5, 6, 7], // Numeric columns
                    className: 'text-right'
                }
            ],
            language: {
                processing: "Loading transaction details...",
                emptyTable: "No transaction data available",
                zeroRecords: "No matching transactions found"
            }
        });
    }
    
    function updateTableTitle() {
        const titles = {
            'customer_summary': '<i class="fa fa-users"></i> Customer Points Summary',
            'transaction_details': '<i class="fa fa-list"></i> Transaction Details'
        };
        $('#table_title').html(titles[currentViewType]);
    }
    
    function switchTableView() {
        if (currentViewType === 'customer_summary') {
            $('#customer_summary_table').show();
            $('#transaction_details_table').hide();
        } else {
            $('#customer_summary_table').hide();
            $('#transaction_details_table').show();
        }
    }
    
    function loadSummaryData() {
        const params = getFilterParams();
        
        $.ajax({
            url: '{{ route("advancedreports.reward-points.summary") }}',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(response) {
                updateSummaryWidgets(response);
            },
            error: function(xhr, status, error) {
                console.error('Failed to load summary data:', error);
                toastr.error('Failed to load summary data');
            }
        });
    }
    
    function updateSummaryWidgets(data) {
        $('#outstanding_liability').text(formatNumber(data.outstanding_liability));
        $('#liability_amount_bdt').html('<span class="display_currency" data-currency_symbol="true">' + (parseFloat(data.liability_amount || 0).toFixed(currencySettings.precision)) + '</span>');
        $('#active_customers').text(formatNumber(data.active_customers_with_points));
        $('#redemption_rate').text(formatNumber(data.redemption_rate) + '%');
        $('#total_points_issued').text(formatNumber(data.total_points_issued));
        $('#total_points_redeemed').text(formatNumber(data.total_points_redeemed));
        $('#points_redeemed_month').text(formatNumber(data.points_redeemed_this_month));
        $('#avg_points_customer').text(formatNumber(data.avg_points_per_customer));

        // Apply currency conversion
        __currency_convert_recursively($('.small-box'));
    }
    
    function loadTableData() {
        showLoading(true);
        const params = getFilterParams();
        
        let url = currentViewType === 'customer_summary' 
            ? '{{ route("advancedreports.reward-points.customer-summary") }}'
            : '{{ route("advancedreports.reward-points.transaction-details") }}';
        
        $.ajax({
            url: url,
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(response) {
                if (currentViewType === 'customer_summary') {
                    populateCustomerSummaryTable(response.data);
                } else {
                    populateTransactionDetailsTable(response.data);
                }
                showLoading(false);
            },
            error: function(xhr, status, error) {
                console.error('Failed to load table data:', error);
                toastr.error('Failed to load data');
                showLoading(false);
            }
        });
    }
    
    function populateCustomerSummaryTable(data) {
        customerSummaryTable.clear();

        data.forEach(function(row) {
            const statusBadge = `<span class="status-${row.status}">${row.status.toUpperCase()}</span>`;

            customerSummaryTable.row.add([
                row.customer_name,
                row.customer_mobile || '-',
                formatNumber(row.total_earned_points),
                formatNumber(row.total_redeemed_points),
                formatNumber(row.current_balance),
                formatCurrency(row.liability_amount || 0),
                formatNumber(row.total_transactions),
                formatNumber(row.redemption_transactions),
                row.last_activity_date || '-',
                statusBadge
            ]);
        });

        customerSummaryTable.draw();
    }
    
    function populateTransactionDetailsTable(data) {
        transactionDetailsTable.clear();

        data.forEach(function(row) {
            const typeBadge = `<span class="type-${row.transaction_type}">${row.transaction_type.toUpperCase()}</span>`;

            transactionDetailsTable.row.add([
                row.invoice_no,
                row.transaction_date,
                row.customer_name,
                formatCurrency(row.invoice_amount || 0),
                formatNumber(row.points_earned),
                formatNumber(row.points_redeemed),
                formatCurrency(row.points_value_redeemed || 0),
                formatCurrency(row.final_payable || 0),
                typeBadge
            ]);
        });

        transactionDetailsTable.draw();
    }
    
    function loadTopPerformers() {
        const params = getFilterParams();
        
        $.ajax({
            url: '{{ route("advancedreports.reward-points.top-performers") }}',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(response) {
                updateTopPerformers(response);
            },
            error: function(xhr, status, error) {
                console.error('Failed to load top performers:', error);
            }
        });
    }
    
    function updateTopPerformers(data) {
        // Top Earners
        let earnersHtml = '';
        if (data.top_earners && data.top_earners.length > 0) {
            data.top_earners.forEach(function(customer, index) {
                earnersHtml += `
                    <div class="performer-item">
                        <div style="display: flex; align-items: center;">
                            <div class="performer-rank">${index + 1}</div>
                            <div>
                                <div class="performer-name">${customer.customer_name}</div>
                                <small class="text-muted">${formatNumber(customer.transaction_count)} transactions</small>
                            </div>
                        </div>
                        <div class="performer-points">${formatNumber(customer.total_earned_points)} pts</div>
                    </div>
                `;
            });
        } else {
            earnersHtml = '<p class="text-muted text-center">@lang("advancedreports::lang.no_data_available")</p>';
        }
        $('#top_earners_list').html(earnersHtml);
        
        // Top Redeemers
        let redeemersHtml = '';
        if (data.top_redeemers && data.top_redeemers.length > 0) {
            data.top_redeemers.forEach(function(customer, index) {
                redeemersHtml += `
                    <div class="performer-item">
                        <div style="display: flex; align-items: center;">
                            <div class="performer-rank">${index + 1}</div>
                            <div>
                                <div class="performer-name">${customer.customer_name}</div>
                                <small class="text-muted">${formatNumber(customer.redemption_count)} redemptions</small>
                            </div>
                        </div>
                        <div class="performer-points">${formatNumber(customer.total_redeemed_points)} pts</div>
                    </div>
                `;
            });
        } else {
            redeemersHtml = '<p class="text-muted text-center">@lang("advancedreports::lang.no_data_available")</p>';
        }
        $('#top_redeemers_list').html(redeemersHtml);
    }
    
    function getFilterParams() {
        return {
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            customer_id: $('#customer_id').val()
        };
    }
    
    function showLoading(show) {
        if (show) {
            $('#loading_overlay').show();
        } else {
            $('#loading_overlay').hide();
        }
    }
    
    function formatNumber(num) {
        return parseFloat(num || 0).toLocaleString(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

    function formatCurrency(amount) {
        if (typeof __currency_trans_from_en === 'function') {
            return __currency_trans_from_en(parseFloat(amount || 0).toFixed(currencySettings.precision), true);
        }

        // Fallback formatting
        const formatted = parseFloat(amount || 0).toLocaleString(undefined, {
            minimumFractionDigits: currencySettings.precision,
            maximumFractionDigits: currencySettings.precision
        });
        return currencySettings.placement === 'after' ?
            formatted + currencySettings.symbol :
            currencySettings.symbol + formatted;
    }
    
    function exportData() {
        const params = getFilterParams();
        params.export_type = currentViewType;
        params._token = '{{ csrf_token() }}';

        showLoading(true);

        // Use AJAX to download the file properly
        $.ajax({
            url: '{{ route("advancedreports.reward-points.export") }}',
            type: 'POST',
            data: params,
            xhrFields: {
                responseType: 'blob'
            },
            success: function(data, status, xhr) {
                // Create blob link to download
                var blob = new Blob([data], {
                    type: xhr.getResponseHeader('Content-Type') || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                });

                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);

                // Get filename from response header or use default
                var filename = 'reward-points-report.xlsx';
                var disposition = xhr.getResponseHeader('Content-Disposition');
                if (disposition && disposition.indexOf('attachment') !== -1) {
                    var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                    var matches = filenameRegex.exec(disposition);
                    if (matches != null && matches[1]) {
                        filename = matches[1].replace(/['"]/g, '');
                    }
                }
                link.download = filename;

                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // Clean up
                window.URL.revokeObjectURL(link.href);
            },
            error: function(xhr, status, error) {
                alert('Export failed: ' + error);
            },
            complete: function() {
                setTimeout(function() {
                    showLoading(false);
                }, 1000);
            }
        });
    }
    
    function printTable() {
        const currentTable = currentViewType === 'customer_summary' 
            ? $('#customer_summary_table')[0] 
            : $('#transaction_details_table')[0];
            
        const printWindow = window.open('', '_blank');
        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Reward Points Report</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    table { border-collapse: collapse; width: 100%; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    .status-active { background-color: #00a65a; color: white; padding: 2px 6px; border-radius: 3px; }
                    .status-moderate { background-color: #f39c12; color: white; padding: 2px 6px; border-radius: 3px; }
                    .status-inactive { background-color: #dd4b39; color: white; padding: 2px 6px; border-radius: 3px; }
                    .type-earned { background-color: #00c0ef; color: white; padding: 2px 6px; border-radius: 3px; }
                    .type-redeemed { background-color: #f39c12; color: white; padding: 2px 6px; border-radius: 3px; }
                    .type-both { background-color: #00a65a; color: white; padding: 2px 6px; border-radius: 3px; }
                    @media print {
                        body { -webkit-print-color-adjust: exact; }
                    }
                </style>
            </head>
            <body>
                <h1>Reward Points Report</h1>
                <h3>${currentViewType === 'customer_summary' ? 'Customer Summary' : 'Transaction Details'}</h3>
                <p>Period: ${$('#start_date').val()} to ${$('#end_date').val()}</p>
                <p>Generated on: ${new Date().toLocaleString()}</p>
                ${currentTable.outerHTML}
            </body>
            </html>
        `;
        
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 250);
    }
});
</script>
@endif
@endsection