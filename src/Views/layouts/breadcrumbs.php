<?php

/**
 * breadcrumbs.php
 * Dynamic breadcrumb navigation for the documentation layout.
 * Expects $page_config['title'] or $title, and $active_page.
 */

$page_title = $page_config['title'] ?? ($title ?? 'Current Page');

// Simplify title for breadcrumb if too long (split by both — and |)
$temp_title = explode('—', $page_title)[0] ?? $page_title;
$temp_title = explode('|', $temp_title)[0] ?? $temp_title;
$breadcrumb_title = mb_strimwidth($temp_title, 0, 40, '...');

// Check page types
$is_resource_index = ($active_page === 'resources.php');
$is_blog_post = (strpos($_SERVER['REQUEST_URI'], '/resource/') !== false);
?>
<nav aria-label="Breadcrumb" class="mb-6">
    <ol role="list" class="flex items-center space-x-2 text-sm text-slate-500">
        <li>
            <div class="flex items-center">
                <a href="/" class="hover:text-emerald-600 transition-colors">
                    <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9.293 2.293a1 1 0 011.414 0l7 7A1 1 0 0117 11h-1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1H9a1 1 0 00-1 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-6H3a1 1 0 01-.707-1.707l7-7z" clip-rule="evenodd" />
                    </svg>
                    <span class="sr-only">Home</span>
                </a>
            </div>
        </li>
        
        <?php if ($is_blog_post) : ?>
        <li>
            <div class="flex items-center">
                <svg class="h-4 w-4 flex-shrink-0 text-slate-300 mx-1" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
                <a href="/resources" class="hover:text-emerald-600 transition-colors">Resources</a>
            </div>
        </li>
        <?php endif; ?>

        <?php if ($active_page !== 'index.php') : ?>
        <li>
            <div class="flex items-center">
                <svg class="h-4 w-4 flex-shrink-0 text-slate-300 mx-1" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
                <span class="text-slate-700 font-medium" aria-current="page"><?= htmlspecialchars(trim($breadcrumb_title)) ?></span>
            </div>
        </li>
        <?php endif; ?>
    </ol>
</nav>
