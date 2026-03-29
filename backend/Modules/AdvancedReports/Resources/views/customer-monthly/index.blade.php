@extends('advancedreports::layouts.app')
@section('title', __('Customer Monthly Sales Report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>Customer Monthly Sales Report
        <small>View customer sales by months with profit analysis</small>
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
                    {!! Form::label('customer_name', __('contact.customer') . ' Name:') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-user"></i>
                        </span>
                        {!! Form::select('customer_name', [], null, [
                        'class' => 'form-control',
                        'style' => 'width:100%',
                        'id' => 'customer_name_filter',
                        'placeholder' => 'Search customer...'
                        ]); !!}
                    </div>
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
                    {!! Form::select('year', array_combine(range(date('Y')-20, date('Y')+10), range(date('Y')-20,
                    date('Y')+10)), date('Y'), ['class' => 'form-control select2', 'style' => 'width:100%', 'id' =>
                    'year_filter']); !!}
                </div>
            </div>
            <div class="col-md-12" style="margin-top: 10px;">
                <div class="form-group">
                    <button type="button" class="btn btn-primary" id="filter_btn">
                        <i class="fa fa-filter"></i> @lang('advancedreports::lang.filter')
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
        <!-- Total Customers -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-customers">
                <i class="fa fa-users modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">Total Customers</div>
                    <div class="modern-widget-number" id="total_customers">1,247</div>
                </div>
            </div>
        </div>

        <!-- Total Transactions -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-transactions">
                <i class="fa fa-shopping-cart modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">Total Transactions</div>
                    <div class="modern-widget-number" id="total_transactions">3,456</div>
                </div>
            </div>
        </div>

        <!-- Total Sales -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-sales">
                <i class="fa fa-money modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">Total Sales</div>
                    <div class="modern-widget-number" id="total_sales">$87,432</div>
                </div>
            </div>
        </div>

        <!-- Total Profit -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-profit">
                <i class="fa fa-line-chart modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">Total Profit</div>
                    <div class="modern-widget-number" id="total_profit">$23,891</div>
                </div>
            </div>
        </div>

        <!-- Profit Margin -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-margin">
                <i class="fa fa-percent modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">Profit Margin</div>
                    <div class="modern-widget-number" id="profit_margin">27.3%</div>
                </div>
            </div>
        </div>

        <!-- Average per Customer -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-average">
                <i class="fa fa-calculator modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">Avg per Customer</div>
                    <div class="modern-widget-number" id="avg_per_customer">$70.12</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' =>
            'Customer Monthly Sales Report'])
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view colored-header" id="customer_monthly_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>Customer</th>
                            <th>Jan</th>
                            <th>Feb</th>
                            <th>Mar</th>
                            <th>Apr</th>
                            <th>May</th>
                            <th>Jun</th>
                            <th>Jul</th>
                            <th>Aug</th>
                            <th>Sep</th>
                            <th>Oct</th>
                            <th>Nov</th>
                            <th>Dec</th>
                            <th>Total Sales</th>
                            <th>Gross Profit ($)</th>
                            <th>Gross Profit (%)</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 text-center footer-total">
                            <td colspan="2"><strong>Total:</strong></td>
                            <td class="footer_jan">0</td>
                            <td class="footer_feb">0</td>
                            <td class="footer_mar">0</td>
                            <td class="footer_apr">0</td>
                            <td class="footer_may">0</td>
                            <td class="footer_jun">0</td>
                            <td class="footer_jul">0</td>
                            <td class="footer_aug">0</td>
                            <td class="footer_sep">0</td>
                            <td class="footer_oct">0</td>
                            <td class="footer_nov">0</td>
                            <td class="footer_dec">0</td>
                            <td class="footer_total_sales">0</td>
                            <td class="footer_gross_profit">0</td>
                            <td class="footer_profit_percent">0%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Customer Details Modal -->
    <div class="modal fade customer_details_modal" tabindex="-1" role="dialog"
        aria-labelledby="customerDetailsModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="customerDetailsModalLabel">Customer Details</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="customer_details_content">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin fa-3x text-muted"></i>
                        <p class="text-muted">Loading customer details...</p>
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
    // Handle null, undefined, empty string
    if (num === null || num === undefined || num === '') {
        return __currency_trans_from_en('0.00', true);
    }
    
    // If it's already a number, use it directly
    if (typeof num === 'number') {
        return __currency_trans_from_en(num.toFixed(2), true);
    }
    
    // If it's a string, try to parse it
    if (typeof num === 'string') {
        // Remove any currency symbols, commas, and non-numeric characters except decimal point and minus
        var cleanStr = num.replace(/[^\d.-]/g, '');
        var parsed = parseFloat(cleanStr);
        
        // Check if parsing was successful
        if (!isNaN(parsed)) {
            return __currency_trans_from_en(parsed.toFixed(2), true);
        }
    }
    
    // Fallback for any other type or if parsing failed
    console.warn('formatCurrency received invalid input:', num, typeof num);
    return __currency_trans_from_en('0.00', true);
}

function formatNumber(num) {
    // Handle null, undefined, empty string
    if (num === null || num === undefined || num === '') {
        return '0';
    }
    
    // If it's already a number, use it directly
    if (typeof num === 'number') {
        return num.toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }
    
    // If it's a string, try to parse it
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
    
    // Fallback
    console.warn('formatNumber received invalid input:', num, typeof num);
    return '0';
}

    $(document).ready(function() {
        // Initialize DataTable
        jQuery.extend(jQuery.fn.dataTableExt.oSort, {
    "currency-asc": function (a, b) {
        // Handle both HTML elements and direct text
        var aText = typeof a === 'string' ? a : $(a).text();
        var bText = typeof b === 'string' ? b : $(b).text();
        
        // Remove all non-numeric characters except decimal point and minus
        var x = parseFloat(aText.replace(/[^\d.-]/g, '')) || 0;
        var y = parseFloat(bText.replace(/[^\d.-]/g, '')) || 0;
        
        return x - y;
    },
    "currency-desc": function (a, b) {
        // Handle both HTML elements and direct text
        var aText = typeof a === 'string' ? a : $(a).text();
        var bText = typeof b === 'string' ? b : $(b).text();
        
        // Remove all non-numeric characters except decimal point and minus
        var x = parseFloat(aText.replace(/[^\d.-]/g, '')) || 0;
        var y = parseFloat(bText.replace(/[^\d.-]/g, '')) || 0;
        
        return y - x;
    }
    });

    // Auto-detect currency columns
    $.fn.dataTable.ext.type.detect.unshift(function (data) {
    // Check if data looks like currency (has currency symbols or is a formatted number)
    if (typeof data === 'string' && (
        data.match(/^[\$£€¥₹]/) || // Starts with currency symbol
        data.match(/[\$£€¥₹]$/) || // Ends with currency symbol  
        data.match(/^\d{1,3}(,\d{3})*(\.\d+)?$/) || // Formatted number like 1,234.56
        data.match(/\d+\.\d+/) || // Contains decimal
        data.match(/%$/) // Percentage
    )) {
        return 'currency';
    }
    return null;
    });

    // Your DataTable initialization
    var customer_monthly_table = $('#customer_monthly_table').DataTable({
    processing: true,
    serverSide: true,
ajax: {
    url: "{{ route('advancedreports.customer-monthly.data') }}",
    data: function (d) {
        d.customer_name = $('#customer_name_filter').val(); // Keep this as is
        d.location_id = $('#location_filter').val();
        d.payment_status = $('#payment_status_filter').val();
        d.payment_method = $('#payment_method_filter').val();
        d.user_id = $('#user_filter').val();
        d.year = $('#year_filter').val();
    }
},
    columns: [
        { data: 'action', name: 'action', orderable: false, searchable: false, width: '80px' },
        { data: 'customer_name', name: 'customer_name', width: '250px' },
        { data: 'jan', name: 'jan', searchable: false, width: '100px' },
        { data: 'feb', name: 'feb', searchable: false, width: '100px' },
        { data: 'mar', name: 'mar', searchable: false, width: '100px' },
        { data: 'apr', name: 'apr', searchable: false, width: '100px' },
        { data: 'may', name: 'may', searchable: false, width: '100px' },
        { data: 'jun', name: 'jun', searchable: false, width: '100px' },
        { data: 'jul', name: 'jul', searchable: false, width: '100px' },
        { data: 'aug', name: 'aug', searchable: false, width: '100px' },
        { data: 'sep', name: 'sep', searchable: false, width: '100px' },
        { data: 'oct', name: 'oct', searchable: false, width: '100px' },
        { data: 'nov', name: 'nov', searchable: false, width: '100px' },
        { data: 'dec', name: 'dec', searchable: false, width: '100px' },
        { data: 'total_sales', name: 'total_sales', searchable: false, width: '120px' },
        { data: 'gross_profit', name: 'gross_profit', searchable: false, width: '120px' },
        { data: 'gross_profit_percent', name: 'gross_profit_percent', searchable: false, width: '120px' }
    ],
    columnDefs: [
        {
            targets: [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16],
            type: 'currency'
        }
    ],
    order: [[14, 'desc']],
    scrollX: true,
    autoWidth: false, // Important: disable auto width
    footerCallback: function (row, data, start, end, display) {
        var api = this.api();

        // Calculate footer totals
        var months = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
        var monthTotals = {};

        // Initialize month totals
        months.forEach(function(month) {
            monthTotals[month] = 0;
        });

        // Calculate monthly totals from visible (filtered) rows
        months.forEach(function(month, index) {
            var columnIndex = index + 2; // Months start from column 2 (0-indexed)

            // Sum values from filtered rows
            api.column(columnIndex, {page: 'current'}).data().each(function(value) {
                try {
                    // Handle different value types better
                    var numericValue = 0;
                    if (typeof value === 'string') {
                        // Extract text content if it's HTML
                        var tempDiv = document.createElement('div');
                        tempDiv.innerHTML = value;
                        var textValue = tempDiv.textContent || tempDiv.innerText || '';
                        // Remove currency symbols and commas, keep only numbers and decimal point
                        var cleanValue = textValue.replace(/[$,\s]/g, '').replace(/[^\d.]/g, '');
                        numericValue = parseFloat(cleanValue) || 0;
                    } else if (typeof value === 'number') {
                        numericValue = value;
                    }

                    monthTotals[month] += Math.abs(numericValue);
                } catch (e) {
                    console.warn('Error parsing value:', value);
                }
            });
        });

        // Calculate total sales and profit
        var total_sales_sum = 0;
        var total_profit = 0;

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
                console.warn('Error parsing total sales:', value);
            }
        });

        api.column(15, {page: 'current'}).data().each(function(value) {
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
                console.warn('Error parsing profit:', value);
            }
        });

        // Update footer cells with positive values
        $('.footer_jan').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.jan || 0).toFixed(2) + '</span>');
        $('.footer_feb').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.feb || 0).toFixed(2) + '</span>');
        $('.footer_mar').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.mar || 0).toFixed(2) + '</span>');
        $('.footer_apr').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.apr || 0).toFixed(2) + '</span>');
        $('.footer_may').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.may || 0).toFixed(2) + '</span>');
        $('.footer_jun').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.jun || 0).toFixed(2) + '</span>');
        $('.footer_jul').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.jul || 0).toFixed(2) + '</span>');
        $('.footer_aug').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.aug || 0).toFixed(2) + '</span>');
        $('.footer_sep').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.sep || 0).toFixed(2) + '</span>');
        $('.footer_oct').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.oct || 0).toFixed(2) + '</span>');
        $('.footer_nov').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.nov || 0).toFixed(2) + '</span>');
        $('.footer_dec').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.dec || 0).toFixed(2) + '</span>');
        $('.footer_total_sales').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(total_sales_sum || 0).toFixed(2) + '</span>');
        $('.footer_gross_profit').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(total_profit || 0).toFixed(2) + '</span>');

        var overall_profit_percent = total_sales_sum > 0 ? (total_profit / total_sales_sum * 100) : 0;
        $('.footer_profit_percent').text(Math.abs(overall_profit_percent || 0).toFixed(2) + '%');

        // Apply currency formatting
        __currency_convert_recursively($('.footer-total'));
    },
    drawCallback: function (settings) {
        __currency_convert_recursively($('#customer_monthly_table'));
    },
    createdRow: function( row, data, dataIndex ) {
        // Right align monetary columns
        for(var i = 2; i <= 16; i++) {
            $(row).find('td:eq(' + i + ')').addClass('text-right');
        }
    }
    });


        // Filter button click
        $('#filter_btn').click(function() {
            customer_monthly_table.ajax.reload();
            loadSummary();
        });

        // Refresh button click
        $('#refresh_btn').click(function() {
            customer_monthly_table.ajax.reload();
            loadSummary();
        });


        // Load summary data
      function loadSummary() {
    var customer_name = $('#customer_name_filter').val();
    var location_id = $('#location_filter').val();
    var payment_status = $('#payment_status_filter').val();
    var payment_method = $('#payment_method_filter').val();
    var user_id = $('#user_filter').val();
    var year = $('#year_filter').val();
    
    $.ajax({
        url: "{{ route('advancedreports.customer-monthly.summary') }}",
        data: {
            customer_name: customer_name,
            location_id: location_id,
            payment_status: payment_status,
            payment_method: payment_method,
            user_id: user_id,
            year: year
        },
        dataType: 'json',
        success: function(data) {
            // Use safe parsing for all numeric values
            var totalCustomers = parseInt(data.total_customers) || 0;
            var totalTransactions = parseInt(data.total_transactions) || 0;
            var totalSales = parseFloat(data.total_sales) || 0;
            var totalProfit = parseFloat(data.total_profit) || 0;
            var profitMargin = parseFloat(data.profit_margin) || 0;
            var averagePerCustomer = parseFloat(data.average_per_customer) || 0;
            
            $('#total_customers').text(formatNumber(totalCustomers));
            $('#total_transactions').text(formatNumber(totalTransactions));
            $('#total_sales').text(formatCurrency(totalSales));
            $('#total_profit').text(formatCurrency(totalProfit));
            $('#profit_margin').text(profitMargin.toFixed(2) + '%');
            $('#avg_per_customer').text(formatCurrency(averagePerCustomer));
            
            $('#summary_cards').show();
        },
        error: function(xhr, status, error) {
            console.log('Error loading summary data:', error);
            console.log('Response:', xhr.responseText);
            
            // Set default values on error
            $('#total_customers').text('0');
            $('#total_transactions').text('0');
            $('#total_sales').text(formatCurrency(0));
            $('#total_profit').text(formatCurrency(0));
            $('#profit_margin').text('0.00%');
            $('#avg_per_customer').text(formatCurrency(0));
        }
    });
}

        // Auto-filter on change for filters
$('#customer_name_filter, #location_filter, #payment_status_filter, #payment_method_filter, #user_filter, #year_filter').change(function() {
    customer_monthly_table.ajax.reload();
    loadSummary();
});

        // Customer details modal
       $(document).on('click', '.view-customer-details', function(e) {
    e.preventDefault();
    var customerId = $(this).data('customer-id');
    var year = $('#year_filter').val() || new Date().getFullYear();
    
    console.log('Loading customer details for ID:', customerId, 'Year:', year);
    
    $('.customer_details_modal').modal('show');
    $('#customer_details_content').html(`
        <div class="text-center">
            <i class="fa fa-spinner fa-spin fa-3x text-muted"></i>
            <p class="text-muted">Loading customer details...</p>
        </div>
    `);
    
    $.ajax({
        url: "{{ route('advancedreports.customer-monthly.details', '') }}/" + customerId,
        method: 'GET',
        data: { 
            year: year,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        timeout: 30000,
        success: function(response) {
            console.log('Customer details response:', response);
            
            if (response && response.customer) {
                var customer = response.customer;
                var transactions = response.transactions || [];
                
                var html = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="box box-info">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-user"></i> Customer Information</h3>
                                </div>
                                <div class="box-body">
                                    <table class="table table-striped">
                                        <tr><td><strong>Name:</strong></td><td>${customer.name || 'N/A'}</td></tr>
                                        <tr><td><strong>Business:</strong></td><td>${customer.supplier_business_name || 'N/A'}</td></tr>
                                        <tr><td><strong>Contact ID:</strong></td><td>${customer.contact_id || customer.id || 'N/A'}</td></tr>
                                        <tr><td><strong>Mobile:</strong></td><td>${customer.mobile || 'N/A'}</td></tr>
                                        <tr><td><strong>Email:</strong></td><td>${customer.email || 'N/A'}</td></tr>
                                        <tr><td><strong>Address:</strong></td><td>${customer.address_line_1 || 'N/A'}</td></tr>
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
                                                <h5 class="description-header">${transactions.length || 0}</h5>
                                                <span class="description-text">TOTAL TRANSACTIONS</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="description-block">
                                                <span class="description-percentage text-yellow"><i class="fa fa-money"></i></span>
                                                <h5 class="description-header">${formatCurrency(calculateTotalAmount(transactions))}</h5>
                                                <span class="description-text">TOTAL SALES</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row text-center" style="margin-top: 15px;">
                                        <div class="col-md-6">
                                            <div class="description-block border-right">
                                                <span class="description-percentage text-blue"><i class="fa fa-calculator"></i></span>
                                                <h5 class="description-header">${formatCurrency(calculateAverageTransaction(transactions))}</h5>
                                                <span class="description-text">AVG PER TRANSACTION</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="description-block">
                                                <span class="description-percentage text-red"><i class="fa fa-cubes"></i></span>
                                                <h5 class="description-header">${formatNumber(calculateTotalQuantity(transactions))}</h5>
                                                <span class="description-text">TOTAL QTY</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Add transactions table if there are transactions
                if (transactions && transactions.length > 0) {
                    html += `
                        <div class="row">
                            <div class="col-md-12">
                                <div class="box box-primary">
                                    <div class="box-header with-border">
                                        <h3 class="box-title"><i class="fa fa-list"></i> Recent Transactions</h3>
                                        <div class="box-tools pull-right">
                                            <span class="label label-primary">${transactions.length} transactions</span>
                                        </div>
                                    </div>
                                    <div class="box-body" style="max-height: 400px; overflow-y: auto;">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover table-condensed" id="customer-transactions-table">
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
                    
                    transactions.forEach(function(transaction, index) {
                        var date = new Date(transaction.transaction_date).toLocaleDateString();
                        var statusClass = '';
                        var statusText = transaction.payment_status || 'unknown';
                        
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
                        
                        // Add alternating row colors for better readability
                        var rowClass = index % 2 === 0 ? 'even-row' : 'odd-row';
                        
                        html += `
                            <tr class="${rowClass}" style="font-size: 12px;">
                                <td>${date}</td>
                                <td><strong style="font-size: 11px;">${transaction.invoice_no || 'N/A'}</strong></td>
                                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                                    title="${transaction.product_name || 'N/A'}">${transaction.product_name || 'N/A'}</td>
                                <td><span class="label label-info" style="font-size: 10px;">${transaction.month_name || 'N/A'}</span></td>
                                <td class="text-center">${formatNumber(transaction.quantity || 0)}</td>
                                <td class="text-right">${formatCurrency(transaction.unit_price_inc_tax || 0)}</td>
                                <td class="text-right"><strong>${formatCurrency(transaction.line_total || 0)}</strong></td>
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
                                                    Showing recent ${transactions.length} transactions for ${year}
                                                </small>
                                            </div>
                                            <div class="col-sm-6 text-right">
                                                <small class="text-muted">
                                                    Total: <strong>${formatCurrency(calculateTotalAmount(transactions))}</strong>
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
                                    <i class="fa fa-info-circle"></i> No transactions found for this customer in ${year}.
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                $('#customer_details_content').html(html);
                $('#customerDetailsModalLabel').text('Customer Details - ' + (customer.name || 'Unknown') + ' (' + year + ')');
                
            } else {
                $('#customer_details_content').html(`
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i> No customer data found.
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
            console.error('Status:', status);
            console.error('Error:', error);
            
            var errorMessage = 'Error loading customer details.';
            
            if (xhr.responseJSON && xhr.responseJSON.error) {
                errorMessage = xhr.responseJSON.error;
            } else if (xhr.status === 404) {
                errorMessage = 'Customer not found.';
            } else if (xhr.status === 403) {
                errorMessage = 'Access denied.';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error occurred.';
            }
            
            $('#customer_details_content').html(`
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-triangle"></i> ${errorMessage}
                    <br><small>Please try again or contact administrator.</small>
                </div>
            `);
        }
    });
});

// Helper functions for calculations
function calculateTotalAmount(transactions) {
    var total = 0;
    transactions.forEach(function(t) {
        total += parseFloat(t.line_total || 0);
    });
    return total;
}

function calculateAverageTransaction(transactions) {
    if (transactions.length === 0) return 0;
    return calculateTotalAmount(transactions) / transactions.length;
}

function calculateTotalQuantity(transactions) {
    var total = 0;
    transactions.forEach(function(t) {
        total += parseFloat(t.quantity || 0);
    });
    return total;
}
        // Load initial summary
        loadSummary();
    });


</script>

<script>
    $(document).ready(function() {
    // Destroy any existing Select2
    if ($('#customer_name_filter').hasClass('select2-hidden-accessible')) {
        $('#customer_name_filter').select2('destroy');
    }
    
    // Initialize customer search dropdown
    $('#customer_name_filter').select2({
        ajax: {
            url: '/contacts/customers',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term,
                    page: params.page,
                };
            },
            processResults: function(data) {
                return {
                    results: data,
                };
            },
        },
        templateResult: function (data) { 
            if (data.loading) {
                return data.text;
            }
            
            var template = '';
            if (data.supplier_business_name) {
                template += data.supplier_business_name + "<br>";
            }
            template += data.text + "<br>Mobile: " + (data.mobile || 'N/A');

            return template;
        },
        templateSelection: function (data) {
            return data.text || data.name || 'Search customer...';
        },
        minimumInputLength: 1,
        allowClear: true,
        placeholder: 'Search customer by name/phone...',
        width: '100%',
        language: {
            inputTooShort: function (args) {
                return 'Please enter ' + args.minimum + ' or more characters';
            },
            noResults: function() {
                return 'No customers found';
            },
            searching: function() {
                return 'Searching...';
            }
        },
        escapeMarkup: function(markup) {
            return markup;
        },
    });

    // Handle customer selection
    $('#customer_name_filter').on('select2:select', function(e) {
        var data = e.params.data;
        // Trigger table reload when customer is selected
        customer_monthly_table.ajax.reload();
        loadSummary();
    });

    // Handle customer clear
    $('#customer_name_filter').on('select2:clear', function(e) {
        // Trigger table reload when customer selection is cleared
        customer_monthly_table.ajax.reload();
        loadSummary();
    });
});
</script>

<style>
    /* Keep your existing DataTable styling exactly as is */
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

    /* Style for tables with colored headers */
    table.colored-header thead th {
        background-color: #3498db;
        color: white;
        font-weight: bold;
        border-bottom: none;
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
    }

    /* Modal styling */
    .customer_details_modal .modal-dialog {
        max-width: 95%;
        width: 1200px;
    }

    .customer_details_modal .table {
        margin-bottom: 0;
    }

    .customer_details_modal .table td {
        padding: 8px;
        border: 1px solid #ddd;
    }

    .customer_details_modal .box {
        margin-bottom: 20px;
    }

    .customer_details_modal .description-block {
        padding: 10px;
    }

    .customer_details_modal .description-header {
        font-size: 20px;
        font-weight: bold;
        margin: 5px 0;
    }

    .customer_details_modal .description-text {
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 600;
    }

    .customer_details_modal .description-percentage {
        font-size: 20px;
        display: block;
        margin-bottom: 5px;
    }

    .customer_details_modal .border-right {
        border-right: 1px solid #ddd;
    }

    .customer_details_modal .text-green {
        color: #00a65a !important;
    }

    .customer_details_modal .text-yellow {
        color: #f39c12 !important;
    }

    .customer_details_modal .text-blue {
        color: #3c8dbc !important;
    }

    .customer_details_modal .text-red {
        color: #dd4b39 !important;
    }

    .customer_details_modal .table-hover tbody tr:hover {
        background-color: #f5f5f5;
    }

    /* Improved transaction table styling */
    #customer-transactions-table {
        font-size: 12px;
    }

    #customer-transactions-table th {
        font-size: 12px;
        font-weight: bold;
        text-align: center;
        padding: 8px 4px;
        border-bottom: 2px solid #ddd;
    }

    #customer-transactions-table td {
        padding: 6px 4px;
        font-size: 11px;
        vertical-align: middle;
    }

    #customer-transactions-table .even-row {
        background-color: #fafafa;
    }

    #customer-transactions-table .odd-row {
        background-color: #ffffff;
    }

    #customer-transactions-table tbody tr:hover {
        background-color: #e8f4f8 !important;
    }

    /* Scrollbar styling for transaction list */
    .customer_details_modal .box-body::-webkit-scrollbar {
        width: 8px;
    }

    .customer_details_modal .box-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .customer_details_modal .box-body::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .customer_details_modal .box-body::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* Responsive modal */
    @media (max-width: 768px) {
        .customer_details_modal .modal-dialog {
            width: 95%;
            margin: 10px auto;
        }

        .customer_details_modal .description-block {
            margin-bottom: 15px;
            border-right: none !important;
        }
    }

    /* Style for tables with colored headers */
    table.colored-header thead th {
        background-color: #3498db;
        color: white;
        font-weight: bold;
        border-bottom: none;
        position: sticky;
        top: 0;
    }

    /* Ensure sticky columns maintain header style */
    table.colored-header th:first-child,
    table.colored-header th:nth-child(2) {
        z-index: 3;
        /* Higher than regular headers */
    }

    @media print {
        #customer_monthly_table thead th {
            background-color: #3498db !important;
            color: white !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }

    /* IMPROVED Modern Widget Styling - Enhanced version */
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

    /* Icon Enhanced Positioning */
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

    /* Content Area Enhanced */
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

    /* Text Styling Enhanced */
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

    /* Number Styling Enhanced */
    .modern-widget-number {
        font-size: 26px !important;
        font-weight: 700 !important;
        line-height: 1 !important;
        color: white !important;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2) !important;
        margin: 0 !important;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    }

    /* Enhanced Widget Color Schemes with better gradients */
    .widget-customers {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
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

    .widget-margin {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%) !important;
    }

    .widget-average {
        background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%) !important;
        color: #333 !important;
    }

    .widget-average * {
        color: #333 !important;
        text-shadow: none !important;
    }

    /* Force all text to be white except average */
    .modern-widget:not(.widget-average) * {
        color: white !important;
    }

    /* Enhanced Mobile Responsive */
    @media (max-width: 768px) {
        .modern-widget {
            height: 85px !important;
            padding: 14px !important;
            border-radius: 10px !important;
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

        .modern-widget-content {
            min-height: 55px !important;
        }
    }

    @media (max-width: 992px) {
        .modern-widget {
            height: 90px !important;
            padding: 16px !important;
        }

        .modern-widget-icon {
            font-size: 40px !important;
            width: 50px !important;
        }

        .modern-widget-text {
            font-size: 12px !important;
        }

        .modern-widget-number {
            font-size: 24px !important;
        }
    }

    /* Ensure Bootstrap columns are equal height */
    .row {
        display: flex;
        flex-wrap: wrap;
    }

    .col-xl-2,
    .col-lg-4,
    .col-md-6,
    .col-sm-6,
    .col-xs-12 {
        display: flex;
        flex-direction: column;
    }
</style>
@endsection