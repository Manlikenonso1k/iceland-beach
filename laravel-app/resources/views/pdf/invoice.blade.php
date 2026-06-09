<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 14px 16px;
        }
        
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            line-height: 1.35;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        .container {
            padding: 0;
        }

        .header {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            padding: 16px 24px;
            margin: 0 0 18px 0;
            display: table;
            width: 100%;
            box-sizing: border-box;
            page-break-inside: avoid;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .logo-section {
            display: table-cell;
            vertical-align: middle;
            width: 180px;
        }

        .logo-section img {
            width: 120px;
            height: auto;
            margin-left: 24px;
        }

        .company-info {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            padding-left: 20px;
        }

        .company-info h1 {
            margin: 0 0 8px 0;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 1.5px;
        }

        .company-info p {
            margin: 2px 0;
            font-size: 10px;
            opacity: 0.95;
        }

        .invoice-meta {
            display: table;
            width: 100%;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }

        .invoice-details,
        .customer-details {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }

        .invoice-details {
            padding-right: 10px;
        }

        .customer-details {
            text-align: right;
            padding-left: 10px;
        }

        .detail-box {
            background: #f8fafc;
            border-left: 4px solid #0ea5e9;
            padding: 12px 16px;
            border-radius: 4px;
        }

        .customer-box {
            background: #f8fafc;
            border-right: 4px solid #06b6d4;
            padding: 12px 16px;
            border-radius: 4px;
        }

        .detail-box h3,
        .customer-box h3 {
            margin: 0 0 10px 0;
            font-size: 11px;
            color: #0ea5e9;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .detail-box p,
        .customer-box p {
            margin: 4px 0;
            line-height: 1.4;
        }

        .detail-box strong,
        .customer-box strong {
            color: #334155;
            font-weight: 600;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 10px 0;
            border: 1px solid #dbeafe;
            page-break-inside: avoid;
        }

        table.items thead tr {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
        }

        table.items th {
            padding: 9px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table.items th.num {
            text-align: right;
        }

        table.items tbody tr {
            border-bottom: 1px solid #e2e8f0;
            page-break-inside: avoid;
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
            padding: 8px 10px;
            color: #334155;
        }

        table.items td.num {
            text-align: right;
            font-weight: 500;
            white-space: nowrap;
        }

        .totals-section {
            margin: 10px 0 8px 0;
            display: table;
            width: 100%;
            page-break-inside: avoid;
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
            padding-left: 12px;
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
            padding: 7px 0;
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
            font-size: 13px;
            padding: 10px 0 !important;
            border-radius: 6px;
        }

        .grand-total .label,
        .grand-total .value {
            color: white !important;
            font-weight: 700;
        }

        .amount-in-words {
            background: #f8fafc;
            padding: 10px 14px;
            border-left: 4px solid #06b6d4;
            border-radius: 6px;
            margin: 10px 0 10px 0;
            page-break-inside: avoid;
        }

        .amount-in-words strong {
            color: #0ea5e9;
        }

        .bank-details {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #0ea5e9;
            border-radius: 8px;
            padding: 12px 14px;
            margin: 8px 0 0 0;
            page-break-inside: avoid;
        }

        .bank-details h3 {
            margin: 0 0 8px 0;
            color: #0ea5e9;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .bank-details p {
            margin: 4px 0;
            line-height: 1.35;
        }

        .bank-details strong {
            color: #0369a1;
            font-weight: 600;
            display: inline-block;
            min-width: 90px;
        }

        .footer {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #0ea5e9;
            text-align: center;
            page-break-inside: avoid;
        }

        .thank-you {
            font-size: 16px;
            font-weight: 700;
            color: #0ea5e9;
            margin-bottom: 8px;
            letter-spacing: 2px;
        }

        .total-words {
            background: #f8fafc;
            padding: 10px 12px;
            border-radius: 6px;
            margin-top: 6px;
            border-left: 4px solid #06b6d4;
        }

        .total-words strong {
            color: #0ea5e9;
        }

        .wave-divider {
            height: 3px;
            background: linear-gradient(90deg, #0ea5e9 0%, #06b6d4 50%, #0ea5e9 100%);
            margin: 12px 0;
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

        <div class="amount-in-words">
            <strong>Amount in Words:</strong> {{ $invoice->total_in_words }}
        </div>

        <div class="bank-details">
            <h3>Payment Information</h3>
            <p><strong>Bank:</strong> {{ $invoice->bank_name }}</p>
            <p><strong>Account Number:</strong> {{ $invoice->bank_account_number }}</p>
            <p><strong>Account Name:</strong> {{ $invoice->bank_account_name }}</p>
        </div>

        <div class="footer">
            <div class="thank-you">THANK YOU FOR YOUR BUSINESS!</div>
        </div>
    </div>
</body>
</html>