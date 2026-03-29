@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.customer_segmentation_report'))

@section('content')

<!-- Content Header -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ __('advancedreports::lang.customer_segmentation_report')}}
        <small
            class="text-muted">@lang('advancedreports::lang.rfm_analysis_geographic_distribution_vip_identification')</small>
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
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('cs_date_range', __('Date Range:')) !!}
                    {!! Form::text('cs_date_range', null, ['placeholder' => __('Select Date Range'), 'class' =>
                    'form-control', 'id' => 'cs_date_range', 'readonly']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('cs_location_id', __('Location:')) !!}
                    {!! Form::select('cs_location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%',
                    'placeholder' => __('All Locations'), 'id' => 'cs_location_id']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('cs_customer_id', __('Customer:')) !!}
                    {!! Form::select('cs_customer_id', $customers, null, ['class' => 'form-control select2',
                    'style' => 'width:100%',
                    'placeholder' => __('All Customers'), 'id' => 'cs_customer_id']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>&nbsp;</label><br>
                    <button type="button" class="btn btn-primary" id="cs_filter_btn">
                        <i class="fa fa-filter"></i> @lang('Filter')
                    </button>
                    <button type="button" class="btn btn-success" id="export_btn">
                        <i class="fa fa-download"></i> {{ __('lang_v1.export') }}
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
                <span class="info-box-icon bg-blue"><i class="fa fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('advancedreports::lang.total_customers')</span>
                    <span class="info-box-number" id="cs_total_customers">0</span>
                    <div class="progress">
                        <div class="progress-bar bg-blue" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="cs_repeat_rate">0%
                        @lang('advancedreports::lang.repeat_rate')</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-map-marker"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('advancedreports::lang.geographic_reach')</span>
                    <span class="info-box-number" id="cs_unique_cities">0</span>
                    <div class="progress">
                        <div class="progress-bar bg-green" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="cs_unique_states">0
                        @lang('advancedreports::lang.states')</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-dollar"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('advancedreports::lang.avg_order_value')</span>
                    <span class="info-box-number" id="cs_avg_order_value">$0</span>
                    <div class="progress">
                        <div class="progress-bar bg-yellow" style="width: 100%"></div>
                    </div>
                    <span class="progress-description">@lang('advancedreports::lang.per_transaction')</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-red"><i class="fa fa-star"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('advancedreports::lang.total_revenue')</span>
                    <span class="info-box-number" id="cs_total_revenue">$0</span>
                    <div class="progress">
                        <div class="progress-bar bg-red" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="cs_total_transactions">0
                        @lang('advancedreports::lang.transactions')</span>
                </div>
            </div>
        </div>
    </div>

    <!-- RFM Analysis Section -->
    <div class="row">
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-pie-chart"></i>
                        @lang('advancedreports::lang.rfm_customer_segments')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="rfm_segment_toggle">
                            <i class="fa fa-bar-chart"></i> @lang('advancedreports::lang.bar_chart')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <canvas id="rfmSegmentChart" height="400"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-trophy"></i>
                        @lang('advancedreports::lang.vip_tier_distribution')</h3>
                </div>
                <div class="box-body">
                    <canvas id="vipTierChart" height="400"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- RFM Score Distribution -->
    <div class="row">
        <div class="col-md-4">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('advancedreports::lang.recency_distribution')</h3>
                </div>
                <div class="box-body">
                    <canvas id="recencyChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('advancedreports::lang.frequency_distribution')</h3>
                </div>
                <div class="box-body">
                    <canvas id="frequencyChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('advancedreports::lang.monetary_distribution')</h3>
                </div>
                <div class="box-body">
                    <canvas id="monetaryChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Geographic Analysis -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-map"></i>
                        @lang('advancedreports::lang.state_wise_customer_distribution')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="state_chart_toggle">
                            <i class="fa fa-pie-chart"></i> @lang('advancedreports::lang.pie_chart')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <canvas id="stateDistributionChart" height="400"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-building"></i>
                        @lang('advancedreports::lang.city_wise_revenue_distribution')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="city_chart_toggle">
                            <i class="fa fa-pie-chart"></i> @lang('advancedreports::lang.pie_chart')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <canvas id="cityDistributionChart" height="400"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Demographic Analysis -->
    <div class="row">
        <div class="col-md-4">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-birthday-cake"></i>
                        @lang('advancedreports::lang.age_group_distribution')</h3>
                </div>
                <div class="box-body">
                    <canvas id="ageGroupChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-user-circle"></i>
                        @lang('advancedreports::lang.customer_type_distribution')</h3>
                </div>
                <div class="box-body">
                    <canvas id="customerTypeChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-shopping-cart"></i>
                        @lang('advancedreports::lang.purchase_behavior')</h3>
                </div>
                <div class="box-body">
                    <canvas id="purchaseBehaviorChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top VIP Customers Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-crown"></i> @lang('advancedreports::lang.top_vip_customers')
                    </h3>
                    <div class="box-tools pull-right">
                        <div class="form-group" style="margin-bottom: 0;">
                            <select id="vip_tier_filter" class="form-control input-sm" style="width: 150px;">
                                <option value="">@lang('advancedreports::lang.all_tiers')</option>
                                <option value="Platinum">Platinum</option>
                                <option value="Gold">Gold</option>
                                <option value="Silver">Silver</option>
                                <option value="Bronze">Bronze</option>
                                <option value="Standard">Standard</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="vip_customers_table">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.rank')</th>
                                    <th>@lang('advancedreports::lang.customer')</th>
                                    <th>@lang('advancedreports::lang.vip_tier')</th>
                                    <th>@lang('advancedreports::lang.vip_score')</th>
                                    <th>@lang('advancedreports::lang.total_spent')</th>
                                    <th>@lang('advancedreports::lang.transactions')</th>
                                    <th>@lang('advancedreports::lang.avg_order_value')</th>
                                    <th>@lang('advancedreports::lang.last_purchase')</th>
                                </tr>
                            </thead>
                            <tbody id="vip_customers_tbody">
                                <!-- @lang('advancedreports::lang.vip_customers_populated_here') -->
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
        // Currency settings
        const currencySettings = {
            symbol: '{{ session("currency")["symbol"] ?? "$" }}',
            precision: {{ session('business.currency_precision') ?? 2 }},
            placement: '{{ session("business.currency_symbol_placement") ?? "before" }}'
        };

        // Format currency helper
        function formatCurrency(value) {
            const formatted = parseFloat(value).toLocaleString(undefined, {
                minimumFractionDigits: currencySettings.precision,
                maximumFractionDigits: currencySettings.precision
            });
            return currencySettings.placement === 'after' ?
                formatted + currencySettings.symbol :
                currencySettings.symbol + formatted;
        }

        // Initialize date picker
        $('#cs_date_range').daterangepicker({
            startDate: moment().subtract(1, 'year'),
            endDate: moment(),
            ranges: {
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'Last 3 Months': [moment().subtract(3, 'months'), moment()],
                'Last 6 Months': [moment().subtract(6, 'months'), moment()],
                'Last Year': [moment().subtract(1, 'year'), moment()],
                'This Year': [moment().startOf('year'), moment().endOf('year')]
            },
            locale: {
                format: 'YYYY-MM-DD'
            }
        });

        // Initialize Select2
        $('.select2').select2();

        // Chart variables
        let rfmSegmentChart, vipTierChart, recencyChart, frequencyChart, monetaryChart;
        let stateDistributionChart, cityDistributionChart;
        let ageGroupChart, customerTypeChart, purchaseBehaviorChart;

        // Load analytics data
        function loadAnalytics() {
            const dateRange = $('#cs_date_range').val().split(' - ');
            const startDate = dateRange[0];
            const endDate = dateRange[1];
            const locationId = $('#cs_location_id').val() || 'all';
            const customerId = $('#cs_customer_id').val() || 'all';

            $.ajax({
                url: '{{ route("advancedreports.customer-segmentation.analytics") }}',
                type: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    location_id: locationId,
                    customer_id: customerId
                },
                success: function(data) {
                    updateSummaryCards(data.summary_cards);
                    updateRFMAnalysis(data.rfm_analysis);
                    updateGeographicDistribution(data.geographic_distribution);
                    updateDemographicAnalysis(data.demographic_analysis);
                    updateVIPCustomers(data.vip_customers);
                },
                error: function(xhr, status, error) {
                    console.error('Error loading analytics:', error);
                }
            });
        }

        // Update summary cards
        function updateSummaryCards(data) {
            $('#cs_total_customers').text(data.total_customers || 0);
            $('#cs_unique_cities').text(data.unique_cities || 0);
            $('#cs_unique_states').text((data.unique_states || 0) + ' states');
            $('#cs_avg_order_value').text(data.formatted_avg_order_value || '$0');
            $('#cs_total_revenue').text(data.formatted_total_revenue || '$0');
            $('#cs_total_transactions').text((data.total_transactions || 0) + ' transactions');
            $('#cs_repeat_rate').text((data.repeat_rate || 0) + '% repeat rate');
        }

        // Update RFM Analysis
        function updateRFMAnalysis(data) {
            // RFM Segment Chart
            const segmentData = data.segment_distribution || [];
            const segmentLabels = segmentData.map(s => s.segment);
            const segmentCounts = segmentData.map(s => s.count);
            const segmentColors = [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
            ];

            if (rfmSegmentChart) rfmSegmentChart.destroy();

            const ctx1 = document.getElementById('rfmSegmentChart').getContext('2d');
            rfmSegmentChart = new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: segmentLabels,
                    datasets: [{
                        data: segmentCounts,
                        backgroundColor: segmentColors.slice(0, segmentLabels.length),
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
                                    const segment = segmentData[context.dataIndex];
                                    return `${context.label}: ${context.parsed} customers (Avg: ${formatCurrency(segment.avg_value || 0)})`;
                                }
                            }
                        }
                    }
                }
            });

            // RFM Distribution Charts
            updateRFMDistributionCharts(data.rfm_distribution || {});
        }

        function updateRFMDistributionCharts(distribution) {
            // Recency Chart
            if (recencyChart) recencyChart.destroy();
            const recencyCtx = document.getElementById('recencyChart').getContext('2d');
            recencyChart = new Chart(recencyCtx, {
                type: 'bar',
                data: {
                    labels: ['1 (Old)', '2', '3', '4', '5 (Recent)'],
                    datasets: [{
                        data: [
                            distribution.recency_distribution?.[1] || 0,
                            distribution.recency_distribution?.[2] || 0,
                            distribution.recency_distribution?.[3] || 0,
                            distribution.recency_distribution?.[4] || 0,
                            distribution.recency_distribution?.[5] || 0
                        ],
                        backgroundColor: '#28a745',
                        borderColor: '#1e7e34',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });

            // Frequency Chart
            if (frequencyChart) frequencyChart.destroy();
            const frequencyCtx = document.getElementById('frequencyChart').getContext('2d');
            frequencyChart = new Chart(frequencyCtx, {
                type: 'bar',
                data: {
                    labels: ['1 (Low)', '2', '3', '4', '5 (High)'],
                    datasets: [{
                        data: [
                            distribution.frequency_distribution?.[1] || 0,
                            distribution.frequency_distribution?.[2] || 0,
                            distribution.frequency_distribution?.[3] || 0,
                            distribution.frequency_distribution?.[4] || 0,
                            distribution.frequency_distribution?.[5] || 0
                        ],
                        backgroundColor: '#ffc107',
                        borderColor: '#e0a800',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });

            // Monetary Chart
            if (monetaryChart) monetaryChart.destroy();
            const monetaryCtx = document.getElementById('monetaryChart').getContext('2d');
            monetaryChart = new Chart(monetaryCtx, {
                type: 'bar',
                data: {
                    labels: ['1 (Low)', '2', '3', '4', '5 (High)'],
                    datasets: [{
                        data: [
                            distribution.monetary_distribution?.[1] || 0,
                            distribution.monetary_distribution?.[2] || 0,
                            distribution.monetary_distribution?.[3] || 0,
                            distribution.monetary_distribution?.[4] || 0,
                            distribution.monetary_distribution?.[5] || 0
                        ],
                        backgroundColor: '#dc3545',
                        borderColor: '#c82333',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        // Update Geographic Distribution
        function updateGeographicDistribution(data) {
            // State Distribution Chart
            const stateData = (data.state_distribution || []).slice(0, 10);
            const stateLabels = stateData.map(s => s.state);
            const stateCounts = stateData.map(s => s.customer_count);

            if (stateDistributionChart) stateDistributionChart.destroy();
            const stateCtx = document.getElementById('stateDistributionChart').getContext('2d');
            stateDistributionChart = new Chart(stateCtx, {
                type: 'bar',
                data: {
                    labels: stateLabels,
                    datasets: [{
                        label: 'Customers',
                        data: stateCounts,
                        backgroundColor: '#36A2EB',
                        borderColor: '#2E86AB',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } }
                }
            });

            // City Distribution Chart
            const cityData = (data.city_distribution || []).slice(0, 10);
            const cityLabels = cityData.map(c => c.city);
            const cityRevenues = cityData.map(c => parseFloat(c.total_revenue) || 0);

            if (cityDistributionChart) cityDistributionChart.destroy();
            const cityCtx = document.getElementById('cityDistributionChart').getContext('2d');
            cityDistributionChart = new Chart(cityCtx, {
                type: 'bar',
                data: {
                    labels: cityLabels,
                    datasets: [{
                        label: 'Revenue',
                        data: cityRevenues,
                        backgroundColor: '#17a2b8',
                        borderColor: '#138496',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Revenue: ${formatCurrency(context.parsed.y)}`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Update Demographic Analysis
        function updateDemographicAnalysis(data) {
            // Age Group Chart
            const ageData = data.age_distribution || [];
            const ageLabels = ageData.map(a => a.age_group);
            const ageCounts = ageData.map(a => a.customer_count);

            if (ageGroupChart) ageGroupChart.destroy();
            const ageCtx = document.getElementById('ageGroupChart').getContext('2d');
            ageGroupChart = new Chart(ageCtx, {
                type: 'doughnut',
                data: {
                    labels: ageLabels,
                    datasets: [{
                        data: ageCounts,
                        backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });

            // Customer Type Chart
            const typeData = data.customer_type_distribution || [];
            const typeLabels = typeData.map(t => t.customer_type);
            const typeCounts = typeData.map(t => t.customer_count);

            if (customerTypeChart) customerTypeChart.destroy();
            const typeCtx = document.getElementById('customerTypeChart').getContext('2d');
            customerTypeChart = new Chart(typeCtx, {
                type: 'pie',
                data: {
                    labels: typeLabels,
                    datasets: [{
                        data: typeCounts,
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#17a2b8'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });

            // Purchase Behavior Chart
            const behaviorData = data.purchase_patterns || [];
            const behaviorLabels = behaviorData.map(b => b.buyer_type);
            const behaviorCounts = behaviorData.map(b => b.count);

            if (purchaseBehaviorChart) purchaseBehaviorChart.destroy();
            const behaviorCtx = document.getElementById('purchaseBehaviorChart').getContext('2d');
            purchaseBehaviorChart = new Chart(behaviorCtx, {
                type: 'bar',
                data: {
                    labels: behaviorLabels,
                    datasets: [{
                        data: behaviorCounts,
                        backgroundColor: '#6f42c1',
                        borderColor: '#5a2d91',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        // Update VIP Customers
        function updateVIPCustomers(data) {
            // VIP Tier Chart
            const vipData = data.vip_distribution || [];
            const vipLabels = vipData.map(v => v.tier);
            const vipCounts = vipData.map(v => v.count);
            const vipColors = {
                'Platinum': '#E5E4E2',
                'Gold': '#FFD700',
                'Silver': '#C0C0C0',
                'Bronze': '#CD7F32',
                'Standard': '#808080'
            };

            if (vipTierChart) vipTierChart.destroy();
            const vipCtx = document.getElementById('vipTierChart').getContext('2d');
            vipTierChart = new Chart(vipCtx, {
                type: 'doughnut',
                data: {
                    labels: vipLabels,
                    datasets: [{
                        data: vipCounts,
                        backgroundColor: vipLabels.map(label => vipColors[label] || '#808080'),
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const tier = vipData[context.dataIndex];
                                    return `${context.label}: ${context.parsed} customers (Avg Revenue: ${formatCurrency(tier.avg_revenue_per_customer || 0)})`;
                                }
                            }
                        }
                    }
                }
            });

            // Update VIP Customers Table
            updateVIPCustomersTable(data.top_vip_customers || []);
        }

        function updateVIPCustomersTable(customers) {
            let html = '';
            customers.forEach((customer, index) => {
                const tierBadge = getTierBadge(customer.vip_tier);
                const lastPurchase = new Date(customer.last_purchase_date).toLocaleDateString();

                html += `
                    <tr>
                        <td><span class="badge bg-blue">#${index + 1}</span></td>
                        <td>
                            <strong>${customer.customer_name}</strong><br>
                            <small class="text-muted">${customer.customer_mobile}</small>
                        </td>
                        <td>${tierBadge}</td>
                        <td><span class="badge bg-primary">${customer.vip_score}</span></td>
                        <td>${formatCurrency(customer.total_spent || 0)}</td>
                        <td>${customer.total_transactions}</td>
                        <td>${formatCurrency(customer.avg_order_value || 0)}</td>
                        <td>${lastPurchase}</td>
                    </tr>
                `;
            });
            $('#vip_customers_tbody').html(html);
        }

        function getTierBadge(tier) {
            const colors = {
                'Platinum': 'bg-gray',
                'Gold': 'bg-yellow',
                'Silver': 'bg-light-blue',
                'Bronze': 'bg-orange',
                'Standard': 'bg-gray'
            };
            const color = colors[tier] || 'bg-gray';
            return `<span class="badge ${color}">${tier}</span>`;
        }

        // Chart toggle functions
        $('#rfm_segment_toggle').click(function() {
            if (rfmSegmentChart.config.type === 'doughnut') {
                rfmSegmentChart.config.type = 'bar';
                rfmSegmentChart.options.plugins.legend.position = 'top';
                $(this).html('<i class="fa fa-pie-chart"></i> Pie Chart');
            } else {
                rfmSegmentChart.config.type = 'doughnut';
                rfmSegmentChart.options.plugins.legend.position = 'bottom';
                $(this).html('<i class="fa fa-bar-chart"></i> Bar Chart');
            }
            rfmSegmentChart.update();
        });

        $('#state_chart_toggle').click(function() {
            if (stateDistributionChart.config.type === 'bar') {
                stateDistributionChart.config.type = 'pie';
                $(this).html('<i class="fa fa-bar-chart"></i> Bar Chart');
            } else {
                stateDistributionChart.config.type = 'bar';
                $(this).html('<i class="fa fa-pie-chart"></i> Pie Chart');
            }
            stateDistributionChart.update();
        });

        $('#city_chart_toggle').click(function() {
            if (cityDistributionChart.config.type === 'bar') {
                cityDistributionChart.config.type = 'pie';
                $(this).html('<i class="fa fa-bar-chart"></i> Bar Chart');
            } else {
                cityDistributionChart.config.type = 'bar';
                $(this).html('<i class="fa fa-pie-chart"></i> Pie Chart');
            }
            cityDistributionChart.update();
        });

        // Event handlers
        $('#cs_filter_btn').click(function() {
            loadAnalytics();
        });

        // Export button click
        $('#export_btn').click(function(e) {
            e.preventDefault();

            var originalText = $(this).html();
            $(this).html('<i class="fa fa-spinner fa-spin"></i> {{ __("advancedreports::lang.exporting") }}');
            $(this).prop('disabled', true);

            // Prepare parameters
            var params = {
                start_date: $('#cs_date_range').val() ? $('#cs_date_range').val().split(' - ')[0] : '',
                end_date: $('#cs_date_range').val() ? $('#cs_date_range').val().split(' - ')[1] : '',
                location_id: $('#cs_location_id').val(),
                customer_id: $('#cs_customer_id').val(),
                _token: '{{ csrf_token() }}'
            };

            // Generate filename
            var filename = 'customer_segmentation_report_' + new Date().toISOString().slice(0,10) + '.xlsx';

            $.ajax({
                url: '{{ route("advancedreports.customer-segmentation.export") }}',
                type: 'POST',
                data: params,
                xhrFields: {
                    responseType: 'blob'
                },
                success: function(data, status, xhr) {
                    try {
                        var blob = new Blob([data], {
                            type: xhr.getResponseHeader('Content-Type') || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                        });
                        var link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        window.URL.revokeObjectURL(link.href);

                        toastr.success('{{ __("advancedreports::lang.exported_successfully") }}');
                    } catch(e) {
                        console.error('Export failed:', e);
                        toastr.error('Export failed. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Export failed:', error);
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        toastr.error(xhr.responseJSON.error);
                    } else {
                        toastr.error('Export failed. Please try again.');
                    }
                },
                complete: function() {
                    setTimeout(function() {
                        $('#export_btn').html(originalText);
                        $('#export_btn').prop('disabled', false);
                    }, 1000);
                }
            });
        });

        // VIP tier filter
        $('#vip_tier_filter').change(function() {
            // This would filter the table - implement as needed
            console.log('Filter by tier:', $(this).val());
        });

        // Load initial data
        loadAnalytics();
    });
</script>
@endsection