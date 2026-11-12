<?php

declare(strict_types=1);

namespace Core;

/**
 * PurchasingPowerHelper
 * Computes server-side SSR inflation-adjusted reality metrics, mode-aware living anchors,
 * and the 0-100 Peace-of-Mind resilience score in strict parity with PurchasingPowerController.ts.
 */
final class PurchasingPowerHelper
{
    /**
     * Build the SSR telemetry view model consumed by components/chart-visualization.twig.
     *
     * @return array{
     *     real_corpus: string,
     *     real_corpus_compact: string,
     *     peace_score: int,
     *     resilience_tier: 'bulletproof'|'prudent'|'vulnerable',
     *     resilience_label: string,
     *     anchor_text: string
     * }
     */
    public static function buildTelemetry(float $nominalCorpus, InvestmentInputs $inputs): array
    {
        $effectiveYears = $inputs->isSwpEnabled()
            ? max(1, $inputs->getYears() + $inputs->getSwpYears())
            : max(1, $inputs->getYears());

        $inflationRate = $inputs->getInflation() > 0.0 ? $inputs->getInflation() : 6.0;
        $discountFactor = pow(1.0 + ($inflationRate / 100.0), $effectiveYears);
        $realValue = $discountFactor > 0.0 ? (float) round($nominalCorpus / $discountFactor) : $nominalCorpus;

        $anchorText = $inputs->isSwpEnabled()
            ? self::resolveSwpAnchor($realValue)
            : self::resolveAccumulationAnchor($realValue, $nominalCorpus, $inflationRate, $effectiveYears);

        $peace = self::calculatePeaceOfMind($inputs);

        return [
            'real_corpus'         => CurrencyHelper::formatInr($realValue),
            'real_corpus_compact' => CurrencyHelper::formatCompactInr($realValue),
            'peace_score'         => $peace['score'],
            'resilience_tier'     => $peace['tier'],
            'resilience_label'    => $peace['label'],
            'anchor_text'         => $anchorText,
        ];
    }

    private static function resolveSwpAnchor(float $realCorpus): string
    {
        if ($realCorpus >= 30000000.0) {
            return 'Perpetual sovereign wealth: provides ₹1,20,000/mo lifetime safe withdrawal with zero capital erosion.';
        }
        if ($realCorpus >= 12500000.0) {
            return 'Complete financial independence: covers luxury living expenses in Pune, Bengaluru, or Hyderabad debt-free.';
        }
        if ($realCorpus >= 5000000.0) {
            return 'A fully paid-off 2BHK/3BHK metro apartment + 5 years of family emergency reserve.';
        }
        if ($realCorpus >= 2000000.0) {
            return 'Comprehensive 4-year overseas Master’s degree tuition + living expenses fully funded.';
        }
        return 'Solid financial foundation: shields household against unexpected medical or employment shocks.';
    }

    private static function resolveAccumulationAnchor(
        float $realCorpus,
        float $nominalCorpus,
        float $inflationRate,
        int $effectiveYears
    ): string {
        $realCompact = CurrencyHelper::formatCompactInr($realCorpus);
        $nominalCompact = CurrencyHelper::formatCompactInr($nominalCorpus);
        $rateStr = rtrim(rtrim(number_format($inflationRate, 1, '.', ''), '0'), '.');

        return "At {$rateStr}% CPI over {$effectiveYears} years, your {$nominalCompact} corpus holds {$realCompact} in today's purchasing power.";
    }

    /**
     * @return array{score: int, tier: 'bulletproof'|'prudent'|'vulnerable', label: string}
     */
    private static function calculatePeaceOfMind(InvestmentInputs $inputs): array
    {
        $score = 70;
        $rate = $inputs->getRate();
        $stepup = $inputs->getStepup();
        $years = $inputs->getYears();

        if ($rate <= 12.0) {
            $score += 15;
        } elseif ($rate <= 13.5) {
            $score += 5;
        } elseif ($rate >= 16.0) {
            $score -= 20;
        } else {
            $score -= 5;
        }

        if ($stepup >= 10.0) {
            $score += 15;
        } elseif ($stepup >= 5.0) {
            $score += 10;
        }

        if ($years >= 15) {
            $score += 10;
        } elseif ($years >= 10) {
            $score += 5;
        } elseif ($years <= 3) {
            $score -= 10;
        }

        $clamped = max(25, min(100, $score));

        if ($clamped >= 88) {
            return [
                'score' => $clamped,
                'tier'  => 'bulletproof',
                'label' => '🛡️ Bulletproof Resilience (Institutional Grade)',
            ];
        }
        if ($clamped >= 72) {
            return [
                'score' => $clamped,
                'tier'  => 'prudent',
                'label' => '⚖️ Prudent & Realistic Balanced Model',
            ];
        }
        return [
            'score' => $clamped,
            'tier'  => 'vulnerable',
            'label' => '⚠️ High Market Volatility Sensitivity',
        ];
    }
}
