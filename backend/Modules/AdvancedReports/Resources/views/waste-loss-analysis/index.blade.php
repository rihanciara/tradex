@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.waste_loss_analysis_report'))

@section('content')

<!-- Content Header -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('advancedreports::lang.waste_loss_analysis_report')
        <small class="text-muted">@lang('advancedreports::lang.waste_loss_subtitle')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">

    <!-- Enhanced Filters Section -->
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', [
            'title' => __('advancedreports::lang.filters'),
            'class' => 'box-primary'
            ])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('wl_date_range', __('advancedreports::lang.analysis_period')) !!}
                    {!! Form::text('wl_date_range', null, ['placeholder' =>
                    __('advancedreports::lang.select_date_range'), 'class' =>
                    'form-control', 'id' => 'wl_date_range', 'readonly']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('wl_location_id', __('advancedreports::lang.location')) !!}
                    {!! Form::select('wl_location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'wl_location_id']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('wl_category_id', __('advancedreports::lang.category')) !!}
                    {!! Form::select('wl_category_id', $categories, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'wl_category_id']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('wl_supplier_id', __('advancedreports::lang.supplier')) !!}
                    {!! Form::select('wl_supplier_id', $suppliers, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'wl_supplier_id']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>&nbsp;</label><br>
                    <button type="button" class="btn btn-primary" id="wl_filter_btn">
                        <i class="fa fa-filter"></i> @lang('advancedreports::lang.analyze_waste_loss')
                    </button>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-red"><i class="fa fa-exclamation-triangle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('advancedreports::lang.expired_products')</span>
                    <span class="info-box-number" id="wl_expired_count">0</span>
                    <div class="progress">
                        <div class="progress-bar bg-red" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="wl_expired_value"><span class="display_currency" data-currency_symbol="true">0</span> total value</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-orange"><i class="fa fa-broken-chain"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('advancedreports::lang.damaged_goods')</span>
                    <span class="info-box-number" id="wl_damaged_count">0</span>
                    <div class="progress">
                        <div class="progress-bar bg-orange" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="wl_damaged_value"><span class="display_currency" data-currency_symbol="true">0</span> total value</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-user-times"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('advancedreports::lang.theft_shrinkage')</span>
                    <span class="info-box-number" id="wl_shrinkage_count">0</span>
                    <div class="progress">
                        <div class="progress-bar bg-yellow" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="wl_shrinkage_value"><span class="display_currency" data-currency_symbol="true">0</span> total value</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box">
                <span class="info-box-icon bg-blue"><i class="fa fa-calculator"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">@lang('advancedreports::lang.total_loss')</span>
                    <span class="info-box-number" id="wl_total_loss"><span class="display_currency" data-currency_symbol="true">0</span></span>
                    <div class="progress">
                        <div class="progress-bar bg-blue" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="wl_upcoming_expirations">0 upcoming expirations</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Loss Analysis Charts -->
    <div class="row">
        <div class="col-md-8">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-line-chart"></i> @lang('advancedreports::lang.loss_trends')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="print_loss_trends" title="@lang('advancedreports::lang.print')">
                            <i class="fa fa-print"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-success" id="export_loss_chart" title="@lang('advancedreports::lang.export_image')">
                            <i class="fa fa-download"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 400px;">
                        <canvas id="lossChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-pie-chart"></i> @lang('advancedreports::lang.loss_breakdown')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="loss_breakdown_toggle">
                            <i class="fa fa-bar-chart"></i> @lang('advancedreports::lang.bar_chart')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="lossBreakdownChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Expired Products Analysis -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock-o"></i> @lang('advancedreports::lang.expired_products_analysis')</h3>
                    <div class="box-tools pull-right">
                        <div class="form-group" style="margin-bottom: 0; display: inline-block; margin-right: 10px;">
                            <select id="expiry_filter" class="form-control input-sm" style="width: 150px;">
                                <option value="">@lang('advancedreports::lang.all_expired')</option>
                                <option value="today">@lang('advancedreports::lang.expired_today')</option>
                                <option value="week">@lang('advancedreports::lang.expired_this_week')</option>
                                <option value="month">@lang('advancedreports::lang.expired_this_month')</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-sm btn-default" id="print_expired_products" title="@lang('advancedreports::lang.print')">
                            <i class="fa fa-print"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <!-- Expiry Categories -->
                        <div class="col-md-6">
                            <h4>@lang('advancedreports::lang.expiry_categories')</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box bg-red">
                                        <span class="info-box-icon"><i class="fa fa-exclamation"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text" style="color: white;">@lang('advancedreports::lang.expired_today')</span>
                                            <span class="info-box-number" id="expired_today_count">0</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box bg-orange">
                                        <span class="info-box-icon"><i class="fa fa-calendar"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text" style="color: white;">@lang('advancedreports::lang.expired_this_week')</span>
                                            <span class="info-box-number" id="expired_week_count">0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Upcoming Expirations -->
                        <div class="col-md-6">
                            <h4>@lang('advancedreports::lang.upcoming_expirations')</h4>
                            <div class="table-responsive">
                                <table class="table table-striped table-condensed">
                                    <thead>
                                        <tr>
                                            <th>@lang('advancedreports::lang.product')</th>
                                            <th>@lang('advancedreports::lang.expiry_date')</th>
                                            <th>@lang('advancedreports::lang.days_remaining')</th>
                                            <th>@lang('advancedreports::lang.quantity')</th>
                                        </tr>
                                    </thead>
                                    <tbody id="upcoming_expirations_tbody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="expired_products_table">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.product')</th>
                                    <th>@lang('advancedreports::lang.sku')</th>
                                    <th>@lang('advancedreports::lang.category')</th>
                                    <th>@lang('advancedreports::lang.location')</th>
                                    <th>@lang('advancedreports::lang.expiry_date')</th>
                                    <th>@lang('advancedreports::lang.days_expired')</th>
                                    <th>@lang('advancedreports::lang.quantity')</th>
                                    <th>@lang('advancedreports::lang.loss_value')</th>
                                </tr>
                            </thead>
                            <tbody id="expired_products_tbody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Damaged Goods & Shrinkage Analysis -->
    <div class="row">
        <!-- Damaged Goods -->
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-broken-chain"></i> @lang('advancedreports::lang.damaged_goods_analysis')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="print_damaged_goods" title="@lang('advancedreports::lang.print')">
                            <i class="fa fa-print"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div style="position: relative; height: 200px;">
                                <canvas id="damageReasonsChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5>@lang('advancedreports::lang.damage_summary')</h5>
                            <div class="info-box bg-orange">
                                <span class="info-box-icon"><i class="fa fa-wrench"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text" style="color: white;">@lang('advancedreports::lang.physical_damage')</span>
                                    <span class="info-box-number" id="physical_damage_count">0</span>
                                </div>
                            </div>
                            <div class="info-box bg-yellow">
                                <span class="info-box-icon"><i class="fa fa-leaf"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text" style="color: white;">@lang('advancedreports::lang.spoilage')</span>
                                    <span class="info-box-number" id="spoilage_count">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.product')</th>
                                    <th>@lang('advancedreports::lang.damage_date')</th>
                                    <th>@lang('advancedreports::lang.quantity')</th>
                                    <th>@lang('advancedreports::lang.value')</th>
                                    <th>@lang('advancedreports::lang.reason')</th>
                                </tr>
                            </thead>
                            <tbody id="damaged_goods_tbody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Theft & Shrinkage -->
        <div class="col-md-6">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-user-times"></i> @lang('advancedreports::lang.theft_shrinkage_analysis')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="print_shrinkage" title="@lang('advancedreports::lang.print')">
                            <i class="fa fa-print"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box bg-red">
                                <span class="info-box-icon"><i class="fa fa-percent"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text" style="color: white;">@lang('advancedreports::lang.shrinkage_rate')</span>
                                    <span class="info-box-number" id="shrinkage_rate">0%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box bg-orange">
                                <span class="info-box-icon"><i class="fa fa-warning"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text" style="color: white;">@lang('advancedreports::lang.high_risk_products')</span>
                                    <span class="info-box-number" id="high_risk_count">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <h5>@lang('advancedreports::lang.high_risk_products')</h5>
                    <div class="table-responsive">
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.product')</th>
                                    <th>@lang('advancedreports::lang.incidents')</th>
                                    <th>@lang('advancedreports::lang.quantity_lost')</th>
                                    <th>@lang('advancedreports::lang.value_lost')</th>
                                </tr>
                            </thead>
                            <tbody id="high_risk_products_tbody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loss Prevention Insights -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-shield"></i> @lang('advancedreports::lang.loss_prevention_insights')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="print_insights" title="@lang('advancedreports::lang.print')">
                            <i class="fa fa-print"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <!-- Top Loss Categories -->
                        <div class="col-md-4">
                            <h4>@lang('advancedreports::lang.top_loss_categories')</h4>
                            <div style="position: relative; height: 250px;">
                                <canvas id="topLossCategoriesChart"></canvas>
                            </div>
                        </div>
                        <!-- Loss by Location -->
                        <div class="col-md-4">
                            <h4>@lang('advancedreports::lang.loss_by_location')</h4>
                            <div style="position: relative; height: 250px;">
                                <canvas id="lossByLocationChart"></canvas>
                            </div>
                        </div>
                        <!-- Recommendations -->
                        <div class="col-md-4">
                            <h4>@lang('advancedreports::lang.recommendations')</h4>
                            <div id="recommendations_container">
                                <!-- Recommendations will be populated here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>
@endsection

@section('javascript')
<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        // Initialize date picker
        $('#wl_date_range').daterangepicker({
            startDate: moment().subtract(6, 'months'),
            endDate: moment(),
            ranges: {
                '@lang("advancedreports::lang.last_month")': [moment().subtract(1, 'month'), moment()],
                '@lang("advancedreports::lang.last_3_months")': [moment().subtract(3, 'months'), moment()],
                '@lang("advancedreports::lang.last_6_months")': [moment().subtract(6, 'months'), moment()],
                '@lang("advancedreports::lang.last_year")': [moment().subtract(1, 'year'), moment()],
                '@lang("advancedreports::lang.this_year")': [moment().startOf('year'), moment().endOf('year')]
            },
            locale: {
                format: 'YYYY-MM-DD'
            }
        });

        // Initialize Select2
        $('.select2').select2();

        // Chart variables
        let lossChart, lossBreakdownChart, damageReasonsChart, topLossCategoriesChart, lossByLocationChart;

        // Load analytics data
        function loadAnalytics() {
            const dateRange = $('#wl_date_range').val().split(' - ');
            const startDate = dateRange[0];
            const endDate = dateRange[1];
            const locationId = $('#wl_location_id').val() || 'all';
            const categoryId = $('#wl_category_id').val() || 'all';
            const supplierId = $('#wl_supplier_id').val() || 'all';

            $.ajax({
                url: '{{ route("advancedreports.waste-loss.analytics") }}',
                type: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    location_id: locationId,
                    category_id: categoryId,
                    supplier_id: supplierId
                },
                success: function(data) {
                    updateSummaryCards(data.summary_cards);
                    updateLossChart(data.trends);
                    updateLossBreakdownChart(data.expired_products, data.damaged_goods, data.theft_shrinkage);
                    updateExpiredProductsTable(data.expired_products);
                    updateDamagedGoodsTable(data.damaged_goods);
                    updateShrinkageTable(data.theft_shrinkage);
                    updateLossPreventionInsights(data.loss_prevention);
                },
                error: function(xhr, status, error) {
                    console.error('Error loading analytics:', error);
                }
            });
        }

        // Update summary cards
        function updateSummaryCards(data) {
            $('#wl_expired_count').text(data.expired_products_count || 0);
            $('#wl_expired_value').html('<span class="display_currency" data-currency_symbol="true">' + (data.total_expired_value || 0) + '</span> total value');
            $('#wl_damaged_count').text(data.damaged_incidents_count || 0);
            $('#wl_damaged_value').html('<span class="display_currency" data-currency_symbol="true">' + (data.total_damaged_value || 0) + '</span> total value');
            $('#wl_shrinkage_count').text(data.shrinkage_incidents_count || 0);
            $('#wl_shrinkage_value').html('<span class="display_currency" data-currency_symbol="true">' + (data.total_shrinkage_value || 0) + '</span> total value');
            $('#wl_total_loss').html('<span class="display_currency" data-currency_symbol="true">' + (data.total_loss_value || 0) + '</span>');
            $('#wl_upcoming_expirations').text((data.upcoming_expirations || 0) + ' upcoming expirations');
            
            // Trigger currency conversion for the new elements
            __currency_convert_recursively($('#wl_expired_value, #wl_damaged_value, #wl_shrinkage_value, #wl_total_loss'));
        }

        // Update loss trends chart
        function updateLossChart(trends) {
            const labels = trends.map(t => t.period);
            const lossData = trends.map(t => t.total_loss);
            const incidentData = trends.map(t => t.incident_count);

            if (lossChart) lossChart.destroy();

            const ctx = document.getElementById('lossChart').getContext('2d');
            lossChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '@lang("advancedreports::lang.total_loss_value")',
                        data: lossData,
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        yAxisID: 'y'
                    }, {
                        label: '@lang("advancedreports::lang.incident_count")',
                        data: incidentData,
                        borderColor: '#f39c12',
                        backgroundColor: 'rgba(243, 156, 18, 0.1)',
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false,
                            },
                        },
                    }
                }
            });
        }

        // Update loss breakdown chart
        function updateLossBreakdownChart(expired, damaged, shrinkage) {
            const data = [
                expired.total_expired_value || 0,
                damaged.total_damage_value || 0,
                shrinkage.total_shrinkage_value || 0
            ];

            if (lossBreakdownChart) lossBreakdownChart.destroy();

            const ctx = document.getElementById('lossBreakdownChart').getContext('2d');
            lossBreakdownChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [
                        '@lang("advancedreports::lang.expired_products")',
                        '@lang("advancedreports::lang.damaged_goods")',
                        '@lang("advancedreports::lang.theft_shrinkage")'
                    ],
                    datasets: [{
                        data: data,
                        backgroundColor: ['#e74c3c', '#f39c12', '#f1c40f'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Update expired products table
        function updateExpiredProductsTable(data) {
            const products = data.expired_products || [];
            const categories = data.expiry_categories || {};
            const upcoming = data.upcoming_expirations || [];

            // Update expiry categories
            $('#expired_today_count').text(categories.expired_today || 0);
            $('#expired_week_count').text(categories.expired_this_week || 0);

            // Update expired products table
            let html = '';
            products.forEach(function(product) {
                html += `
                    <tr>
                        <td>${product.product_name}</td>
                        <td>${product.sku || ''}</td>
                        <td>${product.category_name || ''}</td>
                        <td>${product.location_name}</td>
                        <td>${moment(product.exp_date).format('DD/MM/YYYY')}</td>
                        <td><span class="label label-danger">${product.days_expired} days</span></td>
                        <td>${product.qty_available}</td>
                        <td><span class="display_currency" data-currency_symbol="true">${product.total_loss_value || 0}</span></td>
                    </tr>
                `;
            });
            $('#expired_products_tbody').html(html || '<tr><td colspan="8" class="text-center">@lang("advancedreports::lang.no_expired_products")</td></tr>');
            
            // Convert currency in the table
            __currency_convert_recursively($('#expired_products_tbody'));

            // Update upcoming expirations
            let upcomingHtml = '';
            upcoming.forEach(function(item) {
                upcomingHtml += `
                    <tr>
                        <td>${item.product_name}</td>
                        <td>${moment(item.exp_date).format('DD/MM/YYYY')}</td>
                        <td><span class="label label-warning">${item.days_until_expiry} days</span></td>
                        <td>${item.qty_available}</td>
                    </tr>
                `;
            });
            $('#upcoming_expirations_tbody').html(upcomingHtml || '<tr><td colspan="4" class="text-center">@lang("advancedreports::lang.no_upcoming_expirations")</td></tr>');
        }

        // Update damaged goods table
        function updateDamagedGoodsTable(data) {
            const damages = data.damaged_goods || [];
            const categories = data.damage_categories || {};

            $('#physical_damage_count').text(categories.physical_damage || 0);
            $('#spoilage_count').text(categories.spoilage || 0);

            let html = '';
            damages.slice(0, 10).forEach(function(damage) {
                html += `
                    <tr>
                        <td>${damage.product_name}</td>
                        <td>${moment(damage.damage_date).format('DD/MM/YYYY')}</td>
                        <td>${Math.abs(damage.damaged_quantity)}</td>
                        <td><span class="display_currency" data-currency_symbol="true">${damage.damage_value || 0}</span></td>
                        <td>${damage.reason || 'Not specified'}</td>
                    </tr>
                `;
            });
            $('#damaged_goods_tbody').html(html || '<tr><td colspan="5" class="text-center">@lang("advancedreports::lang.no_damaged_goods")</td></tr>');
            
            // Convert currency in the table
            __currency_convert_recursively($('#damaged_goods_tbody'));
        }

        // Update shrinkage table
        function updateShrinkageTable(data) {
            const highRisk = data.high_risk_products || [];
            
            $('#shrinkage_rate').text((data.shrinkage_rate || 0).toFixed(2) + '%');
            $('#high_risk_count').text(highRisk.length);

            let html = '';
            highRisk.slice(0, 10).forEach(function(product) {
                html += `
                    <tr>
                        <td>${product.product_name}</td>
                        <td><span class="label label-danger">${product.incident_count}</span></td>
                        <td>${product.total_quantity_lost}</td>
                        <td><span class="display_currency" data-currency_symbol="true">${product.total_value_lost || 0}</span></td>
                    </tr>
                `;
            });
            $('#high_risk_products_tbody').html(html || '<tr><td colspan="4" class="text-center">@lang("advancedreports::lang.no_high_risk_products")</td></tr>');
            
            // Convert currency in the table
            __currency_convert_recursively($('#high_risk_products_tbody'));
        }

        // Update loss prevention insights
        function updateLossPreventionInsights(data) {
            updateTopLossCategoriesChart(data.top_loss_categories || []);
            updateLossByLocationChart(data.loss_by_location || []);
            updateRecommendations(data.recommendations || []);
        }

        // Update top loss categories chart
        function updateTopLossCategoriesChart(categories) {
            const labels = categories.map(c => c.category_name);
            const values = categories.map(c => c.total_value_lost);

            if (topLossCategoriesChart) topLossCategoriesChart.destroy();

            const ctx = document.getElementById('topLossCategoriesChart').getContext('2d');
            topLossCategoriesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '@lang("advancedreports::lang.loss_value")',
                        data: values,
                        backgroundColor: '#e74c3c',
                        borderColor: '#c0392b',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Update loss by location chart
        function updateLossByLocationChart(locations) {
            const labels = locations.map(l => l.location_name);
            const values = locations.map(l => l.total_loss_value);

            if (lossByLocationChart) lossByLocationChart.destroy();

            const ctx = document.getElementById('lossByLocationChart').getContext('2d');
            lossByLocationChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: ['#3498db', '#e74c3c', '#f39c12', '#27ae60', '#9b59b6'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Update recommendations
        function updateRecommendations(recommendations) {
            let html = '';
            recommendations.forEach(function(rec) {
                const priorityClass = rec.priority === 'HIGH' ? 'danger' : (rec.priority === 'MEDIUM' ? 'warning' : 'info');
                html += `
                    <div class="callout callout-${priorityClass}">
                        <h5><i class="fa fa-lightbulb-o"></i> ${rec.title}</h5>
                        <p>${rec.description}</p>
                        <small><strong>Potential Savings:</strong> <span class="display_currency" data-currency_symbol="true">${rec.potential_savings || 0}</span></small>
                    </div>
                `;
            });
            $('#recommendations_container').html(html || '<p class="text-muted">@lang("advancedreports::lang.no_recommendations")</p>');
            
            // Convert currency in the recommendations
            __currency_convert_recursively($('#recommendations_container'));
        }

        // Event handlers
        $('#wl_filter_btn').click(function() {
            loadAnalytics();
        });

        // Chart toggle
        $('#loss_breakdown_toggle').click(function() {
            if (lossBreakdownChart.config.type === 'doughnut') {
                lossBreakdownChart.config.type = 'bar';
                $(this).html('<i class="fa fa-pie-chart"></i> @lang("advancedreports::lang.pie_chart")');
            } else {
                lossBreakdownChart.config.type = 'doughnut';
                $(this).html('<i class="fa fa-bar-chart"></i> @lang("advancedreports::lang.bar_chart")');
            }
            lossBreakdownChart.update();
        });

        // Print functions
        $('#print_loss_trends').click(function() {
            printSection('Loss Trends Analysis', $(this).closest('.box'));
        });

        $('#print_expired_products').click(function() {
            printSection('Expired Products Analysis', $(this).closest('.box'));
        });

        $('#print_damaged_goods').click(function() {
            printSection('Damaged Goods Analysis', $(this).closest('.box'));
        });

        $('#print_shrinkage').click(function() {
            printSection('Theft & Shrinkage Analysis', $(this).closest('.box'));
        });

        $('#print_insights').click(function() {
            printSection('Loss Prevention Insights', $(this).closest('.box'));
        });

        $('#export_loss_chart').click(function() {
            exportChartAsImage(lossChart, 'loss-trends-chart');
        });

        // Print and export functions
        function printSection(title, sectionElement) {
            const printContents = sectionElement.html();
            const printWindow = window.open('', '_blank');
            
            printWindow.document.write(`
                <html>
                <head>
                    <title>${title}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
                        th { background-color: #f2f2f2; font-weight: bold; }
                        .info-box { border: 1px solid #ddd; padding: 15px; margin: 10px 0; }
                        .info-box-text { font-weight: bold; }
                        .info-box-number { font-size: 18px; font-weight: bold; }
                        h3, h4, h5 { color: #333; border-bottom: 2px solid #ddd; padding-bottom: 5px; }
                        @media print { body { margin: 0; } .no-print { display: none !important; } }
                    </style>
                </head>
                <body>
                    <h1>${title}</h1>
                    <p>Generated on: ${new Date().toLocaleString()}</p>
                    ${printContents}
                </body>
                </html>
            `);
            
            printWindow.document.close();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 250);
        }

        function exportChartAsImage(chart, filename) {
            if (!chart) {
                alert('@lang("advancedreports::lang.no_chart_available")');
                return;
            }
            
            const url = chart.canvas.toDataURL('image/png');
            const link = document.createElement('a');
            link.download = `${filename}.png`;
            link.href = url;
            link.click();
        }

        // Convert initial currency values
        __currency_convert_recursively($(document));
        
        // Load initial data
        loadAnalytics();
    });
</script>
@endsection