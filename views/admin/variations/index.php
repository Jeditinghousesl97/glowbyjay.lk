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
            align-items: center;
            justify-content: space-between;
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
            margin-right: 15px;
        }

        .create-btn {
            background-color: #d4ac0d;
            /* Mustard yellow from screenshot */
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 25px;
        }

        .var-item {
            margin-bottom: 25px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .var-name {
            font-weight: bold;
            font-size: 18px;
            color: #555;
            margin-bottom: 5px;
        }

        .var-values {
            color: #888;
            font-size: 14px;
        }

        @media (min-width: 992px) {
            .container {
                max-width: 920px;
                padding: 34px 30px 40px;
            }

            .create-btn {
                border-radius: 999px;
                padding: 12px 22px;
                box-shadow: 0 10px 22px rgba(212, 172, 13, 0.2);
            }

            .var-item {
                padding: 16px 0 18px;
            }
        }
    </style>
</head>

<body>
  <!-- Global Loader Injection -->
    <?php include 'views/admin/partials/loader.php'; ?>
    <div class="container">
        <div class="header-bar">
            <div style="display:flex; align-items:center;">
                <a href="<?= BASE_URL ?>admin/dashboard" class="back-circle">❮</a>
                <div>
                    <h2 style="margin:0;">Variation</h2>
                    <p style="margin:0; font-size:12px; color:#999;">Add Variations to your Product</p>
                </div>
            </div>
            <!-- Logo placeholder top right if needed -->
        </div>

        <a href="<?= BASE_URL ?>variation/add" class="create-btn">Create Variations +</a>

        <div class="variation-list">
            <?php foreach ($variations as $var): ?>
                <div class="var-item">
                    <div class="var-name">
                        <?= htmlspecialchars($var['name']) ?>
                        <!-- Delete (Optional, not in screenshot but needed for CRUD) -->
                        <a href="<?= BASE_URL ?>variation/delete/<?= $var['id'] ?>"
                            style="float:right; text-decoration:none; font-size:14px;"
                            onclick="if(confirm('Delete?')){ showGlobalLoader(); return true; } else { return false; }">🗑️</a>
                        <a href="<?= BASE_URL ?>variation/edit/<?= $var['id'] ?>" style="float:right; margin-right:10px;">
                            <img src="<?= BASE_URL ?>assets/icons/edit.png" alt="Edit"
                                style="width:18px; height:18px; display:block;">
                        </a>
                    </div>
                    <div class="var-values">
                        <?php
                        $valNames = array_map(function ($v) {
                            return $v['value'];
                        }, $var['values']);
                        echo implode(", ", $valNames);
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
        // Check if inside Iframe
        if (window.self !== window.top) {
            
            const backBtn = document.querySelector('.back-circle');
            if(backBtn) backBtn.style.display = 'none';

            if(window.parent && window.parent.refreshVariations) {
                window.parent.refreshVariations();
            }
        }
    </script>


</body>

</html>
