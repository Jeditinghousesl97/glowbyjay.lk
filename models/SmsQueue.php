<?php
require_once 'models/BaseModel.php';

class SmsQueue extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    private function ensureSchema()
    {
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS sms_queue (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                event_key VARCHAR(80) NOT NULL,
                recipient_type VARCHAR(30) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                attempts INT NOT NULL DEFAULT 0,
                last_error TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                processed_at TIMESTAMP NULL DEFAULT NULL,
                UNIQUE KEY uniq_order_event_recipient_type (order_id, event_key, recipient_type),
                KEY idx_sms_queue_status_created (status, created_at),
                CONSTRAINT fk_sms_queue_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function enqueue($orderId, $eventKey, $recipientType)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO sms_queue (order_id, event_key, recipient_type, status, attempts)
            VALUES (:order_id, :event_key, :recipient_type, 'pending', 0)
            ON DUPLICATE KEY UPDATE
                status = IF(status = 'sent', status, 'pending'),
                last_error = NULL
        ");

        return $stmt->execute([
            ':order_id' => (int) $orderId,
            ':event_key' => $eventKey,
            ':recipient_type' => $recipientType
        ]);
    }

    public function claimNextBatch($limit = 10)
    {
        $limit = max(1, (int) $limit);
        $select = $this->conn->prepare("
            SELECT id
            FROM sms_queue
            WHERE status IN ('pending', 'failed')
            ORDER BY created_at ASC
            LIMIT {$limit}
        ");
        $select->execute();
        $ids = $select->fetchAll(PDO::FETCH_COLUMN);

        if (empty($ids)) {
            return [];
        }

        $in = implode(',', array_map('intval', $ids));
        $this->conn->exec("
            UPDATE sms_queue
            SET status = 'processing', attempts = attempts + 1, last_error = NULL
            WHERE id IN ({$in})
        ");

        $stmt = $this->conn->query("
            SELECT *
            FROM sms_queue
            WHERE id IN ({$in})
            ORDER BY created_at ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markSent($id)
    {
        $stmt = $this->conn->prepare("
            UPDATE sms_queue
            SET status = 'sent', processed_at = NOW(), last_error = NULL
            WHERE id = :id
        ");

        return $stmt->execute([':id' => (int) $id]);
    }

    public function markFailed($id, $message)
    {
        $stmt = $this->conn->prepare("
            UPDATE sms_queue
            SET status = 'failed', last_error = :message
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => (int) $id,
            ':message' => substr((string) $message, 0, 1000)
        ]);
    }
}
