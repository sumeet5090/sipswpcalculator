<?php

declare(strict_types=1);

namespace Services;

use Core\PdfTemplateInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Core\PdfReportTemplate;

/**
 * PdfGeneratorService
 * Encapsulates Dompdf configuration and rendering workflow for report generation.
 */
class PdfGeneratorService
{
    private PdfTemplateInterface $template;
    private string $fontDir;
    private string $tempDir;

    public function __construct(
        PdfTemplateInterface $template,
        ?string $fontDir = null,
        ?string $tempDir = null
    ) {
        $this->template = $template;
        $this->fontDir = $fontDir ?? dirname(__DIR__, 2) . '/var/cache/fonts';
        $this->tempDir = $tempDir ?? dirname(__DIR__, 2) . '/var/cache/dompdf';
        $this->ensureDirectories();
    }

    private function ensureDirectories(): void
    {
        foreach ([$this->fontDir, $this->tempDir] as $dir) {
            if (!file_exists($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                error_log("PdfGeneratorService: Failed to create cache directory: {$dir}");
            }
        }
    }

    public function getFontDir(): string
    {
        return $this->fontDir;
    }

    public function getTempDir(): string
    {
        return $this->tempDir;
    }

    /**
     * Render inputs into binary PDF content string.
     *
     * @param array<string, mixed> $inputs
     * @return string Binary PDF stream
     */
    public function generate(array $inputs): string
    {
        $html = $this->template->render($inputs);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');
        $options->set('isFontSubsettingEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->set('fontDir', $this->fontDir);
        $options->set('fontCache', $this->fontDir);
        $options->set('tempDir', $this->tempDir);

        $initialLevel = ob_get_level();
        ob_start();

        try {
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfBinary = $dompdf->output();

            return (string) $pdfBinary;
        } finally {
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }
        }
    }
}
