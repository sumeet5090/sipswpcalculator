<?php

declare(strict_types=1);

namespace Tests\Integration;

/**
 * Validates PDF generation endpoint over HTTP, ensuring clean binary stream without HTML/notice corruption.
 */
final class PdfGenerationDownloadTest extends IntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::startLocalServer(9014);
    }

    public static function tearDownAfterClass(): void
    {
        self::stopLocalServer();
    }

    public function testPdfGenerationReturnsPureBinaryStream(): void
    {
        $postData = [
            'clientName' => 'Priya Sharma',
            'advisorName' => 'Apex Financial',
            'sip' => '20000',
            'years' => '10',
            'rate' => '12',
            'stepup' => '10',
            'lumpsum' => '200000',
            'inflation' => '6',
            'enable_swp' => '0',
            'currency_symbol' => '₹',
            'currency' => 'INR',
            'summary_invested' => '₹ 40.25 L',
            'summary_interest' => '₹ 48.50 L',
            'summary_withdrawn' => '₹ 0',
            'summary_corpus' => '₹ 88.75 L',
            'raw_invested' => '4025000',
            'raw_corpus' => '8875000',
            'chartData' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            'tableHtml' => '<table><thead><tr><th>Year</th><th>Invested</th><th>Corpus</th></tr></thead><tbody><tr><td>1</td><td>₹2.4L</td><td>₹2.6L</td></tr></tbody></table>'
        ];

        $ch = curl_init('http://127.0.0.1:9014/generate-pdf');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = (string) curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        $this->assertSame(200, $httpCode, 'PDF endpoint must return 200 OK');
        $this->assertStringContainsString('application/pdf', (string) $contentType);

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        $this->assertStringContainsString('Content-Disposition: attachment;', $headers);
        $this->assertNotEmpty($body, 'PDF body must not be empty');

        // Verify pure PDF magic bytes and valid EOF
        $this->assertStringStartsWith('%PDF-', $body, 'PDF binary must strictly start with %PDF- and not contain leaked HTML/warnings');
        $this->assertStringContainsString('%%EOF', substr($body, -150), 'PDF must end with valid %%EOF trailer');
        $this->assertStringNotContainsString('<br', $body, 'PDF binary must not contain leaked HTML tags');
        $this->assertStringNotContainsString('Deprecated', $body, 'PDF binary must not contain leaked PHP deprecation notices');
    }
}
