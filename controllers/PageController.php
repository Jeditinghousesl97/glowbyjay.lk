<?php
/**
 * Static customer pages.
 */
require_once 'models/Settings.php';
require_once 'helpers/SeoHelper.php';

class PageController extends BaseController
{
    private $settingsModel;

    public function __construct()
    {
        $this->settingsModel = new Settings();
    }

    private function renderPolicyPage($title, $heading, $content)
    {
        $settings = $this->settingsModel->getAll();
        $shareImage = $settings['shop_logo'] ?? '';
        $shareDescription = trim(preg_replace('/\s+/', ' ', (string) $content));
        $shareDescription = function_exists('mb_substr')
            ? mb_substr($shareDescription, 0, 160)
            : substr($shareDescription, 0, 160);
        $slug = 'privacyPolicy';
        if ($heading === 'Refund & Returns Policy') {
            $slug = 'refundReturns';
        } elseif ($heading === 'Terms & Conditions') {
            $slug = 'termsConditions';
        }
        $pageUrl = SeoHelper::absoluteUrl(BASE_URL . 'page/' . $slug);
        $seo = SeoHelper::defaultSeo($settings, [
            'seo_title' => SeoHelper::pageTitle($heading, $settings),
            'seo_description' => SeoHelper::trimText($content, 160),
            'seo_canonical' => $pageUrl,
            'seo_image' => SeoHelper::normalizeAssetUrl($shareImage),
            'seo_json_ld' => [
                SeoHelper::buildOrganizationSchema($settings),
                SeoHelper::buildWebsiteSchema($settings),
                SeoHelper::buildBreadcrumbSchema([
                    ['name' => SeoHelper::shopName($settings), 'url' => SeoHelper::absoluteUrl(BASE_URL)],
                    ['name' => $heading, 'url' => $pageUrl]
                ])
            ]
        ]);

        $this->view('customer/policy_page', [
            'title' => $title,
            'settings' => $settings,
            'heading' => $heading,
            'content' => $content,
            'share_image' => $shareImage,
            'share_description' => $shareDescription,
            'seo_title' => $seo['seo_title'],
            'seo_description' => $seo['seo_description'],
            'seo_canonical' => $seo['seo_canonical'],
            'seo_image' => $seo['seo_image'],
            'seo_type' => $seo['seo_type'],
            'seo_robots' => $seo['seo_robots'],
            'seo_json_ld' => $seo['seo_json_ld'],
            'hide_mobile_welcome' => true,
            'current_page' => 'policy'
        ]);
    }

    public function refundReturns()
    {
        $settings = $this->settingsModel->getAll();
        $this->renderPolicyPage(
            'Refund & Returns Policy',
            'Refund & Returns Policy',
            $settings['refund_policy_content'] ?? "Returns are accepted for eligible items in original condition, unused, and with tags or packaging still attached.\n\nPlease contact the shop as soon as possible after delivery if you need to request a return, exchange, or report a damaged or incorrect item.\n\nApproved refunds are processed after the returned item is received and checked. Shipping or handling charges may be non-refundable unless the issue was caused by us.\n\nPersonalized items, worn items, clearance items, or products marked final sale may not be eligible for return unless they arrive damaged or incorrect."
        );
    }

    public function termsConditions()
    {
        $settings = $this->settingsModel->getAll();
        $this->renderPolicyPage(
            'Terms & Conditions',
            'Terms & Conditions',
            $settings['terms_conditions_content'] ?? "All orders are subject to confirmation, availability, and verification of payment details. We may update or cancel orders if required.\n\nWe do our best to display colors, sizes, and descriptions accurately, but slight differences may appear depending on screens, lighting, or stock updates.\n\nPrices, promotions, and product availability may change without prior notice. Obvious pricing or listing errors may be corrected at any time.\n\nBy using this website, you agree not to misuse the platform, attempt unauthorized access, or interfere with store operations or other users."
        );
    }

    public function privacyPolicy()
    {
        $settings = $this->settingsModel->getAll();
        $this->renderPolicyPage(
            'Privacy Policy',
            'Privacy Policy',
            $settings['privacy_policy_content'] ?? "We may collect details such as your name, contact number, address, and order information to process purchases and support your shopping experience.\n\nYour information is used to fulfill orders, communicate updates, respond to support requests, and improve store operations.\n\nWe take reasonable steps to protect your information and limit access to store-related purposes only.\n\nSome services such as payments, delivery, or analytics may involve trusted third parties. Their handling of information follows their own policies and applicable rules."
        );
    }
}
