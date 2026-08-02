<?php

declare(strict_types=1);

namespace Core;

class MetaManager
{
    public function getMeta(string $pageKey): array
    {
        if ($pageKey === 'home') {
            return [
                'title' => 'SIP and SWP Calculator Together | Combined Mutual Fund Planner India 2026',
                'meta_desc' => 'Free SIP and SWP calculator combined in one tool. Plan your mutual fund journey — from step-up SIP wealth accumulation to SWP retirement withdrawals with charts and PDF reports.',
                'keywords' => 'sip and swp calculator together, sip swp combo calculator, sip swp combined calculator, sip to swp calculator, step up sip calculator, swp calculator india, mutual fund sip swp calculator, swp planner, investment planner, retirement planning, sip calculator india, mutual fund return calculator',
                'canonical' => 'https://sipswpcalculator.com/',
                'og_title' => 'SIP and SWP Calculator Together | Combined Mutual Fund Planner India 2026',
                'og_desc' => 'Free SIP and SWP calculator combined in one tool. Plan your mutual fund journey — from step-up SIP wealth accumulation to SWP retirement withdrawals with charts and PDF reports.',
            ];
        }

        return $this->setDynamicMeta('SIP SWP Calculator', '');
    }

    public function buildFromMetadata(array $metadata, string $fallbackSlug): array
    {
        $title = $metadata['title'] ?? ucfirst(str_replace('-', ' ', $fallbackSlug));
        $desc = $metadata['meta_desc'] ?? $metadata['subtitle'] ?? '';
        $canonical = $metadata['canonical'] ?? ('https://sipswpcalculator.com/' . $fallbackSlug);

        return [
            'title' => $title,
            'meta_desc' => $desc,
            'keywords' => $metadata['keywords'] ?? '',
            'canonical' => $canonical,
            'og_title' => $title,
            'og_desc' => $desc,
        ];
    }

    public function setDynamicMeta(string $title, string $desc, ?string $canonical = null): array
    {
        return [
            'title' => $title,
            'meta_desc' => $desc,
            'keywords' => '',
            'canonical' => $canonical ?? ('https://sipswpcalculator.com' . $_SERVER['REQUEST_URI']),
            'og_title' => $title,
            'og_desc' => $desc,
        ];
    }
}
