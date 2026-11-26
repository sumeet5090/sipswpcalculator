<?php

declare(strict_types=1);

use Controllers\RenderAboutAction;
use Controllers\RenderFaqAction;
use Controllers\RenderGlossaryAction;
use Controllers\RenderPrivacyAction;
use Controllers\RenderTermsAction;
use Core\Config\CalculatorRegistry;

return [
    'calculators' => CalculatorRegistry::getRouterCalculatorDefinitions(),
    'pages' => [
        '/about'    => ['action' => [RenderAboutAction::class, '__invoke'], 'priority' => '0.5', 'changefreq' => 'yearly'],
        '/faq'      => ['action' => [RenderFaqAction::class, '__invoke'], 'priority' => '0.5', 'changefreq' => 'yearly'],
        '/glossary' => ['action' => [RenderGlossaryAction::class, '__invoke'], 'priority' => '0.5', 'changefreq' => 'yearly'],
        '/privacy'  => ['action' => [RenderPrivacyAction::class, '__invoke'], 'priority' => '0.3', 'changefreq' => 'yearly', 'sitemap_exclude' => true],
        '/terms'    => ['action' => [RenderTermsAction::class, '__invoke'], 'priority' => '0.3', 'changefreq' => 'yearly', 'sitemap_exclude' => true],
    ],
];
