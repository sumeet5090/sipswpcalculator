<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\ContentManager;
use PHPUnit\Framework\TestCase;
use Parsedown;
use RuntimeException;

class ContentManagerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/content_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testThrowsExceptionWhenMarkdownFileMissing(): void
    {
        $parsedown = new Parsedown();
        $manager = new ContentManager($parsedown, $this->tempDir);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Content markdown file missing or unauthorized at: /missing");

        $manager->getFileModifiedDate('/missing');
    }

    public function testParsesFrontmatterAndMarkdownBodyCorrectly(): void
    {
        $parsedown = new Parsedown();
        $manager = new ContentManager($parsedown, $this->tempDir);

        $markdownContent = <<<MD
---
title: "Test Title"
is_active: true
priority: 0.9
---
# Hello World
This is a test.
MD;
        file_put_contents("{$this->tempDir}/test-post.md", $markdownContent);

        $result = $manager->getParsedContent('/test-post');

        $this->assertNotNull($result);
        $this->assertEquals('Test Title', $result['metadata']['title']);
        $this->assertTrue($result['metadata']['is_active']);
        $this->assertEquals('0.9', $result['metadata']['priority']);

        $this->assertStringContainsString('<h1>Hello World</h1>', $result['html']);
        $this->assertStringContainsString('<p>This is a test.</p>', $result['html']);
    }

    public function testReturnsNullWhenFetchingNonexistentContent(): void
    {
        $parsedown = new Parsedown();
        $manager = new ContentManager($parsedown, $this->tempDir);

        $result = $manager->getParsedContent('/does-not-exist');
        $this->assertNull($result);
    }

    public function testResolvesContentWithAndWithoutMdExtension(): void
    {
        $parsedown = new Parsedown();
        $manager = new ContentManager($parsedown, $this->tempDir);

        file_put_contents("{$this->tempDir}/extension-test.md", "# Heading\nBody content");

        $withoutExt = $manager->getParsedContent('/extension-test');
        $withExt = $manager->getParsedContent('/extension-test.md');

        $this->assertNotNull($withoutExt);
        $this->assertNotNull($withExt);
        $this->assertSame($withoutExt['html'], $withExt['html']);
        $this->assertSame($manager->getFileModifiedDate('/extension-test'), $manager->getFileModifiedDate('/extension-test.md'));
    }

    public function testGetFileModifiedDateUsesDeterministicFrontmatterInsteadOfFilemtime(): void
    {
        $parsedown = new Parsedown();
        $manager = new ContentManager($parsedown, $this->tempDir);

        file_put_contents("{$this->tempDir}/updated-priority.md", "---\ntitle: \"A\"\ndate: \"2025-01-15\"\nupdated_at: \"2026-06-20\"\n---\nBody");
        file_put_contents("{$this->tempDir}/month-year-only.md", "---\ntitle: \"B\"\ndate: \"October 2026\"\n---\nBody");
        file_put_contents("{$this->tempDir}/no-date.md", "---\ntitle: \"C\"\n---\nBody");

        touch("{$this->tempDir}/updated-priority.md", strtotime('2029-12-31'));
        touch("{$this->tempDir}/month-year-only.md", strtotime('2029-12-31'));
        touch("{$this->tempDir}/no-date.md", strtotime('2029-12-31'));

        $this->assertSame('2026-06-20', $manager->getFileModifiedDate('/updated-priority'));
        $this->assertSame('2026-10-01', $manager->getFileModifiedDate('/month-year-only'));
        $this->assertSame(\Core\DateConstants::CONTENT_FALLBACK_DATE, $manager->getFileModifiedDate('/no-date'));
    }
}
