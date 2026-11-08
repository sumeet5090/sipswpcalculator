<?php

declare(strict_types=1);

namespace Controllers;

use Core\CurrencyHelper;
use Core\Factories\SchemaFactory;
use Core\FaqRepository;
use Core\Http\Request;
use Core\Http\Response;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;
use Core\MetaManager;
use Core\ViewRenderer;
use Services\ConfigServiceInterface;

class RenderHomeAction
{
    private MetaManager $metaManager;
    private ConfigServiceInterface $configService;
    private FaqRepository $faqRepository;
    private ViewRenderer $viewRenderer;
    private SchemaFactory $schemaFactory;
    private ?\Core\InsightRepository $insightRepository;
    private InvestmentCalculator $calculator;

    public function __construct(
        MetaManager $metaManager,
        ConfigServiceInterface $configService,
        FaqRepository $faqRepository,
        ViewRenderer $viewRenderer,
        SchemaFactory $schemaFactory,
        ?\Core\InsightRepository $insightRepository = null,
        ?InvestmentCalculator $calculator = null
    ) {
        $this->metaManager = $metaManager;
        $this->configService = $configService;
        $this->faqRepository = $faqRepository;
        $this->viewRenderer = $viewRenderer;
        $this->schemaFactory = $schemaFactory;
        $this->insightRepository = $insightRepository;
        $this->calculator = $calculator ?? new InvestmentCalculator();
    }

    public function __invoke(Request $request): Response
    {
        $body = $request->getParsedBody();
        if (!isset($body['enable_swp'])) {
            $body['enable_swp'] = true;
        }
        // Instantiate Input DTO via ConfigService
        $inputs = InvestmentInputs::fromRequest($body, $this->configService);

        $page_config = $this->metaManager->getMeta('home');
        $homeFaqs = $this->faqRepository->getByTag('home');
        $calcConfig = $this->configService->getCalculatorDefaults();

        $siteModified = $this->viewRenderer->getTemplateModifiedDate('calculators/home');

        $page_config['additional_head'] = $this->schemaFactory->generateForHome($page_config, $homeFaqs, $siteModified);

        $weeklyCalculations = $this->insightRepository !== null
            ? $this->insightRepository->getWeeklyCalculationCount()
            : 2000;

        $results = $this->calculator->calculate($inputs);
        $sipYears = $inputs->getYears();
        $swpYears = $inputs->getSwpYears();
        $totalYears = $inputs->isSwpEnabled() ? ($sipYears + $swpYears) : $sipYears;

        $initialKpis = [];
        $initialSchedule = [];
        if (!empty($results)) {
            $lastRow = end($results);
            $totalInvested = (float) $lastRow['cumulative_invested'];
            $finalCorpus = (float) $lastRow['combined_total'];
            $totalWithdrawn = (float) ($lastRow['cumulative_withdrawals'] ?? 0);
            $finalGains = ($finalCorpus + $totalWithdrawn) - $totalInvested;

            $maturedRow = ($inputs->isSwpEnabled() && $sipYears > 0 && isset($results[$sipYears - 1]))
                ? $results[$sipYears - 1]
                : $lastRow;
            $maturedCorpus = (float) $maturedRow['combined_total'];
            $maturedInvested = (float) $maturedRow['cumulative_invested'];
            $maturedGains = max(0.0, $maturedCorpus - $maturedInvested);
            $maturedGainPct = $maturedInvested > 0 ? (int) round(($maturedGains / $maturedInvested) * 100) : 0;

            $initialKpis = [
                'is_dual_mode'           => $inputs->isSwpEnabled() && $sipYears > 0,
                'sip_years'              => $sipYears,
                'swp_years'              => $swpYears,
                'total_years'            => $totalYears,
                'invested_compact'       => CurrencyHelper::formatCompactInr($totalInvested),
                'invested_exact'         => CurrencyHelper::formatInr($totalInvested),
                'maturity_compact'       => CurrencyHelper::formatCompactInr($maturedCorpus),
                'maturity_exact'         => CurrencyHelper::formatInr($maturedCorpus),
                'maturity_gains_compact' => CurrencyHelper::formatCompactInr($maturedGains),
                'maturity_gain_pct'      => $maturedGainPct,
                'withdrawn_compact'      => CurrencyHelper::formatCompactInr($totalWithdrawn),
                'withdrawn_exact'        => CurrencyHelper::formatInr($totalWithdrawn),
                'corpus_compact'         => CurrencyHelper::formatCompactInr($finalCorpus),
                'corpus_exact'           => CurrencyHelper::formatInr($finalCorpus),
                'lifetime_gains_compact' => CurrencyHelper::formatCompactInr($finalGains),
            ];

            $totalCount = count($results);
            foreach ($results as $row) {
                $y = (int) $row['year'];
                if ($y === 1 || $y % 5 === 0 || $y === $totalCount) {
                    $initialSchedule[] = [
                        'year'                   => $y,
                        'is_final'               => $y === $totalCount,
                        'cumulative_invested'    => CurrencyHelper::formatInr((float) $row['cumulative_invested']),
                        'combined_total'         => CurrencyHelper::formatInr((float) $row['combined_total']),
                        'interest'               => CurrencyHelper::formatInr((float) $row['interest']),
                        'cumulative_withdrawals' => CurrencyHelper::formatInr((float) ($row['cumulative_withdrawals'] ?? 0)),
                        'has_withdrawals'        => ((float) ($row['cumulative_withdrawals'] ?? 0)) > 0,
                    ];
                }
            }
        }

        $templateData = array_merge($inputs->toTemplateData(), [
            'active_page'         => 'home',
            'is_calculator'       => true,
            'calculator_type'     => 'combo',
            'benchmark_template'  => 'components/benchmarks/sip-swp-dual.twig',
            'benchmark_title'     => 'SIP & SWP Growth Benchmark Reference Table',
            'page_config'         => $page_config,
            'homeFaqs'            => $homeFaqs,
            'calc_config'         => $calcConfig,
            'show_lumpsum'        => true,
            'site_modified'       => $siteModified,
            'weekly_calculations' => $weeklyCalculations,
            'initial_kpis'        => $initialKpis,
            'initial_schedule'    => $initialSchedule,
        ]);

        return Response::html($this->viewRenderer->render('calculators/home', $templateData));
    }
}
