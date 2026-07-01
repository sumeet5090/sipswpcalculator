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
<?php if ($is_blog_post && !$is_resource_index): 
    // Determine category and accent styling dynamically
    $cat_slug = $category ?? ($post_metadata['category'] ?? 'growth');
    $cat_accent = 'emerald';
    $cat_name = 'Wealth Growth';
    $cat_icon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>';
    $gradient_glow = 'from-emerald-500 to-teal-500';
    $badge_classes = 'bg-emerald-50 text-emerald-700 border border-emerald-100';

    if ($cat_slug === 'retirement') {
        $cat_accent = 'indigo';
        $cat_name = 'Retirement Planning';
        $cat_icon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
        $gradient_glow = 'from-indigo-500 to-purple-500';
        $badge_classes = 'bg-indigo-50 text-indigo-700 border border-indigo-100';
    } elseif ($cat_slug === 'comparison') {
        $cat_accent = 'amber';
        $cat_name = 'Strategy & Comparisons';
        $cat_icon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>';
        $gradient_glow = 'from-amber-500 to-orange-500';
        $badge_classes = 'bg-amber-50 text-amber-700 border border-amber-100';
    }
?>
<div class="doc-layout-grid relative">
    <aside class="doc-sidebar doc-sidebar-left pl-4 pr-6">
        <div class="sticky top-28 pt-4 pb-8">
            <?php require_once __DIR__ . '/sidebar-left.php'; ?>
        </div>
    </aside>

    <main class="doc-main-content px-6 lg:px-10 xl:px-12 py-8 pb-16">
        <div class="max-w-4xl mx-auto">
            <?php require_once __DIR__ . '/breadcrumbs.php'; ?>

            <article id="main-content" class="prose prose-slate max-w-none prose-headings:scroll-mt-28 category-<?= $cat_accent ?>">
                <header class="relative mb-12 p-8 sm:p-10 rounded-3xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                    <!-- Decorative Category Glow -->
                    <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br <?= $gradient_glow ?> rounded-full blur-3xl opacity-[0.07] -mr-20 -mt-20 pointer-events-none"></div>
                    
                    <!-- Metadata Badges -->
                    <div class="relative z-10 flex flex-wrap items-center gap-2.5 mb-6">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider <?= $badge_classes ?>">
                            <?= $cat_icon ?>
                            <?= htmlspecialchars($cat_name) ?>
                        </span>
                        <?php if (!empty($post_metadata['tag'])): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 uppercase tracking-wider border border-slate-200/50">
                                <?= htmlspecialchars($post_metadata['tag']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Title -->
                    <h1 class="relative z-10 text-3xl sm:text-4xl md:text-5xl font-black tracking-tight text-slate-900 mb-4 leading-[1.15]">
                        <?= htmlspecialchars($page_config['title'] ?? $title ?? '')?>
                    </h1>

                    <!-- Subtitle -->
                    <?php if (!empty($subtitle)): ?>
                        <p class="relative z-10 text-lg sm:text-xl text-slate-500 font-medium leading-relaxed mb-8 max-w-3xl"><?= htmlspecialchars($subtitle) ?></p>
                    <?php endif; ?>

                    <!-- Author and Publish Details -->
                    <div class="relative z-10 flex flex-wrap items-center justify-between gap-6 pt-6 border-t border-slate-100">
                        <div class="flex items-center gap-3">
                            <img src="/assets/sumeet-boga-56.jpg" alt="Sumeet Boga"
                                class="w-10 h-10 rounded-full shadow-sm border border-slate-100 object-cover"
                                width="40" height="40">
                            <div>
                                <span class="block font-black text-slate-800 leading-tight">Sumeet Boga</span>
                                <span class="text-[11px] text-slate-400 font-bold uppercase tracking-wider">Engineer & Finance Analyst</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 text-xs font-bold text-slate-400 uppercase tracking-widest">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span><?= htmlspecialchars($post_metadata['date'] ?? $updated_date ?? 'March 2026') ?></span>
                            </div>
                            <span class="text-slate-200">|</span>
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                <span><?= htmlspecialchars($post_metadata['read_time'] ?? '8 min') ?> read</span>
                            </div>
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