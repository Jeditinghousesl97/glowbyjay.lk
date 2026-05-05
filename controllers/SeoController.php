<?php
require_once 'models/Category.php';
require_once 'models/Product.php';
require_once 'helpers/SeoHelper.php';

class SeoController extends BaseController
{
    private $categoryModel;
    private $productModel;

    public function __construct()
    {
        $this->categoryModel = null;
        $this->productModel = null;
    }

    private function safeCategories()
    {
        try {
            if (!$this->categoryModel instanceof Category) {
                $this->categoryModel = new Category();
            }

            $categories = $this->categoryModel->getAll();
            return is_array($categories) ? $categories : [];
        } catch (Throwable $e) {
            error_log('Sitemap category load failed: ' . $e->getMessage());
            return [];
        }
    }

    private function safeProducts()
    {
        try {
            if (!$this->productModel instanceof Product) {
                $this->productModel = new Product();
            }

            $products = $this->productModel->getAll();
            if (!is_array($products)) {
                return [];
            }

            return array_values(array_filter($products, static function ($product) {
                return !isset($product['is_active']) || (int) $product['is_active'] === 1;
            }));
        } catch (Throwable $e) {
            error_log('Sitemap product load failed: ' . $e->getMessage());
            return [];
        }
    }

    private function sitemapDate($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return gmdate('Y-m-d', $timestamp);
    }

    private function renderSitemapUrl($url, $lastmod = null, $changefreq = null, $priority = null)
    {
        if (!is_string($url) || trim($url) === '') {
            return;
        }

        echo "  <url>\n";
        echo '    <loc>' . htmlspecialchars($url, ENT_XML1, 'UTF-8') . "</loc>\n";

        if (!empty($lastmod)) {
            echo '    <lastmod>' . htmlspecialchars($lastmod, ENT_XML1, 'UTF-8') . "</lastmod>\n";
        }

        if (!empty($changefreq)) {
            echo '    <changefreq>' . htmlspecialchars($changefreq, ENT_XML1, 'UTF-8') . "</changefreq>\n";
        }

        if ($priority !== null) {
            echo '    <priority>' . htmlspecialchars(number_format((float) $priority, 1, '.', ''), ENT_XML1, 'UTF-8') . "</priority>\n";
        }

        echo "  </url>\n";
    }

    public function sitemap()
    {
        header('Content-Type: application/xml; charset=utf-8');

        $staticUrls = [
            ['loc' => SeoHelper::absoluteUrl(BASE_URL), 'changefreq' => 'daily', 'priority' => 1.0],
            ['loc' => SeoHelper::absoluteUrl(BASE_URL . 'shop'), 'changefreq' => 'daily', 'priority' => 0.9],
            ['loc' => SeoHelper::absoluteUrl(BASE_URL . 'shop/categories'), 'changefreq' => 'weekly', 'priority' => 0.8],
            ['loc' => SeoHelper::absoluteUrl(BASE_URL . 'shop/featured'), 'changefreq' => 'daily', 'priority' => 0.7],
            ['loc' => SeoHelper::absoluteUrl(BASE_URL . 'shop/sales'), 'changefreq' => 'daily', 'priority' => 0.7],
            ['loc' => SeoHelper::absoluteUrl(BASE_URL . 'discounts'), 'changefreq' => 'daily', 'priority' => 0.8],
            ['loc' => SeoHelper::absoluteUrl(BASE_URL . 'contact'), 'changefreq' => 'monthly', 'priority' => 0.5],
            ['loc' => SeoHelper::absoluteUrl(BASE_URL . 'page/refundReturns'), 'changefreq' => 'monthly', 'priority' => 0.3],
            ['loc' => SeoHelper::absoluteUrl(BASE_URL . 'page/termsConditions'), 'changefreq' => 'monthly', 'priority' => 0.3],
            ['loc' => SeoHelper::absoluteUrl(BASE_URL . 'page/privacyPolicy'), 'changefreq' => 'monthly', 'priority' => 0.3],
        ];

        $categories = $this->safeCategories();
        $products = $this->safeProducts();

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($staticUrls as $entry) {
            $this->renderSitemapUrl(
                $entry['loc'] ?? '',
                null,
                $entry['changefreq'] ?? null,
                $entry['priority'] ?? null
            );
        }

        foreach ($categories as $category) {
            $url = SeoHelper::absoluteUrl(BASE_URL . 'shop/category/' . $category['id']);
            $this->renderSitemapUrl(
                $url,
                $this->sitemapDate($category['updated_at'] ?? ($category['created_at'] ?? null)),
                'weekly',
                0.7
            );
        }

        foreach ($products as $product) {
            if (empty($product['id'])) {
                continue;
            }

            $this->renderSitemapUrl(
                SeoHelper::absoluteUrl(BASE_URL . 'shop/product/' . $product['id']),
                $this->sitemapDate($product['updated_at'] ?? ($product['created_at'] ?? null)),
                'weekly',
                0.6
            );
        }

        echo "</urlset>";
        exit;
    }

    public function robots()
    {
        header('Content-Type: text/plain; charset=utf-8');
        $basePath = '/' . trim((string) BASE_URL, '/');
        if ($basePath === '//') {
            $basePath = '/';
        }
        $basePath = rtrim($basePath, '/') . '/';

        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Disallow: " . $basePath . "cart\n";
        echo "Disallow: " . $basePath . "auth/\n";
        echo "Disallow: " . $basePath . "admin/\n";
        echo "Disallow: " . $basePath . "settings/\n";
        echo "Disallow: " . $basePath . "order/\n";
        echo "Sitemap: " . SeoHelper::absoluteUrl(BASE_URL . 'sitemap.xml') . "\n";
        exit;
    }
}
