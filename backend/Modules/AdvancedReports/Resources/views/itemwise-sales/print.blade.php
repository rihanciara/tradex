<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@lang('Itemwise Sales Report') - {{ $business->name }}</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    <!-- Bootstrap 3.3.7 -->
    <link rel="stylesheet" href="{{ asset('bootstrap/css/bootstrap.min.css') }}">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('css/font-awesome.min.css') }}">

    <style type="text/css">
        .print-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }

        .report-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0;
        }

        .filters-section {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }

        .filter-row {
            margin-bottom: 8px;
        }

        .filter-label {
            font-weight: bold;
            color: #495057;
        }

        .summary-section {
            margin-bottom: 20px;
        }

        .summary-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .summary-card {
            flex: 1;
            min-width: 150px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .summary-card.green {
            background: linear-gradient(135deg, #28a745, #1e7e34);
        }

        .summary-card.yellow {
            background: linear-gradient(135deg, #ffc107, #e0a800);
        }

        .summary-card.red {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }

        .summary-card.purple {
            background: linear-gradient(135deg, #6f42c1, #59359a);
        }

        .summary-card.orange {
            background: linear-gradient(135deg, #fd7e14, #e8590c);
        }

        .summary-value {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .summary-label {
            font-size: 11px;
            opacity: 0.9;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 20px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        .data-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
            color: #495057;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .data-table tfoot {
            background-color: #e9ecef;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-success {
            color: #28a745;
        }

        .text-warning {
            color: #ffc107;
        }

        .text-info {
            color: #17a2b8;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
            border-radius: 3px;
            color: white;
        }

        .badge-info {
            background-color: #17a2b8;
        }

        .badge-primary {
            background-color: #007bff;
        }

        .footer-info {
            margin-top: 30px;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
            font-size: 11px;
            color: #6c757d;
        }

        .print-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50px;
            background: white;
            border-top: 1px solid #ddd;
            padding: 10px;
            font-size: 10px;
            text-align: center;
            color: #666;
        }

        @media print {
            body {
                margin: 0;
                font-size: 10px;
            }

            .no-print {
                display: none !important;
            }

            .data-table {
                font-size: 9px;
            }

            .summary-cards {
                page-break-inside: avoid;
            }

            .data-table thead {
                display: table-header-group;
            }

            .data-table tfoot {
                display: table-footer-group;
            }

            tr {
                page-break-inside: avoid;
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .summary-cards {
                flex-direction: column;
            }

            .data-table {
                font-size: 10px;
            }

            .data-table th,
            .data-table td {
                padding: 4px;
            }
        }
    </style>
</head>

<body>
    <!-- Print Header -->
    <div class="print-header">
        <div class="company-name">{{ $business->name }}</div>
        @if(!empty($business->landmark))
        <div class="company-details">{{ $business->landmark }}</div>
        @endif
        @if(!empty($business->city) || !empty($business->state))
        <div class="company-details">
            {{ $business->city }}@if(!empty($business->city) && !empty($business->state)), @endif{{ $business->state }}
            @if(!empty($business->zip)) - {{ $business->zip }}@endif
        </div>
        @endif
        @if(!empty($business->mobile))
        <div class="company-details">{{ __('business.mobile') }}: {{ $business->mobile }}</div>
        @endif
        @if(!empty($business->email))
        <div class="company-details">{{ __('business.email') }}: {{ $business->email }}</div>
        @endif

        <div class="report-title">@lang('Itemwise Sales Report')</div>
    </div>

    <!-- Filters Section -->
    @if(!empty($filters) || !empty($summary['date_range']))
    <div class="filters-section">
        <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 14px;">
            <i class="fa fa-filter"></i> Applied Filters
        </h4>

        <div class="row">
            <div class="col-md-6">
                <div class="filter-row">
                    <span class="filter-label">Date Range:</span> {{ $summary['date_range'] ?? 'All Dates' }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="filter-row">
                    <span class="filter-label">Location:</span> {{ $filters['location_name'] }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="filter-row">
                    <span class="filter-label">Customer:</span> {{ $filters['customer_name'] }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="filter-row">
                    <span class="filter-label">Category:</span> {{ $filters['category_name'] }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="filter-row">
                    <span class="filter-label">Brand:</span> {{ $filters['brand_name'] }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="filter-row">
                    <span class="filter-label">Unit:</span> {{ $filters['unit_name'] }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="filter-row">
                    <span class="filter-label">Tax Rate:</span> {{ $filters['tax_rate_name'] }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="filter-row">
                    <span class="filter-label">Created By:</span> {{ $filters['user_name'] }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="filter-row">
                    <span class="filter-label">Payment Method:</span> {{ $filters['payment_method'] }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="filter-row">
                    <span class="filter-label">Amount Range:</span> {{ $filters['amount_range'] }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="filter-row">
                    <span class="filter-label">Product Filter:</span> {{ $filters['product_filter'] }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="filter-row">
                    <span class="filter-label">Customer Filter:</span> {{ $filters['customer_filter'] }}
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Summary Section -->
    <div class="summary-section">
        <h4 style="margin-bottom: 15px; font-size: 14px;">
            <i class="fa fa-bar-chart"></i> Report Summary
        </h4>

        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-value">{{ $summary['total_transactions'] }}</div>
                <div class="summary-label">Total Transactions</div>
            </div>

            <div class="summary-card green">
                <div class="summary-value">{{ $summary['total_customers'] }}</div>
                <div class="summary-label">Unique Customers</div>
            </div>

            <div class="summary-card yellow">
                <div class="summary-value">{{ $summary['total_products'] }}</div>
                <div class="summary-label">Products Sold</div>
            </div>

            <div class="summary-card red">
                <div class="summary-value">{{ number_format($summary['total_qty_sold'], 2) }}</div>
                <div class="summary-label">Total Qty Sold</div>
            </div>

            <div class="summary-card purple">
                <div class="summary-value">{{ number_format($summary['total_amount'], 2) }}</div>
                <div class="summary-label">Total Sales Amount</div>
            </div>

            <div class="summary-card orange">
                <div class="summary-value">{{ number_format($summary['total_tax'], 2) }}</div>
                <div class="summary-label">Total Tax Amount</div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 8%;">Invoice No</th>
                <th style="width: 8%;">Date</th>
                <th style="width: 12%;">Customer</th>
                <th style="width: 15%;">Product</th>
                <th style="width: 10%;">Category</th>
                <th style="width: 8%;">Brand</th>
                <th style="width: 6%;">Qty</th>
                <th style="width: 8%;">Unit Price</th>
                <th style="width: 6%;">Tax %</th>
                <th style="width: 8%;">Tax Amount</th>
                <th style="width: 8%;">Subtotal</th>
                <th style="width: 8%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
            $total_qty = 0;
            $total_tax_amount = 0;
            $total_subtotal = 0;
            $total_amount = 0;
            @endphp

            @foreach($salesData as $sale)
            @php
            $total_qty += $sale->sold_qty;
            $total_tax_amount += $sale->total_tax;
            $total_subtotal += $sale->subtotal;
            $total_amount += $sale->line_total;
            @endphp
            <tr>
                <td class="text-center">{{ $sale->invoice_no }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($sale->transaction_date)->format('d-m-Y') }}</td>
                <td>
                    @if(!empty($sale->supplier_business_name))
                    <strong>{{ $sale->supplier_business_name }}</strong><br>
                    @endif
                    {{ $sale->customer_name ?: __('advancedreports::lang.walk_in_customer') }}
                    @if(!empty($sale->customer_mobile))
                    <br><small>{{ $sale->customer_mobile }}</small>
                    @endif
                </td>
                <td>
                    <strong>{{ $sale->product_name }}</strong>
                    @if(!empty($sale->variation_name))
                    <br><small>{{ $sale->variation_name }}</small>
                    @endif
                    @if(!empty($sale->sku))
                    <br><small class="text-info">SKU: {{ $sale->sku }}</small>
                    @endif
                </td>
                <td>{{ $sale->category_name ?: '-' }}</td>
                <td>{{ $sale->brand_name ?: '-' }}</td>
                <td class="text-center">
                    <span class="badge badge-info">{{ number_format($sale->sold_qty, 2) }} {{ $sale->unit_name }}</span>
                </td>
                <td class="text-right">{{ number_format($sale->unit_price, 2) }}</td>
                <td class="text-center">
                    @if(!empty($sale->tax_rate))
                    <span class="badge badge-primary">{{ number_format($sale->tax_rate, 1) }}%</span>
                    @else
                    -
                    @endif
                </td>
                <td class="text-right">{{ number_format($sale->total_tax, 2) }}</td>
                <td class="text-right">{{ number_format($sale->subtotal, 2) }}</td>
                <td class="text-right text-success"><strong>{{ number_format($sale->line_total, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #e9ecef;">
                <td colspan="6" class="text-center"><strong>TOTALS:</strong></td>
                <td class="text-center"><strong>{{ number_format($total_qty, 2) }}</strong></td>
                <td></td>
                <td></td>
                <td class="text-right"><strong>{{ number_format($total_tax_amount, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($total_subtotal, 2) }}</strong></td>
                <td class="text-right text-success"><strong>{{ number_format($total_amount, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <!-- Footer Information -->
    <div class="footer-info">
        <div class="row">
            <div class="col-md-6">
                <strong>Report Details:</strong><br>
                Generated on: {{ \Carbon\Carbon::now()->format('d M Y, H:i:s') }}<br>
                Total Records: {{ count($salesData) }}<br>
                Report Period: {{ $summary['date_range'] }}
            </div>
            <div class="col-md-6 text-right">
                <strong>Business Information:</strong><br>
                {{ $business->name }}<br>
                @if(!empty($business->tax_number_1))
                Tax Number: {{ $business->tax_number_1 }}<br>
                @endif
                Printed by: {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
            </div>
        </div>
    </div>

    <!-- Print Footer -->
    <div class="print-footer no-print">
        <div class="text-center">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fa fa-print"></i> Print Report
            </button>
            <button onclick="window.close()" class="btn btn-default" style="margin-left: 10px;">
                <i class="fa fa-times"></i> Close
            </button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('bootstrap/js/bootstrap.min.js') }}"></script>

    <script type="text/javascript">
        // Auto-print when page loads (optional)
        // window.onload = function() { window.print(); }
        
        // Print function
        function printReport() {
            window.print();
        }
        
        // Responsive table handling
        $(document).ready(function() {
            // Add responsive classes if needed
            $('.data-table').addClass('table-responsive');
            
            // Handle print media queries
            if (window.matchMedia) {
                var mediaQueryList = window.matchMedia('print');
                mediaQueryList.addListener(function(mql) {
                    if (mql.matches) {
                        // Before print
                        $('.no-print').hide();
                    } else {
                        // After print
                        $('.no-print').show();
                    }
                });
            }
        });
    </script>
</body>

</html>