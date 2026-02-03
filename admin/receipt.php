<?php
require_once "../core/config/dbquery.php";

$query = new Dbquery();
$sale_id = (int)($_GET['sale_id'] ?? 0);

if($sale_id <= 0){
    die('Invalid sale');
}

$sale = $query->select("sales", "*", "id = ?", [$sale_id], "i");
if($sale->num_rows === 0){
    die('Sale not found');
}
$s = $sale->fetch_assoc();

$waiter = $query->select("waiters", "*", "id = ?", [$s['waiter_id']], "i")->fetch_assoc();
$table = $query->select("tables", "*", "id = ?", [$s['table_id']], "i")->fetch_assoc();

$items = $query->conn->query(
    "SELECT p.name, si.quantity, si.price FROM sale_items si JOIN products p ON p.id = si.product_id WHERE si.sale_id = {$sale_id}"
);

$date = date('Y-m-d', strtotime($s['created_at']));
$time = date('H:i', strtotime($s['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt</title>
<style>
  body{font-family: Arial, sans-serif; margin:0; padding:0;}
  .receipt{width:80mm; padding:10px; color:#000;}
  .center{text-align:center;}
  .line{border-top:1px dashed #000; margin:8px 0;}
  table{width:100%; border-collapse:collapse; font-size:12px;}
  th,td{text-align:left; padding:2px 0;}
  .total{font-weight:bold;}
  @media print{.print-btn{display:none;}}
</style>
</head>
<body>
<div class="receipt">
  <div class="center">
    <div><strong>New Iceland Beach Resort</strong></div>
    <div>Date: <?= $date; ?></div>
    <div>Time: <?= $time; ?></div>
    <div>Waiter: <?= htmlspecialchars($waiter['full_name'] ?? ''); ?></div>
    <div>Table: <?= htmlspecialchars($table['table_name'] ?? ''); ?></div>
  </div>

  <div class="line"></div>

  <table>
    <thead>
      <tr>
        <th>Item</th>
        <th>Qty</th>
        <th>Price</th>
      </tr>
    </thead>
    <tbody>
      <?php while($item = $items->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($item['name']); ?></td>
        <td><?= (int)$item['quantity']; ?></td>
        <td><?= number_format($item['price'] * $item['quantity'], 2); ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <div class="line"></div>
  <div class="total">TOTAL: ₦<?= number_format($s['total_amount'], 2); ?></div>
  <div class="center" style="margin-top:8px;">Thank you for visiting!</div>

  <button class="print-btn" onclick="window.print()">Print</button>
</div>
</body>
</html>
