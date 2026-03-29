<script>
// Exchange routes for POS functionality
window.exchangeRoutes = {
    searchTransaction: "{{ route('exchange.search_transaction') }}",
    store: "{{ route('exchange.store') }}",
    show: "{{ url('exchange') }}/"
};

// Exchange translations for POS
window.exchangeLang = {
    exchange: "@lang('exchange::lang.exchange')",
    add_exchange: "@lang('exchange::lang.add_exchange')",
    invoice_number: "@lang('exchange::lang.invoice_number')",
    search_invoice: "@lang('exchange::lang.search_invoice')",
    add_selected_items: "@lang('exchange::lang.add_selected_items')",
    complete_exchange: "@lang('exchange::lang.complete_exchange')",
    step_1_search_invoice: "@lang('exchange::lang.step_1_search_invoice')",
    step_2_select_items: "@lang('exchange::lang.step_2_select_items')",
    step_3_select_new_items: "@lang('exchange::lang.step_3_select_new_items')",
    exchange_summary: "@lang('exchange::lang.exchange_summary')",
    total_return_value: "@lang('exchange::lang.total_return_value')",
    total_new_value: "@lang('exchange::lang.total_new_value')",
    net_exchange_amount: "@lang('exchange::lang.net_exchange_amount')",
    notes: "@lang('exchange::lang.notes')",
    original_transaction: "@lang('exchange::lang.original_transaction')",
    transaction_not_found: "@lang('exchange::lang.transaction_not_found')",
    no_items_available_for_exchange: "@lang('exchange::lang.no_items_available_for_exchange')",
    exchange_completed_successfully: "@lang('exchange::lang.exchange_completed_successfully')"
};
</script>