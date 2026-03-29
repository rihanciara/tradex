@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.customer_recognition_settings'))

@section('content')

<!-- Content Header -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ __('advancedreports::lang.customer_recognition_settings')}}
        <small class="text-muted">@lang('advancedreports::lang.configure_recognition_periods_scoring_preferences')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">

    <!-- Settings Form -->
    <div class="row">
        <div class="col-md-12">
            <form id="recognition_settings_form">
                @csrf

                <!-- General Settings -->
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-cogs"></i> @lang('advancedreports::lang.general_settings')
                        </h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="module_start_date">@lang('advancedreports::lang.module_start_date'):</label>
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                        <input type="date" class="form-control" id="module_start_date"
                                            name="module_start_date"
                                            value="{{ $settings->module_start_date ? $settings->module_start_date->format('Y-m-d') : date('Y-m-d') }}"
                                            required>
                                    </div>
                                    <small class="text-muted">Date from which recognition system will be
                                        effective</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" id="is_active" name="is_active" {{
                                                $settings->is_active ? 'checked' : '' }}>
                                            <strong>Enable Customer Recognition System</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Turn the entire recognition system on/off</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Period Configuration -->
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-calendar"></i> Recognition Periods
                        </h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <!-- Weekly Settings -->
                            <div class="col-md-4">
                                <div class="period-card weekly-card">
                                    <div class="period-header">
                                        <h4><i class="fa fa-calendar-week"></i> @lang('advancedreports::lang.weekly_recognition')</h4>
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" id="weekly_enabled" name="weekly_enabled" {{
                                                    $settings->weekly_enabled ? 'checked' : '' }}>
                                                Enable Weekly Awards
                                            </label>
                                        </div>
                                    </div>
                                    <div class="period-content">
                                        <div class="form-group">
                                            <label for="winner_count_weekly">Number of Winners:</label>
                                            <select class="form-control" id="winner_count_weekly"
                                                name="winner_count_weekly">
                                                <option value="1" {{ $settings->winner_count_weekly == 1 ? 'selected' :
                                                    '' }}>1</option>
                                                <option value="3" {{ $settings->winner_count_weekly == 3 ? 'selected' :
                                                    '' }}>3</option>
                                                <option value="5" {{ $settings->winner_count_weekly == 5 ? 'selected' :
                                                    '' }}>5</option>
                                                <option value="10" {{ $settings->winner_count_weekly == 10 ? 'selected'
                                                    : '' }}>10</option>
                                                <option value="15" {{ $settings->winner_count_weekly == 15 ? 'selected'
                                                    : '' }}>15</option>
                                                <option value="20" {{ $settings->winner_count_weekly == 20 ? 'selected'
                                                    : '' }}>20</option>
                                            </select>
                                        </div>
                                        <small class="text-muted">Period: Monday to Sunday</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Monthly Settings -->
                            <div class="col-md-4">
                                <div class="period-card monthly-card">
                                    <div class="period-header">
                                        <h4><i class="fa fa-calendar"></i> @lang('advancedreports::lang.monthly_recognition')</h4>
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" id="monthly_enabled" name="monthly_enabled" {{
                                                    $settings->monthly_enabled ? 'checked' : '' }}>
                                                Enable Monthly Awards
                                            </label>
                                        </div>
                                    </div>
                                    <div class="period-content">
                                        <div class="form-group">
                                            <label for="winner_count_monthly">Number of Winners:</label>
                                            <select class="form-control" id="winner_count_monthly"
                                                name="winner_count_monthly">
                                                <option value="1" {{ $settings->winner_count_monthly == 1 ? 'selected' :
                                                    '' }}>1</option>
                                                <option value="3" {{ $settings->winner_count_monthly == 3 ? 'selected' :
                                                    '' }}>3</option>
                                                <option value="5" {{ $settings->winner_count_monthly == 5 ? 'selected' :
                                                    '' }}>5</option>
                                                <option value="10" {{ $settings->winner_count_monthly == 10 ? 'selected'
                                                    : '' }}>10</option>
                                                <option value="15" {{ $settings->winner_count_monthly == 15 ? 'selected'
                                                    : '' }}>15</option>
                                                <option value="20" {{ $settings->winner_count_monthly == 20 ? 'selected'
                                                    : '' }}>20</option>
                                                <option value="50" {{ $settings->winner_count_monthly == 50 ? 'selected'
                                                    : '' }}>50</option>
                                            </select>
                                        </div>
                                        <small class="text-muted">Period: 1st to last day of month</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Yearly Settings -->
                            <div class="col-md-4">
                                <div class="period-card yearly-card">
                                    <div class="period-header">
                                        <h4><i class="fa fa-calendar-o"></i> Yearly Recognition</h4>
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" id="yearly_enabled" name="yearly_enabled" {{
                                                    $settings->yearly_enabled ? 'checked' : '' }}>
                                                Enable Yearly Awards
                                            </label>
                                        </div>
                                    </div>
                                    <div class="period-content">
                                        <div class="form-group">
                                            <label for="winner_count_yearly">Number of Winners:</label>
                                            <select class="form-control" id="winner_count_yearly"
                                                name="winner_count_yearly">
                                                <option value="1" {{ $settings->winner_count_yearly == 1 ? 'selected' :
                                                    '' }}>1</option>
                                                <option value="3" {{ $settings->winner_count_yearly == 3 ? 'selected' :
                                                    '' }}>3</option>
                                                <option value="5" {{ $settings->winner_count_yearly == 5 ? 'selected' :
                                                    '' }}>5</option>
                                                <option value="10" {{ $settings->winner_count_yearly == 10 ? 'selected'
                                                    : '' }}>10</option>
                                                <option value="20" {{ $settings->winner_count_yearly == 20 ? 'selected'
                                                    : '' }}>20</option>
                                                <option value="50" {{ $settings->winner_count_yearly == 50 ? 'selected'
                                                    : '' }}>50</option>
                                                <option value="100" {{ $settings->winner_count_yearly == 100 ?
                                                    'selected' : '' }}>100</option>
                                            </select>
                                        </div>
                                        <small class="text-muted">Period: January 1st to December 31st</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scoring Configuration -->
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-calculator"></i> Scoring Method
                        </h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Scoring Method:</label>
                                    <div class="radio-group">
                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="scoring_method" value="pure_sales" {{
                                                    $settings->scoring_method == 'pure_sales' ? 'checked' : '' }}>
                                                <strong>Pure Sales</strong> - Based only on sales amount
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="scoring_method" value="weighted" {{
                                                    $settings->scoring_method == 'weighted' ? 'checked' : '' }}>
                                                <strong>Weighted Scoring</strong> - Sales + Engagement points
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div id="weighted_settings"
                                    style="{{ $settings->scoring_method == 'weighted' ? '' : 'display: none;' }}">
                                    <div class="form-group">
                                        <label for="sales_weight">Sales Weight (%):</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="sales_weight"
                                                name="sales_weight" value="{{ $settings->sales_weight * 100 }}" min="0"
                                                max="100" step="1">
                                            <span class="input-group-addon">%</span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="engagement_weight">Engagement Weight (%):</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="engagement_weight"
                                                name="engagement_weight"
                                                value="{{ $settings->engagement_weight * 100 }}" min="0" max="100"
                                                step="1">
                                            <span class="input-group-addon">%</span>
                                        </div>
                                    </div>
                                    <small class="text-muted">Total must equal 100%</small>
                                </div>
                            </div>
                        </div>

                        <!-- Scoring Calculator -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="scoring-calculator">
                                    <h5><i class="fa fa-calculator"></i> Scoring Calculator (Test your settings)</h5>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Sales Amount ($):</label>
                                                <input type="number" class="form-control" id="test_sales" value="1000"
                                                    min="0" step="0.01">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Engagement Points:</label>
                                                <input type="number" class="form-control" id="test_engagement"
                                                    value="25" min="0" max="100">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-info" id="calculate_score"
                                                style="margin-top: 25px;">
                                                <i class="fa fa-calculator"></i> Calculate Score
                                            </button>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="score-result" style="margin-top: 25px;">
                                                <strong>Final Score: <span id="final_score">0</span></strong>
                                                <br><small id="score_breakdown"></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historical Data Settings -->
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-history"></i> Historical Data
                        </h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="calculate_historical" name="calculate_historical" {{
                                            $settings->calculate_historical ? 'checked' : '' }}>
                                        <strong>Calculate Historical Data</strong>
                                    </label>
                                </div>
                                <small class="text-muted">Automatically calculate past winners from existing transaction
                                    data</small>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" id="historical_months_group"
                                    style="{{ $settings->calculate_historical ? '' : 'display: none;' }}">
                                    <label for="historical_months">Historical Months:</label>
                                    <select class="form-control" id="historical_months" name="historical_months">
                                        <option value="3" {{ $settings->historical_months == 3 ? 'selected' : '' }}>3
                                            months</option>
                                        <option value="6" {{ $settings->historical_months == 6 ? 'selected' : '' }}>6
                                            months</option>
                                        <option value="12" {{ $settings->historical_months == 12 ? 'selected' : '' }}>12
                                            months</option>
                                        <option value="24" {{ $settings->historical_months == 24 ? 'selected' : '' }}>24
                                            months</option>
                                        <option value="36" {{ $settings->historical_months == 36 ? 'selected' : '' }}>36
                                            months</option>
                                    </select>
                                    <small class="text-muted">How many months back to calculate from start date</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="box box-default">
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fa fa-save"></i> Save Settings
                                </button>
                                <button type="button" class="btn btn-default" id="reset_form">
                                    <i class="fa fa-refresh"></i> Reset
                                </button>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="button" class="btn btn-info" id="rebuild_cache_btn">
                                    <i class="fa fa-database"></i> Rebuild Cache
                                </button>
                                <button type="button" class="btn btn-warning" id="reset_data_btn">
                                    <i class="fa fa-warning"></i> Reset Data
                                </button>
                                <button type="button" class="btn btn-success" id="export_settings_btn">
                                    <i class="fa fa-download"></i> Export Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- System Statistics -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-bar-chart"></i> System Statistics
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" id="refresh_stats">
                            <i class="fa fa-refresh"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row" id="system_stats">
                        <div class="col-md-12 text-center">
                            <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
                            <p class="text-muted">Loading statistics...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Reset Data Modal -->
<div class="modal fade reset_data_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Reset Recognition Data</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fa fa-warning"></i>
                    <strong>Warning:</strong> This action cannot be undone!
                </div>

                <div class="form-group">
                    <label>What data do you want to reset?</label>
                    <select class="form-control" id="reset_type">
                        <option value="cache_only">Cache Only (Rankings will be recalculated)</option>
                        <option value="awards_only">Awards & Periods Only</option>
                        <option value="engagements_only">Engagement Records Only</option>
                        <option value="everything">Everything (Complete Reset)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Type "RESET_ALL_DATA" to confirm:</label>
                    <input type="text" class="form-control" id="reset_confirmation" placeholder="RESET_ALL_DATA">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm_reset_data" disabled>
                    <i class="fa fa-warning"></i> Reset Data
                </button>
            </div>
        </div>
    </div>
</div>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        
        // Scoring method change
        $('input[name="scoring_method"]').change(function() {
            if ($(this).val() === 'weighted') {
                $('#weighted_settings').slideDown();
            } else {
                $('#weighted_settings').slideUp();
            }
        });

        // Weight validation
        $('#sales_weight, #engagement_weight').on('input', function() {
            var salesWeight = parseFloat($('#sales_weight').val()) || 0;
            var engagementWeight = parseFloat($('#engagement_weight').val()) || 0;
            var total = salesWeight + engagementWeight;
            
            if (total !== 100) {
                $(this).parent().addClass('has-error');
            } else {
                $('#sales_weight, #engagement_weight').parent().removeClass('has-error');
            }
        });

        // Auto-adjust weights
        $('#sales_weight').on('change', function() {
            var salesWeight = parseFloat($(this).val()) || 0;
            var engagementWeight = 100 - salesWeight;
            $('#engagement_weight').val(engagementWeight);
        });

        // Historical data checkbox
        $('#calculate_historical').change(function() {
            if ($(this).is(':checked')) {
                $('#historical_months_group').slideDown();
            } else {
                $('#historical_months_group').slideUp();
            }
        });

        // Score calculator
        $('#calculate_score').click(function() {
            var sales = parseFloat($('#test_sales').val()) || 0;
            var engagement = parseFloat($('#test_engagement').val()) || 0;
            var scoringMethod = $('input[name="scoring_method"]:checked').val();
            var salesWeight = parseFloat($('#sales_weight').val()) / 100 || 0.7;
            var engagementWeight = parseFloat($('#engagement_weight').val()) / 100 || 0.3;
            
            var finalScore, breakdown;
            
            if (scoringMethod === 'pure_sales') {
                finalScore = sales;
                breakdown = 'Pure Sales: $' + sales.toFixed(2);
            } else {
                var salesScore = sales * salesWeight;
                var engagementScore = (engagement * 10) * engagementWeight;
                finalScore = salesScore + engagementScore;
                breakdown = 'Sales: $' + salesScore.toFixed(2) + ' + Engagement: $' + engagementScore.toFixed(2);
            }
            
            $('#final_score').text(finalScore.toFixed(2));
            $('#score_breakdown').text(breakdown);
        });

        // Form submission
        $('#recognition_settings_form').on('submit', function(e) {
            e.preventDefault();
            
            // Validate weights for weighted scoring
            if ($('input[name="scoring_method"]:checked').val() === 'weighted') {
                var salesWeight = parseFloat($('#sales_weight').val()) || 0;
                var engagementWeight = parseFloat($('#engagement_weight').val()) || 0;
                
                if (salesWeight + engagementWeight !== 100) {
                    toastr.error('Sales weight and engagement weight must total 100%');
                    return;
                }
            }
            
            var formData = $(this).serialize();
            
            $.ajax({
                url: "{{ route('advancedreports.recognition-settings.update') }}",
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        toastr.success('Settings saved successfully!');
                        loadSystemStats();
                    }
                },
                error: function(xhr) {
                    var message = xhr.responseJSON?.error || 'Error saving settings';
                    toastr.error(message);
                }
            });
        });

        // Reset form
        $('#reset_form').click(function() {
            location.reload();
        });

        // Rebuild cache
        $('#rebuild_cache_btn').click(function() {
            var $btn = $(this);
            var originalText = $btn.html();
            $btn.html('<i class="fa fa-spinner fa-spin"></i> Rebuilding...').prop('disabled', true);
            
            $.ajax({
                url: "{{ route('advancedreports.recognition-settings.rebuild-cache') }}",
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        loadSystemStats();
                    }
                },
                error: function(xhr) {
                    var message = xhr.responseJSON?.error || 'Error rebuilding cache';
                    toastr.error(message);
                },
                complete: function() {
                    $btn.html(originalText).prop('disabled', false);
                }
            });
        });

        // Reset data modal
        $('#reset_data_btn').click(function() {
            $('.reset_data_modal').modal('show');
        });

        // Reset confirmation input
        $('#reset_confirmation').on('input', function() {
            var confirmation = $(this).val();
            $('#confirm_reset_data').prop('disabled', confirmation !== 'RESET_ALL_DATA');
        });

        // Confirm reset data
        $('#confirm_reset_data').click(function() {
            var resetType = $('#reset_type').val();
            var confirmation = $('#reset_confirmation').val();
            
            if (confirmation !== 'RESET_ALL_DATA') {
                toastr.error('Please type "RESET_ALL_DATA" to confirm');
                return;
            }
            
            var $btn = $(this);
            var originalText = $btn.html();
            $btn.html('<i class="fa fa-spinner fa-spin"></i> Resetting...').prop('disabled', true);
            
            $.ajax({
                url: "{{ route('advancedreports.recognition-settings.reset-data') }}",
                method: 'POST',
                data: {
                    reset_type: resetType,
                    confirmation: confirmation
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('.reset_data_modal').modal('hide');
                        loadSystemStats();
                        $('#reset_confirmation').val('');
                    }
                },
                error: function(xhr) {
                    var message = xhr.responseJSON?.error || 'Error resetting data';
                    toastr.error(message);
                },
                complete: function() {
                    $btn.html(originalText).prop('disabled', false);
                }
            });
        });

        // Export settings
        $('#export_settings_btn').click(function(e) {
            e.preventDefault();

            var $btn = $(this);
            var originalText = $btn.html();
            $btn.html('<i class="fa fa-spinner fa-spin"></i> Exporting...').prop('disabled', true);

            var params = {
                _token: '{{ csrf_token() }}'
            };

            // Use AJAX to download the file properly
            $.ajax({
                url: "{{ route('advancedreports.recognition-settings.export') }}",
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
                    var filename = 'recognition-settings.xlsx';
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

        // Load system statistics
        function loadSystemStats() {
            $.ajax({
                url: "{{ route('advancedreports.recognition-settings.statistics') }}",
                success: function(response) {
                    if (response.success) {
                        renderSystemStats(response);
                    }
                },
                error: function() {
                    $('#system_stats').html('<div class="col-md-12 text-center text-danger">Error loading statistics</div>');
                }
            });
        }

        // Render system statistics
        function renderSystemStats(data) {
            var html = '';
            
            // Cache statistics
            if (data.cache_stats && Object.keys(data.cache_stats).length > 0) {
                html += '<div class="col-md-4"><h5>Cache Statistics</h5>';
                Object.keys(data.cache_stats).forEach(function(period) {
                    var stats = data.cache_stats[period];
                    html += `
                        <div class="info-box">
                            <span class="info-box-icon bg-blue"><i class="fa fa-calendar"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">${period.charAt(0).toUpperCase() + period.slice(1)}</span>
                                <span class="info-box-number">${stats.total_customers} customers</span>
                                <div class="progress"><div class="progress-bar" style="width: 70%"></div></div>
                                <span class="progress-description">Total Sales: $${formatNumber(stats.total_sales)}</span>
                            </div>
                        </div>`;
                });
                html += '</div>';
            }
            
            // Award statistics
            if (data.award_stats && Object.keys(data.award_stats).length > 0) {
                html += '<div class="col-md-4"><h5>Award Statistics</h5>';
                Object.keys(data.award_stats).forEach(function(period) {
                    var stats = data.award_stats[period];
                    html += `
                        <div class="info-box">
                            <span class="info-box-icon bg-green"><i class="fa fa-trophy"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">${period.charAt(0).toUpperCase() + period.slice(1)} Awards</span>
                                <span class="info-box-number">${stats.total_awards}</span>
                                <div class="progress"><div class="progress-bar" style="width: ${(stats.awarded_count/stats.total_awards)*100}%"></div></div>
                                <span class="progress-description">${stats.awarded_count} awarded</span>
                            </div>
                        </div>`;
                });
                html += '</div>';
            }
            
            // Engagement statistics
            if (data.engagement_stats && Object.keys(data.engagement_stats).length > 0) {
                html += '<div class="col-md-4"><h5>Engagement Statistics</h5>';
                Object.keys(data.engagement_stats).forEach(function(type) {
                    var stats = data.engagement_stats[type];
                    html += `
                        <div class="info-box">
                            <span class="info-box-icon bg-yellow"><i class="fa fa-star"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">${type.replace('_', ' ')}</span>
                                <span class="info-box-number">${stats.count}</span>
                                <div class="progress"><div class="progress-bar" style="width: ${stats.avg_points*10}%"></div></div>
                                <span class="progress-description">Avg: ${stats.avg_points} pts</span>
                            </div>
                        </div>`;
                });
                html += '</div>';
            }
            
            if (!html) {
                html = '<div class="col-md-12 text-center text-muted">No statistics available yet</div>';
            }
            
            $('#system_stats').html(html);
        }
    });
</script>
@endsection