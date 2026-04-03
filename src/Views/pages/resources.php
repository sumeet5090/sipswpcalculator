<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../functions.php';
$active_page = 'resources.php';

/**
 * resources.php
 * Premium Category Archive with Global Glassmorphism Consistency
 */

$all_posts = [
    // Growth Category (Emerald Accents)
    ['category' => 'growth', 'tag' => 'Beginner', 'tag_color' => 'emerald', 'title' => 'SIP for Beginners: Complete Guide (2026)', 'desc' => 'Everything you need to know to start your first SIP today — KYC, fund selection, and the step-up strategy explained simply.', 'href' => '/resource/sip-for-beginners', 'featured' => true, 'read_time' => '12 min', 'date' => 'March 2026'],
    ['category' => 'growth', 'tag' => 'Strategy', 'tag_color' => 'emerald', 'title' => 'The 20-Year Wealth Blueprint', 'desc' => 'How a simple 10% annual step-up SIP can more than double your final corpus over two decades.', 'href' => '/resource/20-year-wealth-blueprint-step-up-sip', 'read_time' => '8 min', 'date' => 'February 2026'],
    ['category' => 'growth', 'tag' => 'Milestone', 'tag_color' => 'emerald', 'title' => 'How to Reach $1 Million (or ₹1 Crore)', 'desc' => 'Hitting the million-dollar milestone is a matter of math and consistency. Model exactly what you need monthly.', 'href' => '/resource/reach-1-million-dollar-1-crore-rupees-in-18-years', 'read_time' => '10 min', 'date' => 'January 2026'],
    ['category' => 'growth', 'tag' => 'Inflation', 'tag_color' => 'emerald', 'title' => 'The Silent Killer: Inflation Impact on SIP', 'desc' => 'Why flat SIPs guarantee your purchasing power erodes — and the step-up strategy that fights back.', 'href' => '/resource/inflation-impact-on-sip', 'read_time' => '7 min', 'date' => 'March 2026'],
    ['category' => 'growth', 'tag' => 'Optimization', 'tag_color' => 'emerald', 'title' => 'Why Flat SIPs Are Costing You Millions', 'desc' => 'A 10% annual step-up can more than double your final corpus. See the math.', 'href' => '/resource/why-flat-sips-lose-money-stepup-sip-power', 'read_time' => '6 min', 'date' => 'February 2026'],

    // Retirement Category (Indigo Accents)
    ['category' => 'retirement', 'tag' => 'Strategy', 'tag_color' => 'indigo', 'title' => 'The 4% SWP Rule Explained', 'desc' => 'Structure your retirement withdrawals to ensure your money outlasts you. Master the 4% rule.', 'href' => '/resource/retirement-planning-4-percent-swp-rule', 'featured' => true, 'read_time' => '15 min', 'date' => 'March 2026'],
    ['category' => 'retirement', 'tag' => 'Deep Dive', 'tag_color' => 'indigo', 'title' => 'Mathematics of the 4% SWP Rule', 'desc' => 'Sequence-of-returns risk, inflation adjustment, and portfolio survival probability over 30 years.', 'href' => '/resource/mathematics-of-4-percent-rule-swp', 'read_time' => '11 min', 'date' => 'January 2026'],
    ['category' => 'retirement', 'tag' => 'Lifecycle', 'tag_color' => 'indigo', 'title' => 'SIP vs. SWP: Build & Enjoy', 'desc' => 'The full lifecycle: how to transition from your SIP accumulation phase into a sustainable SWP income stream.', 'href' => '/resource/sip-vs-swp-wealth-creation-withdrawal-strategy', 'read_time' => '9 min', 'date' => 'February 2026'],
    ['category' => 'retirement', 'tag' => 'Planning', 'tag_color' => 'indigo', 'title' => 'SWP Retirement Planning Guide', 'desc' => 'A complete guide to using Systematic Withdrawal Plans to generate reliable monthly income in retirement.', 'href' => '/resource/swp-retirement-planning', 'read_time' => '13 min', 'date' => 'March 2026'],
    ['category' => 'retirement', 'tag' => 'Transition', 'tag_color' => 'indigo', 'title' => 'The SIP to SWP Transition Masterguide', 'desc' => 'How to flawlessly automate the bucket strategy and protect your corpus 3 years before retirement.', 'href' => '/resource/sip-to-swp-transition-guide', 'read_time' => '14 min', 'date' => 'February 2026'],

    // Comparison Category (Amber Accents)
    ['category' => 'comparison', 'tag' => 'Comparison', 'tag_color' => 'amber', 'title' => 'SIP vs FD vs PPF: A Direct Comparison', 'desc' => 'Returns, risk, liquidity, and tax compared across major investment instruments. Choose the right path.', 'href' => '/resource/sip-vs-fd-vs-ppf', 'featured' => true, 'read_time' => '10 min', 'date' => 'March 2026'],
    ['category' => 'comparison', 'tag' => 'Comparison', 'tag_color' => 'amber', 'title' => 'SWP vs Fixed Deposit: Which Wins?', 'desc' => 'Head-to-head analysis of SWP from mutual funds vs. bank FDs for generating retirement income.', 'href' => '/resource/swp-vs-fixed-deposit', 'read_time' => '8 min', 'date' => 'February 2026'],
    ['category' => 'comparison', 'tag' => 'Comparison', 'tag_color' => 'amber', 'title' => 'SWP vs Annuity 2026', 'desc' => 'Should you buy an annuity or run an SWP? An honest, data-driven comparison for 2026.', 'href' => '/resource/swp-vs-annuity-2026', 'read_time' => '7 min', 'date' => 'January 2026'],
    ['category' => 'comparison', 'tag' => 'Tax', 'tag_color' => 'amber', 'title' => 'Mutual Fund Tax Guide 2026', 'desc' => 'LTCG, STCG, ELSS, and indexation — everything you need after the 2026 budget changes.', 'href' => '/resource/mutual-fund-tax-2026', 'read_time' => '11 min', 'date' => 'March 2026'],
];

$categories = [
    'growth' => [
        'title' => 'Wealth Growth',
        'desc' => 'Master the compounding engines that drive 20-year wealth building for the modern investor.',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>',
        'accent' => 'emerald'
    ],
    'retirement' => [
        'title' => 'Retirement Hub',
        'desc' => 'Expert withdrawal strategies for generating reliable monthly income without outliving your savings.',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
        'accent' => 'indigo'
    ],
    'comparison' => [
        'title' => 'Strategy Center',
        'desc' => 'Direct comparisons between major investment vehicles and navigating the current tax landscape.',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>',
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
    /* Harmonized Theme Colors */
    :root {
        --mesh-accent-1: rgba(5, 150, 105, 0.05); /* Emerald 600 */
        --mesh-accent-2: rgba(79, 70, 229, 0.05); /* Indigo 600 */
    }

    .subtle-mesh {
        background: 
            radial-gradient(at 0% 0%, var(--mesh-accent-1) 0px, transparent 50%),
            radial-gradient(at 100% 0%, var(--mesh-accent-2) 0px, transparent 50%),
            radial-gradient(at 50% 100%, var(--mesh-accent-1) 0px, transparent 50%);
        background-size: cover;
    }

    .animate-entry {
        animation: archiveEntry 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        opacity: 0;
    }

    @keyframes archiveEntry {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .archive-nav-active {
        color: #059669 !important; /* Emerald primary highlight */
        border-bottom-color: #059669 !important;
    }

    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    /* Custom Section Glows (Unified with site theme) */
    .section-glow-emerald:hover { box-shadow: 0 0 30px -10px rgba(5, 150, 105, 0.15); }
    .section-glow-indigo:hover { box-shadow: 0 0 30px -10px rgba(79, 70, 229, 0.15); }
</style>

<!-- ── Navigation ── -->
<div class="sticky top-16 z-40 bg-white/70 backdrop-blur-md border-b border-slate-200 sm:-mx-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-8 py-3 flex items-center justify-between">
        <nav class="flex gap-10 overflow-x-auto no-scrollbar whitespace-nowrap text-xs font-black text-slate-500 uppercase tracking-widest">
            <?php foreach ($categories as $id => $cat): ?>
                <a href="#<?= $id ?>" class="archive-nav-link pb-1 border-b-2 border-transparent hover:text-emerald-600 transition-all"><?= $cat['title'] ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="hidden md:block text-[10px] font-black text-slate-400 italic">
            Financial Planning Archive Hub
        </div>
    </div>
</div>

<header class="relative py-24 mb-16 rounded-3xl -mx-4 sm:-mx-0 overflow-hidden">
    <div class="absolute inset-0 subtle-mesh"></div>
    <div class="relative z-10 max-w-4xl px-8">
        <span class="inline-block px-3 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-black uppercase tracking-widest rounded-full mb-6 animate-entry" style="animation-delay: 0.1s">
            Investor Education
        </span>
        <h1 class="text-5xl md:text-7xl font-black tracking-tighter text-slate-900 mb-8 animate-entry" style="animation-delay: 0.2s">
            Research & <br><span class="text-emerald-600">Resources</span>
        </h1>
        <p class="text-xl text-slate-500 max-w-2xl leading-relaxed animate-entry" style="animation-delay: 0.3s">
            Explore deep-dives on Systematic Investment Plans, retirement math, and honest instrument comparisons. Built for modern investors planning two decades ahead.
        </p>
    </div>
</header>

<div class="space-y-32 pb-32">
    <?php 
    $delay = 0.4;
    foreach ($categories as $cat_id => $cat): 
        $accent_color = $cat['accent'];
    ?>
        <section id="<?= $cat_id ?>" class="scroll-mt-40 animate-entry" style="animation-delay: <?= $delay ?>s">
            <!-- Category Header -->
            <div class="flex items-center gap-6 mb-12 group">
                <div class="w-14 h-14 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shadow-lg shadow-emerald-600/20 group-hover:scale-110 transition-transform">
                    <?= $cat['icon'] ?>
                </div>
                <div>
                    <h2 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tighter leading-none mb-2"><?= $cat['title'] ?></h2>
                    <p class="text-slate-500 font-medium max-w-xl text-lg"><?= $cat['desc'] ?></p>
                </div>
            </div>

            <!-- Posts Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php 
                $posts = $posts_by_cat[$cat_id] ?? [];
                foreach ($posts as $post): 
                    $is_featured = $post['featured'] ?? false;
                ?>
                    <article class="group relative glass-card flex flex-col p-8 transition-all section-glow-<?= $accent_color ?> <?= $is_featured ? 'md:col-span-2 lg:col-span-2' : '' ?>">
                        <div class="flex flex-col h-full">
                            <div class="flex items-center justify-between mb-6">
                                <span class="text-[10px] font-black uppercase tracking-widest text-<?= $accent_color ?>-600">
                                    <?= $post['tag'] ?>
                                </span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    <?= $post['read_time'] ?> Read
                                </span>
                            </div>
                            
                            <h3 class="<?= $is_featured ? 'text-3xl md:text-4xl' : 'text-xl' ?> font-black text-slate-900 group-hover:text-emerald-600 transition-colors leading-[1.2] mb-6">
                                <a href="<?= $post['href'] ?>" class="after:absolute after:inset-0">
                                    <?= htmlspecialchars($post['title']) ?>
                                </a>
                            </h3>
                            
                            <p class="text-slate-500 leading-relaxed font-medium <?= $is_featured ? 'text-lg line-clamp-3 mb-10' : 'text-sm line-clamp-3 mb-8' ?>">
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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sections = document.querySelectorAll('section');
        const navLinks = document.querySelectorAll('.archive-nav-link');

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
                        link.classList.toggle('archive-nav-active', link.getAttribute('href') === '#' + id);
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