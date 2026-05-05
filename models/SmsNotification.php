<?php
require_once 'models/BaseModel.php';

class SmsNotification extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    private function ensureSchema()
    {
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS sms_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                event_key VARCHAR(80) NOT NULL,
                recipient_phone VARCHAR(40) NOT NULL,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_order_event_phone (order_id, event_key, recipient_phone),
                CONSTRAINT fk_sms_notifications_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function wasSent($orderId, $eventKey, $recipientPhone)
    {
        $stmt = $this->conn->prepare("
            SELECT id
            FROM sms_notifications
            WHERE order_id = :order_id AND event_key = :event_key AND recipient_phone = :recipient_phone
            LIMIT 1
        ");
        $stmt->execute([
            ':order_id' => (int) $orderId,
            ':event_key' => $eventKey,
            ':recipient_phone' => trim((string) $recipientPhone)
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function markSent($orderId, $eventKey, $recipientPhone)
    {
        $stmt = $this->conn->prepare("
            INSERT IGNORE INTO sms_notifications (order_id, event_key, recipient_phone)
            VALUES (:order_id, :event_key, :recipient_phone)
        ");

        return $stmt->execute([
            ':order_id' => (int) $orderId,
            ':event_key' => $eventKey,
            ':recipient_phone' => trim((string) $recipientPhone)
        ]);
    }
}
