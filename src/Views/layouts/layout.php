<?php
/**
 * layout.php
 * The main 12-column grid wrapper.
 * Requires: $page_content to be defined before inclusion.
 */

// If $page_config hasn't been set, define a fallback.
$page_config = $page_config ?? [];

require_once __DIR__ . '/layout-top.php';

$is_home = ($active_page === 'index.php');
$is_index = ($active_page === 'resources.php' || $active_page === 'faq.php' || $active_page === 'glossary.php');

// A page is a blog post (needs sidebars) if it starts with /resource/ and is NOT the index
$is_resource_index = ($active_page === 'resources.php');
$is_blog_post = (strpos($_SERVER['REQUEST_URI'], '/resource/') !== false);

$page_content = $page_content ?? $content ?? '';
?>
<?php if ($is_blog_post && !$is_resource_index): ?>
<div class="doc-layout-grid relative">
    <aside class="doc-sidebar doc-sidebar-left pl-4 pr-6">
        <div class="sticky top-28 pt-4 pb-8">
            <?php require_once __DIR__ . '/sidebar-left.php'; ?>
        </div>
    </aside>

    <main class="doc-main-content px-6 lg:px-10 xl:px-12 py-8 pb-16">
        <div class="max-w-4xl mx-auto">
            <?php require_once __DIR__ . '/breadcrumbs.php'; ?>

            <article id="main-content" class="prose prose-slate max-w-none prose-headings:scroll-mt-28">
                <header class="mb-8 pb-8 border-b border-slate-200">
                    <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-900 mb-3">
                        <?= htmlspecialchars($page_config['title'] ?? $title ?? '')?>
                    </h1>

                    <?php if (!empty($subtitle)): ?>
                        <p class="text-lg text-slate-500 font-medium mb-6"><?= htmlspecialchars($subtitle) ?></p>
                    <?php endif; ?>

                    <div class="flex flex-wrap items-center gap-4 text-sm text-slate-500">
                        <div class="flex items-center gap-3">
                            <img src="/assets/sumeet-boga-56.jpg" alt="Sumeet Boga"
                                class="w-10 h-10 rounded-full shadow-sm border border-emerald-100 object-cover"
                                width="40" height="40">
                            <div>
                                <span class="block font-bold text-slate-700 leading-tight">Sumeet Boga</span>
                                <span class="text-xs text-slate-500">Software Engineer & Author</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 ml-auto">
                            <time datetime="<?= date('Y-m-d')?>" class="hidden sm:block">
                                Last Updated:
                                <?= htmlspecialchars($updated_date ?? date('F j, Y'))?>
                            </time>
                            <span class="hidden sm:block text-slate-300">•</span>
                            <span>8 min read</span>
                        </div>
                    </div>
                </header>

                <?= $page_content ?? ''?>
            </article>
        </div>
    </main>

    <aside class="doc-sidebar doc-sidebar-right pl-6 pr-4">
        <div class="sticky top-28 pt-4 pb-8">
            <?php require_once __DIR__ . '/sidebar-right.php'; ?>
        </div>
    </aside>
</div>

<?php
else: ?>
<!-- Home and Landing Pages are full width within the outer container -->
<main class="w-full py-8">
    <?php if (!$is_home): ?>
    <?php require_once __DIR__ . '/breadcrumbs.php'; ?>
    <?php
    endif; ?>

    <?= $page_content ?? ''?>
</main>
<?php
endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>

<script src="/assets/js/toc.js" defer></script>
<?php
require_once __DIR__ . '/layout-bottom.php';
?>