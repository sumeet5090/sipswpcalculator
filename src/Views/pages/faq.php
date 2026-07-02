<?php

/**
 * faq.php
 * Premium Revamp for the FAQ page.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../functions.php';

$page_config = [
    'title' => 'FAQ | SIP & SWP Calculator - Financial Planning Questions',
    'meta_desc' => 'Expert answers to your most pressing questions about Systematic Investment Plans (SIP), Systematic Withdrawal Plans (SWP), retirement planning, and taxation.',
];

$active_page = 'faq.php';

$faq_categories = [
    ['id' => 'basics', 'label' => 'Basics', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    ['id' => 'strategies', 'label' => 'Strategies', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
    ['id' => 'tax', 'label' => 'Tax & Risk', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M12 16V7'],
    ['id' => 'selection', 'label' => 'Selection', 'icon' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z']
];

$faqs = [
    [
        'category' => 'strategies',
        'q' => 'Can I start an SWP immediately after my SIP ends?',
        'a' => 'Yes, absolutely. This is a common strategy for retirement planning. You accumulate a corpus using SIP during your working years and then switch to SWP to generate a monthly pension-like income post-retirement. Our calculator specifically models this seamless transition.'
    ],
    [
        'category' => 'basics',
        'q' => 'Is SWP better than a fixed deposit interest?',
        'a' => 'Generally, yes. SWP from equity or hybrid mutual funds has the potential to offer higher returns than fixed deposits over the long term. Additionally, SWP is more tax-efficient because you are only taxed on the capital gains portion of the withdrawal, whereas FD interest is fully taxable at your income tax slab rate.'
    ],
    [
        'category' => 'strategies',
        'q' => 'How does the "Step-up" feature work?',
        'a' => 'A "Step-up" SIP means you increase your monthly investment amount by a certain percentage every year (e.g., matching your salary hikes). This exponentially boosts your final corpus. Similarly, a "Step-up" SWP means you increase your withdrawal amount annually to maintain your lifestyle against inflation.'
    ],
    [
        'category' => 'strategies',
        'q' => 'What is a safe withdrawal rate for SWP?',
        'a' => 'Financial experts often recommend the "4% rule," suggesting you withdraw 4% of your total corpus in the first year and adjust for inflation thereafter. However, a safer approach for longer retirements (30+ years) is often 3% to 3.5%, especially in volatile markets.'
    ],
    [
        'category' => 'basics',
        'q' => 'Which is better: SIP or Lump Sum?',
        'a' => 'Lump sum is mathematically superior in a consistently rising market. However, SIP is significantly safer for most investors as it benefits from "Cost Averaging." It allows you to buy more units when prices are low, which protects you from the risk of timing the market incorrectly.'
    ],
    [
        'category' => 'tax',
        'q' => 'Can I lose money in SIP?',
        'a' => 'In the short term, yes. Since SIPs are market-linked, your portfolio value can fluctuate. However, the probability of negative returns decreases sharply the longer you stay invested. Historical data suggests that for periods longer than 7-10 years, the risk of loss in a diversified index fund is historically near zero.'
    ],
    [
        'category' => 'basics',
        'q' => 'What is the minimum amount to start a SIP?',
        'a' => 'Most mutual fund houses worldwide allow SIPs starting from as low as $5 to $10 per month. The key to wealth is not the size of the initial investment, but the consistency and duration of the compounding process.'
    ],
    [
        'category' => 'selection',
        'q' => 'How do I choose the right mutual fund for my SIP?',
        'a' => 'Focus on three key factors: (1) Asset Allocation - Choose based on your risk tolerance; (2) Performance Consistency - Look at 5-10 year track records, not just last year; (3) Cost - Prefer Low Expense Ratio funds (Direct Index Funds) to maximize your returns.'
    ],
    [
        'category' => 'tax',
        'q' => 'How are SWP withdrawals taxed worldwide?',
        'a' => 'SWP withdrawals are treated as partial redemptions of your investment. Only the "Capital Gain" portion of each withdrawal is taxable, while the principal component is tax-free. In many jurisdictions, Long-Term Capital Gains (LTCG) held for over a year are taxed at a much lower rate than regular income tax.'
    ],
    [
        'category' => 'selection',
        'q' => 'How long should I continue my SIP for best results?',
        'a' => 'Compounding works best in the "late stage." You will likely see more growth in years 15-20 than you did in years 1-15. Therefore, we recommend staying invested for at least 10-15 years to truly harness the power of compounding and market growth.'
    ]
];

ob_start();
?>
<style>
    .glow-blob {
        filter: blur(80px);
        opacity: 0.12;
        transition: opacity 0.5s ease;
    }
</style>
<?php
$page_config['additional_head'] = ob_get_clean();

ob_start();
?>

<!-- Decorative Background Glows -->
<div class="absolute top-10 left-1/4 w-96 h-96 bg-emerald-400 rounded-full glow-blob pointer-events-none"></div>
<div class="absolute top-80 right-1/4 w-80 h-80 bg-teal-400 rounded-full glow-blob pointer-events-none"></div>

<!-- FAQ Hero Section -->
<header class="text-center mb-14 md:mb-20 pt-6">
    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100/60 uppercase tracking-widest mb-4">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
        Knowledge Base
    </span>
    <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight pb-3">
        Frequently Asked <span class="bg-gradient-to-r from-emerald-600 to-teal-500 bg-clip-text text-transparent">Questions</span>
    </h1>
    <p class="mt-4 text-lg text-slate-500 max-w-2xl mx-auto leading-relaxed font-medium">
        Everything you need to know about SIP/SWP calculators, investment strategies, and retirement planning.
    </p>
</header>

<!-- Search Bar -->
<div class="relative max-w-xl mx-auto mb-8">
    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
    </div>
    <input id="faqSearch" type="text" placeholder="Search questions..."
        class="w-full pl-11 pr-4 py-3 border border-slate-200 rounded-2xl bg-white text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 shadow-sm transition-all text-sm font-medium">
</div>

<!-- Category Filter Chips -->
<div class="flex flex-wrap justify-center items-center gap-2.5 mb-12">
    <button data-category="all" class="category-chip active">All</button>
    <?php foreach ($faq_categories as $cat) : ?>
    <button data-category="<?= $cat['id']?>" class="category-chip"><?= $cat['label']?></button>
    <?php endforeach; ?>
</div>

<!-- Empty State -->
<div id="emptyState" class="hidden text-center py-20 animate-entry">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 border border-slate-100 text-slate-400 mb-4">
        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>
    <h3 class="text-xl font-bold text-slate-800">No questions found</h3>
    <p class="mt-2 text-slate-500 max-w-xs mx-auto text-sm leading-relaxed">We couldn't find any questions matching your search. Try another query.</p>
</div>

<!-- FAQ Accordion -->
<div id="faqAccordion" class="faq-list">
    <?php foreach ($faqs as $index => $faq) : ?>
    <div class="faq-item" data-category="<?= $faq['category']?>" data-question="<?= strtolower($faq['q'])?>"
        data-answer="<?= strtolower($faq['a'])?>">

        <button class="faq-trigger" onclick="toggleFaq(this)">
            <span class="faq-question">
                <?= htmlspecialchars($faq['q'])?>
            </span>
            <span class="faq-icon-wrap">
                <svg class="faq-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                </svg>
            </span>
        </button>

        <div class="faq-answer">
            <p>
                <?= $faq['a']?>
            </p>
        </div>
    </div>
        <?php
    endforeach; ?>
</div>
</div>

<style>
/* --- FAQ Accordion Styles --- */
.faq-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.faq-item {
    background-color: #ffffff;
    border: 1.5px solid #f1f5f9;
    border-radius: 24px;
    padding: 0.5rem 1.25rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.faq-item:hover {
    border-color: #e2e8f0;
    box-shadow: 0 12px 30px -15px rgba(0, 0, 0, 0.04);
    transform: translateY(-2px);
}

.faq-item.active {
    border-color: rgba(16, 185, 129, 0.2);
    box-shadow: 0 12px 30px -10px rgba(16, 185, 129, 0.08);
}

.faq-trigger {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.25rem;
    padding: 1.25rem 0.5rem;
    background: none;
    border: none;
    cursor: pointer;
    text-align: left;
    outline: none;
}

.faq-question {
    font-size: 1.05rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.5;
    transition: color 0.3s ease;
}

.faq-item:hover .faq-question,
.faq-item.active .faq-question {
    color: #059669;
}

.faq-icon-wrap {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #f1f5f9;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.faq-chevron {
    width: 14px;
    height: 14px;
    color: #64748b;
    transition: transform 0.3s ease, color 0.3s ease;
}

.faq-item.active .faq-icon-wrap {
    background: #10b981;
}

.faq-item.active .faq-chevron {
    transform: rotate(180deg);
    color: #ffffff;
}

.faq-answer {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease;
    opacity: 0;
}

.faq-item.active .faq-answer {
    max-height: 500px;
    opacity: 1;
}

.faq-answer p {
    padding: 0 0.5rem 1.25rem 0.5rem;
    font-size: 0.95rem;
    line-height: 1.8;
    color: #64748b;
    font-medium;
}

/* --- Category Chip Styles --- */
.category-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    appearance: none;
    -webkit-appearance: none;
    background-color: #ffffff;
    color: #64748b;
    border: 1.5px solid #e2e8f0;
    border-radius: 9999px;
    padding: 0.5rem 1.25rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    white-space: nowrap;
    outline: none;
}

.category-chip:hover {
    border-color: #10b981;
    color: #047857;
    background-color: #f0fdf4;
}

.category-chip.active {
    background-color: #10b981;
    color: #ffffff;
    border-color: #10b981;
    box-shadow: 0 10px 20px -10px rgba(16, 185, 129, 0.4);
}
</style>

<script>
    function toggleFaq(button) {
        const item = button.closest('.faq-item');
        // Close all others
        document.querySelectorAll('.faq-item.active').forEach(el => {
            if (el !== item) el.classList.remove('active');
        });
        item.classList.toggle('active');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('faqSearch');
        const faqItems = document.querySelectorAll('.faq-item');
        const categoryChips = document.querySelectorAll('.category-chip');
        const emptyState = document.getElementById('emptyState');
        let currentCategory = 'all';

        function filterFaqs() {
            const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
            let visibleCount = 0;

            faqItems.forEach(item => {
                const q = item.getAttribute('data-question') || '';
                const a = item.getAttribute('data-answer') || '';
                const cat = item.getAttribute('data-category');

                const matchesSearch = !query || q.includes(query) || a.includes(query);
                const matchesCategory = currentCategory === 'all' || cat === currentCategory;

                if (matchesSearch && matchesCategory) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                    item.classList.remove('active');
                }
            });

            if (emptyState) emptyState.classList.toggle('hidden', visibleCount > 0);
        }

        if (searchInput) searchInput.addEventListener('input', filterFaqs);

        categoryChips.forEach(chip => {
            chip.addEventListener('click', function () {
                categoryChips.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                currentCategory = this.getAttribute('data-category');
                filterFaqs();
            });
        });
    });
</script>

<!-- FAQ Schema (Auto-generated from $faqs) -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    <?php
    $schemaEntries = [];
    foreach ($faqs as $faq) {
        $schemaEntries[] = json_encode([
        "@type" => "Question",
        "name" => $faq['q'],
        "acceptedAnswer" => [
            "@type" => "Answer",
            "text" => strip_tags($faq['a'])
        ]
        ]);
    }
    echo implode(",\n    ", $schemaEntries);
    ?>
  ]
}
</script>

<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/../layouts/layout.php';
?>