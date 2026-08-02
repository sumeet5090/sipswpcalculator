<?php

declare(strict_types=1);

return [
    'calculators' => [
        '/sip-calculator' => ['action' => 'RenderGuideAction'],
        '/swp-calculator' => ['action' => 'RenderGuideAction'],
        '/sip-step-up-calculator' => ['action' => 'RenderGuideAction'],
        '/lumpsum-calculator' => ['action' => 'RenderGuideAction'],
        '/retirement-calculator' => ['action' => 'RenderGuideAction'],
        '/my-first-crore-calculator' => ['action' => 'RenderGuideAction']
    ],
    'pages' => [
        '/about'    => 'PageController@about',
        '/faq'      => 'PageController@faq',
        '/glossary' => 'PageController@glossary',
        '/privacy'  => 'PageController@privacy',
        '/terms'    => 'PageController@terms'
    ],
    'blog_redirects' => [
        'sip-for-beginners' => 'growth/sip-for-beginners',
        '20-year-wealth-blueprint-step-up-sip' => 'growth/20-year-wealth-blueprint-step-up-sip',
        'reach-1-million-dollar-in-18-years' => 'growth/reach-1-crore-rupees-via-sip',
        'reach-1-million-dollar-1-crore-rupees-in-18-years' => 'growth/reach-1-crore-rupees-via-sip',
        'reach-1-crore-rupees-via-sip' => 'growth/reach-1-crore-rupees-via-sip',
        'inflation-impact-on-sip' => 'growth/inflation-impact-on-sip',
        'retirement-planning-4-percent-swp-rule' => 'retirement/retirement-planning-4-percent-swp-rule',
        'sip-vs-swp-wealth-creation-withdrawal-strategy' => 'retirement/sip-vs-swp-wealth-creation-withdrawal-strategy',
        'swp-retirement-planning' => 'retirement/swp-retirement-planning',
        'sip-vs-fd-vs-bonds' => 'comparison/sip-vs-fd-vs-ppf',
        'swp-vs-fixed-deposit' => 'comparison/swp-vs-fixed-deposit',
        'swp-vs-annuity-2026' => 'comparison/swp-vs-annuity-2026',
        'mutual-fund-tax-2026' => 'comparison/mutual-fund-tax-2026',
        'mf-returns-benchmarks' => 'comparison/mf-returns-benchmarks',
        // Consolidated / Redundant Posts (Redirected to Cornerstone Guides)
        'why-flat-sips-lose-money-stepup-sip-power' => 'growth/20-year-wealth-blueprint-step-up-sip',
        'mathematics-of-4-percent-rule-swp' => 'retirement/retirement-planning-4-percent-swp-rule',
        'sip-to-swp-transition-guide' => 'retirement/sip-vs-swp-wealth-creation-withdrawal-strategy'
    ],
    'stubs' => [
        '/compound-interest-calculator'    => '/sip-calculator',
        '/dollar-cost-averaging-tool'      => '/sip-calculator',
        '/mutual-fund-calculator'          => '/sip-calculator',
        '/retirement-drawdown-planner'     => '/swp-calculator',
        '/swp-tax-calculator'              => '/swp-calculator',
        '/recurring-investment-calculator' => '/sip-calculator',
        '/20-year-wealth-blueprint-step-up-sip' => '/resource/growth/20-year-wealth-blueprint-step-up-sip',
        '/inflation-impact-on-sip' => '/resource/growth/inflation-impact-on-sip',
        '/mathematics-of-4-percent-rule-swp' => '/resource/retirement/retirement-planning-4-percent-swp-rule',
        '/mf-returns-benchmarks' => '/resource/comparison/mf-returns-benchmarks',
        '/mutual-fund-tax-2026' => '/resource/comparison/mutual-fund-tax-2026',
        '/reach-1-million-dollar-in-18-years' => '/resource/growth/reach-1-crore-rupees-via-sip',
        '/reach-1-million-dollar-1-crore-rupees-in-18-years' => '/resource/growth/reach-1-crore-rupees-via-sip',
        '/reach-1-crore-rupees-via-sip' => '/resource/growth/reach-1-crore-rupees-via-sip',
        '/retirement-planning-4-percent-swp-rule' => '/resource/retirement/retirement-planning-4-percent-swp-rule',
        '/sip-for-beginners' => '/resource/growth/sip-for-beginners',
        '/sip-to-swp-transition-guide' => '/resource/retirement/sip-vs-swp-wealth-creation-withdrawal-strategy',
        '/sip-vs-fd-vs-bonds' => '/resource/comparison/sip-vs-fd-vs-ppf',
        '/sip-vs-swp-wealth-creation-withdrawal-strategy' => '/resource/retirement/sip-vs-swp-wealth-creation-withdrawal-strategy',
        '/swp-retirement-planning' => '/resource/retirement/swp-retirement-planning',
        '/swp-vs-annuity-2026' => '/resource/comparison/swp-vs-annuity-2026',
        '/swp-vs-fixed-deposit' => '/resource/comparison/swp-vs-fixed-deposit',
        '/why-flat-sips-lose-money-stepup-sip-power' => '/resource/growth/20-year-wealth-blueprint-step-up-sip',
        '/earning-30k-at-25-investment-blueprint' => '/resource/growth/earning-30k-at-25-investment-blueprint',
        '/earning-moderate-income-in-20s-investment-blueprint' => '/resource/growth/earning-30k-at-25-investment-blueprint',
        '/resource/growth/earning-moderate-income-in-20s-investment-blueprint' => '/resource/growth/earning-30k-at-25-investment-blueprint',

        // Category Prefixed Legacy Redirects (Fix 404s)
        '/resource/growth/reach-1-million-dollar-in-18-years' => '/resource/growth/reach-1-crore-rupees-via-sip',
        '/resource/growth/reach-1-million-dollar-1-crore-rupees-in-18-years' => '/resource/growth/reach-1-crore-rupees-via-sip',
        '/resource/growth/why-flat-sips-lose-money-stepup-sip-power' => '/resource/growth/20-year-wealth-blueprint-step-up-sip',
        '/resource/retirement/mathematics-of-4-percent-rule-swp' => '/resource/retirement/retirement-planning-4-percent-swp-rule',
        '/resource/retirement/sip-to-swp-transition-guide' => '/resource/retirement/sip-vs-swp-wealth-creation-withdrawal-strategy'
    ]
];
