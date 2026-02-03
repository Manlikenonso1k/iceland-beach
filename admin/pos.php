<?php
session_start();
require_once "../core/config/dbquery.php";

$query = new Dbquery();
$alert = '';

function clean($value){
    return htmlspecialchars(trim((string)$value));
}

if(isset($_POST['waiter_login'])){
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if($username === '' || $password === ''){
        $alert = "<div class='alert alert-danger'>Username and password required.</div>";
    } else {
        $waiter = $query->select("waiters", "*", "username = ? AND is_active = 1", [$username], "s");
        if($waiter->num_rows > 0){
            $w = $waiter->fetch_assoc();
            if(password_verify($password, $w['password_hash'])){
                $_SESSION['waiter_id'] = $w['id'];
                $_SESSION['waiter_name'] = $w['full_name'];
                $_SESSION['waiter_role'] = $w['role'];
            } else {
                $alert = "<div class='alert alert-danger'>Invalid credentials.</div>";
            }
        } else {
            $alert = "<div class='alert alert-danger'>Waiter not found.</div>";
        }
    }
}

if(isset($_POST['waiter_logout'])){
    unset($_SESSION['waiter_id'], $_SESSION['waiter_name'], $_SESSION['waiter_role']);
}

if(isset($_POST['create_sale']) && isset($_SESSION['waiter_id'])){
    $waiter_id = (int)$_SESSION['waiter_id'];
    $table_id = (int)($_POST['table_id'] ?? 0);
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    if($table_id <= 0 || empty($product_ids)){
        $alert = "<div class='alert alert-danger'>Select a table and at least one product.</div>";
    } else {
        $query->conn->begin_transaction();
        try {
            $table = $query->select("tables", "*", "id = ?", [$table_id], "i");
            if($table->num_rows === 0){
                throw new Exception('Table not found.');
            }
            $t = $table->fetch_assoc();

            if($t['assigned_waiter_id'] !== null && (int)$t['assigned_waiter_id'] !== $waiter_id){
                throw new Exception('Table is assigned to another waiter.');
            }

            $dup = $query->select("sales", "id", "table_id = ? AND created_at >= (NOW() - INTERVAL 1 MINUTE)", [$table_id], "i");
            if($dup->num_rows > 0){
                throw new Exception('Duplicate sale detected. Try again in a minute.');
            }

            $total = 0;
            $items = [];
            foreach($product_ids as $idx => $pid){
                $pid = (int)$pid;
                $qty = isset($quantities[$idx]) ? (int)$quantities[$idx] : 0;
                if($pid <= 0 || $qty <= 0){
                    continue;
                }
                $prod = $query->select("products", "*", "id = ?", [$pid], "i");
                if($prod->num_rows === 0){
                    continue;
                }
                $p = $prod->fetch_assoc();
                if((int)$p['stock_quantity'] < $qty){
                    throw new Exception('Insufficient stock for ' . $p['name']);
                }
                $price = (float)$p['price'];
                $total += $price * $qty;
                $items[] = [
                    'product_id' => $pid,
                    'quantity' => $qty,
                    'price' => $price,
                    'name' => $p['name'],
                ];
            }

            if(count($items) === 0){
                throw new Exception('No valid items in order.');
            }

            $sale_id = $query->insertGetId("sales", [
                'waiter_id' => $waiter_id,
                'table_id' => $table_id,
                'total_amount' => $total,
                'sale_date' => date('Y-m-d'),
            ]);

            foreach($items as $item){
                $query->insert("sale_items", [
                    'sale_id' => $sale_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);

                $query->conn->query("UPDATE products SET stock_quantity = stock_quantity - {$item['quantity']} WHERE id = {$item['product_id']}");
            }

            $query->update("tables", [
                'assigned_waiter_id' => $waiter_id,
                'status' => 'billing'
            ], "id = {$table_id}");

            $query->conn->commit();
            header("Location: receipt.php?sale_id={$sale_id}");
            exit();
        } catch (Exception $e){
            $query->conn->rollback();
            $alert = "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
        }
    }
}

$products = $query->select("products", "*", "", [], "");
$tables = $query->select("tables", "*", "", [], "");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>POS - Iceland Beach</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: url('../static/Iceland-beach-resort.jpeg') no-repeat center center/cover;
            animation: pan 30s infinite alternate ease-in-out;
            background-attachment: fixed;
        }

        @keyframes pan {
            0% { background-position: left center; }
            100% { background-position: right center; }
        }

        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.75);
            z-index: 0;
        }

        .pos-wrap {
            position: relative;
            z-index: 1;
        }

        .login-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
<div class="container py-4 pos-wrap">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">POS Sales</h3>
        <?php if(isset($_SESSION['waiter_id'])): ?>
            <form method="post">
                <button class="btn btn-outline-danger" name="waiter_logout">Logout</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if($alert): ?>
        <?= $alert; ?>
    <?php endif; ?>

    <?php if(!isset($_SESSION['waiter_id'])): ?>
        <div class="card login-card mx-auto" style="max-width: 420px;">
            <div class="card-body">
                <h5 class="card-title text-center mb-4">Waiter Login</h5>
                <form method="post" class="d-grid gap-3">
                    <input type="text" class="form-control" name="username" placeholder="Username" required>
                    <input type="password" class="form-control" name="password" placeholder="Password" required>
                    <button class="btn btn-primary" type="submit" name="waiter_login">Login</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <form method="post" class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Waiter</label>
                    <input class="form-control" value="<?= htmlspecialchars($_SESSION['waiter_name']); ?>" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label">Select Table</label>
                    <select name="table_id" class="form-select" required>
                        <option value="">Choose table</option>
                        <?php while($t = $tables->fetch_assoc()): ?>
                            <option value="<?= $t['id']; ?>">
                                <?= htmlspecialchars($t['table_name']); ?> (<?= $t['status']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($p = $products->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <input type="hidden" name="product_id[]" value="<?= $p['id']; ?>">
                                        <?= htmlspecialchars($p['name']); ?>
                                    </td>
                                    <td>₦<?= number_format($p['price'], 2); ?></td>
                                    <td><?= $p['stock_quantity']; ?></td>
                                    <td><input type="number" min="0" class="form-control" name="quantity[]" value="0"></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <button class="btn btn-success" type="submit" name="create_sale">Confirm Order</button>
            </div>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
