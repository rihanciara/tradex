@extends('layouts.app')

@section('title', __('exchange::lang.exchange_receipt'))

@section('content')
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="invoice-print" id="exchange_receipt">
                <!-- Business Header -->
                <div class="row">
                    <div class="col-xs-12 text-center">
                        <h3>{{ $business_details->name }}</h3>
                        @if(!empty($business_details->logo))
                        <img src="{{ asset('uploads/business_logos/' . $business_details->logo) }}" alt="Logo"
                            class="img" style="max-height: 60px;">
                        @endif
                        <p>
                            {!! $business_details->business_address !!}<br>
                            @if(!empty($business_details->contact_number))
                            <strong>@lang('contact.mobile'):</strong> {{ $business_details->contact_number }}
                            @endif
                        </p>
                        <hr>
                        <h4>@lang('exchange::lang.exchange_receipt')</h4>
                    </div>
                </div>

                <!-- Exchange Details -->
                <div class="row">
                    <div class="col-xs-6">
                        <strong>@lang('exchange::lang.exchange_ref_no'):</strong> {{ $exchange->exchange_ref_no }}<br>
                        <strong>@lang('exchange::lang.exchange_date'):</strong> {{
                        @format_datetime($exchange->exchange_date)
                        }}<br>
                        <strong>@lang('exchange::lang.original_invoice'):</strong> {{
                        $exchange->originalTransaction->invoice_no ?? 'N/A' }}<br>
                    </div>
                    <div class="col-xs-6">
                        <strong>@lang('contact.customer'):</strong> {{ $exchange->originalTransaction->contact->name ??
                        'N/A' }}<br>
                        <strong>@lang('business.business_location'):</strong> {{ $exchange->location->name ?? 'N/A'
                        }}<br>
                        <strong>@lang('exchange::lang.created_by'):</strong>
                        {{ $exchange->creator->first_name ?? 'N/A' }} {{ $exchange->creator->last_name ?? '' }}

                        <br>
                    </div>
                </div>

                <hr>

                <!-- Exchange Items -->
                <div class="row">
                    <div class="col-xs-12">
                        <h5>@lang('exchange::lang.exchange_items_details')</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>@lang('exchange::lang.return_item')</th>
                                        <th>@lang('exchange::lang.return_qty')</th>
                                        <th>@lang('exchange::lang.return_amount')</th>
                                        <th>@lang('exchange::lang.new_item')</th>
                                        <th>@lang('exchange::lang.new_qty')</th>
                                        <th>@lang('exchange::lang.new_amount')</th>
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
                                        <td>
                                            @if($line->originalSellLine && $line->originalSellLine->product)
                                            {{ $line->originalSellLine->product->name }}
                                            @if($line->originalSellLine->variations &&
                                            $line->originalSellLine->variations->name != 'DUMMY')
                                            - {{ $line->originalSellLine->variations->name }}
                                            @endif
                                            @else
                                            @lang('exchange::lang.deleted_product')
                                            @endif
                                        </td>
                                        <td>{{ @num_format($line->original_quantity) }}</td>
                                        <td>{{ @num_format($return_amount) }}</td>
                                        <td>
                                            @if($line->newSellLine && $line->newSellLine->product)
                                            {{ $line->newSellLine->product->name }}
                                            @if($line->newSellLine->variations && $line->newSellLine->variations->name
                                            != 'DUMMY')
                                            - {{ $line->newSellLine->variations->name }}
                                            @endif
                                            @elseif($line->exchange_type == 'return_only')
                                            <em>@lang('exchange::lang.return_only')</em>
                                            @else
                                            @lang('exchange::lang.deleted_product')
                                            @endif
                                        </td>
                                        <td>{{ $line->new_quantity > 0 ? @num_format($line->new_quantity) : '-' }}</td>
                                        <td>{{ $new_amount > 0 ? @num_format($new_amount) : '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2" class="text-right">@lang('lang_v1.total'):</th>
                                        <th>{{ @num_format($total_return_amount) }}</th>
                                        <th colspan="2" class="text-right">@lang('lang_v1.total'):</th>
                                        <th>{{ @num_format($total_new_amount) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Exchange Summary -->
                <div class="row">
                    <div class="col-xs-12">
                        <table class="table">
                            <tr>
                                <th class="text-right">@lang('exchange::lang.total_return_value'):</th>
                                <td class="text-right">{{ @num_format($exchange->original_amount) }}</td>
                            </tr>
                            <tr>
                                <th class="text-right">@lang('exchange::lang.total_new_value'):</th>
                                <td class="text-right">{{ @num_format($exchange->new_amount) }}</td>
                            </tr>
                            <tr class="bg-gray">
                                <th class="text-right">@lang('exchange::lang.net_exchange_amount'):</th>
                                <td class="text-right">
                                    <strong>{{ @num_format($exchange->exchange_difference) }}</strong>
                                    @if($exchange->exchange_difference > 0)
                                    <small>(@lang('exchange::lang.customer_paid'))</small>
                                    @elseif($exchange->exchange_difference < 0) <small>
                                        (@lang('exchange::lang.refunded_to_customer'))</small>
                                        @else
                                        <small>(@lang('exchange::lang.even_exchange'))</small>
                                        @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if($exchange->notes)
                <div class="row">
                    <div class="col-xs-12">
                        <strong>@lang('exchange::lang.notes'):</strong>
                        <p>{{ $exchange->notes }}</p>
                    </div>
                </div>
                @endif

                <!-- Footer -->
                <div class="row">
                    <div class="col-xs-12 text-center">
                        <hr>
                        <p>@lang('exchange::lang.thank_you_for_your_business')</p>
                        <small>@lang('exchange::lang.exchange_receipt_footer')</small>
                    </div>
                </div>
            </div>

            <!-- Print Button -->
            <div class="row no-print">
                <div class="col-xs-12 text-center">
                    <button type="button" class="btn btn-primary" onclick="window.print();">
                        <i class="fa fa-print"></i> @lang('messages.print')
                    </button>
                    <a href="{{ route('exchange.index') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> @lang('exchange::lang.back')
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    @media print {
        .no-print {
            display: none !important;
        }

        .invoice-print {
            font-size: 12px;
            margin: 0;
            padding: 15px;
        }

        .table {
            font-size: 11px;
        }

        .table th,
        .table td {
            padding: 4px !important;
        }
    }
</style>
@endsection