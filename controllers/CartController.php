<?php
require_once 'helpers/ImageHelper.php';

class CartController extends BaseController
{
    private function enrichCartItems(array $cart)
    {
        require_once 'models/Product.php';
        $productModel = new Product();
        $updated = false;

        foreach ($cart as &$item) {
            $productId = (int) ($item['id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $product = $productModel->getById($productId);
            if (!$product) {
                continue;
            }

            $qty = max(1, (int) ($item['qty'] ?? 1));
            $variantData = $productModel->getResolvedVariantData($product, (string) ($item['variant_key'] ?? ''));
            $livePrice = (float) ($variantData['price'] ?? 0);
            $validation = $productModel->validatePurchase($productId, $qty, (string) ($item['variant_key'] ?? ''));
            $snapshot = $productModel->getStockSnapshot($product);

            $item['price'] = $livePrice;
            $item['title'] = $product['title'] ?? ($item['title'] ?? 'Product');
            $item['weight_grams'] = max(0, (int) ($variantData['weight_grams'] ?? 0));
            $item['is_free_shipping'] = !empty($product['free_shipping']) ? 1 : 0;
            $item['variant_key'] = (string) ($item['variant_key'] ?? '');
            $item['purchase_blocked'] = empty($validation['ok']) ? 1 : 0;
            $item['stock_message'] = (string) ($validation['message'] ?? '');
            $item['stock_snapshot'] = [
                'status' => (string) ($snapshot['status'] ?? 'in_stock'),
                'available_qty' => array_key_exists('available_qty', $snapshot) ? $snapshot['available_qty'] : null
            ];

            if (!empty($variantData['image_path'])) {
                $item['img'] = ImageHelper::uploadUrl($variantData['image_path'], (string) ($item['img'] ?? ''));
            } elseif (empty($item['img']) && !empty($product['main_image'])) {
                $item['img'] = ImageHelper::uploadUrl($product['main_image'], '');
            }

            $updated = true;
        }

        if ($updated) {
            $_SESSION['cart'] = $cart;
        }

        return $cart;
    }

    public function index()
    {
        // 1. Fetch Settings
        require_once 'models/Setting.php';
        require_once 'models/DeliverySetting.php';
        require_once 'helpers/DeliveryHelper.php';
        require_once 'helpers/SeoHelper.php';

        $settingModel = new Setting();
        $deliverySettingModel = new DeliverySetting();
        $settings = $settingModel->getAllPairs();
        $seo = SeoHelper::defaultSeo($settings, [
            'seo_title' => SeoHelper::pageTitle('My Cart', $settings),
            'seo_description' => 'Review the selected items in your shopping cart before checkout.',
            'seo_canonical' => SeoHelper::absoluteUrl(BASE_URL . 'cart'),
            'seo_robots' => 'noindex,nofollow',
            'seo_json_ld' => [
                SeoHelper::buildOrganizationSchema($settings),
                SeoHelper::buildWebsiteSchema($settings)
            ]
        ]);

        $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
        $cart = $this->enrichCartItems($cart);

        $this->view('customer/shop/cart', [
            'title' => 'My Cart',
            'settings' => $settings,
            'cart' => $cart,
            'deliveryDistricts' => DeliveryHelper::districtList(),
            'deliveryRatesMap' => $deliverySettingModel->getRatesMap(),
            'seo_title' => $seo['seo_title'],
            'seo_description' => $seo['seo_description'],
            'seo_canonical' => $seo['seo_canonical'],
            'seo_image' => $seo['seo_image'],
            'seo_type' => $seo['seo_type'],
            'seo_robots' => $seo['seo_robots'],
            'seo_json_ld' => $seo['seo_json_ld']
        ]);
    }

    // Add Item to Cart (AJAX)
    public function add()
    {
        require_once 'models/Product.php';
        $productModel = new Product();

        // Accept JSON Input
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        $product = $productModel->getById((int) $input['id']);
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }

        $variantKey = trim((string) ($input['variant_key'] ?? ''));
        $qtyToAdd = isset($input['quantity']) ? (int) $input['quantity'] : 1;
        if ($qtyToAdd < 1) {
            $qtyToAdd = 1;
        }
        $validation = $productModel->validatePurchase((int) $input['id'], $qtyToAdd, $variantKey);
        if (empty($validation['ok'])) {
            echo json_encode(['success' => false, 'message' => $validation['message'] ?? 'This item is not available.']);
            exit;
        }

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Check for existing item (Same ID + Same Variations)
        $found = false;

        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $input['id'] && $item['variants'] == $input['variants'] && ($item['variant_key'] ?? '') === $variantKey) {
                $newQty = (int) $item['qty'] + $qtyToAdd;
                $validation = $productModel->validatePurchase((int) $input['id'], $newQty, $variantKey);
                if (empty($validation['ok'])) {
                    echo json_encode(['success' => false, 'message' => $validation['message'] ?? 'Not enough stock available.']);
                    exit;
                }
                $item['qty'] = $newQty;
                $found = true;
                break;
            }
        }

        // Add new if not found
        if (!$found) {
            $variantData = $productModel->getResolvedVariantData($product, $variantKey);
            $imageUrl = $input['img'] ?? '';
            if (!empty($variantData['image_path'])) {
                $imageUrl = ImageHelper::uploadUrl($variantData['image_path'], $imageUrl);
            } elseif (!empty($product['main_image'])) {
                $imageUrl = ImageHelper::uploadUrl($product['main_image'], $imageUrl);
            }

            $livePrice = (float) ($variantData['price'] ?? 0);

            $_SESSION['cart'][] = [
                'id' => (int) $product['id'],
                'title' => $product['title'] ?? ($input['title'] ?? 'Product'),
                'price' => $livePrice,
                'img' => $imageUrl,
                'variants' => $input['variants'] ?? '',
                'variant_key' => $variantKey,
                'qty' => $qtyToAdd,
                'weight_grams' => max(0, (int) ($variantData['weight_grams'] ?? 0)),
                'is_free_shipping' => !empty($product['free_shipping']) ? 1 : 0
            ];
        }

        // Return Success
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'count' => array_sum(array_column($_SESSION['cart'], 'qty'))
        ]);
        exit;
    }

    // Remove Item (AJAX)
    public function remove()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $index = $input['index'] ?? null;

        if ($index !== null && isset($_SESSION['cart'][$index])) {
            array_splice($_SESSION['cart'], $index, 1);
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'cart' => array_values($_SESSION['cart']), // Return new array
            'count' => isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0
        ]);
        exit;
    }

    // Update Item Quantity (AJAX)
    public function updateQty()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $index = $input['index'] ?? null;
        $qty = isset($input['qty']) ? (int) $input['qty'] : 1;

        if ($index === null || !isset($_SESSION['cart'][$index])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Cart item not found.']);
            exit;
        }

        require_once 'models/Product.php';
        $productModel = new Product();
        $variantKey = (string) ($_SESSION['cart'][$index]['variant_key'] ?? '');
        $validation = $productModel->validatePurchase((int) ($_SESSION['cart'][$index]['id'] ?? 0), max(1, $qty), $variantKey);
        if (empty($validation['ok'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $validation['message'] ?? 'Not enough stock available.']);
            exit;
        }

        $_SESSION['cart'][$index]['qty'] = max(1, $qty);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'cart' => array_values($_SESSION['cart']),
            'count' => array_sum(array_column($_SESSION['cart'], 'qty'))
        ]);
        exit;
    }

    // Clear Cart (AJAX)
    public function clear()
    {
        $_SESSION['cart'] = [];

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'count' => 0]);
        exit;
    }

    // Cart Count (AJAX)
    public function count()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'count' => isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0
        ]);
        exit;
    }
}
?>
