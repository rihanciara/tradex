@extends('layouts.app')
@section('title', __('Tax Compliance Report'))

@php
// Get currency settings from session
$currency_symbol = session('currency')['symbol'] ?? '$';
$currency_precision = session('business.currency_precision') ?? 2;
$currency_placement = session('business.currency_symbol_placement') ?? 'before';
@endphp

@section('content')
<!-- Content Header -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        <i class="fas fa-receipt"></i> {{ __('Tax Compliance Report') }}
        <small class="text-muted">{{ __('Multi-Tax Jurisdiction Support & Optimization') }}</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <!-- Summary Cards -->
    <div class="row" id="tax-summary-cards">
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-blue">
                <div class="inner">
                    <h3 id="total-tax-collected">@if($currency_placement === 'before'){{ $currency_symbol }}0@else 0{{ $currency_symbol }}@endif</h3>
                    <p style="color: white !important;">{{ __('Total Tax Collected') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-coins"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3 id="tax-liability">@if($currency_placement === 'before'){{ $currency_symbol }}0@else 0{{ $currency_symbol }}@endif</h3>
                    <p style="color: white !important;">{{ __('Net Tax Liability') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="compliance-score">0%</h3>
                    <p style="color: white !important;">{{ __('Compliance Score') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-purple">
                <div class="inner">
                    <h3 id="potential-savings">@if($currency_placement === 'before'){{ $currency_symbol }}0@else 0{{ $currency_symbol }}@endif</h3>
                    <p style="color: white !important;">{{ __('Potential Savings') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-piggy-bank"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <div class="col-md-8">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Monthly Tax Liability Trend') }}</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <canvas id="tax-trend-chart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Tax by Jurisdiction') }}</h3>
                </div>
                <div class="box-body">
                    <canvas id="jurisdiction-chart" style="height: 250px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Risk Assessment -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Tax Audit Risk Assessment') }}</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-yellow">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Risk Level</span>
                                    <span class="info-box-number" id="risk-level">Low</span>
                                    <div class="progress">
                                        <div class="progress-bar" id="risk-progress" style="width: 0%"></div>
                                    </div>
                                    <span class="progress-description" id="risk-percentage">0% Risk Score</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div id="risk-factors-list"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tax Optimization Insights -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Tax Optimization Insights') }}</h3>
                </div>
                <div class="box-body">
                    <div id="optimization-insights" class="row"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Filing Deadlines -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Upcoming Filing Deadlines') }}</h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="deadlines-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Period') }}</th>
                                    <th>{{ __('Filing Deadline') }}</th>
                                    <th>{{ __('Days Remaining') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="deadlines-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('Tax Compliance Filters')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('tax_date_filter', __('report.date_range') . ':') !!}
                        {!! Form::text('tax_date_filter', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']) !!}
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('tax_jurisdiction', __('Jurisdiction') . ':') !!}
                        {!! Form::select('tax_jurisdiction', ['all' => __('lang_v1.all'), 'federal' => 'Federal', 'state' => 'State/Local', 'other' => 'Other'], null, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('tax_period', __('Filing Period') . ':') !!}
                        {!! Form::select('tax_period', $tax_periods, null, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('report_type', __('Report Type') . ':') !!}
                        {!! Form::select('report_type', ['liability' => 'Tax Liability', 'filing' => 'Filing Assistance', 'optimization' => 'Optimization'], 'liability', ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label><br>
                        <button type="button" class="btn btn-success" id="export-tax-compliance">
                            <i class="fas fa-download"></i> {{ __('Export Report') }}
                        </button>
                        <button type="button" class="btn btn-primary" id="refresh-data">
                            <i class="fas fa-sync"></i> {{ __('Refresh') }}
                        </button>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <!-- Tax Liability Details Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Tax Liability Details') }}</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-success btn-sm" id="export-liability">
                            <i class="fas fa-file-excel"></i> {{ __('Export Excel') }}
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tax_liability_table">
                            <thead>
                                <tr>
                                    <th>{{ __('Tax Name') }}</th>
                                    <th>{{ __('Tax Rate (%)') }}</th>
                                    <th>{{ __('Output Tax') }}</th>
                                    <th>{{ __('Input Tax Credit') }}</th>
                                    <th>{{ __('Net Liability') }}</th>
                                    <th>{{ __('Sales Trans.') }}</th>
                                    <th>{{ __('Purchase Trans.') }}</th>
                                </tr>
                            </thead>
                            <tbody id="liability-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    let taxTrendChart, jurisdictionChart;
    
    // Currency formatting function
    const currency_symbol = '{{ $currency_symbol }}';
    const currency_placement = '{{ $currency_placement }}';
    const currency_precision = {{ $currency_precision }};

    function formatCurrency(amount) {
        const formattedAmount = parseFloat(amount).toLocaleString(undefined, {
            minimumFractionDigits: currency_precision,
            maximumFractionDigits: currency_precision
        });
        if (currency_placement === 'before') {
            return currency_symbol + formattedAmount;
        } else {
            return formattedAmount + currency_symbol;
        }
    }

    // Initialize date range picker
    $('#tax_date_filter').daterangepicker(
        dateRangeSettings,
        function(start, end) {
            $('#tax_date_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            loadTaxData();
        }
    );

    $('#tax_date_filter').on('cancel.daterangepicker', function(ev, picker) {
        $('#tax_date_filter').val('');
        loadTaxData();
    });

    // Filter change handlers
    $(document).on('change', '#tax_jurisdiction, #tax_period, #report_type', function(){
        loadTaxData();
    });

    // Refresh button
    $('#refresh-data').click(function(){
        loadTaxData();
    });

    // Load tax compliance data
    function loadTaxData() {
        let start_date = '';
        let end_date = '';
        
        if ($('#tax_date_filter').val()) {
            start_date = $('input#tax_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            end_date = $('input#tax_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }

        $.ajax({
            url: '{{ route("advancedreports.tax-compliance.summary") }}',
            method: 'GET',
            data: {
                start_date: start_date,
                end_date: end_date,
                jurisdiction: $('#tax_jurisdiction').val(),
                period: $('#tax_period').val()
            },
            success: function(data) {
                // Update summary cards
                $('#total-tax-collected').text(formatCurrency(data.total_tax_collected));
                $('#tax-liability').text(formatCurrency(data.tax_liability));
                $('#compliance-score').text(Number(data.compliance_score).toFixed(1) + '%');
                $('#potential-savings').text(formatCurrency(data.potential_savings));

                // Update charts
                updateTaxTrendChart(data.monthly_tax_trend);
                updateJurisdictionChart(data.tax_by_jurisdiction);

                // Update risk assessment
                updateRiskAssessment(data.audit_risk);

                // Update optimization insights
                updateOptimizationInsights(data.optimization_insights);

                // Update filing deadlines
                updateFilingDeadlines(data.upcoming_deadlines);
            },
            error: function(xhr, status, error) {
                console.error('Error loading tax data:', error);
            }
        });

        // Load liability details
        loadLiabilityDetails();
    }

    // Load tax liability details
    function loadLiabilityDetails() {
        let start_date = '';
        let end_date = '';
        
        if ($('#tax_date_filter').val()) {
            start_date = $('input#tax_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            end_date = $('input#tax_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }

        $.ajax({
            url: '{{ route("advancedreports.tax-compliance.liability-details") }}',
            method: 'GET',
            data: {
                start_date: start_date,
                end_date: end_date
            },
            success: function(data) {
                let tbody = $('#liability-tbody');
                tbody.empty();
                
                data.forEach(function(row) {
                    let tr = `
                        <tr>
                            <td>${row.tax_name}</td>
                            <td>${parseFloat(row.tax_rate).toFixed(2)}%</td>
                            <td>${formatCurrency(row.output_tax)}</td>
                            <td>${formatCurrency(row.input_tax_credit)}</td>
                            <td class="${row.net_liability >= 0 ? 'text-red' : 'text-green'}">${formatCurrency(row.net_liability)}</td>
                            <td>${row.sales_transactions}</td>
                            <td>${row.purchase_transactions}</td>
                        </tr>
                    `;
                    tbody.append(tr);
                });
            }
        });
    }

    // Chart update functions
    function updateTaxTrendChart(data) {
        if (taxTrendChart) {
            taxTrendChart.destroy();
        }

        const ctx = document.getElementById('tax-trend-chart').getContext('2d');
        taxTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => item.month),
                datasets: [{
                    label: 'Tax Liability',
                    data: data.map(item => item.tax_amount),
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    fill: true,
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
                }
            }
        });
    }

    function updateJurisdictionChart(data) {
        if (jurisdictionChart) {
            jurisdictionChart.destroy();
        }

        const ctx = document.getElementById('jurisdiction-chart').getContext('2d');
        jurisdictionChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.jurisdiction),
                datasets: [{
                    data: data.map(item => item.tax_amount),
                    backgroundColor: ['#e74c3c', '#f39c12', '#27ae60', '#3498db', '#9b59b6']
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

    function updateRiskAssessment(riskData) {
        $('#risk-level').text(riskData.risk_level);
        $('#risk-percentage').text(riskData.risk_percentage.toFixed(1) + '% Risk Score');
        
        let progressBar = $('#risk-progress');
        progressBar.css('width', riskData.risk_percentage + '%');
        
        if (riskData.risk_level === 'High') {
            progressBar.removeClass('progress-bar-success progress-bar-warning').addClass('progress-bar-danger');
        } else if (riskData.risk_level === 'Medium') {
            progressBar.removeClass('progress-bar-success progress-bar-danger').addClass('progress-bar-warning');
        } else {
            progressBar.removeClass('progress-bar-warning progress-bar-danger').addClass('progress-bar-success');
        }

        // Update risk factors
        let factorsList = $('#risk-factors-list');
        factorsList.html(`
            <h4>Risk Assessment Summary</h4>
            <p>Your tax compliance risk assessment shows <strong>${riskData.risk_level}</strong> risk level based on ${riskData.risk_factors} out of ${riskData.total_factors} risk factors.</p>
            <div class="alert alert-info">
                <strong>Recommendation:</strong> ${riskData.risk_level === 'High' ? 'Immediate action required to address compliance issues.' : 
                riskData.risk_level === 'Medium' ? 'Monitor closely and address identified issues.' : 'Maintain current compliance standards.'}
            </div>
        `);
    }

    function updateOptimizationInsights(insights) {
        let container = $('#optimization-insights');
        container.empty();
        
        insights.forEach(function(insight, index) {
            let card = `
                <div class="col-md-4">
                    <div class="info-box bg-aqua">
                        <span class="info-box-icon">
                            <i class="fas fa-lightbulb"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">${insight.title}</span>
                            <span class="info-box-number">${insight.description}</span>
                            <div class="progress">
                                <div class="progress-bar" style="width: ${Math.random() * 100}%"></div>
                            </div>
                            <span class="progress-description">${insight.recommendation}</span>
                        </div>
                    </div>
                </div>
            `;
            container.append(card);
        });
    }

    function updateFilingDeadlines(deadlines) {
        let tbody = $('#deadlines-tbody');
        tbody.empty();
        
        deadlines.forEach(function(deadline) {
            let statusBadge = '';
            if (deadline.status === 'overdue') {
                statusBadge = '<span class="label label-danger">Overdue</span>';
            } else if (deadline.status === 'urgent') {
                statusBadge = '<span class="label label-warning">Urgent</span>';
            } else {
                statusBadge = '<span class="label label-info">Upcoming</span>';
            }
            
            let tr = `
                <tr>
                    <td>${deadline.period}</td>
                    <td>${deadline.deadline}</td>
                    <td>${deadline.days_remaining}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="generateFilingReport('${deadline.period}')">
                            <i class="fas fa-file-alt"></i> Generate Report
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(tr);
        });
    }

    // Export functionality
    $('#export-tax-compliance').click(function(e) {
        e.preventDefault();

        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i> {{ __("advancedreports::lang.exporting") }}').prop('disabled', true);

        let params = {
            report_type: $('#report_type').val(),
            jurisdiction: $('#tax_jurisdiction').val(),
            period: $('#tax_period').val(),
            _token: '{{ csrf_token() }}'
        };

        if ($('#tax_date_filter').val()) {
            params.start_date = $('input#tax_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            params.end_date = $('input#tax_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }

        $.ajax({
            url: '{{ route("advancedreports.tax-compliance.export") }}',
            type: 'POST',
            data: params,
            xhrFields: { responseType: 'blob' },
            success: function(data, status, xhr) {
                var blob = new Blob([data], {
                    type: xhr.getResponseHeader('Content-Type') || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                });
                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                var filename = 'tax-compliance-report.xlsx';
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

    // Export liability details to Excel
    $('#export-liability').click(function() {
        let data = [];
        $('#tax_liability_table tbody tr').each(function() {
            let row = [];
            $(this).find('td').each(function() {
                row.push($(this).text().replace(new RegExp('[' + currency_symbol.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ',]', 'g'), ''));
            });
            data.push(row);
        });

        let headers = ['Tax Name', 'Tax Rate (%)', 'Output Tax', 'Input Tax Credit', 'Net Liability', 'Sales Trans.', 'Purchase Trans.'];
        data.unshift(headers);

        let ws = XLSX.utils.aoa_to_sheet(data);
        let wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Tax Liability');
        XLSX.writeFile(wb, 'tax_liability_' + new Date().getTime() + '.xlsx');
    });

    // Generate filing report function
    window.generateFilingReport = function(period) {
        let params = {
            report_type: 'filing',
            period: period
        };

        const queryString = new URLSearchParams(params).toString();
        window.open('{{ route("advancedreports.tax-compliance.filing-assistance") }}?' + queryString, '_blank');
    };

    // Initial load
    loadTaxData();
});
</script>
@endsection