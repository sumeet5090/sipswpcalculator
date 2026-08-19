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
            $ip = $request->getClientIp();
            $this->rateLimiter->checkLimit($ip, 'sipswp_rate_limits', 10, 60);
        } catch (RateLimitExceededException $e) {
            return new Response('Too many requests. Please wait a minute before generating another PDF.', 429);
        }

        try {
            // Use central InvestmentInputs for robust, config-driven clamping
            $calcInputs = InvestmentInputs::fromRequest($post, $this->configService);
            $combined = $this->calculator->calculate($calcInputs);

            // Derive verified server-side summary values from calculated schedule
            $lastRow = !empty($combined) ? $combined[count($combined) - 1] : [];
            $serverInvested = (float) ($lastRow['cumulative_invested'] ?? 0);
            $serverInterest = (float) array_sum(array_column($combined, 'interest'));
            $serverWithdrawn = (float) ($lastRow['cumulative_withdrawals'] ?? 0);
            $serverCorpus = (float) ($lastRow['combined_total'] ?? 0);

            $sym = $this->sanitizer->sanitizeText((string) ($post['currency_symbol'] ?? '₹'), 10);
            $formatter = new \Core\CurrencyHelper();

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

                // Verified Summary Metrics
                'currency_symbol'   => $sym,
                'summary_invested'  => $formatter->format($serverInvested),
                'summary_interest'  => $formatter->format($serverInterest),
                'summary_withdrawn' => $formatter->format($serverWithdrawn),
                'summary_corpus'    => $formatter->format($serverCorpus),
                'raw_invested'      => $serverInvested,
                'raw_corpus'        => $serverCorpus,
                'combined_results'  => $combined,
            ];

            // Generate PDF binary using injected PdfGeneratorService
            $pdf_binary = $this->pdfGenerator->generate($inputs);

            $raw_name = trim((string) $inputs['client_name']);
            $unicode_name = preg_replace('/[^\p{L}\p{N}_\- ]/u', '', $raw_name) ?: 'Client';
            $clean_unicode = (string) preg_replace('/\s+/', '_', trim($unicode_name));
            $ascii_name = (string) preg_replace('/[^a-zA-Z0-9_\-]/', '_', $raw_name) ?: 'Client';
            $ascii_name = (string) preg_replace('/_+/', '_', $ascii_name) ?: 'Client';
            $filename = "Financial_Report_for_{$ascii_name}.pdf";
            $encodedFilename = rawurlencode("Financial_Report_for_{$clean_unicode}.pdf");

            return new Response($pdf_binary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"; filename*=UTF-8''{$encodedFilename}",
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
