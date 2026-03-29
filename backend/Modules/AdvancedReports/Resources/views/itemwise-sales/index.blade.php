@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.itemwise_sales_report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ __('advancedreports::lang.itemwise_sales_report')}}
        <small class="text-muted">@lang('advancedreports::lang.itemwise_sales_subtitle')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
    @component('components.filters', ['title' => __('advancedreports::lang.itemwise_sales_filters')])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('itemwise_date_filter', __('report.date_range') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-calendar"></i>
                    </span>
                    {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'itemwise_date_filter', 'readonly']) !!}
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('customer_filter', __('contact.customer') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-user"></i>
                    </span>
                    {!! Form::select('customer_id', $customers, 'all', ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select'), 'id' => 'customer_filter']) !!}
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
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.all'), 'id' => 'location_filter']) !!}
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
                    {!! Form::select('category_id', $categories, 'all', ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'category_filter']) !!}
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('brand_filter', __('product.brand') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-certificate"></i>
                    </span>
                    {!! Form::select('brand_id', $brands, 'all', ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'brand_filter']) !!}
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('unit_filter', __('product.unit') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-balance-scale"></i>
                    </span>
                    {!! Form::select('unit_id', $units, 'all', ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'unit_filter']) !!}
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('tax_rate_filter', __('sale.tax') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-percent"></i>
                    </span>
                    {!! Form::select('tax_rate_id', $tax_rates, 'all', ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'tax_rate_filter']) !!}
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('user_filter', __('report.user') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-user-circle"></i>
                    </span>
                    {!! Form::select('user_id', $users, 'all', ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'user_filter']) !!}
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
                    {!! Form::text('product_filter', null, ['class' => 'form-control', 'placeholder' => 'Product Name/SKU', 'id' => 'product_filter']) !!}
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('customer_search_filter', __('contact.customer') . ' Search:') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-search"></i>
                    </span>
                    {!! Form::text('customer_filter', null, ['class' => 'form-control', 'placeholder' => 'Customer Name/Mobile', 'id' => 'customer_search_filter']) !!}
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('payment_method_filter', __('lang_v1.payment_method') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-credit-card"></i>
                    </span>
                    {!! Form::select('payment_method', $payment_types, 'all', ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'payment_method_filter']) !!}
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('min_amount_filter', __('advancedreports::lang.min_amount') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-money-bill-alt"></i>
                    </span>
                    {!! Form::number('min_amount', null, ['class' => 'form-control', 'placeholder' => '0.00', 'step' => '0.01', 'id' => 'min_amount_filter']) !!}
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('max_amount_filter', __('advancedreports::lang.max_amount') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fas fa-money-bill-alt"></i>
                    </span>
                    {!! Form::number('max_amount', null, ['class' => 'form-control', 'placeholder' => '0.00', 'step' => '0.01', 'id' => 'max_amount_filter']) !!}
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="col-md-12">
            <div class="form-group">
                <button type="button" class="btn btn-default" id="refresh_filters">
                    <i class="fa fa-refresh"></i> @lang('advancedreports::lang.refresh')
                </button>
                <button type="button" class="btn btn-warning" id="clear_all_filters">
                    <i class="fa fa-refresh"></i> @lang('advancedreports::lang.clear_all_filters')
                </button>
                <button type="button" class="btn btn-success" id="export_btn">
                    <i class="fa fa-download"></i> @lang('lang_v1.export')
                </button>
                <button type="button" class="btn btn-info" id="print_btn">
                    <i class="fa fa-print"></i> @lang('messages.print')
                </button>
            </div>
        </div>

        <!-- Quick Date Filters -->
        <div class="col-md-12">
            <div class="form-group">
                <label>@lang('advancedreports::lang.quick_date_filters'):</label><br>
                <button type="button" class="btn btn-default quick-date" data-range="today">@lang('advancedreports::lang.today')</button>
                <button type="button" class="btn btn-default quick-date" data-range="yesterday">@lang('advancedreports::lang.yesterday')</button>
                <button type="button" class="btn btn-default quick-date" data-range="this_week">@lang('advancedreports::lang.this_week')</button>
                <button type="button" class="btn btn-default quick-date" data-range="last_week">@lang('advancedreports::lang.last_week')</button>
                <button type="button" class="btn btn-default quick-date" data-range="this_month">@lang('advancedreports::lang.this_month')</button>
                <button type="button" class="btn btn-default quick-date" data-range="last_month">@lang('advancedreports::lang.last_month')</button>
                <button type="button" class="btn btn-default quick-date" data-range="this_quarter">@lang('advancedreports::lang.this_quarter')</button>
                <button type="button" class="btn btn-default quick-date" data-range="this_year">@lang('advancedreports::lang.this_year')</button>
                <button type="button" class="btn btn-default" id="clear_date_filter">@lang('advancedreports::lang.clear_date')</button>
            </div>
        </div>
    @endcomponent

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
                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                            @component('advancedreports::components.static', [
                                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="tw-w-6 tw-h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-4.5A2.25 2.25 0 0 1 9 6.75v-1.5m6 1.5v-1.5a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 4.5v15a2.25 2.25 0 0 0 2.25 2.25h10.5A2.25 2.25 0 0 0 19.5 19.5v-4.5l-2.25-2.25h-4.5z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 3h6" />
                                  </svg>',
                                'svg_bg' => 'tw-bg-blue-100',
                                'svg_text' => 'tw-text-blue-500'
                            ])
                                <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('advancedreports::lang.total_transactions')</p>
                                <p class="tw-text-2xl tw-font-semibold" id="total_transactions">0</p>
                            @endcomponent
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                            @component('advancedreports::components.static', [
                                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="tw-w-6 tw-h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                  </svg>',
                                'svg_bg' => 'tw-bg-green-100',
                                'svg_text' => 'tw-text-green-500'
                            ])
                                <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('home.customers')</p>
                                <p class="tw-text-2xl tw-font-semibold" id="total_customers">0</p>
                            @endcomponent
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                            @component('advancedreports::components.static', [
                                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="tw-w-6 tw-h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                  </svg>',
                                'svg_bg' => 'tw-bg-yellow-100',
                                'svg_text' => 'tw-text-yellow-500'
                            ])
                                <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('report.products')</p>
                                <p class="tw-text-2xl tw-font-semibold" id="total_products">0</p>
                            @endcomponent
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                            @component('advancedreports::components.static', [
                                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="tw-w-6 tw-h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.589-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.589-1.202L5.25 4.971Z" />
                                  </svg>',
                                'svg_bg' => 'tw-bg-red-100',
                                'svg_text' => 'tw-text-red-500'
                            ])
                                <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('advancedreports::lang.qty_sold')</p>
                                <p class="tw-text-2xl tw-font-semibold" id="total_qty_sold">0</p>
                            @endcomponent
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                            @component('advancedreports::components.static', [
                                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="tw-w-6 tw-h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                  </svg>',
                                'svg_bg' => 'tw-bg-purple-100',
                                'svg_text' => 'tw-text-purple-500'
                            ])
                                <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('advancedreports::lang.total_sales')</p>
                                <p class="tw-text-2xl tw-font-semibold" id="total_sales">0</p>
                            @endcomponent
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                            @component('advancedreports::components.static', [
                                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="tw-w-6 tw-h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 7.5l3 4.5 3-4.5M9 11.25l3 4.5 3-4.5m0-6.75L12 2.25 9 8.25m3 13.5l-3-4.5 3-4.5m0 9l3-4.5-3-4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                  </svg>',
                                'svg_bg' => 'tw-bg-orange-100',
                                'svg_text' => 'tw-text-orange-500'
                            ])
                                <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('sale.tax')</p>
                                <p class="tw-text-2xl tw-font-semibold" id="total_tax">0</p>
                            @endcomponent
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
                        <i class="fa fa-table"></i> @lang('advancedreports::lang.itemwise_sales_report') -
                        @lang('advancedreports::lang.detailed_view')
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
                        <table class="table table-bordered table-striped ajax_view" id="itemwise_sales_report">
                            <thead>
                                <tr>
                                    <th>@lang('sale.invoice_no')</th>
                                    <th>@lang('messages.date')</th>
                                    <th>@lang('contact.customer')</th>
                                    <th>@lang('sale.product')</th>
                                    <th>@lang('product.category')</th>
                                    <th>@lang('product.brand')</th>
                                    <th>@lang('sale.qty')</th>
                                    <th>@lang('sale.unit_price')</th>
                                    <th>@lang('sale.discount')</th>
                                    <th>@lang('advancedreports::lang.tax_percent')</th>
                                    <th>@lang('sale.tax')</th>
                                    <th>@lang('sale.subtotal')</th>
                                    <th>@lang('sale.total')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr class="bg-gray font-17 footer-total text-center">
                                    <td colspan="6"><strong>@lang('sale.total'):</strong></td>
                                    <td class="total_qty_sold"><span class="display_currency"
                                            data-currency_symbol="false">0</span></td>
                                    <td></td>
                                    <td class="total_discount"><span class="display_currency"
                                            data-currency_symbol="true">0</span></td>
                                    <td></td>
                                    <td class="total_tax"><span class="display_currency"
                                            data-currency_symbol="true">0</span></td>
                                    <td class="total_subtotal"><span class="display_currency"
                                            data-currency_symbol="true">0</span></td>
                                    <td class="total_line_total"><span class="display_currency"
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

<!-- Modal Container for Invoice Details -->
<div class="modal fade view_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        // Initialize date range picker
        dateRangeSettings.startDate = moment().startOf('month');
        dateRangeSettings.endDate = moment().endOf('month');
        $('#itemwise_date_filter').daterangepicker(
            dateRangeSettings,
            function(start, end) {
                $('#itemwise_date_filter').val(
                    start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                );
                itemwise_sales_report.ajax.reload();
                loadSummary();
            }
        );
        $('#itemwise_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#itemwise_date_filter').val('');
            itemwise_sales_report.ajax.reload();
            loadSummary();
        });

        // Initialize Select2
        $('.select2').select2();

        // Filter change events
        $('#customer_filter').change(function() {
            itemwise_sales_report.ajax.reload();
            loadSummary();
        });

        @if(isset($business_locations))
        $('#location_filter').change(function() {
            itemwise_sales_report.ajax.reload();
            loadSummary();
        });
        @endif

        // Advanced filters
        $('#category_filter, #brand_filter, #unit_filter, #tax_rate_filter, #user_filter, #payment_method_filter, #min_amount_filter, #max_amount_filter, #product_filter, #customer_search_filter').on('change keyup', function() {
            clearTimeout(window.filterTimeout);
            window.filterTimeout = setTimeout(function() {
                itemwise_sales_report.ajax.reload();
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
            
            $('#itemwise_date_filter').data('daterangepicker').setStartDate(start);
            $('#itemwise_date_filter').data('daterangepicker').setEndDate(end);
            $('#itemwise_date_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            
            // Highlight active quick date button
            $('.quick-date').removeClass('active btn-primary').addClass('btn-default');
            $(this).removeClass('btn-default').addClass('btn-primary active');
            
            itemwise_sales_report.ajax.reload();
            loadSummary();
        });

        // Clear date filter
        $('#clear_date_filter').click(function() {
            $('#itemwise_date_filter').val('');
            $('.quick-date').removeClass('active btn-primary').addClass('btn-default');
            itemwise_sales_report.ajax.reload();
            loadSummary();
        });

        // Clear all filters
        $('#clear_all_filters').click(function() {
            // Reset all form fields
            $('#customer_filter').val('all').trigger('change');
            $('#location_filter').val('').trigger('change');
            $('#category_filter').val('all').trigger('change');
            $('#brand_filter').val('all').trigger('change');
            $('#unit_filter').val('all').trigger('change');
            $('#tax_rate_filter').val('all').trigger('change');
            $('#user_filter').val('all').trigger('change');
            $('#payment_method_filter').val('all').trigger('change');
            $('#product_filter').val('');
            $('#customer_search_filter').val('');
            $('#min_amount_filter').val('');
            $('#max_amount_filter').val('');
            $('#itemwise_date_filter').val('');
            
            // Clear quick date buttons
            $('.quick-date').removeClass('active btn-primary').addClass('btn-default');
            
            // Reload data
            itemwise_sales_report.ajax.reload();
            loadSummary();
            updateActiveFiltersCount();
            
            toastr.success('All filters cleared successfully');
        });

        // Initialize DataTable with Ultimate POS standard styling
        var itemwise_sales_report = $('table#itemwise_sales_report').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [
                [1, 'desc']
            ],
            "pageLength": 25,
            "lengthMenu": [
                [10, 25, 50, 75, 100, 200, 500, -1],
                [10, 25, 50, 75, 100, 200, 500, "All"]
            ],
            ajax: {
                url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\ItemwiseSalesReportController@getItemwiseSalesData') !!}",
                data: function(d) {
                    var start = '';
                    var end = '';

                    if ($('#itemwise_date_filter').val()) {
                        start = $('input#itemwise_date_filter')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');

                        end = $('input#itemwise_date_filter')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                    }
                    d.start_date = start;
                    d.end_date = end;
                    d.customer_id = $('select#customer_filter').val();
                    @if(isset($business_locations))
                    d.location_id = $('select#location_filter').val();
                    @endif
                    d.category_id = $('select#category_filter').val();
                    d.brand_id = $('select#brand_filter').val();
                    d.unit_id = $('select#unit_filter').val();
                    d.tax_rate_id = $('select#tax_rate_filter').val();
                    d.user_id = $('select#user_filter').val();
                    d.payment_method = $('select#payment_method_filter').val();
                    d.min_amount = $('#min_amount_filter').val();
                    d.max_amount = $('#max_amount_filter').val();
                    d.product_filter = $('#product_filter').val();
                    d.customer_filter = $('#customer_search_filter').val();
                },
            },
            columns: [{
                    data: 'invoice_no',
                    name: 't.invoice_no'
                },
                {
                    data: 'transaction_date',
                    name: 't.transaction_date'
                },
                {
                    data: 'customer_name',
                    name: 'c.name'
                },
                {
                    data: 'product_name',
                    name: 'p.name'
                },
                {
                    data: 'category_name',
                    name: 'cat.name'
                },
                {
                    data: 'brand_name',
                    name: 'b.name'
                },
                {
                    data: 'sold_qty',
                    name: 'sold_qty',
                    searchable: false
                },
                {
                    data: 'unit_price',
                    name: 'transaction_sell_lines.unit_price_before_discount'
                },
                {
                    data: 'total_discount',
                    name: 'total_discount',
                    searchable: false
                },
                {
                    data: 'tax_rate',
                    name: 'tr.amount'
                },
                {
                    data: 'total_tax',
                    name: 'total_tax',
                    searchable: false
                },
                {
                    data: 'subtotal',
                    name: 'subtotal',
                    searchable: false
                },
                {
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
            createdRow: function(row, data, dataIndex) {
                // Apply standard Ultimate POS row styling
                $(row).find('td:eq(6)').addClass('text-center');
                $(row).find('td:eq(7)').addClass('text-right');
                $(row).find('td:eq(8)').addClass('text-right');
                $(row).find('td:eq(9)').addClass('text-center');
                $(row).find('td:eq(10)').addClass('text-right');
                $(row).find('td:eq(11)').addClass('text-right');
                $(row).find('td:eq(12)').addClass('text-right');
            },
            fnDrawCallback: function(oSettings) {
                // Standard Ultimate POS callback for currency formatting
                __currency_convert_recursively($('#itemwise_sales_report'));
            },
        });

        // Button events
        $('#filter_btn').click(function() {
            itemwise_sales_report.ajax.reload();
            loadSummary();
        });


        // Export button click
        $('#export_btn').click(function(e) {
            e.preventDefault();

            var data = getFilterData();

            var originalText = $(this).html();
            $(this).html('<i class="fa fa-spinner fa-spin"></i> ' + '@lang('advancedreports::lang.exporting')');
            $(this).prop('disabled', true);

            var url = "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\ItemwiseSalesReportController@export') !!}";
            var queryString = $.param(data);

            window.open(url + '?' + queryString, '_blank');

            setTimeout(function() {
                $('#export_btn').html(originalText);
                $('#export_btn').prop('disabled', false);
            }, 3000);
        });

        $('#print_btn').click(function() {
            var url = "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\ItemwiseSalesReportController@print') !!}";
            var data = getFilterData();

            var queryString = $.param(data);
            window.open(url + '?' + queryString, '_blank');
        });

        $('#refresh_table').click(function() {
            itemwise_sales_report.ajax.reload();
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
                url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\ItemwiseSalesReportController@getItemwiseSalesData') !!}",
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.summary) {
                        var summary = response.summary;
                        $('#total_transactions').text(summary.total_transactions || 0);
                        $('#total_customers').text(summary.total_customers || 0);
                        $('#total_products').text(summary.total_products || 0);
                        $('#total_qty_sold').text(__currency_trans_from_en(summary.total_qty_sold || 0, false));
                        $('#total_sales').text(__currency_trans_from_en(summary.total_sales || 0, true));
                        $('#total_tax').text(__currency_trans_from_en(summary.total_tax || 0, true));
                    }
                    $('#summary_section').show();
                },
                error: function(xhr, status, error) {
                    console.log('Summary data not available');
                    // Show default values
                    $('#total_transactions').text('0');
                    $('#total_customers').text('0');
                    $('#total_products').text('0');
                    $('#total_qty_sold').text('0');
                    $('#total_sales').text(__currency_trans_from_en(0, true));
                    $('#total_tax').text(__currency_trans_from_en(0, true));
                    $('#summary_section').show();
                }
            });
        }

        // Get filter data
        function getFilterData() {
            var start = '';
            var end = '';
            if ($('#itemwise_date_filter').val()) {
                start = $('input#itemwise_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                end = $('input#itemwise_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
            }

            return {
                start_date: start,
                end_date: end,
                customer_id: $('#customer_filter').val(),
                @if(isset($business_locations))
                location_id: $('#location_filter').val(),
                @endif
                category_id: $('#category_filter').val(),
                brand_id: $('#brand_filter').val(),
                unit_id: $('#unit_filter').val(),
                tax_rate_id: $('#tax_rate_filter').val(),
                user_id: $('#user_filter').val(),
                payment_method: $('#payment_method_filter').val(),
                min_amount: $('#min_amount_filter').val(),
                max_amount: $('#max_amount_filter').val(),
                product_filter: $('#product_filter').val(),
                customer_filter: $('#customer_search_filter').val()
            };
        }

        // Update active filters count
        function updateActiveFiltersCount() {
            var count = 0;
            var filters = [
                '#customer_filter',
                '#location_filter', 
                '#category_filter', 
                '#brand_filter',
                '#unit_filter',
                '#tax_rate_filter',
                '#user_filter',
                '#payment_method_filter',
                '#product_filter', 
                '#customer_search_filter', 
                '#min_amount_filter', 
                '#max_amount_filter',
                '#itemwise_date_filter'
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
    });
</script>

<style>
    /* Use the same enhanced styling as GST Purchase Report */

    /* Advanced Filters Toggle */
    .collapsed-box .box-body {
        display: none !important;
    }

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

    /* Enhanced Summary Cards */
    .small-box.bg-purple {
        background: linear-gradient(135deg, #9b59b6, #8e44ad) !important;
    }

    .small-box.bg-orange {
        background: linear-gradient(135deg, #e67e22, #d35400) !important;
    }

    /* DataTable enhancements */
    .dataTables_wrapper {
        overflow-x: auto;
    }

    .dt-buttons .btn {
        margin-right: 5px !important;
        margin-bottom: 5px !important;
        font-size: 12px !important;
        padding: 6px 12px !important;
        border-radius: 3px !important;
    }

    /* Footer total styling to match project standards */
    .footer-total {
        background-color: #f4f4f4 !important;
        font-weight: 600 !important;
        border-top: 2px solid #3498db !important;
    }

    /* Button styling */
    .btn-primary {
        background-color: #3498db !important;
        border-color: #3498db !important;
    }

    .btn-primary:hover {
        background-color: #2980b9 !important;
        border-color: #2980b9 !important;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .small-box .inner h3 {
            font-size: 20px !important;
        }

        .small-box .icon {
            font-size: 30px !important;
        }
    }

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

    /* Color variations */
    .small-box.bg-blue {
        background: linear-gradient(135deg, #3498db, #2980b9) !important;
    }

    .small-box.bg-green {
        background: linear-gradient(135deg, #2ecc71, #27ae60) !important;
    }

    .small-box.bg-yellow {
        background: linear-gradient(135deg, #f39c12, #e67e22) !important;
    }

    .small-box.bg-red {
        background: linear-gradient(135deg, #e74c3c, #c0392b) !important;
    }

    .small-box.bg-aqua {
        background: linear-gradient(135deg, #1abc9c, #16a085) !important;
    }

    .small-box.bg-orange {
        background: linear-gradient(135deg, #e67e22, #d35400) !important;
    }

    /* Tailwind CSS overrides for widget background colors */
    .tw-bg-blue-100 {
        background-color: #dbeafe !important;
    }
    .tw-text-blue-500 {
        color: #3b82f6 !important;
    }
    .tw-bg-green-100 {
        background-color: #dcfce7 !important;
    }
    .tw-text-green-500 {
        color: #22c55e !important;
    }
    .tw-bg-yellow-100 {
        background-color: #fefce8 !important;
    }
    .tw-text-yellow-500 {
        color: #eab308 !important;
    }
    .tw-bg-red-100 {
        background-color: #fee2e2 !important;
    }
    .tw-text-red-500 {
        color: #ef4444 !important;
    }
    .tw-bg-purple-100 {
        background-color: #f3e8ff !important;
    }
    .tw-text-purple-500 {
        color: #a855f7 !important;
    }
    .tw-bg-orange-100 {
        background-color: #fed7aa !important;
    }
    .tw-text-orange-500 {
        color: #f97316 !important;
    }
</style>
@endsection