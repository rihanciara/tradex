@extends('advancedreports::layouts.app')
@section('title', __('Supplier Wise Sales Report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>Supplier Wise Sales Report
        <small>View supplier wise product sales with profit analysis</small>
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
                    {!! Form::label('supplier_id', __('Supplier') . ':') !!}
                    @php
                    $supplier_options = [];
                    if(is_array($suppliers)) {
                    $supplier_options = $suppliers;
                    } else {
                    $supplier_options = $suppliers->toArray();
                    }
                    @endphp
                    {!! Form::select('supplier_id', $supplier_options, null, ['class' => 'form-control select2', 'style'
                    =>
                    'width:100%', 'placeholder' => __('messages.please_select'), 'id' => 'supplier_filter']); !!}
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
                            <li><a href="#" data-range="today">@lang('Today')</a></li>
                            <li><a href="#" data-range="yesterday">@lang('Yesterday')</a></li>
                            <li class="divider"></li>
                            <li><a href="#" data-range="this_week">@lang('This Week')</a></li>
                            <li><a href="#" data-range="last_week">@lang('Last Week')</a></li>
                            <li class="divider"></li>
                            <li><a href="#" data-range="this_month">@lang('This Month')</a></li>
                            <li><a href="#" data-range="last_month">@lang('Last Month')</a></li>
                            <li class="divider"></li>
                            <li><a href="#" data-range="this_quarter">@lang('This Quarter')</a></li>
                            <li><a href="#" data-range="last_quarter">@lang('Last Quarter')</a></li>
                            <li class="divider"></li>
                            <li><a href="#" data-range="this_year">@lang('This Year')</a></li>
                            <li><a href="#" data-range="last_year">@lang('Last Year')</a></li>
                            <li class="divider"></li>
                            <li><a href="#" data-range="custom">@lang('Custom Range')</a></li>
                        </ul>
                    </div>
                    <input type="hidden" id="start_date" name="start_date" />
                    <input type="hidden" id="end_date" name="end_date" />
                    <input type="text" id="custom_date_range" class="form-control" style="display: none;" />
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
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

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => 'Supplier Wise Sales Report'])
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="supplier_wise_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('Supplier')</th>
                            <th>@lang('Total Products')</th>
                            <th>@lang('Total Transactions')</th>
                            <th>@lang('Quantity Sold')</th>
                            <th>@lang('Sales Amount')</th>
                            <th>@lang('Purchase Cost')</th>
                            <th>@lang('Gross Profit')</th>
                            <th>@lang('Profit Margin') (%)</th>
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

    <!-- Supplier Products Modal -->
    <div class="modal fade supplier_products_modal" tabindex="-1" role="dialog"
        aria-labelledby="supplierProductsModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="supplierProductsModalLabel">Supplier Product Sales</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="supplier_products_content">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin fa-3x text-muted"></i>
                        <p class="text-muted">Loading supplier product details...</p>
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
    $(document).ready(function() {
        // Date range dropdown functionality
        var start_date = moment().startOf('month');
        var end_date = moment().endOf('month');
        
        // Set default date range
        $('#start_date').val(start_date.format('YYYY-MM-DD'));
        $('#end_date').val(end_date.format('YYYY-MM-DD'));
        $('#date_filter_text').text('This Month');

        // Date range picker for custom range
        $('#custom_date_range').daterangepicker({
            startDate: start_date,
            endDate: end_date,
            locale: {
                format: moment_date_format.toUpperCase()
            }
        });

        // Date range dropdown click handlers
        $('.dropdown-menu a').click(function(e) {
            e.preventDefault();
            var range = $(this).data('range');
            var text = $(this).text();
            
            switch(range) {
                case 'today':
                    start_date = moment();
                    end_date = moment();
                    break;
                case 'yesterday':
                    start_date = moment().subtract(1, 'day');
                    end_date = moment().subtract(1, 'day');
                    break;
                case 'this_week':
                    start_date = moment().startOf('week');
                    end_date = moment().endOf('week');
                    break;
                case 'last_week':
                    start_date = moment().subtract(1, 'week').startOf('week');
                    end_date = moment().subtract(1, 'week').endOf('week');
                    break;
                case 'this_month':
                    start_date = moment().startOf('month');
                    end_date = moment().endOf('month');
                    break;
                case 'last_month':
                    start_date = moment().subtract(1, 'month').startOf('month');
                    end_date = moment().subtract(1, 'month').endOf('month');
                    break;
                case 'this_quarter':
                    start_date = moment().startOf('quarter');
                    end_date = moment().endOf('quarter');
                    break;
                case 'last_quarter':
                    start_date = moment().subtract(1, 'quarter').startOf('quarter');
                    end_date = moment().subtract(1, 'quarter').endOf('quarter');
                    break;
                case 'this_year':
                    start_date = moment().startOf('year');
                    end_date = moment().endOf('year');
                    break;
                case 'last_year':
                    start_date = moment().subtract(1, 'year').startOf('year');
                    end_date = moment().subtract(1, 'year').endOf('year');
                    break;
                case 'custom':
                    $('#custom_date_range').show().focus();
                    return;
            }
            
            $('#start_date').val(start_date.format('YYYY-MM-DD'));
            $('#end_date').val(end_date.format('YYYY-MM-DD'));
            $('#date_filter_text').text(text);
            $('#custom_date_range').hide();
            
            // Reload table
            supplier_wise_table.ajax.reload();
        });

        // Custom date range change handler
        $('#custom_date_range').on('apply.daterangepicker', function(ev, picker) {
            $('#start_date').val(picker.startDate.format('YYYY-MM-DD'));
            $('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
            $('#date_filter_text').text(picker.startDate.format(moment_date_format.toUpperCase()) + ' - ' + picker.endDate.format(moment_date_format.toUpperCase()));
            $(this).hide();
            supplier_wise_table.ajax.reload();
        });

        // DataTable initialization
        var supplier_wise_table = $('#supplier_wise_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.supplier-monthly.supplier-wise-sales') }}",
                data: function (d) {
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.location_id = $('#location_id').val();
                    d.supplier_id = $('#supplier_id').val();
                }
            },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'supplier_name', name: 'supplier_name' },
                { data: 'total_products', name: 'total_products', searchable: false },
                { data: 'total_transactions', name: 'total_transactions', searchable: false },
                { data: 'total_qty_sold', name: 'total_qty_sold', searchable: false },
                { data: 'total_sales_amount', name: 'total_sales_amount', searchable: false },
                { data: 'total_purchase_price', name: 'total_purchase_price', searchable: false },
                { data: 'gross_profit', name: 'gross_profit', searchable: false },
                { data: 'profit_margin', name: 'profit_margin', searchable: false }
            ],
            order: [[5, 'desc']],
            "fnDrawCallback": function (oSettings) {
                __currency_convert_recursively($('#supplier_wise_table'));

                var api = this.api();
                
                // Calculate totals for footer
                var total_products = 0;
                var total_transactions = 0;
                var total_qty = 0;
                var total_sales = 0;
                var total_purchase = 0;
                var total_profit = 0;

                api.column(2, {page: 'current'}).data().each(function(value) {
                    var products = parseInt(value.replace(/,/g, '')) || 0;
                    total_products += products;
                });

                api.column(3, {page: 'current'}).data().each(function(value) {
                    var transactions = parseInt(value.replace(/,/g, '')) || 0;
                    total_transactions += transactions;
                });

                api.column(4, {page: 'current'}).data().each(function(value) {
                    try {
                        var cleanValue = 0;
                        if (typeof value === 'string') {
                            // Remove HTML tags and extract text content
                            var tempDiv = document.createElement('div');
                            tempDiv.innerHTML = value;
                            var textValue = tempDiv.textContent || tempDiv.innerText || '';
                            cleanValue = parseFloat(textValue.replace(/[^\d.-]/g, '')) || 0;
                        } else if (typeof value === 'number') {
                            cleanValue = value;
                        }
                        total_qty += cleanValue;
                    } catch (e) {
                        console.warn('Error parsing qty value:', value, e);
                    }
                });

                api.column(5, {page: 'current'}).data().each(function(value) {
                    try {
                        var cleanValue = 0;
                        if (typeof value === 'string') {
                            var tempDiv = document.createElement('div');
                            tempDiv.innerHTML = value;
                            var textValue = tempDiv.textContent || tempDiv.innerText || '';
                            cleanValue = parseFloat(textValue.replace(/[^\d.-]/g, '')) || 0;
                        } else if (typeof value === 'number') {
                            cleanValue = value;
                        }
                        total_sales += cleanValue;
                    } catch (e) {
                        console.warn('Error parsing sales value:', value, e);
                    }
                });

                api.column(6, {page: 'current'}).data().each(function(value) {
                    try {
                        var cleanValue = 0;
                        if (typeof value === 'string') {
                            var tempDiv = document.createElement('div');
                            tempDiv.innerHTML = value;
                            var textValue = tempDiv.textContent || tempDiv.innerText || '';
                            cleanValue = parseFloat(textValue.replace(/[^\d.-]/g, '')) || 0;
                        } else if (typeof value === 'number') {
                            cleanValue = value;
                        }
                        total_purchase += cleanValue;
                    } catch (e) {
                        console.warn('Error parsing purchase value:', value, e);
                    }
                });

                api.column(7, {page: 'current'}).data().each(function(value) {
                    try {
                        var cleanValue = 0;
                        if (typeof value === 'string') {
                            var tempDiv = document.createElement('div');
                            tempDiv.innerHTML = value;
                            var textValue = tempDiv.textContent || tempDiv.innerText || '';
                            cleanValue = parseFloat(textValue.replace(/[^\d.-]/g, '')) || 0;
                        } else if (typeof value === 'number') {
                            cleanValue = value;
                        }
                        total_profit += cleanValue;
                    } catch (e) {
                        console.warn('Error parsing profit value:', value, e);
                    }
                });

                // Update footer
                $('.footer_qty').text(total_qty.toFixed(2));
                $('.footer_sales').text(total_sales.toFixed(2));
                $('.footer_purchase').text(total_purchase.toFixed(2));
                $('.footer_profit').text(total_profit.toFixed(2));

                var overall_profit_margin = total_sales > 0 ? (total_profit / total_sales * 100) : 0;
                $('.footer_margin').text(overall_profit_margin.toFixed(2) + '%');

                __currency_convert_recursively($('.footer_total'));
            }
        });

        // Filter button click
        $('#filter_btn').click(function() {
            supplier_wise_table.ajax.reload();
        });

        // Export functionality
        $('#export_btn').click(function(e) {
            e.preventDefault();
            
            var start_date = $('#start_date').val();
            var end_date = $('#end_date').val();
            var location_id = $('#location_id').val();
            var supplier_id = $('#supplier_id').val();
            
            var url = "{{ route('advancedreports.supplier-monthly.export') }}" + '?' + $.param({
                start_date: start_date,
                end_date: end_date,
                location_id: location_id,
                supplier_id: supplier_id,
                report_type: 'supplier_wise'
            });
            
            window.open(url, '_blank');
        });

        // Auto-filter on change
        $('#location_id, #supplier_id').change(function() {
            supplier_wise_table.ajax.reload();
        });

        // View supplier products modal
        $(document).on('click', '.view-supplier-products', function(e) {
            e.preventDefault();
            var supplierId = $(this).data('supplier-id');
            var startDate = $('#start_date').val();
            var endDate = $('#end_date').val();
            var locationId = $('#location_id').val();
            
            $('.supplier_products_modal').modal('show');
            $('#supplier_products_content').html(`
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-3x text-muted"></i>
                    <p class="text-muted">Loading supplier product details...</p>
                </div>
            `);
            
            $.ajax({
                url: "{{ route('advancedreports.supplier-products.index') }}",
                method: 'GET',
                data: { 
                    supplier_id: supplierId,
                    start_date: startDate,
                    end_date: endDate,
                    location_id: locationId
                },
                success: function(response) {
                    if (response.success && response.supplier) {
                        var supplier = response.supplier;
                        var products = response.products || [];
                        var summary = response.summary || {};
                        
                        var displayName = supplier.supplier_business_name 
                            ? supplier.supplier_business_name + ' (' + supplier.name + ')'
                            : supplier.name;
                        
                        var html = `
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="box box-info">
                                        <div class="box-header with-border">
                                            <h3 class="box-title"><i class="fa fa-building"></i> Supplier Information</h3>
                                        </div>
                                        <div class="box-body">
                                            <table class="table table-striped">
                                                <tr><td><strong>Name:</strong></td><td>${displayName}</td></tr>
                                                <tr><td><strong>Contact:</strong></td><td>${supplier.mobile || 'N/A'}</td></tr>
                                                <tr><td><strong>Email:</strong></td><td>${supplier.email || 'N/A'}</td></tr>
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
                                                <div class="col-md-6">
                                                    <div class="description-block">
                                                        <span class="description-percentage text-green"><i class="fa fa-cubes"></i></span>
                                                        <h5 class="description-header">${summary.total_products || 0}</h5>
                                                        <span class="description-text">PRODUCTS</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="description-block">
                                                        <span class="description-percentage text-yellow"><i class="fa fa-shopping-cart"></i></span>
                                                        <h5 class="description-header">${summary.total_transactions || 0}</h5>
                                                        <span class="description-text">TRANSACTIONS</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row text-center" style="margin-top: 15px;">
                                                <div class="col-md-6">
                                                    <div class="description-block">
                                                        <span class="description-percentage text-blue"><i class="fa fa-line-chart"></i></span>
                                                        <h5 class="description-header">${__currency_trans_from_en(summary.total_quantity || 0, false)}</h5>
                                                        <span class="description-text">TOTAL QTY</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="description-block">
                                                        <span class="description-percentage text-red"><i class="fa fa-money"></i></span>
                                                        <h5 class="description-header">${__currency_trans_from_en(summary.total_amount || 0, true)}</h5>
                                                        <span class="description-text">TOTAL SALES</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        if (products && products.length > 0) {
                            html += `
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="box box-primary">
                                            <div class="box-header with-border">
                                                <h3 class="box-title"><i class="fa fa-list"></i> Product Sales Details</h3>
                                            </div>
                                            <div class="box-body" style="max-height: 400px; overflow-y: auto;">
                                                <table class="table table-striped table-bordered table-condensed">
                                                    <thead>
                                                        <tr>
                                                            <th>Date</th>
                                                            <th>Invoice</th>
                                                            <th>Product</th>
                                                            <th>Qty</th>
                                                            <th>Unit Price</th>
                                                            <th>Total</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                            `;
                            
                            products.forEach(function(product) {
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
                                
                                html += `
                                    <tr>
                                        <td>${date}</td>
                                        <td>${product.invoice_no || 'N/A'}</td>
                                        <td>${product.product_name || 'N/A'}</td>
                                        <td class="text-center">${product.quantity || 0}</td>
                                        <td class="text-right">${__currency_trans_from_en(product.unit_price_inc_tax || 0, true)}</td>
                                        <td class="text-right"><strong>${__currency_trans_from_en(product.line_total || 0, true)}</strong></td>
                                        <td><span class="label ${statusClass}">${statusText.toUpperCase()}</span></td>
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
                            `;
                        } else {
                            html += `
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> No product sales found for this supplier in the selected period.
                                </div>
                            `;
                        }
                        
                        $('#supplier_products_content').html(html);
                        $('#supplierProductsModalLabel').text('Supplier Products - ' + displayName);
                        
                    } else {
                        $('#supplier_products_content').html(`
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i> No supplier data found.
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    var errorMessage = 'Error loading supplier products.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    $('#supplier_products_content').html(`
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-triangle"></i> ${errorMessage}
                        </div>
                    `);
                }
            });
        });
    });
</script>

<style>
    .footer-total {
        background-color: #f5f5f5 !important;
        font-weight: bold !important;
    }

    .footer-total td {
        border-top: 2px solid #ddd !important;
        padding: 8px !important;
    }

    .supplier_products_modal .modal-dialog {
        max-width: 90%;
        width: 1000px;
    }

    .supplier_products_modal .description-block {
        padding: 10px;
        margin: 5px 0;
    }

    .supplier_products_modal .description-header {
        font-size: 18px;
        font-weight: bold;
        margin: 5px 0;
    }

    .supplier_products_modal .description-text {
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 600;
    }

    .supplier_products_modal .description-percentage {
        font-size: 16px;
        display: block;
        margin-bottom: 5px;
    }

    .supplier_products_modal .text-green {
        color: #00a65a !important;
    }

    .supplier_products_modal .text-yellow {
        color: #f39c12 !important;
    }

    .supplier_products_modal .text-blue {
        color: #3c8dbc !important;
    }

    .supplier_products_modal .text-red {
        color: #dd4b39 !important;
    }
</style>
@endsection