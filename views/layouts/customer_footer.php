<?php
if (!function_exists('customer_footer_render')) {
    function customer_footer_render(array $settings, string $baseUrl): void
    {
        $shopName = SeoHelper::shopName($settings);
        if ($shopName === '') {
            $shopName = 'Online Shop';
        }
        $shopLogoUrl = ImageHelper::settingsImageUrl(
            (string) ($settings['shop_logo'] ?? ''),
            'assets/uploads/1774110158_logo_logo.jpg'
        );

        $brandSummary = FooterHelper::brandSummary($settings);
        $policyLinks = FooterHelper::policyLinks($baseUrl);
        $supportLinks = FooterHelper::supportLinks($baseUrl);
        $paymentMethods = FooterHelper::paymentMethods($settings);
        $socialLinks = FooterHelper::socialLinks($settings);
        $whatsappDigits = FooterHelper::whatsappDigits($settings);
        $whatsappLink = FooterHelper::whatsappMessageLink($settings, 'Hi, I need help with my order.');
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
            ['label' => 'Cats', 'url' => $baseUrl . 'shop/categories', 'icon' => 'fa-border-all', 'active' => strpos($currentRoute, 'shop/categories') === 0],
            ['label' => 'Deals', 'url' => $baseUrl . 'discounts', 'icon' => 'fa-tags', 'active' => strpos($currentRoute, 'discounts') === 0],
            ['label' => 'Cart', 'url' => $baseUrl . 'cart', 'icon' => 'fa-cart-shopping', 'active' => strpos($currentRoute, 'cart') === 0, 'badge' => $cartCount],
        ];
        $cartBg = trim((string) ($settings['floating_cart_bg'] ?? '#7c4af0')) ?: '#7c4af0';
        $cartText = trim((string) ($settings['floating_cart_text'] ?? '#ffffff')) ?: '#ffffff';
        $footerYear = FooterHelper::footerYear();
        ?>
        <footer class="site-footer" aria-label="Site footer">
            <div class="site-footer-shell">
                <div class="site-footer-grid">
                    <section class="site-footer-brand">
                        <span class="site-footer-eyebrow">Brand Story</span>
                        <a class="site-footer-brand-logo" href="<?= htmlspecialchars($baseUrl) ?>" aria-label="<?= htmlspecialchars($shopName) ?>">
                            <img src="<?= htmlspecialchars($shopLogoUrl) ?>" alt="<?= htmlspecialchars($shopName) ?>">
                        </a>
                        <?php if (!empty($settings['shop_slogan'])): ?>
                            <p class="site-footer-slogan"><?= htmlspecialchars($settings['shop_slogan']) ?></p>
                        <?php endif; ?>
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
                                <small>Hi, I need help with my order.</small>
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
                    <span class="site-floating-contact-toggle-icon" aria-hidden="true">
                        <i class="fa-brands fa-whatsapp"></i>
                    </span>
                    <span class="site-floating-contact-toggle-text">Help</span>
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
                gap:10px;
                min-height:54px;
                padding:0 18px 0 14px;
                border:1px solid rgba(37,211,102,.18);
                background:linear-gradient(135deg,#25d366 0%,#128c7e 100%);
                color:#fff;
                box-shadow:0 18px 38px rgba(18,140,126,.28);
                cursor:pointer;
                transition:transform .2s ease, box-shadow .2s ease, filter .2s ease;
            }
            .site-floating-contact-toggle:hover{
                transform:translateY(-2px);
                box-shadow:0 22px 44px rgba(18,140,126,.34);
                filter:saturate(1.05);
            }
            .site-floating-contact-toggle-icon{
                width:32px;
                height:32px;
                display:inline-flex;
                align-items:center;
                justify-content:center;
                background:rgba(255,255,255,.18);
            }
            .site-floating-contact-toggle-icon i{
                font-size:18px;
                line-height:1;
            }
            .site-floating-contact-toggle-text{
                font-size:10px;
                font-weight:800;
                letter-spacing:.18em;
                text-transform:uppercase;
                white-space:nowrap;
            }
            .site-footer-brand-logo{
                display:inline-flex;
                align-items:center;
                justify-content:flex-start;
                margin:0 0 14px;
                max-width:220px;
            }
            .site-footer-brand-logo img{
                display:block;
                width:auto;
                max-width:100%;
                max-height:72px;
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
                right:20px;
                bottom:92px;
                z-index:89;
                display:grid;
                justify-items:end;
            }
            .site-floating-cart-button{
                display:inline-flex;
                align-items:center;
                gap:10px;
                min-height:54px;
                padding:0 16px 0 14px;
                background:var(--floating-cart-bg);
                color:var(--floating-cart-text);
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
            @media (max-width: 760px){
                .site-content{
                    padding-bottom:84px;
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
                    min-height:48px;
                    padding:0 14px 0 12px;
                }
                .site-floating-contact-toggle-icon{
                    width:28px;
                    height:28px;
                }
                .site-floating-contact-toggle-text{
                    font-size:9px;
                }
                .site-footer-brand-logo{
                    max-width:180px;
                    margin-bottom:12px;
                }
                .site-footer-brand-logo img{
                    max-height:58px;
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
                    background:var(--primary, #b68a2d);
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
            });
        </script>
        <?php
    }
}
