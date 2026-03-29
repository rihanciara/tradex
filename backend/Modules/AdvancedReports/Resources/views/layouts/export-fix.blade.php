{{-- Export Utility Functions --}}
<script>
/**
 * Universal export function that works with all AdvancedReports exports
 * This fixes the issue where exports open as raw data instead of downloading
 */
function handleExport(url, data, buttonSelector) {
    var $btn = $(buttonSelector);
    var originalText = $btn.html();

    // Show loading state
    $btn.html('<i class="fa fa-spinner fa-spin"></i> Exporting...').prop('disabled', true);

    // Create a form and submit it as POST
    var form = $('<form>', {
        'method': 'POST',
        'action': url,
        'target': '_blank'
    });

    // Add CSRF token
    form.append($('<input>', {
        'type': 'hidden',
        'name': '_token',
        'value': '{{ csrf_token() }}'
    }));

    // Add data fields
    $.each(data, function(key, value) {
        if (value !== null && value !== '') {
            form.append($('<input>', {
                'type': 'hidden',
                'name': key,
                'value': value
            }));
        }
    });

    // Submit the form
    form.appendTo('body').submit().remove();

    // Reset button
    setTimeout(function() {
        $btn.html(originalText).prop('disabled', false);
    }, 3000);
}

/**
 * Legacy fallback for GET-based exports (converts them to POST)
 */
function fixExportButton(buttonSelector, exportUrl, dataCallback) {
    $(document).on('click', buttonSelector, function(e) {
        e.preventDefault();

        var data = {};
        if (typeof dataCallback === 'function') {
            data = dataCallback();
        }

        handleExport(exportUrl, data, buttonSelector);
    });
}
</script>