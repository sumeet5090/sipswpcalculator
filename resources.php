<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
$current_page = 'resources.php';

$all_posts = [
    ['category' => 'growth', 'tag' => 'Beginner', 'tag_color' => 'indigo', 'title' => 'SIP for Beginners: Complete Guide (2026)', 'desc' => 'Everything you need to know to start your first SIP today — KYC, fund selection, and the step-up strategy explained simply.', 'href' => '/resource/sip-for-beginners.php'],
    ['category' => 'growth', 'tag' => 'Strategy', 'tag_color' => 'indigo', 'title' => 'The 20-Year Wealth Blueprint', 'desc' => 'How a simple 10% annual step-up SIP can more than double your final corpus over two decades.', 'href' => '/resource/20-year-wealth-blueprint-step-up-sip.php'],
    ['category' => 'growth', 'tag' => 'Milestone', 'tag_color' => 'emerald', 'title' => 'How to Reach $1 Million (or ₹1 Crore)', 'desc' => 'Hitting the million-dollar milestone is a matter of math and consistency. Model exactly what you need monthly.', 'href' => '/resource/reach-1-million-dollar-1-crore-rupees-in-18-years.php'],
    ['category' => 'growth', 'tag' => 'Inflation', 'tag_color' => 'rose', 'title' => 'The Silent Killer: Inflation Impact on SIP', 'desc' => 'Why flat SIPs guarantee your purchasing power erodes — and the step-up strategy that fights back.', 'href' => '/resource/inflation-impact-on-sip.php'],
    ['category' => 'growth', 'tag' => 'Optimization', 'tag_color' => 'amber', 'title' => 'Why Flat SIPs Are Costing You Millions', 'desc' => 'A 10% annual step-up can more than double your final corpus. See the math.', 'href' => '/resource/why-flat-sips-lose-money-stepup-sip-power.php'],
    ['category' => 'retirement', 'tag' => 'Strategy', 'tag_color' => 'indigo', 'title' => 'The 4% SWP Rule Explained', 'desc' => 'Structure your retirement withdrawals to ensure your money outlasts you. Master the 4% rule.', 'href' => '/resource/retirement-planning-4-percent-swp-rule.php'],
    ['category' => 'retirement', 'tag' => 'Deep Dive', 'tag_color' => 'amber', 'title' => 'Mathematics of the 4% SWP Rule', 'desc' => 'Sequence-of-returns risk, inflation adjustment, and portfolio survival probability over 30 years.', 'href' => '/resource/mathematics-of-4-percent-rule-swp.php'],
    ['category' => 'retirement', 'tag' => 'Lifecycle', 'tag_color' => 'purple', 'title' => 'SIP vs. SWP: Build & Enjoy', 'desc' => 'The full lifecycle: how to transition from your SIP accumulation phase into a sustainable SWP income stream.', 'href' => '/resource/sip-vs-swp-wealth-creation-withdrawal-strategy.php'],
    ['category' => 'retirement', 'tag' => 'Planning', 'tag_color' => 'rose', 'title' => 'SWP Retirement Planning Guide', 'desc' => 'A complete guide to using Systematic Withdrawal Plans to generate reliable monthly income in retirement.', 'href' => '/resource/swp-retirement-planning.php'],
    ['category' => 'retirement', 'tag' => 'Transition', 'tag_color' => 'teal', 'title' => 'The SIP to SWP Transition Masterguide', 'desc' => 'How to flawlessly automate the bucket strategy and protect your corpus 3 years before retirement.', 'href' => '/resource/sip-to-swp-transition-guide.php'],
    ['category' => 'comparison', 'tag' => 'Comparison', 'tag_color' => 'slate', 'title' => 'SIP vs FD vs PPF: A Direct Comparison', 'desc' => 'Returns, risk, liquidity, and tax compared across major investment instruments. Choose the right path.', 'href' => '/resource/sip-vs-fd-vs-ppf.php'],
    ['category' => 'comparison', 'tag' => 'Comparison', 'tag_color' => 'emerald', 'title' => 'SWP vs Fixed Deposit: Which Wins?', 'desc' => 'Head-to-head analysis of SWP from mutual funds vs. bank FDs for generating retirement income.', 'href' => '/resource/swp-vs-fixed-deposit.php'],
    ['category' => 'comparison', 'tag' => 'Comparison', 'tag_color' => 'violet', 'title' => 'SWP vs Annuity 2026', 'desc' => 'Should you buy an annuity or run an SWP? An honest, data-driven comparison for 2026.', 'href' => '/resource/swp-vs-annuity-2026.php'],
    ['category' => 'comparison', 'tag' => 'Tax', 'tag_color' => 'orange', 'title' => 'Mutual Fund Tax Guide 2026', 'desc' => 'LTCG, STCG, ELSS, and indexation — everything you need after the 2026 budget changes.', 'href' => '/resource/mutual-fund-tax-2026.php'],
];

$tag_colors = [
    'indigo' => 'bg-indigo-50 text-indigo-700',
    'emerald' => 'bg-emerald-50 text-emerald-700',
    'rose' => 'bg-rose-50 text-rose-700',
    'amber' => 'bg-amber-50 text-amber-700',
    'purple' => 'bg-purple-50 text-purple-700',
    'teal' => 'bg-teal-50 text-teal-700',
    'slate' => 'bg-slate-100 text-slate-600',
    'violet' => 'bg-violet-50 text-violet-700',
    'orange' => 'bg-orange-50 text-orange-700',
];

$stripes = [
    'indigo' => 'from-slate-900 via-indigo-900 to-slate-800',
    'emerald' => 'from-slate-900 via-emerald-900 to-slate-800',
    'rose' => 'from-slate-900 via-rose-900 to-slate-800',
    'amber' => 'from-slate-900 via-amber-900 to-slate-800',
    'purple' => 'from-slate-900 via-purple-900 to-slate-800',
    'teal' => 'from-slate-900 via-teal-900 to-slate-800',
    'slate' => 'from-slate-900 via-slate-800 to-slate-700',
    'violet' => 'from-slate-900 via-violet-900 to-slate-800',
    'orange' => 'from-slate-900 via-orange-900 to-slate-800'
];
?>
<?php
$page_config = [
    'title' => 'Financial Resources & Guides | SIP & SWP Calculator',
    'meta_desc' => 'Expert blogs and in-depth guides on SIP, SWP, the 4% rule, step-up investing, and wealth-building strategies for a stress-free retirement.',
];

ob_start();
?>
<style>
    .blog-card {
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        opacity: 1;
        display: flex;
    }

    .blog-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 40px -10px rgba(79, 70, 229, 0.12);
        border-color: rgba(99, 102, 241, 0.25) !important;
    }

    .filter-btn.active {
        background-color: #0f172a;
        color: white;
        border-color: #0f172a;
    }
</style>
<?php
$page_config['additional_head'] = ob_get_clean();
$active_page = 'resources.php';

ob_start();
?>

<!-- ── Hero Header ── -->
<header class="text-center mb-12 sm:mb-16">
    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-50 border border-indigo-100 mb-5">
        <span class="relative flex h-2.5 w-2.5">
            <span class="absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75 animate-ping"></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-indigo-500"></span>
        </span>
        <span class="text-sm font-semibold text-indigo-700 tracking-wide uppercase">Knowledge Hub</span>
    </div>

    <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight pb-2">
        <span class="text-gray-900">Resources &amp;</span> <span class="text-gradient">Financial Guides</span>
    </h1>
    <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto leading-relaxed">
        Expert insights, in-depth guides, and honest strategies to help you build wealth and plan a stress-free retirement.
    </p>

    <!-- Filter Bar -->
    <div class="flex flex-wrap justify-center gap-3 mt-10">
        <button data-filter="all" class="filter-btn active px-6 py-2.5 rounded-full border border-slate-200 bg-white text-slate-600 text-sm font-bold shadow-sm hover:border-indigo-300 transition-all">All Guides</button>
        <button data-filter="growth" class="filter-btn px-6 py-2.5 rounded-full border border-slate-200 bg-white text-slate-600 text-sm font-bold shadow-sm hover:border-indigo-300 transition-all">Wealth Growth</button>
        <button data-filter="retirement" class="filter-btn px-6 py-2.5 rounded-full border border-slate-200 bg-white text-slate-600 text-sm font-bold shadow-sm hover:border-indigo-300 transition-all">Retirement</button>
        <button data-filter="comparison" class="filter-btn px-6 py-2.5 rounded-full border border-slate-200 bg-white text-slate-600 text-sm font-bold shadow-sm hover:border-indigo-300 transition-all">Comparison</button>
    </div>
</header>

<main class="pb-16">
    <!-- Unified Grid -->
    <div id="blog-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($all_posts as $post):
            $tc = $tag_colors[$post['tag_color']] ?? 'bg-slate-100 text-slate-600';
            $stripe = $stripes[$post['tag_color']] ?? 'from-indigo-500 to-indigo-400';
        ?>
        <article class="blog-card glass-card flex flex-col border border-slate-100 rounded-2xl overflow-hidden" data-category="<?= $post['category']?>">
            <div class="h-1.5 w-full bg-gradient-to-r <?= $stripe?>"></div>
            <div class="flex flex-col flex-1 p-6">
                <div class="mb-4">
                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-full <?= $tc?> uppercase tracking-wider mb-4">
                        <?= $post['tag']?>
                    </span>
                    <h3 class="text-xl font-extrabold text-gray-900 leading-tight mb-3 group-hover:text-indigo-600">
                        <a href="<?= $post['href']?>">
                            <?= htmlspecialchars($post['title'])?>
                        </a>
                    </h3>
                    <p class="text-gray-500 text-sm leading-relaxed line-clamp-2">
                        <?= htmlspecialchars($post['desc'])?>
                    </p>
                </div>
                <div class="mt-auto pt-5 border-t border-slate-100">
                    <a href="<?= $post['href']?>" class="inline-flex items-center gap-2 text-indigo-600 font-bold text-sm hover:text-indigo-800 transition-all group">
                        Read Full Guide
                        <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Filtering
        const filterBtns = document.querySelectorAll('.filter-btn');
        const cards = document.querySelectorAll('.blog-card');

        function applyFilter(filter) {
            filterBtns.forEach(b => {
                b.classList.toggle('active', b.dataset.filter === filter);
            });

            cards.forEach(card => {
                if (filter === 'all' || card.dataset.category === filter) {
                    card.style.display = 'flex';
                    setTimeout(() => { card.style.opacity = '1'; }, 10);
                } else {
                    card.style.opacity = '0';
                    setTimeout(() => { card.style.display = 'none'; }, 200);
                }
            });
        }

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const filter = btn.dataset.filter;
                applyFilter(filter);
                // Update hash without jump
                history.pushState(null, '', '#' + filter);
            });
        });

        // Handle initial hash
        const initialHash = window.location.hash.slice(1);
        if (initialHash && ['growth', 'retirement', 'comparison'].includes(initialHash)) {
            applyFilter(initialHash);
        } else if (initialHash === 'all') {
            applyFilter('all');
        }

    });
</script>

<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/includes/layout.php';
?>