@extends('advancedreports::layouts.app')
@section('title', __('Supplier Stock Movement & Profit Report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>Supplier Stock Movement & Profit Report
        <small>Track stock movements and profitability by supplier</small>
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
                    {!! Form::label('supplier_id', __('purchase.supplier') . ':') !!}
                    {!! Form::select('supplier_id', $suppliers, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'supplier_filter']); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'location_filter']); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('date_range', __('report.date_range') . ' (for transactions):') !!}
                    {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class'
                    => 'form-control', 'id' => 'date_range_filter', 'readonly']); !!}
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
                    <button type="button" class="btn btn-info" id="refresh_btn">
                        <i class="fa fa-refresh"></i> @lang('lang_v1.refresh')
                    </button>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row" id="summary_cards">
        <!-- Total Suppliers -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-suppliers">
                <i class="fa fa-truck modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">Total Suppliers</div>
                    <div class="modern-widget-number" id="total_suppliers">0</div>
                </div>
            </div>
        </div>

        <!-- Current Stock Value -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-stock">
                <i class="fa fa-cubes modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">Current Stock Value</div>
                    <div class="modern-widget-number" id="total_stock_value">$0</div>
                </div>
            </div>
        </div>

        <!-- Total Sales -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-sales">
                <i class="fa fa-money modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">Total Sales Value</div>
                    <div class="modern-widget-number" id="total_sales_value">$0</div>
                </div>
            </div>
        </div>

        <!-- Total Purchases -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-purchases">
                <i class="fa fa-shopping-cart modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">Total Purchases</div>
                    <div class="modern-widget-number" id="total_purchases_value">$0</div>
                </div>
            </div>
        </div>

        <!-- Total Profit -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-profit">
                <i class="fa fa-line-chart modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">Total Profit</div>
                    <div class="modern-widget-number" id="total_profit">$0</div>
                </div>
            </div>
        </div>

        <!-- Profit Margin -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-margin">
                <i class="fa fa-percent modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">Profit Margin</div>
                    <div class="modern-widget-number" id="profit_margin">0%</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' =>
            'Supplier Stock Movement & Profit Report'])
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view colored-header" id="supplier_stock_table">
                    <thead>
                        <tr>
                            <th rowspan="2">@lang('messages.action')</th>
                            <th rowspan="2">Supplier</th>
                            <th colspan="3" class="text-center bg-info">Today Stock</th>
                            <th colspan="3" class="text-center bg-success">Total Sale</th>
                            <th colspan="3" class="text-center bg-warning">Total Purchase</th>
                            <th colspan="3" class="text-center bg-primary">Balance</th>
                            <th rowspan="2" class="text-center bg-danger">Profit Value</th>
                        </tr>
                        <tr>
                            <!-- Today Stock -->
                            <th class="text-center">Qty</th>
                            <th class="text-center">Purchase Value</th>
                            <th class="text-center">Sale Value</th>
                            <!-- Total Sale -->
                            <th class="text-center">Qty</th>
                            <th class="text-center">Purchase Value</th>
                            <th class="text-center">Sale Value</th>
                            <!-- Total Purchase -->
                            <th class="text-center">Qty</th>
                            <th class="text-center">Purchase Value</th>
                            <th class="text-center">Sale Value</th>
                            <!-- Balance -->
                            <th class="text-center">Qty</th>
                            <th class="text-center">Purchase Value</th>
                            <th class="text-center">Value</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 text-center footer-total">
                            <td colspan="2"><strong>@lang('sale.total'):</strong></td>
                            <!-- Today Stock Totals -->
                            <td class="footer_today_stock_qty">0</td>
                            <td class="footer_today_stock_purchase_value"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td class="footer_today_stock_sale_value"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <!-- Total Sale Totals -->
                            <td class="footer_total_sale_qty">0</td>
                            <td class="footer_total_sale_purchase_value"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td class="footer_total_sale_sale_value"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <!-- Total Purchase Totals -->
                            <td class="footer_total_purchase_qty">0</td>
                            <td class="footer_total_purchase_purchase_value"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td class="footer_total_purchase_sale_value"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <!-- Balance Totals -->
                            <td class="footer_balance_qty">0</td>
                            <td class="footer_balance_purchase_value"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td class="footer_balance_value"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <!-- Profit Total -->
                            <td class="footer_profit_value"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Supplier Details Modal -->
    <div class="modal fade supplier_details_modal" tabindex="-1" role="dialog"
        aria-labelledby="supplierDetailsModalLabel">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="supplierDetailsModalLabel">Supplier Details</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="supplier_details_content">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin fa-3x text-muted"></i>
                        <p class="text-muted">Loading supplier details...</p>
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

    $(document).ready(function() {
        // Initialize date range picker
        dateRangeSettings.startDate = moment().startOf('month');
        dateRangeSettings.endDate = moment().endOf('month');
        $('#date_range_filter').daterangepicker(
            dateRangeSettings, 
            function(start, end) {
                $('#date_range_filter').val(
                    start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                );
                supplier_stock_table.ajax.reload();
                loadSummary();
            }
        );
        $('#date_range_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#date_range_filter').val('');
            supplier_stock_table.ajax.reload();
            loadSummary();
        });

        // Initialize DataTable
        var supplier_stock_table = $('#supplier_stock_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.supplier-stock-movement.data') }}",
                data: function (d) {
                    d.supplier_id = $('#supplier_filter').val();
                    d.location_id = $('#location_filter').val();
                    
                    var start = '';
                    var end = '';
                    if ($('#date_range_filter').val()) {
                        start = $('input#date_range_filter')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');
                        end = $('input#date_range_filter')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                    }
                    d.start_date = start;
                    d.end_date = end;
                }
            },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false, width: '80px' },
                { data: 'supplier_display', name: 'supplier_name', width: '200px' },
                // Today Stock
                { data: 'today_stock_qty', name: 'today_stock_qty', searchable: false, width: '80px', className: 'text-center' },
                { data: 'today_stock_purchase_value', name: 'today_stock_purchase_value', searchable: false, width: '120px', className: 'text-right' },
                { data: 'today_stock_sale_value', name: 'today_stock_sale_value', searchable: false, width: '120px', className: 'text-right' },
                // Total Sale
                { data: 'total_sale_qty', name: 'total_sale_qty', searchable: false, width: '80px', className: 'text-center' },
                { data: 'total_sale_purchase_value', name: 'total_sale_purchase_value', searchable: false, width: '120px', className: 'text-right' },
                { data: 'total_sale_sale_value', name: 'total_sale_sale_value', searchable: false, width: '120px', className: 'text-right' },
                // Total Purchase
                { data: 'total_purchase_qty', name: 'total_purchase_qty', searchable: false, width: '80px', className: 'text-center' },
                { data: 'total_purchase_purchase_value', name: 'total_purchase_purchase_value', searchable: false, width: '120px', className: 'text-right' },
                { data: 'total_purchase_sale_value', name: 'total_purchase_sale_value', searchable: false, width: '120px', className: 'text-right' },
                // Balance
                { data: 'balance_qty', name: 'balance_qty', searchable: false, width: '80px', className: 'text-center' },
                { data: 'balance_purchase_value', name: 'balance_purchase_value', searchable: false, width: '120px', className: 'text-right' },
                { data: 'balance_value', name: 'balance_value', searchable: false, width: '120px', className: 'text-right' },
                // Profit
                { data: 'profit_value', name: 'profit_value', searchable: false, width: '120px', className: 'text-right' }
            ],
            order: [[13, 'desc']], // Sort by profit value desc
            scrollX: true,
            autoWidth: false,
           "fnDrawCallback": function (oSettings) {
    __currency_convert_recursively($('#supplier_stock_table'));
    
    var api = this.api();
    
    // Helper function to extract numeric value from DataTable cell
    function extractNumericValue(value) {
        try {
            if (typeof value === 'number') {
                return value;
            }
            
            if (typeof value === 'string') {
                // Create temporary div to extract text content from HTML
                var tempDiv = document.createElement('div');
                tempDiv.innerHTML = value;
                var textValue = tempDiv.textContent || tempDiv.innerText || '';
                
                // Check if there's a data-orig-value attribute
                var origValue = $(tempDiv).find('span[data-orig-value]').attr('data-orig-value');
                if (origValue !== undefined) {
                    return parseFloat(origValue) || 0;
                }
                
                // Remove currency symbols, commas, spaces and extract numeric value
                var cleanValue = textValue.replace(/[^\d.-]/g, '');
                return parseFloat(cleanValue) || 0;
            }
            
            return 0;
        } catch (e) {
            console.warn('Error parsing value:', value, e);
            return 0;
        }
    }
    
    // Initialize totals
    var totals = {
        today_stock_qty: 0,
        today_stock_purchase_value: 0,
        today_stock_sale_value: 0,
        total_sale_qty: 0,
        total_sale_purchase_value: 0,
        total_sale_sale_value: 0,
        total_purchase_qty: 0,
        total_purchase_purchase_value: 0,
        total_purchase_sale_value: 0,
        balance_qty: 0,
        balance_purchase_value: 0,
        balance_value: 0,
        profit_value: 0
    };
    
    // Calculate totals for each column (adjust column indices based on your table structure)
    // Assuming columns are: Action, Supplier, Today Stock Qty, Today Stock Purchase Value, etc.
    
    // Today Stock Qty (column 2)
    api.column(2, {page: 'current'}).data().each(function(value) {
        totals.today_stock_qty += extractNumericValue(value);
    });
    
    // Today Stock Purchase Value (column 3)
    api.column(3, {page: 'current'}).data().each(function(value) {
        totals.today_stock_purchase_value += extractNumericValue(value);
    });
    
    // Today Stock Sale Value (column 4)
    api.column(4, {page: 'current'}).data().each(function(value) {
        totals.today_stock_sale_value += extractNumericValue(value);
    });
    
    // Total Sale Qty (column 5)
    api.column(5, {page: 'current'}).data().each(function(value) {
        totals.total_sale_qty += extractNumericValue(value);
    });
    
    // Total Sale Purchase Value (column 6)
    api.column(6, {page: 'current'}).data().each(function(value) {
        totals.total_sale_purchase_value += extractNumericValue(value);
    });
    
    // Total Sale Sale Value (column 7)
    api.column(7, {page: 'current'}).data().each(function(value) {
        totals.total_sale_sale_value += extractNumericValue(value);
    });
    
    // Total Purchase Qty (column 8)
    api.column(8, {page: 'current'}).data().each(function(value) {
        totals.total_purchase_qty += extractNumericValue(value);
    });
    
    // Total Purchase Purchase Value (column 9)
    api.column(9, {page: 'current'}).data().each(function(value) {
        totals.total_purchase_purchase_value += extractNumericValue(value);
    });
    
    // Total Purchase Sale Value (column 10)
    api.column(10, {page: 'current'}).data().each(function(value) {
        totals.total_purchase_sale_value += extractNumericValue(value);
    });
    
    // Balance Qty (column 11)
    api.column(11, {page: 'current'}).data().each(function(value) {
        totals.balance_qty += extractNumericValue(value);
    });
    
    // Balance Purchase Value (column 12)
    api.column(12, {page: 'current'}).data().each(function(value) {
        totals.balance_purchase_value += extractNumericValue(value);
    });
    
    // Balance Value (column 13)
    api.column(13, {page: 'current'}).data().each(function(value) {
        totals.balance_value += extractNumericValue(value);
    });
    
    // Profit Value (column 14)
    api.column(14, {page: 'current'}).data().each(function(value) {
        totals.profit_value += extractNumericValue(value);
    });
    
    // Update footer cells (adjust selectors based on your footer structure)
    $('.footer_today_stock_qty').text(totals.today_stock_qty.toFixed(2));
    $('.footer_today_stock_purchase_value').html('<span class="display_currency" data-currency_symbol="true">' + totals.today_stock_purchase_value.toFixed(2) + '</span>');
    $('.footer_today_stock_sale_value').html('<span class="display_currency" data-currency_symbol="true">' + totals.today_stock_sale_value.toFixed(2) + '</span>');
    $('.footer_total_sale_qty').text(totals.total_sale_qty.toFixed(2));
    $('.footer_total_sale_purchase_value').html('<span class="display_currency" data-currency_symbol="true">' + totals.total_sale_purchase_value.toFixed(2) + '</span>');
    $('.footer_total_sale_sale_value').html('<span class="display_currency" data-currency_symbol="true">' + totals.total_sale_sale_value.toFixed(2) + '</span>');
    $('.footer_total_purchase_qty').text(totals.total_purchase_qty.toFixed(2));
    $('.footer_total_purchase_purchase_value').html('<span class="display_currency" data-currency_symbol="true">' + totals.total_purchase_purchase_value.toFixed(2) + '</span>');
    $('.footer_total_purchase_sale_value').html('<span class="display_currency" data-currency_symbol="true">' + totals.total_purchase_sale_value.toFixed(2) + '</span>');
    $('.footer_balance_qty').text(totals.balance_qty.toFixed(2));
    $('.footer_balance_purchase_value').html('<span class="display_currency" data-currency_symbol="true">' + totals.balance_purchase_value.toFixed(2) + '</span>');
    $('.footer_balance_value').html('<span class="display_currency" data-currency_symbol="true">' + totals.balance_value.toFixed(2) + '</span>');
    $('.footer_profit_value').html('<span class="display_currency" data-currency_symbol="true">' + totals.profit_value.toFixed(2) + '</span>');
    
    // Apply currency conversion to footer
    __currency_convert_recursively($('.footer-total, .tfoot'));
}
        });

        // Filter button click
        $('#filter_btn').click(function() {
            supplier_stock_table.ajax.reload();
            loadSummary();
        });

        // Refresh button click
        $('#refresh_btn').click(function() {
            supplier_stock_table.ajax.reload();
            loadSummary();
        });

        // Export button click
        $('#export_btn').click(function(e) {
            e.preventDefault();
            
            var supplier_id = $('#supplier_filter').val() || '';
            var location_id = $('#location_filter').val() || '';
            
            var start_date = '';
            var end_date = '';
            if ($('#date_range_filter').val()) {
                start_date = $('#date_range_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                end_date = $('#date_range_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
            }
            
            var $btn = $(this);
            var originalText = $btn.html();
            $btn.html('<i class="fa fa-spinner fa-spin"></i> Exporting...').prop('disabled', true);
            
            var url = "{{ route('advancedreports.supplier-stock-movement.export') }}";
            url += '?supplier_id=' + supplier_id + '&location_id=' + location_id;
            url += '&start_date=' + start_date + '&end_date=' + end_date;
            
            window.open(url, '_blank');
            
            setTimeout(function() {
                $btn.html(originalText).prop('disabled', false);
            }, 3000);
        });

        // Load summary data
        function loadSummary() {
            var supplier_id = $('#supplier_filter').val();
            var location_id = $('#location_filter').val();
            
            var start_date = '';
            var end_date = '';
            if ($('#date_range_filter').val()) {
                start_date = $('#date_range_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                end_date = $('#date_range_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
            }
            
            $.ajax({
                url: "{{ route('advancedreports.supplier-stock-movement.summary') }}",
                data: {
                    supplier_id: supplier_id,
                    location_id: location_id,
                    start_date: start_date,
                    end_date: end_date
                },
                dataType: 'json',
                success: function(data) {
                    var totalSuppliers = parseInt(data.total_suppliers) || 0;
                    var totalStockValue = parseFloat(data.total_today_stock_sale_value) || 0;
                    var totalSalesValue = parseFloat(data.total_sale_sale_value) || 0;
                    var totalPurchasesValue = parseFloat(data.total_purchase_purchase_value) || 0;
                    var totalProfit = parseFloat(data.total_profit_value) || 0;
                    var profitMargin = parseFloat(data.profit_margin_percent) || 0;
                    
                    $('#total_suppliers').text(formatNumber(totalSuppliers));
                    $('#total_stock_value').text(formatCurrency(totalStockValue));
                    $('#total_sales_value').text(formatCurrency(totalSalesValue));
                    $('#total_purchases_value').text(formatCurrency(totalPurchasesValue));
                    $('#total_profit').text(formatCurrency(totalProfit));
                    $('#profit_margin').text(profitMargin.toFixed(2) + '%');
                    
                    $('#summary_cards').show();
                },
                error: function(xhr, status, error) {
                    console.log('Error loading summary data:', error);
                    
                    // Set default values on error
                    $('#total_suppliers').text('0');
                    $('#total_stock_value').text(formatCurrency(0));
                    $('#total_sales_value').text(formatCurrency(0));
                    $('#total_purchases_value').text(formatCurrency(0));
                    $('#total_profit').text(formatCurrency(0));
                    $('#profit_margin').text('0.00%');
                }
            });
        }

        // Auto-filter on change for filters
        $('#supplier_filter, #location_filter').change(function() {
            supplier_stock_table.ajax.reload();
            loadSummary();
        });

        // Supplier details modal
        $(document).on('click', '.view-supplier-details', function(e) {
            e.preventDefault();
            var supplierId = $(this).data('supplier-id');
            
            console.log('Loading supplier details for ID:', supplierId);
            
            $('.supplier_details_modal').modal('show');
            $('#supplier_details_content').html(`
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-3x text-muted"></i>
                    <p class="text-muted">Loading supplier details...</p>
                </div>
            `);
            
            $.ajax({
                url: "{{ route('advancedreports.supplier-stock-movement.details', '') }}/" + supplierId,
                method: 'GET',
                data: { 
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                timeout: 30000,
                success: function(response) {
                    console.log('Supplier details response:', response);
                    
                    if (response && response.supplier) {
                        var supplier = response.supplier;
                        var products = response.products || [];
                        var transactions = response.transactions || [];
                        
                        var html = `
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="box box-info">
                                        <div class="box-header with-border">
                                            <h3 class="box-title"><i class="fa fa-truck"></i> Supplier Information</h3>
                                        </div>
                                        <div class="box-body">
                                            <table class="table table-striped">
                                                <tr><td><strong>Name:</strong></td><td>${supplier.name || 'N/A'}</td></tr>
                                                <tr><td><strong>Business:</strong></td><td>${supplier.supplier_business_name || 'N/A'}</td></tr>
                                                <tr><td><strong>Contact ID:</strong></td><td>${supplier.contact_id || supplier.id || 'N/A'}</td></tr>
                                                <tr><td><strong>Mobile:</strong></td><td>${supplier.mobile || 'N/A'}</td></tr>
                                                <tr><td><strong>Email:</strong></td><td>${supplier.email || 'N/A'}</td></tr>
                                                <tr><td><strong>Address:</strong></td><td>${supplier.address_line_1 || 'N/A'}</td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="box box-success">
                                        <div class="box-header with-border">
                                            <h3 class="box-title"><i class="fa fa-bar-chart"></i> Summary</h3>
                                        </div>
                                        <div class="box-body">
                                            <div class="row text-center">
                                                <div class="col-md-4">
                                                    <div class="description-block border-right">
                                                        <span class="description-percentage text-green"><i class="fa fa-cubes"></i></span>
                                                        <h5 class="description-header">${products.length || 0}</h5>
                                                        <span class="description-text">TOTAL PRODUCTS</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="description-block border-right">
                                                        <span class="description-percentage text-yellow"><i class="fa fa-archive"></i></span>
                                                        <h5 class="description-header">${formatNumber(calculateTotalStockQty(products))}</h5>
                                                        <span class="description-text">TOTAL STOCK QTY</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="description-block">
                                                        <span class="description-percentage text-blue"><i class="fa fa-money"></i></span>
                                                        <h5 class="description-header">${formatCurrency(calculateTotalStockValue(products))}</h5>
                                                        <span class="description-text">STOCK VALUE</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Add products table
                        if (products && products.length > 0) {
                            html += `
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="box box-primary">
                                            <div class="box-header with-border">
                                                <h3 class="box-title"><i class="fa fa-list"></i> Products</h3>
                                                <div class="box-tools pull-right">
                                                    <span class="label label-primary">${products.length} products</span>
                                                </div>
                                            </div>
                                            <div class="box-body" style="max-height: 400px; overflow-y: auto;">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-bordered table-hover table-condensed">
                                                        <thead style="position: sticky; top: 0; background: #f5f5f5; z-index: 10;">
                                                            <tr>
                                                                <th>Product Name</th>
                                                                <th>SKU</th>
                                                                <th>Current Stock</th>
                                                                <th>Unit</th>
                                                                <th>Stock Value</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                            `;
                            
                            products.forEach(function(product, index) {
                                var rowClass = index % 2 === 0 ? 'even-row' : 'odd-row';
                                
                                html += `
                                    <tr class="${rowClass}" style="font-size: 12px;">
                                        <td>${product.product_name || 'N/A'}</td>
                                        <td>${product.sku || 'N/A'}</td>
                                        <td class="text-center">${formatNumber(product.current_stock || 0)}</td>
                                        <td class="text-center">${product.unit || ''}</td>
                                        <td class="text-right">${formatCurrency(product.stock_value || 0)}</td>
                                    </tr>
                                `;
                            });
                            
                            html += `
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                        
                        // Add recent transactions table
                        if (transactions && transactions.length > 0) {
                            html += `
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="box box-warning">
                                            <div class="box-header with-border">
                                                <h3 class="box-title"><i class="fa fa-exchange"></i> Recent Transactions</h3>
                                                <div class="box-tools pull-right">
                                                    <span class="label label-warning">${transactions.length} transactions</span>
                                                </div>
                                            </div>
                                            <div class="box-body" style="max-height: 400px; overflow-y: auto;">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-bordered table-hover table-condensed">
                                                        <thead style="position: sticky; top: 0; background: #f5f5f5; z-index: 10;">
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Ref No</th>
                                                                <th>Product</th>
                                                                <th>Type</th>
                                                                <th>Qty</th>
                                                                <th>Unit Price</th>
                                                                <th>Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                            `;
                            
                            transactions.forEach(function(transaction, index) {
                                var date = new Date(transaction.transaction_date).toLocaleDateString();
                                var typeClass = transaction.transaction_type === 'Purchase' ? 'label-info' : 'label-success';
                                var rowClass = index % 2 === 0 ? 'even-row' : 'odd-row';
                                
                                html += `
                                    <tr class="${rowClass}" style="font-size: 12px;">
                                        <td>${date}</td>
                                        <td><strong>${transaction.ref_no || 'N/A'}</strong></td>
                                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                                            title="${transaction.product_name || 'N/A'}">${transaction.product_name || 'N/A'}</td>
                                        <td><span class="label ${typeClass}" style="font-size: 10px;">${transaction.transaction_type}</span></td>
                                        <td class="text-center">${formatNumber(transaction.quantity || 0)}</td>
                                        <td class="text-right">${formatCurrency(transaction.unit_price || 0)}</td>
                                        <td class="text-right"><strong>${formatCurrency(transaction.line_total || 0)}</strong></td>
                                    </tr>
                                `;
                            });
                            
                            html += `
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                        
                        $('#supplier_details_content').html(html);
                        $('#supplierDetailsModalLabel').text('Supplier Details - ' + (supplier.name || 'Unknown'));
                        
                    } else {
                        $('#supplier_details_content').html(`
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i> No supplier data found.
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    
                    var errorMessage = 'Error loading supplier details.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMessage = xhr.responseJSON.error;
                    } else if (xhr.status === 404) {
                        errorMessage = 'Supplier not found.';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Access denied.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error occurred.';
                    }
                    
                    $('#supplier_details_content').html(`
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-triangle"></i> ${errorMessage}
                            <br><small>Please try again or contact administrator.</small>
                        </div>
                    `);
                }
            });
        });

        // Helper functions for calculations
        function calculateTotalStockQty(products) {
            var total = 0;
            products.forEach(function(p) {
                total += parseFloat(p.current_stock || 0);
            });
            return total;
        }

        function calculateTotalStockValue(products) {
            var total = 0;
            products.forEach(function(p) {
                total += parseFloat(p.stock_value || 0);
            });
            return total;
        }

        // Load initial summary
        loadSummary();
    });
</script>

<style>
    /* Modern Widget Styling */
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

    /* Widget Color Schemes */
    .widget-suppliers {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    }

    .widget-stock {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
    }

    .widget-sales {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
    }

    .widget-purchases {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%) !important;
    }

    .widget-profit {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%) !important;
    }

    .widget-margin {
        background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%) !important;
        color: #333 !important;
    }

    .widget-margin * {
        color: #333 !important;
        text-shadow: none !important;
    }

    /* Force all text to be white except margin widget */
    .modern-widget:not(.widget-margin) * {
        color: white !important;
    }

    /* Table styling */
    table.colored-header thead th {
        background-color: #3498db;
        color: white;
        font-weight: bold;
        border-bottom: none;
        text-align: center;
    }

    /* Multi-level headers */
    table.colored-header thead tr:first-child th.bg-info {
        background-color: #5bc0de !important;
    }

    table.colored-header thead tr:first-child th.bg-success {
        background-color: #5cb85c !important;
    }

    table.colored-header thead tr:first-child th.bg-warning {
        background-color: #f0ad4e !important;
    }

    table.colored-header thead tr:first-child th.bg-primary {
        background-color: #337ab7 !important;
    }

    table.colored-header thead tr:first-child th.bg-danger {
        background-color: #d9534f !important;
    }

    /* Footer styling */
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
    .supplier_details_modal .modal-dialog {
        max-width: 95%;
        width: 1400px;
    }

    .supplier_details_modal .table {
        margin-bottom: 0;
    }

    .supplier_details_modal .table td {
        padding: 8px;
        border: 1px solid #ddd;
    }

    .supplier_details_modal .box {
        margin-bottom: 20px;
    }

    .supplier_details_modal .description-block {
        padding: 10px;
    }

    .supplier_details_modal .description-header {
        font-size: 20px;
        font-weight: bold;
        margin: 5px 0;
    }

    .supplier_details_modal .description-text {
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 600;
    }

    .supplier_details_modal .description-percentage {
        font-size: 20px;
        display: block;
        margin-bottom: 5px;
    }

    .supplier_details_modal .border-right {
        border-right: 1px solid #ddd;
    }

    .supplier_details_modal .text-green {
        color: #00a65a !important;
    }

    .supplier_details_modal .text-yellow {
        color: #f39c12 !important;
    }

    .supplier_details_modal .text-blue {
        color: #3c8dbc !important;
    }

    .supplier_details_modal .text-red {
        color: #dd4b39 !important;
    }

    .supplier_details_modal .table-hover tbody tr:hover {
        background-color: #f5f5f5;
    }

    .supplier_details_modal .even-row {
        background-color: #fafafa;
    }

    .supplier_details_modal .odd-row {
        background-color: #ffffff;
    }

    .supplier_details_modal tbody tr:hover {
        background-color: #e8f4f8 !important;
    }

    /* Scrollbar styling */
    .supplier_details_modal .box-body::-webkit-scrollbar {
        width: 8px;
    }

    .supplier_details_modal .box-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .supplier_details_modal .box-body::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .supplier_details_modal .box-body::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .modern-widget {
            height: 85px !important;
            padding: 14px !important;
        }

        .modern-widget-icon {
            font-size: 36px !important;
            width: 45px !important;
            margin-right: 12px !important;
        }

        .modern-widget-text {
            font-size: 11px !important;
            letter-spacing: 0.5px !important;
        }

        .modern-widget-number {
            font-size: 22px !important;
        }

        .supplier_details_modal .modal-dialog {
            width: 95%;
            margin: 10px auto;
        }

        .supplier_details_modal .description-block {
            margin-bottom: 15px;
            border-right: none !important;
        }
    }
</style>
@endsection