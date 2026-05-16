<?php
/**
 * Category Model
 */
require_once 'models/BaseModel.php';

class Category extends BaseModel
{

    /**
     * Get All Categories (Ordered by Parent then Display Order)
     */
    public function getAll()
    {
        if (!$this->conn instanceof PDO) {
            return [];
        }

        // We fetch basic list. The hierarchical sorting is easier done in PHP
        // or using a specific recursive query. For simplicity with small datasets:
        // adjust hierarchy in code or simple query.
        $sql = "SELECT * FROM categories ORDER BY parent_id ASC, display_order ASC, name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get Main Categories (For Dropdowns)
     */
    public function getMainCategories()
    {
        $sql = "SELECT * FROM categories WHERE parent_id IS NULL ORDER BY display_order ASC, name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get categories sorted by active product count (high -> low)
     */
    public function getAllByProductCountDesc()
    {
        if (!$this->conn instanceof PDO) {
            return [];
        }

        $sql = "SELECT c.*,
                       (
                           SELECT COUNT(DISTINCT p.id)
                           FROM products p
                           WHERE p.is_active = 1
                             AND (
                                 p.category_id = c.id
                                 OR EXISTS (
                                     SELECT 1
                                     FROM product_categories pc
                                     WHERE pc.product_id = p.id
                                       AND pc.category_id = c.id
                                 )
                             )
                       ) AS product_count
                FROM categories c
                ORDER BY product_count DESC, c.name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM categories WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $sql = "INSERT INTO categories (name, slug, image, parent_id, display_order) VALUES (:name, :slug, :image, :parent_id, :display_order)";
        $stmt = $this->conn->prepare($sql);

        $slug = $this->createSlug($data['name']);
        $displayOrder = isset($data['display_order']) ? (int) $data['display_order'] : 0;

        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':image', $data['image']);
        $stmt->bindParam(':parent_id', $data['parent_id']); // Can be NULL
        $stmt->bindParam(':display_order', $displayOrder, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function update($id, $data)
    {
        $sql = "UPDATE categories SET name = :name, slug = :slug, parent_id = :parent_id, display_order = :display_order";

        // Only update image if specific
        if (!empty($data['image'])) {
            $sql .= ", image = :image";
        }

        $sql .= " WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        $slug = $this->createSlug($data['name']);
        $displayOrder = isset($data['display_order']) ? (int) $data['display_order'] : 0;

        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':parent_id', $data['parent_id']);
        $stmt->bindParam(':display_order', $displayOrder, PDO::PARAM_INT);

        if (!empty($data['image'])) {
            $stmt->bindParam(':image', $data['image']);
        }

        return $stmt->execute();
    }

    public function delete($id)
    {
        // Logic: Should we delete subcategories too? CASCADE in DB handles it usually,
        // or setter parent to NULL. 
        // Our DB schema: FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
        // So sub-categories become main categories (orphaned) if parent deleted.

        $sql = "DELETE FROM categories WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Helper to make URL friendly slug
    private function createSlug($string)
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    }
}
?>
