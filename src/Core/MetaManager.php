<?php
declare(strict_types=1);

namespace Core;

class MetaManager {
    private array $metaData = [
        'home' => [
            'title' => 'SIP & SWP Calculator 2026: Plan Your Retirement Wealth',
            'meta_desc' => 'Free Systematic Investment Plan (SIP) and SWP calculator. Plan your retirement with step-up compounding, inflation adjustments, and multi-currency support.',
            'canonical' => 'https://sipswpcalculator.com/',
        ],
        'sip-calculator' => [
            'title' => 'SIP Guide 2026: Formula, Tax Rules, Step-Up Strategy & Examples',
            'meta_desc' => 'Master SIPs: learn the compounding formula, 2026 LTCG/STCG tax rules (India, USA, UK), step-up strategy with worked examples. Free SIP calculator included.',
            'canonical' => 'https://sipswpcalculator.com/sip-calculator',
        ],
        'sip-step-up-calculator' => [
            'title' => 'SIP Step-Up Calculator 2026: The Power of Increasing Contributions',
            'meta_desc' => 'Calculate how a 5-10% annual step-up in your SIP can double your wealth. Compare flat vs. step-up SIPs with our advanced 2026 tool.',
            'canonical' => 'https://sipswpcalculator.com/sip-step-up-calculator',
        ],
        'swp-tax-calculator' => [
            'title' => 'SWP Tax Calculator 2026: Post-Tax Retirement Income',
            'meta_desc' => 'Calculate post-tax monthly income from your Systematic Withdrawal Plan (SWP) using capital gains tax rules for India, USA, and UK.',
            'canonical' => 'https://sipswpcalculator.com/swp-tax-calculator',
        ],
        'compound-interest-calculator' => [
            'title' => 'Compound Interest Calculator 2026: Plan Compound Returns',
            'meta_desc' => 'Free online compound interest calculator with monthly, quarterly, and annual frequencies. Visualize your exponential savings growth over time.',
            'canonical' => 'https://sipswpcalculator.com/compound-interest-calculator',
        ],
        'dollar-cost-averaging-tool' => [
            'title' => 'Dollar-Cost Averaging (DCA) Calculator 2026: Smooth Volatility',
            'meta_desc' => 'Calculate how regular recurring investments lower your average cost basis. Compare DCA vs. Lump Sum strategy with our free visual tool.',
            'canonical' => 'https://sipswpcalculator.com/dollar-cost-averaging-tool',
        ],
        'recurring-investment-calculator' => [
            'title' => 'Recurring Investment Calculator 2026: Monthly Savings Planner',
            'meta_desc' => 'Calculate the future value of your recurring monthly savings. Plan your wealth accumulation with compounding and annual step-up top-ups.',
            'canonical' => 'https://sipswpcalculator.com/recurring-investment-calculator',
        ],
        'retirement-drawdown-planner' => [
            'title' => 'Retirement Drawdown Planner 2026: Safe Withdrawal Rate',
            'meta_desc' => 'Determine how long your retirement savings will last. Model systematic withdrawals (SWP), inflation, and safe withdrawal rates (3-4% rule).',
            'canonical' => 'https://sipswpcalculator.com/retirement-drawdown-planner',
        ]
    ];

    public function getMeta(string $pageKey): array {
        $meta = $this->metaData[$pageKey] ?? $this->metaData['home'];
        
        // Ensure some defaults if not set
        $meta['og_title'] = $meta['og_title'] ?? $meta['title'];
        $meta['og_desc'] = $meta['og_desc'] ?? $meta['meta_desc'];
        
        return $meta;
    }

    public function setDynamicMeta(string $title, string $desc, ?string $canonical = null): array {
        return [
            'title' => $title,
            'meta_desc' => $desc,
            'canonical' => $canonical ?? ('https://sipswpcalculator.com' . $_SERVER['REQUEST_URI']),
            'og_title' => $title,
            'og_desc' => $desc,
        ];
    }
}
