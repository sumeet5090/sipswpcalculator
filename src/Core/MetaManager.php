<?php
declare(strict_types=1);

namespace Core;

class MetaManager {
    private array $metaData = [
        'home' => [
            'title' => 'SIP & SWP Calculator 2026: Compounding & Retirement Planner',
            'meta_desc' => 'Free Systematic Investment Plan (SIP) and Systematic Withdrawal Plan (SWP) calculator with step-up compounding, inflation adjustments, and tax rules for 2026.',
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
            'title' => 'SWP Tax Calculator India 2026: Post-Tax Retirement Income',
            'meta_desc' => 'Calculate post-tax monthly income from your Systematic Withdrawal Plan (SWP) using the latest 2026 India capital gains tax rules (12.5% LTCG).',
            'canonical' => 'https://sipswpcalculator.com/swp-tax-calculator',
        ],
        // Add more pages as needed
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
