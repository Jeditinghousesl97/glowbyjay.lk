<?php
/**
 * Feedback Model (Maps to 'reviews' table)
 */
require_once 'models/BaseModel.php';

class Feedback extends BaseModel
{

    public function getAll()
    {
        // Date > Newly added (DESC)
        $sql = "SELECT * FROM reviews ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM reviews WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($imagePath)
    {
        $sql = "INSERT INTO reviews (image_path) VALUES (:image_path)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':image_path', $imagePath);
        return $stmt->execute();
    }

    public function delete($id)
    {
        $sql = "DELETE FROM reviews WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>