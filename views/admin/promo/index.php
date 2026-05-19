<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Promo Popup Settings') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
    <style>
        body { background:#f4f4f4; }
        .promo-card{
            background:#fff;
            border:1px solid #ececec;
            box-shadow:0 10px 28px rgba(0,0,0,.05);
            padding:20px;
        }
        .page-header{
            display:flex;
            align-items:center;
            gap:12px;
            margin-bottom:16px;
        }
        .title{ font-size:20px; font-weight:800; color:#111; }
        .note{
            margin:0 0 18px;
            color:#666;
            font-size:13px;
            line-height:1.65;
        }
        .label{
            display:block;
            font-size:12px;
            font-weight:800;
            letter-spacing:.16em;
            text-transform:uppercase;
            color:#555;
            margin-bottom:8px;
        }
        .input-box{
            width:100%;
            padding:12px 14px;
            border:1px solid #e3e3e3;
            background:#fafafa;
            box-sizing:border-box;
            margin-bottom:16px;
            font-size:14px;
        }
        .check-row{
            display:flex;
            align-items:center;
            gap:8px;
            margin-bottom:14px;
            color:#333;
            font-size:14px;
        }
        .upload-box{
            border:1px dashed #d6d6d6;
            background:#fbfbfb;
            min-height:170px;
            display:flex;
            justify-content:center;
            align-items:center;
            position:relative;
            margin-bottom:16px;
            cursor:pointer;
            overflow:hidden;
        }
        .upload-box img{
            position:absolute;
            inset:0;
            width:100%;
            height:100%;
            object-fit:contain;
            background:#fff;
        }
        .upload-hint{
            color:#888;
            font-size:12px;
            text-align:center;
            line-height:1.5;
            padding:14px;
        }
        .save-btn{
            border:none;
            background:#111;
            color:#fff;
            padding:12px 18px;
            font-size:13px;
            font-weight:800;
            letter-spacing:.14em;
            text-transform:uppercase;
            cursor:pointer;
        }
        .back-link{
            display:inline-block;
            margin-top:14px;
            color:#555;
            text-decoration:none;
            font-size:13px;
            letter-spacing:.06em;
        }
    </style>
</head>
<body>
<?php require_once ROOT_PATH . 'helpers/ImageHelper.php'; ?>
<div class="container" style="padding-bottom:90px;">
    <div class="page-header">
        <a href="<?= BASE_URL ?>settings/edit" style="text-decoration:none; color:#111; font-size:24px;">&#10094;</a>
        <div class="title">Promo Popup Settings</div>
    </div>

    <p class="note">Configure the top-right popup shown on customer pages for both desktop and mobile.</p>

    <form action="<?= BASE_URL ?>promo/update" method="POST" enctype="multipart/form-data">
        <?= csrf_input() ?>
        <div class="promo-card">
            <label class="check-row">
                <input type="checkbox" name="promo_enabled" value="1" <?= !empty($promo['promo_enabled']) ? 'checked' : '' ?>>
                Enable promo popup
            </label>

            <label class="check-row">
                <input type="checkbox" name="promo_open_new_tab" value="1" <?= !isset($promo['promo_open_new_tab']) || $promo['promo_open_new_tab'] === '' || !empty($promo['promo_open_new_tab']) ? 'checked' : '' ?>>
                Open promo link in new tab
            </label>

            <label class="label">Promo Link</label>
            <input
                type="text"
                name="promo_link"
                class="input-box"
                placeholder="https://example.com/promo"
                value="<?= htmlspecialchars($promo['promo_link'] ?? '') ?>">

            <label class="label">Promo Image</label>
            <div class="upload-box" onclick="document.getElementById('promoImageInput').click()">
                <?php if (!empty($promo['promo_image'])): ?>
                    <?php
                    $promoImageUrl = ImageHelper::settingsImageUrl((string) $promo['promo_image'], '');
                    $promoImageFile = basename((string) parse_url($promoImageUrl, PHP_URL_PATH));
                    ?>
                    <?= ImageHelper::renderResponsivePicture(
                        $promoImageFile,
                        $promoImageUrl,
                        [
                            'alt' => 'Promo image preview',
                            'loading' => 'lazy',
                            'decoding' => 'async',
                            'fetchpriority' => 'low'
                        ],
                        'product_gallery'
                    ) ?>
                <?php endif; ?>
                <div class="upload-hint">Tap or click to upload promo image<br>Recommended: PNG/WebP with transparent background.</div>
                <input id="promoImageInput" type="file" name="promo_image" accept="image/*" style="display:none;">
            </div>

            <button type="submit" class="save-btn">Save Promo</button>
        </div>
    </form>

        <a href="<?= BASE_URL ?>admin/dashboard" class="back-link">Back to Dashboard</a>
</div>

<?php $current_page = 'promo'; include 'views/layouts/bottom_nav.php'; ?>
</body>
</html>
