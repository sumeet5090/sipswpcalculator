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

ob_start();
?>

<div class="markdown-content">
    <?= $content_html ?>
</div>

<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>
