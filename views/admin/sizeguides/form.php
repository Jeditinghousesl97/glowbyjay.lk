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
        .upload-area {
            background: #f0f0f0;
            border-radius: 8px;
            padding: 30px 20px;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
            margin-top: 20px;
        }

        .guide-preview {
            width: 100%;
            max-width: 220px;
            border-radius: 8px;
            display: block;
            margin: 12px auto 0;
            object-fit: cover;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
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

        .save-txt {
            color: var(--primary-color);
            font-weight: bold;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 16px;
        }

        .current-image-note {
            font-size: 12px;
            color: #666;
            margin: 8px 0 0;
        }

        @media (min-width: 992px) {
            .container {
                max-width: 820px;
                padding: 34px 30px 40px;
            }

            .upload-area {
                border-radius: 18px;
                padding: 46px 24px;
            }

            .guide-preview {
                max-width: 320px;
                border-radius: 16px;
            }
        }
    </style>
</head>

<body>
    <?php require_once ROOT_PATH . 'helpers/ImageHelper.php'; ?>
    <?php include 'views/admin/partials/loader.php'; ?>
    <form action="<?= BASE_URL ?>sizeGuide/<?= $mode === 'edit' ? 'update' : 'store' ?>" method="POST" enctype="multipart/form-data"
        onsubmit="showGlobalLoader()">
        <?= csrf_input() ?>
        <div class="container">
            <?php if ($mode === 'edit'): ?>
                <input type="hidden" name="id" value="<?= $guide['id'] ?>">
            <?php endif; ?>

            <div class="header-bar">
                <div style="display:flex; gap:10px; align-items:center;">
                    <a href="<?= BASE_URL ?>sizeGuide/index" class="back-circle">&#10094;</a>
                    <h2 style="margin:0;"><?= $mode === 'edit' ? 'Edit Guide' : 'Add Guide' ?></h2>
                </div>
                <button type="submit" class="save-txt">SAVE</button>
            </div>

            <input type="text" name="name" class="form-control" placeholder="Size Guide Name"
                value="<?= htmlspecialchars($guide['name'] ?? '') ?>" required>

            <div class="upload-area" onclick="document.getElementById('guide-img').click()">
                <div id="guide-placeholder" style="<?= !empty($guide['image_path']) ? 'display:none;' : '' ?>">
                    <p style="color:#888; margin:0;">Size Guide Image</p>
                    <div style="font-size:24px; margin: 10px 0;">&#128247;</div>
                    <p style="font-size:10px; color:#aaa;">
                        <?= $mode === 'edit' ? 'Tap here to replace the current image' : 'Tap here to upload a photo from gallery' ?>
                    </p>
                </div>

                <div id="guide-current-image" style="<?= empty($guide['image_path']) ? 'display:none;' : '' ?>">
                    <?php if (!empty($guide['image_path'])): ?>
                        <?php $guidePreview = ImageHelper::uploadUrl($guide['image_path'], 'https://via.placeholder.com/320x320?text=Guide'); ?>
                        <?= ImageHelper::renderResponsivePicture(
                            $guide['image_path'],
                            $guidePreview,
                            [
                                'class' => 'guide-preview',
                                'alt' => $guide['name'] ?? 'Size guide',
                                'loading' => 'eager',
                                'decoding' => 'async',
                                'fetchpriority' => 'high'
                            ],
                            'admin_thumb'
                        ) ?>
                        <p class="current-image-note">Tap here to replace the current image.</p>
                    <?php endif; ?>
                </div>

                <p id="guide-feedback" style="display:none; color:#007aff; font-weight:bold; font-size:16px;">+1 image
                    selected</p>
                <input type="file" name="image" id="guide-img" style="display:none;" <?= $mode === 'add' ? 'required' : '' ?>>
            </div>
        </div>
    </form>

    <script>
        document.getElementById('guide-img').addEventListener('change', function (e) {
            if (e.target.files && e.target.files.length > 0) {
                document.getElementById('guide-placeholder').style.display = 'none';

                const currentImage = document.getElementById('guide-current-image');
                if (currentImage) {
                    currentImage.style.display = 'none';
                }

                document.getElementById('guide-feedback').style.display = 'block';
                document.getElementById('guide-feedback').innerText = "+" + e.target.files.length + " image selected";
            }
        });
    </script>

</body>

</html>
