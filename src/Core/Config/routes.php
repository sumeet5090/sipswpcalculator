<?php

declare(strict_types=1);

return [
    'calculators' => [
        '/sip-calculator' => ['action' => 'RenderGuideAction'],
        '/swp-calculator' => ['action' => 'RenderGuideAction'],
        '/sip-step-up-calculator' => ['action' => 'RenderGuideAction'],
        '/lumpsum-calculator' => ['action' => 'RenderGuideAction'],
        '/retirement-calculator' => ['action' => 'RenderGuideAction'],
        '/my-first-crore-calculator' => ['action' => 'RenderGuideAction']
    ],
    'pages' => [
        '/about'    => 'PageController@about',
        '/faq'      => 'PageController@faq',
        '/glossary' => 'PageController@glossary',
        '/privacy'  => 'PageController@privacy',
        '/terms'    => 'PageController@terms'
    ]
];
