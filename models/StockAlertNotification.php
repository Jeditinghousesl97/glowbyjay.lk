<?php
require_once 'models/BaseModel.php';

class StockAlertNotification extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS stock_alert_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                variant_key VARCHAR(255) NOT NULL DEFAULT '',
                alert_type VARCHAR(30) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_stock_alert (product_id, variant_key, alert_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function isActive($productId, $variantKey, $alertType)
    {
        $stmt = $this->conn->prepare("
            SELECT id
            FROM stock_alert_notifications
            WHERE product_id = :product_id
              AND variant_key = :variant_key
              AND alert_type = :alert_type
              AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([
            ':product_id' => (int) $productId,
            ':variant_key' => trim((string) $variantKey),
            ':alert_type' => trim((string) $alertType)
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function activate($productId, $variantKey, $alertType)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO stock_alert_notifications (product_id, variant_key, alert_type, is_active)
            VALUES (:product_id, :variant_key, :alert_type, 1)
            ON DUPLICATE KEY UPDATE is_active = 1, updated_at = CURRENT_TIMESTAMP
        ");
        return $stmt->execute([
            ':product_id' => (int) $productId,
            ':variant_key' => trim((string) $variantKey),
            ':alert_type' => trim((string) $alertType)
        ]);
    }

    public function resolve($productId, $variantKey, $alertType = null)
    {
        $sql = "
            UPDATE stock_alert_notifications
            SET is_active = 0, updated_at = CURRENT_TIMESTAMP
            WHERE product_id = :product_id
              AND variant_key = :variant_key
        ";
        $params = [
            ':product_id' => (int) $productId,
            ':variant_key' => trim((string) $variantKey)
        ];

        if ($alertType !== null) {
            $sql .= " AND alert_type = :alert_type";
            $params[':alert_type'] = trim((string) $alertType);
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }
}
