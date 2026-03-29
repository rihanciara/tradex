@extends('advancedreports::layouts.app')
@php
// Get currency settings from Ultimate POS session
$currency_symbol = session('currency')['symbol'] ?? '';
$currency_precision = session('business.currency_precision') ?: 2;
$currency_symbol_placement = session('business.currency_symbol_placement') ?: 'before';
$thousand_separator = session('business.thousand_separator') ?: ',';
$decimal_separator = session('business.decimal_separator') ?: '.';
@endphp
@section('title', __('advancedreports::lang.customer_recognition_system'))

@section('content')

<!-- Content Header -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ __('advancedreports::lang.customer_recognition_system')}}
        <small class="text-muted">@lang('advancedreports::lang.weekly_monthly_yearly_customer_awards')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">

    <!-- Filters Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-filter"></i> @lang('advancedreports::lang.recognition_filters_controls')
                    </h3>
                </div>
                <div class="box-body">
                    <!-- Primary Filters Row -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('period_type_filter', __('advancedreports::lang.period_type') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                    {!! Form::select('period_type', $period_types, 'monthly', ['class' => 'form-control
                                    select2', 'id' => 'period_type_filter']) !!}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('winner_count_filter', __('advancedreports::lang.winner_count') . ':')
                                !!}
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-trophy"></i></span>
                                    {!! Form::select('winner_count', $winner_counts, 10, ['class' => 'form-control
                                    select2', 'id' => 'winner_count_filter']) !!}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('status_filter', __('sale.status') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-flag"></i></span>
                                    {!! Form::select('status', $status_options, 'all', ['class' => 'form-control
                                    select2', 'id' => 'status_filter']) !!}
                                </div>
                            </div>
                        </div>
                        @if(isset($business_locations))
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('location_filter', __('business.location') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-map-marker"></i></span>
                                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control
                                    select2', 'id' => 'location_filter']) !!}
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Action Buttons Row -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <button type="button" class="btn btn-primary" id="refresh_data">
                                    <i class="fa fa-refresh"></i> @lang('advancedreports::lang.refresh_data')
                                </button>
                                <button type="button" class="btn btn-success" id="export_btn">
                                    <i class="fa fa-download"></i> @lang('lang_v1.export')
                                </button>
                                @if(auth()->user()->can('customer_recognition.manage'))
                                <button type="button" class="btn btn-warning" id="finalize_period_btn">
                                    <i class="fa fa-lock"></i> @lang('advancedreports::lang.finalize_period')
                                </button>
                                <button type="button" class="btn btn-info" id="record_engagement_btn">
                                    <i class="fa fa-plus"></i> @lang('advancedreports::lang.record_engagement')
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Winner Cards Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-trophy"></i>
                        @lang('advancedreports::lang.current_period_winners')</h3>
                </div>
                <div class="box-body">
                    <div class="row" id="winner_cards">
                        <!-- Weekly Winner -->
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="winner-card weekly-winner">
                                <div class="winner-icon">
                                    <i class="fa fa-calendar-week"></i>
                                </div>
                                <div class="winner-content">
                                    <div class="winner-period">@lang('advancedreports::lang.weekly_winner')</div>
                                    <div class="winner-name" id="weekly_winner_name">
                                        @lang('advancedreports::lang.loading')</div>
                                    <div class="winner-sales" id="weekly_winner_sales">0.00</div>
                                </div>
                            </div>
                        </div>

                        <!-- Monthly Winner -->
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="winner-card monthly-winner">
                                <div class="winner-icon">
                                    {{-- calendar month --}}
                                    <i class="fa fa-calendar"></i>
                                </div>
                                <div class="winner-content">
                                    <div class="winner-period">@lang('advancedreports::lang.monthly_winner')</div>
                                    <div class="winner-name" id="monthly_winner_name">
                                        @lang('advancedreports::lang.loading')</div>
                                    <div class="winner-sales" id="monthly_winner_sales">0.00</div>
                                </div>
                            </div>
                        </div>

                        <!-- Yearly Winner -->
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="winner-card yearly-winner">
                                <div class="winner-icon">
                                    <i class="fa fa-calendar-alt"></i>
                                </div>
                                <div class="winner-content">
                                    <div class="winner-period">@lang('advancedreports::lang.yearly_winner')</div>
                                    <div class="winner-name" id="yearly_winner_name">
                                        @lang('advancedreports::lang.loading')</div>
                                    <div class="winner-sales" id="yearly_winner_sales">0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="small-box bg-blue">
                <div class="inner">
                    <h3 id="total_participants">0</h3>
                    <p>@lang('advancedreports::lang.total_participants')</p>
                </div>
                <div class="icon"><i class="fa fa-users"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="total_sales">0</h3>
                    <p>@lang('cash_register.total_sales')</p>
                </div>
                <div class="icon"><i class="fa fa-money"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3 id="total_engagement">0</h3>
                    <p>@lang('advancedreports::lang.engagement_points')</p>
                </div>
                <div class="icon"><i class="fa fa-star"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="avg_score">0</h3>
                    <p>@lang('advancedreports::lang.average_score')</p>
                </div>
                <div class="icon"><i class="fa fa-calculator"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="small-box bg-purple">
                <div class="inner">
                    <h3 id="top_score">0</h3>
                    <p>@lang('advancedreports::lang.top_score')</p>
                </div>
                <div class="icon"><i class="fa fa-trophy"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="small-box bg-orange">
                <div class="inner">
                    <h3 id="avg_transaction">0</h3>
                    <p>@lang('advancedreports::lang.avg_transaction')</p>
                </div>
                <div class="icon"><i class="fa fa-credit-card"></i></div>
            </div>
        </div>
    </div>

    <!-- Main Data Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-list"></i> @lang('advancedreports::lang.customer_rankings')
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" id="refresh_table">
                            <i class="fa fa-refresh"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="customer_recognition_table">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.rank')</th>
                                    <th>@lang('contact.customer')</th>
                                    <th>@lang('advancedreports::lang.sales_total')</th>
                                    <th>@lang('Total Paid')</th> <!-- NEW -->
                                    <th>@lang('Balance Due')</th> <!-- NEW -->
                                    <th>@lang('Payment %')</th> <!-- NEW -->
                                    <th>@lang('advancedreports::lang.transactions')</th>
                                    <th>@lang('advancedreports::lang.avg_transaction')</th>
                                    <th>@lang('advancedreports::lang.engagement_points')</th>
                                    <th>@lang('advancedreports::lang.final_score')</th>
                                    <th>@lang('sale.status')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Engagements Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title" style="color: white;"><i class="fa fa-users"></i>
                        @lang('advancedreports::lang.customer_engagements')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" id="refresh_engagements_table">
                            <i class="fa fa-refresh"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="customer_engagements_table">
                            <thead>
                                <tr>
                                    <th>@lang('contact.customer')</th>
                                    <th>@lang('advancedreports::lang.engagement_type')</th>
                                    <th>@lang('advancedreports::lang.platform')</th>
                                    <th>@lang('advancedreports::lang.reference_url')</th>
                                    <th>@lang('advancedreports::lang.verification_notes')</th>
                                    <th>@lang('advancedreports::lang.points')</th>
                                    <th>@lang('advancedreports::lang.recorded_by')</th>
                                    <th>@lang('advancedreports::lang.recorded_date')</th>
                                    <th>@lang('sale.status')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Enhanced Customer Details Modal -->
<div class="modal fade customer_details_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document" style="width: 95%; max-width: 1200px;">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title text-white">
                    <i class="fa fa-user-circle"></i> @lang('advancedreports::lang.customer_recognition_details')
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="customer_details_content" style="max-height: 70vh; overflow-y: auto;">
                <div class="text-center py-5">
                    <i class="fa fa-spinner fa-spin fa-3x text-primary text-white"></i>
                    <p class="text-muted mt-3">@lang('advancedreports::lang.loading_customer_details')</p>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times"></i> @lang('messages.close')
                </button>
                <button type="button" class="btn btn-info" onclick="printCustomerDetails()">
                    <i class="fa fa-print"></i> Print Details
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Updated Award Customer Modal with Translations and Proper Form -->
<div class="modal fade award_customer_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">@lang('advancedreports::lang.award_customer')</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <!-- FIX: Add method="POST" and CSRF token -->
            <form id="award_customer_form" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="award_customer_id" name="customer_id">
                    <input type="hidden" id="award_rank_position" name="rank_position">
                    <input type="hidden" id="award_period_type" name="period_type">

                    <div class="row">
                        <div class="col-md-12">
                            <h5 id="award_customer_name"></h5>
                            <hr>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="award_type">@lang('advancedreports::lang.award_type')</label>
                        <select class="form-control" id="award_type" name="award_type" required>
                            <option value="">@lang('advancedreports::lang.select_award_type')</option>
                            <option value="manual">@lang('advancedreports::lang.manual_entry')</option>
                            <option value="catalog">@lang('advancedreports::lang.from_catalog')</option>
                        </select>
                    </div>

                    <!-- Manual Award Fields -->
                    <div id="manual_award_fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label
                                        for="gift_description">@lang('advancedreports::lang.gift_description')</label>
                                    <input type="text" class="form-control" id="gift_description"
                                        name="gift_description"
                                        placeholder="e.g., {{ $currency_symbol ?? '$' }}50 Gift Voucher">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="gift_monetary_value">@lang('product.value') ({{ $currency_symbol ?? '$'
                                        }}):</label>
                                    <input type="number" class="form-control" id="gift_monetary_value"
                                        name="gift_monetary_value" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Catalog Award Fields -->
                    <div id="catalog_award_fields" style="display: none;">
                        <div class="form-group">
                            <label for="catalog_item_id">@lang('advancedreports::lang.select_gift_from_catalog')</label>
                            <select class="form-control" id="catalog_item_id" name="catalog_item_id">
                                <option value="">@lang('advancedreports::lang.loading_catalog')</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="award_quantity">@lang('advancedreports::lang.quantity')</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="award_quantity" name="award_quantity"
                                    value="1" min="1" max="999" step="1" placeholder="Enter quantity">
                                <span class="input-group-addon">units</span>
                            </div>
                            <small class="help-block">Number of units to award (will be deducted from stock)</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="award_notes">@lang('lang_v1.notes')</label>
                        <textarea class="form-control" id="award_notes" name="notes" rows="3"
                            placeholder="@lang('advancedreports::lang.additional_notes_about_award')"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
                    <button type="submit" class="btn btn-success" id="award_customer_submit_btn">
                        <i class="fa fa-gift"></i> @lang('advancedreports::lang.award_customer')
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Record Engagement Modal -->
<div class="modal fade record_engagement_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">@lang('advancedreports::lang.record_customer_engagement')</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="record_engagement_form">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                {!! Form::label('customer_id', __('contact.customer') . ':*') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-user"></i>
                                    </span>
                                    {!! Form::select('customer_id', [], null, [
                                    'class' => 'form-control',
                                    'id' => 'engagement_customer_id',
                                    'placeholder' => 'Enter Customer name / phone',
                                    'required'
                                    ]) !!}

                                </div>
                                <small class="text-danger hide contact_due_text">
                                    <strong>@lang('account.customer_due'):</strong> <span></span>
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="engagement_type">@lang('advancedreports::lang.engagement_type')</label>
                                <select class="form-control" id="engagement_type" name="engagement_type" required>
                                    <option value="">@lang('advancedreports::lang.select_type')</option>
                                    <option value="youtube_follow">@lang('advancedreports::lang.youtube_follow')
                                    </option>
                                    <option value="facebook_follow">@lang('advancedreports::lang.facebook_follow')
                                    </option>
                                    <option value="instagram_follow">@lang('advancedreports::lang.instagram_follow')
                                    </option>
                                    <option value="twitter_follow">@lang('advancedreports::lang.twitter_follow')
                                    </option>
                                    <option value="content_share">@lang('advancedreports::lang.content_share')
                                    </option>
                                    <option value="review">@lang('advancedreports::lang.review_testimonial')
                                    </option>
                                    <option value="google_review">@lang('advancedreports::lang.google_review')
                                    </option>
                                    <option value="referral">@lang('advancedreports::lang.customer_referral')
                                    </option>
                                    <option value="other">@lang('lang_v1.other')</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="engagement_points">@lang('advancedreports::lang.points') (0-10):</label>
                                <input type="number" class="form-control" id="engagement_points" name="points" min="0"
                                    max="10" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="engagement_platform">@lang('advancedreports::lang.platform')</label>
                                <input type="text" class="form-control" id="engagement_platform" name="platform"
                                    placeholder="e.g., Facebook, YouTube">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="reference_url">@lang('advancedreports::lang.reference_url')</label>
                                <input type="url" class="form-control" id="reference_url" name="reference_url"
                                    placeholder="Link to post/review">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="verification_notes">@lang('advancedreports::lang.verification_notes')</label>
                        <textarea class="form-control" id="verification_notes" name="verification_notes" rows="3"
                            required
                            placeholder="@lang('advancedreports::lang.describe_verification_notes')"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-plus"></i> @lang('advancedreports::lang.record_engagement')
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Finalize Period Modal -->
<div class="modal fade finalize_period_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">@lang('advancedreports::lang.finalize_period_rankings')</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fa fa-warning"></i>
                    <strong>@lang('advancedreports::lang.warning')</strong>
                    @lang('advancedreports::lang.message_warning_finalized1')
                    @lang('advancedreports::lang.message_warning_finalized2')
                </div>

                <div id="finalize_period_summary">
                    <p><strong>@lang('advancedreports::lang.period')</strong> <span id="finalize_period_label"></span>
                    </p>
                    <p><strong>@lang('advancedreports::lang.total_participants')</strong> <span
                            id="finalize_total_participants"></span></p>
                    <p><strong>@lang('advancedreports::lang.winners_count')</strong> <span
                            id="finalize_winners_count"></span></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
                <button type="button" class="btn btn-warning" id="confirm_finalize_period">
                    <i class="fa fa-lock"></i> @lang('advancedreports::lang.finalize_period')
                </button>
            </div>
        </div>
    </div>
</div>

@stop

@section('css')
<style>
    /* Enhanced Customer Details Modal Styles */
    .customer_details_modal .modal-header {
        border-bottom: 3px solid #fff;
        padding: 15px 20px;
    }

    .customer_details_modal .modal-body {
        padding: 20px;
    }

    .customer_details_modal .modal-footer {
        border-top: 1px solid #e5e5e5;
        padding: 15px 20px;
    }

    /* Customer Information Styles */
    .customer-info-list .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .customer-info-list .info-item:last-child {
        border-bottom: none;
    }

    .customer-info-list .info-item strong {
        min-width: 120px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Performance Stats */
    .performance-stats .stat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .performance-stats .stat-item:last-child {
        border-bottom: none;
    }

    .performance-stats .stat-item strong {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Engagement Stats */
    .engagement-stats .stat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .engagement-stats .stat-item:last-child {
        border-bottom: none;
    }

    .engagement-stats .stat-item strong {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Summary Stats */
    .summary-stat {
        padding: 15px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
    }

    .summary-stat:hover {
        border-color: #007bff;
        box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
        transform: translateY(-2px);
    }

    .summary-stat h3 {
        margin: 0 0 5px 0;
        font-weight: bold;
    }

    .summary-stat p {
        margin: 0;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Engagement Timeline */
    .engagement-timeline {
        max-height: 400px;
        overflow-y: auto;
    }

    .engagement-item {
        display: flex;
        align-items: flex-start;
        padding: 15px;
        border: 1px solid #e8e8e8;
        border-radius: 8px;
        margin-bottom: 10px;
        background: #fafafa;
        transition: all 0.2s ease;
    }

    .engagement-item:hover {
        background: #f0f8ff;
        border-color: #007bff;
        box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
    }

    .engagement-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        border: 2px solid #e0e0e0;
        font-size: 16px;
    }

    .engagement-content {
        flex: 1;
    }

    .engagement-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        padding-bottom: 8px;
        border-bottom: 1px solid #e0e0e0;
    }

    .engagement-details .row {
        margin-bottom: 5px;
    }

    .engagement-details small {
        display: block;
        line-height: 1.4;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .customer_details_modal .modal-dialog {
            width: 95%;
            margin: 10px auto;
        }

        .customer-info-list .info-item,
        .performance-stats .stat-item,
        .engagement-stats .stat-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }

        .engagement-item {
            flex-direction: column;
            text-align: center;
        }

        .engagement-icon {
            align-self: center;
            margin-bottom: 10px;
            margin-right: 0;
        }
    }

    /* Badge enhancements */
    .badge-lg {
        font-size: 14px;
        padding: 6px 12px;
    }

    /* Card styling for AdminLTE compatibility */
    .card {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
        border-radius: 6px;
    }

    .card-header {
        border-radius: 6px 6px 0 0;
        padding: 15px 20px;
    }

    .mb-4 {
        margin-bottom: 1.5rem;
    }

    .mb-3 {
        margin-bottom: 1rem;
    }

    .mb-1 {
        margin-bottom: 0.25rem;
    }

    .mt-2 {
        margin-top: 0.5rem;
    }

    .mt-1 {
        margin-top: 0.25rem;
    }

    .mt-3 {
        margin-top: 1rem;
    }

    .py-5 {
        padding-top: 3rem;
        padding-bottom: 3rem;
    }
</style>
@stop

@section('javascript')
<script type="text/javascript">
    // Currency settings from Ultimate POS business settings
var ultimatePOSCurrency = {
    symbol: "{{ $currency_symbol }}",
    precision: {{ $currency_precision }},
    position: "{{ $currency_symbol_placement }}",
    thousand_separator: "{{ $thousand_separator }}",
    decimal_separator: "{{ $decimal_separator }}"
};
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
 // Initialize product search for catalog items
       function initializeCatalogProductSearch() {
    var $element = $('#catalog_item_id');
    
    // Destroy existing Select2 if it exists
    if ($element.hasClass('select2-hidden-accessible')) {
        $element.select2('destroy');
    }
    
    // Initialize Select2 with product search
    $element.select2({
        ajax: {
            url: '/products/list',
            dataType: 'json',
            delay: 1000,
            data: function(params) {
                var price_group = '';
                var search_fields = [];
                $('.search_fields:checked').each(function(i){
                  search_fields[i] = $(this).val();
                });

                if ($('#price_group').length > 0) {
                    price_group = $('#price_group').val();
                }

                return {
                    price_group: price_group,
                    location_id: $('#location_filter').val() || '',
                    term: params.term,
                    not_for_selling: 0,
                    search_fields: search_fields,
                    page: params.page || 1
                };
            },
            processResults: function(data) {
                // Filter only products that are available
                var filteredResults = data.filter(function(item) {
                    var is_overselling_allowed = $('input#is_overselling_allowed').length > 0;
                    var for_so = $('#sale_type').length && $('#sale_type').val() == 'sales_order';
                    
                    return (item.enable_stock != 1 || item.qty_available > 0 || is_overselling_allowed || for_so);
                });

                return {
                    results: filteredResults.map(function(item) {
                        var selling_price = item.variation_group_price || item.selling_price;
                        var displayName = item.name;
                        if (item.type == 'variable') {
                            displayName += ' - ' + item.variation;
                        }
                        displayName += ' (' + item.sub_sku + ')';
                        
                        return {
                            id: item.variation_id,
                            text: displayName,
                            price: selling_price,
                            formatted_price: __currency_trans_from_en(selling_price, false, false, __currency_precision, true),
                            stock_qty: item.qty_available,
                            unit: item.unit,
                            enable_stock: item.enable_stock
                        };
                    })
                };
            }
        },
        templateResult: function (item) {
            if (item.loading) {
                return item.text;
            }
            
            var template = '<div>';
            template += '<strong>' + item.text + '</strong>';
            template += '<br><small>Price: ' + item.formatted_price;
            
            if (item.enable_stock == 1) {
                var qty_formatted = __currency_trans_from_en(item.stock_qty, false, false, __currency_precision, true);
                template += ' - ' + qty_formatted + item.unit;
            }
            template += '</small></div>';
            
            return $(template);
        },
        templateSelection: function(item) {
            return item.text || item.name;
        },
        minimumInputLength: 2,
        allowClear: true,
        placeholder: 'Search products...',
        width: '100%',
        dropdownParent: $('.award_customer_modal'),
        language: {
            inputTooShort: function (args) {
                return 'Please enter ' + args.minimum + ' or more characters';
            },
            noResults: function() {
                return 'No products found';
            },
            searching: function() {
                return 'Searching...';
            }
        },
        escapeMarkup: function(markup) {
            return markup;
        }
    });
}
    $(document).ready(function() {
      

        // Initialize Select2
        $('.select2').select2();

        // Initialize DataTable
       // Update your DataTable initialization
var customer_recognition_table = $('#customer_recognition_table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: "{{ route('advancedreports.customer-recognition.data') }}",
        data: function (d) {
            d.period_type = $('#period_type_filter').val();
            d.winner_count = $('#winner_count_filter').val();
            d.status = $('#status_filter').val();
            d.location_id = $('#location_filter').val();
        },
        error: function(xhr, error, thrown) {
            console.log('DataTables AJAX Error:', xhr.responseText);
            toastr.error('Error loading data. Check console for details.');
        }
    },
    columns: [
        { data: 'rank_position', name: 'rank_position', width: '5%', className: 'text-center' },
        { data: 'customer_name', name: 'customer_name', width: '18%' },
        { data: 'sales_total', name: 'sales_total', width: '10%', className: 'text-right' },
        { data: 'total_paid', name: 'total_paid', width: '10%', className: 'text-right' },        // NEW
        { data: 'balance_due', name: 'balance_due', width: '10%', className: 'text-right' },     // NEW
        { data: 'payment_percentage', name: 'payment_percentage', width: '8%', className: 'text-center' }, // NEW
        { data: 'transaction_count', name: 'transaction_count', width: '7%', className: 'text-center' },
        { data: 'avg_transaction_value', name: 'avg_transaction_value', width: '10%', className: 'text-right' },
        { data: 'engagement_points', name: 'engagement_points', width: '8%', className: 'text-center' },
        { data: 'final_score', name: 'final_score', width: '8%', className: 'text-center' },
        { data: 'awarded_info', name: 'awarded_info', width: '10%', orderable: false },
        { data: 'action', name: 'action', width: '6%', orderable: false, searchable: false }
    ],
    order: [[0, 'asc']],
    pageLength: 25,
    createdRow: function(row, data, dataIndex) {
        if (data.rank_position <= 3) {
            $(row).addClass('success');
        }
    }
});

        // Initialize Customer Engagements DataTable
        var customer_engagements_table = $('#customer_engagements_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.customer-recognition.engagements-data') }}",
                data: function (d) {
                    d.period_type = $('#period_type_filter').val();
                    d.location_id = $('#location_filter').val();
                },
                error: function(xhr, error, thrown) {
                    console.log('Customer Engagements DataTable AJAX Error:', xhr.responseText);
                    toastr.error('Error loading engagement data. Check console for details.');
                }
            },
            columns: [
                { data: 'customer_name', name: 'customer_name', width: '15%' },
                { data: 'engagement_type_name', name: 'engagement_type_name', width: '15%' },
                { data: 'platform', name: 'platform', width: '12%', className: 'text-center' },
                { data: 'reference_url', name: 'reference_url', width: '20%' },
                { data: 'verification_notes', name: 'verification_notes', width: '18%' },
                { data: 'points', name: 'points', width: '8%', className: 'text-center' },
                { data: 'recorded_by_name', name: 'recorded_by_name', width: '12%' },
                { data: 'recorded_date', name: 'recorded_date', width: '10%', className: 'text-center' },
                { data: 'status', name: 'status', width: '8%', className: 'text-center',
                  render: function(data, type, row) {
                      var statusClass = data === 'verified' ? 'success' : (data === 'pending' ? 'warning' : 'default');
                      return '<span class="label label-' + statusClass + '">' + data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
                  }
                }
            ],
            order: [[7, 'desc']], // Order by recorded_date desc
            pageLength: 15,
            language: {
                emptyTable: "@lang('advancedreports::lang.no_customer_engagements_found')"
            }
        });

        // Refresh engagement table
        $('#refresh_engagements_table').click(function() {
            customer_engagements_table.ajax.reload();
        });

        // Filter change events
      $('#period_type_filter, #winner_count_filter, #status_filter, #location_filter').on('change', function() {
    console.log('Filter changed:', $(this).attr('id'), '=', $(this).val());
    customer_recognition_table.ajax.reload();
    customer_engagements_table.ajax.reload();
    loadSummaryData();
});

        // Refresh data
        $('#refresh_data, #refresh_table').click(function() {
            customer_recognition_table.ajax.reload();
            customer_engagements_table.ajax.reload();
            loadSummaryData();
        });

        // Customer details modal
      $(document).on('click', '.view-customer-details', function() {
    console.log('View customer details clicked');
    
    var customerId = $(this).data('customer-id');
    var periodType = $(this).data('period-type');
    var periodStart = $(this).data('period-start');
    var periodEnd = $(this).data('period-end');
    
    console.log('Button data attributes:', {
        customerId: customerId,
        periodType: periodType,
        periodStart: periodStart,
        periodEnd: periodEnd
    });
    
    // Validate required data
    if (!customerId) {
        toastr.error('Customer ID is missing');
        return;
    }
    
    if (!periodType) {
        periodType = $('#period_type_filter').val() || 'monthly';
        console.log('Using fallback period type:', periodType);
    }
    
    $('.customer_details_modal').modal('show');
    loadCustomerDetails(customerId, periodType, periodStart, periodEnd);
});

        // Award customer modal
        $(document).on('click', '.award-customer', function() {
            var customerId = $(this).data('customer-id');
            var customerName = $(this).data('customer-name');
            var rank = $(this).data('rank');
            
            $('#award_customer_id').val(customerId);
            $('#award_customer_name').text('Award for: ' + customerName + ' (Rank ' + rank + ')');
            $('#award_rank_position').val(rank);
            
            // Initialize product search for catalog items
            initializeCatalogProductSearch();
            $('.award_customer_modal').modal('show');
        });

        // Award type change
        $('#award_type').on('change', function() {
            var type = $(this).val();
            if (type === 'manual') {
                $('#manual_award_fields').show();
                $('#catalog_award_fields').hide();
                $('#gift_description').attr('required', true);
                $('#catalog_item_id').attr('required', false);
            } else if (type === 'catalog') {
                $('#manual_award_fields').hide();
                $('#catalog_award_fields').show();
                $('#gift_description').attr('required', false);
                $('#catalog_item_id').attr('required', true);
                
                // Initialize product search when catalog is selected
                setTimeout(function() {
                    initializeCatalogProductSearch();
                }, 100);
            } else {
                $('#manual_award_fields').hide();
                $('#catalog_award_fields').hide();
            }
        });

        // Award customer form submission
        // Award customer form submission
// Award customer form submission
$('#award_customer_form').on('submit', function(e) {
    e.preventDefault();
    
    var awardType = $('#award_type').val();
    var formData = new FormData(this); // Use FormData instead of serialize
    
    // Only include catalog_item_id if award type is catalog
    if (awardType !== 'catalog') {
        formData.delete('catalog_item_id');
    }
    
    // Only include manual fields if award type is manual
    if (awardType !== 'manual') {
        formData.delete('gift_description');
        formData.delete('gift_monetary_value');
    }
    
    formData.append('period_type', $('#period_type_filter').val());
    formData.append('location_id', $('#location_filter').val());
    
    $.ajax({
        url: "{{ route('advancedreports.customer-recognition.award') }}",
        type: 'POST',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function() {
            $('#award_customer_submit_btn').prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> Processing...');
        },
        success: function(response) {
            console.log('Success response:', response);
            if (response.success) {
                toastr.success('Customer awarded successfully!');
                $('.award_customer_modal').modal('hide');
                $('#award_customer_form')[0].reset();
                $('#award_type').trigger('change'); // Reset the form state
                customer_recognition_table.ajax.reload();
                loadSummaryData();
            } else {
                toastr.error(response.message || 'Error awarding customer');
            }
        },
        error: function(xhr) {
            console.log('Award Customer Error:', xhr.responseText);
            console.log('Status:', xhr.status);
            console.log('Response:', xhr.responseJSON);
            
            var message = 'Error awarding customer';
            
            if (xhr.responseJSON) {
                if (xhr.responseJSON.error) {
                    message = xhr.responseJSON.error;
                } else if (xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseJSON.errors) {
                    // Handle validation errors
                    var errors = Object.values(xhr.responseJSON.errors).flat();
                    message = errors.join('<br>');
                }
            }
            
            toastr.error(message);
        },
        complete: function() {
            $('#award_customer_submit_btn').prop('disabled', false)
                .html('<i class="fa fa-gift"></i> @lang("advancedreports::lang.award_customer")');
        }
    });
});

        // Unaward customer functionality
        $(document).on('click', '.unaward-customer', function() {
            var awardId = $(this).data('award-id');
            var customerName = $(this).data('customer-name');
            var awardDescription = $(this).data('award-description');
            
            // Confirmation dialog
            if (confirm('Are you sure you want to unaward ' + customerName + '?\n\nAward: ' + awardDescription + '\n\nThis will restore the stock (if applicable) and remove the award.')) {
                // Show loading
                var $button = $(this);
                var originalHtml = $button.html();
                $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
                
                $.ajax({
                    url: "{{ route('advancedreports.customer-recognition.unaward') }}",
                    type: 'POST',
                    data: {
                        '_token': $('meta[name="csrf-token"]').attr('content'),
                        'award_id': awardId,
                        'location_id': $('#location_filter').val(),
                        'confirmation': 'CONFIRMED'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message || 'Customer unaward successful!');
                            
                            // Refresh the table
                            customer_recognition_table.ajax.reload(null, false);
                        } else {
                            toastr.error(response.message || 'Failed to unaward customer');
                        }
                    },
                    error: function(xhr) {
                        var message = 'An error occurred while removing the award';
                        
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            message = xhr.responseJSON.error;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = Object.values(xhr.responseJSON.errors).flat();
                            message = errors.join('<br>');
                        }
                        
                        toastr.error(message);
                    },
                    complete: function() {
                        $button.prop('disabled', false).html(originalHtml);
                    }
                });
            }
        });

        // Record engagement modal
        $('#record_engagement_btn').click(function() {
            $('.record_engagement_modal').modal('show');
        });

        // Record engagement form submission
        $('#record_engagement_form').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: "{{ route('advancedreports.customer-recognition.record-engagement') }}",
                method: 'POST',
                data: $(this).serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Engagement recorded successfully!');
                        $('.record_engagement_modal').modal('hide');
                        $('#record_engagement_form')[0].reset();
                        $('#engagement_customer_id').val('').trigger('change');
                        customer_recognition_table.ajax.reload();
                        loadSummaryData();
                    }
                },
                error: function(xhr) {
                    var message = xhr.responseJSON?.error || 'Error recording engagement';
                    toastr.error(message);
                }
            });
        });

// Finalize period modal
$('#finalize_period_btn').click(function() {
    var periodType = $('#period_type_filter').val();
    var winnerCount = $('#winner_count_filter').val();
    
    $('#finalize_period_label').text(periodType.charAt(0).toUpperCase() + periodType.slice(1) + ' Period');
    $('#finalize_winners_count').text(winnerCount);
    
    $.ajax({
        url: "{{ route('advancedreports.customer-recognition.summary') }}",
        type: 'GET',  // This one is correct as GET
        data: { period_type: periodType },
        success: function(response) {
            $('#finalize_total_participants').text(response.period_statistics.total_participants);
        }
    });
    
    $('.finalize_period_modal').modal('show');
});
 // Confirm finalize period - THIS IS THE ONE THAT NEEDS FIXING
// Confirm finalize period - COMPLETELY REWRITTEN
$('#confirm_finalize_period').on('click', function(e) {
    e.preventDefault(); // Prevent any default action
    e.stopPropagation(); // Stop event bubbling
    
    console.log('Finalize period button clicked');
    
    var periodType = $('#period_type_filter').val();
    var winnerCount = $('#winner_count_filter').val();
    
    console.log('Sending data:', {
        period_type: periodType,
        winner_count: winnerCount
    });
    
    $.ajax({
        url: "{{ route('advancedreports.customer-recognition.finalize') }}",
        type: 'POST',
        method: 'POST',
        data: {
            period_type: periodType,
            winner_count: winnerCount,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        beforeSend: function() {
            $('#confirm_finalize_period').prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> Finalizing...');
        },
        success: function(response) {
            console.log('Finalize success response:', response);
            if (response.success) {
                toastr.success('Period finalized successfully!');
                $('.finalize_period_modal').modal('hide');
                customer_recognition_table.ajax.reload();
                loadSummaryData();
            } else {
                toastr.error(response.message || 'Error finalizing period');
            }
        },
        error: function(xhr, status, error) {
            console.error('Finalize Period Error:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            
            var errorMessage = 'Unknown error occurred during finalization';
            var detailedMessage = '';
            
            console.log('Full error response:', xhr.responseJSON);
            
            if (xhr.responseJSON) {
                if (xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                    
                    // Add detailed error information if available
                    if (xhr.responseJSON.details) {
                        detailedMessage = '\n\nDetails:\n' +
                            '• Type: ' + (xhr.responseJSON.details.error_type || 'Unknown') + '\n' +
                            '• File: ' + (xhr.responseJSON.details.file || 'Unknown') + '\n' +
                            '• Line: ' + (xhr.responseJSON.details.line || 'Unknown');
                    }
                } else if (xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                } else if (xhr.responseJSON.errors) {
                    // Handle validation errors
                    var errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join('\n');
                }
            } else {
                errorMessage = 'Server error (Status: ' + xhr.status + ' - ' + xhr.statusText + ')';
            }
            
            // Show detailed error message with longer timeout for reading
            toastr.error(
                'Period Finalization Failed\n\n' + errorMessage + detailedMessage,
                'Error Details', 
                {
                    timeOut: 0,
                    extendedTimeOut: 0,
                    closeButton: true,
                    progressBar: false
                }
            );
        },
        complete: function() {
            $('#confirm_finalize_period').prop('disabled', false)
                .html('<i class="fa fa-lock"></i> @lang("advancedreports::lang.finalize_period")');
        }
    });
    
    return false; // Prevent any default action
});
        // Export functionality
        $('#export_btn').click(function(e) {
            e.preventDefault();

            var $btn = $(this);
            var originalText = $btn.html();
            $btn.html('<i class="fa fa-spinner fa-spin"></i> {{ __("advancedreports::lang.exporting") }}').prop('disabled', true);

            var params = {
                period_type: $('#period_type_filter').val(),
                winner_count: $('#winner_count_filter').val(),
                status: $('#status_filter').val(),
                location_id: $('#location_filter').val() || '',
                _token: '{{ csrf_token() }}'
            };

            // Use AJAX to download the file properly
            $.ajax({
                url: "{{ route('advancedreports.customer-recognition.export') }}",
                type: 'POST',
                data: params,
                xhrFields: {
                    responseType: 'blob'
                },
                success: function(data, status, xhr) {
                    // Create blob link to download
                    var blob = new Blob([data], {
                        type: xhr.getResponseHeader('Content-Type') || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    });

                    var link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);

                    // Get filename from response header or use default
                    var filename = 'customer-recognition-report.xlsx';
                    var disposition = xhr.getResponseHeader('Content-Disposition');
                    if (disposition && disposition.indexOf('attachment') !== -1) {
                        var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                        var matches = filenameRegex.exec(disposition);
                        if (matches != null && matches[1]) {
                            filename = matches[1].replace(/['"]/g, '');
                        }
                    }
                    link.download = filename;

                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    // Clean up
                    window.URL.revokeObjectURL(link.href);
                },
                error: function(xhr, status, error) {
                    alert('Export failed: ' + error);
                },
                complete: function() {
                    setTimeout(function() {
                        $btn.html(originalText).prop('disabled', false);
                    }, 1000);
                }
            });
        });

        // Load summary data
        function loadSummaryData() {
    var data = { period_type: $('#period_type_filter').val() };
    
    $.ajax({
        url: "{{ route('advancedreports.customer-recognition.summary') }}",
        data: data,
        success: function(response) {
            console.log('Summary data loaded:', response);
            updateWinnerCard('weekly', response.current_winners.weekly);
            updateWinnerCard('monthly', response.current_winners.monthly);
            updateWinnerCard('yearly', response.current_winners.yearly);
            
            $('#total_participants').text(response.period_statistics.total_participants);
            $('#total_sales').text(formatCurrency(response.period_statistics.total_sales));
            $('#total_engagement').text(formatNumber(response.period_statistics.total_engagement_points));
            $('#avg_score').text(formatCurrency(response.period_statistics.avg_score));
            $('#top_score').text(formatCurrency(response.period_statistics.top_score));
            $('#avg_transaction').text(formatCurrency(response.period_statistics.avg_transaction_value));
        },
        error: function(xhr, status, error) {
            console.log('Summary data error:', xhr.responseText);
            console.log('Status:', status);
            console.log('Error:', error);
            
            // Set default values on error
            updateWinnerCard('weekly', null);
            updateWinnerCard('monthly', null);
            updateWinnerCard('yearly', null);
            
            $('#total_participants').text('0');
            $('#total_sales').text('0');
            $('#total_engagement').text('0');
            $('#avg_score').text('0');
            $('#top_score').text('0');
            $('#avg_transaction').text('0');
        }
    });
}

        // Update winner card
      function updateWinnerCard(period, winner) {
    var nameElement = $('#' + period + '_winner_name');
    var salesElement = $('#' + period + '_winner_sales');
    
    if (winner) {
        var displayName = winner.business_name ? winner.business_name : winner.name;
        nameElement.text(displayName);
        salesElement.text(formatCurrency(winner.sales_total));
        
    } else {
        nameElement.text('No Winner Yet');
        salesElement.text('0.00');
    }
}

        // Load customer details
     function loadCustomerDetails(customerId, periodType, periodStart, periodEnd) {
    console.log('Loading customer details:', {
        customerId: customerId,
        periodType: periodType,
        periodStart: periodStart,
        periodEnd: periodEnd
    });
    
    $('#customer_details_content').html(`
        <div class="text-center">
            <i class="fa fa-spinner fa-spin fa-3x text-muted"></i>
            <p class="text-muted">Loading customer details...</p>
        </div>
    `);
    
    // Build the URL correctly
    var url = "{{ route('advancedreports.customer-recognition.details', '') }}/" + customerId;
    console.log('Request URL:', url);
    
    var requestData = {
        period_type: periodType,
        period_start: periodStart,
        period_end: periodEnd
    };
    console.log('Request data:', requestData);
    
    $.ajax({
        url: url,
        method: 'GET',
        data: requestData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },
        success: function(response) {
            console.log('Customer details response:', response);
            if (response.success) {
                renderCustomerDetails(response);
            } else {
                $('#customer_details_content').html('<div class="alert alert-danger">Invalid response format</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Customer details error:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            
            var errorMessage = 'Error loading customer details';
            
            try {
                var errorResponse = JSON.parse(xhr.responseText);
                if (errorResponse.error) {
                    errorMessage = errorResponse.error;
                } else if (errorResponse.message) {
                    errorMessage = errorResponse.message;
                }
            } catch (e) {
                if (xhr.status === 404) {
                    errorMessage = 'Customer not found';
                } else if (xhr.status === 403) {
                    errorMessage = 'Access denied';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error occurred';
                }
            }
            
            $('#customer_details_content').html(`
                <div class="alert alert-danger">
                    <strong>Error:</strong> ${errorMessage}
                    <br><small>Status: ${xhr.status} ${xhr.statusText}</small>
                </div>
            `);
        }
    });
}
        // Render customer details
function renderCustomerDetails(data) {
    try {
        var customer = data.customer;
        var ranking = data.ranking;
        var details = data.purchase_details;
        var engagements = data.engagements || [];
        var engagement_stats = data.engagement_stats || {};
        
        console.log('Rendering customer details:', data);
        
        var html = `
            <!-- Customer Header Card -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4 class="mb-0 text-white">
                                        <i class="fa fa-user-circle"></i> ${customer.name || 'Unknown Customer'}
                                    </h4>
                                    ${customer.supplier_business_name ? `<small>${customer.supplier_business_name}</small>` : ''}
                                </div>
                                <div class="col-md-4 text-right">
                                    ${ranking ? `<span class="badge badge-light badge-lg">${ranking.rank_suffix} Place</span>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Row -->
            <div class="row">
                <!-- Customer Information -->
                <div class="col-lg-4 col-md-6">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h4 class="box-title" style="color: white;">
                                <i class="fa fa-info-circle"></i> Customer Information
                            </h4>
                        </div>
                        <div class="box-body">
                            <div class="customer-info-list">
                                <div class="info-item">
                                    <strong><i class="fa fa-user text-muted"></i> Name:</strong>
                                    <span>${customer.name || 'N/A'}</span>
                                </div>
                                <div class="info-item">
                                    <strong><i class="fa fa-building text-muted"></i> Business:</strong>
                                    <span>${customer.supplier_business_name || 'N/A'}</span>
                                </div>
                                <div class="info-item">
                                    <strong><i class="fa fa-phone text-muted"></i> Mobile:</strong>
                                    <span>${customer.mobile || 'N/A'}</span>
                                </div>
                                <div class="info-item">
                                    <strong><i class="fa fa-envelope text-muted"></i> Email:</strong>
                                    <span>${customer.email || 'N/A'}</span>
                                </div>
                                <div class="info-item">
                                    <strong><i class="fa fa-calendar text-muted"></i> Registered:</strong>
                                    <span>${customer.registered_date || 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Period Performance -->
                <div class="col-lg-4 col-md-6">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h4 class="box-title" style="color: white;">
                                <i class="fa fa-trophy"></i> ${data.period.label} Performance
                            </h4>
                        </div>
                        <div class="box-body">`;
        
        if (ranking) {
            html += `
                            <div class="performance-stats">
                                <div class="stat-item text-center mb-3">
                                    <h2 class="mb-1" style="color: #007bff;">${ranking.rank_suffix}</h2>
                                    <small class="text-muted">Ranking Position</small>
                                </div>
                                <div class="stat-item">
                                    <strong><i class="fa fa-dollar"></i> Sales Total:</strong>
                                    <span class="pull-right">${formatCurrency(ranking.sales_total)}</span>
                                </div>
                                <div class="stat-item">
                                    <strong><i class="fa fa-star"></i> Final Score:</strong>
                                    <span class="pull-right">${formatNumber(ranking.final_score)}</span>
                                </div>
                                <div class="stat-item">
                                    <strong><i class="fa fa-heart"></i> Engagement Points:</strong>
                                    <span class="pull-right">${ranking.engagement_points}</span>
                                </div>
                                <div class="stat-item">
                                    <strong><i class="fa fa-shopping-cart"></i> Transactions:</strong>
                                    <span class="pull-right">${ranking.transaction_count}</span>
                                </div>
                                <div class="stat-item">
                                    <strong><i class="fa fa-calculator"></i> Avg Transaction:</strong>
                                    <span class="pull-right">${formatCurrency(ranking.avg_transaction_value)}</span>
                                </div>
                            </div>`;
        } else {
            html += '<p class="text-center text-muted"><i class="fa fa-info-circle"></i> No ranking data for this period</p>';
        }
        
        html += `
                        </div>
                    </div>
                </div>

                <!-- Engagement Statistics -->
                <div class="col-lg-4 col-md-12">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h4 class="box-title" style="color: white;">
                                <i class="fa fa-users"></i> Engagement Summary
                            </h4>
                        </div>
                        <div class="box-body">
                            <div class="engagement-stats">
                                <div class="stat-item">
                                    <strong><i class="fa fa-hashtag"></i> Total Engagements:</strong>
                                    <span class="pull-right badge bg-blue">${engagement_stats.total_engagements || 0}</span>
                                </div>
                                <div class="stat-item">
                                    <strong><i class="fa fa-star"></i> Total Points Earned:</strong>
                                    <span class="pull-right badge bg-blue">${engagement_stats.total_points || 0}</span>
                                </div>
                                ${engagement_stats.recent_engagement ? `
                                <div class="stat-item">
                                    <strong><i class="fa fa-clock-o"></i> Latest Engagement:</strong>
                                    <span class="pull-right text-muted">${engagement_stats.recent_engagement.recorded_date}</span>
                                </div>
                                <div class="stat-item">
                                    <small class="text-muted">
                                        <i class="fa ${engagement_stats.recent_engagement.platform_icon}"></i>
                                        ${engagement_stats.recent_engagement.engagement_type_name} on ${engagement_stats.recent_engagement.platform || 'N/A'}
                                    </small>
                                </div>
                                ` : '<div class="stat-item text-muted"><i class="fa fa-info-circle"></i> No engagements recorded</div>'}
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;

        // Purchase Summary Section
        if (details && details.summary && details.summary.total_transactions > 0) {
            html += `
                <div class="row">
                    <div class="col-md-12">
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h4 class="box-title" style="color: white;">
                                    <i class="fa fa-shopping-bag"></i> Purchase Summary - ${data.period.label}
                                </h4>
                            </div>
                            <div class="box-body">
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <div class="summary-stat">
                                            <h3 style="color: #007bff;">${details.summary.total_transactions}</h3>
                                            <p class="text-muted">Transactions</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="summary-stat">
                                            <h3 style="color: #007bff;">${formatCurrency(details.summary.total_amount)}</h3>
                                            <p class="text-muted">Total Amount</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="summary-stat">
                                            <h3 style="color: #007bff;">${details.summary.total_products}</h3>
                                            <p class="text-muted">Unique Products</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="summary-stat">
                                            <h3 style="color: #007bff;">${details.summary.total_engagement_points}</h3>
                                            <p class="text-muted">Engagement Points</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
        }

        // Customer Engagements Section
        if (engagements.length > 0) {
            html += `
                <div class="row">
                    <div class="col-md-12">
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h4 class="box-title" style="color: white;">
                                    <i class="fa fa-heart"></i> Recent Customer Engagements
                                </h4>
                                <div class="box-tools pull-right">
                                    <span class="label label-info">${engagements.length} recent</span>
                                </div>
                            </div>
                            <div class="box-body">
                                <div class="engagement-timeline">`;
            
            engagements.forEach(function(engagement) {
                var statusBadge = engagement.status === 'verified' ? 'success' : 'warning';
                html += `
                                    <div class="engagement-item">
                                        <div class="engagement-icon">
                                            <i class="fa ${engagement.platform_icon}" style="color: ${engagement.platform_color};"></i>
                                        </div>
                                        <div class="engagement-content">
                                            <div class="engagement-header">
                                                <strong>${engagement.engagement_type_name}</strong>
                                                <span class="pull-right text-muted">${engagement.recorded_date}</span>
                                            </div>
                                            <div class="engagement-details">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <small><strong>Platform:</strong> ${engagement.platform || 'N/A'}</small>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <small><strong>Points:</strong> <span class="badge bg-blue">${engagement.points}</span></small>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <small><strong>Status:</strong> <span class="label label-${statusBadge}">${engagement.status}</span></small>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <small><strong>Recorded by:</strong> ${engagement.recorded_by}</small>
                                                    </div>
                                                </div>
                                                ${engagement.reference_url ? `
                                                <div class="row mt-2">
                                                    <div class="col-md-12">
                                                        <small><strong>Reference:</strong> <a href="${engagement.reference_url}" target="_blank" class="text-primary">${engagement.reference_url.length > 50 ? engagement.reference_url.substring(0, 50) + '...' : engagement.reference_url}</a></small>
                                                    </div>
                                                </div>
                                                ` : ''}
                                                ${engagement.verification_notes ? `
                                                <div class="row mt-1">
                                                    <div class="col-md-12">
                                                        <small><strong>Notes:</strong> <em class="text-muted">${engagement.verification_notes}</em></small>
                                                    </div>
                                                </div>
                                                ` : ''}
                                            </div>
                                        </div>
                                    </div>`;
            });

            html += `
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
        }
        
        $('#customer_details_content').html(html);
        
    } catch (error) {
        console.error('Error rendering customer details:', error);
        $('#customer_details_content').html(`
            <div class="alert alert-danger">
                <strong><i class="fa fa-exclamation-triangle"></i> Error:</strong> Failed to render customer details
                <br><small class="text-muted">${error.message}</small>
            </div>
        `);
    }
}
       

   

        // Initial load
        loadSummaryData();
    });
</script>
<script>
    $(document).ready(function() {
    // Wait a moment for all other scripts to load
    setTimeout(function() {
        initializeEngagementCustomerSelect();
    }, 100);
});

function initializeEngagementCustomerSelect() {
    var $element = $('#engagement_customer_id');
    
    // Destroy existing Select2 if it exists
    if ($element.hasClass('select2-hidden-accessible')) {
        $element.select2('destroy');
    }
    
    // Remove any conflicting attributes
    $element.removeAttr('readonly').removeAttr('disabled');
    
    // Initialize Select2
    $element.select2({
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

            if (typeof(data.total_rp) != "undefined") {
                var rp = data.total_rp ? data.total_rp : 0;
                template += "<br><i class='fa fa-gift text-success'></i> " + rp;
            }

            return template;
        },
        templateSelection: function(data) {
            return data.text || data.name;
        },
        minimumInputLength: 1,
        allowClear: true,
        placeholder: 'Enter Customer name / phone...',
        width: '100%',
        dropdownParent: $('.record_engagement_modal'), // Important for modals
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
    
    console.log('Select2 initialized successfully');
}

$(document).ready(function() {
    console.log('jQuery loaded:', typeof $ !== 'undefined');
    console.log('Select2 loaded:', typeof $.fn.select2 !== 'undefined');
    console.log('Element exists:', $('#engagement_customer_id').length);
    console.log('Element classes:', $('#engagement_customer_id').attr('class'));
    
    // Check if already initialized
    if ($('#engagement_customer_id').hasClass('select2-hidden-accessible')) {
        console.log('Select2 already initialized - destroying first');
        $('#engagement_customer_id').select2('destroy');
    }
});
</script>
<style>
    #engagement_customer_id {
        pointer-events: auto !important;
    }

    .select2-container--default .select2-search--dropdown .select2-search__field {
        pointer-events: auto !important;
        background-color: white !important;
        border: 1px solid #ccc !important;
    }

    .select2-container {
        pointer-events: auto !important;
    }

    /* Specific for your modal */
    .record_engagement_modal .select2-container,
    .award_customer_modal .select2-container {
        z-index: 9999 !important;
    }

    /* Winner Cards Styling */
    .winner-card {
        height: 100px;
        display: flex;
        align-items: center;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        transition: all 0.3s ease;
        margin-bottom: 20px;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .winner-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
    }

    .winner-icon {
        font-size: 45px;
        margin-right: 18px;
        width: 55px;
        text-align: center;
        color: white;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .winner-content {
        flex-grow: 1;
        color: white;
    }

    .winner-period {
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 6px;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }

    .winner-name {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 4px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .winner-sales {
        font-size: 14px;
        font-weight: 600;
        opacity: 0.9;
    }

    .weekly-winner {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .monthly-winner {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .yearly-winner {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    /* Small box styling */
    .small-box {
        border-radius: 8px;
        margin-bottom: 15px;
        min-height: 120px;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .small-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .small-box .inner {
        padding: 15px;
    }

    .small-box .inner h3 {
        font-size: 24px;
        font-weight: 600;
        margin: 0 0 8px 0;
        color: #ffffff;
    }

    .small-box .inner p {
        font-size: 13px;
        margin: 0;
        color: rgba(255, 255, 255, 0.9);
    }

    .small-box .icon {
        position: absolute;
        top: 15px;
        right: 15px;
        font-size: 35px;
        color: rgba(255, 255, 255, 0.2);
    }

    /* Color variations */
    .small-box.bg-blue {
        background: linear-gradient(135deg, #3498db, #2980b9);
    }

    .small-box.bg-green {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
    }

    .small-box.bg-yellow {
        background: linear-gradient(135deg, #f39c12, #e67e22);
    }

    .small-box.bg-red {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
    }

    .small-box.bg-purple {
        background: linear-gradient(135deg, #9b59b6, #8e44ad);
    }

    .small-box.bg-orange {
        background: linear-gradient(135deg, #e67e22, #d35400);
    }

    /* Table styling */
    .table th {
        background-color: #f4f4f4;
        font-weight: 600;
        color: #444;
        border-bottom: 2px solid #ddd;
    }

    .table tbody tr.success {
        background-color: #dff0d8;
    }

    /* Modal improvements */
    .modal-lg {
        width: 95%;
        max-width: 1200px;
    }

    .box {
        margin-bottom: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .box-header {
        border-radius: 8px 8px 0 0;
        padding: 15px;
    }

    .box-header h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }

    .table-condensed td {
        padding: 6px;
        font-size: 13px;
    }

    /* Form styling */
    .form-group label {
        font-weight: 600;
        color: #495057;
    }

    .form-control:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    }

    /* Button styling */
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    /* Alert styling */
    .alert {
        border-radius: 6px;
        border: none;
    }

    .alert-warning {
        background-color: #fff3cd;
        color: #856404;
        border-left: 4px solid #ffc107;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }

    /* Badge styling */
    .badge {
        font-size: 11px;
        padding: 3px 6px;
    }

    .badge-info {
        background-color: #17a2b8;
    }

    .badge-primary {
        background-color: #007bff;
    }

    /* Label styling */
    .label {
        font-size: 10px;
        padding: 3px 6px;
        border-radius: 3px;
    }

    .label-success {
        background-color: #28a745;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .winner-card {
            height: 85px;
            padding: 14px;
        }

        .winner-icon {
            font-size: 36px;
            width: 45px;
            margin-right: 12px;
        }

        .winner-period {
            font-size: 11px;
        }

        .winner-name {
            font-size: 16px;
        }

        .winner-sales {
            font-size: 12px;
        }

        .small-box {
            height: 90px;
        }

        .small-box .inner h3 {
            font-size: 20px;
        }

        .small-box .inner p {
            font-size: 11px;
        }

        .small-box .icon {
            font-size: 30px;
            top: 12px;
            right: 12px;
        }
    }

    /* Box styling */
    .box-primary .box-header {
        background-color: #3498db;
        color: white;
    }

    .box-primary .box-header .box-title {
        color: white;
    }

    .box-info .box-header {
        background-color: #17a2b8;
        color: white;
    }

    .box-success .box-header {
        background-color: #28a745;
        color: white;
    }

    /* Input group styling */
    .input-group-addon {
        background-color: #f8f9fa;
        border: 1px solid #ced4da;
        color: #6c757d;
    }

    /* Loading states */
    .fa-spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>

<script>
    // Print customer details function
function printCustomerDetails() {
    var printContents = document.getElementById('customer_details_content').innerHTML;
    var modalTitle = document.querySelector('.customer_details_modal .modal-title').innerText;
    
    var printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>${modalTitle}</title>
            <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
            <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .box { border: 1px solid #ddd; margin-bottom: 20px; }
                .box-header { background: #f4f4f4; padding: 10px; border-bottom: 1px solid #ddd; }
                .box-body { padding: 15px; }
                .info-item, .stat-item { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #f0f0f0; }
                .engagement-item { border: 1px solid #e8e8e8; padding: 15px; margin-bottom: 10px; background: #fafafa; }
                .engagement-header { border-bottom: 1px solid #e0e0e0; padding-bottom: 8px; margin-bottom: 8px; }
                .text-success { color: #28a745; }
                .text-primary { color: #007bff; }
                .text-info { color: #17a2b8; }
                .text-warning { color: #ffc107; }
                .text-danger { color: #dc3545; }
                .text-muted { color: #6c757d; }
                .badge { background: #007bff; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; }
                .label { padding: 2px 6px; border-radius: 3px; font-size: 11px; }
                .label-success { background: #28a745; color: white; }
                .label-warning { background: #ffc107; color: black; }
                .label-info { background: #17a2b8; color: white; }
                @media print { 
                    .box { page-break-inside: avoid; }
                    a { color: #000 !important; text-decoration: underline; }
                }
            </style>
        </head>
        <body>
            <h1 style="text-align: center; margin-bottom: 30px;">${modalTitle}</h1>
            ${printContents}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
}
</script>
@endsection