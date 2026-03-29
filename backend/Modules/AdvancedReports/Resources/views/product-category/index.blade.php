@extends('layouts.app')

@section('title', __('advancedreports::lang.product_category_performance'))

@php
// Get currency settings from session
$currency_symbol = session('currency')['symbol'] ?? '$';
$currency_precision = session('business.currency_precision') ?? 2;
$currency_symbol_placement = session('business.currency_symbol_placement') ?? 'before';
@endphp

@section('css')
<style>
.info-box {
    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
    border-radius: 0.25rem;
    margin-bottom: 1rem;
}

.chart-container {
    position: relative;
    height: 400px;
    margin: 15px 0;
}

.category-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    background: white;
}

.metric-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.trend-up { color: #28a745; }
.trend-down { color: #dc3545; }
.trend-stable { color: #6c757d; }

.loading-indicator {
    text-align: center;
    padding: 20px;
    display: none;
}
</style>
@endsection

@section('content')
<section class="content-header">
    <h1><i class="fas fa-layer-group"></i> @lang('advancedreports::lang.product_category_performance')</h1>
</section>

<section class="content">
    <!-- Filter Controls -->
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('advancedreports::lang.filter_controls')</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.date_range')</label>
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                    <input type="text" class="form-control" id="pc_date_range" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.business_location')</label>
                                <select class="form-control select2" id="pc_location_id">
                                    @foreach($business_locations as $id => $name)
                                        <option value="{{ $id }}" {{ $id == 'all' ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.categories')</label>
                                <select class="form-control select2" id="pc_category_id" multiple>
                                    @foreach($categories as $id => $name)
                                        <option value="{{ $id }}" {{ $id == 'all' ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.brands')</label>
                                <select class="form-control select2" id="pc_brand_id" multiple>
                                    @foreach($brands as $id => $name)
                                        <option value="{{ $id }}" {{ $id == 'all' ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary" id="pc_apply_filters">
                                <i class="fa fa-search"></i> @lang('advancedreports::lang.apply_filters')
                            </button>
                            <button type="button" class="btn btn-success" id="pc_export_data">
                                <i class="fa fa-download"></i> @lang('advancedreports::lang.export_data')
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div class="loading-indicator" id="pc_loading">
        <i class="fa fa-spinner fa-spin fa-3x"></i>
        <p>@lang('advancedreports::lang.loading_data')</p>
    </div>

    <!-- Overview Cards -->
    <div class="row" id="overview_cards">
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3 id="total_categories">0</h3>
                    <p>@lang('advancedreports::lang.total_categories')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-layer-group"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="total_sales">
                        @if($currency_symbol_placement === 'after')
                            0{{ $currency_symbol }}
                        @else
                            {{ $currency_symbol }}0
                        @endif
                    </h3>
                    <p>@lang('advancedreports::lang.total_sales')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-dollar-sign"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3 id="total_profit">
                        @if($currency_symbol_placement === 'after')
                            0{{ $currency_symbol }}
                        @else
                            {{ $currency_symbol }}0
                        @endif
                    </h3>
                    <p>@lang('advancedreports::lang.total_profit')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-chart-line"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="avg_margin">0%</h3>
                    <p>@lang('advancedreports::lang.average_margin')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-percentage"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Content -->
    <div class="row" id="analytics_content" style="display: none;">
        <!-- Category Contribution -->
        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('advancedreports::lang.category_contribution')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-default chart-toggle" data-chart="contribution" data-current="pie">
                            <i class="fa fa-chart-bar"></i> <span class="toggle-text">Bar</span>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="chart-container">
                        <canvas id="contribution_chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Margin Analysis -->
        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('advancedreports::lang.margin_analysis')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-default chart-toggle" data-chart="margin" data-current="bar">
                            <i class="fa fa-chart-line"></i> <span class="toggle-text">Line</span>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="chart-container">
                        <canvas id="margin_chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Growth Trends -->
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('advancedreports::lang.growth_trends')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-default chart-toggle" data-chart="growth" data-current="line">
                            <i class="fa fa-chart-bar"></i> <span class="toggle-text">Bar</span>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="chart-container">
                        <canvas id="growth_chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cross-Selling Opportunities -->
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('advancedreports::lang.cross_selling_opportunities')</h3>
                </div>
                <div class="box-body">
                    <div id="cross_selling_content">
                        <!-- Cross-selling data will be populated here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Seasonal Patterns -->
        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('advancedreports::lang.seasonal_patterns')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-default chart-toggle" data-chart="seasonal" data-current="bar">
                            <i class="fa fa-chart-line"></i> <span class="toggle-text">Line</span>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="chart-container">
                        <canvas id="seasonal_chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performing Categories -->
        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('advancedreports::lang.top_performing_categories')</h3>
                </div>
                <div class="box-body">
                    <div id="top_performers_content">
                        <!-- Top performers data will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Translation variables
var translations = {
    today: '@lang("advancedreports::lang.today")',
    yesterday: '@lang("advancedreports::lang.yesterday")',
    last_7_days: '@lang("advancedreports::lang.last_7_days")',
    last_30_days: '@lang("advancedreports::lang.last_30_days")',
    this_month: '@lang("advancedreports::lang.this_month")',
    last_month: '@lang("advancedreports::lang.last_month")',
    error_loading_analytics: '@lang("advancedreports::lang.error_loading_analytics_data")',
    margin_percentage: '@lang("advancedreports::lang.margin_percentage")',
    growth_rate_percentage: '@lang("advancedreports::lang.growth_rate_percentage")',
    confidence_label: '@lang("advancedreports::lang.confidence_label")',
    transactions_label: '@lang("advancedreports::lang.transactions_label")',
    analytics_data_received: '@lang("advancedreports::lang.analytics_data_received")'
};

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
    // Initialize date range picker
    var start = moment().subtract(29, 'days');
    var end = moment();
    
    $('#pc_date_range').daterangepicker({
        startDate: start,
        endDate: end,
        ranges: {
            [translations.today]: [moment(), moment()],
            [translations.yesterday]: [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            [translations.last_7_days]: [moment().subtract(6, 'days'), moment()],
            [translations.last_30_days]: [moment().subtract(29, 'days'), moment()],
            [translations.this_month]: [moment().startOf('month'), moment().endOf('month')],
            [translations.last_month]: [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });

    // Initialize select2
    $('.select2').select2();

    // Handle "All" option logic for multi-select dropdowns
    function handleAllSelection(selectElement) {
        var $select = $(selectElement);
        var values = $select.val();
        
        if (values && values.includes('all')) {
            if (values.length > 1) {
                // If "all" is selected along with other options, keep only "all"
                $select.val(['all']).trigger('change.select2');
            }
        }
    }

    // Handle individual selection (deselect "all" when specific items are selected)
    function handleSpecificSelection(selectElement) {
        var $select = $(selectElement);
        var values = $select.val();
        
        if (values && values.length > 1 && values.includes('all')) {
            // Remove "all" when specific items are selected
            var specificValues = values.filter(function(val) { return val !== 'all'; });
            $select.val(specificValues).trigger('change.select2');
        }
    }

    // Bind handlers to multi-select dropdowns
    $('#pc_category_id').on('select2:select', function(e) {
        if (e.params.data.id === 'all') {
            handleAllSelection(this);
        } else {
            handleSpecificSelection(this);
        }
    });

    $('#pc_brand_id').on('select2:select', function(e) {
        if (e.params.data.id === 'all') {
            handleAllSelection(this);
        } else {
            handleSpecificSelection(this);
        }
    });

    // Variables to store chart instances
    var contributionChart = null;
    var marginChart = null;
    var growthChart = null;
    var seasonalChart = null;
    
    // Store chart data for toggling
    var chartData = {
        contribution: null,
        margin: null,
        growth: null,
        seasonal: null
    };

    // Apply filters function
    function applyFilters() {
        var formData = {
            start_date: $('#pc_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD'),
            end_date: $('#pc_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD'),
            location_id: $('#pc_location_id').val(),
            category_id: $('#pc_category_id').val(),
            brand_id: $('#pc_brand_id').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        // Show loading
        $('#pc_loading').show();
        $('#analytics_content').hide();

        // Make AJAX request
        $.ajax({
            url: "{{ route('advancedreports.product-category.analytics') }}",
            method: 'GET',
            data: formData,
            success: function(response) {
                console.log('Analytics data received:', response);
                console.log('Category contribution structure:', response.category_contribution);
                console.log('Margin analysis structure:', response.margin_analysis);
                console.log('Growth trends structure:', response.growth_trends);
                updateOverviewCards(response);
                updateCharts(response);
                $('#pc_loading').hide();
                $('#analytics_content').show();
            },
            error: function(xhr, status, error) {
                console.error('Error loading analytics:', error);
                $('#pc_loading').hide();
                alert(translations.error_loading_analytics);
            }
        });
    }

    // Update overview cards
    function updateOverviewCards(data) {
        console.log('updateOverviewCards called with:', data);
        
        // Debug: Check if category_contribution exists
        if (data.category_contribution) {
            console.log('category_contribution exists:', data.category_contribution);
            console.log('category_contribution keys:', Object.keys(data.category_contribution));
            
            if (data.category_contribution.categories) {
                console.log('categories array exists, length:', data.category_contribution.categories.length);
                console.log('first category sample:', data.category_contribution.categories[0]);
            }
        }
        
        if (data.category_contribution && data.category_contribution.categories && data.category_contribution.categories.length > 0) {
            var categories = data.category_contribution.categories;
            console.log('Processing categories:', categories.length, 'items');
            
            $('#total_categories').text(categories.length);
            console.log('Set total_categories to:', categories.length);
            
            var totalSales = categories.reduce((sum, cat) => {
                console.log('Category sales:', cat.category_name, cat.total_sales);
                return sum + parseFloat(cat.total_sales || 0);
            }, 0);
            
            var totalProfit = categories.reduce((sum, cat) => {
                console.log('Category profit:', cat.category_name, cat.gross_profit);
                return sum + parseFloat(cat.gross_profit || 0);
            }, 0);
            
            var avgMargin = totalSales > 0 ? ((totalProfit / totalSales) * 100) : 0;
            
            console.log('Calculated totals - Sales:', totalSales, 'Profit:', totalProfit, 'Margin:', avgMargin);

            $('#total_sales').text(formatCurrency(totalSales));
            $('#total_profit').text(formatCurrency(totalProfit));
            $('#avg_margin').text(avgMargin.toFixed(1) + '%');

            console.log('DOM updated successfully');
        } else {
            console.log('No category data found, setting defaults');
            // Set defaults if no data
            $('#total_categories').text('0');
            $('#total_sales').text(formatCurrency(0));
            $('#total_profit').text(formatCurrency(0));
            $('#avg_margin').text('0%');
        }
    }

    // Update charts
    function updateCharts(data) {
        // Destroy existing charts
        if (contributionChart) contributionChart.destroy();
        if (marginChart) marginChart.destroy();
        if (growthChart) growthChart.destroy();
        if (seasonalChart) seasonalChart.destroy();

        // Category Contribution Chart
        if (data.category_contribution && data.category_contribution.categories && data.category_contribution.categories.length > 0) {
            // Store data for toggling
            chartData.contribution = {
                labels: data.category_contribution.categories.map(item => item.category_name),
                datasets: [{
                    label: 'Sales Revenue',
                    data: data.category_contribution.categories.map(item => parseFloat(item.total_sales)),
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                        '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
                    ]
                }]
            };
            
            var ctx1 = document.getElementById('contribution_chart').getContext('2d');
            contributionChart = new Chart(ctx1, {
                type: 'pie',
                data: chartData.contribution,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Margin Analysis Chart
        if (data.margin_analysis && data.margin_analysis.margin_data && data.margin_analysis.margin_data.length > 0) {
            // Store data for toggling
            chartData.margin = {
                labels: data.margin_analysis.margin_data.map(item => item.category_name),
                datasets: [{
                    label: translations.margin_percentage,
                    data: data.margin_analysis.margin_data.map(item => parseFloat(item.avg_margin)),
                    backgroundColor: '#36A2EB',
                    borderColor: '#36A2EB'
                }]
            };
            
            var ctx2 = document.getElementById('margin_chart').getContext('2d');
            marginChart = new Chart(ctx2, {
                type: 'bar',
                data: chartData.margin,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Growth Trends Chart
        if (data.growth_trends && data.growth_trends.growth_analysis && Object.keys(data.growth_trends.growth_analysis).length > 0) {
            var growthData = Object.values(data.growth_trends.growth_analysis);
            
            // Store data for toggling
            chartData.growth = {
                labels: growthData.map(item => item.category_name),
                datasets: [{
                    label: translations.growth_rate_percentage,
                    data: growthData.map(item => parseFloat(item.avg_monthly_growth)),
                    borderColor: '#4BC0C0',
                    backgroundColor: '#4BC0C0',
                    fill: true
                }]
            };
            
            var ctx3 = document.getElementById('growth_chart').getContext('2d');
            growthChart = new Chart(ctx3, {
                type: 'line',
                data: chartData.growth,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Cross-selling opportunities
        console.log('Cross-selling data:', data.cross_selling_opportunities);
        if (data.cross_selling_opportunities && data.cross_selling_opportunities.market_basket && data.cross_selling_opportunities.market_basket.length > 0) {
            console.log('Market basket data found:', data.cross_selling_opportunities.market_basket.length, 'items');
            var crossSellingHtml = '<div class="row">';
            data.cross_selling_opportunities.market_basket.forEach(function(item, index) {
                if (index < 6) { // Show top 6
                    crossSellingHtml += '<div class="col-md-4">';
                    crossSellingHtml += '<div class="category-card">';
                    crossSellingHtml += '<h5>' + item.category_a + ' + ' + item.category_b + '</h5>';
                    crossSellingHtml += '<div class="metric-item">';
                    crossSellingHtml += '<span>' + translations.confidence_label + '</span>';
                    crossSellingHtml += '<span><strong>' + parseFloat(item.confidence).toFixed(1) + '%</strong></span>';
                    crossSellingHtml += '</div>';
                    crossSellingHtml += '<div class="metric-item">';
                    crossSellingHtml += '<span>' + translations.transactions_label + '</span>';
                    crossSellingHtml += '<span>' + item.transaction_count + '</span>';
                    crossSellingHtml += '</div>';
                    crossSellingHtml += '</div>';
                    crossSellingHtml += '</div>';
                }
            });
            crossSellingHtml += '</div>';
            $('#cross_selling_content').html(crossSellingHtml);
        } else {
            console.log('No cross-selling data available');
            $('#cross_selling_content').html('<div class="alert alert-info"><i class="fa fa-info-circle"></i> No cross-selling opportunities found. This analysis requires transactions with multiple categories purchased together.</div>');
        }

        // Seasonal Patterns Chart
        console.log('Seasonal patterns data:', data.seasonal_patterns);
        if (data.seasonal_patterns && data.seasonal_patterns.monthly_patterns && Object.keys(data.seasonal_patterns.monthly_patterns).length > 0) {
            // Process nested monthly patterns data
            var monthlyData = {};
            var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            
            // Initialize months with 0 values
            for (var i = 1; i <= 12; i++) {
                monthlyData[i] = 0;
            }
            
            // Aggregate sales across all categories by month
            Object.keys(data.seasonal_patterns.monthly_patterns).forEach(function(categoryName) {
                var categoryData = data.seasonal_patterns.monthly_patterns[categoryName];
                Object.keys(categoryData).forEach(function(month) {
                    monthlyData[parseInt(month)] += parseFloat(categoryData[month] || 0);
                });
            });
            
            // Convert to arrays for Chart.js
            var labels = [];
            var values = [];
            for (var i = 1; i <= 12; i++) {
                if (monthlyData[i] > 0) {
                    labels.push(monthNames[i - 1]);
                    values.push(monthlyData[i]);
                }
            }
            
            console.log('Processed seasonal data - Labels:', labels, 'Values:', values);
            
            if (labels.length > 0) {
                // Store data for toggling
                chartData.seasonal = {
                    labels: labels,
                    datasets: [{
                        label: 'Monthly Sales (' + currencySettings.symbol + ')',
                        data: values,
                        backgroundColor: '#FF9F40',
                        borderColor: '#FF9F40'
                    }]
                };

                var ctx4 = document.getElementById('seasonal_chart').getContext('2d');
                seasonalChart = new Chart(ctx4, {
                    type: 'bar',
                    data: chartData.seasonal,
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
                        }
                    }
                });
            }
        } else {
            $('#seasonal_chart').parent().html('<div class="alert alert-info"><i class="fa fa-info-circle"></i> No seasonal patterns data available.</div>');
        }

        // Top Performing Categories
        if (data.top_performers && data.top_performers.length > 0) {
            var topPerformersHtml = '<div class="row">';
            data.top_performers.forEach(function(category, index) {
                if (index < 5) { // Show top 5
                    var badgeClass = index === 0 ? 'badge-warning' : (index === 1 ? 'badge-secondary' : 'badge-info');
                    var medal = index === 0 ? '🥇' : (index === 1 ? '🥈' : (index === 2 ? '🥉' : ''));
                    
                    topPerformersHtml += '<div class="col-md-12">';
                    topPerformersHtml += '<div class="category-card">';
                    topPerformersHtml += '<div style="display: flex; justify-content: space-between; align-items: center;">';
                    topPerformersHtml += '<h5>' + medal + ' ' + category.category_name + '</h5>';
                    topPerformersHtml += '<span class="badge ' + badgeClass + '">#' + (index + 1) + '</span>';
                    topPerformersHtml += '</div>';
                    topPerformersHtml += '<div class="metric-item">';
                    topPerformersHtml += '<span>Revenue:</span>';
                    topPerformersHtml += '<span><strong>' + formatCurrency(parseFloat(category.total_sales)) + '</strong></span>';
                    topPerformersHtml += '</div>';
                    topPerformersHtml += '<div class="metric-item">';
                    topPerformersHtml += '<span>Transactions:</span>';
                    topPerformersHtml += '<span>' + category.transaction_count + '</span>';
                    topPerformersHtml += '</div>';
                    topPerformersHtml += '</div>';
                    topPerformersHtml += '</div>';
                }
            });
            topPerformersHtml += '</div>';
            $('#top_performers_content').html(topPerformersHtml);
        } else {
            $('#top_performers_content').html('<div class="alert alert-info"><i class="fa fa-info-circle"></i> No top performing categories data available.</div>');
        }
    }

    // Chart toggle functionality
    function toggleChart(chartType, newType) {
        var data = chartData[chartType];
        if (!data) return;
        
        var chartInstance, canvasId;
        switch(chartType) {
            case 'contribution':
                if (contributionChart) contributionChart.destroy();
                canvasId = 'contribution_chart';
                break;
            case 'margin':
                if (marginChart) marginChart.destroy();
                canvasId = 'margin_chart';
                break;
            case 'growth':
                if (growthChart) growthChart.destroy();
                canvasId = 'growth_chart';
                break;
            case 'seasonal':
                if (seasonalChart) seasonalChart.destroy();
                canvasId = 'seasonal_chart';
                break;
        }
        
        var ctx = document.getElementById(canvasId).getContext('2d');
        var chartConfig = {
            type: newType,
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        };
        
        // Customize options based on chart type
        if (newType === 'line' && (chartType === 'growth' || chartType === 'seasonal')) {
            chartConfig.data.datasets[0].fill = false;
            chartConfig.data.datasets[0].tension = 0.4;
        }
        
        // Add specific formatting for different charts
        if (chartType === 'margin' || chartType === 'seasonal') {
            chartConfig.options.scales = {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return chartType === 'seasonal' ? formatCurrency(value) : value + '%';
                        }
                    }
                }
            };
        }
        
        if (chartType === 'growth') {
            chartConfig.options.scales = {
                y: {
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            };
        }
        
        chartInstance = new Chart(ctx, chartConfig);
        
        // Update the stored chart instance
        switch(chartType) {
            case 'contribution': contributionChart = chartInstance; break;
            case 'margin': marginChart = chartInstance; break;
            case 'growth': growthChart = chartInstance; break;
            case 'seasonal': seasonalChart = chartInstance; break;
        }
    }

    // Chart toggle button handlers
    $(document).on('click', '.chart-toggle', function() {
        var $btn = $(this);
        var chartType = $btn.data('chart');
        var currentType = $btn.data('current');
        var newType;
        
        // Determine next chart type
        if (chartType === 'contribution') {
            newType = currentType === 'pie' ? 'bar' : 'pie';
        } else if (chartType === 'margin' || chartType === 'seasonal') {
            newType = currentType === 'bar' ? 'line' : 'bar';
        } else if (chartType === 'growth') {
            newType = currentType === 'line' ? 'bar' : 'line';
        }
        
        toggleChart(chartType, newType);
        
        // Update button state
        $btn.data('current', newType);
        var $icon = $btn.find('i');
        var $text = $btn.find('.toggle-text');
        
        if (chartType === 'contribution') {
            if (newType === 'pie') {
                $icon.removeClass('fa-chart-pie').addClass('fa-chart-bar');
                $text.text('Bar');
            } else {
                $icon.removeClass('fa-chart-bar').addClass('fa-chart-pie');
                $text.text('Pie');
            }
        } else {
            if (newType === 'line') {
                $icon.removeClass('fa-chart-line').addClass('fa-chart-bar');
                $text.text('Bar');
            } else {
                $icon.removeClass('fa-chart-bar').addClass('fa-chart-line');
                $text.text('Line');
            }
        }
    });

    // Event handlers
    $('#pc_apply_filters').click(function() {
        applyFilters();
    });

    $('#pc_export_data').click(function() {
        var formData = {
            start_date: $('#pc_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD'),
            end_date: $('#pc_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD'),
            location_id: $('#pc_location_id').val(),
            category_id: $('#pc_category_id').val(),
            brand_id: $('#pc_brand_id').val()
        };

        var url = "{{ route('advancedreports.product-category.export') }}?" + $.param(formData);
        window.open(url, '_blank');
    });

    // Load initial data
    applyFilters();
});
</script>
@endsection