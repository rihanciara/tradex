@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.operations_summary_report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('advancedreports::lang.operations_summary_report')
        <small>@lang('advancedreports::lang.manage_operations_summary')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', [
            'title' => __('report.filters'),
            'class' => 'box-primary'
            ])
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'location_filter']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' =>
                    'form-control', 'id' => 'date_range_filter', 'readonly']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <br>
                    <button type="button" class="btn btn-primary" id="filter_btn">
                        <i class="fa fa-filter"></i> @lang('advancedreports::lang.filter')
                    </button>
                    <button type="button" class="btn btn-success" id="export_btn">
                        <i class="fa fa-download"></i> @lang('lang_v1.export')
                    </button>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Dashboard Summary Cards -->
    <div class="row" id="dashboard_summary">
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3 id="today_sales">0</h3>
                    <p>@lang('advancedreports::lang.today_sales')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-shopping-cart"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="today_purchases">0</h3>
                    <p>@lang('advancedreports::lang.today_purchases')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-truck"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3 id="today_expenses">0</h3>
                    <p>@lang('advancedreports::lang.today_expenses')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-credit-card"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="net_profit">0</h3>
                    <p>@lang('advancedreports::lang.net_profit')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-line-chart"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-orange">
                <div class="inner">
                    <h3 id="cash_in_hand">0</h3>
                    <p>@lang('advancedreports::lang.cash_in_hand')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-money"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-purple">
                <div class="inner">
                    <h3 id="total_transactions">0</h3>
                    <p>@lang('advancedreports::lang.total_transactions')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-list"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Summary Content -->
    <div class="row">
        <!-- Sales Summary -->
        <div class="col-md-4">
            @component('components.widget', ['class' => 'box-success', 'title' =>
            __('advancedreports::lang.sales_summary')])
            <div id="sales_summary_content">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-3x text-muted"></i>
                    <p class="text-muted">@lang('lang_v1.loading')</p>
                </div>
            </div>
            @endcomponent
        </div>

        <!-- Purchase Summary -->
        <div class="col-md-4">
            @component('components.widget', ['class' => 'box-info', 'title' =>
            __('advancedreports::lang.purchase_summary')])
            <div id="purchase_summary_content">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-3x text-muted"></i>
                    <p class="text-muted">@lang('lang_v1.loading')</p>
                </div>
            </div>
            @endcomponent
        </div>

        <!-- Expense Summary -->
        <div class="col-md-4">
            @component('components.widget', ['class' => 'box-warning', 'title' =>
            __('advancedreports::lang.expense_summary')])
            <div id="expense_summary_content">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-3x text-muted"></i>
                    <p class="text-muted">@lang('lang_v1.loading')</p>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Payment Methods Summary -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' =>
            __('advancedreports::lang.payment_methods_summary')])
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="payment_methods_table">
                    <thead>
                        <tr>
                            <th>@lang('advancedreports::lang.payment_method')</th>
                            <th>@lang('advancedreports::lang.transaction_count')</th>
                            <th>@lang('advancedreports::lang.total_amount')</th>
                        </tr>
                    </thead>
                    <tbody id="payment_methods_body">
                        <tr>
                            <td colspan="3" class="text-center">
                                <i class="fa fa-spinner fa-spin"></i> @lang('lang_v1.loading')
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray font-17">
                            <td><strong>@lang('sale.total')</strong></td>
                            <td id="total_payment_count"><strong>0</strong></td>
                            <td id="total_payment_amount"><strong><span class="display_currency"
                                        data-currency_symbol="true">0</span></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endcomponent
        </div>
    </div>

</section>
<!-- /.content -->

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
    // Initialize date range picker with today as default
    var today = moment();
    var todayFormatted = today.format(moment_date_format);
    $('#date_range_filter').val(todayFormatted + ' ~ ' + todayFormatted);

    $('#date_range_filter').daterangepicker(
        {
            ...dateRangeSettings,
            startDate: today,
            endDate: today,
            locale: dateRangeSettings.locale
        },
        function (start, end) {
            $('#date_range_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
        }
    );

    $('#date_range_filter').on('cancel.daterangepicker', function(ev, picker) {
        $('#date_range_filter').val(todayFormatted + ' ~ ' + todayFormatted);
    });

    // Filter button click
    $('#filter_btn').click(function() {
        console.log('Filter button clicked'); // Debug log
        loadAllData();
    });

    // Export button click
    $('#export_btn').click(function(e) {
        e.preventDefault();
        
        var location_id = $('#location_filter').val();
        var start_date = '';
        var end_date = '';
        
        if($('#date_range_filter').val()){
            start_date = $('input#date_range_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            end_date = $('input#date_range_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }
        
        var $btn = $(this);
        var originalText = $btn.html();
        
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Exporting...').prop('disabled', true);
        
        var url = "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\OperationsSummaryReportController@export') !!}";
        url += '?location_id=' + location_id + '&start_date=' + start_date + '&end_date=' + end_date;
        
        var iframe = $('<iframe>').hide().appendTo('body');
        iframe.attr('src', url);
        
        setTimeout(function() {
            $btn.html(originalText).prop('disabled', false);
            iframe.remove();
        }, 30000);
        
        iframe.on('load', function() {
            setTimeout(function() {
                $btn.html(originalText).prop('disabled', false);
                iframe.remove();
            }, 3000);
        });
    });

    // Load all data - FIXED VERSION
    function loadAllData() {
        console.log('Loading all data...'); // Debug log
        loadDashboardSummary(); // This should also respect date filter
        loadMainSummary();
    }

    // Load dashboard summary - UPDATED TO RESPECT DATE FILTER
    function loadDashboardSummary() {
        var location_id = $('#location_filter').val();
        var start_date = moment().format('YYYY-MM-DD');
        var end_date = moment().format('YYYY-MM-DD');

        // Get the selected date range
        if($('#date_range_filter').val() && $('#date_range_filter').data('daterangepicker')){
            start_date = $('#date_range_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            end_date = $('#date_range_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }

        console.log('Dashboard Summary - Location:', location_id, 'Start:', start_date, 'End:', end_date); // Debug log
        
        $.ajax({
            url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\OperationsSummaryReportController@getDashboardSummary') !!}",
            data: { 
                location_id: location_id,
                start_date: start_date,
                end_date: end_date 
            },
            dataType: 'json',
            success: function(data) {
                console.log('Dashboard data received:', data); // Debug log
                
                if (data.error) {
                    console.error('Dashboard Error:', data.error);
                    return;
                }
                
                // Update widgets with better error handling
                try {
                    $('#today_sales').text(formatCurrency(data.today_sales || 0));
                    $('#today_purchases').text(formatCurrency(data.today_purchases || 0));
                    $('#today_expenses').text(formatCurrency(data.today_expenses || 0));
                    $('#net_profit').text(formatCurrency(data.net_profit || 0));
                    $('#cash_in_hand').text(formatCurrency(data.cash_in_hand || 0));
                    $('#total_transactions').text(data.total_transactions || 0);
                    
                    console.log('Widgets updated successfully'); // Debug log
                } catch (e) {
                    console.error('Error updating widgets:', e);
                    
                    // Fallback without currency formatting
                    $('#today_sales').text(data.today_sales || 0);
                    $('#today_purchases').text(data.today_purchases || 0);
                    $('#today_expenses').text(data.today_expenses || 0);
                    $('#net_profit').text(data.net_profit || 0);
                    $('#cash_in_hand').text(data.cash_in_hand || 0);
                    $('#total_transactions').text(data.total_transactions || 0);
                }
            },
            error: function(xhr, status, error) {
                console.error('Dashboard Summary AJAX Error:', xhr.responseText);
                console.error('Status:', status);
                console.error('Error:', error);
            }
        });
    }

    // Load main summary data - FIXED VERSION
    function loadMainSummary() {
        var location_id = $('#location_filter').val();
        var dateRangeValue = $('#date_range_filter').val();

        // Default to today if no date range set
        if (!dateRangeValue) {
            var todayFormatted = moment().format(moment_date_format);
            dateRangeValue = todayFormatted + ' ~ ' + todayFormatted;
        }

        console.log('Main Summary - Location:', location_id, 'Date Range:', dateRangeValue); // Debug log
        
        $.ajax({
            url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\OperationsSummaryReportController@getSummaryData') !!}",
            data: {
                location_id: location_id,
                date_range: dateRangeValue
            },
            dataType: 'json',
            success: function(data) {
                console.log('Main summary data received:', data); // Debug log
                
                if (data.error) {
                    var errorMsg = '<p class="text-danger">Error: ' + data.error + '</p>';
                    $('#sales_summary_content').html(errorMsg);
                    $('#purchase_summary_content').html(errorMsg);
                    $('#expense_summary_content').html(errorMsg);
                    console.error('Summary Data Error:', data.error);
                    return;
                }

                // Update sales summary with detailed discount breakdown
                var salesHtml = '';
                if (data.sell_details) {
                    salesHtml = '<table class="table table-condensed">' +
                        '<tr><td>Total Sales (Inc. Tax):</td><td class="text-right">' + formatCurrency(data.sell_details.total_sell_inc_tax || 0) + '</td></tr>' +
                        '<tr><td>Total Sales (Exc. Tax):</td><td class="text-right">' + formatCurrency(data.sell_details.total_sell_exc_tax || 0) + '</td></tr>' +
                        '<tr><td>Total Tax:</td><td class="text-right">' + formatCurrency(data.sell_details.total_tax || 0) + '</td></tr>' +
                        '<tr><td><strong>Discount Breakdown:</strong></td><td></td></tr>' +
                        '<tr><td>&nbsp;&nbsp;• Line Discount:</td><td class="text-right">' + formatCurrency(data.sell_details.line_discount || 0) + '</td></tr>' +
                        '<tr><td>&nbsp;&nbsp;• Invoice Discount:</td><td class="text-right">' + formatCurrency(data.sell_details.invoice_discount || 0) + '</td></tr>' +
                        '<tr class="info"><td><strong>&nbsp;&nbsp;• Total Discount:</strong></td><td class="text-right"><strong>' + formatCurrency(data.sell_details.total_discount || 0) + '</strong></td></tr>' +
                        '<tr><td>Invoice Due:</td><td class="text-right">' + formatCurrency(data.sell_details.invoice_due || 0) + '</td></tr>' +
                        '</table>';
                } else {
                    salesHtml = '<p class="text-muted">No sales data available</p>';
                }
                $('#sales_summary_content').html(salesHtml);

                // Update purchase summary
                var purchaseHtml = '';
                if (data.purchase_details) {
                    purchaseHtml = '<table class="table table-condensed">' +
                        '<tr><td>Total Purchases (Inc. Tax):</td><td class="text-right">' + formatCurrency(data.purchase_details.total_purchase_inc_tax || 0) + '</td></tr>' +
                        '<tr><td>Total Purchases (Exc. Tax):</td><td class="text-right">' + formatCurrency(data.purchase_details.total_purchase_exc_tax || 0) + '</td></tr>' +
                        '<tr><td>Purchase Due:</td><td class="text-right">' + formatCurrency(data.purchase_details.purchase_due || 0) + '</td></tr>' +
                        '</table>';
                } else {
                    purchaseHtml = '<p class="text-muted">No purchase data available</p>';
                }
                $('#purchase_summary_content').html(purchaseHtml);

                // Update expense summary
                var expenseHtml = '';
                if (data.expense_details && data.expense_details.expenses && data.expense_details.expenses.length > 0) {
                    expenseHtml = '<table class="table table-condensed">';
                    $.each(data.expense_details.expenses, function(index, expense) {
                        expenseHtml += '<tr><td>' + (expense.category || 'Others') + ':</td><td class="text-right">' + formatCurrency(expense.total_expense || 0) + '</td></tr>';
                    });
                    expenseHtml += '<tr class="info"><td><strong>Total:</strong></td><td class="text-right"><strong>' + formatCurrency(data.expense_details.total_expense || 0) + '</strong></td></tr>';
                    expenseHtml += '</table>';
                } else {
                    expenseHtml = '<p class="text-muted">No expense data available</p>';
                }
                $('#expense_summary_content').html(expenseHtml);

                // Update payment methods
                updatePaymentMethods(data.payment_methods);
            },
            error: function(xhr, status, error) {
                var errorMsg = '<p class="text-danger">Error loading data. Please check console for details.</p>';
                $('#sales_summary_content').html(errorMsg);
                $('#purchase_summary_content').html(errorMsg);
                $('#expense_summary_content').html(errorMsg);
                
                console.error('Main Summary AJAX Error:', xhr.responseText);
                console.error('Status:', status);
                console.error('Error:', error);
            }
        });
    }

    // Update payment methods table
    function updatePaymentMethods(paymentMethods) {
        var paymentMethodsHtml = '';
        var totalCount = 0;
        var totalAmount = 0;

        if (paymentMethods && paymentMethods.length > 0) {
            $.each(paymentMethods, function(index, method) {
                var count = parseInt(method.transaction_count) || 0;
                var amount = parseFloat(method.total_amount) || 0;
                
                totalCount += count;
                totalAmount += amount;
                
                paymentMethodsHtml += '<tr>' +
                    '<td>' + (method.method || 'Unknown') + '</td>' +
                    '<td class="text-center">' + count + '</td>' +
                    '<td class="text-right">' + formatCurrency(amount) + '</td>' +
                    '</tr>';
            });
        } else {
            paymentMethodsHtml = '<tr><td colspan="3" class="text-center text-muted">No payment data available for selected period</td></tr>';
        }
        
        $('#payment_methods_body').html(paymentMethodsHtml);
        $('#total_payment_count').html('<strong>' + totalCount + '</strong>');
        $('#total_payment_amount').html('<strong>' + formatCurrency(totalAmount) + '</strong>');
        
        // Apply currency formatting
        __currency_convert_recursively($('#payment_methods_table'));
    }

    // Helper function for currency formatting
    function formatCurrency(amount) {
        try {
            if (typeof __currency_trans_from_en === 'function') {
                return __currency_trans_from_en(amount, true);
            } else {
                return parseFloat(amount).toFixed(2);
            }
        } catch (e) {
            console.warn('Currency formatting failed:', e);
            return parseFloat(amount).toFixed(2);
        }
    }

    // Load initial data after a slight delay to ensure daterangepicker is ready
    setTimeout(function() {
        loadAllData();
    }, 100);
});
</script>

<style>
    /* Use the same widget styling as other reports */
    .small-box {
        min-height: 130px !important;
        height: 130px !important;
        display: flex !important;
        flex-direction: column !important;
        position: relative !important;
        margin-bottom: 20px !important;
        border-radius: 8px !important;
        overflow: hidden !important;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1) !important;
        transition: all 0.2s ease !important;
    }

    .small-box .inner {
        padding: 15px !important;
        flex-grow: 1 !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        position: relative !important;
        z-index: 2 !important;
    }

    .small-box .inner h3 {
        font-size: 28px !important;
        font-weight: 600 !important;
        margin: 0 0 8px 0 !important;
        line-height: 1 !important;
        color: #ffffff !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    .small-box .inner p {
        font-size: 13px !important;
        margin: 0 !important;
        line-height: 1.2 !important;
        color: rgba(255, 255, 255, 0.9) !important;
        font-weight: 400 !important;
    }

    .small-box .icon {
        position: absolute !important;
        top: 15px !important;
        right: 15px !important;
        z-index: 1 !important;
        font-size: 40px !important;
        color: rgba(255, 255, 255, 0.2) !important;
    }

    .small-box:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15) !important;
    }

    /* Colors */
    .small-box.bg-aqua {
        background-color: #3498db !important;
    }

    .small-box.bg-green {
        background-color: #2ecc71 !important;
    }

    .small-box.bg-yellow {
        background-color: #f39c12 !important;
    }

    .small-box.bg-red {
        background-color: #e74c3c !important;
    }

    .small-box.bg-orange {
        background-color: #e67e22 !important;
    }

    .small-box.bg-purple {
        background-color: #9b59b6 !important;
    }

    /* Hover effects */
    .small-box:hover.bg-aqua {
        background-color: #2980b9 !important;
    }

    .small-box:hover.bg-green {
        background-color: #27ae60 !important;
    }

    .small-box:hover.bg-yellow {
        background-color: #d68910 !important;
    }

    .small-box:hover.bg-red {
        background-color: #c0392b !important;
    }

    .small-box:hover.bg-orange {
        background-color: #d35400 !important;
    }

    .small-box:hover.bg-purple {
        background-color: #8e44ad !important;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
        .small-box {
            min-height: 120px !important;
            height: 120px !important;
        }

        .small-box .inner h3 {
            font-size: 26px !important;
        }

        .small-box .inner p {
            font-size: 12px !important;
        }

        .small-box .icon {
            font-size: 35px !important;
        }
    }

    @media (max-width: 768px) {
        .small-box {
            min-height: 110px !important;
            height: 110px !important;
            margin-bottom: 15px !important;
        }

        .small-box .inner {
            padding: 12px !important;
        }

        .small-box .inner h3 {
            font-size: 24px !important;
        }

        .small-box .inner p {
            font-size: 11px !important;
        }

        .small-box .icon {
            font-size: 30px !important;
            top: 12px !important;
            right: 12px !important;
        }
    }

    /* Row spacing */
    #dashboard_summary {
        margin-bottom: 25px !important;
        padding: 0 !important;
    }

    #dashboard_summary .row {
        margin-left: -10px !important;
        margin-right: -10px !important;
    }

    #dashboard_summary [class*="col-"] {
        padding-left: 10px !important;
        padding-right: 10px !important;
    }

    /* Discount breakdown styling */
    .table td {
        border-top: 1px solid #f0f0f0 !important;
        padding: 6px 8px !important;
    }

    .table .info td {
        background-color: #d9edf7 !important;
        border-color: #bce8f1 !important;
    }

    .table td strong {
        font-weight: 600 !important;
    }
</style>
@endsection