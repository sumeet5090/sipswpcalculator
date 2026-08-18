<?php

declare(strict_types=1);

use Controllers\PageController;
use Controllers\RenderGuideAction;

return [
    'calculators' => [
        '/sip-calculator'            => ['action' => [RenderGuideAction::class, '__invoke'], 'priority' => '0.9', 'changefreq' => 'monthly'],
        '/swp-calculator'            => ['action' => [RenderGuideAction::class, '__invoke'], 'priority' => '0.9', 'changefreq' => 'monthly'],
        '/sip-step-up-calculator'    => ['action' => [RenderGuideAction::class, '__invoke'], 'priority' => '0.8', 'changefreq' => 'monthly'],
        '/lumpsum-calculator'        => ['action' => [RenderGuideAction::class, '__invoke'], 'priority' => '0.8', 'changefreq' => 'monthly'],
        '/retirement-calculator'     => ['action' => [RenderGuideAction::class, '__invoke'], 'priority' => '0.8', 'changefreq' => 'monthly'],
        '/my-first-crore-calculator' => ['action' => [RenderGuideAction::class, '__invoke'], 'priority' => '0.8', 'changefreq' => 'monthly']
    ],
    'pages' => [
        '/about'    => ['action' => [PageController::class, 'about'], 'priority' => '0.5', 'changefreq' => 'yearly'],
        '/faq'      => ['action' => [PageController::class, 'faq'], 'priority' => '0.5', 'changefreq' => 'yearly'],
        '/glossary' => ['action' => [PageController::class, 'glossary'], 'priority' => '0.5', 'changefreq' => 'yearly'],
        '/privacy'  => ['action' => [PageController::class, 'privacy'], 'priority' => '0.3', 'changefreq' => 'yearly'],
        '/terms'    => ['action' => [PageController::class, 'terms'], 'priority' => '0.3', 'changefreq' => 'yearly']
    ]
];
