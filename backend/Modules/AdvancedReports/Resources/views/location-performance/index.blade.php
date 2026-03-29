@extends('layouts.app')
@section('title', __('advancedreports::lang.location_performance_report'))

@section('css')
<style>
/* Staff Performance View Toggle Styles */
.staff-performance-container.card-view .staff-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.staff-performance-container.table-view .row {
    display: block;
    width: 100%;
}

.staff-performance-container.table-view .staff-card {
    display: block;
    background: transparent;
    border: 1px solid #ddd;
    border-radius: 0;
    padding: 10px;
    margin-bottom: 5px;
    box-shadow: none;
    width: 100%;
}

.staff-performance-container.table-view .staff-info {
    display: inline-block;
    width: 30%;
    vertical-align: top;
    padding-right: 15px;
}

.staff-performance-container.table-view .staff-metrics {
    display: inline-block;
    width: 68%;
    vertical-align: top;
}

.staff-performance-container.table-view .staff-info h5 {
    margin: 0;
    font-size: 14px;
    font-weight: bold;
}

.staff-performance-container.table-view .table {
    margin: 0;
    font-size: 12px;
}

.staff-performance-container.table-view .table th,
.staff-performance-container.table-view .table td {
    padding: 5px 8px;
}
</style>
@endsection

@section('content')
<section class="content-header">
    <h1>
        <i class="fas fa-store"></i> @lang('advancedreports::lang.location_performance_report')
        <small>@lang('advancedreports::lang.multi_location_analysis')</small>
    </h1>
</section>

<section class="content">
    <!-- Filter Controls -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-filter"></i> @lang('advancedreports::lang.filters')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-primary" id="lp_filter_btn">
                            <i class="fa fa-search"></i> @lang('advancedreports::lang.apply_filters')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.date_range')</label>
                                <input type="text" id="lp_date_range" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.locations')</label>
                                <select id="lp_locations" class="form-control select2" multiple>
                                    <option value="all">@lang('advancedreports::lang.all_locations')</option>
                                    @foreach($business_locations as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.category')</label>
                                <select id="lp_category_id" class="form-control select2">
                                    @foreach($categories as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.compare_with')</label>
                                <select id="lp_compare_period" class="form-control">
                                    <option value="previous_period">@lang('advancedreports::lang.previous_period')</option>
                                    <option value="same_period_last_year">@lang('advancedreports::lang.same_period_last_year')</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Overview -->
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box bg-green">
                <span class="info-box-icon"><i class="fa fa-store"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text" style="color: white;">@lang('advancedreports::lang.total_locations')</span>
                    <span class="info-box-number" id="lp_total_locations">0</span>
                    <div class="progress">
                        <div class="progress-bar bg-green" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="lp_locations_active">0 @lang('advancedreports::lang.active_locations')</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box bg-blue">
                <span class="info-box-icon"><i class="fa fa-chart-line"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text" style="color: white;">@lang('advancedreports::lang.total_sales')</span>
                    <span class="info-box-number" id="lp_total_sales"><span class="display_currency" data-currency_symbol="true">0</span></span>
                    <div class="progress">
                        <div class="progress-bar bg-blue" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="lp_sales_growth">0% @lang('advancedreports::lang.growth')</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box bg-yellow">
                <span class="info-box-icon"><i class="fa fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text" style="color: white;">@lang('advancedreports::lang.total_customers')</span>
                    <span class="info-box-number" id="lp_total_customers">0</span>
                    <div class="progress">
                        <div class="progress-bar bg-yellow" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="lp_customer_growth">0% @lang('advancedreports::lang.growth')</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box bg-red">
                <span class="info-box-icon"><i class="fa fa-percentage"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text" style="color: white;">@lang('advancedreports::lang.avg_profit_margin')</span>
                    <span class="info-box-number" id="lp_avg_margin">0%</span>
                    <div class="progress">
                        <div class="progress-bar bg-red" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="lp_margin_status">@lang('advancedreports::lang.average_performance')</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Location Comparison -->
    <div class="row">
        <div class="col-md-8">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-chart-bar"></i> @lang('advancedreports::lang.location_sales_comparison')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="export_comparison_chart">
                            <i class="fa fa-download"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 400px;">
                        <canvas id="locationComparisonChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-trophy"></i> @lang('advancedreports::lang.top_performers')</h3>
                </div>
                <div class="box-body">
                    <div id="top_performers_list">
                        <!-- Top performing locations will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Benchmarks -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-chart-line"></i> @lang('advancedreports::lang.performance_trends')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="toggle_trend_chart">
                            <i class="fa fa-exchange-alt"></i> @lang('advancedreports::lang.toggle_view')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="performanceTrendsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-bullseye"></i> @lang('advancedreports::lang.benchmark_scores')</h3>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="benchmarkScoresChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Regional Analysis -->
    <div class="row">
        <div class="col-md-7">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-map-marked-alt"></i> @lang('advancedreports::lang.regional_sales_analysis')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="print_regional_analysis">
                            <i class="fa fa-print"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body" id="regional-analysis-section">
                    <div class="row">
                        <div class="col-md-7">
                            <div style="position: relative; height: 300px;">
                                <canvas id="regionalMarketShareChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <h5>@lang('advancedreports::lang.top_regions')</h5>
                            <div class="table-responsive">
                                <table class="table table-condensed">
                                    <thead>
                                        <tr>
                                            <th>@lang('advancedreports::lang.region')</th>
                                            <th>@lang('advancedreports::lang.sales')</th>
                                            <th>@lang('advancedreports::lang.share')</th>
                                        </tr>
                                    </thead>
                                    <tbody id="top_regions_table">
                                        <!-- Top regions will be populated here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-money-bill-wave"></i> @lang('advancedreports::lang.location_profitability')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="export_profitability">
                            <i class="fa fa-image"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="profitabilityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Location Performance Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-table"></i> @lang('advancedreports::lang.detailed_location_performance')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="export_location_data">
                            <i class="fa fa-download"></i> @lang('advancedreports::lang.export')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table id="location_performance_table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.location')</th>
                                    <th>@lang('advancedreports::lang.city')</th>
                                    <th>@lang('advancedreports::lang.sales')</th>
                                    <th>@lang('advancedreports::lang.transactions')</th>
                                    <th>@lang('advancedreports::lang.avg_transaction')</th>
                                    <th>@lang('advancedreports::lang.customers')</th>
                                    <th>@lang('advancedreports::lang.profit_margin')</th>
                                    <th>@lang('advancedreports::lang.performance_score')</th>
                                </tr>
                            </thead>
                            <tbody id="location_performance_tbody">
                                <!-- Location performance data will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff Performance by Location -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-users-cog"></i> @lang('advancedreports::lang.staff_performance_by_location')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="toggle_staff_view">
                            <i class="fa fa-eye"></i> @lang('advancedreports::lang.toggle_view')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div id="staff_performance_container" class="staff-performance-container card-view">
                        <!-- Staff performance will be populated here -->
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
        // Configure toastr to disable sound
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "3000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut",
            "playSound": false
        };
        
        // Initialize date picker
        $('#lp_date_range').daterangepicker({
            startDate: moment().subtract(6, 'months'),
            endDate: moment(),
            ranges: {
                '@lang("advancedreports::lang.last_month")': [moment().subtract(1, 'month'), moment()],
                '@lang("advancedreports::lang.last_3_months")': [moment().subtract(3, 'months'), moment()],
                '@lang("advancedreports::lang.last_6_months")': [moment().subtract(6, 'months'), moment()],
                '@lang("advancedreports::lang.last_year")': [moment().subtract(1, 'year'), moment()],
                '@lang("advancedreports::lang.this_year")': [moment().startOf('year'), moment().endOf('year')]
            },
            locale: {
                format: 'YYYY-MM-DD'
            }
        });

        // Initialize Select2
        $('.select2').select2();
        
        // Initialize locations filter with 'All' selected by default
        $('#lp_locations').val(['all']).trigger('change');
        
        // Handle 'All' selection for locations
        $('#lp_locations').on('change', function() {
            const selectedValues = $(this).val() || [];
            
            if (selectedValues.includes('all')) {
                if (selectedValues.length > 1) {
                    // If 'all' is selected along with other options, keep only 'all'
                    $(this).val(['all']).trigger('change.select2');
                }
            }
        });

        // Chart variables
        let comparisonChart, trendsChart, benchmarkChart, regionalChart, profitabilityChart;

        // Load analytics data
        function loadAnalytics() {
            const dateRange = $('#lp_date_range').val().split(' - ');
            const startDate = dateRange[0];
            const endDate = dateRange[1];
            let locationIds = $('#lp_locations').val() || [];
            
            // If 'all' is selected, use empty array (backend will handle all locations)
            if (locationIds.includes('all')) {
                locationIds = [];
            }
            const categoryId = $('#lp_category_id').val() || 'all';
            const comparePeriod = $('#lp_compare_period').val() || 'previous_period';

            $.ajax({
                url: '{{ route("advancedreports.location-performance.analytics") }}',
                type: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    location_ids: locationIds,
                    category_id: categoryId,
                    compare_period: comparePeriod
                },
                success: function(data) {
                    updateOverviewCards(data.location_comparison.totals, data.location_comparison.locations);
                    updateLocationComparisonChart(data.location_comparison.locations);
                    updateTopPerformers(data.location_comparison.locations);
                    updatePerformanceTrendsChart(data.performance_trends);
                    updateBenchmarkScores(data.performance_benchmarks);
                    updateRegionalAnalysis(data.regional_sales);
                    updateProfitabilityChart(data.location_profitability);
                    updateLocationPerformanceTable(data.location_comparison.locations, data.performance_benchmarks);
                    updateStaffPerformance(data.staff_performance);
                },
                error: function(xhr, status, error) {
                    console.error('Error loading analytics:', error);
                    toastr.error('@lang("advancedreports::lang.error_loading_data")');
                }
            });
        }

        // Update overview cards
        function updateOverviewCards(totals, locations) {
            // Count total locations and active locations (locations with sales data)
            const totalLocations = locations ? locations.length : 0;
            const activeLocations = locations ? locations.filter(location => location.total_sales > 0).length : 0;
            
            $('#lp_total_locations').text(totalLocations);
            $('#lp_locations_active').text(activeLocations + ' @lang("advancedreports::lang.active_locations")');
            
            $('#lp_total_sales').html('<span class="display_currency" data-currency_symbol="true">' + (totals.total_sales || 0) + '</span>');
            $('#lp_total_customers').text(totals.total_customers || 0);
            $('#lp_avg_margin').text((totals.avg_profit_margin || 0).toFixed(1) + '%');
            
            // Trigger currency conversion
            __currency_convert_recursively($('#lp_total_sales'));
        }

        // Update location comparison chart
        function updateLocationComparisonChart(locations) {
            const labels = locations.map(l => l.location_name);
            const salesData = locations.map(l => l.total_sales);
            const profitData = locations.map(l => l.gross_profit);

            if (comparisonChart) comparisonChart.destroy();

            const ctx = document.getElementById('locationComparisonChart').getContext('2d');
            comparisonChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '@lang("advancedreports::lang.sales")',
                        data: salesData,
                        backgroundColor: '#3498db',
                        borderColor: '#2980b9',
                        borderWidth: 1,
                        yAxisID: 'y'
                    }, {
                        label: '@lang("advancedreports::lang.gross_profit")',
                        data: profitData,
                        backgroundColor: '#27ae60',
                        borderColor: '#229954',
                        borderWidth: 1,
                        yAxisID: 'y'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });
        }

        // Update top performers
        function updateTopPerformers(locations) {
            const topLocations = locations.slice(0, 5);
            let html = '';
            
            topLocations.forEach((location, index) => {
                const rankClass = index === 0 ? 'text-warning' : (index < 3 ? 'text-info' : 'text-muted');
                const trophy = index === 0 ? 'fa-trophy' : (index < 3 ? 'fa-medal' : 'fa-star');
                
                html += `
                    <div class="callout callout-info">
                        <div class="row">
                            <div class="col-xs-8">
                                <h5><i class="fa ${trophy} ${rankClass}"></i> ${location.location_name}</h5>
                                <p><small>${location.city || ''}</small></p>
                            </div>
                            <div class="col-xs-4 text-right">
                                <h4><span class="display_currency" data-currency_symbol="true">${location.total_sales}</span></h4>
                                <p><small>${location.profit_margin.toFixed(1)}% @lang('advancedreports::lang.margin')</small></p>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            $('#top_performers_list').html(html);
            __currency_convert_recursively($('#top_performers_list'));
        }

        // Update performance trends chart
        function updatePerformanceTrendsChart(trendsData) {
            if (!trendsData || Object.keys(trendsData).length === 0) return;

            const datasets = [];
            const colors = ['#3498db', '#e74c3c', '#f39c12', '#27ae60', '#9b59b6'];
            let colorIndex = 0;

            Object.keys(trendsData).forEach(locationId => {
                const locationData = trendsData[locationId];
                const color = colors[colorIndex % colors.length];
                
                datasets.push({
                    label: locationData.location_name,
                    data: locationData.trends.map(t => t.sales),
                    borderColor: color,
                    backgroundColor: color + '20',
                    borderWidth: 2,
                    fill: false
                });
                colorIndex++;
            });

            const labels = Object.values(trendsData)[0]?.trends.map(t => t.period) || [];

            if (trendsChart) trendsChart.destroy();

            const ctx = document.getElementById('performanceTrendsChart').getContext('2d');
            trendsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
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

        // Update benchmark scores
        function updateBenchmarkScores(benchmarkData) {
            if (!benchmarkData.benchmarks) return;

            const locations = Object.keys(benchmarkData.benchmarks);
            const scores = locations.map(id => benchmarkData.benchmarks[id].performance_score);
            const locationNames = locations.map(id => benchmarkData.benchmarks[id].location_name);

            if (benchmarkChart) benchmarkChart.destroy();

            const ctx = document.getElementById('benchmarkScoresChart').getContext('2d');
            benchmarkChart = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: locationNames,
                    datasets: [{
                        label: '@lang("advancedreports::lang.performance_score")',
                        data: scores,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgba(54, 162, 235, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        // Update regional analysis
        function updateRegionalAnalysis(regionalData) {
            if (!regionalData.market_share) return;

            const marketShareData = regionalData.market_share.slice(0, 10);
            const labels = marketShareData.map(r => r.region);
            const shares = marketShareData.map(r => r.market_share);

            if (regionalChart) regionalChart.destroy();

            const ctx = document.getElementById('regionalMarketShareChart').getContext('2d');
            regionalChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: shares,
                        backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'right'
                        }
                    }
                }
            });

            // Update top regions table
            let tableHtml = '';
            marketShareData.slice(0, 5).forEach(region => {
                tableHtml += `
                    <tr>
                        <td>${region.region}</td>
                        <td><span class="display_currency" data-currency_symbol="true">${region.sales}</span></td>
                        <td>${region.market_share.toFixed(1)}%</td>
                    </tr>
                `;
            });
            $('#top_regions_table').html(tableHtml);
            __currency_convert_recursively($('#top_regions_table'));
        }

        // Update profitability chart
        function updateProfitabilityChart(profitabilityData) {
            if (!profitabilityData.location_profitability) return;

            const locations = profitabilityData.location_profitability.slice(0, 10);
            const labels = locations.map(l => l.location_name);
            const netProfits = locations.map(l => l.net_profit);
            const margins = locations.map(l => l.net_profit_margin);

            if (profitabilityChart) profitabilityChart.destroy();

            const ctx = document.getElementById('profitabilityChart').getContext('2d');
            profitabilityChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '@lang("advancedreports::lang.net_profit")',
                        data: netProfits,
                        backgroundColor: netProfits.map(profit => profit >= 0 ? '#27ae60' : '#e74c3c'),
                        borderColor: netProfits.map(profit => profit >= 0 ? '#229954' : '#c0392b'),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
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

        // Update location performance table
        function updateLocationPerformanceTable(locations, benchmarkData) {
            let html = '';
            
            locations.forEach(location => {
                const benchmark = benchmarkData.benchmarks[location.location_id];
                const performanceScore = benchmark ? benchmark.performance_score : 0;
                const scoreClass = performanceScore >= 80 ? 'success' : (performanceScore >= 60 ? 'warning' : 'danger');
                
                html += `
                    <tr>
                        <td><strong>${location.location_name}</strong></td>
                        <td>${location.city || '-'}</td>
                        <td><span class="display_currency" data-currency_symbol="true">${location.total_sales}</span></td>
                        <td>${location.total_transactions.toLocaleString()}</td>
                        <td><span class="display_currency" data-currency_symbol="true">${location.avg_transaction_value}</span></td>
                        <td>${location.unique_customers.toLocaleString()}</td>
                        <td><span class="label label-${location.profit_margin >= 15 ? 'success' : (location.profit_margin >= 10 ? 'warning' : 'danger')}">${location.profit_margin.toFixed(1)}%</span></td>
                        <td><span class="label label-${scoreClass}">${performanceScore.toFixed(1)}</span></td>
                    </tr>
                `;
            });
            
            $('#location_performance_tbody').html(html);
            __currency_convert_recursively($('#location_performance_tbody'));
        }

        // Update staff performance
        function updateStaffPerformance(staffData) {
            if (!staffData || Object.keys(staffData).length === 0) {
                $('#staff_performance_container').html('<p class="text-muted">@lang("advancedreports::lang.no_staff_data")</p>');
                return;
            }

            let html = '';
            Object.keys(staffData).forEach(locationId => {
                const locationData = staffData[locationId];
                const topStaff = locationData.staff.slice(0, 5);

                // Card view structure
                html += `
                    <div class="col-md-6 staff-card">
                        <div class="staff-info">
                            <h5><i class="fa fa-store"></i> ${locationData.location_name}</h5>
                        </div>
                        <div class="staff-metrics">
                            <div class="table-responsive">
                                <table class="table table-condensed table-bordered">
                                    <thead>
                                        <tr>
                                            <th>@lang("advancedreports::lang.staff_name")</th>
                                            <th>@lang("advancedreports::lang.sales")</th>
                                            <th>@lang("advancedreports::lang.amount")</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                `;

                topStaff.forEach(staff => {
                    html += `
                        <tr>
                            <td>${staff.staff_name}</td>
                            <td>${staff.total_sales}</td>
                            <td><span class="display_currency" data-currency_symbol="true">${staff.total_amount}</span></td>
                        </tr>
                    `;
                });

                html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            });

            $('#staff_performance_container').html('<div class="row">' + html + '</div>');
            __currency_convert_recursively($('#staff_performance_container'));
        }

        // Event handlers
        $('#lp_filter_btn').click(function() {
            loadAnalytics();
        });

        $('#export_comparison_chart').click(function() {
            exportChartAsImage(comparisonChart, 'location-comparison-chart');
        });

        $('#export_location_data').click(function() {
            // Export location performance data as CSV
            exportLocationDataAsCSV();
        });

        // Toggle view buttons
        $('#toggle_trend_chart').click(function() {
            // Toggle between different chart views for trends
            toggleTrendChartView();
        });

        $('#toggle_staff_view').click(function() {
            // Toggle staff performance view
            toggleStaffView();
        });

        // Export buttons  
        $('#export_profitability').click(function() {
            exportChartAsImage(profitabilityChart, 'location-profitability-chart');
        });

        // Print button
        $('#print_regional_analysis').click(function() {
            printRegionalAnalysis();
        });

        // Export functions
        function exportChartAsImage(chart, filename) {
            if (!chart) {
                toastr.error('@lang("advancedreports::lang.no_chart_available")');
                return;
            }
            
            const url = chart.canvas.toDataURL('image/png');
            const link = document.createElement('a');
            link.download = `${filename}.png`;
            link.href = url;
            link.click();
        }

        function exportLocationData() {
            const dateRange = $('#lp_date_range').val().split(' - ');
            const startDate = dateRange[0];
            const endDate = dateRange[1];
            let locationIds = $('#lp_locations').val() || [];
            
            // If 'all' is selected, use empty array for export
            if (locationIds.includes('all')) {
                locationIds = [];
            }
            
            window.open('{{ route("advancedreports.location-performance.analytics") }}' + 
                       '?export=csv&start_date=' + startDate + '&end_date=' + endDate + 
                       '&location_ids=' + locationIds.join(','));
        }

        // Toggle chart view for trends
        function toggleTrendChartView() {
            if (trendsChart) {
                // Toggle between line and bar chart
                const currentType = trendsChart.config.type;
                const newType = currentType === 'line' ? 'bar' : 'line';
                
                trendsChart.config.type = newType;
                trendsChart.update();
                
                const chartTypeTranslated = newType === 'line' ? '@lang("advancedreports::lang.line")' : '@lang("advancedreports::lang.bar")';
                toastr.success('@lang("advancedreports::lang.chart_view_toggled_to") ' + chartTypeTranslated + ' @lang("advancedreports::lang.chart")');
            } else {
                toastr.error('@lang("advancedreports::lang.no_chart_available")');
            }
        }

        // Toggle staff view
        function toggleStaffView() {
            const staffContainer = $('#staff_performance_container');
            if (staffContainer.length) {
                const currentView = staffContainer.hasClass('table-view') ? 'table' : 'card';
                
                if (currentView === 'table') {
                    staffContainer.removeClass('table-view').addClass('card-view');
                    toastr.success('@lang("advancedreports::lang.staff_view_switched_to_card_layout")');
                } else {
                    staffContainer.removeClass('card-view').addClass('table-view');
                    toastr.success('@lang("advancedreports::lang.staff_view_switched_to_table_layout")');
                }
            } else {
                toastr.error('@lang("advancedreports::lang.staff_performance_container_not_found")');
            }
        }

        // Print regional analysis
        function printRegionalAnalysis() {
            const regionalSection = $('#regional-analysis-section');
            if (regionalSection.length) {
                const printWindow = window.open('', '_blank');
                const printContent = `
                    <html>
                    <head>
                        <title>@lang('advancedreports::lang.regional_sales_analysis') - {{ config('app.name') }}</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                            .section { margin-bottom: 30px; }
                            .chart-container { text-align: center; margin: 20px 0; }
                            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                            th { background-color: #f2f2f2; }
                            .summary-box { background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin: 10px 0; }
                            @media print { .no-print { display: none; } }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h1>@lang('advancedreports::lang.regional_sales_analysis_report')</h1>
                            <p>@lang('advancedreports::lang.generated_on') ${new Date().toLocaleDateString()} @lang('advancedreports::lang.at') ${new Date().toLocaleTimeString()}</p>
                            <p>{{ config('app.name') }}</p>
                        </div>
                        ${regionalSection.html()}
                    </body>
                    </html>
                `;
                
                printWindow.document.write(printContent);
                printWindow.document.close();
                printWindow.print();
            } else {
                toastr.error('@lang("advancedreports::lang.regional_analysis_data_not_available_for_printing")');
            }
        }

        // Fix export location data function to handle CSV properly
        function exportLocationDataAsCSV() {
            const dateRange = $('#lp_date_range').val().split(' - ');
            const startDate = dateRange[0];
            const endDate = dateRange[1];
            let locationIds = $('#lp_locations').val() || [];
            
            // If 'all' is selected, use empty array for export
            if (locationIds.includes('all')) {
                locationIds = [];
            }
            
            // Get the data and convert to CSV
            $.ajax({
                url: '{{ route("advancedreports.location-performance.analytics") }}',
                type: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    location_ids: locationIds,
                    category_id: $('#lp_category_id').val(),
                    compare_period: $('#lp_compare_period').val()
                },
                success: function(data) {
                    // Convert location comparison data to CSV
                    let csvContent = "@lang('advancedreports::lang.csv_header')\\n";
                    
                    if (data.location_comparison && data.location_comparison.locations) {
                        data.location_comparison.locations.forEach(location => {
                            csvContent += `${location.location_id},"${location.location_name}","${location.city}","${location.state}",${location.total_sales},${location.total_transactions},${location.avg_transaction_value},${location.unique_customers},${location.gross_profit},${location.profit_margin}%\\n`;
                        });
                    }
                    
                    // Create and download CSV file
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const link = document.createElement('a');
                    const url = URL.createObjectURL(blob);
                    link.setAttribute('href', url);
                    link.setAttribute('download', `location-performance-${startDate}-to-${endDate}.csv`);
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    toastr.success('@lang("advancedreports::lang.location_performance_data_exported_successfully")');
                },
                error: function() {
                    toastr.error('@lang("advancedreports::lang.error_exporting_location_data")');
                }
            });
        }

        // Convert initial currency values
        __currency_convert_recursively($(document));
        
        // Load initial data
        loadAnalytics();
    });
</script>
@endsection