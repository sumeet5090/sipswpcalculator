<?php

declare(strict_types=1);

namespace Controllers;

use Core\FaqRepository;
use Core\Http\Request;
use Core\Http\Response;
use Core\InvestmentInputs;
use Core\MetaManager;
use Core\ViewRenderer;
use Services\ConfigService;

class RenderHomeAction
{
    private MetaManager $metaManager;
    private ConfigService $configService;
    private FaqRepository $faqRepository;
    private ViewRenderer $viewRenderer;

    public function __construct(
        MetaManager $metaManager,
        ConfigService $configService,
        FaqRepository $faqRepository,
        ViewRenderer $viewRenderer
    ) {
        $this->metaManager = $metaManager;
        $this->configService = $configService;
        $this->faqRepository = $faqRepository;
        $this->viewRenderer = $viewRenderer;
    }

    public function __invoke(Request $request): Response
    {
        // Instantiate Input DTO via ConfigService
        $inputs = InvestmentInputs::fromRequest($request->getParsedBody(), $this->configService);
        $enable_swp = $inputs->isSwpEnabled();

        $page_config = $this->metaManager->getMeta('home');
        $homeFaqs = $this->faqRepository->getByTag('home');
        $calcConfig = $this->configService->getCalculatorDefaults();

        $siteModified = $this->viewRenderer->getTemplateModifiedDate('calculators/home');

        return Response::html($this->viewRenderer->render('calculators/home', [
            'active_page'         => 'index.php',
            'sip'                 => $inputs->getSip(),
            'years'               => $inputs->getYears(),
            'rate'                => $inputs->getRate(),
            'stepup'              => $inputs->getStepup(),
            'lumpsum'             => $inputs->getLumpsum(),
            'enable_swp'          => $enable_swp,
            'swp_withdrawal'      => $inputs->getSwpWithdrawal(),
            'swp_stepup'          => $inputs->getSwpStepup(),
            'swp_years_input'     => $inputs->getSwpYears(),
            'swp_rate'            => $inputs->getSwpRate(),
            'inflation'           => $inputs->getInflation(),
            'page_config'         => $page_config,
            'homeFaqs'            => $homeFaqs,
            'calc_config'         => $calcConfig,
            'show_lumpsum'        => true,
            'site_modified'       => $siteModified,
        ]));
    }
}
