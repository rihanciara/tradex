@extends('advancedreports::layouts.app')
@section('title', __('Brand Wise Sales Report'))

@section('content')
@php
$symbol = session('currency')['symbol'];
@endphp
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('Brand Wise Sales Report')
        <small>@lang('View sales by brand with purchase price analysis')</small>
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
                    {!! Form::label('brand_id', __('product.brand') . ':') !!}
                    @php
                    $brand_options = [];
                    if(is_array($brands)) {
                    $brand_options = $brands;
                    } else {
                    $brand_options = $brands->toArray();
                    }
                    @endphp
                    {!! Form::select('brand_id', $brand_options, null, ['class' => 'form-control select2', 'style' =>
                    'width:100%', 'placeholder' => __('messages.please_select'), 'id' => 'brand_filter']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'placeholder' => __('messages.please_select'), 'id' => 'location_filter']);
                    !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('sales_date_filter_dropdown', __('report.date_range') . ':') !!}
                    <div class="dropdown date-filter-dropdown">
                        <button type="button" id="sales_date_filter_btn"
                            class="btn btn-default dropdown-toggle form-control text-left" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-calendar"></i>
                            <span id="date_filter_text">@lang('lang_v1.select_a_date_range')</span>
                            <span class="caret pull-right" style="margin-top: 8px;"></span>
                        </button>
                        <ul class="dropdown-menu" style="width: 100%;">
                            <li><a href="#" data-range="today">@lang('advancedreports::lang.today')</a></li>
                            <li><a href="#" data-range="yesterday">@lang('advancedreports::lang.yesterday')</a></li>
                            <li class="divider"></li>
                            <li><a href="#" data-range="this_week">@lang('advancedreports::lang.this_week')</a></li>
                            <li><a href="#" data-range="last_week">@lang('advancedreports::lang.last_week')</a></li>
                            <li class="divider"></li>
                            <li><a href="#" data-range="this_month">@lang('advancedreports::lang.this_month')</a></li>
                            <li><a href="#" data-range="last_month">@lang('advancedreports::lang.last_month')</a></li>
                            <li class="divider"></li>
                            <li><a href="#" data-range="this_quarter">@lang('advancedreports::lang.this_quarter')</a>
                            </li>
                            <li><a href="#" data-range="last_quarter">@lang('advancedreports::lang.last_quarter')</a>
                            </li>
                            <li class="divider"></li>
                            <li><a href="#" data-range="this_year">@lang('advancedreports::lang.this_year')</a></li>
                            <li><a href="#" data-range="last_year">@lang('advancedreports::lang.last_year')</a></li>
                            <li><a href="#" data-range="last_year">@lang('advancedreports::lang.last_year')</a></li>
                            <li class="divider"></li>
                            <li><a href="#" data-range="custom">@lang('advancedreports::lang.custom_range')</a></li>
                        </ul>
                    </div>
                    <input type="hidden" id="start_date" name="start_date" />
                    <input type="hidden" id="end_date" name="end_date" />
                    <input type="text" id="custom_date_range" class="form-control" style="display: none;" />
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('Brand Wise Sales Report')])
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="brand_wise_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('product.brand')</th>
                            <th>@lang('advancedreports::lang.total_products')</th>
                            <th>@lang('advancedreports::lang.total_transactions')</th>
                            <th>@lang('advancedreports::lang.total_quantity_sold')</th>
                            <th>@lang('advancedreports::lang.total_sales_amount')</th>
                            <th>@lang('advancedreports::lang.purchase_cost')</th>
                            <th>@lang('advancedreports::lang.gross_profit') ({{ $symbol }})</th>
                            <th>@lang('advancedreports::lang.profit_margin') (%)</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 footer_total text-center">
                            <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                            <td><span class="footer_qty display_currency" data-currency_symbol="false">0</span></td>
                            <td><span class="footer_sales display_currency" data-currency_symbol="true">0</span></td>
                            <td><span class="footer_purchase display_currency" data-currency_symbol="true">0</span></td>
                            <td><span class="footer_profit display_currency" data-currency_symbol="true">0</span></td>
                            <td><span class="footer_margin">0%</span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endcomponent
        </div>
    </div>
</section>

<!-- Brand Products Modal -->
<div class="modal fade" id="brand_products_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="brand_products_modal_label">@lang('Brand Products')</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div id="brand_summary" class="row" style="margin-bottom: 20px;"></div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="brand_products_table">
                        <thead>
                            <tr>
                                <th>@lang('business.product')</th>
                                <th>@lang('lang_v1.variation')</th>
                                <th>@lang('advancedreports::lang.quantity_sold')</th>
                                <th>@lang('sale.unit_price')</th>
                                <th>@lang('sale.total_amount')</th>
                                <th>@lang('lang_v1.date')</th>
                                <th>@lang('sale.invoice_no')</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr class="bg-gray font-17 text-center">
                                <td colspan="2"><strong>@lang('sale.total'):</strong></td>
                                <td><span id="modal_total_qty">0</span></td>
                                <td></td>
                                <td><span id="modal_total_amount">0.00</span></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
    
    var today = new Date();
    var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    function formatDate(date) {
        var d = new Date(date);
        var month = '' + (d.getMonth() + 1);
        var day = '' + d.getDate();
        var year = d.getFullYear();
        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;
        return [year, month, day].join('-');
    }
    
    document.getElementById('start_date').value = formatDate(firstDay);
    document.getElementById('end_date').value = formatDate(lastDay);
    document.getElementById('date_filter_text').textContent = 'This Month';
    
    function getDateRange(range) {
        var start = new Date();
        var end = new Date();
        
        switch(range) {
            case 'today':
                break;
            case 'yesterday':
                start.setDate(start.getDate() - 1);
                end.setDate(end.getDate() - 1);
                break;
            case 'this_week':
                var day = start.getDay();
                var diff = start.getDate() - day + (day === 0 ? -6 : 1);
                start = new Date(start.setDate(diff));
                end = new Date(start);
                end.setDate(start.getDate() + 6);
                break;
            case 'last_week':
                var day = start.getDay();
                var diff = start.getDate() - day + (day === 0 ? -6 : 1);
                start = new Date(start.setDate(diff - 7));
                end = new Date(start);
                end.setDate(start.getDate() + 6);
                break;
            case 'this_month':
                start = new Date(start.getFullYear(), start.getMonth(), 1);
                end = new Date(start.getFullYear(), start.getMonth() + 1, 0);
                break;
            case 'last_month':
                start = new Date(start.getFullYear(), start.getMonth() - 1, 1);
                end = new Date(start.getFullYear(), start.getMonth(), 0);
                break;
            case 'this_quarter':
                var quarter = Math.floor((start.getMonth() + 3) / 3);
                start = new Date(start.getFullYear(), quarter * 3 - 3, 1);
                end = new Date(start.getFullYear(), quarter * 3, 0);
                break;
            case 'last_quarter':
                var quarter = Math.floor((start.getMonth() + 3) / 3);
                start = new Date(start.getFullYear(), (quarter - 1) * 3 - 3, 1);
                end = new Date(start.getFullYear(), (quarter - 1) * 3, 0);
                break;
            case 'this_year':
                start = new Date(start.getFullYear(), 0, 1);
                end = new Date(start.getFullYear(), 11, 31);
                break;
            case 'last_year':
                start = new Date(start.getFullYear() - 1, 0, 1);
                end = new Date(start.getFullYear() - 1, 11, 31);
                break;
        }
        return {start: start, end: end};
    }
    
    $('.date-filter-dropdown .dropdown-menu a').on('click', function(e) {
        e.preventDefault();
        var range = $(this).data('range');
        var text = $(this).text();
        
        if (range === 'custom') {
            $('#custom_date_range').show().focus();
            return;
        }
        
        var dateRange = getDateRange(range);
        document.getElementById('start_date').value = formatDate(dateRange.start);
        document.getElementById('end_date').value = formatDate(dateRange.end);
        document.getElementById('date_filter_text').textContent = text;
        
        if (typeof brand_wise_table !== 'undefined') {
            brand_wise_table.ajax.reload();
        }
    });
    
    if (typeof $.fn.daterangepicker !== 'undefined') {
        $('#custom_date_range').daterangepicker({
            locale: { format: 'YYYY-MM-DD' }
        }, function(start, end) {
            document.getElementById('start_date').value = start.format('YYYY-MM-DD');
            document.getElementById('end_date').value = end.format('YYYY-MM-DD');
            document.getElementById('date_filter_text').textContent = start.format('MMM DD, YYYY') + ' - ' + end.format('MMM DD, YYYY');
            $('#custom_date_range').hide();
            if (typeof brand_wise_table !== 'undefined') {
                brand_wise_table.ajax.reload();
            }
        });
    }

    var brand_wise_table = $('#brand_wise_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('advancedreports.brand-monthly.brand-wise-sales') }}",
            data: function (d) {
                d.brand_id = $('#brand_filter').val();
                d.location_id = $('#location_filter').val();
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
            },
            error: function(xhr, error, thrown) {
                console.log('AJAX Error: ', xhr.responseText);
            }
        },
        columns: [
            {data: 'action', name: 'action', orderable: false, searchable: false},
            {data: 'brand_name', name: 'brand_name'},
            {data: 'total_products', name: 'total_products', searchable: false},
            {data: 'total_transactions', name: 'total_transactions', searchable: false},
            {data: 'total_qty_sold', name: 'total_qty_sold', searchable: false},
            {data: 'total_sales_amount', name: 'total_sales_amount', searchable: false},
            {data: 'total_purchase_price', name: 'total_purchase_price', searchable: false},
            {data: 'gross_profit', name: 'gross_profit', searchable: false},
            {data: 'profit_margin', name: 'profit_margin', searchable: false}
        ],
        "fnDrawCallback": function (oSettings) {
            if (typeof __currency_convert_recursively === 'function') {
                __currency_convert_recursively($('#brand_wise_table'));
            }
            
            var api = this.api();
            var pageData = api.rows({page: 'current'}).data().toArray();
            
            var total_qty = 0;
            var total_sales = 0;
            var total_purchase = 0;
            
            for (var i = 0; i < pageData.length; i++) {
                var row = pageData[i];
                total_qty += parseFloat(row.total_qty_sold_raw || 0);
                total_sales += parseFloat(row.total_sales_amount_raw || 0);
                total_purchase += parseFloat(row.total_purchase_cost_raw || 0);
            }
            
            var total_profit = total_sales - total_purchase;
            var profit_margin = total_sales > 0 ? ((total_profit / total_sales) * 100) : 0;
            
            if (typeof __currency_trans_from_en === 'function') {
                $('.footer_qty').text(__currency_trans_from_en(total_qty, false));
                $('.footer_sales').text(__currency_trans_from_en(total_sales, true));
                $('.footer_purchase').text(__currency_trans_from_en(total_purchase, true));
                $('.footer_profit').text(__currency_trans_from_en(total_profit, true));
            } else {
                $('.footer_qty').text(total_qty.toFixed(2));
                $('.footer_sales').text('$' + total_sales.toFixed(2));
                $('.footer_purchase').text('$' + total_purchase.toFixed(2));
                $('.footer_profit').text('$' + total_profit.toFixed(2));
            }
            
            $('.footer_margin').text(profit_margin.toFixed(2) + '%');
            
            if (typeof __currency_convert_recursively === 'function') {
                __currency_convert_recursively($('.footer_total'));
            }
        }
    });

    $('#brand_filter, #location_filter').change(function() {
        if (typeof brand_wise_table !== 'undefined') {
            brand_wise_table.ajax.reload();
        }
    });
    
    $(document).on('click', '.view-brand-products', function(e) {
        e.preventDefault();
        var brand_id = $(this).data('brand-id');
        var brand_name = $(this).closest('tr').find('td:eq(1)').text().trim();
        
        $('#brand_products_modal_label').text('Products for: ' + brand_name);
        $('#brand_summary').html('<div class="col-md-12 text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div>');
        $('#brand_products_table tbody').html('<tr><td colspan="7" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading products...</td></tr>');
        $('#brand_products_modal').modal('show');
        
        $.ajax({
            url: "{{ route('advancedreports.brand-products.index') }}",
            type: 'GET',
            data: {
                brand_id: brand_id,
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val(),
                location_id: $('#location_filter').val()
            },
            success: function(response) {
    if (response.success) {
        var summaryHtml = '<div class="col-md-6 col-sm-6 col-xs-12">' +
            '<div class="info-box">' +
            '<span class="info-box-icon bg-aqua"><i class="fa fa-cubes"></i></span>' +
            '<div class="info-box-content">' +
            '<span class="info-box-text">Total Products</span>' +
            '<span class="info-box-number">' + response.summary.total_products + '</span>' +
            '</div></div></div>' +
            '<div class="col-md-6 col-sm-6 col-xs-12">' +
            '<div class="info-box">' +
            '<span class="info-box-icon bg-green"><i class="fa fa-shopping-cart"></i></span>' +
            '<div class="info-box-content">' +
            '<span class="info-box-text">Total Quantity</span>' +
            '<span class="info-box-number">' + parseFloat(response.summary.total_quantity).toFixed(2) + '</span>' +
            '</div></div></div>' +
            '<div class="col-md-6 col-sm-6 col-xs-12">' +
            '<div class="info-box">' +
            '<span class="info-box-icon bg-yellow"><i class="fas fa-dollar-sign"></i></span>' +
            '<div class="info-box-content">' +
            '<span class="info-box-text">Total Sales</span>' +
            '<span class="info-box-number display_currency" data-currency_symbol="true">' + parseFloat(response.summary.total_amount).toFixed(2) + '</span>' +
            '</div></div></div>' +
            '<div class="col-md-6 col-sm-6 col-xs-12">' +
            '<div class="info-box">' +
            '<span class="info-box-icon bg-red"><i class="fas fa-file-invoice"></i></span>' +
            '<div class="info-box-content">' +
            '<span class="info-box-text">Total Transactions</span>' +
            '<span class="info-box-number">' + response.summary.total_transactions + '</span>' +
            '</div></div></div>';
        
        $('#brand_summary').html(summaryHtml);
        
        var tableHtml = '';
        var total_qty = 0;
        var total_amount = 0;
        
        if (response.products && response.products.length > 0) {
            $.each(response.products, function(index, product) {
                var quantity = parseFloat(product.quantity || 0);
                var unit_price = parseFloat(product.unit_price_inc_tax || 0);
                var line_total = parseFloat(product.line_total || 0);
                
                total_qty += quantity;
                total_amount += line_total;
                
                var variation_text = product.variation_name && product.variation_name !== 'DUMMY' ? 
                    ' (' + product.variation_name + ')' : '';
                
                tableHtml += '<tr>' +
                    '<td>' + (product.product_name || '') + variation_text + '</td>' +
                    '<td>' + (product.variation_name === 'DUMMY' ? '-' : (product.variation_name || '-')) + '</td>' +
                    '<td class="text-right">' + quantity.toFixed(2) + '</td>' +
                    '<td class="text-right"><span class="display_currency" data-currency_symbol="true">' + unit_price.toFixed(2) + '</span></td>' +
                    '<td class="text-right"><span class="display_currency" data-currency_symbol="true">' + line_total.toFixed(2) + '</span></td>' +
                    '<td>' + (product.transaction_date || '') + '</td>' +
                    '<td>' + (product.invoice_no || '') + '</td>' +
                    '</tr>';
            });
        } else {
            tableHtml = '<tr><td colspan="7" class="text-center">No products found for this brand in the selected date range.</td></tr>';
        }
        
        $('#brand_products_table tbody').html(tableHtml);
        $('#modal_total_qty').text(total_qty.toFixed(2));
        // Use display_currency class for the modal footer total
        $('#modal_total_amount').html('<span class="display_currency" data-currency_symbol="true">' + total_amount.toFixed(2) + '</span>');
        
        // Apply currency conversion after updating the HTML
        if (typeof __currency_convert_recursively === 'function') {
            __currency_convert_recursively($('#brand_products_modal'));
        }
        
    } else {
        $('#brand_summary').html('<div class="col-md-12 text-center text-danger">Error loading brand summary</div>');
        $('#brand_products_table tbody').html('<tr><td colspan="7" class="text-center text-danger">Error: ' + (response.message || 'Unknown error') + '</td></tr>');
    }
},
            error: function(xhr, status, error) {
                console.log('Error loading brand products:', xhr.responseText);
                $('#brand_summary').html('<div class="col-md-12 text-center text-danger">Error loading brand data</div>');
                $('#brand_products_table tbody').html('<tr><td colspan="7" class="text-center text-danger">Error loading products. Please try again.</td></tr>');
            }
        });
    });

});
</script>
@endsection