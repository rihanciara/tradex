<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title">@lang('advancedreports::lang.sales_details') - {{ $transaction->invoice_no }}</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="row">
                <!-- Transaction Info -->
                <!-- FIXED: Transaction Info - Only transaction-related fields -->
                <div class="col-md-6">
                    <div class="box box-info">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('advancedreports::lang.transaction_info')</h3>
                        </div>
                        <div class="box-body">
                            <table class="table table-condensed">
                                <tr>
                                    <td><strong>@lang('sale.invoice_no'):</strong></td>
                                    <td>{{ $transaction->invoice_no }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('messages.date'):</strong></td>
                                    <td>{{ @format_datetime($transaction->transaction_date) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('business.business_location'):</strong></td>
                                    <td>{{ $transaction->location_name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('sale.payment_status'):</strong></td>
                                    <td>
                                        @if($transaction->payment_status == 'paid')
                                        <span class="label label-success">@lang('lang_v1.paid')</span>
                                        @elseif($transaction->payment_status == 'due')
                                        <span class="label label-danger">@lang('lang_v1.due')</span>
                                        @elseif($transaction->payment_status == 'partial')
                                        <span class="label label-warning">@lang('lang_v1.partial')</span>
                                        @else
                                        <span class="label label-default">{{ $transaction->payment_status }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('advancedreports::lang.created_by'):</strong></td>
                                    <td>{{ trim($transaction->added_by ?? '') ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- FIXED: Customer Info - Only customer-related fields -->
                <div class="col-md-6">
                    <div class="box box-success">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('advancedreports::lang.customer_info')</h3>
                        </div>
                        <div class="box-body">
                            <table class="table table-condensed">
                                <tr>
                                    <td><strong>@lang('contact.name'):</strong></td>
                                    <td>{{ $transaction->customer_name ?? __('advancedreports::lang.walk_in_customer')
                                        }}</td>
                                </tr>
                                @if($transaction->supplier_business_name)
                                <tr>
                                    <td><strong>@lang('business.business_name'):</strong></td>
                                    <td>{{ $transaction->supplier_business_name }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td><strong>@lang('contact.mobile'):</strong></td>
                                    <td>{{ $transaction->customer_mobile ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('business.email'):</strong></td>
                                    <td>{{ $transaction->customer_email ?? '-' }}</td>
                                </tr>
                                @if($transaction->customer_contact_id)
                                <tr>
                                    <td><strong>@lang('lang_v1.contact_id'):</strong></td>
                                    <td>{{ $transaction->customer_contact_id }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products/Items -->
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-warning">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('sale.products')</h3>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-condensed table-striped">
                                    <thead>
                                        <tr>
                                            <th>@lang('sale.product')</th>
                                            <th>@lang('sale.qty')</th>
                                            <th>@lang('sale.unit_price')</th>
                                            <th>@lang('sale.discount')</th>
                                            <th>@lang('sale.tax')</th>
                                            <th>@lang('sale.price_inc_tax')</th>
                                            <th>@lang('sale.subtotal')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                        $subtotal = 0;
                                        $total_tax = 0;
                                        $total_line_discount = 0;
                                        @endphp
                                        @foreach($transaction->sell_lines as $line)
                                        @php
                                        $line_total = $line->quantity * $line->unit_price_inc_tax;
                                        $subtotal += $line_total;
                                        $total_tax += $line->item_tax * $line->quantity;

                                        // Line discount calculation
                                        $line_discount = 0;
                                        if($line->line_discount_amount > 0) {
                                        if($line->line_discount_type == 'percentage') {
                                        $line_discount = ($line->unit_price_before_discount *
                                        $line->line_discount_amount) / 100;
                                        } else {
                                        $line_discount = $line->line_discount_amount;
                                        }
                                        }
                                        $total_line_discount += $line_discount * $line->quantity;
                                        @endphp
                                        <tr>
                                            <td>
                                                {{ $line->product_name ?? 'N/A' }}
                                                @if($line->variation_name && $line->variation_name != 'DUMMY')
                                                <br><small>{{ $line->variation_name }}</small>
                                                @endif
                                            </td>
                                            <td>{{ @num_format($line->quantity) }}</td>
                                            <td>@format_currency($line->unit_price_before_discount)</td>
                                            <td>
                                                @if($line_discount > 0)
                                                @format_currency($line_discount)
                                                @if($line->line_discount_type == 'percentage')
                                                ({{ $line->line_discount_amount }}%)
                                                @endif
                                                @else
                                                -
                                                @endif
                                            </td>
                                            <td>@format_currency($line->item_tax)</td>
                                            <td>@format_currency($line->unit_price_inc_tax)</td>
                                            <td>@format_currency($line_total)</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Info -->
            <div class="row">
                <div class="col-md-6">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('sale.payment_info')</h3>
                        </div>
                        <div class="box-body">
                            <table class="table table-condensed">
                                @foreach($transaction->payment_lines as $payment)
                                <tr>
                                    <td>{{ @format_datetime($payment->paid_on) }}</td>
                                    <td>{{ $payment->method }}</td>
                                    <td>@format_currency($payment->amount)</td>
                                </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Totals -->
                <div class="col-md-6">
                    <div class="box box-danger">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('sale.total_amount')</h3>
                        </div>
                        <div class="box-body">
                            @php
                            // FIXED: Calculate invoice-level discount
                            $invoice_discount = 0;
                            if($transaction->discount_amount > 0) {
                            if($transaction->discount_type == 'percentage') {
                            $invoice_discount = ($transaction->total_before_tax * $transaction->discount_amount) / 100;
                            } else {
                            $invoice_discount = $transaction->discount_amount;
                            }
                            }

                            // FIXED: Total discount = line discount + invoice discount
                            $total_discount = $total_line_discount + $invoice_discount;
                            @endphp

                            <table class="table table-condensed">
                                <tr>
                                    <td><strong>@lang('sale.subtotal'):</strong></td>
                                    <td class="text-right">@format_currency($transaction->total_before_tax)</td>
                                </tr>

                                @if($total_discount > 0)
                                <tr>
                                    <td><strong>@lang('sale.discount'):</strong></td>
                                    <td class="text-right">@format_currency($total_discount)</td>
                                </tr>

                                <!-- ADDED: Discount breakdown -->
                                @if($total_line_discount > 0 && $invoice_discount > 0)
                                <tr>
                                    <td><small>&nbsp;&nbsp;&nbsp;&nbsp;Line Discount:</small></td>
                                    <td class="text-right"><small>@format_currency($total_line_discount)</small></td>
                                </tr>
                                <tr>
                                    <td><small>&nbsp;&nbsp;&nbsp;&nbsp;Invoice Discount:</small></td>
                                    <td class="text-right"><small>@format_currency($invoice_discount)</small></td>
                                </tr>
                                @elseif($total_line_discount > 0)
                                <tr>
                                    <td><small>&nbsp;&nbsp;&nbsp;&nbsp;Line Discount:</small></td>
                                    <td class="text-right"><small>@format_currency($total_line_discount)</small></td>
                                </tr>
                                @elseif($invoice_discount > 0)
                                <tr>
                                    <td><small>&nbsp;&nbsp;&nbsp;&nbsp;Invoice Discount:</small></td>
                                    <td class="text-right"><small>@format_currency($invoice_discount)</small></td>
                                </tr>
                                @endif
                                @endif

                                <tr>
                                    <td><strong>@lang('sale.tax'):</strong></td>
                                    <td class="text-right">@format_currency($transaction->tax_amount)</td>
                                </tr>
                                <tr class="success">
                                    <td><strong>@lang('sale.total_amount'):</strong></td>
                                    <td class="text-right"><strong>@format_currency($transaction->final_total)</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('advancedreports::lang.total_paid'):</strong></td>
                                    <td class="text-right">@format_currency($transaction->payment_lines->sum('amount'))
                                    </td>
                                </tr>
                                <tr
                                    class="{{ $transaction->final_total - $transaction->payment_lines->sum('amount') > 0 ? 'danger' : 'success' }}">
                                    <td><strong>@lang('advancedreports::lang.balance_due'):</strong></td>
                                    <td class="text-right">
                                        <strong>@format_currency($transaction->final_total -
                                            $transaction->payment_lines->sum('amount'))</strong>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            @if($transaction->staff_note)
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-info">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('lang_v1.staff_note')</h3>
                        </div>
                        <div class="box-body">
                            {{ $transaction->staff_note }}
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="modal-footer no-print">
            <div class="pull-left">
                @can('print_invoice')
                {{-- Remove target="_blank" to let JavaScript handle it --}}
                <a href="#" class="btn btn-primary btn-sm print-invoice"
                    data-href="{{ route('sell.printInvoice', [$transaction->id]) }}">
                    <i class="fas fa-print" aria-hidden="true"></i> @lang('lang_v1.print_invoice')
                </a>

                <a href="#" class="btn btn-info btn-sm print-invoice"
                    data-href="{{ route('sell.printInvoice', [$transaction->id]) }}?package_slip=true">
                    <i class="fas fa-file-alt" aria-hidden="true"></i> @lang('lang_v1.packing_slip')
                </a>

                <a href="#" class="btn btn-warning btn-sm print-invoice"
                    data-href="{{ route('sell.printInvoice', [$transaction->id]) }}?delivery_note=true">
                    <i class="fas fa-file-alt" aria-hidden="true"></i> @lang('lang_v1.delivery_note')
                </a>
                @endcan
            </div>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>