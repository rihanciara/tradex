@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.advanced_reports_dashboard'))

@section('css')
<style>
    .stats-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .stats-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .category-section {
        margin-bottom: 30px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .category-header {
        padding: 20px;
        color: white;
        display: flex;
        align-items: center;
        background: linear-gradient(135deg, var(--category-color) 0%, var(--category-color-dark) 100%);
    }
    
    .category-header i {
        font-size: 2em;
        margin-right: 15px;
        opacity: 0.9;
    }
    
    .category-title {
        font-size: 1.4em;
        font-weight: bold;
        margin: 0 0 5px 0;
    }
    
    .category-description {
        margin: 0;
        opacity: 0.9;
        font-size: 0.95em;
    }
    
    .reports-grid {
        padding: 25px;
        background: white;
    }
    
    .report-card {
        background: white;
        border: 1px solid #e8e8e8;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .report-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        border-color: #007bff;
    }
    
    .report-card.featured {
        border-left: 5px solid #007bff;
        background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
    }
    
    .report-card.featured::before {
        content: "⭐ FEATURED";
        position: absolute;
        top: 10px;
        right: 15px;
        background: #007bff;
        color: white;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: bold;
    }
    
    .report-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .report-icon {
        width: 45px;
        height: 45px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        font-size: 1.2em;
    }
    
    .report-title {
        font-size: 1.2em;
        font-weight: bold;
        color: #333;
        margin: 0 0 5px 0;
    }
    
    .report-description {
        color: #666;
        line-height: 1.5;
        margin-bottom: 15px;
    }
    
    .report-features {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 15px;
    }
    
    .feature-tag {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        padding: 4px 8px;
        border-radius: 15px;
        font-size: 11px;
        color: #495057;
    }
    
    .report-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .btn-view-report {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-view-report:hover {
        background: linear-gradient(135deg, #0056b3, #004085);
        color: white;
        transform: translateY(-1px);
        text-decoration: none;
    }
    
    .report-status {
        font-size: 12px;
        color: #28a745;
        font-weight: bold;
    }
    
    /* Category color variables */
    .bg-blue { --category-color: #007bff; --category-color-dark: #0056b3; }
    .bg-green { --category-color: #28a745; --category-color-dark: #1e7e34; }
    .bg-orange { --category-color: #fd7e14; --category-color-dark: #e55b00; }
    .bg-purple { --category-color: #6f42c1; --category-color-dark: #5a32a3; }
    .bg-red { --category-color: #dc3545; --category-color-dark: #c82333; }
    .bg-teal { --category-color: #20c997; --category-color-dark: #1aa179; }
    
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 30px;
        border-radius: 10px;
    }
    
    .hero-content {
        text-align: center;
        padding: 0 30px;
    }
    
    .hero-title {
        font-size: 2.5em;
        font-weight: bold;
        margin-bottom: 10px;
    }
    
    .hero-subtitle {
        font-size: 1.2em;
        opacity: 0.9;
        margin-bottom: 20px;
    }
    
    .hero-stats {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-top: 30px;
    }
    
    .hero-stat {
        text-align: center;
    }
    
    .hero-stat-number {
        font-size: 2em;
        font-weight: bold;
        display: block;
    }
    
    .hero-stat-label {
        font-size: 0.9em;
        opacity: 0.8;
    }
    
    @media (max-width: 768px) {
        .hero-stats {
            flex-direction: column;
            gap: 20px;
        }
        
        .reports-grid {
            padding: 15px;
        }
        
        .report-card {
            padding: 15px;
        }
    }
</style>
@endsection

@section('content')

<!-- Content Header -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ __('Advanced Reports - Business Intelligence Dashboard') }}
        <small class="text-muted">@lang('advancedreports::lang.comprehensive_analytics_description')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">@lang('advancedreports::lang.professional_reports_count')</h1>
            <p class="hero-subtitle">@lang('advancedreports::lang.transform_data_insights')</p>
            
            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="hero-stat-number">{{ number_format($quick_stats['total_customers']) }}</span>
                    <span class="hero-stat-label">@lang('advancedreports::lang.total_customers')</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-number">@format_currency($quick_stats['monthly_sales'])</span>
                    <span class="hero-stat-label">{{ $quick_stats['period'] }} Sales</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-number">{{ number_format($quick_stats['total_products']) }}</span>
                    <span class="hero-stat-label">@lang('advancedreports::lang.products')</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-number">{{ number_format($quick_stats['monthly_transactions']) }}</span>
                    <span class="hero-stat-label">{{ $quick_stats['period'] }} Transactions</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Business Stats Cards -->
    <div class="row" style="margin-bottom: 30px;">
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-aqua stats-card">
                <div class="inner">
                    <h3>{{ number_format($quick_stats['total_customers']) }}</h3>
                    <p>@lang('advancedreports::lang.total_customers')</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-green stats-card">
                <div class="inner">
                    <h3>@format_currency($quick_stats['monthly_sales'])</h3>
                    <p>{{ $quick_stats['period'] }} Sales</p>
                </div>
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-yellow stats-card">
                <div class="inner">
                    <h3>{{ number_format($quick_stats['total_products']) }}</h3>
                    <p>@lang('advancedreports::lang.total_products')</p>
                </div>
                <div class="icon">
                    <i class="fas fa-boxes"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-red stats-card">
                <div class="inner">
                    <h3>{{ number_format($quick_stats['monthly_transactions']) }}</h3>
                    <p>{{ $quick_stats['period'] }} Transactions</p>
                </div>
                <div class="icon">
                    <i class="fas fa-receipt"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Categories -->
    @foreach($report_categories as $category_key => $category)
    <div class="category-section">
        <div class="category-header {{ $category['color'] }}">
            <i class="{{ $category['icon'] }}"></i>
            <div>
                <div class="category-title">{{ $category['title'] }}</div>
                <div class="category-description">{{ $category['description'] }}</div>
            </div>
        </div>
        
        <div class="reports-grid">
            <div class="row">
                @foreach($category['reports'] as $report)
                <div class="col-md-6 col-lg-4">
                    <div class="report-card {{ isset($report['featured']) && $report['featured'] ? 'featured' : '' }}">
                        <div class="report-header">
                            <div class="report-icon">
                                <i class="{{ $report['icon'] }}"></i>
                            </div>
                            <div>
                                <div class="report-title">{{ $report['name'] }}</div>
                            </div>
                        </div>
                        
                        <div class="report-description">
                            {{ $report['description'] }}
                        </div>
                        
                        @if(isset($report['features']))
                        <div class="report-features">
                            @foreach($report['features'] as $feature)
                            <span class="feature-tag">{{ $feature }}</span>
                            @endforeach
                        </div>
                        @endif
                        
                        <div class="report-actions">
                            @if(Route::has($report['route']))
                                <a href="{{ route($report['route']) }}" class="btn-view-report">
                                    <i class="fas fa-chart-bar"></i> @lang('advancedreports::lang.view_report')
                                </a>
                                <span class="report-status">✓ Available</span>
                            @else
                                <button class="btn-view-report" style="opacity: 0.6; cursor: not-allowed;" disabled>
                                    <i class="fas fa-clock"></i> @lang('advancedreports::lang.coming_soon')
                                </button>
                                <span class="report-status" style="color: #ffc107;">⏳ In Development</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach

    <!-- Quick Access Section -->
    <div class="row" style="margin-top: 40px;">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fas fa-rocket"></i> @lang('advancedreports::lang.quick_access_popular_reports')</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="{{ route('advancedreports.customer-group-performance.index') }}" class="btn btn-app">
                                <i class="fas fa-users"></i> Customer Groups
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('advancedreports.customer-monthly.index') }}" class="btn btn-app">
                                <i class="fas fa-chart-line"></i> Sales Analytics
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('advancedreports.expense-monthly.index') }}" class="btn btn-app">
                                <i class="fas fa-credit-card"></i> Expense Reports
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('advancedreports.inventory-turnover.index') }}" class="btn btn-app">
                                <i class="fas fa-warehouse"></i> Inventory
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>

@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // Add smooth scrolling for category navigation
    $('.category-header').click(function() {
        $(this).next('.reports-grid').slideToggle(300);
    });
    
    // Add loading animation for report cards
    $('.btn-view-report').on('click', function() {
        if (!$(this).prop('disabled')) {
            $(this).html('<i class="fas fa-spinner fa-spin"></i> Loading...');
        }
    });
    
    // Animate stats cards on load
    $('.stats-card').each(function(index) {
        $(this).delay(index * 100).animate({
            opacity: 1
        }, 500);
    });
});
</script>
@endsection
