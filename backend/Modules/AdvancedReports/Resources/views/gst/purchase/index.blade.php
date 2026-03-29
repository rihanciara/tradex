@extends('advancedreports::layouts.app')
@section('title', __('lang_v1.gst_purchase_report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ __('lang_v1.gst_purchase_report')}}
        <small class="text-muted">India GST Compliance Report</small>
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
    <!-- Combined Filters Section - Always Expanded -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-filter"></i> GST Purchase Report Filters
                        <span class="badge badge-primary" id="active_filters_count" style="display: none;">0</span>
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" id="refresh_filters">
                            <i class="fa fa-refresh"></i>
                        </button>
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <!-- Primary Filters Row -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('gst_sr_date_filter', __('report.date_range') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                    {!! Form::text('date_range', null, ['placeholder' =>
                                    __('lang_v1.select_a_date_range'), 'class'
                                    => 'form-control', 'id' => 'gst_sr_date_filter', 'readonly']); !!}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('gst_report_supplier_filter', __('purchase.supplier') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-user"></i>
                                    </span>
                                    {!! Form::select('supplier_id', $suppliers, 'all', ['class' => 'form-control
                                    select2',
                                    'placeholder' => __('messages.please_select'), 'id' =>
                                    'gst_report_supplier_filter']) !!}
                                </div>
                            </div>
                        </div>
                        @if(isset($business_locations))
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('location_filter', __('purchase.business_location') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-map-marker"></i>
                                    </span>
                                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control
                                    select2',
                                    'placeholder' => __('messages.all'), 'id' => 'location_filter']); !!}
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('category_filter', __('category.category') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-tags"></i>
                                    </span>
                                    {!! Form::select('category_id', $categories, 'all', ['class' => 'form-control
                                    select2',
                                    'id' => 'category_filter']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Filters Row -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('gstin_filter', 'GSTIN:') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-id-card"></i>
                                    </span>
                                    {!! Form::text('gstin_filter', null, ['class' => 'form-control',
                                    'placeholder' => 'Enter GSTIN', 'id' => 'gstin_filter']) !!}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('product_filter', __('business.product') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-cube"></i>
                                    </span>
                                    {!! Form::text('product_filter', null, ['class' => 'form-control',
                                    'placeholder' => 'Product Name/SKU', 'id' => 'product_filter']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Amount Filters & Action Buttons Row -->
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('min_amount_filter', __('advancedreports::lang.min_amount') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-money"></i>
                                    </span>
                                    {!! Form::number('min_amount', null, ['class' => 'form-control',
                                    'placeholder' => '0.00', 'step' => '0.01', 'id' => 'min_amount_filter']) !!}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('max_amount_filter', __('advancedreports::lang.max_amount') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-money"></i>
                                    </span>
                                    {!! Form::number('max_amount', null, ['class' => 'form-control',
                                    'placeholder' => '0.00', 'step' => '0.01', 'id' => 'max_amount_filter']) !!}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="btn-group-actions">
                                    <button type="button" class="btn btn-primary" id="filter_btn">
                                        <i class="fa fa-filter"></i> @lang('advancedreports::lang.filter')
                                    </button>
                                    <button type="button" class="btn btn-warning" id="clear_all_filters">
                                        <i class="fa fa-refresh"></i> Clear All Filters
                                    </button>
                                    <button type="button" class="btn btn-success" id="export_btn">
                                        <i class="fa fa-download"></i> @lang('lang_v1.export')
                                    </button>
                                    <button type="button" class="btn btn-info" id="print_btn">
                                        <i class="fa fa-print"></i> @lang('messages.print')
                                    </button>
                                    <button type="button" class="btn btn-default" id="refresh_table">
                                        <i class="fa fa-refresh"></i> Refresh
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Date Filters -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Quick Date Filters:</label>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-default quick-date"
                                        data-range="today">Today</button>
                                    <button type="button" class="btn btn-default quick-date"
                                        data-range="yesterday">Yesterday</button>
                                    <button type="button" class="btn btn-default quick-date" data-range="this_week">This
                                        Week</button>
                                    <button type="button" class="btn btn-default quick-date" data-range="last_week">Last
                                        Week</button>
                                    <button type="button" class="btn btn-default quick-date"
                                        data-range="this_month">This Month</button>
                                    <button type="button" class="btn btn-default quick-date"
                                        data-range="last_month">Last Month</button>
                                    <button type="button" class="btn btn-default quick-date"
                                        data-range="this_quarter">This Quarter</button>
                                    <button type="button" class="btn btn-default quick-date" data-range="this_year">This
                                        Year</button>
                                    <button type="button" class="btn btn-default" id="clear_date_filter">Clear
                                        Date</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Summary Cards -->
    <div class="row" id="summary_section">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-bar-chart"></i> @lang('advancedreports::lang.summary')
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" id="toggle_summary">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body" id="summary_cards">
                    <div class="row">
                        <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
                            <div class="small-box bg-blue">
                                <div class="inner">
                                    <h3 id="total_transactions">0</h3>
                                    <p>@lang('advancedreports::lang.total_purchases')</p>
                                </div>
                                <div class="icon">
                                    <i class="fa fa-shopping-bag"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
                            <div class="small-box bg-green">
                                <div class="inner">
                                    <h3 id="total_suppliers">0</h3>
                                    <p>@lang('lang_v1.suppliers')</p>
                                </div>
                                <div class="icon">
                                    <i class="fa fa-truck"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
                            <div class="small-box bg-yellow">
                                <div class="inner">
                                    <h3 id="total_amount">0</h3>
                                    <p>@lang('advancedreports::lang.total_amount')</p>
                                </div>
                                <div class="icon">
                                    <i class="fa fa-money"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
                            <div class="small-box bg-red">
                                <div class="inner">
                                    <h3 id="total_tax">0</h3>
                                    <p>@lang('lang_v1.total_tax')</p>
                                </div>
                                <div class="icon">
                                    <i class="fa fa-percent"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
                            <div class="small-box bg-purple">
                                <div class="inner">
                                    <h3 id="taxable_amount">0</h3>
                                    <p>@lang('lang_v1.taxable_value')</p>
                                </div>
                                <div class="icon">
                                    <i class="fa fa-calculator"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
                            <div class="small-box bg-orange">
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
                </div>
            </div>
        </div>
    </div>

    <!-- Main Data Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-table"></i> @lang('lang_v1.gst_purchase_report') - Detailed View
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" id="refresh_table">
                            <i class="fa fa-refresh"></i>
                        </button>
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="gst_purchase_report">
                            <thead>
                                <tr>
                                    <th>@lang('purchase.ref_no')</th>
                                    <th>@lang('purchase.purchase_date')</th>
                                    <th>@lang('lang_v1.supplier_name')</th>
                                    <th>@lang('product.product_name')</th>
                                    <th>@lang('lang_v1.hsn_code')</th>
                                    <th>@lang('sale.qty')</th>
                                    <th>@lang('sale.unit_price')</th>
                                    <th>@lang('sale.discount')</th>
                                    <th>@lang('lang_v1.taxable_value')</th>
                                    <th>GST%</th>
                                    @if(isset($taxes))
                                    @foreach($taxes as $tax)
                                    <th>
                                        {{$tax['name']}} <br><small>{{$tax['amount']}}%</small>
                                    </th>
                                    @endforeach
                                    @endif
                                    <th>@lang('sale.total')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr class="bg-gray font-17 footer-total text-center">
                                    <td colspan="8"><strong>@lang('sale.total'):</strong></td>
                                    <td class="total_taxable_value"><span class="display_currency"
                                            data-currency_symbol="true">0</span></td>
                                    <td></td>
                                    @if(isset($taxes))
                                    @foreach($taxes as $tax)
                                    <td class="tax_{{$tax['id']}}_total">
                                        <span class="display_currency" data-currency_symbol="true">0</span>
                                    </td>
                                    @endforeach
                                    @endif
                                    <td class="line_total"><span class="display_currency"
                                            data-currency_symbol="true">0</span></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
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
        // Initialize date range picker
        dateRangeSettings.startDate = moment().startOf('month');
        dateRangeSettings.endDate = moment().endOf('month');
        $('#gst_sr_date_filter').daterangepicker(
            dateRangeSettings,
            function(start, end) {
                $('#gst_sr_date_filter').val(
                    start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                );
                gst_purchase_report.ajax.reload();
                loadSummary();
            }
        );
        $('#gst_sr_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#gst_sr_date_filter').val('');
            gst_purchase_report.ajax.reload();
            loadSummary();
        });

        // Initialize Select2
        $('.select2').select2();

        // Filter change events
        $('#gst_report_supplier_filter').change(function() {
            gst_purchase_report.ajax.reload();
            loadSummary();
        });

        @if(isset($business_locations))
        $('#location_filter').change(function() {
            gst_purchase_report.ajax.reload();
            loadSummary();
        });
        @endif

        // Advanced filters
        $('#category_filter, #tax_rate_filter, #min_amount_filter, #max_amount_filter, #gstin_filter, #product_filter').on('change keyup', function() {
            clearTimeout(window.filterTimeout);
            window.filterTimeout = setTimeout(function() {
                gst_purchase_report.ajax.reload();
                loadSummary();
                updateActiveFiltersCount();
            }, 500);
        });

        // Quick date filters
        $('.quick-date').click(function() {
            var range = $(this).data('range');
            var start, end;
            
            switch(range) {
                case 'today':
                    start = end = moment();
                    break;
                case 'yesterday':
                    start = end = moment().subtract(1, 'day');
                    break;
                case 'this_week':
                    start = moment().startOf('week');
                    end = moment().endOf('week');
                    break;
                case 'last_week':
                    start = moment().subtract(1, 'week').startOf('week');
                    end = moment().subtract(1, 'week').endOf('week');
                    break;
                case 'this_month':
                    start = moment().startOf('month');
                    end = moment().endOf('month');
                    break;
                case 'last_month':
                    start = moment().subtract(1, 'month').startOf('month');
                    end = moment().subtract(1, 'month').endOf('month');
                    break;
                case 'this_quarter':
                    start = moment().startOf('quarter');
                    end = moment().endOf('quarter');
                    break;
                case 'this_year':
                    start = moment().startOf('year');
                    end = moment().endOf('year');
                    break;
            }
            
            $('#gst_sr_date_filter').data('daterangepicker').setStartDate(start);
            $('#gst_sr_date_filter').data('daterangepicker').setEndDate(end);
            $('#gst_sr_date_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            
            // Highlight active quick date button
            $('.quick-date').removeClass('active btn-primary').addClass('btn-default');
            $(this).removeClass('btn-default').addClass('btn-primary active');
            
            gst_purchase_report.ajax.reload();
            loadSummary();
        });

        // Clear date filter
        $('#clear_date_filter').click(function() {
            $('#gst_sr_date_filter').val('');
            $('.quick-date').removeClass('active btn-primary').addClass('btn-default');
            gst_purchase_report.ajax.reload();
            loadSummary();
        });

        // Clear all filters
        $('#clear_all_filters').click(function() {
            // Reset all form fields
            $('#gst_report_supplier_filter').val('all').trigger('change');
            $('#location_filter').val('').trigger('change');
            $('#category_filter').val('all').trigger('change');
            $('#tax_rate_filter').val('all').trigger('change');
            $('#gstin_filter').val('');
            $('#product_filter').val('');
            $('#min_amount_filter').val('');
            $('#max_amount_filter').val('');
            $('#gst_sr_date_filter').val('');
            
            // Clear quick date buttons
            $('.quick-date').removeClass('active btn-primary').addClass('btn-default');
            
            // Reload data
            gst_purchase_report.ajax.reload();
            loadSummary();
            updateActiveFiltersCount();
            
            toastr.success('All filters cleared successfully');
        });

        // Refresh filters
        $('#refresh_filters').click(function() {
            updateActiveFiltersCount();
            toastr.info('Filters refreshed');
        });

        // Advanced filters toggle (removed - no longer needed)
        // Apply advanced filters (removed - no longer needed)
        // Clear advanced filters (removed - no longer needed)

        // Initialize DataTable
        gst_purchase_report = $('table#gst_purchase_report').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [
                [1, 'desc']
            ],
            scrollY: "75vh",
            scrollX: true,
            scrollCollapse: true,
            fixedHeader: false,
            ajax: {
                url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\GstPurchaseReportController@getGstPurchaseData') !!}",
                data: function(d) {
                    var start = '';
                    var end = '';

                    if ($('#gst_sr_date_filter').val()) {
                        start = $('input#gst_sr_date_filter')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');

                        end = $('input#gst_sr_date_filter')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                    }
                    d.start_date = start;
                    d.end_date = end;
                    d.supplier_id = $('select#gst_report_supplier_filter').val();
                    @if(isset($business_locations))
                    d.location_id = $('select#location_filter').val();
                    @endif
                    d.category_id = $('select#category_filter').val();
                    d.tax_rate_id = $('select#tax_rate_filter').val();
                    d.min_amount = $('#min_amount_filter').val();
                    d.max_amount = $('#max_amount_filter').val();
                    d.gstin_filter = $('#gstin_filter').val();
                    d.product_filter = $('#product_filter').val();
                },
            },
            columns: [{
                    data: 'ref_no',
                    name: 't.ref_no'
                },
                {
                    data: 'transaction_date',
                    name: 't.transaction_date'
                },
                {
                    data: 'supplier',
                    name: 'c.name'
                },
                {
                    data: 'product_name',
                    name: 'p.name'
                },
                {
                    data: 'short_code',
                    name: 'cat.short_code'
                },
                {
                    data: 'purchase_qty',
                    name: 'purchase_lines.quantity'
                },
                {
                    data: 'unit_price',
                    name: 'purchase_lines.pp_without_discount'
                },
                {
                    data: 'discount_amount',
                    searchable: false
                },
                {
                    data: 'taxable_value',
                    name: 'taxable_value',
                    searchable: false
                },
                {
                    data: 'tax_percent',
                    name: 'tr.amount'
                },

                @if(isset($taxes))
                @foreach($taxes as $tax) {
                    data: "tax_{{$tax['id']}}",
                    searchable: false,
                    orderable: false
                },
                @endforeach
                @endif {
                    data: 'line_total',
                    name: 'line_total',
                    searchable: false
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                },
            ],
            "footerCallback": function(row, data, start, end, display) {
                var total_taxable_value = 0;
                var line_total = 0;

                @if(isset($taxes))
                @foreach($taxes as $tax)
                var tax_{{$tax['id']}}_total = 0;
                @endforeach
                @endif

                for (var r in data) {
                    total_taxable_value += $(data[r].taxable_value).data('orig-value') ?
                        parseFloat($(data[r].taxable_value).data('orig-value')) : 0;

                    line_total += $(data[r].line_total).data('orig-value') ?
                        parseFloat($(data[r].line_total).data('orig-value')) : 0;

                    @if(isset($taxes))
                    @foreach($taxes as $tax)
                    tax_{{$tax['id']}}_total += $(data[r].tax_{{$tax['id']}}).data('orig-value') ?
                        parseFloat($(data[r].tax_{{$tax['id']}}).data('orig-value')) : 0;
                    @endforeach
                    @endif
                }

                @if(isset($taxes))
                @foreach($taxes as $tax)
                if (tax_{{$tax['id']}}_total !== 0) {
                    $('.tax_{{$tax["id"]}}_total').html(__currency_trans_from_en(tax_{{$tax['id']}}_total, false));
                }
                @endforeach
                @endif

                $('.total_taxable_value').html(__currency_trans_from_en(total_taxable_value, false));
                $('.line_total').html(__currency_trans_from_en(line_total, false));

                __currency_convert_recursively($('.footer-total'));
            },
            createdRow: function(row, data, dataIndex) {
                $(row).find('td:eq(5)').addClass('text-center');
                $(row).find('td:eq(6)').addClass('text-right');
                $(row).find('td:eq(7)').addClass('text-right');
                $(row).find('td:eq(8)').addClass('text-right');
                $(row).find('td:eq(9)').addClass('text-center');

                var tax_column_start = 10;
                @if(isset($taxes))
                @php $col_index = 10; @endphp
                @foreach($taxes as $tax)
                $(row).find('td:eq({{$col_index}})').addClass('text-right');
                @php $col_index++; @endphp
                @endforeach
                $(row).find('td:eq({{$col_index}})').addClass('text-right');
                @else
                $(row).find('td:eq(10)').addClass('text-right');
                @endif
            }
        });

        // Button events
        $('#filter_btn').click(function() {
            gst_purchase_report.ajax.reload();
            loadSummary();
        });

        $('#export_btn').click(function() {
            var url = "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\GstPurchaseReportController@export') !!}";
            var data = getFilterData();
            
            var form = $('<form method="GET" action="' + url + '">');
            $.each(data, function(key, value) {
                if (value) {
                    form.append('<input type="hidden" name="' + key + '" value="' + value + '">');
                }
            });
            $('body').append(form);
            form.submit();
            form.remove();
        });

        $('#print_btn').click(function() {
            var url = "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\GstPurchaseReportController@print') !!}";
            var data = getFilterData();
            
            var queryString = $.param(data);
            window.open(url + '?' + queryString, '_blank');
        });

        $('#refresh_table').click(function() {
            gst_purchase_report.ajax.reload();
            loadSummary();
            toastr.success('Table refreshed successfully');
        });

        $('#toggle_summary').click(function() {
            $('#summary_cards').slideToggle();
            var icon = $(this).find('i');
            if (icon.hasClass('fa-minus')) {
                icon.removeClass('fa-minus').addClass('fa-plus');
            } else {
                icon.removeClass('fa-plus').addClass('fa-minus');
            }
        });

        // Load summary data
        function loadSummary() {
            var data = getFilterData();
            data.summary = true;

            $.ajax({
                url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\GstPurchaseReportController@getGstPurchaseData') !!}",
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.summary) {
                        var summary = response.summary;
                        $('#total_transactions').text(summary.total_transactions || 0);
                        $('#total_suppliers').text(summary.total_suppliers || 0);

                        $('#total_amount').text(__currency_trans_from_en(summary.total_purchases || 0, true));
                        $('#total_tax').text(__currency_trans_from_en(summary.total_tax || 0, true));
                        $('#taxable_amount').text(__currency_trans_from_en(summary.taxable_amount || 0, true));

                        var avg_tax_rate = parseFloat(summary.average_tax_rate || 0);
                        $('#avg_tax_rate').text(avg_tax_rate.toFixed(2) + '%');
                    }
                    $('#summary_section').show();
                },
                error: function(xhr, status, error) {
                    console.log('Summary data not available');
                    // Show default values
                    $('#total_transactions').text('0');
                    $('#total_suppliers').text('0');
                    $('#total_amount').text(__currency_trans_from_en(0, true));
                    $('#total_tax').text(__currency_trans_from_en(0, true));
                    $('#taxable_amount').text(__currency_trans_from_en(0, true));
                    $('#avg_tax_rate').text('0.00%');
                    $('#summary_section').show();
                }
            });
        }

        // Get filter data
        function getFilterData() {
            var start = '';
            var end = '';
            if ($('#gst_sr_date_filter').val()) {
                start = $('input#gst_sr_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                end = $('input#gst_sr_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
            }

            return {
                start_date: start,
                end_date: end,
                supplier_id: $('#gst_report_supplier_filter').val(),
                @if(isset($business_locations))
                location_id: $('#location_filter').val(),
                @endif
                category_id: $('#category_filter').val(),
                tax_rate_id: $('#tax_rate_filter').val(),
                min_amount: $('#min_amount_filter').val(),
                max_amount: $('#max_amount_filter').val(),
                gstin_filter: $('#gstin_filter').val(),
                product_filter: $('#product_filter').val()
            };
        }

        // Update active filters count
        function updateActiveFiltersCount() {
            var count = 0;
            var filters = [
                '#gst_report_supplier_filter',
                '#location_filter', 
                '#category_filter', 
                '#tax_rate_filter', 
                '#gstin_filter', 
                '#product_filter', 
                '#min_amount_filter', 
                '#max_amount_filter',
                '#gst_sr_date_filter'
            ];
            
            filters.forEach(function(filter) {
                var val = $(filter).val();
                if (val && val !== 'all' && val !== '') {
                    count++;
                }
            });
            
            if (count > 0) {
                $('#active_filters_count').text(count).show().removeClass('badge-primary').addClass('badge-warning');
            } else {
                $('#active_filters_count').hide();
            }
        }

        // Initialize
        updateActiveFiltersCount();
        loadSummary();

        // Keyboard shortcuts
        $(document).keydown(function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch (e.which) {
                    case 70: // Ctrl+F
                        e.preventDefault();
                        $('.dataTables_filter input').focus();
                        break;
                    case 69: // Ctrl+E
                        e.preventDefault();
                        $('#export_btn').click();
                        break;
                    case 80: // Ctrl+P
                        e.preventDefault();
                        $('#print_btn').click();
                        break;
                    case 82: // Ctrl+R
                        e.preventDefault();
                        $('#refresh_table').click();
                        break;
                }
            }
        });
    });
</script>


<style>
    /* Enhanced GST Purchase Report Styles */

    /* Advanced Filters Toggle */
    .collapsed-box .box-body {
        display: none !important;
    }

    #toggle_advanced_filters {
        transition: all 0.3s ease !important;
    }

    #toggle_advanced_filters:hover {
        background-color: #f0f0f0 !important;
    }

    #toggle_icon {
        transition: transform 0.3s ease !important;
    }

    #advanced_filters_header {
        cursor: pointer !important;
        transition: background-color 0.3s ease !important;
    }

    #advanced_filters_header:hover {
        background-color: #f8f9fa !important;
    }

    #advanced_filters_header:hover .box-title {
        color: #FF6B35 !important;
    }

    /* Active filters indicator */
    #active_filters_count {
        margin-left: 10px !important;
        font-size: 11px !important;
        vertical-align: middle !important;
        animation: pulse 2s infinite !important;
    }

    @keyframes pulse {
        0% {
            opacity: 1;
        }

        50% {
            opacity: 0.7;
        }

        100% {
            opacity: 1;
        }
    }

    .box-warning {
        border-top-color: #f39c12 !important;
    }

    .box-warning .box-header {
        background-color: rgba(243, 156, 18, 0.1) !important;
    }

    /* Advanced filters content */
    #advanced_filters_content {
        background-color: #f9f9f9 !important;
        border-top: 1px solid #e5e5e5 !important;
        margin-top: 0 !important;
        padding: 20px !important;
    }

    #advanced_filters_content .row {
        margin: 0 !important;
    }

    #advanced_filters_content .form-group {
        margin-bottom: 15px !important;
    }

    /* Buttons in advanced filters */
    #apply_advanced_filters,
    #clear_advanced_filters {
        margin: 0 5px !important;
        padding: 8px 16px !important;
    }

    #apply_advanced_filters {
        background-color: #FF6B35 !important;
        border-color: #FF6B35 !important;
        color: white !important;
    }

    #apply_advanced_filters:hover {
        background-color: #e55a2b !important;
        border-color: #e55a2b !important;
    }

    #clear_advanced_filters {
        background-color: #95a5a6 !important;
        border-color: #7f8c8d !important;
        color: white !important;
    }

    #clear_advanced_filters:hover {
        background-color: #7f8c8d !important;
        border-color: #6c7b7d !important;
    }

    /* Enhanced Summary Cards */
    .small-box {
        border-radius: 8px !important;
        margin-bottom: 15px !important;
        min-height: 120px !important;
        position: relative !important;
        overflow: hidden !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
    }

    .small-box:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
    }

    .small-box .inner {
        padding: 15px !important;
    }

    .small-box .inner h3 {
        font-size: 24px !important;
        font-weight: 600 !important;
        margin: 0 0 8px 0 !important;
        color: #ffffff !important;
    }

    .small-box .inner p {
        font-size: 13px !important;
        margin: 0 !important;
        color: rgba(255, 255, 255, 0.9) !important;
    }

    .small-box .icon {
        position: absolute !important;
        top: 15px !important;
        right: 15px !important;
        font-size: 35px !important;
        color: rgba(255, 255, 255, 0.2) !important;
    }

    /* India-themed color variations */
    .small-box.bg-blue {
        background: linear-gradient(135deg, #FF6B35, #FF8C42) !important;
    }

    .small-box.bg-green {
        background: linear-gradient(135deg, #138808, #28a745) !important;
    }

    .small-box.bg-yellow {
        background: linear-gradient(135deg, #FF6B35, #ffc107) !important;
    }

    .small-box.bg-red {
        background: linear-gradient(135deg, #dc3545, #FF6B35) !important;
    }

    .small-box.bg-purple {
        background: linear-gradient(135deg, #6f42c1, #FF6B35) !important;
    }

    .small-box.bg-orange {
        background: linear-gradient(135deg, #FF6B35, #fd7e14) !important;
    }

    /* DataTable enhancements - Default AdminLTE Style */
    .dataTables_wrapper {
        overflow-x: auto;
    }

    .dataTables_wrapper .dt-buttons {
        margin-bottom: 10px !important;
        float: left !important;
    }

    .dataTables_wrapper .dataTables_length {
        float: left !important;
        margin-right: 10px !important;
    }

    .dataTables_wrapper .dataTables_filter {
        float: right !important;
    }

    .dt-buttons .btn {
        margin-right: 5px !important;
        margin-bottom: 5px !important;
        font-size: 12px !important;
        padding: 6px 12px !important;
        border-radius: 3px !important;
    }

    /* Table styling improvements - AdminLTE Default */
    .table {
        width: 100% !important;
        margin-bottom: 20px !important;
    }

    .table th {
        background-color: #f4f4f4 !important;
        font-weight: 600 !important;
        color: #444 !important;
        border-bottom: 2px solid #ddd !important;
        padding: 8px !important;
        vertical-align: middle !important;
    }

    .table td {
        padding: 8px !important;
        vertical-align: middle !important;
        border-top: 1px solid #ddd !important;
    }

    .table-striped>tbody>tr:nth-of-type(odd) {
        background-color: #f9f9f9 !important;
    }

    .table-bordered {
        border: 1px solid #ddd !important;
    }

    .table-bordered th,
    .table-bordered td {
        border: 1px solid #ddd !important;
    }

    .footer-total {
        background-color: #f4f4f4 !important;
        font-weight: 600 !important;
        border-top: 2px solid #FF6B35 !important;
    }

    /* Enhanced box styling */
    .box {
        border-radius: 3px !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24) !important;
        border-top: 3px solid #d2d6de !important;
    }

    .box-primary {
        border-top-color: #FF6B35 !important;
    }

    .box-header {
        border-bottom: 1px solid #f4f4f4 !important;
        padding: 10px 15px !important;
        background-color: transparent !important;
    }

    .box-header .box-title {
        font-size: 18px !important;
        margin: 0 !important;
        line-height: 1.8 !important;
        color: #444 !important;
        font-weight: 400 !important;
    }

    .box-body {
        padding: 15px !important;
    }

    /* Button styling - AdminLTE Default */
    .btn {
        border-radius: 3px !important;
        box-shadow: none !important;
        border: 1px solid transparent !important;
    }

    .btn-primary {
        background-color: #FF6B35 !important;
        border-color: #FF6B35 !important;
        color: #fff !important;
    }

    .btn-primary:hover,
    .btn-primary:focus,
    .btn-primary:active {
        background-color: #e55a2b !important;
        border-color: #e55a2b !important;
        color: #fff !important;
    }

    .btn-success {
        background-color: #00a65a !important;
        border-color: #00a65a !important;
    }

    .btn-info {
        background-color: #00c0ef !important;
        border-color: #00c0ef !important;
    }

    /* Print button specific styling */
    #print_btn {
        background-color: #00c0ef !important;
        border-color: #00c0ef !important;
        color: #fff !important;
    }

    #print_btn:hover,
    #print_btn:focus,
    #print_btn:active {
        background-color: #00a7d0 !important;
        border-color: #00a7d0 !important;
        color: #fff !important;
    }

    /* Form styling - AdminLTE Default */
    .form-control {
        border-radius: 0 !important;
        box-shadow: none !important;
        border-color: #d2d6de !important;
    }

    .form-control:focus {
        border-color: #FF6B35 !important;
        box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 0 3px rgba(255, 107, 53, 0.1) !important;
    }

    .input-group-addon {
        background-color: #fff !important;
        border-color: #d2d6de !important;
        border-radius: 0 !important;
    }

    /* Select2 styling */
    .select2-container--default .select2-selection--single {
        height: 34px !important;
        border: 1px solid #d2d6de !important;
        border-radius: 0 !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 32px !important;
        padding-left: 12px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px !important;
    }

    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #FF6B35 !important;
    }

    /* Badge styles */
    .badge {
        display: inline-block !important;
        min-width: 10px !important;
        padding: 3px 7px !important;
        font-size: 12px !important;
        font-weight: bold !important;
        line-height: 1 !important;
        color: #fff !important;
        text-align: center !important;
        white-space: nowrap !important;
        vertical-align: baseline !important;
        background-color: #777 !important;
        border-radius: 10px !important;
    }

    .badge-primary {
        background-color: #FF6B35 !important;
    }

    .badge-success {
        background-color: #00a65a !important;
    }

    .badge-info {
        background-color: #00c0ef !important;
    }

    .badge-warning {
        background-color: #f39c12 !important;
        color: #fff !important;
    }

    /* Text colors */
    .text-primary {
        color: #FF6B35 !important;
    }

    .text-success {
        color: #00a65a !important;
    }

    .text-info {
        color: #00c0ef !important;
    }

    .text-warning {
        color: #f39c12 !important;
    }

    .text-muted {
        color: #777 !important;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            float: none !important;
            text-align: center !important;
            margin-bottom: 10px !important;
        }

        .dt-buttons {
            text-align: center !important;
            margin-bottom: 10px !important;
        }

        .dt-buttons .btn {
            font-size: 11px !important;
            padding: 4px 8px !important;
            margin: 2px !important;
        }

        .small-box .inner h3 {
            font-size: 20px !important;
        }

        .small-box .icon {
            font-size: 30px !important;
        }
    }
</style>
@endsection