<?php
/**
 * generic-post.php
 * A minimal wrapper for Markdown content.
 * Relies on layout.php for header, breadcrumbs, and sidebar wrappers.
 */
declare(strict_types=1);

// Ensure metadata variables are available for layout.php's title logic
$title = $content_metadata['title'] ?? $page_config['title'] ?? 'Financial Guide';
$subtitle = $content_metadata['subtitle'] ?? '';
$updated_date = $content_metadata['updated'] ?? 'March 2026';

$is_blog_post = (strpos($_SERVER['REQUEST_URI'], '/resource/') !== false);

ob_start();
?>

<div class="markdown-content">
    <?php if (!$is_blog_post): ?>
        <header class="mb-8 border-b border-slate-100 pb-6">
            <h1 class="text-3xl sm:text-5xl font-black tracking-tight text-slate-900 mb-4 leading-tight">
                <?= htmlspecialchars($title) ?>
            </h1>
            <?php if (!empty($subtitle)): ?>
                <p class="text-lg sm:text-xl text-slate-500 font-medium leading-relaxed"><?= htmlspecialchars($subtitle) ?></p>
            <?php endif; ?>
        </header>
    <?php endif; ?>
    <?= $content_html ?>
</div>

<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>
