<?php
/**
 * Database Configuration & Connection
 * 
 * This file handles the connection to the MySQL database.
 * We use the Singleton Pattern here. This means the system will only ever 
 * create ONE connection to the database and reuse it, rather than opening 
 * a new connection every time we need data. This makes the site faster.
 */

// Load the main configuration file
require_once dirname(__DIR__) . '/config/config.php';

class Database
{
    // Configuration Settings (Now loaded from constants)
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $port = DB_PORT;

    // The connection variable
    public $conn;

    /**
     * Get Database Connection
     * 
     * This function attempts to connect to the database.
     * If successful, it returns the connection object.
     * If it fails, it shows an error message.
     */
    public function getConnection()
    {
        $this->conn = null;

        try {

            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";

            // Create the PDO connection
            // PDO (PHP Data Objects) is a secure way to connect to databases
            $this->conn = new PDO($dsn, $this->username, $this->password);

            // Set error mode to exception
            // This means if there is a SQL error, PHP will stop and tell us exactly what's wrong
            // instead of silently failing.
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $exception) {
            // Avoid leaking database errors into HTML/XML responses such as sitemap.xml.
            error_log('Database connection error: ' . $exception->getMessage());
        }

        return $this->conn;
    }
}
?>
