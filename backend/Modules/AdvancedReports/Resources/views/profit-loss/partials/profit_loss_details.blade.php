<div class="profit-loss-statement">
    <!-- Statement Header -->
    <div class="row">
        <div class="col-md-12">
            <div class="statement-header-card">
                <h3 class="text-center">
                    <strong>{{ session()->get('business.name') }}</strong><br>
                    <small>Profit & Loss Statement</small><br>
                    <small class="text-muted">{{ $data['start_date'] ?? '' }} to {{ $data['end_date'] ?? '' }}</small>
                </h3>
            </div>
        </div>
    </div>

    <!-- Cards Layout -->
    <div class="row">
        <!-- Revenue Card -->
        <div class="col-md-6">
            <div class="card revenue-card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fa fa-money text-green"></i> REVENUE
                    </h4>
                </div>
                <div class="card-body">
                    <div class="line-item">
                        <span class="item-label">Gross Sales</span>
                        <span class="item-value positive pull-right"><span class="display_currency" data-currency_symbol="true">{{ $data['total_sell_inc_tax'] ?? 0 }}</span></span>
                        <div class="clearfix"></div>
                    </div>
                    
                    @if(isset($data['total_sell_return']) && $data['total_sell_return'] > 0)
                    <div class="line-item sub-item">
                        <span class="item-label">Less: Sales Returns</span>
                        <span class="item-value negative pull-right">({{ $data['total_sell_return_formatted'] ?? 0 }})</span>
                        <div class="clearfix"></div>
                    </div>
                    @endif

                    <div class="line-item total-item">
                        <strong class="item-label">Net Sales Revenue</strong>
                        <strong class="item-value positive pull-right"><span class="display_currency" data-currency_symbol="true">{{ $data['net_sales'] ?? $data['total_sell_inc_tax'] ?? 0 }}</span></strong>
                        <div class="clearfix"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cost of Goods Sold Card -->
        <div class="col-md-6">
            <div class="card cogs-card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fa fa-cube text-orange"></i> COST OF GOODS SOLD
                    </h4>
                </div>
                <div class="card-body">
                    <div class="line-item">
                        <span class="item-label">Opening Stock</span>
                        <span class="item-value pull-right"><span class="display_currency" data-currency_symbol="true">{{ $data['opening_stock'] ?? 0 }}</span></span>
                        <div class="clearfix"></div>
                    </div>

                    <div class="line-item">
                        <span class="item-label">Purchases</span>
                        <span class="item-value pull-right"><span class="display_currency" data-currency_symbol="true">{{ $data['total_purchase'] ?? 0 }}</span></span>
                        <div class="clearfix"></div>
                    </div>

                    @if(isset($data['total_purchase_return']) && $data['total_purchase_return'] > 0)
                    <div class="line-item sub-item">
                        <span class="item-label">Less: Purchase Returns</span>
                        <span class="item-value negative pull-right">({{ $data['total_purchase_return_formatted'] ?? 0 }})</span>
                        <div class="clearfix"></div>
                    </div>
                    @endif

                    <div class="line-item">
                        <span class="item-label">Less: Closing Stock</span>
                        <span class="item-value negative pull-right">(<span class="display_currency" data-currency_symbol="true">{{ $data['closing_stock'] ?? 0 }}</span>)</span>
                        <div class="clearfix"></div>
                    </div>

                    <div class="line-item total-item">
                        <strong class="item-label">Total Cost of Goods Sold</strong>
                        <strong class="item-value negative pull-right"><span class="display_currency" data-currency_symbol="true">{{ $data['total_purchase'] ?? 0 }}</span></strong>
                        <div class="clearfix"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Gross Profit Card -->
        <div class="col-md-6">
            <div class="card gross-profit-card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fa fa-chart-line text-blue"></i> GROSS PROFIT
                    </h4>
                </div>
                <div class="card-body text-center">
                    <h3 class="gross-profit-amount {{ ($data['gross_profit'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
                        <span class="display_currency" data-currency_symbol="true">{{ $data['gross_profit'] ?? 0 }}</span>
                    </h3>
                    @php 
                        $gross_margin = 0;
                        if (isset($data['total_sell_inc_tax']) && $data['total_sell_inc_tax'] > 0) {
                            $gross_margin = round((($data['gross_profit'] ?? 0) / $data['total_sell_inc_tax']) * 100, 2);
                        }
                    @endphp
                    <p class="margin-text">Gross Profit Margin: <strong>{{ $gross_margin }}%</strong></p>
                </div>
            </div>
        </div>

        <!-- Operating Expenses Card -->
        <div class="col-md-6">
            <div class="card expenses-card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fa fa-money-bill text-red"></i> OPERATING EXPENSES
                    </h4>
                </div>
                <div class="card-body">
                    @if(isset($data['total_expense']) && $data['total_expense'] > 0)
                    <div class="line-item">
                        <span class="item-label">Total Expenses</span>
                        <span class="item-value negative pull-right"><span class="display_currency" data-currency_symbol="true">{{ $data['total_expense'] ?? 0 }}</span></span>
                        <div class="clearfix"></div>
                    </div>
                    @endif

                    @if(isset($data['total_expense_tax']) && $data['total_expense_tax'] > 0)
                    <div class="line-item sub-item">
                        <span class="item-label">Expense Tax</span>
                        <span class="item-value negative pull-right"><span class="display_currency" data-currency_symbol="true">{{ $data['total_expense_tax'] ?? 0 }}</span></span>
                        <div class="clearfix"></div>
                    </div>
                    @endif

                    <div class="line-item total-item">
                        <strong class="item-label">Total Operating Expenses</strong>
                        <strong class="item-value negative pull-right"><span class="display_currency" data-currency_symbol="true">{{ $data['total_operating_expenses'] ?? $data['total_expense'] ?? 0 }}</span></strong>
                        <div class="clearfix"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Net Profit Card -->
        <div class="col-md-6">
            <div class="card net-profit-card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fa fa-trophy text-yellow"></i> NET PROFIT
                    </h4>
                </div>
                <div class="card-body text-center">
                    <h2 class="net-profit-amount {{ ($data['net_profit'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
                        <span class="display_currency" data-currency_symbol="true">{{ $data['net_profit'] ?? 0 }}</span>
                    </h2>
                    @php 
                        $net_margin = 0;
                        if (isset($data['total_sell_inc_tax']) && $data['total_sell_inc_tax'] > 0) {
                            $net_margin = round((($data['net_profit'] ?? 0) / $data['total_sell_inc_tax']) * 100, 2);
                        }
                    @endphp
                    <p class="margin-text">Net Profit Margin: <strong>{{ $net_margin }}%</strong></p>
                </div>
            </div>
        </div>

        <!-- Key Ratios Card -->
        <div class="col-md-6">
            <div class="card ratios-card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fa fa-calculator text-purple"></i> KEY FINANCIAL RATIOS
                    </h4>
                </div>
                <div class="card-body">
                    <div class="ratio-item">
                        <span class="ratio-label">Gross Profit Margin:</span>
                        <span class="ratio-value pull-right">{{ $gross_margin }}%</span>
                        <div class="clearfix"></div>
                    </div>
                    <div class="ratio-item">
                        <span class="ratio-label">Net Profit Margin:</span>
                        <span class="ratio-value pull-right">{{ $net_margin }}%</span>
                        <div class="clearfix"></div>
                    </div>

                    @if(isset($data['total_sell_inc_tax']) && $data['total_sell_inc_tax'] > 0 && isset($data['total_cogs']) && $data['total_cogs'] > 0)
                    @php 
                        $cost_ratio = round(($data['total_cogs'] / $data['total_sell_inc_tax']) * 100, 2);
                    @endphp
                    <div class="ratio-item">
                        <span class="ratio-label">Cost of Sales Ratio:</span>
                        <span class="ratio-value pull-right">{{ $cost_ratio }}%</span>
                        <div class="clearfix"></div>
                    </div>
                    @endif

                    @if(isset($data['total_expense']) && $data['total_expense'] > 0 && isset($data['total_sell_inc_tax']) && $data['total_sell_inc_tax'] > 0)
                    @php 
                        $expense_ratio = round(($data['total_expense'] / $data['total_sell_inc_tax']) * 100, 2);
                    @endphp
                    <div class="ratio-item">
                        <span class="ratio-label">Operating Expense Ratio:</span>
                        <span class="ratio-value pull-right">{{ $expense_ratio }}%</span>
                        <div class="clearfix"></div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profit-loss-statement {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.statement-header-card {
    background: #fff;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    border-top: 4px solid #3498db;
    margin-bottom: 20px;
}

.statement-header-card h3 {
    margin: 0;
    color: #2c3e50;
}

/* Card Styles */
.card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    border: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 25px rgba(0,0,0,0.15);
}

.card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
    padding: 15px 20px;
    border-radius: 8px 8px 0 0;
}

.card-title {
    margin: 0;
    color: #34495e;
    font-weight: 600;
    font-size: 1.1em;
}

.card-body {
    padding: 20px;
}

/* Specific Card Colors */
.revenue-card .card-header {
    background: linear-gradient(135deg, #d5f4e6 0%, #c3f7d8 100%);
    border-bottom: 1px solid #27ae60;
}

.cogs-card .card-header {
    background: linear-gradient(135deg, #fdeaa7 0%, #fdf2c0 100%);
    border-bottom: 1px solid #e67e22;
}

.gross-profit-card {
    border-top: 4px solid #3498db;
}

.gross-profit-card .card-header {
    background: linear-gradient(135deg, #d6eaf8 0%, #ebf3fd 100%);
    border-bottom: 1px solid #3498db;
}

.expenses-card .card-header {
    background: linear-gradient(135deg, #fadbd8 0%, #fdedec 100%);
    border-bottom: 1px solid #e74c3c;
}

.net-profit-card {
    border-top: 4px solid #f1c40f;
}

.net-profit-card .card-header {
    background: linear-gradient(135deg, #fcf3cf 0%, #fef9e7 100%);
    border-bottom: 1px solid #f1c40f;
}

.ratios-card .card-header {
    background: linear-gradient(135deg, #e8daef 0%, #f4ecf7 100%);
    border-bottom: 1px solid #9b59b6;
}

/* Line Items */
.line-item {
    padding: 12px 0;
    border-bottom: 1px dotted #dee2e6;
}

.line-item:last-child {
    border-bottom: none;
}

.sub-item .item-label {
    padding-left: 20px;
    font-size: 0.9em;
    color: #7f8c8d;
}

.total-item {
    border-top: 2px solid #34495e;
    border-bottom: 2px solid #34495e;
    background-color: #f8f9fa;
    padding: 15px 0;
    margin-top: 10px;
    border-radius: 4px;
    padding-left: 10px;
    padding-right: 10px;
}

.item-label {
    font-size: 1em;
    color: #2c3e50;
    display: inline-block;
    width: 70%;
}

.item-value {
    font-weight: 600;
    font-size: 1.1em;
    display: inline-block;
    width: 30%;
    text-align: right;
}

.item-value.positive {
    color: #27ae60;
}

.item-value.negative {
    color: #e74c3c;
}

/* Profit Amounts */
.gross-profit-amount, .net-profit-amount {
    font-weight: bold;
    margin: 20px 0 10px 0;
}

.gross-profit-amount.positive, .net-profit-amount.positive {
    color: #27ae60;
}

.gross-profit-amount.negative, .net-profit-amount.negative {
    color: #e74c3c;
}

.margin-text {
    color: #6c757d;
    margin: 5px 0;
}

/* Ratio Items */
.ratio-item {
    padding: 10px 0;
    border-bottom: 1px dotted #dee2e6;
}

.ratio-item:last-child {
    border-bottom: none;
}

.ratio-label {
    font-weight: 500;
    color: #34495e;
    display: inline-block;
    width: 70%;
}

.ratio-value {
    font-weight: 600;
    color: #2980b9;
    display: inline-block;
    width: 30%;
    text-align: right;
}

/* Responsive Design */
@media (max-width: 768px) {
    .col-md-6 {
        margin-bottom: 15px;
    }
    
    .statement-header-card {
        padding: 15px;
    }
    
    .card-body {
        padding: 15px;
    }
    
    .item-label, .ratio-label {
        width: 60%;
        font-size: 0.9em;
    }
    
    .item-value, .ratio-value {
        width: 40%;
        font-size: 1em;
    }
}

/* Print styles */
@media print {
    .profit-loss-statement {
        background: #fff;
        padding: 10px;
    }
    
    .card {
        box-shadow: none;
        border: 1px solid #000;
        break-inside: avoid;
        margin-bottom: 15px;
    }
    
    .card-header {
        background: #fff !important;
        border-bottom: 2px solid #000;
    }
    
    .item-value.positive,
    .item-value.negative,
    .gross-profit-amount,
    .net-profit-amount {
        color: #000 !important;
    }
    
    .card:hover {
        transform: none;
        box-shadow: none;
    }
}
</style>

<script>
// Format any currency elements in this partial after it's loaded
$(document).ready(function() {
    if (typeof __currency_convert_recursively === 'function') {
        __currency_convert_recursively($('.profit-loss-statement'));
    }
});
</script>