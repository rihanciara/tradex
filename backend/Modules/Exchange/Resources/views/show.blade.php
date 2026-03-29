<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                @lang('exchange::lang.exchange_details') - {{ $exchange->exchange_ref_no }}
            </h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <!-- Exchange Information -->
                <div class="col-md-6">
                    <div class="box box-solid">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('exchange::lang.exchange_information')</h3>
                        </div>
                        <div class="box-body">
                            <table class="table table-condensed">
                                <tr>
                                    <th>@lang('exchange::lang.exchange_ref_no'):</th>
                                    <td>{{ $exchange->exchange_ref_no }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('exchange::lang.exchange_date'):</th>
                                    <td>{{ @format_datetime($exchange->exchange_date) }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('business.business_location'):</th>
                                    <td>{{ $exchange->location->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('lang_v1.status'):</th>
                                    <td>
                                        <span
                                            class="label label-{{ $exchange->status == 'completed' ? 'success' : 'warning' }}">
                                            {{ ucfirst($exchange->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>@lang('exchange::lang.created_by'):</th>
                                    <td>{{ $exchange->creator->name ?? 'N/A' }}</td>
                                </tr>
                                @if($exchange->notes)
                                <tr>
                                    <th>@lang('exchange::lang.notes'):</th>
                                    <td>{{ $exchange->notes }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Original Transaction Info -->
                <div class="col-md-6">
                    <div class="box box-solid">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('exchange::lang.original_transaction')</h3>
                        </div>
                        <div class="box-body">
                            <table class="table table-condensed">
                                <tr>
                                    <th>@lang('sale.invoice_no'):</th>
                                    <td>{{ $exchange->originalTransaction->invoice_no ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('lang_v1.date'):</th>
                                    <td>{{ @format_datetime($exchange->originalTransaction->transaction_date) }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('contact.customer'):</th>
                                    <td>{{ $exchange->originalTransaction->contact->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('lang_v1.total'):</th>
                                    <td>
                                        <span class="display_currency" data-currency_symbol="true">
                                            {{ $exchange->originalTransaction->final_total ?? 0 }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exchange Lines Details -->
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-solid">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('exchange::lang.exchange_items_details')</h3>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr class="bg-gray">
                                            <th>@lang('exchange::lang.return_item')</th>
                                            <th>@lang('exchange::lang.return_qty')</th>
                                            <th>@lang('lang_v1.original_price')</th>
                                            <th>@lang('receipt.discount')</th>
                                            <th>@lang('exchange::lang.return_unit_price')</th>
                                            <th>@lang('exchange::lang.return_amount')</th>
                                            <th>@lang('exchange::lang.new_item')</th>
                                            <th>@lang('exchange::lang.new_qty')</th>
                                            <th>@lang('lang_v1.original_price')</th>
                                            <th>@lang('receipt.discount')</th>
                                            <th>@lang('exchange::lang.new_unit_price')</th>
                                            <th>@lang('exchange::lang.new_amount')</th>
                                            <th>@lang('exchange::lang.difference')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                        $total_return_amount = 0;
                                        $total_new_amount = 0;
                                        @endphp

                                        @foreach($exchange->exchangeLines as $line)
                                        @php
                                        // Calculate return item discount info
                                        $return_original_price = $line->originalSellLine ? ($line->originalSellLine->unit_price_before_discount ?: $line->originalSellLine->unit_price) : $line->original_unit_price;
                                        $return_discount_amount = $line->originalSellLine ? ($line->originalSellLine->line_discount_amount ?: 0) : 0;
                                        $return_discount_type = $line->originalSellLine ? ($line->originalSellLine->line_discount_type ?: '') : '';

                                        // Calculate new item discount info
                                        $new_original_price = $line->newSellLine ? ($line->newSellLine->unit_price_before_discount ?: $line->newSellLine->unit_price) : $line->new_unit_price;
                                        $new_discount_amount = $line->newSellLine ? ($line->newSellLine->line_discount_amount ?: 0) : 0;
                                        $new_discount_type = $line->newSellLine ? ($line->newSellLine->line_discount_type ?: '') : '';

                                        $return_amount = $line->original_quantity * $line->original_unit_price;
                                        $new_amount = $line->new_quantity * $line->new_unit_price;
                                        $difference = $new_amount - $return_amount;

                                        $total_return_amount += $return_amount;
                                        $total_new_amount += $new_amount;
                                        @endphp
                                        <tr>
                                            <!-- Return Item -->
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
                                            <!-- Return Original Price -->
                                            <td>
                                                <span class="display_currency" data-currency_symbol="true">
                                                    {{ $return_original_price }}
                                                </span>
                                            </td>
                                            <!-- Return Discount -->
                                            <td>
                                                @if($return_discount_amount > 0)
                                                    @if($return_discount_type == 'percentage')
                                                        <span class="text-success">{{ $return_discount_amount }}%</span>
                                                    @else
                                                        <span class="text-success display_currency" data-currency_symbol="true">{{ $return_discount_amount }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <!-- Return Final Price -->
                                            <td>
                                                <span class="display_currency" data-currency_symbol="true">
                                                    {{ $line->original_unit_price }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="display_currency" data-currency_symbol="true">
                                                    {{ $return_amount }}
                                                </span>
                                            </td>

                                            <!-- New Item -->
                                            <td>
                                                @if($line->newSellLine && $line->newSellLine->product)
                                                {{ $line->newSellLine->product->name }}
                                                @if($line->newSellLine->variations &&
                                                $line->newSellLine->variations->name != 'DUMMY')
                                                - {{ $line->newSellLine->variations->name }}
                                                @endif
                                                @elseif($line->exchange_type == 'return_only')
                                                <em>@lang('exchange::lang.return_only')</em>
                                                @else
                                                @lang('exchange::lang.deleted_product')
                                                @endif
                                            </td>
                                            <td>{{ $line->new_quantity > 0 ? @num_format($line->new_quantity) : '-' }}</td>
                                            <!-- New Original Price -->
                                            <td>
                                                @if($line->new_unit_price > 0)
                                                <span class="display_currency" data-currency_symbol="true">
                                                    {{ $new_original_price }}
                                                </span>
                                                @else
                                                -
                                                @endif
                                            </td>
                                            <!-- New Discount -->
                                            <td>
                                                @if($new_discount_amount > 0 && $line->new_unit_price > 0)
                                                    @if($new_discount_type == 'percentage')
                                                        <span class="text-success">{{ $new_discount_amount }}%</span>
                                                    @else
                                                        <span class="text-success display_currency" data-currency_symbol="true">{{ $new_discount_amount }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <!-- New Final Price -->
                                            <td>
                                                @if($line->new_unit_price > 0)
                                                <span class="display_currency" data-currency_symbol="true">
                                                    {{ $line->new_unit_price }}
                                                </span>
                                                @else
                                                -
                                                @endif
                                            </td>
                                            <td>
                                                @if($new_amount > 0)
                                                <span class="display_currency" data-currency_symbol="true">
                                                    {{ $new_amount }}
                                                </span>
                                                @else
                                                -
                                                @endif
                                            </td>

                                            <!-- Difference -->
                                            <td class="text-center">
                                                @if($difference != 0)
                                                <span
                                                    class="display_currency {{ $difference > 0 ? 'text-danger' : 'text-success' }}"
                                                    data-currency_symbol="true">
                                                    {{ $difference > 0 ? '+' : '' }}{{ $difference }}
                                                </span>
                                                <br>
                                                <small class="label label-{{ $difference > 0 ? 'danger' : 'success' }}">
                                                    {{ $difference > 0 ? __('exchange::lang.customer_pays') :
                                                    __('exchange::lang.refund_due') }}
                                                </small>
                                                @else
                                                <span class="text-muted">@lang('exchange::lang.no_difference')</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-gray">
                                            <th colspan="5" class="text-right">@lang('exchange::lang.total'):</th>
                                            <th>
                                                <span class="display_currency" data-currency_symbol="true">
                                                    {{ $total_return_amount }}
                                                </span>
                                            </th>
                                            <th colspan="5" class="text-right">@lang('exchange::lang.total'):</th>
                                            <th>
                                                <span class="display_currency" data-currency_symbol="true">
                                                    {{ $total_new_amount }}
                                                </span>
                                            </th>
                                            <th>
                                                @php $net_difference = $total_new_amount - $total_return_amount; @endphp
                                                <strong>
                                                    <span
                                                        class="display_currency {{ $net_difference > 0 ? 'text-danger' : ($net_difference < 0 ? 'text-success' : '') }}"
                                                        data-currency_symbol="true">
                                                        {{ $net_difference > 0 ? '+' : '' }}{{ $net_difference }}
                                                    </span>
                                                </strong>
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Box -->
            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <div class="box box-success">
                        <div class="box-header with-border text-center">
                            <h3 class="box-title">@lang('exchange::lang.exchange_summary')</h3>
                        </div>
                        <div class="box-body">
                            <table class="table table-condensed">
                                <tr>
                                    <th class="text-right">@lang('exchange::lang.total_return_value'):</th>
                                    <td class="text-right">
                                        <span class="display_currency" data-currency_symbol="true">{{
                                            $total_return_amount }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-right">@lang('exchange::lang.total_new_value'):</th>
                                    <td class="text-right">
                                        <span class="display_currency" data-currency_symbol="true">{{ $total_new_amount
                                            }}</span>
                                    </td>
                                </tr>
                                <tr class="bg-gray">
                                    <th class="text-right">@lang('exchange::lang.net_exchange_amount'):</th>
                                    <td class="text-right">
                                        <strong>
                                            <span
                                                class="display_currency {{ $exchange->total_exchange_amount > 0 ? 'text-danger' : ($exchange->total_exchange_amount < 0 ? 'text-success' : '') }}"
                                                data-currency_symbol="true">
                                                {{ $exchange->total_exchange_amount }}
                                            </span>
                                        </strong>
                                        @if($exchange->total_exchange_amount > 0)
                                        <br><small class="text-muted">(@lang('exchange::lang.customer_paid'))</small>
                                        @elseif($exchange->total_exchange_amount < 0) <br><small
                                                class="text-muted">(@lang('exchange::lang.refunded_to_customer'))</small>
                                            @else
                                            <br><small
                                                class="text-muted">(@lang('exchange::lang.even_exchange'))</small>
                                            @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            @if($exchange->exchangeTransaction)
            @can('print_invoice')
            <a href="#" class="print-invoice btn btn-primary"
                data-href="{{ route('sell.printInvoice', [$exchange->exchangeTransaction->id]) }}">
                <i class="fa fa-print"></i> @lang('exchange::lang.print_invoice')
            </a>

            <a href="{{ route('exchange.print', [$exchange->id]) }}" class="btn btn-success" target="_blank">
                <i class="fa fa-receipt"></i> @lang('exchange::lang.print_exchange_receipt')
            </a>
            @endcan
            @endif

            <button type="button" class="btn btn-default" data-dismiss="modal">
                @lang('messages.close')
            </button>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
    __currency_convert_recursively($('.modal-content'));
});
</script>