<?php
// Helper to check active state
$current_page = $current_page ?? 'dashboard';
?>
<div class="bottom-nav">
    <a href="<?= BASE_URL ?>admin/dashboard" class="nav-item <?= $current_page == 'dashboard' ? 'active' : '' ?>" onclick="showGlobalLoader()">
        <img src="<?= BASE_URL ?>assets/icons/dashboard.png" class="nav-icon-img" alt="Dash">
        <span>Dashboard</span>
    </a>
    <a href="<?= BASE_URL ?>product/index" class="nav-item <?= $current_page == 'products' ? 'active' : '' ?>" onclick="showGlobalLoader()">
        <img src="<?= BASE_URL ?>assets/icons/products.png" class="nav-icon-img" alt="Prod">
        <span>Products</span>
    </a>
    
    <a href="<?= BASE_URL ?>feedback/index" class="nav-item <?= $current_page == 'feedback' ? 'active' : '' ?>" onclick="showGlobalLoader()">
        <img src="<?= BASE_URL ?>assets/icons/reviews.png" class="nav-icon-img" alt="Reviews">
        <span>Reviews</span>
    </a>
    
    <a href="<?= BASE_URL ?>order/manage" class="nav-item <?= $current_page == 'orders' ? 'active' : '' ?>" onclick="showGlobalLoader()">
        <img src="<?= BASE_URL ?>assets/icons/dashboard.png" class="nav-icon-img" alt="Orders">
        <span>Orders</span>
    </a>
    <a href="<?= BASE_URL ?>promo/index" class="nav-item <?= $current_page == 'promo' ? 'active' : '' ?>" onclick="showGlobalLoader()">
        <img src="<?= BASE_URL ?>assets/icons/discount.png" class="nav-icon-img" alt="Promo">
        <span>Promo</span>
    </a>
    <a href="<?= BASE_URL ?>myShop/index" class="nav-item <?= $current_page == 'myshop' ? 'active' : '' ?>" onclick="showGlobalLoader()">
        <!-- Using Dashboard icon as placeholder as requested -->
        <img src="<?= BASE_URL ?>assets/icons/Myshop.png" class="nav-icon-img" alt="Shop">
        <span>My Shop</span>
    </a>
    <?php /*
    <a href="<?= BASE_URL ?>settings/index" class="nav-item <?= $current_page == 'settings' ? 'active' : '' ?>" onclick="showGlobalLoader()">
        <img src="<?= BASE_URL ?>assets/icons/settings.png" class="nav-icon-img" alt="Set">
        <span>Settings</span>
    </a>
    */ ?>
</div>

<style>
    /* Icon Styles */
    .nav-icon-img {
        width: 24px;
        height: 24px;
        display: block;
        margin: 0 auto 4px auto;
        object-fit: contain;
        opacity: 0.6;
    }

    .nav-item {
        padding: 5px 4px;
        border-radius: 12px;
        transition: background-color 0.2s;
    }

    .nav-item.active,
    .nav-item:hover {
        background-color: #e1f0ff;
    }

    .nav-item.active .nav-icon-img {
        opacity: 1;
    }

    @media (min-width: 992px) {
        .bottom-nav {
            top: 0;
            bottom: 0;
            left: 0;
            width: 220px;
            padding: 24px 14px;
            border-top: none;
            border-right: 1px solid #edf0f5;
            flex-direction: column;
            justify-content: flex-start;
            align-items: stretch;
            gap: 8px;
            box-shadow: 10px 0 30px rgba(17, 24, 39, 0.04);
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(16px);
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: left;
            padding: 12px 14px;
            font-size: 14px;
            font-weight: 700;
        }

        .nav-item span {
            white-space: nowrap;
        }

        .nav-icon-img {
            margin: 0;
            width: 22px;
            height: 22px;
            flex-shrink: 0;
        }
    }
</style>

<script>
    document.body.classList.add('admin-has-nav');
</script>
