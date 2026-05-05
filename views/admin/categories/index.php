<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $title ?>
    </title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
    <style>
        .category-list {
            background: #fff;
            border-radius: 12px;
            padding: 10px;
            margin-top: 20px;
        }

        .cat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .cat-item:last-child {
            border-bottom: none;
        }

        .cat-name {
            font-weight: 600;
            font-size: 16px;
            color: #333;
        }

        .cat-order {
            font-size: 12px;
            color: #888;
            margin-left: 8px;
        }

        .sub-cat-name {
            font-weight: 400;
            font-size: 14px;
            color: #666;
            margin-left: 20px;
        }

        .cat-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .edit-btn {
            background-color: #00c4b4;
            /* teal from screenshot */
            color: white;
            padding: 5px;
            border-radius: 4px;
            width: 24px;
            height: 24px;
            display: flex;
            text-decoration: none;
            justify-content: center;
            align-items: center;
            font-size: 12px;
        }

        .check-box {
            width: 20px;
            height: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .header-bar {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .back-circle {
            background: #000;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            text-decoration: none;
        }

        @media (min-width: 992px) {
            .container {
                max-width: 860px;
                padding: 34px 30px 40px;
            }

            .category-list {
                border-radius: 24px;
                padding: 20px 22px;
                box-shadow: 0 16px 36px rgba(17, 24, 39, 0.06);
                border: 1px solid rgba(17, 24, 39, 0.05);
            }

            .cat-item {
                padding: 18px 0;
            }

            .cat-name {
                font-size: 18px;
            }

            .sub-cat-name {
                font-size: 15px;
            }
        }
    </style>
</head>

<body>
<?php include 'views/admin/partials/loader.php'; ?>
    <div class="container">
        <div class="header-bar">
            <a href="<?= BASE_URL ?>admin/dashboard" class="back-circle">❮</a>
            <h2 style="margin:0;">Categories</h2>
        </div>

        <a href="<?= BASE_URL ?>category/add" class="btn btn-outline-primary btn-block"
            style="border:1px solid var(--primary-color); color:var(--primary-color); background:white;">
            Add New Category
        </a>

        <div class="category-list">
            <?php foreach ($categoryTree as $mainCat): ?>
                <!-- Main Category -->
                <div class="cat-item">
                    <span class="cat-name">
                        <?= htmlspecialchars($mainCat['name']) ?>
                        <span class="cat-order">#<?= htmlspecialchars((string) ($mainCat['display_order'] ?? 0)) ?></span>
                    </span>
                    <div class="cat-actions">
                        <a href="<?= BASE_URL ?>category/edit/<?= $mainCat['id'] ?>" class="edit-btn">✏️</a>
                        <a href="<?= BASE_URL ?>category/delete/<?= $mainCat['id'] ?>" class="edit-btn"
                            style="background-color:#ff3b30;" onclick="return confirm('Delete Category?')">🗑️</a>
                    </div>
                </div>

                <!-- Sub Categories -->
                <?php if (!empty($mainCat['children'])): ?>
                    <?php foreach ($mainCat['children'] as $subCat): ?>
                        <div class="cat-item">
                            <span class="sub-cat-name">•
                                <?= htmlspecialchars($subCat['name']) ?>
                                <span class="cat-order">#<?= htmlspecialchars((string) ($subCat['display_order'] ?? 0)) ?></span>
                            </span>
                            <div class="cat-actions">
                                <a href="<?= BASE_URL ?>category/edit/<?= $subCat['id'] ?>" class="edit-btn">✏️</a>
                                <a href="<?= BASE_URL ?>category/delete/<?= $subCat['id'] ?>" class="edit-btn"
                                    style="background-color:#ff3b30;" onclick="return confirm('Delete Category?')">🗑️</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
        // Check if inside Iframe
        if (window.self !== window.top) {
            // 1. Hide Back Button
            const backBtn = document.querySelector('.back-circle');
            if(backBtn) backBtn.style.display = 'none';

            // 2. Refresh Parent Dropdown (if parent has the function)
            if(window.parent && window.parent.refreshCategories) {
                window.parent.refreshCategories();
            }
        }
    </script>
</body>

</html>
