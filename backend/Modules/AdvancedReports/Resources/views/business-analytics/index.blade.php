@extends('layouts.app')

@section('title', __('advancedreports::lang.business_analytics_dashboard'))

@section('content')
<section class="content-header">
    <h1>@lang('advancedreports::lang.business_analytics_dashboard')</h1>
    <p>@lang('advancedreports::lang.view_data_usage_across_businesses')</p>
</section>

<section class="content">
    <!-- Overview Cards -->
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3 id="total_businesses">0</h3>
                    <p>@lang('advancedreports::lang.total_businesses')</p>
                </div>
                <div class="icon"><i class="fa fa-building"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="actual_db_size">0 GB</h3>
                    <p>@lang('advancedreports::lang.actual_db_size')</p>
                </div>
                <div class="icon"><i class="fa fa-database"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3 id="total_records">0</h3>
                    <p>@lang('advancedreports::lang.total_records')</p>
                </div>
                <div class="icon"><i class="fa fa-table"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="avg_business_size">0 MB</h3>
                    <p>@lang('advancedreports::lang.avg_business_size')</p>
                </div>
                <div class="icon"><i class="fa fa-calculator"></i></div>
            </div>
        </div>
    </div>

    <!-- Main Data Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-chart-bar"></i> @lang('advancedreports::lang.business_data_analytics')
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-success btn-sm" id="export_excel">
                            <i class="fa fa-download"></i> @lang('advancedreports::lang.export_excel')
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" id="print_table">
                            <i class="fa fa-print"></i> @lang('advancedreports::lang.print')
                        </button>
                        <button type="button" class="btn btn-info btn-sm" id="refresh_data">
                            <i class="fa fa-refresh"></i> @lang('advancedreports::lang.refresh')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="business_analytics_table">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.business_name')</th>
                                    <th>@lang('advancedreports::lang.created_at')</th>
                                    <th>@lang('advancedreports::lang.users')</th>
                                    <th>@lang('advancedreports::lang.products')</th>
                                    <th>@lang('advancedreports::lang.sales')</th>
                                    <th>@lang('advancedreports::lang.purchases')</th>
                                    <th>@lang('advancedreports::lang.contacts')</th>
                                    <th>@lang('advancedreports::lang.locations')</th>
                                    <th>@lang('advancedreports::lang.total_records')</th>
                                    <th>@lang('advancedreports::lang.size_mb')</th>
                                    <th>@lang('advancedreports::lang.top_tables')</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- @lang('advancedreports::lang.data_populated_by_js') -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="overlay" id="loading_overlay" style="display: none;">
                    <i class="fa fa-refresh fa-spin"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Status color classes for table cells */
.status-success {
    background-color: #d4edda !important;
    color: #155724;
}

.status-warning {
    background-color: #fff3cd !important;
    color: #856404;
}

.status-danger {
    background-color: #f8d7da !important;
    color: #721c24;
}

/* Tag styling for top tables */
.table-tag {
    display: inline-block;
    padding: 2px 6px;
    margin: 1px;
    background-color: #f0f0f0;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 11px;
    white-space: nowrap;
}

.table-tag.high-count {
    background-color: #ffebee;
    border-color: #e57373;
    color: #d32f2f;
}

.table-tag.medium-count {
    background-color: #fff3e0;
    border-color: #ffb74d;
    color: #ef6c00;
}

.table-tag.low-count {
    background-color: #e8f5e8;
    border-color: #81c784;
    color: #388e3c;
}

/* Loading overlay */
.overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.8);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
}

/* Responsive table improvements */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 12px;
    }
    
    .table-tag {
        font-size: 10px;
        padding: 1px 4px;
    }
}
</style>

@endsection

@section('javascript')
<script>
$(document).ready(function() {
    let businessAnalyticsTable;
    
    // Initialize DataTable
    initializeDataTable();
    
    // Load initial data
    loadSummaryData();
    loadTableData();
    
    // Event handlers
    $('#refresh_data').click(function() {
        loadSummaryData();
        loadTableData();
    });
    
    $('#export_excel').click(function() {
        exportToExcel();
    });
    
    $('#print_table').click(function() {
        printTable();
    });
    
    function initializeDataTable() {
        businessAnalyticsTable = $('#business_analytics_table').DataTable({
            processing: true,
            ordering: true,
            searching: true,
            paging: true,
            info: true,
            responsive: true,
            pageLength: 25,
            order: [[8, 'desc']], // Order by Total Records descending
            columnDefs: [
                {
                    targets: [2, 3, 4, 5, 6, 7, 8], // Numeric columns
                    className: 'text-right'
                },
                {
                    targets: [9], // Size column
                    className: 'text-right'
                }
            ],
            language: {
                processing: "@lang('advancedreports::lang.loading_business_analytics')",
                emptyTable: "@lang('advancedreports::lang.no_business_data_available')",
                zeroRecords: "@lang('advancedreports::lang.no_matching_businesses_found')"
            }
        });
    }
    
    function loadSummaryData() {
        $.ajax({
            url: '{{ route("advancedreports.business-analytics.summary") }}',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $('#total_businesses').text(response.total_businesses);
                $('#actual_db_size').text(response.actual_db_size);
                $('#total_records').text(response.total_records);
                $('#avg_business_size').text(response.avg_business_size);
            },
            error: function(xhr, status, error) {
                console.error("@lang('advancedreports::lang.failed_to_load_summary_data'):", error);
                toastr.error("@lang('advancedreports::lang.failed_to_load_summary_data')");
            }
        });
    }
    
    function loadTableData() {
        showLoading(true);
        
        $.ajax({
            url: '{{ route("advancedreports.business-analytics.data") }}',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                populateTable(response.data);
                showLoading(false);
            },
            error: function(xhr, status, error) {
                console.error("@lang('advancedreports::lang.failed_to_load_table_data'):", error);
                toastr.error("@lang('advancedreports::lang.failed_to_load_business_analytics_data')");
                showLoading(false);
            }
        });
    }
    
    function populateTable(data) {
        businessAnalyticsTable.clear();
        
        data.forEach(function(row) {
            // Format top tables as tags
            let topTablesHtml = '';
            row.top_tables.forEach(function(table, index) {
                let tagClass = 'table-tag';
                if (table.count > 50000) {
                    tagClass += ' high-count';
                } else if (table.count > 10000) {
                    tagClass += ' medium-count';
                } else {
                    tagClass += ' low-count';
                }
                
                topTablesHtml += `<span class="${tagClass}">${table.name} (${formatNumber(table.count)})</span>`;
                if (index < row.top_tables.length - 1) {
                    topTablesHtml += ' ';
                }
            });
            
            businessAnalyticsTable.row.add([
                row.business_name,
                row.created_at,
                formatNumber(row.users_count),
                formatNumber(row.products_count),
                formatNumber(row.sales_count),
                formatNumber(row.purchase_count),
                formatNumber(row.contacts_count),
                formatNumber(row.locations_count),
                formatNumber(row.total_records),
                row.actual_size_mb,
                topTablesHtml
            ]);
        });
        
        businessAnalyticsTable.draw();
        
        // Apply status colors after drawing
        applyStatusColors(data);
    }
    
    function applyStatusColors(data) {
        data.forEach(function(row, index) {
            const tableRow = businessAnalyticsTable.row(index).node();
            
            // Apply color to Total Records column (index 8)
            $(tableRow).find('td:eq(8)').addClass('status-' + row.records_status);
            
            // Apply color to Size column (index 9)
            $(tableRow).find('td:eq(9)').addClass('status-' + row.size_status);
            
            // Apply color to Top Tables column (index 10) based on number of tables
            if (row.top_tables_status === 'warning') {
                $(tableRow).find('td:eq(10)').addClass('status-warning');
            }
        });
    }
    
    function showLoading(show) {
        if (show) {
            $('#loading_overlay').show();
        } else {
            $('#loading_overlay').hide();
        }
    }
    
    function formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }
    
    function exportToExcel() {
        showLoading(true);

        var params = {
            _token: '{{ csrf_token() }}'
        };

        // Use AJAX to download the file properly
        $.ajax({
            url: '{{ route("advancedreports.business-analytics.export") }}',
            type: 'POST',
            data: params,
            xhrFields: {
                responseType: 'blob'
            },
            success: function(data, status, xhr) {
                // Create blob link to download
                var blob = new Blob([data], {
                    type: xhr.getResponseHeader('Content-Type') || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                });

                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);

                // Get filename from response header or use default
                var filename = 'business-analytics-report.xlsx';
                var disposition = xhr.getResponseHeader('Content-Disposition');
                if (disposition && disposition.indexOf('attachment') !== -1) {
                    var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                    var matches = filenameRegex.exec(disposition);
                    if (matches != null && matches[1]) {
                        filename = matches[1].replace(/['"]/g, '');
                    }
                }
                link.download = filename;

                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // Clean up
                window.URL.revokeObjectURL(link.href);
            },
            error: function(xhr, status, error) {
                alert("@lang('advancedreports::lang.export_failed'): " + error);
            },
            complete: function() {
                setTimeout(function() {
                    showLoading(false);
                }, 1000);
            }
        });
    }
    
    function printTable() {
        // Create a new window for printing
        const printWindow = window.open('', '_blank');
        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>@lang('advancedreports::lang.business_analytics_dashboard')</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    table { border-collapse: collapse; width: 100%; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    .status-success { background-color: #d4edda; }
                    .status-warning { background-color: #fff3cd; }
                    .status-danger { background-color: #f8d7da; }
                    .table-tag { 
                        display: inline-block; 
                        padding: 2px 6px; 
                        margin: 1px;
                        background-color: #f0f0f0; 
                        border: 1px solid #ddd; 
                        border-radius: 3px; 
                        font-size: 11px; 
                    }
                    .high-count { background-color: #ffebee; border-color: #e57373; }
                    .medium-count { background-color: #fff3e0; border-color: #ffb74d; }
                    .low-count { background-color: #e8f5e8; border-color: #81c784; }
                    @media print {
                        body { -webkit-print-color-adjust: exact; }
                    }
                </style>
            </head>
            <body>
                <h1>@lang('advancedreports::lang.business_analytics_dashboard')</h1>
                <p>@lang('advancedreports::lang.generated_on'): ${new Date().toLocaleString()}</p>
                ${$('#business_analytics_table')[0].outerHTML}
            </body>
            </html>
        `;
        
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 250);
    }
});
</script>
@endsection