<?php 
    session_start();
    require_once "../core/config/dbquery.php";
    require_once "sendmail.php";
    
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $query = new Dbquery();
    $mail = new Sendmail();

    function clean($value){
        return htmlspecialchars(trim((string)$value));
    }

    if(isset($_SESSION['username'])):
        $username = $_SESSION['username'];
        $selectuser = $query->select("admin", "*", "username = ?", [$username], "s");

    $select_rooms = $query->select("room", "*", "", [], "");

    $selectmember = $query->select("membership", "*", "", [], "");

    $services = $query->select("services", "*", "", [], "");

    $products = $query->select("products", "*", "", [], "");
    $waiters = $query->select("waiters", "*", "", [], "");
    $tables = $query->select("tables", "*", "", [], "");

    $low_stock_count = 0;
    $low_stock = $query->select("products", "COUNT(*) AS cnt", "stock_quantity <= low_stock_threshold", [], "");
    if($low_stock && $low_stock->num_rows > 0){
        $low_stock_count = (int)$low_stock->fetch_assoc()['cnt'];
    }

    $admin_alert = '';

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        // Inventory
        if(isset($_POST['add_product'])){
            $name = clean($_POST['name'] ?? '');
            $category = clean($_POST['category'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $stock = (int)($_POST['stock_quantity'] ?? 0);
            $low_stock_threshold = (int)($_POST['low_stock_threshold'] ?? 5);

            if($name !== '' && $category !== ''){
                $query->insert("products", [
                    'name' => $name,
                    'category' => $category,
                    'price' => $price,
                    'stock_quantity' => $stock,
                    'low_stock_threshold' => $low_stock_threshold,
                ]);
                $admin_alert = "<div class='alert alert-success'>Product added.</div>";
            } else {
                $admin_alert = "<div class='alert alert-danger'>Name and category are required.</div>";
            }
        }

        if(isset($_POST['update_product'])){
            $id = (int)($_POST['id'] ?? 0);
            $name = clean($_POST['name'] ?? '');
            $category = clean($_POST['category'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $stock = (int)($_POST['stock_quantity'] ?? 0);
            $low_stock_threshold = (int)($_POST['low_stock_threshold'] ?? 5);

            if($id > 0){
                $query->update("products", [
                    'name' => $name,
                    'category' => $category,
                    'price' => $price,
                    'stock_quantity' => $stock,
                    'low_stock_threshold' => $low_stock_threshold,
                ], "id = {$id}");
                $admin_alert = "<div class='alert alert-success'>Product updated.</div>";
            }
        }

        if(isset($_POST['delete_product'])){
            $id = (int)($_POST['id'] ?? 0);
            if($id > 0){
                $query->delete("products", "id = ?", [$id], "i");
                $admin_alert = "<div class='alert alert-success'>Product deleted.</div>";
            }
        }

        // Waiters
        if(isset($_POST['add_waiter'])){
            $full_name = clean($_POST['full_name'] ?? '');
            $username = clean($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = clean($_POST['role'] ?? 'waiter');

            if($full_name !== '' && $username !== '' && $password !== ''){
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $query->insert("waiters", [
                    'full_name' => $full_name,
                    'username' => $username,
                    'password_hash' => $hash,
                    'role' => in_array($role, ['waiter','manager'], true) ? $role : 'waiter',
                    'is_active' => 1,
                ]);
                $admin_alert = "<div class='alert alert-success'>Waiter created.</div>";
            } else {
                $admin_alert = "<div class='alert alert-danger'>All waiter fields are required.</div>";
            }
        }

        if(isset($_POST['toggle_waiter'])){
            $id = (int)($_POST['id'] ?? 0);
            $is_active = (int)($_POST['is_active'] ?? 0);
            if($id > 0){
                $query->update("waiters", ['is_active' => $is_active], "id = {$id}");
                $admin_alert = "<div class='alert alert-success'>Waiter status updated.</div>";
            }
        }

        // Tables
        if(isset($_POST['assign_table'])){
            $table_id = (int)($_POST['table_id'] ?? 0);
            $waiter_id = $_POST['assigned_waiter_id'] !== '' ? (int)$_POST['assigned_waiter_id'] : NULL;
            $status = clean($_POST['status'] ?? 'available');

            if($table_id > 0){
                $assigned = $waiter_id ? $waiter_id : NULL;
                $status = in_array($status, ['available','occupied','billing'], true) ? $status : 'available';

                $data = ['assigned_waiter_id' => $assigned, 'status' => $status];
                $query->update("tables", $data, "id = {$table_id}");
                $admin_alert = "<div class='alert alert-success'>Table updated.</div>";
            }
        }
    }

    // Reports
    $report_rows = [];
    $report_products = [];
    if(isset($_GET['report'])){
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';
        $waiter_id = (int)($_GET['waiter_id'] ?? 0);
        $table_id = (int)($_GET['table_id'] ?? 0);
        $product_id = (int)($_GET['product_id'] ?? 0);

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
        if($table_id > 0){
            $conditions[] = "s.table_id = ?";
            $params[] = $table_id;
            $types .= 'i';
        }
        if($product_id > 0){
            $conditions[] = "si.product_id = ?";
            $params[] = $product_id;
            $types .= 'i';
        }

        $where = $conditions ? ("WHERE " . implode(" AND ", $conditions)) : '';

        $sql = "SELECT s.id AS sale_id, s.sale_date, s.total_amount, w.full_name AS waiter_name, t.table_name
                FROM sales s
                JOIN waiters w ON w.id = s.waiter_id
                JOIN tables t ON t.id = s.table_id
                JOIN sale_items si ON si.sale_id = s.id
                {$where}
                GROUP BY s.id
                ORDER BY s.created_at DESC";

        $stmt = $query->conn->prepare($sql);
        if($params){
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()){
            $report_rows[] = $row;
        }

        $sql_products = "SELECT p.name, SUM(si.quantity) AS qty, SUM(si.quantity * si.price) AS total
                        FROM sale_items si
                        JOIN sales s ON s.id = si.sale_id
                        JOIN products p ON p.id = si.product_id
                        {$where}
                        GROUP BY p.id
                        ORDER BY qty DESC";
        $stmt2 = $query->conn->prepare($sql_products);
        if($params){
            $stmt2->bind_param($types, ...$params);
        }
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        while($row = $result2->fetch_assoc()){
            $report_products[] = $row;
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../static/styles/style.css">
    <link rel="stylesheet" href="style.css">
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
   <link rel="stylesheet" href="../static/CAOS.file/CAOS.file.v-1.5/style.css">
  
   <link rel="icon" type="image/png" sizes="16x16" href="../static/images/img (1).png">
    <title>Iceland Beach resorts | Admin Pannel</title>
</head>
<body>

  <style>
    body{
        overflow-x: auto !important;
    }
    table, tr{
      overflow-x: auto;
    }
    th, td{
      width: max-content;
      min-width: max-content;
      text-wrap: nowrap;
    }
    body{
        overflow-x: auto;
    }
  </style>

<nav class="fixed-top mb-5">
   <div class="logo"><img src="../static/images/img (1).png" alt="Iceland Logo" width="30"><span class="lead ms-3"> | ADMIN PANNEL</span></div>

   <a href="post.php" class="btn btn-primary">Post Discount</a>
</nav>


<div class="container mt-5 py-5">
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs nav-pills" id="adminTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab" aria-controls="dashboard" aria-selected="true">Dashboard Overview</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button" role="tab" aria-controls="bookings" aria-selected="false">Room Bookings</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" type="button" role="tab" aria-controls="inventory" aria-selected="false">Product Inventory</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="waiters-tab" data-bs-toggle="tab" data-bs-target="#waiters" type="button" role="tab" aria-controls="waiters" aria-selected="false">Waiter Management</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tables-tab" data-bs-toggle="tab" data-bs-target="#tables" type="button" role="tab" aria-controls="tables" aria-selected="false">Table Management</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pos-tab" data-bs-toggle="tab" data-bs-target="#pos" type="button" role="tab" aria-controls="pos" aria-selected="false">POS Sales</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab" aria-controls="reports" aria-selected="false">Reports</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="false">Settings</button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content mt-3" id="adminTabContent">

            <!-- Dashboard Overview -->
            <div class="tab-pane fade show active p-4" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase text-muted">Total Rooms</h6>
                                <h3><?= $select_rooms->num_rows; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase text-muted">Total Services</h6>
                                <h3><?= $services->num_rows; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase text-muted">Total Members</h6>
                                <h3><?= $selectmember->num_rows; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info mt-4 mb-0">POS, Inventory, and Reports will populate after new tables are created.</div>
            </div>

            <!-- Room Bookings -->
            <div class="tab-pane fade p-4" id="bookings" role="tabpanel" aria-labelledby="bookings-tab">
                <ul class="nav nav-pills mb-3" id="bookingsTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="bookings-rooms-tab" data-bs-toggle="tab" data-bs-target="#bookings-rooms" type="button" role="tab" aria-controls="bookings-rooms" aria-selected="true">Rooms</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="bookings-services-tab" data-bs-toggle="tab" data-bs-target="#bookings-services" type="button" role="tab" aria-controls="bookings-services" aria-selected="false">Services</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="bookings-membership-tab" data-bs-toggle="tab" data-bs-target="#bookings-membership" type="button" role="tab" aria-controls="bookings-membership" aria-selected="false">Membership</button>
                    </li>
                </ul>

                <div class="tab-content" id="bookingsTabContent">
                    <!-- Rooms (existing logic) -->
                    <div class="tab-pane fade show active" id="bookings-rooms" role="tabpanel" aria-labelledby="bookings-rooms-tab">
        <?php if($select_rooms->num_rows > 0): ?>
            <table class="table-bordered table-responsive table-stripped">
                <thead>
                    <tr>
                        <th>Room Name</th>
                        <th>Room Category</th>
                        <th>Room Price</th>
                        <th>Booked By</th>
                        <th>Booked By Email</th>
                        <th>Signin Date</th> 
                        <th>Expiry</th>
                        <th>Booked</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($room = $select_rooms->fetch_assoc()):?>
                        <tr>
                            <td><?php echo $room['room_name'];?></td>
                            <td><?php echo $room['room_category'];?></td>
                            <td><?php echo $room['room_price'];?></td>
                            <td><?php echo $room['customer_name'];?></td>
                            <td><?php echo $room['email'];?></td>
                            <td><?php echo $room['start_date'];?></td>
                            <td><?php echo $room['end_date'];?></td>
                            <td>
                            <?php 
                                $room_name = $room['room_name'];
                                if($room['is_booked'] == "booked"){
                                    echo "<a class='btn btn-success w-100' href='booking.php?free=$room_name'>Booked</a>";
                                }else{
                                    echo "<a class='btn btn-danger w-100' href='booking.php?room=$room_name'>Not Booked</a>";
                                }

                                $current_date = date("Y-m-d H:i:s");
                                $expiry_date = $room['end_date'];
                                $email = $room['email'];

                                  $updateroom = $query->update("room", ['is_booked' => 'expired', 'customer_name' => NULL, 'email' => NULL, 'total_price' => NULL, 'mailsent' => 'yes'], "is_booked = 'booked' AND end_date = '$current_date' AND mailsent = ''");

                                  if($expiry_date == $current_date  ){
                                    $u_message = 
                                    "
                                        <h1>Thank You for purchasing your room at Iceland Beach</h1>
                                        <p>We sent you this mail to inform you that Your room has expired, and will no longer have access to the room agaian. If you wll like to renew, please visit our website for renew </p>
                                        <a href='https://icelandbeach.com' class='cta-button'>Visit Website</a>
                                    ";
                                    $roomname = $room['room_name'];
                                    $u_format = $mail->message($u_message);

                                    $a_message = 
                                    "
                                        <h1>New iceland beach room $roomname has expired, please kindly inform the visitor to remind them.</p>
                                    ";

                                    $a_format = $mail->message($a_message);

                                    $send_mail = $mail->mailsender("", "$a_format", "$u_format", $room['email']);

                                  }

                                  echo 
                                  "
                                    <script>
                                    // Check if the page has already been refreshed
                                    if (!sessionStorage.getItem('refreshed')) {
                                        sessionStorage.setItem('refreshed', 'true');
                                        location.reload();
                                    }
                                    </script>
                                  ";

                                if(strtotime($expiry_date) - strtotime($current_date) <= 70 * 60){
                                    $updateroom = $query->update("room", ['is_booked' => 'no'], "is_booked = 'expired' AND email = '$email'");
                                }
                            ?>

                            </td>
                        </tr>
                    <?php endwhile;?>
                </tbody>
                </tbody> 
            </table>
        <?php else: ?>
            <p class="text-center">No Rooms Available</p>
        <?php endif; ?>
          </div>

          <!-- Services (existing logic) -->
          <div class="tab-pane fade" id="bookings-services" role="tabpanel" aria-labelledby="bookings-services-tab">
        <?php if($services->num_rows > 0): ?>
            <table class="table-bordered table-responsive table-striped-columns">
                <thead>
                    <tr>
                        <th>Space Type</th>
                        <th>Space Category</th>
                        <th>Space Price</th>
                        <th>Booked By</th>
                        <th>Booked By Email</th>
                        <th>Number of visitors</th> 
                        <th>Signin Date</th> 
                        <th>Expiry</th>
                        <th>Booked</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($service = $services->fetch_assoc()):?>
                        <tr>
                            <td><?php echo $service['service_name'];?></td>
                            <td><?php echo $service['service_category'];?></td>
                            <td><?php echo $service['service_price'];?></td>
                            <td><?php echo $service['customers_name'];?></td>
                            <td><?php echo $service['customers_email'];?></td>
                            <td><?php echo $service['no_of_people'];?></td>
                            <td><?php echo $service['signin'];?></td>
                            <td><?php echo $service['signout'];?></td>
                            <td>
                            <?php 
                                $space_name = $service['service_name'];
                                if($service['is_booked'] == "booked"){
                                    echo "<a class='btn btn-success w-100' href='booking.php?frees=".$space_name."'>Booked</a>";
                                }else{
                                    echo "<a class='btn btn-danger w-100' href='booking.php?space=$space_name'>Not Booked</a>";
                                }

                                $current_date = date("Y-m-d H:i:s");
                                $expiry_date = $service['signout'];
                                $email = $service['customers_email'];

                                  $update_service = $query->update("services", ['is_booked' => 'expired', 'customers_name' => NULL, 'customers_email' => NULL, 'total' => NULL, 'mailsent' => 'yes'], "is_booked = 'booked' AND signout = '$current_date' AND mailsent = ''");

                                  if($expiry_date == $current_date){
                                    $u_message = 
                                    "
                                        <h1>Thank You for purchasing your service at Iceland Beach</h1>
                                        <p>We sent you this mail to inform you that Your service has expired, and will no longer have access to this service anymore. If you wlll like to renew, please visit our website for renew </p>
                                        <a href='https://icelandbeach.com' class='cta-button'>Visit Website</a>
                                    ";
                                    $servicename = $service['service_name'];
                                    $customer = $service['customers_name'];
                                    $u_format = $mail->message($u_message);

                                    $a_message = 
                                    "
                                        <h1> New Service expiry</h1>
                                        <p>New iceland beach room $servicename has expired, please kindly inform $customer to remind them.</p>
                                    ";

                                    $a_format = $mail->message($a_message);

                                    $send_mail = $mail->mailsender("", "$a_format", "$u_format", $service['customers_email']);
                                  }
                                  echo 
                                  "
                                    <script>
                                    // Check if the page has already been refreshed
                                    if (!sessionStorage.getItem('refreshed')) {
                                        sessionStorage.setItem('refreshed', 'true');
                                        location.reload();
                                    }
                                    </script>
                                  ";

                                if(strtotime($expiry_date) - strtotime($current_date) <= 70 * 60){
                                    $updateroom = $query->update("services", ['is_booked' => 'no'], "is_booked = 'expired' AND customers_email = '$email'");
                                }
                            ?>

                            </td>
                        </tr>
                    <?php endwhile;?>
                </tbody>
                </tbody> 
            </table>
        <?php else: ?>
            <p class="text-center">No Rooms Available</p>
        <?php endif; ?>
          </div>

          <!-- Membership (existing logic) -->
          <div class="tab-pane fade" id="bookings-membership" role="tabpanel" aria-labelledby="bookings-membership-tab">

    <?php if($selectmember->num_rows > 0): ?>
        <table class="table-bordered table-responsive table-stripped overflow-x-auto">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Start Date</th>
                    <th>Expiry Date</th>
                    <th>Membership Status</th>
                    <th>Membership type</th>
                </tr>
            </thead>
            <tbody>
                <?php while($member = $selectmember->fetch_assoc()):?>
                    <tr>
                        <td><?php echo $member['full_name'];?></td>
                        <td><?php echo $member['email'];?></td>
                        <td><?php echo $member['start_date'];?></td>
                        <td><?php echo $member['end_date'];?></td>
                        <td>
                        <?php 
                            $member_status = $member['member_status'];
                            if($member_status == "paid"){
                                echo "<a class='btn btn-success w-100'>Paid</a>";
                            }else{
                                echo "<a class='btn btn-danger w-100'>Expired</a>";
                            }
                        ?>
                        </td>

                        <td>
                        <?php 
                            $type = $member['type'];
                            if($type == "basic"){
                                echo "<a class='btn btn-primary w-100'>Basic</a>";
                            }else{
                                echo "<a class='btn btn-warning w-100'>Premium</a>";
                            }
                        ?>
                        </td>
                        <td>
                            <a href="userdetail.php?name=<?=$member['full_name']?>" class='btn btn-secondary w-100'>View User Details</a>
                        </td>


                        <?php 
                            $current_date = date("Y-m-d H:i:s");
                            $expiry_date = $member['end_date'];
                            $email = $member['email'];

                            $updatemember = $query->update("membership", ['member_status' => 'expired', 'mailsent' => 'yes'], "member_status = 'paid' AND end_date = start_date AND mailsent = ''");

                            if($expiry_date == $current_date){
                                $u_message = 
                                "
                                    <h1>Membership Expired</h1>
                                    <p>Hello user, inform you that your membership For iceland beach has expired and you no longer have access to our memberhsip features</p>
                                    <a href='https://icelandbeach.com' class='cta-button'>Visit Website</a><br>
                                    <a href='https://icelandbeach.com/renew' class='cta-button'>Renew Membership</a>
                                ";
                            $u_format = $mail->message($u_message);

                            $a_message = 
                            "
                                <h1>New membershp expiry</h1>
                                <p>This is to notify you that A users membership has expired</p>
                            ";

                            $a_format = $mail->message($a_message);

                            $send_mail = $mail->mailsender("", "$a_format", "$u_format", $member['email']);

                            echo 
                                "
                                <script>
                                // Check if the page has already been refreshed
                                if (!sessionStorage.getItem('refreshed')) {
                                    sessionStorage.setItem('refreshed', 'true');
                                    location.reload();
                                }
                                </script>
                                ";

                            }
                            
                            if(strtotime($expiry_date) - strtotime($current_date) == 7 * 24 * 60 * 60){

                            $u_message = 
                            "
                                <h1>Reminder for your membership renewal</h1>
                                <p>Hello user, This is to remind you that your membership will expire in 7 days time. You can re-subscribe to continue enjoying cool features and benefits at Iceland beach</p>
                                <a href='https://icelandbeach.com' class='cta-button'>Visit Website</a><br>
                                <a href='https://icelandbeach.com/renew' class='cta-button'>Renew Membership</a>
                            ";


                            $u_format = $mail->message($u_message);

                            $a_message = 
                            "
                                <h1>New membershp expiry</h1>
                                <p>This is to notify you that A users membership has 7 days to expire </p>
                            ";

                            $a_format = $mail->message($a_message);

                            $send_mail = $mail->mailsender("", "$a_format", "$u_format", $member['email']);
                        }
                        ?>
                    </tr>
                <?php endwhile;?>
            </tbody>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-center">No Rooms Available</p>
    <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Product Inventory -->
            <div class="tab-pane fade p-4" id="inventory" role="tabpanel" aria-labelledby="inventory-tab">
                <?php if($admin_alert): ?>
                    <?= $admin_alert; ?>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Inventory</h5>
                    <span class="badge bg-warning text-dark">Low stock: <?= $low_stock_count; ?></span>
                </div>

                <form method="post" class="row g-2 mb-4">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="name" placeholder="Product name" required>
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control" name="category" placeholder="Category" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" class="form-control" name="price" placeholder="Price" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control" name="stock_quantity" placeholder="Stock" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control" name="low_stock_threshold" placeholder="Low stock" value="5" required>
                    </div>
                    <div class="col-md-1 d-grid">
                        <button class="btn btn-primary" type="submit" name="add_product">Add</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Low Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($products->num_rows > 0): ?>
                                <?php while($product = $products->fetch_assoc()): ?>
                                    <tr>
                                        <form method="post">
                                            <td>
                                                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($product['name']); ?>">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control" name="category" value="<?= htmlspecialchars($product['category']); ?>">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" class="form-control" name="price" value="<?= $product['price']; ?>">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" name="stock_quantity" value="<?= $product['stock_quantity']; ?>">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" name="low_stock_threshold" value="<?= $product['low_stock_threshold']; ?>">
                                            </td>
                                            <td class="d-flex gap-2">
                                                <input type="hidden" name="id" value="<?= $product['id']; ?>">
                                                <button class="btn btn-sm btn-success" type="submit" name="update_product">Save</button>
                                                <button class="btn btn-sm btn-danger" type="submit" name="delete_product" onclick="return confirm('Delete this product?')">Delete</button>
                                            </td>
                                        </form>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center">No products found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Waiter Management -->
            <div class="tab-pane fade p-4" id="waiters" role="tabpanel" aria-labelledby="waiters-tab">
                <?php if($admin_alert): ?>
                    <?= $admin_alert; ?>
                <?php endif; ?>

                <form method="post" class="row g-2 mb-4">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="full_name" placeholder="Full name" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="username" placeholder="Username" required>
                    </div>
                    <div class="col-md-3">
                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="role">
                            <option value="waiter">Waiter</option>
                            <option value="manager">Manager</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-grid">
                        <button class="btn btn-primary" type="submit" name="add_waiter">Add</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($waiters->num_rows > 0): ?>
                                <?php while($waiter = $waiters->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($waiter['full_name']); ?></td>
                                        <td><?= htmlspecialchars($waiter['username']); ?></td>
                                        <td><?= htmlspecialchars($waiter['role']); ?></td>
                                        <td>
                                            <?= $waiter['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'; ?>
                                        </td>
                                        <td>
                                            <form method="post">
                                                <input type="hidden" name="id" value="<?= $waiter['id']; ?>">
                                                <input type="hidden" name="is_active" value="<?= $waiter['is_active'] ? 0 : 1; ?>">
                                                <button class="btn btn-sm btn-outline-primary" type="submit" name="toggle_waiter">
                                                    <?= $waiter['is_active'] ? 'Disable' : 'Enable'; ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center">No waiters found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Table Management -->
            <div class="tab-pane fade p-4" id="tables" role="tabpanel" aria-labelledby="tables-tab">
                <?php if($admin_alert): ?>
                    <?= $admin_alert; ?>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th>Assigned Waiter</th>
                                <th>Status</th>
                                <th>Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($tables->num_rows > 0): ?>
                                <?php while($table = $tables->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($table['table_name']); ?></td>
                                        <td>
                                            <form method="post" class="d-flex gap-2">
                                                <input type="hidden" name="table_id" value="<?= $table['id']; ?>">
                                                <select name="assigned_waiter_id" class="form-select">
                                                    <option value="">Unassigned</option>
                                                    <?php
                                                        $waiters_list = $query->select("waiters", "*", "is_active = 1", [], "");
                                                        while($w = $waiters_list->fetch_assoc()):
                                                    ?>
                                                        <option value="<?= $w['id']; ?>" <?= $table['assigned_waiter_id'] == $w['id'] ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($w['full_name']); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                        </td>
                                        <td>
                                                <select name="status" class="form-select">
                                                    <option value="available" <?= $table['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                                    <option value="occupied" <?= $table['status'] === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                                                    <option value="billing" <?= $table['status'] === 'billing' ? 'selected' : ''; ?>>Billing</option>
                                                </select>
                                        </td>
                                        <td>
                                                <button class="btn btn-sm btn-primary" type="submit" name="assign_table">Save</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center">No tables found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- POS Sales -->
            <div class="tab-pane fade p-4" id="pos" role="tabpanel" aria-labelledby="pos-tab">
                <div class="alert alert-info">Use the POS screen to punch orders and print receipts.</div>
                <a href="pos.php" class="btn btn-primary">Open POS</a>
            </div>

            <!-- Reports -->
            <div class="tab-pane fade p-4" id="reports" role="tabpanel" aria-labelledby="reports-tab">
                <form method="get" class="row g-2 align-items-end mb-4">
                    <input type="hidden" name="report" value="1">
                    <div class="col-md-2">
                        <label class="form-label">From</label>
                        <input type="date" class="form-control" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To</label>
                        <input type="date" class="form-control" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Waiter</label>
                        <select class="form-select" name="waiter_id">
                            <option value="">All</option>
                            <?php
                                $waiters_list = $query->select("waiters", "*", "", [], "");
                                while($w = $waiters_list->fetch_assoc()):
                            ?>
                                <option value="<?= $w['id']; ?>" <?= (int)($_GET['waiter_id'] ?? 0) === (int)$w['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($w['full_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Table</label>
                        <select class="form-select" name="table_id">
                            <option value="">All</option>
                            <?php
                                $tables_list = $query->select("tables", "*", "", [], "");
                                while($t = $tables_list->fetch_assoc()):
                            ?>
                                <option value="<?= $t['id']; ?>" <?= (int)($_GET['table_id'] ?? 0) === (int)$t['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($t['table_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Product</label>
                        <select class="form-select" name="product_id">
                            <option value="">All</option>
                            <?php
                                $products_list = $query->select("products", "*", "", [], "");
                                while($p = $products_list->fetch_assoc()):
                            ?>
                                <option value="<?= $p['id']; ?>" <?= (int)($_GET['product_id'] ?? 0) === (int)$p['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($p['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary" type="submit">Filter</button>
                    </div>
                </form>

                <?php if(isset($_GET['report'])): ?>
                    <div class="d-flex justify-content-end mb-3">
                        <a class="btn btn-outline-secondary" target="_blank" href="report_print.php?date_from=<?= urlencode($_GET['date_from'] ?? '') ?>&date_to=<?= urlencode($_GET['date_to'] ?? '') ?>&waiter_id=<?= urlencode($_GET['waiter_id'] ?? '') ?>">Print Daily Report</a>
                    </div>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Sale ID</th>
                                    <th>Date</th>
                                    <th>Waiter</th>
                                    <th>Table</th>
                                    <th>Total</th>
                                    <th>Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($report_rows) > 0): ?>
                                    <?php foreach($report_rows as $row): ?>
                                        <tr>
                                            <td><?= $row['sale_id']; ?></td>
                                            <td><?= htmlspecialchars($row['sale_date']); ?></td>
                                            <td><?= htmlspecialchars($row['waiter_name']); ?></td>
                                            <td><?= htmlspecialchars($row['table_name']); ?></td>
                                            <td>₦<?= number_format($row['total_amount'], 2); ?></td>
                                            <td><a href="receipt.php?sale_id=<?= $row['sale_id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Print</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center">No sales found for this filter.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="text-uppercase">Top Selling Products</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($report_products) > 0): ?>
                                    <?php foreach($report_products as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['name']); ?></td>
                                            <td><?= (int)$row['qty']; ?></td>
                                            <td>₦<?= number_format($row['total'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center">No product data.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary">Use the filters above to generate reports.</div>
                <?php endif; ?>
            </div>

            <!-- Settings -->
            <div class="tab-pane fade p-4" id="settings" role="tabpanel" aria-labelledby="settings-tab">
                <div class="alert alert-secondary">Settings coming soon.</div>
            </div>

        </div>








</div>




<!-- FOOTER -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- javascript -->
<script src="../static/scripts/script.js"></script>
</body>
</html>
<?php else: ?>
    echo "<script>window.location.href = 'login.php'</script>";
<?php endif; ?>