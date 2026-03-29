@extends('layouts.app')

@section('title', __('advancedreports::lang.pricing_optimization'))

@php
// Get currency settings from session
$currency_symbol = session('currency')['symbol'] ?? '$';
$currency_precision = session('business.currency_precision') ?? 2;
$currency_symbol_placement = session('business.currency_symbol_placement') ?? 'before';
@endphp

@section('content')
<section class="content-header">
    <h1>@lang('advancedreports::lang.pricing_optimization')</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-chart-line"></i> @lang('advancedreports::lang.pricing_analytics_dashboard')
                    </h3>
                </div>
                <div class="box-body">
                    <!-- Filter Controls -->
                    <div class="row filter-section" style="margin-bottom: 20px;">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.date_range'):</label>
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                    <input type="text" class="form-control" id="pricing_date_range" 
                                           placeholder="@lang('advancedreports::lang.select_date_range')" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.business_location'):</label>
                                <select class="form-control select2" id="pricing_location_id" multiple>
                                    <option value="all">@lang('advancedreports::lang.all_locations')</option>
                                    @foreach($business_locations as $key => $value)
                                        <option value="{{$key}}">{{$value}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.category'):</label>
                                <select class="form-control select2" id="pricing_category_id" multiple>
                                    <option value="all">@lang('advancedreports::lang.all_categories')</option>
                                    @foreach($categories as $key => $value)
                                        <option value="{{$key}}">{{$value}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.brand'):</label>
                                <select class="form-control select2" id="pricing_brand_id" multiple>
                                    <option value="all">@lang('advancedreports::lang.all_brands')</option>
                                    @foreach($brands as $key => $value)
                                        <option value="{{$key}}">{{$value}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary" id="filter_pricing_data">
                                <i class="fa fa-filter"></i> @lang('advancedreports::lang.filter')
                            </button>
                            <button type="button" class="btn btn-success" id="export_pricing_data">
                                <i class="fa fa-download"></i> @lang('advancedreports::lang.export')
                            </button>
                            <span class="loading-indicator" id="pricing_loading" style="display: none;">
                                <i class="fa fa-spinner fa-spin"></i> @lang('advancedreports::lang.loading')
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row pricing-overview" style="display: none;">
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3 id="overview_avg_margin">0%</h3>
                    <p>@lang('advancedreports::lang.average_margin')</p>
                </div>
                <div class="icon"><i class="fa fa-percent"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="overview_total_products">0</h3>
                    <p>@lang('advancedreports::lang.total_products')</p>
                </div>
                <div class="icon"><i class="fa fa-cube"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3 id="overview_total_revenue">
                        @if($currency_symbol_placement === 'after')
                            0{{ $currency_symbol }}
                        @else
                            {{ $currency_symbol }}0
                        @endif
                    </h3>
                    <p>@lang('advancedreports::lang.total_revenue')</p>
                </div>
                <div class="icon"><i class="fa fa-money"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="overview_avg_price">
                        @if($currency_symbol_placement === 'after')
                            0{{ $currency_symbol }}
                        @else
                            {{ $currency_symbol }}0
                        @endif
                    </h3>
                    <p>@lang('advancedreports::lang.average_selling_price')</p>
                </div>
                <div class="icon"><i class="fa fa-tag"></i></div>
            </div>
        </div>
    </div>

    <!-- Price Elasticity Analysis -->
    <div class="row pricing-content" style="display: none;">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-line-chart"></i> @lang('advancedreports::lang.price_elasticity_analysis')
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-default chart-toggle" data-chart="elasticity" data-current="table">
                            <i class="fa fa-chart-bar"></i> <span class="toggle-text">Chart</span>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div id="elasticity_table_view">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="elasticity_table">
                                <thead>
                                    <tr>
                                        <th>@lang('advancedreports::lang.product')</th>
                                        <th>@lang('advancedreports::lang.category')</th>
                                        <th>@lang('advancedreports::lang.current_price')</th>
                                        <th>@lang('advancedreports::lang.quantity_sold')</th>
                                        <th>@lang('advancedreports::lang.velocity')</th>
                                        <th>@lang('advancedreports::lang.elasticity_type')</th>
                                        <th>@lang('advancedreports::lang.recommendation')</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div id="elasticity_chart_view" style="display: none;">
                        <canvas id="elasticity_chart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Competitor Analysis -->
    <div class="row pricing-content" style="display: none;">
        <div class="col-md-12">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-users"></i> @lang('advancedreports::lang.competitor_price_analysis')
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-default chart-toggle" data-chart="competitor" data-current="table">
                            <i class="fa fa-chart-bar"></i> <span class="toggle-text">Chart</span>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div id="competitor_table_view">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="competitor_table">
                                <thead>
                                    <tr>
                                        <th>@lang('advancedreports::lang.product')</th>
                                        <th>@lang('advancedreports::lang.our_price')</th>
                                        <th>@lang('advancedreports::lang.market_average')</th>
                                        <th>@lang('advancedreports::lang.market_position')</th>
                                        <th>@lang('advancedreports::lang.price_difference')</th>
                                        <th>@lang('advancedreports::lang.competitiveness')</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div id="competitor_chart_view" style="display: none;">
                        <canvas id="competitor_chart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Discount Impact Analysis -->
    <div class="row pricing-content" style="display: none;">
        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-percent"></i> @lang('advancedreports::lang.discount_impact_analysis')
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-default chart-toggle" data-chart="discount" data-current="table">
                            <i class="fa fa-chart-pie"></i> <span class="toggle-text">Chart</span>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div id="discount_table_view">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="discount_table">
                                <thead>
                                    <tr>
                                        <th>@lang('advancedreports::lang.product')</th>
                                        <th>@lang('advancedreports::lang.discount_frequency')</th>
                                        <th>@lang('advancedreports::lang.avg_discount_percent')</th>
                                        <th>@lang('advancedreports::lang.discounted_sales')</th>
                                        <th>@lang('advancedreports::lang.regular_sales')</th>
                                        <th>@lang('advancedreports::lang.effectiveness_score')</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div id="discount_chart_view" style="display: none;">
                        <canvas id="discount_chart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dynamic Pricing Suggestions -->
    <div class="row pricing-content" style="display: none;">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-magic"></i> @lang('advancedreports::lang.dynamic_pricing_suggestions')
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-default chart-toggle" data-chart="suggestions" data-current="table">
                            <i class="fa fa-chart-line"></i> <span class="toggle-text">Chart</span>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div id="suggestions_table_view">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="suggestions_table">
                                <thead>
                                    <tr>
                                        <th>@lang('advancedreports::lang.product')</th>
                                        <th>@lang('advancedreports::lang.current_price')</th>
                                        <th>@lang('advancedreports::lang.suggested_price')</th>
                                        <th>@lang('advancedreports::lang.price_change')</th>
                                        <th>@lang('advancedreports::lang.confidence')</th>
                                        <th>@lang('advancedreports::lang.reasoning')</th>
                                        <th>@lang('advancedreports::lang.revenue_impact')</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div id="suggestions_chart_view" style="display: none;">
                        <canvas id="suggestions_chart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Optimization -->
    <div class="row pricing-content" style="display: none;">
        <div class="col-md-12">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-rocket"></i> @lang('advancedreports::lang.revenue_optimization')
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-default chart-toggle" data-chart="optimization" data-current="table">
                            <i class="fa fa-chart-bar"></i> <span class="toggle-text">Chart</span>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div id="optimization_table_view">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="optimization_table">
                                <thead>
                                    <tr>
                                        <th>@lang('advancedreports::lang.category')</th>
                                        <th>@lang('advancedreports::lang.products')</th>
                                        <th>@lang('advancedreports::lang.revenue')</th>
                                        <th>@lang('advancedreports::lang.average_margin')</th>
                                        <th>@lang('advancedreports::lang.optimization_score')</th>
                                        <th>@lang('advancedreports::lang.recommendation')</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div id="optimization_chart_view" style="display: none;">
                        <canvas id="optimization_chart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.pricing-overview, .pricing-content {
    display: none !important;
}
.pricing-overview.loaded, .pricing-content.loaded {
    display: flex !important;
}
.filter-section {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}
.chart-toggle {
    margin-left: 5px;
}
.loading-indicator {
    margin-left: 10px;
    color: #3c8dbc;
}
.table-responsive {
    max-height: 500px;
    overflow-y: auto;
}
.elasticity-elastic { color: #00a65a; font-weight: bold; }
.elasticity-inelastic { color: #dd4b39; font-weight: bold; }
.elasticity-unit { color: #f39c12; font-weight: bold; }
.position-premium { color: #9c27b0; font-weight: bold; }
.position-value { color: #4caf50; font-weight: bold; }
.position-competitive { color: #2196f3; font-weight: bold; }
.confidence-high { color: #4caf50; }
.confidence-medium { color: #ff9800; }
.confidence-low { color: #f44336; }
</style>
@endsection

@section('javascript')
<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Currency settings
var currencySettings = {
    symbol: "{{ $currency_symbol }}",
    precision: {{ $currency_precision }},
    placement: "{{ $currency_symbol_placement }}"
};

// Format currency helper function
function formatCurrency(value) {
    var formatted = parseFloat(value).toLocaleString(undefined, {
        minimumFractionDigits: currencySettings.precision,
        maximumFractionDigits: currencySettings.precision
    });

    if (currencySettings.placement === 'after') {
        return formatted + currencySettings.symbol;
    } else {
        return currencySettings.symbol + formatted;
    }
}

$(document).ready(function() {
    // Initialize components
    initializeDateRangePicker();
    initializeSelect2();
    setupEventHandlers();

    // Auto-load data on page ready
    setTimeout(function() {
        loadPricingAnalytics();
    }, 500);
});

function initializeDateRangePicker() {
    var start = moment().subtract(3, 'months');
    var end = moment();

    function cb(start, end) {
        $('#pricing_date_range').val(start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
    }

    $('#pricing_date_range').daterangepicker({
        startDate: start,
        endDate: end,
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'Last 3 Months': [moment().subtract(3, 'months'), moment()],
            'Last 6 Months': [moment().subtract(6, 'months'), moment()]
        },
        locale: {
            format: 'YYYY-MM-DD'
        }
    }, cb);

    cb(start, end);
}

function initializeSelect2() {
    $('.select2').select2();
    
    // Pre-select "All" options
    $('#pricing_location_id').val('all').trigger('change');
    $('#pricing_category_id').val('all').trigger('change');
    $('#pricing_brand_id').val('all').trigger('change');
    
    // Handle mutual exclusivity for "All" selections
    $('#pricing_location_id, #pricing_category_id, #pricing_brand_id').on('change', function() {
        var $this = $(this);
        var values = $this.val() || [];
        
        if (values.includes('all') && values.length > 1) {
            $this.val('all').trigger('change');
        }
    });
}

function setupEventHandlers() {
    $('#filter_pricing_data').click(function() {
        loadPricingAnalytics();
    });
    
    $('#export_pricing_data').click(function() {
        exportPricingData();
    });
    
    // Chart toggle functionality
    $('.chart-toggle').click(function() {
        var chart = $(this).data('chart');
        var current = $(this).data('current');
        var newView = current === 'table' ? 'chart' : 'table';
        
        $(this).data('current', newView);
        $(this).find('.toggle-text').text(newView === 'table' ? 'Chart' : 'Table');
        
        $('#' + chart + '_table_view').toggle(newView === 'table');
        $('#' + chart + '_chart_view').toggle(newView === 'chart');
        
        if (newView === 'chart') {
            // Render chart when switching to chart view
            // Add delay to ensure Chart.js is loaded
            setTimeout(function() {
                if (typeof Chart !== 'undefined') {
                    renderChart(chart);
                } else {
                    console.warn('Chart.js not yet loaded, retrying...');
                    setTimeout(function() {
                        renderChart(chart);
                    }, 500);
                }
            }, 100);
        }
    });
}

function loadPricingAnalytics() {
    showLoading(true);
    
    var dateRange = $('#pricing_date_range').val().split(' to ');
    var locationIds = $('#pricing_location_id').val();
    var categoryIds = $('#pricing_category_id').val();
    var brandIds = $('#pricing_brand_id').val();
    
    // Handle "all" selections
    if (locationIds && locationIds.includes('all')) locationIds = [];
    if (categoryIds && categoryIds.includes('all')) categoryIds = [];
    if (brandIds && brandIds.includes('all')) brandIds = [];
    
    $.ajax({
        url: '/advanced-reports/pricing-optimization/analytics',
        method: 'GET',
        data: {
            start_date: dateRange[0],
            end_date: dateRange[1],
            location_ids: locationIds,
            category_ids: categoryIds,
            brand_ids: brandIds
        },
        success: function(response) {
            populateOverview(response.price_performance);
            populateElasticityAnalysis(response.price_elasticity);
            populateCompetitorAnalysis(response.competitor_analysis);
            populateDiscountAnalysis(response.discount_impact);
            populatePricingSuggestions(response.pricing_suggestions);
            populateOptimizationAnalysis(response.revenue_optimization);
            showContent();
        },
        error: function(xhr, status, error) {
            console.error('Error loading pricing analytics:', error);
            toastr.error('Failed to load pricing analytics data');
        },
        complete: function() {
            showLoading(false);
        }
    });
}

function showLoading(show) {
    if (show) {
        $('#pricing_loading').show();
        $('.pricing-overview, .pricing-content').removeClass('loaded').hide();
    } else {
        $('#pricing_loading').hide();
    }
}

function showContent() {
    $('.pricing-overview, .pricing-content').addClass('loaded').show();
}

function populateOverview(data) {
    $('#overview_avg_margin').text(data.average_margin + '%');
    $('#overview_total_products').text(data.total_products);
    $('#overview_total_revenue').text(formatCurrency(data.total_revenue));
    $('#overview_avg_price').text(formatCurrency(data.average_selling_price));
}

function populateElasticityAnalysis(data) {
    var tbody = $('#elasticity_table tbody');
    tbody.empty();

    if (data && data.length > 0) {
        data.forEach(function(item) {
            var elasticityClass = 'elasticity-' + item.elasticity_type.toLowerCase().replace(' ', '');
            var row = `
                <tr>
                    <td>${item.product}</td>
                    <td>${item.category || 'N/A'}</td>
                    <td>${formatCurrency(item.current_price)}</td>
                    <td>${item.quantity_sold}</td>
                    <td>${item.velocity}</td>
                    <td><span class="${elasticityClass}">${item.elasticity_type}</span></td>
                    <td><small>${item.recommendation}</small></td>
                </tr>
            `;
            tbody.append(row);
        });
    } else {
        tbody.append('<tr><td colspan="7" class="text-center">No elasticity data available</td></tr>');
    }
}

function populateCompetitorAnalysis(data) {
    var tbody = $('#competitor_table tbody');
    tbody.empty();

    if (data && data.length > 0) {
        data.forEach(function(item) {
            var positionClass = 'position-' + item.position.toLowerCase();
            var differenceColor = parseFloat(item.price_difference) > 0 ? 'text-red' : 'text-green';
            var row = `
                <tr>
                    <td>${item.product}</td>
                    <td>${formatCurrency(item.our_price)}</td>
                    <td>${formatCurrency(item.market_avg)}</td>
                    <td><span class="${positionClass}">${item.position}</span></td>
                    <td><span class="${differenceColor}">${item.price_difference}%</span></td>
                    <td>${item.competitiveness}</td>
                </tr>
            `;
            tbody.append(row);
        });
    } else {
        tbody.append('<tr><td colspan="6" class="text-center">No competitor data available</td></tr>');
    }
}

function populateDiscountAnalysis(data) {
    var tbody = $('#discount_table tbody');
    tbody.empty();
    
    if (data && data.length > 0) {
        data.forEach(function(item) {
            var row = `
                <tr>
                    <td>${item.product}</td>
                    <td>${item.discount_frequency}%</td>
                    <td>${item.avg_discount_percent}%</td>
                    <td>${item.discounted_sales}</td>
                    <td>${item.regular_sales}</td>
                    <td><strong>${item.effectiveness_score}</strong></td>
                </tr>
            `;
            tbody.append(row);
        });
    } else {
        tbody.append('<tr><td colspan="6" class="text-center">No discount data available</td></tr>');
    }
}

function populatePricingSuggestions(data) {
    var tbody = $('#suggestions_table tbody');
    tbody.empty();

    if (data && data.length > 0) {
        data.forEach(function(item) {
            var changeColor = item.price_change.includes('+') ? 'text-green' : 'text-red';
            var confidenceClass = item.confidence > 80 ? 'confidence-high' : (item.confidence > 60 ? 'confidence-medium' : 'confidence-low');
            var row = `
                <tr>
                    <td>${item.product}</td>
                    <td>${formatCurrency(item.current_price)}</td>
                    <td>${formatCurrency(item.suggested_price)}</td>
                    <td><span class="${changeColor}"><strong>${item.price_change}</strong></span></td>
                    <td><span class="${confidenceClass}">${item.confidence}%</span></td>
                    <td><small>${item.reasoning}</small></td>
                    <td><span class="${item.potential_revenue_impact.includes('+') ? 'text-green' : 'text-red'}">${item.potential_revenue_impact}</span></td>
                </tr>
            `;
            tbody.append(row);
        });
    } else {
        tbody.append('<tr><td colspan="7" class="text-center">No pricing suggestions available</td></tr>');
    }
}

function populateOptimizationAnalysis(data) {
    var tbody = $('#optimization_table tbody');
    tbody.empty();

    if (data && data.length > 0) {
        data.forEach(function(item) {
            var row = `
                <tr>
                    <td>${item.category}</td>
                    <td>${item.product_count}</td>
                    <td>${formatCurrency(item.total_revenue)}</td>
                    <td>${item.average_margin}%</td>
                    <td><strong>${item.optimization_score}</strong></td>
                    <td><small>${item.recommendation}</small></td>
                </tr>
            `;
            tbody.append(row);
        });
    } else {
        tbody.append('<tr><td colspan="6" class="text-center">No optimization data available</td></tr>');
    }
}

var chartData = {};

function renderChart(chartType) {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded');
        return;
    }
    
    var ctx = document.getElementById(chartType + '_chart');
    if (!ctx || !chartData[chartType]) return;
    
    var config = getChartConfig(chartType, chartData[chartType]);
    
    // Destroy existing chart if it exists
    if (window[chartType + '_chart_instance']) {
        window[chartType + '_chart_instance'].destroy();
    }
    
    window[chartType + '_chart_instance'] = new Chart(ctx.getContext('2d'), config);
}

function getChartConfig(chartType, data) {
    // Safety check for data
    if (!data || !Array.isArray(data) || data.length === 0) {
        console.warn('No data available for chart:', chartType);
        return {
            type: 'bar',
            data: { labels: ['No Data'], datasets: [{ data: [0], backgroundColor: '#ddd', label: 'No Data Available' }] },
            options: { responsive: true }
        };
    }

    switch(chartType) {
        case 'elasticity':
            return {
                type: 'scatter',
                data: {
                    datasets: [{
                        label: 'Price Elasticity',
                        data: data.map(item => ({
                            x: parseFloat((item.current_price || '0').toString().replace(currencySettings.symbol, '').replace(/,/g, '')),
                            y: parseFloat(item.velocity || 0),
                            product: item.product || '',
                            elasticity: item.elasticity_type || ''
                        })),
                        backgroundColor: function(context) {
                            const point = data[context.dataIndex];
                            const elasticity = point?.elasticity_type || '';
                            return elasticity === 'Elastic' ? '#00a65a' :
                                   elasticity === 'Inelastic' ? '#dd4b39' : '#f39c12';
                        }
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: { title: { display: true, text: 'Price (' + currencySettings.symbol + ')' }},
                        y: { title: { display: true, text: 'Velocity (units/day)' }}
                    }
                }
            };

        case 'competitor':
            return {
                type: 'bar',
                data: {
                    labels: data.slice(0, 10).map(item => item.product || ''),
                    datasets: [{
                        label: 'Our Price',
                        data: data.slice(0, 10).map(item => parseFloat((item.our_price || '0').toString().replace(currencySettings.symbol, '').replace(/,/g, ''))),
                        backgroundColor: '#3c8dbc'
                    }, {
                        label: 'Market Average',
                        data: data.slice(0, 10).map(item => parseFloat((item.market_avg || '0').toString().replace(currencySettings.symbol, '').replace(/,/g, ''))),
                        backgroundColor: '#00a65a'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            title: { display: true, text: 'Price (' + currencySettings.symbol + ')' },
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        }
                    }
                }
            };

        case 'suggestions':
            return {
                type: 'line',
                data: {
                    labels: data.slice(0, 10).map(item => item.product || ''),
                    datasets: [{
                        label: 'Current Price',
                        data: data.slice(0, 10).map(item => parseFloat((item.current_price || '0').toString().replace(currencySettings.symbol, '').replace(/,/g, ''))),
                        borderColor: '#dd4b39',
                        fill: false
                    }, {
                        label: 'Suggested Price',
                        data: data.slice(0, 10).map(item => parseFloat((item.suggested_price || '0').toString().replace(currencySettings.symbol, '').replace(/,/g, ''))),
                        borderColor: '#00a65a',
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            title: { display: true, text: 'Price (' + currencySettings.symbol + ')' },
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value);
                                }
                            }
                        }
                    }
                }
            };
            
        case 'discount':
            // Handle case when no discount data is available
            if (!data || data.length === 0) {
                return {
                    type: 'doughnut',
                    data: {
                        labels: ['No Discount Data'],
                        datasets: [{
                            label: 'Discount Analysis',
                            data: [100],
                            backgroundColor: ['#ddd']
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: {
                                callbacks: {
                                    label: function() {
                                        return 'No discount data available';
                                    }
                                }
                            }
                        }
                    }
                };
            }
            
            return {
                type: 'doughnut',
                data: {
                    labels: data.slice(0, 8).map(item => item.product || ''),
                    datasets: [{
                        label: 'Discount Effectiveness',
                        data: data.slice(0, 8).map(item => parseFloat(item.effectiveness_score || 0)),
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const item = data[context.dataIndex];
                                    return `${item.product}: ${context.parsed}% effectiveness`;
                                }
                            }
                        }
                    }
                }
            };
            
        case 'optimization':
            return {
                type: 'bar',
                data: {
                    labels: data.slice(0, 10).map(item => item.category || ''),
                    datasets: [{
                        label: 'Optimization Score',
                        data: data.slice(0, 10).map(item => parseFloat(item.optimization_score || 0)),
                        backgroundColor: '#3c8dbc'
                    }, {
                        label: 'Average Margin %',
                        data: data.slice(0, 10).map(item => parseFloat((item.average_margin || '0').toString().replace('%', ''))),
                        backgroundColor: '#00a65a',
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: { display: true, text: 'Optimization Score' }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: { display: true, text: 'Margin %' },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            };
            
        default:
            return {
                type: 'bar',
                data: { labels: ['No Data'], datasets: [{ data: [0], backgroundColor: '#ddd', label: 'No Data Available' }] },
                options: { responsive: true }
            };
    }
}

function exportPricingData() {
    var dateRange = $('#pricing_date_range').val().split(' to ');
    var locationIds = $('#pricing_location_id').val();
    var categoryIds = $('#pricing_category_id').val();
    var brandIds = $('#pricing_brand_id').val();

    // Handle "all" selections
    if (locationIds && locationIds.includes('all')) locationIds = [];
    if (categoryIds && categoryIds.includes('all')) categoryIds = [];
    if (brandIds && brandIds.includes('all')) brandIds = [];

    var params = {
        start_date: dateRange[0],
        end_date: dateRange[1],
        _token: '{{ csrf_token() }}'
    };

    if (locationIds && locationIds.length) {
        params['location_ids[]'] = locationIds;
    }
    if (categoryIds && categoryIds.length) {
        params['category_ids[]'] = categoryIds;
    }
    if (brandIds && brandIds.length) {
        params['brand_ids[]'] = brandIds;
    }

    // Use AJAX to download the file properly
    $.ajax({
        url: '/advanced-reports/pricing-optimization/export',
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
            var filename = 'pricing-optimization-report.xlsx';
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
        }
    });
}

// Store chart data when analytics are loaded
function storeChartData(response) {
    chartData = {
        elasticity: response.price_elasticity,
        competitor: response.competitor_analysis,
        discount: response.discount_impact,
        suggestions: response.pricing_suggestions,
        optimization: response.revenue_optimization
    };
}

// Update the success handler in loadPricingAnalytics
$(document).ready(function() {
    // Modify the existing success handler
    var originalSuccess = loadPricingAnalytics;
    loadPricingAnalytics = function() {
        showLoading(true);
        
        var dateRange = $('#pricing_date_range').val().split(' to ');
        var locationIds = $('#pricing_location_id').val();
        var categoryIds = $('#pricing_category_id').val();
        var brandIds = $('#pricing_brand_id').val();
        
        // Handle "all" selections
        if (locationIds && locationIds.includes('all')) locationIds = [];
        if (categoryIds && categoryIds.includes('all')) categoryIds = [];
        if (brandIds && brandIds.includes('all')) brandIds = [];
        
        $.ajax({
            url: '/advanced-reports/pricing-optimization/analytics',
            method: 'GET',
            data: {
                start_date: dateRange[0],
                end_date: dateRange[1],
                location_ids: locationIds,
                category_ids: categoryIds,
                brand_ids: brandIds
            },
            success: function(response) {
                    storeChartData(response); // Store data for charts
                populateOverview(response.price_performance);
                populateElasticityAnalysis(response.price_elasticity);
                populateCompetitorAnalysis(response.competitor_analysis);
                populateDiscountAnalysis(response.discount_impact);
                populatePricingSuggestions(response.pricing_suggestions);
                populateOptimizationAnalysis(response.revenue_optimization);
                showContent();
            },
            error: function(xhr, status, error) {
                console.error('Error loading pricing analytics:', error);
                toastr.error('Failed to load pricing analytics data');
            },
            complete: function() {
                showLoading(false);
            }
        });
    };
});
</script>
@endsection