<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 24px;
        }

        .header {
            text-align: center;
            margin-bottom: 18px;
        }

        .header h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 1px;
        }

        .header p {
            margin: 2px 0;
            font-size: 12px;
        }

        .meta {
            width: 100%;
            margin-bottom: 14px;
        }

        .meta td {
            padding: 4px 0;
            vertical-align: top;
        }

        .meta .right {
            text-align: right;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.items th,
        table.items td {
            border: 1px solid #d1d5db;
            padding: 8px;
        }

        table.items th {
            background: #f3f4f6;
            text-align: left;
        }

        table.items td.num,
        table.items th.num {
            text-align: right;
            white-space: nowrap;
        }

        .totals {
            margin-top: 14px;
            width: 100%;
        }

        .totals td {
            padding: 4px 0;
        }

        .totals .label {
            text-align: right;
            padding-right: 10px;
            font-weight: bold;
        }

        .totals .value {
            text-align: right;
            font-weight: bold;
            width: 180px;
        }

        .bank {
            margin-top: 18px;
            padding: 10px;
            border: 1px solid #d1d5db;
            background: #fafafa;
        }

        .bank p {
            margin: 3px 0;
        }

        .footer {
            margin-top: 22px;
            text-align: center;
        }

        .thank-you {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 6px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ICELAND BEACH RESORT</h1>
        <p>Okun-Ajah, Ajah, Lagos</p>
        <p>+2348028227526</p>
    </div>

    <table class="meta">
        <tr>
            <td>
                <strong>Invoice #:</strong> {{ $invoice->invoice_number }}<br>
                <strong>Date:</strong> {{ optional($invoice->invoice_date)->format('Y-m-d') }}
            </td>
            <td class="right">
                <strong>Customer:</strong> {{ $invoice->customer_name }}<br>
                <strong>Address:</strong> {{ $invoice->customer_address ?: 'N/A' }}<br>
                <strong>Telephone:</strong> {{ $invoice->telephone ?: 'N/A' }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="num">QTY</th>
                <th class="num">Unit Price (N)</th>
                <th class="num">Sub-total (N)</th>
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

    <table class="totals">
        <tr>
            <td class="label">Grand Total:</td>
            <td class="value">N {{ number_format((float) $invoice->total_amount, 2) }}</td>
        </tr>
    </table>

    <div class="bank">
        <p><strong>Bank:</strong> {{ $invoice->bank_name }}</p>
        <p><strong>Account:</strong> {{ $invoice->bank_account_number }}</p>
        <p><strong>Name:</strong> {{ $invoice->bank_account_name }}</p>
    </div>

    <div class="footer">
        <div class="thank-you">THANK YOU</div>
        <div><strong>Grand Total in Words:</strong> {{ $invoice->total_in_words }}</div>
    </div>
</body>
</html>
