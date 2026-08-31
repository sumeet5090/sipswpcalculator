<?php

declare(strict_types=1);

namespace Controllers;

use Core\Factories\SchemaFactory;
use Core\FaqRepository;
use Core\Http\Request;
use Core\Http\Response;
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

    public function __construct(
        MetaManager $metaManager,
        ConfigServiceInterface $configService,
        FaqRepository $faqRepository,
        ViewRenderer $viewRenderer,
        SchemaFactory $schemaFactory,
        ?\Core\InsightRepository $insightRepository = null
    ) {
        $this->metaManager = $metaManager;
        $this->configService = $configService;
        $this->faqRepository = $faqRepository;
        $this->viewRenderer = $viewRenderer;
        $this->schemaFactory = $schemaFactory;
        $this->insightRepository = $insightRepository;
    }

    public function __invoke(Request $request): Response
    {
        // Instantiate Input DTO via ConfigService
        $inputs = InvestmentInputs::fromRequest($request->getParsedBody(), $this->configService);

        $page_config = $this->metaManager->getMeta('home');
        $homeFaqs = $this->faqRepository->getByTag('home');
        $calcConfig = $this->configService->getCalculatorDefaults();

        $siteModified = $this->viewRenderer->getTemplateModifiedDate('calculators/home');

        $page_config['additional_head'] = $this->schemaFactory->generateForHome($page_config, $homeFaqs, $siteModified);

        $weeklyCalculations = $this->insightRepository !== null
            ? $this->insightRepository->getWeeklyCalculationCount()
            : 2000;

        $templateData = array_merge($inputs->toTemplateData(), [
            'active_page'         => 'home',
            'page_config'         => $page_config,
            'homeFaqs'            => $homeFaqs,
            'calc_config'         => $calcConfig,
            'show_lumpsum'        => true,
            'site_modified'       => $siteModified,
            'weekly_calculations' => $weeklyCalculations,
        ]);

        return Response::html($this->viewRenderer->render('calculators/home', $templateData));
    }
}
