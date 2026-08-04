<?php

declare(strict_types=1);

namespace Controllers;

use Core\FaqRepository;
use Core\Http\Request;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;
use Core\MetaManager;
use Core\View;
use Services\ConfigService;
use Services\CsvExportService;

class RenderHomeAction
{
    private MetaManager $metaManager;
    private ConfigService $configService;
    private CsvExportService $csvExportService;
    private FaqRepository $faqRepository;
    private InvestmentCalculator $calculator;

    public function __construct(
        MetaManager $metaManager,
        ConfigService $configService,
        CsvExportService $csvExportService,
        FaqRepository $faqRepository,
        InvestmentCalculator $calculator
    ) {
        $this->metaManager = $metaManager;
        $this->configService = $configService;
        $this->csvExportService = $csvExportService;
        $this->faqRepository = $faqRepository;
        $this->calculator = $calculator;
    }

    public function __invoke(Request $request): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // CSRF & Honeypot Checks for POST requests
        if ($request->getMethod() === 'POST') {
            $post = $request->getParsedBody();
            if (!empty($post['website_url'])) {
                http_response_code(403);
                die('Forbidden: Automated request detected.');
            }
            $token = $post['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
                http_response_code(403);
                die('Forbidden: Invalid security token. Please reload the page and try again.');
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
            $this->csvExportService->export($combined, $enable_swp);
            return;
        }

        $page_config = $this->metaManager->getMeta('home');
        $homeFaqs = $this->faqRepository->getByTag('home');
        $calcConfig = $this->configService->getCalculatorDefaults();

        $homeTemplatePath = __DIR__ . '/../Views/calculators/home.twig';
        $siteModified = file_exists($homeTemplatePath)
            ? date('Y-m-d', filemtime($homeTemplatePath))
            : date('Y-m-d');

        View::render('calculators/home', [
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
        ]);
    }
}
