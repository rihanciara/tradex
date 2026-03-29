@extends('layouts.app')
@section('title', __('Audit Trail Report'))

@section('content')
<!-- Content Header -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        <i class="fas fa-shield-alt"></i> {{ __('Audit Trail Report') }}
        <small class="text-muted">{{ __('Compliance & Risk Management') }}</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <!-- Summary Cards -->
    <div class="row" id="audit-summary-cards">
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3 id="total-activities">0</h3>
                    <p style="color: white !important;">{{ __('Total Activities') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-list"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="high-risk-activities">0</h3>
                    <p style="color: white !important;">{{ __('High Risk Activities') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3 id="transaction-modifications">0</h3>
                    <p style="color: white !important;">{{ __('Transaction Modifications') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-edit"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="active-users">0</h3>
                    <p style="color: white !important;">{{ __('Active Users') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Activities by Type') }}</h3>
                </div>
                <div class="box-body">
                    <canvas id="activities-by-type-chart" style="height: 250px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Risk Level Distribution') }}</h3>
                </div>
                <div class="box-body">
                    <canvas id="risk-distribution-chart" style="height: 250px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Activity Trend Chart -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Daily Activity Trend') }}</h3>
                </div>
                <div class="box-body">
                    <canvas id="daily-trend-chart" style="height: 200px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Active Users -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Top Active Users') }}</h3>
                </div>
                <div class="box-body">
                    <div id="top-users-cards" class="row"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('Audit Trail Filters')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('audit_date_filter', __('report.date_range') . ':') !!}
                        {!! Form::text('audit_date_filter', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']) !!}
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('audit_users_filter', __('lang_v1.by') . ':') !!}
                        {!! Form::select('audit_users_filter', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('audit_subject_type', __('lang_v1.subject_type') . ':') !!}
                        {!! Form::select('audit_subject_type', $transaction_types, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('audit_risk_filter', __('Risk Level') . ':') !!}
                        {!! Form::select('audit_risk_filter', $risk_categories, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label><br>
                        <button type="button" class="btn btn-success" id="export-audit-trail">
                            <i class="fas fa-download"></i> {{ __('Export CSV') }}
                        </button>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <!-- Data Table -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Detailed Audit Trail') }}</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="audit_trail_table">
                        <thead>
                            <tr>
                                <th>{{ __('Date/Time') }}</th>
                                <th>{{ __('Subject Type') }}</th>
                                <th>{{ __('Action') }}</th>
                                <th>{{ __('User') }}</th>
                                <th>{{ __('Risk Level') }}</th>
                                <th>{{ __('Transaction Details') }}</th>
                                <th>{{ __('Compliance Status') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    let audit_trail_table;
    let activitiesChart, riskChart, trendChart;

    // Initialize date range picker
    $('#audit_date_filter').daterangepicker(
        dateRangeSettings,
        function(start, end) {
            $('#audit_date_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            loadSummaryData();
            audit_trail_table.ajax.reload();
        }
    );

    $('#audit_date_filter').on('cancel.daterangepicker', function(ev, picker) {
        $('#audit_date_filter').val('');
        loadSummaryData();
        audit_trail_table.ajax.reload();
    });

    // Initialize DataTable
    audit_trail_table = $('#audit_trail_table').DataTable({
        processing: true,
        serverSide: true,
        fixedHeader: false,
        aaSorting: [[0, 'desc']],
        ajax: {
            url: '{{ route("advancedreports.audit-trail.data") }}',
            data: function(d) {
                if ($('#audit_date_filter').val()) {
                    d.start_date = $('input#audit_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    d.end_date = $('input#audit_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
                }
                d.user_id = $('#audit_users_filter').val();
                d.subject_type = $('#audit_subject_type').val();
                d.risk_level = $('#audit_risk_filter').val();
            }
        },
        columns: [
            { data: 'created_at', name: 'created_at' },
            { data: 'subject_type_formatted', orderable: false, searchable: false },
            { data: 'description', name: 'description' },
            { data: 'user_info', name: 'created_by' },
            { data: 'risk_badge', orderable: false, searchable: false },
            { data: 'transaction_details', orderable: false, searchable: false },
            { data: 'compliance_status', orderable: false, searchable: false }
        ],
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#audit_trail_table'));
        }
    });

    // Filter change handlers
    $(document).on('change', '#audit_users_filter, #audit_subject_type, #audit_risk_filter', function(){
        audit_trail_table.ajax.reload();
    });

    // Load summary data
    function loadSummaryData() {
        let start_date = '';
        let end_date = '';
        
        if ($('#audit_date_filter').val()) {
            start_date = $('input#audit_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            end_date = $('input#audit_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }

        $.ajax({
            url: '{{ route("advancedreports.audit-trail.summary") }}',
            method: 'GET',
            data: {
                start_date: start_date,
                end_date: end_date
            },
            success: function(data) {
                // Update summary cards
                $('#total-activities').text(data.total_activities);
                $('#high-risk-activities').text(data.high_risk_activities);
                $('#transaction-modifications').text(data.transaction_modifications);
                $('#active-users').text(data.active_users);

                // Update charts
                updateActivitiesByTypeChart(data.activities_by_type);
                updateRiskDistributionChart(data.activities_by_risk);
                updateDailyTrendChart(data.daily_activities);
                updateTopUsersCards(data.top_users);
            }
        });
    }

    // Chart update functions
    function updateActivitiesByTypeChart(data) {
        if (activitiesChart) {
            activitiesChart.destroy();
        }

        const ctx = document.getElementById('activities-by-type-chart').getContext('2d');
        activitiesChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.type),
                datasets: [{
                    data: data.map(item => item.count),
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                        '#9966FF', '#FF9F40', '#FF6384', '#36A2EB'
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

    function updateRiskDistributionChart(data) {
        if (riskChart) {
            riskChart.destroy();
        }

        const ctx = document.getElementById('risk-distribution-chart').getContext('2d');
        riskChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.map(item => item.risk_level),
                datasets: [{
                    data: data.map(item => item.count),
                    backgroundColor: ['#d9534f', '#f0ad4e', '#5cb85c']
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

    function updateDailyTrendChart(data) {
        if (trendChart) {
            trendChart.destroy();
        }

        const ctx = document.getElementById('daily-trend-chart').getContext('2d');
        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => item.date),
                datasets: [{
                    label: 'Activities',
                    data: data.map(item => item.count),
                    borderColor: '#36A2EB',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    fill: true
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

    function updateTopUsersCards(data) {
        let html = '';
        data.forEach((user, index) => {
            let badgeClass = 'primary';
            if (index === 0) badgeClass = 'success';
            else if (index === 1) badgeClass = 'info';
            else if (index === 2) badgeClass = 'warning';

            html += `
                <div class="col-md-4">
                    <div class="info-box">
                        <span class="info-box-icon bg-${badgeClass}">
                            <i class="fas fa-user"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">${user.user_name || 'System'}</span>
                            <span class="info-box-number">${user.activity_count}</span>
                            <span class="info-box-more">Activities</span>
                        </div>
                    </div>
                </div>
            `;
        });
        $('#top-users-cards').html(html);
    }

    // Export functionality
    $('#export-audit-trail').click(function(e) {
        e.preventDefault();

        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i> {{ __("advancedreports::lang.exporting") }}').prop('disabled', true);

        let params = {
            user_id: $('#audit_users_filter').val(),
            subject_type: $('#audit_subject_type').val(),
            risk_level: $('#audit_risk_filter').val(),
            _token: '{{ csrf_token() }}'
        };

        if ($('#audit_date_filter').val()) {
            params.start_date = $('input#audit_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            params.end_date = $('input#audit_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }

        // Use AJAX to download the file properly
        $.ajax({
            url: '{{ route("advancedreports.audit-trail.export") }}',
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
                var filename = 'audit-trail-report.xlsx';
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
    loadSummaryData();
});
</script>
@endsection