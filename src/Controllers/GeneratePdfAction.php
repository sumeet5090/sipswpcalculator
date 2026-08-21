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
use Core\CurrencyFormatterInterface;
use Services\FilenameSanitizer;
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
    private CurrencyFormatterInterface $currencyFormatter;
    private FilenameSanitizer $filenameSanitizer;

    public function __construct(
        RateLimiter $rateLimiter,
        PdfGeneratorService $pdfGenerator,
        ConfigService $configService,
        FileUploadService $fileUploadService,
        HtmlSanitizer $sanitizer,
        InvestmentCalculator $calculator,
        ?CurrencyFormatterInterface $currencyFormatter = null,
        ?FilenameSanitizer $filenameSanitizer = null
    ) {
        $this->rateLimiter = $rateLimiter;
        $this->pdfGenerator = $pdfGenerator;
        $this->configService = $configService;
        $this->fileUploadService = $fileUploadService;
        $this->sanitizer = $sanitizer;
        $this->calculator = $calculator;
        $this->currencyFormatter = $currencyFormatter ?? new \Core\CurrencyHelper();
        $this->filenameSanitizer = $filenameSanitizer ?? new FilenameSanitizer();
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
            $rateLimits = $this->configService->getJsonConfig('content/rate_limits.json');
            $maxRequests = (int) ($rateLimits['pdf_generation']['max_requests'] ?? 10);
            $windowSeconds = (int) ($rateLimits['pdf_generation']['window_seconds'] ?? 60);
            $this->rateLimiter->checkLimit($ip, 'sipswp_rate_limits', $maxRequests, $windowSeconds);
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
            $formatter = $this->currencyFormatter;

            $inputs = array_merge($calcInputs->toTemplateData(), [
                'client_name'       => $this->sanitizer->sanitizeText((string) ($post['clientName'] ?? 'N/A'), 100),
                'advisor_name'      => $this->sanitizer->sanitizeText((string) ($post['advisorName'] ?? 'N/A'), 100),
                'custom_disclaimer' => $this->sanitizer->sanitizeText((string) ($post['customDisclaimer'] ?? ''), 1000),
                'chart_base64'      => $this->sanitizer->extractChartData((string) ($post['chartData'] ?? '')),
                'table_html'        => $this->sanitizer->sanitizeTableHtml((string) ($post['tableHtml'] ?? '')),
                'logo_base64'       => $this->fileUploadService->processLogoUpload($request->files('advisorLogo')),

                // Verified Summary Metrics
                'currency_symbol'   => $sym,
                'summary_invested'  => $formatter->format($serverInvested),
                'summary_interest'  => $formatter->format($serverInterest),
                'summary_withdrawn' => $formatter->format($serverWithdrawn),
                'summary_corpus'    => $formatter->format($serverCorpus),
                'raw_invested'      => $serverInvested,
                'raw_corpus'        => $serverCorpus,
                'raw_withdrawn'     => $serverWithdrawn,
                'combined_results'  => $combined,
            ]);

            // Generate PDF binary using injected PdfGeneratorService
            $pdf_binary = $this->pdfGenerator->generate($inputs);

            $names = $this->filenameSanitizer->sanitizeForAttachment((string) $inputs['client_name']);
            $filename = $names['filename'];
            $encodedFilename = $names['encodedFilename'];

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
