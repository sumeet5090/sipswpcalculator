<?php

declare(strict_types=1);

namespace Controllers;

use Core\FaqRepository;
use Core\Http\Request;
use Core\Http\Response;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;
use Core\MetaManager;
use Core\ViewRenderer;
use Services\ConfigService;
use Services\CsvExportService;
use Services\SessionManager;

class RenderHomeAction
{
    private MetaManager $metaManager;
    private ConfigService $configService;
    private CsvExportService $csvExportService;
    private FaqRepository $faqRepository;
    private InvestmentCalculator $calculator;
    private SessionManager $sessionManager;
    private ViewRenderer $viewRenderer;

    public function __construct(
        MetaManager $metaManager,
        ConfigService $configService,
        CsvExportService $csvExportService,
        FaqRepository $faqRepository,
        InvestmentCalculator $calculator,
        SessionManager $sessionManager,
        ViewRenderer $viewRenderer
    ) {
        $this->metaManager = $metaManager;
        $this->configService = $configService;
        $this->csvExportService = $csvExportService;
        $this->faqRepository = $faqRepository;
        $this->calculator = $calculator;
        $this->sessionManager = $sessionManager;
        $this->viewRenderer = $viewRenderer;
    }

    public function __invoke(Request $request): Response
    {
        $this->sessionManager->getCsrfToken();

        // CSRF & Honeypot Checks for POST requests
        if ($request->getMethod() === 'POST') {
            $post = $request->getParsedBody();
            if (!empty($post['website_url'])) {
                return new Response('Forbidden: Automated request detected.', 403);
            }
            $token = (string) ($post['csrf_token'] ?? '');
            if (!$this->sessionManager->verifyCsrfToken($token)) {
                return new Response('Forbidden: Invalid security token. Please reload the page and try again.', 403);
            }
        }

        // Instantiate Input DTO via ConfigService
        $inputs = InvestmentInputs::fromRequest($request->getParsedBody(), $this->configService);
        $enable_swp = $inputs->isSwpEnabled();

        // Handle CSV export action via dedicated service
        $post = $request->getParsedBody();
        $action = $post['action'] ?? '';
        if ($action === 'download_csv') {
            $combined = $this->calculator->calculate($inputs);
            $csvContent = $this->csvExportService->generate($combined, $enable_swp);
            return Response::csv($csvContent, 'SIP_SWP_Yearly_Report.csv');
        }

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
            'combined'            => [],
            'page_config'         => $page_config,
            'homeFaqs'            => $homeFaqs,
            'calc_config'         => $calcConfig,
            'show_lumpsum'        => true,
            'site_modified'       => $siteModified,
        ]));
    }
}
