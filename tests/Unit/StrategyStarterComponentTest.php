<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates the unified Strategy Starter component, drawer toggle controls, and 1-click blueprint attributes.
 */
final class StrategyStarterComponentTest extends TestCase
{
    private string $homeTwigContent;
    private string $strategyStarterContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->homeTwigContent = (string) file_get_contents(__DIR__ . '/../../src/Views/calculators/home.twig');
        $this->strategyStarterContent = (string) file_get_contents(__DIR__ . '/../../src/Views/components/strategy-starter.twig');
    }

    public function testHomeTwigIncludesUnifiedStrategyStarterWithoutDuplicates(): void
    {
        // home.twig must include strategy-starter.twig exactly once
        $this->assertStringContainsString(
            "{% include 'components/strategy-starter.twig' %}",
            $this->homeTwigContent,
            'home.twig must include components/strategy-starter.twig'
        );

        // home.twig must NOT contain separate standalone includes for wealth-quiz or persona-blueprints
        $this->assertStringNotContainsString(
            "{% include 'components/wealth-quiz.twig' %}",
            $this->homeTwigContent,
            'home.twig must not contain duplicate standalone wealth-quiz include'
        );
        $this->assertStringNotContainsString(
            "{% include 'components/persona-blueprints.twig' %}",
            $this->homeTwigContent,
            'home.twig must not contain duplicate standalone persona-blueprints include'
        );
    }

    public function testStrategyStarterHasAriaControlsAndAccordionToggle(): void
    {
        $this->assertStringContainsString('id="toggle-guided-wizard-btn"', $this->strategyStarterContent);
        $this->assertStringContainsString('aria-expanded="false"', $this->strategyStarterContent);
        $this->assertStringContainsString('aria-controls="wealth-guided-wizard-drawer"', $this->strategyStarterContent);
        $this->assertStringContainsString('id="wealth-guided-wizard-drawer"', $this->strategyStarterContent);
    }

    public function testStrategyStarterContainsAllFourPersonaBlueprints(): void
    {
        $this->assertStringContainsString('data-persona="first_crore"', $this->strategyStarterContent);
        $this->assertStringContainsString('data-persona="fire_retirement"', $this->strategyStarterContent);
        $this->assertStringContainsString('data-persona="child_education"', $this->strategyStarterContent);
        $this->assertStringContainsString('data-persona="capital_preservation"', $this->strategyStarterContent);
    }

    public function testStrategyStarterContainsAllThreeWizardSteps(): void
    {
        $this->assertStringContainsString('id="quiz-step-1"', $this->strategyStarterContent);
        $this->assertStringContainsString('id="quiz-step-2"', $this->strategyStarterContent);
        $this->assertStringContainsString('id="quiz-step-3"', $this->strategyStarterContent);
        $this->assertStringContainsString('id="apply-quiz-plan-btn"', $this->strategyStarterContent);
    }
}
