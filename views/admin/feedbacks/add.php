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
        }

        .publish-txt {
            color: #007aff;
            font-weight: bold;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 16px;
        }

        .upload-area {
            background: #f0f0f0;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
            cursor: pointer;
            margin-bottom: 30px;
        }

        .preview-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .preview-item {
            position: relative;
            width: 100%;
            padding-top: 133%;
            /* 3:4 Aspect Ratio roughly */
            background: #eee;
            border-radius: 12px;
            overflow: hidden;
        }

        .preview-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .remove-icon {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #ff3b30;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            z-index: 10;
        }

        @media (min-width: 992px) {
            .container {
                max-width: 980px;
                padding: 34px 30px 40px;
            }

            .upload-area {
                border-radius: 22px;
                padding: 56px 28px;
                margin-bottom: 24px;
            }

            .preview-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: 14px;
            }
        }
    </style>
</head>

<body>
    <?php include 'views/admin/partials/loader.php'; ?>

    <div class="container">
        <div class="header-bar">
            <div style="display:flex; align-items:center; gap:10px;">
                <a href="<?= BASE_URL ?>feedback/index" class="back-circle">❮</a>
                <h2 style="margin:0;">Add Reviews</h2>
            </div>
            <button type="button" class="publish-txt" onclick="uploadImages()">PUBLISH</button>
        </div>

        <!-- Upload Box -->
        <div class="upload-area" onclick="document.getElementById('fileInput').click()">
            <div id="feedback-placeholder">
                <p style="color:#666; font-size:14px; margin-bottom:10px;">Upload Customer's Feedbacks</p>
                <div style="font-size:24px; margin-bottom:10px;">📷 📷 📷</div>
                <p style="font-size:11px; color:#999;">Tap here to upload photos from gallery<br><span
                        style="text-decoration:underline;">Max 15 Images at once</span></p>
            </div>
            <p id="feedback-text" style="display:none; color:#007aff; font-weight:bold; font-size:16px;">+0 image
                selected</p>
        </div>

        <!-- Hidden Input -->
        <input type="file" id="fileInput" multiple accept="image/*" style="display:none;">

        <!-- Previews -->
        <div class="preview-grid" id="previewGrid">
            <!-- JS will populate this -->
        </div>

    </div>

    <script>
        let selectedFiles = [];

        document.getElementById('fileInput').addEventListener('change', function (e) {
            const files = Array.from(e.target.files);

            // Limit total to 15 (optional validation)
            if (selectedFiles.length + files.length > 15) {
                alert("Max 15 images allowed!");
                return;
            }

            files.forEach(file => {
                selectedFiles.push(file);
                addPreview(file);
            });

            updateFeedback();

            // Reset input so same file can be selected again if needed (though unlikely)
            this.value = '';
        });

        function updateFeedback() {
            const count = selectedFiles.length;
            const placeholder = document.getElementById('feedback-placeholder');
            const feedback = document.getElementById('feedback-text');

            if (count > 0) {
                placeholder.style.display = 'none';
                feedback.style.display = 'block';
                feedback.innerText = "+" + count + " image selected";
            } else {
                placeholder.style.display = 'block';
                feedback.style.display = 'none';
            }
        }

        function addPreview(file) {
            const grid = document.getElementById('previewGrid');
            const reader = new FileReader();

            reader.onload = function (e) {
                const div = document.createElement('div');
                div.className = 'preview-item';

                // Generate unique ID for removal matching? 
                // We'll trust index or object reference for simplicity, 
                // but closing over 'file' in remove function is safest.

                div.innerHTML = `
                <img src="${e.target.result}" class="preview-img">
                <div class="remove-icon" onclick="removeFile(this, '${file.name}', ${file.lastModified})">✕</div>
            `;
                grid.appendChild(div);
            }

            reader.readAsDataURL(file);
        }

        function removeFile(el, name, lastMod) {
            // Remove from array
            selectedFiles = selectedFiles.filter(f => !(f.name === name && f.lastModified === lastMod));
            // Remove from DOM
            el.parentElement.remove();

            updateFeedback();
        }

        function uploadImages() {
            if (selectedFiles.length === 0) {
                alert("Please select images first.");
                return;
            }

            showGlobalLoader();

            const formData = new FormData();
            formData.append('_csrf', '<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>');
            selectedFiles.forEach(file => {
                formData.append("images[]", file);
            });

            // Send
            fetch('<?= BASE_URL ?>feedback/store', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        // If backend does replace location manually or returns text
                        window.location.href = '<?= BASE_URL ?>feedback/index';
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Upload failed.");
                });
        }
    </script>

</body>

</html>
