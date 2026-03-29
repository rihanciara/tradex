@extends('advancedreports::layouts.app')
@php
// Get currency settings from Ultimate POS session
$currency_symbol = session('currency')['symbol'] ?? '';
$currency_precision = session('business.currency_precision') ?: 2;
$currency_symbol_placement = session('business.currency_symbol_placement') ?: 'before';
$thousand_separator = session('business.thousand_separator') ?: ',';
$decimal_separator = session('business.decimal_separator') ?: '.';
@endphp
@section('title', __('advancedreports::lang.service_staff_recognition_system'))

@section('content')

<!-- Content Header -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ __('advancedreports::lang.service_staff_recognition_system')}}
        <small class="text-muted">@lang('advancedreports::lang.weekly_monthly_yearly_staff_awards')</small>
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
                                @if(auth()->user()->can('sales_representative.create'))
                                <button type="button" class="btn btn-info" id="record_activity_btn">
                                    <i class="fa fa-plus"></i>
                                    @lang('advancedreports::lang.record_performance_activity')
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
                        @lang('advancedreports::lang.current_period_top_performers')</h3>
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
                                    <div class="winner-period">@lang('advancedreports::lang.weekly_top_performer')</div>
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
                                    <i class="fa fa-calendar"></i>
                                </div>
                                <div class="winner-content">
                                    <div class="winner-period">@lang('advancedreports::lang.monthly_top_performer')
                                    </div>
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
                                    <div class="winner-period">@lang('advancedreports::lang.yearly_top_performer')</div>
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
                    <div class="winner-sales" id="total_staff">0</div>
                    <p>@lang('advancedreports::lang.total_staff')</p>
                </div>
                <div class="icon" style="font-size: 55px"><i class="fa fa-users"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <div class="winner-sales" id="total_sales">0</div>
                    <p>@lang('advancedreports::lang.total_sales')</p>
                </div>
                <div class="icon" style="font-size: 55px"><i class="fa fa-money-bill"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <div class="winner-sales" id="total_activities">0</div>
                    <p>@lang('advancedreports::lang.performance_activities')</p>
                </div>
                <div class="icon" style="font-size: 55px"><i class="fa fa-star"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <div class="winner-sales" id="avg_performance">0</div>
                    <p>@lang('advancedreports::lang.average_performance')</p>
                </div>
                <div class="icon" style="font-size: 55px"><i class="fa fa-calculator"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="small-box bg-purple">
                <div class="inner">
                    <div class="winner-sales" id="top_performer_score">0</div>
                    <p>@lang('advancedreports::lang.top_performer_score')</p>
                </div>
                <div class="icon" style="font-size: 55px"><i class="fa fa-trophy"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="small-box bg-orange">
                <div class="inner">
                    <div class="winner-sales" id="avg_transaction">0</div>
                    <p>@lang('advancedreports::lang.avg_transaction')</p>
                </div>
                <div class="icon" style="font-size: 55px"><i class="fa fa-credit-card"></i></div>
            </div>
        </div>
    </div>

    <!-- Main Data Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title" style="color: white;"><i class="fa fa-list"></i>
                        @lang('advancedreports::lang.staff_rankings')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" id="refresh_table">
                            <i class="fa fa-refresh"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="staff_recognition_table">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.rank')</th>
                                    <th>@lang('advancedreports::lang.staff_name')</th>
                                    <th>@lang('advancedreports::lang.sales_total')</th>
                                    <th>@lang('advancedreports::lang.transactions')</th>
                                    <th>@lang('advancedreports::lang.avg_transaction')</th>
                                    <th>@lang('advancedreports::lang.performance_points')</th>
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

    <!-- Performance Activities Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title" style="color: white;"><i class="fa fa-tasks"></i>
                        @lang('advancedreports::lang.performance_activities')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" id="refresh_activities_table">
                            <i class="fa fa-refresh"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="performance_activities_table">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.staff_name')</th>
                                    <th>@lang('advancedreports::lang.activity_type')</th>
                                    <th>@lang('advancedreports::lang.description')</th>
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

<!-- Award Staff Modal -->
<div class="modal fade award_staff_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">@lang('advancedreports::lang.award_staff')</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="award_staff_form" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="award_staff_id" name="staff_id">
                    <input type="hidden" id="award_rank_position" name="rank_position">
                    <input type="hidden" id="award_period_type" name="period_type">

                    <div class="row">
                        <div class="col-md-12">
                            <h5 id="award_staff_name"></h5>
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
                            <select class="form-control select2" id="catalog_item_id" name="catalog_item_id">
                                <option value="">@lang('advancedreports::lang.loading_catalog')</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="award_quantity">@lang('advancedreports::lang.quantity')</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="award_quantity" name="award_quantity"
                                    value="1" min="1" max="999">
                                <span class="input-group-addon">units</span>
                            </div>
                            <small class="help-block">Number of units to award (will be deducted from stock)</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="award_notes">@lang('lang_v1.notes')</label>
                        <textarea class="form-control" id="award_notes" name="award_notes" rows="3"
                            placeholder="@lang('advancedreports::lang.additional_notes_about_award')"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    <button type="submit" class="btn btn-success">@lang('advancedreports::lang.award_staff')</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Record Performance Activity Modal -->
<div class="modal fade record_activity_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">@lang('advancedreports::lang.record_performance_activity')</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="record_activity_form" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="activity_staff_id">@lang('advancedreports::lang.staff_member')</label>
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            {!! Form::select('staff_id', $waiters, null, [
                            'class' => 'form-control select2',
                            'id' => 'activity_staff_id',
                            'placeholder' => 'Select Staff Member',
                            'required'
                            ]) !!}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="activity_type">@lang('advancedreports::lang.activity_type')</label>
                        <select class="form-control" id="activity_type" name="activity_type" required>
                            <option value="">@lang('advancedreports::lang.select_type')</option>
                            <option value="punctuality">@lang('advancedreports::lang.punctuality')</option>
                            <option value="customer_service">@lang('advancedreports::lang.customer_service_excellence')
                            </option>
                            <option value="upselling">@lang('advancedreports::lang.upselling_success')</option>
                            <option value="teamwork">@lang('advancedreports::lang.teamwork')</option>
                            <option value="training_completion">@lang('advancedreports::lang.training_completion')
                            </option>
                            <option value="cleanliness">@lang('advancedreports::lang.cleanliness_organization')</option>
                            <option value="other">@lang('advancedreports::lang.other')</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="activity_points">@lang('advancedreports::lang.points')</label>
                        <input type="number" class="form-control" id="activity_points" name="points" min="1" max="100"
                            value="10" required>
                        <small class="help-block">Points to award for this activity (1-100)</small>
                    </div>

                    <div class="form-group">
                        <label for="activity_description">@lang('advancedreports::lang.description')</label>
                        <textarea class="form-control" id="activity_description" name="description" rows="3"
                            placeholder="Describe the performance activity..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="verification_notes">@lang('advancedreports::lang.verification_notes')</label>
                        <textarea class="form-control" id="verification_notes" name="verification_notes" rows="3"
                            placeholder="@lang('advancedreports::lang.describe_verification_notes')"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    <button type="submit"
                        class="btn btn-success">@lang('advancedreports::lang.record_activity')</button>
                </div>
            </form>
        </div>
    </div>
</div>

@stop

@section('css')
<style>
    /* Winner Card Styles */
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

    /* Responsive winner cards */
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

    /* Staff Recognition Modal Styles */
    .award_staff_modal .modal-header,
    .record_activity_modal .modal-header {
        border-bottom: 3px solid #fff;
        padding: 15px 20px;
    }

    .award_staff_modal .modal-body,
    .record_activity_modal .modal-body {
        padding: 20px;
    }

    .award_staff_modal .modal-footer,
    .record_activity_modal .modal-footer {
        border-top: 1px solid #e5e5e5;
        padding: 15px 20px;
    }

    /* Statistics boxes hover effect */
    .small-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    /* Table enhancements */
    .table-responsive {
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    #staff_recognition_table th,
    #performance_activities_table th {
        background-color: #f5f5f5;
        font-weight: bold;
    }

    /* Badge styling */
    .badge {
        font-size: 11px;
        padding: 4px 8px;
    }

    .label {
        font-size: 11px;
        padding: 3px 6px;
    }

    /* Button group styling */
    .btn-group .btn {
        margin-right: 2px;
    }

    .btn-xs {
        padding: 1px 5px;
        font-size: 12px;
        line-height: 1.5;
        border-radius: 3px;
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
        if (num === null || num === undefined || num === '') {
            return __currency_trans_from_en('0.00', true);
        }
        
        if (typeof num === 'number') {
            return __currency_trans_from_en(num.toFixed(2), true);
        }
        
        if (typeof num === 'string') {
            var cleanStr = num.replace(/[^\d.-]/g, '');
            var parsed = parseFloat(cleanStr);
            
            if (!isNaN(parsed)) {
                return __currency_trans_from_en(parsed.toFixed(2), true);
            }
        }
        
        return __currency_trans_from_en('0.00', true);
    }

    function formatNumber(num) {
        if (num === null || num === undefined || num === '') {
            return '0';
        }
        
        if (typeof num === 'number') {
            return num.toLocaleString('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            });
        }
        
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
        
        return '0';
    }

    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2();

        // Initialize Staff Recognition DataTable
        var staff_recognition_table = $('#staff_recognition_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.staff-recognition.data') }}",
                data: function (d) {
                    d.period_type = $('#period_type_filter').val();
                    d.winner_count = $('#winner_count_filter').val();
                    d.status = $('#status_filter').val();
                    d.location_id = $('#location_filter').val();
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTables AJAX Error:', xhr.responseText);
                    toastr.error('Error loading data. Check console for details.');
                }
            },
            columns: [
                { data: 'rank_position', name: 'rank_position', width: '5%', className: 'text-center' },
                { data: 'staff_name', name: 'staff_name', width: '20%' },
                { data: 'sales_total', name: 'sales_total', width: '12%', className: 'text-right' },
                { data: 'transaction_count', name: 'transaction_count', width: '10%', className: 'text-center' },
                { data: 'avg_transaction_value', name: 'avg_transaction_value', width: '12%', className: 'text-right' },
                { data: 'performance_points', name: 'performance_points', width: '10%', className: 'text-center' },
                { data: 'final_score', name: 'final_score', width: '10%', className: 'text-center' },
                { data: 'awarded_info', name: 'awarded_info', width: '12%', orderable: false },
                { data: 'action', name: 'action', width: '9%', orderable: false, searchable: false }
            ],
            order: [[0, 'asc']],
            pageLength: 25,
            createdRow: function(row, data, dataIndex) {
                if (data.rank_position <= 3) {
                    $(row).addClass('success');
                }
            }
        });

        // Initialize Performance Activities DataTable
        var performance_activities_table = $('#performance_activities_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.staff-recognition.activities-data') }}",
                error: function(xhr, error, thrown) {
                    console.error('Activities DataTable AJAX Error:', xhr.responseText);
                    toastr.error('Error loading activities data.');
                }
            },
            columns: [
                { data: 'staff_name', name: 'staff_name', width: '18%' },
                { data: 'activity_type', name: 'activity_type', width: '15%' },
                { data: 'description', name: 'description', width: '25%' },
                { data: 'points', name: 'points', width: '8%', className: 'text-center' },
                { data: 'recorded_by_name', name: 'recorded_by_name', width: '15%' },
                { data: 'recorded_date', name: 'recorded_date', width: '12%', className: 'text-center' },
                { data: 'status', name: 'status', width: '7%', className: 'text-center' }
            ],
            order: [[5, 'desc']], // Order by recorded_date desc
            pageLength: 15,
            language: {
                emptyTable: "@lang('advancedreports::lang.no_performance_activities_found')"
            }
        });

        // Filter change events
        $('#period_type_filter, #winner_count_filter, #status_filter, #location_filter').on('change', function() {
            staff_recognition_table.ajax.reload();
            performance_activities_table.ajax.reload();
            loadSummaryData();
        });

        // Refresh data
        $('#refresh_data, #refresh_table').click(function() {
            staff_recognition_table.ajax.reload();
            performance_activities_table.ajax.reload();
            loadSummaryData();
        });

        $('#refresh_activities_table').click(function() {
            performance_activities_table.ajax.reload();
        });

        // Award staff modal
        $(document).on('click', '.award-staff', function() {
            var staffId = $(this).data('staff-id');
            var staffName = $(this).data('staff-name');
            var rank = $(this).data('rank');
            
            $('#award_staff_id').val(staffId);
            $('#award_staff_name').text('Award for: ' + staffName + ' (Rank ' + rank + ')');
            $('#award_rank_position').val(rank);
            $('#award_period_type').val($('#period_type_filter').val());
            
            // Initialize product search for catalog items
            initializeCatalogProductSearch();
            $('.award_staff_modal').modal('show');
        });

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
                                    formatted_price: item.selling_price,
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
                    template += '<br><small>Price: ' + (item.formatted_price || '0');
                    
                    if (item.enable_stock == 1) {
                        var qty_formatted = item.stock_qty || 0;
                        template += ' - Stock: ' + qty_formatted + (item.unit || '');
                    }
                    template += '</small></div>';
                    
                    return $(template);
                },
                templateSelection: function(item) {
                    return item.text || item.name;
                },
                minimumInputLength: 2,
                allowClear: true,
                placeholder: "@lang('advancedreports::lang.select_gift_from_catalog')",
                width: '100%',
                dropdownParent: $('.award_staff_modal'),
                language: {
                    inputTooShort: function (args) {
                        return 'Please enter ' + args.minimum + ' or more characters';
                    },
                    noResults: function() {
                        return "@lang('advancedreports::lang.no_products_found')";
                    },
                    searching: function() {
                        return "@lang('advancedreports::lang.searching')";
                    }
                },
                escapeMarkup: function(markup) {
                    return markup;
                }
            });
        }

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

        // Record activity button
        $('#record_activity_btn').click(function() {
            $('.record_activity_modal').modal('show');
        });

        // Award staff form submission
        $('#award_staff_form').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: "{{ route('advancedreports.staff-recognition.award') }}",
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('.award_staff_modal').modal('hide');
                        staff_recognition_table.ajax.reload();
                        $('#award_staff_form')[0].reset();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    var errorMessage = 'Error awarding staff member';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                }
            });
        });

        // Record activity form submission
        $('#record_activity_form').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: "{{ route('advancedreports.staff-recognition.record-activity') }}",
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('.record_activity_modal').modal('hide');
                        performance_activities_table.ajax.reload();
                        staff_recognition_table.ajax.reload();
                        $('#record_activity_form')[0].reset();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    var errorMessage = 'Error recording activity';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                }
            });
        });

        // Update winner card function
        function updateWinnerCard(period, winner) {
            var cardSelectors = {
                'weekly': {
                    name: '#weekly_winner_name',
                    sales: '#weekly_winner_sales'
                },
                'monthly': {
                    name: '#monthly_winner_name',
                    sales: '#monthly_winner_sales'
                },
                'yearly': {
                    name: '#yearly_winner_name',
                    sales: '#yearly_winner_sales'
                }
            };

            var selectors = cardSelectors[period];
            if (!selectors) return;

            if (winner && winner.staff_name && winner.staff_name !== 'N/A') {
                $(selectors.name).text(winner.staff_name);
                $(selectors.sales).text(formatCurrency(winner.sales_total || 0));
            } else {
                $(selectors.name).text('@lang("advancedreports::lang.no_data_available")');
                $(selectors.sales).text(formatCurrency(0));
            }
        }

        // Load summary data function
        function loadSummaryData() {
            var data = { 
                period_type: $('#period_type_filter').val(),
                location_id: $('#location_filter').val()
            };

            $.ajax({
                url: "{{ route('advancedreports.staff-recognition.summary') }}",
                data: data,
                success: function(response) {
                    console.log('Staff summary data loaded:', response);
                    
                    // Update winner cards
                    if (response.current_winners) {
                        updateWinnerCard('weekly', response.current_winners.weekly);
                        updateWinnerCard('monthly', response.current_winners.monthly);
                        updateWinnerCard('yearly', response.current_winners.yearly);
                    }
                    
                    // Update statistics
                    if (response.success && response.statistics) {
                        $('#total_staff').text(response.statistics.total_staff || '0');
                        $('#total_sales').text(formatCurrency(response.statistics.total_sales || 0));
                        $('#total_activities').text(response.statistics.total_activities || '0');
                        $('#avg_performance').text(formatNumber(response.statistics.avg_performance || 0));
                        $('#top_performer_score').text(formatNumber(response.statistics.top_performer_score || 0));
                        $('#avg_transaction').text(formatCurrency(response.statistics.avg_transaction || 0));
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Staff summary data error:', xhr.responseText);
                    console.log('Status:', status);
                    console.log('Error:', error);
                    
                    // Set default values for winner cards on error
                    updateWinnerCard('weekly', null);
                    updateWinnerCard('monthly', null);
                    updateWinnerCard('yearly', null);
                    
                    // Set default values for statistics
                    $('#total_staff').text('0');
                    $('#total_sales').text(formatCurrency(0));
                    $('#total_activities').text('0');
                    $('#avg_performance').text('0');
                    $('#top_performer_score').text('0');
                    $('#avg_transaction').text(formatCurrency(0));
                }
            });
        }

        // Initial load of summary data
        loadSummaryData();
    });
</script>
@stop