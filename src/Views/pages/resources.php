<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../functions.php';
$active_page = 'resources.php';

/**
 * resources.php
 * Premium Resources Hub - Sidebar Navigation Refactor
 */

$all_posts = [
    // Growth Category (Emerald Accents)
    ['category' => 'growth', 'id' => 'growth', 'tag' => 'Beginner', 'tag_color' => 'emerald', 'title' => 'SIP for Beginners: Complete Guide (2026)', 'desc' => 'Everything you need to know to start your first SIP today — KYC, fund selection, and the step-up strategy explained simply.', 'href' => '/resource/growth/sip-for-beginners', 'featured' => true, 'read_time' => '12 min', 'date' => 'March 2026'],
    ['category' => 'growth', 'id' => 'growth', 'tag' => 'Strategy', 'tag_color' => 'emerald', 'title' => 'The 20-Year Wealth Blueprint', 'desc' => 'How a simple 10% annual step-up SIP can more than double your final corpus over two decades.', 'href' => '/resource/growth/20-year-wealth-blueprint-step-up-sip', 'read_time' => '8 min', 'date' => 'February 2026'],
    ['category' => 'growth', 'id' => 'growth', 'tag' => 'Milestone', 'tag_color' => 'emerald', 'title' => 'How to Reach $1 Million (or ₹1 Crore)', 'desc' => 'Hitting the million-dollar milestone is a matter of math and consistency. Model exactly what you need monthly.', 'href' => '/resource/growth/reach-1-million-dollar-1-crore-rupees-in-18-years', 'read_time' => '10 min', 'date' => 'January 2026'],
    ['category' => 'growth', 'id' => 'growth', 'tag' => 'Inflation', 'tag_color' => 'emerald', 'title' => 'The Silent Killer: Inflation Impact on SIP', 'desc' => 'Why flat SIPs guarantee your purchasing power erodes — and the step-up strategy that fights back.', 'href' => '/resource/growth/inflation-impact-on-sip', 'read_time' => '7 min', 'date' => 'March 2026'],

    // Retirement Category (Indigo Accents)
    ['category' => 'retirement', 'id' => 'retirement', 'tag' => 'Strategy', 'tag_color' => 'indigo', 'title' => 'The 4% SWP Rule Explained', 'desc' => 'Structure your retirement withdrawals to ensure your money outlasts you. Master the 4% rule.', 'href' => '/resource/retirement/retirement-planning-4-percent-swp-rule', 'featured' => true, 'read_time' => '15 min', 'date' => 'March 2026'],
    ['category' => 'retirement', 'id' => 'retirement', 'tag' => 'Lifecycle', 'tag_color' => 'indigo', 'title' => 'SIP vs. SWP: Build & Enjoy', 'desc' => 'The full lifecycle: how to transition from your SIP accumulation phase into a sustainable SWP income stream.', 'href' => '/resource/retirement/sip-vs-swp-wealth-creation-withdrawal-strategy', 'read_time' => '9 min', 'date' => 'February 2026'],
    ['category' => 'retirement', 'id' => 'retirement', 'tag' => 'Planning', 'tag_color' => 'indigo', 'title' => 'SWP Retirement Planning Guide', 'desc' => 'A complete guide to using Systematic Withdrawal Plans to generate reliable monthly income in retirement.', 'href' => '/resource/retirement/swp-retirement-planning', 'read_time' => '13 min', 'date' => 'March 2026'],

    // Comparison Category (Amber Accents)
    ['category' => 'comparison', 'id' => 'comparison', 'tag' => 'Comparison', 'tag_color' => 'amber', 'title' => 'SIP vs FD vs PPF: A Direct Comparison', 'desc' => 'Returns, risk, liquidity, and tax compared across major investment instruments. Choose the right path.', 'href' => '/resource/comparison/sip-vs-fd-vs-ppf', 'featured' => true, 'read_time' => '10 min', 'date' => 'March 2026'],
    ['category' => 'comparison', 'id' => 'comparison', 'tag' => 'Comparison', 'tag_color' => 'amber', 'title' => 'SWP vs Fixed Deposit: Which Wins?', 'desc' => 'Head-to-head analysis of SWP from mutual funds vs. bank FDs for generating retirement income.', 'href' => '/resource/comparison/swp-vs-fixed-deposit', 'read_time' => '8 min', 'date' => 'February 2026'],
    ['category' => 'comparison', 'id' => 'comparison', 'tag' => 'Comparison', 'tag_color' => 'amber', 'title' => 'SWP vs Annuity 2026', 'desc' => 'Should you buy an annuity or run an SWP? An honest, data-driven comparison for 2026.', 'href' => '/resource/comparison/swp-vs-annuity-2026', 'read_time' => '7 min', 'date' => 'January 2026'],
    ['category' => 'comparison', 'id' => 'comparison', 'tag' => 'Tax', 'tag_color' => 'amber', 'title' => 'Mutual Fund Tax Guide 2026', 'desc' => 'LTCG, STCG, ELSS, and indexation — everything you need after the 2026 budget changes.', 'href' => '/resource/comparison/mutual-fund-tax-2026', 'read_time' => '11 min', 'date' => 'March 2026'],
];

$categories = [
    'growth' => [
        'title' => 'Wealth Growth',
        'desc' => 'Master the compounding engines that drive 20-year wealth building for the modern investor.',
        'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>',
        'accent' => 'emerald'
    ],
    'retirement' => [
        'title' => 'Retirement Hub',
        'desc' => 'Expert withdrawal strategies for generating reliable monthly income without outliving your savings.',
        'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
        'accent' => 'indigo'
    ],
    'comparison' => [
        'title' => 'Strategy Center',
        'desc' => 'Direct comparisons between major investment vehicles and navigating the current tax landscape.',
        'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>',
        'accent' => 'amber'
    ]
];

$posts_by_cat = [];
foreach ($all_posts as $post) {
    $posts_by_cat[$post['category']][] = $post;
}

$page_config = [
    'title' => 'Financial Archive: Expert Guides & Strategic Insights',
    'meta_desc' => 'A curated archive of financial wisdom covering wealth creation via SIP, retirement via SWP, and in-depth instrument comparisons.',
];

ob_start();
?>
<style>
    .animate-entry {
        animation: archiveEntry 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        opacity: 0;
    }

    @keyframes archiveEntry {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .archive-sidebar-active {
        color: #059669 !important; /* Emerald highlight */
        background-color: #ecfdf5 !important;
        border-left-color: #10b981 !important;
    }

    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    /* Custom Section Glows */
    .section-glow-emerald:hover { box-shadow: 0 0 30px -10px rgba(5, 150, 105, 0.15); }
    .section-glow-indigo:hover { box-shadow: 0 0 30px -10px rgba(79, 70, 229, 0.15); }
    .section-glow-amber:hover { box-shadow: 0 0 30px -10px rgba(245, 158, 11, 0.15); }
</style>

<!-- Standard Header (Consistent with FAQ/Glossary) -->
<header class="text-center mb-16 lg:mb-20 pt-8 lg:pt-12 animate-entry">
    <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight pb-2 text-slate-900">
        Research & <span class="text-emerald-600">Resources</span>
    </h1>
    <p class="mt-4 text-lg lg:text-xl text-slate-500 max-w-2xl mx-auto leading-relaxed">
        Expert insights, deep-dives on Systematic Investment Plans, retirement math, and honest instrument comparisons.
    </p>
</header>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-start pb-32">
    <!-- Sidebar Navigation -->
    <aside class="lg:col-span-3 sticky top-28 hidden lg:block">
        <nav aria-label="Archive Categories">
            <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-6 px-4">
                Explore Categories
            </h3>
            <ul class="space-y-2">
                <?php foreach ($categories as $id => $cat): ?>
                    <li>
                        <a href="#<?= $id ?>" class="archive-sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-slate-600 border-l-4 border-transparent hover:bg-slate-50 hover:text-slate-900 transition-all">
                            <?= $cat['icon'] ?>
                            <?= $cat['title'] ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="lg:col-span-9">
        <!-- Mobile Navigation (Horizontal Scroll) -->
        <div class="lg:hidden sticky top-16 z-40 bg-white/80 backdrop-blur-md border-b border-slate-100 -mx-4 px-4 py-3 mb-10">
            <nav class="flex gap-4 overflow-x-auto no-scrollbar whitespace-nowrap text-xs font-black text-slate-500 uppercase tracking-widest">
                <?php foreach ($categories as $id => $cat): ?>
                    <a href="#<?= $id ?>" class="archive-sidebar-link px-4 py-2 bg-slate-50 rounded-full border border-slate-200">
                        <?= $cat['title'] ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <div class="space-y-24 lg:space-y-40">
            <?php 
            $delay = 0.1;
            foreach ($categories as $cat_id => $cat): 
                $accent_color = $cat['accent'];
            ?>
                <section id="<?= $cat_id ?>" class="scroll-mt-40 animate-entry" style="animation-delay: <?= $delay ?>s">
                <!-- Section Header -->
                <div class="flex items-center gap-4 lg:gap-6 mb-8 lg:mb-10 group">
                    <div class="w-12 h-12 lg:w-14 lg:h-14 rounded-2xl bg-slate-900 text-white flex items-center justify-center shadow-xl">
                        <?= $cat['icon'] ?>
                    </div>
                    <div>
                        <h2 class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight leading-none mb-2"><?= $cat['title'] ?></h2>
                        <p class="text-slate-500 text-sm lg:text-lg font-medium max-w-2xl leading-relaxed"><?= $cat['desc'] ?></p>
                    </div>
                </div>

                <!-- Posts Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-10">
                    <?php 
                    $posts = $posts_by_cat[$cat_id] ?? [];
                    foreach ($posts as $post): 
                        $is_featured = $post['featured'] ?? false;
                    ?>
                        <article class="group relative glass-card flex flex-col transition-all section-glow-<?= $accent_color ?> <?= $is_featured ? 'md:col-span-2 p-10 lg:p-14' : 'p-8' ?>">
                            <div class="flex flex-col h-full">
                                <div class="flex items-center justify-between mb-6">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-<?= $accent_color ?>-600 px-3 py-1 bg-<?= $accent_color ?>-50 rounded-full">
                                        <?= $post['tag'] ?>
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                        <?= $post['read_time'] ?> Read
                                    </span>
                                </div>
                                
                                <h3 class="<?= $is_featured ? 'text-2xl md:text-3xl lg:text-4xl' : 'text-xl' ?> font-black text-slate-900 group-hover:text-emerald-600 transition-colors leading-[1.2] mb-6">
                                    <a href="<?= $post['href'] ?>" class="after:absolute after:inset-0">
                                        <?= htmlspecialchars($post['title']) ?>
                                    </a>
                                </h3>
                                
                                <p class="text-slate-500 leading-relaxed font-medium <?= $is_featured ? 'text-base lg:text-lg line-clamp-3 mb-10' : 'text-sm line-clamp-3 mb-8' ?>">
                                    <?= htmlspecialchars($post['desc']) ?>
                                </p>
                                
                                <div class="mt-auto flex items-center justify-between pt-6 border-t border-slate-100/60">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest"><?= $post['date'] ?> Edition</span>
                                    <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center -rotate-45 group-hover:rotate-0 transition-transform duration-300">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php 
            $delay += 0.1;
            endforeach; ?>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sections = document.querySelectorAll('section');
        const navLinks = document.querySelectorAll('.archive-sidebar-link');

        const observerOptions = {
            root: null,
            rootMargin: '-20% 0px -70% 0px',
            threshold: 0
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.getAttribute('id');
                    navLinks.forEach(link => {
                        const href = link.getAttribute('href');
                        if (href === '#' + id) {
                            link.classList.add('archive-sidebar-active');
                        } else {
                            link.classList.remove('archive-sidebar-active');
                        }
                    });
                }
            });
        }, observerOptions);

        sections.forEach(section => observer.observe(section));
    });
</script>

<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/../layouts/layout.php';
?>