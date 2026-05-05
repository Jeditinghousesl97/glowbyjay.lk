<?php
require_once 'models/Product.php';
require_once 'models/Setting.php';

class StockController extends BaseController
{
    private $productModel;
    private $settingModel;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->settingModel = new Setting();
    }

    private function requireAdminSession()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('auth/login');
        }
    }

    private function getReportFilters()
    {
        return [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'stock_state' => trim((string) ($_GET['stock_state'] ?? '')),
            'product_type' => trim((string) ($_GET['product_type'] ?? '')),
            'payment_status' => trim((string) ($_GET['payment_status'] ?? '')),
            'order_status' => trim((string) ($_GET['order_status'] ?? '')),
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? ''))
        ];
    }

    public function index()
    {
        $this->requireAdminSession();
        $overview = $this->productModel->getStockOverview();
        $settings = $this->settingModel->getAllPairs();

        $filter = trim((string) ($_GET['filter'] ?? ''));
        $products = array_values(array_filter($overview['products'], function ($product) use ($filter) {
            $snapshot = $product['stock_snapshot'] ?? [];
            if ($filter === 'out_of_stock') {
                return ($snapshot['status'] ?? '') === 'out_of_stock';
            }
            if ($filter === 'low_stock') {
                return ($snapshot['status'] ?? '') === 'low_stock';
            }
            if ($filter === 'variant') {
                return !empty($snapshot['has_variant_stock']);
            }
            return true;
        }));

        $this->view('admin/stock/index', [
            'title' => 'Stock Management',
            'settings' => $settings,
            'summary' => $overview['summary'],
            'products' => $products,
            'active_filter' => $filter
        ]);
    }

    public function report()
    {
        $this->requireAdminSession();
        $settings = $this->settingModel->getAllPairs();
        $filters = $this->getReportFilters();
        $report = $this->productModel->getStockReport($filters);

        $this->view('admin/stock/report', [
            'title' => 'Stock Report',
            'settings' => $settings,
            'filters' => $filters,
            'summary' => $report['summary'],
            'rows' => $report['rows'],
            'bestSeller' => $report['best_seller'],
            'topSellers' => $report['top_sellers'],
            'topRevenue' => $report['top_revenue'],
            'attentionProducts' => $report['attention_products'],
            'deadStock' => $report['dead_stock']
        ]);
    }

    public function exportReport()
    {
        $this->requireAdminSession();
        $filters = $this->getReportFilters();
        $report = $this->productModel->getStockReport($filters);
        $rows = $report['rows'] ?? [];

        $filename = 'stock_report_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, [
            'Product',
            'Variation',
            'SKU',
            'Category',
            'Product Type',
            'Stock Status',
            'Stock Mode',
            'Available Qty',
            'Low Stock Threshold',
            'Variant Count',
            'Selling Price',
            'Sale Price',
            'Weight (g)',
            'Inventory Value',
            'Units Sold',
            'Orders Count',
            'Sales Revenue',
            'Last Ordered At',
            'Product Active'
        ]);

        foreach ($rows as $row) {
            $productCsvRow = [
                $row['title'] ?? '',
                '',
                $row['sku'] ?? '',
                $row['category_name'] ?? '',
                ucfirst((string) ($row['product_type'] ?? 'simple')),
                ucwords(str_replace('_', ' ', (string) ($row['status'] ?? 'in_stock'))),
                ucwords(str_replace('_', ' ', (string) ($row['stock_mode'] ?? 'always_in_stock'))),
                $row['available_qty'] === null ? 'Unlimited / Manual' : (int) $row['available_qty'],
                (int) ($row['low_stock_threshold'] ?? 0),
                (int) ($row['variant_count'] ?? 0),
                number_format((float) ($row['effective_price'] ?? 0), 2, '.', ''),
                !empty($row['sale_price']) ? number_format((float) $row['sale_price'], 2, '.', '') : '',
                (int) ($row['weight_grams'] ?? 0),
                $row['inventory_value'] === null ? '' : number_format((float) $row['inventory_value'], 2, '.', ''),
                (int) ($row['units_sold'] ?? 0),
                (int) ($row['orders_count'] ?? 0),
                number_format((float) ($row['revenue_total'] ?? 0), 2, '.', ''),
                $row['last_ordered_at'] ?? '',
                !empty($row['is_active']) ? 'Yes' : 'No'
            ];

            fputcsv($output, $productCsvRow);

            foreach (($row['variant_rows'] ?? []) as $variantRow) {
                $variantInventoryValue = $variantRow['available_qty'] === null
                    ? ''
                    : number_format(((int) $variantRow['available_qty']) * (float) ($variantRow['effective_price'] ?? 0), 2, '.', '');

                fputcsv($output, [
                    $row['title'] ?? '',
                    $variantRow['combination_label'] ?? ($variantRow['combination_key'] ?? ''),
                    $variantRow['sku'] ?? '',
                    $row['category_name'] ?? '',
                    'Variant Item',
                    ucwords(str_replace('_', ' ', (string) ($variantRow['status'] ?? 'in_stock'))),
                    ucwords(str_replace('_', ' ', (string) ($variantRow['stock_mode'] ?? 'always_in_stock'))),
                    $variantRow['available_qty'] === null ? 'Unlimited / Manual' : (int) $variantRow['available_qty'],
                    (int) ($variantRow['low_stock_threshold'] ?? 0),
                    '',
                    $variantRow['variant_price'] !== null ? number_format((float) $variantRow['variant_price'], 2, '.', '') : '',
                    $variantRow['variant_sale_price'] !== null ? number_format((float) $variantRow['variant_sale_price'], 2, '.', '') : '',
                    (int) ($variantRow['variant_weight_grams'] ?? 0),
                    $variantInventoryValue,
                    '',
                    '',
                    '',
                    '',
                    !empty($variantRow['is_active']) ? 'Yes' : 'No'
                ]);
            }
        }

        fclose($output);
        exit;
    }
}
