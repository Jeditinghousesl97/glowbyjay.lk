<?php
/**
 * Size Guide Model
 */
require_once 'models/BaseModel.php';

class SizeGuide extends BaseModel
{

    public function getAll()
    {
        $sql = "SELECT * FROM size_guides ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM size_guides WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $sql = "INSERT INTO size_guides (name, image_path) VALUES (:name, :image_path)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':image_path', $data['image_path']);
        return $stmt->execute();
    }

    public function update($id, $data)
    {
        $sql = "UPDATE size_guides SET name = :name";

        if (!empty($data['image_path'])) {
            $sql .= ", image_path = :image_path";
        }

        $sql .= " WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $data['name']);

        if (!empty($data['image_path'])) {
            $stmt->bindParam(':image_path', $data['image_path']);
        }

        return $stmt->execute();
    }

    public function delete($id)
    {
        $sql = "DELETE FROM size_guides WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>
