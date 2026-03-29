@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.gst_sales_report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('advancedreports::lang.gst_sales_report')
        <small>@lang('advancedreports::lang.manage_gst_sales_report')</small>
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
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'location_filter']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('customer_id', __('contact.customer') . ':') !!}
                    {!! Form::select('customer_id', $customers, null, ['placeholder' =>
                    __('advancedreports::lang.all_customers'),
                    'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'customer_filter']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' =>
                    'form-control', 'id' => 'date_range_filter', 'readonly']); !!}
                </div>
            </div>
            <div class="col-md-3">
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
                    <h3 id="total_transactions">0</h3>
                    <p>@lang('advancedreports::lang.total_transactions')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-shopping-cart"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="total_customers">0</h3>
                    <p>@lang('advancedreports::lang.total_customers')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-users"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3 id="total_sales">0</h3>
                    <p>@lang('advancedreports::lang.total_sales')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-money"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="total_tax">0</h3>
                    <p>@lang('advancedreports::lang.total_tax')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-percent"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-orange">
                <div class="inner">
                    <h3 id="taxable_amount">0</h3>
                    <p>@lang('advancedreports::lang.taxable_amount')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-calculator"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-purple">
                <div class="inner">
                    <h3 id="avg_tax_rate">0%</h3>
                    <p>@lang('advancedreports::lang.avg_tax_rate')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-line-chart"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Tabs -->
    <div class="row no-print">
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#gst_by_products" data-toggle="tab" aria-expanded="true">
                            <i class="fa fa-cubes" aria-hidden="true"></i>
                            @lang('advancedreports::lang.gst_sales_by_products')
                        </a>
                    </li>
                    <li>
                        <a href="#gst_by_invoice" data-toggle="tab" aria-expanded="false">
                            <i class="fa fa-file-text-o" aria-hidden="true"></i>
                            @lang('advancedreports::lang.gst_sales_by_invoice')
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- GST Sales by Products Tab -->
                    <div class="tab-pane active" id="gst_by_products">
                        @component('components.widget', ['class' => 'box-primary', 'title' =>
                        __('advancedreports::lang.gst_sales_by_products')])
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped ajax_view" id="gst_sales_report_table">
                                <thead>
                                    <tr>
                                        <th>@lang('sale.invoice_no')</th>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('contact.customer')</th>
                                        <th>@lang('lang_v1.gstin_of_cutomer')</th>
                                        <th>@lang('sale.product')</th>
                                        <th>@lang('sale.qty')</th>
                                        <th>@lang('sale.unit_price')</th>
                                        <th>@lang('advancedreports::lang.taxable_value')</th>
                                        <th>@lang('sale.discount')</th>
                                        <th>@lang('sale.tax')</th>
                                        @foreach($taxes as $tax)
                                        <th>{{ $tax['name'] }}</th>
                                        @endforeach
                                        <th>@lang('sale.total')</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 text-center footer-total">
                                        <td colspan="7"><strong>@lang('sale.total'):</strong></td>
                                        <td class="footer_taxable_value"><span class="display_currency"
                                                data-currency_symbol="true">0</span></td>
                                        <td class="footer_discount"><span class="display_currency"
                                                data-currency_symbol="true">0</span></td>
                                        <td class="footer_tax"><span class="display_currency"
                                                data-currency_symbol="true">0</span></td>
                                        @foreach($taxes as $tax)
                                        <td class="footer_tax_{{ $tax['id'] }}"><span class="display_currency"
                                                data-currency_symbol="true">0</span></td>
                                        @endforeach
                                        <td class="footer_total"><span class="display_currency"
                                                data-currency_symbol="true">0</span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @endcomponent
                    </div>

                    <!-- GST Sales by Invoice Tab -->
                    <div class="tab-pane" id="gst_by_invoice">
                        @component('components.widget', ['class' => 'box-primary', 'title' =>
                        __('advancedreports::lang.gst_sales_by_invoice')])
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped ajax_view" id="gst_sales_invoice_table">
                                <thead>
                                    <tr>
                                        <th>@lang('sale.invoice_no')</th>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('contact.customer')</th>
                                        <th>@lang('lang_v1.gstin_of_cutomer')</th>
                                        <th>@lang('advancedreports::lang.total_products')</th>
                                        <th>@lang('advancedreports::lang.taxable_value')</th>
                                        <th>@lang('sale.discount')</th>
                                        <th>@lang('sale.tax')</th>
                                        @foreach($taxes as $tax)
                                        <th>{{ $tax['name'] }}</th>
                                        @endforeach
                                        <th>@lang('sale.total')</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="bg-gray font-17 text-center footer-total-invoice">
                                        <td colspan="5"><strong>@lang('sale.total'):</strong></td>
                                        <td class="footer_invoice_taxable_value"><span class="display_currency"
                                                data-currency_symbol="true">0</span></td>
                                        <td class="footer_invoice_discount"><span class="display_currency"
                                                data-currency_symbol="true">0</span></td>
                                        <td class="footer_invoice_tax"><span class="display_currency"
                                                data-currency_symbol="true">0</span></td>
                                        @foreach($taxes as $tax)
                                        <td class="footer_invoice_tax_{{ $tax['id'] }}"><span class="display_currency"
                                                data-currency_symbol="true">0</span></td>
                                        @endforeach
                                        <td class="footer_invoice_total"><span class="display_currency"
                                                data-currency_symbol="true">0</span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @endcomponent
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- <div class="modal fade view_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div> --}}

</section>
<!-- /.content -->

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
    // Initialize date range picker
    $('#date_range_filter').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#date_range_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
        }
    );
    $('#date_range_filter').on('cancel.daterangepicker', function(ev, picker) {
        $('#date_range_filter').val('');
    });

    // Initialize DataTable for Products view
    var gst_sales_table = $('#gst_sales_report_table').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        autoWidth: false,
        ajax: {
            url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\GstSalesReportController@getGstSalesData') !!}",
            data: function (d) {
                d.location_id = $('#location_filter').val();
                d.customer_id = $('#customer_filter').val();
                
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
            { data: 'invoice_no', name: 't.invoice_no' },
            { data: 'transaction_date', name: 't.transaction_date' },
            { data: 'customer', name: 'c.name' },
            { data: 'tax_number', name: 'c.tax_number' },
            { data: 'product_name', name: 'p.name' },
            { data: 'sell_qty', name: 'sell_qty', searchable: false },
            { data: 'unit_price', name: 'unit_price', searchable: false },
            { data: 'taxable_value', name: 'taxable_value', searchable: false },
            { data: 'discount_amount', name: 'discount_amount', searchable: false },
            { data: 'tax_percent', name: 'tr.amount', searchable: false },
            @foreach($taxes as $tax)
                { data: 'tax_{{ $tax["id"] }}', name: 'tax_{{ $tax["id"] }}', searchable: false, orderable: false },
            @endforeach
            { data: 'line_total', name: 'line_total', searchable: false }
        ],
        order: [[1, 'desc']],
        "fnDrawCallback": function (oSettings) {
            // Only calculate footer if this is the active tab
            if ($('#gst_by_products').hasClass('active')) {
                __currency_convert_recursively($('#gst_sales_report_table'));
                
                // Calculate footer totals
                var api = this.api();
                var total_taxable = 0;
                var total_discount = 0;
                var total_tax = 0;
                var total_amount = 0;
                @foreach($taxes as $tax)
                    var total_tax_{{ $tax['id'] }} = 0;
                @endforeach
                
                api.column(7, {page: 'current'}).data().each(function(value) {
                    var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                    total_taxable += num;
                });
                
                api.column(8, {page: 'current'}).data().each(function(value) {
                    var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                    total_discount += num;
                });
                
                @php $col_index = 10; @endphp
                @foreach($taxes as $tax)
                    api.column({{ $col_index }}, {page: 'current'}).data().each(function(value) {
                        if(value && $(value).data('orig-value')) {
                            total_tax_{{ $tax['id'] }} += parseFloat($(value).data('orig-value')) || 0;
                        }
                    });
                    @php $col_index++; @endphp
                @endforeach
                
                api.column({{ $col_index }}, {page: 'current'}).data().each(function(value) {
                    var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                    total_amount += num;
                });

                $('.footer_taxable_value').html('<span class="display_currency" data-currency_symbol="true">' + total_taxable.toFixed(2) + '</span>');
                $('.footer_discount').html('<span class="display_currency" data-currency_symbol="true">' + total_discount.toFixed(2) + '</span>');
                $('.footer_total').html('<span class="display_currency" data-currency_symbol="true">' + total_amount.toFixed(2) + '</span>');
                
                @foreach($taxes as $tax)
                    $('.footer_tax_{{ $tax["id"] }}').html('<span class="display_currency" data-currency_symbol="true">' + total_tax_{{ $tax['id'] }}.toFixed(2) + '</span>');
                @endforeach
                
                __currency_convert_recursively($('.footer-total'));
                
                // Remove duplicate footer rows - keep only the "Total:" row
                $('#gst_sales_report_table tfoot tr:not(.footer-total)').remove();
                
                // Hide invoice footer when products tab is active
                $('#gst_sales_invoice_table tfoot').hide();
            } else {
                // Hide products footer when not active
                $('#gst_sales_report_table tfoot').hide();
            }
        }
    });

    // Initialize DataTable for Invoice view
    var gst_sales_invoice_table = $('#gst_sales_invoice_table').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        autoWidth: false,
        ajax: {
            url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\GstSalesReportController@getGstSalesDataPerInvoice') !!}",
            data: function (d) {
                d.location_id = $('#location_filter').val();
                d.customer_id = $('#customer_filter').val();
                
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
            { data: 'invoice_no', name: 't.invoice_no' },
            { data: 'transaction_date', name: 't.transaction_date' },
            { data: 'customer', name: 'c.name' },
            { data: 'tax_number', name: 'c.tax_number' },
            { data: 'total_products', name: 'total_products', searchable: false, orderable: false },
            { data: 'total_taxable_value', name: 'total_taxable_value', searchable: false },
            { data: 'total_discount', name: 'total_discount', searchable: false },
            { data: 'total_tax', name: 'total_tax', searchable: false },
            @foreach($taxes as $tax)
                { data: 'tax_{{ $tax["id"] }}', name: 'tax_{{ $tax["id"] }}', searchable: false, orderable: false },
            @endforeach
            { data: 'total_amount', name: 'total_amount', searchable: false }
        ],
        order: [[1, 'desc']],
        "fnDrawCallback": function (oSettings) {
            // Only calculate footer if this is the active tab
            if ($('#gst_by_invoice').hasClass('active')) {
                __currency_convert_recursively($('#gst_sales_invoice_table'));
                
                // Calculate footer totals for invoice view
                var api = this.api();
                var total_taxable = 0;
                var total_discount = 0;
                var total_tax = 0;
                var total_amount = 0;
                @foreach($taxes as $tax)
                    var total_invoice_tax_{{ $tax['id'] }} = 0;
                @endforeach
                
                api.column(5, {page: 'current'}).data().each(function(value) {
                    var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                    total_taxable += num;
                });
                
                api.column(6, {page: 'current'}).data().each(function(value) {
                    var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                    total_discount += num;
                });
                
                api.column(7, {page: 'current'}).data().each(function(value) {
                    var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                    total_tax += num;
                });
                
                @php $col_index = 8; @endphp
                @foreach($taxes as $tax)
                    api.column({{ $col_index }}, {page: 'current'}).data().each(function(value) {
                        if(value && $(value).data('orig-value')) {
                            total_invoice_tax_{{ $tax['id'] }} += parseFloat($(value).data('orig-value')) || 0;
                        }
                    });
                    @php $col_index++; @endphp
                @endforeach
                
                api.column({{ $col_index }}, {page: 'current'}).data().each(function(value) {
                    var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                    total_amount += num;
                });

                $('.footer_invoice_taxable_value').html('<span class="display_currency" data-currency_symbol="true">' + total_taxable.toFixed(2) + '</span>');
                $('.footer_invoice_discount').html('<span class="display_currency" data-currency_symbol="true">' + total_discount.toFixed(2) + '</span>');
                $('.footer_invoice_tax').html('<span class="display_currency" data-currency_symbol="true">' + total_tax.toFixed(2) + '</span>');
                $('.footer_invoice_total').html('<span class="display_currency" data-currency_symbol="true">' + total_amount.toFixed(2) + '</span>');
                
                @foreach($taxes as $tax)
                    $('.footer_invoice_tax_{{ $tax["id"] }}').html('<span class="display_currency" data-currency_symbol="true">' + total_invoice_tax_{{ $tax['id'] }}.toFixed(2) + '</span>');
                @endforeach
                
                __currency_convert_recursively($('.footer-total-invoice'));
                
                // Remove duplicate footer rows - keep only the "Total:" row
                $('#gst_sales_invoice_table tfoot tr:not(.footer-total-invoice)').remove();
                
                // Hide products footer when invoice tab is active
                $('#gst_sales_report_table tfoot').hide();
            } else {
                // Hide invoice footer when not active
                $('#gst_sales_invoice_table tfoot').hide();
            }
        }
    });

    // Filter button click - reload both tables
    $('#filter_btn').click(function() {
        gst_sales_table.ajax.reload();
        gst_sales_invoice_table.ajax.reload();
        loadSummary();
    });

    // Export button click
    $('#export_btn').click(function(e) {
        e.preventDefault();

        var $btn = $(this);
        var originalText = $btn.html();

        $btn.html('<i class="fa fa-spinner fa-spin"></i> Exporting...').prop('disabled', true);

        var start_date = '';
        var end_date = '';
        if($('#date_range_filter').val()){
            start_date = $('input#date_range_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            end_date = $('input#date_range_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }

        var data = {
            location_id: $('#location_filter').val(),
            customer_id: $('#customer_filter').val(),
            start_date: start_date,
            end_date: end_date
        };

        // Create a form and submit it as POST
        var form = $('<form>', {
            'method': 'POST',
            'action': '{!! action("\\Modules\\AdvancedReports\\Http\\Controllers\\GstSalesReportController@export") !!}',
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
            if (value !== null && value !== '') {
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': key,
                    'value': value
                }));
            }
        });

        // Submit the form
        form.appendTo('body').submit().remove();

        // Reset button
        setTimeout(function() {
            $btn.html(originalText).prop('disabled', false);
        }, 3000);
    });

    // Load summary data
    function loadSummary() {
        var location_id = $('#location_filter').val();
        var customer_id = $('#customer_filter').val();
        
        var start_date = '';
        var end_date = '';
        if($('#date_range_filter').val()){
            start_date = $('input#date_range_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
            end_date = $('input#date_range_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }
        
        $.ajax({
            url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\GstSalesReportController@getSummary') !!}",
            data: {
                location_id: location_id,
                customer_id: customer_id,
                start_date: start_date,
                end_date: end_date
            },
            dataType: 'json',
            success: function(data) {
                $('#total_transactions').text(data.total_transactions || 0);
                $('#total_customers').text(data.total_customers || 0);
                
                $('#total_sales').text(__currency_trans_from_en(data.total_sales || 0, true));
                $('#total_tax').text(__currency_trans_from_en(data.total_tax || 0, true));
                $('#taxable_amount').text(__currency_trans_from_en(data.taxable_amount || 0, true));
                
                var avg_tax_rate = parseFloat(data.average_tax_rate || 0);
                $('#avg_tax_rate').text(avg_tax_rate.toFixed(2) + '%');
                
                $('#summary_cards').show();
            },
            error: function() {
                console.log('Error loading summary data');
            }
        });
    }

    // Load initial summary
    loadSummary();

    // Modal for invoice details
    $(document).on('click', 'a.btn-modal', function(e) {
        e.preventDefault();
        var container = $(this).data('container');
        $.get($(this).data('href'), function(data) {
            $(container).html(data).modal('show');
        });
    });

    // Tab switching - fix width when switching to invoice tab
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href");
        if (target === '#gst_by_invoice') {
            // Show invoice footer and hide products footer
            $('#gst_sales_invoice_table tfoot').show();
            $('#gst_sales_report_table tfoot').hide();
            
            // Force table width recalculation after tab is shown
            setTimeout(function() {
                gst_sales_invoice_table.columns.adjust();
            }, 100);
            gst_sales_invoice_table.ajax.reload();
        } else if (target === '#gst_by_products') {
            // Show products footer and hide invoice footer
            $('#gst_sales_report_table tfoot').show();
            $('#gst_sales_invoice_table tfoot').hide();
            
            gst_sales_table.ajax.reload();
        }
    });
});
</script>

<style>
    /* Perfect responsive widget styling - Same as stock report */
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

    /* Responsive font sizing */
    @media (max-width: 1200px) {
        .small-box .inner h3 {
            font-size: 28px !important;
        }

        .small-box .icon {
            font-size: 40px !important;
        }
    }

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
    }

    @media (max-width: 480px) {
        .small-box {
            min-height: 100px !important;
            height: 100px !important;
        }

        .small-box .inner h3 {
            font-size: 20px !important;
        }

        .small-box .inner p {
            font-size: 10px !important;
        }

        .small-box .icon {
            font-size: 25px !important;
        }
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

    /* Tab styling improvements */
    .nav-tabs-custom>.nav-tabs>li.active {
        border-top: 3px solid #3c8dbc;
    }

    .nav-tabs-custom>.nav-tabs>li.active>a {
        background-color: #fff;
        color: #444;
    }

    .nav-tabs-custom>.nav-tabs>li>a {
        color: #444;
        border-radius: 0;
    }

    .nav-tabs-custom>.nav-tabs>li>a:hover {
        background-color: #f4f4f4;
    }

    /* Ensure only one footer shows at a time */
    .tab-pane:not(.active) .footer-total,
    .tab-pane:not(.active) .footer-total-invoice {
        display: none !important;
    }

    /* Initially hide invoice footer since products tab is active by default */
    #gst_sales_invoice_table tfoot {
        display: none;
    }
</style>
@endsection