@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.expense_monthly_report'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('advancedreports::lang.expense_monthly_report')
        <small>@lang('advancedreports::lang.expense_breakdown_description')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', [
            'title' => __('report.filters'),
            'class' => 'box-primary'
            ])

            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'location_filter']); !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('category_id', __('expense.expense_category') . ':') !!}
                    {!! Form::select('category_id', $expense_categories, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'category_filter']); !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('created_by', __('advancedreports::lang.staff') . ':') !!}
                    {!! Form::select('created_by', $users, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'id' => 'user_filter']); !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('expense_for', __('expense.expense_for') . ':') !!}
                    {!! Form::select('expense_for', [
                    '' => __('lang_v1.all'),
                    auth()->user()->id => auth()->user()->username
                    ], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' =>
                    'expense_for_filter']); !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    {!! Form::label('year', __('Year') . ':') !!}
                    {!! Form::select('year', array_combine(range(date('Y')-20, date('Y')+10), range(date('Y')-20,
                    date('Y')+10)), date('Y'), ['class' => 'form-control select2', 'style' => 'width:100%', 'id' =>
                    'year_filter']); !!}
                </div>
            </div>
            <div class="col-md-12" style="margin-top: 10px;">
                <div class="form-group">
                    <button type="button" class="btn btn-primary" id="filter_btn">
                        <i class="fa fa-filter"></i> @lang('advancedreports::lang.filter')
                    </button>
                    <button type="button" class="btn btn-success" id="export_btn">
                        <i class="fa fa-download"></i> @lang('lang_v1.export')
                    </button>
                    <button type="button" class="btn btn-info" id="print_btn">
                        <i class="fa fa-print"></i> @lang('messages.print')
                    </button>
                    <button type="button" class="btn btn-info" id="refresh_btn">
                        <i class="fa fa-refresh"></i> @lang('lang_v1.refresh')
                    </button>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row" id="summary_cards">
        <!-- Total Categories -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-categories">
                <i class="fa fa-tags modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">@lang('advancedreports::lang.total_categories')</div>
                    <div class="modern-widget-number" id="total_categories">0</div>
                </div>
            </div>
        </div>

        <!-- Total Transactions -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-transactions">
                <i class="fa fa-shopping-cart modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">@lang('advancedreports::lang.total_transactions')</div>
                    <div class="modern-widget-number" id="total_transactions">0</div>
                </div>
            </div>
        </div>

        <!-- Total Expenses -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-expenses">
                <i class="fa fa-money modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">@lang('advancedreports::lang.total_expenses')</div>
                    <div class="modern-widget-number" id="total_expenses"><span class="display_currency" data-currency_symbol="true">0</span></div>
                </div>
            </div>
        </div>

        <!-- Average Expense -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-average">
                <i class="fa fa-calculator modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">@lang('advancedreports::lang.average_expense')</div>
                    <div class="modern-widget-number" id="average_expense"><span class="display_currency" data-currency_symbol="true">0</span></div>
                </div>
            </div>
        </div>

        <!-- Average per Category -->
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <div class="modern-widget widget-category-avg">
                <i class="fa fa-bar-chart modern-widget-icon"></i>
                <div class="modern-widget-content">
                    <div class="modern-widget-text">@lang('advancedreports::lang.avg_per_category')</div>
                    <div class="modern-widget-number" id="avg_per_category"><span class="display_currency" data-currency_symbol="true">0</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' =>
            __('advancedreports::lang.expense_monthly_report')])
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view colored-header" id="expense_monthly_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('expense.expense_category')</th>
                            <th>@lang('advancedreports::lang.jan')</th>
                            <th>@lang('advancedreports::lang.feb')</th>
                            <th>@lang('advancedreports::lang.mar')</th>
                            <th>@lang('advancedreports::lang.apr')</th>
                            <th>@lang('advancedreports::lang.may')</th>
                            <th>@lang('advancedreports::lang.jun')</th>
                            <th>@lang('advancedreports::lang.jul')</th>
                            <th>@lang('advancedreports::lang.aug')</th>
                            <th>@lang('advancedreports::lang.sep')</th>
                            <th>@lang('advancedreports::lang.oct')</th>
                            <th>@lang('advancedreports::lang.nov')</th>
                            <th>@lang('advancedreports::lang.dec')</th>
                            <th>@lang('advancedreports::lang.total_expense')</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 text-center footer-total">
                            <td colspan="2"><strong>@lang('sale.total'):</strong></td>
                            <td class="footer_jan"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_feb"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_mar"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_apr"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_may"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_jun"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_jul"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_aug"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_sep"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_oct"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_nov"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_dec"><span class="display_currency" data-currency_symbol="true">0</span>
                            </td>
                            <td class="footer_total_expense"><span class="display_currency"
                                    data-currency_symbol="true">0</span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endcomponent
        </div>
    </div>

    <!-- Category Details Modal -->
    <div class="modal fade category_details_modal" tabindex="-1" role="dialog"
        aria-labelledby="categoryDetailsModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="categoryDetailsModalLabel">@lang('advancedreports::lang.category_details')</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="category_details_content">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin fa-3x text-muted"></i>
                        <p class="text-muted">@lang('advancedreports::lang.loading_category_details')</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                </div>
            </div>
        </div>
    </div>

</section>
<!-- /.content -->

@stop

@section('javascript')
<script type="text/javascript">
    // Currency formatting function
    function formatCurrency(num) {
        if (num === null || num === undefined || num === '') {
            return __currency_trans_from_en('0.00', true);
        }
        
        if (typeof num === 'number') {
            return __currency_trans_from_en(num.toFixed(2), true);
        }
        
        if (typeof num === 'string') {
            var cleanStr = num.replace(/[^\d.-]/g, '');
            var parsed = parseFloat(cleanStr);
            
            if (!isNaN(parsed)) {
                return __currency_trans_from_en(parsed.toFixed(2), true);
            }
        }
        
        console.warn('formatCurrency received invalid input:', num, typeof num);
        return __currency_trans_from_en('0.00', true);
    }

    function formatNumber(num) {
        if (num === null || num === undefined || num === '') {
            return '0';
        }
        
        if (typeof num === 'number') {
            return num.toLocaleString('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            });
        }
        
        if (typeof num === 'string') {
            var cleanStr = num.replace(/[^\d.-]/g, '');
            var parsed = parseFloat(cleanStr);
            
            if (!isNaN(parsed)) {
                return parsed.toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });
            }
        }
        
        console.warn('formatNumber received invalid input:', num, typeof num);
        return '0';
    }

    $(document).ready(function() {
        // Currency column detection
        jQuery.extend(jQuery.fn.dataTableExt.oSort, {
            "currency-asc": function (a, b) {
                var aText = typeof a === 'string' ? a : $(a).text();
                var bText = typeof b === 'string' ? b : $(b).text();
                
                var x = parseFloat(aText.replace(/[^\d.-]/g, '')) || 0;
                var y = parseFloat(bText.replace(/[^\d.-]/g, '')) || 0;
                
                return x - y;
            },
            "currency-desc": function (a, b) {
                var aText = typeof a === 'string' ? a : $(a).text();
                var bText = typeof b === 'string' ? b : $(b).text();
                
                var x = parseFloat(aText.replace(/[^\d.-]/g, '')) || 0;
                var y = parseFloat(bText.replace(/[^\d.-]/g, '')) || 0;
                
                return y - x;
            }
        });

        $.fn.dataTable.ext.type.detect.unshift(function (data) {
            if (typeof data === 'string' && (
                data.match(/^[\$£€¥₹]/) || 
                data.match(/[\$£€¥₹]$/) || 
                data.match(/^\d{1,3}(,\d{3})*(\.\d+)?$/) || 
                data.match(/\d+\.\d+/) || 
                data.match(/%$/)
            )) {
                return 'currency';
            }
            return null;
        });

        // Initialize DataTable
        var expense_monthly_table = $('#expense_monthly_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.expense-monthly.data') }}",
                data: function (d) {
                    d.location_id = $('#location_filter').val();
                    d.category_id = $('#category_filter').val();
                    d.created_by = $('#user_filter').val();
                    d.expense_for = $('#expense_for_filter').val();
                    d.year = $('#year_filter').val();
                }
            },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false, width: '80px' },
                { data: 'category_name', name: 'category_name', width: '250px' },
                { data: 'jan', name: 'jan', searchable: false, width: '100px' },
                { data: 'feb', name: 'feb', searchable: false, width: '100px' },
                { data: 'mar', name: 'mar', searchable: false, width: '100px' },
                { data: 'apr', name: 'apr', searchable: false, width: '100px' },
                { data: 'may', name: 'may', searchable: false, width: '100px' },
                { data: 'jun', name: 'jun', searchable: false, width: '100px' },
                { data: 'jul', name: 'jul', searchable: false, width: '100px' },
                { data: 'aug', name: 'aug', searchable: false, width: '100px' },
                { data: 'sep', name: 'sep', searchable: false, width: '100px' },
                { data: 'oct', name: 'oct', searchable: false, width: '100px' },
                { data: 'nov', name: 'nov', searchable: false, width: '100px' },
                { data: 'dec', name: 'dec', searchable: false, width: '100px' },
                { data: 'total_expense', name: 'total_expense', searchable: false, width: '120px' }
            ],
            columnDefs: [
                {
                    targets: [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14],
                    type: 'currency'
                }
            ],
            order: [[14, 'desc']],
            scrollX: true,
            autoWidth: false,

            "fnDrawCallback": function (oSettings) {
                __currency_convert_recursively($('#expense_monthly_table'));
                
                // Calculate footer totals
                var api = this.api();
                
                var months = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
                var monthTotals = {};
                
                // Initialize month totals
                months.forEach(function(month) {
                    monthTotals[month] = 0;
                });
                
                // Calculate monthly totals
                months.forEach(function(month, index) {
                    var columnIndex = index + 2;
                    
                    api.column(columnIndex, {page: 'current'}).data().each(function(value) {
                        try {
                            var numericValue = 0;
                            
                            if (typeof value === 'string') {
                                var tempDiv = document.createElement('div');
                                tempDiv.innerHTML = value;
                                var textValue = tempDiv.textContent || tempDiv.innerText || '';
                                var cleanValue = textValue.replace(/[$,\s]/g, '').replace(/[^\d.-]/g, '');
                                numericValue = parseFloat(cleanValue) || 0;
                            } else if (typeof value === 'number') {
                                numericValue = value;
                            } else if (value && typeof value === 'object') {
                                var textValue = $(value).text() || '';
                                var cleanValue = textValue.replace(/[$,\s]/g, '').replace(/[^\d.-]/g, '');
                                numericValue = parseFloat(cleanValue) || 0;
                            }
                            
                            monthTotals[month] += numericValue;
                            
                        } catch (e) {
                            console.warn('Error parsing month value for ' + month + ':', value, e);
                        }
                    });
                });
                
                // Calculate total expenses
                var total_expense_sum = 0;
                api.column(14, {page: 'current'}).data().each(function(value) {
                    try {
                        var numericValue = 0;
                        
                        if (typeof value === 'string') {
                            var tempDiv = document.createElement('div');
                            tempDiv.innerHTML = value;
                            var textValue = tempDiv.textContent || tempDiv.innerText || '';
                            var cleanValue = textValue.replace(/[$,\s]/g, '').replace(/[^\d.-]/g, '');
                            numericValue = parseFloat(cleanValue) || 0;
                        } else if (typeof value === 'number') {
                            numericValue = value;
                        } else if (value && typeof value === 'object') {
                            var textValue = $(value).text() || '';
                            var cleanValue = textValue.replace(/[$,\s]/g, '').replace(/[^\d.-]/g, '');
                            numericValue = parseFloat(cleanValue) || 0;
                        }
                        
                        total_expense_sum += numericValue;
                        
                    } catch (e) {
                        console.warn('Error parsing total expense value:', value, e);
                    }
                });

                // Update footer
                $('.footer_jan').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.jan || 0).toFixed(2) + '</span>');
                $('.footer_feb').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.feb || 0).toFixed(2) + '</span>');
                $('.footer_mar').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.mar || 0).toFixed(2) + '</span>');
                $('.footer_apr').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.apr || 0).toFixed(2) + '</span>');
                $('.footer_may').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.may || 0).toFixed(2) + '</span>');
                $('.footer_jun').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.jun || 0).toFixed(2) + '</span>');
                $('.footer_jul').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.jul || 0).toFixed(2) + '</span>');
                $('.footer_aug').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.aug || 0).toFixed(2) + '</span>');
                $('.footer_sep').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.sep || 0).toFixed(2) + '</span>');
                $('.footer_oct').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.oct || 0).toFixed(2) + '</span>');
                $('.footer_nov').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.nov || 0).toFixed(2) + '</span>');
                $('.footer_dec').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(monthTotals.dec || 0).toFixed(2) + '</span>');
                $('.footer_total_expense').html('<span class="display_currency" data-currency_symbol="true">' + Math.abs(total_expense_sum || 0).toFixed(2) + '</span>');
                
                __currency_convert_recursively($('.footer-total'));
            },
            createdRow: function( row, data, dataIndex ) {
                for(var i = 2; i <= 14; i++) {
                    $(row).find('td:eq(' + i + ')').addClass('text-right');
                }
            }
        });

        // Filter button click
        $('#filter_btn').click(function() {
            expense_monthly_table.ajax.reload();
            loadSummary();
        });

        // Refresh button click
        $('#refresh_btn').click(function() {
            expense_monthly_table.ajax.reload();
            loadSummary();
        });

        // Export button click
        $('#export_btn').click(function(e) {
            e.preventDefault();

            var $btn = $(this);
            var originalText = $btn.html();
            $btn.html('<i class="fa fa-spinner fa-spin"></i> @lang("advancedreports::lang.exporting")').prop('disabled', true);

            var params = {
                location_id: $('#location_filter').val() || '',
                category_id: $('#category_filter').val() || '',
                created_by: $('#user_filter').val() || '',
                expense_for: $('#expense_for_filter').val() || '',
                year: $('#year_filter').val() || '',
                _token: '{{ csrf_token() }}'
            };

            // Use AJAX to download the file properly
            $.ajax({
                url: "{{ route('advancedreports.expense-monthly.export') }}",
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
                    var filename = 'expense-monthly-report.xlsx';
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
                    alert('Export failed: ' + error);
                },
                complete: function() {
                    setTimeout(function() {
                        $btn.html(originalText).prop('disabled', false);
                    }, 1000);
                }
            });
        });

        // Print button click
        $('#print_btn').click(function(e) {
            e.preventDefault();
            
            var location_id = $('#location_filter').val() || '';
            var category_id = $('#category_filter').val() || '';
            var created_by = $('#user_filter').val() || '';
            var expense_for = $('#expense_for_filter').val() || '';
            var year = $('#year_filter').val() || '';
            
            var $btn = $(this);
            var originalText = $btn.html();
            $btn.html('<i class="fa fa-spinner fa-spin"></i> @lang("advancedreports::lang.preparing_print")').prop('disabled', true);
            
            // Create print URL with parameters
            var printUrl = "{{ route('advancedreports.expense-monthly.print') }}" + '?' + $.param({
                location_id: location_id,
                category_id: category_id,
                created_by: created_by,
                expense_for: expense_for,
                year: year
            });
            
            // Open print window
            var printWindow = window.open(printUrl, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
            
            // Reset button after a short delay
            setTimeout(function() {
                $btn.html(originalText).prop('disabled', false);
            }, 2000);
            
            // Focus on print window
            if (printWindow) {
                printWindow.focus();
            }
        });

        // Load summary data
        function loadSummary() {
            var params = {
                location_id: $('#location_filter').val(),
                category_id: $('#category_filter').val(),
                created_by: $('#user_filter').val(),
                expense_for: $('#expense_for_filter').val(),
                year: $('#year_filter').val()
            };
            
            $.ajax({
                url: "{{ route('advancedreports.expense-monthly.summary') }}",
                data: params,
                dataType: 'json',
                success: function(data) {
                    var totalCategories = parseInt(data.total_categories) || 0;
                    var totalTransactions = parseInt(data.total_transactions) || 0;
                    var totalExpenses = parseFloat(data.total_expense) || 0;
                    var averageExpense = parseFloat(data.average_expense) || 0;
                    var avgPerCategory = parseFloat(data.average_per_category) || 0;
                    
                    $('#total_categories').text(formatNumber(totalCategories));
                    $('#total_transactions').text(formatNumber(totalTransactions));
                    $('#total_expenses').text(formatCurrency(totalExpenses));
                    $('#average_expense').text(formatCurrency(averageExpense));
                    $('#avg_per_category').text(formatCurrency(avgPerCategory));
                    
                    $('#summary_cards').show();
                },
                error: function(xhr, status, error) {
                    console.error('Error loading summary data:', error);
                    
                    $('#total_categories').text('0');
                    $('#total_transactions').text('0');
                    $('#total_expenses').text(formatCurrency(0));
                    $('#average_expense').text(formatCurrency(0));
                    $('#avg_per_category').text(formatCurrency(0));
                }
            });
        }

        // Auto-filter on change
        $('#location_filter, #category_filter, #user_filter, #expense_for_filter, #year_filter').change(function() {
            expense_monthly_table.ajax.reload();
            loadSummary();
        });

        // Category details modal
        $(document).on('click', '.view-category-details', function(e) {
            e.preventDefault();
            var categoryId = $(this).data('category-id');
            var year = $('#year_filter').val() || new Date().getFullYear();
            
            $('.category_details_modal').modal('show');
            $('#category_details_content').html(`
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-3x text-muted"></i>
                    <p class="text-muted">@lang('advancedreports::lang.loading_category_details')</p>
                </div>
            `);
            
            $.ajax({
                url: "{{ route('advancedreports.expense-monthly.details', '') }}/" + categoryId,
                method: 'GET',
                data: { year: year },
                timeout: 30000,
                success: function(response) {
                    if (response && response.category) {
                        var category = response.category;
                        var expenses = response.expenses || [];
                        
                        var html = `
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="box box-info">
                                        <div class="box-header with-border">
                                            <h3 class="box-title"><i class="fa fa-tag"></i> @lang('advancedreports::lang.category_information')</h3>
                                        </div>
                                        <div class="box-body">
                                            <table class="table table-striped">
                                                <tr><td><strong>@lang('advancedreports::lang.name'):</strong></td><td>${category.name || 'N/A'}</td></tr>
                                                <tr><td><strong>@lang('advancedreports::lang.description'):</strong></td><td>${category.description || 'N/A'}</td></tr>
                                                <tr><td><strong>@lang('advancedreports::lang.id'):</strong></td><td>${category.id || 'N/A'}</td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="box box-warning">
                                        <div class="box-header with-border">
                                            <h3 class="box-title"><i class="fa fa-bar-chart"></i> ${year} @lang('advancedreports::lang.summary')</h3>
                                        </div>
                                        <div class="box-body">
                                            <div class="row text-center">
                                                <div class="col-md-6">
                                                    <div class="description-block border-right">
                                                        <span class="description-percentage text-red"><i class="fa fa-money"></i></span>
                                                        <h5 class="description-header">${expenses.length || 0}</h5>
                                                        <span class="description-text">@lang('advancedreports::lang.total_transactions')</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="description-block">
                                                        <span class="description-percentage text-yellow"><i class="fa fa-calculator"></i></span>
                                                        <h5 class="description-header">${formatCurrency(calculateTotalAmount(expenses))}</h5>
                                                        <span class="description-text">@lang('advancedreports::lang.total_expense')</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row text-center" style="margin-top: 15px;">
                                                <div class="col-md-12">
                                                    <div class="description-block">
                                                        <span class="description-percentage text-blue"><i class="fa fa-line-chart"></i></span>
                                                        <h5 class="description-header">${formatCurrency(calculateAverageTransaction(expenses))}</h5>
                                                        <span class="description-text">@lang('advancedreports::lang.avg_per_transaction')</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Add expenses table if there are expenses
                        if (expenses && expenses.length > 0) {
                            html += `
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="box box-primary">
                                            <div class="box-header with-border">
                                                <h3 class="box-title"><i class="fa fa-list"></i> @lang('advancedreports::lang.recent_expenses')</h3>
                                                <div class="box-tools pull-right">
                                                    <span class="label label-primary">${expenses.length} @lang('advancedreports::lang.transactions')</span>
                                                </div>
                                            </div>
                                            <div class="box-body" style="max-height: 400px; overflow-y: auto;">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-bordered table-hover table-condensed">
                                                        <thead style="position: sticky; top: 0; background: #f5f5f5; z-index: 10;">
                                                            <tr>
                                                                <th>@lang('messages.date')</th>
                                                                <th>@lang('advancedreports::lang.reference')</th>
                                                                <th>@lang('sale.amount')</th>
                                                                <th>@lang('sale.type')</th>
                                                                <th>@lang('advancedreports::lang.month')</th>
                                                                <th>@lang('report.created_by')</th>
                                                                <th>@lang('advancedreports::lang.notes')</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                            `;
                            
                            expenses.forEach(function(expense, index) {
                                var date = new Date(expense.transaction_date).toLocaleDateString();
                                var typeClass = expense.type == 'expense_refund' ? 'label-success' : 'label-danger';
                                var typeText = expense.type == 'expense_refund' ? '@lang("advancedreports::lang.refund")' : '@lang("expense.expense")';
                                var rowClass = index % 2 === 0 ? 'even-row' : 'odd-row';
                                
                                html += `
                                    <tr class="${rowClass}" style="font-size: 12px;">
                                        <td>${date}</td>
                                        <td><strong>${expense.ref_no || 'N/A'}</strong></td>
                                        <td class="text-right"><strong>${formatCurrency(expense.amount || 0)}</strong></td>
                                        <td><span class="label ${typeClass}" style="font-size: 10px;">${typeText}</span></td>
                                        <td><span class="label label-info" style="font-size: 10px;">${expense.month_name || 'N/A'}</span></td>
                                        <td>${expense.created_by || 'N/A'}</td>
                                        <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;">${expense.additional_notes || ''}</td>
                                    </tr>
                                `;
                            });
                            
                            html += `
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="box-footer">
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <small class="text-muted">
                                                            <i class="fa fa-info-circle"></i>
                                                            @lang('advancedreports::lang.showing_recent') ${expenses.length} @lang('advancedreports::lang.transactions_for') ${year}
                                                        </small>
                                                    </div>
                                                    <div class="col-sm-6 text-right">
                                                        <small class="text-muted">
                                                            @lang('sale.total'): <strong>${formatCurrency(calculateTotalAmount(expenses))}</strong>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else {
                            html += `
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i> @lang('advancedreports::lang.no_expense_transactions_found') ${year}.
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                        
                        $('#category_details_content').html(html);
                        $('#categoryDetailsModalLabel').text('@lang("advancedreports::lang.category_details") - ' + (category.name || 'Unknown') + ' (' + year + ')');

                    } else {
                        $('#category_details_content').html(`
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i> @lang('advancedreports::lang.no_category_data_found')
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);

                    var errorMessage = '@lang("advancedreports::lang.error_loading_category_details")';

                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMessage = xhr.responseJSON.error;
                    } else if (xhr.status === 404) {
                        errorMessage = '@lang("advancedreports::lang.category_not_found")';
                    } else if (xhr.status === 403) {
                        errorMessage = '@lang("advancedreports::lang.access_denied")';
                    } else if (xhr.status === 500) {
                        errorMessage = '@lang("advancedreports::lang.server_error_occurred")';
                    }

                    $('#category_details_content').html(`
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-triangle"></i> ${errorMessage}
                            <br><small>@lang('advancedreports::lang.please_try_again_or_contact_admin')</small>
                        </div>
                    `);
                }
            });
        });

        // Helper functions for calculations
        function calculateTotalAmount(expenses) {
            var total = 0;
            expenses.forEach(function(e) {
                total += parseFloat(e.amount || 0);
            });
            return total;
        }

        function calculateAverageTransaction(expenses) {
            if (expenses.length === 0) return 0;
            return calculateTotalAmount(expenses) / expenses.length;
        }

        // Load initial summary
        loadSummary();
    });
</script>

<style>
    /* Modern Widget Styling for Expense Report */
    .modern-widget {
        height: 95px !important;
        display: flex !important;
        align-items: center !important;
        padding: 18px !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12) !important;
        transition: all 0.3s ease !important;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
        color: white;
        border: none !important;
    }

    .modern-widget::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: inherit;
        opacity: 0.95;
        z-index: 1;
    }

    .modern-widget:hover {
        transform: translateY(-4px) scale(1.02) !important;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2) !important;
    }

    .modern-widget-icon {
        font-size: 45px !important;
        opacity: 1 !important;
        margin-right: 18px !important;
        width: 55px !important;
        text-align: center !important;
        flex-shrink: 0 !important;
        color: white !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        z-index: 2 !important;
        position: relative !important;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
    }

    .modern-widget-content {
        flex-grow: 1 !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        min-height: 65px !important;
        color: white !important;
        z-index: 2 !important;
        position: relative !important;
    }

    .modern-widget-text {
        font-size: 13px !important;
        opacity: 0.98 !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 1px !important;
        margin-bottom: 6px !important;
        line-height: 1.2 !important;
        color: white !important;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2) !important;
    }

    .modern-widget-number {
        font-size: 26px !important;
        font-weight: 700 !important;
        line-height: 1 !important;
        color: white !important;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2) !important;
        margin: 0 !important;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    }

    /* Widget Color Schemes for Expense Report */
    .widget-categories {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    }

    .widget-transactions {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
    }

    .widget-expenses {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%) !important;
    }

    .widget-average {
        background: linear-gradient(135deg, #feca57 0%, #ff9ff3 100%) !important;
        color: #333 !important;
    }

    .widget-average * {
        color: #333 !important;
        text-shadow: none !important;
    }

    .widget-category-avg {
        background: linear-gradient(135deg, #48dbfb 0%, #0abde3 100%) !important;
    }

    /* Force all text to be white except average */
    .modern-widget:not(.widget-average) * {
        color: white !important;
    }

    /* Table styling */
    table.colored-header thead th {
        background-color: #e74c3c !important;
        /* Red theme for expenses */
        color: white !important;
        font-weight: bold !important;
        border-bottom: none !important;
        position: sticky !important;
        top: 0 !important;
    }

    /* Footer styling */
    .footer-total {
        background-color: #f5f5f5 !important;
        font-weight: bold !important;
        font-size: 11px !important;
    }

    .footer-total td {
        border-top: 2px solid #ddd !important;
        padding: 6px 4px !important;
        white-space: nowrap !important;
        font-size: 11px !important;
    }

    /* Modal styling */
    .category_details_modal .modal-dialog {
        max-width: 95%;
        width: 1200px;
    }

    .category_details_modal .table {
        margin-bottom: 0;
    }

    .category_details_modal .table td {
        padding: 8px;
        border: 1px solid #ddd;
    }

    .category_details_modal .box {
        margin-bottom: 20px;
    }

    .category_details_modal .description-block {
        padding: 10px;
    }

    .category_details_modal .description-header {
        font-size: 20px;
        font-weight: bold;
        margin: 5px 0;
    }

    .category_details_modal .description-text {
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 600;
    }

    .category_details_modal .description-percentage {
        font-size: 20px;
        display: block;
        margin-bottom: 5px;
    }

    .category_details_modal .border-right {
        border-right: 1px solid #ddd;
    }

    .category_details_modal .text-red {
        color: #dd4b39 !important;
    }

    .category_details_modal .text-yellow {
        color: #f39c12 !important;
    }

    .category_details_modal .text-blue {
        color: #3c8dbc !important;
    }

    .category_details_modal .table-hover tbody tr:hover {
        background-color: #f5f5f5;
    }

    .category_details_modal .even-row {
        background-color: #fafafa;
    }

    .category_details_modal .odd-row {
        background-color: #ffffff;
    }

    .category_details_modal tbody tr:hover {
        background-color: #ffe8e8 !important;
        /* Light red hover for expense theme */
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
        .modern-widget {
            min-height: 120px !important;
            height: 120px !important;
        }

        .modern-widget .modern-widget-icon {
            font-size: 35px !important;
        }

        .modern-widget-number {
            font-size: 24px !important;
        }
    }

    @media (max-width: 768px) {
        .modern-widget {
            min-height: 110px !important;
            height: 110px !important;
            margin-bottom: 15px !important;
        }

        .modern-widget .modern-widget-icon {
            font-size: 30px !important;
        }

        .modern-widget-number {
            font-size: 22px !important;
        }

        .category_details_modal .modal-dialog {
            width: 95%;
            margin: 10px auto;
        }

        .category_details_modal .description-block {
            margin-bottom: 15px;
            border-right: none !important;
        }
    }

    /* Scrollbar styling for transaction list */
    .category_details_modal .box-body::-webkit-scrollbar {
        width: 8px;
    }

    .category_details_modal .box-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .category_details_modal .box-body::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .category_details_modal .box-body::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
</style>
@endsection