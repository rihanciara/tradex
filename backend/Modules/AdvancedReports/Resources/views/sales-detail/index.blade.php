@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.sales_detail_report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ __('advancedreports::lang.sales_detail_report')}}
        <small class="text-muted">@lang('advancedreports::lang.sales_detail_subtitle')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
    @component('components.filters', ['title' => __('advancedreports::lang.sales_detail_filters')])
    <form id="sales_detail_filter_form">
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sales_detail_date_filter', __('report.date_range') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-calendar"></i>
                    </span>
                    {!! Form::text('date_range', null, [
                    'placeholder' => __('lang_v1.select_a_date_range'),
                    'class' => 'form-control',
                    'id' => 'sales_detail_date_filter',
                    'readonly'
                    ]) !!}
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('location_id', __('business.business_location') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-map-marker"></i>
                    </span>
                    @php
                    $formatted_locations = is_array($business_locations) && isset($business_locations['locations'])
                    ? $business_locations['locations']
                    : $business_locations;

                    if (is_object($formatted_locations)) {
                    $formatted_locations = $formatted_locations->toArray();
                    }
                    @endphp
                    {!! Form::select('location_id', $formatted_locations, null, [
                    'class' => 'form-control select2',
                    'style' => 'width:100%',
                    'placeholder' => __('advancedreports::lang.all_locations'),
                    'id' => 'location_id'
                    ]) !!}
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('user_id', __('advancedreports::lang.staff') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-user"></i>
                    </span>
                    @php
                    $formatted_users = is_object($users) ? $users->toArray() : $users;
                    @endphp
                    {!! Form::select('user_id', $formatted_users, null, [
                    'class' => 'form-control select2',
                    'style' => 'width:100%',
                    'placeholder' => __('advancedreports::lang.all_staff'),
                    'id' => 'user_id'
                    ]) !!}
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('week_number', __('advancedreports::lang.week') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-calendar-week"></i>
                    </span>
                    <select id="week_number" name="week_number" class="form-control">
                        <option value="">{{ __('advancedreports::lang.all_weeks') }}</option>
                        <option value="1">{{ __('advancedreports::lang.week_1') }}</option>
                        <option value="2">{{ __('advancedreports::lang.week_2') }}</option>
                        <option value="3">{{ __('advancedreports::lang.week_3') }}</option>
                        <option value="4">{{ __('advancedreports::lang.week_4') }}</option>
                        <option value="5">{{ __('advancedreports::lang.week_5') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="col-md-12">
            <div class="form-group">
                <button type="button" class="btn btn-primary" id="apply-filters">
                    <i class="fa fa-filter"></i> {{ __('advancedreports::lang.apply_filters') }}
                </button>
                <button type="button" class="btn btn-success" id="export_btn">
                    <i class="fa fa-download"></i> {{ __('lang_v1.export') }}
                </button>
                <button type="button" class="btn btn-default" id="clear-filters">
                    <i class="fa fa-refresh"></i> {{ __('advancedreports::lang.clear') }}
                </button>
                <button type="button" class="btn btn-info" id="refresh-report">
                    <i class="fa fa-refresh"></i> {{ __('advancedreports::lang.refresh') }}
                </button>
            </div>
        </div>

        <!-- Quick Date Filters -->
        <div class="col-md-12">
            <div class="form-group">
                <label>{{ __('advancedreports::lang.quick_date_filters') }}:</label><br>
                <button type="button" class="btn btn-default quick-date" data-range="today">{{
                    __('advancedreports::lang.today') }}</button>
                <button type="button" class="btn btn-default quick-date" data-range="yesterday">{{
                    __('advancedreports::lang.yesterday') }}</button>
                <button type="button" class="btn btn-default quick-date" data-range="this_week">{{
                    __('advancedreports::lang.this_week') }}</button>
                <button type="button" class="btn btn-default quick-date" data-range="this_month">{{
                    __('advancedreports::lang.this_month') }}</button>
                <button type="button" class="btn btn-default quick-date" data-range="last_month">{{
                    __('advancedreports::lang.last_month') }}</button>
                <button type="button" class="btn btn-default" id="clear_date_filter">{{
                    __('advancedreports::lang.clear') }}</button>
            </div>
        </div>
    </form>
    @endcomponent

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-bar-chart"></i> {{ __('advancedreports::lang.sales_detail_summary') }}
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" id="toggle_summary">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            @component('advancedreports::components.static', [
                            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="tw-w-6 tw-h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                            </svg>',
                            'svg_bg' => 'tw-bg-green-100',
                            'svg_text' => 'tw-text-green-500'
                            ])
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500">{{
                                __('advancedreports::lang.total_sales') }}</p>
                            <p class="tw-text-2xl tw-font-semibold" id="total-sales"><span class="display_currency" data-currency_symbol="true">0</span></p>
                            @endcomponent
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            @component('advancedreports::components.static', [
                            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="tw-w-6 tw-h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>',
                            'svg_bg' => 'tw-bg-green-100',
                            'svg_text' => 'tw-text-green-500'
                            ])
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500">{{
                                __('advancedreports::lang.total_purchase') }}</p>
                            <p class="tw-text-2xl tw-font-semibold" id="total-purchase"><span class="display_currency" data-currency_symbol="true">0</span></p>
                            @endcomponent
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            @component('advancedreports::components.static', [
                            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="tw-w-6 tw-h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>',
                            'svg_bg' => 'tw-bg-yellow-100',
                            'svg_text' => 'tw-text-yellow-500'
                            ])
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500">{{
                                __('advancedreports::lang.total_profit') }}</p>
                            <p class="tw-text-2xl tw-font-semibold" id="total-profit"><span class="display_currency" data-currency_symbol="true">0</span></p>
                            @endcomponent
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            @component('advancedreports::components.static', [
                            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="tw-w-6 tw-h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                            </svg>',
                            'svg_bg' => 'tw-bg-red-100',
                            'svg_text' => 'tw-text-red-500'
                            ])
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500">{{
                                __('advancedreports::lang.profit_margin') }}</p>
                            <p class="tw-text-2xl tw-font-semibold" id="profit-margin">0.00%</p>
                            @endcomponent
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-table"></i> {{ __('advancedreports::lang.sales_detail_data') }}
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" id="toggle_table">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="sales_detail_table">
                            <thead>
                                <tr>
                                    <th>{{ __('business.product') }}</th>
                                    <th>{{ __('advancedreports::lang.invoice_no') }}</th>
                                    <th>{{ __('advancedreports::lang.sales_date') }}</th>
                                    <th>{{ __('advancedreports::lang.week_number') }}</th>
                                    <th>{{ __('advancedreports::lang.sales_unit') }}</th>
                                    <th>{{ __('advancedreports::lang.qty_sold') }}</th>
                                    <th>{{ __('advancedreports::lang.selling_price') }}</th>
                                    <th>{{ __('advancedreports::lang.purchase_price') }}</th>
                                    <th>{{ __('advancedreports::lang.total_sales_amt') }}</th>
                                    <th>{{ __('advancedreports::lang.total_purchase_amt') }}</th>
                                    <th>{{ __('advancedreports::lang.profit_earned') }}</th>
                                    <th>{{ __('advancedreports::lang.margin_percent') }}</th>
                                    <th>{{ __('advancedreports::lang.staff') }}</th>
                                    <th>{{ __('advancedreports::lang.day_number') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
    // Initialize date range picker
    if (typeof dateRangeSettings !== 'undefined') {
        $('#sales_detail_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
            $('#sales_detail_date_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
        });
    }

    // Initialize select2
    $('.select2').select2();

    // Quick date filter buttons
    $('.quick-date').click(function() {
        var range = $(this).data('range');
        var start, end;

        switch(range) {
            case 'today':
                start = end = moment();
                break;
            case 'yesterday':
                start = end = moment().subtract(1, 'days');
                break;
            case 'this_week':
                start = moment().startOf('week');
                end = moment().endOf('week');
                break;
            case 'this_month':
                start = moment().startOf('month');
                end = moment().endOf('month');
                break;
            case 'last_month':
                start = moment().subtract(1, 'month').startOf('month');
                end = moment().subtract(1, 'month').endOf('month');
                break;
        }

        $('#sales_detail_date_filter').data('daterangepicker').setStartDate(start);
        $('#sales_detail_date_filter').data('daterangepicker').setEndDate(end);
        $('#sales_detail_date_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
    });

    // Clear date filter
    $('#clear_date_filter').click(function() {
        $('#sales_detail_date_filter').val('');
    });

    // Initialize DataTable
    var sales_detail_table = $('#sales_detail_table').DataTable({
        processing: true,
        serverSide: true,
        pageLength: parseInt(__default_datatable_page_entries),
        ajax: {
            url: '{{ route("advancedreports.sales-detail.data") }}',
            data: function(d) {
                d.date_range = $('#sales_detail_date_filter').val();
                d.location_id = $('#location_id').val();
                d.user_id = $('#user_id').val();
                d.week_number = $('#week_number').val();
            }
        },
        columns: [
            {data: 'product_name', name: 'product_name', defaultContent: ''},
            {data: 'invoice_no', name: 'invoice_no', defaultContent: ''},
            {data: 'sales_date', name: 'sales_date', defaultContent: ''},
            {data: 'week_number', name: 'week_number', className: 'text-center', defaultContent: ''},
            {data: 'sales_unit', name: 'sales_unit', defaultContent: ''},
            {data: 'qty_sold', name: 'qty_sold', className: 'text-right', defaultContent: '0',
                render: function(data, type, row) {
                    return __number_f(data || 0, false, false);
                }
            },
            {data: 'selling_price', name: 'selling_price', className: 'text-right', defaultContent: '0',
                render: function(data, type, row) {
                    return __currency_trans_from_en(data || 0, true);
                }
            },
            {data: 'purchase_price', name: 'purchase_price', className: 'text-right', defaultContent: '0',
                render: function(data, type, row) {
                    return __currency_trans_from_en(data || 0, true);
                }
            },
            {data: 'total_sales_amt', name: 'total_sales_amt', className: 'text-right', defaultContent: '0',
                render: function(data, type, row) {
                    return __currency_trans_from_en(data || 0, true);
                }
            },
            {data: 'total_purchase_amt', name: 'total_purchase_amt', className: 'text-right', defaultContent: '0',
                render: function(data, type, row) {
                    return __currency_trans_from_en(data || 0, true);
                }
            },
            {data: 'profit_earned', name: 'profit_earned', className: 'text-right', defaultContent: '0',
                render: function(data, type, row) {
                    return __currency_trans_from_en(data || 0, true);
                }
            },
            {data: 'margin_percent', name: 'margin_percent', className: 'text-right', defaultContent: '0',
                render: function(data, type, row) {
                    return __number_f(data || 0, false, false) + '%';
                }
            },
            {data: 'staff', name: 'staff', defaultContent: '', orderable: false},
            {data: 'day_number', name: 'day_number', className: 'text-center', defaultContent: ''}
        ],
        fnDrawCallback: function(oSettings) {
            updateSummaryFromTable();
        }
    });

    // Apply filters
    $('#apply-filters').click(function() {
        sales_detail_table.ajax.reload();
    });

    // Clear filters
    $('#clear-filters').click(function() {
        $('#sales_detail_filter_form')[0].reset();
        $('.select2').val(null).trigger('change');
        $('#sales_detail_date_filter').val('');
        sales_detail_table.ajax.reload();
    });

    // Export button click
    $('#export_btn').click(function(e) {
        e.preventDefault();

        var originalText = $(this).html();
        $(this).html('<i class="fa fa-spinner fa-spin"></i> {{ __("advancedreports::lang.exporting") }}');
        $(this).prop('disabled', true);

        // Get form data
        var formData = $('#sales_detail_filter_form').serializeArray();
        var params = {};
        $(formData).each(function(index, obj){
            params[obj.name] = obj.value;
        });

        // Add CSRF token
        params._token = '{{ csrf_token() }}';

        // Generate filename
        var filename = 'sales_detail_report_' + new Date().toISOString().slice(0,10) + '.xlsx';

        $.ajax({
            url: '{{ route("advancedreports.sales-detail.export") }}',
            type: 'POST',
            data: params,
            xhrFields: {
                responseType: 'blob'
            },
            success: function(data, status, xhr) {
                try {
                    var blob = new Blob([data], {
                        type: xhr.getResponseHeader('Content-Type') || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    });
                    var link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(link.href);

                    toastr.success('{{ __("advancedreports::lang.exported_successfully") }}');
                } catch(e) {
                    console.error('Export failed:', e);
                    toastr.error('Export failed. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Export failed:', error);
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    toastr.error(xhr.responseJSON.error);
                } else {
                    toastr.error('Export failed. Please try again.');
                }
            },
            complete: function() {
                setTimeout(function() {
                    $('#export_btn').html(originalText);
                    $('#export_btn').prop('disabled', false);
                }, 1000);
            }
        });
    });

    // Refresh report
    $('#refresh-report').click(function() {
        sales_detail_table.ajax.reload();
    });

    // Toggle summary section
    $('#toggle_summary').click(function() {
        var icon = $(this).find('i');
        if (icon.hasClass('fa-minus')) {
            icon.removeClass('fa-minus').addClass('fa-plus');
            $(this).closest('.box').find('.box-body').slideUp();
        } else {
            icon.removeClass('fa-plus').addClass('fa-minus');
            $(this).closest('.box').find('.box-body').slideDown();
        }
    });

    // Toggle table section
    $('#toggle_table').click(function() {
        var icon = $(this).find('i');
        if (icon.hasClass('fa-minus')) {
            icon.removeClass('fa-minus').addClass('fa-plus');
            $(this).closest('.box').find('.box-body').slideUp();
        } else {
            icon.removeClass('fa-plus').addClass('fa-minus');
            $(this).closest('.box').find('.box-body').slideDown();
        }
    });
});

function updateSummaryFromTable() {
    // Get summary data via AJAX
    var formData = $('#sales_detail_filter_form').serialize();

    $.ajax({
        url: '{{ route("advancedreports.sales-detail.summary") }}',
        type: 'GET',
        data: formData,
        success: function(response) {
            if (response.success) {
                updateTotals(response.totals);
            }
        },
        error: function() {
            // Handle error silently or show notification
        }
    });
}

function updateTotals(totals) {
    if (totals) {
        $('#total-sales').text(__currency_trans_from_en(totals.total_sales || 0, true));
        $('#total-purchase').text(__currency_trans_from_en(totals.total_purchase || 0, true));
        $('#total-profit').text(__currency_trans_from_en(totals.total_profit || 0, true));
        $('#profit-margin').text(__number_f(totals.profit_margin || 0, false, false) + '%');
    }
}

</script>
@endsection