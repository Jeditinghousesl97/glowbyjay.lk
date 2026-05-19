<?php
require_once ROOT_PATH . 'helpers/ImageHelper.php';

class SeoHelper
{
    public static function getSiteUrl()
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }

    public static function absoluteUrl($path = '')
    {
        if (empty($path)) {
            return self::getSiteUrl() . BASE_URL;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return rtrim(self::getSiteUrl(), '/') . '/' . ltrim($path, '/');
    }

    public static function currentUrl($includeQuery = true)
    {
        $uri = $_SERVER['REQUEST_URI'] ?? BASE_URL;
        if (!$includeQuery) {
            $uri = strtok($uri, '?');
        }
        return self::absoluteUrl($uri);
    }

    public static function normalizeAssetUrl($value)
    {
        if (empty($value)) {
            return '';
        }

        $value = ImageHelper::settingsImageUrl((string) $value, (string) $value);
        $value = str_replace('/Ecom-CMS/', BASE_URL, $value);

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        return self::absoluteUrl($value);
    }

    public static function normalizeExternalUrl($value)
    {
        if (empty($value)) {
            return '';
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        return 'https://' . ltrim($value, '/');
    }

    public static function productImageUrl($imageName)
    {
        if (empty($imageName)) {
            return '';
        }

        return self::absoluteUrl(ImageHelper::uploadUrl((string) $imageName, ''));
    }

    public static function trimText($text, $length = 160)
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)));
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($text) > $length ? mb_substr($text, 0, $length - 3) . '...' : $text;
        }

        return strlen($text) > $length ? substr($text, 0, $length - 3) . '...' : $text;
    }

    public static function currencyCodeFromSetting($currencyValue)
    {
        $value = strtoupper(trim((string) $currencyValue));
        if ($value === '' || $value === 'RS' || $value === 'LKR' || $value === 'RS.') {
            return 'LKR';
        }

        if ($value === '$' || $value === 'USD') {
            return 'USD';
        }

        if ($value === '€' || $value === 'EUR') {
            return 'EUR';
        }

        if ($value === '£' || $value === 'GBP') {
            return 'GBP';
        }

        if (preg_match('/^[A-Z]{3}$/', $value)) {
            return $value;
        }

        return 'LKR';
    }

    public static function shopName($settings)
    {
        return trim($settings['shop_name'] ?? 'Online Shop');
    }

    public static function shopSlogan($settings)
    {
        return trim($settings['shop_slogan'] ?? '');
    }

    public static function homeTitle($settings)
    {
        $shopName = self::shopName($settings);
        $shopSlogan = self::shopSlogan($settings);

        if ($shopName === '') {
            $shopName = 'Online Shop';
        }

        if ($shopSlogan === '') {
            return $shopName;
        }

        return $shopName . ' - ' . $shopSlogan;
    }

    public static function pageTitle($pageTitle, $settings)
    {
        $pageTitle = trim((string) $pageTitle);
        $shopName = self::shopName($settings);

        if ($pageTitle === '') {
            return $shopName !== '' ? $shopName : 'Online Shop';
        }

        if ($shopName === '') {
            return $pageTitle;
        }

        return $pageTitle . ' - ' . $shopName;
    }

    public static function defaultDescription($settings)
    {
        $parts = [];

        if (!empty($settings['shop_about'])) {
            $parts[] = self::trimText($settings['shop_about'], 110);
        }

        if (!empty($settings['shop_whatsapp'])) {
            $parts[] = 'Contact: ' . trim($settings['shop_whatsapp']);
        }

        return self::trimText(implode(' ', $parts), 160);
    }

    public static function defaultSeo($settings, $overrides = [])
    {
        $shopName = self::shopName($settings);

        $seo = [
            'seo_title' => $shopName,
            'seo_description' => self::defaultDescription($settings),
            'seo_canonical' => self::currentUrl(false),
            'seo_image' => self::normalizeAssetUrl($settings['shop_favicon'] ?? ($settings['shop_logo'] ?? '')),
            'seo_type' => 'website',
            'seo_robots' => 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
            'seo_json_ld' => []
        ];

        foreach ($overrides as $key => $value) {
            if ($value !== null && $value !== '') {
                $seo[$key] = $value;
            }
        }

        return $seo;
    }

    public static function buildOrganizationSchema($settings)
    {
        $sameAs = [];
        foreach (['social_fb', 'social_tiktok', 'social_insta', 'social_youtube'] as $socialKey) {
            if (!empty($settings[$socialKey])) {
                $sameAs[] = self::normalizeExternalUrl($settings[$socialKey]);
            }
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Store',
            'name' => self::shopName($settings),
            'url' => self::absoluteUrl(BASE_URL),
        ];

        $logo = self::normalizeAssetUrl($settings['shop_logo'] ?? '');
        if (!empty($logo)) {
            $schema['logo'] = $logo;
            $schema['image'] = $logo;
        }

        $description = self::defaultDescription($settings);
        if (!empty($description)) {
            $schema['description'] = $description;
        }

        if (!empty($settings['shop_whatsapp'])) {
            $schema['telephone'] = trim($settings['shop_whatsapp']);
        }

        if (!empty($sameAs)) {
            $schema['sameAs'] = $sameAs;
        }

        return $schema;
    }

    public static function buildWebsiteSchema($settings)
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => self::shopName($settings),
            'url' => self::absoluteUrl(BASE_URL),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => self::absoluteUrl(BASE_URL . 'shop?search={search_term_string}'),
                'query-input' => 'required name=search_term_string'
            ]
        ];
    }

    public static function buildBreadcrumbSchema($items)
    {
        $list = [];
        $position = 1;

        foreach ($items as $item) {
            if (empty($item['name']) || empty($item['url'])) {
                continue;
            }

            $list[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $item['name'],
                'item' => $item['url']
            ];
        }

        if (empty($list)) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list
        ];
    }

    public static function buildWebPageSchema($type, $name, $url, $description = '')
    {
        $type = trim((string) $type);
        $name = trim((string) $name);
        $url = trim((string) $url);
        $description = trim((string) $description);

        if ($type === '' || $name === '' || $url === '') {
            return null;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => $name,
            'url' => $url
        ];

        if ($description !== '') {
            $schema['description'] = $description;
        }

        return $schema;
    }

    public static function buildProductSchema($settings, $product, $imageUrl, $url)
    {
        $price = (!empty($product['sale_price']) && (float) $product['sale_price'] < (float) $product['price'])
            ? (float) $product['sale_price']
            : (float) $product['price'];

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product['title'] ?? self::shopName($settings),
            'description' => self::trimText($product['description'] ?? '', 500),
            'sku' => $product['sku'] ?? '',
            'url' => $url,
            'brand' => [
                '@type' => 'Brand',
                'name' => self::shopName($settings)
            ],
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => self::currencyCodeFromSetting($settings['currency_symbol'] ?? 'LKR'),
                'price' => number_format($price, 2, '.', ''),
                'availability' => !empty($product['is_active']) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => $url
            ]
        ];

        if (!empty($imageUrl)) {
            $schema['image'] = [$imageUrl];
        }

        if (!empty($product['category_name'])) {
            $schema['category'] = $product['category_name'];
        }

        if (!empty($settings['shop_whatsapp'])) {
            $schema['seller'] = [
                '@type' => 'Organization',
                'name' => self::shopName($settings),
                'telephone' => trim($settings['shop_whatsapp'])
            ];
        }

        return $schema;
    }
}
