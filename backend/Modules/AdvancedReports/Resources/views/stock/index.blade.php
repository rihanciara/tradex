@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.stock_report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('advancedreports::lang.stock_report')
        <small>@lang('advancedreports::lang.manage_stock_report')</small>
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
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'location_filter']); !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('category_id', __('category.category') . ':') !!}
                    {!! Form::select('category_id', $categories, null, ['placeholder' =>
                    __('advancedreports::lang.all_categories'),
                    'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'category_filter']); !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('exp_date_filter', __('advancedreports::lang.expiry_date') . ':') !!}
                    {!! Form::text('exp_date_filter', null, ['class' => 'form-control', 'id' => 'exp_date_filter',
                    'placeholder' => __('advancedreports::lang.select_date')]) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <br>
                    <label>
                        <input type="checkbox" id="show_zero_stock" value="1">
                        @lang('advancedreports::lang.show_zero_stock')
                    </label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <br>
                    <label>
                        <input type="checkbox" id="stock_need_only" value="1">
                        @lang('advancedreports::lang.stock_need_only')
                    </label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <br>
                    <button type="button" class="btn btn-primary" id="filter_btn">
                        <i class="fa fa-filter"></i> @lang('advancedreports::lang.filter')
                    </button>
                    <br>
                    <button type="button" class="btn btn-success" id="export_btn" style="margin-top: 5px;">
                        <i class="fa fa-download"></i> @lang('lang_v1.export')
                    </button>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Enhanced Summary Cards - 8 widgets in 2 rows -->
    <div class="row" id="summary_cards" style="display: none;">
        <!-- First Row -->
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3 id="total_products">0</h3>
                    <p>@lang('advancedreports::lang.total_products')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-cubes"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="total_stock_qty">0</h3>
                    <p>@lang('advancedreports::lang.total_stock_quantity')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-cube"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-blue">
                <div class="inner">
                    <h3 id="total_stock_value_purchase">0</h3>
                    <p>@lang('advancedreports::lang.total_stock_purchase_value')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-money"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3 id="total_stock_value_sale">0</h3>
                    <p>@lang('advancedreports::lang.total_stock_sales_value')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-line-chart"></i>
                </div>
            </div>
        </div>

        <!-- Second Row -->
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="out_of_stock">0</h3>
                    <p>@lang('advancedreports::lang.out_of_stock')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-orange">
                <div class="inner">
                    <h3 id="low_stock">0</h3>
                    <p>@lang('advancedreports::lang.low_stock')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-warning"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-maroon">
                <div class="inner">
                    <h3 id="expired_stock">0</h3>
                    <p>@lang('advancedreports::lang.expired_stock')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-clock-o"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-xs-12">
            <div class="small-box bg-purple">
                <div class="inner">
                    <h3 id="potential_profit">0</h3>
                    <p>@lang('advancedreports::lang.potential_profit')</p>
                </div>
                <div class="icon">
                    <i class="fa fa-dollar"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabbed Interface for Stock Alerts -->
    <div class="row no-print">
        <div class="col-md-12">
            <!-- Custom Tabs -->
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#stock_report_tab" data-toggle="tab" aria-expanded="true">
                            <i class="fa fa-list" aria-hidden="true"></i> @lang('advancedreports::lang.stock_report')
                        </a>
                    </li>
                    <li>
                        <a href="#product_stock_alert_tab" data-toggle="tab" aria-expanded="false">
                            <i class="fa fa-warning" aria-hidden="true"></i> @lang('home.product_stock_alert')
                        </a>
                    </li>
                    @if (session('business.enable_product_expiry') == 1)
                    <li>
                        <a href="#stock_expiry_alert_tab" data-toggle="tab" aria-expanded="false">
                            <i class="fa fa-clock-o" aria-hidden="true"></i> @lang('home.stock_expiry_alert')
                        </a>
                    </li>
                    <li>
                        <a href="#stock_expiry_report_tab" data-toggle="tab" aria-expanded="false">
                            <i class="fa fa-calendar-times-o" aria-hidden="true"></i>
                            @lang('advancedreports::lang.expiry_report')
                        </a>
                    </li>
                    @endif
                </ul>

                <div class="tab-content">
                    <!-- Main Stock Report Tab -->
                    <div class="tab-pane active" id="stock_report_tab">
                        <div class="row">
                            <div class="col-md-12">
                                @component('components.widget', ['class' => 'box-primary', 'title' =>
                                __('advancedreports::lang.stock_report')])
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped ajax_view" id="stock_report_table">
                                        <thead>
                                            <tr>
                                                <th>@lang('messages.action')</th>
                                                <th>@lang('product.sku')</th>
                                                <th>@lang('sale.product')</th>
                                                <th>@lang('category.category')</th>
                                                <th>@lang('purchase.business_location')</th>
                                                <th>@lang('advancedreports::lang.current_stock')</th>
                                                <th>@lang('advancedreports::lang.selling_price')</th>
                                                <th>@lang('advancedreports::lang.purchase_price')</th>
                                                <th>@lang('advancedreports::lang.stock_value_purchase')</th>
                                                <th>@lang('advancedreports::lang.stock_value_sale')</th>
                                                <th>@lang('advancedreports::lang.potential_profit')</th>
                                                <th>@lang('advancedreports::lang.total_sold')</th>
                                            </tr>
                                        </thead>
                                        <tfoot>
                                            <tr class="bg-gray font-17 text-center footer-total">
                                                <td colspan="8"><strong>@lang('sale.total'):</strong></td>
                                                <td class="footer_total_stock_value_purchase"><span
                                                        class="display_currency" data-currency_symbol="true">0</span>
                                                </td>
                                                <td class="footer_total_stock_value_sale"><span class="display_currency"
                                                        data-currency_symbol="true">0</span></td>
                                                <td class="footer_total_potential_profit"><span class="display_currency"
                                                        data-currency_symbol="true">0</span></td>
                                                <td class="footer_total_sold">0</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                @endcomponent
                            </div>
                        </div>
                    </div>

                    <!-- Product Stock Alert Tab -->
                    <div class="tab-pane" id="product_stock_alert_tab">
                        <div
                            class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                            <div class="tw-p-4 sm:tw-p-5">
                                <div class="tw-flex tw-items-center tw-gap-2.5 tw-mb-4">
                                    <div
                                        class="tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-w-10 tw-h-10">
                                        <svg aria-hidden="true" class="tw-text-yellow-500 tw-size-5 tw-shrink-0"
                                            width="24" height="24" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                                            <path d="M12 8v4"></path>
                                            <path d="M12 16h.01"></path>
                                        </svg>
                                    </div>
                                    <div class="tw-flex tw-items-center tw-flex-1 tw-min-w-0 tw-gap-1">
                                        <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
                                            <h3 class="tw-font-bold tw-text-base lg:tw-text-xl">
                                                {{ __('home.product_stock_alert') }}
                                                @show_tooltip(__('tooltip.product_stock_alert'))
                                            </h3>
                                        </div>
                                        <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2 tw-flex tw-items-center tw-gap-2">
                                            @if (count($all_locations) > 1)
                                            {!! Form::select('stock_alert_location', $all_locations, null, [
                                            'class' => 'form-control select2 tw-flex-grow',
                                            'placeholder' => __('advancedreports::lang.all_locations'),
                                            'id' => 'stock_alert_location',
                                            ]) !!}
                                            @endif

                                            <button id="print_alerte_de_stock" class="btn btn-primary tw-flex-none">
                                                <i class="fa fa-print mr-1"></i>
                                                @lang('messages.print') @lang('advancedreports::lang.stock_need')
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="tw-flow-root tw-border-gray-200">
                                    <div class="tw--mx-4 tw--my-2 tw-overflow-x-auto sm:tw--mx-5">
                                        <div class="tw-inline-block tw-min-w-full tw-py-2 tw-align-middle sm:tw-px-5">
                                            <table class="table table-bordered table-striped" id="stock_alert_table"
                                                style="width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th style="min-width: 300px">@lang('sale.product')</th>
                                                        <th>@lang('business.location')</th>
                                                        <th>@lang('report.current_stock')</th>
                                                        <th>@lang('product.alert_quantity')</th>
                                                        <th>@lang('advancedreports::lang.expected_stock')</th>
                                                        <th>@lang('advancedreports::lang.stock_need')</th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Expiry Alert Tab -->
                    @if (session('business.enable_product_expiry') == 1)
                    <div class="tab-pane" id="stock_expiry_alert_tab">
                        <div
                            class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                            <div class="tw-p-4 sm:tw-p-5">
                                <div class="tw-flex tw-items-center tw-gap-2.5 tw-mb-4">
                                    <div
                                        class="tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-w-10 tw-h-10">
                                        <svg aria-hidden="true" class="tw-text-yellow-500 tw-size-5 tw-shrink-0"
                                            width="24" height="24" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M12 9v4"></path>
                                            <path
                                                d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z">
                                            </path>
                                            <path d="M12 16h.01"></path>
                                        </svg>
                                    </div>
                                    <div class="tw-flex tw-items-center tw-flex-1 tw-min-w-0 tw-gap-1">
                                        <div class="tw-w-full">
                                            <h3 class="tw-font-bold tw-text-base lg:tw-text-xl">
                                                {{ __('home.stock_expiry_alert') }}
                                                @show_tooltip(
                                                __('tooltip.stock_expiry_alert', [
                                                'days'
                                                =>session('business.stock_expiry_alert_days', 30) ]) )
                                            </h3>
                                        </div>
                                    </div>
                                </div>

                                <!-- Enhanced Expiry Alert Summary Cards -->
                                <div class="row" id="expiry_alert_summary" style="margin-bottom: 20px;">
                                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                        <div class="small-box bg-red">
                                            <div class="inner">
                                                <h3 id="alert_expired_count">0</h3>
                                                <p>@lang('advancedreports::lang.expired')</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fa fa-times-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                        <div class="small-box bg-yellow">
                                            <div class="inner">
                                                <h3 id="alert_expiring_soon_count">0</h3>
                                                <p>@lang('advancedreports::lang.expiring_soon')</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fa fa-exclamation-triangle"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                        <div class="small-box bg-blue">
                                            <div class="inner">
                                                <h3 id="alert_total_items">0</h3>
                                                <p>@lang('advancedreports::lang.total_items')</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fa fa-list"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                                        <div class="small-box bg-orange">
                                            <div class="inner">
                                                <h3 id="alert_total_value">0</h3>
                                                <p>@lang('advancedreports::lang.alert_value')</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fa fa-money"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Enhanced Controls -->
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('expiry_alert_days_filter',
                                            __('advancedreports::lang.days_ahead') . ':') !!}
                                            {!! Form::select('expiry_alert_days_filter', [
                                            '7' => __('advancedreports::lang.next_7_days'),
                                            '15' => __('advancedreports::lang.next_15_days'),
                                            '30' => __('advancedreports::lang.next_30_days'),
                                            '60' => __('advancedreports::lang.next_60_days'),
                                            '90' => __('advancedreports::lang.next_90_days'),
                                            ], session('business.stock_expiry_alert_days', 30), ['class' =>
                                            'form-control select2', 'id' => 'expiry_alert_days_filter']); !!}
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            {!! Form::label('expiry_alert_location_filter',
                                            __('purchase.business_location') . ':') !!}
                                            {!! Form::select('expiry_alert_location_filter', $business_locations, null,
                                            ['class' => 'form-control select2', 'id' =>
                                            'expiry_alert_location_filter']); !!}
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <br>
                                            <button type="button" class="btn btn-primary" id="refresh_expiry_alert">
                                                <i class="fa fa-refresh"></i> @lang('advancedreports::lang.refresh')
                                            </button>
                                            <button type="button" class="btn btn-success" id="export_expiry_alert">
                                                <i class="fa fa-download"></i> @lang('lang_v1.export')
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="tw-flow-root tw-border-gray-200">
                                    <div class="tw--mx-4 tw--my-2 tw-overflow-x-auto sm:tw--mx-5">
                                        <div class="tw-inline-block tw-min-w-full tw-py-2 tw-align-middle sm:tw-px-5">
                                            <input type="hidden" id="stock_expiry_alert_days"
                                                value="{{ \Carbon::now()->addDays(session('business.stock_expiry_alert_days', 30))->format('Y-m-d') }}">
                                            <table class="table table-bordered table-striped"
                                                id="stock_expiry_alert_table">
                                                <thead>
                                                    <tr>
                                                        <th>@lang('sale.product')</th>
                                                        <th>@lang('product.sku')</th>
                                                        <th>@lang('business.location')</th>
                                                        <th>@lang('report.stock_left')</th>
                                                        <th>@lang('advancedreports::lang.lot_number')</th>
                                                        <th>@lang('product.exp_date')</th>
                                                        <th>@lang('product.mfg_date')</th>
                                                        <th>@lang('advancedreports::lang.days_left')</th>
                                                        <th>@lang('advancedreports::lang.status')</th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Comprehensive Stock Expiry Report Tab -->
                    @if (session('business.enable_product_expiry') == 1)
                    <div class="tab-pane" id="stock_expiry_report_tab">
                        <div class="row">
                            <div class="col-md-12">
                                <!-- Expiry Filters -->
                                <div class="box box-primary">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">@lang('advancedreports::lang.expiry_filters')</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    {!! Form::label('expiry_location_filter',
                                                    __('purchase.business_location') . ':') !!}
                                                    {!! Form::select('expiry_location_filter', $business_locations,
                                                    null, ['class' => 'form-control select2',
                                                    'style' => 'width:100%', 'id' => 'expiry_location_filter']); !!}
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    {!! Form::label('expiry_category_filter', __('category.category') .
                                                    ':') !!}
                                                    {!! Form::select('expiry_category_filter', $categories, null,
                                                    ['placeholder' =>
                                                    __('advancedreports::lang.all_categories'),
                                                    'class' => 'form-control select2', 'style' => 'width:100%', 'id' =>
                                                    'expiry_category_filter']); !!}
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    {!! Form::label('expiry_status_filter',
                                                    __('advancedreports::lang.expiry_status') . ':') !!}
                                                    {!! Form::select('expiry_status_filter', [
                                                    '' => __('advancedreports::lang.all'),
                                                    'expired' => __('advancedreports::lang.expired'),
                                                    'expiring_7_days' => __('advancedreports::lang.expiring_in_7_days'),
                                                    'expiring_30_days' =>
                                                    __('advancedreports::lang.expiring_in_30_days'),
                                                    'expiring_90_days' =>
                                                    __('advancedreports::lang.expiring_in_90_days'),
                                                    ], null, ['class' => 'form-control select2', 'style' =>
                                                    'width:100%', 'id' => 'expiry_status_filter']); !!}
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <br>
                                                    <button type="button" class="btn btn-primary"
                                                        id="expiry_filter_btn">
                                                        <i class="fa fa-filter"></i>
                                                        @lang('advancedreports::lang.filter')
                                                    </button>
                                                    <button type="button" class="btn btn-success"
                                                        id="expiry_export_btn">
                                                        <i class="fa fa-download"></i> @lang('lang_v1.export')
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Expiry Summary Cards -->
                                <div class="row" id="expiry_summary_cards">
                                    <div class="col-lg-3 col-xs-6">
                                        <div class="small-box bg-red">
                                            <div class="inner">
                                                <h3 id="expired_products_count">0</h3>
                                                <p>@lang('advancedreports::lang.expired_products')</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fa fa-times-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-xs-6">
                                        <div class="small-box bg-yellow">
                                            <div class="inner">
                                                <h3 id="expiring_7_days_count">0</h3>
                                                <p>@lang('advancedreports::lang.expiring_in_7_days')</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fa fa-exclamation-triangle"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-xs-6">
                                        <div class="small-box bg-orange">
                                            <div class="inner">
                                                <h3 id="expiring_30_days_count">0</h3>
                                                <p>@lang('advancedreports::lang.expiring_in_30_days')</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fa fa-clock-o"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-xs-6">
                                        <div class="small-box bg-blue">
                                            <div class="inner">
                                                <h3 id="total_expiry_value">0</h3>
                                                <p>@lang('advancedreports::lang.total_expiry_value')</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fa fa-money"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Expiry Report Table -->
                                @component('components.widget', ['class' => 'box-primary', 'title' =>
                                __('advancedreports::lang.stock_expiry_report')])
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="stock_expiry_report_table">
                                        <thead>
                                            <tr>
                                                <th>@lang('messages.action')</th>
                                                <th>@lang('product.sku')</th>
                                                <th>@lang('sale.product')</th>
                                                <th>@lang('category.category')</th>
                                                <th>@lang('purchase.business_location')</th>
                                                <th>@lang('advancedreports::lang.lot_number')</th>
                                                <th>@lang('product.mfg_date')</th>
                                                <th>@lang('product.exp_date')</th>
                                                <th>@lang('advancedreports::lang.days_to_expire')</th>
                                                <th>@lang('report.current_stock')</th>
                                                <th>@lang('advancedreports::lang.purchase_price')</th>
                                                <th>@lang('advancedreports::lang.stock_value')</th>
                                                <th>@lang('advancedreports::lang.expiry_status')</th>
                                            </tr>
                                        </thead>
                                        <tfoot>
                                            <tr class="bg-gray font-17 text-center footer-total">
                                                <td colspan="11"><strong>@lang('sale.total'):</strong></td>
                                                <td class="footer_total_expiry_value"><span class="display_currency"
                                                        data-currency_symbol="true">0</span></td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                @endcomponent
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade stock_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <!-- Export Progress Modal -->
    <div class="modal fade" id="export_progress_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <i class="fa fa-spinner fa-spin fa-3x text-primary"></i>
                    <h4 class="mt-2">Preparing Export...</h4>
                    <p id="export_message">This may take a few moments for large datasets.</p>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped active" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>
<!-- /.content -->

@stop

@section('javascript')
<script type="text/javascript">
    // Simple currency formatting function - No abbreviations
    function formatCurrency(num) {
        // Use Ultimate POS currency formatting for full amounts
        return __currency_trans_from_en(num.toFixed(2), true);
    }
    
    // Simple number formatting without currency
    function formatNumber(num) {
        return num.toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

    // Load expiry summary data
    @if (session('business.enable_product_expiry') == 1)
    function loadExpirySummary() {
        $.ajax({
            url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\StockReportController@getExpirySummary') !!}",
            data: {
                location_id: $('#expiry_location_filter').val(),
                category_id: $('#expiry_category_filter').val(),
                expiry_status: $('#expiry_status_filter').val()
            },
            dataType: 'json',
            success: function(data) {
                $('#expired_products_count').text(data.expired_count || 0);
                $('#expiring_7_days_count').text(data.expiring_7_days || 0);
                $('#expiring_30_days_count').text(data.expiring_30_days || 0);
                
                var totalValue = parseFloat(data.total_expiry_value || 0);
                $('#total_expiry_value').text(formatCurrency(totalValue));
            },
            error: function() {
                console.error('Error loading expiry summary data:', error);
            }
        });
    }

    // Load expiry alert summary (for simple alert tab)
    function loadExpiryAlertSummary() {
        $.ajax({
            url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\StockReportController@getExpiryAlertSummary') !!}",
            data: {
                location_id: $('#expiry_alert_location_filter').val(),
                expiry_days: $('#expiry_alert_days_filter').val()
            },
            dataType: 'json',
            success: function(data) {
                $('#alert_expired_count').text(data.expired_count || 0);
                $('#alert_expiring_soon_count').text(data.expiring_soon_count || 0);
                $('#alert_total_items').text(data.total_items || 0);
                
                var totalValue = parseFloat(data.total_value || 0);
                $('#alert_total_value').text(formatCurrency(totalValue));
            },
            error: function() {
                console.error('Error loading expiry alert summary data:', error);
            }
        });
    }
    @endif

    $(document).ready(function() {
    // Initialize date picker for expiry filter
    $('#exp_date_filter').datepicker({
        autoclose: true,
        format: 'yyyy-mm-dd'
    });

    // Initialize DataTable
    var stock_report_table = $('#stock_report_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\StockReportController@getStockData') !!}",
            data: function (d) {
                d.location_id = $('#location_filter').val();
                d.category_id = $('#category_filter').val();
                d.show_zero_stock = $('#show_zero_stock').is(':checked') ? 1 : 0;
                d.exp_date_filter = $('#exp_date_filter').val();
                d.stock_need_only = $('#stock_need_only').is(':checked') ? 1 : 0;
            }
        },
        columns: [
            { data: 'action', name: 'action', orderable: false, searchable: false },
            { data: 'sku', name: 'variations.sub_sku' },
            { data: 'product_name', name: 'products.name' },
            { data: 'category_name', name: 'categories.name' },
            { data: 'location_name', name: 'bl.name' },
            {
                data: 'current_stock',
                name: 'current_stock',
                searchable: false,
                render: function(data, type, row) {
                    if (type === 'display') {
                        return parseFloat(data).toLocaleString(undefined, {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 2
                        });
                    }
                    return data;
                }
            },
            { data: 'default_sell_price', name: 'variations.default_sell_price', searchable: false },
            { data: 'default_purchase_price', name: 'variations.default_purchase_price', searchable: false },
            { data: 'stock_value_purchase', name: 'stock_value_purchase', searchable: false },
            { data: 'stock_value_sale', name: 'stock_value_sale', searchable: false },
            { data: 'potential_profit', name: 'potential_profit', searchable: false },
            {
                data: 'total_sold',
                name: 'total_sold',
                searchable: false,
                render: function(data, type, row) {
                    if (type === 'display') {
                        return parseFloat(data).toLocaleString(undefined, {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 2
                        });
                    }
                    return data;
                }
            }
        ],
        order: [[2, 'asc']], // Sort by product name
        "fnDrawCallback": function (oSettings) {
            __currency_convert_recursively($('#stock_report_table'));
            
            // Calculate and update footer totals
            var api = this.api();
            
            // Extract numeric values from formatted currency
            var total_stock_value_purchase = 0;
            var total_stock_value_sale = 0;
            var total_potential_profit = 0;
            
            api.column(8, {page: 'current'}).data().each(function(value) {
                var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                total_stock_value_purchase += num;
            });
            
            api.column(9, {page: 'current'}).data().each(function(value) {
                var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                total_stock_value_sale += num;
            });
            
            api.column(10, {page: 'current'}).data().each(function(value) {
                var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                total_potential_profit += num;
            });

            $('.footer_total_stock_value_purchase').html('<span class="display_currency" data-currency_symbol="true">' + total_stock_value_purchase.toFixed(2) + '</span>');
            $('.footer_total_stock_value_sale').html('<span class="display_currency" data-currency_symbol="true">' + total_stock_value_sale.toFixed(2) + '</span>');
            $('.footer_total_potential_profit').html('<span class="display_currency" data-currency_symbol="true">' + total_potential_profit.toFixed(2) + '</span>');
            
            __currency_convert_recursively($('.footer-total'));
        },
        createdRow: function( row, data, dataIndex ) {
            $(row).find('td:eq(5)').addClass('text-right');
            $(row).find('td:eq(6)').addClass('text-right');
            $(row).find('td:eq(7)').addClass('text-right');
            $(row).find('td:eq(8)').addClass('text-right');
            $(row).find('td:eq(9)').addClass('text-right');
            $(row).find('td:eq(10)').addClass('text-right');
            $(row).find('td:eq(11)').addClass('text-center');
        }
    });

    // Initialize Stock Alert DataTable
    var stock_alert_table = $('#stock_alert_table').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        searching: false,
        scrollY: "75vh",
        scrollX: true,
        scrollCollapse: true,
        fixedHeader: false,
        ajax: {
            url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\StockReportController@getProductStockAlert') !!}",
            data: function (d) {
                if ($('#stock_alert_location').length > 0) {
                    d.location_id = $('#stock_alert_location').val();
                }
            }
        },
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#stock_alert_table'));
        },
    });

    // Initialize Stock Expiry Alert DataTable (Enhanced)
    @if (session('business.enable_product_expiry') == 1)
    var stock_expiry_alert_table = $('#stock_expiry_alert_table').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        scrollY: "75vh",
        scrollX: true,
        scrollCollapse: true,
        fixedHeader: false,
        ajax: {
            url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\StockReportController@getStockExpiryAlert') !!}",
            data: function(d) {
                d.expiry_days = $('#expiry_alert_days_filter').val();
                d.location_id = $('#expiry_alert_location_filter').val();
            },
        },
        order: [[7, 'asc']], // Sort by days left
        columns: [
            { data: 'product', name: 'p.name' },
            { data: 'sku', name: 'sku' },
            { data: 'location', name: 'l.name' },
            { data: 'stock_left', name: 'stock_left' },
            { data: 'lot_number', name: 'lot_number' },
            { data: 'exp_date', name: 'exp_date' },
            { data: 'mfg_date', name: 'mfg_date' },
            { data: 'days_left', name: 'days_left', orderable: false },
            { data: 'status', name: 'status', orderable: false },
        ],
        fnDrawCallback: function(oSettings) {
            __show_date_diff_for_human($('#stock_expiry_alert_table'));
            __currency_convert_recursively($('#stock_expiry_alert_table'));
        },
        createdRow: function( row, data, dataIndex ) {
            // Add row styling based on expiry status
            if (data.days_left && data.days_left.includes('overdue') || data.days_left.includes('expires_today')) {
                $(row).addClass('danger');
            } else if (data.days_left && data.days_left.includes('label-danger')) {
                $(row).addClass('warning');
            }
        }
    });
    @endif

    // Initialize Stock Expiry Report DataTable
    @if (session('business.enable_product_expiry') == 1)
    var stock_expiry_report_table = $('#stock_expiry_report_table').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: {
            url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\StockReportController@getStockExpiryReport') !!}",
            data: function(d) {
                d.location_id = $('#expiry_location_filter').val();
                d.category_id = $('#expiry_category_filter').val();
                d.expiry_status = $('#expiry_status_filter').val();
            },
        },
        order: [[8, 'asc']], // Sort by days to expire
        columns: [
            { data: 'action', name: 'action', orderable: false, searchable: false },
            { data: 'sku', name: 'sku' },
            { data: 'product', name: 'product' },
            { data: 'category', name: 'category' },
            { data: 'location', name: 'location' },
            { data: 'lot_number', name: 'lot_number' },
            { data: 'mfg_date', name: 'mfg_date' },
            { data: 'exp_date', name: 'exp_date' },
            { data: 'days_to_expire', name: 'days_to_expire' },
            {
                data: 'stock_left',
                name: 'stock_left',
                render: function(data, type, row) {
                    if (type === 'display') {
                        return parseFloat(data).toLocaleString(undefined, {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 2
                        });
                    }
                    return data;
                }
            },
            { data: 'purchase_price', name: 'purchase_price' },
            { data: 'stock_value', name: 'stock_value' },
            { data: 'expiry_status', name: 'expiry_status', orderable: false }
        ],
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#stock_expiry_report_table'));
            
            // Calculate and update footer totals
            var api = this.api();
            var total_expiry_value = 0;
            
            api.column(11, {page: 'current'}).data().each(function(value) {
                var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                total_expiry_value += num;
            });

            $('.footer_total_expiry_value').html('<span class="display_currency" data-currency_symbol="true">' + total_expiry_value.toFixed(2) + '</span>');
            __currency_convert_recursively($('.footer-total'));
        },
        createdRow: function( row, data, dataIndex ) {
            $(row).find('td:eq(9)').addClass('text-right');
            $(row).find('td:eq(10)').addClass('text-right');
            $(row).find('td:eq(11)').addClass('text-right');
        }
    });
    @endif

    // Filter button click
    $('#filter_btn').click(function() {
        stock_report_table.ajax.reload();
        loadSummary();
    });

    // Export button functionality
    $('#export_btn').click(function(e) {
        e.preventDefault();

        var $btn = $(this);
        var originalText = $btn.html();

        // Show loading state
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Exporting...').prop('disabled', true);

        var data = {
            location_id: $('#location_filter').val(),
            category_id: $('#category_filter').val(),
            show_zero_stock: $('#show_zero_stock').is(':checked') ? 1 : 0
        };

        // Create a form and submit it as POST
        var form = $('<form>', {
            'method': 'POST',
            'action': '{!! action("\\Modules\\AdvancedReports\\Http\\Controllers\\StockReportController@export") !!}',
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
            if (value !== null && value !== '') {
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': key,
                    'value': value
                }));
            }
        });

        // Submit the form
        form.appendTo('body').submit().remove();

        // Reset button
        setTimeout(function() {
            $btn.html(originalText).prop('disabled', false);
        }, 3000);
    });

    // Load enhanced summary data with new widgets
    function loadSummary() {
        $.ajax({
            url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\StockReportController@getSummary') !!}",
            data: {
                location_id: $('#location_filter').val(),
                category_id: $('#category_filter').val()
            },
            dataType: 'json',
            success: function(data) {
                // Format numbers without abbreviations
                $('#total_products').text(data.total_products || 0);
                
                var stockQty = parseFloat(data.total_stock_qty || 0);
                $('#total_stock_qty').text(formatNumber(stockQty));
                
                var stockValuePurchase = parseFloat(data.total_stock_value_purchase || 0);
                $('#total_stock_value_purchase').text(formatCurrency(stockValuePurchase));
                
                var stockValueSale = parseFloat(data.total_stock_value_sale || 0);
                $('#total_stock_value_sale').text(formatCurrency(stockValueSale));
                
                var profit = parseFloat(data.total_potential_profit || 0);
                $('#potential_profit').text(formatCurrency(profit));
                
                $('#out_of_stock').text(data.out_of_stock || 0);
                $('#low_stock').text(data.low_stock || 0);
                $('#expired_stock').text(data.expired_stock || 0);
                
                $('#summary_cards').show();
            },
            error: function() {
                console.error('Error loading summary data:', error);
            }
        });
    }

    // Load initial summary
    loadSummary();

    // Modal for stock details
    $(document).on('click', 'button.btn-modal', function(e) {
        e.preventDefault();
        var container = $(this).data('container');
        $.get($(this).data('href'), function(data) {
            $(container).html(data).modal('show');
        });
    });

    // Auto-filter on checkbox/input changes
    $('#show_zero_stock, #stock_need_only').change(function() {
        stock_report_table.ajax.reload();
        loadSummary();
    });

    $('#exp_date_filter').change(function() {
        stock_report_table.ajax.reload();
        loadSummary();
    });

    // Handle stock alert location change
    $('#stock_alert_location').change(function() {
        stock_alert_table.ajax.reload();
    });

    // Enhanced expiry alert controls
    @if (session('business.enable_product_expiry') == 1)
    $('#refresh_expiry_alert, #expiry_alert_days_filter, #expiry_alert_location_filter').on('click change', function() {
        stock_expiry_alert_table.ajax.reload();
        loadExpiryAlertSummary();
    });

    // Export expiry alert functionality
    $('#export_expiry_alert').click(function(e) {
        e.preventDefault();
        
        var expiry_days = $('#expiry_alert_days_filter').val();
        var location_id = $('#expiry_alert_location_filter').val();
        
        var $btn = $(this);
        var originalText = $btn.html();
        
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Exporting...').prop('disabled', true);
        
        var url = "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\StockReportController@exportExpiryAlert') !!}";
        url += '?expiry_days=' + expiry_days + '&location_id=' + location_id;
        
        var iframe = $('<iframe>').hide().appendTo('body');
        iframe.attr('src', url);
        
        setTimeout(function() {
            $btn.html(originalText).prop('disabled', false);
            iframe.remove();
        }, 10000);
    });
    @endif
    $('#expiry_filter_btn').click(function() {
        @if (session('business.enable_product_expiry') == 1)
        stock_expiry_report_table.ajax.reload();
        loadExpirySummary();
        @endif
    });

    $('#expiry_location_filter, #expiry_category_filter, #expiry_status_filter').change(function() {
        @if (session('business.enable_product_expiry') == 1)
        stock_expiry_report_table.ajax.reload();
        loadExpirySummary();
        @endif
    });

    // Expiry export functionality
    $('#expiry_export_btn').click(function(e) {
        e.preventDefault();

        var $btn = $(this);
        var originalText = $btn.html();

        $btn.html('<i class="fa fa-spinner fa-spin"></i> Exporting...').prop('disabled', true);

        var data = {
            location_id: $('#expiry_location_filter').val(),
            category_id: $('#expiry_category_filter').val(),
            expiry_status: $('#expiry_status_filter').val()
        };

        // Create a form and submit it as POST
        var form = $('<form>', {
            'method': 'POST',
            'action': '{!! action("\\Modules\\AdvancedReports\\Http\\Controllers\\StockReportController@exportExpiryReport") !!}',
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
            if (value !== null && value !== '') {
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': key,
                    'value': value
                }));
            }
        });

        // Submit the form
        form.appendTo('body').submit().remove();

        // Reset button
        setTimeout(function() {
            $btn.html(originalText).prop('disabled', false);
        }, 3000);
    });

    // Tab change handlers to reload data when needed
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href");
        if (target == "#product_stock_alert_tab") {
            stock_alert_table.ajax.reload();
        }
        @if (session('business.enable_product_expiry') == 1)
        else if (target == "#stock_expiry_alert_tab") {
            stock_expiry_alert_table.ajax.reload();
            loadExpiryAlertSummary();
        }
        else if (target == "#stock_expiry_report_tab") {
            stock_expiry_report_table.ajax.reload();
            loadExpirySummary();
        }
        @endif
    });

    // Load initial summaries if expiry is enabled
    @if (session('business.enable_product_expiry') == 1)
    loadExpirySummary();
    loadExpiryAlertSummary();
    @endif
});
</script>

<!-- Print functionality script -->
<script>
    $(document).ready(function(){
    // Configuration object for easy customization
    const printConfig = {
        title: '{{ __("advancedreports::lang.stock_need") }}',
        columns: {
            product: {
                index: 0,
                label: '{{ __("sale.product") }}',
                enabled: true
            },
            location: {
                index: 1,
                label: '{{ __("business.location") }}',
                enabled: true
            },
            currentStock: {
                index: 2,
                label: '{{ __("report.current_stock") }}',
                enabled: true
            },
            alertQuantity: {
                index: 3,
                label: '{{ __("product.alert_quantity") }}',
                enabled: true
            },
            expectedStock: {
                index: 4,
                label: '{{ __("advancedreports::lang.expected_stock") }}',
                enabled: true
            },
            stockNeed: {
                index: 5,
                label: '{{ __("advancedreports::lang.stock_need") }}',
                enabled: true
            }
        },
        styles: {
            pageMargin: '10mm',
            fontSize: '12px',
            headerFontSize: '16px',
            tableFontSize: '11px'
        }
    };

    $('#print_alerte_de_stock').click(function(){
        // Validate table data
        if (!validateTableData()) {
            return;
        }

        // Show loading state
        const originalText = $(this).html();
        $(this).html('<i class="fa fa-spinner fa-spin mr-1"></i> {{ __("advancedreports::lang.processing") }}...');
        $(this).prop('disabled', true);

        // Generate print window
        setTimeout(() => {
            generatePrintWindow();

            // Reset button state
            $(this).html(originalText);
            $(this).prop('disabled', false);
        }, 500);
    });

    function validateTableData() {
        const tableBody = $('#stock_alert_table tbody');

        if (tableBody.length === 0) {
            alert('{{ __("advancedreports::lang.table_not_found") }}');
            return false;
        }

        const rows = tableBody.find('tr');
        if (rows.length === 0 || rows.find('td:contains("{{ __("advancedreports::lang.no_data_available") }}")').length > 0) {
            alert('{{ __("advancedreports::lang.no_data_available_print") }}');
            return false;
        }

        return true;
    }

    function generatePrintWindow() {
        const printWindow = window.open('', '_blank', 'width=800,height=600');

        if (!printWindow) {
            alert('{{ __("advancedreports::lang.popup_blocked") }}');
            return;
        }

        const printContent = generatePrintContent();
        printWindow.document.write(printContent);
        printWindow.document.close();

        printWindow.onload = function() {
            printWindow.focus();
            printWindow.print();
        };
    }

    function generatePrintContent() {
        const currentDate = moment().format('LLLL');
        const businessName = '{{ session("business.name") }}';
        const selectedLocation = getSelectedLocation();

        return `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>${printConfig.title}</title>
                ${generatePrintStyles()}
            </head>
            <body>
                <div class="print-container">
                    ${generatePrintHeader(businessName, selectedLocation)}
                    ${generatePrintTable()}
                    ${generatePrintFooter(currentDate)}
                </div>
            </body>
            </html>
        `;
    }

    function generatePrintStyles() {
        return `
            <style>
                @media print {
                    @page {
                        margin: ${printConfig.styles.pageMargin};
                        size: A4;
                    }
                    body {
                        -webkit-print-color-adjust: exact !important;
                        color-adjust: exact !important;
                    }
                }

                body {
                    margin: 0;
                    padding: 0;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    font-size: ${printConfig.styles.fontSize};
                    line-height: 1.4;
                    color: #333;
                }

                .print-container {
                    max-width: 100%;
                    margin: 0 auto;
                    padding: 20px;
                }

                .print-header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 2px solid #2563eb;
                    padding-bottom: 15px;
                }

                .business-name {
                    font-size: 20px;
                    font-weight: bold;
                    color: #1e40af;
                    margin-bottom: 5px;
                }

                .report-title {
                    font-size: ${printConfig.styles.headerFontSize};
                    font-weight: bold;
                    color: #374151;
                    margin: 10px 0 5px 0;
                }

                .report-subtitle {
                    font-size: 12px;
                    color: #6b7280;
                    margin-bottom: 15px;
                }

                .report-filters {
                    font-size: 11px;
                    color: #4b5563;
                    margin-top: 10px;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                    background: white;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }

                th {
                    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                    color: white;
                    font-weight: 600;
                    padding: 12px 8px;
                    text-align: left;
                    font-size: ${printConfig.styles.tableFontSize};
                    border: 1px solid #1e40af;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                td {
                    padding: 10px 8px;
                    border: 1px solid #e5e7eb;
                    font-size: ${printConfig.styles.tableFontSize};
                    vertical-align: top;
                }

                tbody tr:nth-child(even) {
                    background-color: #f8fafc;
                }

                tbody tr:hover {
                    background-color: #e0f2fe;
                }

                .stock-need-highlight {
                    font-weight: bold;
                    color: #dc2626;
                }

                .print-footer {
                    margin-top: 30px;
                    padding-top: 15px;
                    border-top: 1px solid #e5e7eb;
                    text-align: center;
                    font-size: 10px;
                    color: #6b7280;
                }

                .summary-stats {
                    margin: 20px 0;
                    padding: 15px;
                    background: #f1f5f9;
                    border-radius: 8px;
                    border: 1px solid #cbd5e1;
                }

                .stat-item {
                    display: inline-block;
                    margin-right: 30px;
                    font-size: 11px;
                }

                .stat-label {
                    font-weight: bold;
                    color: #475569;
                }

                .stat-value {
                    color: #1e40af;
                    font-weight: bold;
                }
            </style>
        `;
    }

    function generatePrintHeader(businessName, selectedLocation) {
        const currentDate = moment().format('MMMM DD, YYYY');
        const currentTime = moment().format('h:mm A');

        // Company Logo Code - Add this section
        const logoUrl = '{{ asset("uploads/business_logos/" . session("business.logo")) }}';
        const logoHtml = logoUrl && '{{ session("business.logo") }}' ?
            `<img src="${logoUrl}" alt="Company Logo" style="max-height: 60px; margin-bottom: 10px;">` : '';

        return `
            <div class="print-header">
                ${logoHtml}
                <div class="business-name">${businessName}</div>
                <div class="report-title">${printConfig.title}</div>
                <div class="report-subtitle">{{ __('advancedreports::lang.generated_on') }}: ${currentDate} {{ __('advancedreports::lang.at') }} ${currentTime}</div>
                ${selectedLocation ? `<div class="report-filters">{{ __('business.location') }}: ${selectedLocation}</div>` : ''}
            </div>
        `;
    }

    function generatePrintTable() {
        let tableHtml = '<table><thead><tr>';

        // Generate table headers for enabled columns
        Object.keys(printConfig.columns).forEach(key => {
            const column = printConfig.columns[key];
            if (column.enabled) {
                tableHtml += `<th>${column.label}</th>`;
            }
        });

        tableHtml += '</tr></thead><tbody>';

        // Generate table rows
        let totalProducts = 0;
        let totalStockNeed = 0;
        let totalCurrentStock = 0;
        let totalAlertQuantity = 0;
        let criticalItems = 0;

        $('#stock_alert_table tbody tr').each(function() {
            const row = $(this);
            if (row.find('td').length > 0) {
                tableHtml += '<tr>';

                Object.keys(printConfig.columns).forEach(key => {
                    const column = printConfig.columns[key];
                    if (column.enabled) {
                        let cellContent = row.find('td').eq(column.index).html() || '';

                        // Extract numeric values for calculations
                        let numericValue = 0;
                        if (cellContent.includes('<span')) {
                            const match = cellContent.match(/>([\d.,-]+)</);
                            if (match) {
                                numericValue = parseFloat(match[1].replace(/[^\d.-]/g, '')) || 0;
                            }
                        } else {
                            numericValue = parseFloat(cellContent.replace(/[^\d.-]/g, '')) || 0;
                        }

                        // Special formatting and calculations based on column
                        if (key === 'stockNeed') {
                            totalStockNeed += numericValue;
                            if (numericValue > 0) criticalItems++;
                            cellContent = `<span class="stock-need-highlight">${cellContent}</span>`;
                        } else if (key === 'currentStock') {
                            totalCurrentStock += numericValue;
                        } else if (key === 'alertQuantity') {
                            totalAlertQuantity += numericValue;
                        }

                        tableHtml += `<td>${cellContent}</td>`;
                    }
                });

                tableHtml += '</tr>';
                totalProducts++;
            }
        });

        tableHtml += '</tbody></table>';

        // Add enhanced summary statistics
        const summaryHtml = `
            <div class="summary-stats">
                <div class="stat-item">
                    <span class="stat-label">{{ __('advancedreports::lang.total_products') }}:</span>
                    <span class="stat-value">${totalProducts}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">{{ __('advancedreports::lang.critical_items') }}:</span>
                    <span class="stat-value">${criticalItems}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">{{ __('advancedreports::lang.total_current_stock') }}:</span>
                    <span class="stat-value">${totalCurrentStock.toFixed(2)}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">{{ __('advancedreports::lang.total_stock_needed') }}:</span>
                    <span class="stat-value">${totalStockNeed.toFixed(2)}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">{{ __('advancedreports::lang.coverage_ratio') }}:</span>
                    <span class="stat-value">${totalAlertQuantity > 0 ? ((totalCurrentStock / totalAlertQuantity) * 100).toFixed(1) + '%' : 'N/A'}</span>
                </div>
            </div>
        `;

        return summaryHtml + tableHtml;
    }

    function generatePrintFooter(currentDate) {
        return `
            <div class="print-footer">
                <p>{{ __('advancedreports::lang.printed_on') }}: ${currentDate}</p>
                <p>{{ __('advancedreports::lang.generated_by') }}: {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                <p>{{ __('advancedreports::lang.system_generated_report') }}</p>
            </div>
        `;
    }

    function getSelectedLocation() {
        const locationSelect = $('#stock_alert_location');
        if (locationSelect.length > 0 && locationSelect.val()) {
            return locationSelect.find('option:selected').text();
        }
        return null;
    }
});
</script>

<style>
    /* Perfect responsive widget styling - Simple & Clean */
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

    .small-box .inner small {
        font-size: 10px !important;
        color: rgba(255, 255, 255, 0.7) !important;
        margin-top: 2px !important;
        opacity: 0 !important;
        transition: opacity 0.3s ease !important;
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

    .small-box:hover .inner small {
        opacity: 1 !important;
    }

    /* Responsive font sizing */
    @media (max-width: 1200px) {
        .small-box .inner h3 {
            font-size: 28px !important;
        }

        .small-box .icon {
            font-size: 40px !important;
        }
    }

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

    @media (max-width: 480px) {
        .small-box {
            min-height: 100px !important;
            height: 100px !important;
        }

        .small-box .inner h3 {
            font-size: 20px !important;
        }

        .small-box .inner p {
            font-size: 10px !important;
        }

        .small-box .icon {
            font-size: 25px !important;
        }
    }

    /* Enhanced colors for new widgets */
    .small-box.bg-aqua {
        background-color: #3498db !important;
    }

    .small-box.bg-green {
        background-color: #2ecc71 !important;
    }

    .small-box.bg-blue {
        background-color: #3f51b5 !important;
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

    .small-box.bg-maroon {
        background-color: #8e44ad !important;
    }

    .small-box.bg-purple {
        background-color: #9b59b6 !important;
    }

    /* Simple hover effects */
    .small-box:hover.bg-aqua {
        background-color: #2980b9 !important;
    }

    .small-box:hover.bg-green {
        background-color: #27ae60 !important;
    }

    .small-box:hover.bg-blue {
        background-color: #303f9f !important;
    }

    .small-box:hover.bg-yellow {
        background-color: #d68910 !important;
    }

    .small-box:hover.bg-red {
        background-color: #c0392b !important;
    }

    .small-box:hover.bg-orange {
        background-color: #d35400 !important;
    }

    .small-box:hover.bg-maroon {
        background-color: #7b1fa2 !important;
    }

    .small-box:hover.bg-purple {
        background-color: #8e44ad !important;
    }

    /* Row spacing for better layout - Simple spacing */
    #summary_cards {
        margin-bottom: 25px !important;
        padding: 0 !important;
    }

    #summary_cards .row {
        margin-left: -10px !important;
        margin-right: -10px !important;
    }

    #summary_cards [class*="col-"] {
        padding-left: 10px !important;
        padding-right: 10px !important;
    }
</style>
@endsection