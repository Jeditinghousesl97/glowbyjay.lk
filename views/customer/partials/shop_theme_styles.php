<style>
    body {
        --primary-color: var(--accent, #b9000b);
        --primary-strong: var(--accent-red, #e31a1a);
        --accent-red: var(--accent-red, #e31a1a);
        background: var(--surface, #fff);
        color: var(--ink, #1c1b1b);
        overflow-x: hidden;
    }

    *,
    *::before,
    *::after {
        border-radius: 0 !important;
    }

    body::before {
        content: none;
    }

    .shop-page {
        --shop-primary: var(--accent, #b9000b);
        --shop-primary-strong: var(--accent-red, #e31a1a);
        --shop-surface: var(--surface, #fcf9f8);
        --shop-surface-low: #f6f3f2;
        --shop-surface-mid: #f0eded;
        --shop-surface-high: #e5e2e1;
        --shop-ink: var(--ink, #1c1b1b);
        --shop-muted: var(--muted, #6d6665);
        --shop-border: var(--line, rgba(28, 27, 27, 0.08));
        --shop-shadow: 0 24px 60px rgba(28, 27, 27, 0.08);
        --shop-shadow-soft: 0 14px 30px rgba(28, 27, 27, 0.06);
        padding: 24px 0 56px;
    }

    .shop-page-shell {
        width: min(1600px, calc(100% - 96px));
        margin: 0 auto;
    }

    .shop-label {
        display: block;
        font-size: 11px;
        letter-spacing: 0.26em;
        text-transform: uppercase;
        color: rgba(28, 27, 27, 0.52);
    }

    .shop-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 22px;
    }

    .shop-btn,
    .shop-btn-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 48px;
        padding: 0 18px;
        border-radius: 16px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .shop-btn {
        background: linear-gradient(135deg, var(--shop-primary) 0%, #3f2e1e 100%);
        color: #fff;
        box-shadow: 0 14px 28px rgba(185, 0, 11, 0.16);
    }

    .shop-btn-secondary {
        background: rgba(255, 255, 255, 0.78);
        color: var(--shop-ink);
        border: 1px solid var(--shop-border);
        box-shadow: var(--shop-shadow-soft);
    }

    .shop-btn:hover,
    .shop-btn-secondary:hover,
    .filter-submit:hover,
    .shop-subcat-card:hover {
        transform: translateY(-1px);
    }

    .shop-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.74);
        border: 1px solid var(--shop-border);
        box-shadow: var(--shop-shadow-soft);
        font-size: 12px;
        font-weight: 700;
        color: var(--shop-ink);
    }

    .shop-filter-bar {
        display: grid;
        grid-template-columns: 1.2fr 0.7fr 0.7fr auto;
        gap: 14px;
        padding: 18px;
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.74);
        border: 1px solid var(--shop-border);
        box-shadow: var(--shop-shadow-soft);
        margin-bottom: 32px;
    }

    .filter-field {
        display: grid;
        gap: 8px;
    }

    .filter-field label {
        font-size: 11px;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: rgba(28, 27, 27, 0.52);
    }

    .filter-field input {
        height: 50px;
        border-radius: 16px;
        border: 1px solid rgba(28, 27, 27, 0.1);
        background: #fff;
        padding: 0 16px;
        font-size: 14px;
        outline: none;
    }

    .filter-field input:focus {
        border-color: rgba(185, 0, 11, 0.28);
        box-shadow: 0 0 0 4px rgba(185, 0, 11, 0.08);
    }

    .filter-submit {
        align-self: end;
        height: 50px;
        min-width: 140px;
        padding: 0 20px;
        border: 0;
        border-radius: 16px;
        background: linear-gradient(135deg, var(--shop-primary) 0%, #3f2e1e 100%);
        color: #fff;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        cursor: pointer;
        box-shadow: 0 14px 28px rgba(185, 0, 11, 0.16);
    }

    .shop-active-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 28px;
    }

    .shop-active-filters .shop-chip {
        background: rgba(255, 255, 255, 0.86);
    }

    .shop-section {
        margin-top: 34px;
    }

    .shop-section-head {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 18px;
    }

    .shop-section-head-left {
        max-width: 720px;
    }

    .shop-section-title {
        font-family: "Noto Serif", serif;
        font-size: clamp(28px, 3.2vw, 48px);
        line-height: 1.02;
        letter-spacing: -0.04em;
    }

    .shop-section-copy {
        margin-top: 10px;
        max-width: 66ch;
        color: var(--shop-muted);
        line-height: 1.8;
        font-size: 15px;
    }

    .shop-view-all {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: var(--shop-primary);
        display: inline-flex;
        align-items: center;
        gap: 10px;
        white-space: nowrap;
    }

    .shop-view-all::after {
        content: "";
        width: 44px;
        height: 1px;
        background: currentColor;
        opacity: 0.35;
    }

    .shop-category-grid {
        display: grid;
        grid-template-columns: 1.35fr 0.95fr;
        gap: 22px;
    }

    .shop-category-stack {
        display: grid;
        gap: 22px;
    }

    .shop-feature-panel,
    .shop-mini-panel {
        position: relative;
        overflow: hidden;
        background: var(--shop-surface-high);
        box-shadow: var(--shop-shadow-soft);
    }

    .shop-feature-panel {
        min-height: 520px;
        border-radius: 30px;
    }

    .shop-mini-panel {
        min-height: 250px;
        border-radius: 26px;
    }

    .shop-feature-panel img,
    .shop-mini-panel img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.7s ease;
    }

    .shop-feature-panel:hover img,
    .shop-mini-panel:hover img {
        transform: scale(1.05);
    }

    .shop-feature-panel .overlay,
    .shop-mini-panel .overlay {
        position: absolute;
        inset: auto 0 0 0;
        padding: 26px;
        background: linear-gradient(180deg, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.34) 100%);
        color: #fff;
    }

    .shop-feature-panel h3 {
        font-size: 38px;
        line-height: 1.08;
    }

    .shop-mini-panel h3 {
        font-size: 26px;
        line-height: 1.08;
    }

    .shop-mini-panel .overlay.center {
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(180deg, rgba(0, 0, 0, 0.06), rgba(0, 0, 0, 0.26));
        text-align: center;
    }

    .shop-mini-panel .overlay.bottom-right {
        inset: auto 0 0 auto;
        width: auto;
        text-align: right;
        padding: 22px 24px;
        background: linear-gradient(180deg, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.34) 100%);
    }

    .shop-mini-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px;
        margin-top: 22px;
    }

    .shop-mini-grid .shop-mini-panel {
        min-height: 170px;
        border-radius: 24px;
    }

    .shop-mini-grid .overlay {
        padding: 18px;
    }

    .shop-mini-grid h3 {
        font-size: 20px;
    }

    .shop-product-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 24px 22px;
        align-items: start;
    }

    .shop-product-grid .product-card {
        min-width: 0;
        width: auto;
        max-width: none;
        margin-bottom: 0;
        display: grid;
        gap: 12px;
        align-content: start;
        height: 100%;
        padding-bottom: 16px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid var(--shop-border);
        box-shadow: var(--shop-shadow-soft);
        overflow: hidden;
    }

    .shop-product-grid .product-thumb-container {
        position: relative;
        aspect-ratio: 4 / 5;
        overflow: hidden;
        background: var(--shop-surface-mid);
    }

    .shop-product-grid .product-thumb-container a,
    .shop-product-grid .product-thumb-container img,
    .shop-product-grid .product-thumb {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.7s ease;
    }

    .shop-product-grid .product-card:hover .product-thumb-container img,
    .shop-product-grid .product-card:hover .product-thumb {
        transform: scale(1.04);
    }

    .shop-product-grid .product-info {
        display: grid;
        gap: 8px;
        align-content: start;
        padding: 0 16px;
    }

    .shop-product-grid .product-name {
        font-family: "Noto Serif", serif;
        font-size: 18px;
        line-height: 1.22;
        letter-spacing: -0.02em;
        min-height: 2.45em;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .shop-product-grid .product-price-box {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        min-height: 22px;
    }

    .shop-product-grid .current-price,
    .shop-product-grid .old-price {
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .shop-product-grid .current-price {
        color: var(--shop-primary);
    }

    .shop-product-grid .old-price {
        color: #8c8785;
        text-decoration: line-through;
    }

    .shop-product-grid .product-category {
        margin-top: 0;
        font-size: 10px;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: var(--shop-muted);
    }

    .shop-product-grid .product-desc {
        padding: 0 16px;
        color: var(--shop-muted);
        font-size: 13px;
        line-height: 1.65;
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 2.75em;
    }

    .shop-product-grid .koko-installment-teaser {
        display: flex;
        align-items: center;
        flex-direction: row;
        gap: 6px;
        flex-wrap: nowrap;
        white-space: nowrap;
        overflow: hidden;
        min-width: 0;
        margin-top: 0;
        padding: 0 16px;
    }

    .shop-product-grid .koko-installment-text {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .shop-product-grid .koko-installment-logo {
        height: 16px;
        width: auto;
        flex-shrink: 0;
        display: block;
    }

    .shop-empty {
        grid-column: 1 / -1;
        padding: 48px 32px;
        text-align: center;
        background: rgba(255, 255, 255, 0.82);
        border: 1px solid var(--shop-border);
        border-radius: 28px;
        box-shadow: var(--shop-shadow-soft);
    }

    .shop-empty h3 {
        font-family: "Noto Serif", serif;
        font-size: 30px;
        line-height: 1.1;
        margin-bottom: 10px;
    }

    .shop-empty p {
        color: var(--shop-muted);
        line-height: 1.8;
        max-width: 52ch;
        margin: 0 auto;
    }

    .shop-subcat-rail {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 6px;
        scroll-snap-type: x mandatory;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .shop-subcat-rail::-webkit-scrollbar {
        display: none;
    }

    .shop-subcat-card {
        flex: 0 0 210px;
        display: grid;
        gap: 12px;
        padding: 14px;
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.74);
        border: 1px solid var(--shop-border);
        box-shadow: var(--shop-shadow-soft);
        text-decoration: none;
        color: inherit;
        scroll-snap-align: start;
    }

    .shop-subcat-card img {
        width: 100%;
        aspect-ratio: 1 / 1;
        object-fit: cover;
        border-radius: 18px;
        background: var(--shop-surface-mid);
    }

    .shop-subcat-card strong {
        display: block;
        font-family: "Noto Serif", serif;
        font-size: 18px;
        line-height: 1.15;
        color: var(--shop-ink);
    }

    .shop-subcat-card span {
        font-size: 11px;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: var(--shop-muted);
    }

    @media (max-width: 1180px) {
        .shop-page-shell {
            width: min(100% - 48px, 1600px);
        }

        .shop-filter-bar,
        .shop-category-grid {
            grid-template-columns: 1fr;
        }

        .shop-feature-panel {
            min-height: 480px;
        }

        .shop-category-stack {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .shop-mini-panel {
            min-height: 240px;
        }

        .shop-mini-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .shop-product-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        body {
            background: #fff;
        }

        .shop-page {
            padding: 18px 0 42px;
        }

        .shop-page-shell {
            width: 100%;
        }

        .shop-actions {
            gap: 10px;
        }

        .shop-btn,
        .shop-btn-secondary,
        .filter-submit {
            width: 100%;
        }

        .shop-filter-bar {
            grid-template-columns: 1fr;
            padding: 16px;
            border-radius: 24px;
        }

        .shop-category-stack {
            grid-template-columns: 1fr;
        }

        .shop-feature-panel {
            min-height: 420px;
        }

        .shop-feature-panel .overlay {
            padding: 18px;
        }

        .shop-feature-panel h3 {
            font-size: 28px;
        }

        .shop-mini-grid {
            grid-template-columns: 1fr;
        }

        .shop-mini-panel {
            min-height: 220px;
        }

        .shop-product-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .shop-subcat-card {
            flex-basis: 172px;
            padding: 12px;
        }

        .shop-subcat-card strong {
            font-size: 16px;
        }

        .shop-section-head {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
