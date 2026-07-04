<?php

declare(strict_types=1);

return [
    'calculators' => [
        '/compound-interest-calculator' => [
            'action'   => 'RenderGuideAction',
            'category' => 'growth',
            'date'     => '2026-02-27'
        ],
        '/dollar-cost-averaging-tool' => [
            'action'   => 'RenderGuideAction',
            'category' => 'growth',
            'date'     => '2026-03-02'
        ],
        '/lumpsum-calculator' => [
            'action'   => 'RenderGuideAction',
            'category' => 'growth',
            'date'     => '2026-07-05'
        ],
        '/mutual-fund-calculator' => [
            'action'   => 'RenderGuideAction',
            'category' => 'growth',
            'date'     => '2026-07-05'
        ],
        '/recurring-investment-calculator' => [
            'action'   => 'RenderGuideAction',
            'category' => 'growth',
            'date'     => '2026-03-02'
        ],
        '/retirement-drawdown-planner' => [
            'action'   => 'RenderGuideAction',
            'category' => 'retirement',
            'date'     => '2026-03-02'
        ],
        '/sip-calculator' => [
            'action'   => 'RenderGuideAction',
            'category' => 'growth',
            'date'     => '2026-02-25'
        ],
        '/swp-calculator' => [
            'action'   => 'RenderGuideAction',
            'category' => 'retirement',
            'date'     => '2026-07-05'
        ],
        '/swp-tax-calculator' => [
            'action'   => 'RenderGuideAction',
            'category' => 'retirement',
            'date'     => '2026-03-02'
        ]
    ],
    'pages' => [
        '/about'    => 'PageController@about',
        '/faq'      => 'PageController@faq',
        '/glossary' => 'PageController@glossary',
        '/privacy'  => 'PageController@privacy',
        '/terms'    => 'PageController@terms'
    ],
    'blog_redirects' => [
        'sip-for-beginners' => 'growth',
        '20-year-wealth-blueprint-step-up-sip' => 'growth',
        'reach-1-million-dollar-in-18-years' => 'growth/reach-1-crore-rupees-via-sip',
        'reach-1-million-dollar-1-crore-rupees-in-18-years' => 'growth/reach-1-crore-rupees-via-sip',
        'reach-1-crore-rupees-via-sip' => 'growth',
        'inflation-impact-on-sip' => 'growth',
        'retirement-planning-4-percent-swp-rule' => 'retirement',
        'sip-vs-swp-wealth-creation-withdrawal-strategy' => 'retirement',
        'swp-retirement-planning' => 'retirement',
        'sip-vs-fd-vs-bonds' => 'comparison',
        'swp-vs-fixed-deposit' => 'comparison',
        'swp-vs-annuity-2026' => 'comparison',
        'mutual-fund-tax-2026' => 'comparison',
        'mf-returns-benchmarks' => 'comparison',
        // Consolidated / Redundant Posts (Redirected to Cornerstone Guides)
        'why-flat-sips-lose-money-stepup-sip-power' => 'growth/20-year-wealth-blueprint-step-up-sip',
        'mathematics-of-4-percent-rule-swp' => 'retirement/retirement-planning-4-percent-swp-rule',
        'sip-to-swp-transition-guide' => 'retirement/sip-vs-swp-wealth-creation-withdrawal-strategy'
    ],
    'stubs' => [
        '/sip-step-up-calculator' => '/sip-calculator',
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
        '/sip-vs-fd-vs-bonds' => '/resource/comparison/sip-vs-fd-vs-bonds',
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
