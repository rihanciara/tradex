@extends('layouts.app')

@section('title', __('advancedreports::lang.seasonal_trends_report'))

@php
// Get currency settings from session
$currency_symbol = session('currency')['symbol'] ?? '$';
$currency_precision = session('business.currency_precision') ?? 2;
$currency_symbol_placement = session('business.currency_symbol_placement') ?? 'before';
@endphp

@section('css')
<style>
.seasonal-overview, .seasonal-content {
    display: none !important;
}
.seasonal-overview.loaded, .seasonal-content.loaded {
    display: block !important;
}
.chart-toggle {
    margin-left: 5px;
}
.loading-indicator {
    margin-left: 10px;
    color: #3c8dbc;
}
.small-box .inner h3, .small-box .inner p {
    color: white !important;
}
.trend-up { 
    color: #28a745; 
    font-weight: bold;
}
.trend-down { 
    color: #dc3545; 
    font-weight: bold;
}
.trend-stable { 
    color: #6c757d; 
    font-weight: bold;
}
.holiday-performance-card {
    border-left: 4px solid #28a745;
    margin-bottom: 10px;
    padding: 10px;
    border-radius: 5px;
}
.holiday-performance-card.high { border-left-color: #28a745; }
.holiday-performance-card.medium { border-left-color: #ffc107; }
.holiday-performance-card.low { border-left-color: #dc3545; }
.season-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: bold;
}
.season-spring { background-color: #28a745; color: white; }
.season-summer { background-color: #ff9800; color: white; }
.season-fall { background-color: #795548; color: white; }
.season-winter { background-color: #2196f3; color: white; }
.promo-effective { 
    background-color: #d4edda !important; 
    color: #155724 !important;
    font-weight: bold;
}
.promo-ineffective { 
    background-color: #f8d7da !important; 
    color: #721c24 !important;
    font-weight: bold;
}
.weather-placeholder {
    background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
    color: white;
    text-align: center;
    padding: 30px;
    border-radius: 10px;
}
</style>
@endsection

@section('content')
<section class="content-header">
    <h1>@lang('advancedreports::lang.seasonal_trends_report')</h1>
</section>

<section class="content">
    <!-- Filters Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-filter"></i> @lang('advancedreports::lang.filters')
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.locations'):</label>
                                <select class="form-control select2" id="seasonal_location_id" multiple>
                                    <option value="all">@lang('advancedreports::lang.all_locations')</option>
                                    @foreach($locations as $id => $location)
                                        <option value="{{ $id }}">{{ $location }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.analysis_period'):</label>
                                <select class="form-control" id="year_range">
                                    <option value="1">@lang('advancedreports::lang.last_1_year')</option>
                                    <option value="2" selected>@lang('advancedreports::lang.last_2_years')</option>
                                    <option value="3">@lang('advancedreports::lang.last_3_years')</option>
                                    <option value="5">@lang('advancedreports::lang.last_5_years')</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.analysis_type'):</label>
                                <select class="form-control" id="analysis_type">
                                    <option value="revenue">@lang('advancedreports::lang.revenue')</option>
                                    <option value="gross_revenue">@lang('advancedreports::lang.gross_revenue')</option>
                                    <option value="transactions">@lang('advancedreports::lang.transactions')</option>
                                    <option value="quantity">@lang('advancedreports::lang.quantity_sold')</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label><br>
                                <button type="button" class="btn btn-primary" id="filter_seasonal_data">
                                    <i class="fa fa-search"></i> @lang('advancedreports::lang.analyze')
                                    <span class="loading-indicator" id="loading_seasonal" style="display: none;">
                                        <i class="fa fa-spinner fa-spin"></i>
                                    </span>
                                </button>
                                <button type="button" class="btn btn-success" id="export_seasonal_data">
                                    <i class="fa fa-download"></i> @lang('advancedreports::lang.export')
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row seasonal-overview" style="display: none;">
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-blue">
                <div class="inner">
                    <h3 id="overview_current_year">
                        @if($currency_symbol_placement === 'after')
                            0{{ $currency_symbol }}
                        @else
                            {{ $currency_symbol }}0
                        @endif
                    </h3>
                    <p>@lang('advancedreports::lang.current_year_performance')</p>
                </div>
                <div class="icon"><i class="fa fa-calendar"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="overview_yoy_growth">0%</h3>
                    <p>@lang('advancedreports::lang.year_over_year_growth')</p>
                </div>
                <div class="icon"><i class="fa fa-line-chart"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-orange">
                <div class="inner">
                    <h3 id="overview_best_season">-</h3>
                    <p>@lang('advancedreports::lang.best_performing_season')</p>
                </div>
                <div class="icon"><i class="fa fa-sun-o"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="overview_best_month">-</h3>
                    <p>@lang('advancedreports::lang.best_performing_month')</p>
                </div>
                <div class="icon"><i class="fa fa-star"></i></div>
            </div>
        </div>
    </div>

    <!-- Monthly and Yearly Trends -->
    <div class="row seasonal-content" style="display: none;">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-line-chart"></i> @lang('advancedreports::lang.monthly_trends')
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-primary" id="export_monthly_trends">
                            <i class="fa fa-file-excel-o"></i> @lang('advancedreports::lang.export_to_excel')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <canvas id="monthly_trends_chart" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-pie-chart"></i> @lang('advancedreports::lang.seasonal_patterns')
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-primary" id="export_seasonal_patterns">
                            <i class="fa fa-file-excel-o"></i> @lang('advancedreports::lang.export_to_excel')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <canvas id="seasonal_patterns_chart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Holiday Performance Analysis -->
    <div class="row seasonal-content" style="display: none;">
        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-gift"></i> @lang('advancedreports::lang.holiday_season_performance')
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-primary" id="export_holiday_performance">
                            <i class="fa fa-file-excel-o"></i> @lang('advancedreports::lang.export_to_excel')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row" id="holiday_performance_cards">
                        <!-- Holiday cards will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Promotional Effectiveness -->
    <div class="row seasonal-content" style="display: none;">
        <div class="col-md-8">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-tags"></i> @lang('advancedreports::lang.promotional_effectiveness')
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-primary" id="export_promotional_data">
                            <i class="fa fa-file-excel-o"></i> @lang('advancedreports::lang.export_to_excel')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="promotional_table">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.promotion_type')</th>
                                    <th>@lang('advancedreports::lang.transactions')</th>
                                    <th>@lang('advancedreports::lang.revenue')</th>
                                    <th>@lang('advancedreports::lang.avg_transaction_value')</th>
                                    <th>@lang('advancedreports::lang.avg_discount')</th>
                                    <th>@lang('advancedreports::lang.discount_percentage')</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Promotional data will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-cloud"></i> @lang('advancedreports::lang.weather_impact_analysis')
                    </h3>
                </div>
                <div class="box-body">
                    <div class="weather-placeholder">
                        <i class="fa fa-cloud-sun fa-3x mb-3"></i>
                        <h4>@lang('advancedreports::lang.weather_analysis_coming_soon')</h4>
                        <p>@lang('advancedreports::lang.weather_analysis_description')</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Peak Performance Analysis -->
    <div class="row seasonal-content" style="display: none;">
        <div class="col-md-6">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-trophy"></i> @lang('advancedreports::lang.best_performing_months')
                    </h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="best_months_table">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.rank')</th>
                                    <th>@lang('advancedreports::lang.month')</th>
                                    <th>@lang('advancedreports::lang.revenue')</th>
                                    <th>@lang('advancedreports::lang.transactions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Best months data will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-bar-chart"></i> @lang('advancedreports::lang.day_of_week_performance')
                    </h3>
                </div>
                <div class="box-body">
                    <canvas id="day_of_week_chart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
    initializeSelect2();

    // Set default values
    $('#seasonal_location_id').val('all').trigger('change');
    
    // Filter change handlers
    $('#seasonal_location_id, #year_range, #analysis_type').on('change', function() {
        var $this = $(this);
        var values = $this.val() || [];
        
        // Handle "all" selections
        if (Array.isArray(values) && values.includes('all')) {
            $this.val(['all']).trigger('change.select2');
        }
    });
    
    // Filter button click
    $('#filter_seasonal_data').click(function() {
        loadSeasonalAnalytics();
    });
    
    // Export button click
    $('#export_seasonal_data').click(function() {
        exportSeasonalData();
    });
    
    // Excel export buttons
    $('#export_monthly_trends').click(function() {
        exportMonthlyTrends();
    });
    
    $('#export_seasonal_patterns').click(function() {
        exportSeasonalPatterns();
    });
    
    $('#export_holiday_performance').click(function() {
        exportHolidayPerformance();
    });
    
    $('#export_promotional_data').click(function() {
        exportPromotionalData();
    });
    
    // Load initial data
    loadSeasonalAnalytics();
});

function initializeSelect2() {
    $('#seasonal_location_id').select2({
        placeholder: '@lang('advancedreports::lang.select_locations')',
        allowClear: false
    });
}

function showLoading(show) {
    if (show) {
        $('#loading_seasonal').show();
        $('.seasonal-overview, .seasonal-content').removeClass('loaded').hide();
    } else {
        $('#loading_seasonal').hide();
    }
}

function showContent() {
    $('.seasonal-overview, .seasonal-content').addClass('loaded').show();
}

function loadSeasonalAnalytics() {
    showLoading(true);
    
    var locationIds = $('#seasonal_location_id').val();
    var yearRange = $('#year_range').val();
    var analysisType = $('#analysis_type').val();
    
    // Handle "all" selections
    if (locationIds && locationIds.includes('all')) locationIds = [];
    
    $.ajax({
        url: '/advanced-reports/seasonal-trends/analytics',
        method: 'GET',
        data: {
            location_ids: locationIds,
            year_range: yearRange,
            analysis_type: analysisType
        },
        success: function(response) {
            console.log('Seasonal analytics loaded:', response);
            populateOverview(response.trend_summary);
            populateMonthlyTrends(response.monthly_trends);
            populateSeasonalPatterns(response.seasonal_patterns);
            populateHolidayPerformance(response.holiday_performance);
            populatePromotionalEffectiveness(response.promotional_effectiveness);
            populatePeakPerformance(response.peak_performance);
            renderCharts(response.chart_data);
            showContent();
        },
        error: function(xhr, status, error) {
            console.error('Error loading seasonal analytics:', error);
            toastr.error('Failed to load seasonal trends data');
        },
        complete: function() {
            showLoading(false);
        }
    });
}

function populateOverview(summary) {
    if (!summary) return;

    // Current year performance
    var currentYear = summary.current_year || {};
    $('#overview_current_year').text(formatCurrency(currentYear.net_revenue ? parseFloat(currentYear.net_revenue) : 0));

    // Year-over-year growth
    var growth = summary.yoy_growth || 'N/A';
    var growthClass = '';
    if (growth !== 'N/A') {
        var growthValue = parseFloat(growth);
        growthClass = growthValue > 0 ? 'trend-up' : (growthValue < 0 ? 'trend-down' : 'trend-stable');
        growth = (growthValue > 0 ? '+' : '') + growth + '%';
    }
    $('#overview_yoy_growth').text(growth).removeClass('trend-up trend-down trend-stable').addClass(growthClass);
}

function populateMonthlyTrends(trends) {
    // Store data globally for export
    window.monthlyTrendsData = trends || [];
}

function populateSeasonalPatterns(patterns) {
    // Store data globally for export
    window.seasonalPatternsData = patterns || [];
    
    // Update best season in overview
    if (patterns && patterns.length > 0) {
        $('#overview_best_season').text(patterns[0].season || '-');
        
        // Update best month from patterns
        var bestMonth = '-';
        // This would typically come from monthly trends data
        $('#overview_best_month').text(bestMonth);
    }
}

function populateHolidayPerformance(holidays) {
    // Store data globally for export
    window.holidayPerformanceData = holidays || [];

    var container = $('#holiday_performance_cards');
    container.empty();

    if (holidays && holidays.length > 0) {
        holidays.slice(0, 6).forEach(function(holiday, index) { // Show top 6 holidays
            var performanceClass = index < 2 ? 'high' : (index < 4 ? 'medium' : 'low');
            var card = `
                <div class="col-md-4">
                    <div class="holiday-performance-card ${performanceClass}">
                        <h4>${holiday.holiday_name} ${holiday.year}</h4>
                        <p><strong>Revenue:</strong> ${formatCurrency(holiday.analysis_value)}</p>
                        <p><strong>Transactions:</strong> ${holiday.transaction_count}</p>
                        <p><strong>Duration:</strong> ${holiday.duration_days} days</p>
                        <p><strong>Daily Average:</strong> ${formatCurrency(holiday.daily_average)}</p>
                    </div>
                </div>
            `;
            container.append(card);
        });
    } else {
        container.append('<div class="col-md-12"><p class="text-center">No holiday performance data available</p></div>');
    }
}

function populatePromotionalEffectiveness(promos) {
    // Store data globally for export
    window.promotionalData = promos || [];

    var tbody = $('#promotional_table tbody');
    tbody.empty();

    if (promos && promos.length > 0) {
        promos.forEach(function(promo) {
            var effectivenessClass = promo.type === 'With Promotion' ? 'promo-effective' : 'promo-ineffective';
            var row = `
                <tr class="${effectivenessClass}">
                    <td>${promo.type}</td>
                    <td>${promo.transaction_count}</td>
                    <td>${formatCurrency(promo.net_revenue)}</td>
                    <td>${formatCurrency(promo.avg_transaction_value)}</td>
                    <td>${formatCurrency(promo.avg_discount_amount)}</td>
                    <td>${promo.discount_percentage}%</td>
                </tr>
            `;
            tbody.append(row);
        });
    } else {
        tbody.append('<tr><td colspan="6" class="text-center">No promotional effectiveness data available</td></tr>');
    }
}

function populatePeakPerformance(peak) {
    if (!peak) return;

    // Best months
    var bestMonthsBody = $('#best_months_table tbody');
    bestMonthsBody.empty();

    if (peak.best_months && peak.best_months.length > 0) {
        peak.best_months.forEach(function(month, index) {
            var row = `
                <tr>
                    <td>${index + 1}</td>
                    <td>${month.month_name}</td>
                    <td>${formatCurrency(month.net_revenue)}</td>
                    <td>${month.transaction_count}</td>
                </tr>
            `;
            bestMonthsBody.append(row);
        });

        // Update overview with best month
        if (peak.best_months[0]) {
            $('#overview_best_month').text(peak.best_months[0].month_name);
        }
    }

    // Store for charts
    window.dayOfWeekData = peak.best_days || [];
}

var chartInstances = {};

function renderCharts(chartData) {
    if (!chartData || typeof Chart === 'undefined') return;
    
    // Destroy existing charts
    Object.values(chartInstances).forEach(chart => {
        if (chart) chart.destroy();
    });
    
    // Render Monthly Trends Chart
    if (chartData.monthly_trend_chart && chartData.monthly_trend_chart.length > 0) {
        renderMonthlyTrendsChart(chartData.monthly_trend_chart);
    }
    
    // Render Seasonal Patterns Chart
    if (chartData.seasonal_chart && chartData.seasonal_chart.length > 0) {
        renderSeasonalChart(chartData.seasonal_chart);
    }
    
    // Render Day of Week Chart
    if (chartData.day_of_week_chart && chartData.day_of_week_chart.length > 0) {
        renderDayOfWeekChart(chartData.day_of_week_chart);
    }
}

function renderMonthlyTrendsChart(data) {
    var ctx = document.getElementById('monthly_trends_chart');
    if (!ctx || !data || !Array.isArray(data)) return;

    chartInstances.monthly = new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: data.map(item => item.label || ''),
            datasets: [{
                label: 'Monthly Performance',
                data: data.map(item => item.value || 0),
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
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
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Value: ' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            }
        }
    });
}

function renderSeasonalChart(data) {
    var ctx = document.getElementById('seasonal_patterns_chart');
    if (!ctx || !data || !Array.isArray(data)) return;

    chartInstances.seasonal = new Chart(ctx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: data.map(item => item.label || ''),
            datasets: [{
                data: data.map(item => item.value || 0),
                backgroundColor: data.map(item => item.color || '#ccc'),
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                            var percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + formatCurrency(context.parsed) + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
}

function renderDayOfWeekChart(data) {
    var ctx = document.getElementById('day_of_week_chart');
    if (!ctx || !data || !Array.isArray(data)) return;

    chartInstances.dayOfWeek = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: data.map(item => item.label || ''),
            datasets: [{
                label: 'Revenue by Day',
                data: data.map(item => item.value || 0),
                backgroundColor: '#28a745',
                borderColor: '#1e7e34',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
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
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Revenue: ' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            }
        }
    });
}

// Export functions
function exportSeasonalData() {
    var locationIds = $('#seasonal_location_id').val();
    var yearRange = $('#year_range').val();
    var analysisType = $('#analysis_type').val();

    // Handle "all" selections
    if (locationIds && locationIds.includes('all')) locationIds = [];

    var params = {
        year_range: yearRange,
        analysis_type: analysisType,
        _token: '{{ csrf_token() }}'
    };

    if (locationIds && locationIds.length) {
        params['location_ids[]'] = locationIds;
    }

    // Use AJAX to download the file properly
    $.ajax({
        url: '/advanced-reports/seasonal-trends/export',
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
            var filename = 'seasonal-trends-report.xlsx';
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

function exportMonthlyTrends() {
    if (!window.monthlyTrendsData || window.monthlyTrendsData.length === 0) {
        toastr.warning('No monthly trends data available to export');
        return;
    }
    
    var wsData = [
        ['Year', 'Month', 'Month Name', 'Transactions', 'Revenue', 'Growth Rate', 'Growth Direction']
    ];
    
    window.monthlyTrendsData.forEach(function(item) {
        wsData.push([
            item.year || '',
            item.month || '',
            item.month_name || '',
            item.transaction_count || '',
            item.analysis_value || '',
            item.growth_rate || '',
            item.growth_direction || ''
        ]);
    });
    
    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.aoa_to_sheet(wsData);
    ws['!cols'] = [{wch: 8}, {wch: 8}, {wch: 15}, {wch: 12}, {wch: 15}, {wch: 12}, {wch: 15}];
    
    XLSX.utils.book_append_sheet(wb, ws, "Monthly Trends");
    XLSX.writeFile(wb, 'Seasonal_Monthly_Trends_' + new Date().toISOString().slice(0, 10) + '.xlsx');
    
    toastr.success('Monthly trends data exported successfully!');
}

function exportSeasonalPatterns() {
    if (!window.seasonalPatternsData || window.seasonalPatternsData.length === 0) {
        toastr.warning('No seasonal patterns data available to export');
        return;
    }
    
    var wsData = [
        ['Season', 'Transactions', 'Revenue', 'Average Transaction Value']
    ];
    
    window.seasonalPatternsData.forEach(function(item) {
        wsData.push([
            item.season || '',
            item.transaction_count || '',
            item.analysis_value || '',
            item.avg_transaction_value || ''
        ]);
    });
    
    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.aoa_to_sheet(wsData);
    ws['!cols'] = [{wch: 10}, {wch: 12}, {wch: 15}, {wch: 18}];
    
    XLSX.utils.book_append_sheet(wb, ws, "Seasonal Patterns");
    XLSX.writeFile(wb, 'Seasonal_Patterns_' + new Date().toISOString().slice(0, 10) + '.xlsx');
    
    toastr.success('Seasonal patterns data exported successfully!');
}

function exportHolidayPerformance() {
    if (!window.holidayPerformanceData || window.holidayPerformanceData.length === 0) {
        toastr.warning('No holiday performance data available to export');
        return;
    }
    
    var wsData = [
        ['Year', 'Holiday', 'Start Date', 'End Date', 'Duration (Days)', 'Transactions', 'Revenue', 'Daily Average']
    ];
    
    window.holidayPerformanceData.forEach(function(item) {
        wsData.push([
            item.year || '',
            item.holiday_name || '',
            item.start_date || '',
            item.end_date || '',
            item.duration_days || '',
            item.transaction_count || '',
            item.analysis_value || '',
            item.daily_average || ''
        ]);
    });
    
    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.aoa_to_sheet(wsData);
    ws['!cols'] = [{wch: 8}, {wch: 20}, {wch: 12}, {wch: 12}, {wch: 12}, {wch: 12}, {wch: 15}, {wch: 15}];
    
    XLSX.utils.book_append_sheet(wb, ws, "Holiday Performance");
    XLSX.writeFile(wb, 'Holiday_Performance_' + new Date().toISOString().slice(0, 10) + '.xlsx');
    
    toastr.success('Holiday performance data exported successfully!');
}

function exportPromotionalData() {
    if (!window.promotionalData || window.promotionalData.length === 0) {
        toastr.warning('No promotional data available to export');
        return;
    }
    
    var wsData = [
        ['Type', 'Transactions', 'Revenue', 'Avg Transaction Value', 'Avg Discount', 'Discount %']
    ];
    
    window.promotionalData.forEach(function(item) {
        wsData.push([
            item.type || '',
            item.transaction_count || '',
            item.net_revenue || '',
            item.avg_transaction_value || '',
            item.avg_discount_amount || '',
            item.discount_percentage || ''
        ]);
    });
    
    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.aoa_to_sheet(wsData);
    ws['!cols'] = [{wch: 18}, {wch: 12}, {wch: 15}, {wch: 18}, {wch: 15}, {wch: 12}];
    
    XLSX.utils.book_append_sheet(wb, ws, "Promotional Effectiveness");
    XLSX.writeFile(wb, 'Promotional_Effectiveness_' + new Date().toISOString().slice(0, 10) + '.xlsx');
    
    toastr.success('Promotional effectiveness data exported successfully!');
}
</script>
@endsection