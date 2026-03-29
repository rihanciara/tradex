@extends('layouts.app')
@section('title', __('Multi-Channel Sales Report'))

@section('content')
<!-- Content Header -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        <i class="fas fa-chart-network"></i> {{ __('Multi-Channel Sales Report') }}
        <small class="text-muted">{{ __('Online vs Offline Performance Analytics') }}</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <!-- Filters -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title">
                        <i class="fas fa-filter"></i> {{ __('Filters') }}
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('channel_date_filter', __('Date Range') . ':') !!}
                                {!! Form::text('channel_date_filter', null, ['class' => 'form-control', 'readonly', 'style' => 'background-color: white;']) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('channel_type', __('Channel Type') . ':') !!}
                                {!! Form::select('channel_type', $channel_types, null, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('location_filter', __('Location') . ':') !!}
                                {!! Form::select('location_filter', $locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label><br>
                                <button type="button" id="refresh-channel-data" class="btn btn-primary">
                                    <i class="fas fa-sync"></i> {{ __('Update Report') }}
                                </button>
                                <button type="button" id="export-channel-data" class="btn btn-success">
                                    <i class="fas fa-download"></i> {{ __('Export') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Channel Overview Cards -->
    <div class="row" id="channel-overview-cards">
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-blue">
                <div class="inner">
                    <h3 id="online-revenue">@if($currency_placement === 'before'){{ $currency_symbol }}0@else 0{{ $currency_symbol }}@endif</h3>
                    <p style="color: white !important;">{{ __('Online Revenue') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-globe"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="offline-revenue">@if($currency_placement === 'before'){{ $currency_symbol }}0@else 0{{ $currency_symbol }}@endif</h3>
                    <p style="color: white !important;">{{ __('Offline Revenue') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-store"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3 id="cross-channel-customers">0</h3>
                    <p style="color: white !important;">{{ __('Cross-Channel Customers') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="channel-efficiency">0%</h3>
                    <p style="color: white !important;">{{ __('Channel Efficiency') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Comparison Charts -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-chart-pie"></i> {{ __('Channel Revenue Distribution') }}
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-toggle="collapse" data-target="#revenue-chart-container">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body" id="revenue-chart-container">
                    <canvas id="channelRevenueChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-chart-bar"></i> {{ __('Channel Performance Metrics') }}
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-toggle="collapse" data-target="#performance-chart-container">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body" id="performance-chart-container">
                    <canvas id="channelPerformanceChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Channel Profitability Analysis -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-coins"></i> {{ __('Channel Profitability Analysis') }}
                    </h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped" id="channel-profitability-table">
                        <thead>
                            <tr>
                                <th>{{ __('Channel') }}</th>
                                <th>{{ __('Orders') }}</th>
                                <th>{{ __('Revenue') }}</th>
                                <th>{{ __('Avg Order Value') }}</th>
                                <th>{{ __('Tax Collected') }}</th>
                                <th>{{ __('Shipping Revenue') }}</th>
                                <th>{{ __('Discounts Given') }}</th>
                                <th>{{ __('Profit Margin') }}</th>
                            </tr>
                        </thead>
                        <tbody id="profitability-tbody">
                            <tr>
                                <td colspan="8" class="text-center">{{ __('Loading...') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Cross-Channel Customer Behavior -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-users"></i> {{ __('Customer Channel Preferences') }}
                    </h3>
                </div>
                <div class="box-body">
                    <canvas id="customerPreferenceChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-clock"></i> {{ __('Peak Hours by Channel') }}
                    </h3>
                </div>
                <div class="box-body">
                    <canvas id="peakHoursChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Cross-Channel Customers -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-star"></i> {{ __('Top Cross-Channel Customers') }}
                    </h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped" id="cross-channel-customers-table">
                        <thead>
                            <tr>
                                <th>{{ __('Customer Name') }}</th>
                                <th>{{ __('Online Orders') }}</th>
                                <th>{{ __('Offline Orders') }}</th>
                                <th>{{ __('Online Revenue') }}</th>
                                <th>{{ __('Offline Revenue') }}</th>
                                <th>{{ __('Total Revenue') }}</th>
                                <th>{{ __('Preference') }}</th>
                            </tr>
                        </thead>
                        <tbody id="cross-channel-tbody">
                            <tr>
                                <td colspan="7" class="text-center">{{ __('Loading...') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Channel Trends -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-trending-up"></i> {{ __('Channel Performance Trends') }}
                    </h3>
                </div>
                <div class="box-body">
                    <canvas id="channelTrendsChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Optimization Insights -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-lightbulb"></i> {{ __('Channel Optimization Insights') }}
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row" id="optimization-insights">
                        <div class="col-md-12 text-center">
                            <i class="fas fa-spinner fa-spin"></i> {{ __('Generating insights...') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    let revenueChart, performanceChart, preferenceChart, hoursChart, trendsChart;
    
    // Currency formatting function
    const currency_symbol = '{{ $currency_symbol }}';
    const currency_placement = '{{ $currency_placement }}';
    
    function formatCurrency(amount) {
        const formattedAmount = Number(amount).toLocaleString();
        if (currency_placement === 'before') {
            return currency_symbol + formattedAmount;
        } else {
            return formattedAmount + currency_symbol;
        }
    }

    // Initialize date range picker
    $('#channel_date_filter').daterangepicker(
        dateRangeSettings,
        function(start, end) {
            $('#channel_date_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            loadChannelData();
        }
    );

    $('#channel_date_filter').on('cancel.daterangepicker', function(ev, picker) {
        $('#channel_date_filter').val('');
        loadChannelData();
    });

    // Filter change handlers
    $(document).on('change', '#channel_type, #location_filter', function(){
        loadChannelData();
    });

    $('#refresh-channel-data').click(function(){
        loadChannelData();
    });

    function loadChannelData() {
        let start_date = '';
        let end_date = '';
        
        if ($('#channel_date_filter').val()) {
            start_date = $('input#channel_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            end_date = $('input#channel_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }
        
        $.ajax({
            url: '{{ route("advancedreports.multi-channel.performance") }}',
            type: 'GET',
            data: {
                start_date: start_date,
                end_date: end_date,
                channel_type: $('#channel_type').val(),
                location: $('#location_filter').val()
            },
            success: function(data) {
                updateOverviewCards(data.overview);
                updateProfitabilityTable(data.profitability);
                updateCrossChannelCustomers(data.customer_behavior);
                updateCharts(data);
                updateOptimizationInsights(data.optimization_insights);
            },
            error: function(xhr, status, error) {
                console.error('Error loading channel data:', error);
                toastr.error('{{ __("Error loading channel data") }}');
            }
        });
    }

    function updateOverviewCards(overview) {
        const online = overview.online || {revenue: 0, customers: 0, transactions: 0};
        const offline = overview.offline || {revenue: 0, customers: 0, transactions: 0};
        
        $('#online-revenue').text(formatCurrency(online.revenue));
        $('#offline-revenue').text(formatCurrency(offline.revenue));
        $('#cross-channel-customers').text((online.customers && offline.customers) ? Math.min(online.customers, offline.customers) : 0);
        
        const totalRevenue = parseFloat(online.revenue) + parseFloat(offline.revenue);
        const efficiency = totalRevenue > 0 ? ((Math.max(online.revenue, offline.revenue) / totalRevenue) * 100) : 0;
        $('#channel-efficiency').text(efficiency.toFixed(1) + '%');
    }

    function updateProfitabilityTable(profitability) {
        let tbody = $('#profitability-tbody');
        tbody.empty();
        
        ['online', 'offline'].forEach(channel => {
            const data = profitability[channel];
            if (data) {
                const profitMargin = data.gross_revenue > 0 ? 
                    (((data.net_revenue - data.total_discounts) / data.gross_revenue) * 100) : 0;
                
                tbody.append(`
                    <tr>
                        <td><span class="label ${channel === 'online' ? 'label-primary' : 'label-success'}">${channel.toUpperCase()}</span></td>
                        <td>${Number(data.total_orders).toLocaleString()}</td>
                        <td>${formatCurrency(data.net_revenue)}</td>
                        <td>${formatCurrency(data.avg_order_value)}</td>
                        <td>${formatCurrency(data.tax_collected)}</td>
                        <td>${formatCurrency(data.shipping_revenue)}</td>
                        <td>${formatCurrency(data.total_discounts)}</td>
                        <td><span class="label ${profitMargin > 0 ? 'label-success' : 'label-danger'}">${profitMargin.toFixed(1)}%</span></td>
                    </tr>
                `);
            }
        });
        
        if (tbody.children().length === 0) {
            tbody.append('<tr><td colspan="8" class="text-center text-muted">{{ __("No data available") }}</td></tr>');
        }
    }

    function updateCrossChannelCustomers(customerBehavior) {
        let tbody = $('#cross-channel-tbody');
        tbody.empty();
        
        if (customerBehavior.cross_channel_customers && customerBehavior.cross_channel_customers.length > 0) {
            customerBehavior.cross_channel_customers.forEach(customer => {
                const preference = customer.online_revenue > customer.offline_revenue ? 'Online' : 'Offline';
                const preferenceClass = preference === 'Online' ? 'label-primary' : 'label-success';
                
                tbody.append(`
                    <tr>
                        <td>${customer.customer_name}</td>
                        <td>${customer.online_orders}</td>
                        <td>${customer.offline_orders}</td>
                        <td>${formatCurrency(customer.online_revenue)}</td>
                        <td>${formatCurrency(customer.offline_revenue)}</td>
                        <td><strong>${formatCurrency(customer.total_revenue)}</strong></td>
                        <td><span class="label ${preferenceClass}">${preference}</span></td>
                    </tr>
                `);
            });
        } else {
            tbody.append('<tr><td colspan="7" class="text-center text-muted">{{ __("No cross-channel customers found") }}</td></tr>');
        }
    }

    function updateCharts(data) {
        // Revenue Distribution Chart
        updateRevenueChart(data.overview);
        
        // Performance Metrics Chart
        updatePerformanceChart(data.profitability);
        
        // Customer Preference Chart
        updateCustomerPreferenceChart(data.customer_behavior);
        
        // Peak Hours Chart
        updatePeakHoursChart(data.optimization_insights.peak_hours);
        
        // Trends Chart
        updateTrendsChart(data.trends);
    }

    function updateRevenueChart(overview) {
        const ctx = document.getElementById('channelRevenueChart').getContext('2d');
        
        if (revenueChart) {
            revenueChart.destroy();
        }
        
        const online = overview.online || {revenue: 0};
        const offline = overview.offline || {revenue: 0};
        
        revenueChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Online', 'Offline'],
                datasets: [{
                    data: [online.revenue, offline.revenue],
                    backgroundColor: ['#3c8dbc', '#00a65a'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + formatCurrency(context.parsed);
                            }
                        }
                    }
                }
            }
        });
    }

    function updatePerformanceChart(profitability) {
        const ctx = document.getElementById('channelPerformanceChart').getContext('2d');
        
        if (performanceChart) {
            performanceChart.destroy();
        }
        
        const online = profitability.online || {total_orders: 0, avg_order_value: 0};
        const offline = profitability.offline || {total_orders: 0, avg_order_value: 0};
        
        performanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Orders', 'Avg Order Value'],
                datasets: [{
                    label: 'Online',
                    data: [online.total_orders, online.avg_order_value],
                    backgroundColor: '#3c8dbc'
                }, {
                    label: 'Offline',
                    data: [offline.total_orders, offline.avg_order_value],
                    backgroundColor: '#00a65a'
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

    function updateCustomerPreferenceChart(customerBehavior) {
        const ctx = document.getElementById('customerPreferenceChart').getContext('2d');
        
        if (preferenceChart) {
            preferenceChart.destroy();
        }
        
        const preference = customerBehavior.preference_analysis || {
            online_preferred: 0,
            offline_preferred: 0,
            balanced: 0
        };
        
        preferenceChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Online Preferred', 'Offline Preferred', 'Balanced'],
                datasets: [{
                    data: [preference.online_preferred, preference.offline_preferred, preference.balanced],
                    backgroundColor: ['#3c8dbc', '#00a65a', '#f39c12']
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

    function updatePeakHoursChart(peakHours) {
        const ctx = document.getElementById('peakHoursChart').getContext('2d');
        
        if (hoursChart) {
            hoursChart.destroy();
        }
        
        // Process peak hours data
        const hours = Array.from({length: 24}, (_, i) => i);
        const onlineData = hours.map(h => {
            const hourData = peakHours.online ? peakHours.online.find(p => p.hour == h) : null;
            return hourData ? hourData.transaction_count : 0;
        });
        const offlineData = hours.map(h => {
            const hourData = peakHours.offline ? peakHours.offline.find(p => p.hour == h) : null;
            return hourData ? hourData.transaction_count : 0;
        });
        
        hoursChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: hours.map(h => h + ':00'),
                datasets: [{
                    label: 'Online',
                    data: onlineData,
                    borderColor: '#3c8dbc',
                    backgroundColor: 'rgba(60, 141, 188, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Offline',
                    data: offlineData,
                    borderColor: '#00a65a',
                    backgroundColor: 'rgba(0, 166, 90, 0.1)',
                    tension: 0.4
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

    function updateTrendsChart(trends) {
        const ctx = document.getElementById('channelTrendsChart').getContext('2d');
        
        if (trendsChart) {
            trendsChart.destroy();
        }
        
        // Process trends data
        const allDates = [...new Set([
            ...(trends.online || []).map(t => t.date),
            ...(trends.offline || []).map(t => t.date)
        ])].sort();
        
        const onlineRevenue = allDates.map(date => {
            const dayData = trends.online ? trends.online.find(t => t.date === date) : null;
            return dayData ? parseFloat(dayData.revenue) : 0;
        });
        
        const offlineRevenue = allDates.map(date => {
            const dayData = trends.offline ? trends.offline.find(t => t.date === date) : null;
            return dayData ? parseFloat(dayData.revenue) : 0;
        });
        
        trendsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: allDates,
                datasets: [{
                    label: 'Online Revenue',
                    data: onlineRevenue,
                    borderColor: '#3c8dbc',
                    backgroundColor: 'rgba(60, 141, 188, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Offline Revenue',
                    data: offlineRevenue,
                    borderColor: '#00a65a',
                    backgroundColor: 'rgba(0, 166, 90, 0.1)',
                    tension: 0.4
                }]
            },
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
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                            }
                        }
                    }
                }
            }
        });
    }

    function updateOptimizationInsights(insights) {
        let container = $('#optimization-insights');
        container.empty();
        
        // Since current data is all offline, show insights about channel potential
        container.append(`
            <div class="col-md-4">
                <div class="info-box bg-yellow">
                    <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Channel Opportunity</span>
                        <span class="info-box-number">100% Offline</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 0%"></div>
                        </div>
                        <span class="progress-description">Consider developing online channels</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box bg-blue">
                    <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Growth Potential</span>
                        <span class="info-box-number">High</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 85%"></div>
                        </div>
                        <span class="progress-description">Online presence can boost revenue</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box bg-green">
                    <span class="info-box-icon"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Customer Reach</span>
                        <span class="info-box-number">Expand</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 60%"></div>
                        </div>
                        <span class="progress-description">Multi-channel can reach more customers</span>
                    </div>
                </div>
            </div>
        `);
    }

    // Export functionality
    $('#export-channel-data').click(function(e) {
        e.preventDefault();

        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i> {{ __("advancedreports::lang.exporting") }}').prop('disabled', true);

        let params = {
            report_type: 'overview',
            _token: '{{ csrf_token() }}'
        };

        if ($('#channel_date_filter').val()) {
            params.start_date = $('input#channel_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            params.end_date = $('input#channel_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }

        // Use AJAX to download the file properly
        $.ajax({
            url: '{{ route("advancedreports.multi-channel.export") }}',
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
                var filename = 'multi-channel-report.xlsx';
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
                    $btn.html(originalText).prop('disabled', false);
                }, 1000);
            }
        });
    });

    // Initial load
    loadChannelData();
});
</script>
@endsection