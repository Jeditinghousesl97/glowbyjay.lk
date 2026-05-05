<?php
require_once 'models/BaseModel.php';

class Order extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    private function ensureSchema()
    {
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_number VARCHAR(50) NOT NULL UNIQUE,
                customer_name VARCHAR(150) NOT NULL,
                first_name VARCHAR(80) NOT NULL,
                last_name VARCHAR(80) NOT NULL,
                email VARCHAR(150) NOT NULL,
                phone VARCHAR(40) NOT NULL,
                phone_alt VARCHAR(40) DEFAULT NULL,
                address TEXT NOT NULL,
                city VARCHAR(100) NOT NULL,
                district VARCHAR(100) DEFAULT NULL,
                postal_code VARCHAR(40) DEFAULT NULL,
                country VARCHAR(100) NOT NULL DEFAULT 'Sri Lanka',
                note TEXT DEFAULT NULL,
                subtotal_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                handling_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                chargeable_weight_grams INT NOT NULL DEFAULT 0,
                total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                currency VARCHAR(10) NOT NULL DEFAULT 'LKR',
                payment_method VARCHAR(50) NOT NULL DEFAULT 'payhere',
                payment_gateway VARCHAR(50) NOT NULL DEFAULT 'payhere',
                payment_status VARCHAR(40) NOT NULL DEFAULT 'pending',
                order_status VARCHAR(40) NOT NULL DEFAULT 'pending',
                stock_applied TINYINT(1) NOT NULL DEFAULT 0,
                courier_service VARCHAR(150) DEFAULT NULL,
                tracking_number VARCHAR(150) DEFAULT NULL,
                admin_seen_at TIMESTAMP NULL DEFAULT NULL,
                gateway_payment_id VARCHAR(120) DEFAULT NULL,
                gateway_status_code VARCHAR(20) DEFAULT NULL,
                gateway_message TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->ensureColumnExists('orders', 'order_status', "ALTER TABLE orders ADD COLUMN order_status VARCHAR(40) NOT NULL DEFAULT 'pending' AFTER payment_status");
        $this->ensureColumnExists('orders', 'stock_applied', "ALTER TABLE orders ADD COLUMN stock_applied TINYINT(1) NOT NULL DEFAULT 0 AFTER order_status");
        $this->ensureColumnExists('orders', 'courier_service', "ALTER TABLE orders ADD COLUMN courier_service VARCHAR(150) DEFAULT NULL AFTER order_status");
        $this->ensureColumnExists('orders', 'tracking_number', "ALTER TABLE orders ADD COLUMN tracking_number VARCHAR(150) DEFAULT NULL AFTER courier_service");
        $this->ensureColumnExists('orders', 'admin_seen_at', "ALTER TABLE orders ADD COLUMN admin_seen_at TIMESTAMP NULL DEFAULT NULL AFTER tracking_number");
        $this->ensureColumnExists('orders', 'subtotal_amount', "ALTER TABLE orders ADD COLUMN subtotal_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER note");
        $this->ensureColumnExists('orders', 'shipping_fee', "ALTER TABLE orders ADD COLUMN shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER subtotal_amount");
        $this->ensureColumnExists('orders', 'handling_fee', "ALTER TABLE orders ADD COLUMN handling_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER shipping_fee");
        $this->ensureColumnExists('orders', 'chargeable_weight_grams', "ALTER TABLE orders ADD COLUMN chargeable_weight_grams INT NOT NULL DEFAULT 0 AFTER shipping_fee");

        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT DEFAULT NULL,
                product_title VARCHAR(255) NOT NULL,
                variant_text VARCHAR(255) DEFAULT NULL,
                variant_key VARCHAR(255) DEFAULT NULL,
                qty INT NOT NULL DEFAULT 1,
                unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                line_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                image_url TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $this->ensureColumnExists('order_items', 'variant_key', "ALTER TABLE order_items ADD COLUMN variant_key VARCHAR(255) DEFAULT NULL AFTER variant_text");

        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS payment_transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                gateway VARCHAR(50) NOT NULL,
                transaction_type VARCHAR(50) NOT NULL,
                payment_id VARCHAR(120) DEFAULT NULL,
                status_code VARCHAR(20) DEFAULT NULL,
                amount DECIMAL(10,2) DEFAULT NULL,
                currency VARCHAR(10) DEFAULT NULL,
                payload LONGTEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_payment_transactions_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    private function ensureColumnExists($table, $column, $alterSql)
    {
        $stmt = $this->conn->prepare("SHOW COLUMNS FROM `{$table}` LIKE :column");
        $stmt->execute([':column' => $column]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->conn->exec($alterSql);
        }
    }

    public function createFromCart(array $customer, array $cart, array $settings, array $options = [])
    {
        return $this->createFromItems($customer, $cart, $settings, $options);
    }

    public function createFromItems(array $customer, array $items, array $settings, array $options = [])
    {
        if (empty($items)) {
            return false;
        }

        $this->conn->beginTransaction();

        try {
            $orderNumber = $this->generateOrderNumber();
            $currency = trim($settings['currency_symbol'] ?? 'LKR');
            $paymentMethod = trim((string) ($options['payment_method'] ?? 'payhere'));
            $paymentGateway = trim((string) ($options['payment_gateway'] ?? $paymentMethod));
            $paymentStatus = trim((string) ($options['payment_status'] ?? 'pending'));
            $orderStatus = trim((string) ($options['order_status'] ?? 'pending'));
            $transactionType = trim((string) ($options['transaction_type'] ?? 'initiated'));
            $transactionStatusCode = trim((string) ($options['transaction_status_code'] ?? strtoupper($paymentStatus)));
            $transactionPayload = $options['transaction_payload'] ?? [
                'customer' => $customer,
                'items_count' => count($items)
            ];
            $subtotalAmount = isset($options['subtotal_amount']) ? (float) $options['subtotal_amount'] : 0.0;
            $shippingFee = isset($options['shipping_fee']) ? (float) $options['shipping_fee'] : 0.0;
            $handlingFee = isset($options['handling_fee']) ? (float) $options['handling_fee'] : 0.0;
            $chargeableWeightGrams = isset($options['chargeable_weight_grams']) ? (int) $options['chargeable_weight_grams'] : 0;

            if ($subtotalAmount <= 0) {
                foreach ($items as $item) {
                    $qty = max(1, (int) ($item['qty'] ?? 1));
                    $price = (float) ($item['price'] ?? 0);
                    $subtotalAmount += ($price * $qty);
                }
            }

            $totalAmount = $subtotalAmount + $shippingFee + $handlingFee;

            $stmt = $this->conn->prepare("
                INSERT INTO orders (
                    order_number, customer_name, first_name, last_name, email, phone, phone_alt,
                    address, city, district, postal_code, country, note, subtotal_amount, shipping_fee, handling_fee, chargeable_weight_grams, total_amount, currency,
                    payment_method, payment_gateway, payment_status, order_status, stock_applied
                ) VALUES (
                    :order_number, :customer_name, :first_name, :last_name, :email, :phone, :phone_alt,
                    :address, :city, :district, :postal_code, :country, :note, :subtotal_amount, :shipping_fee, :handling_fee, :chargeable_weight_grams, :total_amount, :currency,
                    :payment_method, :payment_gateway, :payment_status, :order_status, :stock_applied
                )
            ");

            $stmt->execute([
                ':order_number' => $orderNumber,
                ':customer_name' => $customer['customer_name'],
                ':first_name' => $customer['first_name'],
                ':last_name' => $customer['last_name'],
                ':email' => $customer['email'],
                ':phone' => $customer['phone'],
                ':phone_alt' => $customer['phone_alt'],
                ':address' => $customer['address'],
                ':city' => $customer['city'],
                ':district' => $customer['district'],
                ':postal_code' => $customer['postal_code'],
                ':country' => $customer['country'],
                ':note' => $customer['note'],
                ':subtotal_amount' => number_format($subtotalAmount, 2, '.', ''),
                ':shipping_fee' => number_format($shippingFee, 2, '.', ''),
                ':handling_fee' => number_format($handlingFee, 2, '.', ''),
                ':chargeable_weight_grams' => $chargeableWeightGrams,
                ':total_amount' => number_format($totalAmount, 2, '.', ''),
                ':currency' => $currency,
                ':payment_method' => $paymentMethod,
                ':payment_gateway' => $paymentGateway,
                ':payment_status' => $paymentStatus,
                ':order_status' => $orderStatus,
                ':stock_applied' => !empty($options['stock_applied']) ? 1 : 0
            ]);

            $orderId = (int) $this->conn->lastInsertId();

            $itemStmt = $this->conn->prepare("
                INSERT INTO order_items (
                    order_id, product_id, product_title, variant_text, variant_key, qty, unit_price, line_total, image_url
                ) VALUES (
                    :order_id, :product_id, :product_title, :variant_text, :variant_key, :qty, :unit_price, :line_total, :image_url
                )
            ");

            foreach ($items as $item) {
                $qty = max(1, (int) ($item['qty'] ?? 1));
                $price = (float) ($item['price'] ?? 0);
                $itemStmt->execute([
                    ':order_id' => $orderId,
                    ':product_id' => !empty($item['id']) ? (int) $item['id'] : null,
                    ':product_title' => $item['title'] ?? 'Product',
                    ':variant_text' => $item['variants'] ?? '',
                    ':variant_key' => trim((string) ($item['variant_key'] ?? '')) ?: null,
                    ':qty' => $qty,
                    ':unit_price' => number_format($price, 2, '.', ''),
                    ':line_total' => number_format($price * $qty, 2, '.', ''),
                    ':image_url' => $item['img'] ?? ''
                ]);
            }

            $this->recordTransaction($orderId, $paymentGateway, $transactionType, null, $transactionStatusCode, $totalAmount, $currency, $transactionPayload);

            $this->conn->commit();
            return $this->getById($orderId);
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    private function generateOrderNumber()
    {
        do {
            $suffix = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $orderNumber = 'ORD-' . date('ymd') . '-' . $suffix;
            $exists = $this->getByOrderNumber($orderNumber);
        } while ($exists);

        return $orderNumber;
    }

    public function getById($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByOrderNumber($orderNumber)
    {
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE order_number = :order_number LIMIT 1");
        $stmt->execute([':order_number' => $orderNumber]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getItems($orderId)
    {
        $stmt = $this->conn->prepare("SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id ASC");
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByOrderNumberWithItems($orderNumber)
    {
        $order = $this->getByOrderNumber($orderNumber);
        if (!$order) {
            return null;
        }

        $order['items'] = $this->getItems((int) $order['id']);
        return $order;
    }

    public function findCustomerOrders($email, $phone, $orderNumber = '')
    {
        $email = trim((string) $email);
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);
        $orderNumber = trim((string) $orderNumber);

        if ($email === '' || $phone === '') {
            return [];
        }

        $sql = "
            SELECT *
            FROM orders
            WHERE email = :email
              AND (
                REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', '') = :phone
                OR REPLACE(REPLACE(REPLACE(COALESCE(phone_alt, ''), ' ', ''), '-', ''), '+', '') = :phone
              )
        ";
        $params = [
            ':email' => $email,
            ':phone' => $phone
        ];

        if ($orderNumber !== '') {
            $sql .= " AND order_number = :order_number";
            $params[':order_number'] = $orderNumber;
        }

        $sql .= " ORDER BY created_at DESC LIMIT 50";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($orders as &$order) {
            $order['items'] = $this->getItems((int) $order['id']);
        }

        return $orders;
    }

    public function countAll()
    {
        return (int) $this->conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    }

    public function getRecent($limit = 20)
    {
        $stmt = $this->conn->prepare("SELECT * FROM orders ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buildFilterParts(array $filters)
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(order_number LIKE :search OR customer_name LIKE :search OR email LIKE :search OR phone LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['payment_status'])) {
            $where[] = "payment_status = :payment_status";
            $params[':payment_status'] = trim($filters['payment_status']);
        }

        if (!empty($filters['payment_method'])) {
            $where[] = "payment_method = :payment_method";
            $params[':payment_method'] = trim($filters['payment_method']);
        }

        if (!empty($filters['order_status'])) {
            $where[] = "order_status = :order_status";
            $params[':order_status'] = trim($filters['order_status']);
        }

        if (!empty($filters['date_from'])) {
            $where[] = "DATE(created_at) >= :date_from";
            $params[':date_from'] = trim($filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $where[] = "DATE(created_at) <= :date_to";
            $params[':date_to'] = trim($filters['date_to']);
        }

        if (!empty($filters['only_new'])) {
            $where[] = "admin_seen_at IS NULL";
        }

        $sqlWhere = !empty($where) ? (' WHERE ' . implode(' AND ', $where)) : '';

        return [$sqlWhere, $params];
    }

    public function getFiltered(array $filters = [], $limit = 100)
    {
        [$sqlWhere, $params] = $this->buildFilterParts($filters);
        $sql = "SELECT * FROM orders" . $sqlWhere . " ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFilteredForExport(array $filters = [])
    {
        [$sqlWhere, $params] = $this->buildFilterParts($filters);
        $sql = "SELECT * FROM orders" . $sqlWhere . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSummaryCounts(array $filters = [])
    {
        [$sqlWhere, $params] = $this->buildFilterParts($filters);

        $sql = "
            SELECT
                COUNT(*) AS total_orders,
                SUM(CASE WHEN admin_seen_at IS NULL THEN 1 ELSE 0 END) AS new_orders,
                SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_orders,
                SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) AS payment_pending_orders,
                SUM(CASE WHEN payment_method = 'cod' THEN 1 ELSE 0 END) AS cod_orders,
                SUM(CASE WHEN payment_method = 'payhere' THEN 1 ELSE 0 END) AS payhere_orders,
                SUM(CASE WHEN payment_method = 'koko' THEN 1 ELSE 0 END) AS koko_orders,
                SUM(CASE WHEN payment_method = 'bank_transfer' THEN 1 ELSE 0 END) AS bank_transfer_orders,
                SUM(CASE WHEN order_status = 'processing' THEN 1 ELSE 0 END) AS processing_orders,
                SUM(CASE WHEN order_status = 'completed' THEN 1 ELSE 0 END) AS completed_orders
            FROM orders
            {$sqlWhere}
        ";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_orders' => (int) ($row['total_orders'] ?? 0),
            'new_orders' => (int) ($row['new_orders'] ?? 0),
            'paid_orders' => (int) ($row['paid_orders'] ?? 0),
            'payment_pending_orders' => (int) ($row['payment_pending_orders'] ?? 0),
            'cod_orders' => (int) ($row['cod_orders'] ?? 0),
            'payhere_orders' => (int) ($row['payhere_orders'] ?? 0),
            'koko_orders' => (int) ($row['koko_orders'] ?? 0),
            'bank_transfer_orders' => (int) ($row['bank_transfer_orders'] ?? 0),
            'processing_orders' => (int) ($row['processing_orders'] ?? 0),
            'completed_orders' => (int) ($row['completed_orders'] ?? 0),
        ];
    }

    public function getFinanceSummary(array $filters = [])
    {
        [$sqlWhere, $params] = $this->buildFilterParts($filters);

        $sql = "
            SELECT
                COALESCE(SUM(total_amount), 0) AS gross_total,
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) AS paid_total,
                COALESCE(SUM(CASE WHEN payment_method = 'cod' AND payment_status = 'pending' AND order_status != 'cancelled' THEN total_amount ELSE 0 END), 0) AS cod_outstanding_total,
                COALESCE(SUM(CASE WHEN order_status = 'completed' THEN total_amount ELSE 0 END), 0) AS completed_total,
                COALESCE(AVG(total_amount), 0) AS avg_order_value,
                SUM(CASE WHEN payment_method = 'cod' AND payment_status = 'pending' AND order_status != 'cancelled' THEN 1 ELSE 0 END) AS cod_outstanding_count
            FROM orders
            {$sqlWhere}
        ";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'gross_total' => (float) ($row['gross_total'] ?? 0),
            'paid_total' => (float) ($row['paid_total'] ?? 0),
            'cod_outstanding_total' => (float) ($row['cod_outstanding_total'] ?? 0),
            'completed_total' => (float) ($row['completed_total'] ?? 0),
            'avg_order_value' => (float) ($row['avg_order_value'] ?? 0),
            'cod_outstanding_count' => (int) ($row['cod_outstanding_count'] ?? 0),
        ];
    }

    public function getReportRows(array $filters = [], $limit = 14)
    {
        [$sqlWhere, $params] = $this->buildFilterParts($filters);

        $sql = "
            SELECT
                DATE(created_at) AS report_date,
                COUNT(*) AS orders_count,
                COALESCE(SUM(total_amount), 0) AS gross_total,
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) AS paid_total,
                COALESCE(SUM(CASE WHEN payment_method = 'cod' THEN total_amount ELSE 0 END), 0) AS cod_total,
                COALESCE(SUM(CASE WHEN payment_method = 'payhere' THEN total_amount ELSE 0 END), 0) AS payhere_total,
                COALESCE(SUM(CASE WHEN payment_method = 'koko' THEN total_amount ELSE 0 END), 0) AS koko_total,
                COALESCE(SUM(CASE WHEN payment_method = 'bank_transfer' THEN total_amount ELSE 0 END), 0) AS bank_transfer_total
            FROM orders
            {$sqlWhere}
            GROUP BY DATE(created_at)
            ORDER BY report_date DESC
            LIMIT :limit
        ";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function updatePaymentStatus($orderNumber, $status, $paymentId = null, $statusCode = null, $message = null)
    {
        $stmt = $this->conn->prepare("
            UPDATE orders
            SET payment_status = :payment_status,
                gateway_payment_id = :gateway_payment_id,
                gateway_status_code = :gateway_status_code,
                gateway_message = :gateway_message
            WHERE order_number = :order_number
        ");

        return $stmt->execute([
            ':payment_status' => $status,
            ':gateway_payment_id' => $paymentId,
            ':gateway_status_code' => $statusCode,
            ':gateway_message' => $message,
            ':order_number' => $orderNumber
        ]);
    }

    public function updateOrderStatus($orderNumber, $status)
    {
        $stmt = $this->conn->prepare("
            UPDATE orders
            SET order_status = :order_status
            WHERE order_number = :order_number
        ");

        return $stmt->execute([
            ':order_status' => $status,
            ':order_number' => $orderNumber
        ]);
    }

    public function updateStockApplied($orderNumber, $applied)
    {
        $stmt = $this->conn->prepare("
            UPDATE orders
            SET stock_applied = :stock_applied
            WHERE order_number = :order_number
        ");

        return $stmt->execute([
            ':stock_applied' => !empty($applied) ? 1 : 0,
            ':order_number' => $orderNumber
        ]);
    }

    public function updateCompletionDetails($orderNumber, $courierService = '', $trackingNumber = '')
    {
        $stmt = $this->conn->prepare("
            UPDATE orders
            SET courier_service = :courier_service,
                tracking_number = :tracking_number
            WHERE order_number = :order_number
        ");

        return $stmt->execute([
            ':courier_service' => trim((string) $courierService) !== '' ? trim((string) $courierService) : null,
            ':tracking_number' => trim((string) $trackingNumber) !== '' ? trim((string) $trackingNumber) : null,
            ':order_number' => $orderNumber
        ]);
    }

    public function markSeen($orderNumber)
    {
        $stmt = $this->conn->prepare("
            UPDATE orders
            SET admin_seen_at = COALESCE(admin_seen_at, NOW())
            WHERE order_number = :order_number
        ");

        return $stmt->execute([
            ':order_number' => $orderNumber
        ]);
    }

    public function deleteByOrderNumber($orderNumber)
    {
        $stmt = $this->conn->prepare("DELETE FROM orders WHERE order_number = :order_number");
        return $stmt->execute([
            ':order_number' => $orderNumber
        ]);
    }

    public function recordTransaction($orderId, $gateway, $type, $paymentId = null, $statusCode = null, $amount = null, $currency = null, $payload = null)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO payment_transactions (
                order_id, gateway, transaction_type, payment_id, status_code, amount, currency, payload
            ) VALUES (
                :order_id, :gateway, :transaction_type, :payment_id, :status_code, :amount, :currency, :payload
            )
        ");

        return $stmt->execute([
            ':order_id' => $orderId,
            ':gateway' => $gateway,
            ':transaction_type' => $type,
            ':payment_id' => $paymentId,
            ':status_code' => $statusCode,
            ':amount' => $amount !== null ? number_format((float) $amount, 2, '.', '') : null,
            ':currency' => $currency,
            ':payload' => $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null
        ]);
    }
}
