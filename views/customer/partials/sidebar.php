<aside class="sidebar display-desktop-only">
    <div class="filter-group">
        <span class="filter-title">Filter by Price</span>
        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
            <input type="text" id="minPrice" placeholder="Min" value="<?= htmlspecialchars($_GET['min'] ?? '') ?>"
                style="width: 60px; padding: 5px; border: 1px solid #ddd; border-radius: 4px;">
            <input type="text" id="maxPrice" placeholder="Max" value="<?= htmlspecialchars($_GET['max'] ?? '') ?>"
                style="width: 60px; padding: 5px; border: 1px solid #ddd; border-radius: 4px;">
        </div>

    </div>

    <div class="filter-group">
        <span class="filter-title">Browse Categories</span>
        <?php
        // Organize Categories into Tree if not already organized?
        // Actually, we expect $categories to be passed. The Tree logic logic can be done here or controller.
        // Let's re-use the tree logic if it's cheap, or rely on controller passing $categoryTree. 
        // For partial simplicity, let's keep logic here or assume $categories is available.
        // Copying the standard tree logic from home.php for robustness.
        $categoryTree = [];
        if (!empty($categories)) {
            foreach ($categories as $cat) {
                if (empty($cat['parent_id'])) {
                    $categoryTree[$cat['id']] = $cat;
                    $categoryTree[$cat['id']]['children'] = [];
                }
            }
            foreach ($categories as $cat) {
                if (!empty($cat['parent_id']) && isset($categoryTree[$cat['parent_id']])) {
                    $categoryTree[$cat['parent_id']]['children'][] = $cat;
                }
            }
        }
        ?>
        <?php foreach ($categoryTree as $mainCat): ?>
            <label class="checkbox-label">
                <input type="checkbox" class="category-filter-checkbox" value="<?= $mainCat['id'] ?>" <?= (isset($_GET['cat']) && in_array($mainCat['id'], explode(',', $_GET['cat']))) ? 'checked' : '' ?>>
                <strong>
                    <?= htmlspecialchars($mainCat['name']) ?>
                </strong>
            </label>
            <?php if (!empty($mainCat['children'])): ?>
                <div style="margin-left: 0; display: flex; flex-direction: column;">
                    <?php foreach ($mainCat['children'] as $childCat): ?>
                        <label class="checkbox-label" style="font-size: 12px; color: #777;">
                            <input type="checkbox" class="category-filter-checkbox" value="<?= $childCat['id'] ?>" <?= (isset($_GET['cat']) && in_array($childCat['id'], explode(',', $_GET['cat']))) ? 'checked' : '' ?>>
                            --
                            <?= htmlspecialchars($childCat['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        <button id="applyPriceFilter" class="btn-apply-filter" style="
            width: 100%; 
            padding: 6px; 
            background: #4a148c; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer;
            font-size: 13px;
            margin-top: 15px;">
            Apply
        </button>
    </div>

    <!-- Shop Info Box -->
    <div class="shop-info-box">
        <div class="shop-info-title">
            <?= !empty($settings['shop_name']) ? htmlspecialchars($settings['shop_name']) : 'Spice & Tea Store' ?>
        </div>
        <div class="shop-desc">
            <?php
            $about = $settings['shop_about'] ?? '';
            echo nl2br(htmlspecialchars($about));
            ?>
        </div>

        <?php
        $reviewUrl = '#'; // Default
        if (!empty($settings['review_link'])) {
            $reviewUrl = $settings['review_link'];
            if (strpos($reviewUrl, 'http') !== 0) $reviewUrl = 'https://' . $reviewUrl;
        } elseif (!empty($settings['shop_whatsapp'])) {
            $wa = preg_replace('/[^0-9]/', '', $settings['shop_whatsapp']);
            $reviewUrl = "https://wa.me/$wa?text=I%20would%20like%20to%20leave%20a%20review!";
        }
        ?>
        <a href="<?= $reviewUrl ?>" class="btn-review" target="_blank" style="text-decoration: none; display: block; text-align: center;">Share Your Feedback</a>

        <div class="shop-social-block">
            <div class="shop-social-title">Follow Us</div>
        <div class="social-icons">
            <?php
            // Helper to ensure links have http/https prefix
            function ensureUrl($url) {
                if (empty($url)) return '#';
                if (strpos($url, 'http') !== 0) return 'https://' . $url;
                return $url;
            }
            ?>
            <a href="<?= ensureUrl($settings['social_fb'] ?? '') ?>" target="_blank">
                <img src="<?= BASE_URL ?>assets/icons/facebook.png" alt="FB" class="social-icon-img">
            </a>
            <a href="<?= ensureUrl($settings['social_tiktok'] ?? '') ?>" target="_blank">
                <img src="<?= BASE_URL ?>assets/icons/tiktok.png" alt="TikTok" class="social-icon-img">
            </a>
            <a href="<?= ensureUrl($settings['social_insta'] ?? '') ?>" target="_blank">
                <img src="<?= BASE_URL ?>assets/icons/instagram.png" alt="IG" class="social-icon-img">
            </a>
            <a href="<?= ensureUrl($settings['social_youtube'] ?? '') ?>" target="_blank">
                <img src="<?= BASE_URL ?>assets/icons/youtube.png" alt="YT" class="social-icon-img">
            </a>
        </div>
        </div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Event Delegation for "Apply" Button
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'applyPriceFilter') {
            e.preventDefault();

            const minInput = document.getElementById('minPrice');
            const maxInput = document.getElementById('maxPrice');
            
            if (!minInput || !maxInput) {
                console.error('Filter Inputs missing');
                return;
            }

            const min = minInput.value.trim();
            const max = maxInput.value.trim();
            
            // Collect Selected Categories
            const checkedBoxes = document.querySelectorAll('.category-filter-checkbox:checked');
            const catIds = Array.from(checkedBoxes).map(cb => cb.value).join(',');
            
            // Construct Query Params
            const urlParams = new URLSearchParams(window.location.search);
            if (min) urlParams.set('min', min); else urlParams.delete('min');
            if (max) urlParams.set('max', max); else urlParams.delete('max');
            if (catIds) urlParams.set('cat', catIds); else urlParams.delete('cat');
            
            window.location.href = '<?= BASE_URL ?>shop/categories?' + urlParams.toString();
        }
    });
});
</script>
