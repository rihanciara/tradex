@extends('advancedreports::layouts.app')
@section('title', __('Purchase Analysis Report'))

@section('content')
<!-- Add CSRF token meta tag -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>{{ __('Purchase Analysis Report') }}
        <small>{{ __('Analyze purchase trends, costs, returns & supplier performance') }}</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> {{ __('Level') }}</a></li>
        <li class="active">{{ __('Purchase Analysis Report') }}</li>
    </ol>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', [
            'title' => __('Filters'),
            'class' => 'box-primary'
            ])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('location_filter', __('business.business_location') . ':') !!}
                    {!! Form::select('location_filter', $business_locations, null, [
                    'class' => 'form-control select2',
                    'style' => 'width:100%',
                    'placeholder' => __('messages.all'),
                    ]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('supplier_filter', __('Supplier') . ':') !!}
                    {!! Form::select('supplier_filter', $suppliers, null, [
                    'class' => 'form-control select2',
                    'style' => 'width:100%',
                    'placeholder' => __('messages.all'),
                    ]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('start_date', __('business.start_date') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                        {!! Form::text('start_date', null, ['class' => 'form-control', 'id' => 'start_date',
                        'readonly']) !!}
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('end_date', __('advancedreports::lang.end_date') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                        {!! Form::text('end_date', null, ['class' => 'form-control', 'id' => 'end_date', 'readonly'])
                        !!}
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <button type="button" class="btn btn-primary" id="filter_btn">{{ __('report.apply_filters') }}</button>
                <button type="button" class="btn btn-success" id="export_btn">
                    <i class="fa fa-download"></i> {{ __('lang_v1.export') }}
                </button>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row" id="summary_cards" style="display: none;">
        <!-- Purchase Volume Card -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-blue">
                    <i class="fa fa-shopping-cart"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Purchases</span>
                    <span class="info-box-number" id="total_purchases">0</span>
                    <div class="progress">
                        <div class="progress-bar" style="width: 100%" id="purchases_progress"></div>
                    </div>
                    <span class="progress-description">
                        <span id="total_purchase_amount"><span class="display_currency" data-currency_symbol="true">0.00</span></span> total value
                    </span>
                </div>
            </div>
        </div>

        <!-- Cost Optimization Card -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-green">
                    <i class="fa fa-chart-line"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">Potential Savings</span>
                    <span class="info-box-number" id="potential_savings"><span class="display_currency" data-currency_symbol="true">0.00</span></span>
                    <div class="progress">
                        <div class="progress-bar bg-green" style="width: 75%" id="savings_progress"></div>
                    </div>
                    <span class="progress-description">
                        <span id="high_variance_products">0</span> high-variance products
                    </span>
                </div>
            </div>
        </div>

        <!-- Returns Analysis Card -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-yellow">
                    <i class="fa fa-undo"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">Purchase Returns</span>
                    <span class="info-box-number" id="total_returns">0</span>
                    <div class="progress">
                        <div class="progress-bar bg-yellow" style="width: 50%" id="returns_progress"></div>
                    </div>
                    <span class="progress-description">
                        <span id="return_rate">0%</span> return rate
                    </span>
                </div>
            </div>
        </div>

        <!-- Payment Terms Card -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-red">
                    <i class="fa fa-clock"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">Payment Compliance</span>
                    <span class="info-box-number" id="compliance_rate">0%</span>
                    <div class="progress">
                        <div class="progress-bar bg-red" style="width: 60%" id="compliance_progress"></div>
                    </div>
                    <span class="progress-description">
                        <span id="outstanding_amount"><span class="display_currency" data-currency_symbol="true">0.00</span></span> outstanding
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Suppliers Summary -->
    <div class="row" id="top_suppliers_section" style="display: none;">
        <!-- Top Suppliers Cards -->
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Top 5 Suppliers by Purchase Value</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div id="top_suppliers_cards" style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between;">
                                <!-- Top suppliers will be populated here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Suppliers Chart -->
        <div class="col-md-4">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-pie-chart"></i> Purchase Distribution
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" id="chart_type_toggle" title="Toggle Chart Type">
                            <i class="fa fa-exchange"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="suppliers_chart" width="400" height="300"></canvas>
                    </div>
                    <div id="chart_legend" class="text-center" style="margin-top: 10px;">
                        <!-- Chart legend will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analysis Tabs -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Purchase Analysis Details</h3>
                </div>
                <div class="box-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#supplier_trends" aria-controls="supplier_trends" role="tab" data-toggle="tab">
                                <i class="fa fa-trending-up"></i> <span class="hidden-xs">Supplier Trends</span>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#cost_optimization" aria-controls="cost_optimization" role="tab" data-toggle="tab">
                                <i class="fa fa-chart-line"></i> <span class="hidden-xs">Cost Optimization</span>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#return_analysis" aria-controls="return_analysis" role="tab" data-toggle="tab">
                                <i class="fa fa-undo"></i> <span class="hidden-xs">Return Analysis</span>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#payment_terms" aria-controls="payment_terms" role="tab" data-toggle="tab">
                                <i class="fa fa-clock"></i> <span class="hidden-xs">Payment Terms</span>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" style="margin-top: 20px;">
                        <!-- Supplier Trends Tab -->
                        <div role="tabpanel" class="tab-pane active" id="supplier_trends">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="supplier_trends_table">
                                    <thead>
                                        <tr>
                                            <th>Supplier</th>
                                            <th>Location</th>
                                            <th>Total Purchases</th>
                                            <th>Total Amount</th>
                                            <th>Average Amount</th>
                                            <th>Purchase Frequency</th>
                                            <th>First Purchase</th>
                                            <th>Last Purchase</th>
                                            <th>Activity Level</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr class="bg-gray font-17 text-center footer-total">
                                            <td colspan="2"><strong>Total:</strong></td>
                                            <td class="footer_supplier_purchases">0</td>
                                            <td class="footer_supplier_amount"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td class="footer_supplier_average"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td colspan="4"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Cost Optimization Tab -->
                        <div role="tabpanel" class="tab-pane" id="cost_optimization">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="cost_optimization_table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Supplier</th>
                                            <th>Purchase Count</th>
                                            <th>Total Quantity</th>
                                            <th>Avg Price</th>
                                            <th>Min Price</th>
                                            <th>Max Price</th>
                                            <th>Price Variance</th>
                                            <th>Total Cost</th>
                                            <th>Potential Savings</th>
                                            <th>Priority</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr class="bg-gray font-17 text-center footer-total">
                                            <td colspan="8"><strong>Total:</strong></td>
                                            <td class="footer_optimization_cost"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td class="footer_optimization_savings"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Return Analysis Tab -->
                        <div role="tabpanel" class="tab-pane" id="return_analysis">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="return_analysis_table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Return Ref</th>
                                            <th>Original Purchase</th>
                                            <th>Supplier</th>
                                            <th>Location</th>
                                            <th>Return Amount</th>
                                            <th>Original Amount</th>
                                            <th>Return %</th>
                                            <th>Timeline</th>
                                            <th>Reason</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr class="bg-gray font-17 text-center footer-total">
                                            <td colspan="5"><strong>Total:</strong></td>
                                            <td class="footer_return_amount"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td class="footer_original_amount"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td colspan="3"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Payment Terms Tab -->
                        <div role="tabpanel" class="tab-pane" id="payment_terms">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="payment_terms_table">
                                    <thead>
                                        <tr>
                                            <th>Supplier</th>
                                            <th>Payment Terms</th>
                                            <th>Total Purchases</th>
                                            <th>Total Amount</th>
                                            <th>Paid Amount</th>
                                            <th>Outstanding</th>
                                            <th>Payment Performance</th>
                                            <th>Avg Payment Days</th>
                                            <th>Compliance</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr class="bg-gray font-17 text-center footer-total">
                                            <td colspan="3"><strong>Total:</strong></td>
                                            <td class="footer_terms_total"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td class="footer_terms_paid"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td class="footer_terms_outstanding"><span class="display_currency"
                                                    data-currency_symbol="true">0</span></td>
                                            <td colspan="3"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@stop

@section('javascript')
<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script type="text/javascript">
    // Global chart variables
    let suppliersChart = null;
    let currentChartType = 'pie';
    
    $(document).ready(function() {
            // Initialize date pickers
            $('#start_date, #end_date').datepicker({
                autoclose: true,
                format: 'yyyy-mm-dd'
            });

            // Set default dates (last 30 days)
            var end_date = moment().format('YYYY-MM-DD');
            var start_date = moment().subtract(30, 'days').format('YYYY-MM-DD');
            
            $('#start_date').val(start_date);
            $('#end_date').val(end_date);

            // Initialize DataTables
            var supplier_trends_table = $('#supplier_trends_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('advancedreports.purchase-analysis.supplier-trends') }}",
                    data: function (d) {
                        d.location_id = $('#location_filter').val();
                        d.supplier_id = $('#supplier_filter').val();
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                    }
                },
                columns: [
                    { data: 'supplier_display', name: 'supplier_name' },
                    { data: 'location_name', name: 'bl.name' },
                    { data: 'total_purchases', name: 'total_purchases', searchable: false },
                    { data: 'formatted_total_amount', name: 'total_amount', searchable: false },
                    { data: 'formatted_average_amount', name: 'average_amount', searchable: false },
                    { data: 'purchase_frequency_display', name: 'purchase_frequency', searchable: false },
                    { data: 'first_purchase', name: 'first_purchase' },
                    { data: 'last_purchase', name: 'last_purchase' },
                    { data: 'trend_indicator', name: 'trend_indicator', orderable: false, searchable: false }
                ],
                order: [[3, 'desc']],
                "fnDrawCallback": function (oSettings) {
                    __currency_convert_recursively($('#supplier_trends_table'));
                    calculateSupplierTotals(this.api());
                }
            });

            var cost_optimization_table = $('#cost_optimization_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('advancedreports.purchase-analysis.cost-optimization') }}",
                    data: function (d) {
                        d.location_id = $('#location_filter').val();
                        d.supplier_id = $('#supplier_filter').val();
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                    }
                },
                columns: [
                    { data: 'product_display', name: 'product_name' },
                    { data: 'supplier_name', name: 'supplier_name' },
                    { data: 'purchase_count', name: 'purchase_count', searchable: false },
                    { data: 'total_quantity', name: 'total_quantity', searchable: false },
                    { data: 'formatted_avg_price', name: 'avg_purchase_price', searchable: false },
                    { data: 'formatted_min_price', name: 'min_purchase_price', searchable: false },
                    { data: 'formatted_max_price', name: 'max_purchase_price', searchable: false },
                    { data: 'variance_display', name: 'price_variance_percentage', searchable: false },
                    { data: 'formatted_total_cost', name: 'total_cost', searchable: false },
                    { data: 'formatted_potential_savings', name: 'potential_savings', searchable: false },
                    { data: 'optimization_status', name: 'optimization_status', orderable: false, searchable: false }
                ],
                order: [[9, 'desc']],
                "fnDrawCallback": function (oSettings) {
                    __currency_convert_recursively($('#cost_optimization_table'));
                    calculateOptimizationTotals(this.api());
                }
            });

            var return_analysis_table = $('#return_analysis_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('advancedreports.purchase-analysis.return-analysis') }}",
                    data: function (d) {
                        d.location_id = $('#location_filter').val();
                        d.supplier_id = $('#supplier_filter').val();
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                    }
                },
                columns: [
                    { data: 'transaction_date', name: 't.transaction_date' },
                    { data: 'ref_no', name: 't.ref_no' },
                    { data: 'original_purchase_ref', name: 'original_purchase_ref' },
                    { data: 'supplier_display', name: 'supplier_name' },
                    { data: 'location_name', name: 'bl.name' },
                    { data: 'formatted_return_amount', name: 'return_amount', searchable: false },
                    { data: 'formatted_original_amount', name: 'original_purchase_amount', searchable: false },
                    { data: 'return_percentage_display', name: 'return_percentage', searchable: false },
                    { data: 'timeline_status', name: 'timeline_status', orderable: false, searchable: false },
                    { data: 'return_reason_display', name: 'return_reason' }
                ],
                order: [[0, 'desc']],
                "fnDrawCallback": function (oSettings) {
                    __currency_convert_recursively($('#return_analysis_table'));
                    calculateReturnTotals(this.api());
                }
            });

            var payment_terms_table = $('#payment_terms_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('advancedreports.purchase-analysis.payment-terms') }}",
                    data: function (d) {
                        d.location_id = $('#location_filter').val();
                        d.supplier_id = $('#supplier_filter').val();
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                    }
                },
                columns: [
                    { data: 'supplier_name', name: 'supplier_name' },
                    { data: 'payment_terms_display', name: 'payment_terms_display', orderable: false },
                    { data: 'total_purchases', name: 'total_purchases', searchable: false },
                    { data: 'formatted_total_amount', name: 'total_amount', searchable: false },
                    { data: 'formatted_paid_amount', name: 'paid_amount', searchable: false },
                    { data: 'formatted_outstanding_amount', name: 'outstanding_amount', searchable: false },
                    { data: 'payment_performance', name: 'payment_performance', orderable: false, searchable: false },
                    { data: 'avg_payment_days_display', name: 'avg_payment_days', searchable: false },
                    { data: 'compliance_status', name: 'compliance_status', orderable: false, searchable: false }
                ],
                order: [[3, 'desc']],
                "fnDrawCallback": function (oSettings) {
                    __currency_convert_recursively($('#payment_terms_table'));
                    calculatePaymentTermsTotals(this.api());
                }
            });

            // Filter button click
            $('#filter_btn').click(function() {
                supplier_trends_table.ajax.reload();
                cost_optimization_table.ajax.reload();
                return_analysis_table.ajax.reload();
                payment_terms_table.ajax.reload();
                loadSummary();
            });

            // Export button click
            $('#export_btn').click(function(e) {
                e.preventDefault();

                var originalText = $(this).html();
                $(this).html('<i class="fa fa-spinner fa-spin"></i> {{ __('advancedreports::lang.exporting') }}');
                $(this).prop('disabled', true);

                var data = {
                    location_id: $('#location_filter').val(),
                    supplier_id: $('#supplier_filter').val(),
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val()
                };

                // Create a form and submit it as POST
                var form = $('<form>', {
                    'method': 'POST',
                    'action': '{{ route("advancedreports.purchase-analysis.export") }}',
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
                    if (value) {
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': key,
                            'value': value
                        }));
                    }
                });

                // Submit the form
                form.appendTo('body').submit().remove();

                setTimeout(function() {
                    $('#export_btn').html(originalText);
                    $('#export_btn').prop('disabled', false);
                }, 3000);
            });

            // Auto-filter on change
            $('#location_filter, #supplier_filter').change(function() {
                supplier_trends_table.ajax.reload();
                cost_optimization_table.ajax.reload();
                return_analysis_table.ajax.reload();
                payment_terms_table.ajax.reload();
                loadSummary();
            });

            // Load summary data
            function loadSummary() {
                var location_id = $('#location_filter').val();
                var supplier_id = $('#supplier_filter').val();
                var start_date = $('#start_date').val();
                var end_date = $('#end_date').val();
                
                $.ajax({
                    url: "{{ route('advancedreports.purchase-analysis.summary') }}",
                    data: {
                        location_id: location_id,
                        supplier_id: supplier_id,
                        start_date: start_date,
                        end_date: end_date
                    },
                    dataType: 'json',
                    success: function(data) {
                        // Update summary cards
                        $('#total_purchases').text(data.purchase_trends.total_purchases);
                        $('#total_purchase_amount').text(data.purchase_trends.formatted_total_amount);
                        
                        $('#potential_savings').text(data.cost_optimization.formatted_potential_savings);
                        $('#high_variance_products').text(data.cost_optimization.high_variance_products);
                        
                        $('#total_returns').text(data.return_analysis.total_returns);
                        $('#return_rate').text(data.return_analysis.formatted_return_rate);
                        
                        $('#compliance_rate').text(data.payment_terms.formatted_compliance_rate);
                        $('#outstanding_amount').text(data.payment_terms.formatted_outstanding_amount);

                        // Update progress bars
                        updateProgressBar('#purchases_progress', 100);
                        updateProgressBar('#savings_progress', Math.min(100, data.cost_optimization.high_variance_products * 10));
                        updateProgressBar('#returns_progress', Math.min(100, data.return_analysis.return_rate * 2));
                        updateProgressBar('#compliance_progress', parseFloat(data.payment_terms.compliance_rate));

                        // Show cards
                        $('#summary_cards').show();
                        
                        // Update top suppliers
                        updateTopSuppliers(data.top_suppliers);
                        $('#top_suppliers_section').show();
                    }
                });
            }

            function updateTopSuppliers(suppliers) {
                var html = '';
                var cardWidth = suppliers.length <= 5 ? 'calc(20% - 8px)' : 'calc(25% - 8px)';
                
                suppliers.forEach(function(supplier, index) {
                    var bgClass = ['bg-blue', 'bg-green', 'bg-yellow', 'bg-red', 'bg-purple'][index] || 'bg-gray';
                    var icon = ['fa-trophy', 'fa-medal', 'fa-award', 'fa-star', 'fa-thumbs-up'][index] || 'fa-user';
                    
                    html += '<div class="supplier-card" style="flex: 0 1 ' + cardWidth + '; min-width: 200px; margin-bottom: 10px;">';
                    html += '  <div class="info-box" style="margin: 0;">';
                    html += '    <span class="info-box-icon ' + bgClass + '"><i class="fa ' + icon + '"></i></span>';
                    html += '    <div class="info-box-content">';
                    html += '      <span class="info-box-text" style="font-size: 12px;">' + (supplier.supplier_name || 'Unknown') + '</span>';
                    html += '      <span class="info-box-number" style="font-size: 16px;">' + supplier.formatted_total_amount + '</span>';
                    html += '      <div class="progress" style="height: 4px;">';
                    html += '        <div class="progress-bar" style="width: ' + (100 - index * 10) + '%; height: 4px;"></div>';
                    html += '      </div>';
                    html += '      <span class="progress-description" style="font-size: 11px;">';
                    html += '        <span class="badge bg-light-blue">#' + (index + 1) + '</span> ' + supplier.purchase_count + ' purchases';
                    html += '      </span>';
                    html += '    </div>';
                    html += '  </div>';
                    html += '</div>';
                });
                
                // Add responsive styles for mobile
                html += '<style>';
                html += '@media (max-width: 768px) {';
                html += '  .supplier-card { flex: 0 1 calc(50% - 8px) !important; }';
                html += '}';
                html += '@media (max-width: 480px) {';
                html += '  .supplier-card { flex: 0 1 100% !important; }';
                html += '  #top_suppliers_cards { justify-content: center !important; }';
                html += '}';
                html += '</style>';
                
                $('#top_suppliers_cards').html(html);
                __currency_convert_recursively($('#top_suppliers_cards'));
                
                // Update chart with supplier data
                updateSuppliersChart(suppliers);
            }

            function updateSuppliersChart(suppliers) {
                const ctx = document.getElementById('suppliers_chart').getContext('2d');
                
                // Prepare chart data
                const labels = suppliers.map(supplier => supplier.supplier_name || 'Unknown');
                const data = suppliers.map(supplier => parseFloat(supplier.total_amount));
                const backgroundColors = [
                    '#3c8dbc', // Blue
                    '#00a65a', // Green  
                    '#f39c12', // Yellow
                    '#dd4b39', // Red
                    '#932ab6'  // Purple
                ];
                const borderColors = [
                    '#357ca5',
                    '#008d4c',
                    '#d68512',
                    '#c23321',
                    '#7a1f99'
                ];
                
                // Destroy existing chart
                if (suppliersChart) {
                    suppliersChart.destroy();
                }
                
                // Create new chart based on current type
                const config = {
                    type: currentChartType,
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Purchase Amount',
                            data: data,
                            backgroundColor: backgroundColors.slice(0, labels.length),
                            borderColor: borderColors.slice(0, labels.length),
                            borderWidth: 2,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: currentChartType === 'pie',
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || context.parsed.y || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return label + ': ' + formatCurrency(value) + ' (' + percentage + '%)';
                                    }
                                }
                            }
                        },
                        scales: currentChartType === 'bar' ? {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return formatCurrency(value);
                                    }
                                }
                            }
                        } : {}
                    }
                };
                
                suppliersChart = new Chart(ctx, config);
                
                // Update chart legend manually for pie chart
                if (currentChartType === 'pie') {
                    updateChartLegend(labels, data, backgroundColors);
                }
            }
            
            function updateChartLegend(labels, data, colors) {
                const total = data.reduce((a, b) => a + b, 0);
                let legendHtml = '';
                
                labels.forEach((label, index) => {
                    const value = data[index];
                    const percentage = ((value / total) * 100).toFixed(1);
                    legendHtml += `
                        <div style="display: inline-block; margin: 0 10px 5px 0;">
                            <span style="display: inline-block; width: 12px; height: 12px; background-color: ${colors[index]}; margin-right: 5px;"></span>
                            <small>${label}: ${percentage}%</small>
                        </div>
                    `;
                });
                
                $('#chart_legend').html(legendHtml);
            }
            
            // Chart type toggle functionality
            $('#chart_type_toggle').click(function() {
                currentChartType = currentChartType === 'pie' ? 'bar' : 'pie';
                const icon = currentChartType === 'pie' ? 'fa-pie-chart' : 'fa-bar-chart';
                $('.box-title i').removeClass('fa-pie-chart fa-bar-chart').addClass(icon);
                
                // Re-render chart with current data
                if (suppliersChart && suppliersChart.data.labels.length > 0) {
                    const suppliers = [];
                    suppliersChart.data.labels.forEach((label, index) => {
                        suppliers.push({
                            supplier_name: label,
                            total_amount: suppliersChart.data.datasets[0].data[index]
                        });
                    });
                    updateSuppliersChart(suppliers);
                }
            });

            function updateProgressBar(selector, percentage) {
                $(selector).css('width', percentage + '%');
            }
            
            // Helper function to format currency values
            function formatCurrency(value) {
                // Use the global currency formatting if available
                if (typeof __currency_convert_recursively !== 'undefined') {
                    return new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: "{{ session('currency.code', 'USD') }}",
                        minimumFractionDigits: 2
                    }).format(value);
                }
                return '$' + parseFloat(value).toFixed(2);
            }

            // Calculate totals for footer
            function calculateSupplierTotals(api) {
                var totalPurchases = api.column(2).data().reduce((a, b) => parseInt(a) + parseInt(b), 0);
                var totalAmount = api.column(3).data().reduce((a, b) => {
                    // Handle both text and jQuery objects
                    var text = typeof b === 'string' ? b : $(b).text();
                    var numericValue = parseFloat(text.replace(/[^0-9.-]+/g, "")) || 0;
                    return parseFloat(a) + numericValue;
                }, 0);
                var avgAmount = totalPurchases > 0 ? totalAmount / totalPurchases : 0;

                $('.footer_supplier_purchases').text(totalPurchases);
                $('.footer_supplier_amount .display_currency').text(totalAmount.toFixed(2));
                $('.footer_supplier_average .display_currency').text(avgAmount.toFixed(2));
                __currency_convert_recursively($('.footer-total'));
            }

            function calculateOptimizationTotals(api) {
                var totalCost = api.column(8).data().reduce((a, b) => {
                    var text = typeof b === 'string' ? b : $(b).text();
                    var numericValue = parseFloat(text.replace(/[^0-9.-]+/g, "")) || 0;
                    return parseFloat(a) + numericValue;
                }, 0);
                var totalSavings = api.column(9).data().reduce((a, b) => {
                    var text = typeof b === 'string' ? b : $(b).text();
                    var numericValue = parseFloat(text.replace(/[^0-9.-]+/g, "")) || 0;
                    return parseFloat(a) + numericValue;
                }, 0);

                $('.footer_optimization_cost .display_currency').text(totalCost.toFixed(2));
                $('.footer_optimization_savings .display_currency').text(totalSavings.toFixed(2));
                __currency_convert_recursively($('.footer-total'));
            }

            function calculateReturnTotals(api) {
                var returnAmount = api.column(5).data().reduce((a, b) => {
                    var text = typeof b === 'string' ? b : $(b).text();
                    var numericValue = parseFloat(text.replace(/[^0-9.-]+/g, "")) || 0;
                    return parseFloat(a) + numericValue;
                }, 0);
                var originalAmount = api.column(6).data().reduce((a, b) => {
                    var text = typeof b === 'string' ? b : $(b).text();
                    var numericValue = parseFloat(text.replace(/[^0-9.-]+/g, "")) || 0;
                    return parseFloat(a) + numericValue;
                }, 0);

                $('.footer_return_amount .display_currency').text(returnAmount.toFixed(2));
                $('.footer_original_amount .display_currency').text(originalAmount.toFixed(2));
                __currency_convert_recursively($('.footer-total'));
            }

            function calculatePaymentTermsTotals(api) {
                var totalAmount = api.column(3).data().reduce((a, b) => {
                    var text = typeof b === 'string' ? b : $(b).text();
                    var numericValue = parseFloat(text.replace(/[^0-9.-]+/g, "")) || 0;
                    return parseFloat(a) + numericValue;
                }, 0);
                var paidAmount = api.column(4).data().reduce((a, b) => {
                    var text = typeof b === 'string' ? b : $(b).text();
                    var numericValue = parseFloat(text.replace(/[^0-9.-]+/g, "")) || 0;
                    return parseFloat(a) + numericValue;
                }, 0);
                var outstandingAmount = api.column(5).data().reduce((a, b) => {
                    var text = typeof b === 'string' ? b : $(b).text();
                    var numericValue = parseFloat(text.replace(/[^0-9.-]+/g, "")) || 0;
                    return parseFloat(a) + numericValue;
                }, 0);

                $('.footer_terms_total .display_currency').text(totalAmount.toFixed(2));
                $('.footer_terms_paid .display_currency').text(paidAmount.toFixed(2));
                $('.footer_terms_outstanding .display_currency').text(outstandingAmount.toFixed(2));
                __currency_convert_recursively($('.footer-total'));
            }

            // Load initial data
            loadSummary();
        });
</script>
@endsection