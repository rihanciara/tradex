@extends('advancedreports::layouts.app')

@section('title', __('advancedreports::lang.product_performance_report'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('advancedreports::lang.product_performance_report') }}</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('home') }}"><i class="fa fa-dashboard"></i> {{ __('home.home') }}</a></li>
        <li><a href="{{ route('advancedreports.index') }}">{{ __('advancedreports::lang.advanced_reports') }}</a></li>
        <li class="active">{{ __('advancedreports::lang.product_performance_report') }}</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('report.filters')])
    @slot('tool')
    <button type="button" class="btn btn-box-tool" data-widget="collapse">
        <i class="fa fa-minus"></i>
    </button>
    @endslot

    <form id="product_report_filter_form">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="product_date_filter">{{ __('report.date_range') }}:</label>
                    <div class="dropdown date-filter-dropdown">
                        <button type="button" id="product_date_filter_btn"
                            class="btn btn-default dropdown-toggle form-control text-left" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-calendar"></i>
                            <span id="product_date_filter_text">{{ __('lang_v1.last_30_days') }}</span>
                            <span class="caret pull-right" style="margin-top: 8px;"></span>
                        </button>
                        <ul class="dropdown-menu" style="width: 100%;">
                            <li><a href="#" data-range="today">{{ __('advancedreports::lang.today') }}</a></li>
                            <li><a href="#" data-range="yesterday">{{ __('advancedreports::lang.yesterday') }}</a></li>
                            <li class="divider"></li>
                            <li><a href="#" data-range="this_week">{{ __('advancedreports::lang.this_week') }}</a></li>
                            <li><a href="#" data-range="last_week">{{ __('advancedreports::lang.last_week') }}</a></li>
                            <li class="divider"></li>
                            <li><a href="#" data-range="this_month">{{ __('advancedreports::lang.this_month') }}</a>
                            </li>
                            <li><a href="#" data-range="last_month">{{ __('advancedreports::lang.last_month') }}</a>
                            </li>
                            <li><a href="#" data-range="last_30_days">{{ __('advancedreports::lang.last_30_days') }}</a>
                            </li>
                            <li class="divider"></li>
                            <li><a href="#" data-range="this_quarter">{{ __('advancedreports::lang.this_quarter') }}</a>
                            </li>
                            <li><a href="#" data-range="last_quarter">{{ __('advancedreports::lang.last_quarter') }}</a>
                            </li>
                            <li class="divider"></li>
                            <li><a href="#" data-range="this_year">{{ __('advancedreports::lang.this_year') }}</a></li>
                            <li><a href="#" data-range="last_year">{{ __('advancedreports::lang.last_year') }}</a></li>
                            <li class="divider"></li>
                            <li><a href="#" data-range="custom">{{ __('advancedreports::lang.custom_range') }}</a></li>
                        </ul>
                    </div>
                    <!-- Hidden date inputs for storing selected range -->
                    <input type="hidden" id="start_date" name="start_date"
                        value="{{ date('Y-m-d', strtotime('-30 days')) }}" />
                    <input type="hidden" id="end_date" name="end_date" value="{{ date('Y-m-d') }}" />
                    <!-- Hidden daterangepicker input for custom range -->
                    <input type="text" id="custom_date_range" class="form-control" style="display: none;" />
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="location_id">{{ __('business.business_location') }}:</label>
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                    'placeholder' => __('lang_v1.all'), 'id' => 'location_id']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="customer_id">{{ __('contact.customer') }}:</label>
                    {!! Form::select('customer_id', $customers, null, ['class' => 'form-control select2', 'placeholder'
                    => __('lang_v1.all'), 'id' => 'customer_id']) !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="customer_group_id">{{ __('lang_v1.customer_group') }}:</label>
                    {!! Form::select('customer_group_id', $customer_groups, null, ['class' => 'form-control select2',
                    'placeholder' => __('lang_v1.all'), 'id' => 'customer_group_id']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="category_id">{{ __('category.category') }}:</label>
                    {!! Form::select('category_id', $categories, null, ['class' => 'form-control select2', 'placeholder'
                    => __('lang_v1.all'), 'id' => 'category_id']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="brand_id">{{ __('product.brand') }}:</label>
                    {!! Form::select('brand_id', $brands, null, ['class' => 'form-control select2', 'placeholder' =>
                    __('lang_v1.all'), 'id' => 'brand_id']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="unit_id">{{ __('product.unit') }}:</label>
                    {!! Form::select('unit_id', $units, null, ['class' => 'form-control select2', 'placeholder' =>
                    __('lang_v1.all'), 'id' => 'unit_id']) !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="user_id">{{ __('report.user') }}:</label>
                    {!! Form::select('user_id', $users, null, ['class' => 'form-control select2', 'placeholder' =>
                    __('lang_v1.all'), 'id' => 'user_id']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="payment_method">{{ __('lang_v1.payment_method') }}:</label>
                    {!! Form::select('payment_method', $payment_types, null, ['class' => 'form-control select2',
                    'placeholder' => __('lang_v1.all'), 'id' => 'payment_method']) !!}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>&nbsp;</label><br>
                    <button type="button" class="btn btn-primary" id="apply_filters">
                        <i class="fa fa-filter"></i> {{ __('advancedreports::lang.apply_filters') }}
                    </button>
                    <button type="button" class="btn btn-default" id="clear_filters">
                        <i class="fa fa-refresh"></i> {{ __('advancedreports::lang.clear_filter') }}
                    </button>
                    <button type="button" class="btn btn-success" id="export_report">
                        <i class="fa fa-download"></i> {{ __('lang_v1.export') }}
                    </button>
                    <button type="button" class="btn btn-info" id="refresh_report">
                        <i class="fa fa-refresh"></i> {{ __('lang_v1.refresh') }}
                    </button>
                    <div class="checkbox" style="display: inline-block; margin-left: 10px;">
                        <label>
                            <input type="checkbox" id="auto_refresh"> {{ __('advancedreports::lang.auto_refresh') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endcomponent

    <!-- Summary Widgets -->

    <div class="preview-section" style="background: #f8fafc; padding: 20px; margin: 20px 0; border-radius: 8px;">

        <!-- Row 1: First 4 Widgets -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 15px;">
            <!-- Total Products -->
            <div
                style="background: linear-gradient(45deg, #667eea 0%, #764ba2 100%); height: 90px; display: flex; align-items: center; padding: 15px; border-radius: 8px; color: white; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); transition: transform 0.2s;">
                <i class="fa fa-cubes"
                    style="font-size: 40px; margin-right: 15px; width: 50px; text-align: center; opacity: 0.9;"></i>
                <div>
                    <div
                        style="font-size: 12px; text-transform: uppercase; margin-bottom: 4px; font-weight: 500; letter-spacing: 0.5px;">
                        TOTAL PRODUCTS</div>
                    <div style="font-size: 22px; font-weight: bold;" id="total_products">1</div>
                </div>
            </div>

            <!-- Total Sales -->
            <div
                style="background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%); height: 90px; display: flex; align-items: center; padding: 15px; border-radius: 8px; color: white; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); transition: transform 0.2s;">
                <i class="fa fa-shopping-cart"
                    style="font-size: 40px; margin-right: 15px; width: 50px; text-align: center; opacity: 0.9;"></i>
                <div>
                    <div
                        style="font-size: 12px; text-transform: uppercase; margin-bottom: 4px; font-weight: 500; letter-spacing: 0.5px;">
                        TOTAL SALES</div>
                    <div style="font-size: 22px; font-weight: bold;" id="total_sales_amount">$375.00</div>
                </div>
            </div>

            <!-- Total Customers -->
            <div
                style="background: linear-gradient(45deg, #4facfe 0%, #00f2fe 100%); height: 90px; display: flex; align-items: center; padding: 15px; border-radius: 8px; color: white; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); transition: transform 0.2s;">
                <i class="fa fa-users"
                    style="font-size: 40px; margin-right: 15px; width: 50px; text-align: center; opacity: 0.9;"></i>
                <div>
                    <div
                        style="font-size: 12px; text-transform: uppercase; margin-bottom: 4px; font-weight: 500; letter-spacing: 0.5px;">
                        TOTAL CUSTOMERS</div>
                    <div style="font-size: 22px; font-weight: bold;" id="total_customers">1</div>
                </div>
            </div>

            <!-- Total Profit -->
            <div
                style="background: linear-gradient(45deg, #43e97b 0%, #38f9d7 100%); height: 90px; display: flex; align-items: center; padding: 15px; border-radius: 8px; color: white; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); transition: transform 0.2s;">
                <i class="fa fa-line-chart"
                    style="font-size: 40px; margin-right: 15px; width: 50px; text-align: center; opacity: 0.9;"></i>
                <div>
                    <div
                        style="font-size: 12px; text-transform: uppercase; margin-bottom: 4px; font-weight: 500; letter-spacing: 0.5px;">
                        TOTAL PROFIT</div>
                    <div style="font-size: 22px; font-weight: bold;" id="total_profit">$75.00</div>
                </div>
            </div>
        </div>

        <!-- Row 2: Second 4 Widgets -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 15px;">
            <!-- Qty Sold -->
            <div
                style="background: linear-gradient(45deg, #ff6b6b 0%, #ee5a24 100%); height: 90px; display: flex; align-items: center; padding: 15px; border-radius: 8px; color: white; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); transition: transform 0.2s;">
                <i class="fa fa-balance-scale"
                    style="font-size: 40px; margin-right: 15px; width: 50px; text-align: center; opacity: 0.9;"></i>
                <div>
                    <div
                        style="font-size: 12px; text-transform: uppercase; margin-bottom: 4px; font-weight: 500; letter-spacing: 0.5px;">
                        QTY SOLD</div>
                    <div style="font-size: 22px; font-weight: bold;" id="total_qty_sold">3.00</div>
                </div>
            </div>

            <!-- Total Tax -->
            <div
                style="background: linear-gradient(45deg, #5f27cd 0%, #341f97 100%); height: 90px; display: flex; align-items: center; padding: 15px; border-radius: 8px; color: white; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); transition: transform 0.2s;">
                <i class="fa fa-receipt"
                    style="font-size: 40px; margin-right: 15px; width: 50px; text-align: center; opacity: 0.9;"></i>
                <div>
                    <div
                        style="font-size: 12px; text-transform: uppercase; margin-bottom: 4px; font-weight: 500; letter-spacing: 0.5px;">
                        TOTAL TAX</div>
                    <div style="font-size: 22px; font-weight: bold;" id="total_tax_amount"><span
                            class="display_currency" data-currency_symbol="true">0.00</span></div>
                </div>
            </div>

            <!-- Total Discount -->
            <div
                style="background: linear-gradient(45deg, #fd9644 0%, #f39c12 100%); height: 90px; display: flex; align-items: center; padding: 15px; border-radius: 8px; color: white; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); transition: transform 0.2s;">
                <i class="fa fa-tag"
                    style="font-size: 40px; margin-right: 15px; width: 50px; text-align: center; opacity: 0.9;"></i>
                <div>
                    <div
                        style="font-size: 12px; text-transform: uppercase; margin-bottom: 4px; font-weight: 500; letter-spacing: 0.5px;">
                        TOTAL DISCOUNT</div>
                    <div style="font-size: 22px; font-weight: bold;" id="total_discount_amount"><span
                            class="display_currency" data-currency_symbol="true">0.00</span></div>
                </div>
            </div>

            <!-- Profit Margin -->
            <div
                style="background: linear-gradient(45deg, #fc466b 0%, #3f5efb 100%); height: 90px; display: flex; align-items: center; padding: 15px; border-radius: 8px; color: white; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); transition: transform 0.2s;">
                <i class="fa fa-percent"
                    style="font-size: 40px; margin-right: 15px; width: 50px; text-align: center; opacity: 0.9;"></i>
                <div>
                    <div
                        style="font-size: 12px; text-transform: uppercase; margin-bottom: 4px; font-weight: 500; letter-spacing: 0.5px;">
                        PROFIT MARGIN</div>
                    <div style="font-size: 22px; font-weight: bold;" id="profit_margin">20.00%</div>
                </div>
            </div>
        </div>

    </div>



    <!-- Top Products Section -->
    @component('components.widget', ['class' => 'box-primary', 'title' => __('Top Performing Products')])
    <div class="table-responsive">
        <table class="table table-striped" id="top_products_table">
            <thead>
                <tr>
                    <th>{{ __('Product') }}</th>
                    <th>{{ __('Qty Sold') }}</th>
                    <th>{{ __('Total Amount') }}</th>
                </tr>
            </thead>
            <tbody id="top_products_body">
                <!-- Will be populated by JavaScript -->
            </tbody>
        </table>
    </div>
    @endcomponent

    <!-- Weekly Sales Summary Report -->
    @component('components.widget', ['class' => 'box-info', 'title' => __('Weekly Sales Summary Report')])


    <!-- 1. WEEKLY SALES SUMMARY REPORT -->
    <table class="excel-table">
        <tr>
            <td colspan="4" class="table-title">
                <span id="weekly_sales_title">@lang('advancedreports::lang.weekly_sales_summary_report')</span>
                <span id="weekly_sales_date_range"> - {{ date('d-m-Y', strtotime('-30 days')) }} To {{ date('d-m-Y')
                    }}</span>
            </td>
        </tr>
        <tr>
            <td class="column-header">WEEK</td>
            <td class="column-header">TOTAL SALES<br>AMT</td>
            <td class="column-header">EQUIVALENT<br>PURCHASE VALUE</td>
            <td class="column-header">PROFIT EARNED</td>
        </tr>
        <tbody id="weekly_sales_excel_body">
            <tr>
                <td class="row-header">WEEK 1</td>
                <td class="data-cell">-</td>
                <td class="data-cell">-</td>
                <td class="data-cell">-</td>
            </tr>
            <tr>
                <td class="row-header">WEEK 2</td>
                <td class="data-cell alternate">-</td>
                <td class="data-cell alternate">-</td>
                <td class="data-cell alternate">-</td>
            </tr>
            <tr>
                <td class="row-header">WEEK 3</td>
                <td class="data-cell">-</td>
                <td class="data-cell">-</td>
                <td class="data-cell">-</td>
            </tr>
            <tr>
                <td class="row-header">WEEK 4</td>
                <td class="data-cell alternate">-</td>
                <td class="data-cell alternate">-</td>
                <td class="data-cell alternate">-</td>
            </tr>
            <tr>
                <td class="row-header">WEEK 5</td>
                <td class="data-cell">-</td>
                <td class="data-cell">-</td>
                <td class="data-cell">-</td>
            </tr>
            <tr class="total-row">
                <td class="row-header">TOTAL</td>
                <td class="data-cell">$ 0.00</td>
                <td class="data-cell">$ 0.00</td>
                <td class="data-cell">$ 0.00</td>
            </tr>
        </tbody>
    </table>

    <!-- 2. PURCHASE SUMMARY -->
    <table class="excel-table">
        <tr>
            <td colspan="6" class="table-title">
                PURCHASE SUMMARY
                <span id="purchase_summary_date_range"> - {{ date('d-m-Y', strtotime('-30 days')) }} To {{ date('d-m-Y')
                    }}</span>
            </td>
        </tr>
        <tr>
            <td class="column-header">PURCHASE DATE</td>
            <td class="column-header">INVOICE #</td>
            <td class="column-header">SUPPLIER</td>
            <td class="column-header">PURCHASE AMT</td>
            <td class="column-header">PURCHASE<br>DISCOUNT</td>
            <td class="column-header">PURCHASE TOTAL</td>
        </tr>
        <tbody id="purchase_summary_excel_body">
            <!-- This will be populated by JavaScript with REAL data -->
            <tr>
                <td colspan="6" class="data-cell" style="text-align: center;">Loading purchase data...</td>
            </tr>
        </tbody>
    </table>

    <!-- 3. STAFF PERFORMANCE ANALYSIS -->
    <table class="excel-table">
        <tr>
            <td colspan="10" class="table-title">
                STAFF PERFORMANCE ANALYSIS
                <span id="staff_performance_date_range"> - {{ date('d-m-Y', strtotime('-30 days')) }} To {{
                    date('d-m-Y') }}</span>
            </td>
        </tr>
        <tr>
            <td class="column-header">STAFF NAME</td>
            <td class="column-header">WEEK 1<br><small>1st ---7th</small></td>
            <td class="column-header">WEEK 2<br><small>8th ---14th</small></td>
            <td class="column-header">WEEK 3<br><small>15th ---- 21st</small></td>
            <td class="column-header">WEEK 4<br><small>22nd ---- 28th</small></td>
            <td class="column-header">WEEK 5<br><small>29th ----31st</small></td>
            <td class="column-header">TOTAL SALES</td>
            <td class="column-header">EQUIVALENT<br>PURCHASE VALUE</td>
            <td class="column-header">PROFIT EARNED</td>
            <td class="column-header">PROFIT MARGIN</td>
        </tr>
        <tbody id="staff_performance_excel_body">
            <tr>
                <td class="row-header">MISS VICTORIA</td>
                <td class="data-cell staff-week1">9,681.15</td>
                <td class="data-cell staff-week2">0.00</td>
                <td class="data-cell staff-week3">0.00</td>
                <td class="data-cell staff-week4">0.00</td>
                <td class="data-cell staff-week5">0.00</td>
                <td class="data-cell staff-total">9,681.15</td>
                <td class="data-cell staff-purchase">15,928.73</td>
                <td class="data-cell staff-profit">-6,247.58</td>
                <td class="data-cell staff-margin">-39.22%</td>
            </tr>
            <tr>
                <td class="row-header">MISS BELINDA</td>
                <td class="data-cell staff-week1">12,116.50</td>
                <td class="data-cell staff-week2">0.00</td>
                <td class="data-cell staff-week3">0.00</td>
                <td class="data-cell staff-week4">0.00</td>
                <td class="data-cell staff-week5">0.00</td>
                <td class="data-cell staff-total">12,116.50</td>
                <td class="data-cell staff-purchase">22,251.28</td>
                <td class="data-cell staff-profit">-10,134.78</td>
                <td class="data-cell staff-margin">-45.55%</td>
            </tr>
            <tr>
                <td class="row-header">MISS MARY ANN</td>
                <td class="data-cell staff-week1">12,203.22</td>
                <td class="data-cell staff-week2">0.00</td>
                <td class="data-cell staff-week3">0.00</td>
                <td class="data-cell staff-week4">0.00</td>
                <td class="data-cell staff-week5">0.00</td>
                <td class="data-cell staff-total">12,203.22</td>
                <td class="data-cell staff-purchase">23,266.12</td>
                <td class="data-cell staff-profit">-11,062.90</td>
                <td class="data-cell staff-margin">-47.55%</td>
            </tr>
            <tr class="total-row">
                <td class="row-header">TOTAL</td>
                <td class="data-cell">34,000.87</td>
                <td class="data-cell">0.00</td>
                <td class="data-cell">0.00</td>
                <td class="data-cell">0.00</td>
                <td class="data-cell">0.00</td>
                <td class="data-cell">34,000.87</td>
                <td class="data-cell">61,446.13</td>
                <td class="data-cell">-27,445.26</td>
                <td class="data-cell">-44.67%</td>
            </tr>
        </tbody>
    </table>

    <!-- 4. STOCK VALUATION -->
    <table class="excel-table stock-table">
        <tr>
            <td colspan="4" class="table-title">
                STOCK VALUATION AS AT <span id="stock_valuation_current_date">{{ date('d - M - Y') }}</span>
            </td>
        </tr>
        <tr>
            <td class="column-header">CURRENT STOCK VALUE BY<br>PURCHASE PRICE</td>
            <td class="column-header">CURRENT STOCK VALUE<br>BY SALES PRICE</td>
            <td class="column-header">POTENTIAL<br>PROFIT</td>
            <td class="column-header">PROFIT<br>MARGIN</td>
        </tr>
        <tbody id="stock_valuation_excel_body">
            <tr>
                <td class="data-cell">194,702.31</td>
                <td class="data-cell">269,779.34</td>
                <td class="data-cell">75,077.03</td>
                <td class="data-cell">38.56%</td>
            </tr>
        </tbody>
    </table>


    <div class="table-responsive">
        <table class="table table-striped" id="weekly_sales_table">
            <thead>
                <tr>
                    <th>{{ __('Week') }}</th>
                    <th>{{ __('Total Sales Amount') }}</th>
                    <th>{{ __('Equivalent Purchase Value') }}</th>
                    <th>{{ __('Profit Earned') }}</th>
                    <th>{{ __('Profit Margin') }}</th>
                </tr>
            </thead>
            <tbody id="weekly_sales_body">
                <!-- Will be populated by JavaScript -->
            </tbody>
        </table>
    </div>
    @endcomponent

    <!-- Staff Performance Analysis -->
    @component('components.widget', ['class' => 'box-warning', 'title' => __('Staff Performance Analysis')])
    <div class="table-responsive">
        <table class="table table-striped" id="staff_performance_table">
            <thead>
                <tr>
                    <th>{{ __('Staff Name') }}</th>
                    <th>{{ __('Week 1') }}</th>
                    <th>{{ __('Week 2') }}</th>
                    <th>{{ __('Week 3') }}</th>
                    <th>{{ __('Week 4') }}</th>
                    <th>{{ __('Week 5') }}</th>
                    <th>{{ __('Total Sales') }}</th>
                    <th>{{ __('Equivalent Purchase Value') }}</th>
                    <th>{{ __('Profit Earned') }}</th>
                    <th>{{ __('Profit Margin') }}</th>
                </tr>
            </thead>
            <tbody id="staff_performance_body">
                <!-- Will be populated by JavaScript -->
            </tbody>
        </table>
    </div>
    @endcomponent
    <div class="chart-container">
        <div class="chart-title">STAFF PERFORMANCE CHART</div>
        <div class="chart-wrapper">
            <canvas id="staffPerformanceChart" class="chart-canvas"></canvas>
        </div>
        <!-- UPDATED: Dynamic legend (will be populated by JavaScript) -->
        <div class="chart-legend" id="dynamic-staff-legend">
            <!-- This will be populated automatically with real staff names -->
            <div class="legend-item">
                <span class="legend-color" style="background-color: #ccc;"></span>
                Loading staff data...
            </div>
        </div>
    </div>





    <!-- Purchase Summary -->
    @component('components.widget', ['class' => 'box-success', 'title' => __('Purchase Summary')])
    <div class="table-responsive">
        <table class="table table-striped" id="purchase_summary_table">
            <thead>
                <tr>
                    <th>{{ __('Purchase Date') }}</th>
                    <th>{{ __('Invoice #') }}</th>
                    <th>{{ __('Supplier') }}</th>
                    <th>{{ __('Purchase Amount') }}</th>
                    <th>{{ __('Purchase Discount') }}</th>
                    <th>{{ __('Purchase Total') }}</th>
                </tr>
            </thead>
            <tbody id="purchase_summary_body">
                <!-- Will be populated by JavaScript -->
            </tbody>
        </table>
    </div>
    @endcomponent

    <!-- Stock Valuation Widgets -->
    <div class="row">
        <div class="col-md-3">
            <div class="stock-valuation-widget bg-purchase">
                <i class="fa fa-shopping-cart stock-valuation-icon"></i>
                <div class="stock-valuation-content">
                    <span class="stock-valuation-text">{{ __('Current Stock Value by Purchase Price') }}</span>
                    <span class="stock-valuation-number" id="stock_value_purchase">--</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stock-valuation-widget bg-sales">
                <i class="fa fa-tag stock-valuation-icon"></i>
                <div class="stock-valuation-content">
                    <span class="stock-valuation-text">{{ __('Current Stock Value by Sales Price') }}</span>
                    <span class="stock-valuation-number" id="stock_value_sales">--</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stock-valuation-widget bg-profit">
                <i class="fa fa-trophy stock-valuation-icon"></i>
                <div class="stock-valuation-content">
                    <span class="stock-valuation-text">{{ __('Potential Profit') }}</span>
                    <span class="stock-valuation-number" id="potential_profit">--</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stock-valuation-widget bg-margin">
                <i class="fa fa-percent stock-valuation-icon"></i>
                <div class="stock-valuation-content">
                    <span class="stock-valuation-text">{{ __('Profit Margin') }}</span>
                    <span class="stock-valuation-number" id="stock_profit_margin">0%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Report Table -->
    @component('components.widget', ['class' => 'box-primary', 'title' => __('Product Performance Report')])
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="product_report_table">
            <thead>
                <tr>
                    <th>{{ __('Product') }}</th>
                    <th>{{ __('SKU') }}</th>
                    <th>{{ __('Brand') }}</th>
                    <th>{{ __('Category') }}</th>
                    <th>{{ __('Unit') }}</th>
                    <th>{{ __('Customer') }}</th>
                    <th>{{ __('Invoice No.') }}</th>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Week') }}</th>
                    <th>{{ __('Qty Sold') }}</th>
                    <th>{{ __('Unit Price') }}</th>
                    <th>{{ __('Discount') }}</th>
                    <th>{{ __('Tax') }}</th>
                    <th>{{ __('Price Inc. Tax') }}</th>
                    <th>{{ __('Total Sales') }}</th>
                    <th>{{ __('Purchase Price (Inc)') }}</th>
                    <th>{{ __('Purchase Price (Exc)') }}</th>
                    <th>{{ __('Selling Price') }}</th>
                    <th>{{ __('Profit') }}</th>
                    <th>{{ __('Profit Margin') }}</th>
                    <th>{{ __('Current Stock') }}</th>
                    <th>{{ __('Product Type') }}</th>
                    <th>{{ __('Manage Stock') }}</th>
                    <th>{{ __('Tax Info') }}</th>
                    <th>{{ __('Payment Method') }}</th>
                    <th>{{ __('Custom Fields') }}</th>
                    <th>{{ __('Product Details') }}</th>
                    <th>{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr class="bg-gray font-17 text-center footer_adjustment">
                    <td colspan="9"><strong>{{ __('Total') }}:</strong></td>
                    <td><strong><span id="footer_total_qty">0</span></strong></td>
                    <td></td> <!-- Unit Price -->
                    <td></td> <!-- Discount -->
                    <td><strong><span id="footer_total_tax">0</span></strong></td>
                    <td></td> <!-- Price Inc. Tax -->
                    <td><strong><span id="footer_total_amount">0</span></strong></td>
                    <td></td> <!-- Purchase Price (Inc) -->
                    <td></td> <!-- Purchase Price (Exc) -->
                    <td></td> <!-- Selling Price -->
                    <td><strong><span id="footer_total_profit">0</span></strong></td>
                    <td colspan="9"></td> <!-- Remaining columns -->
                </tr>
            </tfoot>
        </table>
    </div>
    @endcomponent
</section>

<!-- Modal Container for Invoice Details -->
<div class="modal fade view_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>

<!-- Export Modal -->
<div class="modal fade" id="export_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">{{ __('Export Report') }}</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="export_format">{{ __('Export Format') }}:</label>
                    <select class="form-control" id="export_format">
                        <option value="xlsx">Excel (.xlsx)</option>
                        <option value="csv">CSV (.csv)</option>
                    </select>
                </div>

                <div class="form-group">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" id="include_filters" checked>
                            {{ __('Include current filters') }}
                        </label>
                        <small class="text-muted block">
                            Export only data matching your current filter selections (date range, location, customer,
                            etc.)
                        </small>
                    </div>
                </div>

                <div class="form-group">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" id="include_summary" checked>
                            {{ __('Include summary information') }}
                        </label>
                        <small class="text-muted block">
                            Add summary worksheets with totals, weekly sales, staff performance, and stock valuation
                        </small>
                    </div>
                </div>

                <!-- Export Options -->
                <div class="form-group">
                    <label>{{ __('Export Options') }}:</label>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" id="include_weekly_sales" checked>
                            {{ __('Include Weekly Sales Summary') }}
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" id="include_staff_performance" checked>
                            {{ __('Include Staff Performance Analysis') }}
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" id="include_stock_valuation" checked>
                            {{ __('Include Stock Valuation') }}
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" id="include_purchase_summary" checked>
                            {{ __('Include Purchase Summary') }}
                        </label>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> {{ __('Export will process based on your selections. Large
                    datasets may take some time.') }}
                </div>

                <div class="alert alert-warning" id="export_warning" style="display: none;">
                    <i class="fa fa-warning"></i> <span id="export_warning_text"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="download_export">
                    <i class="fa fa-download"></i> {{ __('Download') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
    /* COMPLETE WIDGET RESET - Fixed Heights and Positioning */
    .info-box {
        height: 90px !important;
        /* FIXED HEIGHT for all widgets */
        display: flex !important;
        align-items: center !important;
        padding: 15px !important;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
    }

    .info-box:hover {
        transform: translateY(-2px);
    }


    /* CONTENT AREA FIXED */
    .info-box-content {
        flex-grow: 1 !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        min-height: 60px !important;
        color: white !important;
    }

    /* TEXT FIXED POSITIONING */
    .info-box-text {
        font-size: 12px !important;
        opacity: 0.95 !important;
        font-weight: 500 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        margin-bottom: 4px !important;
        line-height: 1.2 !important;
        color: white !important;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
    }

    /* NUMBER FIXED POSITIONING */
    .info-box-number {
        font-size: 22px !important;
        font-weight: bold !important;
        line-height: 1 !important;
        color: white !important;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
        margin: 0 !important;
    }

    /* IMPROVED GRADIENTS - Better Contrast */

    /* Row 1 - Keep these as they look good */
    .widget-products {
        background: linear-gradient(45deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
    }

    .widget-sales {
        background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%) !important;
        color: white !important;
    }

    .widget-customers {
        background: linear-gradient(45deg, #4facfe 0%, #00f2fe 100%) !important;
        color: white !important;
    }

    .widget-profit {
        background: linear-gradient(45deg, #43e97b 0%, #38f9d7 100%) !important;
        color: white !important;
    }

    /* Row 2 - IMPROVED COLORS */
    .widget-qty {
        background: linear-gradient(45deg, #ff6b6b 0%, #ee5a24 100%) !important;
        color: white !important;
    }

    .widget-tax {
        background: linear-gradient(45deg, #5f27cd 0%, #341f97 100%) !important;
        color: white !important;
    }

    .widget-discount {
        background: linear-gradient(45deg, #fd9644 0%, #f39c12 100%) !important;
        color: white !important;
    }

    .widget-margin {
        background: linear-gradient(45deg, #fc466b 0%, #3f5efb 100%) !important;
        color: white !important;
    }

    /* FORCE ALL TEXT TO BE WHITE */
    .info-box * {
        color: white !important;
    }

    /* Mobile responsive */
    @media (max-width: 768px) {
        .info-box {
            height: 80px !important;
            padding: 12px !important;
        }


        .info-box-text {
            font-size: 10px !important;
        }

        .info-box-number {
            font-size: 18px !important;
        }

        .info-box-content {
            min-height: 50px !important;
        }
    }

    /* OVERRIDE ANY EXISTING STYLES */
    .col-lg-3 .info-box,
    .col-xs-6 .info-box {
        height: 90px !important;
        min-height: 90px !important;
        max-height: 90px !important;
    }

    /* ENSURE BOOTSTRAP COLUMNS ARE EQUAL HEIGHT */
    .row {
        display: flex;
        flex-wrap: wrap;
    }

    .col-lg-3,
    .col-xs-6 {
        display: flex;
        flex-direction: column;
    }

    /* Remove any inline styles that might interfere */
    .info-box[style] {
        background: none !important;
    }

    .info-box-icon[style] {
        color: white !important;
    }

    .info-box-text[style] {
        color: white !important;
    }

    .info-box-number[style] {
        color: white !important;
    }
</style>
<!-- Stock Valuation Section with Fixed Heights -->
<style>
    /* Fix for uniform widget heights and smaller icons */
    .stock-valuation-widget {
        height: 120px;
        /* Fixed height for all widgets */
        display: flex;
        align-items: center;
        padding: 15px;
        border-radius: 8px;
        color: white;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s;
    }

    .stock-valuation-widget:hover {
        transform: translateY(-2px);
    }

    .stock-valuation-icon {
        font-size: 35px !important;
        /* Smaller, consistent icon size */
        opacity: 0.8;
        margin-right: 15px;
        width: 60px;
        /* Fixed width for icons */
        text-align: center;
        flex-shrink: 0;
        /* Prevent icon from shrinking */
    }

    .stock-valuation-content {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .stock-valuation-text {
        font-size: 13px;
        opacity: 0.9;
        margin-bottom: 5px;
        line-height: 1.2;
        font-weight: 500;
    }

    .stock-valuation-number {
        font-size: 22px;
        font-weight: bold;
        line-height: 1;
    }

    /* Background colors */
    .bg-purchase {
        background: linear-gradient(45deg, #3498db, #2980b9);
    }

    .bg-sales {
        background: linear-gradient(45deg, #2ecc71, #27ae60);
    }

    .bg-profit {
        background: linear-gradient(45deg, #f39c12, #e67e22);
    }

    .bg-margin {
        background: linear-gradient(45deg, #e74c3c, #c0392b);
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .stock-valuation-widget {
            height: 100px;
            padding: 12px;
        }

        .stock-valuation-icon {
            font-size: 28px !important;
            width: 50px;
            margin-right: 10px;
        }

        .stock-valuation-text {
            font-size: 11px;
        }

        .stock-valuation-number {
            font-size: 18px;
        }
    }
</style>

<style>
    /* EXCEL TABLE STYLING */
    .excel-table {
        width: 100%;
        border-collapse: collapse;
        border: 2px solid #333;
        font-family: Arial, sans-serif;
        font-size: 12px;
        margin-bottom: 30px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .excel-table .table-title {
        background: linear-gradient(135deg, #4DD0E1, #26C6DA);
        color: #000;
        font-weight: bold;
        font-size: 14px;
        text-align: center;
        padding: 12px;
        border: 2px solid #333;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .excel-table .column-header {
        background: #FFF3CD;
        border: 1px solid #333;
        padding: 8px 12px;
        font-weight: bold;
        text-align: center;
        color: #000;
        font-size: 11px;
        text-transform: uppercase;
    }

    .excel-table .row-header {
        background: #E9ECEF;
        border: 1px solid #333;
        padding: 8px 12px;
        font-weight: bold;
        color: #000;
        text-align: left;
    }

    .excel-table .data-cell {
        border: 1px solid #333;
        padding: 8px 12px;
        text-align: right;
        color: #000;
        background: #fff;
    }

    .excel-table .data-cell.alternate {
        background: #F8F9FA;
    }

    .excel-table .total-row {
        background: #D1ECF1;
        font-weight: bold;
    }

    .excel-table .total-row .data-cell {
        background: #D1ECF1;
        font-weight: bold;
    }

    /* Staff Performance Colors */
    .staff-week1 {
        background: #FFE4B5 !important;
    }

    .staff-week2 {
        background: #E6F3FF !important;
    }

    .staff-week3 {
        background: #E6F3FF !important;
    }

    .staff-week4 {
        background: #E6F3FF !important;
    }

    .staff-week5 {
        background: #E6F3FF !important;
    }

    .staff-total {
        background: #FFFACD !important;
    }

    .staff-purchase {
        background: #E8F5E8 !important;
    }

    .staff-profit {
        background: #F0F8FF !important;
    }

    .staff-margin {
        background: #F5F5DC !important;
    }

    /* Stock Valuation Simple Style */
    .stock-table .column-header {
        background: #FFF3CD;
        padding: 10px;
        font-weight: bold;
    }

    .stock-table .data-cell {
        padding: 10px;
        text-align: center;
        font-weight: bold;
        font-size: 13px;
    }
</style>

<style>
    .chart-container {
        background: #2d3436;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        height: 500px;
        /* FIXED HEIGHT */
    }

    .chart-title {
        color: white;
        text-align: center;
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 15px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .chart-wrapper {
        height: 400px;
        /* FIXED CHART HEIGHT */
        width: 100%;
        position: relative;
    }

    .chart-canvas {
        background: #2d3436 !important;
        border-radius: 4px;
        width: 100% !important;
        height: 100% !important;
    }

    .chart-legend {
        margin-top: 15px;
        text-align: center;
        height: 30px;
        /* FIXED LEGEND HEIGHT */
    }

    .legend-item {
        display: inline-block;
        margin: 0 15px;
        color: white;
        font-size: 12px;
    }

    .legend-color {
        display: inline-block;
        width: 16px;
        height: 16px;
        margin-right: 5px;
        vertical-align: middle;
    }
</style>
<style>
    /* Date Filter Dropdown Styling for Product Report */
    .date-filter-dropdown {
        position: relative;
    }

    .date-filter-dropdown .btn {
        text-align: left;
        background-color: #fff;
        border-color: #d2d6de;
        color: #555;
        padding: 6px 12px;
        height: 34px;
        line-height: 1.42857143;
    }

    .date-filter-dropdown .btn:hover,
    .date-filter-dropdown .btn:focus {
        background-color: #f4f4f4;
        border-color: #adc6f7;
        outline: 0;
    }

    .date-filter-dropdown .btn .fa-calendar {
        margin-right: 5px;
        color: #666;
    }

    .date-filter-dropdown .caret {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
    }

    .date-filter-dropdown .dropdown-menu {
        min-width: 100%;
        border: 1px solid rgba(0, 0, 0, .15);
        border-radius: 4px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, .175);
        background-color: #fff;
        z-index: 1000;
    }

    .date-filter-dropdown .dropdown-menu>li>a {
        padding: 8px 15px;
        color: #333;
        text-decoration: none;
        font-size: 13px;
        line-height: 1.42857143;
        display: block;
        clear: both;
        font-weight: normal;
        white-space: nowrap;
    }

    .date-filter-dropdown .dropdown-menu>li>a:hover,
    .date-filter-dropdown .dropdown-menu>li>a:focus {
        background-color: #f5f5f5;
        color: #262626;
        text-decoration: none;
    }

    .date-filter-dropdown .dropdown-menu>.divider {
        height: 1px;
        margin: 5px 0;
        overflow: hidden;
        background-color: #e5e5e5;
    }

    /* Ensure dropdown stays open on click */
    .date-filter-dropdown.open .dropdown-menu {
        display: block;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .date-filter-dropdown .btn {
            font-size: 12px;
            padding: 5px 10px;
        }
    }
</style>
@endsection

@section('javascript')
<script>
    // ==============================================
// EMERGENCY CLEANUP - REMOVE ALL CONFLICTS
// ==============================================
$(document).ready(function() {
    // Stop all existing chart functions
    window.staffChartInitialized = false;
    $('#apply_filters').off('click.staffChart');
    $(document).off('ajaxComplete.staffChart');
    
    if (window.staffChart) {
        window.staffChart.destroy();
        window.staffChart = null;
    }
});

// ==============================================
// MAIN APPLICATION CODE
// ==============================================
$(document).ready(function() {
    // Helper function for currency formatting
    function formatCurrency(amount) {
        if (typeof __currency_trans_from_en === 'function') {
            return __currency_trans_from_en(amount, true);
        }
        return '$' + parseFloat(amount).toFixed(2);
    }
    
    // Initialize date pickers
    $('#start_date, #end_date').datepicker({
        autoclose: true,
        format: 'yyyy-mm-dd'
    });

    // Initialize select2
    $('.select2').select2();

    // Initialize DataTable
    var productTable = $('#product_report_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("advancedreports.product.data") }}',
            data: function(d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.location_id = $('#location_id').val();
                d.customer_id = $('#customer_id').val();
                d.customer_group_id = $('#customer_group_id').val();
                d.category_id = $('#category_id').val();
                d.brand_id = $('#brand_id').val();
                d.unit_id = $('#unit_id').val();
                d.user_id = $('#user_id').val();
                d.payment_method = $('#payment_method').val();
            },
            error: function(xhr, error, thrown) {
                console.error('DataTable Error:', error);
                if (typeof toastr !== 'undefined') {
                    toastr.error('{{ __("Error loading data. Please try again.") }}');
                }
            }
        },
        columns: [
            {data: 'product', name: 'product'},
            {data: 'sku', name: 'sku'},
            {data: 'brand', name: 'brand'},
            {data: 'category', name: 'category'},
            {data: 'unit', name: 'unit'},
            {data: 'customer', name: 'customer'},
            {data: 'invoice_no', name: 'invoice_no'},
            {data: 'transaction_date', name: 'transaction_date'},
            {data: 'week_display', name: 'week_display'},
            {data: 'sold_qty', name: 'sold_qty'},
            {data: 'unit_price', name: 'unit_price'},
            {data: 'discount', name: 'discount'},
            {data: 'tax_amount', name: 'tax_amount'},
            {data: 'unit_price_inc_tax', name: 'unit_price_inc_tax'},
            {data: 'line_total', name: 'line_total'},
            {data: 'purchase_price_inc', name: 'purchase_price_inc'},
            {data: 'purchase_price_exc', name: 'purchase_price_exc'},
            {data: 'selling_price_display', name: 'selling_price_display'},
            {data: 'actual_profit', name: 'actual_profit'},
            {data: 'actual_profit_margin', name: 'actual_profit_margin'},
            {data: 'current_stock_display', name: 'current_stock_display'},
            {data: 'variation_info', name: 'variation_info'},
            {data: 'manage_stock', name: 'manage_stock'},
            {data: 'tax_info', name: 'tax_info'},
            {data: 'payment_method', name: 'payment_method'},
            {data: 'custom_fields', name: 'custom_fields'},
            {data: 'product_details', name: 'product_details'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [[4, 'desc']],
        lengthMenu: [
            [25, 50, 100, 200, -1],
            [25, 50, 100, 200, "All"]
        ],
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">{{ __("Loading...") }}</span>',
            emptyTable: '{{ __("No data available in table") }}',
            info: '{{ __("Showing _START_ to _END_ of _TOTAL_ entries") }}',
            infoEmpty: '{{ __("Showing 0 to 0 of 0 entries") }}',
            infoFiltered: '{{ __("(filtered from _MAX_ total entries)") }}',
            lengthMenu: '{{ __("Show _MENU_ entries") }}',
            loadingRecords: '{{ __("Loading...") }}',
            search: '{{ __("Search:") }}',
            zeroRecords: '{{ __("No matching records found") }}',
            paginate: {
                first: '{{ __("First") }}',
                last: '{{ __("Last") }}',
                next: '{{ __("Next") }}',
                previous: '{{ __("Previous") }}'
            }
        },
        footerCallback: function(row, data, start, end, display) {
            var api = this.api();
            
            function extractNumericValue(text) {
                if (!text || text === '' || text === '--') return 0;
                var cleaned = text.toString().replace(/[$,\s]/g, '').replace(/[^\d.-]/g, '');
                return parseFloat(cleaned) || 0;
            }
            
            var totals = {
                qty_sold: 0,
                tax_amount: 0, 
                line_total: 0,
                actual_profit: 0
            };
            
            try {
                api.column(9, {page: 'current'}).data().each(function(value) {
                    var qty = extractNumericValue(value);
                    totals.qty_sold += qty;
                });
                
                api.column(12, {page: 'current'}).data().each(function(value) {
                    totals.tax_amount += extractNumericValue(value);
                });
                
                api.column(14, {page: 'current'}).data().each(function(value) {
                    totals.line_total += extractNumericValue(value);
                });
                
                api.column(18, {page: 'current'}).data().each(function(value) {
                    totals.actual_profit += extractNumericValue(value);
                });
                
            } catch (error) {
                console.error('Footer calculation error:', error);
            }
            
            $('#footer_total_qty').text(totals.qty_sold.toFixed(2));
            $('#footer_total_tax').text(formatCurrency(totals.tax_amount));
            $('#footer_total_amount').text(formatCurrency(totals.line_total));
            $('#footer_total_profit').text(formatCurrency(totals.actual_profit));
        },
        drawCallback: function(settings) {
            $('.dataTables_processing').hide();
        }
    });

    // Apply filters
    $('#apply_filters').click(function() {
        var $btn = $(this);
        $btn.html('<i class="fa fa-spinner fa-spin"></i> {{ __("Applying...") }}').prop('disabled', true);
        
        loadSummary();
        productTable.ajax.reload();
        
        setTimeout(function() {
            $btn.html('<i class="fa fa-filter"></i> {{ __("Apply Filters") }}').prop('disabled', false);
        }, 1000);
    });

    // Clear filters
    $('#clear_filters').click(function() {
        var $btn = $(this);
        $btn.html('<i class="fa fa-spinner fa-spin"></i> {{ __("Clearing...") }}').prop('disabled', true);
        
        $('#product_report_filter_form')[0].reset();
        $('.select2').val(null).trigger('change');
        $('#start_date').val('{{ date("Y-m-d", strtotime("-30 days")) }}');
        $('#end_date').val('{{ date("Y-m-d") }}');
        loadSummary();
        productTable.ajax.reload();
        
        setTimeout(function() {
            $btn.html('<i class="fa fa-refresh"></i> {{ __("Clear") }}').prop('disabled', false);
        }, 1000);
    });

    // Refresh report
    $('#refresh_report').click(function() {
        var $btn = $(this);
        $btn.html('<i class="fa fa-spinner fa-spin"></i> {{ __("Refreshing...") }}').prop('disabled', true);
        
        loadSummary();
        productTable.ajax.reload();
        
        if (typeof toastr !== 'undefined') {
            toastr.success('{{ __("Report refreshed successfully") }}');
        }
        
        setTimeout(function() {
            $btn.html('<i class="fa fa-refresh"></i> {{ __("Refresh") }}').prop('disabled', false);
        }, 1000);
    });

    // Export functionality
    $('#export_report').click(function() {
        var $btn = $(this);
        $btn.html('<i class="fa fa-spinner fa-spin"></i> {{ __("Preparing...") }}').prop('disabled', true);
        
        $('#export_modal').modal('show');
        
        setTimeout(function() {
            $btn.html('<i class="fa fa-download"></i> {{ __("Export") }}').prop('disabled', false);
        }, 500);
    });

 $('#download_export').click(function() {
    var format = $('#export_format').val();
    var url = '{{ route("advancedreports.product.export") }}';
    
    // Get checkbox states
    var includeFilters = $('#include_filters').is(':checked');
    var includeSummary = $('#include_summary').is(':checked');
    var includeWeeklySales = $('#include_weekly_sales').is(':checked');
    var includeStaffPerformance = $('#include_staff_performance').is(':checked');
    var includeStockValuation = $('#include_stock_valuation').is(':checked');
    var includePurchaseSummary = $('#include_purchase_summary').is(':checked');
    
    // Build parameters object
    var params = {
        format: format,
        include_filters: includeFilters ? 1 : 0,
        include_summary: includeSummary ? 1 : 0,
        include_weekly_sales: includeWeeklySales ? 1 : 0,
        include_staff_performance: includeStaffPerformance ? 1 : 0,
        include_stock_valuation: includeStockValuation ? 1 : 0,
        include_purchase_summary: includePurchaseSummary ? 1 : 0
    };
    
    // Only include filter parameters if "Include current filters" is checked
    if (includeFilters) {
        params.start_date = $('#start_date').val();
        params.end_date = $('#end_date').val();
        params.location_id = $('#location_id').val();
        params.customer_id = $('#customer_id').val();
        params.customer_group_id = $('#customer_group_id').val();
        params.category_id = $('#category_id').val();
        params.brand_id = $('#brand_id').val();
        params.unit_id = $('#unit_id').val();
        params.user_id = $('#user_id').val();
        params.payment_method = $('#payment_method').val();
    }
    
    $('#download_export').html('<i class="fa fa-spinner fa-spin"></i> {{ __("Generating...") }}').prop('disabled', true);
    
    console.log('🔄 Export parameters:', params);
    
    try {
        var queryString = $.param(params);
        window.location.href = url + '?' + queryString;
        
        // Show success message
        if (typeof toastr !== 'undefined') {
            var message = 'Export started with ';
            if (includeFilters) message += 'current filters ';
            if (includeSummary) message += 'and summary information';
          //  toastr.success(message);
        }
        
    } catch (error) {
        console.error('Export error:', error);
        if (typeof toastr !== 'undefined') {
            toastr.error('{{ __("Export failed. Please try again.") }}');
        }
    }
    
    setTimeout(function() {
        $('#download_export').html('<i class="fa fa-download"></i> {{ __("Download") }}').prop('disabled', false);
        $('#export_modal').modal('hide');
    }, 2000);
});

// Update warning text based on selections
$('#export_modal input[type="checkbox"], #export_format').change(function() {
    updateExportWarning();
});

function updateExportWarning() {
    var includeFilters = $('#include_filters').is(':checked');
    var includeSummary = $('#include_summary').is(':checked');
    var warnings = [];
    
    if (!includeFilters) {
        warnings.push('All data will be exported (no filters applied)');
    }
    
    if (includeSummary) {
        var summaryCount = $('#include_weekly_sales:checked, #include_staff_performance:checked, #include_stock_valuation:checked, #include_purchase_summary:checked').length;
        warnings.push(summaryCount + ' additional summary worksheet(s) will be included');
    }
    
    if (warnings.length > 0) {
        $('#export_warning_text').text(warnings.join('. '));
        $('#export_warning').show();
    } else {
        $('#export_warning').hide();
    }
}
// Show/hide export options based on summary checkbox
$('#include_summary').change(function() {
    var isChecked = $(this).is(':checked');
    $('#include_weekly_sales, #include_staff_performance, #include_stock_valuation, #include_purchase_summary')
        .prop('disabled', !isChecked)
        .closest('.checkbox')
        .toggleClass('text-muted', !isChecked);
    
    if (!isChecked) {
        $('#include_weekly_sales, #include_staff_performance, #include_stock_valuation, #include_purchase_summary')
            .prop('checked', false);
    } else {
        $('#include_weekly_sales, #include_staff_performance, #include_stock_valuation, #include_purchase_summary')
            .prop('checked', true);
    }
});
    function updateWeeklySalesExcelTable() {
    $.ajax({
        url: '{{ route("advancedreports.product.weekly-sales") }}',
        type: 'GET',
        data: getFilterData(),
        success: function(response) {
            console.log('📊 Weekly Sales Excel Data:', response);
            
            var html = '';
            var totalSales = 0, totalPurchase = 0, totalProfit = 0;
            
            // Initialize 5 weeks of data
            var weekData = {
                1: { sales: 0, purchase: 0, profit: 0 },
                2: { sales: 0, purchase: 0, profit: 0 },
                3: { sales: 0, purchase: 0, profit: 0 },
                4: { sales: 0, purchase: 0, profit: 0 },
                5: { sales: 0, purchase: 0, profit: 0 }
            };
            
            // Process response data
            if (response && response.length > 0) {
                response.forEach(function(week) {
                    var weekNum = parseInt(week.week_number);
                    if (weekNum >= 1 && weekNum <= 5) {
                        weekData[weekNum] = {
                            sales: parseFloat(week.total_sales_amt || 0),
                            purchase: parseFloat(week.equivalent_purchase_value || 0),
                            profit: parseFloat(week.profit_earned || 0)
                        };
                    }
                });
            }
            
            // Generate HTML for each week
            for (let i = 1; i <= 5; i++) {
                var data = weekData[i];
                var alternateClass = (i % 2 === 0) ? ' alternate' : '';
                
                html += '<tr>';
                html += '<td class="row-header">WEEK ' + i + '</td>';
                html += '<td class="data-cell' + alternateClass + '">' + 
                        (data.sales > 0 ? formatCurrency(data.sales) : '-') + '</td>';
                html += '<td class="data-cell' + alternateClass + '">' + 
                        (data.purchase > 0 ? formatCurrency(data.purchase) : '-') + '</td>';
                html += '<td class="data-cell' + alternateClass + '">' + 
                        (data.profit > 0 ? formatCurrency(data.profit) : '-') + '</td>';
                html += '</tr>';
                
                totalSales += data.sales;
                totalPurchase += data.purchase;
                totalProfit += data.profit;
            }
            
            // Add total row
            html += '<tr class="total-row">';
            html += '<td class="row-header">TOTAL</td>';
            html += '<td class="data-cell">' + formatCurrency(totalSales) + '</td>';
            html += '<td class="data-cell">' + formatCurrency(totalPurchase) + '</td>';
            html += '<td class="data-cell">' + formatCurrency(totalProfit) + '</td>';
            html += '</tr>';
            
            $('#weekly_sales_excel_body').html(html);
            console.log('📊 Weekly Sales Excel table updated');
        },
        error: function() {
            console.error('📊 Error loading weekly sales data for Excel table');
            // Show empty state
            var html = '';
            for (let i = 1; i <= 5; i++) {
                var alternateClass = (i % 2 === 0) ? ' alternate' : '';
                html += '<tr>';
                html += '<td class="row-header">WEEK ' + i + '</td>';
                html += '<td class="data-cell' + alternateClass + '">-</td>';
                html += '<td class="data-cell' + alternateClass + '">-</td>';
                html += '<td class="data-cell' + alternateClass + '">-</td>';
                html += '</tr>';
            }
            html += '<tr class="total-row">';
            html += '<td class="row-header">TOTAL</td>';
            html += '<td class="data-cell">$ 0.00</td>';
            html += '<td class="data-cell">$ 0.00</td>';
            html += '<td class="data-cell">$ 0.00</td>';
            html += '</tr>';
            $('#weekly_sales_excel_body').html(html);
        }
    });
}

// Update Purchase Summary Excel Table with real data
function updatePurchaseSummaryExcelTable() {
    $.ajax({
        url: '{{ route("advancedreports.product.purchase-summary") }}',
        type: 'GET',
        data: getFilterData(),
        success: function(response) {
            console.log('📊 Purchase Summary Excel Data:', response);
            
            var html = '';
            var totalAmount = 0;
            
            if (response && response.length > 0) {
                response.forEach(function(purchase, index) {
                    var alternateClass = (index % 2 === 0) ? '' : ' alternate';
                    var purchaseDate = formatDate(purchase.purchase_date);
                    var invoiceNo = purchase.invoice_no || '0';
                    var supplierName = purchase.supplier_name || 'Unknown Supplier';
                    var purchaseAmt = parseFloat(purchase.purchase_amt || 0);
                    var purchaseDiscount = parseFloat(purchase.purchase_discount || 0);
                    var purchaseTotal = parseFloat(purchase.purchase_total || 0);
                    
                    html += '<tr>';
                    html += '<td class="data-cell' + alternateClass + '" style="text-align: center;">' + purchaseDate + '</td>';
                    html += '<td class="data-cell' + alternateClass + '" style="text-align: center;">' + invoiceNo + '</td>';
                    html += '<td class="data-cell' + alternateClass + '" style="text-align: center;">' + supplierName + '</td>';
                    html += '<td class="data-cell' + alternateClass + '">' + formatCurrencyNumber(purchaseAmt) + '</td>';
                    html += '<td class="data-cell' + alternateClass + '">' + formatCurrencyNumber(purchaseDiscount) + '</td>';
                    html += '<td class="data-cell' + alternateClass + '">' + formatCurrencyNumber(purchaseTotal) + '</td>';
                    html += '</tr>';
                    
                    totalAmount += purchaseTotal;
                });
            } else {
                // No data available
                html += '<tr>';
                html += '<td colspan="6" class="data-cell" style="text-align: center;">No purchase data available for selected period</td>';
                html += '</tr>';
            }
            
            // Add total row if there's data
            if (response && response.length > 0) {
                html += '<tr class="total-row">';
                html += '<td colspan="5" class="data-cell" style="text-align: right; font-weight: bold;">TOTAL</td>';
                html += '<td class="data-cell">' + formatCurrencyNumber(totalAmount) + '</td>';
                html += '</tr>';
            }
            
            $('#purchase_summary_excel_body').html(html);
            console.log('📊 Purchase Summary Excel table updated');
        },
        error: function() {
            console.error('📊 Error loading purchase summary data for Excel table');
            $('#purchase_summary_excel_body').html(
                '<tr><td colspan="6" class="data-cell" style="text-align: center;">Error loading purchase data</td></tr>'
            );
        }
    });
}

// Helper function to format currency numbers (for Excel tables)
function formatCurrencyNumber(amount) {
    if (!amount || amount === 0) return '0';
    return parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

    // Load summary data
   function loadSummary() {
    $.ajax({
        url: '{{ route("advancedreports.product.summary") }}',
        type: 'GET',
        data: getFilterData(),
        beforeSend: function() {
            $('#summary_widgets .info-box-number').html('<i class="fa fa-spinner fa-spin"></i>');
        },
        success: function(response) {
            updateSummaryWidgets(response);
            updateTopProducts(response.top_products);
        },
        error: handleSummaryError
    });

    // Load individual reports
    loadWeeklySales();
    loadStaffPerformance();
    loadPurchaseSummary();
    loadStockValuation();
    
    // **FIXED: Update Excel tables with real data**
    updateExcelTables();
}

    // Load weekly sales summary
    function loadWeeklySales() {
        $.ajax({
            url: '{{ route("advancedreports.product.weekly-sales") }}',
            type: 'GET',
            data: getFilterData(),
            success: function(response) {
                var html = '';
                $.each(response, function(index, week) {
                    html += '<tr>';
                    html += '<td>Week ' + week.week_number + ' - ' + week.year_number + '</td>';
                    html += '<td>' + formatCurrency(week.total_sales_amt) + '</td>';
                    html += '<td>' + formatCurrency(week.equivalent_purchase_value) + '</td>';
                    html += '<td>' + formatCurrency(week.profit_earned) + '</td>';
                    html += '<td>' + week.profit_margin + '%</td>';
                    html += '</tr>';
                });
                $('#weekly_sales_body').html(html || '<tr><td colspan="5" class="text-center">{{ __("No data available") }}</td></tr>');
            },
            error: function() {
                $('#weekly_sales_body').html('<tr><td colspan="5" class="text-center text-danger">{{ __("Error loading data") }}</td></tr>');
            }
        });
    }

    // Load staff performance (FIXED - NO CHART CALLS)
    function loadStaffPerformance() {
        $.ajax({
            url: '{{ route("advancedreports.product.staff-performance") }}',
            type: 'GET',
            data: getFilterData(),
            success: function(response) {
                console.log('📊 Staff Performance Response:', response);
                
                var html = '';
                var staffData = {};
                var totalWeekSales = [0, 0, 0, 0, 0];
                var grandTotalSales = 0;
                var grandTotalPurchase = 0;
                var grandTotalProfit = 0;
                
                // Group by staff (using REAL names from database)
                $.each(response, function(index, item) {
                    var staffName = (item.staff_name || 'Unknown Staff').trim();
                    
                    if (staffName !== 'Unknown Staff') {
                        if (!staffData[staffName]) {
                            staffData[staffName] = {
                                weeks: {},
                                total_sales: 0,
                                total_purchase: 0,
                                total_profit: 0
                            };
                        }
                        
                        var weekNum = parseInt(item.week_number || 0);
                        var sales = parseFloat(item.total_sales || 0);
                        var purchase = parseFloat(item.equivalent_purchase_value || 0);
                        var profit = parseFloat(item.profit_earned || 0);
                        
                        staffData[staffName].weeks['week_' + weekNum] = sales;
                        staffData[staffName].total_sales += sales;
                        staffData[staffName].total_purchase += purchase;
                        staffData[staffName].total_profit += profit;
                        
                        if (weekNum >= 1 && weekNum <= 5) {
                            totalWeekSales[weekNum - 1] += sales;
                        }
                    }
                });

                var sortedStaff = Object.keys(staffData).sort((a, b) => 
                    staffData[b].total_sales - staffData[a].total_sales
                );
                
                $.each(sortedStaff, function(index, staffName) {
                    var data = staffData[staffName];
                    var profit_margin = data.total_sales > 0 ? 
                        ((data.total_profit / data.total_sales) * 100).toFixed(2) : 0;
                    
                    html += '<tr>';
                    html += '<td>' + staffName + '</td>';
                    html += '<td>' + formatCurrency(data.weeks.week_1 || 0) + '</td>';
                    html += '<td>' + formatCurrency(data.weeks.week_2 || 0) + '</td>';
                    html += '<td>' + formatCurrency(data.weeks.week_3 || 0) + '</td>';
                    html += '<td>' + formatCurrency(data.weeks.week_4 || 0) + '</td>';
                    html += '<td>' + formatCurrency(data.weeks.week_5 || 0) + '</td>';
                    html += '<td>' + formatCurrency(data.total_sales) + '</td>';
                    html += '<td>' + formatCurrency(data.total_purchase) + '</td>';
                    html += '<td>' + formatCurrency(data.total_profit) + '</td>';
                    html += '<td>' + profit_margin + '%</td>';
                    html += '</tr>';
                    
                    grandTotalSales += data.total_sales;
                    grandTotalPurchase += data.total_purchase;
                    grandTotalProfit += data.total_profit;
                });
                
                var grandProfitMargin = grandTotalSales > 0 ? 
                    ((grandTotalProfit / grandTotalSales) * 100).toFixed(2) : 0;
                
                html += '<tr class="bg-gray font-17 text-center" style="font-weight: bold;">';
                html += '<td>TOTAL</td>';
                html += '<td>' + formatCurrency(totalWeekSales[0]) + '</td>';
                html += '<td>' + formatCurrency(totalWeekSales[1]) + '</td>';
                html += '<td>' + formatCurrency(totalWeekSales[2]) + '</td>';
                html += '<td>' + formatCurrency(totalWeekSales[3]) + '</td>';
                html += '<td>' + formatCurrency(totalWeekSales[4]) + '</td>';
                html += '<td>' + formatCurrency(grandTotalSales) + '</td>';
                html += '<td>' + formatCurrency(grandTotalPurchase) + '</td>';
                html += '<td>' + formatCurrency(grandTotalProfit) + '</td>';
                html += '<td>' + grandProfitMargin + '%</td>';
                html += '</tr>';
                
                $('#staff_performance_body').html(html || 
                    '<tr><td colspan="10" class="text-center">{{ __("No data available") }}</td></tr>');
                
                console.log('📊 Staff performance table updated with', sortedStaff.length, 'real staff members');
            },
            error: function() {
                $('#staff_performance_body').html(
                    '<tr><td colspan="10" class="text-center text-danger">{{ __("Error loading data") }}</td></tr>'
                );
            }
        });
    }

    // Load purchase summary
    function loadPurchaseSummary() {
        $.ajax({
            url: '{{ route("advancedreports.product.purchase-summary") }}',
            type: 'GET',
            data: getFilterData(),
            success: function(response) {
                var html = '';
                $.each(response, function(index, purchase) {
                    html += '<tr>';
                    html += '<td>' + formatDate(purchase.purchase_date) + '</td>';
                    html += '<td>' + purchase.invoice_no + '</td>';
                    html += '<td>' + purchase.supplier_name + '</td>';
                    html += '<td>' + formatCurrency(purchase.purchase_amt) + '</td>';
                    html += '<td>' + formatCurrency(purchase.purchase_discount) + '</td>';
                    html += '<td>' + formatCurrency(purchase.purchase_total) + '</td>';
                    html += '</tr>';
                });
                $('#purchase_summary_body').html(html || '<tr><td colspan="6" class="text-center">{{ __("No data available") }}</td></tr>');
            },
            error: function() {
                $('#purchase_summary_body').html('<tr><td colspan="6" class="text-center text-danger">{{ __("Error loading data") }}</td></tr>');
            }
        });
    }

    // Load stock valuation
    function loadStockValuation() {
        $.ajax({
            url: '{{ route("advancedreports.product.stock-valuation") }}',
            type: 'GET',
            data: getFilterData(),
            success: function(response) {
                $('#stock_value_purchase').text(formatCurrency(response.current_stock_value_by_purchase_price || 0));
                $('#stock_value_sales').text(formatCurrency(response.current_stock_value_by_sales_price || 0));
                $('#potential_profit').text(formatCurrency(response.potential_profit || 0));
                $('#stock_profit_margin').text((response.profit_margin || 0) + '%');
            },
            error: function() {
                $('#stock_value_purchase, #stock_value_sales, #potential_profit').text('--');
                $('#stock_profit_margin').text('0%');
            }
        });
    }

  // Update the main updateExcelTables function
function updateExcelTables() {
    console.log('📊 Updating all Excel tables...');
    updateWeeklySalesExcelTable();
    updatePurchaseSummaryExcelTable();
    updateStaffPerformanceExcelTable();
}

    function updateStaffPerformanceExcelTable() {
        $.ajax({
            url: '{{ route("advancedreports.product.staff-performance") }}',
            type: 'GET',
            data: getFilterData(),
            success: function(response) {
                var html = '';
                var staffData = {};
                
                $.each(response, function(index, item) {
                    var staffName = (item.staff_name || 'Unknown Staff').trim();
                    
                    if (staffName !== 'Unknown Staff') {
                        if (!staffData[staffName]) {
                            staffData[staffName] = {
                                weeks: {},
                                total_sales: 0,
                                total_purchase: 0,
                                total_profit: 0
                            };
                        }
                        
                        var weekNum = parseInt(item.week_number || 0);
                        var sales = parseFloat(item.total_sales || 0);
                        var purchase = parseFloat(item.equivalent_purchase_value || 0);
                        var profit = parseFloat(item.profit_earned || 0);
                        
                        staffData[staffName].weeks['week_' + weekNum] = sales;
                        staffData[staffName].total_sales += sales;
                        staffData[staffName].total_purchase += purchase;
                        staffData[staffName].total_profit += profit;
                    }
                });

                var totalWeeks = [0, 0, 0, 0, 0];
                var totalSales = 0, totalPurchase = 0, totalProfit = 0;
                
                var sortedStaff = Object.keys(staffData).sort((a, b) => 
                    staffData[b].total_sales - staffData[a].total_sales
                );
                
                $.each(sortedStaff, function(index, staffName) {
                    var data = staffData[staffName];
                    var profit_margin = data.total_sales > 0 ? 
                        ((data.total_profit / data.total_sales) * 100).toFixed(2) : 0;
                    
                    html += '<tr>';
                    html += '<td class="row-header">' + staffName.toUpperCase() + '</td>';
                    html += '<td class="data-cell staff-week1">' + formatCurrency(data.weeks.week_1 || 0) + '</td>';
                    html += '<td class="data-cell staff-week2">' + formatCurrency(data.weeks.week_2 || 0) + '</td>';
                    html += '<td class="data-cell staff-week3">' + formatCurrency(data.weeks.week_3 || 0) + '</td>';
                    html += '<td class="data-cell staff-week4">' + formatCurrency(data.weeks.week_4 || 0) + '</td>';
                    html += '<td class="data-cell staff-week5">' + formatCurrency(data.weeks.week_5 || 0) + '</td>';
                    html += '<td class="data-cell staff-total">' + formatCurrency(data.total_sales) + '</td>';
                    html += '<td class="data-cell staff-purchase">' + formatCurrency(data.total_purchase) + '</td>';
                    html += '<td class="data-cell staff-profit">' + formatCurrency(data.total_profit) + '</td>';
                    html += '<td class="data-cell staff-margin">' + profit_margin + '%</td>';
                    html += '</tr>';
                    
                    for (let i = 1; i <= 5; i++) {
                        totalWeeks[i-1] += parseFloat(data.weeks['week_' + i] || 0);
                    }
                    totalSales += data.total_sales;
                    totalPurchase += data.total_purchase;
                    totalProfit += data.total_profit;
                });
                
                var totalMargin = totalSales > 0 ? ((totalProfit / totalSales) * 100).toFixed(2) : 0;
                html += '<tr class="total-row">';
                html += '<td class="row-header">TOTAL</td>';
                html += '<td class="data-cell">' + formatCurrency(totalWeeks[0]) + '</td>';
                html += '<td class="data-cell">' + formatCurrency(totalWeeks[1]) + '</td>';
                html += '<td class="data-cell">' + formatCurrency(totalWeeks[2]) + '</td>';
                html += '<td class="data-cell">' + formatCurrency(totalWeeks[3]) + '</td>';
                html += '<td class="data-cell">' + formatCurrency(totalWeeks[4]) + '</td>';
                html += '<td class="data-cell">' + formatCurrency(totalSales) + '</td>';
                html += '<td class="data-cell">' + formatCurrency(totalPurchase) + '</td>';
                html += '<td class="data-cell">' + formatCurrency(totalProfit) + '</td>';
                html += '<td class="data-cell">' + totalMargin + '%</td>';
                html += '</tr>';
                
                $('#staff_performance_excel_body').html(html);
                console.log('📊 Excel staff table updated with', sortedStaff.length, 'real staff members');
            },
            error: function() {
                console.error('📊 Error loading staff data for Excel table');
            }
        });
    }

    // Helper functions
    function getFilterData() {
        return {
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            location_id: $('#location_id').val(),
            customer_id: $('#customer_id').val(),
            customer_group_id: $('#customer_group_id').val(),
            category_id: $('#category_id').val(),
            brand_id: $('#brand_id').val(),
            unit_id: $('#unit_id').val(),
            user_id: $('#user_id').val(),
            payment_method: $('#payment_method').val()
        };
    }

    function updateSummaryWidgets(response) {
        $('#total_products').text(response.total_products);
        $('#total_customers').text(response.total_customers);
        $('#total_qty_sold').text(response.total_qty_sold);
        $('#total_sales_amount').text(response.total_sales_amount);
        $('#total_tax_amount').text(response.total_tax_amount);
        $('#total_discount_amount').text(response.total_discount_amount);
        $('#total_profit').text(response.total_profit);
        $('#profit_margin').text(response.profit_margin);
    }

    function updateTopProducts(topProducts) {
        var topProductsHtml = '';
        if (topProducts && topProducts.length > 0) {
            $.each(topProducts, function(index, product) {
                topProductsHtml += '<tr>';
                topProductsHtml += '<td>' + product.name + '</td>';
                topProductsHtml += '<td>' + product.total_sold + '</td>';
                topProductsHtml += '<td>' + product.total_amount + '</td>';
                topProductsHtml += '</tr>';
            });
        } else {
            topProductsHtml = '<tr><td colspan="3" class="text-center">{{ __("No data available") }}</td></tr>';
        }
        $('#top_products_body').html(topProductsHtml);
    }

    function handleSummaryError(xhr, status, error) {
        console.error('Error loading summary:', error);
        if (typeof toastr !== 'undefined') {
            toastr.error('{{ __("Error loading summary data") }}');
        }
        
        $('#summary_widgets .info-box-number').text('--');
        $('#top_products_body').html('<tr><td colspan="3" class="text-center text-danger">{{ __("Error loading data") }}</td></tr>');
    }

    function formatDate(dateString) {
    if (!dateString) return '';
    
    try {
        var date = new Date(dateString);
        var day = date.getDate().toString().padStart(2, '0');
        var month = date.toLocaleString('default', { month: 'short' });
        var year = date.getFullYear().toString().substr(-2);
        return day + '-' + month + '-' + year;
    } catch (e) {
        return dateString;
    }
}

    // Auto-refresh every 5 minutes
    setInterval(function() {
        if ($('#auto_refresh').is(':checked')) {
            loadSummary();
            productTable.ajax.reload(null, false);
        }
    }, 300000);

    // Load initial data
    loadSummary();
    
    if (typeof toastr !== 'undefined') {
        toastr.info('{{ __("Product Performance Report loaded successfully") }}');
    }
});



$(document).ready(function() {
    // Initialize the date ranges on page load
    updateReportDateRanges();
    
    // Also update when dates change manually
    $('#start_date, #end_date').on('change', function() {
        updateReportDateRanges();
    });
});

// ==============================================
// FIXED STAFF CHART CODE - COMPLETE SOLUTION
// ==============================================

// Global variables
window.staffChart = null;
window.staffChartInitialized = false;

// Create chart function
function createStaffChart(staffNames) {
    const ctx = document.getElementById('staffPerformanceChart');
    if (!ctx) {
        console.error('📊 Chart canvas not found');
        return;
    }
    
    console.log('📊 Creating chart for staff:', staffNames);
    
    // Destroy existing chart
    if (window.staffChart) {
        window.staffChart.destroy();
        window.staffChart = null;
    }
    
    // Colors for different staff members
    const colors = [
        { bg: '#74b9ff', border: '#0984e3' },
        { bg: '#fd79a8', border: '#e84393' },
        { bg: '#a29bfe', border: '#6c5ce7' },
        { bg: '#00b894', border: '#00a085' },
        { bg: '#fdcb6e', border: '#e17055' },
        { bg: '#e17055', border: '#d63031' },
        { bg: '#00cec9', border: '#00b894' }
    ];
    
    // Create datasets for each staff member
    const datasets = staffNames.map((staffName, index) => ({
        label: staffName,
        data: [0, 0, 0, 0, 0, 0], // 5 weeks + total
        backgroundColor: colors[index % colors.length].bg,
        borderColor: colors[index % colors.length].border,
        borderWidth: 1
    }));
    
    // Create chart
    window.staffChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['WEEK 1\n1st—7th', 'WEEK 2\n8th—14th', 'WEEK 3\n15th—21st', 'WEEK 4\n22nd—28th', 'WEEK 5\n29th—31st', 'TOTAL SALES'],
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: 'white',
                    bodyColor: 'white',
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': $' + 
                                parseFloat(context.parsed.y || 0).toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: '#636e72' },
                    ticks: { color: 'white', font: { size: 10 }, maxRotation: 0 }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#636e72' },
                    ticks: { 
                        color: 'white',
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    },
                    title: {
                        display: true,
                        text: 'SALES AMOUNT',
                        color: 'white',
                        font: { size: 12, weight: 'bold' }
                    }
                }
            }
        }
    });
    
    updateChartLegend(staffNames, colors);
    console.log('📊 Chart created successfully');
}

function updateChartLegend(staffNames, colors) {
    const legendContainer = document.querySelector('.chart-legend');
    const dynamicLegend = document.getElementById('dynamic-staff-legend');
    
    if (!legendContainer && !dynamicLegend) {
        console.error('📊 Legend container not found');
        return;
    }
    
    const container = dynamicLegend || legendContainer;
    
    let legendHtml = '';
    staffNames.forEach((staffName, index) => {
        const color = colors[index % colors.length];
        legendHtml += `
            <div class="legend-item">
                <span class="legend-color" style="background-color: ${color.bg};"></span>
                ${staffName}
            </div>
        `;
    });
    
    container.innerHTML = legendHtml;
    console.log('📊 Legend updated with staff:', staffNames);
}


function updateStaffChartData(staffNames, chartData) {
    if (!window.staffChart) {
        createStaffChart(staffNames);
        setTimeout(() => updateStaffChartData(staffNames, chartData), 500);
        return;
    }
    
    if (window.staffChart.data.datasets.length !== staffNames.length) {
        createStaffChart(staffNames);
        setTimeout(() => updateStaffChartData(staffNames, chartData), 500);
        return;
    }
    
    console.log('📊 Updating chart data for:', staffNames);
    
    staffNames.forEach((staffName, index) => {
        if (window.staffChart.data.datasets[index]) {
            const data = chartData[staffName] || [0, 0, 0, 0, 0, 0];
            window.staffChart.data.datasets[index].data = data;
            window.staffChart.data.datasets[index].label = staffName;
            
            console.log(`📊 Updated ${staffName} with data:`, data);
        }
    });
    
    window.staffChart.update('none');
    
    // **FIXED: Force update the legend**
    const colors = [
        { bg: '#74b9ff', border: '#0984e3' },
        { bg: '#fd79a8', border: '#e84393' },
        { bg: '#a29bfe', border: '#6c5ce7' },
        { bg: '#00b894', border: '#00a085' },
        { bg: '#fdcb6e', border: '#e17055' },
        { bg: '#e17055', border: '#d63031' },
        { bg: '#00cec9', border: '#00b894' }
    ];
    
    updateChartLegend(staffNames, colors);
    console.log('📊 Chart and legend updated successfully');
}

function processStaffApiData(response) {
    console.log('📊 Processing staff API data:', response);
    
    if (!response || response.length === 0) {
        return {
            staffNames: ['No Data Available'],
            chartData: { 'No Data Available': [0, 0, 0, 0, 0, 0] }
        };
    }
    
    // Get unique staff names
    const uniqueStaff = [...new Set(response.map(item => {
        let name = (item.staff_name || '').trim();
        if (!name || name === 'Unknown Staff' || name === '') return null;
        return name;
    }))].filter(Boolean);
    
    if (uniqueStaff.length === 0) {
        return {
            staffNames: ['No Data Available'],
            chartData: { 'No Data Available': [0, 0, 0, 0, 0, 0] }
        };
    }
    
    console.log('📊 Found unique staff:', uniqueStaff);
    
    // Initialize staff data structure
    const staffData = {};
    uniqueStaff.forEach(name => {
        staffData[name] = {
            weeks: [0, 0, 0, 0, 0], // Week 1-5
            total: 0
        };
    });
    
    // **FIXED: Process each response item correctly**
    response.forEach(item => {
        const staffName = (item.staff_name || '').trim();
        if (!uniqueStaff.includes(staffName)) return;
        
        const weekNum = parseInt(item.week_number) || 0;
        // **FIXED: Parse sales as float, handle string numbers like "375.00000000"**
        const sales = parseFloat(item.total_sales || item.sales || 0);
        
        console.log(`📊 Processing: ${staffName} - Week ${weekNum}: $${sales}`);
        
        // Add to specific week (1-5)
        if (weekNum >= 1 && weekNum <= 5) {
            staffData[staffName].weeks[weekNum - 1] += sales;
        }
        
        // Always add to total
        staffData[staffName].total += sales;
    });
    
    // Convert to chart format [week1, week2, week3, week4, week5, total]
    const chartData = {};
    uniqueStaff.forEach(staffName => {
        const data = staffData[staffName];
        chartData[staffName] = [...data.weeks, data.total];
        console.log(`📊 Final data for ${staffName}:`, chartData[staffName]);
    });
    
    console.log('📊 Final processed chart data:', chartData);
    
    return {
        staffNames: uniqueStaff,
        chartData: chartData
    };
}
// Function to update the date range display in report titles
// COMPREHENSIVE: Function to update all report date ranges
function updateReportDateRanges() {
    var startDate = $('#start_date').val();
    var endDate = $('#end_date').val();
    
    if (startDate && endDate) {
        // Format dates to DD-MM-YYYY
        var formattedStartDate = moment(startDate).format('DD-MM-YYYY');
        var formattedEndDate = moment(endDate).format('DD-MM-YYYY');
        
        var dateRangeText = ' - ' + formattedStartDate + ' To ' + formattedEndDate;
        
        // Update Weekly Sales Summary Report title
        $('#weekly_sales_date_range').text(dateRangeText);
        
        // Update Purchase Summary Report title (if you add the span)
        if ($('#purchase_summary_date_range').length) {
            $('#purchase_summary_date_range').text(dateRangeText);
        }
        
        // Update Staff Performance Analysis title (if you add the span)
        if ($('#staff_performance_date_range').length) {
            $('#staff_performance_date_range').text(dateRangeText);
        }
        
        // Update Stock Valuation with current end date
        if ($('#stock_valuation_current_date').length) {
            var stockDate = moment(endDate).format('DD - MMM - YYYY').toUpperCase();
            $('#stock_valuation_current_date').text(stockDate);
        }
        
        console.log('📅 All report date ranges updated:', dateRangeText);
    }
}
// Function to format date from YYYY-MM-DD to DD-MM-YYYY
function formatDateForDisplay(dateString) {
    if (!dateString) return '';
    
    try {
        return moment(dateString).format('DD-MM-YYYY');
    } catch (e) {
        console.error('Date formatting error:', e);
        return dateString;
    }
}
// **FIXED: Main function to load and update chart**
function loadStaffChartData() {
    console.log('📊 Loading staff chart data...');
    
    // Get current filter values (don't modify them)
    const filterData = {
        start_date: $('#start_date').val(),
        end_date: $('#end_date').val(),
        location_id: $('#location_id').val(),
        customer_id: $('#customer_id').val(),
        customer_group_id: $('#customer_group_id').val(),
        category_id: $('#category_id').val(),
        brand_id: $('#brand_id').val(),
        unit_id: $('#unit_id').val(),
        user_id: $('#user_id').val(),
        payment_method: $('#payment_method').val()
    };
    
    console.log('📊 Filter data:', filterData);
    
    $.ajax({
        url: '/advanced-reports/product-report/staff-performance',
        type: 'GET',
        data: filterData,
        timeout: 15000,
        success: function(response) {
            console.log('📊 Staff API response received:', response);
            
            if (!response || response.length === 0) {
                console.log('📊 No staff data received');
                if (!window.staffChart) {
                    createStaffChart(['No Data Available']);
                }
                return;
            }
            
            // **FIXED: Process the data**
            const { staffNames, chartData } = processStaffApiData(response);
            console.log('📊 Processed staff names:', staffNames);
            console.log('📊 Processed chart data:', chartData);
            
            // **FIXED: Create or update chart**
            if (!window.staffChart) {
                console.log('📊 Creating new chart...');
                createStaffChart(staffNames);
                // Wait for chart to be created, then update data
                setTimeout(() => {
                    console.log('📊 Updating chart data after creation...');
                    updateStaffChartData(staffNames, chartData);
                }, 500);
            } else {
                console.log('📊 Updating existing chart...');
                updateStaffChartData(staffNames, chartData);
            }
        },
        error: function(xhr, status, error) {
            console.error('📊 Staff API error:', status, error);
            console.error('📊 Response:', xhr.responseText);
            
            if (!window.staffChart) {
                createStaffChart(['Error Loading Data']);
            }
        }
    });
}

// Initialize staff chart
function initStaffChart() {
    if (window.staffChartInitialized) {
        console.log('📊 Chart already initialized');
        return;
    }
    
    console.log('📊 Initializing staff chart...');
    
    if (typeof Chart === 'undefined') {
        console.error('📊 Chart.js not loaded, retrying...');
        setTimeout(initStaffChart, 1000);
        return;
    }
    
    window.staffChartInitialized = true;
    
    createStaffChart(['Loading...']);
    setTimeout(loadStaffChartData, 1000);
}

// Event binding for staff chart
$(document).ready(function() {
    console.log('📊 Setting up staff chart...');
    
    // Initialize chart after a delay to ensure everything is loaded
    setTimeout(initStaffChart, 2000);
    
    // Bind to filter button
    $('#apply_filters').on('click.staffChart', function() {
        console.log('📊 Filters applied, updating staff chart...');
        setTimeout(loadStaffChartData, 1500);
    });
    
    // Bind to clear filters button
    $('#clear_filters').on('click.staffChart', function() {
        console.log('📊 Filters cleared, updating staff chart...');
        setTimeout(loadStaffChartData, 1500);
    });
});

// Manual test functions for debugging
window.testStaffChart = function() {
    console.log('📊 MANUAL TEST: Loading staff chart');
    loadStaffChartData();
};

window.resetStaffChart = function() {
    console.log('📊 MANUAL TEST: Resetting staff chart');
    window.staffChartInitialized = false;
    if (window.staffChart) {
        window.staffChart.destroy();
        window.staffChart = null;
    }
    setTimeout(initStaffChart, 500);
};

window.debugStaffChart = function() {
    console.log('📊 DEBUG INFO:');
    console.log('Chart exists:', !!window.staffChart);
    console.log('Chart initialized:', window.staffChartInitialized);
    console.log('Chart.js loaded:', typeof Chart !== 'undefined');
    console.log('Canvas exists:', !!document.getElementById('staffPerformanceChart'));
    console.log('Legend exists:', !!document.querySelector('.chart-legend'));
    
    // Test API call
    $.get('/advanced-reports/product-report/staff-performance', function(data) {
        console.log('📊 API Test Response:', data);
    }).fail(function(error) {
        console.error('📊 API Test Error:', error);
    });
};

</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
    // Add this to your existing JavaScript section - UPDATED VERSION

// Date range utility functions for product report
function getProductDateRange(rangeType) {
    var start, end;
    var today = moment();
    
    switch(rangeType) {
        case 'today':
            start = today.clone();
            end = today.clone();
            break;
        case 'yesterday':
            start = today.clone().subtract(1, 'day');
            end = today.clone().subtract(1, 'day');
            break;
        case 'this_week':
            start = today.clone().startOf('week');
            end = today.clone().endOf('week');
            break;
        case 'last_week':
            start = today.clone().subtract(1, 'week').startOf('week');
            end = today.clone().subtract(1, 'week').endOf('week');
            break;
        case 'this_month':
            start = today.clone().startOf('month');
            end = today.clone().endOf('month');
            break;
        case 'last_month':
            start = today.clone().subtract(1, 'month').startOf('month');
            end = today.clone().subtract(1, 'month').endOf('month');
            break;
        case 'last_30_days':
            start = today.clone().subtract(30, 'days');
            end = today.clone();
            break;
        case 'this_quarter':
            start = today.clone().startOf('quarter');
            end = today.clone().endOf('quarter');
            break;
        case 'last_quarter':
            start = today.clone().subtract(1, 'quarter').startOf('quarter');
            end = today.clone().subtract(1, 'quarter').endOf('quarter');
            break;
        case 'this_year':
            start = today.clone().startOf('year');
            end = today.clone().endOf('year');
            break;
        case 'last_year':
            start = today.clone().subtract(1, 'year').startOf('year');
            end = today.clone().subtract(1, 'year').endOf('year');
            break;
        default:
            // Default to last 30 days
            start = today.clone().subtract(30, 'days');
            end = today.clone();
    }
    
    return {
        start: start,
        end: end
    };
}

function updateProductDateFilter(rangeType, customStart = null, customEnd = null) {
    var dateRange, displayText;
    
    if (rangeType === 'custom' && customStart && customEnd) {
        dateRange = {
            start: moment(customStart),
            end: moment(customEnd)
        };
        displayText = customStart.format('MMM DD, YYYY') + ' ~ ' + customEnd.format('MMM DD, YYYY');
    } else {
        dateRange = getProductDateRange(rangeType);
        
        // Generate display text
        var rangeLabels = {
            'today': '{{ __("advancedreports::lang.today") }}',
            'yesterday': '{{ __("advancedreports::lang.yesterday") }}',
            'this_week': '{{ __("advancedreports::lang.this_week") }}',
            'last_week': '{{ __("advancedreports::lang.last_week") }}',
            'this_month': '{{ __("advancedreports::lang.this_month") }}',
            'last_month': '{{ __("advancedreports::lang.last_month") }}',
            'last_30_days': '{{ __("advancedreports::lang.last_30_days") }}',
            'this_quarter': '{{ __("advancedreports::lang.this_quarter") }}',
            'last_quarter': '{{ __("advancedreports::lang.last_quarter") }}',
            'this_year': '{{ __("advancedreports::lang.this_year") }}',
            'last_year': '{{ __("advancedreports::lang.last_year") }}'
        };
        
        displayText = rangeLabels[rangeType] || (dateRange.start.format('MMM DD, YYYY') + ' ~ ' + dateRange.end.format('MMM DD, YYYY'));
    }
    
    // Update UI
    $('#product_date_filter_text').text(displayText);
    $('#start_date').val(dateRange.start.format('YYYY-MM-DD'));
    $('#end_date').val(dateRange.end.format('YYYY-MM-DD'));
    
    // **NEW: Update report title date ranges**
    updateReportDateRanges();
    
    // IMPORTANT: Trigger change events so other code knows the dates changed
    $('#start_date, #end_date').trigger('change');
    
    // Store current range type
    $('#product_date_filter_btn').data('current-range', rangeType);
    
    console.log('📅 Date filter updated:', displayText, 'Start:', dateRange.start.format('YYYY-MM-DD'), 'End:', dateRange.end.format('YYYY-MM-DD'));
}
// ENHANCED: Function to trigger data reload with proper sequencing
function triggerDataRefresh() {
    console.log('📅 Triggering data refresh...');
    
    // **NEW: Update report date ranges first**
    updateReportDateRanges();
    
    try {
        // Force trigger DataTable reload with fresh data
        if (typeof productTable !== 'undefined' && productTable) {
            console.log('📅 Reloading DataTable...');
            productTable.ajax.reload(null, false); // false = stay on current page
        }
        
        // Call your existing summary functions
        if (typeof loadSummary === 'function') {
            console.log('📅 Loading summary...');
            loadSummary();
        }
        
        // Call other refresh functions from your original code
        if (typeof loadWeeklySales === 'function') {
            loadWeeklySales();
        }
        if (typeof loadStaffPerformance === 'function') {
            loadStaffPerformance();
        }
        if (typeof loadPurchaseSummary === 'function') {
            loadPurchaseSummary();
        }
        if (typeof loadStockValuation === 'function') {
            loadStockValuation();
        }
        if (typeof updateExcelTables === 'function') {
            updateExcelTables();
        }
        
        console.log('📅 Data refresh completed');
        
    } catch (error) {
        console.error('📅 Error in data refresh:', error);
    }
}
// Add this to your document.ready function
$(document).ready(function() {
    // Initialize custom date range picker (hidden)
    if ($('#custom_date_range').length) {
        $('#custom_date_range').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: '@lang("lang_v1.clear")',
                applyLabel: '@lang("lang_v1.apply")',
                format: 'YYYY-MM-DD'
            }
        });
        
        $('#custom_date_range').on('apply.daterangepicker', function(ev, picker) {
            updateProductDateFilter('custom', picker.startDate, picker.endDate);
            // ENHANCED: Use proper refresh function
            setTimeout(triggerDataRefresh, 200);
        });
        
        $('#custom_date_range').on('cancel.daterangepicker', function(ev, picker) {
            // Reset to last 30 days if cancelled
            updateProductDateFilter('last_30_days');
            setTimeout(triggerDataRefresh, 200);
        });
    }

    // Handle dropdown date filter selection
    $('.date-filter-dropdown .dropdown-menu a').click(function(e) {
        e.preventDefault();
        var rangeType = $(this).data('range');
        
        console.log('📅 Date range selected:', rangeType);
        
        if (rangeType === 'custom') {
            // Show the daterangepicker
            $('#custom_date_range').click();
        } else {
            updateProductDateFilter(rangeType);
            // ENHANCED: Use proper refresh function
            setTimeout(triggerDataRefresh, 200);
        }
    });

    // Set default to last 30 days (matching your current default)
    updateProductDateFilter('last_30_days');

    // ENHANCED: Update existing filter functions to work with new date system
    $(document).off('click.datefilter', '#apply_filters').on('click.datefilter', '#apply_filters', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $btn = $(this);
        
        // Don't do anything if already processing
        if ($btn.prop('disabled')) {
            return false;
        }
        
        var originalHtml = $btn.html();
        
        $btn.html('<i class="fa fa-spinner fa-spin"></i> @lang("advancedreports::lang.applying")').prop('disabled', true);
        
        console.log('📅 Apply filters clicked');
        
        try {
            // Ensure dates are set correctly
            var currentRange = $('#product_date_filter_btn').data('current-range') || 'last_30_days';
            updateProductDateFilter(currentRange);
            
            // Use enhanced refresh function
            triggerDataRefresh();
            
        } catch (error) {
            console.error('📅 Error in apply filters:', error);
        }
        
        // Always restore button state after delay
        setTimeout(function() {
            $btn.html('<i class="fa fa-filter"></i> @lang("advancedreports::lang.apply_filters")').prop('disabled', false);
            console.log('📅 Apply filters button restored');
        }, 2000);
        
        return false;
    });

    // ENHANCED: Update clear filters to reset date filter
    $(document).off('click.datefilter', '#clear_filters').on('click.datefilter', '#clear_filters', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $btn = $(this);
        
        // Don't do anything if already processing
        if ($btn.prop('disabled')) {
            return false;
        }
        
        var originalHtml = $btn.html();
        
        $btn.html('<i class="fa fa-spinner fa-spin"></i> @lang("advancedreports::lang.clearing")').prop('disabled', true);
        
        console.log('📅 Clear filters clicked');
        
        try {
            // Reset all form fields
            if ($('#product_report_filter_form').length) {
                $('#product_report_filter_form')[0].reset();
            }
            $('.select2').val(null).trigger('change');
            
            // Reset date filter to last 30 days
            updateProductDateFilter('last_30_days');
            
            // Use enhanced refresh function
            triggerDataRefresh();
            
        } catch (error) {
            console.error('📅 Error in clear filters:', error);
        }
        
        // Always restore button state after delay
        setTimeout(function() {
            $btn.html('<i class="fa fa-refresh"></i> @lang("advancedreports::lang.clear")').prop('disabled', false);
            console.log('📅 Clear filters button restored');
        }, 2000);
        
        return false;
    });
    
    console.log('📅 Product date filter initialized');
});
</script>


@endsection