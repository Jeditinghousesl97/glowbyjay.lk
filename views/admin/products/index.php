<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css?v=<?= time() ?>">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .add-btn-blue {
            background: linear-gradient(135deg, #0b7bff, #0460cc);
            color: white;
            padding: 10px 20px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 10px 22px rgba(11, 123, 255, 0.2);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .add-btn-blue:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(11, 123, 255, 0.26);
        }

        .search-container {
            position: relative;
            margin-bottom: 25px;
        }

        .search-input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 30px;
            font-size: 14px;
            box-sizing: border-box;
            color: #4b5563;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #93c5fd;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
        }

        .search-icon-circle {
            position: absolute;
            right: 5px;
            top: 5px;
            width: 40px;
            height: 40px;
            background: #facc15;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .search-icon-img {
            width: 18px;
            height: 18px;
            opacity: 0.8;
        }

        .list-header {
            background: #f8fafc;
            padding: 12px 15px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            color: #475569;
            font-size: 13px;
            font-weight: 700;
            border: 1px solid #e2e8f0;
        }

        .delete-all-btn {
            background: #ffe5e5;
            color: #d92d20;
            border: none;
            padding: 7px 12px;
            border-radius: 999px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }

        .delete-all-btn:hover {
            background: #ffd3d3;
        }

        .product-list {
            background: transparent;
            border-radius: 16px;
        }

        .prod-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 14px;
            margin-bottom: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .prod-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
            border-color: #d9e2ec;
        }

        .prod-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-right: 8px;
        }

        .action-btn {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: 1px solid #d0d7e2;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
            transition: transform 0.15s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .action-btn .action-icon {
            width: 14px;
            height: 14px;
            display: inline-block;
        }

        .action-btn .action-icon svg {
            width: 14px;
            height: 14px;
            display: block;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.14);
        }

        .action-clone {
            color: #5b21b6;
            border-color: #c4b5fd;
            background: #f5f3ff;
        }

        .action-edit {
            color: #0f766e;
            border-color: #99f6e4;
            background: #f0fdfa;
        }

        .action-delete {
            color: #b42318;
            border-color: #fecaca;
            background: #fff1f2;
        }

        .prod-thumb {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            object-fit: cover;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
        }

        .prod-main {
            flex: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .prod-info {
            min-width: 0;
        }

        .prod-title {
            font-weight: 700;
            color: #111827;
            font-size: 18px;
            margin-bottom: 5px;
            line-height: 1.35;
        }

        .prod-cat {
            font-size: 13px;
            color: #6b7280;
        }

        @media (min-width: 992px) {
            .search-container {
                max-width: 560px;
            }

            .list-header {
                padding: 14px 18px;
                border-radius: 16px;
                font-size: 14px;
            }
        }

        @media (max-width: 680px) {
            .prod-item {
                align-items: flex-start;
            }

            .prod-main {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    <?php require_once ROOT_PATH . 'helpers/ImageHelper.php'; ?>
 <!-- Global Loader Injection -->
    <?php include 'views/admin/partials/loader.php'; ?>
    <div class="container" style="padding-bottom: 80px;">


        <div class="page-header">
            <div>
                <h2 style="margin:0;">All Products</h2>
                <p style="margin:0; font-size:12px; color:#64748b;">Dark Lavender Clothing!</p>
            </div>
            <div>
                <!-- Shop Logo Placeholder -->
                <!-- <div style="width:30px; height:30px; background:#ddd; border-radius:50%; display:inline-block;"></div> -->
                <a href="<?= BASE_URL ?>product/add" class="add-btn-blue">Add New</a>
            </div>
        </div>

        <!-- Search -->
        <form class="search-container" action="<?= BASE_URL ?>product/index" method="GET">
            <input type="text" name="search" class="search-input" placeholder="Type here to search..."
                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <div class="search-icon-circle" onclick="this.parentElement.submit()" style="cursor:pointer;">
                <img src="<?= BASE_URL ?>assets/icons/search.png" class="search-icon-img" alt="S">
            </div>
        </form>

        <!-- List Header -->
        <div class="list-header">
            <span>Products</span>
            <?php if (!empty($products)): ?>
                <a href="<?= BASE_URL ?>product/delete_all" class="delete-all-btn"
                    onclick="if(confirm('Delete ALL products? This cannot be undone!')){ showGlobalLoader(); return true; } else { return false; }">
                    Delete All
                </a>
            <?php endif; ?>
        </div>

        <!-- Product List -->
        <div class="product-list">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $prod): ?>
                    <div class="prod-item">
                        <div class="prod-actions">
                            <a href="<?= BASE_URL ?>product/cloneProduct/<?= $prod['id'] ?>" class="action-btn action-clone"
                                title="Clone"
                                onclick="if(confirm('Clone this item?')){ showGlobalLoader(); return true; } else { return false; }">
                                <span class="action-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24">
                                        <rect x="9" y="9" width="11" height="11" rx="2"></rect>
                                        <rect x="4" y="4" width="11" height="11" rx="2"></rect>
                                    </svg>
                                </span>
                            </a>
                            <a href="<?= BASE_URL ?>product/edit/<?= $prod['id'] ?>" class="action-btn action-edit"
                                title="Edit"
                                onclick="if(confirm('Edit this item?')){ showGlobalLoader(); return true; } else { return false; }">
                                <span class="action-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M3 21l3.8-1 11-11a2.2 2.2 0 0 0-3.1-3.1l-11 11L3 21z"></path>
                                        <path d="M13.5 6.5l4 4"></path>
                                    </svg>
                                </span>
                            </a>
                            <a href="<?= BASE_URL ?>product/delete/<?= $prod['id'] ?>" class="action-btn action-delete"
                                title="Delete"
                                onclick="if(confirm('Delete this item?')){ showGlobalLoader(); return true; } else { return false; }">
                                <span class="action-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M4 7h16"></path>
                                        <path d="M9 7V5h6v2"></path>
                                        <path d="M7 7l1 13h8l1-13"></path>
                                        <path d="M10 11v6M14 11v6"></path>
                                    </svg>
                                </span>
                            </a>
                        </div>

                        <?php
                        $imgSrc = ImageHelper::uploadUrl($prod['main_image'] ?? '', BASE_URL . "assets/icons/products.png");
                        ?>
                        <?= ImageHelper::renderResponsivePicture(
                            $prod['main_image'] ?? '',
                            $imgSrc,
                            [
                                'class' => 'prod-thumb',
                                'alt' => $prod['title'] ?? 'Product',
                                'loading' => 'lazy',
                                'decoding' => 'async',
                                'fetchpriority' => 'low'
                            ],
                            'admin_thumb'
                        ) ?>

                        <div class="prod-main">
                            <div class="prod-info">
                                <div class="prod-title"><?= htmlspecialchars($prod['title']) ?></div>
                                <div class="prod-cat"><?= htmlspecialchars($prod['category_name'] ?? 'Uncategorized') ?></div>
                            </div>
                            
                            <!-- Visibility Toggle -->
                            <a href="<?= BASE_URL ?>product/toggleActive/<?= $prod['id'] ?>?return=<?= rawurlencode($_SERVER['REQUEST_URI'] ?? 'product/index') ?>" 
                               class="toggle-btn <?= $prod['is_active'] ? 'active' : '' ?>" 
                               title="Toggle Visibility" 
                               onclick="showGlobalLoader();">
                                <div class="toggle-circle"></div>
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; color:#999; margin-top:30px;">
                    No products found.<br>
                    <a href="<?= BASE_URL ?>product/add" style="color:#007aff;">Add your first product</a>
                </p>
            <?php endif; ?>
        </div>

    </div>

    <!-- Bottom Navigation -->
    <?php $current_page = 'products';
    include 'views/layouts/bottom_nav.php'; ?>

</body>

</html>


