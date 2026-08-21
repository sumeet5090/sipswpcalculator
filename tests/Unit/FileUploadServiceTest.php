<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Services\FileUploadService;

class FileUploadServiceTest extends TestCase
{
    private FileUploadService $service;
    private string $tempImageFile;

    protected function setUp(): void
    {
        $this->service = new FileUploadService();
        $this->tempImageFile = sys_get_temp_dir() . '/test_logo_' . uniqid() . '.png';

        // Create a 10x10 dummy PNG image
        $img = imagecreatetruecolor(10, 10);
        if ($img !== false) {
            imagepng($img, $this->tempImageFile);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempImageFile)) {
            unlink($this->tempImageFile);
        }
    }

    public function testProcessLogoUploadReturnsNullWhenNoFileUploaded(): void
    {
        $this->assertNull($this->service->processLogoUpload(null));

        $noFile = [
            'name' => '',
            'type' => '',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        ];
        $this->assertNull($this->service->processLogoUpload($noFile));
    }

    public function testProcessLogoUploadThrowsExceptionForOversizedFile(): void
    {
        $oversized = [
            'name' => 'logo.png',
            'type' => 'image/png',
            'tmp_name' => $this->tempImageFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 3 * 1024 * 1024, // 3MB (exceeds 2MB)
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Logo file too large');
        $this->service->processLogoUpload($oversized);
    }

    public function testProcessLogoUploadThrowsExceptionForInvalidImageFile(): void
    {
        $fakeFile = sys_get_temp_dir() . '/fake_' . uniqid() . '.txt';
        file_put_contents($fakeFile, 'This is plain text, not an image');

        $upload = [
            'name' => 'fake.png',
            'type' => 'image/png',
            'tmp_name' => $fakeFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 100,
        ];

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Uploaded file is not a valid image');
            $this->service->processLogoUpload($upload);
        } finally {
            if (file_exists($fakeFile)) {
                unlink($fakeFile);
            }
        }
    }

    public function testProcessLogoUploadSuccessfullyEncodesValidPng(): void
    {
        $upload = [
            'name' => 'valid_logo.png',
            'type' => 'image/png',
            'tmp_name' => $this->tempImageFile,
            'error' => UPLOAD_ERR_OK,
            'size' => (int) filesize($this->tempImageFile),
        ];

        $result = $this->service->processLogoUpload($upload);

        $this->assertNotNull($result);
        $this->assertStringStartsWith('data:image/png;base64,', $result);
    }
}
