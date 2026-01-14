<?php 
    require_once "../config/dbquery.php";
    require_once "../controller/controller.php";

    class Validatemember extends Dbquery{
        public $alerts = array();

        function validate($post, $price){
            $fullname = Controller::sanitize($post['fullname']);
            $email = Controller::sanitize($post['email']);
            $pass = Controller::sanitize($post['password']);
            $password = password_hash($pass, PASSWORD_DEFAULT);

            if(empty($fullname) or empty($email) or empty($pass)){
                $this->alerts[] = Controller::alert("danger", "Please Fill all Fields");
            }

            if(isset($post['duration']) or !empty($post['duration'])){
                $duration = Controller::sanitize($post['duration']);
                $normalprice = 0;
                if($duration == 12){
                    $normalprice = (int)$duration * $price * 90 / 100; 
                }else{
                    $normalprice = (int)$duration * $price;
                }
            }else{
                $this->alerts[] = Controller::alert("danger", "Please Choose Your membership Duration");
            }

            if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
                $this->alerts[] = Controller::alert("danger", "Please Provied a valid email");
            }


            $selectuser = $this->select("membership", "email", "email = ?", [$email], "s");

            if(count($this->alerts) == 0){
                $_SESSION['fullname'] = $fullname;
                $_SESSION['email'] = $email;
                $_SESSION['password'] = $password;
                $_SESSION['normal_price'] = $normalprice;
                $_SESSION['price'] = $price;
                $_SESSION['duration'] = $duration;
                if($selectuser->num_rows > 0){
                    $this->alerts[] = Controller::alert("primary", "User already exist, Please Login or proceed to renew Your membership");
                    echo Controller::counttime("1", "confirmprice.php");
                }else{
                    $this->alerts[] = Controller::alert("success", "Account Created Successfully, Please Proceed");
                    echo Controller::counttime("1", "confirmprice.php");
                }

            }


            return $this->alerts;
        }
    }