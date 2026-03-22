<?php
/**
 * faq.php
 * Frequently Asked Questions page.
 */
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

$page_config = [
    'title' => 'FAQ | SIP & SWP Calculator - Financial Planning Questions',
    'meta_desc' => 'Answers to common questions about Systematic Investment Plans (SIP), Systematic Withdrawal Plans (SWP), retirement planning, taxation, and wealth building.',
];

$active_page = 'faq.php';

ob_start();
?>

<!-- FAQ Hero Section -->
<header class="text-center mb-12 sm:mb-16">
    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-50 border border-indigo-100 mb-5">
        <span class="text-sm font-semibold text-indigo-700 tracking-wide uppercase italic">Common Queries</span>
    </div>

    <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight pb-2 text-slate-900">
        Frequently Asked <span class="text-indigo-600">Questions</span>
    </h1>
    <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto leading-relaxed">
        Everything you need to know about SIP/SWP calculators, investment strategies, and retirement planning.
    </p>
</header>

<div class="max-w-4xl mx-auto pb-16">
    <div class="space-y-4">
        <details class="group bg-white rounded-xl border border-slate-200 shadow-sm transition-all hover:border-indigo-200">
            <summary class="flex items-center justify-between cursor-pointer px-6 py-5 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                Can I start an SWP immediately after my SIP ends?
                <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </summary>
            <div class="px-6 pb-6 text-slate-600 leading-relaxed">
                Yes, absolutely. This is a common strategy for retirement planning. You accumulate a corpus using SIP during your working years and then switch to SWP to generate a monthly pension-like income post-retirement. Our calculator specifically models this seamless transition.
            </div>
        </details>

        <details class="group bg-white rounded-xl border border-slate-200 shadow-sm transition-all hover:border-indigo-200">
            <summary class="flex items-center justify-between cursor-pointer px-6 py-5 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                Is SWP better than a fixed deposit interest?
                <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </summary>
            <div class="px-6 pb-6 text-slate-600 leading-relaxed">
                Generally, yes. SWP from equity or hybrid mutual funds has the potential to offer higher returns than fixed deposits over the long term. Additionally, SWP is more tax-efficient because you are only taxed on the capital gains portion of the withdrawal, whereas FD interest is fully taxable at your slab rate.
            </div>
        </details>

        <details class="group bg-white rounded-xl border border-slate-200 shadow-sm transition-all hover:border-indigo-200">
            <summary class="flex items-center justify-between cursor-pointer px-6 py-5 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                How does the "Step-up" feature work?
                <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </summary>
            <div class="px-6 pb-6 text-slate-600 leading-relaxed">
                A "Step-up" SIP means you increase your monthly investment amount by a certain percentage every year (e.g., as your salary increases). This significantly boosts your final corpus. Similarly, a "Step-up" SWP means you increase your withdrawal amount annually to combat inflation.
            </div>
        </details>

        <details class="group bg-white rounded-xl border border-slate-200 shadow-sm transition-all hover:border-indigo-200">
            <summary class="flex items-center justify-between cursor-pointer px-6 py-5 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                What is a safe withdrawal rate for SWP?
                <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </summary>
            <div class="px-6 pb-6 text-slate-600 leading-relaxed">
                Financial experts often recommend the "4% rule," suggesting you withdraw 4% of your corpus annually. However, this depends on market conditions, your expected lifespan, and the sequence of investment returns you experience. A conservative approach is to stress-test your withdrawal rate against historical market downturns.
            </div>
        </details>

        <details class="group bg-white rounded-xl border border-slate-200 shadow-sm transition-all hover:border-indigo-200">
            <summary class="flex items-center justify-between cursor-pointer px-6 py-5 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                Which is better: SIP or Lump Sum?
                <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </summary>
            <div class="px-6 pb-6 text-slate-600 leading-relaxed">
                In a rising market, Lump Sum often wins mathematically. However, SIP is psychologically easier and safer for volatile markets as it benefits from <strong>Dollar Cost Averaging</strong>, reducing the risk of investing a large amount at a market peak.
            </div>
        </details>

        <details class="group bg-white rounded-xl border border-slate-200 shadow-sm transition-all hover:border-indigo-200">
            <summary class="flex items-center justify-between cursor-pointer px-6 py-5 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                Can I lose money in SIP?
                <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </summary>
            <div class="px-6 pb-6 text-slate-600 leading-relaxed">
                Yes, in the short term. Since SIPs in equity mutual funds are market-linked, the value can fluctuate. However, historical data shows that over the long term (7-10+ years), the probability of negative returns in a diversified fund is negligible.
            </div>
        </details>

        <details class="group bg-white rounded-xl border border-slate-200 shadow-sm transition-all hover:border-indigo-200">
            <summary class="flex items-center justify-between cursor-pointer px-6 py-5 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                What is the minimum amount to start a SIP worldwide?
                <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </summary>
            <div class="px-6 pb-6 text-slate-600 leading-relaxed">
                Most mutual fund houses worldwide allow SIPs starting from as low as <strong><span class="currency-text">$</span>5/month</strong>. Some AMCs like SBI MF and HDFC MF offer micro-SIPs at <span class="currency-text">$</span>1/month. The key is to start early — even <span class="currency-text">$</span>5/month over 20 years at 12% can grow to <span class="dynamic-amount" data-amount="500000">+</span>.
            </div>
        </details>

        <details class="group bg-white rounded-xl border border-slate-200 shadow-sm transition-all hover:border-indigo-200">
            <summary class="flex items-center justify-between cursor-pointer px-6 py-5 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                How do I choose the right mutual fund for my SIP?
                <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </summary>
            <div class="px-6 pb-6 text-slate-600 leading-relaxed">
                Consider: (1) <strong>Risk profile</strong> — large-cap for stability, small-cap for aggressive growth; (2) <strong>Expense ratio</strong> — lower is better, prefer direct plans; (3) <strong>Track record</strong> — check 5-7 year consistency, not just 1-year returns; (4) <strong>Fund manager experience</strong>. Use AMFI's mutual fund comparison tools for data.
            </div>
        </details>

        <details class="group bg-white rounded-xl border border-slate-200 shadow-sm transition-all hover:border-indigo-200">
            <summary class="flex items-center justify-between cursor-pointer px-6 py-5 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                How are SWP withdrawals taxed worldwide (2026)?
                <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </summary>
            <div class="px-6 pb-6 text-slate-600 leading-relaxed">
                SWP withdrawals are treated as partial redemptions. For <strong>equity funds</strong>: STCG (held < 1 year) taxed at 20%, LTCG taxed at 12.5% on gains above <span class="currency-text">$</span>1,500/year. For <strong>debt funds</strong> (purchased after Apr 2023): taxed at your income slab rate. Only the <em>capital gains portion</em> of each withdrawal is taxable — the principal component is tax-free.
            </div>
        </details>

        <details class="group bg-white rounded-xl border border-slate-200 shadow-sm transition-all hover:border-indigo-200">
            <summary class="flex items-center justify-between cursor-pointer px-6 py-5 font-bold text-slate-800 hover:text-indigo-600 transition-colors">
                How long should I continue my SIP for best results?
                <svg class="w-5 h-5 text-slate-400 group-open:rotate-180 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </summary>
            <div class="px-6 pb-6 text-slate-600 leading-relaxed">
                For equity mutual funds, <strong>7-10 years minimum</strong> is recommended to ride out market cycles and benefit from compounding. Historical data shows that Nifty 50 SIPs held for 10+ years have never delivered negative returns. For retirement goals, 20-30 year SIPs yield the best compounding effect.
            </div>
        </details>
    </div>

    <div class="mt-12 text-center p-8 bg-slate-50 border border-slate-200 rounded-2xl">
        <h3 class="text-xl font-bold text-slate-900 mb-3">Still have questions?</h3>
        <p class="text-slate-600 mb-6">Explore our in-depth guides in the Knowledge Hub or try the advanced calculator.</p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="/resources.php" class="px-6 py-3 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition-colors">View Resource Center</a>
            <a href="/" class="px-6 py-3 bg-white border border-slate-200 text-slate-700 font-bold rounded-lg hover:border-indigo-300 transition-colors">Try Calculator</a>
        </div>
    </div>
</div>

<!-- FAQ Schema -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Can I start an SWP immediately after my SIP ends?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, absolutely. This is a common strategy for retirement planning. You accumulate a corpus using SIP during your working years and then switch to SWP to generate a monthly pension-like income post-retirement. Our calculator specifically models this seamless transition."
      }
    },
    {
      "@type": "Question",
      "name": "Is SWP better than a fixed deposit interest?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Generally, yes. SWP from equity or hybrid mutual funds has the potential to offer higher returns than fixed deposits over the long term. Additionally, SWP is more tax-efficient because you are only taxed on the capital gains portion of the withdrawal, whereas FD interest is fully taxable at your slab rate."
      }
    },
    {
      "@type": "Question",
      "name": "How does the 'Step-up' feature work?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "A 'Step-up' SIP means you increase your monthly investment amount by a certain percentage every year (e.g., as your salary increases). This significantly boosts your final corpus. Similarly, a 'Step-up' SWP means you increase your withdrawal amount annually to combat inflation."
      }
    }
  ]
}
</script>

<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/includes/layout.php';
?>
