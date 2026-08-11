<?php

declare(strict_types=1);

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Core\Exceptions\RateLimitExceededException;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;
use Services\ConfigService;
use Services\FileUploadService;
use Services\HtmlSanitizer;
use Services\PdfGeneratorService;
use Services\RateLimiter;

class GeneratePdfAction
{
    private RateLimiter $rateLimiter;
    private PdfGeneratorService $pdfGenerator;
    private ConfigService $configService;
    private FileUploadService $fileUploadService;
    private HtmlSanitizer $sanitizer;
    private InvestmentCalculator $calculator;

    public function __construct(
        RateLimiter $rateLimiter,
        PdfGeneratorService $pdfGenerator,
        ConfigService $configService,
        FileUploadService $fileUploadService,
        HtmlSanitizer $sanitizer,
        InvestmentCalculator $calculator
    ) {
        $this->rateLimiter = $rateLimiter;
        $this->pdfGenerator = $pdfGenerator;
        $this->configService = $configService;
        $this->fileUploadService = $fileUploadService;
        $this->sanitizer = $sanitizer;
        $this->calculator = $calculator;
    }

    public function __invoke(Request $request): Response
    {
        if ($request->getMethod() !== 'POST') {
            return new Response('Method Not Allowed', 405);
        }

        $post = $request->getParsedBody();

        // Rate limiting check
        try {
            $ip = (string) $request->server('REMOTE_ADDR', 'unknown');
            $this->rateLimiter->checkLimit($ip, 'sipswp_rate_limits', 10, 60);
        } catch (RateLimitExceededException $e) {
            return new Response('Too many requests. Please wait a minute before generating another PDF.', 429);
        }

        try {
            // Use central InvestmentInputs for robust, config-driven clamping
            $calcInputs = InvestmentInputs::fromRequest($post, $this->configService);
            $combined = $this->calculator->calculate($calcInputs);

            $inputs = [
                'client_name'       => $this->sanitizer->sanitizeText((string) ($post['clientName'] ?? 'N/A'), 100),
                'advisor_name'      => $this->sanitizer->sanitizeText((string) ($post['advisorName'] ?? 'N/A'), 100),
                'custom_disclaimer' => $this->sanitizer->sanitizeText((string) ($post['customDisclaimer'] ?? ''), 1000),
                'chart_base64'      => $this->sanitizer->extractChartData((string) ($post['chartData'] ?? '')),
                'table_html'        => $this->sanitizer->sanitizeTableHtml((string) ($post['tableHtml'] ?? '')),
                'sip'               => $calcInputs->getSip(),
                'years'             => $calcInputs->getYears(),
                'rate'              => $calcInputs->getRate(),
                'stepup'            => $calcInputs->getStepup(),
                'lumpsum'           => $calcInputs->getLumpsum(),
                'swp_withdrawal'    => $calcInputs->getSwpWithdrawal(),
                'swp_stepup'        => $calcInputs->getSwpStepup(),
                'swp_years'         => $calcInputs->getSwpYears(),
                'swp_rate'          => $calcInputs->getSwpRate(),
                'logo_base64'       => $this->fileUploadService->processLogoUpload($request->files('advisorLogo')),

                // Summary Metrics
                'currency_symbol'   => $this->sanitizer->sanitizeText((string) ($post['currency_symbol'] ?? ''), 10),
                'summary_invested'  => $this->sanitizer->sanitizeText((string) ($post['summary_invested'] ?? '0'), 50),
                'summary_interest'  => $this->sanitizer->sanitizeText((string) ($post['summary_interest'] ?? '0'), 50),
                'summary_withdrawn' => $this->sanitizer->sanitizeText((string) ($post['summary_withdrawn'] ?? '0'), 50),
                'summary_corpus'    => $this->sanitizer->sanitizeText((string) ($post['summary_corpus'] ?? '0'), 50),
                'raw_invested'      => max(0.0, (float) ($post['raw_invested'] ?? 0)),
                'raw_corpus'        => max(0.0, (float) ($post['raw_corpus'] ?? 0)),
                'combined_results'  => $combined,
            ];

            // Generate PDF binary using injected PdfGeneratorService
            $pdf_binary = $this->pdfGenerator->generate($inputs);

            $raw_name = trim($inputs['client_name']);
            $clean_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $raw_name) ?: 'Client';
            $clean_name = (string) preg_replace('/_+/', '_', $clean_name);
            $filename = "Financial_Report_for_{$clean_name}.pdf";

            return new Response($pdf_binary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => (string) strlen($pdf_binary),
                'Cache-Control' => 'private, max-age=0, must-revalidate',
                'Pragma' => 'public',
            ]);
        } catch (\Throwable $e) {
            error_log('PDF Generation Error: ' . $e->getMessage());
            return new Response('An error occurred during PDF generation. Please try again.', 500);
        }
    }
}
