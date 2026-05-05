<?php
require_once 'models/BaseModel.php';
require_once 'helpers/DeliveryHelper.php';

class DeliverySetting extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
        $this->seedDistricts();
    }

    private function ensureSchema()
    {
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS delivery_district_rates (
                district_name VARCHAR(100) NOT NULL PRIMARY KEY,
                first_kg_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                additional_kg_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                sort_order INT NOT NULL DEFAULT 0,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    private function seedDistricts()
    {
        $districts = DeliveryHelper::districtList();
        $stmt = $this->conn->prepare("
            INSERT INTO delivery_district_rates (district_name, sort_order)
            VALUES (:district_name, :sort_order)
            ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order)
        ");

        foreach ($districts as $index => $districtName) {
            $stmt->execute([
                ':district_name' => $districtName,
                ':sort_order' => $index
            ]);
        }
    }

    public function getAllRates()
    {
        $stmt = $this->conn->query("
            SELECT district_name, first_kg_price, additional_kg_price, sort_order
            FROM delivery_district_rates
            ORDER BY sort_order ASC, district_name ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getRatesMap()
    {
        $map = [];
        foreach ($this->getAllRates() as $row) {
            $map[$row['district_name']] = $row;
        }

        return $map;
    }

    public function saveRates(array $rates)
    {
        $districts = DeliveryHelper::districtList();
        $stmt = $this->conn->prepare("
            UPDATE delivery_district_rates
            SET first_kg_price = :first_kg_price,
                additional_kg_price = :additional_kg_price
            WHERE district_name = :district_name
        ");

        foreach ($districts as $districtName) {
            $row = $rates[$districtName] ?? [];
            $stmt->execute([
                ':district_name' => $districtName,
                ':first_kg_price' => number_format((float) ($row['first_kg_price'] ?? 0), 2, '.', ''),
                ':additional_kg_price' => number_format((float) ($row['additional_kg_price'] ?? 0), 2, '.', '')
            ]);
        }
    }
}
?>
