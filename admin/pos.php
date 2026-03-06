<?php
session_start();
require_once "../core/config/dbquery.php";

$query = new Dbquery();
$alert = '';

function clean($value){
    return htmlspecialchars(trim((string)$value));
}

if(isset($_POST['waiter_login'])){
    $pin = preg_replace('/\D+/', '', $_POST['pin'] ?? '');

    if($pin === ''){
        $alert = "<div class='alert alert-danger'>PIN is required.</div>";
    } else {
        $waiter = $query->select("waiters", "*", "is_active = 1 AND pin_hash IS NOT NULL", [], "");
        $found = false;
        while($w = $waiter->fetch_assoc()){
            if(password_verify($pin, $w['pin_hash'])){
                $_SESSION['waiter_id'] = $w['id'];
                $_SESSION['waiter_name'] = $w['full_name'];
                $_SESSION['waiter_role'] = $w['role'];
                $found = true;
                break;
            }
        }
        if(!$found){
            $alert = "<div class='alert alert-danger'>Invalid PIN.</div>";
        }
    }
}

if(isset($_POST['waiter_logout'])){
    unset($_SESSION['waiter_id'], $_SESSION['waiter_name'], $_SESSION['waiter_role']);
}

if(isset($_GET['force_login']) && $_GET['force_login'] === '1'){
    unset($_SESSION['waiter_id'], $_SESSION['waiter_name'], $_SESSION['waiter_role']);
}

if(isset($_POST['create_sale']) && isset($_SESSION['waiter_id'])){
    $waiter_id = (int)$_SESSION['waiter_id'];
    $table_id = (int)($_POST['table_id'] ?? 0);
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $voided_flags = $_POST['is_voided'] ?? [];
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $void_reason = clean($_POST['void_reason'] ?? '');

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
                $is_voided = isset($voided_flags[$idx]) ? (int)$voided_flags[$idx] : 0;
                if($pid <= 0 || $qty <= 0){
                    continue;
                }
                $prod = $query->select("products", "*", "id = ?", [$pid], "i");
                if($prod->num_rows === 0){
                    continue;
                }
                $p = $prod->fetch_assoc();
                if($is_voided === 0 && (int)$p['stock_quantity'] < $qty){
                    throw new Exception('Insufficient stock for ' . $p['name']);
                }
                $price = (float)$p['price'];
                if($is_voided === 0){
                    $total += $price * $qty;
                }
                $items[] = [
                    'product_id' => $pid,
                    'quantity' => $qty,
                    'price' => $price,
                    'name' => $p['name'],
                    'is_voided' => $is_voided,
                ];
            }

            if(count($items) === 0){
                throw new Exception('No valid items in order.');
            }

            $has_voids = false;
            foreach($items as $item){
                if($item['is_voided'] === 1){
                    $has_voids = true;
                    break;
                }
            }
            if($has_voids && $void_reason === ''){
                throw new Exception('Void reason is required for voided items.');
            }

            $sale_id = $query->insertGetId("sales", [
                'waiter_id' => $waiter_id,
                'table_id' => $table_id,
                'total_amount' => $total,
                'payment_method' => in_array($payment_method, ['cash','transfer','card'], true) ? $payment_method : 'cash',
                'sale_date' => date('Y-m-d'),
            ]);

            foreach($items as $item){
                $sale_item_id = $query->insertGetId("sale_items", [
                    'sale_id' => $sale_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'is_voided' => $item['is_voided'],
                ]);

                if($item['is_voided'] === 0){
                    $query->conn->query("UPDATE products SET stock_quantity = stock_quantity - {$item['quantity']} WHERE id = {$item['product_id']}");
                } else {
                    $query->insert("void_logs", [
                        'sale_item_id' => $sale_item_id,
                        'voided_by' => $waiter_id,
                        'reason' => $void_reason,
                    ]);
                }
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
$view = $_GET['view'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="../static/images/img (1).png">
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .pin-input {
            text-align: center;
            font-size: 1.5rem;
            letter-spacing: 8px;
        }

        .keypad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .keypad button {
            padding: 14px;
            font-size: 1.1rem;
            border-radius: 10px;
        }

        .pos-layout {
            min-height: 100vh;
            padding: 20px 0;
        }

        .product-tile {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            background: #fff;
            cursor: pointer;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 6px;
        }

        .product-tile.active {
            outline: 3px solid #0d6efd;
        }

        .order-panel {
            background: #fff;
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }

        .disabled-card {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .order-item.selected {
            background: #f1f5ff;
        }

        .order-item.voided {
            text-decoration: line-through;
            opacity: 0.6;
        }

        .payment-btn.active {
            background: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        .pos-dark .order-panel,
        .pos-dark .product-tile,
        .pos-dark .login-card {
            background: #111827 !important;
            color: #e5e7eb;
        }
    </style>
</head>
<body>
<div class="container py-4 pos-wrap">

    <?php if($alert): ?>
        <?= $alert; ?>
    <?php endif; ?>

    <?php if(!isset($_SESSION['waiter_id'])): ?>
        <div class="card login-card mx-auto" style="max-width: 420px; width: 100%;">
            <div class="card-body">
                <div class="text-center mb-3">
                    <img src="../static/images/img (1).png" alt="Iceland Logo" width="60" height="60">
                </div>
                <h3 class="text-center mb-2">POS Sales</h3>
                <h6 class="card-title text-center text-muted mb-4">Waiter Login</h6>
                <form method="post" class="d-grid gap-3" autocomplete="off">
                    <input type="password" class="form-control pin-input" name="pin" id="pinInput" placeholder="Enter PIN" inputmode="numeric" pattern="\d{4,6}" required autocomplete="off">
                    <div class="keypad">
                        <button type="button" class="btn btn-outline-primary" data-key="1">1</button>
                        <button type="button" class="btn btn-outline-primary" data-key="2">2</button>
                        <button type="button" class="btn btn-outline-primary" data-key="3">3</button>
                        <button type="button" class="btn btn-outline-primary" data-key="4">4</button>
                        <button type="button" class="btn btn-outline-primary" data-key="5">5</button>
                        <button type="button" class="btn btn-outline-primary" data-key="6">6</button>
                        <button type="button" class="btn btn-outline-primary" data-key="7">7</button>
                        <button type="button" class="btn btn-outline-primary" data-key="8">8</button>
                        <button type="button" class="btn btn-outline-primary" data-key="9">9</button>
                        <button type="button" class="btn btn-outline-secondary" data-key="clear">Clear</button>
                        <button type="button" class="btn btn-outline-primary" data-key="0">0</button>
                        <button type="button" class="btn btn-outline-secondary" data-key="back">Back</button>
                    </div>
                    <button class="btn btn-primary" type="submit" name="waiter_login">Login</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <?php if($view !== 'pos'): ?>
            <div class="w-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0 text-center flex-grow-1">POS Dashboard</h3>
                    <form method="post">
                        <button class="btn btn-outline-danger" name="waiter_logout">Logout</button>
                    </form>
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <a href="pos.php?view=pos" class="card text-decoration-none text-dark h-100">
                            <div class="card-body text-center">
                                <div class="display-6">🧾</div>
                                <h5 class="mt-3">POS</h5>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="report_print.php?waiter_id=<?= (int)$_SESSION['waiter_id']; ?>" class="card text-decoration-none text-dark h-100">
                            <div class="card-body text-center">
                                <div class="display-6">📊</div>
                                <h5 class="mt-3">Reports</h5>
                            </div>
                        </a>
                    </div>
                    <?php $is_manager = $_SESSION['waiter_role'] === 'manager'; ?>
                    <div class="col-md-4">
                        <div class="card h-100 <?= $is_manager ? '' : 'disabled-card'; ?>" title="Manager access only">
                            <?php if($is_manager): ?><a href="index.php#waiters" class="stretched-link"></a><?php endif; ?>
                            <div class="card-body text-center">
                                <div class="display-6">👥</div>
                                <h5 class="mt-3">Users</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 <?= $is_manager ? '' : 'disabled-card'; ?>" title="Manager access only">
                            <?php if($is_manager): ?><a href="index.php#inventory" class="stretched-link"></a><?php endif; ?>
                            <div class="card-body text-center">
                                <div class="display-6">🧃</div>
                                <h5 class="mt-3">Products</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 <?= $is_manager ? '' : 'disabled-card'; ?>" title="Manager access only">
                            <?php if($is_manager): ?><a href="index.php#settings" class="stretched-link"></a><?php endif; ?>
                            <div class="card-body text-center">
                                <div class="display-6">⚙️</div>
                                <h5 class="mt-3">Settings</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <form method="post" class="card h-100 text-center">
                            <button class="btn h-100" name="waiter_logout" style="border: none; background: transparent;">
                                <div class="card-body">
                                    <div class="display-6">🚪</div>
                                    <h5 class="mt-3">Logout</h5>
                                </div>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
        <form method="post" class="pos-layout w-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex gap-2">
                    <a href="pos.php" class="btn btn-outline-secondary">Dashboard</a>
                </div>
                <h3 class="mb-0 text-center flex-grow-1">POS Sales</h3>
                <button class="btn btn-outline-danger" name="waiter_logout" formmethod="post" formaction="">Logout</button>
            </div>

            <div class="row g-3">
                <!-- Left: Keypad -->
                <div class="col-lg-3">
                    <div class="order-panel">
                        <h6 class="text-uppercase text-muted">Quantity</h6>
                        <input type="text" class="form-control pin-input mb-3" id="qtyInput" placeholder="0" readonly>
                        <div class="keypad mb-3" id="qtyKeypad">
                            <button type="button" class="btn btn-outline-primary" data-key="1">1</button>
                            <button type="button" class="btn btn-outline-primary" data-key="2">2</button>
                            <button type="button" class="btn btn-outline-primary" data-key="3">3</button>
                            <button type="button" class="btn btn-outline-primary" data-key="4">4</button>
                            <button type="button" class="btn btn-outline-primary" data-key="5">5</button>
                            <button type="button" class="btn btn-outline-primary" data-key="6">6</button>
                            <button type="button" class="btn btn-outline-primary" data-key="7">7</button>
                            <button type="button" class="btn btn-outline-primary" data-key="8">8</button>
                            <button type="button" class="btn btn-outline-primary" data-key="9">9</button>
                            <button type="button" class="btn btn-outline-secondary" data-key="clear">Clear</button>
                            <button type="button" class="btn btn-outline-primary" data-key="0">0</button>
                            <button type="button" class="btn btn-outline-success" data-key="enter">Enter</button>
                        </div>
                        <div class="small text-muted">Tap product, enter quantity, press Enter.</div>
                    </div>
                </div>

                <!-- Center: Products -->
                <div class="col-lg-6">
                    <div class="order-panel mb-3">
                        <div class="d-flex gap-2 flex-wrap mb-2">
                            <button type="button" class="btn btn-outline-primary filter-btn" data-category="all">All</button>
                            <button type="button" class="btn btn-outline-primary filter-btn" data-category="food">Food</button>
                            <button type="button" class="btn btn-outline-primary filter-btn" data-category="drinks">Drinks</button>
                            <button type="button" class="btn btn-outline-primary filter-btn" data-category="alcohol">Alcohol</button>
                            <button type="button" class="btn btn-outline-primary filter-btn" data-category="grills">Grills</button>
                            <button type="button" class="btn btn-outline-primary filter-btn" data-category="desserts">Desserts</button>
                            <button type="button" class="btn btn-outline-primary filter-btn" data-category="others">Others</button>
                        </div>
                        <input type="text" class="form-control mb-3" id="productSearch" placeholder="Search products..." autofocus>
                        <div class="row g-2" id="productGrid">
                            <?php while($p = $products->fetch_assoc()):
                                $cat = strtolower(trim($p['category']));
                            ?>
                                <div class="col-6 col-md-4 product-item" data-category="<?= htmlspecialchars($cat); ?>" data-name="<?= htmlspecialchars(strtolower($p['name'])); ?>">
                                    <div class="product-tile" data-id="<?= $p['id']; ?>" data-name="<?= htmlspecialchars($p['name']); ?>" data-price="<?= $p['price']; ?>">
                                        <strong><?= htmlspecialchars($p['name']); ?></strong>
                                        <span>₦<?= number_format($p['price'], 2); ?></span>
                                        <small class="text-muted"><?= htmlspecialchars($p['category']); ?></small>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Order Panel -->
                <div class="col-lg-3">
                    <div class="order-panel">
                        <div class="mb-2">
                            <label class="form-label">Waiter</label>
                            <input class="form-control" value="<?= htmlspecialchars($_SESSION['waiter_name']); ?>" disabled>
                        </div>
                        <div class="mb-2">
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

                        <div id="orderList" class="mb-3"></div>
                        <div class="d-flex justify-content-between">
                            <strong>Subtotal</strong><strong id="subtotal">₦0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total</strong><strong id="total">₦0.00</strong>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Void reason (required if voiding)</label>
                            <input type="text" class="form-control" name="void_reason" placeholder="e.g. Mistake">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment</label>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-primary payment-btn active" data-method="cash">Cash</button>
                                <button type="button" class="btn btn-outline-primary payment-btn" data-method="transfer">Transfer</button>
                                <button type="button" class="btn btn-outline-primary payment-btn" data-method="card">Debit Card</button>
                            </div>
                            <input type="hidden" name="payment_method" id="paymentMethod" value="cash">
                        </div>

                        <div id="hiddenInputs"></div>
                        <button class="btn btn-success w-100" type="submit" name="create_sale">Confirm Order</button>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script>
    const pinInput = document.getElementById('pinInput');
    if (pinInput) {
        document.querySelectorAll('.keypad button').forEach(btn => {
            btn.addEventListener('click', () => {
                const key = btn.getAttribute('data-key');
                if (key === 'clear') {
                    pinInput.value = '';
                    return;
                }
                if (key === 'back') {
                    pinInput.value = pinInput.value.slice(0, -1);
                    return;
                }
                if (pinInput.value.length < 6) {
                    pinInput.value += key;
                }
            });
        });
    }

    const productSearch = document.getElementById('productSearch');
    if (productSearch) {
        productSearch.focus();
    }

    const orderList = document.getElementById('orderList');
    const hiddenInputs = document.getElementById('hiddenInputs');
    const subtotalEl = document.getElementById('subtotal');
    const totalEl = document.getElementById('total');
    const qtyInput = document.getElementById('qtyInput');

    let selectedItemId = null;
    const order = {};

    const updateTotals = () => {
        let subtotal = 0;
        Object.values(order).forEach(item => {
            if (!item.is_voided) {
                subtotal += item.price * item.quantity;
            }
        });
        subtotalEl.textContent = `₦${subtotal.toFixed(2)}`;
        totalEl.textContent = `₦${subtotal.toFixed(2)}`;
    };

    const renderOrder = () => {
        orderList.innerHTML = '';
        hiddenInputs.innerHTML = '';

        Object.values(order).forEach(item => {
            const row = document.createElement('div');
            row.className = `order-item p-2 mb-2 border rounded ${item.id === selectedItemId ? 'selected' : ''}`;
            row.innerHTML = `
                <div class="d-flex justify-content-between">
                    <strong>${item.name}</strong>
                    <span>₦${(item.price * item.quantity).toFixed(2)}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-action="dec" data-id="${item.id}">-</button>
                        <span class="mx-2">${item.quantity}</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-action="inc" data-id="${item.id}">+</button>
                    </div>
                    <button type="button" class="btn btn-sm ${item.is_voided ? 'btn-danger' : 'btn-outline-danger'}" data-action="void" data-id="${item.id}">${item.is_voided ? 'VOIDED' : 'Void'}</button>
                </div>
            `;
            row.addEventListener('click', () => {
                selectedItemId = item.id;
                renderOrder();
            });
            orderList.appendChild(row);

            const pid = document.createElement('input');
            pid.type = 'hidden';
            pid.name = 'product_id[]';
            pid.value = item.id;
            hiddenInputs.appendChild(pid);

            const qty = document.createElement('input');
            qty.type = 'hidden';
            qty.name = 'quantity[]';
            qty.value = item.quantity;
            hiddenInputs.appendChild(qty);

            const voided = document.createElement('input');
            voided.type = 'hidden';
            voided.name = 'is_voided[]';
            voided.value = item.is_voided ? 1 : 0;
            hiddenInputs.appendChild(voided);
        });

        updateTotals();
    };

    document.querySelectorAll('.product-tile').forEach(tile => {
        tile.addEventListener('click', () => {
            const id = tile.getAttribute('data-id');
            const name = tile.getAttribute('data-name');
            const price = parseFloat(tile.getAttribute('data-price'));
            if (!order[id]) {
                order[id] = { id, name, price, quantity: 1, is_voided: false };
            } else {
                order[id].quantity += 1;
            }
            selectedItemId = id;
            renderOrder();
        });
    });

    if (orderList) {
        orderList.addEventListener('click', (e) => {
            const button = e.target.closest('button');
            if (!button) return;
            const id = button.getAttribute('data-id');
            const action = button.getAttribute('data-action');
            if (!order[id]) return;
            if (action === 'inc') order[id].quantity += 1;
            if (action === 'dec') order[id].quantity = Math.max(1, order[id].quantity - 1);
            if (action === 'void') order[id].is_voided = !order[id].is_voided;
            renderOrder();
        });
    }

    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const cat = btn.getAttribute('data-category');
            document.querySelectorAll('.product-item').forEach(item => {
                if (cat === 'all' || item.getAttribute('data-category') === cat) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });

    if (productSearch) {
        productSearch.addEventListener('input', () => {
            const q = productSearch.value.toLowerCase();
            document.querySelectorAll('.product-item').forEach(item => {
                const name = item.getAttribute('data-name');
                item.style.display = name.includes(q) ? '' : 'none';
            });
        });
    }

    const paymentMethod = document.getElementById('paymentMethod');
    document.querySelectorAll('.payment-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.payment-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            paymentMethod.value = btn.getAttribute('data-method');
        });
    });

    const qtyKeypad = document.getElementById('qtyKeypad');
    if (qtyKeypad && qtyInput) {
        qtyKeypad.addEventListener('click', (e) => {
            const key = e.target.getAttribute('data-key');
            if (!key) return;
            if (key === 'clear') {
                qtyInput.value = '';
                return;
            }
            if (key === 'enter') {
                if (!selectedItemId || !order[selectedItemId]) return;
                const qtyVal = parseInt(qtyInput.value || '0', 10);
                if (qtyVal > 0) {
                    order[selectedItemId].quantity = qtyVal;
                    renderOrder();
                }
                return;
            }
            if (qtyInput.value.length < 3) {
                qtyInput.value += key;
            }
        });
    }
</script>
</body>
</html>
