<!-- 
    ADMIN GLOBAL LOADER 
    (Identical copy of Customer Mobile Loader for visual consistency)
-->

<style>
    /* Loader Overlay */
    .admin-global-loader-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 99999; /* Higher than modals */
        display: none; /* Hidden by default */
        align-items: center;
        justify-content: center;
        flex-direction: column;
        pointer-events: all; 
    }

    /* Spinner */
    .admin-loader-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #e0e0e0;
        border-top: 5px solid #000; /* Admin generic black or primary */
        border-radius: 50%;
        animation: admin-spin 1s linear infinite;
    }

    @keyframes admin-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .admin-loader-text {
        margin-top: 20px;
        font-weight: 600;
        color: #000;
        font-size: 14px;
        letter-spacing: 1px;
    }
</style>

<!-- HTML Structure -->
<div id="adminGlobalLoader" class="admin-global-loader-overlay">
    <div class="admin-loader-spinner"></div>
    <div class="admin-loader-text">Loading...</div>
</div>

<!-- Scripts -->
<script>
    function showGlobalLoader() {
        const loader = document.getElementById('adminGlobalLoader');
        if(loader) loader.style.display = 'flex';
    }

    function hideGlobalLoader() {
        const loader = document.getElementById('adminGlobalLoader');
        if(loader) loader.style.display = 'none';
    }

    function shouldSkipLoaderLink(link, event) {
        if (!link) return true;
        if (link.classList.contains('no-loader') || link.hasAttribute('data-no-loader')) return true;
        if (link.target === '_blank' || link.hasAttribute('download')) return true;
        if (event && (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0)) return true;

        const hrefAttr = link.getAttribute('href');
        if (!hrefAttr || hrefAttr.startsWith('#') || hrefAttr.startsWith('javascript:') || hrefAttr.startsWith('mailto:') || hrefAttr.startsWith('tel:')) {
            return true;
        }

        return link.hostname !== window.location.hostname;
    }

    function shouldSkipLoaderForm(form, event) {
        if (!form) return true;
        if (form.classList.contains('no-loader') || form.hasAttribute('data-no-loader')) return true;
        if (event && event.defaultPrevented) return true;
        if ((form.getAttribute('target') || '').toLowerCase() === '_blank') return true;

        const method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method === 'dialog') return true;

        const action = form.getAttribute('action');
        if (action && action.trim().toLowerCase().startsWith('javascript:')) return true;

        return false;
    }

    document.addEventListener('click', function(event) {
        const link = event.target.closest('a');
        if (shouldSkipLoaderLink(link, event)) {
            return;
        }
        showGlobalLoader();
    }, false);

    document.addEventListener('submit', function(event) {
        const form = event.target;
        if (shouldSkipLoaderForm(form, event)) {
            return;
        }
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            hideGlobalLoader();
            return;
        }
        showGlobalLoader();
    }, false);

    document.addEventListener('invalid', function() {
        hideGlobalLoader();
    }, true);

    // Safety: Hide on page show (bfcache)
    window.addEventListener('pageshow', function(event) {
        hideGlobalLoader();
    });
</script>
