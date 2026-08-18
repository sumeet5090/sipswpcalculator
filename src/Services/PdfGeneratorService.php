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

    public function __construct(PdfTemplateInterface $template)
    {
        $this->template = $template;
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
