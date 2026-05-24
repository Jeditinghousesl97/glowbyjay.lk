<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Promo Popup Settings') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
    <style>
        body { background:linear-gradient(180deg,#f5f6f8 0%,#eef1f5 100%); }
        .container{
            max-width:1080px;
            margin:0 auto;
        }
        .promo-card{
            background:#fff;
            border:1px solid #e6e8ed;
            box-shadow:0 14px 34px rgba(20,27,45,.06);
            padding:24px;
        }
        .page-header{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:16px;
            margin-bottom:10px;
        }
        .title{ font-size:28px; font-weight:900; color:#0f172a; letter-spacing:.01em; }
        .note{
            margin:0 0 18px;
            color:#475569;
            font-size:14px;
            line-height:1.7;
        }
        .label{
            display:block;
            font-size:11px;
            font-weight:800;
            letter-spacing:.2em;
            text-transform:uppercase;
            color:#334155;
            margin-bottom:8px;
        }
        .input-box{
            width:100%;
            padding:13px 14px;
            border:1px solid #d8dee8;
            background:#f8fafc;
            box-sizing:border-box;
            margin-bottom:16px;
            font-size:14px;
            transition:border-color .2s ease, box-shadow .2s ease;
        }
        .input-box:focus{
            outline:none;
            border-color:#0f172a;
            box-shadow:0 0 0 3px rgba(15,23,42,.08);
        }
        .check-row{
            display:flex;
            align-items:center;
            gap:8px;
            margin-bottom:14px;
            color:#1e293b;
            font-size:14px;
            font-weight:600;
        }
        .upload-box{
            border:1px dashed #cbd5e1;
            background:#f8fafc;
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
            color:#64748b;
            font-size:12px;
            text-align:center;
            line-height:1.5;
            padding:14px;
        }
        .config-block{
            border:1px solid #e7ebf2;
            background:#fff;
            padding:18px;
            margin-bottom:18px;
        }
        .config-title{
            margin:0 0 12px;
            font-size:15px;
            font-weight:800;
            letter-spacing:.08em;
            text-transform:uppercase;
            color:#0f172a;
        }
        .save-btn{
            border:none;
            background:#0f172a;
            color:#fff;
            padding:13px 22px;
            font-size:13px;
            font-weight:800;
            letter-spacing:.16em;
            text-transform:uppercase;
            cursor:pointer;
            transition:transform .2s ease, background-color .2s ease;
        }
        .save-btn:hover{
            background:#111827;
            transform:translateY(-1px);
        }
        .back-link{
            display:inline-block;
            margin-top:14px;
            color:#475569;
            text-decoration:none;
            font-size:13px;
            letter-spacing:.06em;
        }
        .header-left{
            display:flex;
            align-items:center;
            gap:12px;
        }
        .back-arrow{
            text-decoration:none;
            color:#0f172a;
            font-size:24px;
            line-height:1;
        }
        .divider{
            border:0;
            border-top:1px solid #e9edf4;
            margin:8px 0 18px;
        }
    </style>
</head>
<body>
<?php require_once ROOT_PATH . 'helpers/ImageHelper.php'; ?>
<div class="container" style="padding-bottom:90px;">
    <div class="page-header">
        <div class="header-left">
            <a href="<?= BASE_URL ?>settings/edit" class="back-arrow">&#10094;</a>
            <div class="title">Promo Popup Settings</div>
        </div>
    </div>

    <p class="note">Manage both popup types from one place. Update visibility, link behavior, and images for the corner promo popup and entrance popup.</p>

    <form action="<?= BASE_URL ?>promo/update" method="POST" enctype="multipart/form-data">
        <?= csrf_input() ?>
        <div class="promo-card">
            <section class="config-block">
                <h3 class="config-title">Corner Promo Popup</h3>
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
            </section>

            <hr class="divider">

            <section class="config-block">
                <h3 class="config-title">Website Entrance Popup</h3>
                <label class="check-row">
                    <input type="checkbox" name="entrance_popup_enabled" value="1" <?= !empty($promo['entrance_popup_enabled']) ? 'checked' : '' ?>>
                    Enable website entrance main popup
                </label>

                <label class="check-row">
                    <input type="checkbox" name="entrance_popup_open_new_tab" value="1" <?= !isset($promo['entrance_popup_open_new_tab']) || $promo['entrance_popup_open_new_tab'] === '' || !empty($promo['entrance_popup_open_new_tab']) ? 'checked' : '' ?>>
                    Open entrance popup link in new tab
                </label>

                <label class="label">Entrance Popup Link</label>
                <input
                    type="text"
                    name="entrance_popup_link"
                    class="input-box"
                    placeholder="https://example.com/offer"
                    value="<?= htmlspecialchars($promo['entrance_popup_link'] ?? '') ?>">

                <label class="label">Entrance Main Popup Image</label>
                <div class="upload-box" onclick="document.getElementById('entrancePopupImageInput').click()">
                    <?php if (!empty($promo['entrance_popup_image'])): ?>
                        <?php
                        $entrancePopupImageUrl = ImageHelper::settingsImageUrl((string) $promo['entrance_popup_image'], '');
                        $entrancePopupImageFile = basename((string) parse_url($entrancePopupImageUrl, PHP_URL_PATH));
                        ?>
                        <?= ImageHelper::renderResponsivePicture(
                            $entrancePopupImageFile,
                            $entrancePopupImageUrl,
                            [
                                'alt' => 'Entrance popup image preview',
                                'loading' => 'lazy',
                                'decoding' => 'async',
                                'fetchpriority' => 'low'
                            ],
                            'product_gallery'
                        ) ?>
                    <?php endif; ?>
                    <div class="upload-hint">Tap or click to upload entrance popup image.</div>
                    <input id="entrancePopupImageInput" type="file" name="entrance_popup_image" accept="image/*" style="display:none;">
                </div>
            </section>

            <button type="submit" class="save-btn">Save Promo</button>
        </div>
    </form>

        <a href="<?= BASE_URL ?>admin/dashboard" class="back-link">Back to Dashboard</a>
</div>

<?php $current_page = 'promo'; include 'views/layouts/bottom_nav.php'; ?>
</body>
</html>
