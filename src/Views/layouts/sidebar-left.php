<?php
/**
 * sidebar-left.php
 * Contextual sidebar for related category posts.
 */
$active_page = $active_page ?? basename($_SERVER['PHP_SELF']);
if ($active_page === '' || $active_page === '/') $active_page = 'index.php';

// Define the categorized structure of the knowledge base.
$categories = [
    'SIP Strategy & Growth' => [
        'sip-for-beginners.php' => 'SIP for Beginners',
        'inflation-impact-on-sip.php' => 'Inflation Impact on SIP',
        'why-flat-sips-lose-money-stepup-sip-power.php' => 'Step-up SIP Power',
        'reach-1-million-dollar-1-crore-rupees-in-18-years.php' => 'Reach $1 Million in 18 Years',
        '20-year-wealth-blueprint-step-up-sip.php' => '20-Year Wealth Blueprint'
    ],
    'SWP & Retirement' => [
        'swp-retirement-planning.php' => 'SWP Retirement Planning',
        'sip-to-swp-transition-guide.php' => 'SIP to SWP Transition',
        'mathematics-of-4-percent-rule-swp.php' => 'Math of the 4% Rule',
        'retirement-planning-4-percent-swp-rule.php' => '4% SWP Rule Explained',
        'sip-vs-swp-wealth-creation-withdrawal-strategy.php' => 'SIP vs SWP Strategy'
    ],
    'Tax & Comparisons' => [
        'mutual-fund-tax-2026.php' => 'Mutual Fund Tax 2026',
        'sip-vs-fd-vs-ppf.php' => 'SIP vs FD vs PPF',
        'swp-vs-fixed-deposit.php' => 'SWP vs Fixed Deposit',
        'swp-vs-annuity-2026.php' => 'SWP vs Annuity',
        'mf-returns-benchmarks.php' => 'MF Return Benchmarks'
    ]
];

// Determine the active category
$active_category = null;
$active_links = [];

foreach ($categories as $cat_name => $links) {
    if (array_key_exists($active_page, $links)) {
        $active_category = $cat_name;
        $active_links = $links;
        break;
    }
}

// Fallback for non-blog pages (e.g., calculator, glossary, resources index)
if (!$active_category) {
    $active_category = 'Popular Guides';
    $active_links = [
        'sip-for-beginners.php' => 'SIP for Beginners',
        'swp-retirement-planning.php' => 'SWP Retirement Planning',
        'mutual-fund-tax-2026.php' => 'Mutual Fund Tax Rules 2026'
    ];
}
?>

<div class="flex flex-col gap-8">
    <nav aria-label="Related Posts Navigation">
        <h3 class="text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-3 px-3">
            <?= htmlspecialchars($active_category) ?>
        </h3>
        <ul class="space-y-1 text-sm font-medium">
            <?php foreach ($active_links as $file => $title): ?>
                <li>
                    <a href="/resource/<?= $file ?>" class="block px-3 py-1.5 <?= $active_page === $file ? 'text-emerald-700 bg-emerald-50 border-l-2 border-emerald-500' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 border-l-2 border-transparent' ?> transition-colors">
                        <?= htmlspecialchars($title) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <!-- Social Links Block remains as a secondary utility -->
    <div aria-label="Social Sharing">
        <h3 class="text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-3 px-3">Connect</h3>
        <div class="flex items-center gap-4 px-3">
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode('https://sipswpcalculator.com' . $_SERVER['REQUEST_URI']) ?>" class="text-slate-400 hover:text-blue-600 transition-colors" title="Share on LinkedIn" target="_blank" rel="noopener">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill-rule="evenodd" d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" clip-rule="evenodd" />
                </svg>
            </a>
            <a href="https://twitter.com/intent/tweet?url=<?= urlencode('https://sipswpcalculator.com' . $_SERVER['REQUEST_URI']) ?>" class="text-slate-400 hover:text-blue-400 transition-colors" title="Share on Twitter" target="_blank" rel="noopener">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                </svg>
            </a>
        </div>
    </div>
</div>
