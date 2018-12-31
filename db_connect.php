<?php 
class db_connect {
    private $con;
    function __construct(){
 
    }

    function connect(){
        include_once dirname(__FILE__) . '/config.php';
        $this->con = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        return $this->con;
    }
 
}