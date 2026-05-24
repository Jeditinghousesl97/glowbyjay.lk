<?php
require_once 'views/layouts/customer_layout.php';

$shopName = !empty($settings['shop_name']) ? (string) $settings['shop_name'] : 'STYLE1';
$shopAbout = trim((string) ($settings['shop_about'] ?? ''));
$shopWhatsapp = trim((string) ($settings['social_whatsapp'] ?? ($settings['shop_whatsapp'] ?? '')));
$shopWhatsappDigits = preg_replace('/[^0-9]/', '', $shopWhatsapp);
$ownerWhatsappRaw = trim((string) ($settings['shop_whatsapp'] ?? ''));
$ownerWhatsappDigits = preg_replace('/[^0-9]/', '', $ownerWhatsappRaw);
$ownerWhatsappLabel = $ownerWhatsappRaw !== '' ? $ownerWhatsappRaw : ($ownerWhatsappDigits !== '' ? $ownerWhatsappDigits : '');
$shopWhatsappLink = '';
if ($shopWhatsapp !== '') {
    if (preg_match('#^https?://#i', $shopWhatsapp)) {
        $shopWhatsappLink = $shopWhatsapp;
    } elseif ($shopWhatsappDigits !== '') {
        $shopWhatsappLink = 'https://wa.me/' . $shopWhatsappDigits;
    }
}
$shopWhatsappLabel = $shopWhatsapp !== '' ? $shopWhatsapp : '+94 11 000 0000';
$supportEmail = trim((string) ($settings['shop_owner_email'] ?? ''));
if ($supportEmail === '') {
    $supportEmail = trim((string) ($settings['shop_email'] ?? ''));
}
if ($supportEmail === '') {
    $supportEmail = 'hello@style1.lk';
}
$supportHours = '6.00am to 10.00 pm';
$responseTime = 'Within 24 business hours';
$careNote = $shopAbout !== '' ? $shopAbout : 'Reach us for order help, product questions, delivery updates, and general support.';
$contactSummary = $shopAbout !== '' ? $shopAbout : 'Reach us for order support, product guidance, delivery updates, and customer care.';
$contactFormHint = $shopWhatsappDigits !== ''
    ? 'Your message will open in WhatsApp and be sent directly to the Our shop.'
    : 'Please add a WhatsApp number in shop settings so this form can send directly to the shop owner.';
$contactImage = BASE_URL . 'assets/contact.png';

$normalizeExternalUrl = static function (string $url): string {
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }
    return $url;
};

$contactSocialLinks = [];
if ($shopWhatsappLink !== '') {
    $contactSocialLinks[] = [
        'label' => 'WhatsApp',
        'url' => $shopWhatsappLink,
        'icon' => BASE_URL . 'assets/icons/whatsapp.png',
    ];
}

$socialDefinitions = [
    ['key' => 'social_fb', 'label' => 'Facebook', 'icon' => 'facebook.png'],
    ['key' => 'social_insta', 'label' => 'Instagram', 'icon' => 'instagram.png'],
    ['key' => 'social_tiktok', 'label' => 'TikTok', 'icon' => 'tiktok.png'],
    ['key' => 'social_youtube', 'label' => 'YouTube', 'icon' => 'youtube.png'],
];

foreach ($socialDefinitions as $socialDefinition) {
    $rawUrl = (string) ($settings[$socialDefinition['key']] ?? '');
    $socialUrl = $normalizeExternalUrl($rawUrl);
    if ($socialUrl === '') {
        continue;
    }
    $contactSocialLinks[] = [
        'label' => $socialDefinition['label'],
        'url' => $socialUrl,
        'icon' => BASE_URL . 'assets/icons/' . $socialDefinition['icon'],
    ];
}

customer_layout_start([
    'seo_title' => $seo_title ?? ($title ?? ''),
    'seo_description' => $seo_description ?? '',
    'seo_image' => $seo_image ?? '',
    'seo_canonical' => $seo_canonical ?? '',
    'seo_type' => $seo_type ?? 'website',
    'seo_robots' => $seo_robots ?? '',
    'seo_json_ld' => $seo_json_ld ?? []
]);
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Noto+Serif:ital,wght@0,400;0,600;1,400;1,600&display=swap');

    :root{
        --contact-ink:#1c1b1b;
        --contact-muted:#6c6767;
        --contact-soft:#f7f4f3;
        --contact-line:rgba(28,27,27,.08);
        --contact-accent:#c4000d;
        --contact-card:#ffffff;
    }

    .contact-page{
        background:linear-gradient(180deg,#fcf9f8 0%,#faf7f5 100%);
        color:var(--contact-ink);
        padding:34px 0 96px;
        font-family:"Manrope",sans-serif;
    }

    .contact-shell{
        width:min(1600px,calc(100% - 96px));
        margin:0 auto;
    }

    .contact-hero{
        padding:8px 0 28px;
        border-bottom:0;
    }

    .contact-hero-row{
        display:flex;
        align-items:flex-end;
        justify-content:space-between;
        gap:24px;
        margin-bottom:28px;
    }

    .contact-head-left{
        max-width:760px;
    }

    .contact-kicker{
        display:block;
        margin-bottom:8px;
        font-size:11px;
        letter-spacing:.26em;
        text-transform:uppercase;
        color:var(--accent-red, var(--primary));
        font-weight:800;
        font-family:sans-serif !important;
    }

    .contact-title{
        margin:0;
        font-family:sans-serif;
        font-size:clamp(34px,4vw,54px);
        line-height:1.02;
        letter-spacing:-.04em;
    }

    .contact-intro{
        margin:10px 0 0;
        color:var(--contact-muted);
        line-height:1.8;
        font-size:15px;
        max-width:64ch;
    }

    .contact-count{
        font-size:10px;
        font-weight:800;
        letter-spacing:.2em;
        text-transform:uppercase;
        color:var(--accent-red, var(--primary));
        white-space:nowrap;
    }

    .contact-grid{
        display:grid;
        grid-template-columns:minmax(280px,.82fr) minmax(0,1.18fr);
        gap:72px;
        padding-top:64px;
    }

    .contact-left,
    .contact-right{
        min-width:0;
    }

    .contact-section-label{
        display:block;
        margin-bottom:32px;
        font-size:10px;
        letter-spacing:.3em;
        text-transform:uppercase;
        color:#d4af37;
    }

    .contact-location-list{
        display:grid;
        gap:42px;
    }

    .contact-location{
        display:grid;
        gap:14px;
    }

    .contact-location-title{
        margin:0;
        font-family:sans-serif;
        font-style:normal;
        font-weight:600;
        font-size:clamp(28px,2.8vw,36px);
        line-height:1.05;
        letter-spacing:-.03em;
    }

    .contact-location-text{
        margin:0;
        color:var(--contact-muted);
        line-height:1.85;
        font-size:15px;
        white-space:pre-line;
    }

    .contact-care{
        margin-top:58px;
        background:var(--contact-card);
        border:1px solid var(--contact-line);
        padding:32px 30px;
        position:relative;
        overflow:hidden;
        box-shadow:0 14px 34px rgba(31,31,31,.04);
    }

    .contact-care-items{
        display:grid;
        gap:18px;
        position:relative;
        z-index:1;
    }

    .contact-care-item{
        display:flex;
        align-items:center;
        gap:14px;
        color:var(--contact-ink);
        font-size:16px;
    }

    .contact-care-item i{
        width:20px;
        color:var(--contact-muted);
        text-align:center;
        flex-shrink:0;
    }

    .contact-whatsapp-btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-height:40px;
        padding:0 16px;
        background:#289b26;
        color:#fff !important;
        font-size:11px;
        font-weight:700;
        letter-spacing:.16em;
        text-transform:uppercase;
        text-decoration:none;
        box-shadow:0 10px 22px rgba(40,155,38,.24);
        transition:transform .2s ease, box-shadow .2s ease, background-color .2s ease;
    }

    .contact-whatsapp-btn:hover{
        background:#289b26;
        transform:translateY(-1px);
        box-shadow:0 14px 26px rgba(40,155,38,.3);
    }

    .contact-care-social{
        display:flex;
        align-items:center;
        gap:14px;
        flex-wrap:wrap;
        margin-top:8px;
    }

    .contact-care-social-link{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        text-decoration:none;
        transition:transform .2s ease;
    }

    .contact-care-social-link:hover{
        transform:translateY(-1px);
    }

    .contact-care-social-link img{
        width:28px;
        height:28px;
        display:block;
        object-fit:contain;
    }

    .contact-image{
        margin-top:64px;
        aspect-ratio:1 / 1;
        overflow:hidden;
        background:var(--contact-soft);
        box-shadow:0 18px 42px rgba(31,31,31,.06);
    }

    .contact-image img{
        width:100%;
        height:100%;
        object-fit:cover;
    }

    .contact-form-panel{
        position:sticky;
        top:108px;
        background:var(--contact-card);
        border:1px solid var(--contact-line);
        padding:32px 30px 28px;
        box-shadow:0 18px 42px rgba(31,31,31,.06);
    }


    .contact-form-grid{
        display:grid;
        gap:28px 30px;
        grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .contact-field{
        display:grid;
        gap:10px;
    }

    .contact-field.full{
        grid-column:1 / -1;
    }

    .contact-label{
        font-size:12px;
        letter-spacing:.24em;
        text-transform:uppercase;
        color:var(--contact-muted);
        font-weight:700;
    }

    .contact-input,
    .contact-select,
    .contact-textarea{
        width:100%;
        border:0;
        border-bottom:1px solid rgba(28,27,27,.14);
        background:transparent;
        padding:14px 0 16px;
        font-family:"Manrope",sans-serif;
        font-size:18px;
        color:var(--contact-ink);
        outline:none;
        border-radius:0;
        transition:border-color .2s ease, box-shadow .2s ease;
    }

    .contact-input:focus,
    .contact-select:focus,
    .contact-textarea:focus{
        border-bottom-color:var(--contact-accent);
        box-shadow:0 1px 0 0 var(--contact-accent);
    }

    .contact-input::placeholder,
    .contact-textarea::placeholder{
        color:rgba(28,27,27,.26);
    }

    .contact-select{
        appearance:none;
    }

    .contact-textarea{
        resize:none;
        min-height:118px;
    }

    .contact-submit-row{
        margin-top:34px;
        display:flex;
        justify-content:flex-start;
        flex-direction:column;
        gap:12px;
    }

    .contact-submit{
        min-width:214px;
        border:0;
        background:#289b26;
        color:#fff;
        padding:18px 28px;
        font-size:11px;
        letter-spacing:.28em;
        text-transform:uppercase;
        font-weight:600;
        cursor:pointer;
        transition:transform .2s ease, box-shadow .2s ease, background-color .2s ease;
        box-shadow:0 14px 28px rgba(40,155,38,.24);
    }

    .contact-submit:hover{
        transform:translateY(-1px);
        box-shadow:0 18px 34px rgba(40,155,38,.3);
    }

    .contact-submit:disabled{
        opacity:.55;
        cursor:not-allowed;
        transform:none;
        box-shadow:none;
    }

    .contact-form-hint{
        margin:0;
        color:var(--contact-muted);
        font-size:13px;
        line-height:1.7;
        max-width:42ch;
    }

    .contact-meta{
        margin-top:64px;
        padding-top:28px;
        border-top:1px solid rgba(28,27,27,.05);
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:24px 60px;
    }

    .contact-meta-label{
        display:block;
        margin-bottom:8px;
        font-size:10px;
        letter-spacing:.24em;
        text-transform:uppercase;
        color:var(--contact-muted);
    }

    .contact-meta-value{
        margin:0;
        color:var(--contact-ink);
        line-height:1.75;
        font-size:14px;
    }

    @media (max-width: 1180px){
        .contact-hero-row,
        .contact-grid{
            grid-template-columns:1fr;
        }

        .contact-intro{
            max-width:46ch;
        }

        .contact-form-panel{
            position:static;
        }

        .contact-image{
            max-width:420px;
        }

        .contact-form-panel{
            position:static;
            padding:28px 24px 24px;
        }
    }

    @media (max-width: 760px){
        .contact-page{
            padding:18px 0 72px;
        }

        .contact-shell{
            width:100% !important;
            padding-left:14px !important;
            padding-right:14px !important;
        }

        .contact-hero{
            display:none;
        }

        .contact-hero-row{
            flex-direction:column;
            align-items:flex-start;
            gap:10px;
        }

        .contact-title{
            font-size:clamp(34px,10vw,46px);
        }

        .contact-grid{
            padding-top:38px;
            gap:52px;
        }

        .contact-care{
            margin-top:34px;
            padding:24px 22px;
        }

        .contact-image{
            margin-top:34px;
            aspect-ratio:4 / 5;
        }

        .contact-form-grid{
            grid-template-columns:1fr;
            gap:22px;
        }

        .contact-input,
        .contact-select,
        .contact-textarea{
            font-size:16px;
        }

        .contact-meta{
            grid-template-columns:1fr;
            gap:18px;
            margin-top:42px;
        }

        .contact-submit{
            width:100%;
            min-width:0;
        }
    }
</style>

<main class="contact-page">
    <div class="contact-shell">
        <section class="contact-hero">
            <div class="contact-hero-row">
                <div class="contact-head-left">
                    <span class="contact-kicker"><?= htmlspecialchars($shopName) ?> Support</span>
                    <h1 class="contact-title">CONTACT</h1>
                    <p class="contact-intro"><?= htmlspecialchars($contactSummary) ?></p>
                </div>
                <div class="contact-count">Customer Care</div>
            </div>
        </section>

        <section class="contact-grid">
            <div class="contact-left">
                <span class="contact-section-label">Contact Details</span>
                <div class="contact-location-list">
                    <div class="contact-location">
                        <h2 class="contact-location-title" style="font-family:sans-serif !important;font-style:normal !important;"><?= htmlspecialchars($shopName) ?> Support</h2>
                        <p class="contact-location-text"><?= htmlspecialchars($careNote) ?></p>
                    </div>

                </div>

                <div class="contact-care">
                    <span class="contact-section-label" style="margin-bottom:18px;">Customer Care</span>
                    <div class="contact-care-items">
                        <?php if ($ownerWhatsappLabel !== ''): ?>
                            <div class="contact-care-item">
                                <i class="fa-solid fa-phone"></i>
                                <a href="tel:<?= htmlspecialchars($ownerWhatsappDigits) ?>"><?= htmlspecialchars($ownerWhatsappLabel) ?></a>
                            </div>
                        <?php endif; ?>
                        <div class="contact-care-item">
                            <i class="fa-solid fa-envelope"></i>
                            <a href="mailto:<?= htmlspecialchars($supportEmail) ?>"><?= htmlspecialchars($supportEmail) ?></a>
                        </div>
                        <?php if ($shopWhatsappLink !== ''): ?>
                            <div class="contact-care-item">
                                <i class="fa-brands fa-whatsapp"></i>
                                <a class="contact-whatsapp-btn" href="<?= htmlspecialchars($shopWhatsappLink) ?>" target="_blank" rel="noopener noreferrer">WhatsApp support</a>
                            </div>
                        <?php else: ?>
                            <div class="contact-care-item">
                                <i class="fa-solid fa-circle-info"></i>
                                <span>WhatsApp support available on request</span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($contactSocialLinks)): ?>
                            <div class="contact-care-social" aria-label="Social media links">
                                <?php foreach ($contactSocialLinks as $social): ?>
                                    <a
                                        class="contact-care-social-link"
                                        href="<?= htmlspecialchars($social['url']) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="<?= htmlspecialchars($social['label']) ?>">
                                        <img src="<?= htmlspecialchars($social['icon']) ?>" alt="<?= htmlspecialchars($social['label']) ?>">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- <div class="contact-image">
                    <img src="<?= htmlspecialchars($contactImage) ?>" alt="<?= htmlspecialchars($shopName) ?> interior">
                </div> -->
            </div>

            <div class="contact-right">
                <form class="contact-form-panel" id="contactForm">
                    <span class="contact-section-label">Send an Inquiry</span>
                    <div class="contact-form-grid">
                        <div class="contact-field">
                            <label class="contact-label" for="contact_name">Full Name</label>
                            <input class="contact-input" id="contact_name" name="name" type="text" placeholder="YOUR FULL NAME" autocomplete="name" required>
                        </div>

                        <div class="contact-field">
                            <label class="contact-label" for="contact_email">Email Address</label>
                            <input class="contact-input" id="contact_email" name="email" type="email" placeholder="YOUR EMAIL ADDRESS" autocomplete="email" required>
                        </div>

                        <div class="contact-field full">
                            <label class="contact-label" for="contact_subject">Subject</label>
                            <select class="contact-select" id="contact_subject" name="subject" required>
                                <option value="General Inquiry">GENERAL INQUIRY</option>
                                <option value="Order Assistance">ORDER ASSISTANCE</option>
                                <option value="Payment Help">PAYMENT HELP</option>
                                <option value="Delivery Status">DELIVERY STATUS</option>
                                <option value="Product Question">PRODUCT QUESTION</option>
                            </select>
                        </div>

                        <div class="contact-field full">
                            <label class="contact-label" for="contact_message">Your Message</label>
                            <textarea class="contact-textarea" id="contact_message" name="message" placeholder="HOW MAY WE ASSIST YOU?" required></textarea>
                        </div>
                    </div>

                    <div class="contact-submit-row">
                        <button class="contact-submit" id="contactSubmitButton" type="submit" <?= $shopWhatsappDigits === '' ? 'disabled' : '' ?>>Send via WhatsApp</button>
                        <p class="contact-form-hint"><?= htmlspecialchars($contactFormHint) ?></p>
                    </div>

                    <div class="contact-meta">
                        <div>
                            <span class="contact-meta-label">Operating Hours</span>
                            <p class="contact-meta-value"><?= htmlspecialchars(strtoupper($supportHours)) ?></p>
                        </div>
                        <div>
                            <span class="contact-meta-label">Response Time</span>
                            <p class="contact-meta-value"><?= htmlspecialchars(strtoupper($responseTime)) ?></p>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>
</main>

<script>
(function () {
    const form = document.getElementById('contactForm');
    const submitButton = document.getElementById('contactSubmitButton');
    const whatsappDigits = <?= json_encode($shopWhatsappDigits) ?>;
    const shopName = <?= json_encode($shopName) ?>;
    const supportEmail = <?= json_encode($supportEmail) ?>;
    const supportHours = <?= json_encode($supportHours) ?>;
    const responseTime = <?= json_encode($responseTime) ?>;

    if (!form || !submitButton) {
        return;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        if (!whatsappDigits) {
            alert('WhatsApp number is not configured for this shop yet.');
            return;
        }

        const name = document.getElementById('contact_name').value.trim();
        const email = document.getElementById('contact_email').value.trim();
        const subject = document.getElementById('contact_subject').value.trim();
        const message = document.getElementById('contact_message').value.trim();

        if (!name || !email || !subject || !message) {
            alert('Please fill in all contact fields.');
            return;
        }

        const lines = [
            '*Contact Form Inquiry*',
            'Shop: ' + shopName,
            'Name: ' + name,
            'Email: ' + email,
            'Subject: ' + subject,
            'Message: ' + message,
            'Support hours: ' + supportHours,
            'Response time: ' + responseTime,
            'Reply to: ' + supportEmail
        ];

        const url = 'https://wa.me/' + whatsappDigits + '?text=' + encodeURIComponent(lines.join('\n'));
        window.open(url, '_blank', 'noopener,noreferrer');
    });
})();
</script>

<?php customer_layout_end(); ?>
