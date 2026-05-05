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
        .page-header {
            margin-bottom: 20px;
        }

        /* QR & Logo Section */
        .assets-row {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .asset-card {
            flex: 1;
            text-align: center;
        }

        .asset-label {
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
            text-align: left;
            display: block;
        }

        .asset-box {
            background: #fdfdfd;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            margin-bottom: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 120px;
        }

        .asset-img {
            max-width: 100px;
            max-height: 100px;
        }

        .btn-download {
            background-color: #007aff;
            color: white;
            border: none;
            padding: 8px 0;
            width: 100%;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
        }

        /* Shop Link */
        .shop-link-section {
            margin-bottom: 30px;
        }

        .readonly-input-group {
            position: relative;
        }

        .readonly-input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            background: #f0f0f0;
            border: none;
            border-radius: 8px;
            color: #888;
            font-size: 14px;
            box-sizing: border-box;
        }

        .copy-icon {
            position: absolute;
            right: 10px;
            top: 10px;
            cursor: pointer;
            opacity: 0.6;
            font-size: 18px;
        }

        /* Gray Form Area */
        .gray-form-card {
            background-color: #e0e0e0;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 80px;
        }

        .section-title {
            font-weight: bold;
            color: #555;
            margin-bottom: 10px;
            display: block;
            font-size: 14px;
        }

        .input-white {
            width: 100%;
            padding: 12px 15px;
            background: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            margin-bottom: 15px;
        }

        .social-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .social-icon {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }

        .publish-btn {
            background-color: #007aff;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .slider-admin-grid {
            display: grid;
            gap: 16px;
            margin-top: 18px;
        }

        .slider-admin-card {
            background: #f6f6f6;
            border-radius: 12px;
            padding: 14px;
        }

        .slider-preview-box {
            width: 100%;
            height: 150px;
            border-radius: 12px;
            background: #fff;
            border: 1px dashed #d0d0d0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .slider-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .slider-placeholder {
            color: #aaa;
            font-size: 13px;
        }

        .slider-file-input {
            margin-bottom: 12px;
            width: 100%;
        }

        .slider-remove-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: -4px;
            margin-bottom: 12px;
            font-size: 12px;
            color: #666;
        }

        .input-white.textarea-lg {
            min-height: 160px;
            resize: vertical;
            line-height: 1.6;
        }

        @media (min-width: 992px) {
            .container {
                padding-top: 34px;
                padding-bottom: 40px;
            }

            .assets-row {
                max-width: 760px;
            }

            .asset-box {
                border-radius: 20px;
                height: 170px;
                box-shadow: 0 16px 34px rgba(17, 24, 39, 0.06);
                border: 1px solid rgba(17, 24, 39, 0.05);
            }

            .gray-form-card {
                border-radius: 24px;
                padding: 28px;
                box-shadow: 0 18px 36px rgba(17, 24, 39, 0.07);
            }

            .slider-admin-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .slider-admin-card {
                border-radius: 18px;
                padding: 16px;
            }
        }
    </style>
</head>

<body>
    <?php require_once ROOT_PATH . 'helpers/ImageHelper.php'; ?>

    <!-- Global Loader Injection -->
    <?php include 'views/admin/partials/loader.php'; ?>

    <div class="container">
        <div class="page-header">
            <h2 style="margin:0;">My Shop 🏁</h2>
            <p style="margin:0; font-size:11px; color:#888;">Wow! Now your Shop is open to World.</p>
        </div>

        <!-- QR & Logo -->
        <div class="assets-row">
            <!-- QR -->
            <div class="asset-card">
                <span class="asset-label">My shop QR Code</span>
                <div class="asset-box">
                    <?php if ($settings['shop_qr']): ?>
                        <?php
                        $shopQrUrl = ImageHelper::settingsImageUrl($settings['shop_qr'] ?? '', '');
                        $shopQrFile = basename((string) parse_url($shopQrUrl, PHP_URL_PATH));
                        ?>
                        <?= ImageHelper::renderResponsivePicture(
                            $shopQrFile,
                            $shopQrUrl,
                            [
                                'class' => 'asset-img',
                                'alt' => 'QR',
                                'loading' => 'lazy',
                                'decoding' => 'async',
                                'fetchpriority' => 'low'
                            ],
                            'logo'
                        ) ?>
                    <?php else: ?>
                        <span style="color:#ccc; font-size:40px;">🔳</span>
                    <?php endif; ?>
                </div>
                <a href="<?= $settings['shop_qr'] ?? '#' ?>" download class="btn-download">
                    💾 Download QR
                </a>
            </div>

            <!-- Logo -->
            <div class="asset-card">
                <span class="asset-label">My shop Logo</span>
                <div class="asset-box">
                    <?php if ($settings['shop_logo']): ?>
                        <?php
                        $shopLogoUrl = ImageHelper::settingsImageUrl($settings['shop_logo'] ?? '', '');
                        $shopLogoFile = basename((string) parse_url($shopLogoUrl, PHP_URL_PATH));
                        ?>
                        <?= ImageHelper::renderResponsivePicture(
                            $shopLogoFile,
                            $shopLogoUrl,
                            [
                                'class' => 'asset-img',
                                'alt' => 'Logo',
                                'loading' => 'lazy',
                                'decoding' => 'async',
                                'fetchpriority' => 'low'
                            ],
                            'logo'
                        ) ?>
                    <?php else: ?>
                        <span style="color:#ccc; font-size:40px;">🌸</span>
                    <?php endif; ?>
                </div>
                <a href="<?= $settings['shop_logo'] ?? '#' ?>" download class="btn-download">
                    💾 Download Logo
                </a>
            </div>
        </div>

        <!-- Shop Link -->
        <div class="shop-link-section">
            <span class="asset-label" style="font-size:13px; font-weight:bold; margin-bottom:8px;">My Shop Link</span>
            <div class="readonly-input-group">
                <input type="text" class="readonly-input"
                    value="<?= htmlspecialchars($settings['shop_url'] ?? 'yourshopname.freezone.lk') ?>" readonly
                    id="shopLinInput">
                <span class="copy-icon" onclick="copyLink()">📋</span>
            </div>
        </div>

        <!-- Edit Form -->
        <form action="<?= BASE_URL ?>myShop/update" method="POST" enctype="multipart/form-data">
            <?= csrf_input() ?>
            <div class="gray-form-card">

                <label class="section-title">My shop Review Link:</label>
                <input type="text" name="review_link" class="input-white" placeholder="Enter Review Link here"
                    value="<?= htmlspecialchars($settings['review_link']) ?>">

                <label class="section-title" style="margin-top:20px;">My Social Links</label>

                <!-- Facebook -->
                <div class="social-row">
                    <img src="<?= BASE_URL ?>assets/icons/facebook.png" class="social-icon">
                    <input type="text" name="social_fb" class="input-white" style="margin-bottom:0;"
                        placeholder="Enter Link here" value="<?= htmlspecialchars($settings['social_fb']) ?>">
                </div>

                <!-- Tiktok -->
                <div class="social-row">
                    <img src="<?= BASE_URL ?>assets/icons/tiktok.png" class="social-icon">
                    <input type="text" name="social_tiktok" class="input-white" style="margin-bottom:0;"
                        placeholder="Enter Link here" value="<?= htmlspecialchars($settings['social_tiktok']) ?>">
                </div>

                <!-- Instagram -->
                <div class="social-row">
                    <img src="<?= BASE_URL ?>assets/icons/instagram.png" class="social-icon">
                    <input type="text" name="social_insta" class="input-white" style="margin-bottom:0;"
                        placeholder="Enter Link here" value="<?= htmlspecialchars($settings['social_insta']) ?>">
                </div>

                <!-- Youtube -->
                <div class="social-row">
                    <img src="<?= BASE_URL ?>assets/icons/youtube.png" class="social-icon">
                    <input type="text" name="social_youtube" class="input-white" style="margin-bottom:0;"
                        placeholder="Enter Link here" value="<?= htmlspecialchars($settings['social_youtube']) ?>">
                </div>

                <!-- Whatsapp -->
                <div class="social-row">
                    <img src="<?= BASE_URL ?>assets/icons/whatsapp.png" class="social-icon">
                    <input type="text" name="social_whatsapp" class="input-white" style="margin-bottom:0;"
                        placeholder="Enter Link here" value="<?= htmlspecialchars($settings['social_whatsapp']) ?>">
                </div>

                <label class="section-title" style="margin-top:20px;">Shop Owner Email</label>
                <input type="email" name="shop_owner_email" class="input-white" placeholder="owner@yourshop.com"
                    value="<?= htmlspecialchars($settings['shop_owner_email'] ?? '') ?>">

                <label class="section-title" style="margin-top:20px;">Courier Services</label>
                <p style="margin:0 0 12px; font-size:12px; color:#666;">
                    Add one courier service per line. These names will appear in the Complete Order popup.
                </p>
                <textarea name="courier_services_list" class="input-white textarea-lg" style="min-height:140px;" placeholder="e.g.&#10;Domex&#10;Pronto&#10;Koombiyo"><?= htmlspecialchars($settings['courier_services_list'] ?? '') ?></textarea>

                <label class="section-title" style="margin-top:24px;">Home Page Hero Slider</label>
                <p style="margin:0 0 14px; font-size:12px; color:#666;">
                    Upload a desktop image and an optional mobile image for each slide. Mobile images will be used on phones so the hero stays fully visible without cropping.
                </p>

                <div class="slider-admin-grid">
                    <?php for ($i = 1; $i <= 3; $i++):
                        $imageKey = 'hero_slide_' . $i . '_image';
                        $mobileImageKey = 'hero_slide_' . $i . '_mobile_image';
                        $linkKey = 'hero_slide_' . $i . '_link';
                        $imageValue = $settings[$imageKey] ?? '';
                        $mobileImageValue = $settings[$mobileImageKey] ?? '';
                    ?>
                        <div class="slider-admin-card">
                            <label class="section-title" style="margin-top:0;">Slide <?= $i ?></label>

                            <div class="slider-preview-box">
                                <?php if (!empty($imageValue)): ?>
                                    <?php
                                    $heroPreviewUrl = ImageHelper::settingsImageUrl($imageValue, '');
                                    $heroPreviewFile = basename((string) parse_url($heroPreviewUrl, PHP_URL_PATH));
                                    ?>
                                    <?= ImageHelper::renderResponsivePicture(
                                        $heroPreviewFile,
                                        $heroPreviewUrl,
                                        [
                                            'alt' => 'Slide ' . $i . ' preview',
                                            'loading' => 'lazy',
                                            'decoding' => 'async',
                                            'fetchpriority' => 'low'
                                        ],
                                        'hero'
                                    ) ?>
                                <?php else: ?>
                                    <span class="slider-placeholder">No image uploaded</span>
                                <?php endif; ?>
                            </div>

                            <input type="file"
                                name="<?= $imageKey ?>"
                                class="slider-file-input"
                                accept="image/*">

                            <?php if (!empty($imageValue)): ?>
                                <label class="slider-remove-row">
                                    <input type="checkbox" name="remove_hero_slide_<?= $i ?>_image" value="1">
                                    Remove current image
                                </label>
                            <?php endif; ?>

                            <input type="text"
                                name="<?= $linkKey ?>"
                                class="input-white"
                                placeholder="https://your-link-for-this-slide.com"
                                value="<?= htmlspecialchars($settings[$linkKey] ?? '') ?>">

                            <label class="section-title" style="margin-top:12px;">Mobile image (phone)</label>
                            <div class="slider-preview-box" style="height:120px;">
                                <?php if (!empty($mobileImageValue)): ?>
                                    <?php
                                    $heroMobilePreviewUrl = ImageHelper::settingsImageUrl($mobileImageValue, '');
                                    $heroMobilePreviewFile = basename((string) parse_url($heroMobilePreviewUrl, PHP_URL_PATH));
                                    ?>
                                    <?= ImageHelper::renderResponsivePicture(
                                        $heroMobilePreviewFile,
                                        $heroMobilePreviewUrl,
                                        [
                                            'alt' => 'Slide ' . $i . ' mobile preview',
                                            'loading' => 'lazy',
                                            'decoding' => 'async',
                                            'fetchpriority' => 'low'
                                        ],
                                        'hero'
                                    ) ?>
                                <?php else: ?>
                                    <span class="slider-placeholder">No mobile image uploaded</span>
                                <?php endif; ?>
                            </div>

                            <input type="file"
                                name="<?= $mobileImageKey ?>"
                                class="slider-file-input"
                                accept="image/*">

                            <?php if (!empty($mobileImageValue)): ?>
                                <label class="slider-remove-row">
                                    <input type="checkbox" name="remove_hero_slide_<?= $i ?>_mobile_image" value="1">
                                    Remove current mobile image
                                </label>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>

                <label class="section-title" style="margin-top:24px;">Policy Pages Content</label>
                <p style="margin:0 0 14px; font-size:12px; color:#666;">
                    Edit the customer-facing text for your footer policy pages. Use new lines to separate paragraphs.
                </p>

                <label class="section-title">Refund &amp; Returns Policy</label>
                <textarea name="refund_policy_content" class="input-white textarea-lg" placeholder="Enter refund and returns policy content here"><?= htmlspecialchars($settings['refund_policy_content'] ?? '') ?></textarea>

                <label class="section-title">Terms &amp; Conditions</label>
                <textarea name="terms_conditions_content" class="input-white textarea-lg" placeholder="Enter terms and conditions content here"><?= htmlspecialchars($settings['terms_conditions_content'] ?? '') ?></textarea>

                <label class="section-title">Privacy Policy</label>
                <textarea name="privacy_policy_content" class="input-white textarea-lg" placeholder="Enter privacy policy content here"><?= htmlspecialchars($settings['privacy_policy_content'] ?? '') ?></textarea>

                  <label class="section-title" style="margin-top:24px;">Checkout Settings</label>
                  <p style="margin:0 0 14px; font-size:12px; color:#666;">
                    Turn checkout methods on or off for this shop. PayHere and KOKO API credentials are managed from Settings.
                  </p>

                <label class="slider-remove-row" style="margin:0 0 12px;">
                    <input type="checkbox" name="cod_enabled" value="1" <?= !empty($settings['cod_enabled']) ? 'checked' : '' ?>>
                    Enable Cash on Delivery
                </label>

                <label class="slider-remove-row" style="margin:0 0 12px;">
                    <input type="checkbox" name="whatsapp_ordering_enabled" value="1" <?= !empty($settings['whatsapp_ordering_enabled']) ? 'checked' : '' ?>>
                    Enable WhatsApp Ordering
                </label>

                <label class="slider-remove-row" style="margin:0 0 12px;">
                    <input type="checkbox" name="bank_transfer_enabled" value="1" <?= !empty($settings['bank_transfer_enabled']) ? 'checked' : '' ?>>
                    Enable Bank Transfer
                </label>

                <label class="section-title" style="margin-top:18px;">Bank Transfer Details</label>
                <textarea name="bank_transfer_details" class="input-white textarea-lg" placeholder="Enter one paragraph of bank transfer details here. Example: Bank name, account name, account number, branch, and any payment reference instructions."><?= htmlspecialchars($settings['bank_transfer_details'] ?? '') ?></textarea>

                <p style="margin:0 0 8px; font-size:12px; color:#666;">
                    WhatsApp orders are sent directly to the admin WhatsApp number and are not saved to orders, finance, or reports.
                </p>

                <button type="submit" class="publish-btn" style="margin-top:20px;" onclick="showGlobalLoader()">
                    💾 PUBLISH
                </button>
            </div>
        </form>
    </div>

    <!-- Bottom Navigation -->
    <?php $current_page = 'myshop';
    include 'views/layouts/bottom_nav.php'; ?>

    <script>
        function copyLink() {
            const copyText = document.getElementById("shopLinInput");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);
            alert("Copied the text: " + copyText.value);
        }
    </script>

</body>

</html>
