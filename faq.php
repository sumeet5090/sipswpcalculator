<?php
/**
 * faq.php
 * Premium Revamp for the FAQ page.
 */
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

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

<!-- FAQ Hero Section -->
<header class="text-center mb-12 sm:mb-16">
    <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight pb-2">
        Frequently Asked <span class="text-emerald-600">Questions</span>
    </h1>
    <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto leading-relaxed">
        Everything you need to know about SIP/SWP calculators, investment strategies, and retirement planning.
    </p>
</header>

<div class="max-w-4xl mx-auto pb-32 px-4">
    <!-- Category Filter Chips -->
    <div class="flex flex-wrap justify-center items-center gap-3 mb-16 px-4">
        <button data-category="all"
            class="category-chip px-8 py-3 rounded-full bg-emerald-600 text-white font-black text-sm uppercase tracking-widest shadow-xl shadow-emerald-500/20 transition-all hover:scale-105">
            All Topics
        </button>
        <?php foreach ($faq_categories as $cat): ?>
        <button data-category="<?= $cat['id']?>"
            class="category-chip px-8 py-3 rounded-full bg-white border border-slate-200 text-slate-400 font-black text-sm uppercase tracking-widest transition-all hover:border-emerald-500 hover:text-emerald-600 hover:bg-emerald-50 shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $cat['icon']?>">
                    </path>
                </svg>
                <?= $cat['label']?>
            </div>
        </button>
        <?php
endforeach; ?>
    </div>

    <!-- FAQ Accordion Container -->
    <div id="faqAccordion" class="space-y-6">
        <?php foreach ($faqs as $index => $faq): ?>
        <div class="faq-item group bg-white rounded-[32px] border-2 border-slate-50 shadow-sm hover:shadow-2xl hover:border-emerald-500/10 transition-all duration-300 overflow-hidden"
            data-category="<?= $faq['category']?>" data-question="<?= strtolower($faq['q'])?>"
            data-answer="<?= strtolower($faq['a'])?>">

            <button
                class="faq-trigger w-full flex items-center justify-between cursor-pointer px-10 py-8 text-left transition-colors"
                onclick="toggleFaq(this)">
                <span class="text-xl sm:text-2xl font-black text-slate-800 group-hover:text-emerald-700 leading-tight">
                    <?= htmlspecialchars($faq['q'])?>
                </span>
                <div
                    class="ml-6 w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300 flex-shrink-0 faq-icon">
                    <svg class="w-6 h-6 transition-transform duration-500" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
            </button>

            <div class="faq-content overflow-hidden transition-all duration-500 max-h-0 opacity-0">
                <div class="px-10 pb-10">
                    <div class="h-[2px] w-12 bg-emerald-500 mb-8 rounded-full"></div>
                    <p class="text-xl text-slate-600 leading-relaxed font-medium">
                        <?= $faq['a']?>
                    </p>

                    <div class="mt-10 flex items-center gap-4">
                        <span
                            class="px-3 py-1 rounded-lg bg-emerald-50 text-[10px] font-black text-emerald-600 uppercase tracking-widest border border-emerald-100">
                            <?= strtoupper($faq['category'])?>
                        </span>
                        <div class="h-[1px] flex-grow bg-slate-50"></div>
                        <button class="text-slate-300 hover:text-emerald-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
endforeach; ?>
    </div>
</div>

<style>
    .faq-item.active {
        border-color: rgba(5, 150, 105, 0.2);
        box-shadow: 0 25px 50px -12px rgba(5, 150, 105, 0.15);
    }

    .faq-item.active .faq-content {
        max-height: 1000px;
        opacity: 1;
    }

    .faq-item.active .faq-icon {
        background-color: #059669;
        color: white;
        transform: rotate(45deg);
    }

    .faq-item.active .faq-trigger span {
        color: #047857;
    }

    .category-chip.active {
        background-color: #059669;
        color: white;
        border-color: #059669;
        box-shadow: 0 10px 30px -5px rgba(5, 150, 105, 0.3);
    }
</style>

<script>
    function toggleFaq(button) {
        const item = button.closest('.faq-item');
        const isActive = item.classList.contains('active');

        // Close all others
        document.querySelectorAll('.faq-item.active').forEach(activeItem => {
            if (activeItem !== item) activeItem.classList.remove('active');
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
            const query = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            faqItems.forEach(item => {
                const q = item.getAttribute('data-question');
                const a = item.getAttribute('data-answer');
                const cat = item.getAttribute('data-category');

                const matchesSearch = q.includes(query) || a.includes(query);
                const matchesCategory = currentCategory === 'all' || cat === currentCategory;

                if (matchesSearch && matchesCategory) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                    item.classList.remove('active');
                }
            });

            emptyState.classList.toggle('hidden', visibleCount > 0);
        }

        searchInput.addEventListener('input', filterFaqs);

        categoryChips.forEach(chip => {
            chip.addEventListener('click', function () {
                categoryChips.forEach(c => {
                    c.classList.remove('active', 'bg-emerald-600', 'text-white', 'shadow-xl', 'shadow-emerald-500/20');
                    c.classList.add('bg-white', 'text-slate-400', 'border-slate-200');
                });

                this.classList.add('active', 'bg-emerald-600', 'text-white', 'shadow-xl', 'shadow-emerald-500/20');
                this.classList.remove('text-slate-400', 'border-slate-200');

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
require_once __DIR__ . '/includes/layout.php';
?>