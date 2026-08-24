<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Config\ThemeConstants;
use PHPUnit\Framework\TestCase;

/**
 * Validates WCAG 2.1 AA and AAA color contrast ratios for all financial theme tokens on light surfaces.
 */
final class AccessibilityColorContrastTest extends TestCase
{
    private function hexToRgb(string $hex): array
    {
        $clean = ltrim($hex, '#');
        if (strlen($clean) === 3) {
            $clean = $clean[0] . $clean[0] . $clean[1] . $clean[1] . $clean[2] . $clean[2];
        }
        $int = (int) hexdec($clean);
        return [
            'r' => ($int >> 16) & 255,
            'g' => ($int >> 8) & 255,
            'b' => $int & 255
        ];
    }

    private function getLuminance(string $hex): float
    {
        $rgb = $this->hexToRgb($hex);
        $transform = static function (int $channel): float {
            $c = $channel / 255.0;
            return $c <= 0.04045 ? $c / 12.92 : (float) pow(($c + 0.055) / 1.055, 2.4);
        };

        $r = $transform($rgb['r']);
        $g = $transform($rgb['g']);
        $b = $transform($rgb['b']);

        return (0.2126 * $r) + (0.7152 * $g) + (0.0722 * $b);
    }

    private function getContrastRatio(string $fgHex, string $bgHex): float
    {
        $l1 = $this->getLuminance($fgHex);
        $l2 = $this->getLuminance($bgHex);

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    public function testGrowthColorMeetsWcagAaOnWhiteAndSlate50(): void
    {
        $growth = ThemeConstants::COLOR_FINANCIAL_GROWTH; // #047857

        $ratioWhite = $this->getContrastRatio($growth, '#ffffff');
        $ratioSlate = $this->getContrastRatio($growth, '#f8fafc');

        $this->assertGreaterThanOrEqual(4.5, $ratioWhite, "Growth color {$growth} must meet WCAG 2.1 AA (>= 4.5:1) on white");
        $this->assertGreaterThanOrEqual(4.5, $ratioSlate, "Growth color {$growth} must meet WCAG 2.1 AA (>= 4.5:1) on slate-50");
    }

    public function testWithdrawalColorMeetsWcagAaOnWhiteAndSlate50(): void
    {
        $withdrawal = ThemeConstants::COLOR_FINANCIAL_WITHDRAWAL; // #be123c

        $ratioWhite = $this->getContrastRatio($withdrawal, '#ffffff');
        $ratioSlate = $this->getContrastRatio($withdrawal, '#f8fafc');

        $this->assertGreaterThanOrEqual(4.5, $ratioWhite, "Withdrawal color {$withdrawal} must meet WCAG 2.1 AA (>= 4.5:1) on white");
        $this->assertGreaterThanOrEqual(4.5, $ratioSlate, "Withdrawal color {$withdrawal} must meet WCAG 2.1 AA (>= 4.5:1) on slate-50");
    }

    public function testPrincipalAndTaxColorsMeetWcagAaOnWhite(): void
    {
        $principal = ThemeConstants::COLOR_FINANCIAL_PRINCIPAL; // #334155
        $tax = ThemeConstants::COLOR_FINANCIAL_TAX; // #6d28d9

        $this->assertGreaterThanOrEqual(4.5, $this->getContrastRatio($principal, '#ffffff'));
        $this->assertGreaterThanOrEqual(4.5, $this->getContrastRatio($tax, '#ffffff'));
    }

    public function testBrandDarkAndTextPrimaryMeetWcagAaaOnWhite(): void
    {
        $brandDark = ThemeConstants::COLOR_BRAND_800; // #065f46
        $textPrimary = ThemeConstants::COLOR_TEXT_PRIMARY; // #0f172a

        $this->assertGreaterThanOrEqual(7.0, $this->getContrastRatio($brandDark, '#ffffff'), "Brand dark must meet WCAG AAA (>= 7:1)");
        $this->assertGreaterThanOrEqual(7.0, $this->getContrastRatio($textPrimary, '#ffffff'), "Text primary must meet WCAG AAA (>= 7:1)");
    }
}
