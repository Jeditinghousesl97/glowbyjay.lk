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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .add-btn-blue {
            background-color: #007aff;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }

        .feedback-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            padding-bottom: 80px;
        }

        .fb-item {
            position: relative;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .fb-img {
            width: 100%;
            display: block;
            height: auto;
            border-radius: 12px;
        }

        .delete-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #ff3b30;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .modal-box {
            background: white;
            width: 80%;
            max-width: 300px;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .modal-btn-row {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn-yes {
            background: #ff3b30;
            color: white;
            padding: 8px 20px;
            border-radius: 6px;
            border: none;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-cancel {
            background: #ccc;
            color: #333;
            padding: 8px 20px;
            border-radius: 6px;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }

        @media (min-width: 992px) {
            .feedback-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 18px;
                padding-bottom: 24px;
            }

            .fb-item {
                border-radius: 20px;
                box-shadow: 0 16px 34px rgba(17, 24, 39, 0.06);
            }

            .add-btn-blue {
                padding: 12px 20px;
                border-radius: 999px;
                box-shadow: 0 10px 22px rgba(0, 122, 255, 0.18);
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
            <div>
                <h2 style="margin:0;">Feedbacks</h2>
                <p style="margin:0; font-size:11px; color:#888;">Dark Lavender Clothing!</p>
            </div>
            <div>
                <!-- Logo or Avatar placeholder -->
                <a href="<?= BASE_URL ?>feedback/add" class="add-btn-blue">Add New</a>
            </div>
        </div>

        <div class="feedback-grid">
            <?php foreach ($feedbacks as $fb): ?>
                <div class="fb-item">
                    <?php $feedbackImage = ImageHelper::uploadUrl($fb['image_path'] ?? '', 'https://via.placeholder.com/320?text=Feedback'); ?>
                    <?= ImageHelper::renderResponsivePicture(
                        $fb['image_path'] ?? '',
                        $feedbackImage,
                        [
                            'class' => 'fb-img',
                            'alt' => 'Feedback',
                            'loading' => 'lazy',
                            'decoding' => 'async',
                            'fetchpriority' => 'low'
                        ],
                        'feedback'
                    ) ?>
                    <div class="delete-btn" onclick="confirmDelete(<?= $fb['id'] ?>);">
                        🗑
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($feedbacks)): ?>
            <p style="text-align:center; color:#999;">No feedbacks yet.</p>
        <?php endif; ?>
    </div>

    <!-- Custom Confirmation Modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-box">
            <h3>Delete Feedback?</h3>
            <p style="color:#666; font-size:14px;">Are you sure you want to delete this feedback image?</p>
            <div class="modal-btn-row">
                <button class="btn-cancel" onclick="closeModal()">Cancel</button>
                <a href="#" id="deleteLink" class="btn-yes">Yes</a>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            const modal = document.getElementById('confirmModal');
            const link = document.getElementById('deleteLink');
            link.href = '<?= BASE_URL ?>feedback/delete/' + id;
            link.onclick = function() { showGlobalLoader(); }; // Add loader trigger
            modal.style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('confirmModal').style.display = 'none';
        }
    </script>

    <?php $current_page = 'feedback';
    include 'views/layouts/bottom_nav.php'; ?>

</body>

</html>
