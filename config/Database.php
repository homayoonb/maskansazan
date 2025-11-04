<?php


class Database {
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $dbname = "maskansazan";

    public $conn;

    public function connect()
    {
        $this->conn = null;
        try {
            $this->conn=new mysqli($this->host,$this->user,$this->pass,$this->dbname);
            $this->conn->set_charset("utf8");
            if($this->conn->connect_error){
                die("Connection failed: ".$this->conn->connect_error);
            }
            return $this->conn;
//            echo "Connected successfully";
        }catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
