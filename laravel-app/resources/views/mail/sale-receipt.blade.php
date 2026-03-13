<h2>Iceland POS Receipt</h2>
<p><strong>Sale #:</strong> {{ $sale->id }}</p>
<p><strong>Date:</strong> {{ optional($sale->sale_date)->format('Y-m-d') }}</p>
<p><strong>Waiter:</strong> {{ $sale->waiter?->full_name }}</p>
<p><strong>Table:</strong> {{ $sale->diningTable?->table_name }}</p>
<p><strong>Payment:</strong> {{ strtoupper($sale->payment_method) }}</p>
<hr>
@foreach($sale->items as $item)
<p>{{ $item->product?->name }} x {{ $item->quantity }} - NGN {{ number_format((float)$item->price * (int)$item->quantity, 2) }}@if($item->is_voided) (VOIDED) @endif</p>
@endforeach
<hr>
<p><strong>Total:</strong> NGN {{ number_format((float)$sale->total_amount, 2) }}</p>
