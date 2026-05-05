<?php
require_once 'models/BaseModel.php';

class EmailNotification extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    private function ensureSchema()
    {
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS email_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                event_key VARCHAR(80) NOT NULL,
                recipient_email VARCHAR(190) NOT NULL,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_order_event_recipient (order_id, event_key, recipient_email),
                CONSTRAINT fk_email_notifications_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function wasSent($orderId, $eventKey, $recipientEmail)
    {
        $stmt = $this->conn->prepare("
            SELECT id
            FROM email_notifications
            WHERE order_id = :order_id AND event_key = :event_key AND recipient_email = :recipient_email
            LIMIT 1
        ");
        $stmt->execute([
            ':order_id' => (int) $orderId,
            ':event_key' => $eventKey,
            ':recipient_email' => trim(strtolower((string) $recipientEmail))
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function markSent($orderId, $eventKey, $recipientEmail)
    {
        $stmt = $this->conn->prepare("
            INSERT IGNORE INTO email_notifications (order_id, event_key, recipient_email)
            VALUES (:order_id, :event_key, :recipient_email)
        ");

        return $stmt->execute([
            ':order_id' => (int) $orderId,
            ':event_key' => $eventKey,
            ':recipient_email' => trim(strtolower((string) $recipientEmail))
        ]);
    }
}
