@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.customer_group_report'))

@section('css')
<link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.css" />
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css">
<style>
    .kpi-widget {
        transition: transform 0.2s ease-in-out;
    }
    
    .kpi-widget:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .leaderboard-rank {
        font-weight: bold;
        font-size: 1.2em;
    }
    
    .rank-1 { color: #FFD700; } /* Gold */
    .rank-2 { color: #C0C0C0; } /* Silver */  
    .rank-3 { color: #CD7F32; } /* Bronze */
    
    .drill-down-btn {
        cursor: pointer;
        color: #337ab7;
        text-decoration: underline;
    }
    
    .drill-down-btn:hover {
        color: #23527c;
    }
    
    .btn.drill-down-btn {
        color: white !important;
    }
    
    .btn.drill-down-btn:hover {
        color: white !important;
    }
    
    .risk-high { color: #d9534f; font-weight: bold; }
    .risk-medium { color: #f0ad4e; font-weight: bold; }
    .risk-low { color: #5cb85c; font-weight: bold; }
    
    .aging-0-30 { background-color: #d4edda; }
    .aging-31-60 { background-color: #fff3cd; }
    .aging-61-90 { background-color: #f8d7da; }
    .aging-90-plus { background-color: #f5c6cb; }
    
    .drill-down-section {
        display: none;
        margin-top: 20px;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background-color: #f9f9f9;
    }
    
    .breadcrumb-drill {
        background: none;
        padding: 0;
        margin-bottom: 10px;
    }
    
    .breadcrumb-drill > li + li:before {
        content: ">";
        padding: 0 5px;
        color: #ccc;
    }
    
    .performance-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        color: white;
        font-size: 12px;
        font-weight: bold;
    }
    
    .badge-gold { background-color: #FFD700; color: #333; }
    .badge-silver { background-color: #C0C0C0; color: #333; }
    .badge-bronze { background-color: #CD7F32; color: white; }
    
    /* Clean Select2 styling */
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

<!-- Navigation Breadcrumb -->
<div style="padding: 15px; background: #f4f4f4; border-bottom: 1px solid #ddd; margin-bottom: 0;">
    <a href="{{ route('advancedreports.index') }}" class="btn btn-primary btn-sm">
        <i class="fas fa-arrow-left"></i> Back to Advanced Reports Dashboard
    </a>
</div>

<!-- Content Header -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ __('advancedreports::lang.customer_group_report')}}
        <small class="text-muted">{{ __('advancedreports::lang.customer_group_description') }}</small>
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
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('cg_date_range', __('Date Range') . ':') !!}
                    {!! Form::text('cg_date_range', null, ['class' => 'form-control', 'id' => 'cg_date_range', 'placeholder' => __('Select Date Range'), 'readonly']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('cg_location_filter', __('Location') . ':') !!}
                    {!! Form::select('cg_location_filter', $locations, null, ['class' => 'form-control', 'id' => 'cg_location_filter']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('cg_group_filter', __('Customer Group') . ':') !!}
                    {!! Form::select('cg_group_filter', $customer_groups, null, ['class' => 'form-control', 'id' => 'cg_group_filter']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('cg_salesperson_filter', __('advancedreports::lang.top_salesperson') . ':') !!}
                    {!! Form::select('cg_salesperson_filter', $salespeople, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('All Salespeople'), 'id' => 'cg_salesperson_filter']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('cg_payment_method', __('Payment Method') . ':') !!}
                    {!! Form::select('cg_payment_method', $payment_methods, null, ['class' => 'form-control', 'id' => 'cg_payment_method']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <br>
                    <button type="button" id="cg_filter_btn" class="btn btn-primary">{{ __('Filter') }}</button>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Filter Options -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default collapsed-box">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('Advanced Options') }}</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" id="include_returns" checked> {{ __('advancedreports::lang.include_returns') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" id="include_drafts"> {{ __('advancedreports::lang.include_drafts') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" id="dynamic_grouping" checked> {{ __('advancedreports::lang.dynamic_grouping') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row" id="kpi-cards">
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-aqua kpi-widget">
                <div class="inner">
                    <h3 id="total-customers">0</h3>
                    <p>{{ __('Total Customers') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-green kpi-widget">
                <div class="inner">
                    <h3 id="net-sales"><span class="display_currency" data-currency_symbol="true">0</span></h3>
                    <p>{{ __('advancedreports::lang.net_sales') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-yellow kpi-widget">
                <div class="inner">
                    <h3 id="collection-efficiency">0%</h3>
                    <p>{{ __('advancedreports::lang.collection_efficiency') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-percentage"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-red kpi-widget">
                <div class="inner">
                    <h3 id="outstanding-due"><span class="display_currency" data-currency_symbol="true">0</span></h3>
                    <p>{{ __('advancedreports::lang.outstanding_due') }}</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performers Row -->
    <div class="row">
        <div class="col-md-6">
            <div class="info-box bg-blue">
                <span class="info-box-icon"><i class="fas fa-trophy"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ __('advancedreports::lang.top_performing_group') }}</span>
                    <span class="info-box-number" id="top-group">{{ __('Loading...') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info-box bg-purple">
                <span class="info-box-icon"><i class="fas fa-user-tie"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ __('advancedreports::lang.top_salesperson') }}</span>
                    <span class="info-box-number" id="top-salesperson">{{ __('Loading...') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Leaderboard -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.group_leaderboard') }}</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="refresh-data">
                            <i class="fas fa-sync"></i> {{ __('Refresh') }}
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table id="leaderboard-table" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Rank') }}</th>
                                    <th>{{ __('Customer Group') }}</th>
                                    <th>{{ __('advancedreports::lang.top_salesperson') }}</th>
                                    <th>{{ __('advancedreports::lang.customer_count') }}</th>
                                    <th>{{ __('advancedreports::lang.invoice_count') }}</th>
                                    <th>{{ __('Gross Sales') }}</th>
                                    <th>{{ __('Returns') }}</th>
                                    <th>{{ __('advancedreports::lang.net_sales') }}</th>
                                    <th>{{ __('Collections') }}</th>
                                    <th>{{ __('advancedreports::lang.collection_efficiency') }}</th>
                                    <th>{{ __('advancedreports::lang.outstanding_due') }}</th>
                                    <th>{{ __('advancedreports::lang.margin_percentage') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="13" class="text-center">
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

    <!-- Drill-down Section -->
    <div id="drill-down-container"></div>

    <!-- Aging Analysis -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.aging_analysis') }}</h3>
                </div>
                <div class="box-body">
                    <canvas id="aging-chart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ __('advancedreports::lang.aging_buckets') }}</h3>
                </div>
                <div class="box-body">
                    <div id="aging-summary">
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
                                <option value="comprehensive">{{ __('Comprehensive Report') }}</option>
                                <option value="group_leaderboard">{{ __('advancedreports::lang.group_leaderboard') }}</option>
                                <option value="aging_analysis">{{ __('advancedreports::lang.aging_analysis') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" id="export-data" class="btn btn-success">
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
    $('#cg_date_range').daterangepicker({
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

    // Initialize Select2 for salesperson filter
    $('#cg_salesperson_filter').select2({
        placeholder: "{{ __('All Salespeople') }}",
        allowClear: true
    });

    // Charts variables
    let agingChart;

    // Load initial data
    loadCustomerGroupData();

    // Filter button click
    $('#cg_filter_btn, #refresh-data').click(function() {
        loadCustomerGroupData();
    });

    // Filter change events
    $('#cg_date_range').on('apply.daterangepicker', function() {
        loadCustomerGroupData();
    });

    $('#cg_location_filter, #cg_group_filter, #cg_payment_method, #include_returns, #include_drafts, #dynamic_grouping').change(function() {
        loadCustomerGroupData();
    });

    // Load customer group data via AJAX
    function loadCustomerGroupData() {
        let start_date = moment().startOf('year').format('YYYY-MM-DD');
        let end_date = moment().format('YYYY-MM-DD');
        
        if ($('#cg_date_range').val()) {
            start_date = $('input#cg_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
            end_date = $('input#cg_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }
        
        $.ajax({
            url: '{{ route("advancedreports.customer-group-performance.data") }}',
            type: 'GET',
            data: {
                start_date: start_date,
                end_date: end_date,
                location_id: $('#cg_location_filter').val(),
                customer_group_id: $('#cg_group_filter').val(),
                salesperson_id: $('#cg_salesperson_filter').val(),
                payment_method: $('#cg_payment_method').val(),
                include_returns: $('#include_returns').is(':checked'),
                include_drafts: $('#include_drafts').is(':checked')
            },
            success: function(data) {
                updateKPICards(data.summary_metrics);
                updateLeaderboard(data.group_leaderboard);
                updateAgingAnalysis(data.aging_analysis);
            },
            error: function(xhr, status, error) {
                console.error('Error loading customer group data:', error);
                toastr.error('{{ __("Error loading customer group data") }}');
            }
        });
    }

    // Update KPI cards
    function updateKPICards(metrics) {
        $('#total-customers').text(formatNumber(metrics.total_customers || 0));
        $('#net-sales').html('<span class="display_currency" data-currency_symbol="true">' + (metrics.net_sales || 0).toFixed(currencySettings.precision) + '</span>');
        $('#collection-efficiency').text(formatNumber(metrics.collection_efficiency || 0) + '%');
        $('#outstanding-due').html('<span class="display_currency" data-currency_symbol="true">' + (metrics.outstanding_due || 0).toFixed(currencySettings.precision) + '</span>');
        $('#top-group').text(metrics.top_group || 'No Data');
        $('#top-salesperson').text(metrics.top_salesperson || 'No Data');

        // Apply currency conversion
        __currency_convert_recursively($('#kpi-cards'));
    }

    // Update leaderboard table
    function updateLeaderboard(leaderboard) {
        const tbody = $('#leaderboard-table tbody');
        tbody.empty();
        
        if (!leaderboard || leaderboard.length === 0) {
            tbody.append('<tr><td colspan="13" class="text-center text-muted">No data available</td></tr>');
            return;
        }
        
        leaderboard.forEach((row, index) => {
            const rank = index + 1;
            const rankClass = getRankClass(rank);
            const rankBadge = getRankBadge(rank);
            
            tbody.append(`
                <tr>
                    <td>
                        <span class="leaderboard-rank ${rankClass}">${rank}</span>
                        ${rankBadge}
                    </td>
                    <td>
                        <span class="drill-down-btn" data-level="group" data-value="${row.customer_group}">
                            ${row.customer_group}
                        </span>
                    </td>
                    <td>
                        <span class="drill-down-btn" data-level="salesperson" data-value="${row.salesperson_id}">
                            ${row.salesperson_name}
                        </span>
                    </td>
                    <td>${formatNumber(row.customer_count)}</td>
                    <td>${formatNumber(row.invoice_count)}</td>
                    <td>${formatCurrency(row.gross_sales)}</td>
                    <td>${formatCurrency(row.returns)}</td>
                    <td>${formatCurrency(row.net_sales)}</td>
                    <td>${formatCurrency(row.total_collected || 0)}</td>
                    <td>${formatNumber(row.collection_efficiency || 0)}%</td>
                    <td>${formatCurrency(row.outstanding_due || 0)}</td>
                    <td>${formatNumber(row.margin_percentage || 0)}%</td>
                    <td>
                        <button class="btn btn-xs btn-primary drill-down-btn"
                                data-level="salespeople"
                                data-value="${row.customer_group}">
                            <i class="fas fa-search"></i> Drill Down
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    // Handle drill-down clicks
    $(document).on('click', '.drill-down-btn', function() {
        const level = $(this).data('level');
        const value = $(this).data('value');
        
        
        switch(level) {
            case 'group':
                loadSalespersonDrilldown(value);
                break;
            case 'salespeople':
                loadSalespersonDrilldown(value);
                break;
            case 'salesperson':
                loadCustomerDrilldown(value);
                break;
            case 'customer':
                loadInvoiceDrilldown(value);
                break;
            default:
                console.warn('Unknown drill-down level:', level);
        }
    });

    // Load salesperson drill-down
    function loadSalespersonDrilldown(customerGroup) {
        let start_date = $('input#cg_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
        let end_date = $('input#cg_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
        
        $.ajax({
            url: '{{ route("advancedreports.customer-group-performance.salespeople", ":group") }}'.replace(':group', encodeURIComponent(customerGroup)),
            type: 'GET',
            data: {
                start_date: start_date,
                end_date: end_date,
                location_id: $('#cg_location_filter').val(),
                payment_method: $('#cg_payment_method').val()
            },
            success: function(salespeople) {
                showDrilldownSection('salespeople', customerGroup, salespeople);
            },
            error: function(xhr, status, error) {
                toastr.error('Error loading salesperson data');
            }
        });
    }

    // Load customer drill-down
    function loadCustomerDrilldown(salespersonId) {
        let start_date = $('input#cg_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
        let end_date = $('input#cg_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
        
        $.ajax({
            url: '{{ route("advancedreports.customer-group-performance.customers", ":salesperson") }}'.replace(':salesperson', salespersonId),
            type: 'GET',
            data: {
                start_date: start_date,
                end_date: end_date,
                location_id: $('#cg_location_filter').val(),
                payment_method: $('#cg_payment_method').val()
            },
            success: function(customers) {
                showDrilldownSection('customers', salespersonId, customers);
            },
            error: function(xhr, status, error) {
                toastr.error('Error loading customer data');
            }
        });
    }

    // Load invoice drill-down
    function loadInvoiceDrilldown(customerId) {
        let start_date = $('input#cg_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
        let end_date = $('input#cg_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
        
        $.ajax({
            url: '{{ route("advancedreports.customer-group-performance.invoices", ":customer") }}'.replace(':customer', customerId),
            type: 'GET',
            data: {
                start_date: start_date,
                end_date: end_date,
                location_id: $('#cg_location_filter').val()
            },
            success: function(invoices) {
                showDrilldownSection('invoices', customerId, invoices);
            },
            error: function(xhr, status, error) {
                toastr.error('Error loading invoice data');
            }
        });
    }

    // Show drill-down section
    function showDrilldownSection(type, identifier, data) {
        const container = $('#drill-down-container');
        const title = getTitleForDrilldown(type, identifier);
        let tableHtml = '';
        
        if (type === 'salespeople') {
            tableHtml = buildSalespeopleTable(data);
        } else if (type === 'customers') {
            tableHtml = buildCustomersTable(data);
        } else if (type === 'invoices') {
            tableHtml = buildInvoicesTable(data);
        }
        
        const drilldownHtml = `
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-info drill-down-section" style="display: block;">
                        <div class="box-header with-border">
                            <h3 class="box-title">${title}</h3>
                            <div class="box-tools pull-right">
                                <button type="button" class="btn btn-box-tool" onclick="$(this).closest('.drill-down-section').slideUp()">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="box-body">
                            <ol class="breadcrumb breadcrumb-drill">
                                <li><a href="#" onclick="$('#drill-down-container').empty()">Groups</a></li>
                                <li class="active">${type.charAt(0).toUpperCase() + type.slice(1)}</li>
                            </ol>
                            <div class="table-responsive">
                                ${tableHtml}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.html(drilldownHtml);
        container.find('.drill-down-section').slideDown();
    }

    // Update aging analysis
    function updateAgingAnalysis(agingData) {
        // Destroy existing chart
        if (agingChart) {
            agingChart.destroy();
        }
        
        if (!agingData || Object.keys(agingData).length === 0) {
            $('#aging-summary').html('<div class="text-center text-muted">No aging data available</div>');
            return;
        }
        
        // Prepare chart data
        const labels = Object.keys(agingData);
        const amounts = labels.map(label => agingData[label].total_amount);
        const counts = labels.map(label => agingData[label].count);
        
        // Create aging chart
        const agingCtx = document.getElementById('aging-chart').getContext('2d');
        agingChart = new Chart(agingCtx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: amounts,
                    backgroundColor: ['#d4edda', '#fff3cd', '#f8d7da', '#f5c6cb'],
                    borderWidth: 1
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
        
        // Update aging summary
        let summaryHtml = '';
        labels.forEach((label, index) => {
            const item = agingData[label];
            const agingClass = getAgingClass(label);
            summaryHtml += `
                <div class="row ${agingClass}" style="padding: 5px 0;">
                    <div class="col-md-4"><strong>${label}</strong></div>
                    <div class="col-md-4">${item.count} invoices</div>
                    <div class="col-md-4">${formatCurrency(item.total_amount)}</div>
                </div>
            `;
        });
        
        $('#aging-summary').html(summaryHtml);
    }

    // Export functionality
    $('#export-data').click(function(e) {
        e.preventDefault();

        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i> {{ __("advancedreports::lang.exporting") }}').prop('disabled', true);

        let start_date = $('input#cg_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
        let end_date = $('input#cg_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');

        const params = {
            export_type: $('#export-type').val(),
            start_date: start_date,
            end_date: end_date,
            location_id: $('#cg_location_filter').val(),
            customer_group_id: $('#cg_group_filter').val(),
            salesperson_id: $('#cg_salesperson_filter').val(),
            payment_method: $('#cg_payment_method').val(),
            include_returns: $('#include_returns').is(':checked'),
            include_drafts: $('#include_drafts').is(':checked'),
            _token: '{{ csrf_token() }}'
        };

        // Use AJAX to download the file properly
        $.ajax({
            url: '{{ route("advancedreports.customer-group-performance.export") }}',
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
                var filename = 'customer-group-performance-report.xlsx';
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

    // Currency settings
    const currencySettings = {
        symbol: '{{ session("currency")["symbol"] ?? "$" }}',
        precision: {{ session('business.currency_precision') ?? 2 }},
        placement: '{{ session("business.currency_symbol_placement") ?? "before" }}'
    };

    // Helper functions
    function formatCurrency(amount) {
        if (typeof __currency_trans_from_en === 'function') {
            return __currency_trans_from_en(parseFloat(amount || 0).toFixed(currencySettings.precision), true);
        }

        // Fallback formatting
        const formatted = parseFloat(amount || 0).toLocaleString(undefined, {
            minimumFractionDigits: currencySettings.precision,
            maximumFractionDigits: currencySettings.precision
        });
        return currencySettings.placement === 'after' ?
            formatted + currencySettings.symbol :
            currencySettings.symbol + formatted;
    }

    function formatNumber(num) {
        return parseFloat(num || 0).toLocaleString(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

    function getRankClass(rank) {
        switch(rank) {
            case 1: return 'rank-1';
            case 2: return 'rank-2';
            case 3: return 'rank-3';
            default: return '';
        }
    }

    function getRankBadge(rank) {
        switch(rank) {
            case 1: return '<span class="performance-badge badge-gold">🥇</span>';
            case 2: return '<span class="performance-badge badge-silver">🥈</span>';
            case 3: return '<span class="performance-badge badge-bronze">🥉</span>';
            default: return '';
        }
    }

    function getAgingClass(bucket) {
        switch(bucket) {
            case '0-30 days': return 'aging-0-30';
            case '31-60 days': return 'aging-31-60';
            case '61-90 days': return 'aging-61-90';
            case '90+ days': return 'aging-90-plus';
            default: return '';
        }
    }

    function getTitleForDrilldown(type, identifier) {
        switch(type) {
            case 'salespeople': return `{{ __('advancedreports::lang.salesperson_performance') }} - ${identifier}`;
            case 'customers': return `{{ __('advancedreports::lang.customer_drill_down') }}`;
            case 'invoices': return `{{ __('advancedreports::lang.invoice_drill_down') }}`;
            default: return 'Drill-down Analysis';
        }
    }

    function buildSalespeopleTable(data) {
        let html = `
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('advancedreports::lang.top_salesperson') }}</th>
                        <th>{{ __('advancedreports::lang.customer_count') }}</th>
                        <th>{{ __('advancedreports::lang.invoice_count') }}</th>
                        <th>{{ __('Gross Sales') }}</th>
                        <th>{{ __('Returns') }}</th>
                        <th>{{ __('advancedreports::lang.net_sales') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        data.forEach(row => {
            html += `
                <tr>
                    <td>${row.salesperson_name}</td>
                    <td>${formatNumber(row.customer_count)}</td>
                    <td>${formatNumber(row.invoice_count)}</td>
                    <td>${formatCurrency(row.gross_sales)}</td>
                    <td>${formatCurrency(row.returns)}</td>
                    <td>${formatCurrency(row.net_sales)}</td>
                    <td>
                        <button class="btn btn-xs btn-info drill-down-btn"
                                data-level="salesperson"
                                data-value="${row.salesperson_id}">
                            View Customers
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        return html;
    }

    function buildCustomersTable(data) {
        let html = `
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Mobile') }}</th>
                        <th>{{ __('Total Sales') }}</th>
                        <th>{{ __('Returns') }}</th>
                        <th>{{ __('advancedreports::lang.net_sales') }}</th>
                        <th>{{ __('advancedreports::lang.outstanding_due') }}</th>
                        <th>{{ __('Last Sale') }}</th>
                        <th>{{ __('Risk') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        data.forEach(row => {
            const riskClass = getRiskClass(row.risk_tag);
            html += `
                <tr>
                    <td>${row.customer_name}</td>
                    <td>${row.mobile || ''}</td>
                    <td>${formatCurrency(row.total_sales)}</td>
                    <td>${formatCurrency(row.returns)}</td>
                    <td>${formatCurrency(row.net_sales)}</td>
                    <td>${formatCurrency(row.outstanding_due)}</td>
                    <td>${row.last_sale_date}</td>
                    <td><span class="${riskClass}">${row.risk_tag}</span></td>
                    <td>
                        <button class="btn btn-xs btn-success drill-down-btn" 
                                data-level="customer" 
                                data-value="${row.customer_id}">
                            View Invoices
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        return html;
    }

    function buildInvoicesTable(data) {
        let html = `
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('Invoice No') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Products') }}</th>
                        <th>{{ __('Quantity') }}</th>
                        <th>{{ __('Amount') }}</th>
                        <th>{{ __('Paid') }}</th>
                        <th>{{ __('Due') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        data.forEach(row => {
            html += `
                <tr>
                    <td>${row.invoice_no}</td>
                    <td>${row.transaction_date}</td>
                    <td>${row.products}</td>
                    <td>${formatNumber(row.total_quantity)}</td>
                    <td>${formatCurrency(row.amount)}</td>
                    <td>${formatCurrency(row.paid)}</td>
                    <td>${formatCurrency(row.due)}</td>
                    <td><span class="label ${getPaymentStatusClass(row.payment_status)}">${row.payment_status}</span></td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        return html;
    }

    function getRiskClass(risk) {
        switch(risk) {
            case 'High Risk': return 'risk-high';
            case 'Medium Risk': return 'risk-medium';
            case 'Low Risk': return 'risk-low';
            default: return '';
        }
    }

    function getPaymentStatusClass(status) {
        switch(status) {
            case 'Paid': return 'label-success';
            case 'Partial': return 'label-warning';
            case 'Due': return 'label-danger';
            default: return 'label-default';
        }
    }

    // Initial load
    loadCustomerGroupData();
});
</script>
@endsection