<?php
/**
 * Product Model
 */
require_once 'models/BaseModel.php';

class Product extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        if ($this->conn instanceof PDO) {
            $this->ensureSchema();
        }
    }

    private function ensureSchema()
    {
        $this->ensureColumnExists('products', 'weight_grams', "ALTER TABLE products ADD COLUMN weight_grams INT NOT NULL DEFAULT 0 AFTER sale_price");
        $this->ensureColumnExists('products', 'free_shipping', "ALTER TABLE products ADD COLUMN free_shipping TINYINT(1) NOT NULL DEFAULT 0 AFTER weight_grams");
        $this->ensureColumnExists('products', 'stock_mode', "ALTER TABLE products ADD COLUMN stock_mode VARCHAR(30) NOT NULL DEFAULT 'always_in_stock' AFTER free_shipping");
        $this->ensureColumnExists('products', 'stock_qty', "ALTER TABLE products ADD COLUMN stock_qty INT NOT NULL DEFAULT 0 AFTER stock_mode");
        $this->ensureColumnExists('products', 'low_stock_threshold', "ALTER TABLE products ADD COLUMN low_stock_threshold INT NOT NULL DEFAULT 5 AFTER stock_qty");
        $this->ensureColumnExists('products', 'manual_stock_status', "ALTER TABLE products ADD COLUMN manual_stock_status VARCHAR(20) NOT NULL DEFAULT 'in_stock' AFTER low_stock_threshold");
        $this->ensureColumnExists('products', 'short_description', "ALTER TABLE products ADD COLUMN short_description TEXT DEFAULT NULL AFTER description");
        $this->ensureVariantStockTables();
    }

    private function ensureColumnExists($table, $column, $alterSql)
    {
        $stmt = $this->conn->prepare("SHOW COLUMNS FROM `{$table}` LIKE :column");
        $stmt->execute([':column' => $column]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->conn->exec($alterSql);
        }
    }

    private function ensureTableExists($table, $createSql)
    {
        $stmt = $this->conn->prepare("SHOW TABLES LIKE :table_name");
        $stmt->execute([':table_name' => $table]);
        if (!$stmt->fetchColumn()) {
            $this->conn->exec($createSql);
        }
    }

    private function ensureVariantStockTables()
    {
        $this->ensureTableExists('product_variant_stock', "CREATE TABLE product_variant_stock (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            combination_key VARCHAR(255) NOT NULL,
            combination_label VARCHAR(255) NOT NULL,
            sku VARCHAR(120) DEFAULT NULL,
            variant_price DECIMAL(10,2) DEFAULT NULL,
            variant_sale_price DECIMAL(10,2) DEFAULT NULL,
            variant_weight_grams INT NOT NULL DEFAULT 0,
            image_path VARCHAR(255) DEFAULT NULL,
            stock_mode VARCHAR(30) NOT NULL DEFAULT 'track_stock',
            stock_qty INT NOT NULL DEFAULT 0,
            low_stock_threshold INT NOT NULL DEFAULT 5,
            manual_stock_status VARCHAR(20) NOT NULL DEFAULT 'in_stock',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_product_combination (product_id, combination_key),
            KEY idx_variant_stock_product (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->ensureTableExists('product_variant_stock_values', "CREATE TABLE product_variant_stock_values (
            id INT AUTO_INCREMENT PRIMARY KEY,
            variant_stock_id INT NOT NULL,
            variation_id INT NOT NULL,
            variation_value_id INT NOT NULL,
            KEY idx_variant_stock_values_variant (variant_stock_id),
            KEY idx_variant_stock_values_variation (variation_id, variation_value_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->ensureColumnExists('product_variant_stock', 'variant_price', "ALTER TABLE product_variant_stock ADD COLUMN variant_price DECIMAL(10,2) DEFAULT NULL AFTER sku");
        $this->ensureColumnExists('product_variant_stock', 'variant_sale_price', "ALTER TABLE product_variant_stock ADD COLUMN variant_sale_price DECIMAL(10,2) DEFAULT NULL AFTER variant_price");
        $this->ensureColumnExists('product_variant_stock', 'variant_weight_grams', "ALTER TABLE product_variant_stock ADD COLUMN variant_weight_grams INT NOT NULL DEFAULT 0 AFTER variant_price");
        $this->ensureColumnExists('product_variant_stock', 'image_path', "ALTER TABLE product_variant_stock ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER variant_weight_grams");
    }

    private function normalizeStockMode($mode)
    {
        $mode = trim((string) $mode);
        return in_array($mode, ['always_in_stock', 'track_stock', 'manual_out_of_stock'], true)
            ? $mode
            : 'always_in_stock';
    }

    private function normalizeManualStockStatus($status)
    {
        $status = trim((string) $status);
        return $status === 'out_of_stock' ? 'out_of_stock' : 'in_stock';
    }

    private function normalizeVariantCombinationKey(array $rows)
    {
        usort($rows, function ($a, $b) {
            return ((int) ($a['variation_id'] ?? 0)) <=> ((int) ($b['variation_id'] ?? 0));
        });

        $parts = [];
        foreach ($rows as $row) {
            $parts[] = ((int) ($row['variation_id'] ?? 0)) . ':' . ((int) ($row['variation_value_id'] ?? 0));
        }
        return implode('|', $parts);
    }

    private function saveVariantStocks($productId, array $variantStocks)
    {
        $deleteValues = $this->conn->prepare("DELETE psv FROM product_variant_stock_values psv
            INNER JOIN product_variant_stock pvs ON psv.variant_stock_id = pvs.id
            WHERE pvs.product_id = :product_id");
        $deleteValues->execute([':product_id' => $productId]);

        $deleteStocks = $this->conn->prepare("DELETE FROM product_variant_stock WHERE product_id = :product_id");
        $deleteStocks->execute([':product_id' => $productId]);

        if (empty($variantStocks)) {
            return;
        }

        $insertStock = $this->conn->prepare("INSERT INTO product_variant_stock
            (product_id, combination_key, combination_label, sku, variant_price, variant_sale_price, variant_weight_grams, image_path, stock_mode, stock_qty, low_stock_threshold, manual_stock_status, is_active)
            VALUES
            (:product_id, :combination_key, :combination_label, :sku, :variant_price, :variant_sale_price, :variant_weight_grams, :image_path, :stock_mode, :stock_qty, :low_stock_threshold, :manual_stock_status, :is_active)");
        $insertValue = $this->conn->prepare("INSERT INTO product_variant_stock_values
            (variant_stock_id, variation_id, variation_value_id)
            VALUES
            (:variant_stock_id, :variation_id, :variation_value_id)");

        foreach ($variantStocks as $variantStock) {
            $values = $variantStock['values'] ?? [];
            if (empty($values) || !is_array($values)) {
                continue;
            }

            $normalizedValues = [];
            foreach ($values as $valueRow) {
                $variationId = (int) ($valueRow['variation_id'] ?? 0);
                $variationValueId = (int) ($valueRow['variation_value_id'] ?? 0);
                if ($variationId <= 0 || $variationValueId <= 0) {
                    continue;
                }
                $normalizedValues[] = [
                    'variation_id' => $variationId,
                    'variation_value_id' => $variationValueId
                ];
            }

            if (empty($normalizedValues)) {
                continue;
            }

            $combinationKey = $this->normalizeVariantCombinationKey($normalizedValues);
            $insertStock->execute([
                ':product_id' => $productId,
                ':combination_key' => $combinationKey,
                ':combination_label' => trim((string) ($variantStock['combination_label'] ?? $combinationKey)),
                ':sku' => trim((string) ($variantStock['sku'] ?? '')) ?: null,
                ':variant_price' => $this->normalizeVariantPrice($variantStock['variant_price'] ?? null),
                ':variant_sale_price' => $this->normalizeVariantSalePrice(
                    $variantStock['variant_sale_price'] ?? null,
                    $variantStock['variant_price'] ?? null
                ),
                ':variant_weight_grams' => max(0, (int) ($variantStock['variant_weight_grams'] ?? 0)),
                ':image_path' => trim((string) ($variantStock['image_path'] ?? '')) ?: null,
                ':stock_mode' => $this->normalizeStockMode($variantStock['stock_mode'] ?? 'track_stock'),
                ':stock_qty' => max(0, (int) ($variantStock['stock_qty'] ?? 0)),
                ':low_stock_threshold' => max(0, (int) ($variantStock['low_stock_threshold'] ?? 5)),
                ':manual_stock_status' => $this->normalizeManualStockStatus($variantStock['manual_stock_status'] ?? 'in_stock'),
                ':is_active' => !empty($variantStock['is_active']) ? 1 : 0
            ]);

            $variantStockId = (int) $this->conn->lastInsertId();
            foreach ($normalizedValues as $valueRow) {
                $insertValue->execute([
                    ':variant_stock_id' => $variantStockId,
                    ':variation_id' => $valueRow['variation_id'],
                    ':variation_value_id' => $valueRow['variation_value_id']
                ]);
            }
        }
    }

    private function normalizeVariantPrice($price)
    {
        if ($price === null || $price === '') {
            return null;
        }

        return max(0, (float) $price);
    }

    private function normalizeVariantSalePrice($salePrice, $regularPrice)
    {
        if ($salePrice === null || $salePrice === '') {
            return null;
        }

        $salePrice = max(0, (float) $salePrice);
        $regularPrice = $this->normalizeVariantPrice($regularPrice);
        if ($regularPrice !== null && ($salePrice <= 0 || $salePrice >= $regularPrice)) {
            return null;
        }

        return $salePrice;
    }

    /**
     * For listing pages: if base product has no discount but at least one active variant has
     * a valid sale (< variant price), expose that variant discount via product price fields.
     */
    private function applyVariantDiscountPricing(array $products)
    {
        if (empty($products)) {
            return $products;
        }

        $productIds = array_values(array_unique(array_filter(array_map(function ($product) {
            return (int) ($product['id'] ?? 0);
        }, $products))));

        if (empty($productIds)) {
            return $products;
        }

        $placeholders = [];
        $params = [];
        foreach ($productIds as $index => $productId) {
            $key = ':pid_' . $index;
            $placeholders[] = $key;
            $params[$key] = $productId;
        }

        $sql = "SELECT
                    product_id,
                    MIN(CASE
                        WHEN is_active = 1
                         AND variant_price IS NOT NULL
                         AND variant_sale_price IS NOT NULL
                         AND variant_sale_price > 0
                         AND variant_sale_price < variant_price
                        THEN variant_sale_price
                        ELSE NULL
                    END) AS min_variant_sale_price,
                    MIN(CASE
                        WHEN is_active = 1
                         AND variant_price IS NOT NULL
                         AND variant_sale_price IS NOT NULL
                         AND variant_sale_price > 0
                         AND variant_sale_price < variant_price
                        THEN variant_price
                        ELSE NULL
                    END) AS min_variant_regular_price
                FROM product_variant_stock
                WHERE product_id IN (" . implode(', ', $placeholders) . ")
                GROUP BY product_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $variantDiscountMap = [];
        foreach ($rows as $row) {
            $pid = (int) ($row['product_id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $sale = isset($row['min_variant_sale_price']) ? (float) $row['min_variant_sale_price'] : 0.0;
            $regular = isset($row['min_variant_regular_price']) ? (float) $row['min_variant_regular_price'] : 0.0;
            if ($sale > 0 && $regular > 0 && $sale < $regular) {
                $variantDiscountMap[$pid] = [
                    'sale_price' => $sale,
                    'regular_price' => $regular
                ];
            }
        }

        foreach ($products as &$product) {
            $pid = (int) ($product['id'] ?? 0);
            if ($pid <= 0 || !isset($variantDiscountMap[$pid])) {
                continue;
            }

            $baseRegular = isset($product['price']) ? (float) $product['price'] : 0.0;
            $baseSale = isset($product['sale_price']) ? (float) $product['sale_price'] : 0.0;
            $baseHasDiscount = $baseRegular > 0 && $baseSale > 0 && $baseSale < $baseRegular;
            if ($baseHasDiscount) {
                continue;
            }

            $product['price'] = $variantDiscountMap[$pid]['regular_price'];
            $product['sale_price'] = $variantDiscountMap[$pid]['sale_price'];
        }
        unset($product);

        return $products;
    }

    public function getAll($search = null)
    {
        if (!$this->conn instanceof PDO) {
            return [];
        }

        // specific query to join categories and parent categories
        $sql = "SELECT p.*, c.name as category_name, pc.name as parent_category_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN categories pc ON c.parent_id = pc.id";

        if ($search) {
            $sql .= " WHERE p.title LIKE :search OR p.sku LIKE :search";
        }

        $sql .= " ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($sql);

        if ($search) {
            $term = "%$search%";
            $stmt->bindParam(':search', $term);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        try {
            $this->conn->beginTransaction();

            // 1. Insert Core Product
            $sql = "INSERT INTO products (
                title, slug, sku, price, sale_price, weight_grams, free_shipping, stock_mode, stock_qty, low_stock_threshold, manual_stock_status, description, short_description, 
                main_image, is_featured, category_id, size_guide_id
            ) VALUES (
                :title, :slug, :sku, :price, :sale_price, :weight_grams, :free_shipping, :stock_mode, :stock_qty, :low_stock_threshold, :manual_stock_status, :description, :short_description, 
                :main_image, :is_featured, :category_id, :size_guide_id
            )";

            $stmt = $this->conn->prepare($sql);

            $slug = $this->createSlug($data['title']);
            // Avoid duplicate slug collision by appending timestamp if needed, 
            // but for now simple slug.

            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':slug', $slug);
            $stmt->bindParam(':sku', $data['sku']);
            $stmt->bindParam(':price', $data['price']);

            // Handle optional fields
            $salePrice = !empty($data['sale_price']) ? $data['sale_price'] : null;
            $stmt->bindParam(':sale_price', $salePrice);
            $weightGrams = max(0, (int) ($data['weight_grams'] ?? 0));
            $stmt->bindParam(':weight_grams', $weightGrams);
            $freeShipping = !empty($data['free_shipping']) ? 1 : 0;
            $stmt->bindParam(':free_shipping', $freeShipping);
            $stockMode = $this->normalizeStockMode($data['stock_mode'] ?? 'always_in_stock');
            $stockQty = max(0, (int) ($data['stock_qty'] ?? 0));
            $lowStockThreshold = max(0, (int) ($data['low_stock_threshold'] ?? 5));
            $manualStockStatus = $this->normalizeManualStockStatus($data['manual_stock_status'] ?? 'in_stock');
            $stmt->bindParam(':stock_mode', $stockMode);
            $stmt->bindParam(':stock_qty', $stockQty);
            $stmt->bindParam(':low_stock_threshold', $lowStockThreshold);
            $stmt->bindParam(':manual_stock_status', $manualStockStatus);

            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':short_description', $data['short_description']);
            $stmt->bindParam(':main_image', $data['main_image']);

            // Fix: isset check is always true for boolean false. Use !empty or direct cast.
            $isFeatured = !empty($data['is_featured']) ? 1 : 0;
            $stmt->bindParam(':is_featured', $isFeatured);

            $stmt->bindParam(':category_id', $data['category_id']);

            $sizeGuideId = !empty($data['size_guide_id']) ? $data['size_guide_id'] : null;
            $stmt->bindParam(':size_guide_id', $sizeGuideId);

            $stmt->execute();
            $productId = $this->conn->lastInsertId();

            // 2. Insert Gallery Images
            if (!empty($data['gallery_images'])) {
                $sqlImg = "INSERT INTO product_images (product_id, image_path) VALUES (:pid, :path)";
                $stmtImg = $this->conn->prepare($sqlImg);

                foreach ($data['gallery_images'] as $path) {
                    $stmtImg->bindParam(':pid', $productId);
                    $stmtImg->bindParam(':path', $path);
                    $stmtImg->execute();
                }
            }

            // 3. Insert Variation Links
            // Expecting data['variations'] to be array of [variation_id, variation_value_id]
            if (!empty($data['variations'])) {
                $sqlVar = "INSERT INTO product_variations (product_id, variation_id, variation_value_id) VALUES (:pid, :vid, :vvid)";
                $stmtVar = $this->conn->prepare($sqlVar);

                foreach ($data['variations'] as $var) {
                    $stmtVar->bindParam(':pid', $productId);
                    $stmtVar->bindParam(':vid', $var['variation_id']);
                    $stmtVar->bindParam(':vvid', $var['variation_value_id']);
                                        $stmtVar->execute();
                }
            }

            // 4. Insert Multi-Categories
            if (!empty($data['categories']) && is_array($data['categories'])) {
                $sqlCat = "INSERT INTO product_categories (product_id, category_id) VALUES (:pid, :cid)";
                $stmtCat = $this->conn->prepare($sqlCat);

                foreach ($data['categories'] as $catId) {
                    $stmtCat->bindParam(':pid', $productId);
                    $stmtCat->bindParam(':cid', $catId);
                    $stmtCat->execute();
                }
            }

            if (isset($data['variant_stocks']) && is_array($data['variant_stocks'])) {
                $this->saveVariantStocks($productId, $data['variant_stocks']);
            }

            $this->conn->commit();

            return $productId;

        } catch (Exception $e) {
            $this->conn->rollBack();
            // Log error in production
            return false;
        }
    }

    public function update($data)
    {
        try {
            $this->conn->beginTransaction();

            // 1. Update Core Product
            $sql = "UPDATE products SET 
                    title = :title, 
                    slug = :slug, 
                    sku = :sku, 
                    price = :price, 
                    sale_price = :sale_price, 
                    weight_grams = :weight_grams,
                    free_shipping = :free_shipping,
                    stock_mode = :stock_mode,
                    stock_qty = :stock_qty,
                    low_stock_threshold = :low_stock_threshold,
                    manual_stock_status = :manual_stock_status,
                    description = :description, 
                    short_description = :short_description,
                    is_featured = :is_featured, 
                    category_id = :category_id, 
                    size_guide_id = :size_guide_id
                    WHERE id = :id";

            // Only update main_image if a new one is provided or we strictly want to
            if (!empty($data['main_image'])) {
                $sql = "UPDATE products SET 
                        title = :title, 
                        slug = :slug, 
                        sku = :sku, 
                        price = :price, 
                        sale_price = :sale_price, 
                        weight_grams = :weight_grams,
                        free_shipping = :free_shipping,
                        stock_mode = :stock_mode,
                        stock_qty = :stock_qty,
                        low_stock_threshold = :low_stock_threshold,
                        manual_stock_status = :manual_stock_status,
                        description = :description, 
                        short_description = :short_description,
                        main_image = :main_image,
                        is_featured = :is_featured, 
                        category_id = :category_id, 
                        size_guide_id = :size_guide_id
                        WHERE id = :id";
            }

            $stmt = $this->conn->prepare($sql);

            $slug = $this->createSlug($data['title']);

            $stmt->bindParam(':id', $data['id']);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':slug', $slug);
            $stmt->bindParam(':sku', $data['sku']);
            $stmt->bindParam(':price', $data['price']);

            $salePrice = !empty($data['sale_price']) ? $data['sale_price'] : null;
            $stmt->bindParam(':sale_price', $salePrice);
            $weightGrams = max(0, (int) ($data['weight_grams'] ?? 0));
            $stmt->bindParam(':weight_grams', $weightGrams);
            $freeShipping = !empty($data['free_shipping']) ? 1 : 0;
            $stmt->bindParam(':free_shipping', $freeShipping);
            $stockMode = $this->normalizeStockMode($data['stock_mode'] ?? 'always_in_stock');
            $stockQty = max(0, (int) ($data['stock_qty'] ?? 0));
            $lowStockThreshold = max(0, (int) ($data['low_stock_threshold'] ?? 5));
            $manualStockStatus = $this->normalizeManualStockStatus($data['manual_stock_status'] ?? 'in_stock');
            $stmt->bindParam(':stock_mode', $stockMode);
            $stmt->bindParam(':stock_qty', $stockQty);
            $stmt->bindParam(':low_stock_threshold', $lowStockThreshold);
            $stmt->bindParam(':manual_stock_status', $manualStockStatus);

            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':short_description', $data['short_description']);

            if (!empty($data['main_image'])) {
                $stmt->bindParam(':main_image', $data['main_image']);
            }

            $isFeatured = !empty($data['is_featured']) ? 1 : 0;
            $stmt->bindParam(':is_featured', $isFeatured);

            $stmt->bindParam(':category_id', $data['category_id']);

            $sizeGuideId = !empty($data['size_guide_id']) ? $data['size_guide_id'] : null;
            $stmt->bindParam(':size_guide_id', $sizeGuideId);

            $stmt->execute();

            // Check if any row was actually updated
            // Note: If values are identical to existing, MySQL might return 0 affected rows depending on flags.
            // But usually for an ID based update, if ID exists, it returns 1 or 0.
            // If ID matches nothing, it returns 0.
            // We want to ensure we don't return false if data was just identical (silent success), 
            // but we MUST fail if ID was wrong.
            // However, user specifically asked: "return true only when the update is successful and at least one row is affected."
            // So we will enforce rowCount > 0 condition for strictness.

            $mainUpdateSuccess = $stmt->rowCount() > 0;

            // 2. Append New Gallery Images
            if (!empty($data['new_gallery_images'])) {
                $sqlImg = "INSERT INTO product_images (product_id, image_path) VALUES (:pid, :path)";
                $stmtImg = $this->conn->prepare($sqlImg);

                foreach ($data['new_gallery_images'] as $path) {
                    $stmtImg->bindParam(':pid', $data['id']);
                    $stmtImg->bindParam(':path', $path);
                    $stmtImg->execute();
                    $mainUpdateSuccess = true; // Consider success if we added images
                }
            }

            if (!empty($data['remove_gallery_image_ids']) && is_array($data['remove_gallery_image_ids'])) {
                $removeIds = array_values(array_filter(array_map('intval', $data['remove_gallery_image_ids'])));
                if (!empty($removeIds)) {
                    $placeholders = [];
                    $params = [':pid' => (int) $data['id']];
                    foreach ($removeIds as $index => $removeId) {
                        $key = ':remove_' . $index;
                        $placeholders[] = $key;
                        $params[$key] = $removeId;
                    }

                    $sqlDeleteImages = "DELETE FROM product_images
                        WHERE product_id = :pid AND id IN (" . implode(', ', $placeholders) . ")";
                    $stmtDeleteImages = $this->conn->prepare($sqlDeleteImages);
                    $stmtDeleteImages->execute($params);

                    if ($stmtDeleteImages->rowCount() > 0) {
                        $mainUpdateSuccess = true;
                    }
                }
            }

            // 3. Update Variations
            if (isset($data['variations'])) {
                // Delete existing
                $sqlDel = "DELETE FROM product_variations WHERE product_id = :pid";
                $stmtDel = $this->conn->prepare($sqlDel);
                $stmtDel->bindParam(':pid', $data['id']);
                $stmtDel->execute();

                // If we deleted vars, that's a change too
                if ($stmtDel->rowCount() > 0) {
                    $mainUpdateSuccess = true;
                }

                if (!empty($data['variations'])) {
                    $sqlVar = "INSERT INTO product_variations (product_id, variation_id, variation_value_id) VALUES (:pid, :vid, :vvid)";
                    $stmtVar = $this->conn->prepare($sqlVar);

                    foreach ($data['variations'] as $var) {
                        $stmtVar->bindParam(':pid', $data['id']);
                        $stmtVar->bindParam(':vid', $var['variation_id']);
                        $stmtVar->bindParam(':vvid', $var['variation_value_id']);
                        $stmtVar->execute();
                        $mainUpdateSuccess = true; // Consider success if we added vars
                    }
                }
                        }

            // 4. Update Multi-Categories
            if (isset($data['categories'])) { // Only if sent
                // Delete existing
                $sqlDel = "DELETE FROM product_categories WHERE product_id = :pid";
                $stmtDel = $this->conn->prepare($sqlDel);
                $stmtDel->bindParam(':pid', $data['id']);
                $stmtDel->execute();
                
                if ($stmtDel->rowCount() > 0) {
                     $mainUpdateSuccess = true;
                }

                // Insert new
                if (!empty($data['categories']) && is_array($data['categories'])) {
                    $sqlCat = "INSERT INTO product_categories (product_id, category_id) VALUES (:pid, :cid)";
                    $stmtCat = $this->conn->prepare($sqlCat);

                    foreach ($data['categories'] as $catId) {
                        $stmtCat->bindParam(':pid', $data['id']);
                        $stmtCat->bindParam(':cid', $catId);
                        $stmtCat->execute();
                        $mainUpdateSuccess = true;
                    }
                }
            }

            if (isset($data['variant_stocks']) && is_array($data['variant_stocks'])) {
                $this->saveVariantStocks((int) $data['id'], $data['variant_stocks']);
                if (!empty($data['variant_stocks'])) {
                    $mainUpdateSuccess = true;
                }
            }

            $this->conn->commit();

            // Return true if at least something changed (Main Product, Images, or Variations)
            return $mainUpdateSuccess;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function delete($id)
    {
        $sql = "DELETE FROM products WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function deleteAll()
    {
        $sql = "DELETE FROM products";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute();
    }

    private function createSlug($string)
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    }

    /**
     * Get Featured Products
     */
    public function getFeatured($limit = 6)
    {
        $sql = "SELECT p.*, c.name as category_name, pc.name as parent_category_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN categories pc ON c.parent_id = pc.id
                WHERE p.is_featured = 1 AND p.is_active = 1
                ORDER BY p.created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $this->applyVariantDiscountPricing($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Get Latest Products
     */
    public function getLatest($limit = 6)
    {
        $sql = "SELECT p.*, c.name as category_name, pc.name as parent_category_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN categories pc ON c.parent_id = pc.id
                WHERE p.is_active = 1
                ORDER BY p.created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $this->applyVariantDiscountPricing($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Get On Sale Products
     */
    public function getOnSale($limit = 6)
    {
        $sql = "SELECT p.*, c.name as category_name, pc.name as parent_category_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN categories pc ON c.parent_id = pc.id
                WHERE p.is_active = 1
                  AND (
                    (p.sale_price IS NOT NULL AND p.sale_price > 0 AND p.sale_price < p.price)
                    OR EXISTS (
                        SELECT 1
                        FROM product_variant_stock pvs
                        WHERE pvs.product_id = p.id
                          AND pvs.is_active = 1
                          AND pvs.variant_sale_price IS NOT NULL
                          AND pvs.variant_sale_price > 0
                          AND pvs.variant_price IS NOT NULL
                          AND pvs.variant_sale_price < pvs.variant_price
                    )
                  )
                ORDER BY p.created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $this->applyVariantDiscountPricing($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Get All On Sale Products (For Discounts Page)
     */
    public function getAllOnSale()
    {
        $sql = "SELECT p.*, c.name as category_name, pc.name as parent_category_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN categories pc ON c.parent_id = pc.id
                WHERE p.is_active = 1
                  AND (
                    (p.sale_price IS NOT NULL AND p.sale_price > 0 AND p.sale_price < p.price)
                    OR EXISTS (
                        SELECT 1
                        FROM product_variant_stock pvs
                        WHERE pvs.product_id = p.id
                          AND pvs.is_active = 1
                          AND pvs.variant_sale_price IS NOT NULL
                          AND pvs.variant_sale_price > 0
                          AND pvs.variant_price IS NOT NULL
                          AND pvs.variant_sale_price < pvs.variant_price
                    )
                  )
                ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $this->applyVariantDiscountPricing($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    /**
     * Get All Featured Products (No Limit)
     */
    public function getAllFeatured()
    {
        $sql = "SELECT p.*, c.name as category_name, pc.name as parent_category_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN categories pc ON c.parent_id = pc.id
                WHERE p.is_featured = 1 AND p.is_active = 1
                ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $this->applyVariantDiscountPricing($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getFreeShippingProducts($limit = null)
    {
        $sql = "SELECT p.*, c.name as category_name, pc.name as parent_category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN categories pc ON c.parent_id = pc.id
                WHERE p.free_shipping = 1 AND p.is_active = 1
                ORDER BY p.created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT :limit";
        }

        $stmt = $this->conn->prepare($sql);
        if ($limit !== null) {
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Get Single Product by ID
     */
    public function getById($id)
    {
        $sql = "SELECT p.*, c.name as category_name, pc.name as parent_category_name, sg.image_path as size_guide_image
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN categories pc ON c.parent_id = pc.id
                LEFT JOIN size_guides sg ON p.size_guide_id = sg.id
                WHERE p.id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get Gallery Images
     */
    public function getGalleryImages($productId)
    {
        $sql = "SELECT image_path FROM product_images WHERE product_id = :pid";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':pid', $productId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN); // Returns array of strings
    }

    public function getGalleryImageRecords($productId)
    {
        $sql = "SELECT id, image_path FROM product_images WHERE product_id = :pid ORDER BY id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':pid', $productId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGalleryImageRecordsByIds($productId, array $imageIds)
    {
        $imageIds = array_values(array_filter(array_map('intval', $imageIds)));
        if (empty($imageIds)) {
            return [];
        }

        $placeholders = [];
        $params = [':pid' => (int) $productId];
        foreach ($imageIds as $index => $imageId) {
            $key = ':img' . $index;
            $placeholders[] = $key;
            $params[$key] = $imageId;
        }

        $sql = "SELECT id, image_path FROM product_images
                WHERE product_id = :pid AND id IN (" . implode(', ', $placeholders) . ")";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $this->applyVariantDiscountPricing($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Get Product Categories (IDs)
     */
    public function getProductCategoryIds($productId)
    {
        $sql = "SELECT category_id FROM product_categories WHERE product_id = :pid";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':pid', $productId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }


    /**
     * Get Product Variations
     * Grouped by Variation Name (e.g. Color => [ {val_id, val_name}, ... ])
     */
    public function getVariations($productId)
    {
        // Join product_variations -> variations, variation_values
        $sql = "SELECT v.id as variation_id, v.name as var_name, vv.id as val_id, vv.value as val_name, vv.color_hex
                FROM product_variations pv
                JOIN variations v ON pv.variation_id = v.id
                JOIN variation_values vv ON pv.variation_value_id = vv.id
                WHERE pv.product_id = :pid
                ORDER BY v.id, vv.id"; // Order ensures grouping works easily

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':pid', $productId);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Grouping logic
        $grouped = [];
        foreach ($rows as $row) {
            $name = $row['var_name']; // e.g. "Color" or "Size"
            if (!isset($grouped[$name])) {
                $grouped[$name] = [];
            }
            $grouped[$name][] = [
                'variation_id' => (int) $row['variation_id'],
                'id' => $row['val_id'],
                'value' => $row['val_name'],
                'hex' => $row['color_hex']
            ];
        }
        return $grouped;
    }

    public function productRequiresVariationSelection($productId)
    {
        return !empty($this->getVariations($productId));
    }

    public function getVariantStockRows($productId)
    {
        $sql = "SELECT pvs.*, pvsv.variation_id, pvsv.variation_value_id, v.name AS variation_name, vv.value AS variation_value
                FROM product_variant_stock pvs
                LEFT JOIN product_variant_stock_values pvsv ON pvs.id = pvsv.variant_stock_id
                LEFT JOIN variations v ON pvsv.variation_id = v.id
                LEFT JOIN variation_values vv ON pvsv.variation_value_id = vv.id
                WHERE pvs.product_id = :product_id
                ORDER BY pvs.id ASC, pvsv.variation_id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':product_id' => $productId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $row) {
            $variantId = (int) $row['id'];
            if (!isset($grouped[$variantId])) {
                $grouped[$variantId] = [
                    'id' => $variantId,
                    'product_id' => (int) $row['product_id'],
                    'combination_key' => (string) $row['combination_key'],
                    'combination_label' => (string) $row['combination_label'],
                    'sku' => (string) ($row['sku'] ?? ''),
                    'variant_price' => $row['variant_price'] !== null ? (float) $row['variant_price'] : null,
                    'variant_sale_price' => $row['variant_sale_price'] !== null ? (float) $row['variant_sale_price'] : null,
                    'variant_weight_grams' => (int) ($row['variant_weight_grams'] ?? 0),
                    'image_path' => (string) ($row['image_path'] ?? ''),
                    'stock_mode' => (string) $row['stock_mode'],
                    'stock_qty' => (int) $row['stock_qty'],
                    'low_stock_threshold' => (int) $row['low_stock_threshold'],
                    'manual_stock_status' => (string) $row['manual_stock_status'],
                    'is_active' => !empty($row['is_active']),
                    'values' => []
                ];
            }

            if (!empty($row['variation_name']) && !empty($row['variation_value'])) {
                $grouped[$variantId]['values'][] = [
                    'variation_id' => (int) $row['variation_id'],
                    'variation_value_id' => (int) $row['variation_value_id'],
                    'variation_name' => (string) $row['variation_name'],
                    'variation_value' => (string) $row['variation_value']
                ];
            }
        }

        return array_values($grouped);
    }

    public function getVariantStockMap($productId)
    {
        $rows = $this->getVariantStockRows($productId);
        $map = [];
        foreach ($rows as $row) {
            $map[$row['combination_key']] = $row;
        }
        return $map;
    }

    public function getVariantRowByKey($productId, $variantKey)
    {
        $variantKey = trim((string) $variantKey);
        if ($variantKey === '') {
            return null;
        }

        $map = $this->getVariantStockMap($productId);
        return $map[$variantKey] ?? null;
    }

    public function getResolvedVariantData(array $product, $variantKey = '')
    {
        $basePrice = (!empty($product['sale_price']) && (float) $product['sale_price'] < (float) $product['price'])
            ? (float) $product['sale_price']
            : (float) $product['price'];
        $resolved = [
            'price' => $basePrice,
            'regular_price' => (float) ($product['price'] ?? 0),
            'sale_price' => (!empty($product['sale_price']) && (float) $product['sale_price'] < (float) $product['price']) ? (float) $product['sale_price'] : null,
            'weight_grams' => max(0, (int) ($product['weight_grams'] ?? 0)),
            'image_path' => (string) ($product['main_image'] ?? ''),
            'variant_row' => null
        ];

        $variant = $this->getVariantRowByKey((int) ($product['id'] ?? 0), $variantKey);
        if (!$variant) {
            return $resolved;
        }

        if ($variant['variant_price'] !== null) {
            $resolved['regular_price'] = (float) $variant['variant_price'];
            $resolved['price'] = (float) $variant['variant_price'];
            $resolved['sale_price'] = null;
        }
        if ($variant['variant_sale_price'] !== null && $variant['variant_price'] !== null && (float) $variant['variant_sale_price'] < (float) $variant['variant_price']) {
            $resolved['sale_price'] = (float) $variant['variant_sale_price'];
            $resolved['price'] = (float) $variant['variant_sale_price'];
        }
        $resolved['weight_grams'] = max(0, (int) ($variant['variant_weight_grams'] ?? 0));
        if (!empty($variant['image_path'])) {
            $resolved['image_path'] = (string) $variant['image_path'];
        }

        $resolved['variant_row'] = $variant;
        return $resolved;
    }

    public function getStockAlertState($productId, $variantKey = '')
    {
        $productId = (int) $productId;
        $variantKey = trim((string) $variantKey);
        $product = $this->getById($productId);
        if (!$product) {
            return null;
        }

        if ($variantKey !== '') {
            $variant = $this->getVariantRowByKey($productId, $variantKey);
            if (!$variant || empty($variant['is_active'])) {
                return [
                    'product_id' => $productId,
                    'product_title' => (string) ($product['title'] ?? 'Product'),
                    'variant_key' => $variantKey,
                    'variant_label' => (string) ($variant['combination_label'] ?? $variantKey),
                    'status' => 'out_of_stock',
                    'stock_qty' => 0,
                    'threshold' => 0,
                    'is_variant' => true
                ];
            }

            $status = 'in_stock';
            $stockQty = null;
            $threshold = max(0, (int) ($variant['low_stock_threshold'] ?? 0));
            if (($variant['stock_mode'] ?? '') === 'track_stock') {
                $stockQty = max(0, (int) ($variant['stock_qty'] ?? 0));
                if ($stockQty <= 0) {
                    $status = 'out_of_stock';
                } elseif ($stockQty <= $threshold) {
                    $status = 'low_stock';
                }
            }

            return [
                'product_id' => $productId,
                'product_title' => (string) ($product['title'] ?? 'Product'),
                'variant_key' => $variantKey,
                'variant_label' => (string) ($variant['combination_label'] ?? $variantKey),
                'status' => $status,
                'stock_qty' => $stockQty,
                'threshold' => $threshold,
                'is_variant' => true
            ];
        }

        $snapshot = $this->getStockSnapshot($product);
        return [
            'product_id' => $productId,
            'product_title' => (string) ($product['title'] ?? 'Product'),
            'variant_key' => '',
            'variant_label' => '',
            'status' => (string) ($snapshot['status'] ?? 'in_stock'),
            'stock_qty' => $snapshot['available_qty'] === null ? null : (int) $snapshot['available_qty'],
            'threshold' => (int) ($snapshot['low_stock_threshold'] ?? 0),
            'is_variant' => false
        ];
    }

    public function getStockSnapshot(array $product)
    {
        $mode = $this->normalizeStockMode($product['stock_mode'] ?? 'always_in_stock');
        $qty = max(0, (int) ($product['stock_qty'] ?? 0));
        $threshold = max(0, (int) ($product['low_stock_threshold'] ?? 5));
        $manualStatus = $this->normalizeManualStockStatus($product['manual_stock_status'] ?? 'in_stock');
        $variantRows = $this->getVariantStockRows((int) ($product['id'] ?? 0));

        $hasVariantStock = !empty($variantRows);
        $inStock = true;
        $availableQty = null;
        $status = 'in_stock';

        if ($hasVariantStock) {
            $totalQty = 0;
            $hasAvailableVariant = false;
            $hasLowStockVariant = false;
            foreach ($variantRows as $row) {
                if (!$row['is_active']) {
                    continue;
                }
                if ($row['stock_mode'] === 'manual_out_of_stock') {
                    continue;
                }
                if ($row['stock_mode'] === 'always_in_stock') {
                    $hasAvailableVariant = true;
                    $availableQty = null;
                    continue;
                }
                if ($row['stock_mode'] === 'track_stock' && (int) $row['stock_qty'] > 0) {
                    $hasAvailableVariant = true;
                    $totalQty += (int) $row['stock_qty'];
                    if ((int) $row['stock_qty'] <= max(0, (int) ($row['low_stock_threshold'] ?? 0))) {
                        $hasLowStockVariant = true;
                    }
                }
                if ($row['manual_stock_status'] === 'in_stock' && $row['stock_mode'] !== 'track_stock') {
                    $hasAvailableVariant = true;
                }
            }
            $inStock = $hasAvailableVariant;
            $availableQty = $availableQty === null && $hasAvailableVariant && $totalQty > 0 ? $totalQty : $availableQty;
            if ($hasAvailableVariant && $hasLowStockVariant) {
                $status = 'low_stock';
            }
        } elseif ($mode === 'track_stock') {
            $inStock = $qty > 0;
            $availableQty = $qty;
        } elseif ($mode === 'manual_out_of_stock') {
            $inStock = $manualStatus === 'in_stock';
            $availableQty = $inStock ? null : 0;
        } else {
            $inStock = true;
            $availableQty = null;
        }

        if (!$inStock) {
            $status = 'out_of_stock';
        } elseif ($status !== 'low_stock' && $availableQty !== null && $availableQty <= $threshold) {
            $status = 'low_stock';
        }

        return [
            'has_variant_stock' => $hasVariantStock,
            'variant_rows' => $variantRows,
            'stock_mode' => $mode,
            'stock_qty' => $qty,
            'low_stock_threshold' => $threshold,
            'manual_stock_status' => $manualStatus,
            'in_stock' => $inStock,
            'status' => $status,
            'available_qty' => $availableQty
        ];
    }

    public function validatePurchase($productId, $qty, $variantKey = '')
    {
        $product = $this->getById($productId);
        if (!$product || empty($product['is_active'])) {
            return ['ok' => false, 'message' => 'This product is no longer available.'];
        }

        $qty = max(1, (int) $qty);
        $variantKey = trim((string) $variantKey);
        if ($this->productRequiresVariationSelection($productId) && $variantKey === '') {
            return ['ok' => false, 'message' => 'Please choose a valid product variation.'];
        }

        $snapshot = $this->getStockSnapshot($product);
        if ($snapshot['has_variant_stock']) {
            $variantMap = $this->getVariantStockMap($productId);
            $variant = $variantMap[$variantKey] ?? null;
            if (!$variant || empty($variant['is_active'])) {
                return ['ok' => false, 'message' => 'That variation is not available.'];
            }

            if ($variant['stock_mode'] === 'track_stock' && (int) $variant['stock_qty'] < $qty) {
                return ['ok' => false, 'message' => 'Only ' . (int) $variant['stock_qty'] . ' items available for this variation.'];
            }

            if ($variant['stock_mode'] === 'manual_out_of_stock' && $variant['manual_stock_status'] !== 'in_stock') {
                return ['ok' => false, 'message' => 'This variation is out of stock.'];
            }
            if ($variant['stock_mode'] !== 'track_stock' && $variant['stock_mode'] !== 'always_in_stock') {
                return ['ok' => false, 'message' => 'This variation is out of stock.'];
            }
        } else {
            if ($snapshot['stock_mode'] === 'track_stock' && (int) $snapshot['stock_qty'] < $qty) {
                return ['ok' => false, 'message' => 'Only ' . (int) $snapshot['stock_qty'] . ' items available in stock.'];
            }

            if ($snapshot['stock_mode'] === 'manual_out_of_stock' && !$snapshot['in_stock']) {
                return ['ok' => false, 'message' => 'This product is out of stock.'];
            }
        }

        return ['ok' => true, 'product' => $product, 'snapshot' => $snapshot];
    }

    public function reduceStockForLineItem($productId, $qty, $variantKey = '')
    {
        $qty = max(1, (int) $qty);
        $product = $this->getById($productId);
        if (!$product) {
            return false;
        }

        $snapshot = $this->getStockSnapshot($product);
        if ($snapshot['has_variant_stock']) {
            $variantMap = $this->getVariantStockMap($productId);
            $variant = $variantMap[trim((string) $variantKey)] ?? null;
            if (!$variant) {
                return false;
            }

            if ($variant['stock_mode'] === 'track_stock') {
                $stmt = $this->conn->prepare("UPDATE product_variant_stock
                    SET stock_qty = GREATEST(stock_qty - :qty, 0)
                    WHERE id = :id");
                return $stmt->execute([
                    ':qty' => $qty,
                    ':id' => $variant['id']
                ]);
            }

            return true;
        }

        if ($snapshot['stock_mode'] === 'track_stock') {
            $stmt = $this->conn->prepare("UPDATE products
                SET stock_qty = GREATEST(stock_qty - :qty, 0)
                WHERE id = :id");
            return $stmt->execute([
                ':qty' => $qty,
                ':id' => $productId
            ]);
        }

        return true;
    }

    public function restoreStockForLineItem($productId, $qty, $variantKey = '')
    {
        $qty = max(1, (int) $qty);
        $product = $this->getById($productId);
        if (!$product) {
            return false;
        }

        $snapshot = $this->getStockSnapshot($product);
        if ($snapshot['has_variant_stock']) {
            $variantMap = $this->getVariantStockMap($productId);
            $variant = $variantMap[trim((string) $variantKey)] ?? null;
            if (!$variant) {
                return false;
            }

            if ($variant['stock_mode'] === 'track_stock') {
                $stmt = $this->conn->prepare("UPDATE product_variant_stock
                    SET stock_qty = stock_qty + :qty
                    WHERE id = :id");
                return $stmt->execute([
                    ':qty' => $qty,
                    ':id' => $variant['id']
                ]);
            }

            return true;
        }

        if ($snapshot['stock_mode'] === 'track_stock') {
            $stmt = $this->conn->prepare("UPDATE products
                SET stock_qty = stock_qty + :qty
                WHERE id = :id");
            return $stmt->execute([
                ':qty' => $qty,
                ':id' => $productId
            ]);
        }

        return true;
    }

    public function getStockOverview()
    {
        $products = $this->getAll();
        $summary = [
            'tracked_products' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
            'variant_products' => 0
        ];

        foreach ($products as &$product) {
            $snapshot = $this->getStockSnapshot($product);
            $product['stock_snapshot'] = $snapshot;
            if ($snapshot['stock_mode'] === 'track_stock' || $snapshot['has_variant_stock']) {
                $summary['tracked_products']++;
            }
            if ($snapshot['has_variant_stock']) {
                $summary['variant_products']++;
            }
            if ($snapshot['status'] === 'low_stock') {
                $summary['low_stock']++;
            }
            if ($snapshot['status'] === 'out_of_stock') {
                $summary['out_of_stock']++;
            }
        }

        return ['summary' => $summary, 'products' => $products];
    }

    private function buildStockReportOrderFilterParts(array $filters)
    {
        $where = [];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[] = "DATE(o.created_at) >= :date_from";
            $params[':date_from'] = trim((string) $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $where[] = "DATE(o.created_at) <= :date_to";
            $params[':date_to'] = trim((string) $filters['date_to']);
        }

        if (!empty($filters['payment_status'])) {
            $where[] = "o.payment_status = :payment_status";
            $params[':payment_status'] = trim((string) $filters['payment_status']);
        }

        if (!empty($filters['order_status'])) {
            $where[] = "o.order_status = :order_status";
            $params[':order_status'] = trim((string) $filters['order_status']);
        } else {
            $where[] = "o.order_status != 'cancelled'";
        }

        return [$where, $params];
    }

    private function getStockReportSalesMap(array $filters)
    {
        [$where, $params] = $this->buildStockReportOrderFilterParts($filters);

        $sql = "
            SELECT
                oi.product_id,
                COUNT(DISTINCT oi.order_id) AS orders_count,
                COALESCE(SUM(oi.qty), 0) AS units_sold,
                COALESCE(SUM(oi.line_total), 0) AS revenue_total,
                MAX(o.created_at) AS last_ordered_at
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            WHERE oi.product_id IS NOT NULL
        ";

        if (!empty($where)) {
            $sql .= ' AND ' . implode(' AND ', $where);
        }

        $sql .= ' GROUP BY oi.product_id';

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $map = [];
        foreach (($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
            $map[(int) $row['product_id']] = [
                'orders_count' => (int) ($row['orders_count'] ?? 0),
                'units_sold' => (int) ($row['units_sold'] ?? 0),
                'revenue_total' => (float) ($row['revenue_total'] ?? 0),
                'last_ordered_at' => $row['last_ordered_at'] ?? null
            ];
        }

        return $map;
    }

    public function getStockReport(array $filters = [])
    {
        $overview = $this->getStockOverview();
        $salesMap = $this->getStockReportSalesMap($filters);
        $search = trim((string) ($filters['search'] ?? ''));
        $stockState = trim((string) ($filters['stock_state'] ?? ''));
        $productType = trim((string) ($filters['product_type'] ?? ''));

        $summary = [
            'total_products' => 0,
            'tracked_products' => 0,
            'variant_products' => 0,
            'simple_products' => 0,
            'in_stock' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
            'attention_products' => 0,
            'tracked_variants' => 0,
            'in_stock_variants' => 0,
            'low_stock_variants' => 0,
            'out_of_stock_variants' => 0,
            'units_on_hand' => 0,
            'inventory_value' => 0.0,
            'products_with_sales' => 0,
            'zero_sales_products' => 0,
            'total_units_sold' => 0,
            'total_sales_revenue' => 0.0
        ];
        $rows = [];

        foreach (($overview['products'] ?? []) as $product) {
            $snapshot = $product['stock_snapshot'] ?? $this->getStockSnapshot($product);
            $sales = $salesMap[(int) $product['id']] ?? [
                'orders_count' => 0,
                'units_sold' => 0,
                'revenue_total' => 0.0,
                'last_ordered_at' => null
            ];
            $effectivePrice = (!empty($product['sale_price']) && (float) $product['sale_price'] > 0 && (float) $product['sale_price'] < (float) $product['price'])
                ? (float) $product['sale_price']
                : (float) ($product['price'] ?? 0);
            $availableQty = $snapshot['available_qty'];
            $inventoryValue = $availableQty !== null ? ((int) $availableQty * $effectivePrice) : null;
            $variantRows = [];
            $variantSummary = [
                'total' => 0,
                'active' => 0,
                'tracked' => 0,
                'in_stock' => 0,
                'low_stock' => 0,
                'out_of_stock' => 0
            ];

            foreach (($snapshot['variant_rows'] ?? []) as $variantRow) {
                $variantMode = (string) ($variantRow['stock_mode'] ?? 'always_in_stock');
                $variantQty = max(0, (int) ($variantRow['stock_qty'] ?? 0));
                $variantThreshold = max(0, (int) ($variantRow['low_stock_threshold'] ?? 0));
                $variantIsActive = !empty($variantRow['is_active']);
                $variantStatus = 'in_stock';
                $variantAvailableQty = null;

                if (!$variantIsActive) {
                    $variantStatus = 'out_of_stock';
                    $variantAvailableQty = 0;
                } elseif ($variantMode === 'track_stock') {
                    $variantAvailableQty = $variantQty;
                    if ($variantQty <= 0) {
                        $variantStatus = 'out_of_stock';
                    } elseif ($variantQty <= $variantThreshold) {
                        $variantStatus = 'low_stock';
                    }
                } elseif ($variantMode === 'always_in_stock') {
                    $variantStatus = 'in_stock';
                } elseif (($variantRow['manual_stock_status'] ?? 'in_stock') !== 'in_stock') {
                    $variantStatus = 'out_of_stock';
                    $variantAvailableQty = 0;
                }

                $variantEffectivePrice = $effectivePrice;
                if (
                    isset($variantRow['variant_sale_price'], $variantRow['variant_price'])
                    && $variantRow['variant_sale_price'] !== null
                    && (float) $variantRow['variant_sale_price'] > 0
                    && (float) $variantRow['variant_sale_price'] < (float) $variantRow['variant_price']
                ) {
                    $variantEffectivePrice = (float) $variantRow['variant_sale_price'];
                } elseif ($variantRow['variant_price'] !== null && (float) $variantRow['variant_price'] > 0) {
                    $variantEffectivePrice = (float) $variantRow['variant_price'];
                }

                $variantRows[] = [
                    'id' => (int) ($variantRow['id'] ?? 0),
                    'combination_key' => (string) ($variantRow['combination_key'] ?? ''),
                    'combination_label' => (string) ($variantRow['combination_label'] ?? ''),
                    'sku' => (string) ($variantRow['sku'] ?? ''),
                    'status' => $variantStatus,
                    'stock_mode' => $variantMode,
                    'available_qty' => $variantAvailableQty,
                    'stock_qty' => $variantQty,
                    'low_stock_threshold' => $variantThreshold,
                    'effective_price' => $variantEffectivePrice,
                    'variant_price' => $variantRow['variant_price'] !== null ? (float) $variantRow['variant_price'] : null,
                    'variant_sale_price' => $variantRow['variant_sale_price'] !== null ? (float) $variantRow['variant_sale_price'] : null,
                    'variant_weight_grams' => (int) ($variantRow['variant_weight_grams'] ?? 0),
                    'is_active' => $variantIsActive
                ];

                $variantSummary['total']++;
                if ($variantIsActive) {
                    $variantSummary['active']++;
                }
                if ($variantMode === 'track_stock') {
                    $variantSummary['tracked']++;
                }
                if ($variantStatus === 'in_stock') {
                    $variantSummary['in_stock']++;
                } elseif ($variantStatus === 'low_stock') {
                    $variantSummary['low_stock']++;
                } else {
                    $variantSummary['out_of_stock']++;
                }
            }

            $row = [
                'id' => (int) $product['id'],
                'title' => (string) ($product['title'] ?? 'Product'),
                'sku' => (string) ($product['sku'] ?? ''),
                'category_name' => (string) ($product['category_name'] ?? ''),
                'status' => (string) ($snapshot['status'] ?? 'in_stock'),
                'stock_mode' => (string) ($snapshot['stock_mode'] ?? 'always_in_stock'),
                'available_qty' => $availableQty === null ? null : (int) $availableQty,
                'low_stock_threshold' => (int) ($snapshot['low_stock_threshold'] ?? 0),
                'has_variant_stock' => !empty($snapshot['has_variant_stock']),
                'variant_count' => !empty($snapshot['variant_rows']) ? count($snapshot['variant_rows']) : 0,
                'product_type' => !empty($snapshot['has_variant_stock']) ? 'variant' : 'simple',
                'units_sold' => (int) ($sales['units_sold'] ?? 0),
                'orders_count' => (int) ($sales['orders_count'] ?? 0),
                'revenue_total' => (float) ($sales['revenue_total'] ?? 0),
                'last_ordered_at' => $sales['last_ordered_at'] ?? null,
                'inventory_value' => $inventoryValue,
                'effective_price' => $effectivePrice,
                'price' => (float) ($product['price'] ?? 0),
                'sale_price' => !empty($product['sale_price']) ? (float) $product['sale_price'] : null,
                'weight_grams' => (int) ($product['weight_grams'] ?? 0),
                'is_active' => !empty($product['is_active']),
                'variant_summary' => $variantSummary,
                'variant_rows' => $variantRows
            ];

            if ($search !== '') {
                $haystack = strtolower(trim(implode(' ', [
                    $row['title'],
                    $row['sku'],
                    $row['category_name'],
                    implode(' ', array_map(function ($variantRow) {
                        return trim((string) (($variantRow['combination_label'] ?? '') . ' ' . ($variantRow['sku'] ?? '')));
                    }, $variantRows))
                ])));
                if (strpos($haystack, strtolower($search)) === false) {
                    continue;
                }
            }

            if ($stockState !== '' && $row['status'] !== $stockState) {
                continue;
            }

            if ($productType !== '' && $row['product_type'] !== $productType) {
                continue;
            }

            $summary['total_products']++;
            $summary['total_units_sold'] += $row['units_sold'];
            $summary['total_sales_revenue'] += $row['revenue_total'];

            if ($row['stock_mode'] === 'track_stock' || $row['has_variant_stock']) {
                $summary['tracked_products']++;
            }
            if ($row['has_variant_stock']) {
                $summary['variant_products']++;
            } else {
                $summary['simple_products']++;
            }
            if ($row['status'] === 'in_stock') {
                $summary['in_stock']++;
            } elseif ($row['status'] === 'low_stock') {
                $summary['low_stock']++;
            } else {
                $summary['out_of_stock']++;
            }
            if ($row['status'] !== 'in_stock' || ($variantSummary['low_stock'] + $variantSummary['out_of_stock']) > 0) {
                $summary['attention_products']++;
            }
            if ($row['available_qty'] !== null) {
                $summary['units_on_hand'] += $row['available_qty'];
            }
            if ($row['inventory_value'] !== null) {
                $summary['inventory_value'] += $row['inventory_value'];
            }
            if ($row['units_sold'] > 0) {
                $summary['products_with_sales']++;
            } else {
                $summary['zero_sales_products']++;
            }
            $summary['tracked_variants'] += $variantSummary['tracked'];
            $summary['in_stock_variants'] += $variantSummary['in_stock'];
            $summary['low_stock_variants'] += $variantSummary['low_stock'];
            $summary['out_of_stock_variants'] += $variantSummary['out_of_stock'];

            $rows[] = $row;
        }

        $byUnitsSold = $rows;
        usort($byUnitsSold, function ($a, $b) {
            if ($b['units_sold'] === $a['units_sold']) {
                return $b['revenue_total'] <=> $a['revenue_total'];
            }
            return $b['units_sold'] <=> $a['units_sold'];
        });

        $byRevenue = $rows;
        usort($byRevenue, function ($a, $b) {
            if ($b['revenue_total'] === $a['revenue_total']) {
                return $b['units_sold'] <=> $a['units_sold'];
            }
            return $b['revenue_total'] <=> $a['revenue_total'];
        });

        $byAttention = $rows;
        usort($byAttention, function ($a, $b) {
            $priority = [
                'out_of_stock' => 3,
                'low_stock' => 2,
                'in_stock' => 1
            ];
            $aPriority = $priority[$a['status']] ?? 0;
            $bPriority = $priority[$b['status']] ?? 0;
            if ($bPriority === $aPriority) {
                if ($a['units_sold'] === $b['units_sold']) {
                    return strcmp($a['title'], $b['title']);
                }
                return $a['units_sold'] <=> $b['units_sold'];
            }
            return $bPriority <=> $aPriority;
        });

        $deadStock = array_values(array_filter($rows, function ($row) {
            return $row['units_sold'] === 0;
        }));
        usort($deadStock, function ($a, $b) {
            if (($b['inventory_value'] ?? 0) === ($a['inventory_value'] ?? 0)) {
                return strcmp($a['title'], $b['title']);
            }
            return ($b['inventory_value'] ?? 0) <=> ($a['inventory_value'] ?? 0);
        });

        return [
            'summary' => $summary,
            'rows' => $byAttention,
            'best_seller' => !empty($byUnitsSold) && (int) ($byUnitsSold[0]['units_sold'] ?? 0) > 0 ? $byUnitsSold[0] : null,
            'top_sellers' => array_slice(array_values(array_filter($byUnitsSold, function ($row) {
                return $row['units_sold'] > 0;
            })), 0, 5),
            'top_revenue' => array_slice(array_values(array_filter($byRevenue, function ($row) {
                return $row['revenue_total'] > 0;
            })), 0, 5),
            'attention_products' => array_slice($byAttention, 0, 8),
            'dead_stock' => array_slice($deadStock, 0, 8)
        ];
    }

    /**
     * Get Related Products (Same category, excluding current)
     */
    public function getRelated($categoryId, $excludeId, $limit = 4)
    {
        $sql = "SELECT p.*, c.name as category_name, pc.name as parent_category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN categories pc ON c.parent_id = pc.id
                WHERE p.category_id = :catId AND p.id != :excludeId AND p.is_active = 1
                ORDER BY RAND() LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':catId', $categoryId);
        $stmt->bindParam(':excludeId', $excludeId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $related = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($related) >= $limit) {
            return $related;
        }

        $excludeIds = array_map('intval', array_column($related, 'id'));
        $excludeIds[] = (int) $excludeId;
        $excludeIds = array_values(array_unique($excludeIds));

        $remaining = max(0, (int) $limit - count($related));
        if ($remaining === 0) {
            return $related;
        }

        $placeholders = [];
        $params = [];
        foreach ($excludeIds as $index => $id) {
            $placeholder = ':exclude_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $id;
        }

        $fallbackSql = "SELECT p.*, c.name as category_name, pc.name as parent_category_name
                        FROM products p
                        LEFT JOIN categories c ON p.category_id = c.id
                        LEFT JOIN categories pc ON c.parent_id = pc.id
                        WHERE p.is_active = 1 AND p.id NOT IN (" . implode(', ', $placeholders) . ")
                        ORDER BY p.created_at DESC, p.id DESC
                        LIMIT :limit";
        $fallbackStmt = $this->conn->prepare($fallbackSql);
        foreach ($params as $placeholder => $id) {
            $fallbackStmt->bindValue($placeholder, $id, PDO::PARAM_INT);
        }
        $fallbackStmt->bindValue(':limit', $remaining, PDO::PARAM_INT);
        $fallbackStmt->execute();

        return array_merge($related, $fallbackStmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Get Filtered Products (Price Range)
     * Safe, clean implementation
     */
    public function getFiltered($minPrice = null, $maxPrice = null, $search = null, $categoryIds = [])
    {
        $sql = "SELECT p.*, c.name as category_name, pc.name as parent_category_name
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN categories pc ON c.parent_id = pc.id
                WHERE 1=1";

        // Params array for execution
        $params = [];

        // 1. Price Filter (Standard)
        if (!empty($minPrice)) {
            $sql .= " AND p.price >= :minPrice";
            $params[':minPrice'] = $minPrice;
        }

        if (!empty($maxPrice)) {
            $sql .= " AND p.price <= :maxPrice";
            $params[':maxPrice'] = $maxPrice;
        }

        // 2. Search Filter
        if (!empty($search)) {
            $sql .= " AND (p.title LIKE :search OR p.sku LIKE :search)";
            $params[':search'] = "%$search%";
        }

        // 3. Category Filter (Array of IDs)
        // If categories are selected, we filter products that match ANY of these IDs.
        if (!empty($categoryIds) && is_array($categoryIds)) {
            // Create placeholders: ?, ?, ?
            // Since we use named params elsewhere, we can mix if careful or use named params with index
            // Safest with PDO is IN clause with generated named keys.

            $inQuery = "";
            foreach ($categoryIds as $i => $id) {
                $key = ":cat" . $i;
                $inQuery .= ($inQuery ? ", " : "") . $key;
                $params[$key] = $id;
            }

            if (!empty($inQuery)) {
                // Multi-Category Support
                // Check Primary Cat OR Primary Parent OR Multi-Cat OR Multi-Cat Parent
                $sql .= " AND (
                            p.category_id IN ($inQuery) 
                            OR c.parent_id IN ($inQuery)
                            OR EXISTS (
                                SELECT 1 FROM product_categories pc_multi 
                                LEFT JOIN categories c_multi ON pc_multi.category_id = c_multi.id
                                WHERE pc_multi.product_id = p.id 
                                AND (pc_multi.category_id IN ($inQuery) OR c_multi.parent_id IN ($inQuery))
                            )
                        )";
            }
        }

        $sql .= " AND p.is_active = 1";
        $sql .= " ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function nextCloneTitle($title)
    {
        $baseTitle = trim((string) $title);
        if ($baseTitle === '') {
            $baseTitle = 'Product';
        }

        $candidate = $baseTitle . ' (Copy)';
        $suffix = 2;
        while ($this->titleExists($candidate)) {
            $candidate = $baseTitle . ' (Copy ' . $suffix . ')';
            $suffix++;
        }

        return $candidate;
    }

    private function titleExists($title)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM products WHERE title = :title");
        $stmt->execute([':title' => (string) $title]);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    public function cloneById($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return 0;
        }

        $product = $this->getById($id);
        if (!$product) {
            return 0;
        }

        $galleryImages = $this->getGalleryImages($id);
        $variantRows = $this->getVariantStockRows($id);
        $categoryIds = $this->getProductCategoryIds($id);

        $variationRowsStmt = $this->conn->prepare("SELECT variation_id, variation_value_id FROM product_variations WHERE product_id = :pid");
        $variationRowsStmt->execute([':pid' => $id]);
        $variationRows = $variationRowsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $formattedVariations = array_map(function ($row) {
            return [
                'variation_id' => (int) ($row['variation_id'] ?? 0),
                'variation_value_id' => (int) ($row['variation_value_id'] ?? 0),
            ];
        }, $variationRows);

        $formattedVariantStocks = array_map(function ($row) {
            return [
                'combination_key' => (string) ($row['combination_key'] ?? ''),
                'combination_label' => (string) ($row['combination_label'] ?? ''),
                'sku' => '',
                'variant_price' => $row['variant_price'],
                'variant_sale_price' => $row['variant_sale_price'],
                'variant_weight_grams' => (int) ($row['variant_weight_grams'] ?? 0),
                'image_path' => (string) ($row['image_path'] ?? ''),
                'stock_mode' => (string) ($row['stock_mode'] ?? 'track_stock'),
                'stock_qty' => (int) ($row['stock_qty'] ?? 0),
                'low_stock_threshold' => (int) ($row['low_stock_threshold'] ?? 5),
                'manual_stock_status' => (string) ($row['manual_stock_status'] ?? 'in_stock'),
                'is_active' => !empty($row['is_active']),
                'values' => is_array($row['values'] ?? null) ? $row['values'] : []
            ];
        }, $variantRows);

        $cloneData = [
            'title' => $this->nextCloneTitle((string) ($product['title'] ?? 'Product')),
            'sku' => '',
            'price' => $product['price'] ?? 0,
            'sale_price' => $product['sale_price'] ?? null,
            'weight_grams' => max(0, (int) ($product['weight_grams'] ?? 0)),
            'free_shipping' => !empty($product['free_shipping']),
            'stock_mode' => (string) ($product['stock_mode'] ?? 'always_in_stock'),
            'stock_qty' => max(0, (int) ($product['stock_qty'] ?? 0)),
            'low_stock_threshold' => max(0, (int) ($product['low_stock_threshold'] ?? 5)),
            'manual_stock_status' => (string) ($product['manual_stock_status'] ?? 'in_stock'),
            'description' => (string) ($product['description'] ?? ''),
            'short_description' => (string) ($product['short_description'] ?? ''),
            'main_image' => (string) ($product['main_image'] ?? ''),
            'is_featured' => !empty($product['is_featured']),
            'category_id' => (int) ($product['category_id'] ?? 0),
            'size_guide_id' => !empty($product['size_guide_id']) ? (int) $product['size_guide_id'] : null,
            'gallery_images' => is_array($galleryImages) ? $galleryImages : [],
            'variations' => $formattedVariations,
            'categories' => $categoryIds,
            'variant_stocks' => $formattedVariantStocks
        ];

        return (int) $this->create($cloneData);
    }

    public function toggleActive($id)
    {
        $sql = "UPDATE products SET is_active = NOT is_active WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>
