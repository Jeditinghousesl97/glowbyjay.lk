<?php
if (!function_exists('customer_footer_render')) {
    function customer_footer_render(array $settings, string $baseUrl): void
    {
        $shopName = SeoHelper::shopName($settings);
        if ($shopName === '') {
            $shopName = 'Online Shop';
        }
        $shopLogoUrl = ImageHelper::settingsImageUrl(
            (string) ($settings['footer_logo'] ?? $settings['shop_logo'] ?? ''),
            'assets/uploads/1774110158_logo_logo.jpg'
        );

        $brandSummary = FooterHelper::brandSummary($settings);
        $policyLinks = FooterHelper::policyLinks($baseUrl);
        $supportLinks = FooterHelper::supportLinks($baseUrl);
        $paymentMethods = FooterHelper::paymentMethods($settings);
        $socialLinks = FooterHelper::socialLinks($settings);
        $whatsappDigits = FooterHelper::whatsappDigits($settings);
        $whatsappLink = FooterHelper::whatsappMessageLink($settings, 'Hello, I’m currently visiting your web store and would like more information. Is anyone available to assist me?');
        $whatsappLabel = FooterHelper::whatsappLabel($settings);
        $cartCount = 0;
        if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $cartCount += max(1, (int) ($item['qty'] ?? 0));
            }
        }
        $currentRoute = trim((string) ($_GET['url'] ?? ''), '/');
        if ($currentRoute === '') {
            $currentRoute = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
        }
        if ($currentRoute === 'index.php' || $currentRoute === 'home' || $currentRoute === 'home2') {
            $currentRoute = '';
        }
        $mobileNavItems = [
            ['label' => 'Home', 'url' => $baseUrl, 'icon' => 'fa-house', 'active' => $currentRoute === ''],
            ['label' => 'Shop', 'url' => $baseUrl . 'shop', 'icon' => 'fa-bag-shopping', 'active' => strpos($currentRoute, 'shop') === 0],
            ['label' => 'Categories', 'url' => $baseUrl . 'shop/categories', 'icon' => 'fa-border-all', 'active' => strpos($currentRoute, 'shop/categories') === 0],
            ['label' => 'Deals', 'url' => $baseUrl . 'discounts', 'icon' => 'fa-tags', 'active' => strpos($currentRoute, 'discounts') === 0],
            ['label' => 'Cart', 'url' => $baseUrl . 'cart', 'icon' => 'fa-cart-shopping', 'active' => strpos($currentRoute, 'cart') === 0, 'badge' => $cartCount],
        ];
        $cartBg = trim((string) ($settings['floating_cart_bg'] ?? '#7c4af0')) ?: '#7c4af0';
        $cartText = trim((string) ($settings['floating_cart_text'] ?? '#ffffff')) ?: '#ffffff';
        $footerYear = FooterHelper::footerYear();
        $reviewImages = [];
        try {
            require_once ROOT_PATH . 'models/Feedback.php';
            require_once ROOT_PATH . 'models/Setting.php';
            $feedbackModel = new Feedback();
            $settingModel = new Setting();
            $rows = $feedbackModel->getAll();
            $savedOrderRaw = (string) $settingModel->get('review_slider_order', '[]');
            $savedOrder = json_decode($savedOrderRaw, true);
            if (is_array($savedOrder) && !empty($savedOrder)) {
                $positionMap = [];
                foreach ($savedOrder as $index => $id) {
                    $positionMap[(int) $id] = (int) $index;
                }
                usort($rows, static function ($a, $b) use ($positionMap) {
                    $idA = (int) ($a['id'] ?? 0);
                    $idB = (int) ($b['id'] ?? 0);
                    $hasA = array_key_exists($idA, $positionMap);
                    $hasB = array_key_exists($idB, $positionMap);
                    if ($hasA && $hasB) return $positionMap[$idA] <=> $positionMap[$idB];
                    if ($hasA) return -1;
                    if ($hasB) return 1;
                    return strtotime((string) ($b['created_at'] ?? '')) <=> strtotime((string) ($a['created_at'] ?? ''));
                });
            }
            foreach ($rows as $row) {
                $path = trim((string) ($row['image_path'] ?? ''));
                if ($path !== '') {
                    $reviewImages[] = $path;
                }
            }
        } catch (Throwable $e) {
            $reviewImages = [];
        }
        ?>
        <?php if (!empty($reviewImages)): ?>
            <section class="site-prefooter-reviews" aria-label="Customer reviews">
                <div class="site-prefooter-reviews-shell">
                <div class="site-prefooter-reviews-head">
                        <div>
                            <h2>Customer Reviews</h2>
                            <p>Real WhatsApp feedback from our happy customers.</p>
                        </div>
                        <div class="site-prefooter-reviews-actions" aria-label="Review slider controls">
                            <button class="site-prefooter-reviews-nav prev" type="button" data-review-slider-prev aria-label="Previous reviews">
                                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                            </button>
                            <button class="site-prefooter-reviews-nav next" type="button" data-review-slider-next aria-label="Next reviews">
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </button>
                        </div>
                </div>
                <div class="site-prefooter-reviews-slider-wrap">
                        <div class="site-prefooter-reviews-slider" data-review-slider>
                            <?php foreach ($reviewImages as $reviewImage): ?>
                                <?php
                                $reviewImageUrl = ImageHelper::uploadUrl($reviewImage, '');
                                ?>
                                <article class="site-prefooter-review-card">
                                    <?= ImageHelper::renderResponsivePicture(
                                        $reviewImage,
                                        $reviewImageUrl,
                                        [
                                            'alt' => 'Customer review screenshot',
                                            'class' => 'site-prefooter-review-image',
                                            'data-review-image' => '1',
                                            'loading' => 'lazy',
                                            'decoding' => 'async',
                                            'fetchpriority' => 'low'
                                        ],
                                        'product_gallery'
                                    ) ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
        <footer class="site-footer" aria-label="Site footer">
            <div class="site-footer-shell">
                <div class="site-footer-top-badge-image">
                    <img
                        src="<?= htmlspecialchars($baseUrl . 'assets/footer-badges.png?v=' . (@filemtime(ROOT_PATH . 'assets/footer-badges.png') ?: time())) ?>"
                        alt="Store trust badges"
                        loading="lazy"
                        decoding="async">
                </div>
                <div class="site-footer-grid">
                    <section class="site-footer-brand">
                        <span class="site-footer-eyebrow">Brand Story</span>
                        <a class="site-footer-brand-logo" href="<?= htmlspecialchars($baseUrl) ?>" aria-label="<?= htmlspecialchars($shopName) ?>">
                            <img src="<?= htmlspecialchars($shopLogoUrl) ?>" alt="<?= htmlspecialchars($shopName) ?>">
                        </a>
                        <p class="site-footer-copy"><?= htmlspecialchars($brandSummary) ?></p>
                        <?php if (!empty($socialLinks)): ?>
                            <div class="site-footer-social" aria-label="Social media links">
                                <?php foreach ($socialLinks as $social): ?>
                                    <a
                                        class="site-footer-social-link"
                                        href="<?= htmlspecialchars($social['url']) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="<?= htmlspecialchars($social['label']) ?>">
                                        <img src="<?= htmlspecialchars($social['icon']) ?>" alt="<?= htmlspecialchars($social['label']) ?>">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="site-footer-column">
                        <span class="site-footer-eyebrow">Policies</span>
                        <nav class="site-footer-links" aria-label="Policies">
                            <?php foreach ($policyLinks as $link): ?>
                                <a href="<?= htmlspecialchars($link['url']) ?>"><?= htmlspecialchars($link['label']) ?></a>
                            <?php endforeach; ?>
                        </nav>
                    </section>

                    <section class="site-footer-column">
                        <span class="site-footer-eyebrow">Support</span>
                        <nav class="site-footer-links" aria-label="Support">
                            <?php foreach ($supportLinks as $link): ?>
                                <a href="<?= htmlspecialchars($link['url']) ?>"><?= htmlspecialchars($link['label']) ?></a>
                            <?php endforeach; ?>
                        </nav>
                    </section>

                    <section class="site-footer-payments">
                        <span class="site-footer-eyebrow">Secure Payment Methods</span>
                        <p class="site-footer-note">We offer a variety of secure payment options to suit your needs.</p>
                        <div class="site-footer-payment-grid">
                            <?php if (!empty($paymentMethods)): ?>
                                <?php foreach ($paymentMethods as $method): ?>
                                    <span class="site-footer-payment-card">
                                        <img src="<?= htmlspecialchars($method['url']) ?>" alt="<?= htmlspecialchars($method['label']) ?>">
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="site-footer-empty">No payment methods enabled yet.</div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>

                <div class="site-footer-bottom">
                    <div class="site-footer-copyright">
                        <?= htmlspecialchars($footerYear . ' ' . $shopName . '. All rights reserved.') ?> | Built with <a href="https://www.asseminate.com" target="_blank" rel="noopener noreferrer">Asseminate</a>
                    </div>
                    <div class="site-footer-badges" aria-label="Footer highlights">
                        <span>Secure</span>
                        <span>Trusted</span>
                        <span>Support</span>
                    </div>
                </div>
            </div>
        </footer>
        <nav class="site-mobile-footer-nav" aria-label="Mobile footer navigation">
            <?php foreach ($mobileNavItems as $item): ?>
                <a class="site-mobile-footer-nav-item <?= !empty($item['active']) ? 'active' : '' ?>" href="<?= htmlspecialchars($item['url']) ?>">
                    <span class="site-mobile-footer-nav-icon" aria-hidden="true">
                        <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>
                        <?php if (($item['label'] ?? '') === 'Cart'): ?>
                            <span class="site-mobile-footer-nav-badge" data-mobile-cart-badge style="<?= !empty($item['badge']) && (int) $item['badge'] > 0 ? '' : 'display:none;' ?>"><?= (int) ($item['badge'] ?? 0) ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="site-mobile-footer-nav-label"><?= htmlspecialchars($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php if ($whatsappDigits !== '' && $whatsappLink !== ''): ?>
            <div class="site-floating-contact" data-floating-contact>
                <div class="site-floating-contact-panel" id="siteFloatingContactPanel" data-floating-contact-panel aria-hidden="true">
                    <div class="site-floating-contact-head">
                        <span class="site-floating-contact-kicker">Quick Contact</span>
                        <strong>Need help?</strong>
                        <p>Choose a support option below and continue without leaving the page.</p>
                    </div>
                    <div class="site-floating-contact-links">
                        <a
                            class="site-floating-contact-link whatsapp"
                            href="<?= htmlspecialchars($whatsappLink) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="<?= htmlspecialchars($whatsappLabel) ?>">
                            <span class="site-floating-contact-icon" aria-hidden="true"><i class="fa-brands fa-whatsapp"></i></span>
                            <span>
                                <strong>WhatsApp</strong>
                                <small>Hello, I’m currently visiting your web store and would like more information. Is anyone available to assist me?</small>
                            </span>
                        </a>
                        <?php foreach ($supportLinks as $link): ?>
                            <a class="site-floating-contact-link" href="<?= htmlspecialchars($link['url']) ?>">
                                <span class="site-floating-contact-icon" aria-hidden="true"><i class="fa-solid fa-circle-info"></i></span>
                                <span>
                                    <strong><?= htmlspecialchars($link['label']) ?></strong>
                                    <small>Open support page</small>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button
                    class="site-floating-contact-toggle"
                    type="button"
                    data-floating-contact-toggle
                    aria-expanded="false"
                    aria-controls="siteFloatingContactPanel"
                    aria-label="Open quick contact menu">
                    <img
                        class="site-floating-contact-toggle-image"
                        src="<?= htmlspecialchars($baseUrl . 'assets/whatsapp-floating-button.png?v=' . (@filemtime(ROOT_PATH . 'assets/whatsapp-floating-button.png') ?: time())) ?>"
                        alt="WhatsApp">
                </button>
            </div>
        <?php endif; ?>
        <?php if ($cartCount > 0): ?>
            <div class="site-floating-cart" data-floating-cart style="--floating-cart-bg: <?= htmlspecialchars($cartBg) ?>; --floating-cart-text: <?= htmlspecialchars($cartText) ?>;">
                <a href="<?= htmlspecialchars($baseUrl . 'cart') ?>" class="site-floating-cart-button" aria-label="Open cart">
                    <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                    <span class="site-floating-cart-label">Cart</span>
                    <span class="site-floating-cart-badge" data-cart-count-badge><?= (int) $cartCount ?></span>
                </a>
            </div>
        <?php else: ?>
            <div class="site-floating-cart" data-floating-cart style="display:none; --floating-cart-bg: <?= htmlspecialchars($cartBg) ?>; --floating-cart-text: <?= htmlspecialchars($cartText) ?>;">
                <a href="<?= htmlspecialchars($baseUrl . 'cart') ?>" class="site-floating-cart-button" aria-label="Open cart">
                    <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                    <span class="site-floating-cart-label">Cart</span>
                    <span class="site-floating-cart-badge" data-cart-count-badge style="display:none;">0</span>
                </a>
            </div>
        <?php endif; ?>
        <div class="site-review-lightbox" data-review-lightbox aria-hidden="true">
            <button type="button" class="site-review-lightbox-close" data-review-lightbox-close aria-label="Close review viewer">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
            <button type="button" class="site-review-lightbox-nav prev" data-review-lightbox-prev aria-label="Previous review">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </button>
            <div class="site-review-lightbox-stage">
                <img src="" alt="Customer review full view" data-review-lightbox-image>
            </div>
            <button type="button" class="site-review-lightbox-nav next" data-review-lightbox-next aria-label="Next review">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
        <?php
        ?>
        <style>
            *, *::before, *::after {
                border-radius: 0 !important;
            }
            .site-floating-contact{
                position:fixed;
                right:20px;
                bottom:20px;
                z-index:90;
                display:grid;
                justify-items:end;
                gap:12px;
            }
            .site-floating-contact-panel{
                width:min(320px,calc(100vw - 28px));
                padding:16px;
                border:1px solid rgba(31,31,31,.08);
                background:#fff;
                box-shadow:0 18px 44px rgba(31,31,31,.16);
                display:none;
            }
            .site-floating-contact.open .site-floating-contact-panel{
                display:grid;
                gap:14px;
            }
            .site-floating-contact-head{
                display:grid;
                gap:6px;
            }
            .site-floating-contact-kicker{
                font-size:10px;
                letter-spacing:.24em;
                text-transform:uppercase;
                color:var(--footer-link);
                font-weight:800;
            }
            .site-floating-contact-head strong{
                font-size:20px;
                line-height:1.1;
                color:#1f1f1f;
            }
            .site-floating-contact-head p{
                margin:0;
                color:#6d6665;
                font-size:13px;
                line-height:1.7;
            }
            .site-floating-contact-links{
                display:grid;
                gap:10px;
            }
            .site-floating-contact-link{
                display:flex;
                align-items:center;
                gap:12px;
                padding:12px;
                border:1px solid rgba(31,31,31,.08);
                background:#fff;
                color:#1f1f1f;
                text-decoration:none;
                transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease;
            }
            .site-floating-contact-link:hover{
                transform:translateY(-1px);
                border-color:var(--footer-link);
                box-shadow:0 10px 22px rgba(31,31,31,.08);
            }
            .site-floating-contact-link strong{
                display:block;
                font-size:12px;
                letter-spacing:.18em;
                text-transform:uppercase;
            }
            .site-floating-contact-link small{
                display:block;
                margin-top:4px;
                color:#6d6665;
                font-size:11px;
                line-height:1.4;
            }
            .site-floating-contact-link.whatsapp{
                background:linear-gradient(135deg,#25d366 0%,#128c7e 100%);
                color:#fff;
                border-color:rgba(37,211,102,.18);
            }
            .site-floating-contact-link.whatsapp small{
                color:rgba(255,255,255,.86);
            }
            .site-floating-contact-icon{
                width:34px;
                height:34px;
                display:inline-flex;
                align-items:center;
                justify-content:center;
                flex-shrink:0;
                background:rgba(255,255,255,.16);
            }
            .site-floating-contact-link:not(.whatsapp) .site-floating-contact-icon{
                background:#f6f3f2;
                color:#1f1f1f;
            }
            .site-floating-contact-icon i{
                font-size:17px;
                line-height:1;
            }
            .site-floating-contact-toggle{
                display:inline-flex;
                align-items:center;
                justify-content:center;
                padding:0;
                border:0;
                background:transparent;
                box-shadow:none;
                cursor:pointer;
                transition:transform .2s ease, filter .2s ease;
            }
            .site-floating-contact-toggle:hover{
                transform:translateY(-2px);
                filter:saturate(1.05);
            }
            .site-floating-contact-toggle-image{
                display:block;
                width:170px;
                max-width:42vw;
                height:auto;
            }
            .site-footer-brand-logo{
                display:inline-flex;
                align-items:center;
                justify-content:flex-start;
                margin:0 0 14px;
                max-width:300px;
            }
            .site-footer-brand-logo img{
                display:block;
                width:auto;
                max-width:100%;
                max-height:96px;
                object-fit:contain;
            }
            .site-footer-social{
                display:flex;
                align-items:center;
                gap:10px;
                margin-top:14px;
                flex-wrap:wrap;
            }
            .site-footer-social-link{
                display:inline-flex;
                align-items:center;
                justify-content:center;
                transition:transform .2s ease;
            }
            .site-footer-social-link:hover{
                transform:translateY(-1px);
            }
            .site-footer-social-link img{
                width:30px;
                height:30px;
                object-fit:contain;
                display:block;
            }
            .site-floating-cart{
                position:fixed;
                left:20px;
                bottom:92px;
                z-index:89;
                display:grid;
                justify-items:start;
            }
            .site-floating-cart-button{
                display:inline-flex;
                align-items:center;
                gap:10px;
                min-height:54px;
                padding:0 16px 0 14px;
                background:#d4af37 !important;
                color:#ffffff !important;
                text-decoration:none;
                box-shadow:0 18px 38px rgba(31,31,31,.18);
                border:1px solid rgba(0,0,0,.08);
                transition:transform .2s ease, box-shadow .2s ease, filter .2s ease;
            }
            .site-floating-cart-button:hover{
                transform:translateY(-2px);
                box-shadow:0 22px 44px rgba(31,31,31,.22);
                filter:saturate(1.03);
            }
            .site-floating-cart-button i{
                font-size:17px;
                line-height:1;
            }
            .site-floating-cart-label{
                font-size:10px;
                font-weight:800;
                letter-spacing:.18em;
                text-transform:uppercase;
                white-space:nowrap;
            }
            .site-floating-cart-badge{
                min-width:22px;
                height:22px;
                padding:0 6px;
                border-radius:999px;
                background:rgba(255,255,255,.18);
                color:var(--floating-cart-text);
                font-size:11px;
                font-weight:800;
                line-height:22px;
                text-align:center;
            }
            .site-mobile-footer-nav{
                display:none;
            }
            .site-prefooter-reviews{
                background:#f2f2f2;
                border-top:1px solid rgba(0,0,0,.05);
            }
            .site-prefooter-reviews-shell{
                width:min(1600px,calc(100% - 80px));
                margin:0 auto;
                padding:88px 0;
            }
            .site-prefooter-reviews-head h2{
                margin:0;
                font-family:Manrope, sans-serif;
                font-weight:900;
                font-style:normal;
                font-size:24px;
                line-height:24px;
                text-transform:uppercase;
                color:rgb(36, 24, 15);
            }
            .site-prefooter-reviews-head{
                display:flex;
                align-items:flex-end;
                justify-content:space-between;
                gap:16px;
            }
            .site-prefooter-reviews-head p{
                margin:8px 0 0;
                color:#6d6665;
                font-size:14px;
            }
            .site-prefooter-reviews-actions{
                display:flex;
                gap:10px;
                margin-left:auto;
            }
            .site-prefooter-reviews-slider-wrap{
                margin-top:18px;
                position:relative;
                display:grid;
                align-items:center;
            }
            .site-prefooter-reviews-slider{
                display:grid;
                grid-auto-flow:column;
                grid-auto-columns:minmax(220px, 320px);
                gap:14px;
                overflow-x:auto;
                scroll-snap-type:x mandatory;
                scrollbar-width:none;
                padding:2px 0;
            }
            .site-prefooter-reviews-slider::-webkit-scrollbar{
                display:none;
            }
            .site-prefooter-review-card{
                scroll-snap-align:start;
                background:#fff;
                border:1px solid rgba(0,0,0,.06);
                box-shadow:0 8px 24px rgba(0,0,0,.06);
            }
            .site-prefooter-review-card img{
                width:100%;
                height:100%;
                object-fit:cover;
                background:#f4f4f4;
                display:block;
            }
            .site-prefooter-review-card picture{
                display:block;
                width:100%;
                height:100%;
            }
            .site-prefooter-review-card{
                display:flex;
                align-items:center;
                justify-content:center;
                aspect-ratio:3 / 4;
            }
            .site-prefooter-reviews-nav{
                position:static;
                width:46px;
                height:46px;
                border:1px solid rgba(0,0,0,.12);
                background:#fff;
                color:#1c1b1b;
                cursor:pointer;
                display:inline-flex;
                align-items:center;
                justify-content:center;
            }
            .site-prefooter-reviews-nav i{ font-size:14px; }
            .site-review-lightbox{
                position:fixed;
                inset:0;
                background:rgba(15,15,15,.92);
                z-index:160;
                display:none;
                align-items:center;
                justify-content:center;
                padding:22px;
            }
            .site-review-lightbox.open{
                display:flex;
            }
            .site-review-lightbox-stage{
                width:min(1100px, calc(100vw - 140px));
                height:min(88vh, 900px);
                display:flex;
                align-items:center;
                justify-content:center;
            }
            .site-review-lightbox-stage img{
                max-width:100%;
                max-height:100%;
                width:auto;
                height:auto;
                object-fit:contain;
                display:block;
            }
            .site-review-lightbox-close{
                position:absolute;
                top:16px;
                right:16px;
                width:42px;
                height:42px;
                border:1px solid rgba(255,255,255,.24);
                background:rgba(255,255,255,.12);
                color:#fff;
                cursor:pointer;
            }
            .site-review-lightbox-nav{
                width:44px;
                height:44px;
                border:1px solid rgba(255,255,255,.24);
                background:rgba(255,255,255,.12);
                color:#fff;
                cursor:pointer;
            }
            .site-review-lightbox-nav.prev{ margin-right:16px; }
            .site-review-lightbox-nav.next{ margin-left:16px; }
            @media (max-width: 760px){
                .site-content{
                    padding-bottom:84px;
                }
                .site-prefooter-reviews-shell{
                    width:100%;
                    max-width:none;
                    padding:64px 0;
                    padding-left:12px;
                    padding-right:12px;
                }
                .site-prefooter-reviews-head h2{
                    font-size:24px;
                    line-height:24px;
                }
                .site-prefooter-reviews-head{
                    align-items:flex-start;
                }
                .site-prefooter-reviews-head p{
                    font-size:13px;
                }
                .site-prefooter-reviews-slider{
                    grid-auto-columns:minmax(180px, 74vw);
                    gap:10px;
                    padding:2px 0;
                }
                .site-prefooter-review-card img{
                    height:100%;
                }
                .site-prefooter-reviews-nav{
                    display:none;
                }
                .site-review-lightbox{
                    padding:8px;
                }
                .site-review-lightbox-stage{
                    width:calc(100vw - 16px);
                    height:calc(100vh - 110px);
                }
                .site-review-lightbox-nav{
                    width:38px;
                    height:38px;
                }
                .site-review-lightbox-nav.prev{ margin-right:8px; }
                .site-review-lightbox-nav.next{ margin-left:8px; }
                .site-review-lightbox-close{
                    top:8px;
                    right:8px;
                    width:38px;
                    height:38px;
                }
                .site-floating-contact{
                    right:14px;
                    bottom:88px;
                    gap:10px;
                }
                .site-floating-contact-panel{
                    width:min(100vw - 28px,320px);
                    padding:14px;
                }
                .site-floating-contact-toggle{
                    padding:0;
                }
                .site-floating-contact-toggle-image{
                    width:150px;
                    max-width:48vw;
                }
                .site-footer-brand-logo{
                    max-width:240px;
                    margin-bottom:12px;
                }
                .site-footer-brand-logo img{
                    max-height:78px;
                }
                .site-floating-cart{
                    right:14px;
                    bottom:140px;
                    display:none !important;
                }
                .site-floating-cart-button{
                    min-height:44px;
                    padding:0 12px 0 10px;
                    gap:8px;
                }
                .site-floating-cart-label{
                    display:none;
                }
                .site-floating-cart-button i{
                    font-size:16px;
                }
                .site-floating-cart-badge{
                    min-width:20px;
                    height:20px;
                    padding:0 5px;
                    font-size:10px;
                    line-height:20px;
                }
                .site-mobile-footer-nav{
                    position:fixed;
                    left:0;
                    right:0;
                    bottom:0;
                    z-index:86;
                    display:grid;
                    grid-template-columns:repeat(5,minmax(0,1fr));
                    gap:0;
                    background:var(--nav-mobile-bg);
                    border-top:1px solid var(--line);
                    box-shadow:0 -10px 26px rgba(31,31,31,.08);
                    padding:8px 6px calc(8px + env(safe-area-inset-bottom));
                }
                .site-mobile-footer-nav-item{
                    display:flex;
                    flex-direction:column;
                    align-items:center;
                    justify-content:center;
                    gap:6px;
                    min-width:0;
                    color:#6d6665;
                    text-decoration:none;
                    text-align:center;
                    font-size:9px;
                    font-weight:500;
                    letter-spacing:.12em;
                    text-transform:uppercase;
                }
                .site-mobile-footer-nav-label{
                    display:block;
                    max-width:100%;
                    overflow:hidden;
                    text-overflow:ellipsis;
                    white-space:nowrap;
                }
                .site-mobile-footer-nav-item.active{
                    color:var(--nav-mobile-active);
                }
                .site-mobile-footer-nav-icon{
                    position:relative;
                    display:inline-flex;
                    align-items:center;
                    justify-content:center;
                    width:24px;
                    height:24px;
                    color:inherit;
                }
                .site-mobile-footer-nav-icon i{
                    font-size:16px;
                    opacity:.88;
                    line-height:1;
                    color:var(--nav-mobile-icon);
                }
                .site-mobile-footer-nav-badge{
                    position:absolute;
                    top:-6px;
                    right:-8px;
                    min-width:18px;
                    height:18px;
                    padding:0 5px;
                    border-radius:999px;
                    background:#d4af37;
                    color:#fff;
                    font-size:9px;
                    line-height:18px;
                    text-align:center;
                }
                .site-mobile-footer-nav-item.active .site-mobile-footer-nav-icon i{
                    color:var(--nav-mobile-active);
                    opacity:1;
                }
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const widget = document.querySelector('[data-floating-contact]');
                const toggle = document.querySelector('[data-floating-contact-toggle]');
                const panel = document.querySelector('[data-floating-contact-panel]');

                if (!widget || !toggle || !panel) {
                    return;
                }

                const closeWidget = function () {
                    widget.classList.remove('open');
                    toggle.setAttribute('aria-expanded', 'false');
                    panel.setAttribute('aria-hidden', 'true');
                };

                toggle.addEventListener('click', function (event) {
                    event.preventDefault();
                    const isOpen = widget.classList.toggle('open');
                    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    panel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
                });

                document.addEventListener('click', function (event) {
                    if (!widget.classList.contains('open')) {
                        return;
                    }

                    if (widget.contains(event.target)) {
                        return;
                    }

                    closeWidget();
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        closeWidget();
                    }
                });

                if (typeof window.updateCartUi === 'function') {
                    const initialBadge = document.querySelector('[data-cart-count-badge]');
                    const initialCount = initialBadge ? parseInt(initialBadge.textContent || '0', 10) || 0 : 0;
                    window.updateCartUi(initialCount);
                }

                const reviewSlider = document.querySelector('[data-review-slider]');
                const reviewPrev = document.querySelector('[data-review-slider-prev]');
                const reviewNext = document.querySelector('[data-review-slider-next]');
                if (reviewSlider && reviewPrev && reviewNext) {
                    const slideStep = function () {
                        const card = reviewSlider.querySelector('.site-prefooter-review-card');
                        if (!card) {
                            return 280;
                        }
                        const gap = parseInt(window.getComputedStyle(reviewSlider).columnGap || '14', 10) || 14;
                        return card.getBoundingClientRect().width + gap;
                    };
                    reviewPrev.addEventListener('click', function () {
                        reviewSlider.scrollBy({ left: -slideStep(), behavior: 'smooth' });
                    });
                    reviewNext.addEventListener('click', function () {
                        reviewSlider.scrollBy({ left: slideStep(), behavior: 'smooth' });
                    });

                    let autoSlideTimer = null;
                    const startAutoSlide = function () {
                        if (autoSlideTimer) {
                            clearInterval(autoSlideTimer);
                        }
                        autoSlideTimer = setInterval(function () {
                            const step = slideStep();
                            const maxScrollLeft = reviewSlider.scrollWidth - reviewSlider.clientWidth;
                            if (reviewSlider.scrollLeft + step >= maxScrollLeft - 4) {
                                reviewSlider.scrollTo({ left: 0, behavior: 'smooth' });
                            } else {
                                reviewSlider.scrollBy({ left: step, behavior: 'smooth' });
                            }
                        }, 3500);
                    };
                    const stopAutoSlide = function () {
                        if (autoSlideTimer) {
                            clearInterval(autoSlideTimer);
                            autoSlideTimer = null;
                        }
                    };

                    reviewSlider.addEventListener('mouseenter', stopAutoSlide);
                    reviewSlider.addEventListener('mouseleave', startAutoSlide);
                    reviewSlider.addEventListener('touchstart', stopAutoSlide, { passive: true });
                    reviewSlider.addEventListener('touchend', startAutoSlide, { passive: true });
                    startAutoSlide();
                }

                const reviewThumbs = [...document.querySelectorAll('[data-review-image]')];
                const lightbox = document.querySelector('[data-review-lightbox]');
                const lightboxImg = document.querySelector('[data-review-lightbox-image]');
                const lightboxClose = document.querySelector('[data-review-lightbox-close]');
                const lightboxPrev = document.querySelector('[data-review-lightbox-prev]');
                const lightboxNext = document.querySelector('[data-review-lightbox-next]');
                let lightboxIndex = 0;
                let touchStartX = 0;
                let touchEndX = 0;

                const renderLightbox = function () {
                    if (!lightboxImg || !reviewThumbs.length) return;
                    const src = reviewThumbs[lightboxIndex].getAttribute('src') || '';
                    lightboxImg.setAttribute('src', src);
                };

                const openLightbox = function (index) {
                    if (!lightbox || !reviewThumbs.length) return;
                    lightboxIndex = index;
                    renderLightbox();
                    lightbox.classList.add('open');
                    lightbox.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                };

                const closeLightbox = function () {
                    if (!lightbox) return;
                    lightbox.classList.remove('open');
                    lightbox.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                };

                const goPrev = function () {
                    if (!reviewThumbs.length) return;
                    lightboxIndex = (lightboxIndex - 1 + reviewThumbs.length) % reviewThumbs.length;
                    renderLightbox();
                };

                const goNext = function () {
                    if (!reviewThumbs.length) return;
                    lightboxIndex = (lightboxIndex + 1) % reviewThumbs.length;
                    renderLightbox();
                };

                reviewThumbs.forEach((img, index) => {
                    img.style.cursor = 'zoom-in';
                    img.addEventListener('click', function () {
                        openLightbox(index);
                    });
                });

                if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
                if (lightboxPrev) lightboxPrev.addEventListener('click', goPrev);
                if (lightboxNext) lightboxNext.addEventListener('click', goNext);

                if (lightbox) {
                    lightbox.addEventListener('click', function (event) {
                        if (event.target === lightbox) closeLightbox();
                    });
                    lightbox.addEventListener('touchstart', function (event) {
                        touchStartX = event.changedTouches[0].clientX;
                    }, { passive: true });
                    lightbox.addEventListener('touchend', function (event) {
                        touchEndX = event.changedTouches[0].clientX;
                        const delta = touchEndX - touchStartX;
                        if (Math.abs(delta) < 40) return;
                        if (delta > 0) {
                            goPrev();
                        } else {
                            goNext();
                        }
                    }, { passive: true });
                }

                document.addEventListener('keydown', function (event) {
                    if (!lightbox || !lightbox.classList.contains('open')) return;
                    if (event.key === 'Escape') closeLightbox();
                    if (event.key === 'ArrowLeft') goPrev();
                    if (event.key === 'ArrowRight') goNext();
                });
            });
        </script>
        <?php
    }
}
