@extends('layouts.app')
@section('title', __('Tax Filing Report'))

@php
// Get currency settings from session
$currency_symbol = session('currency')['symbol'] ?? '$';
$currency_precision = session('business.currency_precision') ?? 2;
$currency_placement = session('business.currency_symbol_placement') ?? 'before';
@endphp

@section('content')
<style>
@media print {
    .no-print { display: none !important; }
    .content-wrapper { margin: 0 !important; padding: 0 !important; }
    .main-header, .main-sidebar, .control-sidebar { display: none !important; }
    .content { margin: 0 !important; padding: 10px !important; }
    body { color: #000 !important; }
    
    /* Compact widgets for print */
    .info-box { 
        border: 1px solid #ccc !important;
        height: 50px !important;
        min-height: 50px !important;
        max-height: 50px !important;
        margin-bottom: 5px !important;
        overflow: hidden !important;
    }
    
    
    .info-box-content {
        margin-left: 50px !important;
        padding: 5px 10px !important;
        height: 50px !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
    }
    
    .info-box-text {
        font-size: 9px !important;
        margin-bottom: 2px !important;
        line-height: 1 !important;
    }
    
    .info-box-number {
        font-size: 14px !important;
        font-weight: bold !important;
        line-height: 1 !important;
    }
    
    .box { 
        border: 1px solid #ccc !important;
        margin-bottom: 15px !important;
    }
    
    .box-header {
        padding: 10px !important;
        border-bottom: 1px solid #ccc !important;
    }
    
    .box-body {
        padding: 10px !important;
    }
    
    .box-title {
        font-size: 14px !important;
        font-weight: bold !important;
    }
    
    /* Table styling for print */
    .table {
        font-size: 10px !important;
    }
    
    .table th,
    .table td {
        padding: 5px !important;
        border: 1px solid #ddd !important;
    }
    
    /* Alert styling for print */
    .alert {
        padding: 10px !important;
        margin: 10px 0 !important;
        border: 1px solid #ccc !important;
    }
    
    /* Page breaks */
    .page-break {
        page-break-before: always;
    }
    
    /* Avoid breaking inside widgets */
    .info-box,
    .box {
        page-break-inside: avoid;
    }
    
    /* Additional compact spacing */
    .row {
        margin-bottom: 10px !important;
    }
    
    .col-lg-3,
    .col-xs-6 {
        padding-left: 5px !important;
        padding-right: 5px !important;
    }
}
</style>

<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        <i class="fas fa-file-alt"></i> {{ __('Tax Filing Report') }}
        <small class="text-muted">{{ ucfirst($period) }} Report for {{ $year }}</small>
    </h1>
    <div class="row">
        <div class="col-md-12">
            <button class="btn btn-primary no-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print Report
            </button>
            <button class="btn btn-default no-print" onclick="window.close()">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        </div>
    </div>
</section>

<section class="content">
    <!-- Business Header -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border text-center">
                    <h3 class="box-title" style="font-size: 24px; font-weight: bold;">
                        {{ $business->name }}
                    </h3>
                    <br>
                    <p class="text-muted" style="margin: 10px 0;">
                        {{ $business->city }}, {{ $business->state }} {{ $business->zip_code }}<br>
                        @if($business->tax_number_1)
                            Tax ID: {{ $business->tax_number_1 }}
                        @endif
                    </p>
                    <h4 class="text-center" style="color: #3c8dbc; margin: 20px 0;">
                        Tax Filing Assistance Report - {{ ucfirst($period) }} {{ $year }}
                    </h4>
                    <p class="text-center text-muted">
                        Generated on: {{ \Carbon\Carbon::now()->format('F d, Y \a\t g:i A') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filing Summary -->
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-chart-line"></i> Filing Summary
                    </h3>
                </div>
                <div class="box-body">
                    @php
                        $total_gross_sales = collect($filing_data)->sum('gross_sales');
                        $total_taxable_sales = collect($filing_data)->sum('taxable_sales');
                        $total_tax_collected = collect($filing_data)->sum('tax_collected');
                        $total_tax_paid = collect($filing_data)->sum('tax_paid_on_purchases');
                        $net_liability = $total_tax_collected - $total_tax_paid;
                        $periods_count = count($filing_data);
                    @endphp
                    
                    <div class="row">
                        <div class="col-lg-3 col-xs-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-blue"><i class="fas fa-coins"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Gross Sales</span>
                                    <span class="info-box-number">
                                        @if($currency_placement === 'before')
                                            {{ $currency_symbol }}{{ number_format($total_gross_sales, $currency_precision) }}
                                        @else
                                            {{ number_format($total_gross_sales, $currency_precision) }}{{ $currency_symbol }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-green"><i class="fas fa-calculator"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Tax Collected</span>
                                    <span class="info-box-number">
                                        @if($currency_placement === 'before')
                                            {{ $currency_symbol }}{{ number_format($total_tax_collected, $currency_precision) }}
                                        @else
                                            {{ number_format($total_tax_collected, $currency_precision) }}{{ $currency_symbol }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-yellow"><i class="fas fa-file-invoice-dollar"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Net Tax Liability</span>
                                    <span class="info-box-number {{ $net_liability >= 0 ? 'text-red' : 'text-green' }}">
                                        @if($currency_placement === 'before')
                                            {{ $currency_symbol }}{{ number_format($net_liability, $currency_precision) }}
                                        @else
                                            {{ number_format($net_liability, $currency_precision) }}{{ $currency_symbol }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-xs-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-purple"><i class="fas fa-calendar-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Filing Periods</span>
                                    <span class="info-box-number">{{ $periods_count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Filing Data -->
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-table"></i> Detailed Filing Data
                    </h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr style="background-color: #f4f4f4;">
                                <th>Period</th>
                                <th>Gross Sales</th>
                                <th>Taxable Sales</th>
                                <th>Tax Collected</th>
                                <th>Tax Paid on Purchases</th>
                                <th>Net Liability</th>
                                <th>Filing Deadline</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($filing_data as $data)
                            @php
                                $period_net_liability = $data['tax_collected'] - $data['tax_paid_on_purchases'];
                            @endphp
                            <tr>
                                <td><strong>{{ $data['period'] }}</strong></td>
                                <td>
                                    @if($currency_placement === 'before')
                                        {{ $currency_symbol }}{{ number_format($data['gross_sales'], $currency_precision) }}
                                    @else
                                        {{ number_format($data['gross_sales'], $currency_precision) }}{{ $currency_symbol }}
                                    @endif
                                </td>
                                <td>
                                    @if($currency_placement === 'before')
                                        {{ $currency_symbol }}{{ number_format($data['taxable_sales'], $currency_precision) }}
                                    @else
                                        {{ number_format($data['taxable_sales'], $currency_precision) }}{{ $currency_symbol }}
                                    @endif
                                </td>
                                <td>
                                    @if($currency_placement === 'before')
                                        {{ $currency_symbol }}{{ number_format($data['tax_collected'], $currency_precision) }}
                                    @else
                                        {{ number_format($data['tax_collected'], $currency_precision) }}{{ $currency_symbol }}
                                    @endif
                                </td>
                                <td>
                                    @if($currency_placement === 'before')
                                        {{ $currency_symbol }}{{ number_format($data['tax_paid_on_purchases'], $currency_precision) }}
                                    @else
                                        {{ number_format($data['tax_paid_on_purchases'], $currency_precision) }}{{ $currency_symbol }}
                                    @endif
                                </td>
                                <td class="{{ $period_net_liability >= 0 ? 'text-red' : 'text-green' }}">
                                    @if($currency_placement === 'before')
                                        {{ $currency_symbol }}{{ number_format($period_net_liability, $currency_precision) }}
                                    @else
                                        {{ number_format($period_net_liability, $currency_precision) }}{{ $currency_symbol }}
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($data['filing_deadline'])->format('M d, Y') }}</td>
                                <td>
                                    @php
                                        $deadline = \Carbon\Carbon::parse($data['filing_deadline']);
                                        $now = \Carbon\Carbon::now();
                                        $days_remaining = $now->diffInDays($deadline, false);
                                        
                                        if ($days_remaining < 0) {
                                            $status_class = 'label-danger';
                                            $status_text = 'Overdue';
                                        } elseif ($days_remaining <= 7) {
                                            $status_class = 'label-warning';
                                            $status_text = 'Due Soon';
                                        } else {
                                            $status_class = 'label-success';
                                            $status_text = 'On Track';
                                        }
                                    @endphp
                                    <span class="label {{ $status_class }}">{{ $status_text }}</span>
                                    <small class="text-muted">
                                        @if ($days_remaining < 0)
                                            ({{ abs($days_remaining) }} days overdue)
                                        @else
                                            ({{ $days_remaining }} days remaining)
                                        @endif
                                    </small>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot style="background-color: #f9f9f9; font-weight: bold;">
                            <tr>
                                <td>TOTAL</td>
                                <td>
                                    @if($currency_placement === 'before')
                                        {{ $currency_symbol }}{{ number_format($total_gross_sales, $currency_precision) }}
                                    @else
                                        {{ number_format($total_gross_sales, $currency_precision) }}{{ $currency_symbol }}
                                    @endif
                                </td>
                                <td>
                                    @if($currency_placement === 'before')
                                        {{ $currency_symbol }}{{ number_format($total_taxable_sales, $currency_precision) }}
                                    @else
                                        {{ number_format($total_taxable_sales, $currency_precision) }}{{ $currency_symbol }}
                                    @endif
                                </td>
                                <td>
                                    @if($currency_placement === 'before')
                                        {{ $currency_symbol }}{{ number_format($total_tax_collected, $currency_precision) }}
                                    @else
                                        {{ number_format($total_tax_collected, $currency_precision) }}{{ $currency_symbol }}
                                    @endif
                                </td>
                                <td>
                                    @if($currency_placement === 'before')
                                        {{ $currency_symbol }}{{ number_format($total_tax_paid, $currency_precision) }}
                                    @else
                                        {{ number_format($total_tax_paid, $currency_precision) }}{{ $currency_symbol }}
                                    @endif
                                </td>
                                <td class="{{ $net_liability >= 0 ? 'text-red' : 'text-green' }}">
                                    @if($currency_placement === 'before')
                                        {{ $currency_symbol }}{{ number_format($net_liability, $currency_precision) }}
                                    @else
                                        {{ number_format($net_liability, $currency_precision) }}{{ $currency_symbol }}
                                    @endif
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Filing Instructions -->
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-info-circle"></i> Filing Instructions & Important Notes
                    </h3>
                </div>
                <div class="box-body">
                    <div class="alert alert-info">
                        <h4><i class="icon fas fa-info"></i> Important Filing Information:</h4>
                        <ul>
                            <li><strong>Review Accuracy:</strong> Please verify all amounts before filing with tax authorities</li>
                            <li><strong>Filing Deadlines:</strong> Ensure all returns are filed by the specified deadlines to avoid penalties</li>
                            <li><strong>Supporting Documents:</strong> Maintain proper documentation for all transactions and tax calculations</li>
                            <li><strong>Net Liability:</strong> Positive amounts indicate tax owed, negative amounts indicate refunds or credits</li>
                            <li><strong>Record Keeping:</strong> Keep this report as part of your tax filing records</li>
                        </ul>
                    </div>
                    
                    @if($net_liability > 0)
                    <div class="alert alert-warning">
                        <h4><i class="icon fas fa-exclamation-triangle"></i> Payment Due:</h4>
                        <p>You have a net tax liability of <strong>
                            @if($currency_placement === 'before')
                                {{ $currency_symbol }}{{ number_format($net_liability, $currency_precision) }}
                            @else
                                {{ number_format($net_liability, $currency_precision) }}{{ $currency_symbol }}
                            @endif
                        </strong> for the {{ $period }} period(s) in {{ $year }}. Please ensure payment is made by the respective filing deadlines.</p>
                    </div>
                    @elseif($net_liability < 0)
                    <div class="alert alert-success">
                        <h4><i class="icon fas fa-check"></i> Credit Available:</h4>
                        <p>You have a net tax credit of <strong>
                            @if($currency_placement === 'before')
                                {{ $currency_symbol }}{{ number_format(abs($net_liability), $currency_precision) }}
                            @else
                                {{ number_format(abs($net_liability), $currency_precision) }}{{ $currency_symbol }}
                            @endif
                        </strong> for the {{ $period }} period(s) in {{ $year }}. This may be applied to future tax liabilities or claimed as a refund.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection