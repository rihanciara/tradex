<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GST Purchase Report - {{ $business->name ?? config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
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

        /* Header Section - India GST Compliance */
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #FF6B35;
            padding-bottom: 20px;
            position: relative;
        }

        .company-details {
            margin-bottom: 15px;
        }

        .company-name {
            font-size: 26px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .company-address {
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }

        .company-gstin {
            font-size: 14px;
            color: #e74c3c;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .report-title {
            font-size: 22px;
            color: #FF6B35;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .report-period {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 15px;
        }

        .compliance-note {
            position: absolute;
            top: 0;
            right: 0;
            background: #f8f9fa;
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 10px;
            color: #6c757d;
        }

        .print-info {
            font-size: 11px;
            color: #95a5a6;
        }

        /* Filters Display */
        .filters-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 25px;
        }

        .filters-title {
            font-size: 14px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }

        .filter-item {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 5px;
            font-size: 11px;
        }

        .filter-label {
            font-weight: 600;
            color: #6c757d;
        }

        .filter-value {
            color: #495057;
        }

        /* Summary Cards - India GST Format */
        .summary-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            gap: 15px;
        }

        .summary-card {
            flex: 1;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            position: relative;
        }

        .summary-card.primary {
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C42 100%);
            color: white;
        }

        .summary-card.success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .summary-card.warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
        }

        .summary-card.info {
            background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
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

        /* Table Styles - India GST Compliance */
        .table-container {
            margin-bottom: 25px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .data-table thead {
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C42 100%);
            color: white;
        }

        .data-table th {
            padding: 12px 6px;
            text-align: center;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 9px;
        }

        .data-table th:last-child {
            border-right: none;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .data-table tbody tr:hover {
            background-color: #fff3cd;
        }

        .data-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            text-align: center;
            font-size: 9px;
        }

        .data-table td:first-child,
        .data-table td:nth-child(3),
        .data-table td:nth-child(4) {
            text-align: left;
            font-weight: 500;
        }

        .data-table td:last-child {
            border-right: none;
        }

        .currency {
            text-align: right !important;
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }

        .text-success {
            color: #28a745 !important;
        }

        .text-primary {
            color: #007bff !important;
        }

        .text-warning {
            color: #ffc107 !important;
        }

        .text-muted {
            color: #6c757d !important;
        }

        /* Footer Total Row */
        .data-table tfoot {
            background: linear-gradient(135deg, #343a40 0%, #495057 100%);
            color: white;
            font-weight: bold;
        }

        .data-table tfoot td {
            padding: 12px 6px;
            border-top: 2px solid #FF6B35;
            font-weight: 600;
        }

        /* GST Compliance Section */
        .gst-compliance {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }

        .compliance-title {
            font-size: 16px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
            text-align: center;
            border-bottom: 2px solid #FF6B35;
            padding-bottom: 10px;
        }

        .gst-summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .gst-summary-table th,
        .gst-summary-table td {
            padding: 10px;
            border: 1px solid #dee2e6;
            text-align: center;
            font-size: 12px;
        }

        .gst-summary-table th {
            background: #e9ecef;
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

        .authorized-signature {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }

        .signature-line {
            border-top: 1px solid #333;
            width: 200px;
            margin: 40px auto 10px;
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
                margin-bottom: 15px;
            }

            .summary-card {
                padding: 10px;
            }

            .data-table {
                font-size: 8px;
            }

            .data-table th,
            .data-table td {
                padding: 4px 3px;
            }

            .report-footer {
                margin-top: 20px;
            }

            /* Ensure colors print properly */
            .summary-card.primary,
            .summary-card.success,
            .summary-card.warning,
            .summary-card.info {
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }

            .data-table thead {
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }

            .page-break {
                page-break-before: always;
            }
        }

        /* No data message */
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-style: italic;
        }

        /* Badge styles */
        .badge {
            display: inline-block;
            padding: 3px 6px;
            font-size: 8px;
            font-weight: 600;
            border-radius: 3px;
            text-transform: uppercase;
        }

        .badge-primary {
            background-color: #007bff;
            color: white;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .summary-section {
                flex-direction: column;
            }

            .data-table {
                font-size: 9px;
            }
        }
    </style>
</head>

<body>
    <div class="print-container">
        <!-- Header Section -->
        <div class="report-header">
            <div class="compliance-note">
                <strong>GST Compliance Report</strong><br>
                As per Indian Tax Laws
            </div>

            <div class="company-details">
                <div class="company-name">{{ $business->name ?? config('app.name') }}</div>
                @if(isset($business->address))
                <div class="company-address">
                    {{ $business->address ?? '' }}
                    @if(isset($business->city))
                    {{ $business->city ?? '' }}
                    @endif
                    @if(isset($business->state))
                    {{ $business->state ?? '' }}
                    @endif
                    @if(isset($business->zip_code))
                    - {{ $business->zip_code ?? '' }}
                    @endif
                </div>
                @endif
                @if(isset($business->tax_number_1))
                <div class="company-gstin">
                    <strong>GSTIN: {{ $business->tax_number_1 ?? 'Not Available' }}</strong>
                </div>
                @endif
            </div>

            <div class="report-title">GST Purchase Report</div>
            <div class="report-period">
                Period: {{ $summary['date_range'] ?? 'All Dates' }}
            </div>
            <div class="print-info">
                Generated on {{ date('F j, Y \a\t g:i A') }} | Report ID: GPR-{{ date('YmdHis') }}
            </div>
        </div>

        <!-- Filters Section -->
        @if(isset($filters) && !empty(array_filter($filters)))
        <div class="filters-section">
            <div class="filters-title">Applied Filters</div>
            @if(!empty($filters['supplier_name']))
            <div class="filter-item">
                <span class="filter-label">Supplier:</span>
                <span class="filter-value">{{ $filters['supplier_name'] }}</span>
            </div>
            @endif
            @if(!empty($filters['location_name']))
            <div class="filter-item">
                <span class="filter-label">Location:</span>
                <span class="filter-value">{{ $filters['location_name'] }}</span>
            </div>
            @endif
            @if(!empty($filters['category_name']))
            <div class="filter-item">
                <span class="filter-label">Category:</span>
                <span class="filter-value">{{ $filters['category_name'] }}</span>
            </div>
            @endif
            @if(!empty($filters['tax_rate_name']))
            <div class="filter-item">
                <span class="filter-label">Tax Rate:</span>
                <span class="filter-value">{{ $filters['tax_rate_name'] }}</span>
            </div>
            @endif
        </div>
        @endif

        <!-- Summary Cards -->
        <div class="summary-section">
            <div class="summary-card primary">
                <div class="summary-label">Total Transactions</div>
                <div class="summary-value">{{ number_format($summary['total_transactions'] ?? 0) }}</div>
            </div>
            <div class="summary-card success">
                <div class="summary-label">Total Suppliers</div>
                <div class="summary-value">{{ number_format($summary['total_suppliers'] ?? 0) }}</div>
            </div>
            <div class="summary-card warning">
                <div class="summary-label">Taxable Amount</div>
                <div class="summary-value">₹{{ number_format($summary['total_taxable_amount'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-card info">
                <div class="summary-label">Total Tax</div>
                <div class="summary-value">₹{{ number_format($summary['total_tax_amount'] ?? 0, 2) }}</div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="table-container">
            @if(isset($purchaseData) && count($purchaseData) > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Purchase Ref</th>
                        <th style="width: 70px;">Date</th>
                        <th style="width: 120px;">Supplier Details</th>
                        <th style="width: 100px;">Product</th>
                        <th style="width: 50px;">HSN</th>
                        <th style="width: 40px;">Qty</th>
                        <th style="width: 60px;">Rate</th>
                        <th style="width: 80px;">Taxable Value</th>
                        <th style="width: 40px;">GST%</th>
                        <th style="width: 70px;">Tax Amount</th>
                        <th style="width: 80px;">Total Amount</th>
                        <th style="width: 80px;">Location</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $totalTaxableAmount = 0;
                    $totalTaxAmount = 0;
                    $totalAmount = 0;
                    $totalQuantity = 0;
                    @endphp

                    @foreach($purchaseData as $purchase)
                    @php
                    $taxableAmount = $purchase->taxable_amount ?? 0;
                    $taxAmount = ($purchase->item_tax ?? 0) * ($purchase->quantity ?? 0);
                    $lineTotal = $purchase->total_amount ?? 0;

                    $totalTaxableAmount += $taxableAmount;
                    $totalTaxAmount += $taxAmount;
                    $totalAmount += $lineTotal;
                    $totalQuantity += $purchase->quantity ?? 0;
                    @endphp
                    <tr>
                        <td><strong>{{ $purchase->ref_no ?? '' }}</strong></td>
                        <td>{{ $purchase->transaction_date ?
                            \Carbon\Carbon::parse($purchase->transaction_date)->format('d-m-Y') : '' }}</td>
                        <td>
                            @if($purchase->supplier_business_name)
                            <strong>{{ $purchase->supplier_business_name }}</strong><br>
                            @endif
                            {{ $purchase->supplier ?? '' }}
                            @if($purchase->tax_number)
                            <br><small class="text-muted">{{ $purchase->tax_number }}</small>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $purchase->product_name ?? '' }}</strong>
                            @if($purchase->sku)
                            <br><small class="text-muted">{{ $purchase->sku }}</small>
                            @endif
                        </td>
                        <td>{{ $purchase->hsn_code ?? '-' }}</td>
                        <td class="text-center">
                            <span class="badge badge-primary">{{ number_format($purchase->quantity ?? 0, 2) }} {{
                                $purchase->unit ?? '' }}</span>
                        </td>
                        <td class="currency">₹{{ number_format($purchase->unit_price ?? 0, 2) }}</td>
                        <td class="currency">₹{{ number_format($taxableAmount, 2) }}</td>
                        <td class="text-center">
                            @if($purchase->tax_rate)
                            <span class="badge badge-warning">{{ $purchase->tax_rate }}%</span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="currency text-primary">₹{{ number_format($taxAmount, 2) }}</td>
                        <td class="currency text-success"><strong>₹{{ number_format($lineTotal, 2) }}</strong></td>
                        <td>{{ $purchase->location_name ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5"><strong>TOTAL:</strong></td>
                        <td class="text-center"><strong>{{ number_format($totalQuantity, 2) }}</strong></td>
                        <td></td>
                        <td class="currency"><strong>₹{{ number_format($totalTaxableAmount, 2) }}</strong></td>
                        <td></td>
                        <td class="currency"><strong>₹{{ number_format($totalTaxAmount, 2) }}</strong></td>
                        <td class="currency"><strong>₹{{ number_format($totalAmount, 2) }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            @else
            <div class="no-data">
                <h3>No Purchase Data Available</h3>
                <p>No purchase transactions found for the selected criteria.</p>
            </div>
            @endif
        </div>

        <!-- GST Compliance Summary -->
        @if(isset($purchaseData) && count($purchaseData) > 0)
        <div class="gst-compliance">
            <div class="compliance-title">GST Summary for Input Tax Credit</div>
            <table class="gst-summary-table">
                <thead>
                    <tr>
                        <th>Particulars</th>
                        <th>Taxable Value (₹)</th>
                        <th>Tax Amount (₹)</th>
                        <th>Total Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Total Purchases (Including GST)</strong></td>
                        <td class="currency">{{ number_format($summary['total_taxable_amount'] ?? 0, 2) }}</td>
                        <td class="currency">{{ number_format($summary['total_tax_amount'] ?? 0, 2) }}</td>
                        <td class="currency"><strong>{{ number_format($summary['total_amount'] ?? 0, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <td><strong>Available for Input Tax Credit</strong></td>
                        <td class="currency">{{ number_format($summary['total_taxable_amount'] ?? 0, 2) }}</td>
                        <td class="currency">{{ number_format($summary['total_tax_amount'] ?? 0, 2) }}</td>
                        <td class="currency"><strong>{{ number_format($summary['total_tax_amount'] ?? 0, 2) }}</strong>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Additional GST Details -->
            <div
                style="margin-top: 20px; padding: 15px; background: #fff; border: 1px solid #dee2e6; border-radius: 4px;">
                <h4 style="color: #495057; margin-bottom: 10px; font-size: 14px;">Important Notes:</h4>
                <ul style="margin: 0; padding-left: 20px; font-size: 11px; color: #6c757d;">
                    <li>This report is generated for GST compliance purposes as per Indian tax regulations.</li>
                    <li>Input Tax Credit (ITC) is available on eligible purchases as per GST laws.</li>
                    <li>Please verify GSTIN details of suppliers before claiming ITC.</li>
                    <li>Retain original tax invoices for audit purposes.</li>
                    <li>Report generated on {{ date('d-m-Y') }} at {{ date('H:i:s') }}</li>
                </ul>
            </div>
        </div>
        @endif

        <!-- Tax Rate-wise Summary -->
        @if(isset($purchaseData) && count($purchaseData) > 0)
        @php
        $taxRateSummary = [];
        foreach($purchaseData as $purchase) {
        $rate = $purchase->tax_rate ?? 0;
        if (!isset($taxRateSummary[$rate])) {
        $taxRateSummary[$rate] = [
        'taxable_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 0,
        'count' => 0
        ];
        }
        $taxRateSummary[$rate]['taxable_amount'] += $purchase->taxable_amount ?? 0;
        $taxRateSummary[$rate]['tax_amount'] += ($purchase->item_tax ?? 0) * ($purchase->quantity ?? 0);
        $taxRateSummary[$rate]['total_amount'] += $purchase->total_amount ?? 0;
        $taxRateSummary[$rate]['count']++;
        }
        ksort($taxRateSummary);
        @endphp

        <div class="gst-compliance" style="margin-top: 20px;">
            <div class="compliance-title">Tax Rate-wise Summary</div>
            <table class="gst-summary-table">
                <thead>
                    <tr>
                        <th>GST Rate</th>
                        <th>No. of Items</th>
                        <th>Taxable Value (₹)</th>
                        <th>Tax Amount (₹)</th>
                        <th>Total Value (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($taxRateSummary as $rate => $summary)
                    <tr>
                        <td class="text-center">
                            @if($rate > 0)
                            <strong>{{ $rate }}%</strong>
                            @else
                            <span class="text-muted">Exempt</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $summary['count'] }}</td>
                        <td class="currency">{{ number_format($summary['taxable_amount'], 2) }}</td>
                        <td class="currency">{{ number_format($summary['tax_amount'], 2) }}</td>
                        <td class="currency"><strong>{{ number_format($summary['total_amount'], 2) }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #343a40; color: white; font-weight: bold;">
                        <td><strong>TOTAL</strong></td>
                        <td class="text-center"><strong>{{ array_sum(array_column($taxRateSummary, 'count')) }}</strong>
                        </td>
                        <td class="currency"><strong>{{ number_format(array_sum(array_column($taxRateSummary,
                                'taxable_amount')), 2) }}</strong></td>
                        <td class="currency"><strong>{{ number_format(array_sum(array_column($taxRateSummary,
                                'tax_amount')), 2) }}</strong></td>
                        <td class="currency"><strong>{{ number_format(array_sum(array_column($taxRateSummary,
                                'total_amount')), 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif

        <!-- Authorized Signature -->
        <div class="authorized-signature">
            <div class="signature-line"></div>
            <div><strong>Authorized Signatory</strong></div>
            <div>{{ $business->name ?? config('app.name') }}</div>
            <div style="font-size: 10px; color: #6c757d; margin-top: 5px;">
                {{ $business->tax_number_1 ? 'GSTIN: ' . $business->tax_number_1 : '' }}
            </div>
        </div>

        <!-- Footer -->
        <div class="report-footer">
            <div class="footer-left">
                <strong>{{ $business->name ?? config('app.name') }}</strong><br>
                GST Purchase Report - India Compliance<br>
                <small>Generated by Advanced Reports Module</small>
            </div>
            <div class="footer-right">
                Report Date: {{ date('F j, Y') }}<br>
                Time: {{ date('g:i A') }}<br>
                <small>Page 1 of 1</small>
            </div>
        </div>
    </div>

    <script>
        // Auto-trigger print dialog when page loads
        window.onload = function() {
            // Small delay to ensure page is fully loaded
            setTimeout(function() {
                window.print();
            }, 500);
        };

        // Handle print events
        window.onbeforeprint = function() {
            document.title = 'GST Purchase Report - ' + '{{ $business->name ?? config("app.name") }}';
        };

        window.onafterprint = function() {
            // Optional: Close window after printing (uncomment if needed)
            // window.close();
        };

        // Handle browser back button after print
        window.addEventListener('beforeunload', function(e) {
            // Optional: Add confirmation before leaving
        });
    </script>
</body>

</html>