<?php
require_once "../core/config/dbquery.php";

$query = new Dbquery();

$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$waiter_id = (int)($_GET['waiter_id'] ?? 0);

$conditions = [];
$params = [];
$types = '';

if($date_from !== ''){
    $conditions[] = "s.sale_date >= ?";
    $params[] = $date_from;
    $types .= 's';
}
if($date_to !== ''){
    $conditions[] = "s.sale_date <= ?";
    $params[] = $date_to;
    $types .= 's';
}
if($waiter_id > 0){
    $conditions[] = "s.waiter_id = ?";
    $params[] = $waiter_id;
    $types .= 'i';
}

$where = $conditions ? ("WHERE " . implode(" AND ", $conditions)) : '';

$sql = "SELECT s.id, s.sale_date, s.total_amount, w.full_name AS waiter_name
        FROM sales s
        JOIN waiters w ON w.id = s.waiter_id
        {$where}
        ORDER BY s.created_at DESC";
$stmt = $query->conn->prepare($sql);
if($params){
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$items_sql = "SELECT p.name, SUM(si.quantity) AS qty, SUM(si.quantity * si.price) AS total
              FROM sale_items si
              JOIN sales s ON s.id = si.sale_id
              JOIN products p ON p.id = si.product_id
              {$where}
              GROUP BY p.id
              ORDER BY qty DESC";
$stmt2 = $query->conn->prepare($items_sql);
if($params){
    $stmt2->bind_param($types, ...$params);
}
$stmt2->execute();
$items = $stmt2->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daily Report</title>
<style>
  body{font-family: Arial, sans-serif; margin:0; padding:0; color:#000;}
  .report{width:80mm; padding:10px;}
  .center{text-align:center;}
  .line{border-top:1px dashed #000; margin:8px 0;}
  table{width:100%; border-collapse:collapse; font-size:12px;}
  th,td{text-align:left; padding:2px 0;}
  @media print{.print-btn{display:none;}}
</style>
</head>
<body>
<div class="report">
  <div class="center">
    <strong>Daily Report</strong><br>
    <?= htmlspecialchars($date_from ?: 'All Dates'); ?> - <?= htmlspecialchars($date_to ?: ''); ?>
  </div>
  <div class="line"></div>
  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Waiter</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['sale_date']); ?></td>
          <td><?= htmlspecialchars($row['waiter_name']); ?></td>
          <td>₦<?= number_format($row['total_amount'], 2); ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <div class="line"></div>
  <div><strong>Item Breakdown</strong></div>
  <table>
    <thead>
      <tr>
        <th>Item</th>
        <th>Qty</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      <?php while($item = $items->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($item['name']); ?></td>
          <td><?= (int)$item['qty']; ?></td>
          <td>₦<?= number_format($item['total'], 2); ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <button class="print-btn" onclick="window.print()">Print</button>
</div>
</body>
</html>
