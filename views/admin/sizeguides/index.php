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
        .guide-list {
            margin-top: 20px;
        }

        .guide-item {
            background: #fff;
            border-radius: 12px;
            padding: 10px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
            border: 1px solid #f0f0f0;
        }

        .guide-info {
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 0;
        }

        .guide-thumb {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            background-color: #eee;
            flex-shrink: 0;
        }

        .guide-name {
            font-weight: 600;
            font-size: 15px;
            color: #333;
        }

        .guide-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .action-btn-icon {
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 16px;
        }

        .action-btn-icon.edit {
            background-color: #00c4b4;
        }

        .action-btn-icon.delete {
            background-color: #ff3b30;
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
                max-width: 900px;
                padding: 34px 30px 40px;
            }

            .guide-item {
                border-radius: 18px;
                padding: 16px 18px;
                box-shadow: 0 16px 34px rgba(17, 24, 39, 0.06);
            }

            .guide-thumb {
                width: 64px;
                height: 64px;
                border-radius: 14px;
            }
        }
    </style>
</head>

<body>
    <?php require_once ROOT_PATH . 'helpers/ImageHelper.php'; ?>
    <?php include 'views/admin/partials/loader.php'; ?>
    <div class="container">
        <div class="header-bar">
            <a href="<?= BASE_URL ?>admin/dashboard" class="back-circle">&#10094;</a>
            <h2 style="margin:0;">Size Guides</h2>
        </div>

        <a href="<?= BASE_URL ?>sizeGuide/add" class="btn btn-outline-primary btn-block"
            style="border:1px solid var(--primary-color); color:var(--primary-color); background:white;">
            Add Size Guide
        </a>

        <div class="guide-list">
            <?php foreach ($guides as $guide): ?>
                <div class="guide-item">
                    <div class="guide-info">
                        <?php if (!empty($guide['image_path'])): ?>
                            <?php $guideImage = ImageHelper::uploadUrl($guide['image_path'], 'https://via.placeholder.com/160?text=Guide'); ?>
                            <?= ImageHelper::renderResponsivePicture(
                                $guide['image_path'],
                                $guideImage,
                                [
                                    'class' => 'guide-thumb',
                                    'alt' => $guide['name'] ?? 'Size guide',
                                    'loading' => 'lazy',
                                    'decoding' => 'async',
                                    'fetchpriority' => 'low'
                                ],
                                'admin_thumb'
                            ) ?>
                        <?php else: ?>
                            <div class="guide-thumb"></div>
                        <?php endif; ?>

                        <span class="guide-name">
                            <?= htmlspecialchars($guide['name']) ?>
                        </span>
                    </div>

                    <div class="guide-actions">
                        <a href="<?= BASE_URL ?>sizeGuide/edit/<?= $guide['id'] ?>" class="action-btn-icon edit"
                            onclick="showGlobalLoader()" title="Edit Size Guide">
                            &#9998;
                        </a>
                        <a href="<?= BASE_URL ?>sizeGuide/delete/<?= $guide['id'] ?>" class="action-btn-icon delete"
                            onclick="if(confirm('Delete this size guide?')){ showGlobalLoader(); return true; } else { return false; }"
                            title="Delete Size Guide">
                            &#128465;
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($guides)): ?>
                <p style="text-align:center; color:#999; margin-top:20px;">No size guides found.</p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        if (window.self !== window.top) {
            const backBtn = document.querySelector('.back-circle');
            if (backBtn) {
                backBtn.style.display = 'none';
            }

            if (window.parent && window.parent.refreshSizeGuides) {
                window.parent.refreshSizeGuides();
            }
        }
    </script>

</body>

</html>
