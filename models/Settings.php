<?php
/**
 * Settings Model
 */
require_once 'models/BaseModel.php';

class Settings extends BaseModel
{
    public function getAll()
    {
        $sql = "SELECT setting_key, setting_value FROM settings";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        // Fetch as key-value pairs
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function get($key)
    {
        $sql = "SELECT setting_value FROM settings WHERE setting_key = :key";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':key', $key);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}
?>