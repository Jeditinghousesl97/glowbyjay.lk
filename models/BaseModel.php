<?php
/**
 * Base Model
 * 
 * This is the "Parent" class for all Models (User, Product, etc.).
 * It automatically connects to the database when created.
 */
class BaseModel
{
    protected $conn; // properties accessible by children

    public function __construct()
    {
        // Get the single database connection instance
        $database = new Database();
        $this->conn = $database->getConnection();
    }
}
?>