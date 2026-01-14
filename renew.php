<?php  
        $title = "Renew Membership";
        require_once "includes/header.php";
        // require_once "core/config/dbquery.php";
        require_once 'core/controller/controller.php';
        $query = new Dbquery();

        if(isset($_POST['proceed'])){
            $error = [];
            $name = Controller::sanitize($_POST['full_name']);
            $fullname = explode(" ", Controller::sanitize($_POST['full_name']), 3);
            $email = Controller::sanitize($_POST['email']);

            $select = $query->select("membership", "email", "email = ?", [$email], 's');

            if($select->num_rows > 0){
                $_SESSON['firstname'] = $fullname[0];
                $_SESSION['lastname'] = $fullname[1];
                $_SESSION['email'] = Controller::sanitize($_POST['email']);
    
                $price = 20000;

                if(isset($_POST['mtype'])){
                    $mtype = Controller::sanitize($_POST['mtype']);
                    if($mtype === "Premium"){
                        $price = 50000;
                    }
                }
                $_SESSION['mtype'] = $mtype;

                $duration = 1;
                $_SESSION['duration'] = $duration;
                if (isset($_POST['mplan'])) {
                    $mplan = Controller::sanitize($_POST['mplan']);
                    $_SESSION['mplan'] = $mplan;   
                    if ($mplan === "Quarterly") {
                        $price = $price * 6;
                        $_SESSION['duration'] = 6;
                    } elseif ($mplan === "Annually") {
                        $price = $price * 12 * 80 / 100;
                        $_SESSION['duration'] = 12;
                    }
    
                    $_SESSION['price'] = Controller::sanitize($price);

                    echo Controller::alert("primary", "<b>Success!!</b> You will be redirected soon");
                    echo Controller::counttime("1", "core/processor/confirmprice2.php");
                }
            }else{
                echo $error[] = Controller::alert("danger", "Account does not exist");
            }

        }
?>
<style>
    form{
        width: 500px;
        max-width: 100%;
    }
</style>

    <div class="container p-3">
        <form method="POST" class="needs-validation mx-auto" novalidate>
        <h1 class="text-center">Renew Membership</h1>
            <div class="my-3">
                <label for="validationCustom01" class="form-label">Full name</label>
                <input type="text" class="form-control" id="validationCustom01" placeholder="John Doe" name="full_name" required minlength="8">
                <div class="invalid-feedback">
                    Please input a valid name
                </div>
            </div>

            <div class="my-3">
                <label for="validationCustom01" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="validationCustom01" placeholder="John@me.com" name="email" required minlength="8">
                <div class="invalid-feedback">
                    Please input a valid name
                </div>
            </div>
            
            <div class="col-12 my-2">
                <label for="validationCustom04" class="form-label">Membership Plan</label>
                <select class="form-select" id="validationCustom04" required name="mplan">
                    <option selected disabled value="">Choose...</option>
                    <option value="Monthly">Monthly</option>
                    <option value="Quarterly">Quarterly</option>
                    <option value="Annually">Annually</option>
                </select>
                <div class="invalid-feedback">
                    Please select a Subscription Plan
                </div>
            </div>

            <div class="col-12 my-2">
                <label for="validationCustom04" class="form-label">Membership Type</label>
                <select class="form-select" id="validationCustom04" required name="mtype">
                    <option selected disabled value="">Choose...</option>
                    <option value="Basic">Basic</option>
                    <option value="Premium">Premium</option>
                </select>
                <div class="invalid-feedback">
                    Please select a Subscription Type
                </div>
            </div>

            <div class="my-4">
                <button class="btn btn-warning w-100" type="submit" name="proceed">Proceed</button>
            </div>

        </form>
    </div>




<script>
    // Example starter JavaScript for disabling form submissions if there are invalid fields
(() => {
  'use strict'

  // Fetch all the forms we want to apply custom Bootstrap validation styles to
  const forms = document.querySelectorAll('.needs-validation')

  // Loop over them and prevent submission
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }

      form.classList.add('was-validated')
    }, false)
  })
})()
</script>
<?php 
    require_once "includes/footer.php";
?>