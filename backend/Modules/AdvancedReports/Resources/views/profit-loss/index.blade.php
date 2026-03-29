@extends('advancedreports::layouts.app')
@php
// Get currency settings from Ultimate POS session
$currency_symbol = session('currency')['symbol'] ?? '';
$currency_precision = session('business.currency_precision') ?: 2;
$currency_symbol_placement = session('business.currency_symbol_placement') ?: 'before';
$thousand_separator = session('business.thousand_separator') ?: ',';
$decimal_separator = session('business.decimal_separator') ?: '.';
@endphp
@section('title', __('advancedreports::lang.profit_loss_analysis'))

@section('content')

<!-- Content Header -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ __('advancedreports::lang.profit_loss_analysis')}}
        <small class="text-muted">@lang('advancedreports::lang.comprehensive_profit_loss_description')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">

    <!-- Enhanced Filters Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-filter"></i> @lang('advancedreports::lang.filters_controls')
                    </h3>
                </div>
                <div class="box-body">
                    <!-- Primary Filters Row -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('period_type_filter', __('advancedreports::lang.period_type') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                    {!! Form::select('period_type', $period_options, 'this_month', ['class' =>
                                    'form-control', 'id' => 'period_type_filter']) !!}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('date_range_filter', __('advancedreports::lang.custom_date_range') .
                                ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-calendar-alt"></i></span>
                                    <input type="text" class="form-control" id="date_range_filter"
                                        placeholder="Select custom date range">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('location_filter', __('business.location') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-map-marker"></i></span>
                                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control
                                    select2', 'id' => 'location_filter']) !!}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('customer_filter', __('Customer') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                    {!! Form::select('customer_id', $customers, null, ['class' => 'form-control
                                    select2', 'id' => 'customer_filter', 'placeholder' => 'All Customers']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons Row -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <button type="button" class="btn btn-primary" id="refresh_data">
                                    <i class="fa fa-refresh"></i> @lang('advancedreports::lang.refresh_data')
                                </button>
                                <button type="button" class="btn btn-success" id="export_excel_btn">
                                    <i class="fa fa-file-excel"></i> @lang('advancedreports::lang.export_excel')
                                </button>
                                <button type="button" class="btn btn-info" id="export_pdf_btn">
                                    <i class="fa fa-file-pdf"></i> @lang('advancedreports::lang.export_pdf')
                                </button>
                                <button type="button" class="btn btn-warning" id="print_btn">
                                    <i class="fa fa-print"></i> @lang('Print')
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics Cards -->
    <div class="row" id="metrics_cards">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-money-bill"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Revenue</span>
                    <span class="info-box-number" id="total_revenue"><span class="display_currency"
                            data-currency_symbol="true">0</span></span>
                    <div class="progress">
                        <div class="progress-bar" id="revenue_progress" style="width: 0%"></div>
                    </div>
                    <span class="progress-description" id="revenue_change">0% from last period</span>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-blue"><i class="fa fa-chart-line"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Gross Profit</span>
                    <span class="info-box-number" id="gross_profit"><span class="display_currency"
                            data-currency_symbol="true">0</span></span>
                    <div class="progress">
                        <div class="progress-bar" id="gross_profit_progress" style="width: 0%"></div>
                    </div>
                    <span class="progress-description" id="gross_profit_margin">0% margin</span>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-calculator"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Net Profit</span>
                    <span class="info-box-number" id="net_profit"><span class="display_currency"
                            data-currency_symbol="true">0</span></span>
                    <div class="progress">
                        <div class="progress-bar" id="net_profit_progress" style="width: 0%"></div>
                    </div>
                    <span class="progress-description" id="net_profit_margin">0% margin</span>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-red"><i class="fa fa-chart-line"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Growth Rate</span>
                    <span class="info-box-number" id="growth_rate">0%</span>
                    <div class="progress">
                        <div class="progress-bar" id="growth_progress" style="width: 0%"></div>
                    </div>
                    <span class="progress-description">Compared to last period</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Profit & Loss Statement -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-chart-bar"></i> Profit & Loss Statement</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" id="refresh_statement">
                            <i class="fa fa-refresh"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div id="profit_loss_statement" style="min-height: 400px;">
                        <!-- Default P&L Cards -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="small-box bg-green">
                                    <div class="inner">
                                        <h3 id="pl_revenue">$0.00</h3>
                                        <p>Total Revenue</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fa fa-money-bill"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small-box bg-red">
                                    <div class="inner">
                                        <h3 id="pl_expenses">$0.00</h3>
                                        <p>Total Expenses</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fa fa-credit-card"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small-box bg-blue">
                                    <div class="inner">
                                        <h3 id="pl_gross_profit">$0.00</h3>
                                        <p>Gross Profit</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fa fa-chart-line"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small-box bg-yellow">
                                    <div class="inner">
                                        <h3 id="pl_net_profit">$0.00</h3>
                                        <p>Net Profit</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fa fa-calculator"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed P&L Statement Table -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr class="bg-light-blue">
                                                <th width="60%">Account</th>
                                                <th width="40%" class="text-right">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="bg-green-light">
                                                <td><strong>REVENUE</strong></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;&nbsp;Sales Revenue</td>
                                                <td class="text-right" id="pl_sales_revenue">$0.00</td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;&nbsp;Other Revenue</td>
                                                <td class="text-right" id="pl_other_revenue">$0.00</td>
                                            </tr>
                                            <tr class="bg-gray-light">
                                                <td><strong>Total Revenue</strong></td>
                                                <td class="text-right"><strong id="pl_total_revenue">$0.00</strong></td>
                                            </tr>

                                            <tr class="bg-red-light">
                                                <td><strong>COST OF GOODS SOLD</strong></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;&nbsp;Product Costs</td>
                                                <td class="text-right" id="pl_product_costs">$0.00</td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;&nbsp;Direct Labor</td>
                                                <td class="text-right" id="pl_direct_labor">$0.00</td>
                                            </tr>
                                            <tr class="bg-gray-light">
                                                <td><strong>Total COGS</strong></td>
                                                <td class="text-right"><strong id="pl_total_cogs">$0.00</strong></td>
                                            </tr>
                                            <tr class="bg-blue-light">
                                                <td><strong>GROSS PROFIT</strong></td>
                                                <td class="text-right"><strong id="pl_gross_profit_amount">$0.00</strong></td>
                                            </tr>

                                            <tr class="bg-yellow-light">
                                                <td><strong>OPERATING EXPENSES</strong></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;&nbsp;Staff Salaries</td>
                                                <td class="text-right" id="pl_staff_salaries">$0.00</td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;&nbsp;Rent & Utilities</td>
                                                <td class="text-right" id="pl_rent_utilities">$0.00</td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;&nbsp;Marketing & Advertising</td>
                                                <td class="text-right" id="pl_marketing">$0.00</td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;&nbsp;Other Operating Expenses</td>
                                                <td class="text-right" id="pl_other_expenses">$0.00</td>
                                            </tr>
                                            <tr class="bg-gray-light">
                                                <td><strong>Total Operating Expenses</strong></td>
                                                <td class="text-right"><strong id="pl_total_operating_expenses">$0.00</strong></td>
                                            </tr>

                                            <tr class="bg-success">
                                                <td><strong>NET PROFIT</strong></td>
                                                <td class="text-right"><strong id="pl_net_profit_amount">$0.00</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profit Trends Chart - Full Width -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-line-chart"></i> Profit Trends</h3>
                    <div class="box-tools pull-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-default" data-period="daily"
                                id="daily_trend">Daily</button>
                            <button type="button" class="btn btn-sm btn-primary" data-period="weekly"
                                id="weekly_trend">Weekly</button>
                            <button type="button" class="btn btn-sm btn-default" data-period="monthly"
                                id="monthly_trend">Monthly</button>
                        </div>
                    </div>
                </div>
                <div class="box-body">
                    <canvas id="profit_trends_chart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Analysis Tabs -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-analytics"></i> Detailed Profit Analysis</h3>
                </div>
                <div class="box-body">
                    <!-- Enhanced Navigation Tabs -->
                    <ul class="nav nav-tabs nav-justified">
                        <li class="active">
                            <a href="#profit_by_products" data-toggle="tab">
                                <i class="fa fa-cube"></i> <span class="hidden-xs">By Products</span>
                            </a>
                        </li>
                        <li>
                            <a href="#profit_by_categories" data-toggle="tab">
                                <i class="fa fa-tags"></i> <span class="hidden-xs">By Categories</span>
                            </a>
                        </li>
                        <li>
                            <a href="#profit_by_brands" data-toggle="tab">
                                <i class="fa fa-certificate"></i> <span class="hidden-xs">By Brands</span>
                            </a>
                        </li>
                        <li>
                            <a href="#profit_by_customers" data-toggle="tab">
                                <i class="fa fa-users"></i> <span class="hidden-xs">By Customers</span>
                            </a>
                        </li>
                        <li>
                            <a href="#profit_by_locations" data-toggle="tab">
                                <i class="fa fa-map-marker"></i> <span class="hidden-xs">By Locations</span>
                            </a>
                        </li>
                        <li>
                            <a href="#profit_by_staff" data-toggle="tab">
                                <i class="fa fa-user-tie"></i> <span class="hidden-xs">By Staff</span>
                            </a>
                        </li>
                        <li>
                            <a href="#profit_by_invoices" data-toggle="tab">
                                <i class="fa fa-file-invoice"></i> <span class="hidden-xs">By Invoices</span>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" style="margin-top: 20px;">
                        <div class="tab-pane active" id="profit_by_products">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="profit_by_products_table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity Sold</th>
                                            <th>Revenue</th>
                                            <th>Cost</th>
                                            <th>Gross Profit</th>
                                            <th>Margin %</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Total</th>
                                            <th id="total_qty_products">0</th>
                                            <th id="total_revenue_products"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></th>
                                            <th id="total_cost_products"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></th>
                                            <th id="total_profit_products"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></th>
                                            <th id="avg_margin_products">0%</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane" id="profit_by_categories">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="profit_by_categories_table">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Products Count</th>
                                            <th>Revenue</th>
                                            <th>Cost</th>
                                            <th>Gross Profit</th>
                                            <th>Margin %</th>
                                            <th>Contribution %</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Total</th>
                                            <th id="total_products_categories">0</th>
                                            <th id="total_revenue_categories"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></th>
                                            <th id="total_cost_categories"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></th>
                                            <th id="total_profit_categories"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></th>
                                            <th id="avg_margin_categories">0%</th>
                                            <th>100%</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane" id="profit_by_brands">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="profit_by_brands_table">
                                    <thead>
                                        <tr>
                                            <th>Brand</th>
                                            <th>Products Count</th>
                                            <th>Revenue</th>
                                            <th>Cost</th>
                                            <th>Gross Profit</th>
                                            <th>Margin %</th>
                                            <th>Market Share %</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane" id="profit_by_customers">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="profit_by_customers_table">
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Transactions</th>
                                            <th>Revenue</th>
                                            <th>Cost</th>
                                            <th>Gross Profit</th>
                                            <th>Profit per Transaction</th>
                                            <th>Customer Value</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane" id="profit_by_locations">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="profit_by_locations_table">
                                    <thead>
                                        <tr>
                                            <th>Location</th>
                                            <th>Transactions</th>
                                            <th>Revenue</th>
                                            <th>Cost</th>
                                            <th>Gross Profit</th>
                                            <th>Margin %</th>
                                            <th>Performance</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane" id="profit_by_staff">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="profit_by_staff_table">
                                    <thead>
                                        <tr>
                                            <th>Staff Member</th>
                                            <th>Sales Count</th>
                                            <th>Revenue</th>
                                            <th>Cost</th>
                                            <th>Gross Profit</th>
                                            <th>Avg Profit per Sale</th>
                                            <th>Performance Rating</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane" id="profit_by_invoices">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="profit_by_invoices_table">
                                    <thead>
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Location</th>
                                            <th>Revenue</th>
                                            <th>Cost</th>
                                            <th>Gross Profit</th>
                                            <th>Margin %</th>
                                            <th>Payment Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Total</th>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                            <th id="total_revenue_invoices"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></th>
                                            <th id="total_cost_invoices"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></th>
                                            <th id="total_profit_invoices"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></th>
                                            <th id="avg_margin_invoices">0%</th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>

@stop

@section('css')
<style>
    /* Minimal styles for animations only - use Tailwind for layout/colors */
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
    .loading { animation: pulse 2s infinite; }
</style>
@stop

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="text/javascript">
    // Currency settings from Ultimate POS business settings
    var ultimatePOSCurrency = {
        symbol: "{{ $currency_symbol }}",
        precision: {{ $currency_precision }},
        position: "{{ $currency_symbol_placement }}",
        thousand_separator: "{{ $thousand_separator }}",
        decimal_separator: "{{ $decimal_separator }}"
    };

    var profitTrendsChart = null;
    var currentFilters = {};

    function formatCurrency(num) {
        if (num === null || num === undefined || num === '') {
            return ultimatePOSCurrency.symbol + '0.00';
        }
        
        if (typeof num === 'number') {
            return ultimatePOSCurrency.symbol + num.toFixed(ultimatePOSCurrency.precision).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }
        
        return ultimatePOSCurrency.symbol + '0.00';
    }

    function formatNumber(num, decimals = 2) {
        if (num === null || num === undefined || num === '') {
            return '0';
        }
        
        if (typeof num === 'number') {
            return num.toFixed(decimals).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }
        
        return '0';
    }

    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2();

        // Initialize date range picker
        $('#date_range_filter').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear'
            }
        });

        $('#date_range_filter').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD'));
            refreshAllData();
        });

        // Period type change handler
        $('#period_type_filter').on('change', function() {
            var period = $(this).val();
            if (period === 'custom') {
                $('#date_range_filter').closest('.form-group').show();
                $('#date_range_filter').click();
            } else {
                $('#date_range_filter').closest('.form-group').hide();
                setDateRangeFromPeriod(period);
                refreshAllData();
            }
        });

        // Location filter change
        $('#location_filter, #customer_filter').on('change', function() {
            refreshAllData();
        });

        // Refresh button
        $('#refresh_data').click(function() {
            refreshAllData();
        });

        // Export buttons
        $('#export_excel_btn').click(function() {
            exportData('excel');
        });

        $('#export_pdf_btn').click(function() {
            exportData('pdf');
        });

        $('#print_btn').click(function() {
            printReport();
        });

        // Trend period buttons
        $('.btn-group button[data-period]').click(function() {
            $('.btn-group button').removeClass('btn-primary').addClass('btn-default');
            $(this).removeClass('btn-default').addClass('btn-primary');
            var period = $(this).data('period');
            loadProfitTrends(period);
        });

        // Initialize DataTables for each tab
        initializeDataTables();

        // Tab change handler
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr('href');
            refreshTabData(target);
        });

        // Initial currency formatting for default values
        __currency_convert_recursively($('#metrics_cards'));

        // Initial load
        setDateRangeFromPeriod('this_month');
        refreshAllData();
    });

    function setDateRangeFromPeriod(period) {
        var start, end;
        var today = moment();

        switch(period) {
            case 'today':
                start = today.clone().startOf('day');
                end = today.clone().endOf('day');
                break;
            case 'this_week':
                start = today.clone().startOf('week');
                end = today.clone().endOf('week');
                break;
            case 'this_month':
                start = today.clone().startOf('month');
                end = today.clone().endOf('month');
                break;
            case 'this_quarter':
                start = today.clone().startOf('quarter');
                end = today.clone().endOf('quarter');
                break;
            case 'this_year':
                start = today.clone().startOf('year');
                end = today.clone().endOf('year');
                break;
            case 'last_week':
                start = today.clone().subtract(1, 'week').startOf('week');
                end = today.clone().subtract(1, 'week').endOf('week');
                break;
            case 'last_month':
                start = today.clone().subtract(1, 'month').startOf('month');
                end = today.clone().subtract(1, 'month').endOf('month');
                break;
            case 'last_quarter':
                start = today.clone().subtract(1, 'quarter').startOf('quarter');
                end = today.clone().subtract(1, 'quarter').endOf('quarter');
                break;
            case 'last_year':
                start = today.clone().subtract(1, 'year').startOf('year');
                end = today.clone().subtract(1, 'year').endOf('year');
                break;
            default:
                start = today.clone().startOf('month');
                end = today.clone().endOf('month');
        }
        
        currentFilters = {
            start_date: start.format('YYYY-MM-DD'),
            end_date: end.format('YYYY-MM-DD'),
            location_id: $('#location_filter').val(),
            customer_id: $('#customer_filter').val()
        };
    }

    function refreshAllData() {
        // Update current filters
        var dateRange = $('#date_range_filter').val();
        if (dateRange && dateRange.includes(' to ')) {
            var dates = dateRange.split(' to ');
            currentFilters.start_date = dates[0];
            currentFilters.end_date = dates[1];
        }
        
        currentFilters.location_id = $('#location_filter').val();
        currentFilters.customer_id = $('#customer_filter').val();

        // Refresh all components
        loadSummaryData();
        loadProfitLossStatement();
        loadProfitTrends();
        refreshCurrentTab();
    }

    function loadSummaryData() {
        $.ajax({
            url: "{{ route('advancedreports.profit-loss.summary') }}",
            data: currentFilters,
            success: function(response) {
                if (response.success) {
                    updateMetricsCards(response.data, response.metrics);
                }
            },
            error: function(xhr) {
                console.error('Error loading summary data:', xhr);
                toastr.error('Error loading summary data');
            }
        });
    }

    function loadProfitLossStatement() {
        // Show loading state on the cards
        $('#pl_revenue, #pl_expenses, #pl_gross_profit, #pl_net_profit').html('<i class="fa fa-spinner fa-spin"></i>');
        $('#pl_sales_revenue, #pl_other_revenue, #pl_total_revenue, #pl_product_costs, #pl_direct_labor, #pl_total_cogs, #pl_gross_profit_amount, #pl_staff_salaries, #pl_rent_utilities, #pl_marketing, #pl_other_expenses, #pl_total_operating_expenses, #pl_net_profit_amount').html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: "{{ route('advancedreports.profit-loss.summary') }}",
            data: currentFilters,
            success: function(response) {
                if (response.success && response.data) {
                    var data = response.data;

                    // Update summary cards
                    $('#pl_revenue').text(formatCurrency(data.total_sell_inc_tax || 0));
                    $('#pl_expenses').text(formatCurrency(data.total_cost || 0));
                    $('#pl_gross_profit').text(formatCurrency(data.gross_profit || 0));
                    $('#pl_net_profit').text(formatCurrency(data.net_profit || 0));

                    // Update detailed P&L statement
                    $('#pl_sales_revenue').text(formatCurrency(data.total_sell_inc_tax || 0));
                    $('#pl_other_revenue').text(formatCurrency(0)); // Placeholder for other revenue
                    $('#pl_total_revenue').text(formatCurrency(data.total_sell_inc_tax || 0));

                    $('#pl_product_costs').text(formatCurrency(data.total_cost || 0));
                    $('#pl_direct_labor').text(formatCurrency(0)); // Placeholder for direct labor
                    $('#pl_total_cogs').text(formatCurrency(data.total_cost || 0));
                    $('#pl_gross_profit_amount').text(formatCurrency(data.gross_profit || 0));

                    // Operating expenses (placeholder values - can be enhanced)
                    var operatingExpenses = Math.max(0, (data.gross_profit || 0) - (data.net_profit || 0));
                    $('#pl_staff_salaries').text(formatCurrency(operatingExpenses * 0.6)); // 60% of operating expenses
                    $('#pl_rent_utilities').text(formatCurrency(operatingExpenses * 0.2)); // 20% of operating expenses
                    $('#pl_marketing').text(formatCurrency(operatingExpenses * 0.1)); // 10% of operating expenses
                    $('#pl_other_expenses').text(formatCurrency(operatingExpenses * 0.1)); // 10% of operating expenses
                    $('#pl_total_operating_expenses').text(formatCurrency(operatingExpenses));

                    $('#pl_net_profit_amount').text(formatCurrency(data.net_profit || 0));

                    // Apply color coding based on profit/loss
                    var grossProfit = data.gross_profit || 0;
                    var netProfit = data.net_profit || 0;

                    if (grossProfit >= 0) {
                        $('#pl_gross_profit').parent().removeClass('bg-red').addClass('bg-blue');
                        $('#pl_gross_profit_amount').parent().parent().removeClass('bg-red-light').addClass('bg-blue-light');
                    } else {
                        $('#pl_gross_profit').parent().removeClass('bg-blue').addClass('bg-red');
                        $('#pl_gross_profit_amount').parent().parent().removeClass('bg-blue-light').addClass('bg-red-light');
                    }

                    if (netProfit >= 0) {
                        $('#pl_net_profit').parent().removeClass('bg-red').addClass('bg-yellow');
                        $('#pl_net_profit_amount').parent().parent().removeClass('bg-danger').addClass('bg-success');
                    } else {
                        $('#pl_net_profit').parent().removeClass('bg-yellow').addClass('bg-red');
                        $('#pl_net_profit_amount').parent().parent().removeClass('bg-success').addClass('bg-danger');
                    }

                } else {
                    console.error('Invalid response format:', response);
                    showDefaultValues();
                }
            },
            error: function(xhr) {
                console.error('Error loading profit loss statement:', xhr);
                showDefaultValues();
                $('#profit_loss_statement').prepend('<div class="alert alert-warning alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>Unable to load latest data. Showing default values.</div>');
            }
        });
    }

    function showDefaultValues() {
        // Reset to default values
        $('#pl_revenue, #pl_expenses, #pl_gross_profit, #pl_net_profit').text('$0.00');
        $('#pl_sales_revenue, #pl_other_revenue, #pl_total_revenue, #pl_product_costs, #pl_direct_labor, #pl_total_cogs, #pl_gross_profit_amount, #pl_staff_salaries, #pl_rent_utilities, #pl_marketing, #pl_other_expenses, #pl_total_operating_expenses, #pl_net_profit_amount').text('$0.00');
    }

    function loadProfitTrends(period = 'weekly') {
        $.ajax({
            url: "{{ route('advancedreports.profit-loss.trends') }}",
            data: Object.assign({}, currentFilters, {period: period}),
            success: function(response) {
                if (response.success) {
                    updateProfitTrendsChart(response.trends);
                }
            },
            error: function(xhr) {
                console.error('Error loading profit trends:', xhr);
            }
        });
    }

    function updateMetricsCards(data, metrics) {
        // Use pre-formatted values from backend
        $('#total_revenue').html('<span class="display_currency" data-currency_symbol="true">' + (data.total_sell_inc_tax || 0) + '</span>');
        $('#gross_profit').html('<span class="display_currency" data-currency_symbol="true">' + (data.gross_profit || 0) + '</span>');
        $('#net_profit').html('<span class="display_currency" data-currency_symbol="true">' + (data.net_profit || 0) + '</span>');
        $('#growth_rate').text(formatNumber(metrics.growth_rate || 0, 1) + '%');

        $('#gross_profit_margin').text(formatNumber(metrics.gross_profit_margin || 0, 1) + '% margin');
        $('#net_profit_margin').text(formatNumber(metrics.net_profit_margin || 0, 1) + '% margin');

        // Convert currency elements to show currency symbols
        __currency_convert_recursively($('#metrics_cards'));

        // Update progress bars
        updateProgressBars(metrics);
    }

    function updateProgressBars(metrics) {
        var maxProgress = 100;
        
        $('#revenue_progress').css('width', Math.min(100, Math.abs(metrics.growth_rate || 0)) + '%');
        $('#gross_profit_progress').css('width', Math.min(100, metrics.gross_profit_margin || 0) + '%');
        $('#net_profit_progress').css('width', Math.min(100, metrics.net_profit_margin || 0) + '%');
        $('#growth_progress').css('width', Math.min(100, Math.abs(metrics.growth_rate || 0)) + '%');
        
        // Color coding for progress bars
        var growthBar = $('#growth_progress');
        if (metrics.growth_rate > 0) {
            growthBar.removeClass('progress-bar-danger').addClass('progress-bar-success');
        } else if (metrics.growth_rate < 0) {
            growthBar.removeClass('progress-bar-success').addClass('progress-bar-danger');
        }
    }

    function updateProfitTrendsChart(trends) {
        var ctx = document.getElementById('profit_trends_chart').getContext('2d');
        
        if (profitTrendsChart) {
            profitTrendsChart.destroy();
        }
        
        profitTrendsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: trends.map(function(item) { return item.label; }),
                datasets: [{
                    label: 'Profit',
                    data: trends.map(function(item) { return item.profit; }),
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Revenue',
                    data: trends.map(function(item) { return item.revenue; }),
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                            }
                        }
                    }
                }
            }
        });
    }

    function initializeDataTables() {
        // Initialize all DataTables for different tabs
        // Implementation will be added for each specific table
        
        // Products table
        var productsTable = $('#profit_by_products_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.profit-loss.analysis') }}",
                data: function (d) {
                    return Object.assign(d, currentFilters, {type: 'product'});
                }
            },
            columns: [
                {data: 'product', name: 'product'},
                {data: 'quantity', name: 'quantity', className: 'text-center'},
                {data: 'revenue', name: 'revenue', className: 'text-right'},
                {data: 'cost', name: 'cost', className: 'text-right'},
                {data: 'profit', name: 'profit', className: 'text-right'},
                {data: 'margin', name: 'margin', className: 'text-center'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();

                // Calculate totals
                var totalQty = 0, totalRevenue = 0, totalCost = 0, totalProfit = 0;

                for (var i = 0; i < data.length; i++) {
                    totalQty += parseFloat(data[i].quantity.replace(/,/g, '')) || 0;
                    totalRevenue += parseFloat(data[i].revenue.replace(/,/g, '')) || 0;
                    totalCost += parseFloat(data[i].cost.replace(/,/g, '')) || 0;
                    totalProfit += parseFloat(data[i].profit.replace(/,/g, '')) || 0;
                }

                var avgMargin = totalRevenue > 0 ? ((totalProfit / totalRevenue) * 100) : 0;

                // Update footer
                $('#total_qty_products').text(formatNumber(totalQty, 0));
                $('#total_revenue_products').html('<span class="display_currency" data-currency_symbol="true">' + totalRevenue.toFixed(2) + '</span>');
                $('#total_cost_products').html('<span class="display_currency" data-currency_symbol="true">' + totalCost.toFixed(2) + '</span>');
                $('#total_profit_products').html('<span class="display_currency" data-currency_symbol="true">' + totalProfit.toFixed(2) + '</span>');
                $('#avg_margin_products').text(formatNumber(avgMargin, 2) + '%');

                // Convert currency in footer
                __currency_convert_recursively($('#profit_by_products_table tfoot'));
            }
        });

        // Categories table
        var categoriesTable = $('#profit_by_categories_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.profit-loss.analysis') }}",
                data: function (d) {
                    return Object.assign(d, currentFilters, {type: 'category'});
                }
            },
            columns: [
                {data: 'category', name: 'category'},
                {data: 'products_count', name: 'products_count', className: 'text-center'},
                {data: 'revenue', name: 'revenue', className: 'text-right'},
                {data: 'cost', name: 'cost', className: 'text-right'},
                {data: 'profit', name: 'profit', className: 'text-right'},
                {data: 'margin', name: 'margin', className: 'text-center'},
                {data: 'contribution', name: 'contribution', className: 'text-center'}
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();

                // Calculate totals
                var totalProducts = 0, totalRevenue = 0, totalCost = 0, totalProfit = 0;

                for (var i = 0; i < data.length; i++) {
                    totalProducts += parseInt(data[i].products_count) || 0;
                    totalRevenue += parseFloat(data[i].revenue.replace(/,/g, '')) || 0;
                    totalCost += parseFloat(data[i].cost.replace(/,/g, '')) || 0;
                    totalProfit += parseFloat(data[i].profit.replace(/,/g, '')) || 0;
                }

                var avgMargin = totalRevenue > 0 ? ((totalProfit / totalRevenue) * 100) : 0;

                // Update footer
                $('#total_products_categories').text(totalProducts);
                $('#total_revenue_categories').html('<span class="display_currency" data-currency_symbol="true">' + totalRevenue.toFixed(2) + '</span>');
                $('#total_cost_categories').html('<span class="display_currency" data-currency_symbol="true">' + totalCost.toFixed(2) + '</span>');
                $('#total_profit_categories').html('<span class="display_currency" data-currency_symbol="true">' + totalProfit.toFixed(2) + '</span>');
                $('#avg_margin_categories').text(formatNumber(avgMargin, 2) + '%');

                // Convert currency in footer
                __currency_convert_recursively($('#profit_by_categories_table tfoot'));
            }
        });

        // Brands table
        var brandsTable = $('#profit_by_brands_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.profit-loss.analysis') }}",
                data: function (d) {
                    return Object.assign(d, currentFilters, {type: 'brand'});
                }
            },
            columns: [
                {data: 'brand', name: 'brand'},
                {data: 'products_count', name: 'products_count', className: 'text-center'},
                {data: 'revenue', name: 'revenue', className: 'text-right'},
                {data: 'cost', name: 'cost', className: 'text-right'},
                {data: 'profit', name: 'profit', className: 'text-right'},
                {data: 'margin', name: 'margin', className: 'text-center'},
                {data: 'market_share', name: 'market_share', className: 'text-center'}
            ]
        });

        // Customers table
        var customersTable = $('#profit_by_customers_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.profit-loss.analysis') }}",
                data: function (d) {
                    return Object.assign(d, currentFilters, {type: 'customer'});
                }
            },
            columns: [
                {data: 'customer', name: 'customer'},
                {data: 'orders_count', name: 'orders_count', className: 'text-center'},
                {data: 'revenue', name: 'revenue', className: 'text-right'},
                {data: 'cost', name: 'cost', className: 'text-right'},
                {data: 'profit', name: 'profit', className: 'text-right'},
                {data: 'margin', name: 'margin', className: 'text-center'},
                {data: 'avg_order_value', name: 'avg_order_value', className: 'text-right'}
            ]
        });

        // Locations table
        var locationsTable = $('#profit_by_locations_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.profit-loss.analysis') }}",
                data: function (d) {
                    return Object.assign(d, currentFilters, {type: 'location'});
                }
            },
            columns: [
                {data: 'location', name: 'location'},
                {data: 'transactions_count', name: 'transactions_count', className: 'text-center'},
                {data: 'revenue', name: 'revenue', className: 'text-right'},
                {data: 'cost', name: 'cost', className: 'text-right'},
                {data: 'profit', name: 'profit', className: 'text-right'},
                {data: 'margin', name: 'margin', className: 'text-center'},
                {data: 'performance', name: 'performance', className: 'text-center'}
            ]
        });

        // Staff table
        var staffTable = $('#profit_by_staff_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.profit-loss.analysis') }}",
                data: function (d) {
                    return Object.assign(d, currentFilters, {type: 'staff'});
                }
            },
            columns: [
                {data: 'staff', name: 'staff'},
                {data: 'sales_count', name: 'sales_count', className: 'text-center'},
                {data: 'revenue', name: 'revenue', className: 'text-right'},
                {data: 'cost', name: 'cost', className: 'text-right'},
                {data: 'profit', name: 'profit', className: 'text-right'},
                {data: 'margin', name: 'margin', className: 'text-center'},
                {data: 'commission', name: 'commission', className: 'text-right'}
            ]
        });

        // Invoices table
        var invoicesTable = $('#profit_by_invoices_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.profit-loss.analysis') }}",
                data: function (d) {
                    return Object.assign(d, currentFilters, {type: 'invoice'});
                }
            },
            columns: [
                {data: 'invoice_no', name: 'invoice_no'},
                {data: 'transaction_date', name: 'transaction_date', className: 'text-center'},
                {data: 'customer_name', name: 'customer_name'},
                {data: 'location_name', name: 'location_name'},
                {data: 'revenue', name: 'revenue', className: 'text-right'},
                {data: 'cost', name: 'cost', className: 'text-right'},
                {data: 'profit', name: 'profit', className: 'text-right'},
                {data: 'margin', name: 'margin', className: 'text-center'},
                {data: 'payment_status', name: 'payment_status', className: 'text-center'},
                {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
            ],
            order: [[1, 'desc']], // Order by date desc by default
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();

                // Calculate totals
                var totalRevenue = api.column(4, {page: 'current'}).data().reduce(function (a, b) {
                    return parseFloat(a) + parseFloat(b.replace(/[^0-9.-]+/g,""));
                }, 0);

                var totalCost = api.column(5, {page: 'current'}).data().reduce(function (a, b) {
                    return parseFloat(a) + parseFloat(b.replace(/[^0-9.-]+/g,""));
                }, 0);

                var totalProfit = totalRevenue - totalCost;
                var avgMargin = totalRevenue > 0 ? (totalProfit / totalRevenue * 100) : 0;

                // Update footer totals
                $('#total_revenue_invoices .display_currency').text(formatCurrency(totalRevenue));
                $('#total_cost_invoices .display_currency').text(formatCurrency(totalCost));
                $('#total_profit_invoices .display_currency').text(formatCurrency(totalProfit));
                $('#avg_margin_invoices').text(avgMargin.toFixed(2) + '%');
            }
        });
    }

    function refreshTabData(tabId) {
        // Refresh data for specific tab when activated
        var tableId = tabId.replace('#', '') + '_table';
        var table = $('#' + tableId).DataTable();
        if (table) {
            table.ajax.reload();
        }
    }

    function refreshCurrentTab() {
        var activeTab = $('.nav-tabs li.active a').attr('href');
        if (activeTab) {
            refreshTabData(activeTab);
        }
    }

    function exportData(format) {
        var url = "{{ route('advancedreports.profit-loss.export') }}";
        var params = Object.assign({}, currentFilters, {format: format});
        
        // Create a form and submit for file download
        var form = $('<form>', {
            'method': 'POST',
            'action': url
        });
        
        // Add CSRF token
        form.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': '{{ csrf_token() }}'
        }));
        
        // Add parameters
        $.each(params, function(key, value) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': key,
                'value': value
            }));
        });
        
        $('body').append(form);
        form.submit();
        form.remove();
    }

    function printReport() {
        // Get the current date range for the print header
        var dateRange = $('#date_range_filter').val() || 'All Time';
        var location = $('#location_filter option:selected').text() || 'All Locations';
        
        // Create print content
        var printContent = `
            <div class="print-header">
                <h1>Profit & Loss Report</h1>
                <p><strong>Period:</strong> ${dateRange}</p>
                <p><strong>Location:</strong> ${location}</p>
                <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
            </div>
            
            <div class="print-summary">
                ${$('#summary_metrics_section .row').clone().wrap('<div>').parent().html()}
            </div>
            
            <div class="print-statement">
                <h2>Profit & Loss Statement</h2>
                ${$('#profit_loss_statement').clone().html()}
            </div>
        `;
        
        // Create print window
        var printWindow = window.open('', '_blank', 'width=800,height=600');
        
        printWindow.document.write(`
            <html>
                <head>
                    <title>Profit & Loss Report</title>
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            margin: 20px; 
                            color: #333; 
                        }
                        .print-header { 
                            text-align: center; 
                            margin-bottom: 30px; 
                            border-bottom: 2px solid #ddd; 
                            padding-bottom: 20px; 
                        }
                        .print-header h1 { 
                            margin: 0 0 10px 0; 
                            color: #2c3e50; 
                        }
                        .print-summary .row { 
                            display: flex; 
                            flex-wrap: wrap; 
                            margin-bottom: 20px; 
                        }
                        .print-summary .col-lg-3 { 
                            flex: 1; 
                            min-width: 200px; 
                            margin: 10px; 
                        }
                        .small-box { 
                            border: 1px solid #ddd; 
                            border-radius: 5px; 
                            padding: 15px; 
                            text-align: center; 
                        }
                        .inner h3 { 
                            margin: 0; 
                            font-size: 24px; 
                            font-weight: bold; 
                        }
                        .inner p { 
                            margin: 5px 0 0 0; 
                            color: #666; 
                        }
                        .bg-blue { background-color: #3498db !important; color: white; }
                        .bg-green { background-color: #2ecc71 !important; color: white; }
                        .bg-yellow { background-color: #f39c12 !important; color: white; }
                        .bg-red { background-color: #e74c3c !important; color: white; }
                        .text-bold { font-weight: bold; }
                        .text-white { color: white; }
                        .profit-loss-table { 
                            width: 100%; 
                            border-collapse: collapse; 
                            margin-top: 20px; 
                        }
                        .profit-loss-table th, 
                        .profit-loss-table td { 
                            border: 1px solid #ddd; 
                            padding: 8px; 
                            text-align: left; 
                        }
                        .profit-loss-table th { 
                            background-color: #f2f2f2; 
                            font-weight: bold; 
                        }
                        .text-right { text-align: right; }
                        .positive { color: #2ecc71; font-weight: bold; }
                        .negative { color: #e74c3c; font-weight: bold; }
                        @media print {
                            body { margin: 0; }
                            .no-print { display: none !important; }
                        }
                    </style>
                </head>
                <body>
                    ${printContent}
                </body>
            </html>
        `);
        
        printWindow.document.close();
        
        // Wait for content to load then print
        setTimeout(() => {
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        }, 1000);
    }

</script>
@stop