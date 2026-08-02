<?php

declare(strict_types=1);

namespace Core;

class MetaManager
{
    private array $metaData = [
        'home' => [
            'title' => 'SIP and SWP Calculator Together | Combined Mutual Fund Planner India 2026',
            'meta_desc' => 'Free SIP and SWP calculator combined in one tool. Plan your mutual fund journey — from step-up SIP wealth accumulation to SWP retirement withdrawals with charts and PDF reports.',
            'keywords' => 'sip and swp calculator together, sip swp combo calculator, sip swp combined calculator, sip to swp calculator, step up sip calculator, swp calculator india, mutual fund sip swp calculator, swp planner, investment planner, retirement planning, sip calculator india, mutual fund return calculator',
            'canonical' => 'https://sipswpcalculator.com/',
        ],
        'sip-calculator' => [
            'title' => 'SIP Calculator India 2026: Step-Up Mutual Fund Return Calculator & Guide',
            'meta_desc' => 'Free SIP calculator with step-up (top-up) compounding for Indian mutual funds. Calculate SIP returns, view interactive charts, and export PDF reports. Includes 2026 LTCG/STCG tax rules and worked examples.',
            'keywords' => 'sip calculator, sip return calculator, mutual fund sip, sip calculation formula, sip tax rules',
            'canonical' => 'https://sipswpcalculator.com/sip-calculator',
        ],

        'swp-calculator' => [
            'title' => 'SWP Calculator India 2026: Free Systematic Withdrawal Plan Calculator',
            'meta_desc' => 'Free SWP calculator for Indian mutual funds. Calculate post-tax monthly retirement income with step-up withdrawals. Plan your retirement corpus drawdown with interactive charts and yearly breakdown tables.',
            'keywords' => 'swp calculator, systematic withdrawal plan calculator, swp mutual fund, mutual fund withdrawal, retirement calculator',
            'canonical' => 'https://sipswpcalculator.com/swp-calculator',
        ],

        'lumpsum-calculator' => [
            'title' => 'Lumpsum Calculator India 2026: Mutual Fund Returns Calculator',
            'meta_desc' => 'Free lumpsum calculator for Indian mutual funds. Calculate returns on your one-time investments with inflation-adjusted compounding and interactive growth charts.',
            'keywords' => 'lumpsum calculator, one time investment calculator, mutual fund lumpsum, fd vs mutual fund lumpsum, lumpsum return calculator',
            'canonical' => 'https://sipswpcalculator.com/lumpsum-calculator',
        ],

        'sip-step-up-calculator' => [
            'title' => 'Step-Up SIP Calculator India 2026: Annual Increase SIP Planner',
            'meta_desc' => 'Free Step-up SIP calculator. Calculate how increasing your SIP amount annually by 5%, 10%, or 20% impacts your long-term wealth creation. Beat inflation easily.',
            'keywords' => 'step up sip calculator, top up sip calculator, increasing sip calculator, stepup sip, mutual fund step up',
            'canonical' => 'https://sipswpcalculator.com/sip-step-up-calculator',
        ],

        'retirement-calculator' => [
            'title' => 'Retirement Calculator India 2026: Complete Pension & Drawdown Planner',
            'meta_desc' => 'Free retirement calculator for India. Plan your accumulation phase (SIP) and drawdown phase (SWP). See exactly how long your money will last using the 4% rule.',
            'keywords' => 'retirement calculator india, pension calculator, swp retirement calculator, 4 percent rule calculator, retirement corpus calculator',
            'canonical' => 'https://sipswpcalculator.com/retirement-calculator',
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
