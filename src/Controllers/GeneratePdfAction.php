<?php

declare(strict_types=1);

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Core\Exceptions\RateLimitExceededException;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;
use Services\ConfigServiceInterface;
use Services\FileUploadService;
use Services\HtmlSanitizer;
use Core\CurrencyFormatterInterface;
use Services\FilenameSanitizer;
use Services\PdfGeneratorService;
use Services\SpecializedReportBuilder;

class GeneratePdfAction
{
    private PdfGeneratorService $pdfGenerator;
    private ConfigServiceInterface $configService;
    private FileUploadService $fileUploadService;
    private HtmlSanitizer $sanitizer;
    private InvestmentCalculator $calculator;
    private CurrencyFormatterInterface $currencyFormatter;
    private FilenameSanitizer $filenameSanitizer;
    private SpecializedReportBuilder $specializedReportBuilder;

    public function __construct(
        PdfGeneratorService $pdfGenerator,
        ConfigServiceInterface $configService,
        FileUploadService $fileUploadService,
        HtmlSanitizer $sanitizer,
        InvestmentCalculator $calculator,
        ?CurrencyFormatterInterface $currencyFormatter = null,
        ?FilenameSanitizer $filenameSanitizer = null,
        ?SpecializedReportBuilder $specializedReportBuilder = null
    ) {
        $this->pdfGenerator = $pdfGenerator;
        $this->configService = $configService;
        $this->fileUploadService = $fileUploadService;
        $this->sanitizer = $sanitizer;
        $this->calculator = $calculator;
        $this->currencyFormatter = $currencyFormatter ?? new \Core\CurrencyHelper();
        $this->filenameSanitizer = $filenameSanitizer ?? new FilenameSanitizer();
        $this->specializedReportBuilder = $specializedReportBuilder ?? new SpecializedReportBuilder($this->currencyFormatter);
    }

    public function __invoke(Request $request): Response
    {
        if ($request->getMethod() !== 'POST') {
            return new Response('Method Not Allowed', 405);
        }

        $post = $request->getParsedBody();

        try {
            $sym = $this->sanitizer->sanitizeText((string) ($post['currency_symbol'] ?? '₹'), 10);
            $calcType = strtolower(trim((string) ($post['calculator_type'] ?? '')));

            $commonMeta = [
                'client_name'       => $this->sanitizer->sanitizeText((string) ($post['clientName'] ?? 'N/A'), 100),
                'advisor_name'      => $this->sanitizer->sanitizeText((string) ($post['advisorName'] ?? 'N/A'), 100),
                'custom_disclaimer' => $this->sanitizer->sanitizeText((string) ($post['customDisclaimer'] ?? ''), 1000),
                'chart_base64'      => $this->sanitizer->extractChartData((string) ($post['chartData'] ?? '')),
                'logo_base64'       => $this->fileUploadService->processLogoUpload($request->files('advisorLogo')),
                'currency_symbol'   => $sym,
            ];

            if ($this->specializedReportBuilder->isSpecialized($calcType)) {
                $specializedContext = $this->specializedReportBuilder->buildPdfContext($calcType, $post, $sym);
                $inputs = array_merge($commonMeta, $specializedContext ?? []);
            } else {
                // Use central InvestmentInputs for robust, config-driven clamping
                $isSwpOnly = $calcType === 'swp' || (isset($post['corpus']) && !isset($post['sip']));
                $calcInputs = $isSwpOnly
                    ? InvestmentInputs::fromSwpRequest($post, $this->configService)
                    : ($calcType === 'lumpsum'
                        ? InvestmentInputs::fromLumpsumRequest($post, $this->configService)
                        : InvestmentInputs::fromRequest($post, $this->configService));
                $combined = $this->calculator->calculate($calcInputs);

                // Derive verified server-side summary values from calculated schedule using exact closed-form delta reconciliation
                $lastRow = !empty($combined) ? $combined[count($combined) - 1] : [];
                $serverInvested = (float) ($lastRow['cumulative_invested'] ?? 0);
                $serverWithdrawn = (float) ($lastRow['cumulative_withdrawals'] ?? 0);
                $serverCorpus = (float) ($lastRow['combined_total'] ?? 0);
                $serverInterest = max(0.0, ($serverCorpus + $serverWithdrawn) - $serverInvested);

                $formatter = $this->currencyFormatter;

                $inputs = array_merge($calcInputs->toTemplateData(), $commonMeta, [
                    'summary_invested'  => $formatter->format($serverInvested),
                    'summary_interest'  => $formatter->format($serverInterest),
                    'summary_withdrawn' => $formatter->format($serverWithdrawn),
                    'summary_corpus'    => $formatter->format($serverCorpus),
                    'raw_invested'      => $serverInvested,
                    'raw_corpus'        => $serverCorpus,
                    'raw_withdrawn'     => $serverWithdrawn,
                    'combined_results'  => $combined,
                ]);
            }

            // Generate PDF binary using injected PdfGeneratorService
            $pdf_binary = $this->pdfGenerator->generate($inputs);

            if ($this->specializedReportBuilder->isSpecialized($calcType)) {
                $baseFilename = $this->specializedReportBuilder->resolveFilename($calcType, 'pdf');
                $rawClientName = trim((string) ($inputs['client_name'] ?? ''));
                if ($rawClientName === '' || $rawClientName === 'N/A') {
                    $filename = $baseFilename;
                    $encodedFilename = rawurlencode($baseFilename);
                } else {
                    $prefix = pathinfo($baseFilename, PATHINFO_FILENAME) . '_for';
                    $names = $this->filenameSanitizer->sanitizeForAttachment($rawClientName, $prefix);
                    $filename = $names['filename'];
                    $encodedFilename = $names['encodedFilename'];
                }
            } else {
                $names = $this->filenameSanitizer->sanitizeForAttachment((string) $inputs['client_name']);
                $filename = $names['filename'];
                $encodedFilename = $names['encodedFilename'];
            }

            return new Response($pdf_binary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"; filename*=UTF-8''{$encodedFilename}",
                'Content-Length' => (string) strlen($pdf_binary),
                'Cache-Control' => 'private, max-age=0, must-revalidate',
                'Pragma' => 'public',
                'X-Accel-Buffering' => 'no',
            ]);
        } catch (\Throwable $e) {
            error_log('PDF Generation Error: ' . $e->getMessage());
            return new Response('An error occurred during PDF generation. Please try again.', 500);
        }
    }
}
