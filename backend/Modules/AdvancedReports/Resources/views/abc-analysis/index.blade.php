@extends('layouts.app')

@section('title', __('advancedreports::lang.abc_analysis'))

@php
// Get currency settings from session
$currency_symbol = session('currency')['symbol'] ?? '$';
$currency_precision = session('business.currency_precision') ?? 2;
$currency_symbol_placement = session('business.currency_symbol_placement') ?? 'before';
@endphp

@section('content')
<section class="content-header">
    <h1>@lang('advancedreports::lang.abc_analysis')</h1>
    <p>@lang('advancedreports::lang.abc_analysis_description')</p>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-chart-bar"></i> @lang('advancedreports::lang.abc_analysis_dashboard')
                    </h3>
                </div>
                <div class="box-body">
                    <!-- Filter Controls -->
                    <div class="row filter-section" style="margin-bottom: 20px;">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.analysis_type'):</label>
                                <select class="form-control" id="analysis_type">
                                    <option value="value">@lang('advancedreports::lang.inventory_value')</option>
                                    <option value="sales">@lang('advancedreports::lang.sales_revenue')</option>
                                    <option value="hybrid">@lang('advancedreports::lang.hybrid_analysis')</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.business_location'):</label>
                                <select class="form-control select2" id="abc_location_id" multiple>
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
                                <select class="form-control select2" id="abc_category_id" multiple>
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
                                <select class="form-control select2" id="abc_brand_id" multiple>
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
                            <button type="button" class="btn btn-primary" id="filter_abc_data">
                                <i class="fa fa-filter"></i> @lang('advancedreports::lang.analyze')
                            </button>
                            <button type="button" class="btn btn-success" id="export_abc_data">
                                <i class="fa fa-download"></i> @lang('advancedreports::lang.export')
                            </button>
                            <span class="loading-indicator" id="abc_loading" style="display: none;">
                                <i class="fa fa-spinner fa-spin"></i> @lang('advancedreports::lang.loading')
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row abc-overview" style="display: none;">
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="overview_a_grade">0</h3>
                    <p>@lang('advancedreports::lang.a_grade_items') <small id="a_grade_percent">(0%)</small></p>
                </div>
                <div class="icon"><i class="fa fa-star"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3 id="overview_b_grade">0</h3>
                    <p>@lang('advancedreports::lang.b_grade_items') <small id="b_grade_percent">(0%)</small></p>
                </div>
                <div class="icon"><i class="fa fa-certificate"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="overview_c_grade">0</h3>
                    <p>@lang('advancedreports::lang.c_grade_items') <small id="c_grade_percent">(0%)</small></p>
                </div>
                <div class="icon"><i class="fa fa-circle-o"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-blue">
                <div class="inner">
                    <h3 id="overview_total_value">
                        @if($currency_symbol_placement === 'after')
                            0{{ $currency_symbol }}
                        @else
                            {{ $currency_symbol }}0
                        @endif
                    </h3>
                    <p>@lang('advancedreports::lang.total_analysis_value')</p>
                </div>
                <div class="icon"><i class="fa fa-money"></i></div>
            </div>
        </div>
    </div>

    <!-- ABC Summary Chart -->
    <div class="row abc-content" style="display: none;">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-bar-chart"></i> @lang('advancedreports::lang.abc_distribution_chart')
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-default chart-toggle" data-chart="distribution" data-current="bar">
                            <i class="fa fa-pie-chart"></i> <span class="toggle-text">@lang('advancedreports::lang.pie_chart')</span>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div id="distribution_bar_view">
                        <canvas id="abc_distribution_chart" height="200"></canvas>
                    </div>
                    <div id="distribution_pie_view" style="display: none;">
                        <canvas id="abc_pie_chart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-line-chart"></i> @lang('advancedreports::lang.pareto_analysis')
                    </h3>
                </div>
                <div class="box-body">
                    <canvas id="pareto_chart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Contribution Analysis -->
    <div class="row abc-content" style="display: none;">
        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-money"></i> @lang('advancedreports::lang.revenue_contribution_analysis')
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-primary" id="export_contribution_excel">
                            <i class="fa fa-file-excel-o"></i> @lang('advancedreports::lang.export_to_excel')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="contribution_table">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.abc_grade')</th>
                                    <th>@lang('advancedreports::lang.item_count')</th>
                                    <th>@lang('advancedreports::lang.total_revenue')</th>
                                    <th>@lang('advancedreports::lang.inventory_value')</th>
                                    <th>@lang('advancedreports::lang.turnover_ratio')</th>
                                    <th>@lang('advancedreports::lang.focus_strategy')</th>
                                    <th>@lang('advancedreports::lang.management_approach')</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resource Allocation Recommendations -->
    <div class="row abc-content" style="display: none;">
        <div class="col-md-12">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-cogs"></i> @lang('advancedreports::lang.resource_allocation_recommendations')
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row" id="resource_recommendations">
                        <!-- Resource allocation cards will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Product Classification Table -->
    <div class="row abc-content" style="display: none;">
        <div class="col-md-12">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-table"></i> @lang('advancedreports::lang.detailed_product_classification')
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-primary" id="export_classification_excel">
                            <i class="fa fa-file-excel-o"></i> @lang('advancedreports::lang.export_to_excel')
                        </button>
                        <div class="btn-group" style="margin-left: 5px;">
                            <button type="button" class="btn btn-xs btn-default" id="filter_grade_all">@lang('advancedreports::lang.all')</button>
                            <button type="button" class="btn btn-xs btn-danger" id="filter_grade_a">A</button>
                            <button type="button" class="btn btn-xs btn-warning" id="filter_grade_b">B</button>
                            <button type="button" class="btn btn-xs btn-success" id="filter_grade_c">C</button>
                        </div>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-striped table-hover table-condensed" id="classification_table">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.rank')</th>
                                    <th>@lang('advancedreports::lang.product')</th>
                                    <th>@lang('advancedreports::lang.variant_title')</th>
                                    <th>@lang('advancedreports::lang.variant_sku')</th>
                                    <th>@lang('advancedreports::lang.category')</th>
                                    <th>@lang('advancedreports::lang.abc_grade')</th>
                                    <th>@lang('advancedreports::lang.ending_quantity')</th>
                                    <th>@lang('advancedreports::lang.total_cost_value')</th>
                                    <th>@lang('advancedreports::lang.total_selling_value')</th>
                                    <th>@lang('advancedreports::lang.cumulative_percent')</th>
                                    <th>@lang('advancedreports::lang.priority')</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.abc-overview, .abc-content {
    display: none !important;
}
.abc-overview.loaded, .abc-content.loaded {
    display: flex !important;
}
.abc-content.loaded {
    display: block !important;
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
.small-box .inner h3, .small-box .inner p {
    color: white !important;
}
.grade-a { 
    background-color: #f8d7da !important; 
    color: #721c24 !important;
    font-weight: bold;
}
.grade-b { 
    background-color: #fff3cd !important; 
    color: #856404 !important;
    font-weight: bold;
}
.grade-c { 
    background-color: #d4edda !important; 
    color: #155724 !important;
    font-weight: bold;
}
.priority-high { color: #dc3545; font-weight: bold; }
.priority-medium { color: #fd7e14; font-weight: bold; }
.priority-low { color: #28a745; font-weight: bold; }
.resource-card {
    border-left: 4px solid #007bff;
    padding: 15px;
    margin-bottom: 15px;
    background: #f8f9fa;
    border-radius: 5px;
}
.resource-card.grade-a { border-left-color: #dc3545; }
.resource-card.grade-b { border-left-color: #fd7e14; }
.resource-card.grade-c { border-left-color: #28a745; }
.action-item {
    padding: 3px 8px;
    margin: 2px;
    background: #e9ecef;
    border-radius: 12px;
    font-size: 11px;
    display: inline-block;
}
</style>
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
    setupEventHandlers();

    // Auto-load data on page ready
    setTimeout(function() {
        loadABCAnalytics();
    }, 500);
});

function initializeSelect2() {
    $('.select2').select2();
    
    // Pre-select "All" options
    $('#abc_location_id').val('all').trigger('change');
    $('#abc_category_id').val('all').trigger('change');
    $('#abc_brand_id').val('all').trigger('change');
    
    // Handle mutual exclusivity for "All" selections
    $('#abc_location_id, #abc_category_id, #abc_brand_id').on('change', function() {
        var $this = $(this);
        var values = $this.val() || [];
        
        if (values.includes('all') && values.length > 1) {
            $this.val('all').trigger('change');
        }
    });
}

function setupEventHandlers() {
    $('#filter_abc_data').click(function() {
        loadABCAnalytics();
    });
    
    $('#export_abc_data').click(function() {
        exportABCData();
    });
    
    $('#export_classification_excel').click(function() {
        exportClassificationTable();
    });
    
    $('#export_contribution_excel').click(function() {
        exportContributionAnalysis();
    });
    
    // Chart toggle functionality
    $('.chart-toggle').click(function() {
        var chart = $(this).data('chart');
        var current = $(this).data('current');
        var newView = current === 'bar' ? 'pie' : 'bar';
        
        $(this).data('current', newView);
        $(this).find('.toggle-text').text(newView === 'bar' ? '@lang('advancedreports::lang.bar_chart')' : '@lang('advancedreports::lang.pie_chart')');
        
        $('#' + chart + '_bar_view').toggle(newView === 'bar');
        $('#' + chart + '_pie_view').toggle(newView === 'pie');
        
        if (newView === 'pie' && typeof Chart !== 'undefined') {
            setTimeout(function() {
                renderPieChart();
            }, 100);
        }
    });
    
    // Grade filter buttons
    $('#filter_grade_all, #filter_grade_a, #filter_grade_b, #filter_grade_c').click(function() {
        var grade = $(this).attr('id').replace('filter_grade_', '').toUpperCase();
        if (grade === 'ALL') grade = '';
        
        filterTableByGrade(grade);
        
        // Update button states
        $('#filter_grade_all, #filter_grade_a, #filter_grade_b, #filter_grade_c').removeClass('active');
        $(this).addClass('active');
    });
}

function loadABCAnalytics() {
    showLoading(true);
    
    var locationIds = $('#abc_location_id').val();
    var categoryIds = $('#abc_category_id').val();
    var brandIds = $('#abc_brand_id').val();
    var analysisType = $('#analysis_type').val();
    
    // Handle "all" selections
    if (locationIds && locationIds.includes('all')) locationIds = [];
    if (categoryIds && categoryIds.includes('all')) categoryIds = [];
    if (brandIds && brandIds.includes('all')) brandIds = [];
    
    $.ajax({
        url: '/advanced-reports/abc-analysis/analytics',
        method: 'GET',
        data: {
            location_ids: locationIds,
            category_ids: categoryIds,
            brand_ids: brandIds,
            analysis_type: analysisType
        },
        success: function(response) {
            console.log('ABC analytics loaded:', response);
            populateOverview(response.abc_summary);
            populateContributionAnalysis(response.revenue_contribution);
            populateResourceRecommendations(response.resource_allocation);
            populateClassificationTable(response.abc_classification);
            renderCharts(response.chart_data);
            showContent();
        },
        error: function(xhr, status, error) {
            console.error('Error loading ABC analytics:', error);
            toastr.error('@lang('advancedreports::lang.failed_to_load_abc_analysis_data')');
        },
        complete: function() {
            showLoading(false);
        }
    });
}

function showLoading(show) {
    if (show) {
        $('#abc_loading').show();
        $('.abc-overview, .abc-content').removeClass('loaded').hide();
    } else {
        $('#abc_loading').hide();
    }
}

function showContent() {
    $('.abc-overview, .abc-content').addClass('loaded').show();
}

function populateOverview(summary) {
    if (!summary || !summary.grade_summary) return;

    $('#overview_total_value').text(formatCurrency(summary.total_value || 0));

    var gradeData = summary.grade_summary;

    // A-grade data with null checking
    var aGrade = gradeData.A || { count: 0, percentage_items: '0.0' };
    $('#overview_a_grade').text(aGrade.count || 0);
    $('#a_grade_percent').text('(' + (aGrade.percentage_items || '0.0') + '%)');

    // B-grade data with null checking
    var bGrade = gradeData.B || { count: 0, percentage_items: '0.0' };
    $('#overview_b_grade').text(bGrade.count || 0);
    $('#b_grade_percent').text('(' + (bGrade.percentage_items || '0.0') + '%)');

    // C-grade data with null checking
    var cGrade = gradeData.C || { count: 0, percentage_items: '0.0' };
    $('#overview_c_grade').text(cGrade.count || 0);
    $('#c_grade_percent').text('(' + (cGrade.percentage_items || '0.0') + '%)');
}

function populateContributionAnalysis(contributions) {
    var tbody = $('#contribution_table tbody');
    tbody.empty();

    // Store data globally for export
    window.contributionData = contributions || [];

    if (contributions && contributions.length > 0) {
        contributions.forEach(function(item) {
            var gradeClass = 'grade-' + item.grade.toLowerCase();
            var row = `
                <tr>
                    <td><span class="${gradeClass}">Grade ${item.grade}</span></td>
                    <td>${item.item_count}</td>
                    <td>${formatCurrency(item.total_revenue)}</td>
                    <td>${formatCurrency(item.total_inventory_value)}</td>
                    <td>${item.average_turnover}</td>
                    <td><small>${item.focus_strategy}</small></td>
                    <td><small>${item.management_approach}</small></td>
                </tr>
            `;
            tbody.append(row);
        });
    } else {
        tbody.append('<tr><td colspan="7" class="text-center">@lang('advancedreports::lang.no_contribution_data_available')</td></tr>');
    }
}

function populateResourceRecommendations(recommendations) {
    var container = $('#resource_recommendations');
    container.empty();
    
    if (recommendations && recommendations.length > 0) {
        recommendations.forEach(function(rec) {
            var gradeClass = 'grade-' + rec.grade.toLowerCase();
            var actions = rec.key_actions.map(action => `<span class="action-item">${action}</span>`).join('');
            
            var card = `
                <div class="col-md-4">
                    <div class="resource-card ${gradeClass}">
                        <h4><strong>Grade ${rec.grade} Management</strong></h4>
                        <table class="table table-condensed table-borderless" style="margin-bottom: 10px;">
                            <tr><td><strong>Monitoring:</strong></td><td>${rec.monitoring_frequency}</td></tr>
                            <tr><td><strong>Safety Stock:</strong></td><td>${rec.safety_stock_level}</td></tr>
                            <tr><td><strong>Procurement:</strong></td><td>${rec.procurement_priority}</td></tr>
                            <tr><td><strong>Storage:</strong></td><td>${rec.storage_location}</td></tr>
                            <tr><td><strong>Review Cycle:</strong></td><td>${rec.review_cycle}</td></tr>
                        </table>
                        <div style="margin-top: 10px;">
                            <strong>Key Actions:</strong><br>
                            ${actions}
                        </div>
                    </div>
                </div>
            `;
            container.append(card);
        });
    } else {
        container.append('<div class="col-md-12"><p class="text-center">@lang('advancedreports::lang.no_recommendations_available')</p></div>');
    }
}

var classificationData = []; // Store for filtering

function populateClassificationTable(classification) {
    classificationData = classification || [];
    window.classificationData = classificationData; // Make globally accessible for export
    renderClassificationTable(classificationData);
}

function renderClassificationTable(data) {
    var tbody = $('#classification_table tbody');
    tbody.empty();

    if (data && data.length > 0) {
        data.forEach(function(item) {
            var gradeClass = 'grade-' + item.abc_grade.toLowerCase();
            var priorityClass = 'priority-' + item.priority.toLowerCase();

            var row = `
                <tr>
                    <td>${item.rank}</td>
                    <td><strong>${item.product_name}</strong></td>
                    <td>${item.variant_title}</td>
                    <td>${item.variant_sku}</td>
                    <td>${item.category}</td>
                    <td><span class="${gradeClass}">${item.abc_grade}</span></td>
                    <td>${item.ending_quantity}</td>
                    <td>${formatCurrency(item.total_cost_value)}</td>
                    <td>${formatCurrency(item.total_selling_value)}</td>
                    <td>${item.cumulative_percentage}%</td>
                    <td><span class="${priorityClass}">${item.priority}</span></td>
                </tr>
            `;
            tbody.append(row);
        });
    } else {
        tbody.append('<tr><td colspan="11" class="text-center">@lang('advancedreports::lang.no_classification_data_available')</td></tr>');
    }
}

function filterTableByGrade(grade) {
    if (grade === '') {
        renderClassificationTable(classificationData);
    } else {
        var filtered = classificationData.filter(item => item.abc_grade === grade);
        renderClassificationTable(filtered);
    }
}

var chartInstances = {};

function renderCharts(chartData) {
    if (!chartData || typeof Chart === 'undefined') return;
    
    // Destroy existing charts
    Object.values(chartInstances).forEach(chart => {
        if (chart) chart.destroy();
    });
    
    // Render ABC Distribution Bar Chart
    if (chartData.bar_chart && chartData.bar_chart.length > 0) {
        renderDistributionChart(chartData.bar_chart);
    }
    
    // Render Pareto Chart
    if (chartData.pareto_chart && chartData.pareto_chart.length > 0) {
        renderParetoChart(chartData.pareto_chart);
    }
    
    // Store pie chart data for toggle
    window.pieChartData = chartData.pie_chart;
}

function renderDistributionChart(data) {
    var ctx = document.getElementById('abc_distribution_chart');
    if (!ctx || !data || !Array.isArray(data)) return;
    
    // Ensure all items have required properties with defaults
    var safeData = data.map(item => ({
        grade: item.grade || 'Unknown',
        count: item.count || 0,
        percentage_value: item.percentage_value || 0
    }));
    
    chartInstances.distribution = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: safeData.map(item => `Grade ${item.grade}`),
            datasets: [{
                label: '@lang('advancedreports::lang.number_of_items')',
                data: safeData.map(item => item.count),
                backgroundColor: safeData.map(item => item.grade === 'A' ? '#d32f2f' : (item.grade === 'B' ? '#f57c00' : '#388e3c')),
                borderColor: safeData.map(item => item.grade === 'A' ? '#b71c1c' : (item.grade === 'B' ? '#ef6c00' : '#2e7d32')),
                borderWidth: 1,
                yAxisID: 'y'
            }, {
                label: '@lang('advancedreports::lang.value_percentage')',
                data: safeData.map(item => item.percentage_value),
                type: 'line',
                borderColor: '#1976d2',
                backgroundColor: 'transparent',
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
                    title: { display: true, text: '@lang('advancedreports::lang.number_of_items')' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: '@lang('advancedreports::lang.value_percentage')' },
                    grid: { drawOnChartArea: false }
                }
            },
            plugins: {
                legend: { display: true },
                title: { display: true, text: '@lang('advancedreports::lang.abc_classification_distribution')' }
            }
        }
    });
}

function renderPieChart() {
    var ctx = document.getElementById('abc_pie_chart');
    if (!ctx || !window.pieChartData || !Array.isArray(window.pieChartData)) return;
    
    if (chartInstances.pie) {
        chartInstances.pie.destroy();
    }
    
    // Ensure all items have required properties with defaults
    var safeData = window.pieChartData.map(item => ({
        label: item.label || 'Unknown',
        value: item.value || 0,
        color: item.color || '#cccccc',
        count: item.count || 0
    }));
    
    chartInstances.pie = new Chart(ctx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: safeData.map(item => item.label),
            datasets: [{
                data: safeData.map(item => item.value),
                backgroundColor: safeData.map(item => item.color),
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                title: { display: true, text: '@lang('advancedreports::lang.abc_value_distribution')' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const item = safeData[context.dataIndex] || { label: 'Unknown', count: 0 };
                            return `${item.label}: ${context.parsed}% (${item.count} items)`;
                        }
                    }
                }
            }
        }
    });
}

function renderParetoChart(data) {
    var ctx = document.getElementById('pareto_chart');
    if (!ctx) return;
    
    // Take top 15 items for readability
    var topItems = data.slice(0, 15);
    
    chartInstances.pareto = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: topItems.map(item => item.product_name.length > 15 ? item.product_name.substring(0, 15) + '...' : item.product_name),
            datasets: [{
                label: '@lang('advancedreports::lang.analysis_value')',
                data: topItems.map(item => parseFloat(item.analysis_value.replace(',', ''))),
                backgroundColor: topItems.map(item => 
                    item.abc_grade === 'A' ? '#d32f2f' : 
                    (item.abc_grade === 'B' ? '#f57c00' : '#388e3c')
                ),
                borderWidth: 1,
                yAxisID: 'y'
            }, {
                label: '@lang('advancedreports::lang.cumulative_percentage')',
                data: topItems.map(item => parseFloat(item.cumulative_percentage)),
                type: 'line',
                borderColor: '#1976d2',
                backgroundColor: 'transparent',
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
                    title: { display: true, text: '@lang('advancedreports::lang.analysis_value')' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: '@lang('advancedreports::lang.cumulative_percentage')' },
                    max: 100,
                    grid: { drawOnChartArea: false }
                }
            },
            plugins: {
                legend: { display: true },
                title: { display: true, text: '@lang('advancedreports::lang.pareto_analysis_top_contributing_products')' }
            }
        }
    });
}

function exportABCData() {
    var locationIds = $('#abc_location_id').val();
    var categoryIds = $('#abc_category_id').val();
    var brandIds = $('#abc_brand_id').val();
    var analysisType = $('#analysis_type').val();

    // Handle "all" selections
    if (locationIds && locationIds.includes('all')) locationIds = [];
    if (categoryIds && categoryIds.includes('all')) categoryIds = [];
    if (brandIds && brandIds.includes('all')) brandIds = [];

    var params = {
        analysis_type: analysisType,
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
        url: '/advanced-reports/abc-analysis/export',
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
            var filename = 'abc-analysis-report.xlsx';
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

function exportClassificationTable() {
    // Check if we have classification data
    if (!window.classificationData || window.classificationData.length === 0) {
        toastr.warning('@lang('advancedreports::lang.no_classification_data_available_to_export')');
        return;
    }
    
    // Get current filter (if any)
    var activeFilter = '';
    if ($('#filter_grade_a').hasClass('active')) activeFilter = 'A';
    else if ($('#filter_grade_b').hasClass('active')) activeFilter = 'B';
    else if ($('#filter_grade_c').hasClass('active')) activeFilter = 'C';
    
    // Filter data if needed
    var dataToExport = window.classificationData;
    if (activeFilter) {
        dataToExport = dataToExport.filter(item => item.abc_grade === activeFilter);
    }
    
    // Create worksheet data
    var wsData = [
        // Header row
        [
            'Rank', 'Product Name', 'Variant Title', 'Variant SKU', 'Category', 'Brand',
            'ABC Grade', 'Priority', 'Ending Quantity', 'Unit Cost', 'Unit Price',
            'Total Cost Value', 'Total Selling Value', 'Sales Quantity', 'Sales Revenue',
            'Analysis Value', 'Individual %', 'Cumulative %'
        ]
    ];
    
    // Add data rows
    dataToExport.forEach(function(item) {
        wsData.push([
            item.rank || '',
            item.product_name || '',
            item.variant_title || '',
            item.variant_sku || '',
            item.category || '',
            item.brand || '',
            item.abc_grade || '',
            item.priority || '',
            item.ending_quantity || '',
            item.unit_cost || '',
            item.unit_price || '',
            item.total_cost_value || '',
            item.total_selling_value || '',
            item.sales_quantity || '',
            item.sales_revenue || '',
            item.analysis_value || '',
            item.individual_percentage || '',
            item.cumulative_percentage || ''
        ]);
    });
    
    // Create workbook and worksheet
    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.aoa_to_sheet(wsData);
    
    // Set column widths
    ws['!cols'] = [
        {wch: 6},   // Rank
        {wch: 25},  // Product Name
        {wch: 20},  // Variant Title
        {wch: 15},  // Variant SKU
        {wch: 15},  // Category
        {wch: 15},  // Brand
        {wch: 8},   // ABC Grade
        {wch: 10},  // Priority
        {wch: 12},  // Ending Quantity
        {wch: 12},  // Unit Cost
        {wch: 12},  // Unit Price
        {wch: 15},  // Total Cost Value
        {wch: 15},  // Total Selling Value
        {wch: 12},  // Sales Quantity
        {wch: 15},  // Sales Revenue
        {wch: 15},  // Analysis Value
        {wch: 12},  // Individual %
        {wch: 12}   // Cumulative %
    ];
    
    // Add worksheet to workbook
    XLSX.utils.book_append_sheet(wb, ws, "Product Classification");
    
    // Generate filename
    var filename = 'ABC_Product_Classification';
    if (activeFilter) {
        filename += '_Grade_' + activeFilter;
    }
    filename += '_' + new Date().toISOString().slice(0, 10) + '.xlsx';
    
    // Save file
    XLSX.writeFile(wb, filename);
    
    toastr.success('@lang('advancedreports::lang.classification_data_exported_successfully')');
}

function exportContributionAnalysis() {
    // Check if we have contribution data
    if (!window.contributionData || window.contributionData.length === 0) {
        toastr.warning('@lang('advancedreports::lang.no_contribution_analysis_data_available_to_export')');
        return;
    }
    
    // Create worksheet data
    var wsData = [
        // Header row
        [
            'ABC Grade', 'Item Count', 'Total Revenue', 'Total Inventory Value', 
            'Average Turnover', 'Focus Strategy', 'Management Approach'
        ]
    ];
    
    // Add data rows
    window.contributionData.forEach(function(item) {
        wsData.push([
            item.grade || '',
            item.item_count || '',
            item.total_revenue || '',
            item.total_inventory_value || '',
            item.average_turnover || '',
            item.focus_strategy || '',
            item.management_approach || ''
        ]);
    });
    
    // Create workbook and worksheet
    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.aoa_to_sheet(wsData);
    
    // Set column widths
    ws['!cols'] = [
        {wch: 10},  // ABC Grade
        {wch: 12},  // Item Count
        {wch: 15},  // Total Revenue
        {wch: 18},  // Total Inventory Value
        {wch: 15},  // Average Turnover
        {wch: 35},  // Focus Strategy
        {wch: 40}   // Management Approach
    ];
    
    // Add worksheet to workbook
    XLSX.utils.book_append_sheet(wb, ws, "Revenue Contribution");
    
    // Generate filename
    var filename = 'ABC_Revenue_Contribution_Analysis_' + new Date().toISOString().slice(0, 10) + '.xlsx';
    
    // Save file
    XLSX.writeFile(wb, filename);
    
    toastr.success('@lang('advancedreports::lang.revenue_contribution_data_exported_successfully')');
}
</script>
@endsection