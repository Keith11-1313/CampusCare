<?php
require_once __DIR__ . '/config.php';

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        }
        catch (PDOException $e) {
<<<<<<< HEAD
            error_log("CampusCare DB connection failed: " . $e->getMessage());
            die("Database connection failed. Please check your configuration.");
=======
            die("Database connection failed: " . $e->getMessage());
>>>>>>> 624513a96c1a8a7d40912a2b3205458cbff711af
        }
    }

    /**
     * Get singleton database instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the PDO connection object
     */
    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * Execute a prepared query and return the statement
     */
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch all rows from a query
     */
    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Fetch a single row
     */
    public function fetch($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Fetch a single column value
     */
    public function fetchColumn($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    /**
     * Get the last inserted ID
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback()
    {
        return $this->pdo->rollBack();
    }

    // Prevent cloning
    private function __clone()
    {
    }
}
