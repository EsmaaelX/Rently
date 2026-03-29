<?php
/**
 * Database Connection Class (Singleton)
 * Uses PDO with prepared statements to prevent SQL Injection.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private string $host   = 'localhost';
    private string $dbName = 'rently';
    private string $user   = 'root';
    private string $pass   = '';

    private function __construct()
    {
        $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    /** Get the singleton instance */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Get the raw PDO connection */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    // Prevent cloning / unserialization
    private function __clone() {}
    public function __wakeup() { throw new \Exception("Cannot unserialize singleton"); }
}
