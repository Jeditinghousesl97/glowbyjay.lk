<?php
/**
 * Variation Model
 */
require_once 'models/BaseModel.php';

class Variation extends BaseModel
{

    /**
     * Get All Variations with their Values
     */
    public function getAll()
    {
        // Fetch all attributes
        $sql = "SELECT * FROM variations ORDER BY id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $variations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch values for each (n+1 problem, but fine for small dataset)
        foreach ($variations as &$var) {
            $sqlMsg = "SELECT * FROM variation_values WHERE variation_id = :vid";
            $stmtMsg = $this->conn->prepare($sqlMsg);
            $stmtMsg->bindParam(':vid', $var['id']);
            $stmtMsg->execute();
            $var['values'] = $stmtMsg->fetchAll(PDO::FETCH_ASSOC);
        }

        return $variations;
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM variations WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $variation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($variation) {
            $sqlVal = "SELECT * FROM variation_values WHERE variation_id = :vid";
            $stmtVal = $this->conn->prepare($sqlVal);
            $stmtVal->bindParam(':vid', $id);
            $stmtVal->execute();
            $variation['values'] = $stmtVal->fetchAll(PDO::FETCH_ASSOC);
        }

        return $variation;
    }

    /**
     * Create Variation with Values
     * @param string $name Attribute Name (e.g. Color)
     * @param array $values Array of value strings (e.g. ['Red', 'Blue'])
     */
    public function createWithValues($name, $values)
    {
        try {
            $this->conn->beginTransaction();

            // 1. Insert Attribute
            $sql = "INSERT INTO variations (name) VALUES (:name)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->execute();
            $variationId = $this->conn->lastInsertId();

            // 2. Insert Values
            $sqlVal = "INSERT INTO variation_values (variation_id, value) VALUES (:vid, :val)";
            $stmtVal = $this->conn->prepare($sqlVal);

            foreach ($values as $val) {
                if (!empty(trim($val))) {
                    $stmtVal->bindParam(':vid', $variationId);
                    $stmtVal->bindValue(':val', trim($val));
                    $stmtVal->execute();
                }
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    /**
     * Update Variation Name and Values
     * @param int $id Variation ID
     * @param string $name Attribute Name
     * @param array $values Array of value strings
     */
    public function updateWithValues($id, $name, $values)
    {
        try {
            $this->conn->beginTransaction();

            // 1. Update Attribute Name
            $sql = "UPDATE variations SET name = :name WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // 2. Clear Old Values (Simplest way to handle sync)
            $sqlDel = "DELETE FROM variation_values WHERE variation_id = :vid";
            $stmtDel = $this->conn->prepare($sqlDel);
            $stmtDel->bindParam(':vid', $id);
            $stmtDel->execute();

            // 3. Insert New Values
            $sqlVal = "INSERT INTO variation_values (variation_id, value) VALUES (:vid, :val)";
            $stmtVal = $this->conn->prepare($sqlVal);

            foreach ($values as $val) {
                if (!empty(trim($val))) {
                    $stmtVal->bindParam(':vid', $id);
                    $stmtVal->bindValue(':val', trim($val));
                    $stmtVal->execute();
                }
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // Delete logic if needed
    public function delete($id)
    {
        // Cascade delete should handle values if configured, else manual
        $sql = "DELETE FROM variations WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>