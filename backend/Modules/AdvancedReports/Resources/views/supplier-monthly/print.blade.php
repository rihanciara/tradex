<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Monthly Sales Report - {{ $year ?? date('Y') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        .print-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header Section */
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 20px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .report-title {
            font-size: 20px;
            color: #3498db;
            margin-bottom: 10px;
        }

        .report-period {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 15px;
        }

        .print-info {
            font-size: 11px;
            color: #95a5a6;
        }

        /* Summary Cards */
        .summary-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 15px;
        }

        .summary-card {
            flex: 1;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }

        .summary-card.primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }

        .summary-card.success {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
        }

        .summary-card.info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }

        .summary-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 18px;
            font-weight: bold;
        }

        /* Table Styles */
        .table-container {
            margin-bottom: 30px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .data-table thead {
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            color: white;
        }

        .data-table th {
            padding: 12px 8px;
            text-align: center;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        .data-table th:last-child {
            border-right: none;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .data-table tbody tr:hover {
            background-color: #e3f2fd;
        }

        .data-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            text-align: center;
        }

        .data-table td:first-child {
            text-align: left;
            font-weight: 500;
        }

        .data-table td:last-child {
            border-right: none;
        }

        .currency {
            text-align: right !important;
            font-family: 'Courier New', monospace;
        }

        .positive {
            color: #27ae60;
            font-weight: 600;
        }

        .negative {
            color: #e74c3c;
            font-weight: 600;
        }

        /* Footer Total Row */
        .data-table tfoot {
            background: linear-gradient(135deg, #ecf0f1 0%, #bdc3c7 100%);
            font-weight: bold;
        }

        .data-table tfoot td {
            padding: 12px 8px;
            border-top: 2px solid #34495e;
            font-weight: 600;
        }

        /* Footer Section */
        .report-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-left {
            font-size: 11px;
            color: #7f8c8d;
        }

        .footer-right {
            font-size: 11px;
            color: #7f8c8d;
        }

        /* Print Specific Styles */
        @media print {
            body {
                font-size: 10px;
            }

            .print-container {
                padding: 10px;
            }

            .summary-section {
                margin-bottom: 20px;
            }

            .summary-card {
                padding: 10px;
            }

            .data-table {
                font-size: 9px;
            }

            .data-table th,
            .data-table td {
                padding: 6px 4px;
            }

            .report-footer {
                margin-top: 20px;
            }

            /* Ensure colors print properly */
            .summary-card.primary,
            .summary-card.success,
            .summary-card.info {
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }

            .data-table thead {
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
        }

        /* No data message */
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-style: italic;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .summary-section {
                flex-direction: column;
            }

            .data-table {
                font-size: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="print-container">
        <!-- Header Section -->
        <div class="report-header">
            <div class="company-name">{{ $business_name ?? config('app.name') }}</div>
            <div class="report-title">Supplier Monthly Sales Report</div>
            <div class="report-period">
                @if(isset($year))
                Year: {{ $year }}
                @endif
                @if(isset($filters) && !empty($filters))
                @if(!empty($filters['supplier_name']))
                | Supplier: {{ $filters['supplier_name'] }}
                @endif
                @if(!empty($filters['location_name']))
                | Location: {{ $filters['location_name'] }}
                @endif
                @endif
            </div>
            <div class="print-info">
                Generated on {{ date('F j, Y \a\t g:i A') }} | Page <span id="pageNumber"></span>
            </div>
        </div>

        <!-- Summary Cards -->
        @if(isset($summary))
        <div class="summary-section">
            <div class="summary-card primary">
                <div class="summary-label">Total Suppliers</div>
                <div class="summary-value">{{ number_format($summary['total_suppliers'] ?? 0) }}</div>
            </div>
            <div class="summary-card info">
                <div class="summary-label">Total Transactions</div>
                <div class="summary-value">{{ number_format($summary['total_transactions'] ?? 0) }}</div>
            </div>
            <div class="summary-card success">
                <div class="summary-label">Total Sales</div>
                <div class="summary-value">${{ number_format($summary['total_sales'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Total Quantity</div>
                <div class="summary-value">{{ number_format($summary['total_quantity'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-card {{ ($summary['total_profit'] ?? 0) >= 0 ? 'success' : '' }}">
                <div class="summary-label">Total Profit</div>
                <div class="summary-value {{ ($summary['total_profit'] ?? 0) >= 0 ? '' : 'negative' }}">
                    ${{ number_format($summary['total_profit'] ?? 0, 2) }}
                </div>
            </div>
        </div>
        @endif

        <!-- Data Table -->
        <div class="table-container">
            @if(isset($supplierData) && count($supplierData) > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 200px;">Supplier</th>
                        <th style="width: 80px;">Jan</th>
                        <th style="width: 80px;">Feb</th>
                        <th style="width: 80px;">Mar</th>
                        <th style="width: 80px;">Apr</th>
                        <th style="width: 80px;">May</th>
                        <th style="width: 80px;">Jun</th>
                        <th style="width: 80px;">Jul</th>
                        <th style="width: 80px;">Aug</th>
                        <th style="width: 80px;">Sep</th>
                        <th style="width: 80px;">Oct</th>
                        <th style="width: 80px;">Nov</th>
                        <th style="width: 80px;">Dec</th>
                        <th style="width: 100px;">Total Sales</th>
                        <th style="width: 80px;">Total Qty</th>
                        <th style="width: 100px;">Gross Profit</th>
                        <th style="width: 80px;">Profit %</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $monthTotals = [
                    'jan' => 0, 'feb' => 0, 'mar' => 0, 'apr' => 0,
                    'may' => 0, 'jun' => 0, 'jul' => 0, 'aug' => 0,
                    'sep' => 0, 'oct' => 0, 'nov' => 0, 'dec' => 0
                    ];
                    $grandTotalSales = 0;
                    $grandTotalQuantity = 0;
                    $grandTotalProfit = 0;
                    @endphp

                    @foreach($supplierData as $supplier)
                    @php
                    $totalSales = $supplier['total_sales'] ?? 0;
                    $totalCost = $supplier['total_cost'] ?? 0;
                    $totalQuantity = $supplier['total_quantity'] ?? 0;
                    $grossProfit = $totalSales - $totalCost;
                    $profitPercent = $totalSales > 0 ? ($grossProfit / $totalSales) * 100 : 0;

                    // Add to grand totals
                    $grandTotalSales += $totalSales;
                    $grandTotalQuantity += $totalQuantity;
                    $grandTotalProfit += $grossProfit;

                    // Add to month totals
                    foreach(['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'] as
                    $month) {
                    $monthTotals[$month] += $supplier[$month] ?? 0;
                    }
                    @endphp
                    <tr>
                        <td>{{ $supplier['supplier_name'] ?? 'No Supplier' }}</td>
                        <td class="currency">${{ number_format($supplier['jan'] ?? 0, 2) }}</td>
                        <td class="currency">${{ number_format($supplier['feb'] ?? 0, 2) }}</td>
                        <td class="currency">${{ number_format($supplier['mar'] ?? 0, 2) }}</td>
                        <td class="currency">${{ number_format($supplier['apr'] ?? 0, 2) }}</td>
                        <td class="currency">${{ number_format($supplier['may'] ?? 0, 2) }}</td>
                        <td class="currency">${{ number_format($supplier['jun'] ?? 0, 2) }}</td>
                        <td class="currency">${{ number_format($supplier['jul'] ?? 0, 2) }}</td>
                        <td class="currency">${{ number_format($supplier['aug'] ?? 0, 2) }}</td>
                        <td class="currency">${{ number_format($supplier['sep'] ?? 0, 2) }}</td>
                        <td class="currency">${{ number_format($supplier['oct'] ?? 0, 2) }}</td>
                        <td class="currency">${{ number_format($supplier['nov'] ?? 0, 2) }}</td>
                        <td class="currency">${{ number_format($supplier['dec'] ?? 0, 2) }}</td>
                        <td class="currency">${{ number_format($totalSales, 2) }}</td>
                        <td class="currency">{{ number_format($totalQuantity, 2) }}</td>
                        <td class="currency {{ $grossProfit >= 0 ? 'positive' : 'negative' }}">
                            ${{ number_format($grossProfit, 2) }}
                        </td>
                        <td class="currency {{ $profitPercent >= 0 ? 'positive' : 'negative' }}">
                            {{ number_format($profitPercent, 2) }}%
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>TOTAL:</strong></td>
                        <td class="currency"><strong>${{ number_format($monthTotals['jan'], 2) }}</strong></td>
                        <td class="currency"><strong>${{ number_format($monthTotals['feb'], 2) }}</strong></td>
                        <td class="currency"><strong>${{ number_format($monthTotals['mar'], 2) }}</strong></td>
                        <td class="currency"><strong>${{ number_format($monthTotals['apr'], 2) }}</strong></td>
                        <td class="currency"><strong>${{ number_format($monthTotals['may'], 2) }}</strong></td>
                        <td class="currency"><strong>${{ number_format($monthTotals['jun'], 2) }}</strong></td>
                        <td class="currency"><strong>${{ number_format($monthTotals['jul'], 2) }}</strong></td>
                        <td class="currency"><strong>${{ number_format($monthTotals['aug'], 2) }}</strong></td>
                        <td class="currency"><strong>${{ number_format($monthTotals['sep'], 2) }}</strong></td>
                        <td class="currency"><strong>${{ number_format($monthTotals['oct'], 2) }}</strong></td>
                        <td class="currency"><strong>${{ number_format($monthTotals['nov'], 2) }}</strong></td>
                        <td class="currency"><strong>${{ number_format($monthTotals['dec'], 2) }}</strong></td>
                        <td class="currency"><strong>${{ number_format($grandTotalSales, 2) }}</strong></td>
                        <td class="currency"><strong>{{ number_format($grandTotalQuantity, 2) }}</strong></td>
                        <td class="currency {{ $grandTotalProfit >= 0 ? 'positive' : 'negative' }}">
                            <strong>${{ number_format($grandTotalProfit, 2) }}</strong>
                        </td>
                        <td class="currency {{ $grandTotalProfit >= 0 ? 'positive' : 'negative' }}">
                            <strong>{{ $grandTotalSales > 0 ? number_format(($grandTotalProfit / $grandTotalSales) *
                                100, 2) : 0 }}%</strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
            @else
            <div class="no-data">
                <h3>No Data Available</h3>
                <p>No supplier sales data found for the selected criteria.</p>
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="report-footer">
            <div class="footer-left">
                <strong>{{ $business_name ?? config('app.name') }}</strong><br>
                Generated by Advanced Reports Module
            </div>
            <div class="footer-right">
                Report Date: {{ date('F j, Y') }}<br>
                Time: {{ date('g:i A') }}
            </div>
        </div>
    </div>

    <script>
        // Auto-trigger print dialog when page loads
        window.onload = function() {
            window.print();
        };

        // Add page numbers for print
        window.onbeforeprint = function() {
            document.getElementById('pageNumber').textContent = '1';
        };
    </script>
</body>

</html>