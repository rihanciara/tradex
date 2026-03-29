@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.daily_report'))

@section('content')

<!-- Add CSRF token meta tag -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('advancedreports::lang.daily_report')
        <small>@lang('advancedreports::lang.comprehensive_daily_overview')</small>
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
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'location_filter']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('end_date', __('advancedreports::lang.report_date') . ':') !!}
                    <div class="input-group">
                        {!! Form::text('end_date', \Carbon\Carbon::now()->format(session('business.date_format') ?:
                        'd/m/Y'),
                        ['class' => 'form-control', 'id' => 'end_date_filter', 'placeholder' => 'DD/MM/YYYY']); !!}
                        <div class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </div>
                    </div>
                    <small class="text-muted">@lang('advancedreports::lang.date_format')</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <br>
                    <button type="button" class="btn btn-primary" id="filter_btn">
                        <i class="fa fa-refresh"></i> @lang('advancedreports::lang.refresh')
                    </button>
                    {{-- <button type="button" class="btn btn-success" id="export_btn">
                        <i class="fa fa-download"></i> @lang('lang_v1.export')
                    </button> --}}
                    {{-- <button type="button" class="btn btn-info" id="test_data_btn">
                        <i class="fa fa-bug"></i> Test Data
                    </button> --}}
                </div>
            </div>
            @endcomponent
        </div>
    </div>


    <!-- Main Summary Cards with Tailwind Widgets -->
    <div class="row" id="summary_cards">
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div
                class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div
                            class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-green-100 tw-text-green-500">
                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h-11v-14h-2"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 5l14 1l-1 7h-13"></path>
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('advancedreports::lang.todays_sales')</p>
                            <p class="tw-text-2xl tw-font-semibold" id="today_sales">
                                <span class="display_currency" data-currency_symbol="true">0</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div
                class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div
                            class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-sky-100 tw-text-sky-500">
                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h-11v-14h-2"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 5l14 1l-1 7h-13"></path>
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('advancedreports::lang.todays_purchases')</p>
                            <p class="tw-text-2xl tw-font-semibold" id="today_purchases">
                                <span class="display_currency" data-currency_symbol="true">0</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div
                class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div
                            class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-red-100 tw-text-red-500">
                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l18 0"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 15l.01 0"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 15l2 0"></path>
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('advancedreports::lang.todays_expenses')</p>
                            <p class="tw-text-2xl tw-font-semibold" id="today_expenses">
                                <span class="display_currency" data-currency_symbol="true">0</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div
                class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div
                            class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-sky-100 tw-text-sky-500">
                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l3 3l4 -6l4 2l5 -5"></path>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 12v5a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10a2 2 0 0 1 2 -2h5"></path>
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('advancedreports::lang.todays_profit')</p>
                            <p class="tw-text-2xl tw-font-semibold" id="today_profit">
                                <span class="display_currency" data-currency_symbol="true">0</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div
                class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div
                            class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-sky-100 tw-text-sky-500">
                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 3l3 1.5l3 -1.5l3 1.5l3 -1.5"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16l-1 10h-14z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14v4"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 14v4"></path>
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('advancedreports::lang.cash_in_hand')</p>
                            <p class="tw-text-2xl tw-font-semibold" id="cash_in_hand">
                                <span class="display_currency" data-currency_symbol="true">0</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div
                class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div
                            class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-sky-100 tw-text-sky-500">
                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 21l18 0"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l18 0"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 6l7 -3l7 3"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 10l0 11"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 10l0 11"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 14l0 3"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l0 3"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 14l0 3"></path>
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('advancedreports::lang.bank_balance')</p>
                            <p class="tw-text-2xl tw-font-semibold" id="bank_balance">
                                <span class="display_currency" data-currency_symbol="true">0</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Position Cards with Tailwind Widgets -->
    <div class="row">
        <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6 col-xs-12">
            <div
                class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div
                            class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-sky-100 tw-text-sky-500">
                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path>
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('advancedreports::lang.customer_due')</p>
                            <p class="tw-text-2xl tw-font-semibold" id="customer_due">
                                <span class="display_currency" data-currency_symbol="true">0</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6 col-xs-12">
            <div
                class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div
                            class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-sky-100 tw-text-sky-500">
                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 17h-2v-4m-1 -8h11v12m-4 0h6m4 0h2v-6h-8m0 -5h5l3 5"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l4 0"></path>
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('advancedreports::lang.supplier_due')</p>
                            <p class="tw-text-2xl tw-font-semibold" id="supplier_due">
                                <span class="display_currency" data-currency_symbol="true">0</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6 col-xs-12">
            <div
                class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div
                            class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-sky-100 tw-text-sky-500">
                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.7 8a3 3 0 0 0 -2.7 -2h-4a3 3 0 0 0 0 6h4a3 3 0 0 1 0 6h-4a3 3 0 0 1 -2.7 -2">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3"></path>
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('advancedreports::lang.todays_collections')</p>
                            <p class="tw-text-2xl tw-font-semibold" id="today_collections">
                                <span class="display_currency" data-currency_symbol="true">0</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6 col-xs-12">
            <div
                class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-flex tw-items-center tw-gap-4">
                        <div
                            class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-sky-100 tw-text-sky-500">
                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 21l8 0"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 17l0 4"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 4l10 0"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 4v8a5 5 0 0 1 -10 0v-8">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 9l14 0"></path>
                            </svg>
                        </div>
                        <div class="tw-flex-1 tw-min-w-0">
                            <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('advancedreports::lang.net_worth')</p>
                            <p class="tw-text-2xl tw-font-semibold" id="net_worth">
                                <span class="display_currency" data-currency_symbol="true">0</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Summary -->
    <div class="row">
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-success', 'title' =>
            __('advancedreports::lang.todays_activity')])
            <div class="table-responsive">
                <table class="table table-condensed" id="activity_summary">
                    <tbody>
                        <tr>
                            <td>@lang('advancedreports::lang.total_transactions'):</td>
                            <td class="text-right" id="transactions_count">0</td>
                        </tr>
                        <tr>
                            <td>@lang('advancedreports::lang.new_customers'):</td>
                            <td class="text-right" id="new_customers">0</td>
                        </tr>
                        <tr>
                            <td>@lang('advancedreports::lang.avg_transaction_value'):</td>
                            <td class="text-right" id="avg_transaction">
                                <span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                        </tr>
                        <tr class="info">
                            <td><strong>@lang('advancedreports::lang.gross_profit_margin'):</strong></td>
                            <td class="text-right" id="profit_margin"><strong>0%</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @endcomponent
        </div>

        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-info', 'title' =>
            __('advancedreports::lang.financial_snapshot')])
            <div class="table-responsive">
                <table class="table table-condensed" id="financial_snapshot">
                    <tbody>
                        <tr>
                            <td>@lang('advancedreports::lang.opening_balance'):</td>
                            <td class="text-right" id="opening_balance">
                                <span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                        </tr>
                        <tr>
                            <td>@lang('advancedreports::lang.closing_balance'):</td>
                            <td class="text-right" id="closing_balance">
                                <span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                        </tr>
                        <tr>
                            <td>@lang('advancedreports::lang.cash_flow'):</td>
                            <td class="text-right" id="cash_flow">
                                <span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                        </tr>
                        <tr class="info">
                            <td><strong>@lang('advancedreports::lang.liquidity_ratio'):</strong></td>
                            <td class="text-right" id="liquidity_ratio"><strong>0:1</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Sales & Purchase Analysis with Tailwind Widgets -->
    <div class="row">
        <!-- Sales Analysis -->
        <div class="col-md-6">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-chart-bar"></i> @lang('advancedreports::lang.sales_analysis')
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div
                                class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                                <div class="tw-p-4 sm:tw-p-5">
                                    <div class="tw-flex tw-items-center tw-gap-4">
                                        <div
                                            class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-green-100 tw-text-green-500">
                                            <svg aria-hidden="true" class="tw-w-6 tw-h-6"
                                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2"
                                                stroke="currentColor" fill="none" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M17 17h-11v-14h-2"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 5l14 1l-1 7h-13"></path>
                                            </svg>
                                        </div>
                                        <div class="tw-flex-1 tw-min-w-0">
                                            <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('advancedreports::lang.total_sales')</p>
                                            <p class="tw-text-2xl tw-font-semibold" id="sales_count">0</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div
                                class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                                <div class="tw-p-4 sm:tw-p-5">
                                    <div class="tw-flex tw-items-center tw-gap-4">
                                        <div
                                            class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-sky-100 tw-text-sky-500">
                                            <svg aria-hidden="true" class="tw-w-6 tw-h-6"
                                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2"
                                                stroke="currentColor" fill="none" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M16.7 8a3 3 0 0 0 -2.7 -2h-4a3 3 0 0 0 0 6h4a3 3 0 0 1 0 6h-4a3 3 0 0 1 -2.7 -2">
                                                </path>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3">
                                                </path>
                                            </svg>
                                        </div>
                                        <div class="tw-flex-1 tw-min-w-0">
                                            <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('advancedreports::lang.total_amount')</p>
                                            <p class="tw-text-2xl tw-font-semibold" id="sales_total">
                                                <span class="display_currency" data-currency_symbol="true">0</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row" style="margin-top: 15px;">
                        <div class="col-md-12">
                            <table class="table table-condensed">
                                <tr>
                                    <td>@lang('sale.subtotal'):</td>
                                    <td class="text-right" id="sales_subtotal">
                                        <span class="display_currency" data-currency_symbol="true">0</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>@lang('sale.tax'):</td>
                                    <td class="text-right" id="sales_tax">
                                        <span class="display_currency" data-currency_symbol="true">0</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>@lang('sale.discount'):</td>
                                    <td class="text-right" id="sales_discount">
                                        <span class="display_currency" data-currency_symbol="true">0</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Analysis -->
        <div class="col-md-6">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-truck"></i> @lang('advancedreports::lang.purchase_analysis')
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div
                                class="tw-mb-4 tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-ring-1 tw-ring-gray-200">
                                <div class="tw-p-4 sm:tw-p-5">
                                    <div class="tw-flex tw-items-center tw-gap-4">
                                        <div
                                            class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-sky-100 tw-text-sky-500">
                                            <svg aria-hidden="true" class="tw-w-6 tw-h-6"
                                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2"
                                                stroke="currentColor" fill="none" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M7 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M17 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M5 17h-2v-4m-1 -8h11v12m-4 0h6m4 0h2v-6h-8m0 -5h5l3 5"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l4 0">
                                                </path>
                                            </svg>
                                        </div>
                                        <div class="tw-flex-1 tw-min-w-0">
                                            <p class="tw-text-sm tw-font-medium tw-text-gray-500">@lang('advancedreports::lang.total_purchases')</p>
                                            <p class="tw-text-2xl tw-font-semibold" id="purchase_total">
                                                <span class="display_currency" data-currency_symbol="true">0</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products & Payment Methods -->
    <div class="row">
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-success', 'title' => __('advancedreports::lang.top_selling_products')])
            <div id="top_products_content">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="text-muted">@lang('advancedreports::lang.loading')</p>
                </div>
            </div>
            @endcomponent
        </div>

        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-info', 'title' => __('advancedreports::lang.payment_methods_analysis')])
            <div id="payment_methods_content">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="text-muted">@lang('advancedreports::lang.loading')</p>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Expense Breakdown -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-danger', 'title' => __('advancedreports::lang.expense_breakdown')])
            <div id="expense_breakdown_content">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="text-muted">@lang('advancedreports::lang.loading')</p>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Monthly Cash Flow Breakdown -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('advancedreports::lang.monthly_cash_flow_breakdown')])
            <div id="monthly_cash_breakdown_content">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="text-muted">@lang('advancedreports::lang.loading')</p>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

</section>
<!-- /.content -->

@stop

@section('javascript')

<script type="text/javascript">
    // Currency formatting function
    function formatCurrency(num) {
        if (typeof __currency_trans_from_en === 'function') {
            return __currency_trans_from_en(num, true);
        }
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: "{{ session('currency.code', 'USD') }}",
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(num || 0);
    }

    function formatNumber(num) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }).format(num || 0);
    }

    $(document).ready(function() {
        console.log('Initializing daily report (safe version)...');
        
        // ===== SIMPLE DATE PICKER INITIALIZATION (NO LOOPS) =====
        $('#end_date_filter').datepicker({
            autoclose: true,
            format: 'dd/mm/yyyy',
            todayHighlight: true,
            todayBtn: 'linked',
            clearBtn: false,
            orientation: 'bottom auto',
            weekStart: 1,
            startDate: '-5y',
            endDate: '+1m'
        });

        // ===== SET DEFAULT DATE TO TODAY (SIMPLE) =====
        var today = moment().format('DD/MM/YYYY');
        $('#end_date_filter').val(today);
        console.log('Default date set to:', today);

        // ===== SIMPLE DATE CHANGE HANDLER =====
        $('#end_date_filter').on('changeDate', function(e) {
            if (e.date) {
                var formattedDate = moment(e.date).format('DD/MM/YYYY');
                console.log('Date changed to:', formattedDate);
                $(this).val(formattedDate);
                
                // Debounced reload to prevent multiple calls
                clearTimeout(window.dateChangeTimeout);
                window.dateChangeTimeout = setTimeout(function() {
                    loadAllData();
                }, 300);
            }
        });

        // ===== MANUAL DATE INPUT (SIMPLIFIED) =====
        $('#end_date_filter').on('blur', function() {
            var inputValue = $(this).val().trim();
            
            if (inputValue && inputValue.length >= 8) {
                try {
                    var parsedDate = moment(inputValue, ['DD/MM/YYYY', 'DD-MM-YYYY', 'D/M/YYYY'], true);
                    
                    if (parsedDate.isValid()) {
                        var correctedFormat = parsedDate.format('DD/MM/YYYY');
                        $(this).val(correctedFormat);
                        console.log('Date corrected to:', correctedFormat);
                        
                        clearTimeout(window.dateChangeTimeout);
                        window.dateChangeTimeout = setTimeout(function() {
                            loadAllData();
                        }, 300);
                    } else {
                        console.warn('Invalid date, resetting to today');
                        $(this).val(moment().format('DD/MM/YYYY'));
                    }
                } catch (e) {
                    console.error('Date parsing error:', e);
                    $(this).val(moment().format('DD/MM/YYYY'));
                }
            }
        });

        // ===== FILTER BUTTON =====
        $('#filter_btn').click(function() {
            var $btn = $(this);
            var originalText = $btn.html();
            $btn.html('<i class="fa fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
            
            loadAllData().finally(function() {
                $btn.html(originalText).prop('disabled', false);
            });
        });

        // ===== LOCATION FILTER =====
        $('#location_filter').change(function() {
            console.log('Location changed to:', $(this).val());
            clearTimeout(window.locationChangeTimeout);
            window.locationChangeTimeout = setTimeout(function() {
                loadAllData();
            }, 300);
        });

        // ===== EXPORT BUTTON =====
        $('#export_btn').click(function(e) {
            e.preventDefault();
            
            var location_id = $('#location_filter').val() || '';
            var end_date = $('#end_date_filter').val() || '';
            
            var $btn = $(this);
            var originalText = $btn.html();
            
            $btn.html('<i class="fa fa-spinner fa-spin"></i> Exporting...').prop('disabled', true);
            
            var url = "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\DailyReportController@export') !!}";
            url += '?location_id=' + encodeURIComponent(location_id) + '&end_date=' + encodeURIComponent(end_date);
            
            window.open(url, '_blank');
            
            setTimeout(function() {
                $btn.html(originalText).prop('disabled', false);
            }, 2000);
        });

        // ===== LOAD ALL DATA (SAFE VERSION) =====
        function loadAllData() {
            console.log('Loading all data (safe)...');
            
            var location_id = $('#location_filter').val() || '';
            var end_date = $('#end_date_filter').val() || '';
            
            // Convert DD/MM/YYYY to YYYY-MM-DD
            if (end_date && end_date.match(/^\d{1,2}\/\d{1,2}\/\d{4}$/)) {
                var parts = end_date.split('/');
                var day = parts[0].padStart(2, '0');
                var month = parts[1].padStart(2, '0');
                var year = parts[2];
                end_date = year + '-' + month + '-' + day;
            }
            
            // Load summary and detailed data
            return Promise.all([
                loadSummary(end_date),
                loadDetailedBreakdown(end_date)
            ]).catch(function(error) {
                console.error('Error loading data:', error);
                showToast('error', 'Error loading report data');
            });
        }

        // ===== LOAD SUMMARY (SAFE VERSION) =====
        function loadSummary(converted_date) {
            var location_id = $('#location_filter').val() || '';
            var end_date = converted_date || $('#end_date_filter').val() || '';
            
            return $.ajax({
                url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\DailyReportController@getSummary') !!}",
                method: 'GET',
                data: {
                    location_id: location_id,
                    end_date: end_date,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',
                timeout: 15000,
                success: function(data) {
                    console.log('Summary data received');
                    
                    // Update widgets safely
                    updateWidgetValue('#today_sales', data.today_sales || 0);
                    updateWidgetValue('#today_purchases', data.today_purchases || 0);
                    updateWidgetValue('#today_expenses', data.today_expenses || 0);
                    updateWidgetValue('#today_profit', data.today_profit || 0);
                    updateWidgetValue('#cash_in_hand', data.cash_in_hand || 0);
                    updateWidgetValue('#bank_balance', data.bank_balance || 0);
                    updateWidgetValue('#customer_due', data.customer_due || 0);
                    updateWidgetValue('#supplier_due', data.supplier_due || 0);
                    updateWidgetValue('#today_collections', data.today_collections || 0);
                    updateWidgetValue('#net_worth', data.net_worth || 0);

                    // Update activity summary
                    $('#transactions_count').text(formatNumber(data.transactions_count || 0));
                    $('#new_customers').text(formatNumber(data.new_customers || 0));
                    $('#profit_margin').text((data.profit_margin || 0).toFixed(2) + '%');
                    $('#liquidity_ratio').text((data.liquidity_ratio || 0).toFixed(2) + ':1');

                    // Update financial snapshot
                    $('#opening_balance .display_currency').text((data.opening_balance || 0).toFixed(2));
                    $('#closing_balance .display_currency').text((data.closing_balance || 0).toFixed(2));
                    $('#cash_flow .display_currency').text((data.cash_flow || 0).toFixed(2));
                    $('#avg_transaction .display_currency').text((data.avg_transaction || 0).toFixed(2));
                },
                error: function(xhr, status, error) {
                    console.error('Summary loading error:', error);
                    showToast('error', 'Error loading summary data');
                }
            });
        }

        // ===== LOAD DETAILED BREAKDOWN (SAFE VERSION) =====
        function loadDetailedBreakdown(converted_date) {
            var location_id = $('#location_filter').val() || '';
            var end_date = $('#end_date_filter').val() || '';
            
            return $.ajax({
                url: "{!! action('\\Modules\\AdvancedReports\\Http\\Controllers\\DailyReportController@getDailyReportData') !!}",
                method: 'GET',
                data: {
                    location_id: location_id,
                    end_date: end_date,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',
                timeout: 15000,
                success: function(data) {
                    console.log('Detailed data received');
                    
                    // Render sections safely
                    if (data.sales_breakdown) {
                        renderSalesAnalysis(data.sales_breakdown);
                    }
                    if (data.purchase_breakdown) {
                        renderPurchaseAnalysis(data.purchase_breakdown);
                    }
                    if (data.top_products) {
                        renderTopProducts(data.top_products);
                    }
                    if (data.payment_methods) {
                        renderPaymentMethods(data.payment_methods);
                    }
                    if (data.expense_breakdown) {
                        renderExpenseBreakdown(data.expense_breakdown);
                    }
                    if (data.monthly_cash_breakdown) {
                        renderMonthlyCashBreakdown(data.monthly_cash_breakdown);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Detailed data error:', error);
                    showToast('error', 'Error loading detailed data');
                }
            });
        }

        // ===== HELPER FUNCTIONS =====
        function updateWidgetValue(selector, value) {
            try {
                $(selector).text(formatCurrency(value));
            } catch (e) {
                console.error('Error updating widget:', selector, e);
            }
        }

        function showToast(type, message) {
            if (typeof toastr !== 'undefined') {
                toastr[type](message);
            } else {
                console.log(type.toUpperCase() + ':', message);
            }
        }

        // ===== RENDER FUNCTIONS (UPDATED FOR TAILWIND WIDGETS) =====
        function renderSalesAnalysis(salesData) {
            if (!salesData) return;

            // Update individual Tailwind widget elements
            $('#sales_count').text(formatNumber(salesData.count || 0));
            $('#sales_total').text(formatCurrency(salesData.total || 0));
            $('#sales_subtotal').text(formatCurrency(salesData.subtotal || 0));
            $('#sales_tax').text(formatCurrency(salesData.tax || 0));
            $('#sales_discount').text(formatCurrency(salesData.discount || 0));
        }

        function renderPurchaseAnalysis(purchaseData) {
            if (!purchaseData) return;

            // Update individual Tailwind widget element
            $('#purchase_total').text(formatCurrency(purchaseData.total || 0));
        }

        function renderTopProducts(products) {
            if (!products || products.length === 0) {
                $('#top_products_content').html('<p class="text-muted">No products sold</p>');
                return;
            }

            var html = '<table class="table table-condensed"><thead><tr><th>Product</th><th>Qty</th><th>Amount</th></tr></thead><tbody>';
            products.forEach(function(product) {
                html += `<tr><td>${product.name}</td><td>${formatNumber(product.quantity_sold)}</td><td>${formatCurrency(product.total_amount)}</td></tr>`;
            });
            html += '</tbody></table>';
            $('#top_products_content').html(html);
        }

        function renderPaymentMethods(paymentMethods) {
            if (!paymentMethods || paymentMethods.length === 0) {
                $('#payment_methods_content').html('<p class="text-muted">No payments</p>');
                return;
            }

            var html = '<table class="table table-condensed"><thead><tr><th>Method</th><th>Amount</th></tr></thead><tbody>';
            paymentMethods.forEach(function(method) {
                html += `<tr><td>${method.method}</td><td>${formatCurrency(method.total_amount)}</td></tr>`;
            });
            html += '</tbody></table>';
            $('#payment_methods_content').html(html);
        }

        function renderExpenseBreakdown(expenses) {
            if (!expenses || expenses.length === 0) {
                $('#expense_breakdown_content').html('<p class="text-muted">No expenses</p>');
                return;
            }

            var html = '<table class="table table-condensed"><thead><tr><th>Category</th><th>Amount</th></tr></thead><tbody>';
            expenses.forEach(function(expense) {
                html += `<tr><td>${expense.category || 'Uncategorized'}</td><td>${formatCurrency(expense.total)}</td></tr>`;
            });
            html += '</tbody></table>';
            $('#expense_breakdown_content').html(html);
        }

        function renderMonthlyCashBreakdown(monthlyData) {
            if (!monthlyData || monthlyData.length === 0) {
                $('#monthly_cash_breakdown_content').html('<p class="text-muted">No monthly data</p>');
                return;
            }

            var html = '<table class="table table-condensed"><thead><tr><th>Month</th><th>Transactions</th><th>Net Flow</th></tr></thead><tbody>';
            monthlyData.forEach(function(month) {
                html += `<tr><td>${month.month_name}</td><td>${month.transaction_count}</td><td>${formatCurrency(month.net_cash_flow)}</td></tr>`;
            });
            html += '</tbody></table>';
            $('#monthly_cash_breakdown_content').html(html);
        }

        // ===== INITIAL LOAD (SAFE) =====
        console.log('Starting initial data load...');
        setTimeout(function() {
            loadAllData();
        }, 500); // Small delay to ensure DOM is ready

        console.log('Daily report initialized successfully');
    });
</script>


@endsection