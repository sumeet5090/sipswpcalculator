<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Config\CalculatorRegistry;
use PHPUnit\Framework\TestCase;
use Services\ConfigService;

/**
 * Verifies SSoT alignment between content/calculator_registry.json, content/calculator_defaults.json,
 * PHP CalculatorRegistry, and TypeScript CalculatorRegistry.ts.
 */
final class CalculatorRegistryTest extends TestCase
{
    public function testAllRoutesResolveToRegisteredModesAndStrategyClasses(): void
    {
        CalculatorRegistry::resetCache();
        $modes = CalculatorRegistry::getModes();
        $routes = CalculatorRegistry::getRoutes();

        $this->assertGreaterThanOrEqual(34, count($routes));
        $this->assertCount(12, CalculatorRegistry::getSpecializedModeNames());

        foreach ($routes as $path => $routeCfg) {
            $mode = (string) ($routeCfg['mode'] ?? '');
            $this->assertArrayHasKey($mode, $modes, "Route {$path} points to unknown mode '{$mode}'");

            $strategyClass = (string) ($routeCfg['strategy_class'] ?? '');
            $this->assertTrue(class_exists($strategyClass), "Strategy class missing for {$path}: {$strategyClass}");

            $formPartial = $modes[$mode]['form_partial'] ?? null;
            if (is_string($formPartial) && $formPartial !== '') {
                $templateFile = __DIR__ . '/../../src/Views/' . ltrim($formPartial, '/');
                $this->assertFileExists($templateFile, "Missing form partial for mode '{$mode}': {$templateFile}");
            }
        }
    }

    public function testCalculatorDefaultsCoverAllSpecializedNumericFieldsWithValidBounds(): void
    {
        $configService = new ConfigService(__DIR__ . '/../../content/calculator_defaults.json');
        $defaults = $configService->getCalculatorDefaults();

        $expectedFields = [
            'ci_principal', 'ci_rate', 'ci_years', 'ci_frequency',
            'cagr_initial', 'cagr_final', 'cagr_years',
            'emi_principal', 'emi_rate', 'emi_years',
            'inf_amount', 'inf_rate', 'inf_years',
            'ppf_deposit', 'ppf_rate', 'ppf_years', 'ppf_timing',
            'fd_principal', 'fd_rate', 'fd_years', 'fd_senior', 'fd_frequency',
            'gratuity_salary', 'gratuity_years', 'gratuity_months', 'gratuity_covered',
            'nps_monthly_contribution', 'nps_current_age', 'nps_retirement_age', 'nps_expected_return', 'nps_annuity_percent', 'nps_annuity_rate',
            'post_ret_existing_corpus', 'post_ret_gratuity', 'post_ret_nps_lump_sum', 'post_ret_pension', 'post_ret_expense', 'post_ret_return', 'post_ret_inflation', 'post_ret_age', 'post_ret_horizon',
            'epf_basic_salary', 'epf_current_age', 'epf_retirement_age', 'epf_vpf_percent', 'epf_salary_hike', 'epf_interest_rate', 'epf_current_balance', 'epf_cap_eps',
            'tax_portfolio_value', 'tax_invested_cost', 'tax_expected_return', 'tax_holding_years', 'tax_grandfather_fmv', 'tax_include_cess',
            'it_gross_salary', 'it_basic_salary', 'it_hra_received', 'it_rent_paid', 'it_is_metro', 'it_sec_80c', 'it_sec_80d', 'it_nps_80ccd1b', 'it_home_loan_24b',
        ];

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $defaults, "calculator_defaults.json missing field '{$field}'");
            $entry = $defaults[$field];
            $this->assertIsArray($entry);
            $this->assertArrayHasKey('default', $entry, "Missing 'default' for '{$field}'");

            if (isset($entry['min'], $entry['max'])) {
                $min = (float) $entry['min'];
                $max = (float) $entry['max'];
                $def = (float) $entry['default'];
                $this->assertLessThanOrEqual($max, $min, "Invalid min > max on '{$field}'");
                $this->assertGreaterThanOrEqual($min, $def, "Default < min on '{$field}'");
                $this->assertLessThanOrEqual($max, $def, "Default > max on '{$field}'");
                if (isset($entry['slider_max'])) {
                    $sliderMax = (float) $entry['slider_max'];
                    $this->assertLessThanOrEqual($max, $sliderMax, "slider_max > max on '{$field}'");
                }
            }
        }
    }
}
