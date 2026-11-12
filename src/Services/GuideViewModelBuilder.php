<?php

declare(strict_types=1);

namespace Services;

use Core\BlogRepository;
use Core\ContentManager;
use Core\CurrencyHelper;
use Core\DateConstants;
use Core\Exceptions\RouteNotFoundException;
use Core\Factories\SchemaFactory;
use Core\FaqRepository;
use Core\Inputs\CagrInputs;
use Core\Inputs\CalculatorInputsInterface;
use Core\Inputs\CompoundInterestInputs;
use Core\Inputs\EmiInputs;
use Core\Inputs\FdInputs;
use Core\Inputs\InflationInputs;
use Core\Inputs\PpfInputs;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;
use Core\Math\CagrEngine;
use Core\Math\CompoundInterestEngine;
use Core\Math\EmiEngine;
use Core\Math\FdEngine;
use Core\Math\InflationEngine;
use Core\Math\PpfEngine;
use Core\MetaManager;
use Core\PurchasingPowerHelper;
use Core\Strategies\StrategyFactory;
use Core\Strategies\TargetCorpusStrategy;

/**
 * GuideViewModelBuilder
 * Domain service to build presentation view models for educational guides and calculator pages.
 */
class GuideViewModelBuilder
{
    private ContentManager $contentManager;
    private MetaManager $metaManager;
    private SchemaFactory $schemaFactory;
    private FaqRepository $faqRepository;
    private BlogRepository $blogRepository;
    private StrategyFactory $strategyFactory;
    private ConfigServiceInterface $configService;
    private InvestmentCalculator $calculator;

    public function __construct(
        ContentManager $contentManager,
        MetaManager $metaManager,
        SchemaFactory $schemaFactory,
        FaqRepository $faqRepository,
        BlogRepository $blogRepository,
        StrategyFactory $strategyFactory,
        ConfigServiceInterface $configService,
        ?InvestmentCalculator $calculator = null
    ) {
        $this->contentManager = $contentManager;
        $this->metaManager = $metaManager;
        $this->schemaFactory = $schemaFactory;
        $this->faqRepository = $faqRepository;
        $this->blogRepository = $blogRepository;
        $this->strategyFactory = $strategyFactory;
        $this->configService = $configService;
        $this->calculator = $calculator ?? new InvestmentCalculator();
    }

    /**
     * Build the presentation data array and target template layout for a guide slug.
     *
     * @param string $slug Guide URL path slug (e.g. 'sip-calculator')
     * @return array{layout: string, data: array<string, mixed>}
     */
    public function build(string $slug): array
    {
        $cleanSlug = ltrim($slug, '/');
        $fileSlug = basename($cleanSlug);

        $path = "/calculators/{$fileSlug}";
        $content = $this->contentManager->getParsedContent($path);

        if (!$content) {
            throw new RouteNotFoundException("Guide content not found for path: {$path}");
        }

        $meta = $content['metadata'];
        $seo_category = $meta['seo_category'] ?? 'growth';
        $type = $meta['type'] ?? 'guide';
        $publishedDate = $meta['date'] ?? DateConstants::CONTENT_FALLBACK_DATE;

        $page_config = $this->metaManager->buildFromMetadata($meta, '/' . $cleanSlug);

        $strategy = $this->strategyFactory->create($fileSlug);
        $calculator_type = 'all';

        if ($type === 'calculator') {
            $calculator_type = $strategy->getType();
        }

        $faqs = $this->faqRepository->getByTag($fileSlug);

        $page_config['additional_head'] = $this->schemaFactory->generateForPage(
            $cleanSlug,
            $type,
            $page_config,
            $publishedDate,
            $faqs
        );

        $initialInputs = $strategy->getInitialInputs();
        $calcDefaults = $this->configService->getCalculatorDefaults();

        $show_lumpsum = in_array($calculator_type, ['lumpsum', 'sip', 'sip-step-up', 'combo', 'all'], true);
        $layout = ($type === 'calculator') ? 'calculators/calculator-guide' : 'layouts/generic-post';

        $targetCorpus = ($strategy instanceof TargetCorpusStrategy) ? $strategy->getTargetCorpus() : null;
        $targetCalculatedSip = null;
        if ($strategy instanceof TargetCorpusStrategy && $targetCorpus !== null && $initialInputs instanceof InvestmentInputs) {
            $targetCalculatedSip = $this->calculator->calculateRequiredSip($initialInputs, $targetCorpus);
        }

        $ssrHydration = $type === 'calculator'
            ? $this->buildSsrHydrationData($initialInputs, $calculator_type, $targetCalculatedSip)
            : ['initial_kpis' => [], 'initial_schedule' => [], 'initial_telemetry' => []];

        $all_posts = $this->blogRepository->getAllPosts();
        $related_calculators = $this->loadRelatedCalculators($fileSlug);
        $pillar_guides = $this->loadPillarGuides($fileSlug);

        $data = array_merge($initialInputs->toTemplateData(), [
            'content_html'          => $content['html'],
            'content_metadata'      => $meta,
            'page_config'           => $page_config,
            'active_page'           => $cleanSlug,
            'is_calculator'         => ($type === 'calculator'),
            'seo_category'          => $seo_category,
            'calculator_type'       => $calculator_type,
            'benchmark_template'    => $strategy->getBenchmarkTemplate(),
            'benchmark_title'       => $strategy->getBenchmarkTitle(),
            'target_corpus'         => $targetCorpus,
            'target_calculated_sip' => $targetCalculatedSip,
            'calc_config'           => $calcDefaults,
            'show_lumpsum'          => $show_lumpsum,
            'faqs'                  => $faqs,
            'all_posts'             => $all_posts,
            'related_calculators'   => $related_calculators,
            'pillar_guides'         => $pillar_guides,
            'initial_kpis'          => $ssrHydration['initial_kpis'],
            'initial_schedule'      => $ssrHydration['initial_schedule'],
            'initial_telemetry'     => $ssrHydration['initial_telemetry'],
        ]);

        return [
            'layout' => $layout,
            'data'   => $data,
        ];
    }

    /**
     * Compute SSR initial KPI cards, milestone schedules, and telemetry strip data
     * so crawlers and initial paint never show `₹ --` placeholders or wrong mode text.
     *
     * @return array{
     *     initial_kpis: array<string, mixed>,
     *     initial_schedule: list<array<string, mixed>>,
     *     initial_telemetry: array<string, mixed>
     * }
     */
    private function buildSsrHydrationData(
        CalculatorInputsInterface $inputs,
        string $calculatorType,
        ?float $targetCalculatedSip
    ): array {
        if ($inputs instanceof InvestmentInputs) {
            $simInputs = ($targetCalculatedSip !== null && $calculatorType === 'target_corpus')
                ? $inputs->withSip(round($targetCalculatedSip))
                : $inputs;

            $results = $this->calculator->calculate($simInputs);
            if (empty($results)) {
                return ['initial_kpis' => [], 'initial_schedule' => [], 'initial_telemetry' => []];
            }

            $sipYears = $simInputs->getYears();
            $swpYears = $simInputs->getSwpYears();
            $totalYears = $simInputs->isSwpEnabled() ? ($sipYears + $swpYears) : $sipYears;
            $isDualMode = ($simInputs->isSwpEnabled() && $sipYears > 0);

            $lastRow = end($results);
            $totalInvested = (float) $lastRow['cumulative_invested'];
            $finalCorpus = (float) $lastRow['combined_total'];
            $totalWithdrawn = (float) ($lastRow['cumulative_withdrawals'] ?? 0.0);
            $finalGains = ($finalCorpus + $totalWithdrawn) - $totalInvested;

            $maturedRow = ($isDualMode && isset($results[$sipYears - 1]))
                ? $results[$sipYears - 1]
                : $lastRow;
            $maturedCorpus = (float) $maturedRow['combined_total'];
            $maturedInvested = (float) $maturedRow['cumulative_invested'];
            $maturedGains = max(0.0, $maturedCorpus - $maturedInvested);

            $card2Value = $isDualMode ? $maturedCorpus : $finalGains;
            $gainPct = $isDualMode
                ? ($maturedInvested > 0 ? (int) round(($maturedGains / $maturedInvested) * 100) : 0)
                : ($totalInvested > 0 ? (int) round(($finalGains / $totalInvested) * 100) : 0);

            $initialKpis = [
                'is_dual_mode'           => $isDualMode,
                'sip_years'              => $sipYears,
                'swp_years'              => $swpYears,
                'total_years'            => $totalYears,
                'invested_compact'       => CurrencyHelper::formatCompactInr($totalInvested),
                'invested_exact'         => CurrencyHelper::formatInr($totalInvested),
                'maturity_compact'       => CurrencyHelper::formatCompactInr($card2Value),
                'maturity_exact'         => CurrencyHelper::formatInr($card2Value),
                'maturity_gains_compact' => CurrencyHelper::formatCompactInr($maturedGains),
                'maturity_gain_pct'      => $gainPct,
                'withdrawn_compact'      => CurrencyHelper::formatCompactInr($totalWithdrawn),
                'withdrawn_exact'        => CurrencyHelper::formatInr($totalWithdrawn),
                'corpus_compact'         => CurrencyHelper::formatCompactInr($finalCorpus),
                'corpus_exact'           => CurrencyHelper::formatInr($finalCorpus),
                'lifetime_gains_compact' => CurrencyHelper::formatCompactInr($finalGains),
            ];

            $initialSchedule = $this->extractMilestoneRows($results);
            $initialTelemetry = PurchasingPowerHelper::buildTelemetry($finalCorpus, $simInputs);

            return [
                'initial_kpis'      => $initialKpis,
                'initial_schedule'  => $initialSchedule,
                'initial_telemetry' => $initialTelemetry,
            ];
        }

        return $this->buildSpecializedSsrData($inputs);
    }

    /**
     * Compute SSR initial KPI cards and mobile milestone schedule for the 6 specialized engines.
     *
     * @return array{
     *     initial_kpis: array<string, mixed>,
     *     initial_schedule: list<array<string, mixed>>,
     *     initial_telemetry: array<string, mixed>
     * }
     */
    private function buildSpecializedSsrData(CalculatorInputsInterface $inputs): array
    {
        if ($inputs instanceof CompoundInterestInputs) {
            $engine = new CompoundInterestEngine();
            $res = $engine->calculate(
                $inputs->getPrincipal(),
                $inputs->getRate(),
                $inputs->getYears(),
                $inputs->getFrequency()
            );
            $principal = (float) $res['principal'];
            $interest = (float) $res['total_interest'];
            $finalAmount = (float) $res['final_amount'];
            $gainPct = $principal > 0 ? (int) round(($interest / $principal) * 100) : 0;

            $rows = [];
            foreach ($res['schedule'] as $item) {
                $rows[] = [
                    'year'                   => (int) $item['year'],
                    'cumulative_invested'    => $principal,
                    'interest'               => (float) $item['interest_earned'],
                    'combined_total'         => (float) $item['closing_balance'],
                    'cumulative_withdrawals' => 0.0,
                ];
            }

            return [
                'initial_kpis' => [
                    'is_dual_mode'     => false,
                    'invested_compact' => CurrencyHelper::formatCompactInr($principal),
                    'invested_exact'   => CurrencyHelper::formatInr($principal),
                    'maturity_compact' => CurrencyHelper::formatCompactInr($interest),
                    'maturity_exact'   => CurrencyHelper::formatInr($interest),
                    'gain_badge_text'  => "+{$gainPct}% (EAR: {$res['effective_annual_rate']}%)",
                    'corpus_compact'   => CurrencyHelper::formatCompactInr($finalAmount),
                    'corpus_exact'     => CurrencyHelper::formatInr($finalAmount),
                ],
                'initial_schedule'  => $this->extractMilestoneRows($rows),
                'initial_telemetry' => [],
            ];
        }

        if ($inputs instanceof CagrInputs) {
            $engine = new CagrEngine();
            $initial = $inputs->getInitialValue();
            $final = $inputs->getFinalValue();
            $years = $inputs->getYears();
            $res = $engine->calculate($initial, $final, $years);

            $fullYears = max(1, (int) ceil($years));
            $annualRate = $res['cagr_percentage'] / 100.0;
            $prevVal = $initial;
            $rows = [];
            for ($yr = 1; $yr <= $fullYears; $yr++) {
                $tFrac = min((float) $yr, $years);
                $currentVal = round($initial * pow(1.0 + $annualRate, $tFrac));
                $rows[] = [
                    'year'                   => $yr,
                    'cumulative_invested'    => $initial,
                    'interest'               => $currentVal - $prevVal,
                    'combined_total'         => $currentVal,
                    'cumulative_withdrawals' => 0.0,
                ];
                $prevVal = $currentVal;
            }

            $pct = (float) $res['absolute_return_percentage'];
            $sign = $pct >= 0 ? '+' : '';
            $cagrFormatted = sprintf('%.2f%% p.a.', $res['cagr_percentage']);

            return [
                'initial_kpis' => [
                    'is_dual_mode'     => false,
                    'invested_compact' => CurrencyHelper::formatCompactInr($initial),
                    'invested_exact'   => CurrencyHelper::formatInr($initial),
                    'maturity_compact' => CurrencyHelper::formatCompactInr($res['total_gain']),
                    'maturity_exact'   => CurrencyHelper::formatInr($res['total_gain']),
                    'gain_badge_text'  => sprintf('%s%.1f%% (%.2fx)', $sign, $pct, $res['multiplier']),
                    'corpus_compact'   => $cagrFormatted,
                    'corpus_exact'     => $cagrFormatted,
                ],
                'initial_schedule'  => $this->extractMilestoneRows($rows),
                'initial_telemetry' => [],
            ];
        }

        if ($inputs instanceof EmiInputs) {
            $engine = new EmiEngine();
            $res = $engine->calculate($inputs->getPrincipal(), $inputs->getRate(), $inputs->getYears());
            $principal = (float) $res['principal'];
            $interest = (float) $res['total_interest'];
            $totalPayment = $principal + $interest;
            $emiRounded = round((float) $res['monthly_emi']);

            $cumulativePrincipalPaid = 0.0;
            $rows = [];
            foreach ($res['schedule'] as $item) {
                $cumulativePrincipalPaid += (float) $item['principal_paid'];
                $rows[] = [
                    'year'                   => (int) $item['year'],
                    'cumulative_invested'    => round($cumulativePrincipalPaid),
                    'interest'               => (float) $item['interest_paid'],
                    'combined_total'         => (float) $item['closing_balance'],
                    'cumulative_withdrawals' => 0.0,
                ];
            }

            return [
                'initial_kpis' => [
                    'is_dual_mode'      => false,
                    'invested_compact'  => CurrencyHelper::formatCompactInr($principal),
                    'invested_exact'    => CurrencyHelper::formatInr($principal),
                    'maturity_compact'  => CurrencyHelper::formatCompactInr($interest),
                    'maturity_exact'    => CurrencyHelper::formatInr($interest),
                    'gain_badge_text'   => sprintf('%.1f%% of Loan', $res['interest_ratio_percentage']),
                    'withdrawn_compact' => CurrencyHelper::formatCompactInr($totalPayment),
                    'withdrawn_exact'   => CurrencyHelper::formatInr($totalPayment),
                    'corpus_compact'    => CurrencyHelper::formatCompactInr($emiRounded) . ' / mo',
                    'corpus_exact'      => CurrencyHelper::formatInr($emiRounded) . ' / mo',
                ],
                'initial_schedule'  => $this->extractMilestoneRows($rows),
                'initial_telemetry' => [],
            ];
        }

        if ($inputs instanceof InflationInputs) {
            $engine = new InflationEngine();
            $pv = $inputs->getAmount();
            $res = $engine->calculate($pv, $inputs->getRate(), $inputs->getYears());

            $prevCost = $pv;
            $rows = [];
            foreach ($res['schedule'] as $item) {
                $step = (float) $item['future_cost'] - $prevCost;
                $prevCost = (float) $item['future_cost'];
                $rows[] = [
                    'year'                   => (int) $item['year'],
                    'cumulative_invested'    => $pv,
                    'interest'               => $step,
                    'combined_total'         => (float) $item['future_cost'],
                    'cumulative_withdrawals' => 0.0,
                ];
            }

            return [
                'initial_kpis' => [
                    'is_dual_mode'     => false,
                    'invested_compact' => CurrencyHelper::formatCompactInr($pv),
                    'invested_exact'   => CurrencyHelper::formatInr($pv),
                    'maturity_compact' => CurrencyHelper::formatCompactInr($res['cost_increase']),
                    'maturity_exact'   => CurrencyHelper::formatInr($res['cost_increase']),
                    'gain_badge_text'  => sprintf('-%.1f%% Value', $res['purchasing_power_loss_percentage']),
                    'corpus_compact'   => CurrencyHelper::formatCompactInr($res['future_cost']),
                    'corpus_exact'     => CurrencyHelper::formatInr($res['future_cost']),
                ],
                'initial_schedule'  => $this->extractMilestoneRows($rows),
                'initial_telemetry' => [],
            ];
        }

        if ($inputs instanceof PpfInputs) {
            $res = PpfEngine::calculate(
                $inputs->getAnnualInvestment(),
                $inputs->getRate(),
                $inputs->getYears(),
                $inputs->getTiming()
            );

            $cumDeposit = 0.0;
            $rows = [];
            foreach ($res['schedule'] as $item) {
                $cumDeposit += (float) $item['annual_deposit'];
                $rows[] = [
                    'year'                   => (int) $item['year'],
                    'cumulative_invested'    => $cumDeposit,
                    'interest'               => (float) $item['interest_earned'],
                    'combined_total'         => (float) $item['closing_balance'],
                    'cumulative_withdrawals' => 0.0,
                ];
            }

            return [
                'initial_kpis' => [
                    'is_dual_mode'     => false,
                    'invested_compact' => CurrencyHelper::formatCompactInr($res['total_invested']),
                    'invested_exact'   => CurrencyHelper::formatInr($res['total_invested']),
                    'maturity_compact' => CurrencyHelper::formatCompactInr($res['total_interest']),
                    'maturity_exact'   => CurrencyHelper::formatInr($res['total_interest']),
                    'gain_badge_text'  => '100% Tax-Free (EEE)',
                    'corpus_compact'   => CurrencyHelper::formatCompactInr($res['maturity_amount']),
                    'corpus_exact'     => CurrencyHelper::formatInr($res['maturity_amount']),
                ],
                'initial_schedule'  => $this->extractMilestoneRows($rows),
                'initial_telemetry' => [],
            ];
        }

        if ($inputs instanceof FdInputs) {
            $res = FdEngine::calculate(
                $inputs->getAmount(),
                $inputs->getRate(),
                $inputs->getYears(),
                $inputs->isSenior(),
                $inputs->getPayoutFrequency()
            );

            $cumPayout = 0.0;
            $rows = [];
            foreach ($res['yearly_schedule'] as $item) {
                $cumPayout += (float) $item['payout_withdrawn'];
                $rows[] = [
                    'year'                   => (int) $item['year'],
                    'cumulative_invested'    => (float) $res['principal'],
                    'interest'               => (float) $item['interest_earned'],
                    'combined_total'         => (float) $item['closing_balance'],
                    'cumulative_withdrawals' => round($cumPayout),
                ];
            }

            $finalVal = $inputs->getPayoutFrequency() === 'cumulative'
                ? (float) $res['maturity_amount']
                : (float) $res['periodic_payout'];

            return [
                'initial_kpis' => [
                    'is_dual_mode'     => false,
                    'invested_compact' => CurrencyHelper::formatCompactInr($res['principal']),
                    'invested_exact'   => CurrencyHelper::formatInr($res['principal']),
                    'maturity_compact' => CurrencyHelper::formatCompactInr($res['total_interest']),
                    'maturity_exact'   => CurrencyHelper::formatInr($res['total_interest']),
                    'gain_badge_text'  => $inputs->isSenior() ? '+0.5% Senior Card' : "{$res['effective_rate']}% Card Rate",
                    'corpus_compact'   => CurrencyHelper::formatCompactInr($finalVal),
                    'corpus_exact'     => CurrencyHelper::formatInr($finalVal),
                ],
                'initial_schedule'  => $this->extractMilestoneRows($rows),
                'initial_telemetry' => [],
            ];
        }

        return ['initial_kpis' => [], 'initial_schedule' => [], 'initial_telemetry' => []];
    }

    /**
     * Select milestone years (Year 1, every 5th year, and final year) formatted for mobile SSR cards/list.
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function extractMilestoneRows(array $rows): array
    {
        $totalCount = count($rows);
        if ($totalCount === 0) {
            return [];
        }

        $milestones = [];
        foreach ($rows as $row) {
            $y = (int) $row['year'];
            if ($y === 1 || $y % 5 === 0 || $y === $totalCount) {
                $withdrawals = (float) ($row['cumulative_withdrawals'] ?? 0.0);
                $milestones[] = [
                    'year'                   => $y,
                    'is_final'               => $y === $totalCount,
                    'cumulative_invested'    => CurrencyHelper::formatInr((float) $row['cumulative_invested']),
                    'combined_total'         => CurrencyHelper::formatInr((float) $row['combined_total']),
                    'interest'               => CurrencyHelper::formatInr((float) $row['interest']),
                    'cumulative_withdrawals' => CurrencyHelper::formatInr($withdrawals),
                    'has_withdrawals'        => $withdrawals > 0.0,
                ];
            }
        }

        return $milestones;
    }

    /**
     * Load contextual related calculators from content mapping.
     *
     * @return array<int, array{href: string, title: string, description: string}>
     */
    private function loadRelatedCalculators(string $slug): array
    {
        $data = $this->configService->getJsonConfig('content/calculator_links.json');
        if (!isset($data[$slug]) || !is_array($data[$slug])) {
            return [];
        }

        return $data[$slug];
    }

    /**
     * Load educational pillar guides from content mapping.
     *
     * @return array<int, array{href: string, title: string, tag: string, read_time: string, category: string, category_name: string, description: string}>
     */
    private function loadPillarGuides(string $slug): array
    {
        $data = $this->configService->getJsonConfig('content/calculator_pillar_guides.json');
        if (!isset($data[$slug]) || !is_array($data[$slug])) {
            return [];
        }

        return $data[$slug];
    }
}
