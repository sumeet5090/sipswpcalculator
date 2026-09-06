<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\BlogRepository;
use Core\ContentManager;
use Parsedown;
use PHPUnit\Framework\TestCase;

class BlogRepositoryCachingTest extends TestCase
{
    private string $tempDir;
    private ContentManager $contentManager;
    private BlogRepository $blogRepository;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/blog_cache_test_' . uniqid();
        mkdir($this->tempDir . '/blog/growth', 0777, true);

        $postContent = "---\ntitle: \"Test Title\"\nread_time: \"4 min\"\ntag: \"Guide\"\n---\n# Post Heading\nPost content body.";
        file_put_contents($this->tempDir . '/blog/growth/test-slug.md', $postContent);

        $categoriesContent = json_encode(['growth' => ['name' => 'Wealth Growth']]);
        file_put_contents($this->tempDir . '/categories.json', $categoriesContent);

        $this->contentManager = new ContentManager(new Parsedown(), $this->tempDir);
        $this->blogRepository = new BlogRepository(
            $this->contentManager,
            $this->tempDir . '/categories.json'
        );
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempDir . '/blog/growth/test-slug.md')) {
            unlink($this->tempDir . '/blog/growth/test-slug.md');
        }
        if (is_dir($this->tempDir . '/blog/growth')) {
            rmdir($this->tempDir . '/blog/growth');
        }
        if (is_dir($this->tempDir . '/blog')) {
            rmdir($this->tempDir . '/blog');
        }
        if (file_exists($this->tempDir . '/categories.json')) {
            unlink($this->tempDir . '/categories.json');
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testContentManagerMemoizesParsedContent(): void
    {
        $parsed1 = $this->contentManager->getParsedContent('/blog/growth/test-slug');
        $this->assertNotNull($parsed1);
        $this->assertSame('Test Title', $parsed1['metadata']['title']);

        // Mutate file on disk
        file_put_contents($this->tempDir . '/blog/growth/test-slug.md', "---\ntitle: \"Mutated Title\"\n---\nBody");

        // Verify in-memory cache returns previous parse
        $parsed2 = $this->contentManager->getParsedContent('/blog/growth/test-slug');
        $this->assertSame('Test Title', $parsed2['metadata']['title']);
    }

    public function testGetPostBySlugUsesPreParsedContent(): void
    {
        $preParsed = [
            'metadata' => [
                'title' => 'Pre-Parsed Title',
                'read_time' => '10 min',
                'tag' => 'Advanced'
            ],
            'html' => '<h1>Pre-Parsed</h1>'
        ];

        $post = $this->blogRepository->getPostBySlug('growth', 'test-slug', $preParsed);
        $this->assertNotNull($post);
        $this->assertSame('Pre-Parsed Title', $post['title']);
        $this->assertSame('10 min', $post['read_time']);
        $this->assertSame('Advanced', $post['tag']);
    }
}
