@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.sales_report'))

@section('css')
@parent
<style>
    /* Force Tailwind background colors for widget icons */
    .tw-bg-emerald-100 {
        background-color: #dcfce7 !important;
    }

    .tw-text-emerald-500 {
        color: #10b981 !important;
    }

    .tw-bg-sky-100 {
        background-color: #e0f2fe !important;
    }

    .tw-text-sky-500 {
        color: #0ea5e9 !important;
    }

    .tw-bg-green-100 {
        background-color: #dcfce7 !important;
    }

    .tw-text-green-500 {
        color: #22c55e !important;
    }

    .tw-bg-yellow-100 {
        background-color: #fef3c7 !important;
    }

    .tw-text-yellow-500 {
        color: #eab308 !important;
    }

    .tw-bg-red-100 {
        background-color: #fee2e2 !important;
    }

    .tw-text-red-500 {
        color: #ef4444 !important;
    }

    .tw-bg-orange-100 {
        background-color: #ffedd5 !important;
    }

    .tw-text-orange-500 {
        color: #f97316 !important;
    }

    .tw-bg-blue-100 {
        background-color: #dbeafe !important;
    }

    .tw-text-blue-500 {
        color: #3b82f6 !important;
    }

    .tw-bg-teal-100 {
        background-color: #ccfbf1 !important;
    }

    .tw-text-teal-500 {
        color: #14b8a6 !important;
    }
</style>
@endsection

@section('content')

<!-- Add CSRF token meta tag -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang('advancedreports::lang.sales_report')
        <small>@lang('advancedreports::lang.manage_sales_report')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', [
            'title' => __('report.filters'),
            'class' => 'box-primary'
            ])
            <div class="col-lg-3 col-md-4 col-sm-12">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'location_filter']) !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-12">
                <div class="form-group">
                    {!! Form::label('customer_id', __('contact.customer') . ':') !!}
                    {!! Form::select('customer_id', $customers, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'customer_filter']) !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-12">
                <div class="form-group">
                    {!! Form::label('payment_status', __('purchase.payment_status') . ':') !!}
                    {!! Form::select('payment_status', [
                    '' => __('lang_v1.all'),
                    'paid' => __('lang_v1.paid'),
                    'due' => __('lang_v1.due'),
                    'partial' => __('lang_v1.partial')
                    ], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' =>
                    'payment_status_filter']) !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-12">
                <div class="form-group">
                    {!! Form::label('payment_method', __('lang_v1.payment_method') . ':') !!}
                    {!! Form::select('payment_method', $payment_types, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'payment_method_filter']) !!}
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
                            <li class="divider"></li>
                            <li><a href="#" data-range="custom">@lang('advancedreports::lang.custom_range')</a></li>
                        </ul>
                    </div>
                    <!-- Hidden date inputs for storing selected range -->
                    <input type="hidden" id="start_date" name="start_date" />
                    <input type="hidden" id="end_date" name="end_date" />
                    <!-- Hidden daterangepicker input for custom range -->
                    <input type="text" id="custom_date_range" class="form-control" style="display: none;" />
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <br>
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

    <!-- Summary Cards -->
    <div class="row" id="summary_cards" style="display: none;">
        <div class="col-lg-3 col-md-4 col-sm-12">
            @component('advancedreports::components.static', [
            'svg' => '<svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
            </svg>',
            'svg_bg' => 'tw-bg-sky-100',
            'svg_text' => 'tw-text-sky-500'
            ])
            <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">
                @lang('advancedreports::lang.total_transactions')
            </p>
            <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-font-semibold tw-tracking-tight tw-font-mono"
                id="total_transactions">
                0
            </p>
            <p class="tw-text-xs tw-text-gray-500 tw-mt-1" id="transactions_change">
                <span id="total_customers_count">0 customers</span> • <span id="total_products_count">0 products</span>
            </p>
            @endcomponent
        </div>

        <div class="col-lg-3 col-md-4 col-sm-12">
            @component('advancedreports::components.static', [
            'svg' => '<svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path
                    d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12" />
                <path d="M20 12v4h-4a2 2 0 0 1 0 -4h4" />
            </svg>',
            'svg_bg' => 'tw-bg-green-100',
            'svg_text' => 'tw-text-green-500'
            ])
            <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">
                @lang('advancedreports::lang.total_sales_amount')
            </p>
            <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-font-semibold tw-tracking-tight tw-font-mono"
                id="total_sales">
                <span class="display_currency" data-currency_symbol="true">0</span>
            </p>
            <p class="tw-text-xs tw-text-gray-500 tw-mt-1" id="sales_change">
                0% from last period
            </p>
            @endcomponent
        </div>

        <div class="col-lg-3 col-md-4 col-sm-12">
            @component('advancedreports::components.static', [
            'svg' => '<svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2" />
                <path d="M9 12l2 2l4 -4" />
            </svg>',
            'svg_bg' => 'tw-bg-yellow-100',
            'svg_text' => 'tw-text-yellow-500'
            ])
            <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">
                @lang('advancedreports::lang.total_tax_amount')
            </p>
            <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-font-semibold tw-tracking-tight tw-font-mono"
                id="total_tax">
                <span class="display_currency" data-currency_symbol="true">0</span>
            </p>
            <p class="tw-text-xs tw-text-gray-500 tw-mt-1" id="tax_change">
                0% from last period
            </p>
            @endcomponent
        </div>

        <div class="col-lg-3 col-md-4 col-sm-12">
            @component('advancedreports::components.static', [
            'svg' => '<svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                <path d="M17 17h-11v-14h-2" />
                <path d="M6 5l14 1l-1 7h-13" />
            </svg>',
            'svg_bg' => 'tw-bg-red-100',
            'svg_text' => 'tw-text-red-500'
            ])
            <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">
                @lang('advancedreports::lang.total_discount')
            </p>
            <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-font-semibold tw-tracking-tight tw-font-mono"
                id="total_discount">
                <span class="display_currency" data-currency_symbol="true">0</span>
            </p>
            <p class="tw-text-xs tw-text-gray-500 tw-mt-1" id="discount_change">
                Line: <span id="line_discount_amount"><span class="display_currency"
                        data-currency_symbol="true">0</span></span> • Invoice: <span id="invoice_discount_amount"><span
                        class="display_currency" data-currency_symbol="true">0</span></span>
            </p>
            @endcomponent
        </div>
    </div>

    <!-- Payment Status Cards -->
    <div class="row" id="payment_status_cards" style="display: none;">
        <div class="col-lg-3 col-md-4 col-sm-12">
            @component('advancedreports::components.static', [
            'svg' => '<svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M7 12l5 5l10 -10" />
            </svg>',
            'svg_bg' => 'tw-bg-emerald-100',
            'svg_text' => 'tw-text-emerald-500'
            ])
            <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">
                @lang('advancedreports::lang.paid_transactions')
            </p>
            <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-font-semibold tw-tracking-tight tw-font-mono"
                id="paid_transactions_amount">
                <span class="display_currency" data-currency_symbol="true">0</span>
            </p>
            <p class="tw-text-xs tw-text-gray-500 tw-mt-1" id="paid_transactions_count">
                0 invoices paid
            </p>
            @endcomponent
        </div>

        <div class="col-lg-3 col-md-4 col-sm-12">
            @component('advancedreports::components.static', [
            'svg' => '<svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M12 9v4" />
                <path
                    d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" />
                <path d="M12 16h.01" />
            </svg>',
            'svg_bg' => 'tw-bg-orange-100',
            'svg_text' => 'tw-text-orange-500'
            ])
            <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">
                @lang('advancedreports::lang.due_transactions')
            </p>
            <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-font-semibold tw-tracking-tight tw-font-mono"
                id="due_transactions_amount">
                <span class="display_currency" data-currency_symbol="true">0</span>
            </p>
            <p class="tw-text-xs tw-text-gray-500 tw-mt-1" id="due_transactions_count">
                0 invoices due
            </p>
            @endcomponent
        </div>

        <div class="col-lg-3 col-md-4 col-sm-12">
            @component('advancedreports::components.static', [
            'svg' => '<svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                <path d="M12 7v5l3 3" />
            </svg>',
            'svg_bg' => 'tw-bg-blue-100',
            'svg_text' => 'tw-text-blue-500'
            ])
            <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">
                @lang('advancedreports::lang.partial_transactions')
            </p>
            <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-font-semibold tw-tracking-tight tw-font-mono"
                id="partial_transactions_amount">
                <span class="display_currency" data-currency_symbol="true">0</span>
            </p>
            <p class="tw-text-xs tw-text-gray-500 tw-mt-1" id="partial_transactions_count">
                0 invoices partial
            </p>
            @endcomponent
        </div>

        <div class="col-lg-3 col-md-4 col-sm-12">
            @component('advancedreports::components.static', [
            'svg' => '<svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                <path d="M8 7m0 1a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v1a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1z" />
                <path d="M8 14l0 .01" />
                <path d="M12 14l0 .01" />
                <path d="M16 14l0 .01" />
                <path d="M8 17l0 .01" />
                <path d="M12 17l0 .01" />
                <path d="M16 17l0 .01" />
            </svg>',
            'svg_bg' => 'tw-bg-teal-100',
            'svg_text' => 'tw-text-teal-500'
            ])
            <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">
                @lang('advancedreports::lang.average_transaction_value')
            </p>
            <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-font-semibold tw-tracking-tight tw-font-mono"
                id="average_transaction_value">
                <span class="display_currency" data-currency_symbol="true">0</span>
            </p>
            <p class="tw-text-xs tw-text-gray-500 tw-mt-1" id="average_description">
                Per transaction
            </p>
            @endcomponent
        </div>
    </div>

    <!-- Due Amount Analysis Cards -->
    <div id="due_analysis_cards" style="display: none;">
        <div class="tw-mb-4">
            <h4 class="tw-text-lg tw-font-semibold tw-text-gray-700 tw-flex tw-items-center tw-gap-2">
                <svg aria-hidden="true" class="tw-w-5 tw-h-5 tw-text-blue-500" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" />
                    <path d="M16 3v4" />
                    <path d="M8 3v4" />
                    <path d="M4 11h16" />
                    <path d="M7 14h.013" />
                    <path d="M10.01 14h.005" />
                    <path d="M13.01 14h.005" />
                    <path d="M16.015 14h.005" />
                    <path d="M13.015 17h.005" />
                    <path d="M7.01 17h.005" />
                    <path d="M10.01 17h.005" />
                </svg>
                @lang('advancedreports::lang.due_collections_analysis')
            </h4>
        </div>

        <div class="row">
            <div class="col-lg-4 col-md-4 col-sm-12">
                @component('advancedreports::components.static', [
                'svg' => '<svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path
                        d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12" />
                    <path d="M20 12v4h-4a2 2 0 0 1 0 -4h4" />
                </svg>',
                'svg_bg' => 'tw-bg-green-100',
                'svg_text' => 'tw-text-green-500'
                ])
                <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">
                    @lang('advancedreports::lang.collected_today')
                </p>
                <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-font-semibold tw-tracking-tight tw-font-mono"
                    id="due_collected_today">
                    <span class="display_currency" data-currency_symbol="true">0</span>
                </p>
                <p class="tw-text-xs tw-text-gray-500 tw-mt-1">
                    @lang('advancedreports::lang.due_payments_received')
                </p>
                @endcomponent
            </div>

            <div class="col-lg-4 col-md-4 col-sm-12">
                @component('advancedreports::components.static', [
                'svg' => '<svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                    <path d="M12 7v5l3 3" />
                </svg>',
                'svg_bg' => 'tw-bg-yellow-100',
                'svg_text' => 'tw-text-yellow-500'
                ])
                <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">
                    @lang('advancedreports::lang.pending_due_today')
                </p>
                <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-font-semibold tw-tracking-tight tw-font-mono"
                    id="pending_due_today">
                    <span class="display_currency" data-currency_symbol="true">0</span>
                </p>
                <p class="tw-text-xs tw-text-gray-500 tw-mt-1">
                    @lang('advancedreports::lang.todays_unpaid_sales')
                </p>
                @endcomponent
            </div>

            <div class="col-lg-4 col-md-4 col-sm-12">
                @component('advancedreports::components.static', [
                'svg' => '<svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                    <path d="M12 9v4" />
                    <path d="M12 16h.01" />
                </svg>',
                'svg_bg' => 'tw-bg-red-100',
                'svg_text' => 'tw-text-red-500'
                ])
                <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">
                    @lang('advancedreports::lang.overdue_amounts')
                </p>
                <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-font-semibold tw-tracking-tight tw-font-mono"
                    id="overdue_amount">
                    <span class="display_currency" data-currency_symbol="true">0</span>
                </p>
                <p class="tw-text-xs tw-text-gray-500 tw-mt-1">
                    @lang('advancedreports::lang.previous_days_unpaid')
                </p>
                @endcomponent
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' =>
            __('advancedreports::lang.sales_report')])
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="sales_report_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('sale.invoice_no')</th>
                            <th>@lang('contact.customer')</th>
                            <th>@lang('business.business_location')</th>
                            <th>@lang('advancedreports::lang.invoice_subtotal')</th>
                            <th>@lang('sale.tax')</th>
                            <th>@lang('advancedreports::lang.line_discount')</th>
                            <th>@lang('advancedreports::lang.invoice_discount')</th>
                            <th>@lang('advancedreports::lang.total_discount')</th>
                            <th>@lang('sale.total_amount')</th>
                            <th>@lang('advancedreports::lang.paid_amount')</th>
                            <th>@lang('advancedreports::lang.due_amount')</th>
                            <th>@lang('sale.payment_status')</th>
                            <th>@lang('lang_v1.payment_method')</th>
                            <th>@lang('advancedreports::lang.created_by')</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 text-center footer-total">
                            <td colspan="5"><strong>@lang('sale.total'):</strong></td>
                            <td class="footer_total_subtotal"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td class="footer_total_tax"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td class="footer_total_line_discount"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td class="footer_total_invoice_discount"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td class="footer_total_discount"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td class="footer_total_amount"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td class="footer_total_paid"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td class="footer_total_due"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endcomponent
        </div>
    </div>

    <div class="modal fade sales_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    {{-- <div class="modal fade view_modal" tabindex="-1" role="dialog">
    </div> --}}

</section>
<!-- /.content -->

@stop

@section('javascript')
<script type="text/javascript">
    // Currency formatting function
    function formatCurrency(num) {
        return __currency_trans_from_en(num.toFixed(2), true);
    }
    
    function formatNumber(num) {
        return num.toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

    // Date range utility functions
    function getDateRange(rangeType) {
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
                start = today.clone();
                end = today.clone();
        }
        
        return {
            start: start,
            end: end
        };
    }

    function updateDateFilter(rangeType, customStart = null, customEnd = null) {
        var dateRange, displayText;
        
        if (rangeType === 'custom' && customStart && customEnd) {
            dateRange = {
                start: moment(customStart),
                end: moment(customEnd)
            };
            displayText = customStart.format(moment_date_format) + ' ~ ' + customEnd.format(moment_date_format);
        } else {
            dateRange = getDateRange(rangeType);
            
            // Generate display text
            var rangeLabels = {
                'today': LANG.today || 'Today',
                'yesterday': LANG.yesterday || 'Yesterday',
                'this_week': LANG.this_week || 'This Week',
                'last_week': LANG.last_week || 'Last Week',
                'this_month': LANG.this_month || 'This Month',
                'last_month': LANG.last_month || 'Last Month',
                'this_quarter': LANG.this_quarter || 'This Quarter',
                'last_quarter': LANG.last_quarter || 'Last Quarter',
                'this_year': LANG.this_year || 'This Year',
                'last_year': LANG.last_year || 'Last Year'
            };
            
            displayText = rangeLabels[rangeType] || (dateRange.start.format(moment_date_format) + ' ~ ' + dateRange.end.format(moment_date_format));
        }
        
        // Update UI
        $('#date_filter_text').text(displayText);
        $('#start_date').val(dateRange.start.format('YYYY-MM-DD'));
        $('#end_date').val(dateRange.end.format('YYYY-MM-DD'));
        
        // Store current range type
        $('#sales_date_filter_btn').data('current-range', rangeType);
    }

    $(document).ready(function() {
        // Initialize custom date range picker (hidden)
        if ($('#custom_date_range').length) {
            $('#custom_date_range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: LANG.clear || 'Clear',
                    applyLabel: LANG.apply || 'Apply',
                    format: moment_date_format
                }
            });
            
            $('#custom_date_range').on('apply.daterangepicker', function(ev, picker) {
                updateDateFilter('custom', picker.startDate, picker.endDate);
            });
            
            $('#custom_date_range').on('cancel.daterangepicker', function(ev, picker) {
                // Reset to today if cancelled
                updateDateFilter('today');
            });
        }

        // Handle dropdown date filter selection
        $('.date-filter-dropdown .dropdown-menu a').click(function(e) {
            e.preventDefault();
            var rangeType = $(this).data('range');
            
            if (rangeType === 'custom') {
                // Show the daterangepicker
                $('#custom_date_range').click();
            } else {
                updateDateFilter(rangeType);
                // Auto-trigger filter
                sales_report_table.ajax.reload();
                loadSummary();
            }
        });

        // Set default to today
        updateDateFilter('today');

        // Initialize DataTable
        var sales_report_table = $('#sales_report_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\SalesReportController@getSalesData') !!}",
                data: function (d) {
                    d.location_id = $('#location_filter').val();
                    d.customer_id = $('#customer_filter').val();
                    d.payment_status = $('#payment_status_filter').val();
                    d.payment_method = $('#payment_method_filter').val();
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                }
            },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'transaction_date', name: 'transactions.transaction_date' },
                { data: 'invoice_no', name: 'transactions.invoice_no' },
                { data: 'customer_name', name: 'customer_name' },
                { data: 'location_name', name: 'bl.name' },
                { data: 'invoice_subtotal', name: 'invoice_subtotal', searchable: false },
                { data: 'tax_amount', name: 'transactions.tax_amount', searchable: false },
                { data: 'line_discount', name: 'line_discount', searchable: false },
                { data: 'invoice_discount', name: 'invoice_discount', searchable: false },
                { data: 'discount_amount', name: 'discount_amount', searchable: false },
                { data: 'final_total', name: 'transactions.final_total', searchable: false },
                { data: 'total_paid', name: 'total_paid', searchable: false },
                { data: 'due_amount', name: 'due_amount', searchable: false },
                { data: 'payment_status', name: 'transactions.payment_status', searchable: false },
                { data: 'payment_method', name: 'payment_method', searchable: false },
                { data: 'created_by', name: 'created_by', searchable: false }
            ],
            order: [[1, 'desc']], // Sort by date desc
            "fnDrawCallback": function (oSettings) {
                __currency_convert_recursively($('#sales_report_table'));
                
                // Calculate footer totals
                var api = this.api();
                
                var total_subtotal = 0;
                var total_tax = 0;
                var total_line_discount = 0;
                var total_invoice_discount = 0;
                var total_discount = 0;
                var total_amount = 0;
                var total_paid = 0;
                var total_due = 0;
                
                // Invoice Subtotal (column 5)
                api.column(5, {page: 'current'}).data().each(function(value) {
                    var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                    total_subtotal += num;
                });
                
                // Tax Amount (column 6)
                api.column(6, {page: 'current'}).data().each(function(value) {
                    var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                    total_tax += num;
                });
                
                // Line Discount (column 7)
                api.column(7, {page: 'current'}).data().each(function(value) {
                    var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                    total_line_discount += num;
                });
                
                // Invoice Discount (column 8)
                api.column(8, {page: 'current'}).data().each(function(value) {
                    var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                    total_invoice_discount += num;
                });
                
                // Total Discount (column 9)
                api.column(9, {page: 'current'}).data().each(function(value) {
                    var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                    total_discount += num;
                });
                
                // Total Amount (column 10)
                api.column(10, {page: 'current'}).data().each(function(value) {
                    var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                    total_amount += num;
                });
                
                // Total Paid (column 11)
                api.column(11, {page: 'current'}).data().each(function(value) {
                    var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                    total_paid += num;
                });
                
                // Due Amount (column 12)
                api.column(12, {page: 'current'}).data().each(function(value) {
                    var num = parseFloat($(value).text().replace(/[^0-9.-]+/g,"")) || 0;
                    total_due += num;
                });

                // Update footer
                $('.footer_total_subtotal').html('<span class="display_currency" data-currency_symbol="true">' + total_subtotal.toFixed(2) + '</span>');
                $('.footer_total_tax').html('<span class="display_currency" data-currency_symbol="true">' + total_tax.toFixed(2) + '</span>');
                $('.footer_total_line_discount').html('<span class="display_currency" data-currency_symbol="true">' + total_line_discount.toFixed(2) + '</span>');
                $('.footer_total_invoice_discount').html('<span class="display_currency" data-currency_symbol="true">' + total_invoice_discount.toFixed(2) + '</span>');
                $('.footer_total_discount').html('<span class="display_currency" data-currency_symbol="true">' + total_discount.toFixed(2) + '</span>');
                $('.footer_total_amount').html('<span class="display_currency" data-currency_symbol="true">' + total_amount.toFixed(2) + '</span>');
                $('.footer_total_paid').html('<span class="display_currency" data-currency_symbol="true">' + total_paid.toFixed(2) + '</span>');
                $('.footer_total_due').html('<span class="display_currency" data-currency_symbol="true">' + total_due.toFixed(2) + '</span>');
                
                __currency_convert_recursively($('.footer-total'));
            },
            createdRow: function( row, data, dataIndex ) {
                // Right align monetary columns
                $(row).find('td:eq(5)').addClass('text-right');  // Invoice Subtotal
                $(row).find('td:eq(6)').addClass('text-right');  // Tax
                $(row).find('td:eq(7)').addClass('text-right');  // Line Discount
                $(row).find('td:eq(8)').addClass('text-right');  // Invoice Discount
                $(row).find('td:eq(9)').addClass('text-right');  // Total Discount
                $(row).find('td:eq(10)').addClass('text-right'); // Total Amount
                $(row).find('td:eq(11)').addClass('text-right'); // Paid Amount
                $(row).find('td:eq(12)').addClass('text-right'); // Due Amount
            }
        });

        // Filter button click
        $('#filter_btn').click(function() {
            sales_report_table.ajax.reload();
            loadSummary();
        });

        // Export button click
        $('#export_btn').click(function(e) {
            e.preventDefault();

            var location_id = $('#location_filter').val();
            var customer_id = $('#customer_filter').val();
            var payment_status = $('#payment_status_filter').val();
            var payment_method = $('#payment_method_filter').val();
            var start_date = $('#start_date').val();
            var end_date = $('#end_date').val();

            var originalText = $(this).html();
            $(this).html('<i class="fa fa-spinner fa-spin"></i> ' + @json(__('advancedreports::lang.exporting')));
            $(this).prop('disabled', true);

            var params = {
                location_id: location_id,
                customer_id: customer_id,
                payment_status: payment_status,
                payment_method: payment_method,
                start_date: start_date,
                end_date: end_date,
                _token: '{{ csrf_token() }}'
            };

            var filename = 'sales_report_' + start_date + '_to_' + end_date + '_' + new Date().toISOString().slice(0,10) + '.xlsx';

            $.ajax({
                url: '{{ route("advancedreports.sales.export") }}',
                type: 'POST',
                data: params,
                xhrFields: {
                    responseType: 'blob'
                },
                success: function(data, status, xhr) {
                    try {
                        var blob = new Blob([data], {
                            type: xhr.getResponseHeader('Content-Type') || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                        });
                        var link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        window.URL.revokeObjectURL(link.href);
                    } catch(e) {
                        console.error('Export failed:', e);
                        toastr.error('Export failed. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Export failed:', error);
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        toastr.error(xhr.responseJSON.error);
                    } else {
                        toastr.error('Export failed. Please try again.');
                    }
                },
                complete: function() {
                    setTimeout(function() {
                        $('#export_btn').html(originalText);
                        $('#export_btn').prop('disabled', false);
                    }, 1000);
                }
            });
        });

        // Load summary data
        function loadSummary() {
            var location_id = $('#location_filter').val();
            var customer_id = $('#customer_filter').val();
            var payment_status = $('#payment_status_filter').val();
            var start_date = $('#start_date').val();
            var end_date = $('#end_date').val();

            $.ajax({
                url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\SalesReportController@getSummary') !!}",
                data: {
                    location_id: location_id,
                    customer_id: customer_id,
                    payment_status: payment_status,
                    start_date: start_date,
                    end_date: end_date
                },
                dataType: 'json',
                success: function(data) {
                    // Basic summary data
                    var totalTransactions = parseInt(data.total_transactions || 0);
                    var totalCustomers = parseInt(data.total_customers || 0);
                    var totalProducts = parseInt(data.total_products || 0);
                    
                    $('#total_transactions').text(formatNumber(totalTransactions));
                    $('#total_customers_count').text(totalCustomers + ' ' + @json(__('advancedreports::lang.customers')));
                    $('#total_products_count').text(totalProducts + ' ' + @json(__('advancedreports::lang.products')));
                    
                    var totalSales = parseFloat(data.total_sales || 0);
                    $('#total_sales').text(formatCurrency(totalSales));
                    
                    var totalTax = parseFloat(data.total_tax || 0);
                    $('#total_tax').text(formatCurrency(totalTax));
                    
                    // Discount breakdown
                    var lineDiscount = parseFloat(data.line_discount || 0);
                    var invoiceDiscount = parseFloat(data.invoice_discount || 0);
                    var totalDiscount = parseFloat(data.total_discount || 0);
                    
                    $('#total_discount').text(formatCurrency(totalDiscount));
                    $('#line_discount_amount').text(formatCurrency(lineDiscount));
                    $('#invoice_discount_amount').text(formatCurrency(invoiceDiscount));
                    
                    // Paid transactions
                    var paidAmount = parseFloat(data.paid_amount || 0);
                    var paidTransactionsCount = parseInt(data.paid_transactions || 0);
                    
                    $('#paid_transactions_amount').text(formatCurrency(paidAmount));
                    $('#paid_transactions_count').text(paidTransactionsCount + ' ' + (paidTransactionsCount === 1 ? 'invoice' : 'invoices') + ' paid');
                    
                    // Due transactions
                    var totalDueAmount = parseFloat(data.total_due_amount || 0);
                    var dueTransactionsCount = parseInt(data.due_transactions || 0);
                    
                    $('#due_transactions_amount').text(formatCurrency(totalDueAmount));
                    $('#due_transactions_count').text(dueTransactionsCount + ' ' + (dueTransactionsCount === 1 ? 'invoice' : 'invoices') + ' due');
                    
                    // Partial transactions
                    var partialAmount = parseFloat(data.partial_amount || 0);
                    var partialTransactionsCount = parseInt(data.partial_transactions || 0);
                    
                    $('#partial_transactions_amount').text(formatCurrency(partialAmount));
                    $('#partial_transactions_count').text(partialTransactionsCount + ' ' + (partialTransactionsCount === 1 ? 'invoice' : 'invoices') + ' partial');
                    
                    // Average transaction value
                    var averageValue = totalTransactions > 0 ? totalSales / totalTransactions : 0;
                    
                    $('#average_transaction_value').text(formatCurrency(averageValue));
                    $('#average_description').text('Per transaction');
                    
                    // Due analysis data
                    var collectedToday = parseFloat(data.due_collected_today || 0);
                    $('#due_collected_today').text(formatCurrency(collectedToday));
                    
                    var pendingDueToday = parseFloat(data.pending_due_today || 0);
                    $('#pending_due_today').text(formatCurrency(pendingDueToday));
                    
                    var overdueAmount = parseFloat(data.overdue_amount || 0);
                    $('#overdue_amount').text(formatCurrency(overdueAmount));
                    
                    // Show all card sections
                    $('#summary_cards').show();
                    $('#payment_status_cards').show();
                    $('#due_analysis_cards').show();
                },
                error: function() {
                    console.log('Error loading summary data');
                }
            });
        }

        // Load initial summary
        loadSummary();

        // Modal for sales details
        $(document).on('click', '.sales_modal_btn', function(e) {
            e.preventDefault();
            var container = $(this).data('container');
            $.get($(this).data('href'), function(data) {
                $(container).html(data).modal('show');
            });
        });

        $(document).on('click', '.sales-invoice-modal', function(e) {
    e.preventDefault();
    var container = $(this).data('container') || '.view_modal';
    var url = $(this).data('href');
    
    if (url) {
        // Show loading state
        $(container).html('<div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><br><br>Loading...</div></div></div>').modal('show');
        
        $.get(url, function(data) {
            $(container).html(data);
        }).fail(function() {
            $(container).html('<div class="modal-dialog"><div class="modal-content"><div class="modal-body"><div class="alert alert-danger">Error loading invoice details</div></div></div></div>');
            toastr.error('Error loading invoice details');
        });
    }
});

        // View modal handler (for standard transaction view)
        $(document).on('click', '.btn-modal', function(e) {
            e.preventDefault();
            var container = $(this).data('container') || '.view_modal';
            var url = $(this).data('href');
            
            if (url) {
                $.get(url, function(data) {
                    $(container).html(data).modal('show');
                }).fail(function() {
                    toastr.error('Error loading content');
                });
            }
        });

        // Add Payment Modal
        $(document).on('click', '.add_payment_modal', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            
            $.get(url, function(data) {
                $('.view_modal').html(data).modal('show');
            }).fail(function() {
                toastr.error('Error loading payment form');
            });
        });

        // View Payment Modal
        $(document).on('click', '.view_payment_modal', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            
            $.get(url, function(data) {
                $('.view_modal').html(data).modal('show');
            }).fail(function() {
                toastr.error('Error loading payments');
            });
        });

        // View Invoice URL Modal
        $(document).on('click', '.view_invoice_url', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            
            $.get(url, function(data) {
                $('.view_modal').html(data).modal('show');
            }).fail(function() {
                toastr.error('Error loading invoice URL');
            });
        });

        // Print invoice functionality - SIMPLIFIED VERSION
       // Alternative: Simple and reliable print method
$(document).on('click', '.print-invoice', function(e) {
    e.preventDefault();
    var print_url = $(this).data('href');
    
    if (!print_url) {
        console.error('Print URL not found');
        return;
    }
    
    // Close modals
    $('.sales_modal').modal('hide');
    $('.view_modal').modal('hide');
    
    // Add timestamp to make URL unique and prevent caching issues
    var separator = print_url.includes('?') ? '&' : '?';
    var uniqueUrl = print_url + separator + '_print_time=' + Date.now();
    
    // Simple window.open - let user handle printing manually
    var printWindow = window.open(uniqueUrl, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
    
    if (printWindow) {
        printWindow.focus();
        
        // Optional: Show user instruction
        setTimeout(function() {
            if (!printWindow.closed) {
                console.log('Print window opened successfully');
            }
        }, 1000);
    } else {
        alert('Please allow pop-ups to enable printing');
    }
});
        // Delete confirmation
        $(document).on('click', '.delete-sale', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            
            if (confirm('Are you sure you want to delete this transaction?')) {
                $.ajax({
                    url: url,
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.msg || 'Transaction deleted successfully');
                            sales_report_table.ajax.reload();
                            loadSummary();
                        } else {
                            toastr.error(response.msg || 'Error deleting transaction');
                        }
                    },
                    error: function() {
                        toastr.error('Error deleting transaction');
                    }
                });
            }
        });

        // Auto-filter on change for other filters
        $('#location_filter, #customer_filter, #payment_status_filter, #payment_method_filter').change(function() {
            sales_report_table.ajax.reload();
            loadSummary();
        });
    });
</script>

<style>
    /* Date Filter Dropdown Styling */
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



    /* Caret styling */
    .caret {
        display: inline-block;
        width: 0;
        height: 0;
        margin-left: 2px;
        vertical-align: middle;
        border-top: 4px solid;
        border-right: 4px solid transparent;
        border-left: 4px solid transparent;
    }


    /* Screen reader only */
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        border: 0;
    }


    /* Modal styling */
    .modal-lg {
        width: 90%;
        max-width: 1200px;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .small-box {
            min-height: 110px !important;
            height: 110px !important;
        }

        .small-box .inner h3 {
            font-size: 24px !important;
        }

        .small-box .inner p {
            font-size: 11px !important;
        }

        .btn-group .btn {
            margin-bottom: 2px !important;
        }

        .modal-lg {
            width: 95%;
        }

        .date-filter-dropdown .btn {
            font-size: 12px;
            padding: 5px 10px;
        }
    }
</style>
@endsection