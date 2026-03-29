/**
 * POS Exchange Functionality
 * Handles exchange operations within the POS interface
 */

$(document).ready(function() {
    // Exchange variables
    let current_exchange_transaction = null;
    let exchangeable_items = [];
    let selected_exchange_items = [];

    // Open exchange modal
    $(document).on('click', '#pos-exchange', function(e) {
        e.preventDefault();
        $('#pos_exchange_modal').modal('show');
        resetExchangeModal();
    });

    // Search for exchange transaction
    $(document).on('click', '#pos_search_exchange_invoice', function() {
        const invoice_no = $('#pos_exchange_invoice').val().trim();
        if (!invoice_no) {
            show_toastr('error', 'Please enter an invoice number', 'Error');
            return;
        }

        searchExchangeTransaction(invoice_no);
    });

    // Enter key on invoice search
    $(document).on('keypress', '#pos_exchange_invoice', function(e) {
        if (e.which === 13) {
            $('#pos_search_exchange_invoice').click();
        }
    });

    // Add exchange items to POS cart
    $(document).on('click', '#pos_add_exchange_items', function() {
        if (selected_exchange_items.length === 0) {
            show_toastr('error', 'Please select items to exchange', 'Error');
            return;
        }

        addExchangeItemsToPOS();
        $('#pos_exchange_modal').modal('hide');
    });

    // Complete exchange
    $(document).on('click', '#pos_complete_exchange', function() {
        processExchange();
    });

    // Select exchange item
    $(document).on('click', '.exchange-item-row', function() {
        const $row = $(this);
        const line_id = $row.data('line-id');

        if ($row.hasClass('exchange-item-selected')) {
            // Deselect
            $row.removeClass('exchange-item-selected');
            selected_exchange_items = selected_exchange_items.filter(item => item.line_id !== line_id);
        } else {
            // Select
            $row.addClass('exchange-item-selected');
            const qty = parseFloat($row.find('.pos-exchange-qty-input').val()) || 1;
            const max_qty = parseFloat($row.find('.pos-exchange-qty-input').attr('max'));

            if (qty > max_qty) {
                show_toastr('error', 'Quantity cannot exceed available quantity', 'Error');
                $row.find('.pos-exchange-qty-input').val(max_qty);
                return;
            }

            const item_data = {
                line_id: line_id,
                quantity: qty,
                product_name: $row.find('td:eq(0)').text(),
                unit_price: parseFloat($row.data('unit-price')),
                available_qty: max_qty
            };

            selected_exchange_items.push(item_data);
        }

        updateExchangeSummary();
    });

    // Update exchange quantity
    $(document).on('change', '.pos-exchange-qty-input', function() {
        const $input = $(this);
        const $row = $input.closest('.exchange-item-row');
        const line_id = $row.data('line-id');
        const qty = parseFloat($input.val()) || 1;
        const max_qty = parseFloat($input.attr('max'));

        if (qty > max_qty) {
            show_toastr('error', 'Quantity cannot exceed available quantity', 'Error');
            $input.val(max_qty);
            return;
        }

        // Update selected item quantity
        const selected_item = selected_exchange_items.find(item => item.line_id === line_id);
        if (selected_item) {
            selected_item.quantity = qty;
            updateExchangeSummary();
        }
    });

    /**
     * Search for exchange transaction
     */
    function searchExchangeTransaction(invoice_no) {
        $.ajax({
            url: window.exchangeRoutes.searchTransaction,
            method: 'POST',
            data: {
                invoice_no: invoice_no,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function() {
                $('#pos_search_exchange_invoice').prop('disabled', true)
                    .html('<i class="fa fa-spinner fa-spin"></i> Searching...');
            },
            success: function(response) {
                if (response.success) {
                    current_exchange_transaction = response.transaction;
                    displayExchangeTransactionDetails(response.transaction);
                    displayExchangeableItems(response.exchangeable_lines);
                    show_toastr('success', 'Invoice found successfully!', 'Success');
                } else {
                    show_toastr('error', response.message, 'Error');
                }
            },
            error: function(xhr) {
                console.error('Search error:', xhr);
                show_toastr('error', 'Error searching transaction', 'Error');
            },
            complete: function() {
                $('#pos_search_exchange_invoice').prop('disabled', false)
                    .html('<i class="fas fa-search"></i> Search Invoice');
            }
        });
    }

    /**
     * Display transaction details
     */
    function displayExchangeTransactionDetails(transaction) {
        let html = '<div class="alert alert-info">';
        html += '<strong>Invoice:</strong> ' + transaction.invoice_no + '<br>';
        html += '<strong>Customer:</strong> ' + (transaction.contact ? transaction.contact.name : 'Walk-In Customer') + '<br>';
        html += '<strong>Date:</strong> ' + formatDate(transaction.transaction_date) + '<br>';
        html += '<strong>Total:</strong> ' + formatCurrency(transaction.final_total);
        html += '</div>';

        $('#pos_exchange_transaction_details').html(html);
        $('#pos_exchange_transaction_info').show();
        $('#exchange-step-2').show();
    }

    /**
     * Display exchangeable items
     */
    function displayExchangeableItems(lines) {
        exchangeable_items = lines;

        let html = '<div class="table-responsive">';
        html += '<table class="table table-striped table-bordered">';
        html += '<thead><tr>';
        html += '<th>Product</th>';
        html += '<th>Available Qty</th>';
        html += '<th>Unit Price</th>';
        html += '<th>Exchange Qty</th>';
        html += '<th>Amount</th>';
        html += '</tr></thead><tbody>';

        $.each(lines, function(index, line) {
            const available_qty = parseFloat(line.quantity) - parseFloat(line.quantity_returned || 0);
            let product_name = line.product.name;
            if (line.variations && line.variations.name !== 'DUMMY') {
                product_name += ' - ' + line.variations.name;
            }

            html += '<tr class="exchange-item-row" data-line-id="' + line.id + '" data-unit-price="' + line.unit_price_inc_tax + '">';
            html += '<td>' + product_name + '</td>';
            html += '<td>' + available_qty + '</td>';
            html += '<td>' + formatCurrency(line.unit_price_inc_tax) + '</td>';
            html += '<td><input type="number" class="form-control pos-exchange-qty-input" min="1" max="' + available_qty + '" value="1"></td>';
            html += '<td>' + formatCurrency(line.unit_price_inc_tax) + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        $('#pos_exchangeable_items_table').html(html);
    }

    /**
     * Add exchange items to POS cart
     */
    function addExchangeItemsToPOS() {
        $.each(selected_exchange_items, function(index, item) {
            // Add as negative quantity to represent return
            const return_item = {
                product_id: getProductIdFromExchangeItem(item.line_id),
                variation_id: getVariationIdFromExchangeItem(item.line_id),
                quantity: -item.quantity, // Negative for return
                unit_price: item.unit_price,
                product_name: item.product_name + ' (Return)',
                is_exchange_return: true,
                exchange_line_id: item.line_id
            };

            // Add return item to POS cart
            addItemToPOSCart(return_item);
        });

        show_toastr('success', 'Exchange items added to cart', 'Success');
        updateExchangeSummary();
    }

    /**
     * Get product ID from exchange item
     */
    function getProductIdFromExchangeItem(line_id) {
        const line = exchangeable_items.find(item => item.id === line_id);
        return line ? line.product_id : null;
    }

    /**
     * Get variation ID from exchange item
     */
    function getVariationIdFromExchangeItem(line_id) {
        const line = exchangeable_items.find(item => item.id === line_id);
        return line ? line.variation_id : null;
    }

    /**
     * Add item to POS cart
     */
    function addItemToPOSCart(item) {
        // This function should integrate with your existing POS cart system
        // You'll need to adapt this based on your POS implementation

        // Example implementation - adapt as needed
        if (typeof pos_cart !== 'undefined' && pos_cart.addItem) {
            pos_cart.addItem(item);
        } else {
            // Alternative method - trigger existing POS add item functionality
            console.log('Adding exchange item to POS cart:', item);
        }
    }

    /**
     * Update exchange summary
     */
    function updateExchangeSummary() {
        let total_return = 0;

        $.each(selected_exchange_items, function(index, item) {
            total_return += item.quantity * item.unit_price;
        });

        $('#pos_total_return_amount').text(formatCurrency(total_return));

        // Show exchange summary if items are selected
        if (selected_exchange_items.length > 0) {
            $('#exchange-step-3').show();
            $('#pos_complete_exchange').show();
        } else {
            $('#exchange-step-3').hide();
            $('#pos_complete_exchange').hide();
        }
    }

    /**
     * Process exchange
     */
    function processExchange() {
        if (!current_exchange_transaction || selected_exchange_items.length === 0) {
            show_toastr('error', 'Please select items for exchange', 'Error');
            return;
        }

        // Prepare exchange data
        const exchange_data = {
            original_transaction_id: current_exchange_transaction.id,
            location_id: $('#location_id').val(),
            exchange_lines: selected_exchange_items.map(item => ({
                original_sell_line_id: item.line_id,
                original_quantity: item.quantity
            })),
            notes: $('#pos_exchange_notes').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        $.ajax({
            url: window.exchangeRoutes.store,
            method: 'POST',
            data: exchange_data,
            beforeSend: function() {
                $('#pos_complete_exchange').prop('disabled', true)
                    .html('<i class="fa fa-spinner fa-spin"></i> Processing...');
            },
            success: function(response) {
                if (response.success) {
                    show_toastr('success', response.message, 'Success');
                    $('#pos_exchange_modal').modal('hide');
                    resetExchangeModal();

                    // Optionally print exchange receipt
                    if (response.invoice_url) {
                        window.open(response.invoice_url, '_blank');
                    }
                } else {
                    show_toastr('error', response.message, 'Error');
                }
            },
            error: function(xhr) {
                console.error('Exchange error:', xhr);
                show_toastr('error', 'Error processing exchange', 'Error');
            },
            complete: function() {
                $('#pos_complete_exchange').prop('disabled', false)
                    .html('<i class="fas fa-check"></i> Complete Exchange');
            }
        });
    }

    /**
     * Reset exchange modal
     */
    function resetExchangeModal() {
        $('#pos_exchange_invoice').val('');
        $('#pos_exchange_transaction_info').hide();
        $('#exchange-step-2').hide();
        $('#exchange-step-3').hide();
        $('#pos_complete_exchange').hide();
        $('#pos_exchange_notes').val('');

        current_exchange_transaction = null;
        exchangeable_items = [];
        selected_exchange_items = [];

        $('#pos_exchangeable_items_table').empty();
        $('#pos_exchange_transaction_details').empty();
    }

    /**
     * Format currency
     */
    function formatCurrency(amount) {
        return __currency_trans_from_en(amount, true);
    }

    /**
     * Format date
     */
    function formatDate(date) {
        if (!date) return 'N/A';
        try {
            return new Date(date).toLocaleDateString();
        } catch (e) {
            return date.toString().split(' ')[0];
        }
    }

    /**
     * Show toastr notification
     */
    function show_toastr(type, message, title) {
        if (typeof toastr !== 'undefined') {
            toastr[type](message, title);
        } else {
            alert(title + ': ' + message);
        }
    }
});