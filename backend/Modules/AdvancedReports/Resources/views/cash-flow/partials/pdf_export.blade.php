<!DOCTYPE html>
<html>
<head>
    <title>Cash Flow Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .summary-table th, .summary-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .summary-table th { background-color: #f2f2f2; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 12px; }
        .data-table th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .positive { color: green; }
        .negative { color: red; }
        .section-title { background-color: #e9ecef; padding: 10px; margin: 20px 0 10px 0; font-weight: bold; }
        .summary-row { background-color: #f8f9fa; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Cash Flow Report</h1>
        <p><strong>Period:</strong> {{ $export_data['period'] }}</p>
        <p><strong>Location:</strong> {{ $export_data['location'] }}</p>
        <p><strong>Generated:</strong> {{ date('Y-m-d H:i:s') }}</p>
    </div>

    <div class="section-title">Executive Summary</div>
    <table class="summary-table">
        <tr>
            <th>Metric</th>
            <th class="text-right">Amount</th>
        </tr>
        <tr>
            <td>Opening Balance</td>
            <td class="text-right">${{ $export_data['summary']['opening_balance'] }}</td>
        </tr>
        <tr>
            <td>Cash Inflows</td>
            <td class="text-right positive">${{ $export_data['summary']['cash_inflows'] }}</td>
        </tr>
        <tr>
            <td>Cash Outflows</td>
            <td class="text-right negative">${{ $export_data['summary']['cash_outflows'] }}</td>
        </tr>
        <tr class="summary-row">
            <td>Net Cash Flow</td>
            <td class="text-right @if((float)str_replace(',', '', $export_data['summary']['net_cash_flow'])) >= 0) positive @else negative @endif">
                ${{ $export_data['summary']['net_cash_flow'] }}
            </td>
        </tr>
        <tr class="summary-row">
            <td>Closing Balance</td>
            <td class="text-right">${{ $export_data['summary']['closing_balance'] }}</td>
        </tr>
        <tr>
            <td>Outstanding Receivables</td>
            <td class="text-right">${{ $export_data['summary']['total_receivables'] }}</td>
        </tr>
        <tr>
            <td>Outstanding Payables</td>
            <td class="text-right">${{ $export_data['summary']['total_payables'] }}</td>
        </tr>
    </table>

    @if(!empty($export_data['payment_methods']))
    <div class="section-title">Payment Method Breakdown</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Payment Method</th>
                <th class="text-center">Transactions</th>
                <th class="text-right">Total Amount</th>
                <th class="text-right">Inflows</th>
                <th class="text-right">Outflows</th>
            </tr>
        </thead>
        <tbody>
            @foreach($export_data['payment_methods'] as $method)
            <tr>
                <td>{{ ucfirst(str_replace('_', ' ', $method['method'])) }}</td>
                <td class="text-center">{{ $method['transaction_count'] }}</td>
                <td class="text-right">${{ number_format($method['total_amount'], 2) }}</td>
                <td class="text-right">${{ number_format($method['inflow_amount'], 2) }}</td>
                <td class="text-right">${{ number_format($method['outflow_amount'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(!empty($export_data['daily_flows']))
    <div class="section-title">Daily Cash Flow (Last {{ count($export_data['daily_flows']) }} Days)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th class="text-right">Inflows</th>
                <th class="text-right">Outflows</th>
                <th class="text-right">Net Flow</th>
                <th class="text-right">Running Balance</th>
            </tr>
        </thead>
        <tbody>
            @php $displayLimit = 15; @endphp
            @foreach(array_slice($export_data['daily_flows'], -$displayLimit) as $day)
            <tr>
                <td>{{ \Carbon\Carbon::parse($day['date'])->format('M d, Y') }}</td>
                <td class="text-right">${{ number_format($day['cash_inflow'], 2) }}</td>
                <td class="text-right">${{ number_format($day['cash_outflow'], 2) }}</td>
                <td class="text-right @if($day['net_flow'] >= 0) positive @else negative @endif">
                    ${{ number_format($day['net_flow'], 2) }}
                </td>
                <td class="text-right">${{ number_format($day['running_balance'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(!empty($export_data['receivables']))
    <div class="section-title">Outstanding Receivables (Top 20)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Invoice No</th>
                <th>Customer</th>
                <th>Date</th>
                <th class="text-right">Total</th>
                <th class="text-right">Due Amount</th>
                <th class="text-center">Days Overdue</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_slice($export_data['receivables'], 0, 20) as $receivable)
            <tr>
                <td>{{ $receivable['invoice_no'] }}</td>
                <td>{{ $receivable['customer_name'] ?: 'Walk-in Customer' }}</td>
                <td>{{ \Carbon\Carbon::parse($receivable['transaction_date'])->format('M d, Y') }}</td>
                <td class="text-right">${{ number_format($receivable['final_total'], 2) }}</td>
                <td class="text-right">${{ number_format($receivable['due_amount'], 2) }}</td>
                <td class="text-center @if($receivable['days_overdue'] > 30) negative @endif">
                    {{ $receivable['days_overdue'] }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(!empty($export_data['payables']))
    <div class="section-title">Outstanding Payables (Top 20)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Reference</th>
                <th>Supplier</th>
                <th>Date</th>
                <th>Type</th>
                <th class="text-right">Total</th>
                <th class="text-right">Due Amount</th>
                <th class="text-center">Days Overdue</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_slice($export_data['payables'], 0, 20) as $payable)
            <tr>
                <td>{{ $payable['ref_no'] }}</td>
                <td>{{ $payable['supplier_name'] ?: 'Unknown' }}</td>
                <td>{{ \Carbon\Carbon::parse($payable['transaction_date'])->format('M d, Y') }}</td>
                <td>{{ ucfirst($payable['type']) }}</td>
                <td class="text-right">${{ number_format($payable['final_total'], 2) }}</td>
                <td class="text-right">${{ number_format($payable['due_amount'], 2) }}</td>
                <td class="text-center @if($payable['days_overdue'] > 30) negative @endif">
                    {{ $payable['days_overdue'] }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div style="margin-top: 50px; text-align: center; color: #666; font-size: 12px;">
        <p>Generated by Advanced Reports Module - {{ date('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>