<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'task_tracker';
    private $username = 'root';  // Update if using custom username
    private $password = 'Joshi@123';      // Add password if any
    public $conn;

    public function connect() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=$this->host;dbname=$this->db_name;charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("DB Connection failed: " . $e->getMessage());
        }
        return $this->conn;
    }
}
?>
