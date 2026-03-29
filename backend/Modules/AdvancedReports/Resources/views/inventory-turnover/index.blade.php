@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.inventory_turnover_report'))

@section('content')

<!-- Content Header -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ __('advancedreports::lang.inventory_turnover_report')}}
        <small class="text-muted">@lang('advancedreports::lang.stock_rotation_analysis_movement_classification')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">

    <!-- Enhanced Filters Section -->
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', [
            'title' => __('Filters'),
            'class' => 'box-primary'
            ])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('it_date_range', __('Date Range:')) !!}
                    {!! Form::text('it_date_range', null, ['placeholder' => __('Select Date Range'), 'class' =>
                    'form-control', 'id' => 'it_date_range', 'readonly']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('it_location_id', __('Location:')) !!}
                    {!! Form::select('it_location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%',
                    'placeholder' => __('All Locations'), 'id' => 'it_location_id']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('it_category_id', __('Category:')) !!}
                    {!! Form::select('it_category_id', $categories, null, ['class' => 'form-control select2',
                    'style' => 'width:100%',
                    'placeholder' => __('All Categories'), 'id' => 'it_category_id']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('it_supplier_id', __('Supplier:')) !!}
                    {!! Form::select('it_supplier_id', $suppliers, null, ['class' => 'form-control select2',
                    'style' => 'width:100%',
                    'placeholder' => __('All Suppliers'), 'id' => 'it_supplier_id']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>&nbsp;</label><br>
                    <button type="button" class="btn btn-primary" id="it_filter_btn">
                        <i class="fa fa-filter"></i> @lang('Filter')
                    </button>
                    <button type="button" class="btn btn-success" id="export_btn">
                        <i class="fa fa-download"></i> {{ __('lang_v1.export') }}
                    </button>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-blue-100 tw-text-blue-500">
                            <svg class="tw-w-6 tw-h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-truncate">@lang('advancedreports::lang.total_products')</p>
                            <div class="tw-flex tw-items-baseline tw-gap-2">
                                <p class="tw-text-2xl tw-font-semibold tw-text-gray-900" id="it_total_products">0</p>
                            </div>
                            <p class="tw-text-xs tw-text-gray-500 tw-mt-1" id="it_products_sold">0 @lang('advancedreports::lang.products_sold')</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-green-100 tw-text-green-500">
                            <svg class="tw-w-6 tw-h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-truncate">@lang('advancedreports::lang.avg_turnover_ratio')</p>
                            <div class="tw-flex tw-items-baseline tw-gap-2">
                                <p class="tw-text-2xl tw-font-semibold tw-text-gray-900" id="it_avg_turnover">0</p>
                            </div>
                            <p class="tw-text-xs tw-text-gray-500 tw-mt-1" id="it_daily_velocity">0 @lang('advancedreports::lang.units_per_day')</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-yellow-100 tw-text-yellow-500">
                            <svg class="tw-w-6 tw-h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-truncate">@lang('advancedreports::lang.stock_value')</p>
                            <div class="tw-flex tw-items-baseline tw-gap-2">
                                <p class="tw-text-2xl tw-font-semibold tw-text-gray-900" id="it_stock_value"><span class="display_currency" data-currency_symbol="true">0</span></p>
                            </div>
                            <p class="tw-text-xs tw-text-gray-500 tw-mt-1" id="it_stock_qty">0 @lang('advancedreports::lang.units')</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-red-100 tw-text-red-500">
                            <svg class="tw-w-6 tw-h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-truncate">@lang('advancedreports::lang.stock_health')</p>
                            <div class="tw-flex tw-items-baseline tw-gap-2">
                                <p class="tw-text-2xl tw-font-semibold tw-text-gray-900" id="it_stock_health">0%</p>
                            </div>
                            <p class="tw-text-xs tw-text-gray-500 tw-mt-1" id="it_dead_stock">0 @lang('advancedreports::lang.dead_stock_items')</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Movement Classification -->
    <div class="row">
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-pie-chart"></i> @lang('advancedreports::lang.stock_movement_classification')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="movement_chart_toggle">
                            <i class="fa fa-bar-chart"></i> @lang('advancedreports::lang.bar_chart')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <canvas id="movementClassificationChart" height="400"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock-o"></i> @lang('advancedreports::lang.inventory_aging')</h3>
                </div>
                <div class="box-body">
                    <canvas id="inventoryAgingChart" height="400"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Turnover Analysis -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-line-chart"></i> @lang('advancedreports::lang.category_movement_analysis')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="category_chart_toggle">
                            <i class="fa fa-pie-chart"></i> @lang('advancedreports::lang.pie_chart')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <canvas id="categoryMovementChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-exclamation"></i> @lang('advancedreports::lang.risk_analysis')</h3>
                </div>
                <div class="box-body">
                    <canvas id="riskAnalysisChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Recommendations -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-lightbulb-o"></i> @lang('advancedreports::lang.stock_level_recommendations')</h3>
                    <div class="box-tools pull-right">
                        <div class="form-group" style="margin-bottom: 0;">
                            <select id="recommendation_filter" class="form-control input-sm" style="width: 150px;">
                                <option value="">@lang('advancedreports::lang.all_actions')</option>
                                <option value="REORDER NOW">@lang('advancedreports::lang.reorder_now')</option>
                                <option value="REDUCE STOCK">@lang('advancedreports::lang.reduce_stock')</option>
                                <option value="LIQUIDATE">@lang('advancedreports::lang.liquidate')</option>
                                <option value="MAINTAIN">@lang('advancedreports::lang.maintain')</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                                <div class="tw-p-4 sm:tw-p-5">
                                    <div class="tw-flex tw-items-center tw-gap-4">
                                        <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-yellow-100 tw-text-yellow-500">
                                            <svg class="tw-w-6 tw-h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17M17 13v4a2 2 0 01-2 2H9a2 2 0 01-2-2v-4m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01"></path>
                                            </svg>
                                        </div>
                                        <div class="tw-flex-1 tw-min-w-0">
                                            <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-truncate">@lang('advancedreports::lang.reorder_now')</p>
                                            <div class="tw-flex tw-items-baseline tw-gap-2">
                                                <p class="tw-text-2xl tw-font-semibold tw-text-gray-900" id="reorder_count">0</p>
                                            </div>
                                            <p class="tw-text-xs tw-text-gray-500 tw-mt-1" id="reorder_value"><span class="display_currency" data-currency_symbol="true">0</span> @lang('advancedreports::lang.value')</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                                <div class="tw-p-4 sm:tw-p-5">
                                    <div class="tw-flex tw-items-center tw-gap-4">
                                        <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-red-100 tw-text-red-500">
                                            <svg class="tw-w-6 tw-h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                            </svg>
                                        </div>
                                        <div class="tw-flex-1 tw-min-w-0">
                                            <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-truncate">@lang('advancedreports::lang.reduce_stock')</p>
                                            <div class="tw-flex tw-items-baseline tw-gap-2">
                                                <p class="tw-text-2xl tw-font-semibold tw-text-gray-900" id="reduce_count">0</p>
                                            </div>
                                            <p class="tw-text-xs tw-text-gray-500 tw-mt-1" id="reduce_savings"><span class="display_currency" data-currency_symbol="true">0</span> @lang('advancedreports::lang.potential_savings')</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                                <div class="tw-p-4 sm:tw-p-5">
                                    <div class="tw-flex tw-items-center tw-gap-4">
                                        <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-gray-100 tw-text-gray-500">
                                            <svg class="tw-w-6 tw-h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </div>
                                        <div class="tw-flex-1 tw-min-w-0">
                                            <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-truncate">@lang('advancedreports::lang.liquidate')</p>
                                            <div class="tw-flex tw-items-baseline tw-gap-2">
                                                <p class="tw-text-2xl tw-font-semibold tw-text-gray-900" id="liquidate_count">0</p>
                                            </div>
                                            <p class="tw-text-xs tw-text-gray-500 tw-mt-1" id="liquidate_value"><span class="display_currency" data-currency_symbol="true">0</span> @lang('advancedreports::lang.value')</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="recommendations_table">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.product')</th>
                                    <th>@lang('advancedreports::lang.category')</th>
                                    <th>@lang('advancedreports::lang.current_stock')</th>
                                    <th>@lang('advancedreports::lang.velocity')</th>
                                    <th>@lang('advancedreports::lang.turnover_ratio')</th>
                                    <th>@lang('advancedreports::lang.action')</th>
                                    <th>@lang('advancedreports::lang.recommended_qty')</th>
                                    <th>@lang('advancedreports::lang.impact')</th>
                                    <th>@lang('advancedreports::lang.reason')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performers vs Worst Performers -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-star"></i> @lang('advancedreports::lang.top_performers_fast_moving')</h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.product')</th>
                                    <th>@lang('advancedreports::lang.turnover')</th>
                                    <th>@lang('advancedreports::lang.velocity')</th>
                                    <th>@lang('advancedreports::lang.stock')</th>
                                </tr>
                            </thead>
                            <tbody id="top_performers_tbody">
                                <!-- @lang('advancedreports::lang.top_performers_populated_here') -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-warning"></i> @lang('advancedreports::lang.worst_performers_dead_stock')</h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.product')</th>
                                    <th>@lang('advancedreports::lang.stock_value')</th>
                                    <th>@lang('advancedreports::lang.days_in_stock')</th>
                                    <th>@lang('advancedreports::lang.last_sale')</th>
                                </tr>
                            </thead>
                            <tbody id="worst_performers_tbody">
                                <!-- @lang('advancedreports::lang.worst_performers_populated_here') -->
                            </tbody>
                        </table>
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
        $('#it_date_range').daterangepicker({
            startDate: moment().subtract(6, 'months'),
            endDate: moment(),
            ranges: {
                'Last 3 Months': [moment().subtract(3, 'months'), moment()],
                'Last 6 Months': [moment().subtract(6, 'months'), moment()],
                'Last Year': [moment().subtract(1, 'year'), moment()],
                'This Year': [moment().startOf('year'), moment().endOf('year')]
            },
            locale: {
                format: 'YYYY-MM-DD'
            }
        });

        // Initialize Select2
        $('.select2').select2();

        // Chart variables
        let movementClassificationChart, inventoryAgingChart, categoryMovementChart, riskAnalysisChart;

        // DataTable variable
        let recommendationsTable;

        // Initialize DataTable
        function initializeRecommendationsTable() {
            recommendationsTable = $('#recommendations_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("advancedreports.inventory-turnover.recommendations-data") }}',
                    data: function (d) {
                        d.start_date = $('#it_date_range').val().split(' - ')[0];
                        d.end_date = $('#it_date_range').val().split(' - ')[1];
                        d.location_id = $('#it_location_id').val();
                        d.category_id = $('#it_category_id').val();
                        d.supplier_id = $('#it_supplier_id').val();
                        d.action_filter = $('#recommendation_filter').val();
                    }
                },
                columns: [
                    { data: 'product', name: 'product_name', title: '@lang('advancedreports::lang.product')' },
                    { data: 'category_name', name: 'category_name', title: '@lang('advancedreports::lang.category')' },
                    {
                        data: 'current_stock',
                        name: 'current_stock',
                        title: '@lang('advancedreports::lang.current_stock')',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return formatNumber(data);
                            }
                            return data;
                        }
                    },
                    { data: 'velocity_formatted', name: 'velocity', title: '@lang('advancedreports::lang.velocity')' },
                    { data: 'turnover_formatted', name: 'turnover_ratio', title: '@lang('advancedreports::lang.turnover_ratio')' },
                    { data: 'action_badge', name: 'action', title: '@lang('advancedreports::lang.action')' },
                    {
                        data: 'recommended_qty',
                        name: 'recommended_qty',
                        title: '@lang('advancedreports::lang.recommended_qty')',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return formatNumber(data);
                            }
                            return data;
                        }
                    },
                    { data: 'impact_formatted', name: 'potential_savings', title: '@lang('advancedreports::lang.impact')' },
                    { data: 'reason_short', name: 'reason', title: '@lang('advancedreports::lang.reason')' }
                ],
                pageLength: 25,
                order: [[7, 'desc']], // Sort by impact column descending
                responsive: true
            });
        }

        // Load analytics data
        function loadAnalytics() {
            const dateRange = $('#it_date_range').val().split(' - ');
            const startDate = dateRange[0];
            const endDate = dateRange[1];
            const locationId = $('#it_location_id').val() || 'all';
            const categoryId = $('#it_category_id').val() || 'all';
            const supplierId = $('#it_supplier_id').val() || 'all';

            $.ajax({
                url: '{{ route("advancedreports.inventory-turnover.analytics") }}',
                type: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    location_id: locationId,
                    category_id: categoryId,
                    supplier_id: supplierId
                },
                success: function(data) {
                    updateSummaryCards(data.summary_cards || {});
                    updateMovementClassification(data.movement_classification || {});
                    updateInventoryAging(data.inventory_aging || {});
                    updateStockRecommendations(data.stock_recommendations || {});
                    updatePerformanceTables(data.movement_classification || {});
                },
                error: function(xhr, status, error) {
                    console.error('Error loading analytics:', error);
                }
            });
        }

        // Helper function to format numbers with thousand separators
        function formatNumber(num) {
            return parseFloat(num || 0).toLocaleString(undefined, {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            });
        }

        // Update summary cards
        function updateSummaryCards(data) {
            $('#it_total_products').text(formatNumber(data.total_products || 0));
            $('#it_products_sold').text(formatNumber(data.products_sold || 0) + ' @lang('advancedreports::lang.products_sold')');
            $('#it_avg_turnover').text(data.avg_turnover_ratio || 0);
            $('#it_daily_velocity').text((data.avg_daily_velocity || 0) + ' @lang('advancedreports::lang.units_per_day')');
            $('#it_stock_value').text(data.formatted_stock_value || '0');
            $('#it_stock_qty').text(formatNumber(data.total_stock_qty || 0) + ' @lang('advancedreports::lang.units')');
            $('#it_stock_health').text((data.stock_health_percentage || 0) + '%');
            $('#it_dead_stock').text(formatNumber(data.dead_stock_count || 0) + ' @lang('advancedreports::lang.dead_stock_items')');

            // Update health bar
            const healthPercentage = data.stock_health_percentage || 0;
            $('#it_health_bar').css('width', healthPercentage + '%');

            // Update health bar color based on percentage
            if (healthPercentage >= 70) {
                $('#it_health_bar').removeClass('bg-red bg-yellow').addClass('bg-green');
            } else if (healthPercentage >= 40) {
                $('#it_health_bar').removeClass('bg-red bg-green').addClass('bg-yellow');
            } else {
                $('#it_health_bar').removeClass('bg-green bg-yellow').addClass('bg-red');
            }
        }

        // Update movement classification
        function updateMovementClassification(data) {
            // Movement Classification Chart
            const classificationData = Array.isArray(data.classifications) ? data.classifications : [];
            const labels = classificationData.map(c => c.classification);
            const counts = classificationData.map(c => c.count);
            const colors = ['#28a745', '#ffc107', '#fd7e14', '#dc3545'];

            if (movementClassificationChart) movementClassificationChart.destroy();
            
            const ctx1 = document.getElementById('movementClassificationChart').getContext('2d');
            movementClassificationChart = new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: counts,
                        backgroundColor: colors.slice(0, labels.length),
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
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const classification = classificationData[context.dataIndex];
                                    return `${context.label}: ${context.parsed} products (${Math.round(classification.total_stock_value || 0).toLocaleString()})`;
                                }
                            }
                        }
                    }
                }
            });

            // Category Movement Chart
            const categoryData = Array.isArray(data.category_movement) ? data.category_movement : [];
            const categoryLabels = categoryData.map(c => c.category);
            const fastMovingPercentages = categoryData.map(c => c.fast_moving_percentage);

            if (categoryMovementChart) categoryMovementChart.destroy();
            const categoryCtx = document.getElementById('categoryMovementChart').getContext('2d');
            categoryMovementChart = new Chart(categoryCtx, {
                type: 'bar',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        label: '@lang('advancedreports::lang.fast_moving_percentage')',
                        data: fastMovingPercentages,
                        backgroundColor: '#28a745',
                        borderColor: '#1e7e34',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Update inventory aging
        function updateInventoryAging(data) {
            const agingData = Array.isArray(data.aging_buckets) ? data.aging_buckets : [];
            const agingLabels = agingData.map(a => a.bucket);
            const agingCounts = agingData.map(a => a.count);
            const agingColors = ['#28a745', '#6f42c1', '#ffc107', '#fd7e14', '#dc3545', '#6c757d'];

            if (inventoryAgingChart) inventoryAgingChart.destroy();
            const agingCtx = document.getElementById('inventoryAgingChart').getContext('2d');

            // Handle empty data case
            if (agingLabels.length === 0) {
                inventoryAgingChart = new Chart(agingCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['No Data'],
                        datasets: [{
                            data: [1],
                            backgroundColor: ['#e9ecef'],
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
                            },
                            tooltip: {
                                enabled: false
                            }
                        }
                    }
                });
            } else {
                inventoryAgingChart = new Chart(agingCtx, {
                    type: 'doughnut',
                    data: {
                        labels: agingLabels,
                        datasets: [{
                            data: agingCounts,
                            backgroundColor: agingColors.slice(0, agingLabels.length),
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
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const bucket = agingData[context.dataIndex];
                                        return `${context.label}: ${context.parsed} items (${Math.round(bucket.total_stock_value || 0).toLocaleString()})`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Risk Analysis Chart
            const riskData = Array.isArray(data.risk_analysis) ? data.risk_analysis : [];
            const riskLabels = riskData.map(r => r.risk_level);
            const riskPercentages = riskData.map(r => r.percentage);
            const riskColors = ['#28a745', '#ffc107', '#dc3545', '#6c757d'];

            if (riskAnalysisChart) riskAnalysisChart.destroy();
            const riskCtx = document.getElementById('riskAnalysisChart').getContext('2d');

            // Handle empty data case
            if (riskLabels.length === 0) {
                riskAnalysisChart = new Chart(riskCtx, {
                    type: 'bar',
                    data: {
                        labels: ['No Data'],
                        datasets: [{
                            data: [0],
                            backgroundColor: ['#e9ecef'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        }
                    }
                });
            } else {
                riskAnalysisChart = new Chart(riskCtx, {
                    type: 'bar',
                    data: {
                        labels: riskLabels,
                        datasets: [{
                            data: riskPercentages,
                            backgroundColor: riskColors.slice(0, riskLabels.length),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }

        // Update stock recommendations
        function updateStockRecommendations(data) {
            const actionSummary = Array.isArray(data.action_summary) ? data.action_summary : [];

            // Update action summary cards
            const reorderAction = actionSummary.find(a => a.action === 'REORDER NOW') || {};
            const reduceAction = actionSummary.find(a => a.action === 'REDUCE STOCK') || {};
            const liquidateAction = actionSummary.find(a => a.action === 'LIQUIDATE') || {};

            $('#reorder_count').text(formatNumber(reorderAction.count || 0));
            $('#reorder_value').text(formatNumber(Math.round(Math.abs(reorderAction.potential_impact || 0))) + ' risk');

            $('#reduce_count').text(formatNumber(reduceAction.count || 0));
            $('#reduce_savings').text(formatNumber(Math.round(reduceAction.potential_impact || 0)) + ' potential savings');

            $('#liquidate_count').text(formatNumber(liquidateAction.count || 0));
            $('#liquidate_value').text(formatNumber(Math.round(liquidateAction.total_stock_value || 0)) + ' value');

            // Refresh DataTable
            if (recommendationsTable) {
                recommendationsTable.ajax.reload();
            }
        }


        // Update performance tables
        function updatePerformanceTables(data) {
            // Top performers - ensure it's an array
            let topPerformers = [];
            if (data && data.top_performers && Array.isArray(data.top_performers)) {
                topPerformers = data.top_performers;
            }
            
            let topHtml = '';
            if (topPerformers.length > 0) {
                topPerformers.slice(0, 10).forEach((product, index) => {
                    // Ensure product object has required properties
                    if (product && product.product_name) {
                        topHtml += `
                            <tr>
                                <td>
                                    <strong>${product.product_name || 'N/A'}</strong><br>
                                    <small class="text-muted">${product.product_sku || 'N/A'}</small>
                                </td>
                                <td><span class="badge bg-green">${product.turnover_ratio || 0}</span></td>
                                <td>${(product.velocity || 0).toFixed(2)}/day</td>
                                <td>${formatNumber(product.current_stock || 0)}</td>
                            </tr>
                        `;
                    }
                });
            } else {
                topHtml = '<tr><td colspan="4" class="text-center">@lang('advancedreports::lang.no_fast_moving_products_found')</td></tr>';
            }
            $('#top_performers_tbody').html(topHtml);

            // Worst performers - ensure it's an array
            let worstPerformers = [];
            if (data && data.worst_performers && Array.isArray(data.worst_performers)) {
                worstPerformers = data.worst_performers;
            }
            
            let worstHtml = '';
            if (worstPerformers.length > 0) {
                worstPerformers.slice(0, 10).forEach((product, index) => {
                    // Ensure product object has required properties
                    if (product && product.product_name) {
                        const lastSale = product.last_sale_date ? new Date(product.last_sale_date).toLocaleDateString() : 'Never';
                        worstHtml += `
                            <tr>
                                <td>
                                    <strong>${product.product_name || 'N/A'}</strong><br>
                                    <small class="text-muted">${product.product_sku || 'N/A'}</small>
                                </td>
                                <td>${Math.round(product.stock_value || 0).toLocaleString()}</td>
                                <td>${Math.round(product.days_inventory_outstanding || 0)} days</td>
                                <td><small>${lastSale}</small></td>
                            </tr>
                        `;
                    }
                });
            } else {
                worstHtml = '<tr><td colspan="4" class="text-center">@lang('advancedreports::lang.no_dead_stock_found')</td></tr>';
            }
            $('#worst_performers_tbody').html(worstHtml);
        }

        // Chart toggle functions
        $('#movement_chart_toggle').click(function() {
            if (movementClassificationChart.config.type === 'doughnut') {
                movementClassificationChart.config.type = 'bar';
                movementClassificationChart.options.plugins.legend.position = 'top';
                $(this).html('<i class="fa fa-pie-chart"></i> @lang('advancedreports::lang.pie_chart')');
            } else {
                movementClassificationChart.config.type = 'doughnut';
                movementClassificationChart.options.plugins.legend.position = 'bottom';
                $(this).html('<i class="fa fa-bar-chart"></i> @lang('advancedreports::lang.bar_chart')');
            }
            movementClassificationChart.update();
        });

        $('#category_chart_toggle').click(function() {
            if (categoryMovementChart.config.type === 'bar') {
                categoryMovementChart.config.type = 'pie';
                $(this).html('<i class="fa fa-bar-chart"></i> @lang('advancedreports::lang.bar_chart')');
            } else {
                categoryMovementChart.config.type = 'bar';
                $(this).html('<i class="fa fa-pie-chart"></i> @lang('advancedreports::lang.pie_chart')');
            }
            categoryMovementChart.update();
        });

        // Event handlers
        $('#it_filter_btn').click(function() {
            loadAnalytics();
            if (recommendationsTable) {
                recommendationsTable.ajax.reload();
            }
        });

        // Export button click
        $('#export_btn').click(function(e) {
            e.preventDefault();

            var originalText = $(this).html();
            $(this).html('<i class="fa fa-spinner fa-spin"></i> {{ __('advancedreports::lang.exporting') }}');
            $(this).prop('disabled', true);

            var dateRange = $('#it_date_range').val().split(' - ');
            var data = {
                start_date: dateRange[0],
                end_date: dateRange[1],
                location_id: $('#it_location_id').val(),
                category_id: $('#it_category_id').val(),
                supplier_id: $('#it_supplier_id').val()
            };

            // Create a form and submit it as POST
            var form = $('<form>', {
                'method': 'POST',
                'action': '{{ route("advancedreports.inventory-turnover.export") }}',
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

        // Recommendation filter
        $('#recommendation_filter').change(function() {
            if (recommendationsTable) {
                recommendationsTable.ajax.reload();
            }
        });


        // Initialize DataTable
        initializeRecommendationsTable();

        // Load initial data
        loadAnalytics();
    });
</script>
@endsection