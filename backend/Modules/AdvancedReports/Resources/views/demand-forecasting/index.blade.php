@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.demand_forecasting_report'))

@section('content')

<!-- Content Header -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('advancedreports::lang.demand_forecasting_report')
        <small class="text-muted">@lang('advancedreports::lang.sales_prediction_subtitle')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">

    <!-- Enhanced Filters Section -->
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', [
            'title' => __('advancedreports::lang.filters'),
            'class' => 'box-primary'
            ])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('df_date_range', __('advancedreports::lang.historical_period')) !!}
                    {!! Form::text('df_date_range', null, ['placeholder' =>
                    __('advancedreports::lang.select_date_range'), 'class' =>
                    'form-control', 'id' => 'df_date_range', 'readonly']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('df_location_id', __('advancedreports::lang.location')) !!}
                    {!! Form::select('df_location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'df_location_id']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('df_category_id', __('advancedreports::lang.category')) !!}
                    {!! Form::select('df_category_id', $categories, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'df_category_id']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="df_forecast_period">@lang('advancedreports::lang.forecast_period')</label>
                    <select name="df_forecast_period" id="df_forecast_period" class="form-control">
                        <option value="3" selected>@lang('advancedreports::lang.3_months')</option>
                        <option value="6">@lang('advancedreports::lang.6_months')</option>
                        <option value="12">@lang('advancedreports::lang.12_months')</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>&nbsp;</label><br>
                    <button type="button" class="btn btn-primary" id="df_filter_btn">
                        <i class="fa fa-filter"></i> @lang('advancedreports::lang.generate_forecast')
                    </button>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-blue"><i class="fa fa-line-chart"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('advancedreports::lang.products_analyzed')</span>
                    <span class="info-box-number" id="df_products_analyzed">0</span>
                    <div class="progress">
                        <div class="progress-bar bg-blue" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="df_prediction_accuracy">0% prediction accuracy</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-shopping-cart"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('advancedreports::lang.forecasted_demand')</span>
                    <span class="info-box-number" id="df_forecasted_demand">0</span>
                    <div class="progress">
                        <div class="progress-bar bg-green" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="df_forecast_period_text">Next 3 months</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-exclamation-triangle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('advancedreports::lang.stockout_alerts')</span>
                    <span class="info-box-number" id="df_critical_alerts">0</span>
                    <div class="progress">
                        <div class="progress-bar bg-yellow" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="df_high_alerts">0 high priority</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-red"><i class="fa fa-refresh"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('advancedreports::lang.reorder_required')</span>
                    <span class="info-box-number" id="df_reorder_required">0</span>
                    <div class="progress">
                        <div class="progress-bar bg-red" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="df_avg_days_supply">0 avg days supply</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock-out Alerts -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-exclamation-triangle"></i> @lang('advancedreports::lang.critical_stockout_alerts')</h3>
                    <div class="box-tools pull-right">
                        <div class="form-group" style="margin-bottom: 0; display: inline-block; margin-right: 10px;">
                            <select id="alert_level_filter" class="form-control input-sm" style="width: 120px;">
                                <option value="">@lang('advancedreports::lang.all_alerts')</option>
                                <option value="Critical">@lang('advancedreports::lang.critical')</option>
                                <option value="High">@lang('advancedreports::lang.high')</option>
                                <option value="Medium">@lang('advancedreports::lang.medium')</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-sm btn-default" id="print_stockout_alerts" title="@lang('advancedreports::lang.print')">
                            <i class="fa fa-print"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="stockout_alerts_table">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.product')</th>
                                    <th>@lang('advancedreports::lang.current_stock')</th>
                                    <th>@lang('advancedreports::lang.daily_demand')</th>
                                    <th>@lang('advancedreports::lang.days_of_supply')</th>
                                    <th>@lang('advancedreports::lang.alert_level')</th>
                                    <th>@lang('advancedreports::lang.estimated_stockout')</th>
                                    <th>@lang('advancedreports::lang.recommended_action')</th>
                                </tr>
                            </thead>
                            <tbody id="stockout_alerts_tbody">
                                <!-- Alerts will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Demand Predictions and Seasonal Patterns -->
    <div class="row">
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-line-chart"></i> @lang('advancedreports::lang.demand_forecast_visualization')</h3>
                    <div class="box-tools pull-right">
                        <div class="form-group" style="margin-bottom: 0; display: inline-block; margin-right: 10px;">
                            <select id="forecast_product_filter" class="form-control input-sm select2"
                                style="width: 200px;">
                                <option value="">@lang('advancedreports::lang.select_product')</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-sm btn-success" id="export_forecast_chart" title="@lang('advancedreports::lang.export_image')">
                            <i class="fa fa-download"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div id="forecast_chart_container">
                        <canvas id="demandForecastChart" height="400"></canvas>
                    </div>
                    <div class="row" style="margin-top: 15px;">
                        <div class="col-md-12">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-default"
                                    id="show_historical">@lang('advancedreports::lang.historical')</button>
                                <button type="button" class="btn btn-sm btn-primary"
                                    id="show_forecast">@lang('advancedreports::lang.forecast')</button>
                                <button type="button" class="btn btn-sm btn-default"
                                    id="show_combined">@lang('advancedreports::lang.combined')</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-calendar"></i> @lang('advancedreports::lang.seasonal_patterns')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="seasonal_toggle">
                            <i class="fa fa-bar-chart"></i> Bar Chart
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <canvas id="seasonalPatternsChart" height="400"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Forecast Methods Comparison -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-bar-chart"></i> @lang('advancedreports::lang.forecasting_methods_comparison')</h3>
                </div>
                <div class="box-body">
                    <canvas id="forecastMethodsChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-pie-chart"></i> @lang('advancedreports::lang.demand_confidence_levels')</h3>
                </div>
                <div class="box-body">
                    <canvas id="confidenceLevelsChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Reorder Optimization -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-refresh"></i> @lang('advancedreports::lang.reorder_point_optimization')</h3>
                    <div class="box-tools pull-right">
                        <div class="form-group" style="margin-bottom: 0; display: inline-block; margin-right: 10px;">
                            <select id="reorder_filter" class="form-control input-sm" style="width: 150px;">
                                <option value="">@lang('advancedreports::lang.all_products')</option>
                                <option value="REORDER NOW">@lang('advancedreports::lang.reorder_now')</option>
                                <option value="MONITOR CLOSELY">@lang('advancedreports::lang.monitor_closely')</option>
                                <option value="OVERSTOCKED">@lang('advancedreports::lang.overstocked')</option>
                                <option value="OPTIMAL">@lang('advancedreports::lang.optimal')</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-sm btn-default" id="print_reorder_optimization" title="@lang('advancedreports::lang.print')">
                            <i class="fa fa-print"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box bg-red">
                                <span class="info-box-icon"><i class="fa fa-shopping-cart"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text" style="color: white;">@lang('advancedreports::lang.reorder_now')</span>
                                    <span class="info-box-number" id="reorder_now_count">0</span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 100%"></div>
                                    </div>
                                    <span class="progress-description">@lang('advancedreports::lang.products')</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-yellow">
                                <span class="info-box-icon"><i class="fa fa-eye"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text" style="color: white;">@lang('advancedreports::lang.monitor_closely')</span>
                                    <span class="info-box-number" id="monitor_count">0</span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 100%"></div>
                                    </div>
                                    <span class="progress-description">@lang('advancedreports::lang.products')</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-blue">
                                <span class="info-box-icon"><i class="fa fa-arrow-up"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text" style="color: white;">@lang('advancedreports::lang.overstocked')</span>
                                    <span class="info-box-number" id="overstocked_count">0</span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 100%"></div>
                                    </div>
                                    <span class="progress-description">@lang('advancedreports::lang.products')</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-green">
                                <span class="info-box-icon"><i class="fa fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text" style="color: white;">@lang('advancedreports::lang.optimal')</span>
                                    <span class="info-box-number" id="optimal_count">0</span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 100%"></div>
                                    </div>
                                    <span class="progress-description">@lang('advancedreports::lang.products')</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.product')</th>
                                    <th>@lang('advancedreports::lang.current_stock')</th>
                                    <th>@lang('advancedreports::lang.reorder_point')</th>
                                    <th>@lang('advancedreports::lang.eoq')</th>
                                    <th>@lang('advancedreports::lang.days_of_supply')</th>
                                    <th>@lang('advancedreports::lang.recommendation')</th>
                                    <th>@lang('advancedreports::lang.priority')</th>
                                </tr>
                            </thead>
                            <tbody id="reorder_optimization_tbody">
                                <!-- Reorder data will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Forecasted Products -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-star"></i> @lang('advancedreports::lang.highest_forecasted_demand')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="print_highest_demand" title="@lang('advancedreports::lang.print')">
                            <i class="fa fa-print"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.product')</th>
                                    <th>@lang('advancedreports::lang.forecast')</th>
                                    <th>@lang('advancedreports::lang.confidence')</th>
                                    <th>@lang('advancedreports::lang.trend')</th>
                                </tr>
                            </thead>
                            <tbody id="top_forecast_tbody">
                                <!-- Top forecasts will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-warning"></i> @lang('advancedreports::lang.most_volatile_demand')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="print_volatile_demand" title="@lang('advancedreports::lang.print')">
                            <i class="fa fa-print"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.product')</th>
                                    <th>@lang('advancedreports::lang.volatility')</th>
                                    <th>@lang('advancedreports::lang.forecast')</th>
                                    <th>@lang('advancedreports::lang.confidence')</th>
                                </tr>
                            </thead>
                            <tbody id="volatile_demand_tbody">
                                <!-- Volatile demand products will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>

@endsection

@section('javascript')
<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        // Initialize date picker
        $('#df_date_range').daterangepicker({
            startDate: moment().subtract(1, 'year'),
            endDate: moment(),
            ranges: {
                'Last 6 Months': [moment().subtract(6, 'months'), moment()],
                '@lang("advancedreports::lang.last_year")': [moment().subtract(1, 'year'), moment()],
                'Last 2 Years': [moment().subtract(2, 'years'), moment()],
                '@lang("advancedreports::lang.this_year")': [moment().startOf('year'), moment().endOf('year')]
            },
            locale: {
                format: 'YYYY-MM-DD'
            }
        });

        // Initialize Select2
        $('.select2').select2();

        // Initialize product filter select2 with enhanced search
        $('#forecast_product_filter').select2({
            placeholder: '@lang("advancedreports::lang.select_product")',
            allowClear: true,
            width: '200px',
            minimumInputLength: 0,
            escapeMarkup: function (markup) { return markup; }
        });

        // Chart variables
        let demandForecastChart, seasonalPatternsChart, forecastMethodsChart, confidenceLevelsChart;
        let currentForecastData = [];

        // Helper function to format numbers with thousand separators
        function formatNumber(num) {
            return parseFloat(num || 0).toLocaleString(undefined, {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            });
        }

        // Load analytics data
        function loadAnalytics() {
            const dateRange = $('#df_date_range').val().split(' - ');
            const startDate = dateRange[0];
            const endDate = dateRange[1];
            const locationId = $('#df_location_id').val() || 'all';
            const categoryId = $('#df_category_id').val() || 'all';
            const forecastPeriod = $('#df_forecast_period').val() || 3;

            $.ajax({
                url: '{{ route("advancedreports.demand-forecasting.analytics") }}',
                type: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    location_id: locationId,
                    category_id: categoryId,
                    forecast_period: forecastPeriod
                },
                success: function(data) {
                    currentForecastData = data.sales_predictions || [];
                    updateSummaryCards(data.summary_cards || {});
                    updateStockoutAlerts(data.stockout_alerts || {});
                    updateSeasonalPatterns(data.seasonal_patterns || {});
                    updateReorderOptimization(data.reorder_optimization || {});
                    updateForecastTables(data.sales_predictions || []);
                    updateProductFilter(data.sales_predictions || []);
                    updateForecastMethodsChart(data.sales_predictions || []);
                    updateConfidenceLevelsChart(data.sales_predictions || []);
                    
                    // Initialize demand forecast chart with first product
                    if (currentForecastData.length > 0) {
                        updateDemandForecastChart(currentForecastData[0]);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading analytics:', error);
                }
            });
        }

        // Update summary cards
        function updateSummaryCards(data) {
            $('#df_products_analyzed').text(formatNumber(data.total_products_analyzed || 0));
            $('#df_prediction_accuracy').text((data.prediction_accuracy || 0) + '% prediction accuracy');
            $('#df_forecasted_demand').text(formatNumber(data.total_forecasted_demand || 0));
            $('#df_forecast_period_text').text('Next ' + (data.forecast_period_months || 3) + ' months');
            $('#df_critical_alerts').text(formatNumber(data.critical_alerts || 0));
            $('#df_high_alerts').text(formatNumber(data.high_alerts || 0) + ' high priority');
            $('#df_reorder_required').text(formatNumber(data.products_needing_reorder || 0));
            $('#df_avg_days_supply').text((data.avg_days_of_supply || 0) + ' avg days supply');
        }

        // Update stock-out alerts
        function updateStockoutAlerts(data) {
            const alerts = Array.isArray(data.critical_alerts) ? data.critical_alerts.concat(data.high_alerts || []) : [];
            
            let html = '';
            alerts.slice(0, 20).forEach((alert) => {
                const alertBadge = getAlertBadge(alert.alert_level);
                const daysSupply = alert.days_of_supply === 999 ? '∞' : Math.round(alert.days_of_supply);
                
                html += `
                    <tr data-alert-level="${alert.alert_level}">
                        <td>
                            <strong>${alert.product_name}</strong><br>
                            <small class="text-muted">${alert.product_sku}</small>
                        </td>
                        <td>${formatNumber(alert.current_stock)}</td>
                        <td>${alert.daily_demand.toFixed(2)}/day</td>
                        <td>${daysSupply} days</td>
                        <td>${alertBadge}</td>
                        <td><small>${alert.estimated_stockout_date}</small></td>
                        <td><small>${alert.recommended_action}</small></td>
                    </tr>
                `;
            });
            
            if (html === '') {
                html = '<tr><td colspan="7" class="text-center">@lang("advancedreports::lang.no_critical_alerts_found")</td></tr>';
            }
            
            $('#stockout_alerts_tbody').html(html);
        }

        function getAlertBadge(level) {
            const colors = {
                'Critical': 'bg-red',
                'High': 'bg-orange', 
                'Medium': 'bg-yellow',
                'Low': 'bg-green'
            };
            return `<span class="badge ${colors[level] || 'bg-gray'}">${level}</span>`;
        }

        // Update seasonal patterns chart
        function updateSeasonalPatterns(data) {
            const seasonalData = Array.isArray(data.seasonal_patterns) ? data.seasonal_patterns : [];
            const labels = seasonalData.map(s => s.season);
            const demands = seasonalData.map(s => s.total_demand);
            const indices = seasonalData.map(s => s.seasonality_index);

            if (seasonalPatternsChart) seasonalPatternsChart.destroy();
            
            const ctx = document.getElementById('seasonalPatternsChart').getContext('2d');
            seasonalPatternsChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: demands,
                        backgroundColor: ['#3498db', '#2ecc71', '#f39c12', '#e74c3c'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const season = seasonalData[context.dataIndex];
                                    return `${context.label}: ${context.parsed} units (Index: ${season.seasonality_index})`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Update reorder optimization
        function updateReorderOptimization(data) {
            const summary = data.recommendations_summary || [];
            
            // Update summary boxes
            const reorderNow = summary.find(s => s.recommendation === 'REORDER NOW') || {count: 0};
            const monitorClosely = summary.find(s => s.recommendation === 'MONITOR CLOSELY') || {count: 0};
            const overstocked = summary.find(s => s.recommendation === 'OVERSTOCKED') || {count: 0};
            const optimal = summary.find(s => s.recommendation === 'OPTIMAL') || {count: 0};
            
            $('#reorder_now_count').text(formatNumber(reorderNow.count));
            $('#monitor_count').text(formatNumber(monitorClosely.count));
            $('#overstocked_count').text(formatNumber(overstocked.count));
            $('#optimal_count').text(formatNumber(optimal.count));
            
            // Update reorder table
            const optimizations = Array.isArray(data.optimizations) ? data.optimizations : [];
            let html = '';
            
            optimizations.slice(0, 25).forEach((opt) => {
                const recommendationBadge = getRecommendationBadge(opt.reorder_recommendation);
                const priorityBadge = getPriorityBadge(opt.priority);
                const daysSupply = opt.days_of_supply === 999 ? '∞' : Math.round(opt.days_of_supply);
                
                html += `
                    <tr data-recommendation="${opt.reorder_recommendation}">
                        <td>
                            <strong>${opt.product_name}</strong><br>
                            <small class="text-muted">${opt.product_sku}</small>
                        </td>
                        <td>${formatNumber(opt.current_stock)}</td>
                        <td>${formatNumber(opt.reorder_point)}</td>
                        <td>${formatNumber(opt.economic_order_qty)}</td>
                        <td>${daysSupply}</td>
                        <td>${recommendationBadge}</td>
                        <td>${priorityBadge}</td>
                    </tr>
                `;
            });
            
            $('#reorder_optimization_tbody').html(html);
        }

        function getRecommendationBadge(recommendation) {
            const colors = {
                'REORDER NOW': 'bg-red',
                'MONITOR CLOSELY': 'bg-yellow',
                'OVERSTOCKED': 'bg-blue',
                'OPTIMAL': 'bg-green'
            };
            return `<span class="badge ${colors[recommendation] || 'bg-gray'}">${recommendation}</span>`;
        }

        function getPriorityBadge(priority) {
            const colors = {
                'High': 'bg-red',
                'Medium': 'bg-yellow',
                'Low': 'bg-green'
            };
            return `<span class="badge ${colors[priority] || 'bg-gray'}">${priority}</span>`;
        }

        // Update forecast tables
        function updateForecastTables(forecastData) {
            // Top forecasted products
            let topHtml = '';
            const topProducts = forecastData.slice(0, 10);
            
            topProducts.forEach((product) => {
                const totalForecast = product.forecasts.combined.reduce((a, b) => a + b, 0);
                const trendBadge = getTrendBadge(product.growth_trend);
                const confidenceBadge = getConfidenceBadge(product.confidence_level);
                
                topHtml += `
                    <tr>
                        <td>
                            <strong>${product.product_name}</strong><br>
                            <small class="text-muted">${product.product_sku}</small>
                        </td>
                        <td>${formatNumber(Math.round(totalForecast))} units</td>
                        <td>${confidenceBadge}</td>
                        <td>${trendBadge}</td>
                    </tr>
                `;
            });
            $('#top_forecast_tbody').html(topHtml);

            // Most volatile demand
            let volatileHtml = '';
            const volatileProducts = forecastData
                .filter(p => p.demand_volatility !== undefined)
                .sort((a, b) => b.demand_volatility - a.demand_volatility)
                .slice(0, 10);
            
            volatileProducts.forEach((product) => {
                const totalForecast = product.forecasts.combined.reduce((a, b) => a + b, 0);
                const confidenceBadge = getConfidenceBadge(product.confidence_level);
                
                volatileHtml += `
                    <tr>
                        <td>
                            <strong>${product.product_name}</strong><br>
                            <small class="text-muted">${product.product_sku}</small>
                        </td>
                        <td><span class="badge bg-red">${product.demand_volatility}</span></td>
                        <td>${formatNumber(Math.round(totalForecast))} units</td>
                        <td>${confidenceBadge}</td>
                    </tr>
                `;
            });
            $('#volatile_demand_tbody').html(volatileHtml);
        }

        function getTrendBadge(trend) {
            const colors = {
                'Growing': 'bg-green',
                'Declining': 'bg-red',
                'Stable': 'bg-blue'
            };
            return `<span class="badge ${colors[trend] || 'bg-gray'}">${trend}</span>`;
        }

        function getConfidenceBadge(confidence) {
            const colors = {
                'High': 'bg-green',
                'Medium': 'bg-yellow',
                'Low': 'bg-red'
            };
            return `<span class="badge ${colors[confidence] || 'bg-gray'}">${confidence}</span>`;
        }

        // Update product filter dropdown
        function updateProductFilter(forecastData) {
            let options = '<option value="">@lang("advancedreports::lang.select_product_option")</option>';
            forecastData.forEach((product) => {
                options += `<option value="${product.product_id}">${product.product_name} (${product.product_sku})</option>`;
            });
            $('#forecast_product_filter').html(options);
        }

        // Update demand forecast chart
        function updateDemandForecastChart(productData, displayMode = 'combined') {
            if (!productData) return;

            const historical = productData.historical_data || [];
            const forecast = productData.forecasts.combined || [];
            const futurePeriods = productData.future_periods || [];

            // Create labels (last 12 months + future months)
            const historicalLabels = [];
            const currentDate = moment();
            for (let i = historical.length - 1; i >= 0; i--) {
                historicalLabels.unshift(currentDate.clone().subtract(i, 'months').format('MMM YY'));
            }
            
            let labels, datasets;

            switch(displayMode) {
                case 'historical':
                    labels = historicalLabels;
                    datasets = [{
                        label: '@lang("advancedreports::lang.historical_demand")',
                        data: historical,
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        fill: false,
                        tension: 0.1
                    }];
                    break;
                case 'forecast':
                    labels = futurePeriods;
                    datasets = [{
                        label: '@lang("advancedreports::lang.forecasted_demand")',
                        data: forecast,
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        fill: false,
                        tension: 0.1,
                        borderDash: [5, 5]
                    }];
                    break;
                default: // combined
                    labels = historicalLabels.concat(futurePeriods);
                    datasets = [{
                        label: '@lang("advancedreports::lang.historical_demand")',
                        data: historical.concat(Array(forecast.length).fill(null)),
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        fill: false,
                        tension: 0.1
                    }, {
                        label: '@lang("advancedreports::lang.forecasted_demand")',
                        data: Array(historical.length).fill(null).concat(forecast),
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        fill: false,
                        tension: 0.1,
                        borderDash: [5, 5]
                    }];
            }

            if (demandForecastChart) demandForecastChart.destroy();

            const ctx = document.getElementById('demandForecastChart').getContext('2d');
            demandForecastChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: `Demand Forecast: ${productData.product_name}`
                        },
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Demand (Units)'
                            }
                        }
                    }
                }
            });
        }

        // Update forecast methods comparison chart
        function updateForecastMethodsChart(forecastData) {
            if (!forecastData.length) return;

            const firstProduct = forecastData[0];
            const methods = ['sma', 'trend', 'exponential', 'seasonal', 'combined'];
            const methodLabels = '@lang("advancedreports::lang.forecasting_methods_labels")'.split(', ');
            const methodData = methods.map(method => {
                const forecast = firstProduct.forecasts[method] || [];
                return forecast.reduce((a, b) => a + b, 0);
            });

            if (forecastMethodsChart) forecastMethodsChart.destroy();

            const ctx = document.getElementById('forecastMethodsChart').getContext('2d');
            forecastMethodsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: methodLabels,
                    datasets: [{
                        label: '@lang("advancedreports::lang.forecasted_demand")',
                        data: methodData,
                        backgroundColor: ['#3498db', '#2ecc71', '#f39c12', '#e74c3c', '#9b59b6'],
                        borderColor: ['#2980b9', '#27ae60', '#e67e22', '#c0392b', '#8e44ad'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: '@lang("advancedreports::lang.forecasting_methods_comparison_title")'
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Update confidence levels chart
        function updateConfidenceLevelsChart(forecastData) {
            const confidenceCounts = forecastData.reduce((acc, product) => {
                acc[product.confidence_level] = (acc[product.confidence_level] || 0) + 1;
                return acc;
            }, {});

            const labels = Object.keys(confidenceCounts);
            const data = Object.values(confidenceCounts);

            if (confidenceLevelsChart) confidenceLevelsChart.destroy();

            const ctx = document.getElementById('confidenceLevelsChart').getContext('2d');
            confidenceLevelsChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: ['#2ecc71', '#f39c12', '#e74c3c'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: '@lang("advancedreports::lang.forecast_confidence_distribution_title")'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Event handlers
        $('#df_filter_btn').click(function() {
            loadAnalytics();
        });

        // Product filter change
        $('#forecast_product_filter').change(function() {
            const productId = $(this).val();
            if (productId) {
                const product = currentForecastData.find(p => p.product_id == productId);
                if (product) {
                    updateDemandForecastChart(product);
                    updateForecastMethodsChart([product]);
                }
            }
        });

        // Chart toggles
        $('#seasonal_toggle').click(function() {
            if (seasonalPatternsChart.config.type === 'doughnut') {
                seasonalPatternsChart.config.type = 'bar';
                $(this).html('<i class="fa fa-pie-chart"></i> Pie Chart');
            } else {
                seasonalPatternsChart.config.type = 'doughnut';
                $(this).html('<i class="fa fa-bar-chart"></i> Bar Chart');
            }
            seasonalPatternsChart.update();
        });

        // Demand forecast chart view toggles
        $('#show_historical').click(function() {
            $(this).removeClass('btn-default').addClass('btn-primary');
            $('#show_forecast, #show_combined').removeClass('btn-primary').addClass('btn-default');
            
            const productId = $('#forecast_product_filter').val();
            if (productId && currentForecastData.length > 0) {
                const product = currentForecastData.find(p => p.product_id == productId);
                if (product) {
                    updateDemandForecastChart(product, 'historical');
                }
            }
        });

        $('#show_forecast').click(function() {
            $(this).removeClass('btn-default').addClass('btn-primary');
            $('#show_historical, #show_combined').removeClass('btn-primary').addClass('btn-default');
            
            const productId = $('#forecast_product_filter').val();
            if (productId && currentForecastData.length > 0) {
                const product = currentForecastData.find(p => p.product_id == productId);
                if (product) {
                    updateDemandForecastChart(product, 'forecast');
                }
            }
        });

        $('#show_combined').click(function() {
            $(this).removeClass('btn-default').addClass('btn-primary');
            $('#show_historical, #show_forecast').removeClass('btn-primary').addClass('btn-default');
            
            const productId = $('#forecast_product_filter').val();
            if (productId && currentForecastData.length > 0) {
                const product = currentForecastData.find(p => p.product_id == productId);
                if (product) {
                    updateDemandForecastChart(product, 'combined');
                }
            }
        });

        // Alert level filter
        $('#alert_level_filter').change(function() {
            const level = $(this).val();
            if (level) {
                $('#stockout_alerts_table tbody tr').hide();
                $('#stockout_alerts_table tbody tr[data-alert-level="' + level + '"]').show();
            } else {
                $('#stockout_alerts_table tbody tr').show();
            }
        });

        // Reorder filter
        $('#reorder_filter').change(function() {
            const recommendation = $(this).val();
            if (recommendation) {
                $('#reorder_optimization_tbody tr').hide();
                $('#reorder_optimization_tbody tr[data-recommendation="' + recommendation + '"]').show();
            } else {
                $('#reorder_optimization_tbody tr').show();
            }
        });

        // Print and Export functionality
        $('#print_stockout_alerts').click(function() {
            printSection('Critical Stock-out Alerts', $('#stockout_alerts_table').closest('.box'));
        });

        $('#print_reorder_optimization').click(function() {
            printSection('Reorder Point Optimization', $('#reorder_filter').closest('.box'));
        });

        $('#print_highest_demand').click(function() {
            printSection('Highest Forecasted Demand', $('#print_highest_demand').closest('.box'));
        });

        $('#print_volatile_demand').click(function() {
            printSection('Most Volatile Demand', $('#print_volatile_demand').closest('.box'));
        });

        $('#export_forecast_chart').click(function() {
            exportChartAsImage();
        });

        // Print section function
        function printSection(title, sectionElement) {
            const printContents = sectionElement.html();
            const originalContents = document.body.innerHTML;
            const printWindow = window.open('', '_blank');
            
            printWindow.document.write(`
                <html>
                <head>
                    <title>${title}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
                        th { background-color: #f2f2f2; font-weight: bold; }
                        .info-box { border: 1px solid #ddd; padding: 15px; margin: 10px 0; }
                        .info-box-text { font-weight: bold; }
                        .info-box-number { font-size: 18px; font-weight: bold; }
                        .progress-description { color: #666; font-size: 12px; }
                        h3 { color: #333; border-bottom: 2px solid #ddd; padding-bottom: 5px; }
                        @media print {
                            body { margin: 0; }
                            .no-print { display: none !important; }
                        }
                    </style>
                </head>
                <body>
                    <h1>${title}</h1>
                    <p>Generated on: ${new Date().toLocaleString()}</p>
                    ${printContents}
                </body>
                </html>
            `);
            
            printWindow.document.close();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 250);
        }

        // Export chart as image function
        function exportChartAsImage() {
            if (!demandForecastChart) {
                alert('No chart available to export. Please select a product first.');
                return;
            }
            
            const canvas = document.getElementById('demandForecastChart');
            const url = canvas.toDataURL('image/png');
            const productName = $('#forecast_product_filter option:selected').text() || 'Product';
            
            const link = document.createElement('a');
            link.download = `demand-forecast-${productName.replace(/[^a-z0-9]/gi, '_').toLowerCase()}.png`;
            link.href = url;
            link.click();
        }

        // Load initial data
        loadAnalytics();
    });
</script>
@endsection