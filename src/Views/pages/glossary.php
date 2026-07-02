<?php

/**
 * glossary.php
 * Final Robust Redesign for the Financial Glossary.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../functions.php';

$page_config = [
    'title' => 'Financial Glossary | SIP & SWP Calculator - Investing Terms Explained',
    'meta_desc' => 'Understand complex financial jargon with our simple, plain-English glossary. Terms covered: XIRR, SWP, SIP, Expense Ratio, 4% Rule, and more.',
];

$active_page = 'glossary.php';

$glossary_terms = [
    ['id' => 'sip', 'category' => 'Investment Basics', 'q' => 'Systematic Investment Plan (SIP)', 'a' => 'A method of investing a fixed sum regularly in a mutual fund, rather than a one-time lump sum. It benefits from rupee-cost averaging.'],
    ['id' => 'corpus', 'category' => 'Investment Basics', 'q' => 'Investment Corpus', 'a' => 'Your total wealth pool — the sum of all your original investments plus market gains accrued over time.'],
    ['id' => 'expense-ratio', 'category' => 'Investment Basics', 'q' => 'Expense Ratio', 'a' => 'The annual fee charged by a mutual fund to cover management and administration costs. Lower is usually better.'],
    ['id' => 'xirr', 'category' => 'Advanced Metrics', 'q' => 'XIRR (Extended Internal Rate of Return)', 'a' => 'An accurate measure of returns for uneven cash flows like SIPs, accounting for the timing of every installment.'],
    ['id' => 'cagr', 'category' => 'Advanced Metrics', 'q' => 'CAGR (Compound Annual Growth Rate)', 'a' => 'The mean annual growth rate of an investment over a specified period longer than one year.'],
    ['id' => 'swp', 'category' => 'Retirement & Withdrawal', 'q' => 'Systematic Withdrawal Plan (SWP)', 'a' => 'A facility to withdraw a fixed amount regularly from a mutual fund, highly tax-efficient for retirement income.'],
    ['id' => 'swr', 'category' => 'Retirement & Withdrawal', 'q' => 'Safe Withdrawal Rate (SWR)', 'a' => 'The maximum percentage of your portfolio withdrawn annually without a high risk of exhausting funds.'],
    ['id' => 'four-percent-rule', 'category' => 'Retirement & Withdrawal', 'q' => 'The 4% Rule', 'a' => 'A guideline suggesting that if you withdraw 4% of your total corpus in the first year and adjust for inflation, the funds may last 30+ years.']
];

usort($glossary_terms, function ($a, $b) {
    return strcmp($a['q'], $b['q']);
});

$letters = [];
foreach ($glossary_terms as $term) {
    $firstChar = strtoupper(substr($term['q'], 0, 1));
    if (!in_array($firstChar, $letters)) {
        $letters[] = $firstChar;
    }
}
sort($letters);

$schemaHelper = new \Core\SchemaHelper();
$breadcrumbs = $schemaHelper->getBreadcrumbs([
    'Home' => '/',
    'Glossary' => '/glossary'
]);

$faqData = [];
foreach ($glossary_terms as $term) {
    $faqData[$term['q']] = $term['a'];
}
$faq = $schemaHelper->getFAQ($faqData);

$page_config['additional_head'] = '
    <script type="application/ld+json">' . $breadcrumbs . '</script>
    <script type="application/ld+json">' . $faq . '</script>
';

ob_start();
?>

<?php include __DIR__ . '/../components/theme-glow-blobs.php'; ?>

<?php
$hero = [
    'badge' => 'Financial Terms',
    'title_prefix' => 'Financial ',
    'title_highlight' => 'Encyclopedia',
    'description' => 'Master the terminology of wealth growth and retirement planning. Clear, simple definitions for every concept.'
];
include __DIR__ . '/../components/page-hero.php';
?>

<div class="max-w-7xl mx-auto pb-32 px-4 relative">
    <!-- Search Bar -->
    <div class="relative max-w-xl mx-auto mb-10">
        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <input id="glossarySearch" type="text" placeholder="Search glossary terms or definitions..."
            class="w-full pl-11 pr-4 py-3 border border-slate-200 rounded-2xl bg-white text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-sm transition-all text-sm font-medium">
    </div>

    <!-- A-Z Navigation (No-Fail-Spacing with Padding) -->
    <nav class="sticky top-24 z-50 mb-16 py-4 flex justify-center">
        <div
            class="nav-pill flex flex-wrap justify-center items-center bg-white/95 backdrop-blur-xl border border-slate-200/60 rounded-full px-4 py-3 shadow-2xl shadow-emerald-500/10 gap-x-1 sm:gap-x-4">
            <?php foreach ($letters as $letter) : ?>
            <a href="#letter-<?= $letter?>"
                class="letter-link min-w-[40px] h-10 sm:min-w-[48px] sm:h-12 flex items-center justify-center rounded-full text-lg sm:text-xl font-black text-slate-400 hover:text-white hover:bg-emerald-600 transition-all duration-300">
                <?= $letter?>
            </a>
                <?php
            endforeach; ?>
        </div>
    </nav>

    <!-- Empty State -->
    <div id="emptyState" class="hidden text-center py-20 animate-entry">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 border border-slate-100 text-slate-400 mb-4">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h3 class="text-xl font-bold text-slate-800">No terms found</h3>
        <p class="mt-2 text-slate-500 max-w-xs mx-auto text-sm leading-relaxed">We couldn't find any terms matching your search. Try another query.</p>
    </div>

    <!-- Glossary Content Grid -->
    <div id="glossaryContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php
        $currentLetter = '';
        foreach ($glossary_terms as $term) :
            $firstChar = strtoupper(substr($term['q'], 0, 1));
            if ($firstChar !== $currentLetter) :
                $currentLetter = $firstChar;
                ?>
        <!-- Mandatory Section Break -->
        <div id="letter-<?= $currentLetter?>" class="section-header col-span-full pt-16 pb-8">
            <div class="flex items-center gap-8">
                <span class="text-7xl font-black text-slate-900/5 leading-none select-none">
                    <?= $currentLetter?>
                </span>
                <div class="h-[2px] w-full bg-slate-100 rounded-full flex-grow"></div>
            </div>
        </div>
                <?php
            endif; ?>

        <!-- Premium Glossary Card -->
        <article
            class="glossary-card glass-card bg-white rounded-3xl p-6 border border-slate-100 shadow-sm hover:shadow-3xl hover:border-emerald-500/10 hover:-translate-y-4 transition-all duration-500 group relative flex flex-col h-full overflow-hidden"
            data-term="<?= strtolower($term['q'])?>" data-desc="<?= strtolower($term['a'])?>">

            <div class="relative z-10 flex flex-col h-full">
                <span class="inline-flex self-start px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-50 text-slate-500 border border-slate-100 uppercase tracking-wider mb-4">
                    <?= htmlspecialchars($term['category']) ?>
                </span>
                <h3
                    class="text-2xl font-black text-slate-900 mb-6 leading-tight group-hover:text-emerald-700 transition-colors">
                    <?= htmlspecialchars($term['q'])?>
                </h3>

                <p class="text-sm leading-relaxed flex-grow text-slate-500 font-medium">
                    <?= $term['a']?>
                </p>
            </div>
        </article>
                <?php
        endforeach; ?>
    </div>
</div>

<style>
    /* Mandatory Override for Grid Headers */
    .section-header {
        grid-column: 1 / -1 !important;
        width: 100% !important;
        display: block !important;
        scroll-margin-top: 200px;
    }

    /* Fixed Navigation Spacing */
    .letter-link {
        flex-shrink: 0 !important;
        margin: 0 4px !important;
    }

    @keyframes fadeInUpScale {
        from {
            opacity: 0;
            transform: translateY(40px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .glossary-card {
        animation: fadeInUpScale 0.7s cubic-bezier(0.19, 1, 0.22, 1) backwards;
    }

    html {
        scroll-behavior: smooth;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('glossarySearch');
        const cards = document.querySelectorAll('.glossary-card');
        const container = document.getElementById('glossaryContainer');
        const emptyState = document.getElementById('emptyState');
        const sectionHeaders = document.querySelectorAll('.section-header');
        const letterLinks = document.querySelectorAll('.letter-link');

        // ScrollSpy logic to highlight active A-Z letter
        function updateActiveLetter() {
            // If user is searching, don't show active scrollspy highlight
            if (searchInput && searchInput.value.trim().length > 0) {
                letterLinks.forEach(link => {
                    link.classList.remove('bg-emerald-600', 'text-white');
                    link.classList.add('text-slate-400');
                });
                return;
            }

            let activeLetter = '';
            const scrollPos = window.scrollY || document.documentElement.scrollTop;

            // Find the current section
            sectionHeaders.forEach(header => {
                const headerTop = header.offsetTop;
                // Offset calculation (nav height + safety margin)
                if (scrollPos >= headerTop - 180) {
                    activeLetter = header.id.replace('letter-', '');
                }
            });

            // Update classes
            letterLinks.forEach(link => {
                const target = link.getAttribute('href').replace('#letter-', '');
                if (target === activeLetter) {
                    link.classList.add('bg-emerald-600', 'text-white');
                    link.classList.remove('text-slate-400');
                } else {
                    link.classList.remove('bg-emerald-600', 'text-white');
                    link.classList.add('text-slate-400');
                }
            });
        }

        window.addEventListener('scroll', updateActiveLetter);
        updateActiveLetter(); // Initial run

        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            let visibleCount = 0;

            cards.forEach(card => {
                const term = card.getAttribute('data-term');
                const desc = card.getAttribute('data-desc');

                if (term.includes(query) || desc.includes(query)) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });

            if (query.length > 0) {
                sectionHeaders.forEach(header => header.classList.add('hidden'));
            } else {
                sectionHeaders.forEach(header => header.classList.remove('hidden'));
            }

            if (visibleCount === 0) {
                container.style.display = 'none';
                emptyState.classList.remove('hidden');
            } else {
                container.style.display = 'grid';
                emptyState.classList.add('hidden');
            }

            updateActiveLetter();
        });

        // Staggered card animations
        cards.forEach((card, index) => {
            card.style.animationDelay = (index * 0.08) + 's';
        });
    });
</script>

<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/../layouts/layout.php';
?>