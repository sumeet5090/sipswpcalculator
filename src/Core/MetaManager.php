<?php

declare(strict_types=1);

namespace Core;

class MetaManager
{
    private array $metaData = [
        'home' => [
            'title' => 'SIP & SWP Calculator 2026: Plan Mutual Fund Returns in India',
            'meta_desc' => 'Free online Systematic Investment Plan (SIP) and SWP calculator. Plan your Indian mutual fund retirement wealth with step-up compounding and inflation adjustments.',
            'canonical' => 'https://sipswpcalculator.com/',
        ],
        'sip-calculator' => [
            'title' => 'SIP Calculator & Guide 2026: Mutual Fund Returns, Formula & Tax Rules',
            'meta_desc' => 'Master SIPs: learn the compounding formula, 2026 LTCG/STCG mutual fund tax rules in India, step-up strategy with worked examples. Free SIP calculator included.',
            'canonical' => 'https://sipswpcalculator.com/sip-calculator',
        ],
        'sip-step-up-calculator' => [
            'title' => 'SIP Step-Up Calculator India 2026: Power of Increasing Contributions',
            'meta_desc' => 'Calculate how a 5-10% annual step-up in your mutual fund SIP can double your wealth. Compare flat vs. step-up SIPs with our advanced 2026 tool.',
            'canonical' => 'https://sipswpcalculator.com/sip-step-up-calculator',
        ],
        'swp-tax-calculator' => [
            'title' => 'SWP Tax Calculator 2026: Post-Tax Indian Mutual Fund Income',
            'meta_desc' => 'Calculate post-tax monthly income from your Systematic Withdrawal Plan (SWP) using 2026 capital gains tax (LTCG/STCG) rules for Indian mutual funds.',
            'canonical' => 'https://sipswpcalculator.com/swp-tax-calculator',
        ],
        'compound-interest-calculator' => [
            'title' => 'Compound Interest Calculator India 2026: Plan Compound Returns',
            'meta_desc' => 'Free online compound interest calculator with monthly, quarterly, and annual frequencies. Visualize your exponential mutual fund savings growth over time.',
            'canonical' => 'https://sipswpcalculator.com/compound-interest-calculator',
        ],
        'dollar-cost-averaging-tool' => [
            'title' => 'DCA (SIP) Calculator India 2026: Dollar-Cost Averaging Tool',
            'meta_desc' => 'Calculate how periodic monthly investments (SIP/DCA) average your mutual fund cost basis. Compare systematic vs lump-sum returns with our free tool.',
            'canonical' => 'https://sipswpcalculator.com/dollar-cost-averaging-tool',
        ],
        'recurring-investment-calculator' => [
            'title' => 'Recurring Investment Calculator India 2026: Monthly Savings Planner',
            'meta_desc' => 'Calculate the future value of your recurring monthly mutual fund savings. Plan your wealth accumulation with compounding and annual step-up top-ups.',
            'canonical' => 'https://sipswpcalculator.com/recurring-investment-calculator',
        ],
        'retirement-drawdown-planner' => [
            'title' => 'Retirement SWP Drawdown Planner 2026: Mutual Fund Income Planner',
            'meta_desc' => 'Determine how long your Indian mutual fund retirement corpus will last. Model systematic withdrawals (SWP), inflation, and safe withdrawal rates.',
            'canonical' => 'https://sipswpcalculator.com/retirement-drawdown-planner',
        ]
    ];

    public function getMeta(string $pageKey): array
    {
        $meta = $this->metaData[$pageKey] ?? $this->metaData['home'];

        // Ensure some defaults if not set
        $meta['og_title'] = $meta['og_title'] ?? $meta['title'];
        $meta['og_desc'] = $meta['og_desc'] ?? $meta['meta_desc'];

        return $meta;
    }

    public function setDynamicMeta(string $title, string $desc, ?string $canonical = null): array
    {
        return [
            'title' => $title,
            'meta_desc' => $desc,
            'canonical' => $canonical ?? ('https://sipswpcalculator.com' . $_SERVER['REQUEST_URI']),
            'og_title' => $title,
            'og_desc' => $desc,
        ];
    }
}
