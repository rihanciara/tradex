@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.daily_summary_report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('advancedreports::lang.daily_summary_report')
        <small>@lang('advancedreports::lang.manage_daily_summary')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', [
            'title' => __('report.filters'),
            'class' => 'box-primary'
            ])
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'location_filter']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' =>
                    'form-control', 'id' => 'date_range_filter', 'readonly']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <br>
                    <button type="button" class="btn btn-primary" id="filter_btn">
                        <i class="fa fa-filter"></i> @lang('advancedreports::lang.filter')
                    </button>
                    <button type="button" class="btn btn-success" id="export_btn">
                        <i class="fa fa-download"></i> @lang('lang_v1.export')
                    </button>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row" id="summary_cards" style="display: none;">
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3 id="total_sales">0</h3>
                    <p>@lang('advancedreports::lang.total_sales')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-shopping-cart"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="total_purchases">0</h3>
                    <p>@lang('advancedreports::lang.total_purchases')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-truck"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3 id="total_expenses">0</h3>
                    <p>@lang('advancedreports::lang.total_expenses')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-credit-card"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="net_profit">0</h3>
                    <p>@lang('advancedreports::lang.net_profit')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-line-chart"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-orange">
                <div class="inner">
                    <h3 id="avg_daily_sales">0</h3>
                    <p>@lang('advancedreports::lang.avg_daily_sales')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-calculator"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-purple">
                <div class="inner">
                    <h3 id="profitable_days">0</h3>
                    <p>@lang('advancedreports::lang.profitable_days')</p>
                    <small id="days_count_text"></small>
                </div>
                <div class="icon">
                    <i class="fa fa-calendar-check-o"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' =>
            __('advancedreports::lang.daily_summary_report')])
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="daily_summary_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('advancedreports::lang.day')</th>
                            <th>@lang('advancedreports::lang.sales_count')</th>
                            <th>@lang('advancedreports::lang.total_sales')</th>
                            <th>@lang('advancedreports::lang.purchase_count')</th>
                            <th>@lang('advancedreports::lang.total_purchases')</th>
                            <th>@lang('advancedreports::lang.expense_count')</th>
                            <th>@lang('advancedreports::lang.total_expenses')</th>
                            <th>@lang('advancedreports::lang.cash_received')</th>
                            <th>@lang('advancedreports::lang.card_received')</th>
                            <th>@lang('advancedreports::lang.net_profit')</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 text-center footer-total">
                            <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                            <td class="footer_total_sales"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td></td>
                            <td class="footer_total_purchases"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td></td>
                            <td class="footer_total_expenses"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td class="footer_cash_received"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td class="footer_card_received"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td class="footer_net_profit"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Detailed Transaction Tables -->

    <!-- Daily Purchase Table -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => 'Daily Purchase'])
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="daily_purchase_report_table">
                    <thead>
                        <tr>
                            <th>@lang('purchase.ref_no')</th>
                            <th>@lang('purchase.supplier')</th>
                            <th>@lang('sale.total')</th>
                            <th>@lang('report.paid')</th>
                            <th>@lang('report.due')</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr class="bg-gray font-17 footer-total text-center">
                            <td colspan="2"><strong>@lang('sale.total'):</strong></td>
                            <td class="text-left"><span class="display_currency" id="footer_total_amount"
                                    data-currency_symbol="true"></span></td>
                            <td class="text-left"><span class="display_currency" id="footer_total_paid"
                                    data-currency_symbol="true"></span></td>
                            <td class="text-left"><span class="display_currency" id="footer_total_due"
                                    data-currency_symbol="true"></span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Daily Purchase Return Table -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => 'Daily Purchase Return'])
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="daily_purchase_return_report_table">
                    <thead>
                        <tr>
                            <th>@lang('purchase.ref_no')</th>
                            <th>@lang('purchase.supplier')</th>
                            <th>@lang('sale.total')</th>
                            <th>@lang('report.paid')</th>
                            <th>@lang('report.due')</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr class="bg-gray font-17 footer-total text-center">
                            <td colspan="2"><strong>@lang('sale.total'):</strong></td>
                            <td class="text-left"><span class="display_currency" id="return_footer_total_amount"
                                    data-currency_symbol="true"></span></td>
                            <td class="text-left"><span class="display_currency" id="return_footer_total_paid"
                                    data-currency_symbol="true"></span></td>
                            <td class="text-left"><span class="display_currency" id="return_footer_total_due"
                                    data-currency_symbol="true"></span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Supplier Due Payment Table -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => 'Supplier Due Payment'])
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="daily_purchase_payment_report_table">
                    <thead>
                        <tr>
                            <th>@lang('purchase.ref_no')</th>
                            <th>@lang('purchase.supplier')</th>
                            <th>@lang('report.paid')</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr class="bg-gray font-17 footer-total text-center">
                            <td colspan="2"><strong>@lang('sale.total'):</strong></td>
                            <td class="text-left"><span class="display_currency"
                                    id="daily_purchase_payment_footer_total_paid" data-currency_symbol="true"></span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Daily Sales Table -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => 'Sales'])
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="daily_sale_report_table">
                    <thead>
                        <tr>
                            <th>@lang('lang_v1.contact_id')</th>
                            <th>@lang('sale.customer_name')</th>
                            <th>@lang('sale.invoice_no')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('sale.total') (@lang('product.exc_of_tax'))</th>
                            <th>@lang('sale.discount')</th>
                            <th>@lang('sale.tax')</th>
                            <th>@lang('sale.total') (@lang('product.inc_of_tax'))</th>
                            <th>@lang('report.paid')</th>
                            <th>@lang('report.due')</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr class="bg-gray font-17 footer-total text-center">
                            <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                            <td class="text-left"><span class="display_currency" id="daily_sell_footer_total_before_tax"
                                    data-currency_symbol="true"></span></td>
                            <td class="text-left"><span class="display_currency" id="daily_sell_footer_discount_amount"
                                    data-currency_symbol="true"></span></td>
                            <td class="text-left"><span class="display_currency" id="daily_sell_footer_tax_amount"
                                    data-currency_symbol="true"></span></td>
                            <td class="text-left"><span class="display_currency" id="daily_sell_footer_final_total"
                                    data-currency_symbol="true"></span></td>
                            <td class="text-left"><span class="display_currency" id="daily_sell_footer_total_paid"
                                    data-currency_symbol="true"></span></td>
                            <td class="text-left"><span class="display_currency" id="daily_sell_footer_total_due"
                                    data-currency_symbol="true"></span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Daily Sales Return Table -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => 'Sales Return'])
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="daily_sale_return_report_table">
                    <thead>
                        <tr>
                            <th>@lang('lang_v1.contact_id')</th>
                            <th>@lang('sale.customer_name')</th>
                            <th>@lang('sale.invoice_no')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('sale.total') (@lang('product.exc_of_tax'))</th>
                            <th>@lang('sale.discount')</th>
                            <th>@lang('sale.tax')</th>
                            <th>@lang('sale.total') (@lang('product.inc_of_tax'))</th>
                            <th>@lang('report.paid')</th>
                            <th>@lang('report.due')</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr class="bg-gray font-17 footer-total text-center">
                            <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                            <td class="text-left"><span class="display_currency"
                                    id="daily_sell_return_footer_total_before_tax" data-currency_symbol="true"></span>
                            </td>
                            <td class="text-left"><span class="display_currency"
                                    id="daily_sell_return_footer_discount_amount" data-currency_symbol="true"></span>
                            </td>
                            <td class="text-left"><span class="display_currency"
                                    id="daily_sell_return_footer_tax_amount" data-currency_symbol="true"></span></td>
                            <td class="text-left"><span class="display_currency"
                                    id="daily_sell_return_footer_final_total" data-currency_symbol="true"></span></td>
                            <td class="text-left"><span class="display_currency"
                                    id="daily_sell_return_footer_total_paid" data-currency_symbol="true"></span></td>
                            <td class="text-left"><span class="display_currency" id="daily_sell_return_footer_total_due"
                                    data-currency_symbol="true"></span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Customer Due Received Table -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => 'Customer Due Received'])
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="daily_sell_payment_report_table">
                    <thead>
                        <tr>
                            <th>@lang('purchase.ref_no')</th>
                            <th>@lang('contact.customer')</th>
                            <th>@lang('sale.amount')</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr class="bg-gray font-17 footer-total text-center">
                            <td colspan="2"><strong>@lang('sale.total'):</strong></td>
                            <td class="text-left"><span class="display_currency"
                                    id="daily_sell_payment_footer_total_paid" data-currency_symbol="true"></span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Daily Details Modal -->
    <div class="modal fade" id="daily_details_modal" tabindex="-1" role="dialog" style="z-index: 1050;">
        <div class="modal-dialog modal-xl" role="document" style="width: 95%; max-width: 1200px;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">@lang('advancedreports::lang.daily_details')</h4>
                </div>
                <div class="modal-body" id="daily_details_content" style="max-height: 80vh; overflow-y: auto;">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                </div>
            </div>
        </div>
    </div>

</section>
<!-- /.content -->

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#summary_cards').show();
    // Initialize date range picker with passed dates or default to last 30 days
    @if(isset($start_date) && isset($end_date))
        var startDate = moment('{{ $start_date }}');
        var endDate = moment('{{ $end_date }}');
    @else
        var startDate = moment().subtract(29, 'days');
        var endDate = moment();
    @endif
    
    $('#date_range_filter').val(startDate.format(moment_date_format) + ' ~ ' + endDate.format(moment_date_format));
    
    $('#date_range_filter').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#date_range_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
        }
    );
    
    $('#date_range_filter').on('cancel.daterangepicker', function(ev, picker) {
        $('#date_range_filter').val(startDate.format(moment_date_format) + ' ~ ' + endDate.format(moment_date_format));
    });

    // Initialize DataTable
  var daily_summary_table = $('#daily_summary_table').DataTable({
    processing: true,
    serverSide: true,
    pageLength: 25, // Add row limit
    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]], // Add length menu
    ajax: {
        url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\DailySummaryReportController@getDailySummaryData') !!}",
        data: function (d) {
            d.location_id = $('#location_filter').val();
            
            var start = '';
            var end = '';
            if($('#date_range_filter').val()){
                start = $('input#date_range_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                end = $('input#date_range_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
            }
            d.start_date = start;
            d.end_date = end;
        },
        error: function(xhr, error, code) {
            console.log('DataTable Error:', xhr.responseText);
        }
    },
    columns: [
        { data: 'action', name: 'action', orderable: false, searchable: false },
        { data: 'formatted_date', name: 'date' },
        { data: 'day_name', name: 'day_name' },
        { data: 'sales_count', name: 'sales_count', searchable: false },
        { data: 'total_sales', name: 'total_sales', searchable: false },
        { data: 'purchases_count', name: 'purchases_count', searchable: false },
        { data: 'total_purchases', name: 'total_purchases', searchable: false },
        { data: 'expenses_count', name: 'expenses_count', searchable: false },
        { data: 'total_expenses', name: 'total_expenses', searchable: false },
        { data: 'cash_received', name: 'cash_received', searchable: false },
        { data: 'card_received', name: 'card_received', searchable: false },
        { data: 'net_profit', name: 'net_profit', searchable: false }
    ],
    order: [[1, 'desc']],
    "fnDrawCallback": function (oSettings) {
        __currency_convert_recursively($('#daily_summary_table'));
        
        // Calculate and update footer totals
        var api = this.api();
        
        var total_sales = 0;
        var total_purchases = 0;
        var total_expenses = 0;
        var total_cash = 0;
        var total_card = 0;
        var total_net_profit = 0;
        
        api.column(4, {page: 'current'}).data().each(function(value) {
            var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
            total_sales += num;
        });
        
        api.column(6, {page: 'current'}).data().each(function(value) {
            var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
            total_purchases += num;
        });
        
        api.column(8, {page: 'current'}).data().each(function(value) {
            var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
            total_expenses += num;
        });
        
        api.column(9, {page: 'current'}).data().each(function(value) {
            var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
            total_cash += num;
        });
        
        api.column(10, {page: 'current'}).data().each(function(value) {
            var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
            total_card += num;
        });
        
        api.column(11, {page: 'current'}).data().each(function(value) {
            var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
            total_net_profit += num;
        });

        $('.footer_total_sales').html('<span class="display_currency" data-currency_symbol="true">' + total_sales.toFixed(2) + '</span>');
        $('.footer_total_purchases').html('<span class="display_currency" data-currency_symbol="true">' + total_purchases.toFixed(2) + '</span>');
        $('.footer_total_expenses').html('<span class="display_currency" data-currency_symbol="true">' + total_expenses.toFixed(2) + '</span>');
        $('.footer_cash_received').html('<span class="display_currency" data-currency_symbol="true">' + total_cash.toFixed(2) + '</span>');
        $('.footer_card_received').html('<span class="display_currency" data-currency_symbol="true">' + total_card.toFixed(2) + '</span>');
        $('.footer_net_profit').html('<span class="display_currency" data-currency_symbol="true">' + total_net_profit.toFixed(2) + '</span>');
        
        __currency_convert_recursively($('.footer-total'));
    },
    createdRow: function( row, data, dataIndex ) {
        $(row).find('td:eq(3)').addClass('text-center');
        $(row).find('td:eq(4)').addClass('text-right');
        $(row).find('td:eq(5)').addClass('text-center');
        $(row).find('td:eq(6)').addClass('text-right');
        $(row).find('td:eq(7)').addClass('text-center');
        $(row).find('td:eq(8)').addClass('text-right');
        $(row).find('td:eq(9)').addClass('text-right');
        $(row).find('td:eq(10)').addClass('text-right');
        $(row).find('td:eq(11)').addClass('text-right');
    }
});
    // Initialize Daily Purchase Table
var daily_purchase_table = $('#daily_purchase_report_table').DataTable({
    processing: true,
    serverSide: true,
    searching: true, // Enable search
    paging: true, // Enable pagination
    pageLength: 15, // Limit rows
    lengthMenu: [[10, 15, 25, 50], [10, 15, 25, 50]], // Length options
    ordering: true, // Enable sorting
    ajax: {
        url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\DailySummaryReportController@getDailyPurchase') !!}",
        data: function (d) {
            d.location_id = $('#location_filter').val();
            var start = '';
            var end = '';
            if($('#date_range_filter').val()){
                start = $('input#date_range_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                end = $('input#date_range_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
            }
            d.start_date = start;
            d.end_date = end;
        },
        error: function(xhr, error, code) {
            console.log('Purchase Table Error:', xhr.responseText);
        }
    },
    columns: [
        { data: 'ref_no', name: 'ref_no' },
        { data: 'supplier_name', name: 'supplier_name' },
        { data: 'final_total', name: 'final_total' },
        { data: 'total_paid', name: 'total_paid' },
        { data: 'total_due', name: 'total_due' }
    ],
    fnDrawCallback: function(oSettings) {
        var final_total = sum_table_col($('#daily_purchase_report_table'), 'final_total');
        var paid_amount = sum_table_col($('#daily_purchase_report_table'), 'paid_amount');
        var total_due = sum_table_col($('#daily_purchase_report_table'), 'total_due');
        $('#footer_total_amount').text(final_total);
        $('#footer_total_paid').text(paid_amount);
        $('#footer_total_due').text(total_due);
        __currency_convert_recursively($('#daily_purchase_report_table'));
    }
});
    // Initialize Daily Purchase Return Table
var daily_purchase_return_table = $('#daily_purchase_return_report_table').DataTable({
    processing: true,
    serverSide: true,
    searching: true,
    paging: true,
    pageLength: 15,
    lengthMenu: [[10, 15, 25, 50], [10, 15, 25, 50]],
    ordering: true,
    ajax: {
        url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\DailySummaryReportController@getDailyPurchaseReturn') !!}",
        data: function (d) {
            d.location_id = $('#location_filter').val();
            var start = '';
            var end = '';
            if($('#date_range_filter').val()){
                start = $('input#date_range_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                end = $('input#date_range_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
            }
            d.start_date = start;
            d.end_date = end;
        }
    },
    columns: [
        { data: 'ref_no', name: 'ref_no' },
        { data: 'supplier_name', name: 'supplier_name' },
        { data: 'final_total', name: 'final_total' },
        { data: 'total_paid', name: 'total_paid' },
        { data: 'total_due', name: 'total_due' }
    ],
    fnDrawCallback: function(oSettings) {
        var final_total = sum_table_col($('#daily_purchase_return_report_table'), 'final_total');
        var paid_amount = sum_table_col($('#daily_purchase_return_report_table'), 'paid_amount');
        var total_due = sum_table_col($('#daily_purchase_return_report_table'), 'total_due');
        $('#return_footer_total_amount').text(final_total);
        $('#return_footer_total_paid').text(paid_amount);
        $('#return_footer_total_due').text(total_due);
        __currency_convert_recursively($('#daily_purchase_return_report_table'));
    }
});
    // Initialize Daily Purchase Payment Table
  var daily_purchase_payment_table = $('#daily_purchase_payment_report_table').DataTable({
    processing: true,
    serverSide: true,
    searching: true,
    paging: true,
    pageLength: 15,
    lengthMenu: [[10, 15, 25, 50], [10, 15, 25, 50]],
    ordering: true,
    ajax: {
        url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\DailySummaryReportController@getDailyPurchasePayment') !!}",
        data: function (d) {
            d.location_id = $('#location_filter').val();
            var start = '';
            var end = '';
            if($('#date_range_filter').val()){
                start = $('input#date_range_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                end = $('input#date_range_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
            }
            d.start_date = start;
            d.end_date = end;
        }
    },
    columns: [
        { data: 'payment_ref_no', name: 'payment_ref_no' },
        { data: 'supplier_name', name: 'supplier_name' },
        { data: 'amount', name: 'amount' }
    ],
    fnDrawCallback: function(oSettings) {
        var paid_amount = sum_table_col($('#daily_purchase_payment_report_table'), 'paid-amount');
        $('#daily_purchase_payment_footer_total_paid').text(paid_amount);
        __currency_convert_recursively($('#daily_purchase_payment_report_table'));
    }
});
    // Initialize Daily Sale Table
  var daily_sale_table = $('#daily_sale_report_table').DataTable({
    processing: true,
    serverSide: true,
    searching: true,
    paging: true,
    pageLength: 15,
    lengthMenu: [[10, 15, 25, 50], [10, 15, 25, 50]],
    ordering: true,
    ajax: {
        url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\DailySummaryReportController@getDailySales') !!}",
        data: function (d) {
            d.location_id = $('#location_filter').val();
            var start = '';
            var end = '';
            if($('#date_range_filter').val()){
                start = $('input#date_range_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                end = $('input#date_range_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
            }
            d.start_date = start;
            d.end_date = end;
        }
    },
    columns: [
        { data: 'contact_id', name: 'contact_id' },
        { data: 'customer_name', name: 'customer_name' },
        { data: 'invoice_no', name: 'invoice_no' },
        { data: 'transaction_date', name: 'transaction_date' },
        { data: 'total_before_tax', name: 'total_before_tax' },
        { data: 'discount_amount', name: 'discount_amount' },
        { data: 'tax_amount', name: 'tax_amount' },
        { data: 'final_total', name: 'final_total' },
        { data: 'total_paid', name: 'total_paid' },
        { data: 'total_due', name: 'total_due' }
    ],
    fnDrawCallback: function(oSettings) {
        var total_before_tax = sum_table_col($('#daily_sale_report_table'), 'total_before_tax');
        var discount_amount = sum_table_col($('#daily_sale_report_table'), 'discount_amount');
        var tax_amount = sum_table_col($('#daily_sale_report_table'), 'tax_amount');
        var final_total = sum_table_col($('#daily_sale_report_table'), 'final_total');
        var total_paid = sum_table_col($('#daily_sale_report_table'), 'total_paid');
        var total_due = sum_table_col($('#daily_sale_report_table'), 'total_due');
        $('#daily_sell_footer_total_before_tax').text(total_before_tax);
        $('#daily_sell_footer_discount_amount').text(discount_amount);
        $('#daily_sell_footer_tax_amount').text(tax_amount);
        $('#daily_sell_footer_final_total').text(final_total);
        $('#daily_sell_footer_total_paid').text(total_paid);
        $('#daily_sell_footer_total_due').text(total_due);
        __currency_convert_recursively($('#daily_sale_report_table'));
    }
});
    // Initialize Daily Sale Return Table
 var daily_sale_return_table = $('#daily_sale_return_report_table').DataTable({
    processing: true,
    serverSide: true,
    searching: true,
    paging: true,
    pageLength: 15,
    lengthMenu: [[10, 15, 25, 50], [10, 15, 25, 50]],
    ordering: true,
    ajax: {
        url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\DailySummaryReportController@getDailySaleReturn') !!}",
        data: function (d) {
            d.location_id = $('#location_filter').val();
            var start = '';
            var end = '';
            if($('#date_range_filter').val()){
                start = $('input#date_range_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                end = $('input#date_range_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
            }
            d.start_date = start;
            d.end_date = end;
        }
    },
    columns: [
        { data: 'contact_id', name: 'contact_id' },
        { data: 'customer_name', name: 'customer_name' },
        { data: 'invoice_no', name: 'invoice_no' },
        { data: 'transaction_date', name: 'transaction_date' },
        { data: 'total_before_tax', name: 'total_before_tax' },
        { data: 'discount_amount', name: 'discount_amount' },
        { data: 'tax_amount', name: 'tax_amount' },
        { data: 'final_total', name: 'final_total' },
        { data: 'total_paid', name: 'total_paid' },
        { data: 'total_due', name: 'total_due' }
    ],
    fnDrawCallback: function(oSettings) {
        var total_before_tax = sum_table_col($('#daily_sale_return_report_table'), 'total_before_tax');
        var discount_amount = sum_table_col($('#daily_sale_return_report_table'), 'discount_amount');
        var tax_amount = sum_table_col($('#daily_sale_return_report_table'), 'tax_amount');
        var final_total = sum_table_col($('#daily_sale_return_report_table'), 'final_total');
        var total_paid = sum_table_col($('#daily_sale_return_report_table'), 'total_paid');
        var total_due = sum_table_col($('#daily_sale_return_report_table'), 'total_due');
        $('#daily_sell_return_footer_total_before_tax').text(total_before_tax);
        $('#daily_sell_return_footer_discount_amount').text(discount_amount);
        $('#daily_sell_return_footer_tax_amount').text(tax_amount);
        $('#daily_sell_return_footer_final_total').text(final_total);
        $('#daily_sell_return_footer_total_paid').text(total_paid);
        $('#daily_sell_return_footer_total_due').text(total_due);
        __currency_convert_recursively($('#daily_sale_return_report_table'));
    }
});
    // Initialize Daily Sell Payment Table
 var daily_sell_payment_table = $('#daily_sell_payment_report_table').DataTable({
    processing: true,
    serverSide: true,
    searching: true,
    paging: true,
    pageLength: 15,
    lengthMenu: [[10, 15, 25, 50], [10, 15, 25, 50]],
    ordering: true,
    ajax: {
        url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\DailySummaryReportController@getDailySellPayment') !!}",
        data: function (d) {
            d.location_id = $('#location_filter').val();
            var start = '';
            var end = '';
            if($('#date_range_filter').val()){
                start = $('input#date_range_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                end = $('input#date_range_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
            }
            d.start_date = start;
            d.end_date = end;
        }
    },
    columns: [
        { data: 'payment_ref_no', name: 'payment_ref_no' },
        { data: 'customer_name', name: 'customer_name' },
        { data: 'amount', name: 'amount' }
    ],
    fnDrawCallback: function(oSettings) {
        var total_paid = sum_table_col($('#daily_sell_payment_report_table'), 'paid-amount');
        $('#daily_sell_payment_footer_total_paid').text(total_paid);
        __currency_convert_recursively($('#daily_sell_payment_report_table'));
    }
});
    // Filter button click
$('#filter_btn').click(function() {
    // Show widgets immediately
    $('#summary_cards').show();
    
    daily_summary_table.ajax.reload();
    loadSummary();
    
    // Reload all detailed tables
    if (typeof daily_purchase_table !== 'undefined') daily_purchase_table.ajax.reload();
    if (typeof daily_purchase_return_table !== 'undefined') daily_purchase_return_table.ajax.reload();
    if (typeof daily_purchase_payment_table !== 'undefined') daily_purchase_payment_table.ajax.reload();
    if (typeof daily_sale_table !== 'undefined') daily_sale_table.ajax.reload();
    if (typeof daily_sale_return_table !== 'undefined') daily_sale_return_table.ajax.reload();
    if (typeof daily_sell_payment_table !== 'undefined') daily_sell_payment_table.ajax.reload();
});

    // Export button click
    $('#export_btn').click(function(e) {
        e.preventDefault();
        
        var location_id = $('#location_filter').val();
        var start_date = '';
        var end_date = '';
        
        if($('#date_range_filter').val()){
            start_date = $('input#date_range_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            end_date = $('input#date_range_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }
        
        var $btn = $(this);
        var originalText = $btn.html();
        
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Exporting...').prop('disabled', true);
        
        var url = "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\DailySummaryReportController@export') !!}";
        url += '?location_id=' + location_id + '&start_date=' + start_date + '&end_date=' + end_date;
        
        var iframe = $('<iframe>').hide().appendTo('body');
        iframe.attr('src', url);
        
        setTimeout(function() {
            $btn.html(originalText).prop('disabled', false);
            iframe.remove();
        }, 30000);
        
        iframe.on('load', function() {
            setTimeout(function() {
                $btn.html(originalText).prop('disabled', false);
                iframe.remove();
            }, 3000);
        });
    });

    // View details button click
    $(document).on('click', '.view-details', function() {
        var date = $(this).data('date');
        var location_id = $('#location_filter').val();
        
        // Show modal with daily details for this date
        $('#daily_details_content').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Loading details...</p></div>');
        $('#daily_details_modal .modal-title').text('Daily Details for ' + moment(date).format('DD MMM YYYY'));
        $('#daily_details_modal').modal('show');
        
        // Load detailed breakdown for this date
        $.ajax({
            url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\DailySummaryReportController@getDailyDetails') !!}",
            data: {
                date: date,
                location_id: location_id
            },
            dataType: 'json',
            success: function(data) {
                var detailsHtml = buildDetailedView(data);
                $('#daily_details_content').html(detailsHtml);
                
                // Apply currency formatting to the new content
                __currency_convert_recursively($('#daily_details_content'));
            },
            error: function(xhr, status, error) {
                $('#daily_details_content').html('<div class="alert alert-danger">Error loading details: ' + error + '</div>');
                console.error('Details Error:', xhr.responseText);
            }
        });
    });

    // Build detailed view HTML
    function buildDetailedView(data) {
        var html = '<div class="row">';
        
        // Sales Details
        html += '<div class="col-md-6">';
        html += '<div class="box box-success">';
        html += '<div class="box-header with-border"><h3 class="box-title"><i class="fa fa-shopping-cart"></i> Sales Details</h3></div>';
        html += '<div class="box-body">';
        if (data.sales && data.sales.length > 0) {
            html += '<div class="table-responsive">';
            html += '<table class="table table-condensed table-striped">';
            html += '<thead><tr><th>Invoice</th><th>Customer</th><th>Amount</th><th>Paid</th><th>Due</th></tr></thead>';
            html += '<tbody>';
            var salesTotal = 0;
            $.each(data.sales, function(index, sale) {
                salesTotal += parseFloat(sale.final_total || 0);
                html += '<tr>';
                html += '<td>' + (sale.invoice_no || '') + '</td>';
                html += '<td>' + (sale.customer_name || 'Walk-in Customer') + '</td>';
                html += '<td class="text-right"><span class="display_currency">' + parseFloat(sale.final_total || 0).toFixed(2) + '</span></td>';
                html += '<td class="text-right"><span class="display_currency">' + parseFloat(sale.total_paid || 0).toFixed(2) + '</span></td>';
                html += '<td class="text-right"><span class="display_currency">' + parseFloat(sale.balance_due || 0).toFixed(2) + '</span></td>';
                html += '</tr>';
            });
            html += '</tbody>';
            html += '<tfoot><tr class="info"><td colspan="2"><strong>Total:</strong></td><td class="text-right"><strong><span class="display_currency">' + salesTotal.toFixed(2) + '</span></strong></td><td colspan="2"></td></tr></tfoot>';
            html += '</table>';
            html += '</div>';
        } else {
            html += '<p class="text-muted">No sales for this date.</p>';
        }
        html += '</div></div></div>';

        // Purchases Details
        html += '<div class="col-md-6">';
        html += '<div class="box box-info">';
        html += '<div class="box-header with-border"><h3 class="box-title"><i class="fa fa-truck"></i> Purchase Details</h3></div>';
        html += '<div class="box-body">';
        if (data.purchases && data.purchases.length > 0) {
            html += '<div class="table-responsive">';
            html += '<table class="table table-condensed table-striped">';
            html += '<thead><tr><th>Ref No</th><th>Supplier</th><th>Amount</th><th>Paid</th><th>Due</th></tr></thead>';
            html += '<tbody>';
            var purchaseTotal = 0;
            $.each(data.purchases, function(index, purchase) {
                purchaseTotal += parseFloat(purchase.final_total || 0);
                html += '<tr>';
                html += '<td>' + (purchase.ref_no || '') + '</td>';
                html += '<td>' + (purchase.supplier_name || '') + '</td>';
                html += '<td class="text-right"><span class="display_currency">' + parseFloat(purchase.final_total || 0).toFixed(2) + '</span></td>';
                html += '<td class="text-right"><span class="display_currency">' + parseFloat(purchase.total_paid || 0).toFixed(2) + '</span></td>';
                html += '<td class="text-right"><span class="display_currency">' + parseFloat(purchase.balance_due || 0).toFixed(2) + '</span></td>';
                html += '</tr>';
            });
            html += '</tbody>';
            html += '<tfoot><tr class="info"><td colspan="2"><strong>Total:</strong></td><td class="text-right"><strong><span class="display_currency">' + purchaseTotal.toFixed(2) + '</span></strong></td><td colspan="2"></td></tr></tfoot>';
            html += '</table>';
            html += '</div>';
        } else {
            html += '<p class="text-muted">No purchases for this date.</p>';
        }
        html += '</div></div></div>';

        html += '</div><div class="row">';

        // Expenses Details
        html += '<div class="col-md-6">';
        html += '<div class="box box-warning">';
        html += '<div class="box-header with-border"><h3 class="box-title"><i class="fa fa-credit-card"></i> Expense Details</h3></div>';
        html += '<div class="box-body">';
        if (data.expenses && data.expenses.length > 0) {
            html += '<div class="table-responsive">';
            html += '<table class="table table-condensed table-striped">';
            html += '<thead><tr><th>Ref No</th><th>Category</th><th>Amount</th><th>Notes</th></tr></thead>';
            html += '<tbody>';
            var expenseTotal = 0;
            $.each(data.expenses, function(index, expense) {
                expenseTotal += parseFloat(expense.final_total || 0);
                html += '<tr>';
                html += '<td>' + (expense.ref_no || '') + '</td>';
                html += '<td>' + (expense.category_name || 'Uncategorized') + '</td>';
                html += '<td class="text-right"><span class="display_currency">' + parseFloat(expense.final_total || 0).toFixed(2) + '</span></td>';
                html += '<td>' + (expense.additional_notes || '') + '</td>';
                html += '</tr>';
            });
            html += '</tbody>';
            html += '<tfoot><tr class="info"><td colspan="2"><strong>Total:</strong></td><td class="text-right"><strong><span class="display_currency">' + expenseTotal.toFixed(2) + '</span></strong></td><td></td></tr></tfoot>';
            html += '</table>';
            html += '</div>';
        } else {
            html += '<p class="text-muted">No expenses for this date.</p>';
        }
        html += '</div></div></div>';

        // Payments Details
        html += '<div class="col-md-6">';
        html += '<div class="box box-primary">';
        html += '<div class="box-header with-border"><h3 class="box-title"><i class="fa fa-money"></i> Payment Details</h3></div>';
        html += '<div class="box-body">';
        if (data.payments && data.payments.length > 0) {
            html += '<div class="table-responsive">';
            html += '<table class="table table-condensed table-striped">';
            html += '<thead><tr><th>Ref No</th><th>Contact</th><th>Type</th><th>Method</th><th>Amount</th></tr></thead>';
            html += '<tbody>';
            var paymentTotal = 0;
            $.each(data.payments, function(index, payment) {
                paymentTotal += parseFloat(payment.amount || 0);
                html += '<tr>';
                html += '<td>' + (payment.payment_ref_no || '') + '</td>';
                html += '<td>' + (payment.contact_name || '') + '</td>';
                html += '<td>' + (payment.transaction_type === 'sell' ? 'Sale' : (payment.transaction_type === 'purchase' ? 'Purchase' : payment.transaction_type)) + '</td>';
                html += '<td>' + (payment.method || '') + '</td>';
                html += '<td class="text-right"><span class="display_currency">' + parseFloat(payment.amount || 0).toFixed(2) + '</span></td>';
                html += '</tr>';
            });
            html += '</tbody>';
            html += '<tfoot><tr class="info"><td colspan="4"><strong>Total:</strong></td><td class="text-right"><strong><span class="display_currency">' + paymentTotal.toFixed(2) + '</span></strong></td></tr></tfoot>';
            html += '</table>';
            html += '</div>';
        } else {
            html += '<p class="text-muted">No payments for this date.</p>';
        }
        html += '</div></div></div>';

        html += '</div>';
        
        return html;
    }

    // Load summary data
  function loadSummary() {
    var location_id = $('#location_filter').val();
    var start_date = '';
    var end_date = '';
    
    if($('#date_range_filter').val()){
        start_date = $('input#date_range_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
        end_date = $('input#date_range_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
    }
    
    // Show loading state for widgets
    $('#summary_cards').show();
    $('.small-box .inner h3').html('<i class="fa fa-spinner fa-spin"></i>');
    
    $.ajax({
        url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\DailySummaryReportController@getSummary') !!}",
        data: {
            location_id: location_id,
            start_date: start_date,
            end_date: end_date
        },
        dataType: 'json',
        success: function(data) {
            if (data.error) {
                console.error('Summary Error:', data.error);
                // Show error state
                $('.small-box .inner h3').text('Error');
                return;
            }
            
            // Update widgets with data
            $('#total_sales').text(__currency_trans_from_en(data.total_sales || 0, true));
            $('#total_purchases').text(__currency_trans_from_en(data.total_purchases || 0, true));
            $('#total_expenses').text(__currency_trans_from_en(data.total_expenses || 0, true));
            $('#net_profit').text(__currency_trans_from_en(data.net_profit || 0, true));
            $('#avg_daily_sales').text(__currency_trans_from_en(data.avg_daily_sales || 0, true));
            $('#profitable_days').text(data.profitable_days || 0);
            $('#days_count_text').text('out of ' + (data.days_count || 0) + ' days');
            
            // Ensure widgets are visible
            $('#summary_cards').show();
            
            // Add some animation
            $('.small-box').addClass('animated fadeIn');
            setTimeout(function() {
                $('.small-box').removeClass('animated fadeIn');
            }, 600);
        },
        error: function(xhr, status, error) {
            console.error('Summary AJAX Error:', xhr.responseText);
            // Show error state
            $('.small-box .inner h3').text('Error');
            $('#summary_cards').show(); // Still show the widgets even on error
        }
    });
}
    // Load initial summary and data
    loadSummary();

    // Helper function to sum table columns
    function sum_table_col(table, class_name) {
        var sum = 0;
        table.find('.' + class_name).each(function() {
            var value = $(this).data('orig-value') || 0;
            if (!isNaN(value) && value !== '') {
                sum += parseFloat(value);
            }
        });
        return sum.toFixed(2);
    }
});
</script>

<style>
    /* Use the same widget styling as other reports */
    .small-box {
        min-height: 130px !important;
        height: 130px !important;
        display: flex !important;
        flex-direction: column !important;
        position: relative !important;
        margin-bottom: 20px !important;
        border-radius: 8px !important;
        overflow: hidden !important;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1) !important;
        transition: all 0.2s ease !important;
    }

    .small-box .inner {
        padding: 15px !important;
        flex-grow: 1 !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        position: relative !important;
        z-index: 2 !important;
    }

    .small-box .inner h3 {
        font-size: 28px !important;
        font-weight: 600 !important;
        margin: 0 0 8px 0 !important;
        line-height: 1 !important;
        color: #ffffff !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    .small-box .inner p {
        font-size: 13px !important;
        margin: 0 !important;
        line-height: 1.2 !important;
        color: rgba(255, 255, 255, 0.9) !important;
        font-weight: 400 !important;
    }

    .small-box .inner small {
        font-size: 10px !important;
        color: rgba(255, 255, 255, 0.7) !important;
        margin-top: 2px !important;
        opacity: 0 !important;
        transition: opacity 0.3s ease !important;
    }

    .small-box .icon {
        position: absolute !important;
        top: 15px !important;
        right: 15px !important;
        z-index: 1 !important;
        font-size: 40px !important;
        color: rgba(255, 255, 255, 0.2) !important;
    }

    .small-box:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15) !important;
    }

    .small-box:hover .inner small {
        opacity: 1 !important;
    }

    /* Colors */
    .small-box.bg-aqua {
        background-color: #3498db !important;
    }

    .small-box.bg-green {
        background-color: #2ecc71 !important;
    }

    .small-box.bg-yellow {
        background-color: #f39c12 !important;
    }

    .small-box.bg-red {
        background-color: #e74c3c !important;
    }

    .small-box.bg-orange {
        background-color: #e67e22 !important;
    }

    .small-box.bg-purple {
        background-color: #9b59b6 !important;
    }

    /* Hover effects */
    .small-box:hover.bg-aqua {
        background-color: #2980b9 !important;
    }

    .small-box:hover.bg-green {
        background-color: #27ae60 !important;
    }

    .small-box:hover.bg-yellow {
        background-color: #d68910 !important;
    }

    .small-box:hover.bg-red {
        background-color: #c0392b !important;
    }

    .small-box:hover.bg-orange {
        background-color: #d35400 !important;
    }

    .small-box:hover.bg-purple {
        background-color: #8e44ad !important;
    }

    /* Modal styling */
    .modal-xl {
        width: 95% !important;
        max-width: 1200px !important;
    }

    .modal-body {
        padding: 20px !important;
    }

    .box {
        margin-bottom: 20px !important;
    }

    .box-header {
        background-color: #f4f4f4 !important;
        border-bottom: 1px solid #ddd !important;
        border-radius: 3px 3px 0 0 !important;
        padding: 10px 15px !important;
    }

    .box-title {
        font-size: 16px !important;
        margin: 0 !important;
        line-height: 1.42857143 !important;
    }

    .box-body {
        border-radius: 0 0 3px 3px !important;
        border: 1px solid #ddd !important;
        border-top: none !important;
        padding: 15px !important;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
        .small-box {
            min-height: 120px !important;
            height: 120px !important;
        }

        .small-box .inner h3 {
            font-size: 26px !important;
        }

        .small-box .inner p {
            font-size: 12px !important;
        }

        .small-box .icon {
            font-size: 35px !important;
        }
    }

    @media (max-width: 768px) {
        .small-box {
            min-height: 110px !important;
            height: 110px !important;
            margin-bottom: 15px !important;
        }

        .small-box .inner {
            padding: 12px !important;
        }

        .small-box .inner h3 {
            font-size: 24px !important;
        }

        .small-box .inner p {
            font-size: 11px !important;
        }

        .small-box .icon {
            font-size: 30px !important;
            top: 12px !important;
            right: 12px !important;
        }

        .modal-xl {
            width: 98% !important;
        }
    }

    /* Row spacing */
    #summary_cards {
        margin-bottom: 25px !important;
        padding: 0 !important;
    }

    #summary_cards .row {
        margin-left: -10px !important;
        margin-right: -10px !important;
    }

    #summary_cards [class*="col-"] {
        padding-left: 10px !important;
        padding-right: 10px !important;
    }
</style>
@endsection