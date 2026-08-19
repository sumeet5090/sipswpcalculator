<?php

declare(strict_types=1);

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;
use Services\ConfigService;
use Services\CsvExportService;

/**
 * DownloadCsvAction
 * Single Responsibility action dedicated strictly to processing CSV report downloads.
 */
class DownloadCsvAction
{
    private InvestmentCalculator $calculator;
    private ConfigService $configService;
    private CsvExportService $csvExportService;

    public function __construct(
        InvestmentCalculator $calculator,
        ConfigService $configService,
        CsvExportService $csvExportService
    ) {
        $this->calculator = $calculator;
        $this->configService = $configService;
        $this->csvExportService = $csvExportService;
    }

    public function __invoke(Request $request): Response
    {
        $body = $request->getParsedBody();
        $inputs = InvestmentInputs::fromRequest($body, $this->configService);
        $enableSwp = $inputs->isSwpEnabled();
        $combined = $this->calculator->calculate($inputs);

        $currency = strtoupper((string) ($body['currency'] ?? $body['cur'] ?? 'INR'));
        $symbolMap = ['INR' => '₹', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'AED' => 'AED', 'CAD' => '$', 'AUD' => '$'];
        $sym = $symbolMap[$currency] ?? '₹';

        $csvContent = $this->csvExportService->generate($combined, $enableSwp, $sym);

        return Response::csv($csvContent, 'SIP_SWP_Yearly_Report.csv');
    }
}
