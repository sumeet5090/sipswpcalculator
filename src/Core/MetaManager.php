<?php

declare(strict_types=1);

namespace Core;

class MetaManager
{
    private array $metaData = [
        'home' => [
            'title' => 'SIP SWP Calculator with Step-Up India 2026: Mutual Fund Planner',
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

        'swp-calculator' => [
            'title' => 'SWP Calculator India 2026: Free Systematic Withdrawal Plan Tool',
            'meta_desc' => 'Free SWP calculator for Indian mutual funds. Calculate post-tax monthly retirement income with step-up withdrawals. Compare SWP vs FD. Plan your retirement corpus drawdown.',
            'keywords' => 'swp calculator, systematic withdrawal plan calculator, swp mutual fund, mutual fund withdrawal, retirement calculator',
            'canonical' => 'https://sipswpcalculator.com/swp-calculator',
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
