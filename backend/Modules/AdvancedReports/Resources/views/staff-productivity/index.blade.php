@extends('layouts.app')
@section('title', __('advancedreports::lang.staff_productivity_report'))

@section('css')
<style>
/* Staff Productivity View Toggle Styles */
.staff-productivity-container.card-view .staff-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.staff-productivity-container.table-view .row {
    display: block;
    width: 100%;
}

.staff-productivity-container.table-view .staff-card {
    display: block;
    background: transparent;
    border: 1px solid #ddd;
    border-radius: 0;
    padding: 10px;
    margin-bottom: 5px;
    box-shadow: none;
    width: 100%;
}

.staff-productivity-container.table-view .staff-info {
    display: inline-block;
    width: 30%;
    vertical-align: top;
    padding-right: 15px;
}

.staff-productivity-container.table-view .staff-metrics {
    display: inline-block;
    width: 68%;
    vertical-align: top;
}

.staff-productivity-container.table-view .staff-info h5 {
    margin: 0;
    font-size: 14px;
    font-weight: bold;
}

.staff-productivity-container.table-view .table {
    margin: 0;
    font-size: 12px;
}

.staff-productivity-container.table-view .table th,
.staff-productivity-container.table-view .table td {
    padding: 5px 8px;
}

.suggestion-card {
    border-left: 4px solid #007bff;
    background: #f8f9fa;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 0 5px 5px 0;
}

.suggestion-card.priority-high {
    border-left-color: #dc3545;
}

.suggestion-card.priority-medium {
    border-left-color: #ffc107;
}

.suggestion-card.priority-low {
    border-left-color: #28a745;
}

.performance-score {
    font-size: 24px;
    font-weight: bold;
}

.score-excellent { color: #28a745; }
.score-good { color: #007bff; }
.score-average { color: #ffc107; }
.score-poor { color: #dc3545; }
</style>
@endsection

@section('content')
<section class="content-header">
    <h1>
        <i class="fas fa-users-cog"></i> @lang('advancedreports::lang.staff_productivity_report')
        <small>@lang('advancedreports::lang.sales_performance_and_efficiency')</small>
    </h1>
</section>

<section class="content">
    <!-- Filter Controls -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-filter"></i> @lang('advancedreports::lang.filters')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-primary" id="sp_filter_btn">
                            <i class="fa fa-search"></i> @lang('advancedreports::lang.apply_filters')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.date_range')</label>
                                <input type="text" id="sp_date_range" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.locations')</label>
                                <select id="sp_locations" class="form-control select2" multiple>
                                    <option value="all">@lang('advancedreports::lang.all_locations')</option>
                                    @foreach($business_locations as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.category')</label>
                                <select id="sp_category_id" class="form-control select2">
                                    @foreach($categories as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.staff_members')</label>
                                <select id="sp_staff_ids" class="form-control select2" multiple>
                                    <option value="all">@lang('advancedreports::lang.all_staff')</option>
                                    @foreach($staff_members as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Overview -->
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box bg-green">
                <span class="info-box-icon"><i class="fa fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text" style="color: white;">@lang('advancedreports::lang.total_staff')</span>
                    <span class="info-box-number" id="sp_total_staff">0</span>
                    <div class="progress">
                        <div class="progress-bar bg-green" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="sp_active_staff">0 @lang('advancedreports::lang.active_staff')</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box bg-blue">
                <span class="info-box-icon"><i class="fa fa-chart-line"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text" style="color: white;">@lang('advancedreports::lang.total_sales')</span>
                    <span class="info-box-number" id="sp_total_sales"><span class="display_currency" data-currency_symbol="true">0</span></span>
                    <div class="progress">
                        <div class="progress-bar bg-blue" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="sp_avg_per_staff">0 @lang('advancedreports::lang.avg_per_staff')</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box bg-yellow">
                <span class="info-box-icon"><i class="fa fa-handshake"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text" style="color: white;">@lang('advancedreports::lang.total_commissions')</span>
                    <span class="info-box-number" id="sp_total_commissions"><span class="display_currency" data-currency_symbol="true">0</span></span>
                    <div class="progress">
                        <div class="progress-bar bg-yellow" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="sp_avg_commission">0 @lang('advancedreports::lang.avg_commission')</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="info-box bg-red">
                <span class="info-box-icon"><i class="fa fa-percentage"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text" style="color: white;">@lang('advancedreports::lang.avg_efficiency')</span>
                    <span class="info-box-number" id="sp_avg_efficiency">0%</span>
                    <div class="progress">
                        <div class="progress-bar bg-red" style="width: 100%"></div>
                    </div>
                    <span class="progress-description" id="sp_efficiency_status">@lang('advancedreports::lang.efficiency_tracking')</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff Sales Performance & Top Performers -->
    <div class="row">
        <div class="col-md-8">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-chart-bar"></i> @lang('advancedreports::lang.staff_sales_performance')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="export_sales_chart">
                            <i class="fa fa-download"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 400px;">
                        <canvas id="staffSalesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-star"></i> @lang('advancedreports::lang.top_performers')</h3>
                </div>
                <div class="box-body">
                    <div id="top_performers_list">
                        <!-- Top performers will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Working Hours Efficiency & Productivity Trends -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock"></i> @lang('advancedreports::lang.working_hours_efficiency')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="toggle_efficiency_chart">
                            <i class="fa fa-exchange-alt"></i> @lang('advancedreports::lang.toggle_view')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="efficiencyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-trending-up"></i> @lang('advancedreports::lang.productivity_trends')</h3>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="productivityTrendsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Commission Tracking & Performance Improvement -->
    <div class="row">
        <div class="col-md-7">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-money-bill-wave"></i> @lang('advancedreports::lang.commission_tracking')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="export_commission_chart">
                            <i class="fa fa-image"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="commissionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-lightbulb"></i> @lang('advancedreports::lang.improvement_suggestions')</h3>
                </div>
                <div class="box-body" style="max-height: 300px; overflow-y: auto;">
                    <div id="improvement_suggestions">
                        <!-- Suggestions will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Staff Performance Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-table"></i> @lang('advancedreports::lang.detailed_staff_performance')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="export_staff_data">
                            <i class="fa fa-download"></i> @lang('advancedreports::lang.export')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table id="staff_performance_table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.staff_name')</th>
                                    <th>@lang('advancedreports::lang.location')</th>
                                    <th>@lang('advancedreports::lang.sales')</th>
                                    <th>@lang('advancedreports::lang.transactions')</th>
                                    <th>@lang('advancedreports::lang.efficiency_score')</th>
                                    <th>@lang('advancedreports::lang.commission')</th>
                                    <th>@lang('advancedreports::lang.performance_rating')</th>
                                </tr>
                            </thead>
                            <tbody id="staff_performance_tbody">
                                <!-- Staff performance data will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff Comparison Analysis -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-balance-scale"></i> @lang('advancedreports::lang.staff_comparison_analysis')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-default" id="toggle_comparison_view">
                            <i class="fa fa-eye"></i> @lang('advancedreports::lang.toggle_view')
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div id="staff_comparison_container" class="staff-productivity-container card-view">
                        <!-- Staff comparison will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>
@endsection

@section('javascript')
<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        // Configure toastr to disable sound
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "3000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut",
            "playSound": false
        };
        
        // Initialize date picker
        $('#sp_date_range').daterangepicker({
            startDate: moment().subtract(3, 'months'),
            endDate: moment(),
            ranges: {
                '@lang("advancedreports::lang.last_month")': [moment().subtract(1, 'month'), moment()],
                '@lang("advancedreports::lang.last_3_months")': [moment().subtract(3, 'months'), moment()],
                '@lang("advancedreports::lang.last_6_months")': [moment().subtract(6, 'months'), moment()],
                '@lang("advancedreports::lang.last_year")': [moment().subtract(1, 'year'), moment()],
                '@lang("advancedreports::lang.this_year")': [moment().startOf('year'), moment().endOf('year')]
            },
            locale: {
                format: 'YYYY-MM-DD'
            }
        });

        // Initialize Select2
        $('.select2').select2();
        
        // Initialize locations filter with 'All' selected by default
        $('#sp_locations').val(['all']).trigger('change');
        
        // Handle 'All' selection for locations
        $('#sp_locations').on('change', function() {
            const selectedValues = $(this).val() || [];
            
            if (selectedValues.includes('all')) {
                if (selectedValues.length > 1) {
                    // If 'all' is selected along with other options, keep only 'all'
                    $(this).val(['all']).trigger('change.select2');
                }
            }
        });

        // Initialize staff filter with 'All' selected by default
        $('#sp_staff_ids').val(['all']).trigger('change');
        
        // Handle 'All' selection for staff
        $('#sp_staff_ids').on('change', function() {
            const selectedValues = $(this).val() || [];
            
            if (selectedValues.includes('all')) {
                if (selectedValues.length > 1) {
                    // If 'all' is selected along with other options, keep only 'all'
                    $(this).val(['all']).trigger('change.select2');
                }
            }
        });

        // Chart variables
        let salesChart, efficiencyChart, trendsChart, commissionChart;

        // Load analytics data
        function loadAnalytics() {
            const dateRange = $('#sp_date_range').val().split(' - ');
            const startDate = dateRange[0];
            const endDate = dateRange[1];
            let locationIds = $('#sp_locations').val() || [];
            
            // If 'all' is selected, use empty array (backend will handle all locations)
            if (locationIds.includes('all')) {
                locationIds = [];
            }
            
            let staffIds = $('#sp_staff_ids').val() || [];
            
            // If 'all' is selected, use empty array (backend will handle all staff)
            if (staffIds.includes('all')) {
                staffIds = [];
            }
            
            const categoryId = $('#sp_category_id').val() || 'all';

            $.ajax({
                url: '{{ route("advancedreports.staff-productivity.analytics") }}',
                type: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    location_ids: locationIds,
                    staff_ids: staffIds,
                    category_id: categoryId
                },
                success: function(data) {
                    updateOverviewCards(data.staff_sales_performance.totals);
                    updateStaffSalesChart(data.staff_sales_performance.staff_performance);
                    updateTopPerformers(data.staff_sales_performance.staff_performance);
                    updateEfficiencyChart(data.working_hours_efficiency.efficiency_metrics);
                    updateProductivityTrends(data.productivity_trends);
                    updateCommissionChart(data.commission_tracking.commission_data);
                    updateImprovementSuggestions(data.performance_suggestions.staff_suggestions);
                    updateStaffPerformanceTable(data.staff_comparison.staff_ranking);
                    updateStaffComparison(data.staff_comparison.comparison_metrics);
                },
                error: function(xhr, status, error) {
                    console.error('Analytics loading error:', error);
                    toastr.error('@lang("advancedreports::lang.error_loading_data")');
                }
            });
        }

        // Update overview cards
        function updateOverviewCards(totals) {
            $('#sp_total_staff').text(totals.total_staff || 0);
            $('#sp_total_sales').html('<span class="display_currency" data-currency_symbol="true">' + (totals.total_sales || 0) + '</span>');
            $('#sp_total_commissions').html('<span class="display_currency" data-currency_symbol="true">' + (totals.total_sales * 0.05 || 0) + '</span>');
            $('#sp_avg_efficiency').text('85%'); // Placeholder
            
            // Update progress descriptions
            $('#sp_active_staff').text(totals.total_staff + ' @lang("advancedreports::lang.active_staff")');
            $('#sp_avg_per_staff').html('<span class="display_currency" data-currency_symbol="true">' + (totals.total_staff > 0 ? totals.total_sales / totals.total_staff : 0) + '</span> @lang("advancedreports::lang.avg_per_staff")');
            $('#sp_avg_commission').html('<span class="display_currency" data-currency_symbol="true">' + (totals.total_staff > 0 ? (totals.total_sales * 0.05) / totals.total_staff : 0) + '</span> @lang("advancedreports::lang.avg_commission")');
            
            // Convert currency
            __currency_convert_recursively($('#sp_total_sales'));
            __currency_convert_recursively($('#sp_total_commissions'));
            __currency_convert_recursively($('#sp_avg_per_staff'));
            __currency_convert_recursively($('#sp_avg_commission'));
        }

        // Update staff sales chart
        function updateStaffSalesChart(staffData) {
            const labels = staffData.slice(0, 10).map(s => s.staff_name);
            const salesData = staffData.slice(0, 10).map(s => s.total_sales);
            const transactionData = staffData.slice(0, 10).map(s => s.total_transactions);

            const ctx = document.getElementById('staffSalesChart').getContext('2d');
            if (salesChart) salesChart.destroy();
            
            salesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '@lang("advancedreports::lang.sales")',
                        data: salesData,
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    }, {
                        label: '@lang("advancedreports::lang.transactions")',
                        data: transactionData,
                        backgroundColor: 'rgba(255, 99, 132, 0.8)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left'
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        }

        // Update top performers
        function updateTopPerformers(staffData) {
            const topStaff = staffData.slice(0, 5);
            let html = '';
            
            topStaff.forEach((staff, index) => {
                const rankClass = index === 0 ? 'text-warning' : (index < 3 ? 'text-info' : 'text-muted');
                const trophy = index === 0 ? 'fa-trophy' : (index < 3 ? 'fa-medal' : 'fa-star');
                
                html += `
                    <div class="callout callout-info">
                        <div class="row">
                            <div class="col-xs-8">
                                <h5><i class="fa ${trophy} ${rankClass}"></i> ${staff.staff_name}</h5>
                                <p><small>${staff.location_name || ''}</small></p>
                            </div>
                            <div class="col-xs-4 text-right">
                                <h4><span class="display_currency" data-currency_symbol="true">${staff.total_sales}</span></h4>
                                <p><small>${staff.total_transactions} @lang('advancedreports::lang.transactions')</small></p>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            $('#top_performers_list').html(html);
            __currency_convert_recursively($('#top_performers_list'));
        }

        // Update efficiency chart
        function updateEfficiencyChart(efficiencyData) {
            const labels = efficiencyData.slice(0, 10).map(e => e.staff_name);
            const efficiencyScores = efficiencyData.slice(0, 10).map(e => e.efficiency_score);
            const salesPerHour = efficiencyData.slice(0, 10).map(e => e.sales_per_hour);

            const ctx = document.getElementById('efficiencyChart').getContext('2d');
            if (efficiencyChart) efficiencyChart.destroy();
            
            efficiencyChart = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '@lang("advancedreports::lang.efficiency_score")',
                        data: efficiencyScores,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(75, 192, 192, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        // Update productivity trends
        function updateProductivityTrends(trendsData) {
            if (!trendsData || Object.keys(trendsData).length === 0) return;

            const datasets = [];
            const colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'];
            let colorIndex = 0;

            Object.keys(trendsData).forEach(staffId => {
                const staffData = trendsData[staffId];
                const color = colors[colorIndex % colors.length];
                
                datasets.push({
                    label: staffData.staff_name,
                    data: staffData.trends.map(t => t.sales),
                    borderColor: color,
                    backgroundColor: color + '20',
                    fill: false,
                    tension: 0.1
                });
                
                colorIndex++;
            });

            const labels = Object.values(trendsData)[0]?.trends.map(t => t.period) || [];

            const ctx = document.getElementById('productivityTrendsChart').getContext('2d');
            if (trendsChart) trendsChart.destroy();
            
            trendsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Update commission chart
        function updateCommissionChart(commissionData) {
            const labels = commissionData.slice(0, 10).map(c => c.staff_name);
            const commissions = commissionData.slice(0, 10).map(c => c.commission_amount);
            const sales = commissionData.slice(0, 10).map(c => c.total_sales);

            const ctx = document.getElementById('commissionChart').getContext('2d');
            if (commissionChart) commissionChart.destroy();
            
            commissionChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '@lang("advancedreports::lang.commission_amount")',
                        data: commissions,
                        backgroundColor: 'rgba(255, 206, 86, 0.8)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Update improvement suggestions
        function updateImprovementSuggestions(suggestionsData) {
            let html = '';
            
            suggestionsData.slice(0, 5).forEach(staff => {
                if (staff.suggestions && staff.suggestions.length > 0) {
                    html += `<h6><i class="fa fa-user"></i> ${staff.staff_name}</h6>`;
                    
                    staff.suggestions.forEach(suggestion => {
                        html += `
                            <div class="suggestion-card priority-${suggestion.priority}">
                                <div class="suggestion-content">
                                    <strong>${suggestion.type.toUpperCase()}:</strong> ${suggestion.suggestion}
                                    <br><small class="text-muted">@lang('advancedreports::lang.current'): ${suggestion.current_metric} | @lang('advancedreports::lang.target'): ${suggestion.target_metric}</small>
                                </div>
                            </div>
                        `;
                    });
                    html += '<hr>';
                }
            });
            
            if (html === '') {
                html = '<p class="text-muted">@lang("advancedreports::lang.no_suggestions_available")</p>';
            }
            
            $('#improvement_suggestions').html(html);
        }

        // Update staff performance table
        function updateStaffPerformanceTable(staffRanking) {
            let html = '';
            
            staffRanking.forEach(staff => {
                const ratingClass = staff.performance_rating === 'Excellent' ? 'success' : 
                                 staff.performance_rating === 'Good' ? 'info' : 
                                 staff.performance_rating === 'Average' ? 'warning' : 'danger';
                
                html += `
                    <tr>
                        <td><strong>${staff.staff_name}</strong></td>
                        <td>${staff.location_name || '-'}</td>
                        <td><span class="display_currency" data-currency_symbol="true">${staff.total_sales}</span></td>
                        <td>${staff.total_transactions}</td>
                        <td>${(staff.efficiency_score || 0).toFixed(1)}%</td>
                        <td><span class="display_currency" data-currency_symbol="true">${(staff.total_sales * 0.05).toFixed(2)}</span></td>
                        <td><span class="label label-${ratingClass}">${staff.performance_rating}</span></td>
                    </tr>
                `;
            });
            
            $('#staff_performance_tbody').html(html);
            __currency_convert_recursively($('#staff_performance_tbody'));
        }

        // Update staff comparison
        function updateStaffComparison(comparisonMetrics) {
            let html = '';
            
            Object.keys(comparisonMetrics).forEach(key => {
                const metric = comparisonMetrics[key];
                if (metric) {
                    html += `
                        <div class="col-md-3 staff-card">
                            <div class="staff-info">
                                <h5>${key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</h5>
                            </div>
                            <div class="staff-metrics">
                                <p><strong>${metric.staff_name}</strong></p>
                                <p><span class="display_currency" data-currency_symbol="true">${metric.total_sales}</span></p>
                                <p><small>${metric.location_name || ''}</small></p>
                            </div>
                        </div>
                    `;
                }
            });
            
            $('#staff_comparison_container').html('<div class="row">' + html + '</div>');
            __currency_convert_recursively($('#staff_comparison_container'));
        }

        // Event handlers
        $('#sp_filter_btn').click(function() {
            loadAnalytics();
        });

        $('#export_sales_chart').click(function() {
            exportChartAsImage(salesChart, 'staff-sales-chart');
        });

        $('#export_commission_chart').click(function() {
            exportChartAsImage(commissionChart, 'commission-chart');
        });

        $('#export_staff_data').click(function() {
            exportStaffDataAsCSV();
        });

        // Toggle view buttons
        $('#toggle_efficiency_chart').click(function() {
            toggleEfficiencyChartView();
        });

        $('#toggle_comparison_view').click(function() {
            toggleComparisonView();
        });

        // Export functions
        function exportChartAsImage(chart, filename) {
            if (!chart) {
                toastr.error('@lang("advancedreports::lang.no_chart_available")');
                return;
            }
            
            const url = chart.canvas.toDataURL('image/png');
            const link = document.createElement('a');
            link.download = `${filename}.png`;
            link.href = url;
            link.click();
        }

        function exportStaffDataAsCSV() {
            const dateRange = $('#sp_date_range').val().split(' - ');
            const startDate = dateRange[0];
            const endDate = dateRange[1];
            let locationIds = $('#sp_locations').val() || [];
            let staffIds = $('#sp_staff_ids').val() || [];
            
            if (locationIds.includes('all')) {
                locationIds = [];
            }
            
            if (staffIds.includes('all')) {
                staffIds = [];
            }
            
            $.ajax({
                url: '{{ route("advancedreports.staff-productivity.analytics") }}',
                type: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    location_ids: locationIds,
                    staff_ids: staffIds,
                    category_id: $('#sp_category_id').val()
                },
                success: function(data) {
                    let csvContent = "@lang('advancedreports::lang.staff_csv_header')\n";
                    
                    if (data.staff_comparison && data.staff_comparison.staff_ranking) {
                        data.staff_comparison.staff_ranking.forEach(staff => {
                            csvContent += `"${staff.staff_name}","${staff.location_name}",${staff.total_sales},${staff.total_transactions},${staff.efficiency_score || 0},${(staff.total_sales * 0.05).toFixed(2)},"${staff.performance_rating}"\n`;
                        });
                    }
                    
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const link = document.createElement('a');
                    const url = URL.createObjectURL(blob);
                    link.setAttribute('href', url);
                    link.setAttribute('download', `staff-productivity-${startDate}-to-${endDate}.csv`);
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    toastr.success('@lang("advancedreports::lang.staff_data_exported_successfully")');
                },
                error: function() {
                    toastr.error('@lang("advancedreports::lang.error_exporting_staff_data")');
                }
            });
        }

        function toggleEfficiencyChartView() {
            if (efficiencyChart) {
                const currentType = efficiencyChart.config.type;
                const newType = currentType === 'radar' ? 'bar' : 'radar';
                
                efficiencyChart.config.type = newType;
                efficiencyChart.update();
                
                const chartTypeTranslated = newType === 'radar' ? '@lang("advancedreports::lang.radar")' : '@lang("advancedreports::lang.bar")';
                toastr.success('@lang("advancedreports::lang.chart_view_toggled_to") ' + chartTypeTranslated + ' @lang("advancedreports::lang.chart")');
            } else {
                toastr.error('@lang("advancedreports::lang.no_chart_available")');
            }
        }

        function toggleComparisonView() {
            const comparisonContainer = $('#staff_comparison_container');
            if (comparisonContainer.length) {
                const currentView = comparisonContainer.hasClass('table-view') ? 'table' : 'card';
                
                if (currentView === 'table') {
                    comparisonContainer.removeClass('table-view').addClass('card-view');
                    toastr.success('@lang("advancedreports::lang.comparison_view_switched_to_card_layout")');
                } else {
                    comparisonContainer.removeClass('card-view').addClass('table-view');
                    toastr.success('@lang("advancedreports::lang.comparison_view_switched_to_table_layout")');
                }
            } else {
                toastr.error('@lang("advancedreports::lang.comparison_container_not_found")');
            }
        }

        // Convert initial currency values
        __currency_convert_recursively($(document));
        
        // Load initial data
        loadAnalytics();
    });
</script>
@endsection