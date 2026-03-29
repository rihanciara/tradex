{{-- Fixed Exchange Receipt Template with Proper Print Margins --}}
@extends('layouts.app')

@section('title', __('exchange::lang.exchange_receipt'))

@section('content')
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="invoice-print" id="exchange_receipt">
                {{-- Business Header --}}
                <div class="row">
                    <div class="col-xs-12 text-center">
                        <h3 style="margin-bottom: 10px; font-weight: bold; color: #333;">{{ $business_details->name }}
                        </h3>

                        {{-- Only show business logo, NOT invoice header image --}}
                        @if(!empty($business_details->logo) && file_exists(public_path('uploads/business_logos/' .
                        $business_details->logo)))
                        <img src="{{ asset('uploads/business_logos/' . $business_details->logo) }}" alt="Business Logo"
                            style="max-height: 60px; margin-bottom: 10px;">
                        @endif

                        {{-- Business text information --}}
                        <div style="margin-bottom: 15px; font-size: 12px; color: #666;">
                            @if(!empty($business_details->landmark))
                            {{ $business_details->landmark }}<br>
                            @endif
                            @if(!empty($business_details->city))
                            {{ $business_details->city }}
                            @if(!empty($business_details->state)), {{ $business_details->state }}@endif
                            @if(!empty($business_details->zip_code)) - {{ $business_details->zip_code }}@endif
                            <br>
                            @endif
                            @if(!empty($business_details->mobile))
                            <strong>Mobile:</strong> {{ $business_details->mobile }}<br>
                            @endif
                            @if(!empty($business_details->email))
                            <strong>Email:</strong> {{ $business_details->email }}<br>
                            @endif
                            @if(!empty($business_details->tax_number_1))
                            <strong>{{ $business_details->tax_label_1 ?? 'Tax No' }}:</strong> {{
                            $business_details->tax_number_1 }}<br>
                            @endif
                        </div>

                        <hr style="border-top: 2px solid #333; margin: 15px 0;">
                        <h4
                            style="margin: 15px 0; padding: 10px; background: #f5f5f5; border: 1px solid #ddd; color: #333;">
                            @lang('exchange::lang.exchange_receipt')
                        </h4>
                    </div>
                </div>

                {{-- Exchange Details --}}
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-xs-6">
                        <table style="width: 100%; font-size: 13px;">
                            <tr>
                                <td style="padding: 3px 0; font-weight: bold; width: 40%;">
                                    @lang('exchange::lang.exchange_ref_no'):</td>
                                <td style="padding: 3px 0;"><strong>{{ $exchange->exchange_ref_no }}</strong></td>
                            </tr>
                            <tr>
                                <td style="padding: 3px 0; font-weight: bold;">@lang('exchange::lang.exchange_date'):
                                </td>
                                <td style="padding: 3px 0;">{{
                                    \Carbon\Carbon::parse($exchange->exchange_date)->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 3px 0; font-weight: bold;">@lang('exchange::lang.original_invoice'):
                                </td>
                                <td style="padding: 3px 0;">{{ $exchange->originalTransaction->invoice_no ?? 'N/A' }}
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-xs-6">
                        <table style="width: 100%; font-size: 13px;">
                            <tr>
                                <td style="padding: 3px 0; font-weight: bold; width: 40%;">@lang('contact.customer'):
                                </td>
                                <td style="padding: 3px 0;">{{ $exchange->originalTransaction->contact->name ?? 'Walk-In
                                    Customer' }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 3px 0; font-weight: bold;">@lang('business.business_location'):</td>
                                <td style="padding: 3px 0;">{{ $exchange->location->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 3px 0; font-weight: bold;">@lang('exchange::lang.created_by'):</td>
                                <td style="padding: 3px 0;">
                                    {{ $exchange->creator->first_name ?? 'N/A' }} {{ $exchange->creator->last_name ?? ''
                                    }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr style="border-top: 1px solid #ddd; margin: 20px 0;">

                {{-- Exchange Items --}}
                <div class="row">
                    <div class="col-xs-12">
                        <h5
                            style="margin-bottom: 15px; padding: 8px; background: #f8f9fa; border-left: 4px solid #007bff;">
                            @lang('exchange::lang.exchange_items_details')
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered" style="font-size: 12px; margin-bottom: 20px;">
                                <thead>
                                    <tr style="background: #f5f5f5;">
                                        <th style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                            @lang('exchange::lang.return_item')</th>
                                        <th
                                            style="padding: 8px; border: 1px solid #ddd; text-align: center; width: 10%;">
                                            @lang('exchange::lang.return_qty')</th>
                                        <th
                                            style="padding: 8px; border: 1px solid #ddd; text-align: center; width: 12%;">
                                            @lang('exchange::lang.return_amount')</th>
                                        <th style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                            @lang('exchange::lang.new_item')</th>
                                        <th
                                            style="padding: 8px; border: 1px solid #ddd; text-align: center; width: 10%;">
                                            @lang('exchange::lang.new_qty')</th>
                                        <th
                                            style="padding: 8px; border: 1px solid #ddd; text-align: center; width: 12%;">
                                            @lang('exchange::lang.new_amount')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $total_return_amount = 0;
                                    $total_new_amount = 0;
                                    @endphp

                                    @foreach($exchange->exchangeLines as $line)
                                    @php
                                    $return_amount = $line->original_quantity * $line->original_unit_price;
                                    $new_amount = $line->new_quantity * $line->new_unit_price;
                                    $total_return_amount += $return_amount;
                                    $total_new_amount += $new_amount;
                                    @endphp
                                    <tr>
                                        <td style="padding: 8px; border: 1px solid #ddd; vertical-align: top;">
                                            @if($line->originalSellLine && $line->originalSellLine->product)
                                            {{ $line->originalSellLine->product->name }}
                                            @if($line->originalSellLine->variations &&
                                            $line->originalSellLine->variations->name != 'DUMMY')
                                            <br><small style="color: #666;">{{ $line->originalSellLine->variations->name
                                                }}</small>
                                            @endif
                                            @else
                                            <em style="color: #999;">@lang('exchange::lang.deleted_product')</em>
                                            @endif
                                        </td>
                                        <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">{{
                                            number_format($line->original_quantity, 2) }}</td>
                                        <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">{{
                                            number_format($return_amount, 2) }}</td>
                                        <td style="padding: 8px; border: 1px solid #ddd; vertical-align: top;">
                                            @if($line->newSellLine && $line->newSellLine->product)
                                            {{ $line->newSellLine->product->name }}
                                            @if($line->newSellLine->variations && $line->newSellLine->variations->name
                                            != 'DUMMY')
                                            <br><small style="color: #666;">{{ $line->newSellLine->variations->name
                                                }}</small>
                                            @endif
                                            @elseif($line->exchange_type == 'return_only')
                                            <em style="color: #d9534f;">@lang('exchange::lang.return_only')</em>
                                            @else
                                            <em style="color: #999;">@lang('exchange::lang.deleted_product')</em>
                                            @endif
                                        </td>
                                        <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                            {{ $line->new_quantity > 0 ? number_format($line->new_quantity, 2) : '-' }}
                                        </td>
                                        <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">
                                            {{ $new_amount > 0 ? number_format($new_amount, 2) : '-' }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr style="background: #f0f0f0; font-weight: bold;">
                                        <td colspan="2"
                                            style="padding: 8px; border: 1px solid #ddd; text-align: right;">
                                            <strong>@lang('lang_v1.total'):</strong>
                                        </td>
                                        <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">
                                            <strong>{{ number_format($total_return_amount, 2) }}</strong>
                                        </td>
                                        <td colspan="2"
                                            style="padding: 8px; border: 1px solid #ddd; text-align: right;">
                                            <strong>@lang('lang_v1.total'):</strong>
                                        </td>
                                        <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">
                                            <strong>{{ number_format($total_new_amount, 2) }}</strong>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Exchange Summary --}}
                <div class="row">
                    <div class="col-xs-12">
                        <h5
                            style="margin-bottom: 15px; padding: 8px; background: #f8f9fa; border-left: 4px solid #28a745;">
                            @lang('exchange::lang.exchange_summary')
                        </h5>
                        <table class="table" style="font-size: 13px; margin-bottom: 20px;">
                            <tr>
                                <th style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd; width: 70%;">
                                    @lang('exchange::lang.total_return_value'):
                                </th>
                                <td style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">
                                    {{ number_format($exchange->original_amount, 2) }}
                                </td>
                            </tr>
                            <tr>
                                <th style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">
                                    @lang('exchange::lang.total_new_value'):
                                </th>
                                <td style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">
                                    {{ number_format($exchange->new_amount, 2) }}
                                </td>
                            </tr>
                            <tr style="background: #e8f5e8; font-size: 14px;">
                                <th
                                    style="text-align: right; padding: 12px; border: 2px solid #28a745; font-weight: bold;">
                                    @lang('exchange::lang.net_exchange_amount'):
                                </th>
                                <td
                                    style="text-align: right; padding: 12px; border: 2px solid #28a745; font-weight: bold; color: #155724;">
                                    <strong>{{ number_format($exchange->exchange_difference, 2) }}</strong>
                                    @if($exchange->exchange_difference > 0)
                                    <br><small
                                        style="color: #856404; font-style: italic;">(@lang('exchange::lang.customer_paid'))</small>
                                    @elseif($exchange->exchange_difference < 0) <br><small
                                            style="color: #721c24; font-style: italic;">(@lang('exchange::lang.refunded_to_customer'))</small>
                                        @else
                                        <br><small
                                            style="color: #155724; font-style: italic;">(@lang('exchange::lang.even_exchange'))</small>
                                        @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if($exchange->notes)
                <div class="row">
                    <div class="col-xs-12">
                        <div
                            style="margin: 20px 0; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                            <strong style="color: #856404;">@lang('exchange::lang.notes'):</strong>
                            <p style="margin: 5px 0 0 0; color: #856404;">{{ $exchange->notes }}</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Footer --}}
                <div class="row">
                    <div class="col-xs-12 text-center">
                        <hr style="border-top: 2px solid #333; margin: 30px 0 20px 0;">
                        <p style="margin: 10px 0; font-weight: bold; font-size: 14px;">
                            @lang('exchange::lang.thank_you_for_your_business')</p>
                        <small style="color: #666; font-style: italic;">
                            @lang('exchange::lang.exchange_receipt_footer') |
                            Generated on: {{ now()->format('d/m/Y H:i:s') }}
                        </small>
                    </div>
                </div>
            </div>

            {{-- Print Button --}}
            <div class="row no-print">
                <div class="col-xs-12 text-center"
                    style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <button type="button" class="btn btn-primary btn-lg" onclick="window.print();" style="margin: 5px;">
                        <i class="fa fa-print"></i> Print M1
                        {{-- @lang('messages.print') --}}
                    </button>
                    @if(auth()->user()->can('exchange.access'))
                    <a href="{{ route('exchange.print-only', $exchange->id) }}" target="_blank"
                        class="btn btn-primary btn-lg" style="margin: 5px;">
                        <i class="fa fa-print"></i> Print M2
                    </a>
                    @endif
                    <a href="{{ route('exchange.index') }}" class="btn btn-default btn-lg" style="margin: 5px;">
                        <i class="fa fa-arrow-left"></i> @lang('exchange::lang.back')
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    /* FIXED: Enhanced print styles with proper margins */
    @media print {

        /* Hide non-print elements */
        .no-print {
            display: none !important;
        }

        /* CRITICAL: Set proper page margins */
        @page {
            margin: 20mm 15mm 20mm 15mm !important;
            /* Top Right Bottom Left */
            size: A4;
        }

        /* Body and container margins */
        body {
            margin: 0 !important;
            padding: 0 !important;
            font-size: 12px;
            line-height: 1.4;
            color: #000 !important;
            background: white !important;
        }

        /* Main container with proper margins */
        .invoice-print {
            margin: 10mm 0 !important;
            padding: 0 5mm !important;
            background: white !important;
            color: #000 !important;
            width: 100% !important;
            max-width: none !important;
        }

        /* Grid system adjustments */
        .row {
            margin: 0 !important;
            page-break-inside: avoid;
        }

        .col-xs-12,
        .col-xs-6 {
            padding: 0 !important;
            float: none !important;
            width: 100% !important;
        }

        .col-xs-6 {
            width: 50% !important;
            float: left !important;
        }

        /* Typography adjustments */
        h3,
        h4,
        h5 {
            margin: 8px 0 !important;
            page-break-after: avoid;
            color: #000 !important;
        }

        h3 {
            font-size: 18px !important;
        }

        h4 {
            font-size: 16px !important;
        }

        h5 {
            font-size: 14px !important;
        }

        /* Table styles for print */
        .table {
            font-size: 11px !important;
            border-collapse: collapse !important;
            width: 100% !important;
            margin: 10px 0 !important;
        }

        .table th,
        .table td {
            padding: 4px 6px !important;
            border: 1px solid #000 !important;
            vertical-align: top !important;
            word-wrap: break-word !important;
        }

        .table th {
            background: #f5f5f5 !important;
            font-weight: bold !important;
            color: #000 !important;
        }

        /* Table responsive override */
        .table-responsive {
            overflow-x: visible !important;
            border: none !important;
        }

        /* Text alignment */
        .text-center {
            text-align: center !important;
        }

        .text-right {
            text-align: right !important;
        }

        /* Remove shadows and gradients */
        * {
            box-shadow: none !important;
            text-shadow: none !important;
            background-image: none !important;
        }

        /* Ensure proper line breaks */
        tr {
            page-break-inside: avoid;
        }

        /* Summary section styling */
        .table tr:last-child th,
        .table tr:last-child td {
            border-bottom: 2px solid #000 !important;
        }

        /* Footer spacing */
        hr {
            border-top: 1px solid #000 !important;
            margin: 15px 0 !important;
        }

        /* Image handling */
        img {
            max-width: 100% !important;
            height: auto !important;
            page-break-inside: avoid;
        }

        /* Small text adjustments */
        small {
            font-size: 10px !important;
        }

        /* Prevent content overflow */
        * {
            max-width: 100% !important;
            word-wrap: break-word !important;
        }

        /* Force break after large sections */
        .exchange-summary {
            page-break-before: auto;
        }
    }

    /* Screen styles remain unchanged */
    .invoice-print {
        background: white;
        color: #333;
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .invoice-print h3 {
        color: #333 !important;
    }

    .invoice-print .table {
        border-collapse: collapse;
    }

    .invoice-print .table th,
    .invoice-print .table td {
        border: 1px solid #ddd;
    }

    /* Ensure proper spacing on screen */
    .invoice-print .row {
        margin-bottom: 0;
    }

    .invoice-print .col-xs-12,
    .invoice-print .col-xs-6 {
        padding-left: 15px;
        padding-right: 15px;
    }
</style>

@push('javascript')
<script>
    $(document).ready(function() {
        // Auto-print functionality with delay to ensure proper rendering
        if (window.location.search.includes('auto_print=1')) {
            setTimeout(function() {
                window.print();
            }, 1500); // Increased delay for better rendering
        }
        
        // Enhanced print function
        window.printReceipt = function() {
            // Hide all non-essential elements
            $('.no-print').hide();
            
            // Trigger print
            setTimeout(function() {
                window.print();
                
                // Show elements back after print dialog
                setTimeout(function() {
                    $('.no-print').show();
                }, 1000);
            }, 500);
        };
    });
</script>
@endpush
@endsection