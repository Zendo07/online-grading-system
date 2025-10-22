<?php
// Database Connection Class

class Database {
    private $host = 'localhost';
    private $db_name = 'online_grading_system';
    private $username = 'root';  // Change if needed
    private $password = '';      // Change if needed
    private $conn;

    // Get database connection
    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
            die();
        }

        return $this->conn;
    }
}
?>