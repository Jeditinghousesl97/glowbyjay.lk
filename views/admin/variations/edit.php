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
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
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
            color: #007aff;
            font-weight: bold;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 16px;
        }

        /* Blue SAVE */

        .label {
            font-weight: bold;
            color: #666;
            margin-bottom: 8px;
            display: block;
        }

        .input-box {
            background: #f0f0f0;
            border: none;
            border-radius: 8px;
            padding: 12px 15px;
            width: 100%;
            font-size: 14px;
            margin-bottom: 20px;
            box-sizing: border-box;
        }

        .input-row {
            position: relative;
            margin-bottom: 10px;
        }

        .add-btn-circle {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            width: 50px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            cursor: pointer;
            color: #333;
        }


        /* Value Item Style (Blue Box) */
        .value-item {
            background-color: #d1f7ff;
            /* Cyan/Blue from screenshot */
            color: #333;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .remove-icon {
            font-size: 20px;
            cursor: pointer;
            color: #333;
        }

        .input-pink-bg {
            background-color: #ffeaea;
            /* Light pink for the "New Input" area */
        }

        .input-row .input-box {
            margin-bottom: 0;
        }

        @media (min-width: 992px) {
            .container {
                max-width: 820px;
                padding: 34px 30px 40px;
            }

            .value-item {
                border-radius: 14px;
                padding: 14px 16px;
            }
        }
    </style>
</head>

<body>

    <form action="<?= BASE_URL ?>variation/update" method="POST" id="varForm">
        <?= csrf_input() ?>
        <input type="hidden" name="id" value="<?= $variation['id'] ?>">
        <div class="container">
            <div class="header-bar">
                <div style="display:flex; gap:10px; align-items:center;">
                    <a href="<?= BASE_URL ?>variation/index" class="back-circle">❮</a>
                    <div>
                        <h2 style="margin:0;">Edit Variation</h2>
                        <p style="margin:0; font-size:11px; color:#888;">Edit Existing Variation</p>
                    </div>
                </div>
                <button type="submit" class="save-txt">UPDATE</button>
            </div>

            <label class="label">Attribute</label>
            <input type="text" name="name" class="input-box" placeholder="Ex: Color" required
                value="<?= htmlspecialchars($variation['name']) ?>">

            <label class="label">Values</label>

            <!-- The input to add new values -->
            <div class="input-row">
                <input type="text" id="newValueInput" class="input-box input-pink-bg" placeholder="Ex: Red">
                <span class="add-btn-circle" onclick="addValue()"> + </span>
            </div>

            <!-- The list of added values -->
            <div id="valuesList">
                <?php if (!empty($variation['values'])): ?>
                    <?php foreach ($variation['values'] as $val): ?>
                        <div class="value-item">
                            <span>
                                <?= htmlspecialchars($val['value']) ?>
                            </span>
                            <span class="remove-icon" onclick="this.parentElement.remove()"> - </span>
                            <input type="hidden" name="values[]" value="<?= htmlspecialchars($val['value']) ?>">
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </form>

    <script>
        function addValue() {
            const input = document.getElementById('newValueInput');
            const value = input.value.trim();

            if (value) {
                const list = document.getElementById('valuesList');

                // Create Visual Element
                const div = document.createElement('div');
                div.className = 'value-item';
                div.innerHTML = `
                <span>${value}</span>
                <span class="remove-icon" onclick="this.parentElement.remove()"> - </span>
                <input type="hidden" name="values[]" value="${value}">
            `;

                // Append
                list.appendChild(div);

                // Clear Input
                input.value = '';
                input.focus();
            }
        }

        // Allow Enter key to add value instead of submitting form if inside the value input
        document.getElementById('newValueInput').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addValue();
            }
        });

        // Prevent submission if empty values
        document.getElementById('varForm').addEventListener('submit', function (e) {
            const values = document.querySelectorAll('input[name="values[]"]');
            if (values.length === 0) {
                e.preventDefault();
                alert("Please add at least one value (e.g. Red, Small)");
            }
        });
    </script>

</body>

</html>
