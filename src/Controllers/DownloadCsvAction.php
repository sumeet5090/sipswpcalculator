<?php

declare(strict_types=1);

namespace Controllers;

use Core\CurrencyFormatterInterface;
use Core\Http\Request;
use Core\Http\Response;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;
use Services\ConfigServiceInterface;
use Services\CsvExportServiceInterface;
use Services\SpecializedReportBuilder;

/**
 * DownloadCsvAction
 * Single Responsibility action dedicated strictly to processing CSV report downloads.
 */
class DownloadCsvAction
{
    private InvestmentCalculator $calculator;
    private ConfigServiceInterface $configService;
    private CsvExportServiceInterface $csvExportService;
    private CurrencyFormatterInterface $currencyFormatter;
    private SpecializedReportBuilder $specializedReportBuilder;

    public function __construct(
        InvestmentCalculator $calculator,
        ConfigServiceInterface $configService,
        CsvExportServiceInterface $csvExportService,
        ?CurrencyFormatterInterface $currencyFormatter = null,
        ?SpecializedReportBuilder $specializedReportBuilder = null
    ) {
        $this->calculator = $calculator;
        $this->configService = $configService;
        $this->csvExportService = $csvExportService;
        $this->currencyFormatter = $currencyFormatter ?? new \Core\CurrencyHelper();
        $this->specializedReportBuilder = $specializedReportBuilder ?? new SpecializedReportBuilder($this->currencyFormatter);
    }

    public function __invoke(Request $request): Response
    {
        $body = $request->getParsedBody();
        $currency = strtoupper((string) ($body['currency'] ?? $body['cur'] ?? 'INR'));
        $sym = $this->currencyFormatter->getSymbol($currency);
        $calcType = strtolower(trim((string) ($body['calculator_type'] ?? '')));

        if ($this->specializedReportBuilder->isSpecialized($calcType)) {
            $dataset = $this->specializedReportBuilder->buildCsv($calcType, $body, $sym);
            if ($dataset !== null) {
                $csvContent = $this->csvExportService->generateDynamic($dataset['headers'], $dataset['rows']);
                return Response::csv($csvContent, $dataset['filename']);
            }
        }

        $isSwpOnly = $calcType === 'swp' || (isset($body['corpus']) && !isset($body['sip']));
        $inputs = $isSwpOnly
            ? InvestmentInputs::fromSwpRequest($body, $this->configService)
            : ($calcType === 'lumpsum'
                ? InvestmentInputs::fromLumpsumRequest($body, $this->configService)
                : InvestmentInputs::fromRequest($body, $this->configService));
        $enableSwp = $inputs->isSwpEnabled();
        $combined = $this->calculator->calculate($inputs);

        $csvContent = $this->csvExportService->generate($combined, $enableSwp, $sym);

        return Response::csv($csvContent, 'SIP_SWP_Yearly_Report.csv');
    }
}
