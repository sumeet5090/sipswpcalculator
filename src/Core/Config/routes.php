<?php

declare(strict_types=1);

use Controllers\PageController;
use Controllers\RenderGuideAction;

return [
    'calculators' => [
        '/sip-calculator'            => [RenderGuideAction::class, '__invoke'],
        '/swp-calculator'            => [RenderGuideAction::class, '__invoke'],
        '/sip-step-up-calculator'    => [RenderGuideAction::class, '__invoke'],
        '/lumpsum-calculator'        => [RenderGuideAction::class, '__invoke'],
        '/retirement-calculator'     => [RenderGuideAction::class, '__invoke'],
        '/my-first-crore-calculator' => [RenderGuideAction::class, '__invoke']
    ],
    'pages' => [
        '/about'    => [PageController::class, 'about'],
        '/faq'      => [PageController::class, 'faq'],
        '/glossary' => [PageController::class, 'glossary'],
        '/privacy'  => [PageController::class, 'privacy'],
        '/terms'    => [PageController::class, 'terms']
    ]
];
