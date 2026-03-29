<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('advancedreports::lang.stock_report') - {{ $product->name }}</h4>
        </div>
        <div class="modal-body">

            <!-- Product Basic Info -->
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('sale.product') @lang('advancedreports::lang.product_info')</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-condensed">
                                        <tr>
                                            <th>@lang('product.sku'):</th>
                                            <td>{{ $product->sku }}</td>
                                        </tr>
                                        <tr>
                                            <th>@lang('sale.product'):</th>
                                            <td>{{ $product->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>@lang('category.category'):</th>
                                            <td>{{ $category->name ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-condensed">
                                        <tr>
                                            <th>@lang('product.unit'):</th>
                                            <td>{{ $unit_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>@lang('lang_v1.type'):</th>
                                            <td>{{ ucfirst($product->type) }}</td>
                                        </tr>
                                        <tr>
                                            <th>@lang('sale.status'):</th>
                                            <td>
                                                @if($product->enable_stock == 1)
                                                <span
                                                    class="label label-success">@lang('advancedreports::lang.active')</span>
                                                @else
                                                <span
                                                    class="label label-default">@lang('advancedreports::lang.inactive')</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Details by Location -->
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-info">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('advancedreports::lang.current_stock') @lang('lang_v1.by')
                                @lang('purchase.business_location')</h3>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>@lang('lang_v1.variation')</th>
                                            <th>@lang('purchase.business_location')</th>
                                            <th>@lang('advancedreports::lang.current_stock')</th>
                                            <th>@lang('lang_v1.purchase_price')</th>
                                            <th>@lang('advancedreports::lang.selling_price')</th>
                                            <th>@lang('advancedreports::lang.stock_value_purchase')</th>
                                            <th>@lang('advancedreports::lang.stock_value_sale')</th>
                                            <th>@lang('advancedreports::lang.potential_profit')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                        $total_stock_value_purchase = 0;
                                        $total_stock_value_sale = 0;
                                        $total_potential_profit = 0;
                                        $total_current_stock = 0;
                                        @endphp

                                        @forelse($stockData as $variationData)
                                        @forelse($variationData['locations'] as $locationData)
                                        @php
                                        $variation = $variationData['variation'];
                                        $current_stock = $locationData['current_stock'];
                                        $purchase_price = $locationData['purchase_price'];
                                        $selling_price = $locationData['selling_price'];
                                        $stock_value_purchase = $current_stock * $purchase_price;
                                        $stock_value_sale = $current_stock * $selling_price;
                                        $potential_profit = $stock_value_sale - $stock_value_purchase;

                                        $total_stock_value_purchase += $stock_value_purchase;
                                        $total_stock_value_sale += $stock_value_sale;
                                        $total_potential_profit += $potential_profit;
                                        $total_current_stock += $current_stock;
                                        @endphp

                                        <tr>
                                            <td>
                                                {{ $variation->name && $variation->name != 'DUMMY' ? $variation->name :
                                                'Default' }}
                                                @if($variation->sub_sku)
                                                <br><small class="text-muted">{{ $variation->sub_sku }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $locationData['business_location']->name }}</td>
                                            <td
                                                class="{{ $current_stock <= 0 ? 'text-danger' : ($current_stock <= 10 ? 'text-warning' : '') }}">
                                                {{ number_format($current_stock, 2) }} {{ $unit_name }}
                                            </td>
                                            <td class="text-right">
                                                <span class="display_currency" data-currency_symbol="true">{{
                                                    number_format($purchase_price, 2) }}</span>
                                            </td>
                                            <td class="text-right">
                                                <span class="display_currency" data-currency_symbol="true">{{
                                                    number_format($selling_price, 2) }}</span>
                                            </td>
                                            <td class="text-right">
                                                <span class="display_currency" data-currency_symbol="true">{{
                                                    number_format($stock_value_purchase, 2) }}</span>
                                            </td>
                                            <td class="text-right">
                                                <span class="display_currency" data-currency_symbol="true">{{
                                                    number_format($stock_value_sale, 2) }}</span>
                                            </td>
                                            <td
                                                class="text-right {{ $potential_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                                <span class="display_currency" data-currency_symbol="true">{{
                                                    number_format($potential_profit, 2) }}</span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td>{{ $variationData['variation']->name &&
                                                $variationData['variation']->name != 'DUMMY' ?
                                                $variationData['variation']->name : 'Default' }}</td>
                                            <td colspan="7" class="text-center text-muted">
                                                @lang('lang_v1.no_data_found')</td>
                                        </tr>
                                        @endforelse
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">
                                                @lang('lang_v1.no_data_found')</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-gray">
                                            <td colspan="2"><strong>@lang('sale.total'):</strong></td>
                                            <td><strong>{{ number_format($total_current_stock, 2) }} {{
                                                    $unit->short_name ?? 'units' }}</strong></td>
                                            <td></td>
                                            <td></td>
                                            <td class="text-right">
                                                <strong><span class="display_currency" data-currency_symbol="true">{{
                                                        number_format($total_stock_value_purchase, 2) }}</span></strong>
                                            </td>
                                            <td class="text-right">
                                                <strong><span class="display_currency" data-currency_symbol="true">{{
                                                        number_format($total_stock_value_sale, 2) }}</span></strong>
                                            </td>
                                            <td
                                                class="text-right {{ $total_potential_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                                <strong><span class="display_currency" data-currency_symbol="true">{{
                                                        number_format($total_potential_profit, 2) }}</span></strong>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function(){
    __currency_convert_recursively($('.stock_modal'));
  });
</script>