<?php

declare(strict_types=1);

namespace Core;

class MetaManager
{
    private array $metaData = [
        'home' => [
            'title' => 'SIP SWP Calculator India 2026: Free Online Mutual Fund Planner',
            'meta_desc' => 'Free SIP SWP calculator India. Plan mutual fund investments with step-up SIP, lumpsum, and SWP retirement income. Calculate returns, compare scenarios, and export PDF reports.',
            'keywords' => 'sip swp calculator, mutual fund calculator, step up sip, swp calculator india, lumpsum calculator',
            'canonical' => 'https://sipswpcalculator.com/',
        ],
        'sip-calculator' => [
            'title' => 'SIP Calculator India 2026: Free Mutual Fund Return Calculator & Guide',
            'meta_desc' => 'Free SIP calculator with step-up compounding for Indian mutual funds. Calculate returns using the FV annuity formula. Includes 2026 LTCG/STCG tax rules and worked examples.',
            'keywords' => 'sip calculator, sip return calculator, mutual fund sip, sip calculation formula, sip tax rules',
            'canonical' => 'https://sipswpcalculator.com/sip-calculator',
        ],
        'sip-step-up-calculator' => [
            'title' => 'SIP Step-Up Calculator India 2026: Power of Increasing Contributions',
            'meta_desc' => 'Calculate how a 5-10% annual step-up in your mutual fund SIP can double your wealth. Compare flat vs. step-up SIPs with our advanced 2026 tool.',
            'keywords' => 'step up sip calculator, sip top up, increasing sip, step up calculator india',
            'canonical' => 'https://sipswpcalculator.com/sip-step-up-calculator',
        ],
        'swp-calculator' => [
            'title' => 'SWP Calculator India 2026: Free Systematic Withdrawal Plan Tool',
            'meta_desc' => 'Free SWP calculator for Indian mutual funds. Calculate post-tax monthly retirement income with step-up withdrawals. Compare SWP vs FD. Plan your retirement corpus drawdown.',
            'keywords' => 'swp calculator, systematic withdrawal plan, swp calculator india, retirement withdrawal calculator',
            'canonical' => 'https://sipswpcalculator.com/swp-calculator',
        ],
        'swp-tax-calculator' => [
            'title' => 'SWP Tax Calculator India 2026: Post-Tax Mutual Fund Income Planner',
            'meta_desc' => 'Free SWP tax calculator. Calculate post-tax monthly income from your Systematic Withdrawal Plan using 2026 capital gains tax (LTCG 12.5%, STCG 20%) rules for Indian mutual funds.',
            'keywords' => 'swp tax calculator, mutual fund withdrawal tax, swp taxation, ltcg on swp',
            'canonical' => 'https://sipswpcalculator.com/swp-tax-calculator',
        ],
        'compound-interest-calculator' => [
            'title' => 'Compound Interest Calculator India 2026: Plan Compound Returns',
            'meta_desc' => 'Free online compound interest calculator with monthly, quarterly, and annual frequencies. Visualize your exponential mutual fund savings growth over time.',
            'keywords' => 'compound interest calculator, compounding calculator, mutual fund compound interest',
            'canonical' => 'https://sipswpcalculator.com/compound-interest-calculator',
        ],
        'dollar-cost-averaging-tool' => [
            'title' => 'DCA (SIP) Calculator India 2026: Dollar-Cost Averaging Tool',
            'meta_desc' => 'Calculate how periodic monthly investments (SIP/DCA) average your mutual fund cost basis. Compare systematic vs lump-sum returns with our free tool.',
            'keywords' => 'dollar cost averaging calculator, dca calculator, systematic investment plan',
            'canonical' => 'https://sipswpcalculator.com/dollar-cost-averaging-tool',
        ],
        'recurring-investment-calculator' => [
            'title' => 'Recurring Investment Calculator India 2026: Monthly Savings Planner',
            'meta_desc' => 'Calculate the future value of your recurring monthly mutual fund savings. Plan your wealth accumulation with compounding and annual step-up top-ups.',
            'keywords' => 'recurring investment calculator, monthly savings calculator, future value calculator',
            'canonical' => 'https://sipswpcalculator.com/recurring-investment-calculator',
        ],
        'retirement-drawdown-planner' => [
            'title' => 'Retirement SWP Drawdown Planner 2026: Mutual Fund Income Planner',
            'meta_desc' => 'Determine how long your Indian mutual fund retirement corpus will last. Model systematic withdrawals (SWP), inflation, and safe withdrawal rates.',
            'keywords' => 'retirement drawdown calculator, swp drawdown, retirement income planner, mutual fund retirement calculator',
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
