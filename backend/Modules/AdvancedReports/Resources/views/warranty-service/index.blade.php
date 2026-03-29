@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.warranty_service_report'))

@section('css')
<link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.css" />
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css">
<style>
    .info-box-content {
        padding: 5px 10px;
    }

    .warranty-status-active {
        color: #28a745;
    }

    .warranty-status-expiring {
        color: #ffc107;
    }

    .warranty-status-expired {
        color: #dc3545;
    }

    .warranty-status-claimed {
        color: #17a2b8;
    }

    /* Clean Select2 styling to match standard form controls */
    .select2-container--default .select2-selection--single {
        background-color: #fff;
        border: 1px solid #d2d6de;
        border-radius: 0;
        height: 34px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #555;
        line-height: 32px;
        padding-left: 12px;
        padding-right: 20px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px;
        position: absolute;
        top: 1px;
        right: 1px;
        width: 20px;
    }

    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #3c8dbc;
        outline: 0;
    }
</style>
@endsection

@section('content')

<!-- Content Header -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ __('advancedreports::lang.warranty_service_report')}}
        <small class="text-muted">{{ __('advancedreports::lang.warranty_service_description') ?: __('Track warranties,
            service requests, and customer support metrics') }}</small>
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
                    {!! Form::label('warranty_date_filter', __('Date Range') . ':') !!}
                    {!! Form::text('warranty_date_filter', null, ['class' => 'form-control', 'id' =>
                    'warranty_date_filter', 'placeholder' => __('Select Date Range'), 'readonly']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('customer_filter', __('Customer') . ':') !!}
                    {!! Form::select('customer_filter', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('All Customers'), 'id' => 'customer_filter']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('warranty_status_filter', __('Warranty Status') . ':') !!}
                    {!! Form::select('warranty_status_filter', $warranty_statuses, null, ['class' => 'form-control', 'id' => 'warranty_status_filter']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <br>
                    <button type="button" id="refresh-warranty-data" class="btn btn-primary">{{ __('Filter') }}</button>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row">
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3 id="total-products-sold">0</h3>
                    <p>{{ __('advancedreports::lang.total_products_sold') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="warranty-coverage"><span id="warranty-coverage-value">0</span>%</h3>
                    <p>{{ __('advancedreports::lang.warranty_coverage') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3 id="service-requests">0</h3>
                    <p>{{ __('advancedreports::lang.service_requests') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-tools"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="resolution-rate"><span id="resolution-rate-value">0</span>%</h3>
                    <p>{{ __('advancedreports::lang.resolution_rate') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.warranty_status_distribution') }}</h3>
                </div>
                <div class="box-body">
                    <canvas id="warranty-status-chart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.service_request_trends') }}</h3>
                </div>
                <div class="box-body">
                    <canvas id="service-trends-chart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Warranty Tracking -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.warranty_tracking') }}</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="toggle-warranty-view">
                            <i class="fas fa-table"></i> {{ __('Toggle View') }}
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <!-- Table View -->
                    <div id="warranty-table-view" class="table-responsive">
                        <table id="warranty-tracking-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('advancedreports::lang.warranty_status') }}</th>
                                    <th>{{ __('advancedreports::lang.warranty_end_date') }}</th>
                                    <th>{{ __('advancedreports::lang.days_remaining') }}</th>
                                    <th>{{ __('advancedreports::lang.product_value') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> {{ __('Loading...') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Card View -->
                    <div id="warranty-card-view" class="row" style="display: none;">
                        <div class="text-center"><i class="fas fa-spinner fa-spin"></i> {{ __('Loading...') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Requests -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.service_requests_analysis') }}</h3>
                    <div class="box-tools pull-right">
                        <select id="service-status-filter" class="form-control input-sm"
                            style="width: 150px; display: inline-block;">
                            @foreach($service_statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table id="service-requests-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('advancedreports::lang.request_id') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Priority') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Created Date') }}</th>
                                    <th>{{ __('advancedreports::lang.resolution_days') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> {{ __('Loading...') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Support Metrics & Performance -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.customer_support_metrics') }}</h3>
                </div>
                <div class="box-body">
                    <div id="support-metrics-cards">
                        <div class="text-center"><i class="fas fa-spinner fa-spin"></i> {{ __('Loading...') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.warranty_claims_analysis') }}</h3>
                </div>
                <div class="box-body">
                    <canvas id="warranty-claims-chart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Insights -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.key_insights') }}</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>{{ __('advancedreports::lang.warranties_expiring_soon') }}</h4>
                            <div id="expiring-warranties-list">
                                <div class="text-center"><i class="fas fa-spinner fa-spin"></i> {{ __('Loading...') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h4>{{ __('advancedreports::lang.service_recommendations') }}</h4>
                            <div id="service-recommendations">
                                <div class="text-center"><i class="fas fa-spinner fa-spin"></i> {{ __('Loading...') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Insights -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.performance_insights') }}</h3>
                </div>
                <div class="box-body">
                    <div class="row" id="performance-insights">
                        <div class="text-center"><i class="fas fa-spinner fa-spin"></i> {{ __('Loading...') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Export') }}</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-3">
                            <select id="export-type" class="form-control">
                                <option value="comprehensive">{{ __('advancedreports::lang.comprehensive_report') }}
                                </option>
                                <option value="warranty_tracking">{{ __('advancedreports::lang.warranty_tracking') }}
                                </option>
                                <option value="service_requests">{{ __('advancedreports::lang.service_requests') }}
                                </option>
                                <option value="warranty_claims">{{ __('advancedreports::lang.warranty_claims') }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" id="export-warranty-data" class="btn btn-success">
                                <i class="fas fa-download"></i> {{ __('Export to CSV') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>
@endsection

@section('javascript')
<!-- Date Range Picker -->
<script type="text/javascript" src="//cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.js"></script>
<!-- Select2 -->
<script src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>
<!-- Chart.js -->
<script src="//cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    $(document).ready(function() {
    // Initialize date range picker
    $('#warranty_date_filter').daterangepicker({
        startDate: moment().startOf('year'),
        endDate: moment(),
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'This Year': [moment().startOf('year'), moment()]
        },
        locale: {
            format: 'YYYY-MM-DD'
        }
    });

    // Initialize Select2
    $('.select2').select2();

    // Charts variables
    let warrantyStatusChart, serviceTrendsChart, warrantyClaimsChart;

    // Refresh data button
    $('#refresh-warranty-data').click(function() {
        loadWarrantyServiceData();
    });

    // Service status filter
    $('#service-status-filter').change(function() {
        loadWarrantyServiceData();
    });

    // Toggle warranty view
    let warrantyViewMode = 'table'; // 'table' or 'card'
    $('#toggle-warranty-view').click(function() {
        if (warrantyViewMode === 'table') {
            $('#warranty-table-view').hide();
            $('#warranty-card-view').show();
            $(this).find('i').removeClass('fas fa-table').addClass('fas fa-th');
            warrantyViewMode = 'card';
        } else {
            $('#warranty-card-view').hide();
            $('#warranty-table-view').show();
            $(this).find('i').removeClass('fas fa-th').addClass('fas fa-table');
            warrantyViewMode = 'table';
        }
    });

    // Load warranty and service data via AJAX
    function loadWarrantyServiceData() {
        let start_date = moment().startOf('year').format('YYYY-MM-DD');
        let end_date = moment().format('YYYY-MM-DD');
        
        if ($('#warranty_date_filter').val()) {
            start_date = $('input#warranty_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            end_date = $('input#warranty_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }
        
        $.ajax({
            url: '{{ route("advancedreports.warranty-service.data") }}',
            type: 'GET',
            data: {
                start_date: start_date,
                end_date: end_date,
                customer_id: $('#customer_filter').val(),
                warranty_status: $('#warranty_status_filter').val(),
                service_status: $('#service-status-filter').val()
            },
            success: function(data) {
                updateOverviewCards(data.overview);
                updateWarrantyTracking(data.warranty_tracking);
                updateServiceRequests(data.service_requests);
                updateSupportMetrics(data.support_metrics);
                updateCharts(data);
                updateExpiringWarranties(data.warranty_tracking);
                updateServiceRecommendations(data.insights);
                updatePerformanceInsights(data);
            },
            error: function(xhr, status, error) {
                console.error('Error loading warranty service data:', error);
                toastr.error('{{ __("Error loading warranty and service data") }}');
            }
        });
    }

    function updateOverviewCards(overview) {
        $('#total-products-sold').text(overview.total_products_sold || 0);
        $('#warranty-coverage-value').text((overview.warranty_coverage_percentage || 0).toFixed(1));
        $('#service-requests').text(overview.total_service_requests || 0);
        $('#resolution-rate-value').text((overview.resolution_rate || 0).toFixed(1));
    }

    function updateWarrantyTracking(warranties) {
        // Update Table View
        const tbody = $('#warranty-tracking-table tbody');
        tbody.empty();
        
        // Update Card View
        const cardContainer = $('#warranty-card-view');
        cardContainer.empty();
        
        if (!warranties || warranties.length === 0) {
            tbody.append('<tr><td colspan="6" class="text-center text-muted">No warranty data available</td></tr>');
            cardContainer.append('<div class="col-md-12"><div class="text-center text-muted">No warranty data available</div></div>');
            return;
        }
        
        warranties.forEach(warranty => {
            const statusClass = getWarrantyStatusClass(warranty.warranty_status);
            
            // Add to table view
            const row = `
                <tr>
                    <td>${warranty.product_name}</td>
                    <td>${warranty.customer_name}</td>
                    <td><span class="label ${statusClass}">${warranty.warranty_status}</span></td>
                    <td>${warranty.warranty_end_date}</td>
                    <td>${warranty.days_remaining >= 0 ? warranty.days_remaining + ' days' : 'Expired'}</td>
                    <td>${formatCurrency(warranty.product_value)}</td>
                </tr>
            `;
            tbody.append(row);
            
            // Add to card view
            const statusColor = warranty.warranty_status === 'Active' ? 'success' : 
                               warranty.warranty_status === 'Expiring Soon' ? 'warning' : 
                               warranty.warranty_status === 'Expired' ? 'danger' : 'default';
            
            const card = `
                <div class="col-md-4 mb-3">
                    <div class="box box-${statusColor}">
                        <div class="box-header with-border">
                            <h3 class="box-title">${warranty.product_name}</h3>
                            <span class="label label-${statusColor === 'default' ? 'info' : statusColor} pull-right">${warranty.warranty_status}</span>
                        </div>
                        <div class="box-body">
                            <p><strong>Customer:</strong> ${warranty.customer_name}</p>
                            <p><strong>End Date:</strong> ${warranty.warranty_end_date}</p>
                            <p><strong>Days Remaining:</strong> 
                                <span class="text-${statusColor === 'success' ? 'success' : statusColor === 'warning' ? 'warning' : 'danger'}">
                                    ${warranty.days_remaining >= 0 ? warranty.days_remaining + ' days' : 'Expired'}
                                </span>
                            </p>
                            <p><strong>Product Value:</strong> ${formatCurrency(warranty.product_value)}</p>
                        </div>
                    </div>
                </div>
            `;
            cardContainer.append(card);
        });
    }

    function updateServiceRequests(serviceData) {
        const tbody = $('#service-requests-table tbody');
        tbody.empty();
        
        if (!serviceData.service_requests || serviceData.service_requests.length === 0) {
            tbody.append('<tr><td colspan="8" class="text-center text-muted">No service requests available</td></tr>');
            return;
        }
        
        serviceData.service_requests.forEach(request => {
            const statusClass = getServiceStatusClass(request.status);
            const priorityClass = getPriorityClass(request.priority);
            
            const row = `
                <tr>
                    <td>#${request.request_id}</td>
                    <td>${request.request_type}</td>
                    <td><span class="label ${priorityClass}">${request.priority}</span></td>
                    <td><span class="label ${statusClass}">${request.status}</span></td>
                    <td>${request.product_name}</td>
                    <td>${request.customer_name}</td>
                    <td>${request.created_date}</td>
                    <td>${request.resolution_days || 0} days</td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function updateSupportMetrics(metrics) {
        const container = $('#support-metrics-cards');
        container.empty();
        
        const cards = [
            {
                title: 'Response Time',
                value: `${metrics.avg_first_response_time || 0} days`,
                class: metrics.avg_first_response_time <= 1 ? 'bg-green' : (metrics.avg_first_response_time <= 3 ? 'bg-yellow' : 'bg-red'),
                icon: 'fas fa-clock'
            },
            {
                title: 'Resolution Time',
                value: `${metrics.avg_resolution_time || 0} days`,
                class: metrics.avg_resolution_time <= 3 ? 'bg-green' : (metrics.avg_resolution_time <= 7 ? 'bg-yellow' : 'bg-red'),
                icon: 'fas fa-tools'
            },
            {
                title: 'Customer Satisfaction',
                value: `${metrics.avg_satisfaction_rating || 0}/5`,
                class: metrics.avg_satisfaction_rating >= 4 ? 'bg-green' : (metrics.avg_satisfaction_rating >= 3 ? 'bg-yellow' : 'bg-red'),
                icon: 'fas fa-star'
            }
        ];
        
        cards.forEach(card => {
            container.append(`
                <div class="col-md-12">
                    <div class="info-box ${card.class}">
                        <span class="info-box-icon"><i class="${card.icon}"></i></span>
                        <div class="info-box-content" style="color: white;">
                            <span class="info-box-text text-white">${card.title}</span>
                            <span class="info-box-number">${card.value}</span>
                        </div>
                    </div>
                </div>
            `);
        });
    }

    function updateCharts(data) {
        // Warranty Status Distribution Chart
        if (warrantyStatusChart) {
            warrantyStatusChart.destroy();
        }
        
        const warrantyStatusCtx = document.getElementById('warranty-status-chart').getContext('2d');
        const warrantyStatusData = getWarrantyStatusData(data.warranty_tracking);
        
        // Check if there's warranty data
        if (warrantyStatusData.data.length === 0) {
            // Show "No Data" message instead of empty chart
            const chartContainer = warrantyStatusCtx.canvas.parentNode;
            chartContainer.innerHTML = '<div class="text-center text-muted" style="padding: 50px;"><i class="fas fa-info-circle fa-2x mb-3"></i><br>No warranty data available for selected period</div>';
        } else {
            warrantyStatusChart = new Chart(warrantyStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: warrantyStatusData.labels,
                    datasets: [{
                        data: warrantyStatusData.data,
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#6c757d'],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        position: 'bottom'
                    }
                }
            });
        }

        // Service Trends Chart
        if (serviceTrendsChart) {
            serviceTrendsChart.destroy();
        }
        
        const serviceTrendsCtx = document.getElementById('service-trends-chart').getContext('2d');
        
        serviceTrendsChart = new Chart(serviceTrendsCtx, {
            type: 'line',
            data: {
                labels: data.service_trends ? data.service_trends.map(t => t.date) : [],
                datasets: [{
                    label: 'Service Requests',
                    data: data.service_trends ? data.service_trends.map(t => t.requests) : [],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.1
                }, {
                    label: 'Resolved',
                    data: data.service_trends ? data.service_trends.map(t => t.resolved) : [],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Warranty Claims Chart
        if (warrantyClaimsChart) {
            warrantyClaimsChart.destroy();
        }
        
        const warrantyClaimsCtx = document.getElementById('warranty-claims-chart').getContext('2d');
        const claimsData = data.warranty_claims ? data.warranty_claims.claims_by_reason : [];
        
        warrantyClaimsChart = new Chart(warrantyClaimsCtx, {
            type: 'bar',
            data: {
                labels: claimsData.map(c => c.reason),
                datasets: [{
                    label: 'Claims Count',
                    data: claimsData.map(c => c.count),
                    backgroundColor: '#ffc107'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function updateExpiringWarranties(warranties) {
        const container = $('#expiring-warranties-list');
        container.empty();
        
        if (!warranties) {
            container.html('<div class="text-muted">No warranty data available</div>');
            return;
        }
        
        const expiring = warranties.filter(w => w.warranty_status === 'Expiring Soon');
        
        if (expiring.length === 0) {
            container.html('<div class="text-success"><i class="fas fa-check-circle"></i> No warranties expiring soon</div>');
            return;
        }
        
        expiring.slice(0, 5).forEach(warranty => {
            container.append(`
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                    <div>
                        <strong>${warranty.product_name}</strong>
                        <div class="small text-muted">${warranty.customer_name}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-warning"><strong>${warranty.days_remaining} days</strong></div>
                        <div class="small text-muted">${warranty.end_date}</div>
                    </div>
                </div>
            `);
        });
    }

    function updateServiceRecommendations(insights) {
        const container = $('#service-recommendations');
        container.empty();
        
        if (!insights || Object.keys(insights).length === 0) {
            container.html('<div class="text-muted">No recommendations available</div>');
            return;
        }
        
        Object.values(insights).forEach(insight => {
            const iconClass = getInsightIcon(insight.status);
            const textClass = getInsightTextClass(insight.status);
            
            container.append(`
                <div class="mb-3">
                    <div class="d-flex align-items-center mb-1">
                        <i class="${iconClass} ${textClass} mr-2"></i>
                        <strong>${insight.metric}</strong>
                    </div>
                    <p class="mb-1">${insight.recommendation}</p>
                    <small class="text-muted">Current: ${insight.value}</small>
                </div>
            `);
        });
    }

    function updatePerformanceInsights(data) {
        const container = $('#performance-insights');
        container.empty();
        
        if (!data.overview) {
            container.html('<div class="text-muted text-center">No performance data available</div>');
            return;
        }
        
        const insights = generatePerformanceInsights(data);
        
        insights.forEach(insight => {
            container.append(`
                <div class="col-md-4">
                    <div class="info-box ${insight.class}">
                        <span class="info-box-icon"><i class="${insight.icon}"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text text-white">${insight.title}</span>
                            <span class="info-box-number">${insight.value}</span>
                            <div class="progress">
                                <div class="progress-bar" style="width: ${insight.progress}%"></div>
                            </div>
                            <span class="progress-description">${insight.description}</span>
                        </div>
                    </div>
                </div>
            `);
        });
    }

    // Helper functions
    function getWarrantyStatusClass(status) {
        switch(status) {
            case 'Active': return 'label-success';
            case 'Expiring Soon': return 'label-warning';
            case 'Expired': return 'label-danger';
            case 'Claimed': return 'label-info';
            default: return 'label-default';
        }
    }

    function getServiceStatusClass(status) {
        switch(status) {
            case 'resolved': return 'label-success';
            case 'in_progress': return 'label-warning';
            case 'open': return 'label-danger';
            case 'closed': return 'label-default';
            default: return 'label-default';
        }
    }

    function getPriorityClass(priority) {
        switch(priority) {
            case 'high': return 'label-danger';
            case 'medium': return 'label-warning';
            case 'low': return 'label-success';
            default: return 'label-default';
        }
    }

    function getWarrantyStatusData(warranties) {
        if (!warranties) return { labels: [], data: [] };
        
        const statusCounts = warranties.reduce((acc, warranty) => {
            acc[warranty.warranty_status] = (acc[warranty.warranty_status] || 0) + 1;
            return acc;
        }, {});
        
        return {
            labels: Object.keys(statusCounts),
            data: Object.values(statusCounts)
        };
    }

    function generatePerformanceInsights(data) {
        const insights = [];
        const overview = data.overview;
        
        // Warranty coverage insight
        insights.push({
            class: overview.warranty_coverage_percentage >= 80 ? 'bg-green' : 'bg-yellow',
            icon: 'fas fa-shield-alt',
            title: 'Warranty Coverage',
            value: `${overview.warranty_coverage_percentage}%`,
            progress: overview.warranty_coverage_percentage,
            description: overview.warranty_coverage_percentage >= 80 ? 'Excellent coverage' : 'Needs improvement'
        });

        // Resolution efficiency
        insights.push({
            class: overview.resolution_rate >= 90 ? 'bg-green' : (overview.resolution_rate >= 75 ? 'bg-yellow' : 'bg-red'),
            icon: 'fas fa-check-circle',
            title: 'Resolution Rate',
            value: `${overview.resolution_rate}%`,
            progress: overview.resolution_rate,
            description: overview.resolution_rate >= 90 ? 'Excellent performance' : 'Room for improvement'
        });

        // Response time performance
        if (data.support_metrics) {
            const responseTime = data.support_metrics.avg_first_response_time;
            insights.push({
                class: responseTime <= 1 ? 'bg-green' : (responseTime <= 3 ? 'bg-yellow' : 'bg-red'),
                icon: 'fas fa-clock',
                title: 'Avg Response Time',
                value: `${responseTime} days`,
                progress: Math.max(0, 100 - (responseTime * 20)),
                description: responseTime <= 1 ? 'Excellent response' : 'Can be improved'
            });
        }

        return insights;
    }

    function getInsightIcon(status) {
        switch(status) {
            case 'good': 
            case 'excellent': return 'fas fa-check-circle';
            case 'warning': return 'fas fa-exclamation-triangle';
            case 'poor':
            case 'needs_improvement': return 'fas fa-exclamation-circle';
            case 'attention': return 'fas fa-bell';
            default: return 'fas fa-info-circle';
        }
    }

    function getInsightTextClass(status) {
        switch(status) {
            case 'good':
            case 'excellent': return 'text-success';
            case 'warning': return 'text-warning';
            case 'poor':
            case 'needs_improvement': return 'text-danger';
            case 'attention': return 'text-info';
            default: return 'text-muted';
        }
    }

    function formatCurrency(amount) {
        @if($currency_placement == 'before')
            return '{{ $currency_symbol }}' + parseFloat(amount || 0).toFixed(2);
        @else
            return parseFloat(amount || 0).toFixed(2) + '{{ $currency_symbol }}';
        @endif
    }

    // Export functionality
    $('#export-warranty-data').click(function(e) {
        e.preventDefault();

        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i> {{ __("advancedreports::lang.exporting") }}').prop('disabled', true);

        let params = {
            report_type: $('#export-type').val(),
            _token: '{{ csrf_token() }}'
        };

        if ($('#warranty_date_filter').val()) {
            params.start_date = $('input#warranty_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            params.end_date = $('input#warranty_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }

        if ($('#customer_filter').val()) {
            params.customer_id = $('#customer_filter').val();
        }

        $.ajax({
            url: '{{ route("advancedreports.warranty-service.export") }}',
            type: 'POST',
            data: params,
            xhrFields: { responseType: 'blob' },
            success: function(data, status, xhr) {
                var blob = new Blob([data], {
                    type: xhr.getResponseHeader('Content-Type') || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                });
                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                var filename = 'warranty-service-report.xlsx';
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
                window.URL.revokeObjectURL(link.href);
            },
            error: function(xhr, status, error) {
                alert('Export failed: ' + error);
            },
            complete: function() {
                setTimeout(function() {
                    $btn.html(originalText).prop('disabled', false);
                }, 1000);
            }
        });
    });

    // Initial load
    loadWarrantyServiceData();
});
</script>
@endsection