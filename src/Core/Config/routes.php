<?php

declare(strict_types=1);

use Controllers\RenderAboutAction;
use Controllers\RenderFaqAction;
use Controllers\RenderGlossaryAction;
use Controllers\RenderGuideAction;
use Controllers\RenderPrivacyAction;
use Controllers\RenderTermsAction;

return [
    'calculators' => [
        '/sip-calculator'            => ['action' => [RenderGuideAction::class, '__invoke'], 'priority' => '0.9', 'changefreq' => 'monthly'],
        '/swp-calculator'            => ['action' => [RenderGuideAction::class, '__invoke'], 'priority' => '0.9', 'changefreq' => 'monthly'],
        '/sip-step-up-calculator'    => ['action' => [RenderGuideAction::class, '__invoke'], 'priority' => '0.8', 'changefreq' => 'monthly'],
        '/lumpsum-calculator'        => ['action' => [RenderGuideAction::class, '__invoke'], 'priority' => '0.8', 'changefreq' => 'monthly'],
        '/retirement-calculator'     => ['action' => [RenderGuideAction::class, '__invoke'], 'priority' => '0.8', 'changefreq' => 'monthly'],
        '/my-first-crore-calculator' => ['action' => [RenderGuideAction::class, '__invoke'], 'priority' => '0.8', 'changefreq' => 'monthly'],
        '/target-corpus-calculator'  => ['action' => [RenderGuideAction::class, '__invoke'], 'priority' => '0.8', 'changefreq' => 'monthly']
    ],
    'pages' => [
        '/about'    => ['action' => [RenderAboutAction::class, '__invoke'], 'priority' => '0.5', 'changefreq' => 'yearly'],
        '/faq'      => ['action' => [RenderFaqAction::class, '__invoke'], 'priority' => '0.5', 'changefreq' => 'yearly'],
        '/glossary' => ['action' => [RenderGlossaryAction::class, '__invoke'], 'priority' => '0.5', 'changefreq' => 'yearly'],
        '/privacy'  => ['action' => [RenderPrivacyAction::class, '__invoke'], 'priority' => '0.3', 'changefreq' => 'yearly', 'sitemap_exclude' => true],
        '/terms'    => ['action' => [RenderTermsAction::class, '__invoke'], 'priority' => '0.3', 'changefreq' => 'yearly', 'sitemap_exclude' => true]
    ]
];
