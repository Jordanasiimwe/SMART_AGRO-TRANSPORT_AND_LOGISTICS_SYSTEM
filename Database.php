<?php

class Database {
    private static ?Database $instance = null;
    public PDO $connection;

    private function __construct() {
        // Ideally load these from environment variables or a separate config file
        // explicitly ignored by git.
        // Shared hosts often disable putenv()/getenv(), so we check $_ENV and $_SERVER too.
        $host = $_ENV['DB_HOST'] ?? ($_SERVER['DB_HOST'] ?? (getenv('DB_HOST') ?: 'localhost'));
        $db_name = $_ENV['DB_NAME'] ?? ($_SERVER['DB_NAME'] ?? (getenv('DB_NAME') ?: 'agriconnect_db'));
        $username = $_ENV['DB_USER'] ?? ($_SERVER['DB_USER'] ?? (getenv('DB_USER') ?: 'root'));
        $password = $_ENV['DB_PASS'] ?? ($_SERVER['DB_PASS'] ?? (getenv('DB_PASS') ?: ''));

        $dsn = 'mysql:host=' . $host . ';dbname=' . $db_name . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            $errorMessage = "Database Connection Failed. ";
            if (str_contains($e->getMessage(), 'Connection refused') || str_contains($e->getMessage(), '2002') || str_contains($e->getMessage(), 'server has gone away')) {
                $errorMessage .= "The MySQL server is not responding (Error 2002). Please ensure MySQL is started in XAMPP. If it crashes on startup, check mysql_error.log for LSN corruption.";
            }
            throw new Exception($errorMessage . " Details: " . $e->getMessage());
        }
    }

    public static function getInstance(): PDO {
        if (self::$instance !== null) {
            try {
                // Ping the server to ensure connection is still alive
                self::$instance->connection->query('SELECT 1');
            } catch (PDOException $e) {
                // If connection is lost (e.g. server has gone away), reset instance
                self::$instance = null;
            }
        }

        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->connection;
    }
}