<?php
require_once ROOT_PATH . 'helpers/SeoHelper.php';

class FooterHelper
{
    public static function brandSummary(array $settings): string
    {
        $about = trim((string) ($settings['shop_about'] ?? ''));
        if ($about !== '') {
            return $about;
        }

        $shopName = SeoHelper::shopName($settings);
        $slogan = SeoHelper::shopSlogan($settings);

        $fallback = 'Fresh curation, secure checkout, and a polished shopping experience.';
        if ($shopName === '' && $slogan === '') {
            return $fallback;
        }

        if ($shopName !== '' && $slogan !== '') {
            return $shopName . ' brings you ' . $slogan . '. ' . $fallback;
        }

        return trim(($shopName !== '' ? $shopName . ' delivers a refined shopping experience. ' : '') . $fallback);
    }

    public static function policyLinks(string $baseUrl): array
    {
        return [
            [
                'label' => 'Refund & Returns Policy',
                'url' => $baseUrl . 'page/refundReturns',
            ],
            [
                'label' => 'Terms & Conditions',
                'url' => $baseUrl . 'page/termsConditions',
            ],
            [
                'label' => 'Privacy Policy',
                'url' => $baseUrl . 'page/privacyPolicy',
            ],
        ];
    }

    public static function supportLinks(string $baseUrl): array
    {
        return [
            [
                'label' => 'My Orders',
                'url' => $baseUrl . 'order/myOrders',
            ],
            [
                'label' => 'Contact Us',
                'url' => $baseUrl . 'contact',
            ],
            [
                'label' => 'Browse Categories',
                'url' => $baseUrl . 'shop/categories',
            ],
        ];
    }

    public static function paymentMethods(array $settings): array
    {
        $definitions = [
            'payhere_enabled' => [
                'label' => 'PayHere',
                'file' => 'payhere.png',
            ],
            'koko_enabled' => [
                'label' => 'KOKO',
                'file' => 'koko.png',
            ],
            'cod_enabled' => [
                'label' => 'Cash on Delivery',
                'file' => 'cod.png',
            ],
            'bank_transfer_enabled' => [
                'label' => 'Bank Transfer',
                'file' => 'bank.png',
            ],
        ];

        $methods = [];
        foreach ($definitions as $settingKey => $definition) {
            if (empty($settings[$settingKey])) {
                continue;
            }

            $relativePath = 'assets/icons/payment-gateways/' . $definition['file'];
            $absolutePath = ROOT_PATH . $relativePath;

            $methods[] = [
                'label' => $definition['label'],
                'url' => BASE_URL . $relativePath . '?v=' . (@filemtime($absolutePath) ?: time()),
            ];
        }

        return $methods;
    }

    public static function whatsappDigits(array $settings): string
    {
        $raw = trim((string) ($settings['shop_whatsapp'] ?? ''));
        if ($raw === '') {
            $raw = trim((string) ($settings['social_whatsapp'] ?? ''));
        }

        return preg_replace('/[^0-9]/', '', $raw);
    }

    public static function whatsappLink(array $settings): string
    {
        $digits = self::whatsappDigits($settings);
        if ($digits === '') {
            return '';
        }

        return 'https://wa.me/' . $digits;
    }

    public static function whatsappMessageLink(array $settings, string $message = 'Hi, I need help with my order.'): string
    {
        $baseLink = self::whatsappLink($settings);
        if ($baseLink === '') {
            return '';
        }

        return $baseLink . '?text=' . rawurlencode($message);
    }

    public static function whatsappLabel(array $settings): string
    {
        $shopName = SeoHelper::shopName($settings);
        if ($shopName === '') {
            return 'WhatsApp';
        }

        return $shopName . ' WhatsApp';
    }

    public static function footerYear(): string
    {
        return date('Y');
    }

    public static function socialLinks(array $settings): array
    {
        $links = [];

        $whatsappDigits = self::whatsappDigits($settings);
        if ($whatsappDigits !== '') {
            $relativeIcon = 'assets/icons/whatsapp.png';
            $absoluteIcon = ROOT_PATH . $relativeIcon;
            $links[] = [
                'label' => 'WhatsApp',
                'url' => 'https://wa.me/' . $whatsappDigits,
                'icon' => BASE_URL . $relativeIcon . '?v=' . (@filemtime($absoluteIcon) ?: time()),
            ];
        }

        $definitions = [
            'social_fb' => ['label' => 'Facebook', 'icon' => 'facebook.png'],
            'social_insta' => ['label' => 'Instagram', 'icon' => 'instagram.png'],
            'social_tiktok' => ['label' => 'TikTok', 'icon' => 'tiktok.png'],
            'social_youtube' => ['label' => 'YouTube', 'icon' => 'youtube.png'],
        ];

        foreach ($definitions as $key => $meta) {
            $url = trim((string) ($settings[$key] ?? ''));
            if ($url === '') {
                continue;
            }

            if (!preg_match('#^https?://#i', $url)) {
                $url = 'https://' . ltrim($url, '/');
            }

            $relativeIcon = 'assets/icons/' . $meta['icon'];
            $absoluteIcon = ROOT_PATH . $relativeIcon;
            $links[] = [
                'label' => $meta['label'],
                'url' => $url,
                'icon' => BASE_URL . $relativeIcon . '?v=' . (@filemtime($absoluteIcon) ?: time()),
            ];
        }

        return $links;
    }
}
