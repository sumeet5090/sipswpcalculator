<?php

declare(strict_types=1);

namespace Controllers;

use Core\Http\Request;
use Core\Http\Response;
use Core\Exceptions\RateLimitExceededException;
use Core\InvestmentInputs;
use Services\ConfigService;
use Services\HtmlSanitizer;
use Services\PdfGeneratorService;
use Services\RateLimiter;
use Services\SessionManager;

class GeneratePdfAction
{
    private RateLimiter $rateLimiter;
    private SessionManager $sessionManager;
    private PdfGeneratorService $pdfGenerator;
    private ConfigService $configService;
    private HtmlSanitizer $sanitizer;

    public function __construct(
        RateLimiter $rateLimiter,
        SessionManager $sessionManager,
        PdfGeneratorService $pdfGenerator,
        ConfigService $configService,
        ?HtmlSanitizer $sanitizer = null
    ) {
        $this->rateLimiter = $rateLimiter;
        $this->sessionManager = $sessionManager;
        $this->pdfGenerator = $pdfGenerator;
        $this->configService = $configService;
        $this->sanitizer = $sanitizer ?? new HtmlSanitizer();
    }

    public function __invoke(Request $request): Response
    {
        if ($request->getMethod() !== 'POST') {
            return new Response('Method Not Allowed', 405);
        }

        $post = $request->getParsedBody();
        $token = (string) ($post['csrf_token'] ?? '');
        if (!$this->sessionManager->verifyCsrfToken($token)) {
            return new Response('Forbidden: Invalid security token. Please reload the page and try again.', 403);
        }

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
                'logo_base64'       => $this->handleLogoUpload($request->files('advisorLogo')),

                // Summary Metrics
                'currency_symbol'   => $this->sanitizer->sanitizeText((string) ($post['currency_symbol'] ?? ''), 10),
                'summary_invested'  => $this->sanitizer->sanitizeText((string) ($post['summary_invested'] ?? '0'), 50),
                'summary_interest'  => $this->sanitizer->sanitizeText((string) ($post['summary_interest'] ?? '0'), 50),
                'summary_withdrawn' => $this->sanitizer->sanitizeText((string) ($post['summary_withdrawn'] ?? '0'), 50),
                'summary_corpus'    => $this->sanitizer->sanitizeText((string) ($post['summary_corpus'] ?? '0'), 50),
                'raw_invested'      => (float) ($post['raw_invested'] ?? 0),
                'raw_corpus'        => (float) ($post['raw_corpus'] ?? 0),
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
        } catch (\Exception $e) {
            error_log('PDF Generation Error: ' . $e->getMessage());
            return new Response('An error occurred during PDF generation. Please try again.', 500);
        }
    }

    private function handleLogoUpload(?array $logoFile): ?string
    {
        if ($logoFile === null || ($logoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmp_name = $logoFile['tmp_name'];
        $file_size = $logoFile['size'];

        if ($file_size > 2 * 1024 * 1024) {
            throw new \RuntimeException('Logo file too large. Maximum 2MB allowed.');
        }

        $image_info = @getimagesize($tmp_name);
        if ($image_info === false) {
            throw new \RuntimeException('Uploaded file is not a valid image.');
        }

        $allowed_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
        if (!in_array($image_info[2], $allowed_types, true)) {
            throw new \RuntimeException('Invalid image type. Only JPEG, PNG, GIF, and WebP are allowed.');
        }

        if ($image_info[0] > 2000 || $image_info[1] > 2000) {
            throw new \RuntimeException('Image dimensions too large. Maximum 2000x2000 pixels.');
        }

        $safe_mime = $image_info['mime'];
        $data = file_get_contents($tmp_name);
        if ($data === false) {
            throw new \RuntimeException('Failed to read uploaded image file.');
        }
        return 'data:' . $safe_mime . ';base64,' . base64_encode($data);
    }
}
