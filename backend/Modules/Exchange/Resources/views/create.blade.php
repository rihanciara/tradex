@extends('layouts.app')

@section('title', __('exchange::lang.exchange'))

@push('stylesheets')


<style>
    /* Success Dialog Overlay - ULTRA HIGH SPECIFICITY */
    .success-dialog-overlay {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        background: rgba(0, 0, 0, 0.7) !important;
        backdrop-filter: blur(3px) !important;
        z-index: 999999 !important;

        /* CRITICAL: Force display and centering */
        display: none !important;
        align-items: center !important;
        justify-content: center !important;

        /* Ensure it's above everything */
        pointer-events: auto !important;

        /* Animation */
        opacity: 0 !important;
        transition: opacity 0.3s ease !important;
    }

    /* When shown */
    .success-dialog-overlay.show {
        display: flex !important;
        opacity: 1 !important;
    }

    /* Success Dialog Container - ENHANCED */
    .success-dialog {
        background: #ffffff !important;
        border-radius: 20px !important;
        padding: 0 !important;
        min-width: 450px !important;
        max-width: 550px !important;
        width: 90vw !important;
        max-height: 90vh !important;

        /* CRITICAL: Positioning */
        position: relative !important;
        top: auto !important;
        left: auto !important;
        transform: none !important;
        margin: 0 auto !important;

        /* Visual styling */
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5) !important;
        border: 2px solid #ffffff !important;
        overflow: hidden !important;

        /* Animation */
        animation: modalSlideIn 0.4s ease-out !important;

        /* Ensure solid background */
        backdrop-filter: none !important;
    }

    /* Success Dialog Header - ENHANCED */
    .success-dialog-header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
        padding: 30px 25px 20px 25px !important;
        text-align: center !important;
        color: #ffffff !important;
        position: relative !important;
        overflow: hidden !important;
        border-bottom: 3px solid #ffffff !important;
    }

    /* Animated background effect */
    .success-dialog-header::before {
        content: '' !important;
        position: absolute !important;
        top: -50% !important;
        left: -50% !important;
        width: 200% !important;
        height: 200% !important;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%) !important;
        animation: shimmer 3s ease-in-out infinite !important;
    }

    /* Success Icon - ENHANCED */
    .success-icon {
        width: 80px !important;
        height: 80px !important;
        background: rgba(255, 255, 255, 0.25) !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 auto 15px auto !important;
        font-size: 40px !important;
        border: 3px solid rgba(255, 255, 255, 0.5) !important;
        animation: successIconBounce 0.8s ease-out !important;
        position: relative !important;
        z-index: 2 !important;
        color: #ffffff !important;
    }

    /* Success Dialog Title */
    .success-dialog-title {
        font-size: 1.6rem !important;
        font-weight: 700 !important;
        margin: 0 0 8px 0 !important;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
        position: relative !important;
        z-index: 2 !important;
        color: #ffffff !important;
    }

    /* Success Dialog Subtitle */
    .success-dialog-subtitle {
        font-size: 1rem !important;
        margin: 0 !important;
        opacity: 0.95 !important;
        font-weight: 400 !important;
        position: relative !important;
        z-index: 2 !important;
        color: #ffffff !important;
    }

    /* Success Dialog Body - ENHANCED */
    .success-dialog-body {
        padding: 30px 25px !important;
        text-align: center !important;
        background: #ffffff !important;
        color: #333333 !important;
    }

    /* Success Dialog Message */
    .success-dialog-message {
        font-size: 1.1rem !important;
        color: #555555 !important;
        margin-bottom: 25px !important;
        line-height: 1.6 !important;
        font-weight: 400 !important;
    }

    /* Success Dialog Actions */
    .success-dialog-actions {
        display: flex !important;
        gap: 15px !important;
        justify-content: center !important;
        flex-wrap: wrap !important;
    }

    /* Success Buttons - ENHANCED */
    .success-btn {
        padding: 12px 25px !important;
        border: none !important;
        border-radius: 25px !important;
        font-size: 1rem !important;
        font-weight: 600 !important;
        cursor: pointer !important;
        transition: all 0.3s ease !important;
        text-decoration: none !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        min-width: 130px !important;
        justify-content: center !important;
        position: relative !important;
        overflow: hidden !important;
        outline: none !important;
    }

    /* Primary Button */
    .success-btn-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4) !important;
    }

    .success-btn-primary:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 25px rgba(0, 123, 255, 0.5) !important;
        color: #ffffff !important;
        text-decoration: none !important;
    }

    /* Secondary Button */
    .success-btn-secondary {
        background: #f8f9fa !important;
        color: #6c757d !important;
        border: 2px solid #dee2e6 !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
    }

    .success-btn-secondary:hover {
        background: #e9ecef !important;
        border-color: #adb5bd !important;
        transform: translateY(-1px) !important;
        color: #495057 !important;
        text-decoration: none !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
    }

    /* Animations */
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-30px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes successIconBounce {

        0%,
        20%,
        53%,
        80%,
        100% {
            transform: translateY(0);
        }

        40%,
        43% {
            transform: translateY(-20px);
        }

        70% {
            transform: translateY(-10px);
        }

        90% {
            transform: translateY(-4px);
        }
    }

    @keyframes shimmer {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* CRITICAL: Override any conflicting styles */
    body .success-dialog-overlay {
        position: fixed !important;
        background: rgba(0, 0, 0, 0.7) !important;
        z-index: 999999 !important;
    }

    body .success-dialog-overlay .success-dialog {
        background: #ffffff !important;
        position: relative !important;
        top: auto !important;
        left: auto !important;
        transform: none !important;
        margin: 0 auto !important;
    }

    body .success-dialog-overlay .success-dialog-header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
        color: #ffffff !important;
    }

    body .success-dialog-overlay .success-dialog-body {
        background: #ffffff !important;
        color: #333333 !important;
    }

    /* Mobile Responsive */
    @media (max-width: 600px) {
        .success-dialog {
            min-width: 300px !important;
            width: 95vw !important;
            margin: 10px !important;
        }

        .success-dialog-header {
            padding: 20px 15px 15px 15px !important;
        }

        .success-icon {
            width: 60px !important;
            height: 60px !important;
            font-size: 30px !important;
            margin-bottom: 10px !important;
        }

        .success-dialog-title {
            font-size: 1.3rem !important;
        }

        .success-dialog-subtitle {
            font-size: 0.9rem !important;
        }

        .success-dialog-body {
            padding: 20px 15px !important;
        }

        .success-dialog-message {
            font-size: 1rem !important;
            margin-bottom: 20px !important;
        }

        .success-dialog-actions {
            flex-direction: column !important;
            gap: 10px !important;
        }

        .success-btn {
            width: 100% !important;
            min-width: auto !important;
            padding: 10px 15px !important;
        }
    }

    /* Force override any theme styles */
    .success-dialog-overlay * {
        box-sizing: border-box !important;
    }

    /* Ensure proper text colors */
    .success-dialog-header * {
        color: #ffffff !important;
    }

    .success-dialog-body * {
        color: inherit !important;
    }

    .success-btn-primary * {
        color: #ffffff !important;
    }

    .success-btn-secondary * {
        color: #6c757d !important;
    }

    .success-btn-secondary:hover * {
        color: #495057 !important;
    }
</style>
@endpush

@section('content')
<section class="content-header">
    <h1>@lang('exchange::lang.exchange')</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('exchange::lang.create_exchange')</h3>
                </div>

                <div class="box-body">
                    <!-- Debug Panel (Remove in Production) -->
                    {{-- <div id="debug-panel"
                        style="background: #f0f0f0; padding: 10px; margin-bottom: 20px; border-radius: 5px; display: none;">
                        <strong>Debug Panel:</strong>
                        <button type="button" class="btn btn-xs btn-default" onclick="forceStep(1)">Force Step
                            1</button>
                        <button type="button" class="btn btn-xs btn-default" onclick="forceStep(2)">Force Step
                            2</button>
                        <button type="button" class="btn btn-xs btn-default" onclick="forceStep(3)">Force Step
                            3</button>
                        <button type="button" class="btn btn-xs btn-default" onclick="forceStep(4)">Force Step
                            4</button>
                        <button type="button" class="btn btn-xs btn-info" onclick="showDebugInfo()">Show Debug
                            Info</button>
                        <button type="button" class="btn btn-xs btn-warning" onclick="$('#debug-panel').hide()">Hide
                            Panel</button>
                        <span id="debug-info" style="margin-left: 10px; font-size: 12px;"></span>
                    </div> --}}

                    <!-- Step Progress Indicator - ENHANCED WITH INLINE STYLES -->
                    <div class="step-progress"
                        style="display: flex; justify-content: center; align-items: center; margin-bottom: 30px; padding: 20px; background: white; border-radius: 8px; border: 1px solid #ddd; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: relative;">
                        <div class="step-progress-line" id="progress-line"
                            style="position: absolute; top: 50%; left: 20%; height: 2px; background-color: #337ab7; z-index: 2; transition: width 0.5s ease; width: 0%;">
                        </div>
                        <div
                            style="content: ''; position: absolute; top: 50%; left: 20%; right: 20%; height: 2px; background-color: #ddd; z-index: 1;">
                        </div>

                        <div class="step-item active" data-step="1"
                            style="display: flex; flex-direction: column; align-items: center; margin: 0 15px; color: #337ab7; font-size: 14px; position: relative; z-index: 3; background: white; padding: 0 10px; min-width: 80px; text-align: center; font-weight: 600;">
                            <div class="step-number"
                                style="width: 36px; height: 36px; border-radius: 50%; background: #337ab7; color: white; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; font-weight: bold; font-size: 14px; border: 2px solid #337ab7; transform: scale(1.1); box-shadow: 0 2px 8px rgba(51, 122, 183, 0.3);">
                                1</div>
                            <div class="step-label"
                                style="font-size: 12px; font-weight: 600; line-height: 1.2; margin-top: 4px; color: #337ab7;">
                                @lang('exchange::lang.search_invoice')</div>
                        </div>

                        <div class="step-item" data-step="2"
                            style="display: flex; flex-direction: column; align-items: center; margin: 0 15px; color: #999; font-size: 14px; position: relative; z-index: 3; background: white; padding: 0 10px; min-width: 80px; text-align: center;">
                            <div class="step-number"
                                style="width: 36px; height: 36px; border-radius: 50%; background: #ddd; color: white; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; font-weight: bold; font-size: 14px; border: 2px solid #ddd;">
                                2</div>
                            <div class="step-label"
                                style="font-size: 12px; font-weight: 500; line-height: 1.2; margin-top: 4px;">
                                @lang('exchange::lang.select_items')</div>
                        </div>

                        <div class="step-item" data-step="3"
                            style="display: flex; flex-direction: column; align-items: center; margin: 0 15px; color: #999; font-size: 14px; position: relative; z-index: 3; background: white; padding: 0 10px; min-width: 80px; text-align: center;">
                            <div class="step-number"
                                style="width: 36px; height: 36px; border-radius: 50%; background: #ddd; color: white; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; font-weight: bold; font-size: 14px; border: 2px solid #ddd;">
                                3</div>
                            <div class="step-label"
                                style="font-size: 12px; font-weight: 500; line-height: 1.2; margin-top: 4px;">
                                @lang('exchange::lang.new_items')</div>
                        </div>

                        <div class="step-item" data-step="4"
                            style="display: flex; flex-direction: column; align-items: center; margin: 0 15px; color: #999; font-size: 14px; position: relative; z-index: 3; background: white; padding: 0 10px; min-width: 80px; text-align: center;">
                            <div class="step-number"
                                style="width: 36px; height: 36px; border-radius: 50%; background: #ddd; color: white; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; font-weight: bold; font-size: 14px; border: 2px solid #ddd;">
                                4</div>
                            <div class="step-label"
                                style="font-size: 12px; font-weight: 500; line-height: 1.2; margin-top: 4px;">
                                @lang('exchange::lang.payment')</div>
                        </div>
                    </div>

                    <!-- Step 1: Search Original Transaction -->
                    <div id="step-1" class="exchange-step active">
                        <h4>@lang('exchange::lang.step_1_search_invoice')</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('sale.invoice_no'):</label>
                                    <div class="input-group">
                                        <input type="text" id="search_invoice_no" class="form-control"
                                            placeholder="@lang('exchange::lang.enter_invoice_number')">
                                        <span class="input-group-btn">
                                            <button type="button" class="btn btn-primary" id="search_transaction_btn">
                                                <i class="fa fa-search"></i> @lang('exchange::lang.search_invoice')
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="transaction_details" style="display: none;">
                            <div class="alert-modern alert-success">
                                <i class="fa fa-check-circle"></i>
                                @lang('exchange::lang.invoice_found_successfully')
                            </div>
                            <div id="transaction_info"></div>
                            <div class="text-center" style="margin-top: 20px;">
                                <button type="button" class="btn btn-primary btn-modern" onclick="nextStep(2)">
                                    <i class="fa fa-arrow-right"></i> @lang('exchange::lang.proceed_to_select_items')
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Select Items for Exchange -->
                    <div id="step-2" class="exchange-step">
                        <h4>@lang('exchange::lang.step_2_select_items')</h4>
                        <div id="exchangeable_items_table"></div>
                        <div class="text-center" style="margin-top: 20px;">
                            <button type="button" class="btn btn-default btn-modern" onclick="prevStep(1)">
                                <i class="fa fa-arrow-left"></i> @lang('exchange::lang.previous')
                            </button>
                            <button type="button" class="btn btn-primary btn-modern" id="proceed_to_step3"
                                onclick="nextStep(3)">
                                <i class="fa fa-arrow-right"></i> @lang('exchange::lang.proceed_to_new_items')
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Select New Items -->
                    <div id="step-3" class="exchange-step">
                        <h4>@lang('exchange::lang.step_3_select_new_items')</h4>

                        <!-- Product Search Section - ENHANCED -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>@lang('exchange::lang.search_product'):</label>
                                    <div class="input-group">
                                        <div class="input-group-btn">
                                            <button type="button" class="btn btn-default bg-white btn-flat"
                                                data-toggle="modal" data-target="#configure_search_modal"
                                                title="{{__('exchange::lang.configure_product_search')}}">
                                                <i class="fas fa-search-plus"></i>
                                            </button>
                                        </div>
                                        {!! Form::text('search_product_exchange', null, ['class' => 'form-control
                                        mousetrap', 'id' => 'search_product_exchange', 'placeholder' =>
                                        __('exchange::lang.search_product_placeholder')]) !!}
                                        <span class="input-group-btn">
                                            {{-- Camera Barcode Scanner Button --}}
                                            {{--
                                            <x-camera-barcode-scanner search-input-id="search_product_exchange" /> --}}

                                            <button type="button" class="btn btn-primary"
                                                id="manual_product_search_btn">
                                                <i class="fa fa-search"></i>
                                            </button>
                                            <button type="button"
                                                class="btn btn-default bg-white btn-flat pos_add_quick_product"
                                                data-href="{{action([\App\Http\Controllers\ProductController::class, 'quickAdd'])}}"
                                                data-container=".quick_add_product_modal">
                                                <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                            </button>
                                        </span>
                                    </div>
                                    <div id="product_search_results" style="display: none; margin-top: 10px;">
                                        <!-- Manual search results will appear here -->
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('business.business_location'):</label>
                                    <select class="form-control select2" id="location_id_exchange" name="location_id"
                                        required>
                                        @foreach($business_locations as $id => $name)
                                        <option value="{{ $id }}" @if($default_location && $default_location->id == $id)
                                            selected @endif>
                                            {{ $name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- New Items Table -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-condensed table-bordered table-striped"
                                        id="exchange_new_items_table">
                                        <thead>
                                            <tr>
                                                <th class="text-center">#</th>
                                                <th class="text-center">@lang('sale.product')</th>
                                                <th class="text-center">@lang('sale.qty')</th>
                                                <th
                                                    class="@if(!auth()->user()->can('edit_product_price_from_sale_screen')) hide @endif">
                                                    @lang('sale.unit_price')
                                                </th>
                                                <th
                                                    class="@if(!auth()->user()->can('edit_product_discount_from_sale_screen')) hide @endif">
                                                    @lang('receipt.discount')
                                                </th>
                                                <th class="text-center">@lang('sale.tax')</th>
                                                <th class="text-center">@lang('sale.price_inc_tax')</th>
                                                <th class="text-center">@lang('sale.subtotal')</th>
                                                <th class="text-center"><i class="fas fa-times" aria-hidden="true"></i>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-condensed table-bordered table-striped">
                                        <tr>
                                            <td>
                                                <div class="pull-right">
                                                    <b>@lang('sale.item'):</b>
                                                    <span class="total_new_quantity">0</span>
                                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                                    <b>@lang('lang_v1.total'): </b>
                                                    <span class="new_items_total">0</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Summary Cards - ENHANCED WITH INLINE STYLES -->
                        <div style="display: flex; gap: 20px; margin: 30px 0; flex-wrap: wrap;">
                            <div
                                style="flex: 1; min-width: 200px; padding: 25px 20px; background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); border: 1px solid #e3e6f0; border-radius: 12px; text-align: center; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); transition: all 0.3s ease; position: relative; overflow: hidden; border-top: 4px solid #d9534f;">
                                <i class="fa fa-undo"
                                    style="position: absolute; top: 15px; right: 15px; font-size: 1.5rem; opacity: 0.2; color: #d9534f;"></i>
                                <div class="total_return_quantity"
                                    style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px; text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); line-height: 1; color: #d9534f;">
                                    0</div>
                                <div
                                    style="color: #6c757d; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                    @lang('exchange::lang.items_to_return')</div>
                            </div>
                            <div
                                style="flex: 1; min-width: 200px; padding: 25px 20px; background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); border: 1px solid #e3e6f0; border-radius: 12px; text-align: center; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); transition: all 0.3s ease; position: relative; overflow: hidden; border-top: 4px solid #5cb85c;">
                                <i class="fa fa-plus-circle"
                                    style="position: absolute; top: 15px; right: 15px; font-size: 1.5rem; opacity: 0.2; color: #5cb85c;"></i>
                                <div class="total_new_quantity"
                                    style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px; text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); line-height: 1; color: #5cb85c;">
                                    0</div>
                                <div
                                    style="color: #6c757d; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                    @lang('exchange::lang.new_items')</div>
                            </div>
                            <div
                                style="flex: 1; min-width: 200px; padding: 25px 20px; background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); border: 1px solid #e3e6f0; border-radius: 12px; text-align: center; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); transition: all 0.3s ease; position: relative; overflow: hidden; border-top: 4px solid #337ab7;">
                                <i class="fa fa-calculator"
                                    style="position: absolute; top: 15px; right: 15px; font-size: 1.5rem; opacity: 0.2; color: #337ab7;"></i>
                                <div class="exchange_difference"
                                    style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px; text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); line-height: 1; color: #337ab7;">
                                    0.00 </div>
                                <div
                                    style="color: #6c757d; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                    @lang('exchange::lang.difference')</div>
                            </div>
                        </div>

                        <!-- Exchange Summary -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="panel panel-info">
                                    <div class="panel-heading">@lang('exchange::lang.exchange_summary')</div>
                                    <div class="panel-body">
                                        <table class="table table-condensed">
                                            <tr>
                                                <th>@lang('exchange::lang.return_item')</th>
                                                <th>@lang('exchange::lang.return_qty')</th>
                                                <th>@lang('exchange::lang.return_amount')</th>
                                                <th>@lang('exchange::lang.new_item')</th>
                                                <th>@lang('exchange::lang.new_qty')</th>
                                                <th>@lang('exchange::lang.new_amount')</th>
                                                <th>@lang('exchange::lang.difference')</th>
                                                <th>@lang('messages.action')</th>
                                            </tr>
                                            <tbody id="exchange_summary_tbody">
                                                <!-- Exchange lines will be populated here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center" style="margin-top: 20px;">
                            <button type="button" class="btn btn-default btn-modern" onclick="prevStep(2)">
                                <i class="fa fa-arrow-left"></i> @lang('exchange::lang.previous')
                            </button>
                            <button type="button" class="btn btn-primary btn-modern" id="proceed_to_step4"
                                onclick="nextStep(4)">
                                <i class="fa fa-arrow-right"></i> @lang('exchange::lang.proceed_to_payment')
                            </button>
                        </div>
                    </div>

                    <!-- Step 4: Payment & Finalize -->
                    <div id="step-4" class="exchange-step">
                        <h4>@lang('exchange::lang.step_4_payment_finalize')</h4>

                        <!-- Summary Cards - ENHANCED WITH INLINE STYLES -->
                        <div style="display: flex; gap: 20px; margin: 30px 0; flex-wrap: wrap;">
                            <div
                                style="flex: 1; min-width: 200px; padding: 25px 20px; background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); border: 1px solid #e3e6f0; border-radius: 12px; text-align: center; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); transition: all 0.3s ease; position: relative; overflow: hidden; border-top: 4px solid #d9534f;">
                                <i class="fa fa-arrow-down"
                                    style="position: absolute; top: 15px; right: 15px; font-size: 1.5rem; opacity: 0.2; color: #d9534f;"></i>
                                <div id="final_total_return_amount"
                                    style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px; text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); line-height: 1; color: #d9534f;">
                                    0.00 </div>
                                <div
                                    style="color: #6c757d; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                    @lang('exchange::lang.total_return_amount')</div>
                            </div>
                            <div
                                style="flex: 1; min-width: 200px; padding: 25px 20px; background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); border: 1px solid #e3e6f0; border-radius: 12px; text-align: center; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); transition: all 0.3s ease; position: relative; overflow: hidden; border-top: 4px solid #5cb85c;">
                                <i class="fa fa-arrow-up"
                                    style="position: absolute; top: 15px; right: 15px; font-size: 1.5rem; opacity: 0.2; color: #5cb85c;"></i>
                                <div id="final_total_new_amount"
                                    style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px; text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); line-height: 1; color: #5cb85c;">
                                    0.00 </div>
                                <div
                                    style="color: #6c757d; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                    @lang('exchange::lang.total_new_amount')</div>
                            </div>
                            <div
                                style="flex: 1; min-width: 200px; padding: 25px 20px; background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); border: 1px solid #e3e6f0; border-radius: 12px; text-align: center; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); transition: all 0.3s ease; position: relative; overflow: hidden; border-top: 4px solid #337ab7;">
                                <i class="fa fa-balance-scale"
                                    style="position: absolute; top: 15px; right: 15px; font-size: 1.5rem; opacity: 0.2; color: #337ab7;"></i>
                                <div id="final_net_exchange_amount"
                                    style="font-size: 2.5rem; font-weight: 700; margin-bottom: 8px; text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); line-height: 1; color: #337ab7;">
                                    0.00 </div>
                                <div
                                    style="color: #6c757d; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                    @lang('exchange::lang.net_amount')</div>
                            </div>
                        </div>

                        <!-- Payment Method Section -->
                        {{-- CORRECTED Payment Form Section for Step 4 --}}
                        {{-- Replace your existing payment form section with this --}}

                        <div class="row" id="payment_method_section">
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading">@lang('exchange::lang.payment_method')</div>
                                    <div class="panel-body">
                                        <div class="payment_row" id="exchange_payment_row">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        {!! Form::label('exchange_payment_method',
                                                        __('exchange::lang.payment_method') . ':*') !!}
                                                        <div class="input-group">
                                                            <span class="input-group-addon">
                                                                <i class="fas fa-money-bill-alt"></i>
                                                            </span>
                                                            {!! Form::select('exchange_payment_method',
                                                            [
                                                            'cash' => __('exchange::lang.cash'),
                                                            'card' => __('exchange::lang.card'),
                                                            'cheque' => __('exchange::lang.cheque'),
                                                            'bank_transfer' => __('exchange::lang.bank_transfer')
                                                            ],
                                                            'cash',
                                                            ['class' => 'form-control payment_types_dropdown', 'id' =>
                                                            'exchange_payment_method', 'style' => 'width:100%;']) !!}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        {!! Form::label('exchange_payment_amount', __('sale.amount') .
                                                        ':*') !!}
                                                        <div class="input-group">
                                                            <span class="input-group-addon">
                                                                <i class="fas fa-money-bill-alt"></i>
                                                            </span>
                                                            {!! Form::text('exchange_payment_amount', 0, ['class' =>
                                                            'form-control payment-amount input_number', 'id' =>
                                                            'exchange_payment_amount', 'placeholder' =>
                                                            __('sale.amount'), 'readonly']) !!}
                                                        </div>
                                                    </div>
                                                </div>
                                                @if(!empty($accounts))
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        {!! Form::label('exchange_payment_account',
                                                        __('exchange::lang.payment_account') . ':') !!}
                                                        <div class="input-group">
                                                            <span class="input-group-addon">
                                                                <i class="fas fa-money-bill-alt"></i>
                                                            </span>
                                                            {!! Form::select('exchange_payment_account', $accounts,
                                                            null, ['class' => 'form-control select2', 'id' =>
                                                            'exchange_payment_account', 'style' => 'width:100%;']) !!}
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>

                                            <!-- Payment Type Details -->
                                            <div class="row" id="exchange_payment_details">
                                                <!-- Card Details -->
                                                <div class="col-md-12 payment_details_div" id="card_details_div"
                                                    style="display: none;">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                {!! Form::label('card_number',
                                                                __('exchange::lang.card_number') . ':') !!}
                                                                {!! Form::text('card_number', null, ['class' =>
                                                                'form-control', 'placeholder' =>
                                                                __('exchange::lang.card_number')]) !!}
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                {!! Form::label('card_holder_name',
                                                                __('exchange::lang.card_holder_name') . ':') !!}
                                                                {!! Form::text('card_holder_name', null, ['class' =>
                                                                'form-control', 'placeholder' =>
                                                                __('exchange::lang.card_holder_name')]) !!}
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                {!! Form::label('card_type',
                                                                __('exchange::lang.card_type') . ':') !!}
                                                                {!! Form::select('card_type', ['credit' => 'Credit
                                                                Card', 'debit' => 'Debit Card'], 'credit', ['class' =>
                                                                'form-control']) !!}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Cheque Details -->
                                                <div class="col-md-12 payment_details_div" id="cheque_details_div"
                                                    style="display: none;">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                {!! Form::label('cheque_number',
                                                                __('exchange::lang.cheque_number') . ':') !!}
                                                                {!! Form::text('cheque_number', null, ['class' =>
                                                                'form-control', 'placeholder' =>
                                                                __('exchange::lang.cheque_number')]) !!}
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                {!! Form::label('bank_name',
                                                                __('exchange::lang.bank_name') . ':') !!}
                                                                {!! Form::text('bank_name', null, ['class' =>
                                                                'form-control', 'placeholder' =>
                                                                __('exchange::lang.bank_name')]) !!}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Bank Transfer Details -->
                                                <div class="col-md-12 payment_details_div"
                                                    id="bank_transfer_details_div" style="display: none;">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                {!! Form::label('transaction_no',
                                                                __('exchange::lang.transaction_number') . ':') !!}
                                                                {!! Form::text('transaction_no', null, ['class' =>
                                                                'form-control', 'placeholder' =>
                                                                __('exchange::lang.transaction_number')]) !!}
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                {!! Form::label('bank_name',
                                                                __('exchange::lang.bank_name') . ':') !!}
                                                                {!! Form::text('bank_name', null, ['class' =>
                                                                'form-control', 'placeholder' =>
                                                                __('exchange::lang.bank_name')]) !!}
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

                        <!-- Notes Section -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang('exchange::lang.notes'):</label>
                                    <textarea class="form-control" id="exchange_notes" name="notes" rows="3"
                                        placeholder="@lang('exchange::lang.exchange_notes_placeholder')"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="text-center" style="margin-top: 20px;">
                            <button type="button" class="btn btn-default btn-modern" onclick="prevStep(3)">
                                <i class="fa fa-arrow-left"></i> @lang('exchange::lang.previous')
                            </button>
                            <button type="button" class="btn btn-success btn-modern" id="finalize_exchange_btn">
                                <i class="fa fa-check"></i> @lang('exchange::lang.finalize_exchange')
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loading-overlay">
    <div class="loading-content">
        <div class="spinner"></div>
        <div>@lang('exchange::lang.processing_exchange')</div>
    </div>
</div>

<!-- Custom Success Dialog - WITHOUT TIMER -->
<div class="success-dialog-overlay" id="success-dialog-overlay" style="display: none;">
    <div class="success-dialog">
        <div class="success-dialog-header">
            <div class="success-icon">
                <i class="fa fa-check"></i>
            </div>
            <h3 class="success-dialog-title">Exchange Completed!</h3>
            <p class="success-dialog-subtitle">Transaction processed successfully</p>
        </div>
        <div class="success-dialog-body">
            <p class="success-dialog-message">
                Your exchange has been completed successfully. Would you like to print the invoice for your records?
            </p>
            <div class="success-dialog-actions">
                <button type="button" class="success-btn success-btn-primary" id="print-invoice-btn">
                    <i class="fa fa-print"></i>
                    Print Invoice
                </button>
                <button type="button" class="success-btn success-btn-secondary" id="skip-print-btn">
                    <i class="fa fa-times"></i>
                    Skip
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<!-- Modals -->
<div class="modal fade" id="product_selection_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">@lang('exchange::lang.select_product')</h4>
            </div>
            <div class="modal-body">
                <div id="product_list_for_exchange"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

@include('sale_pos.partials.configure_search_modal')
@endsection

@section('javascript')
<script src="{{ asset('js/pos.js') }}"></script>

<script>
    $(document).ready(function() {
    // ============================================================================
    // GLOBAL VARIABLES AND CONFIGURATION
    // ============================================================================
    
    var current_transaction = null;
    var exchange_lines = [];
    var new_item_row_count = 0;
    var business_id = "{{ session('user.business_id') }}";
    var currentStep = 1;
    
    // Route Configuration
    window.exchangeRoutes = {
        store: '/exchange/store',
        searchTransaction: '/exchange/search-transaction',
        index: '/exchange',
        cancel: function(id) { return '/exchange/' + id + '/cancel'; },
        destroy: function(id) { return '/exchange/' + id; },
        printReceipt: function(id) { return '/exchange/' + id + '/print-receipt'; },
        printOnly: function(id) { return '/exchange/' + id + '/print-only'; }
    };

    // ============================================================================
    // INITIALIZATION
    // ============================================================================
    
    function initializePage() {
        updateStep(1);
        $('#success-dialog-overlay').hide();
        initializeProductSearch();
        console.log('Exchange system initialized successfully');
    }

    // ============================================================================
    // UTILITY FUNCTIONS
    // ============================================================================
    
    function formatCurrency(amount, showCurrency) {
        if (typeof showCurrency === 'undefined') showCurrency = true;
        
        if (typeof amount === 'undefined' || amount === null) {
            return __currency_trans_from_en(0, showCurrency);
        }
        
        return __currency_trans_from_en(amount, showCurrency);
    }

    function formatNumber(number, precision) {
        if (typeof precision === 'undefined') precision = 2;
        
        if (typeof number === 'undefined' || number === null) {
            return '0';
        }
        
        return parseFloat(number).toFixed(precision);
    }

    function showToast(message, type, duration) {
        if (typeof duration === 'undefined') duration = 5000;
        
        $('.toast').remove();
        
        var iconClass = 'fa-info-circle';
        if (type === 'success') iconClass = 'fa-check-circle';
        else if (type === 'error') iconClass = 'fa-exclamation-circle';
        else if (type === 'warning') iconClass = 'fa-exclamation-triangle';
        
        var toast = $('<div class="toast toast-' + type + '" style="position: fixed; top: 20px; right: 20px; z-index: 10000; background: white; border-radius: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); margin-bottom: 10px; padding: 15px 20px; min-width: 300px; opacity: 0; transform: translateX(100%); transition: all 0.3s ease; border-left: 4px solid;">' +
            '<i class="fa ' + iconClass + '" style="margin-right: 10px;"></i>' + message + 
            '<button type="button" style="background: none; border: none; float: right; font-size: 18px; line-height: 1; margin-left: 10px; cursor: pointer;" onclick="$(this).parent().remove();">&times;</button>' +
            '</div>');

        if (type === 'success') toast.css('border-left-color', '#5cb85c');
        else if (type === 'error') toast.css('border-left-color', '#d9534f');
        else if (type === 'warning') toast.css('border-left-color', '#f0ad4e');
        else toast.css('border-left-color', '#5bc0de');

        $('body').append(toast);
        
        setTimeout(function() { 
            toast.css({ 'opacity': '1', 'transform': 'translateX(0)' });
        }, 100);
        
        setTimeout(function() {
            toast.css({ 'opacity': '0', 'transform': 'translateX(100%)' });
            setTimeout(function() { toast.remove(); }, 300);
        }, duration);
    }

    // ============================================================================
    // STEP NAVIGATION FUNCTIONS
    // ============================================================================

    window.nextStep = function(step) {
        if (step > 4) return;
        if (!validateStep(currentStep)) return;
        updateStep(step);
    };
    
    window.prevStep = function(step) {
        if (step < 1) return;
        updateStep(step);
    };
    
    function updateStep(step) {
        console.log('Updating to step:', step);
        
        $('.exchange-step').removeClass('active').hide();
        $('#step-' + step).addClass('active').show();
        updateStepIndicators(step);
        currentStep = step;
        
        if (step === 4) {
            calculateFinalAmounts();
        }
    }

    function updateStepIndicators(step) {
        $('.step-item').each(function(index) {
            var stepNum = index + 1;
            var $item = $(this);
            var $number = $item.find('.step-number');
            var $label = $item.find('.step-label');
            
            $item.removeClass('active completed');
            
            if (stepNum < step) {
                // Completed step - Green
                $item.addClass('completed');
                $item.attr('style', 'display: flex; flex-direction: column; align-items: center; margin: 0 15px; color: #5cb85c; font-size: 14px; position: relative; z-index: 3; background: white; padding: 0 10px; min-width: 80px; text-align: center; font-weight: 600;');
                $number.attr('style', 'width: 36px; height: 36px; border-radius: 50%; background: #5cb85c; color: white; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; font-weight: bold; font-size: 14px; border: 2px solid #5cb85c;');
                $number.html('<i class="fa fa-check"></i>');
                $label.attr('style', 'font-size: 12px; font-weight: 600; line-height: 1.2; margin-top: 4px; color: #5cb85c;');
            } else if (stepNum === step) {
                // Current/Active step - Blue
                $item.addClass('active');
                $item.attr('style', 'display: flex; flex-direction: column; align-items: center; margin: 0 15px; color: #337ab7; font-size: 14px; position: relative; z-index: 3; background: white; padding: 0 10px; min-width: 80px; text-align: center; font-weight: 600;');
                $number.attr('style', 'width: 36px; height: 36px; border-radius: 50%; background: #337ab7; color: white; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; font-weight: bold; font-size: 14px; border: 2px solid #337ab7;');
                $number.html(stepNum);
                $label.attr('style', 'font-size: 12px; font-weight: 600; line-height: 1.2; margin-top: 4px; color: #337ab7;');
            } else {
                // Future step - Gray
                $item.attr('style', 'display: flex; flex-direction: column; align-items: center; margin: 0 15px; color: #999; font-size: 14px; position: relative; z-index: 3; background: white; padding: 0 10px; min-width: 80px; text-align: center; font-weight: 600;');
                $number.attr('style', 'width: 36px; height: 36px; border-radius: 50%; background: #f5f5f5; color: #999; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; font-weight: bold; font-size: 14px; border: 2px solid #ddd;');
                $number.html(stepNum);
                $label.attr('style', 'font-size: 12px; font-weight: 600; line-height: 1.2; margin-top: 4px; color: #999;');
            }
        });
    }

    function validateStep(step) {
        switch (step) {
            case 1:
                if (!current_transaction) {
                    showToast('Please search and select an invoice first', 'error');
                    return false;
                }
                break;
            case 2:
                if (exchange_lines.length === 0) {
                    showToast('Please select at least one item for exchange', 'error');
                    return false;
                }
                break;
        }
        return true;
    }

    // ============================================================================
    // STEP 1: SEARCH TRANSACTION
    // ============================================================================

    $('#search_transaction_btn').click(function() {
        var invoice_no = $('#search_invoice_no').val().trim();
        if (!invoice_no) {
            showToast('Enter invoice number', 'error');
            return;
        }
        searchTransaction(invoice_no);
    });
    
    $('#search_invoice_no').keypress(function(e) {
        if (e.which == 13) {
            $('#search_transaction_btn').click();
        }
    });
    
    function searchTransaction(invoice_no) {
        $.ajax({
            url: window.exchangeRoutes.searchTransaction,
            method: 'POST',
            data: {
                invoice_no: invoice_no,
                _token: $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val()
            },
            beforeSend: function() {
                $('#search_transaction_btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Searching...');
            },
            success: function(response) {
                if (response.success) {
                    current_transaction = response.transaction;
                    displayTransactionDetails(response.transaction);
                    displayExchangeableItems(response.exchangeable_lines);
                    showToast('Invoice found successfully!', 'success');
                } else {
                    showToast(response.message, 'error');
                }
            },
            error: function(xhr) {
                console.error('Search error:', xhr);
                showToast('Error searching transaction', 'error');
            },
            complete: function() {
                $('#search_transaction_btn').prop('disabled', false).html('<i class="fa fa-search"></i> Search Invoice');
            }
        });
    }
    
    function displayTransactionDetails(transaction) {
        var html = '<div class="well">';
        html += '<strong>Invoice No:</strong> ' + transaction.invoice_no + '<br>';
        html += '<strong>Customer:</strong> ' + (transaction.contact ? transaction.contact.name : 'Walk-In Customer') + '<br>';
        
        var transaction_date = 'N/A';
        if (transaction.transaction_date) {
            try {
                var date = new Date(transaction.transaction_date);
                if (!isNaN(date.getTime())) {
                    transaction_date = date.toLocaleDateString();
                } else {
                    transaction_date = transaction.transaction_date.split(' ')[0];
                }
            } catch (e) {
                transaction_date = transaction.transaction_date.toString().split(' ')[0];
            }
        }
        html += '<strong>Date:</strong> ' + transaction_date + '<br>';
        html += '<strong>Total:</strong> ' + formatCurrency(transaction.final_total) + '<br>';
        html += '</div>';
        
        $('#transaction_info').html(html);
        $('#transaction_details').show();
    }

    function displayExchangeableItems(lines) {
        var html = '<table class="table table-striped table-bordered">';
        html += '<thead><tr>';
        html += '<th>Product</th>';
        html += '<th>Available Qty</th>';
        html += '<th>Original Price</th>';
        html += '<th>Discount</th>';
        html += '<th>Final Price</th>';
        html += '<th>Subtotal</th>';
        html += '<th>Qty to Exchange</th>';
        html += '<th>Action</th>';
        html += '</tr></thead><tbody>';
        
        $.each(lines, function(index, line) {
            var available_qty = parseFloat(line.quantity) - parseFloat(line.quantity_returned || 0);
            var product_name = line.product.name;
            if (line.variations && line.variations.name != 'DUMMY') {
                product_name += ' - ' + line.variations.name;
            }

            // Calculate discount information
            var original_price = parseFloat(line.unit_price_before_discount || line.unit_price || 0);
            var final_price = parseFloat(line.unit_price_inc_tax || 0);
            var discount_amount = parseFloat(line.line_discount_amount || 0);
            var discount_type = line.line_discount_type || '';

            var discount_display = '-';
            if (discount_amount > 0) {
                if (discount_type === 'percentage') {
                    discount_display = discount_amount + '%';
                } else {
                    discount_display = formatCurrency(discount_amount);
                }
            }

            html += '<tr data-line-id="' + line.id + '">';
            html += '<td>' + product_name + '</td>';
            html += '<td>' + formatNumber(available_qty) + '</td>';
            html += '<td>' + formatCurrency(original_price) + '</td>';
            html += '<td><span class="text-success">' + discount_display + '</span></td>';
            html += '<td><strong>' + formatCurrency(final_price) + '</strong></td>';
            html += '<td>' + formatCurrency(final_price * available_qty) + '</td>';
            html += '<td><input type="number" class="form-control exchange-qty" min="0" max="' + available_qty + '" step="1" value="0" style="width: 100px;"></td>';
            html += '<td><button type="button" class="btn btn-primary btn-sm add-to-exchange" data-line-id="' + line.id + '">Add to Exchange</button></td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        $('#exchangeable_items_table').html(html);
    }

    // ============================================================================
    // STEP 2: SELECT ITEMS FOR EXCHANGE
    // ============================================================================

    $(document).on('click', '.add-to-exchange', function() {
        var $button = $(this);
        var $row = $button.closest('tr');
        var line_id = $button.data('line-id');
        var qty = parseFloat($row.find('.exchange-qty').val());
        var max_qty = parseFloat($row.find('.exchange-qty').attr('max'));

        if (!qty || qty <= 0) {
            showToast('Please enter a valid quantity greater than 0', 'error');
            $row.find('.exchange-qty').focus().select();
            return;
        }

        if (qty > max_qty) {
            showToast('Quantity cannot exceed available quantity (' + max_qty + ')', 'error');
            $row.find('.exchange-qty').focus().select();
            return;
        }

        $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Adding...');

        var line_data = null;
        if (current_transaction && current_transaction.sell_lines) {
            $.each(current_transaction.sell_lines, function(index, line) {
                if (line.id == line_id) {
                    line_data = line;
                    return false;
                }
            });
        }

        if (line_data) {
            var existing_index = -1;
            $.each(exchange_lines, function(index, item) {
                if (item.original_sell_line_id == line_id) {
                    existing_index = index;
                    return false;
                }
            });
            
            if (existing_index >= 0) {
                exchange_lines[existing_index].original_quantity = qty;
                showToast('Exchange quantity updated successfully', 'success');
            } else {
                exchange_lines.push({
                    original_sell_line_id: line_id,
                    original_quantity: qty,
                    original_unit_price: line_data.unit_price_inc_tax,
                    line_data: line_data,
                    new_product_id: null,
                    new_variation_id: null,
                    new_quantity: 0,
                    new_unit_price: 0
                });
                showToast('Item added to exchange successfully', 'success');
            }

            $row.addClass('success').css('background-color', '#dff0d8');
            updateExchangeSummary();
        } else {
            showToast('Error: Could not find product data', 'error');
        }

        setTimeout(function() {
            $button.prop('disabled', false).html('Add to Exchange');
        }, 1000);
    });

    $(document).on('change', '.exchange-qty', function() {
        var $input = $(this);
        var qty = parseFloat($input.val());
        var max_qty = parseFloat($input.attr('max'));
        var min_qty = parseFloat($input.attr('min')) || 0.01;

        if (qty < min_qty) {
            $input.val(min_qty);
            showToast('Minimum quantity is ' + min_qty, 'warning');
        } else if (qty > max_qty) {
            $input.val(max_qty);
            showToast('Maximum available quantity is ' + max_qty, 'warning');
        }
    });

    // ============================================================================
    // STEP 3: PRODUCT SEARCH AND NEW ITEMS
    // ============================================================================

    function initializeProductSearch() {
        if ($('#search_product_exchange').hasClass('ui-autocomplete-input')) {
            $('#search_product_exchange').autocomplete('destroy');
        }
        
        $('#search_product_exchange').autocomplete({
            delay: 1000,
            source: function(request, response) {
                $.ajax({
                    url: '/products/list',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        location_id: $('#location_id_exchange').val(),
                        term: request.term,
                        not_for_selling: 0
                    },
                    success: function(data) {
                        response(data);
                    },
                    error: function(xhr, status, error) {
                        console.error('Product search error:', error);
                        response([]);
                        showToast('Error searching products', 'error');
                    }
                });
            },
            minLength: 2,
            response: function(event, ui) {
                if (ui.content.length == 1) {
                    ui.item = ui.content[0];
                    if ((ui.item.enable_stock == 1 && ui.item.qty_available > 0) || ui.item.enable_stock == 0) {
                        $(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', ui);
                        $(this).autocomplete('close');
                    }
                } else if (ui.content.length == 0) {
                    showToast('No products found', 'warning');
                    $('input#search_product_exchange').select();
                }
            },
            select: function(event, ui) {
                $(this).val('');
                if (ui.item.enable_stock != 1 || ui.item.qty_available > 0) {
                    addNewItemToExchange(ui.item);
                } else {
                    showToast('Out of stock', 'error');
                }
                return false;
            },
            focus: function(event, ui) {
                return false;
            }
        }).autocomplete('instance')._renderItem = function(ul, item) {
            var string = '<div style="padding: 8px;">';
            string += '<strong>' + item.name;
            if (item.type == 'variable' && item.variation) {
                string += ' - ' + item.variation;
            }
            string += '</strong><br>';

            var selling_price = item.selling_price;
            if (item.variation_group_price) {
                selling_price = item.variation_group_price;
            }

            string += '<small>SKU: ' + item.sub_sku + '</small><br>';
            string += '<small>Price: ' + formatCurrency(selling_price) + '</small>';
            
            if (item.enable_stock == 1) {
                var qty_available = formatNumber(item.qty_available);
                string += '<br><small>Stock: ' + qty_available + ' ' + (item.unit || '') + '</small>';
            }
            string += '</div>';

            return $('<li>').append(string).appendTo(ul);
        };
    }

    $('#manual_product_search_btn').click(function() {
        var search_term = $('#search_product_exchange').val().trim();
        if (search_term.length < 2) {
            showToast('Enter at least 2 characters', 'warning');
            return;
        }
        manualProductSearch(search_term);
    });
    
    function manualProductSearch(term) {
        $.ajax({
            url: '/products/list',
            type: 'GET',
            dataType: 'json',
            data: {
                location_id: $('#location_id_exchange').val(),
                term: term,
                not_for_selling: 0
            },
            beforeSend: function() {
                $('#manual_product_search_btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
            },
            success: function(data) {
                displayManualSearchResults(data);
            },
            error: function(xhr, status, error) {
                console.error('Manual search error:', error);
                showToast('Error searching products: ' + error, 'error');
            },
            complete: function() {
                $('#manual_product_search_btn').prop('disabled', false).html('<i class="fa fa-search"></i>');
            }
        });
    }
    
    function displayManualSearchResults(products) {
        if (products.length === 0) {
            showToast('No products found', 'warning');
            $('#product_search_results').hide();
            return;
        }
        
        var html = '<div class="panel panel-default">';
        html += '<div class="panel-heading">Search Results (' + products.length + ' found)</div>';
        html += '<div class="panel-body" style="max-height: 300px; overflow-y: auto;">';
        
        $.each(products, function(index, item) {
            var selling_price = item.selling_price;
            if (item.variation_group_price) {
                selling_price = item.variation_group_price;
            }
            
            var product_name = item.name;
            if (item.type == 'variable' && item.variation) {
                product_name += ' - ' + item.variation;
            }
            
            var stock_info = '';
            if (item.enable_stock == 1) {
                var qty_available = item.qty_available || 0;
                stock_info = '<span class="label label-' + (qty_available > 0 ? 'success' : 'danger') + '">Stock: ' + qty_available + '</span>';
            } else {
                stock_info = '<span class="label label-info">No Stock Management</span>';
            }
            
            html += '<div class="row" style="border-bottom: 1px solid #eee; padding: 10px 0;">';
            html += '<div class="col-md-8">';
            html += '<strong>' + product_name + '</strong><br>';
            html += '<small>SKU: ' + item.sub_sku + ' | Price: ' + formatCurrency(selling_price) + '</small><br>';
            html += stock_info;
            html += '</div>';
            html += '<div class="col-md-4 text-right">';
            
            if (item.enable_stock != 1 || item.qty_available > 0) {
                html += '<button type="button" class="btn btn-primary btn-sm manual-add-product" ';
                html += 'data-product=\'' + JSON.stringify(item) + '\'>';
                html += '<i class="fa fa-plus"></i> Add</button>';
            } else {
                html += '<button type="button" class="btn btn-default btn-sm" disabled>';
                html += 'Out of Stock</button>';
            }
            
            html += '</div></div>';
        });
        
        html += '</div></div>';
        $('#product_search_results').html(html).show();
    }
    
    $(document).on('click', '.manual-add-product', function() {
        var productData = $(this).data('product');
        addNewItemToExchange(productData);
        $('#product_search_results').hide();
        $('#search_product_exchange').val('');
    });

    $('#search_product_exchange').on('keypress', function(e) {
        if (e.which == 13) {
            e.preventDefault();
            $('#manual_product_search_btn').click();
        }
    });
    
    $('#search_product_exchange').on('input', function() {
        if ($(this).val().length === 0) {
            $('#product_search_results').hide();
        }
    });

    function addNewItemToExchange(item) {
        new_item_row_count++;
        
        var selling_price = item.selling_price;
        if (item.variation_group_price) {
            selling_price = item.variation_group_price;
        }

        var product_name = item.name;
        if (item.type == 'variable') {
            product_name += ' - ' + item.variation;
        }

        var html = '<tr class="new_item_row" data-row-index="' + new_item_row_count + '">';
        html += '<td>' + new_item_row_count + '</td>';
        html += '<td>' + product_name + ' (' + item.sub_sku + ')</td>';
        html += '<td><input type="number" class="form-control new_item_quantity" min="1" step="1" value="1" data-variation-id="' + item.variation_id + '" data-product-id="' + item.product_id + '"></td>';
        html += '<td><input type="number" class="form-control new_item_unit_price" min="0" step="0.01" value="' + parseFloat(selling_price).toFixed(2) + '"></td>';
        html += '<td><input type="number" class="form-control new_item_discount" min="0" step="1" value="0"></td>';
        html += '<td><select class="form-control new_item_tax"><option value="0" data-rate="0">None</option></select></td>';
        html += '<td><input type="number" class="form-control new_item_price_inc_tax" readonly value="' + parseFloat(selling_price).toFixed(2) + '"></td>';
        html += '<td><span class="new_item_subtotal">' + formatCurrency(selling_price) + '</span></td>';
        html += '<td><button type="button" class="btn btn-danger btn-sm remove_new_item"><i class="fas fa-times"></i></button></td>';
        html += '</tr>';

        $('#exchange_new_items_table tbody').append(html);
        
        calculateNewItemsTotal();
        updateExchangeSummary();

        $('#exchange_new_items_table tbody tr:last .new_item_quantity').focus().select();
        $('input#search_product_exchange').focus().select();
    }

    function calculateNewItemsTotal() {
        var total_quantity = 0;
        var total_amount = 0;

        $('#exchange_new_items_table tbody tr').each(function() {
            var qty = parseFloat($(this).find('.new_item_quantity').val()) || 0;
            var price_inc_tax = parseFloat($(this).find('.new_item_price_inc_tax').val()) || 0;
            var subtotal = qty * price_inc_tax;

            total_quantity += qty;
            total_amount += subtotal;

            $(this).find('.new_item_subtotal').text(formatCurrency(subtotal));
        });

        $('.total_new_quantity').text(formatNumber(total_quantity));
        $('.new_items_total').text(formatCurrency(total_amount, false));
    }

    $(document).on('change', '.new_item_quantity, .new_item_unit_price, .new_item_discount, .new_item_tax', function() {
        var row = $(this).closest('tr');
        var qty = parseFloat(row.find('.new_item_quantity').val()) || 0;
        var unit_price = parseFloat(row.find('.new_item_unit_price').val()) || 0;
        var discount = parseFloat(row.find('.new_item_discount').val()) || 0;
        var tax_rate = parseFloat(row.find('.new_item_tax option:selected').data('rate')) || 0;

        var discounted_price = unit_price - discount;
        if (discounted_price < 0) discounted_price = 0;

        var price_inc_tax = discounted_price + (discounted_price * tax_rate / 100);
        row.find('.new_item_price_inc_tax').val(price_inc_tax.toFixed(2));

        calculateNewItemsTotal();
        updateExchangeSummary();
    });

    $(document).on('click', '.remove_new_item', function() {
        $(this).closest('tr').remove();
        calculateNewItemsTotal();
        updateExchangeSummary();
    });

    // ============================================================================
    // EXCHANGE SUMMARY FUNCTIONS
    // ============================================================================

    function getNewItemsArray() {
        var newItems = [];
        $('#exchange_new_items_table tbody tr').each(function() {
            var $row = $(this);
            var productText = $row.find('td:eq(1)').text();
            var qty = parseFloat($row.find('.new_item_quantity').val()) || 0;
            var price = parseFloat($row.find('.new_item_price_inc_tax').val()) || 0;
            var amount = qty * price;
            
            newItems.push({
                name: productText,
                quantity: qty,
                unit_price: price,
                amount: amount,
                product_id: $row.find('.new_item_quantity').data('product-id'),
                variation_id: $row.find('.new_item_quantity').data('variation-id')
            });
        });
        return newItems;
    }

    function updateExchangeSummary() {
        var newItems = getNewItemsArray();
        var total_return = 0;
        var total_new = 0;

        $.each(exchange_lines, function(index, item) {
            total_return += item.original_quantity * item.original_unit_price;
        });
        
        $.each(newItems, function(index, item) {
            total_new += item.amount;
        });

        var difference = total_new - total_return;

        $('.total_return_quantity').text(exchange_lines.length);
        $('.total_new_quantity').text(newItems.length);
        $('.exchange_difference').text(formatCurrency(difference));
        
        var maxRows = Math.max(exchange_lines.length, newItems.length);
        var html = '';
        
        for (var i = 0; i < maxRows; i++) {
            var returnItem = exchange_lines[i];
            var newItem = newItems[i];
            
            html += '<tr>';
            
            if (returnItem) {
                var return_amount = returnItem.original_quantity * returnItem.original_unit_price;
                var product_name = returnItem.line_data.product.name;
                if (returnItem.line_data.variations && returnItem.line_data.variations.name != 'DUMMY') {
                    product_name += ' - ' + returnItem.line_data.variations.name;
                }
                
                html += '<td>' + product_name + '</td>';
                html += '<td>' + formatNumber(returnItem.original_quantity) + '</td>';
                html += '<td>' + formatCurrency(return_amount) + '</td>';
            } else {
                html += '<td>-</td><td>-</td><td>-</td>';
            }
            
            if (newItem) {
                html += '<td>' + newItem.name + '</td>';
                html += '<td>' + formatNumber(newItem.quantity) + '</td>';
                html += '<td>' + formatCurrency(newItem.amount) + '</td>';
            } else {
                html += '<td>-</td><td>-</td><td>-</td>';
            }
            
            var rowDifference = 0;
            if (returnItem && newItem) {
                rowDifference = newItem.amount - (returnItem.original_quantity * returnItem.original_unit_price);
            } else if (newItem) {
                rowDifference = newItem.amount;
            } else if (returnItem) {
                rowDifference = -(returnItem.original_quantity * returnItem.original_unit_price);
            }
            
            html += '<td>' + formatCurrency(rowDifference) + '</td>';
            
            if (returnItem) {
                html += '<td><button type="button" class="btn btn-danger btn-sm remove_exchange_line" data-index="' + i + '">Remove</button></td>';
            } else {
                html += '<td>-</td>';
            }
            
            html += '</tr>';
        }

        $('#exchange_summary_tbody').html(html);
    }

    $(document).on('click', '.remove_exchange_line', function() {
        var index = $(this).data('index');
        exchange_lines.splice(index, 1);
        updateExchangeSummary();
    });

    // ============================================================================
    // STEP 4: PAYMENT AND FINALIZATION
    // ============================================================================

    function calculateFinalAmounts() {
        var total_return = 0;
        var total_new = 0;

        $.each(exchange_lines, function(index, item) {
            total_return += item.original_quantity * item.original_unit_price;
        });

        $('#exchange_new_items_table tbody tr').each(function() {
            var qty = parseFloat($(this).find('.new_item_quantity').val()) || 0;
            var price = parseFloat($(this).find('.new_item_price_inc_tax').val()) || 0;
            total_new += (qty * price);
        });

        var net_amount = total_new - total_return;

        $('#final_total_return_amount').text(formatCurrency(total_return, false));
        $('#final_total_new_amount').text(formatCurrency(total_new, false));
        $('#final_net_exchange_amount').text(formatCurrency(net_amount, false));

        if (net_amount > 0) {
            $('#exchange_payment_amount').val(formatCurrency(net_amount, false)).prop('readonly', false);
            $('#payment_method_section').show();
        } else if (net_amount < 0) {
            $('#exchange_payment_amount').val(formatCurrency(Math.abs(net_amount), false)).prop('readonly', true);
            $('#payment_method_section').show();
        } else {
            $('#exchange_payment_amount').val(0).prop('readonly', true);
            $('#payment_method_section').hide();
        }
    }

    $(document).on('change', '#exchange_payment_method', function() {
        var method = $(this).val();
        $('.payment_details_div').hide();
        
        if (method == 'card') {
            $('#card_details_div').show();
        } else if (method == 'cheque') {
            $('#cheque_details_div').show();
        } else if (method == 'bank_transfer') {
            $('#bank_transfer_details_div').show();
        }
    });

    function validatePaymentDetails() {
        var paymentMethod = $('#exchange_payment_method').val();
        var isValid = true;
        var errors = [];
        
        if (paymentMethod === 'card') {
            var cardNumber = $('#card_number').val().trim();
            var cardHolder = $('#card_holder_name').val().trim();
            
            if (!cardNumber) {
                errors.push('Card number is required for card payments');
                isValid = false;
            }
            
            if (!cardHolder) {
                errors.push('Card holder name is required for card payments');
                isValid = false;
            }
            
            if (cardNumber && cardNumber.replace(/\s/g, '').length < 13) {
                errors.push('Card number must be at least 13 digits');
                isValid = false;
            }
        }
        
        if (paymentMethod === 'cheque') {
            var chequeNumber = $('#cheque_number').val().trim();
            var bankName = $('#bank_name').val().trim();
            
            if (!chequeNumber) {
                errors.push('Cheque number is required for cheque payments');
                isValid = false;
            }
            
            if (!bankName) {
                errors.push('Bank name is required for cheque payments');
                isValid = false;
            }
        }
        
        if (paymentMethod === 'bank_transfer') {
            var transactionNo = $('#transaction_no').val().trim();
            var bankName = $('#bank_name').val().trim();
            
            if (!transactionNo) {
                errors.push('Transaction number is required for bank transfers');
                isValid = false;
            }
            
            if (!bankName) {
                errors.push('Bank name is required for bank transfers');
                isValid = false;
            }
        }
        
        if (!isValid) {
            showToast('Payment validation failed: ' + errors.join(', '), 'error');
        }
        
        return isValid;
    }

    $(document).on('input', '#card_number', function() {
        var value = $(this).val().replace(/\s/g, '').replace(/[^0-9]/gi, '');
        var formattedValue = value.match(/.{1,4}/g);
        if (formattedValue) {
            formattedValue = formattedValue.join(' ');
        } else {
            formattedValue = value;
        }
        $(this).val(formattedValue);
    });

    // ============================================================================
    // FINALIZE EXCHANGE
    // ============================================================================

    $(document).off('click', '#finalize_exchange_btn').on('click', '#finalize_exchange_btn', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        
        if (!exchange_lines || exchange_lines.length === 0) {
            showToast('Please select at least one item for exchange', 'error');
            return false;
        }

        var hasNewItems = $('#exchange_new_items_table tbody tr').length > 0;
        if (!hasNewItems) {
            showToast('Please add at least one new item for exchange', 'error');
            return false;
        }

        var invalidItems = false;
        $('#exchange_new_items_table tbody tr').each(function() {
            var qty = parseFloat($(this).find('.new_item_quantity').val()) || 0;
            var price = parseFloat($(this).find('.new_item_price_inc_tax').val()) || 0;
            var product_id = $(this).find('.new_item_quantity').data('product-id');
            var variation_id = $(this).find('.new_item_quantity').data('variation-id');

            if (qty <= 0 || price <= 0 || !product_id || !variation_id) {
                invalidItems = true;
                return false;
            }
        });

        if (invalidItems) {
            showToast('Please ensure all new items have valid quantity, price, and product information', 'error');
            return false;
        }

        var totalDifference = 0;
        var returnTotal = 0;
        var newTotal = 0;
        
        $.each(exchange_lines, function(index, item) {
            returnTotal += item.original_quantity * item.original_unit_price;
        });
        
        $('#exchange_new_items_table tbody tr').each(function() {
            var qty = parseFloat($(this).find('.new_item_quantity').val()) || 0;
            var price = parseFloat($(this).find('.new_item_price_inc_tax').val()) || 0;
            newTotal += (qty * price);
        });
        
        totalDifference = newTotal - returnTotal;

        if (Math.abs(totalDifference) > 1000) {
            if (!confirm('This is a high-value exchange (Difference: ' + Math.abs(totalDifference).toFixed(2) + '). Are you sure you want to proceed?')) {
                return false;
            }
        }

        var new_items = [];
        $('#exchange_new_items_table tbody tr').each(function() {
            var qty = parseFloat($(this).find('.new_item_quantity').val()) || 0;
            var price = parseFloat($(this).find('.new_item_price_inc_tax').val()) || 0;
            var product_id = $(this).find('.new_item_quantity').data('product-id');
            var variation_id = $(this).find('.new_item_quantity').data('variation-id');

            if (qty > 0 && price > 0) {
                new_items.push({
                    product_id: product_id,
                    variation_id: variation_id,
                    quantity: qty,
                    unit_price: price
                });
            }
        });

        $.each(exchange_lines, function(index, item) {
            if (index < new_items.length) {
                var new_item = new_items[index];
                item.new_product_id = new_item.product_id;
                item.new_variation_id = new_item.variation_id;
                item.new_quantity = new_item.quantity;
                item.new_unit_price = new_item.unit_price;
            }
        });

        var netAmount = parseFloat($('#final_net_exchange_amount').text().replace(/[^0-9.-]/g, '')) || 0;
        if (netAmount !== 0) {
            if (!validatePaymentDetails()) {
                return false;
            }
        }

        var data = {
            original_transaction_id: current_transaction.id,
            location_id: $('#location_id_exchange').val(),
            exchange_lines: exchange_lines,
            payment_method: $('#exchange_payment_method').val(),
            payment_amount: parseFloat($('#exchange_payment_amount').val()) || 0,
            card_number: $('#card_number').val(),
            card_holder_name: $('#card_holder_name').val(),
            card_type: $('#card_type').val(),
            cheque_number: $('#cheque_number').val(),
            bank_name: $('#bank_name').val(),
            transaction_no: $('#transaction_no').val(),
            payment_account: $('#exchange_payment_account').val(),
            notes: $('#exchange_notes').val(),
            _token: $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val()
        };
        
        $.ajax({
            url: window.exchangeRoutes.store,
            method: 'POST',
            data: data,
            beforeSend: function() {
                $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing Exchange...');
                showToast('Processing exchange...', 'info', 10000);
            },
            success: function(response) {
                if (response.success) {
                    window.exchangeId = response.exchange_id;
                    showToast(response.message || 'Exchange completed successfully!', 'success');
                    
                    if (response.invoice_url) {
                        showSuccessDialog(response.invoice_url);
                    } else {
                        showToast('Exchange completed successfully!', 'success');
                        setTimeout(function() {
                            window.location.href = window.exchangeRoutes.index;
                        }, 2000);
                    }
                } else {
                    showToast(response.message || 'Error processing exchange', 'error');
                }
            },
            error: function(xhr) {
                console.error('Exchange error:', xhr);
                var response = xhr.responseJSON;
                var errorMessage = 'Something went wrong';
                
                if (response && response.message) {
                    errorMessage = response.message;
                } else if (response && response.errors) {
                    var errors = [];
                    for (var field in response.errors) {
                        errors.push(response.errors[field][0]);
                    }
                    errorMessage = errors.join(', ');
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error occurred. Please try again.';
                } else if (xhr.status === 403) {
                    errorMessage = 'You do not have permission to perform this action.';
                }
                
                showToast(errorMessage, 'error', 8000);
            },
            complete: function() {
                $button.prop('disabled', false).html('<i class="fa fa-check"></i> Finalize Exchange');
            }
        });
    });

    // ============================================================================
    // SUCCESS DIALOG AND RECEIPT PRINTING
    // ============================================================================

    function showSuccessDialog(invoiceUrl) {
        var $overlay = $('#success-dialog-overlay');
        
        if ($overlay.length === 0) {
            createSuccessDialogHTML();
            $overlay = $('#success-dialog-overlay');
        }
        
        $('#print-invoice-btn, #skip-print-btn').off('click');
        
        // Apply comprehensive styling to ensure proper display
        $overlay.removeClass('show').css({
            'position': 'fixed',
            'top': '0',
            'left': '0',
            'right': '0',
            'bottom': '0',
            'width': '100vw',
            'height': '100vh',
            'background': 'rgba(0, 0, 0, 0.7)',
            'backdrop-filter': 'blur(3px)',
            'z-index': '999999',
            'display': 'flex',
            'align-items': 'center',
            'justify-content': 'center',
            'opacity': '0',
            'transition': 'opacity 0.3s ease'
        });
        
        // Ensure dialog has proper styling
        $('.success-dialog').css({
            'background': '#ffffff',
            'border-radius': '20px',
            'padding': '0',
            'min-width': '450px',
            'max-width': '550px',
            'width': '90vw',
            'max-height': '90vh',
            'position': 'relative',
            'top': 'auto',
            'left': 'auto',
            'transform': 'none',
            'margin': '0 auto',
            'box-shadow': '0 25px 50px rgba(0, 0, 0, 0.5)',
            'border': '2px solid #ffffff',
            'overflow': 'hidden',
            'animation': 'slideInUp 0.4s ease-out'
        });
        
        // Force header background
        $('.success-dialog-header').css({
            'background': 'linear-gradient(135deg, #28a745 0%, #20c997 100%)',
            'color': '#ffffff',
            'padding': '30px 25px 20px 25px',
            'text-align': 'center',
            'position': 'relative',
            'overflow': 'hidden',
            'border-bottom': '3px solid #ffffff'
        });
        
        // Force body background  
        $('.success-dialog-body').css({
            'background': '#ffffff',
            'color': '#333333',
            'padding': '30px 25px',
            'text-align': 'center'
        });
        
        // Style the buttons
        $('.success-btn-primary').css({
            'background': 'linear-gradient(135deg, #007bff 0%, #0056b3 100%)',
            'color': '#ffffff',
            'box-shadow': '0 4px 15px rgba(0, 123, 255, 0.4)',
            'border': 'none',
            'border-radius': '25px',
            'padding': '12px 25px',
            'font-size': '1rem',
            'font-weight': '600',
            'cursor': 'pointer',
            'transition': 'all 0.3s ease',
            'text-decoration': 'none',
            'display': 'inline-flex',
            'align-items': 'center',
            'gap': '8px',
            'min-width': '130px',
            'justify-content': 'center'
        });
        
        $('.success-btn-secondary').css({
            'background': '#f8f9fa',
            'color': '#6c757d',
            'border': '2px solid #dee2e6',
            'border-radius': '25px',
            'padding': '12px 25px',
            'font-size': '1rem',
            'font-weight': '600',
            'cursor': 'pointer',
            'transition': 'all 0.3s ease',
            'text-decoration': 'none',
            'display': 'inline-flex',
            'align-items': 'center',
            'gap': '8px',
            'min-width': '130px',
            'justify-content': 'center',
            'box-shadow': '0 2px 8px rgba(0, 0, 0, 0.1)'
        });
        
        // Show with animation
        $overlay.addClass('show');
        setTimeout(function() { $overlay.css('opacity', '1'); }, 50);
        
        $('#print-invoice-btn').on('click', function() {
            if (typeof window.exchangeId !== 'undefined' && window.exchangeId) {
                var receiptUrl = window.exchangeRoutes.printOnly(window.exchangeId);
                var printWindow = window.open(receiptUrl, 'printWindow', 'width=800,height=600');
                if (printWindow) {
                    printWindow.onload = function() {
                        setTimeout(function() {
                            printWindow.print();
                            printWindow.onafterprint = function() {
                                printWindow.close();
                            };
                        }, 1000);
                    };
                } else {
                    showToast('Popup blocked. Please allow popups for this site.', 'warning');
                }
            } else if (invoiceUrl) {
                window.open(invoiceUrl, '_blank');
            }
            
            hideSuccessDialog();
            showToast('Receipt sent to printer', 'success');
            setTimeout(function() {
                window.location.href = window.exchangeRoutes.index;
            }, 2000);
        });
        
        $('#skip-print-btn').on('click', function() {
            hideSuccessDialog();
            showToast('Exchange completed - redirecting...', 'success');
            setTimeout(function() {
                window.location.href = window.exchangeRoutes.index;
            }, 1000);
        });
        
        $overlay.on('click', function(e) {
            if (e.target === this) {
                hideSuccessDialog();
            }
        });
        
        $(document).on('keydown.successDialog', function(e) {
            if (e.keyCode === 27) {
                hideSuccessDialog();
            }
        });
    }

    function hideSuccessDialog() {
        var $overlay = $('#success-dialog-overlay');
        $overlay.css('opacity', '0');
        setTimeout(function() {
            $overlay.removeClass('show').css('display', 'none');
        }, 300);
        $overlay.off('click');
        $(document).off('keydown.successDialog');
    }

    function createSuccessDialogHTML() {
        // Inject CSS animations if not already present
        if (!$('#success-dialog-animations').length) {
            var animationCSS = `
                <style id="success-dialog-animations">
                @keyframes slideInUp {
                    from {
                        opacity: 0;
                        transform: translateY(50px) scale(0.9);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0) scale(1);
                    }
                }
                
                @keyframes bounce {
                    0%, 20%, 53%, 80%, 100% {
                        transform: translateY(0);
                    }
                    40%, 43% {
                        transform: translateY(-20px);
                    }
                    70% {
                        transform: translateY(-10px);
                    }
                    90% {
                        transform: translateY(-4px);
                    }
                }
                
                @keyframes shimmer {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                
                .success-dialog-overlay.show {
                    display: flex !important;
                    opacity: 1 !important;
                }
                
                .success-icon {
                    animation: bounce 0.8s ease-out !important;
                }
                
                .success-dialog-header::before {
                    animation: shimmer 3s ease-in-out infinite !important;
                }
                </style>
            `;
            $('head').append(animationCSS);
        }
        
        var dialogHtml = `
            <div class="success-dialog-overlay" id="success-dialog-overlay">
                <div class="success-dialog">
                    <div class="success-dialog-header">
                        <div class="success-icon">
                            <i class="fa fa-check"></i>
                        </div>
                        <h3 class="success-dialog-title">Exchange Completed!</h3>
                        <p class="success-dialog-subtitle">Transaction processed successfully</p>
                    </div>
                    <div class="success-dialog-body">
                        <p class="success-dialog-message">
                            Your exchange has been completed successfully. Would you like to print the invoice for your records?
                        </p>
                        <div class="success-dialog-actions">
                            <button type="button" class="success-btn success-btn-primary" id="print-invoice-btn">
                                <i class="fa fa-print"></i>
                                Print Invoice
                            </button>
                            <button type="button" class="success-btn success-btn-secondary" id="skip-print-btn">
                                <i class="fa fa-times"></i>
                                Skip
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#success-dialog-overlay').remove();
        $('body').append(dialogHtml);
    }

    // ============================================================================
    // MISCELLANEOUS EVENT HANDLERS
    // ============================================================================

    $('#location_id_exchange').change(function() {
        $('#exchange_new_items_table tbody').html('');
        calculateNewItemsTotal();
        updateExchangeSummary();
    });

    $(document).on('click', 'button.pos_add_quick_product', function() {
        var url = $(this).data('href');
        var container = $(this).data('container');
        $.ajax({
            url: url + '?product_for=pos',
            dataType: 'html',
            success: function(result) {
                $(container).html(result).modal('show');
            }
        });
    });

    $(document).on('quickProductAdded', function(e) {
        if ($('#location_id_exchange').val() == '') {
            showToast('Select location', 'warning');
        } else {
            addNewItemToExchange({
                variation_id: e.variation.id,
                product_id: e.variation.product_id,
                name: e.variation.product_name,
                variation: e.variation.name,
                sub_sku: e.variation.sub_sku,
                selling_price: e.variation.default_sell_price,
                type: 'variable',
                enable_stock: 0,
                qty_available: 999
            });
        }
    });

    // ============================================================================
    // DEBUG FUNCTIONS AND GLOBAL EXPORTS
    // ============================================================================

    window.forceStep = function(step) {
        currentStep = step - 1;
        updateStep(step);
    };
    
    window.showDebugInfo = function() {
        var info = 'Current Step: ' + currentStep + 
                  ' | Transaction: ' + (current_transaction ? 'Found' : 'None') + 
                  ' | Exchange Lines: ' + exchange_lines.length;
        console.log('Debug Info:', {
            currentStep: currentStep,
            transaction: current_transaction,
            exchangeLines: exchange_lines
        });
        showToast(info, 'info');
    };

    window.testSuccessDialog = function() {
        showSuccessDialog('test-url');
    };

    window.current_transaction = current_transaction;
    window.exchange_lines = exchange_lines;
    window.showToast = showToast;
    window.showSuccessDialog = showSuccessDialog;
    window.hideSuccessDialog = hideSuccessDialog;
    window.updateExchangeSummary = updateExchangeSummary;
    window.calculateNewItemsTotal = calculateNewItemsTotal;

    // ============================================================================
    // INITIALIZATION
    // ============================================================================

    initializePage();
    
    console.log('Exchange JavaScript loaded successfully');
    console.log('Available debug commands: forceStep(1-4), showDebugInfo(), testSuccessDialog()');

});
</script>
<script>
$(function() {
    const invoiceNo = new URLSearchParams(window.location.search).get('invoice_no');
    if (invoiceNo) {
        $('#search_invoice_no').val(invoiceNo);
        $('#search_transaction_btn').trigger('click');
    }
});
</script>
@endsection