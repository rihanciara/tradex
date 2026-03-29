@extends('advancedreports::layouts.app')
@section('title', __('Brand Monthly Sales Report'))

@php
$symbol = session('currency')['symbol'];
@endphp

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('advancedreports::lang.brand_monthly_sales_report') }}
        <small>{{ __('advancedreports::lang.view_brand_sales_by_months_with_profit_analysis') }}</small>
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
                    {{-- {!! Form::label('brand_name', __('product.brand') . ' Name:') !!} --}}
                    {!! Form::text('brand_name', null, ['class' => 'form-control', 'id' => 'brand_name_filter',
                    'placeholder' => __('advancedreports::lang.search_by_brand_name')]); !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <button type="button" class="btn btn-primary" id="filter_btn">
                        <i class="fa fa-filter"></i> @lang('advancedreports::lang.search')
                    </button>
                    <button type="button" class="btn btn-success" id="export_btn">
                        <i class="fa fa-download"></i> @lang('lang_v1.export')
                    </button>
                    <button type="button" class="btn btn-info" id="refresh_btn">
                        <i class="fa fa-refresh"></i> @lang('lang_v1.refresh')
                    </button>
                </div>
            </div>

            {{-- Divider --}}
            <div class="col-md-12">
                <hr>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('brand_id', __('product.brand') . ':') !!}
                    @php
                    $brand_options = ['' => __('lang_v1.all'), '0' => 'No Brand'];
                    if(is_array($brands)) {
                    $brand_options = array_merge($brand_options, $brands);
                    } else {
                    $brand_options = collect($brand_options)->merge($brands)->toArray();
                    }
                    @endphp
                    {!! Form::select('brand_id', $brand_options, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'brand_filter']); !!}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'location_filter']); !!}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('payment_status', __('purchase.payment_status') . ':') !!}
                    {!! Form::select('payment_status', [
                    '' => __('lang_v1.all'),
                    'paid' => __('lang_v1.paid'),
                    'due' => __('lang_v1.due'),
                    'partial' => __('lang_v1.partial')
                    ], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' =>
                    'payment_status_filter']); !!}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('payment_method', __('lang_v1.payment_method') . ':') !!}
                    {!! Form::select('payment_method', $payment_types, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'payment_method_filter']); !!}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('user_id', __('advancedreports::lang.staff') . ':') !!}
                    {!! Form::select('user_id', $users, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'user_filter']); !!}
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('year', __('Year') . ':') !!}
                    {!! Form::select('year', array_combine(range(date('Y')-5, date('Y')+1), range(date('Y')-5,
                    date('Y')+1)), date('Y'), ['class' => 'form-control select2', 'style' => 'width:100%', 'id' =>
                    'year_filter']); !!}
                </div>
            </div>


            @endcomponent
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row" id="summary_cards">
        <!-- Total Brands -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-brands">
                <i class="fa fa-tags modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">Total Brands</div>
                    <div class="modern-widget-number" id="total_brands">0</div>
                </div>
            </div>
        </div>

        <!-- Total Transactions -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-transactions">
                <i class="fa fa-shopping-cart modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">Total Transactions</div>
                    <div class="modern-widget-number" id="total_transactions">0</div>
                </div>
            </div>
        </div>

        <!-- Total Sales -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-sales">
                <i class="fa fa-money modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">Total Sales</div>
                    <div class="modern-widget-number" id="total_sales">$0.00</div>
                </div>
            </div>
        </div>

        <!-- Total Quantity -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-quantity">
                <i class="fa fa-cubes modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">Total Quantity</div>
                    <div class="modern-widget-number" id="total_quantity">0</div>
                </div>
            </div>
        </div>

        <!-- Total Profit -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-profit">
                <i class="fa fa-line-chart modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">Total Profit</div>
                    <div class="modern-widget-number" id="total_profit">$0.00</div>
                </div>
            </div>
        </div>

        <!-- Average per Brand -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-average">
                <i class="fa fa-calculator modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">Avg per Brand</div>
                    <div class="modern-widget-number" id="avg_per_brand">$0.00</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' =>
            'Brand Monthly Sales Report'])
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view colored-header" id="brand_monthly_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('product.brand')</th>
                            <th>@lang('advancedreports::lang.jan')</th>
                            <th>@lang('advancedreports::lang.feb')</th>
                            <th>@lang('advancedreports::lang.mar')</th>
                            <th>@lang('advancedreports::lang.apr')</th>
                            <th>@lang('advancedreports::lang.may')</th>
                            <th>@lang('advancedreports::lang.jun')</th>
                            <th>@lang('advancedreports::lang.jul')</th>
                            <th>@lang('advancedreports::lang.aug')</th>
                            <th>@lang('advancedreports::lang.sep')</th>
                            <th>@lang('advancedreports::lang.oct')</th>
                            <th>@lang('advancedreports::lang.nov')</th>
                            <th>@lang('advancedreports::lang.dec')</th>
                            <th>@lang('cash_register.total_sales')</th>
                            <th>@lang('advancedreports::lang.total_qty')</th>
                            <th>@lang('lang_v1.gross_profit') ({{ $symbol }})</th>
                            <th>@lang('lang_v1.gross_profit') (%)</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 text-center footer-total">
                            <td colspan="2"><strong>@lang('sale.total'):</strong></td>
                            <td class="footer_jan"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_feb"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_mar"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_apr"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_may"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_jun"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_jul"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_aug"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_sep"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_oct"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_nov"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_dec"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_total_sales"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td class="footer_total_quantity"><span>0</span></td>
                            <td class="footer_gross_profit"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td class="footer_profit_percent">0%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Brand Details Modal -->
    <div class="modal fade brand_details_modal" tabindex="-1" role="dialog" aria-labelledby="brandDetailsModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="brandDetailsModalLabel">Brand Details</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="brand_details_content">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin fa-3x text-muted"></i>
                        <p class="text-muted">Loading brand details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</section>
<!-- /.content -->

@stop

@section('javascript')
<script type="text/javascript">
    // Currency formatting function
    function formatCurrency(num) {
        if (num === null || num === undefined || num === '') {
            return __currency_trans_from_en('0.00', true);
        }
        
        if (typeof num === 'number') {
            return __currency_trans_from_en(num.toFixed(2), true);
        }
        
        if (typeof num === 'string') {
            var cleanStr = num.replace(/[^\d.-]/g, '');
            var parsed = parseFloat(cleanStr);
            
            if (!isNaN(parsed)) {
                return __currency_trans_from_en(parsed.toFixed(2), true);
            }
        }
        
        console.warn('formatCurrency received invalid input:', num, typeof num);
        return __currency_trans_from_en('0.00', true);
    }

    function formatNumber(num) {
        if (num === null || num === undefined || num === '') {
            return '0';
        }
        
        if (typeof num === 'number') {
            return num.toLocaleString('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            });
        }
        
        if (typeof num === 'string') {
            var cleanStr = num.replace(/[^\d.-]/g, '');
            var parsed = parseFloat(cleanStr);
            
            if (!isNaN(parsed)) {
                return parsed.toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });
            }
        }
        
        console.warn('formatNumber received invalid input:', num, typeof num);
        return '0';
    }

document.addEventListener('DOMContentLoaded', function() {
        // Currency column detection and sorting
        jQuery.extend(jQuery.fn.dataTableExt.oSort, {
            "currency-asc": function (a, b) {
                var aText = typeof a === 'string' ? a : $(a).text();
                var bText = typeof b === 'string' ? b : $(b).text();
                
                var x = parseFloat(aText.replace(/[^\d.-]/g, '')) || 0;
                var y = parseFloat(bText.replace(/[^\d.-]/g, '')) || 0;
                
                return x - y;
            },
            "currency-desc": function (a, b) {
                var aText = typeof a === 'string' ? a : $(a).text();
                var bText = typeof b === 'string' ? b : $(b).text();
                
                var x = parseFloat(aText.replace(/[^\d.-]/g, '')) || 0;
                var y = parseFloat(bText.replace(/[^\d.-]/g, '')) || 0;
                
                return y - x;
            }
        });

        $.fn.dataTable.ext.type.detect.unshift(function (data) {
            if (typeof data === 'string' && (
                data.match(/^[\$£€¥₹]/) || 
                data.match(/[\$£€¥₹]$/) || 
                data.match(/^\d{1,3}(,\d{3})*(\.\d+)?$/) || 
                data.match(/\d+\.\d+/) || 
                data.match(/%$/)
            )) {
                return 'currency';
            }
            return null;
        });

        // DataTable initialization
        var brand_monthly_table = $('#brand_monthly_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.brand-monthly.data') }}",
                data: function (d) {
                    d.brand_name = $('#brand_name_filter').val();
                    d.brand_id = $('#brand_filter').val();
                    d.location_id = $('#location_filter').val();
                    d.payment_status = $('#payment_status_filter').val();
                    d.payment_method = $('#payment_method_filter').val();
                    d.user_id = $('#user_filter').val();
                    d.year = $('#year_filter').val();
                }
            },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false, width: '80px' },
                { data: 'brand_name', name: 'brand_name', width: '250px' },
                {
                    data: 'jan',
                    name: 'jan',
                    searchable: false,
                    width: '100px',
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            var cleanData = typeof data === 'string' ? data.replace(/<[^>]*>/g, '').replace(/[^\d.-]/g, '') : data;
                            return formatCurrency(parseFloat(cleanData) || 0);
                        }
                        return data;
                    }
                },
                {
                    data: 'feb',
                    name: 'feb',
                    searchable: false,
                    width: '100px',
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            var cleanData = typeof data === 'string' ? data.replace(/<[^>]*>/g, '').replace(/[^\d.-]/g, '') : data;
                            return formatCurrency(parseFloat(cleanData) || 0);
                        }
                        return data;
                    }
                },
                {
                    data: 'mar',
                    name: 'mar',
                    searchable: false,
                    width: '100px',
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            var cleanData = typeof data === 'string' ? data.replace(/<[^>]*>/g, '').replace(/[^\d.-]/g, '') : data;
                            return formatCurrency(parseFloat(cleanData) || 0);
                        }
                        return data;
                    }
                },
                {
                    data: 'apr',
                    name: 'apr',
                    searchable: false,
                    width: '100px',
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            var cleanData = typeof data === 'string' ? data.replace(/<[^>]*>/g, '').replace(/[^\d.-]/g, '') : data;
                            return formatCurrency(parseFloat(cleanData) || 0);
                        }
                        return data;
                    }
                },
                {
                    data: 'may',
                    name: 'may',
                    searchable: false,
                    width: '100px',
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            var cleanData = typeof data === 'string' ? data.replace(/<[^>]*>/g, '').replace(/[^\d.-]/g, '') : data;
                            return formatCurrency(parseFloat(cleanData) || 0);
                        }
                        return data;
                    }
                },
                {
                    data: 'jun',
                    name: 'jun',
                    searchable: false,
                    width: '100px',
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            var cleanData = typeof data === 'string' ? data.replace(/<[^>]*>/g, '').replace(/[^\d.-]/g, '') : data;
                            return formatCurrency(parseFloat(cleanData) || 0);
                        }
                        return data;
                    }
                },
                {
                    data: 'jul',
                    name: 'jul',
                    searchable: false,
                    width: '100px',
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            var cleanData = typeof data === 'string' ? data.replace(/<[^>]*>/g, '').replace(/[^\d.-]/g, '') : data;
                            return formatCurrency(parseFloat(cleanData) || 0);
                        }
                        return data;
                    }
                },
                {
                    data: 'aug',
                    name: 'aug',
                    searchable: false,
                    width: '100px',
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            var cleanData = typeof data === 'string' ? data.replace(/<[^>]*>/g, '').replace(/[^\d.-]/g, '') : data;
                            return formatCurrency(parseFloat(cleanData) || 0);
                        }
                        return data;
                    }
                },
                {
                    data: 'sep',
                    name: 'sep',
                    searchable: false,
                    width: '100px',
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            var cleanData = typeof data === 'string' ? data.replace(/<[^>]*>/g, '').replace(/[^\d.-]/g, '') : data;
                            return formatCurrency(parseFloat(cleanData) || 0);
                        }
                        return data;
                    }
                },
                {
                    data: 'oct',
                    name: 'oct',
                    searchable: false,
                    width: '100px',
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            var cleanData = typeof data === 'string' ? data.replace(/<[^>]*>/g, '').replace(/[^\d.-]/g, '') : data;
                            return formatCurrency(parseFloat(cleanData) || 0);
                        }
                        return data;
                    }
                },
                {
                    data: 'nov',
                    name: 'nov',
                    searchable: false,
                    width: '100px',
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            var cleanData = typeof data === 'string' ? data.replace(/<[^>]*>/g, '').replace(/[^\d.-]/g, '') : data;
                            return formatCurrency(parseFloat(cleanData) || 0);
                        }
                        return data;
                    }
                },
                {
                    data: 'dec',
                    name: 'dec',
                    searchable: false,
                    width: '100px',
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            var cleanData = typeof data === 'string' ? data.replace(/<[^>]*>/g, '').replace(/[^\d.-]/g, '') : data;
                            return formatCurrency(parseFloat(cleanData) || 0);
                        }
                        return data;
                    }
                },
                {
                    data: 'total_sales',
                    name: 'total_sales',
                    searchable: false,
                    width: '120px',
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            var cleanData = typeof data === 'string' ? data.replace(/<[^>]*>/g, '').replace(/[^\d.-]/g, '') : data;
                            return formatCurrency(parseFloat(cleanData) || 0);
                        }
                        return data;
                    }
                },
                {
                    data: 'total_quantity',
                    name: 'total_quantity',
                    searchable: false,
                    width: '100px',
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            var cleanData = typeof data === 'string' ? data.replace(/<[^>]*>/g, '').replace(/[^\d.-]/g, '') : data;
                            return formatNumber(parseFloat(cleanData) || 0);
                        }
                        return data;
                    }
                },
                {
                    data: 'gross_profit',
                    name: 'gross_profit',
                    searchable: false,
                    width: '120px',
                    render: function(data, type, row) {
                        if (type === 'display' && data) {
                            var cleanData = typeof data === 'string' ? data.replace(/<[^>]*>/g, '').replace(/[^\d.-]/g, '') : data;
                            return formatCurrency(parseFloat(cleanData) || 0);
                        }
                        return data;
                    }
                },
                { data: 'gross_profit_percent', name: 'gross_profit_percent', searchable: false, width: '120px' }
            ],
            columnDefs: [
                {
                    targets: [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 16, 17],
                    type: 'currency'
                }
            ],
            order: [[14, 'desc']],
            scrollX: true,
            autoWidth: false,

            footerCallback: function (row, data, start, end, display) {
                __currency_convert_recursively($('#brand_monthly_table'));
                
                var api = this.api();
                
                var months = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
                var monthTotals = {};
                
                // Initialize month totals
                months.forEach(function(month) {
                    monthTotals[month] = 0;
                });
                
                // Calculate monthly totals
                months.forEach(function(month, index) {
                    var columnIndex = index + 2;
                    
                    api.column(columnIndex, {page: 'current'}).data().each(function(value, rowIndex) {
                        try {
                            var numericValue = 0;

                            if (typeof value === 'string') {
                                var tempDiv = document.createElement('div');
                                tempDiv.innerHTML = value;
                                var textValue = tempDiv.textContent || tempDiv.innerText || '';
                                var cleanValue = textValue.replace(/[$,\s]/g, '').replace(/[^\d.]/g, '');
                                numericValue = parseFloat(cleanValue) || 0;
                            } else if (typeof value === 'number') {
                                numericValue = value;
                            } else if (value && typeof value === 'object') {
                                var textValue = $(value).text() || '';
                                var cleanValue = textValue.replace(/[$,\s]/g, '').replace(/[^\d.]/g, '');
                                numericValue = parseFloat(cleanValue) || 0;
                            }

                            monthTotals[month] += Math.abs(numericValue);
                        } catch (e) {
                            console.warn('Error parsing month value for ' + month + ':', value, e);
                        }
                    });
                });
                
                // Calculate total sales
                var total_sales_sum = 0;
                api.column(14, {page: 'current'}).data().each(function(value) {
                    try {
                        var numericValue = 0;
                        
                        if (typeof value === 'string') {
                            var tempDiv = document.createElement('div');
                            tempDiv.innerHTML = value;
                            var textValue = tempDiv.textContent || tempDiv.innerText || '';
                            var cleanValue = textValue.replace(/[$,\s]/g, '').replace(/[^\d.]/g, '');
                            numericValue = parseFloat(cleanValue) || 0;
                        } else if (typeof value === 'number') {
                            numericValue = value;
                        }

                        total_sales_sum += Math.abs(numericValue);
                    } catch (e) {
                        console.warn('Error parsing total sales value:', value, e);
                    }
                });

                // Calculate total quantity
                var total_quantity_sum = 0;
                api.column(15, {page: 'current'}).data().each(function(value) {
                    try {
                        var numericValue = 0;
                        
                        if (typeof value === 'string') {
                            var tempDiv = document.createElement('div');
                            tempDiv.innerHTML = value;
                            var textValue = tempDiv.textContent || tempDiv.innerText || '';
                            var cleanValue = textValue.replace(/[^\d.]/g, '');
                            numericValue = parseFloat(cleanValue) || 0;
                        } else if (typeof value === 'number') {
                            numericValue = value;
                        }

                        total_quantity_sum += Math.abs(numericValue);
                    } catch (e) {
                        console.warn('Error parsing total quantity value:', value, e);
                    }
                });
                
                // Calculate total profit
                var total_profit = 0;
                api.column(16, {page: 'current'}).data().each(function(value) {
                    try {
                        var numericValue = 0;
                        
                        if (typeof value === 'string') {
                            var tempDiv = document.createElement('div');
                            tempDiv.innerHTML = value;
                            var textValue = tempDiv.textContent || tempDiv.innerText || '';
                            var cleanValue = textValue.replace(/[$,\s]/g, '').replace(/[^\d.]/g, '');
                            numericValue = parseFloat(cleanValue) || 0;
                        } else if (typeof value === 'number') {
                            numericValue = value;
                        }

                        total_profit += Math.abs(numericValue);
                    } catch (e) {
                        console.warn('Error parsing profit value:', value, e);
                    }
                });

                // Update footer using row parameter to target specific footer
                $(row).find('.footer_jan').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.jan || 0).toFixed(2) + '</span>');
                $(row).find('.footer_feb').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.feb || 0).toFixed(2) + '</span>');
                $(row).find('.footer_mar').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.mar || 0).toFixed(2) + '</span>');
                $(row).find('.footer_apr').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.apr || 0).toFixed(2) + '</span>');
                $(row).find('.footer_may').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.may || 0).toFixed(2) + '</span>');
                $(row).find('.footer_jun').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.jun || 0).toFixed(2) + '</span>');
                $(row).find('.footer_jul').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.jul || 0).toFixed(2) + '</span>');
                $(row).find('.footer_aug').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.aug || 0).toFixed(2) + '</span>');
                $(row).find('.footer_sep').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.sep || 0).toFixed(2) + '</span>');
                $(row).find('.footer_oct').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.oct || 0).toFixed(2) + '</span>');
                $(row).find('.footer_nov').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.nov || 0).toFixed(2) + '</span>');
                $(row).find('.footer_dec').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.dec || 0).toFixed(2) + '</span>');
                $(row).find('.footer_total_sales').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(total_sales_sum || 0).toFixed(2) + '</span>');
                $(row).find('.footer_total_quantity').html('<span>' + formatNumber(Math.abs(total_quantity_sum || 0)) + '</span>');
                $(row).find('.footer_gross_profit').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(total_profit || 0).toFixed(2) + '</span>');

                var overall_profit_percent = total_sales_sum > 0 ? (total_profit / total_sales_sum * 100) : 0;
                $(row).find('.footer_profit_percent').text(Math.abs(overall_profit_percent || 0).toFixed(2) + '%');

                __currency_convert_recursively($(row));
            },
            createdRow: function( row, data, dataIndex ) {
                for(var i = 2; i <= 17; i++) {
                    $(row).find('td:eq(' + i + ')').addClass('text-right');
                }
            }
        });

        // Filter button click
        $('#filter_btn').click(function() {
            brand_monthly_table.ajax.reload();
            loadSummary();
        });

        // Refresh button click
        $('#refresh_btn').click(function() {
            brand_monthly_table.ajax.reload();
            loadSummary();
        });

        // Export button click
        $('#export_btn').click(function(e) {
            e.preventDefault();
            
            var brand_name = $('#brand_name_filter').val() || '';
            var brand_id = $('#brand_filter').val() || '';
            var location_id = $('#location_filter').val() || '';
            var payment_status = $('#payment_status_filter').val() || '';
            var payment_method = $('#payment_method_filter').val() || '';
            var user_id = $('#user_filter').val() || '';
            var year = $('#year_filter').val() || '';
            
            var $btn = $(this);
            var originalText = $btn.html();
            $btn.html('<i class="fa fa-spinner fa-spin"></i> Exporting...').prop('disabled', true);
            
            // Create iframe for download
            var $iframe = $('<iframe>', {
                src: "{{ route('advancedreports.brand-monthly.export') }}" + '?' + $.param({
                    brand_name: brand_name,
                    brand_id: brand_id,
                    location_id: location_id,
                    payment_status: payment_status,
                    payment_method: payment_method,
                    user_id: user_id,
                    year: year
                }),
                style: 'display: none;'
            });
            
            $iframe.appendTo('body');
            
            setTimeout(function() {
                $iframe.remove();
                $btn.html(originalText).prop('disabled', false);
            }, 5000);
        });

        // Load summary data
        function loadSummary() {
            var brand_name = $('#brand_name_filter').val();
            var brand_id = $('#brand_filter').val();
            var location_id = $('#location_filter').val();
            var payment_status = $('#payment_status_filter').val();
            var payment_method = $('#payment_method_filter').val();
            var user_id = $('#user_filter').val();
            var year = $('#year_filter').val();
            
            $.ajax({
                url: "{{ route('advancedreports.brand-monthly.summary') }}",
                data: {
                    brand_name: brand_name,
                    brand_id: brand_id,
                    location_id: location_id,
                    payment_status: payment_status,
                    payment_method: payment_method,
                    user_id: user_id,
                    year: year
                },
                dataType: 'json',
               success: function(data) {
    var totalBrands = parseInt(data.total_brands) || 0;
    var totalTransactions = parseInt(data.total_transactions) || 0;
    var totalSales = parseFloat(data.total_sales) || 0;
    var totalQuantity = parseFloat(data.total_quantity) || 0;
    var totalProfit = parseFloat(data.total_profit) || 0;
    var averagePerBrand = parseFloat(data.average_per_brand) || 0;
    
    $('#total_brands').text(formatNumber(totalBrands));
    $('#total_transactions').text(formatNumber(totalTransactions));
    $('#total_sales').text(formatCurrency(totalSales));        // ✅ Use actual value
    $('#total_quantity').text(formatNumber(totalQuantity));    // ✅ Use actual value  
    $('#total_profit').text(formatCurrency(totalProfit));      // ✅ Use actual value
    $('#avg_per_brand').text(formatCurrency(averagePerBrand)); // ✅ Use actual value
}
            });
        }

        // Auto-filter on change for filters
        $('#brand_name_filter, #brand_filter, #location_filter, #payment_status_filter, #payment_method_filter, #user_filter, #year_filter').change(function() {
            brand_monthly_table.ajax.reload();
            loadSummary();
        });

        // Brand details modal
        $(document).on('click', '.view-brand-details', function(e) {
            e.preventDefault();
            var brandId = $(this).data('brand-id');
            var year = $('#year_filter').val() || new Date().getFullYear();
            
            console.log('Loading brand details for ID:', brandId, 'Year:', year);
            
            $('.brand_details_modal').modal('show');
            $('#brand_details_content').html(`
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-3x text-muted"></i>
                    <p class="text-muted">Loading brand details...</p>
                </div>
            `);
            
            $.ajax({
                url: "{{ route('advancedreports.brand-monthly.details', '') }}/" + brandId,
                method: 'GET',
                data: { 
                    year: year,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                timeout: 30000,
                success: function(response) {
                    console.log('Brand details response:', response);
                    
                    if (response && response.brand) {
                        var brand = response.brand;
                        var products = response.products || [];
                        
                        var html = `
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="box box-info">
                                        <div class="box-header with-border">
                                            <h3 class="box-title"><i class="fa fa-tag"></i> Brand Information</h3>
                                        </div>
                                        <div class="box-body">
                                            <table class="table table-striped">
                                                <tr><td><strong>Name:</strong></td><td>${brand.name || 'No Brand'}</td></tr>
                                                <tr><td><strong>Description:</strong></td><td>${brand.description || 'N/A'}</td></tr>
                                                <tr><td><strong>ID:</strong></td><td>${brand.id || 0}</td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="box box-success">
                                        <div class="box-header with-border">
                                            <h3 class="box-title"><i class="fa fa-bar-chart"></i> ${year} Summary</h3>
                                        </div>
                                        <div class="box-body">
                                            <div class="row text-center">
                                                <div class="col-md-6">
                                                    <div class="description-block border-right">
                                                        <span class="description-percentage text-green"><i class="fa fa-shopping-cart"></i></span>
                                                        <h5 class="description-header">${products.length || 0}</h5>
                                                        <span class="description-text">TOTAL TRANSACTIONS</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="description-block">
                                                        <span class="description-percentage text-yellow"><i class="fa fa-money"></i></span>
                                                        <h5 class="description-header">${formatCurrency(calculateTotalAmount(products))}</h5>
                                                        <span class="description-text">TOTAL SALES</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row text-center" style="margin-top: 15px;">
                                                <div class="col-md-6">
                                                    <div class="description-block border-right">
                                                        <span class="description-percentage text-blue"><i class="fa fa-calculator"></i></span>
                                                        <h5 class="description-header">${formatCurrency(calculateAverageTransaction(products))}</h5>
                                                        <span class="description-text">AVG PER TRANSACTION</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="description-block">
                                                        <span class="description-percentage text-red"><i class="fa fa-cubes"></i></span>
                                                        <h5 class="description-header">${formatNumber(calculateTotalQuantity(products))}</h5>
                                                        <span class="description-text">TOTAL QTY</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Add products table if there are products
                        if (products && products.length > 0) {
                            html += `
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="box box-primary">
                                            <div class="box-header with-border">
                                                <h3 class="box-title"><i class="fa fa-list"></i> Recent Sales</h3>
                                                <div class="box-tools pull-right">
                                                    <span class="label label-primary">${products.length} transactions</span>
                                                </div>
                                            </div>
                                            <div class="box-body" style="max-height: 400px; overflow-y: auto;">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-bordered table-hover table-condensed" id="brand-products-table">
                                                        <thead style="position: sticky; top: 0; background: #f5f5f5; z-index: 10;">
                                                            <tr>
                                                                <th style="width: 100px;">Date</th>
                                                                <th style="width: 120px;">Invoice</th>
                                                                <th style="min-width: 200px;">Product</th>
                                                                <th style="width: 80px;">Month</th>
                                                                <th style="width: 80px;">Qty</th>
                                                                <th style="width: 100px;">Unit Price</th>
                                                                <th style="width: 120px;">Line Total</th>
                                                                <th style="width: 90px;">Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                            `;
                            
                            products.forEach(function(product, index) {
                                var date = new Date(product.transaction_date).toLocaleDateString();
                                var statusClass = '';
                                var statusText = product.payment_status || 'unknown';
                                
                                switch(statusText.toLowerCase()) {
                                    case 'paid':
                                        statusClass = 'label-success';
                                        break;
                                    case 'due':
                                        statusClass = 'label-danger';
                                        break;
                                    case 'partial':
                                        statusClass = 'label-warning';
                                        break;
                                    default:
                                        statusClass = 'label-default';
                                }
                                
                                var rowClass = index % 2 === 0 ? 'even-row' : 'odd-row';
                                
                                html += `
                                    <tr class="${rowClass}" style="font-size: 12px;">
                                        <td>${date}</td>
                                        <td><strong style="font-size: 11px;">${product.invoice_no || 'N/A'}</strong></td>
                                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                                            title="${product.product_name || 'N/A'}">${product.product_name || 'N/A'}</td>
                                        <td><span class="label label-info" style="font-size: 10px;">${product.month_name || 'N/A'}</span></td>
                                        <td class="text-center">${formatNumber(product.quantity || 0)}</td>
                                        <td class="text-right">${formatCurrency(product.unit_price_inc_tax || 0)}</td>
                                        <td class="text-right"><strong>${formatCurrency(product.line_total || 0)}</strong></td>
                                        <td><span class="label ${statusClass}" style="font-size: 10px;">${statusText.toUpperCase()}</span></td>
                                    </tr>
                                `;
                            });
                            
                            html += `
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="box-footer">
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <small class="text-muted">
                                                            <i class="fa fa-info-circle"></i> 
                                                            Showing recent ${products.length} transactions for ${year}
                                                        </small>
                                                    </div>
                                                    <div class="col-sm-6 text-right">
                                                        <small class="text-muted">
                                                            Total: <strong>${formatCurrency(calculateTotalAmount(products))}</strong>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else {
                            html += `
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i> No sales found for this brand in ${year}.
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                        
                        $('#brand_details_content').html(html);
                        $('#brandDetailsModalLabel').text('Brand Details - ' + (brand.name || 'No Brand') + ' (' + year + ')');
                        
                    } else {
                        $('#brand_details_content').html(`
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i> No brand data found.
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    
                    var errorMessage = 'Error loading brand details.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMessage = xhr.responseJSON.error;
                    } else if (xhr.status === 404) {
                        errorMessage = 'Brand not found.';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Access denied.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error occurred.';
                    }
                    
                    $('#brand_details_content').html(`
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-triangle"></i> ${errorMessage}
                            <br><small>Please try again or contact administrator.</small>
                        </div>
                    `);
                }
            });
        });

        // Helper functions for calculations
        function calculateTotalAmount(products) {
            var total = 0;
            products.forEach(function(p) {
                total += parseFloat(p.line_total || 0);
            });
            return total;
        }

        function calculateAverageTransaction(products) {
            if (products.length === 0) return 0;
            return calculateTotalAmount(products) / products.length;
        }

        function calculateTotalQuantity(products) {
            var total = 0;
            products.forEach(function(p) {
                total += parseFloat(p.quantity || 0);
            });
            return total;
        }

        // Load initial summary
        loadSummary();
    });
</script>

<style>
    /* Brand-specific widget colors */
    .widget-brands {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%) !important;
    }

    .widget-quantity {
        background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%) !important;
    }

    /* Reuse existing styles from customer monthly report */
    .modern-widget {
        height: 95px !important;
        display: flex !important;
        align-items: center !important;
        padding: 18px !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12) !important;
        transition: all 0.3s ease !important;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
        color: white;
        border: none !important;
    }

    .modern-widget::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: inherit;
        opacity: 0.95;
        z-index: 1;
    }

    .modern-widget:hover {
        transform: translateY(-4px) scale(1.02) !important;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2) !important;
    }

    .modern-widget-icon {
        font-size: 45px !important;
        opacity: 1 !important;
        margin-right: 18px !important;
        width: 55px !important;
        text-align: center !important;
        flex-shrink: 0 !important;
        color: white !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        z-index: 2 !important;
        position: relative !important;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
    }

    .modern-widget-content {
        flex-grow: 1 !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        min-height: 65px !important;
        color: white !important;
        z-index: 2 !important;
        position: relative !important;
    }

    .modern-widget-text {
        font-size: 13px !important;
        opacity: 0.98 !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 1px !important;
        margin-bottom: 6px !important;
        line-height: 1.2 !important;
        color: white !important;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2) !important;
    }

    .modern-widget-number {
        font-size: 26px !important;
        font-weight: 700 !important;
        line-height: 1 !important;
        color: white !important;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2) !important;
        margin: 0 !important;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    }

    .widget-transactions {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
    }

    .widget-sales {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
    }

    .widget-profit {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%) !important;
    }

    .widget-average {
        background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%) !important;
        color: #333 !important;
    }

    .widget-average * {
        color: #333 !important;
        text-shadow: none !important;
    }

    .modern-widget:not(.widget-average) * {
        color: white !important;
    }

    /* Table styling */
    table.colored-header thead th {
        background-color: #3498db;
        color: white;
        font-weight: bold;
        border-bottom: none;
        position: sticky;
        top: 0;
    }

    .footer-total {
        background-color: #f5f5f5 !important;
        font-weight: bold !important;
        font-size: 11px !important;
    }

    .footer-total td {
        border-top: 2px solid #ddd !important;
        padding: 6px 4px !important;
        white-space: nowrap !important;
        font-size: 11px !important;
    }

    /* Modal styling */
    .brand_details_modal .modal-dialog {
        max-width: 95%;
        width: 1200px;
    }

    .brand_details_modal .table {
        margin-bottom: 0;
    }

    .brand_details_modal .table td {
        padding: 8px;
        border: 1px solid #ddd;
    }

    .brand_details_modal .box {
        margin-bottom: 20px;
    }

    .brand_details_modal .description-block {
        padding: 10px;
    }

    .brand_details_modal .description-header {
        font-size: 20px;
        font-weight: bold;
        margin: 5px 0;
    }

    .brand_details_modal .description-text {
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 600;
    }

    .brand_details_modal .description-percentage {
        font-size: 20px;
        display: block;
        margin-bottom: 5px;
    }

    .brand_details_modal .border-right {
        border-right: 1px solid #ddd;
    }

    .brand_details_modal .text-green {
        color: #00a65a !important;
    }

    .brand_details_modal .text-yellow {
        color: #f39c12 !important;
    }

    .brand_details_modal .text-blue {
        color: #3c8dbc !important;
    }

    .brand_details_modal .text-red {
        color: #dd4b39 !important;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
        .modern-widget {
            min-height: 90px !important;
            height: 90px !important;
        }

        .modern-widget-icon {
            font-size: 40px !important;
        }

        .modern-widget-text {
            font-size: 12px !important;
        }

        .modern-widget-number {
            font-size: 24px !important;
        }
    }

    @media (max-width: 768px) {
        .modern-widget {
            min-height: 85px !important;
            height: 85px !important;
            padding: 14px !important;
        }

        .modern-widget-icon {
            font-size: 36px !important;
            margin-right: 12px !important;
        }

        .modern-widget-text {
            font-size: 11px !important;
        }

        .modern-widget-number {
            font-size: 22px !important;
        }
    }
</style>