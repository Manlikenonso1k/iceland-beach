<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 0;
        }
        
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        .container {
            padding: 40px;
        }

        /* Header with blue background */
        .header {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            padding: 30px 40px;
            margin: 0 -40px 30px -40px;
            display: table;
            width: 100%;
            box-sizing: border-box;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .logo-section {
            display: table-cell;
            vertical-align: middle;
            width: 200px;
        }

        .logo-section img {
            width: 140px;
            height: auto;
        }

        .company-info {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            padding-left: 20px;
        }

        .company-info h1 {
            margin: 0 0 8px 0;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 1.5px;
        }

        .company-info p {
            margin: 3px 0;
            font-size: 12px;
            opacity: 0.95;
        }

        /* Invoice details section */
        .invoice-meta {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .invoice-details,
        .customer-details {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }

        .invoice-details {
            padding-right: 20px;
        }

        .customer-details {
            text-align: right;
            padding-left: 20px;
        }

        .detail-box {
            background: #f8fafc;
            border-left: 4px solid #0ea5e9;
            padding: 15px 20px;
            border-radius: 4px;
        }

        .customer-box {
            background: #f8fafc;
            border-right: 4px solid #06b6d4;
            padding: 15px 20px;
            border-radius: 4px;
        }

        .detail-box h3,
        .customer-box h3 {
            margin: 0 0 10px 0;
            font-size: 13px;
            color: #0ea5e9;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .detail-box p,
        .customer-box p {
            margin: 6px 0;
            line-height: 1.6;
        }

        .detail-box strong,
        .customer-box strong {
            color: #334155;
            font-weight: 600;
        }

        /* Items table */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            overflow: hidden;
        }

        table.items thead tr {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
        }

        table.items th {
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table.items th.num {
            text-align: right;
        }

        table.items tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }

        table.items tbody tr:last-child {
            border-bottom: none;
        }

        table.items tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        table.items tbody tr:hover {
            background: #f1f5f9;
        }

        table.items td {
            padding: 12px 16px;
            color: #334155;
        }

        table.items td.num {
            text-align: right;
            font-weight: 500;
            white-space: nowrap;
        }

        /* Totals section */
        .totals-section {
            margin: 30px 0;
            display: table;
            width: 100%;
        }

        .totals-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .totals-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-left: 20px;
        }

        .totals-table {
            width: 100%;
            margin-left: auto;
        }

        .totals-table tr {
            border-bottom: 1px solid #e2e8f0;
        }

        .totals-table tr:last-child {
            border-bottom: none;
        }

        .totals-table td {
            padding: 10px 0;
        }

        .totals-table .label {
            text-align: right;
            padding-right: 20px;
            font-weight: 600;
            color: #334155;
        }

        .totals-table .value {
            text-align: right;
            font-weight: 600;
            color: #0f172a;
            width: 150px;
        }

        .grand-total {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white !important;
            font-size: 15px;
            padding: 15px 0 !important;
            border-radius: 6px;
        }

        .grand-total .label,
        .grand-total .value {
            color: white !important;
            font-weight: 700;
        }

        /* Bank details */
        .bank-details {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #0ea5e9;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }

        .bank-details h3 {
            margin: 0 0 12px 0;
            color: #0ea5e9;
            font-size: 13px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .bank-details p {
            margin: 8px 0;
            line-height: 1.6;
        }

        .bank-details strong {
            color: #0369a1;
            font-weight: 600;
            display: inline-block;
            min-width: 80px;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 25px;
            border-top: 3px solid #0ea5e9;
            text-align: center;
        }

        .thank-you {
            font-size: 22px;
            font-weight: 700;
            color: #0ea5e9;
            margin-bottom: 12px;
            letter-spacing: 2px;
        }

        .total-words {
            background: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            border-left: 4px solid #06b6d4;
        }

        .total-words strong {
            color: #0ea5e9;
        }

        /* Decorative elements */
        .wave-divider {
            height: 4px;
            background: linear-gradient(90deg, #0ea5e9 0%, #06b6d4 50%, #0ea5e9 100%);
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                <img src="{{ public_path('images/iceland-logo.png') }}" alt="Iceland Beach Resort">
            </div>
            <div class="company-info">
                <h1>ICELAND BEACH RESORT</h1>
                <p>📍 Okun-Ajah, Ajah, Lagos</p>
                <p>📞 +234 802 822 7526</p>
                <p>✉️ info@icelandbeachresort.com</p>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="invoice-meta">
            <div class="invoice-details">
                <div class="detail-box">
                    <h3>Invoice Details</h3>
                    <p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
                    <p><strong>Invoice Date:</strong> {{ optional($invoice->invoice_date)->format('d M, Y') }}</p>
                    <p><strong>Due Date:</strong> {{ optional($invoice->invoice_date)->addDays(7)->format('d M, Y') }}</p>
                </div>
            </div>
            <div class="customer-details">
                <div class="customer-box">
                    <h3>Bill To</h3>
                    <p><strong>{{ $invoice->customer_name }}</strong></p>
                    <p>{{ $invoice->customer_address ?: 'N/A' }}</p>
                    <p>📞 {{ $invoice->telephone ?: 'N/A' }}</p>
                </div>
            </div>
        </div>

        <div class="wave-divider"></div>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th class="num" style="width: 15%;">Quantity</th>
                    <th class="num" style="width: 17.5%;">Unit Price (₦)</th>
                    <th class="num" style="width: 17.5%;">Sub-total (₦)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="num">{{ number_format((float) $item->quantity) }}</td>
                        <td class="num">{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="num">{{ number_format((float) $item->sub_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-section">
            <div class="totals-left"></div>
            <div class="totals-right">
                <table class="totals-table">
                    <tr class="grand-total">
                        <td class="label">GRAND TOTAL</td>
                        <td class="value">₦ {{ number_format((float) $invoice->total_amount, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="bank-details">
            <h3>💳 Payment Information</h3>
            <p><strong>Bank:</strong> {{ $invoice->bank_name }}</p>
            <p><strong>Account Number:</strong> {{ $invoice->bank_account_number }}</p>
            <p><strong>Account Name:</strong> {{ $invoice->bank_account_name }}</p>
        </div>

        <div class="footer">
            <div class="thank-you">THANK YOU FOR YOUR BUSINESS!</div>
            <div class="total-words">
                <strong>Amount in Words:</strong> {{ $invoice->total_in_words }}
            </div>
        </div>
    </div>
</body>
</html>