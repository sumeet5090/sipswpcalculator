<?php
namespace Controllers;

class CalculatorController {
    
    public function home() {
        require_once __DIR__ . '/../Views/calculators/home.php';
    }

    public function compoundInterestCalculator() {
        require_once __DIR__ . '/../Views/calculators/compound-interest-calculator.php';
    }

    public function dollarCostAveragingTool() {
        require_once __DIR__ . '/../Views/calculators/dollar-cost-averaging-tool.php';
    }

    public function recurringInvestmentCalculator() {
        require_once __DIR__ . '/../Views/calculators/recurring-investment-calculator.php';
    }

    public function retirementDrawdownPlanner() {
        require_once __DIR__ . '/../Views/calculators/retirement-drawdown-planner.php';
    }

    public function sipCalculator() {
        require_once __DIR__ . '/../Views/calculators/sip-calculator.php';
    }

    public function sipStepUpCalculator() {
        require_once __DIR__ . '/../Views/calculators/sip-step-up-calculator.php';
    }

    public function swpTaxCalculator() {
        require_once __DIR__ . '/../Views/calculators/swp-tax-calculator.php';
    }
}
