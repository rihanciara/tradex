@extends('advancedreports::layouts.app')
@section('title', __('Cash Flow Report'))

@section('content')

<!-- Add CSRF token meta tag -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1><i class="fa fa-chart-line"></i> Cash Flow Report
        <small>Track daily cash flow, payments, and forecasting</small>
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
    <!-- Filters -->
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', [
            'title' => __('report.filters'),
            'class' => 'box-primary'
            ])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'location_filter']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('start_date', __('business.start_date') . ':') !!}
                    {!! Form::text('start_date', '', ['class' => 'form-control', 'id' => 'start_date', 'readonly']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('end_date', __('advancedreports::lang.end_date') . ':') !!}
                    {!! Form::text('end_date', '', ['class' => 'form-control', 'id' => 'end_date', 'readonly']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <br>
                    <button type="button" class="btn btn-primary" id="filter_btn">
                        <i class="fa fa-filter"></i> @lang('advancedreports::lang.filter')
                    </button>
                    <button type="button" class="btn btn-success" id="export_btn">
                        <i class="fa fa-download"></i> {{ __('lang_v1.export') }}
                    </button>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Cash Flow Summary Cards -->
    <div class="row" id="summary_cards" style="display: none;">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-arrow-up"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Cash Inflows</span>
                    <span class="info-box-number" id="cash_inflows"><span class="display_currency" data-currency_symbol="true">0</span></span>
                    <div class="progress">
                        <div class="progress-bar bg-green" id="inflows_progress" style="width: 0%"></div>
                    </div>
                    <span class="progress-description" id="inflows_change">0% from last period</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-red"><i class="fa fa-arrow-down"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Cash Outflows</span>
                    <span class="info-box-number" id="cash_outflows"><span class="display_currency" data-currency_symbol="true">0</span></span>
                    <div class="progress">
                        <div class="progress-bar bg-red" id="outflows_progress" style="width: 0%"></div>
                    </div>
                    <span class="progress-description" id="outflows_change">0% from last period</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa fa-exchange-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Net Cash Flow</span>
                    <span class="info-box-number" id="net_cash_flow"><span class="display_currency" data-currency_symbol="true">0</span></span>
                    <div class="progress">
                        <div class="progress-bar bg-aqua" id="net_flow_progress" style="width: 0%"></div>
                    </div>
                    <span class="progress-description" id="net_flow_change">0% from last period</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-purple"><i class="fa fa-wallet"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Cash Position</span>
                    <span class="info-box-number" id="cash_position"><span class="display_currency" data-currency_symbol="true">0</span></span>
                    <div class="progress">
                        <div class="progress-bar bg-purple" id="position_progress" style="width: 0%"></div>
                    </div>
                    <span class="progress-description" id="position_change">Closing balance</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Outstanding Balances Cards -->
    <div class="row" id="balances_cards" style="display: none;">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-receipt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Outstanding Receivables</span>
                    <span class="info-box-number" id="receivables_amount"><span class="display_currency" data-currency_symbol="true">0</span></span>
                    <div class="progress">
                        <div class="progress-bar bg-yellow" id="receivables_progress" style="width: 0%"></div>
                    </div>
                    <span class="progress-description" id="receivables_count">0 invoices</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-orange"><i class="fa fa-file-invoice-dollar"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Outstanding Payables</span>
                    <span class="info-box-number" id="payables_amount"><span class="display_currency" data-currency_symbol="true">0</span></span>
                    <div class="progress">
                        <div class="progress-bar bg-orange" id="payables_progress" style="width: 0%"></div>
                    </div>
                    <span class="progress-description" id="payables_count">0 bills</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-teal"><i class="fa fa-balance-scale"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Working Capital</span>
                    <span class="info-box-number" id="working_capital"><span class="display_currency" data-currency_symbol="true">0</span></span>
                    <div class="progress">
                        <div class="progress-bar bg-teal" id="working_capital_progress" style="width: 0%"></div>
                    </div>
                    <span class="progress-description">Receivables - Payables</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fa fa-chart-pie"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Liquidity Ratio</span>
                    <span class="info-box-number" id="liquidity_ratio">0.0</span>
                    <div class="progress">
                        <div class="progress-bar bg-info" id="liquidity_progress" style="width: 0%"></div>
                    </div>
                    <span class="progress-description">Cash / Payables</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Tabs -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-chart-line"></i> Cash Flow Analysis</h3>
                </div>
                <div class="box-body">
                    <!-- Navigation Tabs -->
                    <ul class="nav nav-tabs nav-justified">
                        <li class="active">
                            <a href="#daily_cash_flow" data-toggle="tab">
                                <i class="fa fa-calendar-day"></i> <span class="hidden-xs">Daily Cash Flow</span>
                            </a>
                        </li>
                        <li>
                            <a href="#payment_methods" data-toggle="tab">
                                <i class="fa fa-credit-card"></i> <span class="hidden-xs">Payment Methods</span>
                            </a>
                        </li>
                        <li>
                            <a href="#receivables" data-toggle="tab">
                                <i class="fa fa-receipt"></i> <span class="hidden-xs">Receivables</span>
                            </a>
                        </li>
                        <li>
                            <a href="#payables" data-toggle="tab">
                                <i class="fa fa-file-invoice-dollar"></i> <span class="hidden-xs">Payables</span>
                            </a>
                        </li>
                        <li>
                            <a href="#cash_forecast" data-toggle="tab">
                                <i class="fa fa-chart-area"></i> <span class="hidden-xs">Cash Forecast</span>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" style="margin-top: 20px;">
                        <!-- Daily Cash Flow Tab -->
                        <div class="tab-pane active" id="daily_cash_flow">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="daily_cash_flow_table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Cash Inflow</th>
                                            <th>Cash Outflow</th>
                                            <th>Net Cash Flow</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr class="bg-gray font-17 text-center footer-total">
                                            <td><strong>Total:</strong></td>
                                            <td class="footer_total_inflows"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td class="footer_total_outflows"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td class="footer_total_net_flow"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Payment Methods Tab -->
                        <div class="tab-pane" id="payment_methods">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="payment_methods_table">
                                    <thead>
                                        <tr>
                                            <th>Payment Method</th>
                                            <th>Transaction Count</th>
                                            <th>Total Amount</th>
                                            <th>Average Amount</th>
                                            <th>Inflows</th>
                                            <th>Outflows</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr class="bg-gray font-17 text-center footer-total">
                                            <td><strong>Total:</strong></td>
                                            <td class="footer_payment_count">0</td>
                                            <td class="footer_payment_total"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td class="footer_payment_average"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td class="footer_payment_inflows"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td class="footer_payment_outflows"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td>100%</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Receivables Tab -->
                        <div class="tab-pane" id="receivables">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="receivables_table">
                                    <thead>
                                        <tr>
                                            <th>Action</th>
                                            <th>Date</th>
                                            <th>Invoice No</th>
                                            <th>Customer</th>
                                            <th>Location</th>
                                            <th>Total Amount</th>
                                            <th>Due Amount</th>
                                            <th>Overdue Status</th>
                                            <th>Payment Status</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr class="bg-gray font-17 text-center footer-total">
                                            <td colspan="5"><strong>Total:</strong></td>
                                            <td class="footer_receivables_total"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td class="footer_receivables_due"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Payables Tab -->
                        <div class="tab-pane" id="payables">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="payables_table">
                                    <thead>
                                        <tr>
                                            <th>Action</th>
                                            <th>Date</th>
                                            <th>Reference No</th>
                                            <th>Supplier</th>
                                            <th>Type</th>
                                            <th>Location</th>
                                            <th>Total Amount</th>
                                            <th>Due Amount</th>
                                            <th>Overdue Status</th>
                                            <th>Payment Status</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr class="bg-gray font-17 text-center footer-total">
                                            <td colspan="6"><strong>Total:</strong></td>
                                            <td class="footer_payables_total"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td class="footer_payables_due"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Cash Forecast Tab -->
                        <div class="tab-pane" id="cash_forecast">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="forecast_days">Forecast Period (Days):</label>
                                        <select id="forecast_days" class="form-control">
                                            <option value="7">7 Days</option>
                                            <option value="14">14 Days</option>
                                            <option value="30" selected>30 Days</option>
                                            <option value="60">60 Days</option>
                                            <option value="90">90 Days</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <button type="button" class="btn btn-primary" id="generate_forecast">
                                        <i class="fa fa-chart-area"></i> Generate Forecast
                                    </button>
                                </div>
                            </div>

                            <div class="row" id="forecast_summary" style="display: none;">
                                <div class="col-md-12">
                                    <div class="alert alert-info">
                                        <h4><i class="fa fa-info-circle"></i> Current Cash Position</h4>
                                        Current Balance: <strong id="current_cash_balance"><span class="display_currency" data-currency_symbol="true">0</span></strong>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive" id="forecast_table_container" style="display: none;">
                                <table class="table table-bordered table-striped" id="cash_forecast_table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Expected Receivables</th>
                                            <th>Expected Payables</th>
                                            <th>Net Cash Flow</th>
                                            <th>Projected Balance</th>
                                            <th>Trend</th>
                                        </tr>
                                    </thead>
                                    <tbody id="forecast_table_body">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade cash_flow_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@stop

@section('javascript')
<script type="text/javascript">
    // Currency formatting function
    function formatCurrency(num) {
        return __currency_trans_from_en(num.toFixed(2), true);
    }
    
    function formatNumber(num, decimals = 0) {
        return num.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    // Calculate percentage change
    function calculatePercentageChange(current, previous) {
        if (previous == 0) {
            return current > 0 ? 100 : 0;
        }
        return ((current - previous) / Math.abs(previous)) * 100;
    }

    $(document).ready(function() {
        // Initialize date pickers
        $('#start_date, #end_date').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd'
        });

        // Set default dates (last 30 days)
        var end_date = moment().format('YYYY-MM-DD');
        var start_date = moment().subtract(30, 'days').format('YYYY-MM-DD');
        
        $('#start_date').val(start_date);
        $('#end_date').val(end_date);

        // Initialize DataTables
        var daily_cash_flow_table = $('#daily_cash_flow_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.cash-flow.daily-data') }}",
                data: function (d) {
                    d.location_id = $('#location_filter').val();
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                }
            },
            columns: [
                { data: 'formatted_date', name: 'date', searchable: true },
                { data: 'formatted_cash_inflow', name: 'cash_inflow', searchable: false },
                { data: 'formatted_cash_outflow', name: 'cash_outflow', searchable: false },
                { data: 'formatted_net_flow', name: 'net_flow', searchable: false },
                { data: 'flow_indicator', name: 'flow_indicator', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']],
            "fnDrawCallback": function (oSettings) {
                __currency_convert_recursively($('#daily_cash_flow_table'));
            },
            "footerCallback": function ( row, data, start, end, display ) {
                var footer_total_inflows = 0;
                var footer_total_outflows = 0;
                var footer_total_net_flow = 0;

                for (var r in data){
                    footer_total_inflows += $(data[r].formatted_cash_inflow).data('orig-value') ? parseFloat($(data[r].formatted_cash_inflow).data('orig-value')) : 0;
                    footer_total_outflows += $(data[r].formatted_cash_outflow).data('orig-value') ? parseFloat($(data[r].formatted_cash_outflow).data('orig-value')) : 0;
                    footer_total_net_flow += $(data[r].formatted_net_flow).data('orig-value') ? parseFloat($(data[r].formatted_net_flow).data('orig-value')) : 0;
                }

                $('.footer_total_inflows').html(__currency_trans_from_en(footer_total_inflows, true));
                $('.footer_total_outflows').html(__currency_trans_from_en(footer_total_outflows, true));
                $('.footer_total_net_flow').html(__currency_trans_from_en(footer_total_net_flow, true));
            }
        });

        var payment_methods_table = $('#payment_methods_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.cash-flow.payment-methods') }}",
                data: function (d) {
                    d.location_id = $('#location_filter').val();
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                }
            },
            columns: [
                { data: 'method_display', name: 'method' },
                { data: 'transaction_count', name: 'transaction_count', searchable: false },
                { data: 'formatted_total_amount', name: 'total_amount', searchable: false },
                { data: 'formatted_average_amount', name: 'average_amount', searchable: false },
                { data: 'formatted_inflow_amount', name: 'inflow_amount', searchable: false },
                { data: 'formatted_outflow_amount', name: 'outflow_amount', searchable: false },
                { data: 'percentage', name: 'percentage', orderable: false, searchable: false }
            ],
            order: [[2, 'desc']],
            "fnDrawCallback": function (oSettings) {
                __currency_convert_recursively($('#payment_methods_table'));
            },
            "footerCallback": function ( row, data, start, end, display ) {
                var footer_total_count = 0;
                var footer_total_amount = 0;
                var footer_total_inflows = 0;
                var footer_total_outflows = 0;

                for (var r in data){
                    footer_total_count += parseInt(data[r].transaction_count) || 0;
                    footer_total_amount += $(data[r].formatted_total_amount).data('orig-value') ? parseFloat($(data[r].formatted_total_amount).data('orig-value')) : 0;
                    footer_total_inflows += $(data[r].formatted_inflow_amount).data('orig-value') ? parseFloat($(data[r].formatted_inflow_amount).data('orig-value')) : 0;
                    footer_total_outflows += $(data[r].formatted_outflow_amount).data('orig-value') ? parseFloat($(data[r].formatted_outflow_amount).data('orig-value')) : 0;
                }

                var average_amount = footer_total_count > 0 ? footer_total_amount / footer_total_count : 0;

                $('.footer_payment_count').text(footer_total_count);
                $('.footer_payment_total').html(__currency_trans_from_en(footer_total_amount, true));
                $('.footer_payment_average').html(__currency_trans_from_en(average_amount, true));
                $('.footer_payment_inflows').html(__currency_trans_from_en(footer_total_inflows, true));
                $('.footer_payment_outflows').html(__currency_trans_from_en(footer_total_outflows, true));

                // Calculate percentages
                if (footer_total_amount > 0) {
                    for (var r in data){
                        var row_amount = $(data[r].formatted_total_amount).data('orig-value') ? parseFloat($(data[r].formatted_total_amount).data('orig-value')) : 0;
                        var percentage = (row_amount / footer_total_amount * 100).toFixed(1) + '%';
                        $(data[r].percentage).text(percentage);
                    }
                }
            }
        });

        var receivables_table = $('#receivables_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.cash-flow.receivables') }}",
                data: function (d) {
                    d.location_id = $('#location_filter').val();
                }
            },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'transaction_date', name: 't.transaction_date' },
                { data: 'invoice_no', name: 't.invoice_no' },
                { data: 'customer_name', name: 'customer_name' },
                { data: 'location_name', name: 'bl.name' },
                { data: 'formatted_final_total', name: 'final_total', searchable: false },
                { data: 'formatted_due_amount', name: 'due_amount', searchable: false },
                { data: 'overdue_status', name: 'overdue_status', orderable: false, searchable: false },
                { data: 'payment_status', name: 'payment_status' }
            ],
            order: [[1, 'desc']],
            "fnDrawCallback": function (oSettings) {
                __currency_convert_recursively($('#receivables_table'));
            }
        });

        var payables_table = $('#payables_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.cash-flow.payables') }}",
                data: function (d) {
                    d.location_id = $('#location_filter').val();
                }
            },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'transaction_date', name: 't.transaction_date' },
                { data: 'ref_no', name: 't.ref_no' },
                { data: 'supplier_name', name: 'supplier_name' },
                { data: 'transaction_type', name: 'type' },
                { data: 'location_name', name: 'bl.name' },
                { data: 'formatted_final_total', name: 'final_total', searchable: false },
                { data: 'formatted_due_amount', name: 'due_amount', searchable: false },
                { data: 'overdue_status', name: 'overdue_status', orderable: false, searchable: false },
                { data: 'payment_status', name: 'payment_status' }
            ],
            order: [[1, 'desc']],
            "fnDrawCallback": function (oSettings) {
                __currency_convert_recursively($('#payables_table'));
            },
            "footerCallback": function ( row, data, start, end, display ) {
                var footer_total_final_total = 0;
                var footer_total_due_amount = 0;

                for (var r in data){
                    footer_total_final_total += $(data[r].formatted_final_total).data('orig-value') ? parseFloat($(data[r].formatted_final_total).data('orig-value')) : 0;
                    footer_total_due_amount += $(data[r].formatted_due_amount).data('orig-value') ? parseFloat($(data[r].formatted_due_amount).data('orig-value')) : 0;
                }

                $('.footer_payables_total').html(__currency_trans_from_en(footer_total_final_total, true));
                $('.footer_payables_due').html(__currency_trans_from_en(footer_total_due_amount, true));
            }
        });

        // Filter button click
        $('#filter_btn').click(function() {
            daily_cash_flow_table.ajax.reload();
            payment_methods_table.ajax.reload();
            receivables_table.ajax.reload();
            payables_table.ajax.reload();
            loadSummary();
        });

        // Export button click
        $('#export_btn').click(function(e) {
            e.preventDefault();

            var originalText = $(this).html();
            $(this).html('<i class="fa fa-spinner fa-spin"></i> {{ __('advancedreports::lang.exporting') }}');
            $(this).prop('disabled', true);

            var data = {
                location_id: $('#location_filter').val(),
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val()
            };

            // Create a form and submit it as POST
            var form = $('<form>', {
                'method': 'POST',
                'action': '{{ route("advancedreports.cash-flow.export") }}',
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

            setTimeout(function() {
                $('#export_btn').html(originalText);
                $('#export_btn').prop('disabled', false);
            }, 3000);
        });

        // Auto-filter on change for location
        $('#location_filter').change(function() {
            daily_cash_flow_table.ajax.reload();
            payment_methods_table.ajax.reload();
            receivables_table.ajax.reload();
            payables_table.ajax.reload();
            loadSummary();
        });

        // Load summary data
        function loadSummary() {
            var location_id = $('#location_filter').val();
            var start_date = $('#start_date').val();
            var end_date = $('#end_date').val();
            
            $.ajax({
                url: "{{ route('advancedreports.cash-flow.summary') }}",
                data: {
                    location_id: location_id,
                    start_date: start_date,
                    end_date: end_date
                },
                dataType: 'json',
                success: function(data) {
                    // Cash flow summary - update with raw values and let currency conversion handle formatting
                    $('#cash_inflows').html('<span class="display_currency" data-currency_symbol="true">' + (data.cash_inflows.total || 0) + '</span>');
                    $('#cash_outflows').html('<span class="display_currency" data-currency_symbol="true">' + (data.cash_outflows.total || 0) + '</span>');
                    $('#net_cash_flow').html('<span class="display_currency" data-currency_symbol="true">' + (data.net_cash_flow || 0) + '</span>');
                    $('#cash_position').html('<span class="display_currency" data-currency_symbol="true">' + (data.closing_balance || 0) + '</span>');

                    // Outstanding balances
                    $('#receivables_amount').html('<span class="display_currency" data-currency_symbol="true">' + (data.receivables.total || 0) + '</span>');
                    $('#receivables_count').text(data.receivables.count + ' invoices');

                    $('#payables_amount').html('<span class="display_currency" data-currency_symbol="true">' + (data.payables.total || 0) + '</span>');
                    $('#payables_count').text(data.payables.count + ' bills');

                    // Working capital and ratios
                    var working_capital = (data.receivables.total || 0) - (data.payables.total || 0);
                    $('#working_capital').html('<span class="display_currency" data-currency_symbol="true">' + working_capital + '</span>');

                    var liquidity_ratio = data.payables.total > 0 ? (data.closing_balance / data.payables.total) : 0;
                    $('#liquidity_ratio').text(formatNumber(liquidity_ratio, 2));

                    // Convert all currency elements in summary cards
                    __currency_convert_recursively($('#summary_cards, #balances_cards'));

                    // Calculate percentage changes
                    var inflows_change = calculatePercentageChange(data.cash_inflows.total, data.previous_net_flow);
                    var outflows_change = calculatePercentageChange(data.cash_outflows.total, Math.abs(data.previous_net_flow));
                    var net_flow_change = calculatePercentageChange(data.net_cash_flow, data.previous_net_flow);

                    $('#inflows_change').text(formatNumber(inflows_change, 1) + '% from last period');
                    $('#outflows_change').text(formatNumber(outflows_change, 1) + '% from last period');
                    $('#net_flow_change').text(formatNumber(net_flow_change, 1) + '% from last period');

                    // Update progress bars
                    updateProgressBars(data);

                    // Show cards
                    $('#summary_cards').show();
                    $('#balances_cards').show();
                },
                error: function() {
                    console.log('Error loading summary data');
                }
            });
        }

        function updateProgressBars(data) {
            // Simple progress calculation based on positive/negative values
            var max_amount = Math.max(data.cash_inflows.total, data.cash_outflows.total, Math.abs(data.net_cash_flow));
            
            if (max_amount > 0) {
                $('#inflows_progress').css('width', (data.cash_inflows.total / max_amount * 100) + '%');
                $('#outflows_progress').css('width', (data.cash_outflows.total / max_amount * 100) + '%');
                $('#net_flow_progress').css('width', (Math.abs(data.net_cash_flow) / max_amount * 100) + '%');
            }

            // Position progress based on positive balance
            var position_percentage = Math.min(Math.max(data.closing_balance / (data.closing_balance + data.receivables.total) * 100, 0), 100);
            $('#position_progress').css('width', position_percentage + '%');

            // Working capital progress
            var total_working_items = data.receivables.total + data.payables.total;
            if (total_working_items > 0) {
                $('#working_capital_progress').css('width', Math.min(data.receivables.total / total_working_items * 100, 100) + '%');
                $('#receivables_progress').css('width', Math.min(data.receivables.total / total_working_items * 100, 100) + '%');
                $('#payables_progress').css('width', Math.min(data.payables.total / total_working_items * 100, 100) + '%');
            }

            // Liquidity ratio progress
            var liquidity_ratio = data.payables.total > 0 ? (data.closing_balance / data.payables.total) : 0;
            $('#liquidity_progress').css('width', Math.min(liquidity_ratio * 20, 100) + '%'); // Scale to make visible
        }





        // Cash forecast functionality
        $('#generate_forecast').click(function() {
            var location_id = $('#location_filter').val();
            var forecast_days = $('#forecast_days').val();
            
            $.ajax({
                url: "{{ route('advancedreports.cash-flow.forecast') }}",
                data: {
                    location_id: location_id,
                    forecast_days: forecast_days
                },
                dataType: 'json',
                success: function(data) {
                    $('#current_cash_balance').html('<span class="display_currency" data-currency_symbol="true">' + (data.current_balance || 0) + '</span>');
                    $('#forecast_summary').show();

                    // Populate forecast table
                    var tbody = $('#forecast_table_body');
                    tbody.empty();

                    $.each(data.forecast, function(index, row) {
                        var trend_class = row.balance_trend === 'positive' ? 'text-success' : 'text-danger';
                        var trend_icon = row.balance_trend === 'positive' ? 'fa-arrow-up' : 'fa-arrow-down';

                        var tr = '<tr>' +
                                '<td>' + row.formatted_date + '</td>' +
                                '<td class="text-right"><span class="display_currency" data-currency_symbol="true">' + (row.receivables || 0) + '</span></td>' +
                                '<td class="text-right"><span class="display_currency" data-currency_symbol="true">' + (row.payables || 0) + '</span></td>' +
                                '<td class="text-right"><span class="display_currency" data-currency_symbol="true">' + (row.net_flow || 0) + '</span></td>' +
                                '<td class="text-right ' + trend_class + '"><span class="display_currency" data-currency_symbol="true">' + (row.projected_balance || 0) + '</span></td>' +
                                '<td class="text-center"><i class="fa ' + trend_icon + ' ' + trend_class + '"></i></td>' +
                                '</tr>';
                        tbody.append(tr);
                    });

                    $('#forecast_table_container').show();
                    __currency_convert_recursively($('#cash_forecast_table, #forecast_summary'));
                },
                error: function() {
                    toastr.error('Error generating cash forecast');
                }
            });
        });


        // Initial currency formatting for footer elements
        __currency_convert_recursively($('.footer-total'));

        // Load initial summary
        loadSummary();

        // Modal for transaction details
        $(document).on('click', '.view-transaction', function(e) {
            e.preventDefault();
            var transaction_id = $(this).data('id');
            // Implementation for viewing transaction details
            toastr.info('View transaction details for ID: ' + transaction_id);
        });
    });
</script>

<style>
    /* Section Spacing */
    #balances_cards {
        margin-top: 20px;
        margin-bottom: 20px;
    }

    /* Tab content spacing */
    .tab-content {
        min-height: 400px;
    }

    /* Forecast section styling */
    #forecast_summary {
        margin: 20px 0;
    }

    #forecast_table_container {
        margin-top: 20px;
    }

    /* Custom alert styling */
    .alert-info h4 {
        margin-top: 0;
    }

    /* Progress bar custom colors */
    .progress-bar.bg-green {
        background-color: #00a65a !important;
    }

    .progress-bar.bg-red {
        background-color: #dd4b39 !important;
    }

    .progress-bar.bg-aqua {
        background-color: #00c0ef !important;
    }

    .progress-bar.bg-purple {
        background-color: #605ca8 !important;
    }

    .progress-bar.bg-yellow {
        background-color: #f39c12 !important;
    }

    .progress-bar.bg-orange {
        background-color: #ff851b !important;
    }

    .progress-bar.bg-teal {
        background-color: #39cccc !important;
    }

    .progress-bar.bg-info {
        background-color: #00c0ef !important;
    }

    /* Table styling */
    .table-responsive {
        margin-top: 15px;
    }

    /* Footer totals styling */
    .footer-total {
        font-weight: bold;
        background-color: #f4f4f4 !important;
    }

    /* Status labels */
    .label-orange {
        background-color: #ff851b;
    }

</style>
@endsection