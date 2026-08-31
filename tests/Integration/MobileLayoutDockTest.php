<?php

declare(strict_types=1);

namespace Tests\Integration;

class MobileLayoutDockTest extends IntegrationTestCase
{
    private static int $port = 9097;

    public static function setUpBeforeClass(): void
    {
        self::startLocalServer(self::$port);
    }

    public static function tearDownAfterClass(): void
    {
        self::stopLocalServer();
    }

    private function fetchHtml(string $path): string
    {
        $url = sprintf('http://127.0.0.1:%d%s', self::$port, $path);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $html = curl_exec($ch);

        return is_string($html) ? $html : '';
    }

    public function testCalculatorRoutesContainMobileActionDockAndMiniHud(): void
    {
        $calculatorPaths = [
            '/',
            '/sip-calculator',
            '/swp-calculator',
            '/sip-step-up-calculator',
            '/lumpsum-calculator',
            '/retirement-calculator',
            '/my-first-crore-calculator',
            '/target-corpus-calculator'
        ];

        foreach ($calculatorPaths as $path) {
            $html = $this->fetchHtml($path);
            $this->assertNotEmpty($html, "Failed to load calculator route: {$path}");
            $this->assertStringContainsString(
                'id="mobile-action-dock"',
                $html,
                "Calculator route '{$path}' must include #mobile-action-dock"
            );
            $this->assertStringContainsString(
                'id="mobile-sticky-mini-hud"',
                $html,
                "Calculator route '{$path}' must include #mobile-sticky-mini-hud"
            );
        }
    }

    public function testNonCalculatorRoutesDoNotContainMobileActionDockOrMiniHud(): void
    {
        $contentPaths = [
            '/glossary',
            '/faq',
            '/about',
            '/resources',
            '/resource/growth/sip-for-beginners',
            '/privacy',
            '/terms'
        ];

        foreach ($contentPaths as $path) {
            $html = $this->fetchHtml($path);
            $this->assertNotEmpty($html, "Failed to load content route: {$path}");
            $this->assertStringNotContainsString(
                'id="mobile-action-dock"',
                $html,
                "Content route '{$path}' must NOT render #mobile-action-dock (occlusion hazard)"
            );
            $this->assertStringNotContainsString(
                'id="mobile-sticky-mini-hud"',
                $html,
                "Content route '{$path}' must NOT render #mobile-sticky-mini-hud"
            );
            // Must still have universal scroll to top FAB
            $this->assertStringContainsString(
                'id="mobile-scroll-top-fab"',
                $html,
                "Content route '{$path}' missing universal #mobile-scroll-top-fab"
            );
        }
    }

    public function testGlossaryNavigationHasHorizontalTrackOnMobile(): void
    {
        $html = $this->fetchHtml('/glossary');
        $this->assertStringContainsString('no-scrollbar', $html, "Glossary A-Z nav must use no-scrollbar utility.");
        $this->assertStringContainsString('touch-pan-x', $html, "Glossary A-Z nav must support touch gestures.");
        $this->assertStringContainsString('overflow-x-auto', $html, "Glossary A-Z nav must be horizontally scrollable.");
    }

    public function testFaqCategoryChipsHaveHorizontalScrollCarousel(): void
    {
        $html = $this->fetchHtml('/faq');
        $this->assertStringContainsString('no-scrollbar', $html, "FAQ categories must use no-scrollbar utility.");
        $this->assertStringContainsString('overflow-x-auto', $html, "FAQ categories must be horizontally scrollable.");
        $this->assertStringContainsString('flex-shrink-0', $html, "FAQ chips must not wrap internal text.");
    }

    public function testArticleMetadataHasResponsiveHeader(): void
    {
        $html = $this->fetchHtml('/resource/growth/sip-for-beginners');
        $this->assertStringContainsString('Published:', $html, "Article must display published date.");
        $this->assertStringNotContainsString('id="mobile-action-dock"', $html, "Article must not display calculator dock.");
    }
}
