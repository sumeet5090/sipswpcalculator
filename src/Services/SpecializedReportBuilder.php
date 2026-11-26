<?php

declare(strict_types=1);

namespace Services;

use Core\CurrencyFormatterInterface;
use Core\CurrencyHelper;
use Services\Reports\LoanAndInflationReportHandler;
use Services\Reports\RetirementAndTaxReportHandler;
use Services\Reports\SavingsAndLoanReportHandler;
use Services\Reports\SpecializedReportHandlerInterface;

/**
 * SpecializedReportBuilder
 * Facade dispatching CSV dataset and PDF report context generation across all 12 specialized calculator handlers.
 */
class SpecializedReportBuilder
{
    /** @var array<string, SpecializedReportHandlerInterface> */
    private array $handlersByType = [];

    /**
     * @param list<SpecializedReportHandlerInterface>|null $handlers
     */
    public function __construct(
        ?CurrencyFormatterInterface $currencyFormatter = null,
        ?array $handlers = null
    ) {
        $formatter = $currencyFormatter ?? new CurrencyHelper();
        $handlerList = $handlers ?? [
            new SavingsAndLoanReportHandler($formatter),
            new LoanAndInflationReportHandler($formatter),
            new RetirementAndTaxReportHandler($formatter),
        ];

        foreach ($handlerList as $handler) {
            foreach ($handler->getSupportedTypes() as $type) {
                $this->handlersByType[$type] = $handler;
            }
        }
    }

    public function isSpecialized(string $calculatorType): bool
    {
        return isset($this->handlersByType[$calculatorType]);
    }

    public function resolveFilename(string $calculatorType, string $extension = 'csv'): string
    {
        if (isset($this->handlersByType[$calculatorType])) {
            return $this->handlersByType[$calculatorType]->resolveFilename($calculatorType, $extension);
        }
        $ext = ltrim($extension, '.');
        return "SIP_SWP_Yearly_Report.{$ext}";
    }

    /**
     * @param array<string, mixed> $post
     * @return array{headers: list<string>, rows: list<list<int|float|string>>, filename: string}|null
     */
    public function buildCsv(string $calculatorType, array $post, string $sym): ?array
    {
        $handler = $this->handlersByType[$calculatorType] ?? null;
        if ($handler === null) {
            return null;
        }

        $symbol = trim($sym) !== '' ? trim($sym) : '₹';
        return $handler->buildCsv($calculatorType, $post, $symbol);
    }

    /**
     * Build specialized context overrides for PdfReportTemplate & Twig report views.
     *
     * @param array<string, mixed> $post
     * @return array<string, mixed>|null
     */
    public function buildPdfContext(string $calculatorType, array $post, string $sym): ?array
    {
        $handler = $this->handlersByType[$calculatorType] ?? null;
        if ($handler === null) {
            return null;
        }

        $symbol = trim($sym) !== '' ? trim($sym) : '₹';
        return $handler->buildPdfContext($calculatorType, $post, $symbol);
    }
}
