<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Services\FilenameSanitizer;

class FilenameSanitizerTest extends TestCase
{
    private FilenameSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new FilenameSanitizer();
    }

    public function testSanitizeForAttachmentGeneratesCleanAsciiAndUnicodeFilenames(): void
    {
        $result = $this->sanitizer->sanitizeForAttachment('Sumeet Boga!@#');
        $this->assertSame('Financial_Report_for_Sumeet_Boga_.pdf', $result['filename']);
        $this->assertSame(rawurlencode('Financial_Report_for_Sumeet_Boga.pdf'), $result['encodedFilename']);
    }

    public function testSanitizeForAttachmentHandlesEmptyNameWithFallback(): void
    {
        $result = $this->sanitizer->sanitizeForAttachment('');
        $this->assertSame('Financial_Report_for_Client.pdf', $result['filename']);
        $this->assertSame(rawurlencode('Financial_Report_for_Client.pdf'), $result['encodedFilename']);
    }
}
