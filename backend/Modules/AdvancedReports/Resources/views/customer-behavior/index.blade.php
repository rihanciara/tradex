@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.customer_behavior_analytics'))

@section('content')

<!-- Content Header -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ __('advancedreports::lang.customer_behavior_analytics')}}
        <small class="text-muted">@lang('advancedreports::lang.analyze_customer_purchase_patterns')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">

    <!-- Enhanced Filters Section -->
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', [
            'title' => __('Filters'),
            'class' => 'box-primary'
            ])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('cb_date_range', __('Date Range:')) !!}
                    {!! Form::text('cb_date_range', null, ['placeholder' => __('Select Date Range'), 'class' =>
                    'form-control', 'id' => 'cb_date_range', 'readonly']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('cb_location_id', __('Location:')) !!}
                    {!! Form::select('cb_location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%',
                    'placeholder' => __('All Locations'), 'id' => 'cb_location_id']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('cb_customer_id', __('Customer:')) !!}
                    {!! Form::select('cb_customer_id', $customers, null, ['class' => 'form-control select2',
                    'style' => 'width:100%',
                    'placeholder' => __('All Customers'), 'id' => 'cb_customer_id']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('cb_category_id', __('Category:')) !!}
                    {!! Form::select('cb_category_id', $categories, null, ['class' => 'form-control select2',
                    'style' => 'width:100%',
                    'placeholder' => __('All Categories'), 'id' => 'cb_category_id']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <br>
                    <button type="button" class="btn btn-primary" id="cb_filter_btn">{{ __('Filter') }}</button>
                    <button type="button" class="btn btn-success" id="export_btn">
                        <i class="fa fa-download"></i> {{ __('lang_v1.export') }}
                    </button>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row" id="cb_summary_cards" style="display: none;">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-blue"><i class="fa fa-shopping-cart"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('advancedreports::lang.total_transactions')</span>
                    <span class="info-box-number" id="cb_total_transactions">0</span>
                    <div class="progress">
                        <div class="progress-bar bg-blue" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="cb_total_revenue">$0</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('advancedreports::lang.unique_customers')</span>
                    <span class="info-box-number" id="cb_unique_customers">0</span>
                    <div class="progress">
                        <div class="progress-bar bg-green" style="width: 100%"></div>
                    </div>
                    <span class="progress-description">@lang('advancedreports::lang.active_customers')</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-calculator"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('advancedreports::lang.avg_order_value')</span>
                    <span class="info-box-number" id="cb_avg_order_value">$0</span>
                    <div class="progress">
                        <div class="progress-bar bg-yellow" style="width: 100%"></div>
                    </div>
                    <span class="progress-description">@lang('advancedreports::lang.per_transaction')</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-red"><i class="fa fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('advancedreports::lang.peak_hour')</span>
                    <span class="info-box-number" id="cb_peak_hour">--:--</span>
                    <div class="progress">
                        <div class="progress-bar bg-red" style="width: 100%"></div>
                    </div>
                    <span class="progress-description">@lang('advancedreports::lang.busiest_time')</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Patterns Section -->
    <div class="row" id="cb_patterns_section" style="display: none;">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.daily_purchase_patterns') }}</h3>
                    <div class="box-tools pull-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-primary" id="daily_pattern_toggle">
                                <i class="fa fa-bar-chart"></i> @lang('advancedreports::lang.bar_chart')
                            </button>
                        </div>
                    </div>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="daily_patterns_chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.hourly_purchase_distribution') }}</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-success" id="hourly_pattern_toggle">
                            <i class="fa fa-line-chart"></i> @lang('advancedreports::lang.line_chart')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="hourly_patterns_chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Seasonal and Monthly Patterns -->
    <div class="row" id="cb_seasonal_section" style="display: none;">
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.seasonal_purchase_patterns') }}</h3>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 250px;">
                        <canvas id="seasonal_patterns_chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.monthly_trends') }}</h3>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 250px;">
                        <canvas id="monthly_patterns_chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Preferences Section -->
    <div class="row" id="cb_categories_section" style="display: none;">
        <div class="col-md-7">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.category_preferences') }}</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-primary" id="category_chart_toggle">
                            <i class="fa fa-pie-chart"></i> @lang('advancedreports::lang.toggle_view')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 350px;">
                        <canvas id="category_preferences_chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-5">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.category_performance') }}</h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.category')</th>
                                    <th>@lang('advancedreports::lang.revenue_percentage')</th>
                                    <th>@lang('advancedreports::lang.customers')</th>
                                </tr>
                            </thead>
                            <tbody id="category_performance_table">
                                <!-- Populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Value Trends Section -->
    <div class="row" id="cb_order_value_section" style="display: none;">
        <div class="col-md-8">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.average_order_value_trends') }}</h3>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="order_value_trends_chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.customer_value_segments') }}</h3>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="customer_segments_chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Satisfaction Section -->
    <div class="row" id="cb_satisfaction_section" style="display: none;">
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.customer_satisfaction_score') }}</h3>
                </div>
                <div class="box-body text-center">
                    <div class="satisfaction-gauge" style="position: relative; height: 250px;">
                        <canvas id="satisfaction_gauge_chart"></canvas>
                    </div>
                    <p class="lead" id="satisfaction_description">@lang('advancedreports::lang.calculating')</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.satisfaction_metrics') }}</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box info-box-sm">
                                <span class="info-box-icon bg-green"><i class="fa fa-refresh"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">@lang('advancedreports::lang.repeat_customer_rate')</span>
                                    <span class="info-box-number" id="repeat_customer_rate">0%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box info-box-sm">
                                <span class="info-box-icon bg-yellow"><i class="fa fa-undo"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">@lang('advancedreports::lang.return_rate')</span>
                                    <span class="info-box-number" id="return_rate">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box info-box-sm">
                                <span class="info-box-icon bg-blue"><i class="fa fa-heart"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">@lang('advancedreports::lang.customer_retention')</span>
                                    <span class="info-box-number" id="customer_retention">0%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box info-box-sm">
                                <span class="info-box-icon bg-purple"><i class="fa fa-calendar"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">@lang('advancedreports::lang.avg_days_between')</span>
                                    <span class="info-box-number" id="avg_days_between">0</span>
                                </div>
                            </div>
                        </div>
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
        $('#cb_date_range').daterangepicker({
            ranges: {
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 3 Months': [moment().subtract(3, 'months').startOf('month'), moment().subtract(1, 'months').endOf('month')],
                'This Year': [moment().startOf('year'), moment().endOf('year')]
            },
            startDate: moment().subtract(3, 'months'),
            endDate: moment(),
            locale: {
                format: 'YYYY-MM-DD'
            }
        });

        // Chart variables
        let dailyPatternsChart = null;
        let hourlyPatternsChart = null;
        let seasonalPatternsChart = null;
        let monthlyPatternsChart = null;
        let categoryPreferencesChart = null;
        let orderValueTrendsChart = null;
        let customerSegmentsChart = null;
        let satisfactionGaugeChart = null;

        // Load analytics data
        function loadAnalytics() {
            var dateRange = $('#cb_date_range').val();
            var dates = dateRange.split(' - ');
            var start_date = dates[0];
            var end_date = dates[1];
            var location_id = $('#cb_location_id').val();
            var customer_id = $('#cb_customer_id').val();
            var category_id = $('#cb_category_id').val();

            $.ajax({
                url: '{{ route("advancedreports.customer-behavior.analytics") }}',
                method: 'GET',
                data: {
                    start_date: start_date,
                    end_date: end_date,
                    location_id: location_id,
                    customer_id: customer_id,
                    category_id: category_id
                },
                success: function(response) {
                    updateSummaryCards(response.summary_cards);
                    updatePurchasePatterns(response.purchase_patterns);
                    updateCategoryPreferences(response.category_preferences);
                    updateOrderValueTrends(response.order_value_trends);
                    updateSatisfactionMetrics(response.satisfaction_metrics);
                    
                    // Show all sections
                    $('#cb_summary_cards, #cb_patterns_section, #cb_seasonal_section, #cb_categories_section, #cb_order_value_section, #cb_satisfaction_section').show();
                },
                error: function(xhr, status, error) {
                    console.error('Error loading analytics:', error);
                    toastr.error('@lang('advancedreports::lang.failed_to_load_customer_behavior_analytics')');
                }
            });
        }

        function updateSummaryCards(data) {
            $('#cb_total_transactions').text(data.total_transactions);
            $('#cb_unique_customers').text(data.unique_customers);
            $('#cb_avg_order_value').text(data.formatted_avg_order_value);
            $('#cb_peak_hour').text(data.peak_hour);
            $('#cb_total_revenue').text(data.formatted_total_revenue + ' Total Revenue');
            
            // Convert currency
            __currency_convert_recursively($('#cb_summary_cards'));
        }

        function updatePurchasePatterns(patterns) {
            // Daily patterns chart
            if (dailyPatternsChart) dailyPatternsChart.destroy();
            const dailyCtx = document.getElementById('daily_patterns_chart').getContext('2d');
            
            dailyPatternsChart = new Chart(dailyCtx, {
                type: 'bar',
                data: {
                    labels: patterns.daily.map(item => item.day_name),
                    datasets: [{
                        label: '@lang('advancedreports::lang.transactions')',
                        data: patterns.daily.map(item => item.transaction_count),
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }, {
                        label: '@lang('advancedreports::lang.revenue')',
                        data: patterns.daily.map(item => item.total_amount),
                        type: 'line',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        yAxisID: 'y1'
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
                            title: {
                                display: true,
                                text: '@lang('advancedreports::lang.transactions')'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: '@lang('advancedreports::lang.revenue')'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });

            // Hourly patterns chart
            if (hourlyPatternsChart) hourlyPatternsChart.destroy();
            const hourlyCtx = document.getElementById('hourly_patterns_chart').getContext('2d');
            
            hourlyPatternsChart = new Chart(hourlyCtx, {
                type: 'line',
                data: {
                    labels: patterns.hourly.map(item => item.hour_label),
                    datasets: [{
                        label: '@lang('advancedreports::lang.transactions')',
                        data: patterns.hourly.map(item => item.transaction_count),
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: '@lang('advancedreports::lang.transaction_volume_by_hour')'
                        }
                    }
                }
            });

            // Seasonal patterns chart
            if (seasonalPatternsChart) seasonalPatternsChart.destroy();
            const seasonalCtx = document.getElementById('seasonal_patterns_chart').getContext('2d');
            
            seasonalPatternsChart = new Chart(seasonalCtx, {
                type: 'doughnut',
                data: {
                    labels: patterns.seasonal.map(item => item.season),
                    datasets: [{
                        data: patterns.seasonal.map(item => item.total_amount),
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)', 
                            'rgba(255, 205, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)'
                        ]
                    }]
                },
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

            // Monthly patterns chart
            if (monthlyPatternsChart) monthlyPatternsChart.destroy();
            const monthlyCtx = document.getElementById('monthly_patterns_chart').getContext('2d');
            
            monthlyPatternsChart = new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: patterns.monthly.map(item => item.month_name),
                    datasets: [{
                        label: '@lang('advancedreports::lang.customers')',
                        data: patterns.monthly.map(item => item.unique_customers),
                        backgroundColor: 'rgba(153, 102, 255, 0.8)'
                    }, {
                        label: '@lang('advancedreports::lang.transactions')',
                        data: patterns.monthly.map(item => item.transaction_count),
                        backgroundColor: 'rgba(255, 159, 64, 0.8)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: false
                        },
                        y: {
                            stacked: false
                        }
                    }
                }
            });
        }

        function updateCategoryPreferences(categories) {
            // Category preferences chart
            if (categoryPreferencesChart) categoryPreferencesChart.destroy();
            const categoryCtx = document.getElementById('category_preferences_chart').getContext('2d');
            
            const topCategories = categories.slice(0, 8); // Show top 8 categories
            
            categoryPreferencesChart = new Chart(categoryCtx, {
                type: 'pie',
                data: {
                    labels: topCategories.map(item => item.category_name),
                    datasets: [{
                        data: topCategories.map(item => item.total_amount),
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 205, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)',
                            'rgba(255, 159, 64, 0.8)',
                            'rgba(199, 199, 199, 0.8)',
                            'rgba(83, 102, 255, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const category = topCategories[context.dataIndex];
                                    return category.category_name + ': ' + category.amount_percentage + '% (' + __currency_trans_from_en(category.total_amount, true) + ')';
                                }
                            }
                        }
                    }
                }
            });

            // Update category performance table
            let tableHTML = '';
            categories.slice(0, 10).forEach(function(category) {
                tableHTML += '<tr>';
                tableHTML += '<td>' + category.category_name + '</td>';
                tableHTML += '<td><span class="badge bg-blue">' + category.amount_percentage + '%</span></td>';
                tableHTML += '<td>' + category.unique_customers + '</td>';
                tableHTML += '</tr>';
            });
            $('#category_performance_table').html(tableHTML);
        }

        function updateOrderValueTrends(trends) {
            // Order value trends chart
            if (orderValueTrendsChart) orderValueTrendsChart.destroy();
            const trendsCtx = document.getElementById('order_value_trends_chart').getContext('2d');
            
            orderValueTrendsChart = new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: trends.weekly_trends.map(item => moment(item.week_start).format('MMM DD')),
                    datasets: [{
                        label: '@lang('advancedreports::lang.avg_order_value')',
                        data: trends.weekly_trends.map(item => item.avg_order_value),
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: '@lang('advancedreports::lang.transaction_count')',
                        data: trends.weekly_trends.map(item => item.transaction_count),
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        yAxisID: 'y1',
                        tension: 0.4
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
                            title: {
                                display: true,
                                text: '@lang('advancedreports::lang.average_order_value')'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: '@lang('advancedreports::lang.transaction_count')'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });

            // Customer segments chart
            if (customerSegmentsChart) customerSegmentsChart.destroy();
            const segmentsCtx = document.getElementById('customer_segments_chart').getContext('2d');
            
            customerSegmentsChart = new Chart(segmentsCtx, {
                type: 'doughnut',
                data: {
                    labels: ['High Value ($500+)', 'Medium Value ($100-$499)', 'Low Value (<$100)'],
                    datasets: [{
                        data: [
                            trends.customer_segments.segments.high_value,
                            trends.customer_segments.segments.medium_value,
                            trends.customer_segments.segments.low_value
                        ],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(255, 205, 86, 0.8)',
                            'rgba(201, 203, 207, 0.8)'
                        ]
                    }]
                },
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

        function updateSatisfactionMetrics(satisfaction) {
            // Update satisfaction metrics
            $('#repeat_customer_rate').text(satisfaction.repeat_customer_rate + '%');
            $('#return_rate').text(satisfaction.return_rate + '%');
            $('#customer_retention').text(satisfaction.customer_retention + '%');
            $('#avg_days_between').text(Math.round(satisfaction.purchase_frequency.avg_days_between_purchases));

            // Create satisfaction gauge
            if (satisfactionGaugeChart) satisfactionGaugeChart.destroy();
            const gaugeCtx = document.getElementById('satisfaction_gauge_chart').getContext('2d');
            
            const score = satisfaction.satisfaction_score;
            let scoreColor = 'rgba(220, 53, 69, 0.8)'; // Red for low
            let description = 'Needs Improvement';
            
            if (score >= 80) {
                scoreColor = 'rgba(40, 167, 69, 0.8)'; // Green for excellent
                description = 'Excellent Satisfaction';
            } else if (score >= 60) {
                scoreColor = 'rgba(255, 193, 7, 0.8)'; // Yellow for good
                description = 'Good Satisfaction';
            } else if (score >= 40) {
                scoreColor = 'rgba(255, 133, 27, 0.8)'; // Orange for fair
                description = 'Fair Satisfaction';
            }

            satisfactionGaugeChart = new Chart(gaugeCtx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [score, 100 - score],
                        backgroundColor: [scoreColor, 'rgba(233, 236, 239, 0.8)'],
                        borderWidth: 0,
                        circumference: 180,
                        rotation: 270
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '75%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: false
                        }
                    }
                },
                plugins: [{
                    beforeDraw: function(chart) {
                        const width = chart.width;
                        const height = chart.height;
                        const ctx = chart.ctx;
                        ctx.restore();
                        const fontSize = (height / 100).toFixed(2);
                        ctx.font = fontSize + "em sans-serif";
                        ctx.textBaseline = "middle";
                        const text = Math.round(score) + "%";
                        const textX = Math.round((width - ctx.measureText(text).width) / 2);
                        const textY = height / 1.4;
                        ctx.fillText(text, textX, textY);
                        ctx.save();
                    }
                }]
            });

            $('#satisfaction_description').text(description);
        }

        // Event handlers
        $('#cb_filter_btn').click(function() {
            loadAnalytics();
        });

        // Export button click
        $('#export_btn').click(function(e) {
            e.preventDefault();

            var originalText = $(this).html();
            $(this).html('<i class="fa fa-spinner fa-spin"></i> {{ __("advancedreports::lang.exporting") }}');
            $(this).prop('disabled', true);

            // Get parameters
            var params = {
                start_date: $('#cb_date_range').val() ? $('#cb_date_range').val().split(' - ')[0] : '',
                end_date: $('#cb_date_range').val() ? $('#cb_date_range').val().split(' - ')[1] : '',
                location_id: $('#cb_location_id').val(),
                customer_id: $('#cb_customer_id').val(),
                category_id: $('#cb_category_id').val(),
                _token: '{{ csrf_token() }}'
            };

            // Use AJAX to download the file properly
            $.ajax({
                url: '{{ route("advancedreports.customer-behavior.export") }}',
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
                    var filename = 'customer-behavior-analysis.xlsx';
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
                        $('#export_btn').html(originalText);
                        $('#export_btn').prop('disabled', false);
                    }, 1000);
                }
            });
        });

        // Chart toggle buttons
        $('#daily_pattern_toggle').click(function() {
            if (dailyPatternsChart.config.type === 'bar') {
                dailyPatternsChart.config.type = 'line';
                dailyPatternsChart.data.datasets[0].type = 'line';
                $(this).html('<i class="fa fa-bar-chart"></i> @lang('advancedreports::lang.bar_chart')');
            } else {
                dailyPatternsChart.config.type = 'bar';
                dailyPatternsChart.data.datasets[0].type = 'bar';
                $(this).html('<i class="fa fa-line-chart"></i> @lang('advancedreports::lang.line_chart')');
            }
            dailyPatternsChart.update();
        });

        $('#hourly_pattern_toggle').click(function() {
            if (hourlyPatternsChart.config.type === 'line') {
                hourlyPatternsChart.config.type = 'bar';
                $(this).html('<i class="fa fa-line-chart"></i> @lang('advancedreports::lang.line_chart')');
            } else {
                hourlyPatternsChart.config.type = 'line';
                $(this).html('<i class="fa fa-bar-chart"></i> @lang('advancedreports::lang.bar_chart')');
            }
            hourlyPatternsChart.update();
        });

        $('#category_chart_toggle').click(function() {
            if (categoryPreferencesChart.config.type === 'pie') {
                categoryPreferencesChart.config.type = 'bar';
                categoryPreferencesChart.options.plugins.legend.position = 'top';
                $(this).html('<i class="fa fa-pie-chart"></i> Pie Chart');
            } else {
                categoryPreferencesChart.config.type = 'pie';
                categoryPreferencesChart.options.plugins.legend.position = 'right';
                $(this).html('<i class="fa fa-bar-chart"></i> @lang('advancedreports::lang.bar_chart')');
            }
            categoryPreferencesChart.update();
        });

        // Load initial data
        loadAnalytics();
    });
</script>
@endsection