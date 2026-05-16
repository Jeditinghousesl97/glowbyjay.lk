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
        .save-order-btn {
            background:#111;
            color:#fff;
            padding:10px 18px;
            border:0;
            border-radius:8px;
            font-weight:bold;
            cursor:pointer;
            margin-right:8px;
        }

        .feedback-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            padding-bottom: 80px;
        }

        .fb-item {
            position: relative;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            cursor: move;
        }
        .fb-item.dragging { opacity: 0.45; }

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
                grid-template-columns: repeat(6, minmax(0, 1fr));
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
                <h2 style="margin:0;">Customer Reviews</h2>
                <p style="margin:0; font-size:11px; color:#888;">Manage WhatsApp review screenshots for the prefooter slider.</p>
            </div>
            <div>
                <!-- Logo or Avatar placeholder -->
                <button type="button" class="save-order-btn" onclick="submitReviewOrder()">Save Order</button>
                <a href="<?= BASE_URL ?>feedback/add" class="add-btn-blue">Add New</a>
            </div>
        </div>

        <form id="reviewOrderForm" action="<?= BASE_URL ?>feedback/reorder" method="POST">
            <?= csrf_input() ?>
            <input type="hidden" name="review_order" id="reviewOrderInput" value="">
        </form>

        <div class="feedback-grid" id="feedbackGrid">
            <?php foreach ($feedbacks as $fb): ?>
                <div class="fb-item" draggable="true" data-review-id="<?= (int) $fb['id'] ?>">
                    <?php $feedbackImage = ImageHelper::uploadUrl($fb['image_path'] ?? '', 'https://via.placeholder.com/320?text=Feedback'); ?>
                    <?= ImageHelper::renderResponsivePicture(
                        $fb['image_path'] ?? '',
                        $feedbackImage,
                        [
                            'class' => 'fb-img',
                            'alt' => 'Customer review screenshot',
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
            <p style="text-align:center; color:#999;">No review screenshots yet.</p>
        <?php endif; ?>
    </div>

    <!-- Custom Confirmation Modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-box">
            <h3>Delete Review Screenshot?</h3>
            <p style="color:#666; font-size:14px;">Are you sure you want to delete this review screenshot?</p>
            <div class="modal-btn-row">
                <button class="btn-cancel" onclick="closeModal()">Cancel</button>
                <a href="#" id="deleteLink" class="btn-yes">Yes</a>
            </div>
        </div>
    </div>

    <script>
        const feedbackGrid = document.getElementById('feedbackGrid');
        let draggingItem = null;

        if (feedbackGrid) {
            feedbackGrid.querySelectorAll('.fb-item').forEach(item => {
                item.addEventListener('dragstart', () => {
                    draggingItem = item;
                    item.classList.add('dragging');
                });
                item.addEventListener('dragend', () => {
                    item.classList.remove('dragging');
                    draggingItem = null;
                });
            });

            feedbackGrid.addEventListener('dragover', (event) => {
                event.preventDefault();
                const afterElement = getDragAfterElement(feedbackGrid, event.clientY, event.clientX);
                if (!draggingItem) return;
                if (afterElement == null) {
                    feedbackGrid.appendChild(draggingItem);
                } else {
                    feedbackGrid.insertBefore(draggingItem, afterElement);
                }
            });
        }

        function getDragAfterElement(container, y, x) {
            const draggableElements = [...container.querySelectorAll('.fb-item:not(.dragging)')];
            let closest = { offset: Number.NEGATIVE_INFINITY, element: null };
            draggableElements.forEach(child => {
                const box = child.getBoundingClientRect();
                const offsetY = y - box.top - box.height / 2;
                const offsetX = x - box.left - box.width / 2;
                const distance = Math.abs(offsetY) + Math.abs(offsetX) * 0.15;
                const score = -distance;
                if (score > closest.offset) {
                    closest = { offset: score, element: child };
                }
            });
            return closest.element;
        }

        function submitReviewOrder() {
            const ids = [...document.querySelectorAll('#feedbackGrid .fb-item')]
                .map(el => el.getAttribute('data-review-id'))
                .filter(Boolean);
            if (!ids.length) return;
            document.getElementById('reviewOrderInput').value = ids.join(',');
            showGlobalLoader();
            document.getElementById('reviewOrderForm').submit();
        }

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
