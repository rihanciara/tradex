@extends('layouts.app')
@section('title', __('Supplier Performance Report'))

@section('content')
<!-- Content Header -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        <i class="fas fa-truck"></i> {{ __('Supplier Performance Report') }}
        <small class="text-muted">{{ __('Delivery Performance, Quality Assessment & Risk Analysis') }}</small>
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
                                {!! Form::label('supplier_date_filter', __('Date Range') . ':') !!}
                                {!! Form::text('supplier_date_filter', null, ['class' => 'form-control', 'readonly', 'style' => 'background-color: white;']) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('supplier_filter', __('Supplier') . ':') !!}
                                {!! Form::select('supplier_filter', $suppliers, null, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('performance_metric', __('Performance Metric') . ':') !!}
                                {!! Form::select('performance_metric', $performance_metrics, null, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label><br>
                                <button type="button" id="refresh-supplier-data" class="btn btn-primary">
                                    <i class="fas fa-sync"></i> {{ __('Update Report') }}
                                </button>
                                <button type="button" id="export-supplier-data" class="btn btn-success">
                                    <i class="fas fa-download"></i> {{ __('Export') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row" id="supplier-overview-cards">
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-blue">
                <div class="inner">
                    <h3 id="total-suppliers">0</h3>
                    <p style="color: white !important;">{{ __('Total Suppliers') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="total-spend">@if($currency_placement === 'before'){{ $currency_symbol }}0@else 0{{ $currency_symbol }}@endif</h3>
                    <p style="color: white !important;">{{ __('Total Spend') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3 id="payment-compliance">0%</h3>
                    <p style="color: white !important;">{{ __('Payment Compliance') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-credit-card"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="avg-delivery-days">0</h3>
                    <p style="color: white !important;">{{ __('Avg Delivery Days') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Charts -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-chart-pie"></i> {{ __('Delivery Performance') }}
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-toggle="collapse" data-target="#delivery-chart-container">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body" id="delivery-chart-container">
                    <canvas id="deliveryPerformanceChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-chart-bar"></i> {{ __('Quality Assessment') }}
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-toggle="collapse" data-target="#quality-chart-container">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body" id="quality-chart-container">
                    <canvas id="qualityAssessmentChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Supplier Performance Rankings -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-trophy"></i> {{ __('Supplier Performance Rankings') }}
                    </h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped" id="supplier-performance-table">
                        <thead>
                            <tr>
                                <th>{{ __('Rank') }}</th>
                                <th>{{ __('Supplier Name') }}</th>
                                <th>{{ __('Total Orders') }}</th>
                                <th>{{ __('Delivery Performance') }}</th>
                                <th>{{ __('Quality Score') }}</th>
                                <th>{{ __('Payment Compliance') }}</th>
                                <th>{{ __('Risk Level') }}</th>
                                <th>{{ __('Overall Score') }}</th>
                            </tr>
                        </thead>
                        <tbody id="performance-rankings-tbody">
                            <tr>
                                <td colspan="8" class="text-center">{{ __('Loading...') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Risk Analysis -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-exclamation-triangle"></i> {{ __('Supplier Risk Distribution') }}
                    </h3>
                </div>
                <div class="box-body">
                    <canvas id="riskDistributionChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-chart-line"></i> {{ __('Payment Compliance Trends') }}
                    </h3>
                </div>
                <div class="box-body">
                    <canvas id="paymentComplianceChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Performance Analysis -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-analytics"></i> {{ __('Detailed Performance Analysis') }}
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>{{ __('Top Performing Suppliers') }}</h4>
                            <div id="top-performers-list">
                                <div class="text-center"><i class="fas fa-spinner fa-spin"></i> {{ __('Loading...') }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h4>{{ __('Suppliers Requiring Attention') }}</h4>
                            <div id="attention-required-list">
                                <div class="text-center"><i class="fas fa-spinner fa-spin"></i> {{ __('Loading...') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Insights -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-lightbulb"></i> {{ __('Key Performance Insights') }}
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row" id="performance-insights">
                        <div class="col-md-12 text-center">
                            <i class="fas fa-spinner fa-spin"></i> {{ __('Analyzing supplier performance...') }}
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
    let deliveryChart, qualityChart, riskChart, complianceChart;
    
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
    $('#supplier_date_filter').daterangepicker(
        dateRangeSettings,
        function(start, end) {
            $('#supplier_date_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            loadSupplierPerformanceData();
        }
    );

    $('#supplier_date_filter').on('cancel.daterangepicker', function(ev, picker) {
        $('#supplier_date_filter').val('');
        loadSupplierPerformanceData();
    });

    // Filter change handlers
    $(document).on('change', '#supplier_filter, #performance_metric', function(){
        loadSupplierPerformanceData();
    });

    $('#refresh-supplier-data').click(function(){
        loadSupplierPerformanceData();
    });

    function loadSupplierPerformanceData() {
        let start_date = '';
        let end_date = '';
        
        if ($('#supplier_date_filter').val()) {
            start_date = $('input#supplier_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            end_date = $('input#supplier_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }
        
        $.ajax({
            url: '{{ route("advancedreports.supplier-performance.data") }}',
            type: 'GET',
            data: {
                start_date: start_date,
                end_date: end_date,
                supplier_id: $('#supplier_filter').val(),
                metric: $('#performance_metric').val()
            },
            success: function(data) {
                updateOverviewCards(data.overview);
                updatePerformanceRankings(data.delivery_performance);
                updateTopPerformers(data.rankings);
                updateAttentionRequired(data.rankings);
                updateCharts(data);
                updatePerformanceInsights(data);
            },
            error: function(xhr, status, error) {
                console.error('Error loading supplier performance data:', error);
                toastr.error('{{ __("Error loading supplier performance data") }}');
            }
        });
    }

    function updateOverviewCards(overview) {
        $('#total-suppliers').text(overview.total_suppliers || 0);
        $('#total-spend').text(formatCurrency(overview.total_spent || 0));
        $('#payment-compliance').text((overview.payment_compliance_rate || 0).toFixed(1) + '%');
        $('#avg-delivery-days').text((overview.avg_delivery_days || 0).toFixed(0));
    }

    function updatePerformanceRankings(deliveryData) {
        let tbody = $('#performance-rankings-tbody');
        tbody.empty();
        
        if (deliveryData && deliveryData.length > 0) {
            deliveryData.forEach((supplier, index) => {
                let rankBadge = '';
                if (index === 0) rankBadge = '<span class="label label-warning"><i class="fas fa-trophy"></i> 1st</span>';
                else if (index === 1) rankBadge = '<span class="label label-default"><i class="fas fa-medal"></i> 2nd</span>';
                else if (index === 2) rankBadge = '<span class="label label-warning"><i class="fas fa-medal"></i> 3rd</span>';
                else rankBadge = `<span class="label label-primary">${index + 1}th</span>`;
                
                let qualityBadge = getQualityBadge(supplier.quality_score || 0);
                let riskBadge = getRiskBadge(supplier.risk_score || 0);
                
                tbody.append(`
                    <tr>
                        <td>${rankBadge}</td>
                        <td><strong>${supplier.supplier_name}</strong></td>
                        <td>${supplier.total_orders}</td>
                        <td>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-blue" style="width: ${supplier.delivery_rate}%"></div>
                            </div>
                            <small>${supplier.delivery_rate}% (${supplier.avg_delivery_days} days avg)</small>
                        </td>
                        <td>${qualityBadge}</td>
                        <td>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-green" style="width: ${supplier.payment_compliance}%"></div>
                            </div>
                            <small>${supplier.payment_compliance}%</small>
                        </td>
                        <td>${riskBadge}</td>
                        <td>
                            <span class="label label-success">${supplier.performance_score}%</span>
                        </td>
                    </tr>
                `);
            });
        } else {
            tbody.append('<tr><td colspan="8" class="text-center text-muted">{{ __("No supplier performance data available") }}</td></tr>');
        }
    }

    function getQualityBadge(score) {
        if (score >= 90) return `<span class="label label-success">${score}% Excellent</span>`;
        if (score >= 80) return `<span class="label label-info">${score}% Good</span>`;
        if (score >= 70) return `<span class="label label-warning">${score}% Average</span>`;
        return `<span class="label label-danger">${score}% Poor</span>`;
    }

    function getRiskBadge(score) {
        if (score <= 25) return '<span class="label label-success">Low Risk</span>';
        if (score <= 50) return '<span class="label label-warning">Medium Risk</span>';
        if (score <= 75) return '<span class="label label-danger">High Risk</span>';
        return '<span class="label label-danger">Critical Risk</span>';
    }

    function updateCharts(data) {
        // Delivery Performance Chart
        updateDeliveryChart(data.delivery_performance);
        
        // Quality Assessment Chart
        updateQualityChart(data.quality_assessment);
        
        // Risk Distribution Chart
        updateRiskChart(data.risk_analysis);
        
        // Payment Compliance Chart
        updateComplianceChart(data.payment_compliance);
    }

    function updateDeliveryChart(deliveryData) {
        const ctx = document.getElementById('deliveryPerformanceChart').getContext('2d');
        
        if (deliveryChart) {
            deliveryChart.destroy();
        }
        
        if (deliveryData && deliveryData.length > 0) {
            const suppliers = deliveryData.map(d => d.supplier_name);
            const onTimeRates = deliveryData.map(d => d.on_time_rate);
            const deliveryDays = deliveryData.map(d => d.avg_delivery_days);
            
            deliveryChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: suppliers,
                    datasets: [{
                        label: 'On-Time Rate (%)',
                        data: onTimeRates,
                        backgroundColor: '#3c8dbc',
                        yAxisID: 'y'
                    }, {
                        label: 'Avg Delivery Days',
                        data: deliveryDays,
                        backgroundColor: '#00a65a',
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
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        }
    }

    function updateQualityChart(qualityData) {
        const ctx = document.getElementById('qualityAssessmentChart').getContext('2d');
        
        if (qualityChart) {
            qualityChart.destroy();
        }
        
        if (qualityData && qualityData.length > 0) {
            const suppliers = qualityData.map(d => d.supplier_name);
            const qualityScores = qualityData.map(d => d.quality_score);
            
            qualityChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: suppliers,
                    datasets: [{
                        data: qualityScores,
                        backgroundColor: ['#3c8dbc', '#00a65a', '#f39c12', '#dd4b39', '#605ca8']
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
    }

    function updateRiskChart(riskData) {
        const ctx = document.getElementById('riskDistributionChart').getContext('2d');
        
        if (riskChart) {
            riskChart.destroy();
        }
        
        const riskLevels = ['Low Risk', 'Medium Risk', 'High Risk', 'Critical Risk'];
        const riskCounts = riskLevels.map(level => 
            riskData ? riskData.filter(d => getRiskLevel(d.risk_score) === level).length : 0
        );
        
        riskChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: riskLevels,
                datasets: [{
                    data: riskCounts,
                    backgroundColor: ['#00a65a', '#f39c12', '#dd4b39', '#d73925']
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

    function updateComplianceChart(complianceData) {
        const ctx = document.getElementById('paymentComplianceChart').getContext('2d');
        
        if (complianceChart) {
            complianceChart.destroy();
        }
        
        if (complianceData && complianceData.length > 0) {
            const suppliers = complianceData.map(d => d.supplier_name);
            const complianceRates = complianceData.map(d => d.payment_rate);
            
            complianceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: suppliers,
                    datasets: [{
                        label: 'Payment Compliance %',
                        data: complianceRates,
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
                            max: 100
                        }
                    }
                }
            });
        }
    }

    function getRiskLevel(score) {
        if (score <= 25) return 'Low Risk';
        if (score <= 50) return 'Medium Risk';
        if (score <= 75) return 'High Risk';
        return 'Critical Risk';
    }

    function updatePerformanceInsights(data) {
        let container = $('#performance-insights');
        container.empty();
        
        // Generate insights based on data
        let insights = generateInsights(data);
        
        insights.forEach(insight => {
            container.append(`
                <div class="col-md-4">
                    <div class="info-box ${insight.class}">
                        <span class="info-box-icon"><i class="${insight.icon}"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">${insight.title}</span>
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

    function generateInsights(data) {
        let insights = [];
        
        // Best performing supplier
        if (data.delivery_performance && data.delivery_performance.length > 0) {
            const best = data.delivery_performance[0];
            insights.push({
                class: 'bg-green',
                icon: 'fas fa-star',
                title: 'Top Performer',
                value: best.supplier_name,
                progress: best.performance_score,
                description: `${best.performance_score}% overall score`
            });
        }
        
        // Average delivery time insight
        if (data.overview) {
            insights.push({
                class: 'bg-blue',
                icon: 'fas fa-clock',
                title: 'Delivery Performance',
                value: `${data.overview.avg_delivery_days || 0} days`,
                progress: Math.max(0, 100 - (data.overview.avg_delivery_days || 0) * 10),
                description: 'Average delivery time across all suppliers'
            });
        }
        
        // Payment compliance insight
        if (data.overview) {
            insights.push({
                class: data.overview.payment_compliance_rate > 80 ? 'bg-green' : 'bg-yellow',
                icon: 'fas fa-credit-card',
                title: 'Payment Compliance',
                value: `${(data.overview.payment_compliance_rate || 0).toFixed(1)}%`,
                progress: data.overview.payment_compliance_rate || 0,
                description: 'Overall payment compliance rate'
            });
        }
        
        return insights;
    }

    // Export functionality
    $('#export-supplier-data').click(function(e) {
        e.preventDefault();

        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i> {{ __("advancedreports::lang.exporting") }}').prop('disabled', true);

        let params = {
            report_type: 'delivery',
            _token: '{{ csrf_token() }}'
        };

        if ($('#supplier_date_filter').val()) {
            params.start_date = $('input#supplier_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            params.end_date = $('input#supplier_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }

        if ($('#supplier_filter').val()) {
            params.supplier_id = $('#supplier_filter').val();
        }

        // Use AJAX to download the file properly
        $.ajax({
            url: '{{ route("advancedreports.supplier-performance.export") }}',
            type: 'POST',
            data: params,
            xhrFields: {
                responseType: 'blob'
            },
            success: function(data, status, xhr) {
                var blob = new Blob([data], {
                    type: xhr.getResponseHeader('Content-Type') || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                });

                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);

                var filename = 'supplier-performance-report.xlsx';
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

    // Update top performing suppliers list
    function updateTopPerformers(rankings) {
        const container = $('#top-performers-list');
        container.empty();
        
        if (!rankings || rankings.length === 0) {
            container.html('<div class="text-muted text-center">No data available</div>');
            return;
        }
        
        // Get top 5 suppliers
        const topSuppliers = rankings.slice(0, 5);
        
        topSuppliers.forEach((supplier, index) => {
            const badgeClass = index === 0 ? 'bg-gold' : index === 1 ? 'bg-silver' : index === 2 ? 'bg-bronze' : 'bg-green';
            container.append(`
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                    <div>
                        <span class="badge ${badgeClass} mr-2">#${index + 1}</span>
                        <strong>${supplier.supplier_name}</strong>
                    </div>
                    <div class="text-right">
                        <div class="small text-muted">${supplier.orders_count} orders</div>
                        <div class="text-success"><strong>${supplier.overall_score.toFixed(1)}%</strong></div>
                    </div>
                </div>
            `);
        });
    }

    // Update suppliers requiring attention list  
    function updateAttentionRequired(rankings) {
        const container = $('#attention-required-list');
        container.empty();
        
        if (!rankings || rankings.length === 0) {
            container.html('<div class="text-muted text-center">No data available</div>');
            return;
        }
        
        // Get suppliers with scores below 70%
        const attentionRequired = rankings.filter(supplier => supplier.overall_score < 70);
        
        if (attentionRequired.length === 0) {
            container.html('<div class="text-success text-center"><i class="fas fa-check-circle"></i> All suppliers performing well!</div>');
            return;
        }
        
        // Take worst 5 performers
        const worstSuppliers = attentionRequired.slice(-5).reverse();
        
        worstSuppliers.forEach(supplier => {
            const alertClass = supplier.overall_score < 40 ? 'text-danger' : supplier.overall_score < 60 ? 'text-warning' : 'text-info';
            const iconClass = supplier.overall_score < 40 ? 'fas fa-exclamation-triangle' : 'fas fa-exclamation-circle';
            
            container.append(`
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                    <div>
                        <i class="${iconClass} ${alertClass} mr-2"></i>
                        <strong>${supplier.supplier_name}</strong>
                        <div class="small text-muted">${supplier.orders_count} orders</div>
                    </div>
                    <div class="text-right">
                        <div class="${alertClass}"><strong>${supplier.overall_score.toFixed(1)}%</strong></div>
                        <div class="small text-muted">${supplier.performance_grade}</div>
                    </div>
                </div>
            `);
        });
    }

    // Initial load
    loadSupplierPerformanceData();
});
</script>
@endsection