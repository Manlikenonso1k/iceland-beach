<?php 
    class Conn{
        private $db_host;
        private $db_user;
        private $db_pass;
        private $db_name;
        public $conn;

        public function __construct(){
            $this->db_host = "localhost";
            $this->db_user = "u519226541_icelandbeach";
            $this->db_pass = "YXvvHRefb*e1@L8gR1AruDTK";
            $this->db_name = "u519226541_iceland";
            $this->conn = new mysqli($this->db_host, $this->db_user, $this->db_pass, $this->db_name, 4306);

            if($this->conn->connect_error){
                die("Connection Error ". $this->conn->connect_error);
            }
        }

        }