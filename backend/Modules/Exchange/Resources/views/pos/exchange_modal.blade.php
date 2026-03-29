<div class="modal fade" id="pos_exchange_modal" tabindex="-1" role="dialog" aria-labelledby="exchangeModalLabel">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gray dark:bg-gray-800">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="exchangeModalLabel">
                    <i class="fas fa-exchange-alt"></i> @lang('exchange::lang.add_exchange')
                </h4>
            </div>

            <div class="modal-body">
                <!-- Step 1: Search Original Transaction -->
                <div id="exchange-step-1" class="exchange-step">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-search"></i> @lang('exchange::lang.step_1_search_invoice')</h5>
                            <div class="form-group">
                                <label for="pos_exchange_invoice">@lang('exchange::lang.invoice_number'):</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="pos_exchange_invoice"
                                           placeholder="@lang('exchange::lang.invoice_number')">
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-primary" id="pos_search_exchange_invoice">
                                            <i class="fas fa-search"></i> @lang('exchange::lang.search_invoice')
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="pos_exchange_transaction_info" style="display: none;">
                                <h5>@lang('exchange::lang.original_transaction')</h5>
                                <div id="pos_exchange_transaction_details"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Select Items for Exchange -->
                <div id="exchange-step-2" class="exchange-step" style="display: none;">
                    <h5><i class="fas fa-list"></i> @lang('exchange::lang.step_2_select_items')</h5>
                    <div id="pos_exchangeable_items_table"></div>
                    <div class="text-center" style="margin-top: 20px;">
                        <button type="button" class="btn btn-success" id="pos_add_exchange_items">
                            <i class="fas fa-plus"></i> @lang('exchange::lang.add_selected_items')
                        </button>
                    </div>
                </div>

                <!-- Exchange Summary -->
                <div id="exchange-step-3" class="exchange-step" style="display: none;">
                    <h5><i class="fas fa-calculator"></i> @lang('exchange::lang.exchange_summary')</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-striped">
                                <tr>
                                    <th>@lang('exchange::lang.total_return_value'):</th>
                                    <td><span id="pos_total_return_amount" class="display_currency">0.00</span></td>
                                </tr>
                                <tr>
                                    <th>@lang('exchange::lang.total_new_value'):</th>
                                    <td><span id="pos_total_new_amount" class="display_currency">0.00</span></td>
                                </tr>
                                <tr class="bg-info">
                                    <th>@lang('exchange::lang.net_exchange_amount'):</th>
                                    <td><span id="pos_net_exchange_amount" class="display_currency">0.00</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pos_exchange_notes">@lang('exchange::lang.notes'):</label>
                                <textarea class="form-control" id="pos_exchange_notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    @lang('messages.close')
                </button>
                <button type="button" class="btn btn-primary" id="pos_complete_exchange" style="display: none;">
                    <i class="fas fa-check"></i> @lang('exchange::lang.complete_exchange')
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.exchange-step {
    min-height: 300px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-bottom: 15px;
    background: #f9f9f9;
}

.exchange-item-row {
    cursor: pointer;
}

.exchange-item-row:hover {
    background-color: #f5f5f5;
}

.exchange-item-selected {
    background-color: #d4edda !important;
    border-left: 4px solid #28a745;
}

.pos-exchange-qty-input {
    width: 80px;
    text-align: center;
}
</style>