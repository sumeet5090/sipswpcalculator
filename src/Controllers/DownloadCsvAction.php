<?php

declare(strict_types=1);

namespace Controllers;

use Core\CurrencyFormatterInterface;
use Core\Http\Request;
use Core\Http\Response;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;
use Services\ConfigServiceInterface;
use Services\CsvExportService;

/**
 * DownloadCsvAction
 * Single Responsibility action dedicated strictly to processing CSV report downloads.
 */
class DownloadCsvAction
{
    private InvestmentCalculator $calculator;
    private ConfigServiceInterface $configService;
    private CsvExportService $csvExportService;
    private CurrencyFormatterInterface $currencyFormatter;

    public function __construct(
        InvestmentCalculator $calculator,
        ConfigServiceInterface $configService,
        CsvExportService $csvExportService,
        ?CurrencyFormatterInterface $currencyFormatter = null
    ) {
        $this->calculator = $calculator;
        $this->configService = $configService;
        $this->csvExportService = $csvExportService;
        $this->currencyFormatter = $currencyFormatter ?? new \Core\CurrencyHelper();
    }

    public function __invoke(Request $request): Response
    {
        $body = $request->getParsedBody();
        $isSwpOnly = isset($body['corpus']) && !isset($body['sip']);
        $inputs = $isSwpOnly
            ? InvestmentInputs::fromSwpRequest($body, $this->configService)
            : InvestmentInputs::fromRequest($body, $this->configService);
        $enableSwp = $inputs->isSwpEnabled();
        $combined = $this->calculator->calculate($inputs);

        $currency = strtoupper((string) ($body['currency'] ?? $body['cur'] ?? 'INR'));
        $sym = $this->currencyFormatter->getSymbol($currency);

        $csvContent = $this->csvExportService->generate($combined, $enableSwp, $sym);

        return Response::csv($csvContent, 'SIP_SWP_Yearly_Report.csv');
    }
}
