<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Exchange Receipt - {{ $exchange->exchange_ref_no }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 14px;
            line-height: 1.4;
            color: #333;
        }

        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 20px;
        }

        .header h2 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 24px;
        }

        .header img {
            max-height: 60px;
            margin-bottom: 10px;
        }

        .header p {
            margin: 5px 0;
            color: #666;
        }

        .receipt-title {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
        }

        .details-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 30px;
        }

        .details-left,
        .details-right {
            flex: 1;
        }

        .detail-item {
            margin-bottom: 8px;
        }

        .detail-label {
            font-weight: bold;
            color: #2c3e50;
            display: inline-block;
            min-width: 120px;
        }

        .items-section {
            margin: 30px 0;
        }

        .section-title {
            background: #f8f9fa;
            padding: 12px 15px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
            font-weight: bold;
            color: #2c3e50;
            font-size: 16px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .items-table th {
            background: #34495e;
            color: white;
            padding: 12px 8px;
            text-align: left;
            border: 1px solid #2c3e50;
            font-weight: bold;
        }

        .items-table td {
            padding: 10px 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .items-table tr:nth-child(even) {
            background: #f8f9fa;
        }

        .items-table tr:hover {
            background: #e3f2fd;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }

        .summary-table th,
        .summary-table td {
            padding: 12px 15px;
            border: 1px solid #ddd;
        }

        .summary-table th {
            background: #f8f9fa;
            font-weight: bold;
            text-align: right;
            width: 70%;
        }

        .summary-table td {
            text-align: right;
            font-weight: bold;
        }

        .net-amount {
            background: #e8f5e8 !important;
            color: #2e7d32;
            font-size: 16px;
        }

        .net-amount th {
            background: #4caf50 !important;
            color: white;
        }

        .payment-status {
            font-size: 12px;
            color: #666;
            font-style: italic;
            margin-left: 10px;
        }

        .notes-section {
            margin: 30px 0;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
        }

        .notes-section strong {
            color: #856404;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            color: #666;
        }

        .footer p {
            margin: 10px 0;
        }

        .print-buttons {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .print-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 25px;
            margin: 0 10px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .print-btn:hover {
            background: #2980b9;
        }

        .close-btn {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 12px 25px;
            margin: 0 10px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .close-btn:hover {
            background: #7f8c8d;
        }

        @media print {
            body {
                margin: 0;
                padding: 15px;
                font-size: 12px;
            }

            .print-buttons {
                display: none !important;
            }

            .receipt-container {
                max-width: none;
                margin: 0;
            }

            .items-table {
                font-size: 11px;
            }

            .items-table th,
            .items-table td {
                padding: 6px 4px;
            }

            .summary-table {
                font-size: 12px;
            }

            .header h2 {
                font-size: 20px;
            }

            .receipt-title {
                font-size: 16px;
                padding: 10px;
            }
        }

        @media (max-width: 768px) {
            .details-section {
                flex-direction: column;
                gap: 15px;
            }

            .detail-label {
                min-width: 100px;
            }

            .items-table {
                font-size: 12px;
            }

            .items-table th,
            .items-table td {
                padding: 8px 4px;
            }
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <!-- Business Header -->
        <div class="header">
            <h2>{{ $business_details->name }}</h2>
            @if(!empty($business_details->logo))
            <img src="{{ asset('uploads/business_logos/' . $business_details->logo) }}" alt="Logo">
            @endif
            <p>{!! $business_details->business_address !!}</p>
            @if(!empty($business_details->contact_number))
            <p><strong>Phone:</strong> {{ $business_details->contact_number }}</p>
            @endif
        </div>

        <!-- Receipt Title -->
        <div class="receipt-title">
            📄 Exchange Receipt
        </div>

        <!-- Exchange Details -->
        <div class="details-section">
            <div class="details-left">
                <div class="detail-item">
                    <span class="detail-label">Exchange Ref No:</span>
                    <strong>{{ $exchange->exchange_ref_no }}</strong>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Exchange Date:</span>
                    {{ \Carbon\Carbon::parse($exchange->exchange_date)->format('d/m/Y H:i') }}
                </div>
                <div class="detail-item">
                    <span class="detail-label">Original Invoice:</span>
                    {{ $exchange->originalTransaction->invoice_no ?? 'N/A' }}
                </div>
            </div>
            <div class="details-right">
                <div class="detail-item">
                    <span class="detail-label">Customer:</span>
                    {{ $exchange->originalTransaction->contact->name ?? 'Walk-In Customer' }}
                </div>
                <div class="detail-item">
                    <span class="detail-label">Business Location:</span>
                    {{ $exchange->location->name ?? 'N/A' }}
                </div>
                <div class="detail-item">
                    <span class="detail-label">Created By:</span>
                    {{ $exchange->creator->first_name ?? 'N/A' }} {{ $exchange->creator->last_name ?? '' }}
                </div>
            </div>
        </div>

        <!-- Exchange Items -->
        <div class="items-section">
            <div class="section-title">Exchange Items Details</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Return Item</th>
                        <th class="text-center">Return Qty</th>
                        <th class="text-right">Return Amount</th>
                        <th>New Item</th>
                        <th class="text-center">New Qty</th>
                        <th class="text-right">New Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $total_return_amount = 0;
                    $total_new_amount = 0;
                    @endphp

                    @foreach($exchange->exchangeLines as $line)
                    @php
                    $return_amount = $line->original_quantity * $line->original_unit_price;
                    $new_amount = $line->new_quantity * $line->new_unit_price;
                    $total_return_amount += $return_amount;
                    $total_new_amount += $new_amount;
                    @endphp
                    <tr>
                        <td>
                            @if($line->originalSellLine && $line->originalSellLine->product)
                            {{ $line->originalSellLine->product->name }}
                            @if($line->originalSellLine->variations && $line->originalSellLine->variations->name !=
                            'DUMMY')
                            <br><small style="color: #666;">{{ $line->originalSellLine->variations->name }}</small>
                            @endif
                            @else
                            <em>Deleted Product</em>
                            @endif
                        </td>
                        <td class="text-center">{{ number_format($line->original_quantity, 2) }}</td>
                        <td class="text-right">{{ number_format($return_amount, 2) }}</td>
                        <td>
                            @if($line->newSellLine && $line->newSellLine->product)
                            {{ $line->newSellLine->product->name }}
                            @if($line->newSellLine->variations && $line->newSellLine->variations->name != 'DUMMY')
                            <br><small style="color: #666;">{{ $line->newSellLine->variations->name }}</small>
                            @endif
                            @elseif($line->exchange_type == 'return_only')
                            <em style="color: #e74c3c;">Return Only</em>
                            @else
                            <em>Deleted Product</em>
                            @endif
                        </td>
                        <td class="text-center">{{ $line->new_quantity > 0 ? number_format($line->new_quantity, 2) : '-'
                            }}</td>
                        <td class="text-right">{{ $new_amount > 0 ? number_format($new_amount, 2) : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #34495e; color: white; font-weight: bold;">
                        <td colspan="2" class="text-right"><strong>Total:</strong></td>
                        <td class="text-right"><strong>{{ number_format($total_return_amount, 2) }}</strong></td>
                        <td colspan="2" class="text-right"><strong>Total:</strong></td>
                        <td class="text-right"><strong>{{ number_format($total_new_amount, 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Exchange Summary -->
        <div class="items-section">
            <div class="section-title">Exchange Summary</div>
            <table class="summary-table">
                <tr>
                    <th>Total Return Value:</th>
                    <td>{{ number_format($exchange->original_amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Total New Value:</th>
                    <td>{{ number_format($exchange->new_amount, 2) }}</td>
                </tr>
                <tr class="net-amount">
                    <th>Net Exchange Amount:</th>
                    <td>
                        <strong>{{ number_format($exchange->exchange_difference, 2) }}</strong>
                        @if($exchange->exchange_difference > 0)
                        <span class="payment-status">(Customer Paid)</span>
                        @elseif($exchange->exchange_difference < 0) <span class="payment-status">(Refunded to
                            Customer)</span>
                            @else
                            <span class="payment-status">(Even Exchange)</span>
                            @endif
                    </td>
                </tr>
            </table>
        </div>

        @if($exchange->notes)
        <div class="notes-section">
            <strong>Notes:</strong>
            <p style="margin: 5px 0 0 0;">{{ $exchange->notes }}</p>
        </div>
        @endif

        <!-- Print Buttons -->
        <div class="print-buttons">
            <button class="print-btn" onclick="window.print()">
                🖨️ Print Receipt
            </button>
            <button class="close-btn" onclick="window.close()">
                ✖️ Close
            </button>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Thank you for your business</strong></p>
            <p><small>This is an exchange receipt</small></p>
        </div>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        // Optional: Close window after printing
        window.onafterprint = function() {
            // Uncomment the line below if you want to auto-close after printing
            // window.close();
        };
    </script>
</body>

</html>