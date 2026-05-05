<?php
/**
 * Product Controller
 */
require_once 'models/Product.php';
require_once 'models/Category.php';
require_once 'models/SizeGuide.php';
require_once 'models/Variation.php';
require_once 'helpers/ImageHelper.php';
require_once 'helpers/StockAlertService.php';

class ProductController extends BaseController
{

    private $productModel;
    private $categoryModel;
    private $sizeGuideModel;
    private $variationModel;
    private $stockAlertService;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->sizeGuideModel = new SizeGuide();
        $this->variationModel = new Variation();
        $this->stockAlertService = new StockAlertService();
    }

    private function parseVariantStocksFromRequest()
    {
        $raw = trim((string) ($_POST['variant_stocks_json'] ?? ''));
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeVariantImageUploadMap()
    {
        $keys = $_POST['variant_image_keys'] ?? [];
        $files = $_FILES['variant_image_files'] ?? null;
        if (!is_array($keys) || !$files || !is_array($files['name'] ?? null)) {
            return [];
        }

        $uploads = [];
        foreach ($keys as $index => $combinationKey) {
            $combinationKey = trim((string) $combinationKey);
            if ($combinationKey === '') {
                continue;
            }

            if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $storedName = ImageHelper::storeUploadedArrayFile($files, $index, 'variant_' . $index);
            if ($storedName !== '') {
                $uploads[$combinationKey] = $storedName;
            }
        }

        return $uploads;
    }

    private function buildVariantStocksForSave()
    {
        $variantStocks = $this->parseVariantStocksFromRequest();
        if (empty($variantStocks)) {
            return [];
        }

        $uploadedImages = $this->normalizeVariantImageUploadMap();
        foreach ($variantStocks as &$variantStock) {
            $combinationKey = trim((string) ($variantStock['combination_key'] ?? ''));
            if ($combinationKey !== '' && isset($uploadedImages[$combinationKey])) {
                $variantStock['image_path'] = $uploadedImages[$combinationKey];
            } else {
                $variantStock['image_path'] = trim((string) ($variantStock['image_path'] ?? ''));
            }
        }
        unset($variantStock);

        return $variantStocks;
    }

    private function getProductFormDependencies()
    {
        return [
            'categories' => $this->categoryModel->getAll(),
            'sizeGuides' => $this->sizeGuideModel->getAll(),
            'variations' => $this->variationModel->getAll()
        ];
    }

    private function getSelectedVariationTokensFromRequest()
    {
        $tokens = $_POST['selected_variations'] ?? [];
        if (!is_array($tokens)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($token) {
            return trim((string) $token);
        }, $tokens)));
    }

    private function buildProductDraftFromRequest(array $baseProduct = [])
    {
        $categories = $_POST['categories'] ?? ($baseProduct['categories'] ?? []);
        if (!is_array($categories)) {
            $categories = [];
        }

        return array_merge($baseProduct, [
            'id' => $baseProduct['id'] ?? ($_POST['id'] ?? null),
            'title' => trim((string) ($_POST['title'] ?? ($baseProduct['title'] ?? ''))),
            'sku' => trim((string) ($_POST['sku'] ?? ($baseProduct['sku'] ?? ''))),
            'price' => trim((string) ($_POST['price'] ?? ($baseProduct['price'] ?? ''))),
            'sale_price' => trim((string) ($_POST['sale_price'] ?? ($baseProduct['sale_price'] ?? ''))),
            'weight_grams' => trim((string) ($_POST['weight_grams'] ?? ($baseProduct['weight_grams'] ?? '0'))),
            'free_shipping' => isset($_POST['free_shipping']) ? 1 : (!empty($baseProduct['free_shipping']) ? 1 : 0),
            'stock_mode' => trim((string) ($_POST['stock_mode'] ?? ($baseProduct['stock_mode'] ?? 'always_in_stock'))),
            'stock_qty' => trim((string) ($_POST['stock_qty'] ?? ($baseProduct['stock_qty'] ?? '0'))),
            'low_stock_threshold' => trim((string) ($_POST['low_stock_threshold'] ?? ($baseProduct['low_stock_threshold'] ?? '5'))),
            'manual_stock_status' => trim((string) ($_POST['manual_stock_status'] ?? ($baseProduct['manual_stock_status'] ?? 'in_stock'))),
            'description' => (string) ($_POST['description'] ?? ($baseProduct['description'] ?? '')),
            'short_description' => (string) ($_POST['short_description'] ?? ($baseProduct['short_description'] ?? '')),
            'category_id' => trim((string) ($_POST['category_id'] ?? ($baseProduct['category_id'] ?? ''))),
            'size_guide_id' => trim((string) ($_POST['size_guide_id'] ?? ($baseProduct['size_guide_id'] ?? ''))),
            'is_featured' => isset($_POST['is_featured']) ? 1 : (!empty($baseProduct['is_featured']) ? 1 : 0),
            'main_image' => $baseProduct['main_image'] ?? ($_POST['current_main_image'] ?? ''),
            'gallery_image_records' => $baseProduct['gallery_image_records'] ?? [],
            'gallery_images' => $baseProduct['gallery_images'] ?? [],
            'categories' => $categories,
            'selected_variation_tokens' => $this->getSelectedVariationTokensFromRequest(),
            'variant_stocks' => $this->parseVariantStocksFromRequest()
        ]);
    }

    private function getProductValidationError($isEdit = false, array $baseProduct = [])
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $price = trim((string) ($_POST['price'] ?? ''));
        $categoryId = trim((string) ($_POST['category_id'] ?? ''));

        if ($title === '') {
            return ['message' => 'Please enter the product title.', 'field' => 'title'];
        }

        if ($price === '') {
            return ['message' => 'Please enter the product price.', 'field' => 'price'];
        }

        if ($categoryId === '') {
            return ['message' => 'Please select at least one category.', 'field' => 'category_id'];
        }

        $existingMainImage = trim((string) ($baseProduct['main_image'] ?? ($_POST['current_main_image'] ?? '')));
        $mainImageMissing = empty($_FILES['main_image']['name']) && $existingMainImage === '';
        if (!$isEdit && $mainImageMissing) {
            return ['message' => 'Please upload the main product image.', 'field' => 'main_image'];
        }

        return null;
    }

    private function renderProductForm($mode, array $product, array $options = [])
    {
        $deps = $this->getProductFormDependencies();
        $viewData = array_merge($deps, [
            'title' => $mode === 'edit' ? 'Edit Product' : 'Add Product',
            'product' => $product
        ], $options);

        if ($mode === 'edit') {
            $viewData['mode'] = 'edit';
        }

        $this->view('admin/products/add', $viewData);
    }

    public function index()
    {
        $search = $_GET['search'] ?? null;
        $products = $this->productModel->getAll($search);
        $this->view('admin/products/index', [
            'title' => 'Products',
            'products' => $products
        ]);
    }

    public function delete($id)
    {
        // Delete files before DB record
        $product = $this->productModel->getById($id);
        
        if ($product) {
            // Delete Main Image
            if (!empty($product['main_image'])) {
                $this->deleteFile($product['main_image']);
            }

            // Delete Gallery Images
            $galleryImages = $this->productModel->getGalleryImages($id);
            if (!empty($galleryImages)) {
                foreach ($galleryImages as $img) {
                    $this->deleteFile($img);
                }
            }

            $variantRows = $this->productModel->getVariantStockRows($id);
            foreach ($variantRows as $variantRow) {
                if (!empty($variantRow['image_path'])) {
                    $this->deleteFile($variantRow['image_path']);
                }
            }
        }

        // Delete DB Record
        $this->productModel->delete($id);
        $this->redirect('product/index');
    }


    public function delete_all()
    {
        $this->productModel->deleteAll();
        $this->redirect('product/index');
    }

    public function cloneProduct($id)
    {
        $newProductId = $this->productModel->cloneById((int) $id);
        if ($newProductId > 0) {
            $this->stockAlertService->syncProductAlerts($newProductId);
        }
        $this->redirect('product/index');
    }

    public function add()
    {
        $this->renderProductForm('add', [
            'product' => [
                'price' => 0,
                'weight_grams' => 0,
                'stock_mode' => 'always_in_stock',
                'stock_qty' => 0,
                'low_stock_threshold' => 5,
                'manual_stock_status' => 'in_stock',
                'variant_stocks' => [],
                'selected_variation_tokens' => []
            ]
        ]['product']);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('product/add');
        }

        // 1. Check for POST Max Size Limit Exceeded
        // If the user uploads files larger than php.ini 'post_max_size', both $_POST and $_FILES will be empty.
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
            $maxPost = ini_get('post_max_size');
            $this->renderProductForm('add', [
                'price' => 0,
                'weight_grams' => 0,
                'stock_mode' => 'always_in_stock',
                'stock_qty' => 0,
                'low_stock_threshold' => 5,
                'manual_stock_status' => 'in_stock',
                'variant_stocks' => [],
                'selected_variation_tokens' => []
            ], [
                'product_form_error' => "The total size of your files exceeds the server limit ($maxPost). Please upload smaller files and try again.",
                'product_form_error_field' => 'main_image'
            ]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validationError = $this->getProductValidationError(false);
            if ($validationError) {
                $this->renderProductForm('add', $this->buildProductDraftFromRequest([
                    'price' => 0,
                    'weight_grams' => 0,
                    'stock_mode' => 'always_in_stock',
                    'stock_qty' => 0,
                    'low_stock_threshold' => 5,
                    'manual_stock_status' => 'in_stock',
                    'variant_stocks' => [],
                    'selected_variation_tokens' => []
                ]), [
                    'product_form_error' => $validationError['message'],
                    'product_form_error_field' => $validationError['field']
                ]);
                return;
            }

            $title = $_POST['title'] ?? '';
            $price = $_POST['price'] ?? '';
            $categoryId = $_POST['category_id'] ?? '';

            // 4. Handle Main Image
            $mainImagePath = isset($_FILES['main_image']) ? ImageHelper::storeUploadedFile($_FILES['main_image'], 'main') : '';

            // 5. Handle Gallery Images
            $galleryPaths = [];
            if (isset($_FILES['gallery_images'])) {
                $files = $_FILES['gallery_images'];
                $count = count($files['name']);

                for ($i = 0; $i < $count; $i++) {
                    if (!empty($files['name'][$i]) && $files['error'][$i] == 0) {
                        $storedName = ImageHelper::storeUploadedArrayFile($files, $i, 'gal_' . $i);
                        if ($storedName !== '') {
                            $galleryPaths[] = $storedName;
                        }
                    }
                }
            }

            // 6. Handle Variations
            $formattedVars = [];
            if (isset($_POST['selected_variations']) && is_array($_POST['selected_variations'])) {
                foreach ($_POST['selected_variations'] as $combo) {
                    $parts = explode('_', $combo);
                    if (count($parts) == 2) {
                        $formattedVars[] = [
                            'variation_id' => $parts[0],
                            'variation_value_id' => $parts[1]
                        ];
                    }
                }
            }

            // 7. Prepare Data
            $data = [
                'title' => $title,
                'sku' => $_POST['sku'] ?? '',
                'price' => $price,
                'sale_price' => !empty($_POST['sale_price']) ? $_POST['sale_price'] : null,
                'weight_grams' => max(0, (int) ($_POST['weight_grams'] ?? 0)),
                'free_shipping' => isset($_POST['free_shipping']),
                'stock_mode' => $_POST['stock_mode'] ?? 'always_in_stock',
                'stock_qty' => max(0, (int) ($_POST['stock_qty'] ?? 0)),
                'low_stock_threshold' => max(0, (int) ($_POST['low_stock_threshold'] ?? 5)),
                'manual_stock_status' => $_POST['manual_stock_status'] ?? 'in_stock',
                'description' => $_POST['description'] ?? '',
                'short_description' => $_POST['short_description'] ?? '',
                'category_id' => $categoryId,
                'size_guide_id' => !empty($_POST['size_guide_id']) ? $_POST['size_guide_id'] : null,
                'is_featured' => isset($_POST['is_featured']), // Checkbox sends 'on' or nothing
                'main_image' => $mainImagePath,
                'gallery_images' => $galleryPaths,
                'variations' => $formattedVars,
                'categories' => $_POST['categories'] ?? [], // Capture array
                'variant_stocks' => $this->buildVariantStocksForSave()
            ];


            // 8. Save
            $newProductId = (int) $this->productModel->create($data);
            if ($newProductId > 0) {
                $this->stockAlertService->syncProductAlerts($newProductId);
                $this->redirect('product/index');
            } else {
                $this->renderProductForm('add', $this->buildProductDraftFromRequest([
                    'price' => 0,
                    'weight_grams' => 0,
                    'stock_mode' => 'always_in_stock',
                    'stock_qty' => 0,
                    'low_stock_threshold' => 5,
                    'manual_stock_status' => 'in_stock',
                    'variant_stocks' => [],
                    'selected_variation_tokens' => []
                ]), [
                    'product_form_error' => 'There was an issue saving the product. Please make sure the product title is unique and try again.',
                    'product_form_error_field' => 'title'
                ]);
            }

        }

        $this->redirect('product/index');
    }

    public function edit($id)
    {
        $product = $this->productModel->getById($id);
        if (!$product) {
            $this->redirect('product/index');
        }

        // Get existing images and variations
        $product['gallery_images'] = $this->productModel->getGalleryImages($id);
        $product['gallery_image_records'] = $this->productModel->getGalleryImageRecords($id);
        $product['variations'] = $this->productModel->getVariations($id); // This returns grouped vars
        $product['variant_stocks'] = $this->productModel->getVariantStockRows($id);
        foreach ($product['variant_stocks'] as &$variantStock) {
            $variantStock['image_url'] = !empty($variantStock['image_path'])
                ? ImageHelper::uploadUrl($variantStock['image_path'], '')
                : '';
        }
        unset($variantStock);
        // We might need raw variation lines to pre-select, but let's see how the form expects it.
                // The form writes to hidden inputs 'selected_variations[]' as 'varId_valId'.
        // We need to reconstruct that list.

        // Get multi-categories
        $product['categories'] = $this->productModel->getProductCategoryIds($id);
        $product['selected_variation_tokens'] = array_map(function ($variation) {
            return (string) ($variation['variation_id'] ?? '') . '_' . (string) ($variation['variation_value_id'] ?? '');
        }, array_reduce(array_values($product['variant_stocks'] ?? []), function ($carry, $row) {
            foreach (($row['values'] ?? []) as $value) {
                $carry[] = $value;
            }
            return $carry;
        }, []));

        $this->renderProductForm('edit', $product);
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('product/index');
        }

        // 1. Check for POST Max Size Limit Exceeded
        // Mirrors logic from store() to prevent silent failures on large uploads
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
            $maxPost = ini_get('post_max_size');
            $id = $_POST['id'] ?? null;
            $existingProduct = !empty($id) ? $this->productModel->getById($id) : null;
            if ($existingProduct) {
                $existingProduct['gallery_images'] = $this->productModel->getGalleryImages($id);
                $existingProduct['gallery_image_records'] = $this->productModel->getGalleryImageRecords($id);
                $existingProduct['variations'] = $this->productModel->getVariations($id);
                $existingProduct['variant_stocks'] = $this->productModel->getVariantStockRows($id);
                $existingProduct['categories'] = $this->productModel->getProductCategoryIds($id);
                $this->renderProductForm('edit', $existingProduct, [
                    'product_form_error' => "The total size of your files exceeds the server limit ($maxPost). Please upload smaller files and try again.",
                    'product_form_error_field' => 'main_image'
                ]);
                return;
            }

            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $existingProduct = !empty($id) ? $this->productModel->getById($id) : null;
            if ($existingProduct) {
                $existingProduct['gallery_images'] = $this->productModel->getGalleryImages($id);
                $existingProduct['gallery_image_records'] = $this->productModel->getGalleryImageRecords($id);
                $existingProduct['variations'] = $this->productModel->getVariations($id);
                $existingProduct['variant_stocks'] = $this->productModel->getVariantStockRows($id);
                $existingProduct['categories'] = $this->productModel->getProductCategoryIds($id);
            }

            // 2. Validate ID and Required Fields
            // Strict check: we must have an ID to update
            if (empty($id)) {
                $this->renderProductForm('add', $this->buildProductDraftFromRequest(), [
                    'product_form_error' => 'Product ID is missing. Please open the product again and try updating it.',
                    'product_form_error_field' => 'title'
                ]);
                return;
            }
            if (!$existingProduct) {
                $this->redirect('product/index');
            }

            $validationError = $this->getProductValidationError(true, $existingProduct);
            if ($validationError) {
                $this->renderProductForm('edit', $this->buildProductDraftFromRequest($existingProduct), [
                    'product_form_error' => $validationError['message'],
                    'product_form_error_field' => $validationError['field']
                ]);
                return;
            }

            $title = $_POST['title'] ?? '';
            $price = $_POST['price'] ?? '';
            $categoryId = $_POST['category_id'] ?? '';

            // 4. Handle Main Image (Update only if new one provided)
            $mainImagePath = $_POST['current_main_image'] ?? '';
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
                $storedMain = ImageHelper::storeUploadedFile($_FILES['main_image'], 'main');
                if ($storedMain !== '') {
                    if (!empty($_POST['current_main_image'])) {
                        $this->deleteFile($_POST['current_main_image']);
                    }
                    $mainImagePath = $storedMain;
                }
            }

            // 5. Handle Gallery Images (Append new ones)
            $galleryPaths = [];
            if (isset($_FILES['gallery_images'])) {
                $files = $_FILES['gallery_images'];
                $count = count($files['name']);
                for ($i = 0; $i < $count; $i++) {
                    if (!empty($files['name'][$i]) && $files['error'][$i] == 0) {
                        $storedGallery = ImageHelper::storeUploadedArrayFile($files, $i, 'gal_' . $i);
                        if ($storedGallery !== '') {
                            $galleryPaths[] = $storedGallery;
                        }
                    }
                }
            }

            $removeGalleryImageIds = array_values(array_filter(array_map('intval', $_POST['remove_gallery_image_ids'] ?? [])));
            $galleryImagesToDelete = $this->productModel->getGalleryImageRecordsByIds($id, $removeGalleryImageIds);
            $existingVariantMap = $this->productModel->getVariantStockMap((int) $id);

            // 6. Handle Variations
            $formattedVars = [];
            if (isset($_POST['selected_variations']) && is_array($_POST['selected_variations'])) {
                foreach ($_POST['selected_variations'] as $combo) {
                    $parts = explode('_', $combo);
                    if (count($parts) == 2) {
                        $formattedVars[] = [
                            'variation_id' => $parts[0],
                            'variation_value_id' => $parts[1]
                        ];
                    }
                }
            }

            // 7. Prepare Data for Model
            $data = [
                'id' => $id,
                'title' => $title,
                'sku' => $_POST['sku'] ?? '',
                'price' => $price,
                'sale_price' => !empty($_POST['sale_price']) ? $_POST['sale_price'] : null,
                'weight_grams' => max(0, (int) ($_POST['weight_grams'] ?? 0)),
                'free_shipping' => isset($_POST['free_shipping']),
                'stock_mode' => $_POST['stock_mode'] ?? 'always_in_stock',
                'stock_qty' => max(0, (int) ($_POST['stock_qty'] ?? 0)),
                'low_stock_threshold' => max(0, (int) ($_POST['low_stock_threshold'] ?? 5)),
                'manual_stock_status' => $_POST['manual_stock_status'] ?? 'in_stock',
                'description' => $_POST['description'] ?? '',
                'short_description' => $_POST['short_description'] ?? '',
                'category_id' => $categoryId,
                'size_guide_id' => !empty($_POST['size_guide_id']) ? $_POST['size_guide_id'] : null,
                'is_featured' => isset($_POST['is_featured']), // Checkbox sends 'on' if checked
                'main_image' => $mainImagePath,
                'new_gallery_images' => $galleryPaths, // array of new paths to ADD
                'remove_gallery_image_ids' => $removeGalleryImageIds,
                'variations' => $formattedVars,
                'categories' => $_POST['categories'] ?? [],
                'variant_stocks' => $this->buildVariantStocksForSave()
            ];


            // 8. Execute Update
            // DEBUG: Trace Model Result
            $result = $this->productModel->update($data);
            // var_dump($result); die("Model Update Result");

            if ($result) {
                $this->stockAlertService->syncProductAlerts((int) $id);
                $newVariantImageMap = [];
                foreach (($data['variant_stocks'] ?? []) as $variantStock) {
                    $combinationKey = trim((string) ($variantStock['combination_key'] ?? ''));
                    if ($combinationKey === '') {
                        continue;
                    }
                    $newVariantImageMap[$combinationKey] = trim((string) ($variantStock['image_path'] ?? ''));
                }

                foreach ($existingVariantMap as $combinationKey => $variantRow) {
                    $oldImagePath = trim((string) ($variantRow['image_path'] ?? ''));
                    $newImagePath = $newVariantImageMap[$combinationKey] ?? '';
                    if ($oldImagePath !== '' && $oldImagePath !== $newImagePath) {
                        $this->deleteFile($oldImagePath);
                    }
                }

                foreach ($galleryImagesToDelete as $galleryImage) {
                    if (!empty($galleryImage['image_path'])) {
                        $this->deleteFile($galleryImage['image_path']);
                    }
                }
                $this->redirect('product/index');
            } else {
                $this->renderProductForm('edit', $this->buildProductDraftFromRequest($existingProduct), [
                    'product_form_error' => 'There was an issue updating the product. Please review the form and try again.',
                    'product_form_error_field' => 'title'
                ]);
            }

        }

        $this->redirect('product/index');
    }
        public function toggleActive($id)
    {
        $this->productModel->toggleActive($id);
        if (isset($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            $this->redirect('product/index');
        }
    }

}
?>

