<?php 
    require_once "includes/header.php";
    require_once "core/config/dbquery.php";
    require_once 'core/controller/controller.php';

    $query = new Dbquery();

    if(isset($_GET['name'])):
        $name = Controller::sanitize($_GET['name']);
        $data = $query->select("membership", "*", "full_name = ?", [$name], 's');
            if($data->num_rows > 0):
                $fetchuser = $data->fetch_assoc();
?> 
    <div class="container">
        

        <h2>User Details</h2>
        <div class="card my-5">
            <div class="card-body">
                <h3 class="card-title mb-4">Name: <?php echo $fetchuser['full_name'];?></h3>
                <h6 class="card-subtitle mb-2 text-muted">Email: <?php echo $fetchuser['email'];?></h6>
                <h6 class="card-text mb-2 text-muted">Date Of Birth: <?php echo $fetchuser['dob'];?></h6>
                <h6 class="card-text mb-2 text-muted">Place Of Birth: <?php echo $fetchuser['pob'];?></h6>
                <h6 class="card-text mb-2 text-muted">Phone: <?php echo $fetchuser['phone_no'];?></h6>
                <h6 class="card-text mb-2 text-muted">Membership Type: <?php echo $fetchuser['type'];?></h6>
                <h6 class="card-text mb-2 text-muted">Join Date: <?php echo $fetchuser['start_date'];?></h6>
                <h6 class="card-text mb-2 text-muted">Expiry Date: <?php echo $fetchuser['end_date'];?></h6>
                <h6 class="card-text mb-2 text-muted">Address: <?php echo $fetchuser['address'];?></h6>
                <h6 class="card-text mb-2 text-muted">City: <?php echo $fetchuser['city'];?></h6>
                <h6 class="card-text mb-2 text-muted">Nationality: <?php echo $fetchuser['nationality'];?></h6>
                <h6 class="card-text mb-2 text-muted">Emergency Name: <?php echo $fetchuser['ename'];?></h6>
                <h6 class="card-text mb-2 text-muted">Emergency Number: <a href="tel: <?php echo $fetchuser['ephone'];?>"><?php echo $fetchuser['ephone'];?></a></h6>
                
                <a href="index.php" class="btn btn-secondary my-4">Back Home</a>
            </div>
        </div>
        </div>

            <?php else: ?>
                <p class="text-center mt-5 mb-2 text-muted">User does'nt exist</p>
                <a href="index.php" class="btn btn-secondary my-4">Back Home</a>
            <?php endif; ?>



    </div>
<?php else: ?>
    <h1 class="text-center my-5">No User is Available</h1>
<?php 
    endif;
    
    require_once 'includes/footer.php';

?>
