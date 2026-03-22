<?php
/**
 * glossary.php
 * Financial Encyclopedia / Glossary page.
 */
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

$page_config = [
    'title' => 'Financial Glossary | SIP & SWP Calculator - Investing Terms Explained',
    'meta_desc' => 'Understand complex financial jargon with our simple, plain-English glossary. Terms covered: XIRR, SWP, SIP, Expense Ratio, 4% Rule, and more.',
];

$active_page = 'glossary.php';

// Categorized Glossary Terms
$glossary_categories = [
    'Investment Basics' => [
        [
            'id' => 'sip',
            'q' => 'Systematic Investment Plan (SIP)',
            'a' => 'A method of investing a fixed sum of money regularly in a mutual fund, rather than making a one-time lump sum payment. It benefits from rupee-cost averaging and disciplined wealth creation.'
        ],
        [
            'id' => 'corpus',
            'q' => 'Investment Corpus',
            'a' => 'Your corpus is the total accumulated wealth pool — the sum of all your original investments plus all market gains accrued over time. It represents the "pot" from which you can draw income in the future.'
        ],
        [
            'id' => 'expense-ratio',
            'q' => 'Expense Ratio',
            'a' => 'The annual fee charged by a mutual fund to cover management, administration, and marketing costs. It is expressed as a percentage of the fund\'s daily net assets. Lower expense ratios mean more money stays in your pocket.'
        ]
    ],
    'Advanced Metrics' => [
        [
            'id' => 'xirr',
            'q' => 'XIRR (Extended Internal Rate of Return)',
            'a' => 'XIRR is the most accurate way to calculate returns on investments like SIPs where multiple cash flows happen at different times. Unlike CAGR, which considers only initial and final values, XIRR accounts for the timing of every single installment.'
        ],
        [
            'id' => 'cagr',
            'q' => 'CAGR (Compound Annual Growth Rate)',
            'a' => 'The mean annual growth rate of an investment over a specified period of time longer than one year. It represents the smoothed annual rate of return as if the investment had grown at a steady rate.'
        ]
    ],
    'Retirement & Withdrawal' => [
        [
            'id' => 'swp',
            'q' => 'Systematic Withdrawal Plan (SWP)',
            'a' => 'A facility that allows an investor to withdraw a fixed amount of money from their mutual fund investment at regular intervals (monthly, quarterly, etc.). It is highly tax-efficient and ideal for generating regular retirement income.'
        ],
        [
            'id' => 'swr',
            'q' => 'Safe Withdrawal Rate (SWR)',
            'a' => 'The maximum percentage of your portfolio that can be withdrawn annually in retirement without a high risk of exhausting your funds prematurely. It aims to balance your current income needs with long-term capital preservation.'
        ],
        [
            'id' => 'four-percent-rule',
            'q' => 'The 4% Rule',
            'a' => 'A classic retirement guideline suggesting that if you withdraw 4% of your total corpus in the first year and adjust for inflation thereafter, your money has a high probability of lasting 30 years or more.'
        ]
    ]
];

ob_start();
?>

<!-- Glossary Hero -->
<header class="text-center mb-12 sm:mb-16">
    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-50 border border-indigo-100 mb-5">
        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
        </svg>
        <span class="text-sm font-bold text-indigo-700 tracking-wide uppercase italic">The Investor's Dictionary</span>
    </div>

    <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight pb-2 text-slate-900">
        Financial <span class="text-indigo-600">Encyclopedia</span>
    </h1>
    <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto leading-relaxed">
        Master the language of wealth building. Simple, plain-English definitions for every term used in our advanced calculators.
    </p>
</header>

<div class="max-w-4xl mx-auto pb-20">
    <div class="space-y-12">
        <?php foreach ($glossary_categories as $category_name => $terms): ?>
        <section aria-labelledby="cat-<?= md5($category_name) ?>">
            <div class="flex items-center gap-4 mb-6">
                <h2 id="cat-<?= md5($category_name) ?>" class="text-xl font-extrabold text-slate-800 tracking-tight">
                    <?= htmlspecialchars($category_name) ?>
                </h2>
                <div class="flex-grow h-px bg-gradient-to-r from-slate-200 to-transparent"></div>
            </div>

            <div class="grid gap-4">
                <?php foreach ($terms as $term): ?>
                <details class="group bg-white rounded-2xl border border-slate-200 shadow-sm transition-all hover:border-indigo-300 hover:shadow-md overflow-hidden" id="<?= $term['id'] ?>">
                    <summary class="flex items-center justify-between cursor-pointer px-6 py-5 list-none focus:outline-none">
                        <span class="text-lg font-bold text-slate-700 group-hover:text-indigo-600 transition-colors">
                            <?= htmlspecialchars($term['q']) ?>
                        </span>
                        <div class="p-1.5 rounded-full bg-slate-50 border border-slate-100 group-open:bg-indigo-50 group-open:border-indigo-100 transition-colors">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-indigo-500 group-open:rotate-180 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </summary>
                    <div class="px-6 pb-6 text-slate-600 leading-relaxed text-base border-t border-slate-50 pt-4">
                        <?= $term['a'] ?>
                    </div>
                </details>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </div>

    <!-- CTA Section -->
    <div class="mt-16 p-10 bg-gradient-to-br from-indigo-600 to-indigo-800 rounded-3xl shadow-xl relative overflow-hidden text-center text-white">
        <!-- Background Decoration -->
        <div class="absolute top-0 right-0 -mr-20 -mt-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-64 h-64 bg-emerald-400/10 rounded-full blur-3xl"></div>

        <div class="relative z-10">
            <h3 class="text-2xl font-bold mb-3">Ready to plan your future?</h3>
            <p class="text-indigo-100 mb-8 max-w-lg mx-auto">Put these terms into practice using our professional-grade SIP and SWP calculators.</p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="/" class="px-8 py-4 bg-white text-indigo-700 font-extrabold rounded-xl shadow-lg hover:bg-slate-50 hover:scale-105 transition-all">Launch Advanced Calculator</a>
                <a href="/resources.php" class="px-8 py-4 bg-indigo-500/20 backdrop-blur-md border border-indigo-400/30 text-white font-extrabold rounded-xl hover:bg-indigo-500/30 transition-all">Explore Guides</a>
            </div>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/includes/layout.php';
?>
