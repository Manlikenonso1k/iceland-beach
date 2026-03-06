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
  "SELECT p.name, si.quantity, si.price, si.is_voided FROM sale_items si JOIN products p ON p.id = si.product_id WHERE si.sale_id = {$sale_id}"
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
  html, body{margin:0; padding:0; color:#000; width:80mm;}
  body{font-family: Arial, sans-serif;}
  .receipt{width:72mm; padding:6px;}
  .center{text-align:center;}
  .line{border-top:1px dashed #000; margin:5px 0;}
  table{width:100%; border-collapse:collapse; font-size:11px;}
  th,td{text-align:left; padding:1px 0; vertical-align:top;}
  th:nth-child(2), th:nth-child(3), td:nth-child(2), td:nth-child(3){white-space:nowrap;}
  .item-name{max-width:40mm; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:inline-block;}
  .total{font-weight:bold;}
  @media print{
    @page{size:80mm auto; margin:0;}
    .print-btn{display:none;}
    html, body{width:80mm; height:auto !important; overflow:hidden;}
    .receipt{width:76mm; padding:2mm; display:block;}
  }
</style>
</head>
<body>
<div class="receipt">
  <div class="center">
    <div><strong>New Iceland Beach Resort</strong></div>
    <div>Date: <?= $date; ?> | Time: <?= $time; ?></div>
    <div>Waiter: <?= htmlspecialchars($waiter['full_name'] ?? ''); ?> | Table: <?= htmlspecialchars($table['table_name'] ?? ''); ?></div>
    <div>Payment: <?= htmlspecialchars($s['payment_method']); ?></div>
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
        <td><span class="item-name"><?= $item['is_voided'] ? 'VOIDED: ' : '' ?><?= htmlspecialchars($item['name']); ?></span></td>
        <td><?= (int)$item['quantity']; ?></td>
        <td><?= number_format($item['price'] * $item['quantity'], 2); ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <div class="line"></div>
  <div class="total">TOTAL: ₦<?= number_format($s['total_amount'], 2); ?></div>
  <div class="center" style="margin-top:5px;">Thank you for visiting!</div>

  <button class="print-btn" onclick="printAndReturn()">Print</button>
</div>

<script>
function printAndReturn(){
  let redirected = false;

  function goBackToPosLogin(){
    if(redirected) return;
    redirected = true;
    window.location.href = 'pos.php?force_login=1';
  }

  window.addEventListener('afterprint', goBackToPosLogin, { once: true });
  window.print();
  setTimeout(goBackToPosLogin, 1200);
}
</script>
</body>
</html>
