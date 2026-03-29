@extends('advancedreports::layouts.app')
@section('title', __('Customer Lifetime Value Report'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>{{ __('Customer Lifetime Value Report') }}
        <small>{{ __('Analyze customer value, segmentation, and churn prediction') }}</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> {{ __('Level') }}</a></li>
        <li class="active">{{ __('Customer Lifetime Value Report') }}</li>
    </ol>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', [
            'title' => __('Filters'),
            'class' => 'box-primary'
            ])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('clv_date_range', __('Date Range:')) !!}
                    {!! Form::text('clv_date_range', null, ['placeholder' => __('Select Date Range'), 'class' =>
                    'form-control', 'id' => 'clv_date_range', 'readonly']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('clv_location_id', __('Location:')) !!}
                    {!! Form::select('clv_location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%',
                    'placeholder' => __('All Locations'), 'id' => 'clv_location_id']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('clv_customer_group_id', __('Customer Group:')) !!}
                    {!! Form::select('clv_customer_group_id', [], null, ['class' => 'form-control select2',
                    'style' => 'width:100%',
                    'placeholder' => __('All Groups'), 'id' => 'clv_customer_group_id']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <br>
                    <button type="button" class="btn btn-primary" id="clv_filter_btn">{{ __('Filter') }}</button>
                    <button type="button" class="btn btn-success" id="clv_export_btn">{{ __('Export') }}</button>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row" id="clv_summary_cards" style="display: none;">
        <div class="col-lg-3 col-sm-6 col-xs-12">
            <div class="small-box bg-blue" style="margin-bottom: 10px;">
                <div class="inner" style="padding: 10px;">
                    <h3 class="text-bold text-white" id="total_customers" style="font-size: 24px; margin: 5px 0;">0</h3>
                    <p style="font-size: 13px; margin: 0;">{{ __('Total Customers') }}</p>
                </div>
                <div class="icon" style="font-size: 50px; top: 10px; right: 10px;">
                    <i class="fa fa-users"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6 col-xs-12">
            <div class="small-box bg-green" style="margin-bottom: 10px;">
                <div class="inner" style="padding: 10px;">
                    <h3 class="text-bold text-white" id="total_revenue" style="font-size: 24px; margin: 5px 0;">0</h3>
                    <p style="font-size: 13px; margin: 0;">{{ __('Total Revenue') }}</p>
                </div>
                <div class="icon" style="font-size: 50px; top: 10px; right: 10px;">
                    <i class="fa fa-money-bill"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6 col-xs-12">
            <div class="small-box bg-yellow" style="margin-bottom: 10px;">
                <div class="inner" style="padding: 10px;">
                    <h3 class="text-bold text-white" id="avg_clv" style="font-size: 24px; margin: 5px 0;">0</h3>
                    <p style="font-size: 13px; margin: 0;">{{ __('Average CLV') }}</p>
                </div>
                <div class="icon" style="font-size: 50px; top: 10px; right: 10px;">
                    <i class="fa fa-line-chart"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6 col-xs-12">
            <div class="small-box bg-red" style="margin-bottom: 10px;">
                <div class="inner" style="padding: 10px;">
                    <h3 class="text-bold text-white" id="at_risk_customers" style="font-size: 24px; margin: 5px 0;">0</h3>
                    <p style="font-size: 13px; margin: 0;">{{ __('At Risk Customers') }}</p>
                </div>
                <div class="icon" style="font-size: 50px; top: 10px; right: 10px;">
                    <i class="fa fa-warning"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Dashboard -->
    <div class="row" id="clv_analytics_section" style="display: none;">
        <!-- Customer Segmentation Chart -->
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Customer Value Segmentation') }}</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="segmentation_chart_toggle">
                            <i class="fa fa-bar-chart"></i> Toggle Chart
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="segmentationChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Frequency Chart -->
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Purchase Frequency Analysis') }}</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="frequency_chart_toggle">
                            <i class="fa fa-pie-chart"></i> Toggle Chart
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="frequencyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Retention & Churn Analysis -->
    <div class="row" id="clv_retention_section" style="display: none;">
        <div class="col-md-12">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Customer Retention Metrics') }}</h3>
                </div>
                <div class="box-body">
                    <!-- First Row: Basic Retention Metrics -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-blue"><i class="fa fa-calendar"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">{{ __('Avg Lifetime (Days)') }}</span>
                                    <span class="info-box-number" id="avg_lifetime_days">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-green"><i class="fa fa-refresh"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">{{ __('Repeat Rate') }}</span>
                                    <span class="info-box-number" id="repeat_rate">0%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-yellow"><i class="fa fa-shopping-cart"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">{{ __('Avg Purchases') }}</span>
                                    <span class="info-box-number" id="avg_purchases_per_customer">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-purple"><i class="fa fa-line-chart"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">{{ __('Growth Rate') }}</span>
                                    <span class="info-box-number" id="customer_growth_rate">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Second Row: New vs Returning Customers -->
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-aqua"><i class="fa fa-user-plus"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">{{ __('New Customers (30d)') }}</span>
                                    <span class="info-box-number" id="new_customers_30d">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-orange"><i class="fa fa-user-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">{{ __('Returning (30d)') }}</span>
                                    <span class="info-box-number" id="returning_customers_30d">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-teal"><i class="fa fa-trophy"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">{{ __('3M Retention Rate') }}</span>
                                    <span class="info-box-number" id="retention_rate_3m">0%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-maroon"><i class="fa fa-medal"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">{{ __('6M Retention Rate') }}</span>
                                    <span class="info-box-number" id="retention_rate_6m">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Churn Risk Analysis Section -->
    <div class="row" id="clv_churn_section" style="display: none;">
        <div class="col-md-6">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Churn Risk Analysis') }}</h3>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 200px;">
                        <canvas id="churnChart"></canvas>
                    </div>
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-xs-12">
                            <div class="progress-group">
                                <span class="progress-text">{{ __('High Risk') }}</span>
                                <span class="float-right" id="high_risk_count">0</span>
                                <div class="progress progress-sm">
                                    <div class="progress-bar progress-bar-danger" id="high_risk_progress"
                                        style="width: 0%"></div>
                                </div>
                            </div>
                            <div class="progress-group">
                                <span class="progress-text">{{ __('Medium Risk') }}</span>
                                <span class="float-right" id="medium_risk_count">0</span>
                                <div class="progress progress-sm">
                                    <div class="progress-bar progress-bar-warning" id="medium_risk_progress"
                                        style="width: 0%"></div>
                                </div>
                            </div>
                            <div class="progress-group">
                                <span class="progress-text">{{ __('Low Risk') }}</span>
                                <span class="float-right" id="low_risk_count">0</span>
                                <div class="progress progress-sm">
                                    <div class="progress-bar progress-bar-success" id="low_risk_progress"
                                        style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Loyalty Distribution') }}</h3>
                </div>
                <div class="box-body">
                    <div id="loyalty_distribution">
                        <!-- Loyalty distribution progress bars will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Loyalty Trends Section -->
    <div class="row" id="clv_loyalty_section" style="display: none;">
        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Customer Loyalty Trends') }}</h3>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="loyaltyTrendsChart"></canvas>
                    </div>
                    <div class="row" style="margin-top: 15px;">
                        <div class="col-md-3">
                            <div class="info-box info-box-sm">
                                <span class="info-box-icon bg-green"><i class="fa fa-heart"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">{{ __('Loyalty Rate') }}</span>
                                    <span class="info-box-number" id="loyalty_rate">0%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box info-box-sm">
                                <span class="info-box-icon bg-blue"><i class="fa fa-star"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">{{ __('High Value Loyal') }}</span>
                                    <span class="info-box-number" id="high_value_loyal">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box info-box-sm">
                                <span class="info-box-icon bg-purple"><i class="fa fa-refresh"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">{{ __('Avg Frequency') }}</span>
                                    <span class="info-box-number" id="avg_frequency_loyal">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box info-box-sm">
                                <span class="info-box-icon bg-orange"><i class="fa fa-calendar"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">{{ __('Current Month') }}</span>
                                    <span class="info-box-number" id="current_month_score">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Customers -->
    <div class="row" id="clv_top_customers_section" style="display: none;">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Top 10 Customers by CLV') }}</h3>
                </div>
                <div class="box-body">
                    <div id="top_customers_cards"
                        style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between;">
                        <!-- Top customers will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Segmentation Table -->
    <div class="row" id="clv_table_section" style="display: none;">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Customer Segmentation Details') }}</h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="customer_segmentation_table">
                            <thead>
                                <tr>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Segment') }}</th>
                                    <th>{{ __('Total Spent') }}</th>
                                    <th>{{ __('Purchase Frequency') }}</th>
                                    <th>{{ __('CLV Score') }}</th>
                                    <th>{{ __('Last Purchase') }}</th>
                                    <th>{{ __('Risk Level') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Include necessary scripts -->
@endsection

@section('javascript')
<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
    // Initialize charts
    var segmentationChart, frequencyChart, churnChart;
    
    // Date range picker
    $('#clv_date_range').daterangepicker(
        dateRangeSettings,
        function(start, end) {
            $('#clv_date_range').val(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
        }
    );

    // Initialize DataTable
    var segmentation_table = $('#customer_segmentation_table').DataTable({
        processing: true,
        serverSide: true,
        scrollY: "75vh",
        scrollX: true,
        scrollCollapse: true,
        ajax: {
            url: "{{ action('\\Modules\\AdvancedReports\\Http\\Controllers\\CustomerLifetimeValueController@getCustomerSegmentationData') }}",
            data: function(d) {
                var date_range = $('#clv_date_range').val();
                if (date_range) {
                    var dates = date_range.split(' ~ ');
                    d.start_date = dates[0];
                    d.end_date = dates[1];
                }
                d.location_id = $('#clv_location_id').val();
                d.customer_group_id = $('#clv_customer_group_id').val();
            }
        },
        columns: [
            {data: 'customer_name', name: 'customer_name'},
            {data: 'segment', name: 'segment'},
            {data: 'formatted_total_spent', name: 'total_spent'},
            {data: 'purchase_frequency', name: 'purchase_frequency'},
            {data: 'formatted_clv', name: 'clv_score'},
            {data: 'last_purchase_date', name: 'last_purchase'},
            {data: 'risk_level', name: 'risk_level'}
        ],
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#customer_segmentation_table'));
        },
        createdRow: function(row, data, dataIndex) {
            $(row).find('td:eq(1)').addClass('segment-' + data.segment.replace(/\s+/g, '-').toLowerCase());
        }
    });

    // Filter button
    $('#clv_filter_btn').click(function() {
        segmentation_table.ajax.reload();
        loadAnalyticsData();
    });

    // Export button
    $('#clv_export_btn').click(function() {
        var date_range = $('#clv_date_range').val();
        var location_id = $('#clv_location_id').val();
        var customer_group_id = $('#clv_customer_group_id').val();
        
        var data = {};
        if (date_range) {
            var dates = date_range.split(' ~ ');
            data.start_date = dates[0];
            data.end_date = dates[1];
        }
        if (location_id) data.location_id = location_id;
        if (customer_group_id) data.customer_group_id = customer_group_id;
        
        // Create a form and submit it as POST
        var form = $('<form>', {
            'method': 'POST',
            'action': "{{ action('\\Modules\\AdvancedReports\\Http\\Controllers\\CustomerLifetimeValueController@export') }}",
            'target': '_blank'
        });
        
        // Add CSRF token
        form.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': '{{ csrf_token() }}'
        }));
        
        // Add data fields
        $.each(data, function(key, value) {
            if (value) {
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': key,
                    'value': value
                }));
            }
        });
        
        // Submit the form
        form.appendTo('body').submit().remove();
    });

    // Chart toggle buttons
    $('#segmentation_chart_toggle').click(function() {
        if (segmentationChart.config.type === 'pie') {
            segmentationChart.config.type = 'bar';
            segmentationChart.update();
        } else {
            segmentationChart.config.type = 'pie';
            segmentationChart.update();
        }
    });

    $('#frequency_chart_toggle').click(function() {
        if (frequencyChart.config.type === 'doughnut') {
            frequencyChart.config.type = 'bar';
            frequencyChart.update();
        } else {
            frequencyChart.config.type = 'doughnut';
            frequencyChart.update();
        }
    });

    // Load analytics data
    function loadAnalyticsData() {
        var date_range = $('#clv_date_range').val();
        var location_id = $('#clv_location_id').val();
        var customer_group_id = $('#clv_customer_group_id').val();
        
        var data = {};
        if (date_range) {
            var dates = date_range.split(' ~ ');
            data.start_date = dates[0];
            data.end_date = dates[1];
        }
        data.location_id = location_id;
        data.customer_group_id = customer_group_id;

        $.ajax({
            url: "{{ action('\\Modules\\AdvancedReports\\Http\\Controllers\\CustomerLifetimeValueController@getCustomerLifetimeValueData') }}",
            type: 'GET',
            data: data,
            success: function(response) {
                updateSummaryCards(response.summary);
                updateSegmentationChart(response.segmentation);
                updateFrequencyChart(response.frequency_analysis);
                updateRetentionMetrics(response.retention_metrics, response.frequency_analysis);
                updateChurnChart(response.churn_prediction);
                updateLoyaltyTrends(response.loyalty_trends);
                updateTopCustomers(response.top_customers);
                
                // Show all sections
                $('#clv_summary_cards, #clv_analytics_section, #clv_retention_section, #clv_churn_section, #clv_loyalty_section, #clv_top_customers_section, #clv_table_section').show();
            },
            error: function(xhr, status, error) {
                console.error('Error loading analytics data:', error);
                toastr.error('Error loading analytics data');
            }
        });
    }

    function updateSummaryCards(summary) {
        $('#total_customers').text(summary.total_customers);
        $('#total_revenue').html('<span class="display_currency" data-currency_symbol="true">' + summary.total_revenue + '</span>');
        $('#avg_clv').html('<span class="display_currency" data-currency_symbol="true">' + summary.avg_clv + '</span>');
        $('#at_risk_customers').text(summary.at_risk_customers);
        
        __currency_convert_recursively($('#clv_summary_cards'));
    }

    function updateSegmentationChart(segmentation) {
        var ctx = document.getElementById('segmentationChart').getContext('2d');
        
        if (segmentationChart) {
            segmentationChart.destroy();
        }

        var labels = Object.keys(segmentation);
        var data = Object.values(segmentation);
        var colors = [
            '#28a745', '#17a2b8', '#007bff', '#6f42c1', 
            '#fd7e14', '#ffc107', '#dc3545', '#6c757d',
            '#20c997', '#e83e8c', '#f8f9fa'
        ];

        segmentationChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderColor: colors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom',
                    labels: {
                        fontSize: 10,
                        usePointStyle: true
                    }
                },
                plugins: {
                    legend: {
                        display: true
                    }
                }
            }
        });
    }

    function updateFrequencyChart(frequency_analysis) {
        var ctx = document.getElementById('frequencyChart').getContext('2d');
        
        if (frequencyChart) {
            frequencyChart.destroy();
        }

        var labels = Object.keys(frequency_analysis.segments);
        var data = Object.values(frequency_analysis.segments);
        var colors = ['#dc3545', '#ffc107', '#28a745', '#007bff', '#6f42c1'];

        frequencyChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderColor: colors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom',
                    labels: {
                        fontSize: 10,
                        usePointStyle: true
                    }
                }
            }
        });
    }

    function updateRetentionMetrics(metrics, frequencyAnalysis) {
        // First Row - Basic Metrics
        $('#avg_lifetime_days').text(metrics.avg_customer_lifetime_days);
        $('#repeat_rate').text((frequencyAnalysis.repeat_rate || 0) + '%');
        $('#avg_purchases_per_customer').text(metrics.avg_purchases_per_customer);
        $('#customer_growth_rate').text((metrics.customer_growth_rate || 0) + '%');
        
        // Second Row - Advanced Metrics
        $('#new_customers_30d').text(metrics.new_customers_30d || 0);
        $('#returning_customers_30d').text(metrics.returning_customers_30d || 0);
        $('#retention_rate_3m').text((metrics.retention_rate_3m || 0) + '%');
        $('#retention_rate_6m').text((metrics.retention_rate_6m || 0) + '%');
    }

    function updateChurnChart(churn) {
        var ctx = document.getElementById('churnChart').getContext('2d');
        
        if (churnChart) {
            churnChart.destroy();
        }

        churnChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Low Risk', 'Medium Risk', 'High Risk'],
                datasets: [{
                    data: [churn.active, churn.low_risk, churn.medium_risk, churn.high_risk],
                    backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545'],
                    borderColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    display: false
                }
            }
        });

        // Update progress bars
        var total = churn.total_customers;
        if (total > 0) {
            $('#high_risk_count').text(churn.high_risk);
            $('#medium_risk_count').text(churn.medium_risk);
            $('#low_risk_count').text(churn.low_risk);
            
            $('#high_risk_progress').css('width', ((churn.high_risk / total) * 100) + '%');
            $('#medium_risk_progress').css('width', ((churn.medium_risk / total) * 100) + '%');
            $('#low_risk_progress').css('width', ((churn.low_risk / total) * 100) + '%');
        }
    }

    function updateTopCustomers(customers) {
        var html = '';
        // 4 cards per row on large screens (25% each), 2 on small screens (50% each)
        var cardWidth = 'calc(25% - 8px)';
        
        customers.forEach(function(customer, index) {
            var bgClass = ['bg-blue', 'bg-green', 'bg-yellow', 'bg-red', 'bg-purple', 
                          'bg-maroon', 'bg-navy', 'bg-teal', 'bg-olive', 'bg-lime'][index] || 'bg-gray';
            var icon = ['fa-crown', 'fa-trophy', 'fa-medal', 'fa-star', 'fa-thumbs-up',
                       'fa-heart', 'fa-diamond', 'fa-gem', 'fa-award', 'fa-bookmark'][index] || 'fa-user';
            
            html += '<div class="customer-card" style="flex: 0 1 ' + cardWidth + '; min-width: 200px; margin-bottom: 10px;">';
            html += '  <div class="info-box" style="margin: 0;">';
            html += '    <span class="info-box-icon ' + bgClass + '"><i class="fa ' + icon + '"></i></span>';
            html += '    <div class="info-box-content">';
            html += '      <span class="info-box-text" style="font-size: 11px;">' + (customer.customer_name || 'Walk-in') + '</span>';
            html += '      <span class="info-box-number" style="font-size: 14px;"><span class="display_currency" data-currency_symbol="true">' + customer.clv_score + '</span></span>';
            html += '      <div class="progress" style="height: 3px;">';
            html += '        <div class="progress-bar" style="width: ' + (100 - index * 5) + '%; height: 3px;"></div>';
            html += '      </div>';
            html += '      <span class="progress-description" style="font-size: 10px;">';
            html += '        <span class="badge bg-light-blue">#' + (index + 1) + '</span> ' + customer.purchase_frequency + ' purchases';
            html += '      </span>';
            html += '    </div>';
            html += '  </div>';
            html += '</div>';
        });
        
        // Add responsive styles for Top Customers cards
        html += '<style>';
        html += '@media (max-width: 992px) {';
        html += '  .customer-card { flex: 0 1 calc(50% - 8px) !important; min-width: 180px !important; }';
        html += '}';
        html += '@media (max-width: 576px) {';
        html += '  .customer-card { flex: 0 1 100% !important; min-width: 160px !important; }';
        html += '}';
        html += '</style>';
        
        $('#top_customers_cards').html(html);
        __currency_convert_recursively($('#top_customers_cards'));
    }

    // Customer Loyalty Trends Chart
    let loyaltyTrendsChart = null;

    function updateLoyaltyTrends(loyaltyData) {
        // Update loyalty metrics
        const metrics = loyaltyData.metrics;
        $('#loyalty_rate').text(metrics.loyalty_rate + '%');
        $('#high_value_loyal').text(metrics.high_value_loyal);
        $('#avg_frequency_loyal').text(metrics.avg_frequency_loyal);
        $('#current_month_score').text(metrics.current_month_score + '%');

        // Update loyalty distribution progress bars
        if (loyaltyData.distribution && loyaltyData.distribution.length > 0) {
            const totalCustomers = metrics.total_customers;
            let progressHtml = '';
            
            loyaltyData.distribution.forEach(function(segment) {
                if (segment.count > 0) {
                    const percentage = totalCustomers > 0 ? Math.round((segment.count / totalCustomers) * 100) : 0;
                    const segmentClass = segment.segment.toLowerCase().replace(/\s+/g, '-');
                    
                    progressHtml += '<div class="progress-group">';
                    progressHtml += '  <span class="progress-text">' + segment.segment + '</span>';
                    progressHtml += '  <span class="float-right"><b>' + segment.count + '</b>/' + totalCustomers + '</span>';
                    progressHtml += '  <div class="progress progress-sm">';
                    progressHtml += '    <div class="progress-bar segment-' + segmentClass + '" style="width: ' + percentage + '%"></div>';
                    progressHtml += '  </div>';
                    progressHtml += '</div>';
                }
            });
            $('#loyalty_distribution').html(progressHtml);
        }

        // Update loyalty trends chart
        if (loyaltyData.trends_data && loyaltyData.trends_data.length > 0) {
            const ctx = document.getElementById('loyaltyTrendsChart').getContext('2d');
            
            if (loyaltyTrendsChart) {
                loyaltyTrendsChart.destroy();
            }

            const labels = loyaltyData.trends_data.map(item => item.month);
            const loyaltyScores = loyaltyData.trends_data.map(item => item.loyalty_score);
            const activeCustomers = loyaltyData.trends_data.map(item => item.active_customers);

            loyaltyTrendsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Loyalty Score (%)',
                        data: loyaltyScores,
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y'
                    }, {
                        label: 'Active Customers',
                        data: activeCustomers,
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Customer Loyalty Trends (6 Months)'
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Loyalty Score (%)'
                            },
                            min: 0,
                            max: 100
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Active Customers'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        }
    }

    // Load initial data
    loadAnalyticsData();
});
</script>

<!-- Additional CSS for segments -->
<style>
    .segment-champions {
        background-color: #d4edda !important;
    }

    .segment-loyal-customers {
        background-color: #cfe2ff !important;
    }

    .segment-potential-loyalists {
        background-color: #fff3cd !important;
    }

    .segment-new-customers {
        background-color: #e7f3ff !important;
    }

    .segment-promising {
        background-color: #f0e6ff !important;
    }

    .segment-need-attention {
        background-color: #ffe6cc !important;
    }

    .segment-about-to-sleep {
        background-color: #fff0e6 !important;
    }

    .segment-at-risk {
        background-color: #ffe6e6 !important;
    }

    .segment-cannot-lose-them {
        background-color: #ffcccc !important;
    }

    .segment-hibernating {
        background-color: #f5f5f5 !important;
    }

    .segment-lost {
        background-color: #e6e6e6 !important;
    }

    .customer-card {
        transition: transform 0.2s ease-in-out;
    }

    .customer-card:hover {
        transform: translateY(-2px);
    }

    .progress-group {
        margin-bottom: 15px;
    }

    .float-right {
        float: right;
    }
</style>
@endsection