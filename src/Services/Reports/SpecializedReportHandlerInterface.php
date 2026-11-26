<?php

declare(strict_types=1);

namespace Services\Reports;

/**
 * Contract for specialized calculator CSV and PDF report builders.
 */
interface SpecializedReportHandlerInterface
{
    /**
     * @return list<string>
     */
    public function getSupportedTypes(): array;

    public function resolveFilename(string $calculatorType, string $extension = 'csv'): string;

    /**
     * @param array<string, mixed> $post
     * @return array{headers: list<string>, rows: list<list<int|float|string>>, filename: string}
     */
    public function buildCsv(string $calculatorType, array $post, string $symbol): array;

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    public function buildPdfContext(string $calculatorType, array $post, string $symbol): array;
}
