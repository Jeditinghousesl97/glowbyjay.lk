<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Image Optimizer') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css?v=<?= time() ?>">
    <style>
        .imgopt-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; margin-bottom:20px; }
        .imgopt-card, .imgopt-panel { background:#fff; border-radius:18px; padding:18px; box-shadow:0 4px 20px rgba(0,0,0,0.04); }
        .imgopt-label { font-size:11px; color:#777; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px; }
        .imgopt-value { font-size:26px; font-weight:900; color:#111; }
        .imgopt-note { margin-top:6px; font-size:12px; color:#777; line-height:1.5; }
        .imgopt-actions { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
        .imgopt-btn { display:inline-flex; align-items:center; justify-content:center; padding:11px 15px; border:none; border-radius:12px; text-decoration:none; font-size:13px; font-weight:800; cursor:pointer; }
        .imgopt-btn.primary { background:#111; color:#fff; }
        .imgopt-btn.secondary { background:#fff; color:#333; border:1px solid #ececec; }
        .imgopt-btn.warn { background:#c77918; color:#fff; }
        .imgopt-form { display:grid; gap:14px; }
        .imgopt-field { display:grid; gap:6px; }
        .imgopt-field label { font-size:12px; font-weight:700; color:#555; }
        .imgopt-field input, .imgopt-field select { width:100%; padding:11px 12px; border-radius:12px; border:1px solid #ddd; background:#fff; font-size:14px; box-sizing:border-box; }
        .imgopt-list { display:grid; gap:10px; }
        .imgopt-item { padding:12px 14px; border:1px solid #f0f0f0; border-radius:14px; font-size:13px; color:#333; line-height:1.6; }
        .imgopt-badges { display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }
        .imgopt-badge { display:inline-flex; padding:6px 10px; border-radius:999px; background:#f5f5f5; font-size:11px; font-weight:800; text-transform:uppercase; color:#333; }
        .imgopt-alert { margin-bottom:16px; padding:14px 16px; border-radius:14px; font-size:13px; font-weight:700; line-height:1.5; }
        .imgopt-alert.success { background:#e9f8ef; color:#17663b; border:1px solid #cfe9d8; }
        .imgopt-alert.warn { background:#fff5e6; color:#9a5a0a; border:1px solid #f2d19a; }
        .imgopt-alert.error { background:#fff0f0; color:#a43838; border:1px solid #efc0c0; }
    </style>
</head>
<body>
<?php include 'views/admin/partials/loader.php'; ?>
<div class="container">
    <div class="page-header" style="margin-bottom:20px;">
        <div>
            <h1 class="page-title">Image Optimizer</h1>
            <p class="shop-subtitle">Generate optimized image files for your existing uploads so the whole website can load faster.</p>
        </div>
    </div>

    <div class="imgopt-actions">
        <a href="<?= BASE_URL ?>admin/dashboard" class="imgopt-btn secondary">Back to Dashboard</a>
        <a href="<?= BASE_URL ?>settings/edit" class="imgopt-btn secondary">Image Settings</a>
        <a href="<?= BASE_URL ?>admin/serverCheck" class="imgopt-btn secondary">Server Check</a>
        <form method="POST" style="display:inline-flex;">
            <?= csrf_input() ?>
            <input type="hidden" name="reset_opcache" value="1">
            <button type="submit" class="imgopt-btn secondary">Reset PHP Opcache</button>
        </form>
    </div>

    <?php if (isset($_GET['opcache'])): ?>
        <?php $opcacheStatus = (string) $_GET['opcache']; ?>
        <?php if ($opcacheStatus === 'success'): ?>
            <div class="imgopt-alert success">PHP opcache reset completed. Please hard refresh the storefront and inspect the image markup again.</div>
        <?php elseif ($opcacheStatus === 'unavailable'): ?>
            <div class="imgopt-alert warn">This server does not expose <code>opcache_reset()</code> to PHP, so we could not clear opcache from the app.</div>
        <?php else: ?>
            <div class="imgopt-alert error">The app tried to reset PHP opcache, but the server did not confirm success. A PHP handler restart from hosting may still be needed.</div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="imgopt-grid">
        <div class="imgopt-card">
            <div class="imgopt-label">Original Uploads</div>
            <div class="imgopt-value"><?= (int) ($upload_count ?? 0) ?></div>
            <div class="imgopt-note">Files currently stored in <code>assets/uploads</code>.</div>
        </div>
        <div class="imgopt-card">
            <div class="imgopt-label">Derived Images</div>
            <div class="imgopt-value"><?= (int) (($optimization_summary['derived_files'] ?? $derived_count) ?? 0) ?></div>
            <div class="imgopt-note">Optimized responsive files already generated in <code>assets/uploads/derived</code>.</div>
        </div>
        <div class="imgopt-card">
            <div class="imgopt-label">Need Optimization</div>
            <div class="imgopt-value" style="font-size:18px; line-height:1.4;"><?= (int) (($optimization_summary['missing_derivatives'] ?? 0)) ?> Files</div>
            <div class="imgopt-note"><?= (int) (($optimization_summary['fully_optimized'] ?? 0)) ?> files are already fully optimized.</div>
        </div>
        <div class="imgopt-card">
            <div class="imgopt-label">Local Files Ready For Cloudflare</div>
            <div class="imgopt-value"><?= (int) ($migratable_count ?? 0) ?></div>
            <div class="imgopt-note"><?= htmlspecialchars((string) ($cloudflare_status['label'] ?? 'Cloudflare Off')) ?>. Old local originals can be copied to Cloudflare in batches from this page.</div>
        </div>
        <div class="imgopt-card">
            <div class="imgopt-label">Missing Local Files Referenced By Site</div>
            <div class="imgopt-value"><?= (int) ($restorable_missing_count ?? 0) ?></div>
            <div class="imgopt-note">These are referenced images that can be pulled back from Cloudflare R2 into <code>assets/uploads</code>.</div>
        </div>
    </div>

    <div class="imgopt-panel" style="margin-bottom:16px;">
        <h3 style="margin:0 0 12px;">Move Existing Local Images To Cloudflare</h3>
        <div class="imgopt-item" style="margin-bottom:12px;">
            <strong>Status:</strong> <?= htmlspecialchars((string) ($cloudflare_status['label'] ?? 'Cloudflare Off')) ?><br>
            <?= htmlspecialchars((string) ($cloudflare_status['message'] ?? '')) ?>
        </div>
        <form method="POST" class="imgopt-form">
            <?= csrf_input() ?>
            <input type="hidden" name="migrate_cloudflare" value="1">
            <div class="imgopt-field">
                <label for="migration_limit">Files Per Batch</label>
                <input type="number" min="1" step="1" name="migration_limit" id="migration_limit" value="<?= (int) ($migration_limit ?? 25) ?>" placeholder="25">
            </div>
            <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#333;">
                <input type="checkbox" name="delete_local_after_upload" value="1" <?= !empty($migration_delete_local) ? 'checked' : '' ?>>
                Delete each local file immediately after its Cloudflare upload succeeds
            </label>
            <input type="hidden" name="migration_offset" value="0">
            <div class="imgopt-note">Recommended order: first run without deletion, confirm storefront images work from Cloudflare, then run again with deletion if you want to free server storage.</div>
            <div class="imgopt-actions" style="margin-bottom:0;">
                <button type="submit" class="imgopt-btn primary">Start Cloudflare Migration</button>
            </div>
        </form>
    </div>

    <?php if (!empty($migration_summary)): ?>
        <div class="imgopt-panel" style="margin-bottom:16px;">
            <h3 style="margin:0 0 12px;">Cloudflare Migration Result</h3>
            <?php if (!empty($migration_summary['message'])): ?>
                <div class="imgopt-alert warn"><?= htmlspecialchars((string) $migration_summary['message']) ?></div>
            <?php endif; ?>
            <div class="imgopt-grid" style="margin-bottom:12px;">
                <div class="imgopt-card">
                    <div class="imgopt-label">Batch Scanned</div>
                    <div class="imgopt-value"><?= (int) ($migration_summary['scanned'] ?? 0) ?></div>
                </div>
                <div class="imgopt-card">
                    <div class="imgopt-label">Uploaded To Cloudflare</div>
                    <div class="imgopt-value"><?= (int) ($migration_summary['uploaded'] ?? 0) ?></div>
                </div>
                <div class="imgopt-card">
                    <div class="imgopt-label">Deleted Local</div>
                    <div class="imgopt-value"><?= (int) ($migration_summary['deleted_local'] ?? 0) ?></div>
                </div>
                <div class="imgopt-card">
                    <div class="imgopt-label">Failed</div>
                    <div class="imgopt-value"><?= (int) ($migration_summary['failed'] ?? 0) ?></div>
                </div>
            </div>

            <div class="imgopt-item" style="margin-bottom:12px;">
                <strong>Progress:</strong>
                <?= (int) ($migration_summary['next_offset'] ?? 0) ?> / <?= (int) ($migration_summary['total'] ?? 0) ?> local files processed
                <?php if (!empty($migration_summary['complete'])): ?>
                    <span style="color:#1d7a40; font-weight:800;"> - Completed</span>
                <?php endif; ?>
            </div>

            <?php if (empty($migration_summary['complete']) && (int) ($migration_summary['next_offset'] ?? 0) < (int) ($migration_summary['total'] ?? 0)): ?>
                <form method="POST" class="imgopt-form" style="margin-top:14px;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="migrate_cloudflare" value="1">
                    <input type="hidden" name="migration_limit" value="<?= (int) ($migration_summary['limit'] ?? ($migration_limit ?? 25)) ?>">
                    <input type="hidden" name="migration_offset" value="<?= (int) ($migration_summary['next_offset'] ?? 0) ?>">
                    <?php if (!empty($migration_delete_local)): ?>
                        <input type="hidden" name="delete_local_after_upload" value="1">
                    <?php endif; ?>
                    <div class="imgopt-actions" style="margin-bottom:0;">
                        <button type="submit" class="imgopt-btn primary">Continue Cloudflare Migration</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if (!empty($migration_summary['files'])): ?>
                <div class="imgopt-note" style="margin:14px 0 8px;">Sample migrated files:</div>
                <div class="imgopt-list">
                    <?php foreach ($migration_summary['files'] as $fileName): ?>
                        <div class="imgopt-item"><?= htmlspecialchars((string) $fileName) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="imgopt-panel" style="margin-bottom:16px;">
        <h3 style="margin:0 0 12px;">Pull Referenced Images From Cloudflare To Local Uploads</h3>
        <div class="imgopt-item" style="margin-bottom:12px;">
            <strong>What this does:</strong> restores missing images that your website already references from Cloudflare R2 into local <code>assets/uploads</code>. Frontend image loading will automatically use the local copy again once restored.
        </div>
        <form method="POST" class="imgopt-form">
            <?= csrf_input() ?>
            <input type="hidden" name="restore_local_from_cloudflare" value="1">
            <div class="imgopt-field">
                <label for="restore_limit">Files Per Batch</label>
                <input type="number" min="1" step="1" name="restore_limit" id="restore_limit" value="<?= (int) ($restore_limit ?? 25) ?>" placeholder="25">
            </div>
            <input type="hidden" name="restore_offset" value="0">
            <div class="imgopt-note">Use this if you want a local backup again or if the site should keep working after Cloudflare is turned off.</div>
            <div class="imgopt-actions" style="margin-bottom:0;">
                <button type="submit" class="imgopt-btn primary">Start Pull To Local</button>
            </div>
        </form>
    </div>

    <?php if (!empty($restore_summary)): ?>
        <div class="imgopt-panel" style="margin-bottom:16px;">
            <h3 style="margin:0 0 12px;">Pull To Local Result</h3>
            <?php if (!empty($restore_summary['message'])): ?>
                <div class="imgopt-alert warn"><?= htmlspecialchars((string) $restore_summary['message']) ?></div>
            <?php endif; ?>
            <div class="imgopt-grid" style="margin-bottom:12px;">
                <div class="imgopt-card">
                    <div class="imgopt-label">Batch Scanned</div>
                    <div class="imgopt-value"><?= (int) ($restore_summary['scanned'] ?? 0) ?></div>
                </div>
                <div class="imgopt-card">
                    <div class="imgopt-label">Restored To Local</div>
                    <div class="imgopt-value"><?= (int) ($restore_summary['restored'] ?? 0) ?></div>
                </div>
                <div class="imgopt-card">
                    <div class="imgopt-label">Optimized Locally</div>
                    <div class="imgopt-value"><?= (int) ($restore_summary['optimized'] ?? 0) ?></div>
                </div>
                <div class="imgopt-card">
                    <div class="imgopt-label">Failed</div>
                    <div class="imgopt-value"><?= (int) ($restore_summary['failed'] ?? 0) ?></div>
                </div>
            </div>

            <div class="imgopt-item" style="margin-bottom:12px;">
                <strong>Progress:</strong>
                <?= (int) ($restore_summary['next_offset'] ?? 0) ?> / <?= (int) ($restore_summary['total'] ?? 0) ?> missing local references processed
                <?php if (!empty($restore_summary['complete'])): ?>
                    <span style="color:#1d7a40; font-weight:800;"> - Completed</span>
                <?php endif; ?>
            </div>

            <?php if (empty($restore_summary['complete']) && (int) ($restore_summary['next_offset'] ?? 0) < (int) ($restore_summary['total'] ?? 0)): ?>
                <form method="POST" class="imgopt-form" style="margin-top:14px;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="restore_local_from_cloudflare" value="1">
                    <input type="hidden" name="restore_limit" value="<?= (int) ($restore_summary['limit'] ?? ($restore_limit ?? 25)) ?>">
                    <input type="hidden" name="restore_offset" value="<?= (int) ($restore_summary['next_offset'] ?? 0) ?>">
                    <div class="imgopt-actions" style="margin-bottom:0;">
                        <button type="submit" class="imgopt-btn primary">Continue Pull To Local</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if (!empty($restore_summary['files'])): ?>
                <div class="imgopt-note" style="margin:14px 0 8px;">Sample restored files:</div>
                <div class="imgopt-list">
                    <?php foreach ($restore_summary['files'] as $fileName): ?>
                        <div class="imgopt-item"><?= htmlspecialchars((string) $fileName) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="imgopt-panel" style="margin-bottom:16px;">
        <h3 style="margin:0 0 12px;">Run Optimizer</h3>
        <form method="POST" class="imgopt-form">
            <?= csrf_input() ?>
            <div class="imgopt-field">
                <label for="run_mode">Run Mode</label>
                <select name="run_mode" id="run_mode">
                    <option value="missing" <?= ($mode ?? 'scan') === 'missing' ? 'selected' : '' ?>>Create Missing Files</option>
                    <option value="rebuild" <?= ($mode ?? 'scan') === 'rebuild' ? 'selected' : '' ?>>Rebuild Everything</option>
                </select>
            </div>
            <div class="imgopt-field">
                <label for="limit">Files Per Batch</label>
                <input type="number" min="1" step="1" name="limit" id="limit" value="<?= (int) ($batch_limit ?? 25) ?>" placeholder="25">
            </div>
            <input type="hidden" name="offset" value="0">
            <div class="imgopt-note">Start with 20 or 25 files per batch. Bigger numbers can still timeout on shared hosting.</div>
            <div class="imgopt-actions" style="margin-bottom:0;">
                <button type="submit" class="imgopt-btn primary">Run Optimizer</button>
                <button type="submit" name="run_mode" value="rebuild" class="imgopt-btn warn" onclick="return confirm('Rebuild all optimized images? This will delete old derived files and generate them again.')">Rebuild All</button>
            </div>
        </form>
        <?php if (!empty($optimization_summary['missing_formats'])): ?>
            <div class="imgopt-badges">
                <?php foreach ($optimization_summary['missing_formats'] as $format => $count): ?>
                    <span class="imgopt-badge">Missing <?= htmlspecialchars(strtoupper((string) $format)) ?>: <?= (int) $count ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($run_summary)): ?>
        <div class="imgopt-panel" style="margin-bottom:16px;">
            <h3 style="margin:0 0 12px;">Last Run Result</h3>
            <div class="imgopt-grid" style="margin-bottom:12px;">
                <div class="imgopt-card">
                    <div class="imgopt-label">Batch Scanned</div>
                    <div class="imgopt-value"><?= (int) ($run_summary['scanned'] ?? 0) ?></div>
                </div>
                <div class="imgopt-card">
                    <div class="imgopt-label">Optimized</div>
                    <div class="imgopt-value"><?= (int) ($run_summary['optimized'] ?? 0) ?></div>
                </div>
                <div class="imgopt-card">
                    <div class="imgopt-label">Skipped</div>
                    <div class="imgopt-value"><?= (int) ($run_summary['skipped'] ?? 0) ?></div>
                </div>
                <div class="imgopt-card">
                    <div class="imgopt-label">Failed</div>
                    <div class="imgopt-value"><?= (int) ($run_summary['failed'] ?? 0) ?></div>
                </div>
            </div>

            <div class="imgopt-item" style="margin-bottom:12px;">
                <strong>Progress:</strong>
                <?= (int) ($run_summary['next_offset'] ?? 0) ?> / <?= (int) ($run_summary['total'] ?? 0) ?> files processed
                <?php if (!empty($run_summary['complete'])): ?>
                    <span style="color:#1d7a40; font-weight:800;"> - Completed</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($run_summary['formats'])): ?>
                <div class="imgopt-badges">
                    <?php foreach ($run_summary['formats'] as $format => $count): ?>
                        <span class="imgopt-badge"><?= htmlspecialchars(strtoupper((string) $format)) ?>: <?= (int) $count ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($run_summary['complete']) && (int) ($run_summary['next_offset'] ?? 0) < (int) ($run_summary['total'] ?? 0)): ?>
                <form method="POST" class="imgopt-form" style="margin-top:14px;">
                    <?= csrf_input() ?>
                    <input type="hidden" name="run_mode" value="<?= htmlspecialchars((string) ($mode ?? 'missing')) ?>">
                    <input type="hidden" name="limit" value="<?= (int) ($run_summary['limit'] ?? ($batch_limit ?? 25)) ?>">
                    <input type="hidden" name="offset" value="<?= (int) ($run_summary['next_offset'] ?? 0) ?>">
                    <div class="imgopt-actions" style="margin-bottom:0;">
                        <button type="submit" class="imgopt-btn primary">Continue Next Batch</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if (!empty($run_summary['files'])): ?>
                <div class="imgopt-note" style="margin:14px 0 8px;">Sample processed files:</div>
                <div class="imgopt-list">
                    <?php foreach ($run_summary['files'] as $fileName): ?>
                        <div class="imgopt-item"><?= htmlspecialchars((string) $fileName) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="imgopt-panel" style="margin-bottom:16px;">
        <h3 style="margin:0 0 12px;">Inspect One Image</h3>
        <form method="POST" class="imgopt-form">
            <?= csrf_input() ?>
            <div class="imgopt-field">
                <label for="inspect_image">Paste Image URL or Filename</label>
                <input
                    type="text"
                    name="inspect_image"
                    id="inspect_image"
                    value="<?= htmlspecialchars((string) (($inspect_report['input'] ?? ''))) ?>"
                    placeholder="https://freezone.lk/assets/uploads/1773501416_main_af.jpg">
            </div>
            <div class="imgopt-actions" style="margin-bottom:0;">
                <button type="submit" class="imgopt-btn primary">Inspect Image</button>
            </div>
        </form>

        <?php if (!empty($inspect_report['input'])): ?>
            <div class="imgopt-list" style="margin-top:14px;">
                <div class="imgopt-item">
                    <strong>Filename:</strong> <?= htmlspecialchars((string) ($inspect_report['filename'] ?? '')) ?><br>
                    <strong>Original Found:</strong> <?= !empty($inspect_report['exists']) ? 'Yes' : 'No' ?>
                </div>

                <?php if (!empty($inspect_report['exists'])): ?>
                    <div class="imgopt-item">
                        <strong>Original URL:</strong><br>
                        <a href="<?= htmlspecialchars((string) ($inspect_report['original_url'] ?? '')) ?>" target="_blank" rel="noopener noreferrer">
                            <?= htmlspecialchars((string) ($inspect_report['original_url'] ?? '')) ?>
                        </a>
                    </div>

                    <div class="imgopt-item">
                        <strong>Browser Delivery Sources</strong><br>
                        <?php if (!empty($inspect_report['delivery']['sources'])): ?>
                            <?php foreach ($inspect_report['delivery']['sources'] as $source): ?>
                                <div style="margin-top:8px;">
                                    <strong><?= htmlspecialchars((string) ($source['type'] ?? '')) ?></strong><br>
                                    <span style="word-break:break-all;"><?= htmlspecialchars((string) ($source['srcset'] ?? '')) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span>No responsive source files detected yet for this image.</span>
                        <?php endif; ?>
                    </div>

                    <div class="imgopt-item">
                        <strong>Generated Files:</strong> <?= count($inspect_report['derived_existing'] ?? []) ?><br>
                        <strong>Missing Files:</strong> <?= count($inspect_report['derived_missing'] ?? []) ?>
                    </div>

                    <?php if (!empty($inspect_report['derived_existing'])): ?>
                        <div class="imgopt-item">
                            <strong>Existing Derived Files</strong>
                            <?php foreach (($inspect_report['derived_existing'] ?? []) as $file): ?>
                                <div style="margin-top:8px; word-break:break-all;">
                                    <?= htmlspecialchars(strtoupper((string) ($file['format'] ?? ''))) ?> <?= (int) ($file['width'] ?? 0) ?>w<br>
                                    <a href="<?= htmlspecialchars((string) ($file['url'] ?? '')) ?>" target="_blank" rel="noopener noreferrer">
                                        <?= htmlspecialchars((string) ($file['url'] ?? '')) ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($inspect_report['derived_missing'])): ?>
                        <div class="imgopt-item">
                            <strong>Missing Derived Files</strong>
                            <?php foreach (($inspect_report['derived_missing'] ?? []) as $file): ?>
                                <div style="margin-top:8px;">
                                    <?= htmlspecialchars(strtoupper((string) ($file['format'] ?? ''))) ?> <?= (int) ($file['width'] ?? 0) ?>w
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="imgopt-panel">
        <h3 style="margin:0 0 12px;">How To Use This</h3>
        <div class="imgopt-list">
            <div class="imgopt-item">Start with <strong>Create Missing Files</strong>. That is the safest first run for your live site.</div>
            <div class="imgopt-item">Use a small batch like <strong>20</strong> or <strong>25</strong> files. This is much safer on shared hosting.</div>
            <div class="imgopt-item">After each run, click <strong>Continue Next Batch</strong> until the progress says completed.</div>
            <div class="imgopt-item">Use <strong>Rebuild Everything</strong> only if you later change the optimization rules and want a fresh full rebuild.</div>
        </div>
    </div>
</div>
</body>
</html>
