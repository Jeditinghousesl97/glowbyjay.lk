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
        .header-bar {
            display: flex;
            gap: 10px;
            align-items: center;
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

        .section-label {
            font-weight: bold;
            color: #555;
            margin-top: 20px;
            margin-bottom: 5px;
            display: block;
        }

        .sub-label {
            font-size: 11px;
            color: #999;
            margin-bottom: 10px;
            display: block;
        }

        /* Image Upload Blocks */
        .images-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .main-img-box {
            flex: 1;
            background-color: #ffeaea;
            /* Pinkish */
            border-radius: 12px;
            text-align: center;
            padding: 20px;
            cursor: pointer;
            position: relative;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .gallery-box {
            flex: 1;
            background-color: #f0f0f0;
            /* Gray */
            border-radius: 12px;
            text-align: center;
            padding: 20px;
            cursor: pointer;
            position: relative;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .preview-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
            position: absolute;
            top: 0;
            left: 0;
            display: none;
        }

        .existing-gallery-wrap {
            margin-bottom: 20px;
        }

        .existing-gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 10px;
        }

        .gallery-thumb-card {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            background: #f5f5f5;
            border: 1px solid #ececec;
            min-height: 96px;
        }

        .gallery-thumb-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .gallery-thumb-card.removing {
            opacity: 0.45;
        }

        .gallery-remove-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            border: none;
            border-radius: 999px;
            background: rgba(0, 0, 0, 0.72);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 7px 10px;
            cursor: pointer;
        }

        .gallery-remove-btn.marked {
            background: #ff3b30;
        }

        .gallery-thumb-status {
            position: absolute;
            left: 8px;
            bottom: 8px;
            background: rgba(0, 0, 0, 0.72);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            border-radius: 999px;
            padding: 5px 8px;
            display: none;
        }

        .gallery-thumb-card.removing .gallery-thumb-status {
            display: inline-flex;
        }

        .input-box {
            background: #f0f0f0;
            border: none;
            border-radius: 8px;
            padding: 12px 15px;
            width: 100%;
            font-size: 14px;
            margin-bottom: 15px;
            box-sizing: border-box;
        }

        .price-row {
            display: flex;
            gap: 15px;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #2196F3;
        }

        input:checked+.slider:before {
            transform: translateX(24px);
        }

        .btn-yellow {
            background-color: #d4ac0d;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            width: 48%;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-blue {
            background-color: #007aff;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            width: 48%;
            font-weight: bold;
            cursor: pointer;
            float: right;
        }

        /* Modal for Variation Selection */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 400px;
            padding: 20px;
            border-radius: 15px;
        }

        .var-group {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .var-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .var-opt {
            display: inline-block;
            padding: 5px 10px;
            background: #eee;
            border-radius: 5px;
            margin: 3px;
            cursor: pointer;
            user-select: none;
        }

        .var-opt.selected {
            background: #007aff;
            color: white;
        }

                /* Loading */
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: 2000;
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007aff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    
            /* Multi-Category List Styles */
        .dropdown-trigger {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f0f0f0; /* Matches input-box */
            margin-bottom: 0 !important; /* Touch the list below */
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .cat-list-box {
            background: #fff;
            border: 1px solid #ccc;
            border-top: none; /* Merge with trigger */
            border-radius: 0 0 8px 8px;
            padding: 10px;
            max-height: 200px;
            overflow-y: auto;
            margin-bottom: 20px;
            display: none; /* Hidden by default */
        }

        .cat-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .cat-item:last-child {
            border-bottom: none;
        }

        .cat-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .cat-name {
            font-size: 14px;
            color: #333;
            cursor: pointer;
        }

        .sub-cat-indent {
            margin-left: 25px;
            border-left: 2px solid #eee;
            padding-left: 10px;
        }

        .stock-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .stock-panel {
            background: #fff;
            border: 1px solid #e8e8e8;
            border-radius: 12px;
            padding: 14px;
            margin-top: 12px;
        }

        .stock-row {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 12px;
        }

        .stock-row > * {
            flex: 1;
        }

        .variant-stock-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 12px;
        }

        .variant-stock-table th,
        .variant-stock-table td {
            border-bottom: 1px solid #f0f0f0;
            padding: 10px 8px;
            text-align: left;
            vertical-align: top;
        }

        .variant-stock-table th {
            color: #777;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .variant-stock-table input,
        .variant-stock-table select {
            width: 100%;
            margin-bottom: 0;
            font-size: 12px;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background: #fff;
            box-sizing: border-box;
        }

        .variant-stock-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .btn-soft {
            background: #f3f6ff;
            color: #1f5eff;
            border: 1px solid #d9e4ff;
            padding: 10px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
        }

        .btn-soft-danger {
            background: #fff1f0;
            color: #d83b31;
            border: 1px solid #ffd6d1;
        }

        .draft-alert {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: #fff7e8;
            border: 1px solid #ffd79a;
            color: #7a5200;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 13px;
        }

        .draft-alert.show {
            display: flex;
        }

        .draft-alert.success {
            background: #eefbf3;
            border-color: #b7e5c6;
            color: #1f6b3d;
        }

        .draft-alert button {
            border: none;
            background: transparent;
            color: inherit;
            font-weight: 700;
            cursor: pointer;
            padding: 0;
            white-space: nowrap;
        }

        .validation-alert {
            display: none;
            background: #fff1f0;
            border: 1px solid #ffc9c5;
            color: #b42318;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 16px;
            font-size: 13px;
            font-weight: 700;
        }

        .validation-alert.show {
            display: block;
        }

        .field-error {
            border: 2px solid #ff5a4f !important;
            background: #fff8f7 !important;
            box-shadow: 0 0 0 4px rgba(255, 90, 79, 0.12);
        }

        @media (max-width: 640px) {
            .stock-grid,
            .stock-row,
            .images-container,
            .price-row {
                grid-template-columns: 1fr;
                flex-direction: column;
            }

            .btn-yellow,
            .btn-blue {
                width: 100%;
                float: none;
            }

            .variant-stock-actions {
                flex-direction: column;
            }

            .variant-stock-actions .btn-soft {
                width: 100%;
            }

            .existing-gallery-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .variant-stock-table,
            .variant-stock-table thead,
            .variant-stock-table tbody,
            .variant-stock-table tr,
            .variant-stock-table td {
                display: block;
                width: 100%;
            }

            .variant-stock-table {
                margin-top: 14px;
            }

            .variant-stock-table thead {
                display: none;
            }

            .variant-stock-table tr {
                background: #fafafa;
                border: 1px solid #ececec;
                border-radius: 14px;
                padding: 12px;
                margin-bottom: 12px;
            }

            .variant-stock-table td {
                border: none;
                padding: 0;
                margin-bottom: 10px;
            }

            .variant-stock-table td:last-child {
                margin-bottom: 0;
            }

            .variant-stock-table td::before {
                content: attr(data-label);
                display: block;
                font-size: 10px;
                font-weight: 800;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                color: #777;
                margin-bottom: 4px;
            }

            .variant-stock-table td[data-label="Remove"]::before {
                display: none;
            }

            .variant-stock-table td[data-label="Active"] input[type="checkbox"] {
                width: 20px;
                height: 20px;
            }

            .variant-stock-table td[data-label="Remove"] .btn-soft-danger {
                width: 100%;
            }

            #variantStockEmptyState td::before {
                display: none;
            }
        }

        @media (min-width: 992px) {
            .container {
                max-width: 1120px;
                padding: 34px 30px 40px;
            }

            .header-bar {
                margin-bottom: 28px;
            }

            .section-label {
                font-size: 15px;
                margin-top: 24px;
            }

            .images-container,
            .price-row,
            .stock-grid {
                gap: 18px;
            }

            .main-img-box,
            .gallery-box {
                min-height: 180px;
                border-radius: 18px;
            }

            .input-box {
                font-size: 15px;
                padding: 14px 16px;
                border-radius: 12px;
            }

            .existing-gallery-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }

            .stock-panel {
                border-radius: 18px;
                padding: 18px;
                box-shadow: 0 16px 36px rgba(17, 24, 39, 0.06);
            }
        }
    </style>
</head>

<body>
    <?php require_once ROOT_PATH . 'helpers/ImageHelper.php'; ?>

    <!-- Global Loader Injection -->
    <?php include 'views/admin/partials/loader.php'; ?>


    <!-- Form -->
    <form action="<?= BASE_URL ?>product/<?= isset($mode) && $mode === 'edit' ? 'update' : 'store' ?>" method="POST"
        enctype="multipart/form-data" id="productForm" novalidate>
        <?= csrf_input() ?>
        <div class="container" style="padding-bottom: 80px;">

            <div class="header-bar">
                <a href="<?= BASE_URL ?>product/index" class="back-circle">❮</a>
                <div>
                    <h2 style="margin:0;"><?= isset($mode) && $mode === 'edit' ? 'Edit Product' : 'Add Product' ?></h2>
                    <p style="margin:0; font-size:11px; color:#888;">List New Items in One Minute...</p>
                </div>
            </div>

            <div id="draftAlert" class="draft-alert">
                <span id="draftAlertText"></span>
                <button type="button" id="discardDraftBtn">Forget Saved Draft</button>
            </div>

            <div id="productValidationAlert" class="validation-alert <?= !empty($product_form_error) ? 'show' : '' ?>"><?= htmlspecialchars($product_form_error ?? '') ?></div>

            <?php if (isset($mode) && $mode === 'edit'): ?>
                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                <input type="hidden" name="current_main_image" value="<?= $product['main_image'] ?>">
            <?php endif; ?>

            <!-- Images -->
            <span class="section-label">Product Images</span>
            <span class="sub-label">Maximum Size of each photo to upload: 800Kb</span>

                <div class="images-container">
                <!-- Main Image -->
                <div class="main-img-box" id="mainImageBox" onclick="document.getElementById('mainImgInput').click()">
                    <?php if (isset($mode) && $mode === 'edit' && !empty($product['main_image'])): ?>
                        <?php $mainPreviewImage = ImageHelper::uploadUrl($product['main_image'], ''); ?>
                        <img id="mainPreview" class="preview-img"
                            src="<?= htmlspecialchars($mainPreviewImage) ?>" style="display:block;">
                        <div id="mainPlaceholder" style="display:none;">
                        <?php else: ?>
                            <img id="mainPreview" class="preview-img">
                            <div id="mainPlaceholder">
                            <?php endif; ?>
                            <div style="font-size:24px;">📷</div>
                            <p style="font-size:10px; color:#555;">Tap here to<br>upload a photo</p>
                        </div>
                        <input type="file" name="main_image" id="mainImgInput" style="display:none;" accept="image/*"
                            <?= (isset($mode) && $mode === 'edit' && !empty($product['main_image'])) ? '' : 'required' ?>>
                    </div>

                    <!-- Gallery -->
                    <div class="gallery-box" onclick="document.getElementById('galImgInput').click()">
                        <!-- Show count if selected -->
                        <div id="galPlaceholder">
                            <div style="font-size:24px;">📷 📷 📷</div>
                            <p style="font-size:10px; color:#555;">Tap here to upload photos<br>Max: 10 Photos</p>
                        </div>
                        <p id="galCount" style="display:none; font-weight:bold; color:#007aff;">0 Selected</p>
                        <input type="file" name="gallery_images[]" id="galImgInput" style="display:none;"
                            accept="image/*" multiple>
                    </div>
                </div>

                <?php if (isset($mode) && $mode === 'edit' && !empty($product['gallery_image_records'])): ?>
                    <div class="existing-gallery-wrap">
                        <span class="section-label" style="margin-top:0;">Current Gallery Images</span>
                        <span class="sub-label">Tap Remove on any image you want to delete when you save this product.</span>
                        <div class="existing-gallery-grid">
                            <?php foreach ($product['gallery_image_records'] as $galleryImage): ?>
                                <?php $galleryThumbUrl = ImageHelper::uploadUrl($galleryImage['image_path'] ?? '', ''); ?>
                                <div class="gallery-thumb-card" id="gallery-card-<?= (int) $galleryImage['id'] ?>">
                                    <img src="<?= htmlspecialchars($galleryThumbUrl) ?>" alt="Gallery image">
                                    <button type="button" class="gallery-remove-btn"
                                        onclick="toggleGalleryImageRemoval(<?= (int) $galleryImage['id'] ?>, this)">Remove</button>
                                    <span class="gallery-thumb-status">Will be deleted</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div id="removedGalleryImageInputs"></div>
                    </div>
                <?php endif; ?>

                <!-- Category -->
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span class="section-label">Select Categories <span style="color:red">*</span></span>
                    <a href="javascript:void(0)"
                        onclick="openIframeModal('<?= BASE_URL ?>category/index', 'Manage Categories')"
                        style="font-size:12px; color:#007aff; text-decoration:none; font-weight:600;">+ Add / Manage
                        Categories</a>
                </div>
                                <!-- Hidden Input for Backward Compatibility (Primary Category) -->
                <input type="hidden" name="category_id" id="primaryCatInput" required
                    value="<?= $product['category_id'] ?? '' ?>">

               
                    <!-- Multi-Check Dropdown Trigger -->
                <div class="input-box dropdown-trigger" id="categoryTrigger" onclick="toggleCatDropdown()">
                    <span id="catTriggerText">Select Categories...</span>
                    <span id="catArrow" style="font-size:12px; color:#999;">▼</span>
                </div>

                <!-- Multi-Check List (Hidden) -->
                <div class="cat-list-box" id="catListContainer">
                    <?php foreach ($categories as $cat): ?>
                        <?php if (!$cat['parent_id']): ?>
                            <!-- Main Category -->
                            <div class="cat-item">
                                <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>"
                                    class="cat-checkbox" onchange="updatePrimaryCat()"
                                    <?= ( (isset($product['category_id']) && $product['category_id'] == $cat['id']) || (isset($product['categories']) && in_array($cat['id'], $product['categories'])) ) ? 'checked' : '' ?>>
                                <span class="cat-name" onclick="this.previousElementSibling.click()">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </span>
                            </div>

                            <!-- Sub Categories -->
                            <?php foreach ($categories as $sub): ?>
                                <?php if ($sub['parent_id'] == $cat['id']): ?>
                                    <div class="cat-item sub-cat-indent">
                                        <input type="checkbox" name="categories[]" value="<?= $sub['id'] ?>"
                                            class="cat-checkbox" onchange="updatePrimaryCat()"
                                            <?= ( (isset($product['category_id']) && $product['category_id'] == $sub['id']) || (isset($product['categories']) && in_array($sub['id'], $product['categories'])) ) ? 'checked' : '' ?>>
                                        <span class="cat-name" onclick="this.previousElementSibling.click()">
                                            <?= htmlspecialchars($sub['name']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>


                <!-- Info -->
                <span class="section-label">Product Title <span style="color:red">*</span></span>
                <input type="text" name="title" class="input-box" placeholder="Enter product name here..."
                    value="<?= htmlspecialchars($product['title'] ?? '') ?>" required>

                <span class="section-label">Price</span>
                <div class="price-row">
                    <input type="number" name="price" class="input-box" placeholder="Normal Price" step="0.01"
                        value="<?= $product['price'] ?? '' ?>" required>
                    <input type="number" name="sale_price" class="input-box" placeholder="Discounted Price" step="0.01"
                        style="background:#ffeaea;" value="<?= $product['sale_price'] ?? '' ?>">
                </div>

                <span class="section-label">Product Weight (g)</span>
                <input type="number" name="weight_grams" class="input-box" min="0" step="1"
                    placeholder="Enter product weight in grams"
                    value="<?= htmlspecialchars((string) ($product['weight_grams'] ?? '0')) ?>">

                <span class="section-label">Product Description</span>
                <textarea name="description" class="input-box" rows="4"
                    placeholder="You can use external links, emojis... 🌸"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>

                <span class="section-label">Product Short Description</span>
                <textarea name="short_description" class="input-box" rows="3"
                    placeholder="Short summary shown on the single product page"><?= htmlspecialchars($product['short_description'] ?? '') ?></textarea>

                <!-- Size Guide -->
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span class="section-label">Size Guide</span>
                    <a href="javascript:void(0)"
                        onclick="openIframeModal('<?= BASE_URL ?>sizeGuide/index', 'Manage Size Guides')"
                        style="font-size:12px; color:#007aff; text-decoration:none; font-weight:600;">+ Add / Manage
                        Guides</a>
                </div>
                <select name="size_guide_id" class="input-box">
                    <option value="">+ Click here to select Size Guides</option>
                    <?php foreach ($sizeGuides as $sg): ?>
                        <option value="<?= $sg['id'] ?>" <?= (isset($product['size_guide_id']) && $product['size_guide_id'] == $sg['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sg['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- SKU -->
                <span class="section-label">Product Code (SKU)</span>
                <input type="text" name="sku" class="input-box" placeholder="Enter product name here..."
                    value="<?= htmlspecialchars($product['sku'] ?? '') ?>">

                <!-- Featured -->
                <span class="section-label">Featured Product</span>
                <label class="toggle-switch">
                    <input type="checkbox" name="is_featured" <?= (isset($product['is_featured']) && $product['is_featured']) ? 'checked' : '' ?>>
                    <span class="slider"></span>
                </label>

                <span class="section-label" style="margin-top:20px;">Free Shipping</span>
                <label class="toggle-switch">
                    <input type="checkbox" name="free_shipping" <?= !empty($product['free_shipping']) ? 'checked' : '' ?>>
                    <span class="slider"></span>
                </label>

                <div style="margin-top: 24px;">
                    <button type="button" class="btn-yellow" onclick="openVarModal()">Add Variations</button>
                </div>

                <span class="section-label" style="margin-top:20px;">Stock Management</span>
                <div class="stock-grid">
                    <div>
                        <span class="sub-label">Choose how this product should be sold</span>
                        <select name="stock_mode" id="stockModeInput" class="input-box" onchange="toggleStockPanels()">
                            <?php
                                $rawStockMode = $product['stock_mode'] ?? 'always_in_stock';
                                $stockMode = in_array($rawStockMode, ['track_stock', 'manual_out_of_stock'], true) ? $rawStockMode : 'always_in_stock';
                            ?>
                            <option value="always_in_stock" <?= $stockMode === 'always_in_stock' ? 'selected' : '' ?>>Always in Stock</option>
                            <option value="track_stock" <?= $stockMode === 'track_stock' ? 'selected' : '' ?>>Track Product Stock</option>
                            <option value="manual_out_of_stock" <?= $stockMode === 'manual_out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                        </select>
                    </div>
                    <div>
                        <span class="sub-label">Variation stock appears only when stock tracking is enabled</span>
                        <div class="input-box" id="stockModeHintBox" style="display:flex; align-items:center; color:#666; min-height:46px;">Exact variation stock matrix for tracked products</div>
                    </div>
                </div>
                <input type="hidden" name="manual_stock_status" id="manualStockStatusHidden" value="<?= $stockMode === 'manual_out_of_stock' ? 'out_of_stock' : 'in_stock' ?>">

                <div id="simpleStockPanel" class="stock-panel">
                    <div class="stock-row">
                        <div>
                            <span class="sub-label">Available quantity</span>
                            <input type="number" name="stock_qty" class="input-box" min="0" step="1"
                                value="<?= htmlspecialchars((string) ($product['stock_qty'] ?? '0')) ?>">
                        </div>
                        <div>
                            <span class="sub-label">Low stock alert threshold</span>
                            <input type="number" name="low_stock_threshold" class="input-box" min="0" step="1"
                                value="<?= htmlspecialchars((string) ($product['low_stock_threshold'] ?? '5')) ?>">
                        </div>
                    </div>
                </div>

                <div id="variantStockPanel" class="stock-panel">
                    <div style="display:flex; justify-content:space-between; gap:12px; align-items:center;">
                        <div>
                            <strong style="display:block; margin-bottom:4px;">Variation Stock Matrix</strong>
                            <span class="sub-label" style="margin:0;">Create only the exact variation combinations you really sell.</span>
                        </div>
                    </div>
                    <div class="variant-stock-actions">
                        <button type="button" class="btn-soft" onclick="generateVariantCombinations()">Generate Selected Combinations</button>
                        <button type="button" class="btn-soft btn-soft-danger" onclick="clearVariantCombinations()">Clear Matrix</button>
                    </div>
                    <table class="variant-stock-table">
                        <thead>
                            <tr>
                                <th>Combination</th>
                                <th>Price</th>
                                <th>Sale Price</th>
                                <th>Weight (g)</th>
                                <th>Image</th>
                                <th>SKU</th>
                                <th>Mode</th>
                                <th>Qty</th>
                                <th>Low Stock</th>
                                <th>Active</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="variantStockTableBody">
                            <tr id="variantStockEmptyState">
                                <td colspan="11" style="color:#777;">No exact combinations yet. Select variation values, then generate the combinations you actually sell.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn-blue" onclick="showGlobalLoader()">Publish</button>
                </div>

            </div>

            <!-- Variations Hidden Inputs container -->
            <div id="hiddenVars"></div>
            <input type="hidden" name="variant_stocks_json" id="variantStocksJson"
                value='<?= htmlspecialchars(json_encode($product["variant_stocks"] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'>

            <!-- Variations Modal -->
            <div class="modal-overlay" id="varModal">
                <div class="modal-content">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h3 style="margin:0;">Select Variations</h3>
                        <a href="javascript:void(0)" onclick="openIframeModal('<?= BASE_URL ?>variation/index', 'Manage Variations')"
                            style="font-size:12px; color:#007aff; text-decoration:none;">+ Manage Variations</a>
                    </div>
                    <p style="color:#666; font-size:12px;">Tap to select available options</p>

                    <div style="max-height: 300px; overflow-y: auto;" id="variationListContainer">
                        <?php foreach ($variations as $var): ?>
                            <div class="var-group">
                                <div class="var-title">
                                    <?= htmlspecialchars($var['name']) ?>
                                </div>
                                <div>
                                    <?php foreach ($var['values'] as $val): ?>
                                        <?php
                                        // Check if this value is selected in the product data
                                        $selected = '';
                                        $selectedVariationTokens = $product['selected_variation_tokens'] ?? [];
                                        if (isset($product['variations']) && is_array($product['variations'])) {
                                            // $product['variations'] is grouped: 'Color' => [[id=X, value=Y]]
                                            // We need to check if $val['id'] exists in any of the grouped arrays
                                            foreach ($product['variations'] as $group) {
                                                foreach ($group as $gItem) {
                                                    if ($gItem['id'] == $val['id']) {
                                                        $selected = 'selected';
                                                        break 2;
                                                    }
                                                }
                                            }
                                        }
                                        if ($selected === '' && in_array((string) $var['id'] . '_' . (string) $val['id'], $selectedVariationTokens, true)) {
                                            $selected = 'selected';
                                        }
                                        ?>
                                        <div class="var-opt <?= $selected ?>" data-id="<?= $var['id'] ?>_<?= $val['id'] ?>"
                                            onclick="toggleVar(this)">
                                            <?= htmlspecialchars($val['value']) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top:20px; text-align:right;">
                        <button type="button" class="btn-blue" style="width:100%;"
                            onclick="closeVarModal()">Done</button>
                    </div>
                </div>
            </div>

            <!-- Universal Iframe Modal -->
            <div class="modal-overlay" id="universalModal" style="z-index: 1001;">
                <div class="modal-content"
                    style="width: 95%; max-width: 600px; height: 80vh; display:flex; flex-direction:column; padding:0;">
                    <div
                        style="padding: 15px; border-bottom: 1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                        <h3 style="margin:0;" id="universalModalTitle">Manage Items</h3>
                        <button type="button" onclick="closeIframeModal()"
                            style="border:none; background:none; font-size:20px; cursor:pointer;">&times;</button>
                    </div>
                    <iframe id="universalFrame" src="" style="flex:1; border:none; width:100%;"></iframe>
                </div>
            </div>

    </form>

    <script>
        const initialVariantStockRows = <?= json_encode($product['variant_stocks'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        let variantStockRows = Array.isArray(initialVariantStockRows) ? initialVariantStockRows : [];
        variantStockRows = variantStockRows.map(row => {
            if (row && row.stock_mode === 'manual_out_of_stock') {
                return {
                    ...row,
                    stock_mode: 'always_in_stock',
                    is_active: false,
                    manual_stock_status: 'in_stock'
                };
            }
            return row;
        });
        const baseVariantPrice = <?= json_encode((float) ($product['sale_price'] ?? $product['price'] ?? 0)) ?>;
        const baseVariantRegularPrice = <?= json_encode((float) ($product['price'] ?? 0)) ?>;
        const baseVariantSalePrice = <?= json_encode(!empty($product['sale_price']) ? (float) $product['sale_price'] : null) ?>;
        const baseVariantWeight = <?= json_encode((int) ($product['weight_grams'] ?? 0)) ?>;
        const productForm = document.getElementById('productForm');
        const draftAlert = document.getElementById('draftAlert');
        const draftAlertText = document.getElementById('draftAlertText');
        const discardDraftBtn = document.getElementById('discardDraftBtn');
        const isEditMode = <?= isset($mode) && $mode === 'edit' ? 'true' : 'false' ?>;
        const draftStorageKey = isEditMode
            ? 'fz_product_form_draft_edit_<?= (int) ($product['id'] ?? 0) ?>'
            : 'fz_product_form_draft_add';
        const trackedFieldNames = ['title', 'price', 'sale_price', 'weight_grams', 'description', 'short_description', 'size_guide_id', 'sku', 'stock_mode', 'stock_qty', 'low_stock_threshold'];
        const initialServerValidation = {
            message: <?= json_encode((string) ($product_form_error ?? '')) ?>,
            field: <?= json_encode((string) ($product_form_error_field ?? '')) ?>
        };
        let isSubmittingProductForm = false;
        let suppressDraftTracking = false;
        let saveDraftTimer = null;
        let initialDraftFingerprint = '';
        let allowIntentionalNavigation = false;

        function showDraftAlert(message, tone = 'warning', allowDiscard = true) {
            if (!draftAlert || !draftAlertText) return;
            draftAlert.classList.add('show');
            draftAlert.classList.remove('success');
            if (tone === 'success') {
                draftAlert.classList.add('success');
            }
            draftAlertText.textContent = message;
            if (discardDraftBtn) {
                discardDraftBtn.style.display = allowDiscard ? 'inline-block' : 'none';
            }
        }

        function hideDraftAlert() {
            if (!draftAlert) return;
            draftAlert.classList.remove('show', 'success');
        }

        function getCurrentDraftState() {
            const fields = {};
            trackedFieldNames.forEach(name => {
                const field = productForm.elements.namedItem(name);
                fields[name] = field ? field.value : '';
            });

            return {
                fields,
                toggles: {
                    is_featured: !!productForm.querySelector('input[name="is_featured"]')?.checked,
                    free_shipping: !!productForm.querySelector('input[name="free_shipping"]')?.checked
                },
                categories: Array.from(document.querySelectorAll('input[name="categories[]"]:checked')).map(cb => cb.value),
                selectedVariations: Array.from(document.querySelectorAll('.var-opt.selected')).map(el => el.dataset.id),
                removedGalleryImageIds: Array.from(document.querySelectorAll('input[name="remove_gallery_image_ids[]"]')).map(input => input.value),
                variantStockRows,
                files: {
                    mainImageSelected: (document.getElementById('mainImgInput')?.files.length || 0) > 0,
                    galleryImageCount: document.getElementById('galImgInput')?.files.length || 0
                }
            };
        }

        function getDraftFingerprint(state) {
            return JSON.stringify(state);
        }

        function saveDraftNow() {
            if (isSubmittingProductForm || suppressDraftTracking) {
                return;
            }

            const draftState = getCurrentDraftState();
            const draftFingerprint = getDraftFingerprint(draftState);

            if (draftFingerprint === initialDraftFingerprint) {
                localStorage.removeItem(draftStorageKey);
                hideDraftAlert();
                return;
            }

            localStorage.setItem(draftStorageKey, JSON.stringify({
                ...draftState,
                saved_at: Date.now()
            }));
            showDraftAlert('Draft auto-saved on this device. You can safely come back later.', 'success');
        }

        function scheduleDraftSave() {
            if (suppressDraftTracking || isSubmittingProductForm) {
                return;
            }

            window.clearTimeout(saveDraftTimer);
            saveDraftTimer = window.setTimeout(saveDraftNow, 300);
        }

        function hasUnsavedChanges() {
            return getDraftFingerprint(getCurrentDraftState()) !== initialDraftFingerprint;
        }

        function clearSavedDraft(showMessage = false) {
            window.clearTimeout(saveDraftTimer);
            localStorage.removeItem(draftStorageKey);
            if (showMessage) {
                showDraftAlert('Saved draft cleared for this product form.', 'success', false);
                window.setTimeout(hideDraftAlert, 2200);
            } else {
                hideDraftAlert();
            }
        }

        function restoreDraftFromStorage() {
            const rawDraft = localStorage.getItem(draftStorageKey);
            if (!rawDraft) {
                return;
            }

            let draftState;
            try {
                draftState = JSON.parse(rawDraft);
            } catch (error) {
                localStorage.removeItem(draftStorageKey);
                return;
            }

            if (!window.confirm('Unsaved product draft found. Do you want to restore it now?')) {
                clearSavedDraft();
                return;
            }

            suppressDraftTracking = true;

            trackedFieldNames.forEach(name => {
                const field = productForm.elements.namedItem(name);
                if (field && draftState.fields && Object.prototype.hasOwnProperty.call(draftState.fields, name)) {
                    field.value = draftState.fields[name];
                }
            });

            const featuredInput = productForm.querySelector('input[name="is_featured"]');
            if (featuredInput) {
                featuredInput.checked = !!draftState.toggles?.is_featured;
            }

            const freeShippingInput = productForm.querySelector('input[name="free_shipping"]');
            if (freeShippingInput) {
                freeShippingInput.checked = !!draftState.toggles?.free_shipping;
            }

            const selectedCategories = new Set((draftState.categories || []).map(String));
            document.querySelectorAll('input[name="categories[]"]').forEach(checkbox => {
                checkbox.checked = selectedCategories.has(String(checkbox.value));
            });
            updatePrimaryCat();

            document.querySelectorAll('.var-opt.selected').forEach(el => el.classList.remove('selected'));
            const selectedVariations = new Set((draftState.selectedVariations || []).map(String));
            document.querySelectorAll('.var-opt').forEach(el => {
                if (selectedVariations.has(String(el.dataset.id))) {
                    el.classList.add('selected');
                }
            });
            populateHiddenVars();

            document.querySelectorAll('input[name="remove_gallery_image_ids[]"]').forEach(input => input.remove());
            document.querySelectorAll('.gallery-thumb-card.removing').forEach(card => card.classList.remove('removing'));
            document.querySelectorAll('.gallery-remove-btn.marked').forEach(btn => btn.classList.remove('marked'));
            const removedGalleryImageIds = new Set((draftState.removedGalleryImageIds || []).map(String));
            removedGalleryImageIds.forEach(id => {
                toggleGalleryImageRemoval(id);
            });

            variantStockRows = Array.isArray(draftState.variantStockRows) ? draftState.variantStockRows : [];
            renderVariantStockRows();
            toggleStockPanels();

            suppressDraftTracking = false;

            const imageWarning = draftState.files?.mainImageSelected || Number(draftState.files?.galleryImageCount || 0) > 0
                ? ' Image files could not be restored, so please re-upload them.'
                : '';
            showDraftAlert('Unsaved draft restored.' + imageWarning, 'success');
        }

        function normalizeVariantKey(values) {
            return values
                .slice()
                .sort((a, b) => Number(a.variation_id) - Number(b.variation_id))
                .map(v => `${v.variation_id}:${v.variation_value_id}`)
                .join('|');
        }

        function renderVariantStockRows() {
            const tbody = document.getElementById('variantStockTableBody');
            if (!tbody) return;

            if (!variantStockRows.length) {
                tbody.innerHTML = `<tr id="variantStockEmptyState"><td colspan="11" style="color:#777;">No exact combinations yet. Select variation values, then generate the combinations you actually sell.</td></tr>`;
                syncVariantStocksJson();
                return;
            }

            tbody.innerHTML = variantStockRows.map((row, index) => `
                <tr>
                    <td data-label="Combination">
                        <div style="font-weight:800; font-size:15px; color:#128244; background:#edf9f0; border:1px solid #d8f0dd; border-radius:12px; padding:9px 10px;">${escapeHtml(row.combination_label || row.combination_key)}</div>
                        <div style="font-size:11px; color:#888; margin-top:4px;">${row.combination_key}</div>
                    </td>
                    <td data-label="Price"><input type="number" min="0" step="0.01" value="${Number(row.variant_price ?? baseVariantRegularPrice)}" onchange="updateVariantRow(${index}, 'variant_price', this.value)"></td>
                    <td data-label="Sale Price"><input type="number" min="0" step="0.01" value="${row.variant_sale_price !== null && row.variant_sale_price !== undefined ? Number(row.variant_sale_price) : (baseVariantSalePrice !== null ? Number(baseVariantSalePrice) : '')}" onchange="updateVariantRow(${index}, 'variant_sale_price', this.value)"></td>
                    <td data-label="Weight (g)"><input type="number" min="0" step="1" value="${Number(row.variant_weight_grams ?? baseVariantWeight)}" onchange="updateVariantRow(${index}, 'variant_weight_grams', this.value)"></td>
                    <td data-label="Image">
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            ${row.image_url ? `<img src="${escapeHtml(row.image_url)}" alt="Variant image" style="width:48px; height:48px; object-fit:cover; border-radius:10px; border:1px solid #e5e5e5;">` : `<div style="width:48px; height:48px; border-radius:10px; border:1px dashed #d8d8d8; display:flex; align-items:center; justify-content:center; color:#999; font-size:10px;">No image</div>`}
                            <input type="hidden" name="variant_image_keys[]" value="${escapeHtml(row.combination_key || '')}">
                            <input type="file" name="variant_image_files[]" accept="image/*" onchange="updateVariantImageMeta(${index}, this)">
                            <div style="font-size:11px; color:#777;">${row.image_path ? escapeHtml(row.image_path) : 'Uses product image on storefront if empty.'}</div>
                        </div>
                    </td>
                    <td data-label="SKU"><input type="text" value="${escapeHtml(row.sku || '')}" onchange="updateVariantRow(${index}, 'sku', this.value)"></td>
                    <td data-label="Mode">
                        <select onchange="updateVariantMode(${index}, this.value)">
                            <option value="always_in_stock" ${(row.stock_mode || 'always_in_stock') === 'always_in_stock' ? 'selected' : ''}>Always In Stock</option>
                            <option value="track_stock" ${row.stock_mode === 'track_stock' ? 'selected' : ''}>Track Stock</option>
                        </select>
                    </td>
                    <td data-label="Qty">${renderVariantStockField(index, 'stock_qty', row)}</td>
                    <td data-label="Low Stock">${renderVariantStockField(index, 'low_stock_threshold', row)}</td>
                    <td data-label="Active" style="text-align:center;">
                        <input type="checkbox" ${row.is_active ? 'checked' : ''} onchange="updateVariantRow(${index}, 'is_active', this.checked)">
                    </td>
                    <td data-label="Remove">
                        <button type="button" class="btn-soft btn-soft-danger" onclick="removeVariantRow(${index})">Remove</button>
                    </td>
                </tr>
            `).join('');
            syncVariantStocksJson();
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function updateVariantRow(index, key, value) {
            if (!variantStockRows[index]) return;
            variantStockRows[index][key] = ['stock_qty', 'low_stock_threshold', 'variant_weight_grams'].includes(key)
                ? Number(value || 0)
                : (['variant_price', 'variant_sale_price'].includes(key)
                    ? (value === '' ? null : Number(value || 0))
                    : value);
            if (key === 'is_active') {
                variantStockRows[index][key] = !!value;
            }
            syncVariantStocksJson();
            scheduleDraftSave();
        }

        function updateVariantMode(index, value) {
            if (!variantStockRows[index]) return;
            variantStockRows[index].stock_mode = value === 'track_stock' ? 'track_stock' : 'always_in_stock';
            variantStockRows[index].manual_stock_status = 'in_stock';
            renderVariantStockRows();
            scheduleDraftSave();
        }

        function renderVariantStockField(index, key, row) {
            const isTracked = String(row.stock_mode || 'always_in_stock') === 'track_stock';
            if (!isTracked) {
                return `<div style="font-size:11px; color:#999; padding:11px 0;">Only for Track Stock</div>`;
            }
            const value = key === 'low_stock_threshold'
                ? Number(row.low_stock_threshold || 5)
                : Number(row.stock_qty || 0);
            return `<input type="number" min="0" step="1" value="${value}" onchange="updateVariantRow(${index}, '${key}', this.value)">`;
        }

        function updateVariantImageMeta(index, input) {
            if (!variantStockRows[index]) return;
            const file = input?.files?.[0] || null;
            if (!file) return;

            variantStockRows[index].image_path = file.name;
            variantStockRows[index].image_url = '';
            syncVariantStocksJson();
            scheduleDraftSave();
        }

        function removeVariantRow(index) {
            variantStockRows.splice(index, 1);
            renderVariantStockRows();
            scheduleDraftSave();
        }

        function clearVariantCombinations() {
            variantStockRows = [];
            renderVariantStockRows();
            scheduleDraftSave();
        }

        function getSelectedVariationGroups() {
            const grouped = {};
            document.querySelectorAll('.var-opt.selected').forEach(el => {
                const [variationId, variationValueId] = (el.dataset.id || '').split('_');
                const variationName = el.closest('.var-group')?.querySelector('.var-title')?.textContent?.trim() || 'Variation';
                if (!grouped[variationId]) {
                    grouped[variationId] = {
                        variation_id: Number(variationId),
                        variation_name: variationName,
                        values: []
                    };
                }
                grouped[variationId].values.push({
                    variation_id: Number(variationId),
                    variation_value_id: Number(variationValueId),
                    variation_name: variationName,
                    variation_value: el.textContent.trim()
                });
            });

            return Object.values(grouped).filter(group => group.values.length > 0);
        }

        function cartesianProduct(groups, index = 0, current = [], result = []) {
            if (index >= groups.length) {
                result.push(current.slice());
                return result;
            }

            groups[index].values.forEach(value => {
                current.push(value);
                cartesianProduct(groups, index + 1, current, result);
                current.pop();
            });
            return result;
        }

        function generateVariantCombinations() {
            const groups = getSelectedVariationGroups();
            if (!groups.length) {
                alert('Select variation values first to generate exact combinations.');
                return;
            }

            const combos = cartesianProduct(groups);
            const existingKeys = new Set(variantStockRows.map(row => row.combination_key));
            combos.forEach(combo => {
                const combinationKey = normalizeVariantKey(combo);
                if (existingKeys.has(combinationKey)) {
                    return;
                }

                variantStockRows.push({
                    combination_key: combinationKey,
                    combination_label: combo.map(item => `${item.variation_name}: ${item.variation_value}`).join(' / '),
                    variant_price: baseVariantRegularPrice,
                    variant_sale_price: baseVariantSalePrice,
                    variant_weight_grams: baseVariantWeight,
                    image_path: '',
                    image_url: '',
                    sku: '',
                    stock_mode: 'always_in_stock',
                    stock_qty: 0,
                    low_stock_threshold: 5,
                    manual_stock_status: 'in_stock',
                    is_active: true,
                    values: combo
                });
                existingKeys.add(combinationKey);
            });

            renderVariantStockRows();
            scheduleDraftSave();
        }

        function syncVariantStocksJson() {
            const input = document.getElementById('variantStocksJson');
            if (input) {
                input.value = JSON.stringify(variantStockRows);
            }
        }

        function toggleStockPanels() {
            const stockMode = document.getElementById('stockModeInput')?.value || 'always_in_stock';
            const simplePanel = document.getElementById('simpleStockPanel');
            const variantPanel = document.getElementById('variantStockPanel');
            const manualStockStatusHidden = document.getElementById('manualStockStatusHidden');
            const stockModeHintBox = document.getElementById('stockModeHintBox');
            const selectedGroups = getSelectedVariationGroups();
            const hasVariantSelections = selectedGroups.length > 0 || variantStockRows.length > 0;
            const shouldShowVariantPanel = (stockMode === 'track_stock' || stockMode === 'always_in_stock') && hasVariantSelections;
            const isTrackingStock = stockMode === 'track_stock';

            if (manualStockStatusHidden) {
                manualStockStatusHidden.value = stockMode === 'manual_out_of_stock' ? 'out_of_stock' : 'in_stock';
            }
            if (simplePanel) {
                simplePanel.style.display = isTrackingStock && !hasVariantSelections ? 'block' : 'none';
            }
            if (variantPanel) {
                variantPanel.style.display = shouldShowVariantPanel ? 'block' : 'none';
            }
            if (stockModeHintBox) {
                stockModeHintBox.textContent = stockMode === 'manual_out_of_stock'
                    ? 'This product will appear as out of stock on the website.'
                    : (shouldShowVariantPanel ? 'Exact variation matrix for the selected variant combinations.' : (isTrackingStock ? 'Track quantity for this simple product.' : 'This product will stay available without quantity tracking.'));
            }
        }

                // Auto-Refresh Logic (Added for Shop Owner Auto Updates)
            window.refreshCategories = function() {
            fetch('<?= BASE_URL ?>category/get_json')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('catListContainer');
                    // Get currently checked IDs to preserve selection
                    const checkedIds = Array.from(document.querySelectorAll('input[name="categories[]"]:checked')).map(cb => cb.value);
                    
                    let html = '';
                    
                    // 1. Main Categories
                    data.filter(c => !c.parent_id).forEach(main => {
                        const isChecked = checkedIds.includes(String(main.id)) ? 'checked' : '';
                        html += `
                        <div class="cat-item">
                            <input type="checkbox" name="categories[]" value="${main.id}" class="cat-checkbox" onchange="updatePrimaryCat()" ${isChecked}>
                            <span class="cat-name" onclick="this.previousElementSibling.click()">${main.name}</span>
                        </div>`;
                        
                        // 2. Sub Categories
                        data.filter(sub => sub.parent_id == main.id).forEach(child => {
                            const isSubChecked = checkedIds.includes(String(child.id)) ? 'checked' : '';
                            html += `
                            <div class="cat-item sub-cat-indent">
                                <input type="checkbox" name="categories[]" value="${child.id}" class="cat-checkbox" onchange="updatePrimaryCat()" ${isSubChecked}>
                                <span class="cat-name" onclick="this.previousElementSibling.click()">${child.name}</span>
                            </div>`;
                        });
                    });
                    
                    container.innerHTML = html;
                });
        };

        // NEW: Sync Checkboxes with Hidden Primary Input AND Update Label
        function updatePrimaryCat() {
            const checkboxes = document.querySelectorAll('input[name="categories[]"]:checked');
            const primaryInput = document.getElementById('primaryCatInput');
            const label = document.getElementById('catTriggerText');
            
            if (checkboxes.length > 0) {
                primaryInput.value = checkboxes[0].value;
                // Update Label
                label.innerText = checkboxes.length + " Category Selected"; 
                if(checkboxes.length > 1) label.innerText = checkboxes.length + " Categories Selected";
                
                label.style.fontWeight = "bold";
                label.style.color = "#007aff";
            } else {
                primaryInput.value = "";
                label.innerText = "Select Categories...";
                label.style.fontWeight = "normal";
                label.style.color = "#333";
            }

            scheduleDraftSave();
        }

        function toggleCatDropdown() {
            const box = document.getElementById('catListContainer');
            const arrow = document.getElementById('catArrow');
            if (box.style.display === 'block') {
                box.style.display = 'none';
                arrow.innerText = '▼';
            } else {
                box.style.display = 'block';
                arrow.innerText = '▲';
            }
        }




        window.refreshSizeGuides = function() {
            fetch('<?= BASE_URL ?>sizeGuide/get_json')
                .then(response => response.json())
                .then(data => {
                    const select = document.querySelector('select[name="size_guide_id"]');
                    const currentValue = select.value;
                    let html = '<option value="">+ Click here to select Size Guides</option>';
                    
                    data.forEach(sg => {
                        html += `<option value="${sg.id}">${sg.name}</option>`;
                    });
                    
                    select.innerHTML = html;
                    select.value = currentValue;
                });
        };

        window.refreshVariations = function() {
             fetch('<?= BASE_URL ?>variation/get_json')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('variationListContainer');
                    let html = '';
                    
                    data.forEach(varItem => {
                        html += `
                        <div class="var-group">
                            <div class="var-title">${varItem.name}</div>
                            <div>`;
                            
                        if(varItem.values) {
                            varItem.values.forEach(val => {
                                // Note: We lose 'selected' state on refresh for new items, 
                                // but existing selection logic handled via hidden inputs won't be visually broken
                                // until re-opened. Major goal is to see NEW items.
                                html += `<div class="var-opt" data-id="${varItem.id}_${val.id}" onclick="toggleVar(this)">${val.value}</div>`;
                            });
                        }
                        
                        html += `</div></div>`;
                    });
                    
                    container.innerHTML = html;
                    
                    // Re-apply selections if needed (optional advanced step), 
                    // for now we just want to see the new options.
                });
        };

        // Image Preview Logic
        document.getElementById('mainImgInput').addEventListener('change', function (e) {
            if (e.target.files && e.target.files[0]) {
                let reader = new FileReader();
                reader.onload = function (evt) {
                    const img = document.getElementById('mainPreview');
                    img.src = evt.target.result;
                    img.style.display = 'block';
                    document.getElementById('mainPlaceholder').style.display = 'none';
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        document.getElementById('galImgInput').addEventListener('change', function (e) {
            const count = e.target.files.length;
            if (count > 0) {
                document.getElementById('galPlaceholder').style.display = 'none';
                const txt = document.getElementById('galCount');
                txt.style.display = 'block';
                txt.innerText = count + " Photos Selected";
            }
        });

        function toggleGalleryImageRemoval(imageId, button = null) {
            const normalizedId = String(imageId);
            const card = document.getElementById('gallery-card-' + normalizedId);
            const inputsContainer = document.getElementById('removedGalleryImageInputs');
            if (!card || !inputsContainer) {
                return;
            }

            const existingInput = inputsContainer.querySelector('input[value="' + normalizedId + '"]');
            const actionButton = button || card.querySelector('.gallery-remove-btn');

            if (existingInput) {
                existingInput.remove();
                card.classList.remove('removing');
                if (actionButton) {
                    actionButton.classList.remove('marked');
                    actionButton.textContent = 'Remove';
                }
            } else {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'remove_gallery_image_ids[]';
                hiddenInput.value = normalizedId;
                inputsContainer.appendChild(hiddenInput);
                card.classList.add('removing');
                if (actionButton) {
                    actionButton.classList.add('marked');
                    actionButton.textContent = 'Undo';
                }
            }

            scheduleDraftSave();
        }

        // Modal Logic
        function openVarModal() { document.getElementById('varModal').style.display = 'flex'; }

        function closeVarModal() {
            document.getElementById('varModal').style.display = 'none';
            populateHiddenVars();
            scheduleDraftSave();
        }

        function toggleVar(el) {
            el.classList.toggle('selected');
            populateHiddenVars();
            toggleStockPanels();
            scheduleDraftSave();
        }

                // Universal Modal Logic (Fixed Glitch + Loader)
        function openIframeModal(url, title) {
            showGlobalLoader(); // Show loader immediately
            document.getElementById('universalModalTitle').innerText = title;
            const frame = document.getElementById('universalFrame');
            
            // Clear previous source to prevent "ghost" content
            frame.src = 'about:blank';
            
            frame.onload = function() {
                hideGlobalLoader(); // Hide when new content is ready
            };
            
            frame.src = url;
            document.getElementById('universalModal').style.display = 'flex';
        }

        function closeIframeModal() {
            document.getElementById('universalModal').style.display = 'none';
            document.getElementById('universalFrame').src = 'about:blank'; // Reset to blank
        }


        // Convert selections to hidden inputs for form submission
        function populateHiddenVars() {
            const container = document.getElementById('hiddenVars');
            container.innerHTML = '';
            const selected = document.querySelectorAll('.var-opt.selected');

            selected.forEach(el => {
                const val = el.getAttribute('data-id'); // varId_valId
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_variations[]';
                input.value = val;
                container.appendChild(input);
            });

            // Update button text to show count
            const btn = document.querySelector('.btn-yellow');
            if (selected.length > 0) {
                btn.innerText = "Variations (" + selected.length + ")";
            } else {
                btn.innerText = "Add Variations";
            }
        }

        const validationAlert = document.getElementById('productValidationAlert');

        function clearValidationErrors() {
            if (validationAlert) {
                validationAlert.classList.remove('show');
                validationAlert.textContent = '';
            }

            document.querySelectorAll('.field-error').forEach(function (element) {
                element.classList.remove('field-error');
            });
        }

        function showValidationError(message, element) {
            if (validationAlert) {
                validationAlert.textContent = message;
                validationAlert.classList.add('show');
            }

            if (element) {
                element.classList.add('field-error');
                if (typeof element.focus === 'function') {
                    element.focus({ preventScroll: true });
                }
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else if (validationAlert) {
                validationAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        function getValidationTarget(fieldName) {
            if (fieldName === 'category_id') {
                return document.getElementById('categoryTrigger');
            }
            if (fieldName === 'main_image') {
                return document.getElementById('mainImageBox');
            }
            return document.querySelector('[name="' + fieldName + '"]');
        }

        function getCheckedCategories() {
            return Array.from(document.querySelectorAll('input[name="categories[]"]:checked'));
        }

        function validateProductForm() {
            clearValidationErrors();

            const titleInput = document.querySelector('input[name="title"]');
            const priceInput = document.querySelector('input[name="price"]');
            const categoryTrigger = document.getElementById('categoryTrigger');
            const mainImageInput = document.getElementById('mainImgInput');
            const mainImageBox = document.getElementById('mainImageBox');
            const primaryCategoryInput = document.getElementById('primaryCatInput');

            if (titleInput && titleInput.value.trim() === '') {
                showValidationError('Please enter the product title.', titleInput);
                return false;
            }

            if (priceInput && priceInput.value.trim() === '') {
                showValidationError('Please enter the product price.', priceInput);
                return false;
            }

            if (getCheckedCategories().length === 0 || !primaryCategoryInput || primaryCategoryInput.value.trim() === '') {
                showValidationError('Please select at least one category.', categoryTrigger);
                return false;
            }

            if (mainImageInput && mainImageInput.required && (!mainImageInput.files || mainImageInput.files.length === 0)) {
                showValidationError('Please upload the main product image.', mainImageBox);
                return false;
            }

            return true;
        }

        // Form Submit Loading (Global)
        document.getElementById('productForm').addEventListener('submit', function (event) {
            if (!validateProductForm()) {
                event.preventDefault();
                hideGlobalLoader();
                return;
            }

            isSubmittingProductForm = true;
            localStorage.removeItem(draftStorageKey);
            showGlobalLoader();
        });

        // Trigger Loader on Image Uploads (Visual Feedback)
        document.getElementById('mainImgInput').addEventListener('change', function() {
            if(this.files.length > 0) showGlobalLoader();
            // Loader hides automatically via timeout in preview logic or manually below if instant
            setTimeout(hideGlobalLoader, 1000); // Simulate network delay for effect
            document.getElementById('mainImageBox').classList.remove('field-error');
            scheduleDraftSave();
        });

        document.getElementById('galImgInput').addEventListener('change', function() {
            if(this.files.length > 0) showGlobalLoader();
            setTimeout(hideGlobalLoader, 1000);
            scheduleDraftSave();
        });

        productForm.addEventListener('input', function (event) {
            if (event.target && event.target.matches('input, textarea, select')) {
                event.target.classList.remove('field-error');
                if (event.target.name === 'categories[]' || event.target.id === 'primaryCatInput') {
                    document.getElementById('categoryTrigger').classList.remove('field-error');
                }
                scheduleDraftSave();
            }
        }, true);

        productForm.addEventListener('change', function (event) {
            if (event.target && event.target.matches('input, textarea, select')) {
                event.target.classList.remove('field-error');
                if (event.target.name === 'categories[]' || event.target.id === 'primaryCatInput') {
                    document.getElementById('categoryTrigger').classList.remove('field-error');
                }
                scheduleDraftSave();
            }
        }, true);

        document.addEventListener('click', function (event) {
            const link = event.target.closest('a[href]');
            if (!link || isSubmittingProductForm || !hasUnsavedChanges()) {
                return;
            }

            const href = link.getAttribute('href') || '';
            if (!href || href.startsWith('javascript:') || href.startsWith('#') || link.target === '_blank') {
                return;
            }

            if (!window.confirm('You have unsaved product changes. Leave this page anyway?')) {
                event.preventDefault();
                return;
            }

            allowIntentionalNavigation = true;
            window.setTimeout(() => {
                allowIntentionalNavigation = false;
            }, 1000);
            saveDraftNow();
        }, true);

        window.addEventListener('beforeunload', function (event) {
            if (isSubmittingProductForm || allowIntentionalNavigation || !hasUnsavedChanges()) {
                return;
            }

            event.preventDefault();
            event.returnValue = '';
        });

        if (discardDraftBtn) {
            discardDraftBtn.addEventListener('click', function () {
                suppressDraftTracking = true;
                clearSavedDraft(true);
                suppressDraftTracking = false;
            });
        }

        window.addEventListener('load', function () {
            populateHiddenVars();
            updatePrimaryCat(); // Set initial label state
            renderVariantStockRows();
            toggleStockPanels();
            initialDraftFingerprint = getDraftFingerprint(getCurrentDraftState());
            if (initialServerValidation.message) {
                showValidationError(initialServerValidation.message, getValidationTarget(initialServerValidation.field));
            } else {
                restoreDraftFromStorage();
            }
            if (hasUnsavedChanges()) {
                scheduleDraftSave();
            }
        });
    </script>
</body>

</html>
