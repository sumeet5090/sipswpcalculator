<?php

declare(strict_types=1);

namespace Services;

use Core\BlogRepository;
use Core\ContentManager;
use Core\DateConstants;
use Core\Exceptions\RouteNotFoundException;
use Core\Factories\SchemaFactory;
use Core\FaqRepository;
use Core\MetaManager;
use Core\Strategies\StrategyFactory;

/**
 * GuideViewModelBuilder
 * Domain service to build presentation view models for educational guides and calculator pages.
 */
class GuideViewModelBuilder
{
    private ContentManager $contentManager;
    private MetaManager $metaManager;
    private SchemaFactory $schemaFactory;
    private FaqRepository $faqRepository;
    private BlogRepository $blogRepository;
    private StrategyFactory $strategyFactory;
    private ConfigServiceInterface $configService;

    public function __construct(
        ContentManager $contentManager,
        MetaManager $metaManager,
        SchemaFactory $schemaFactory,
        FaqRepository $faqRepository,
        BlogRepository $blogRepository,
        StrategyFactory $strategyFactory,
        ConfigServiceInterface $configService
    ) {
        $this->contentManager = $contentManager;
        $this->metaManager = $metaManager;
        $this->schemaFactory = $schemaFactory;
        $this->faqRepository = $faqRepository;
        $this->blogRepository = $blogRepository;
        $this->strategyFactory = $strategyFactory;
        $this->configService = $configService;
    }

    /**
     * Build the presentation data array and target template layout for a guide slug.
     *
     * @param string $slug Guide URL path slug (e.g. 'sip-calculator')
     * @return array{layout: string, data: array<string, mixed>}
     */
    public function build(string $slug): array
    {
        $path = "/calculators/{$slug}";
        $content = $this->contentManager->getParsedContent($path);

        if (!$content) {
            throw new RouteNotFoundException("Guide content not found for path: {$path}");
        }

        $meta = $content['metadata'];
        $seo_category = $meta['seo_category'] ?? 'growth';
        $type = $meta['type'] ?? 'guide';
        $publishedDate = $meta['date'] ?? DateConstants::CONTENT_FALLBACK_DATE;

        $page_config = $this->metaManager->buildFromMetadata($meta, '/' . $slug);

        $strategy = $this->strategyFactory->create($slug);
        $calculator_type = 'all';

        if ($type === 'calculator') {
            $calculator_type = $strategy->getType();
        }

        $faqs = $this->faqRepository->getByTag($slug);

        $page_config['additional_head'] = $this->schemaFactory->generateForPage(
            $slug,
            $type,
            $page_config,
            $publishedDate,
            $faqs
        );

        $initialInputs = $strategy->getInitialInputs();
        $calcDefaults = $this->configService->getCalculatorDefaults();

        $show_lumpsum = ($calculator_type === 'lumpsum');
        $layout = ($type === 'calculator') ? 'calculators/calculator-guide' : 'layouts/generic-post';

        $all_posts = $this->blogRepository->getAllPosts();
        $related_calculators = $this->loadRelatedCalculators($slug);

        $data = array_merge($initialInputs->toTemplateData(), [
            'content_html'        => $content['html'],
            'content_metadata'    => $meta,
            'page_config'         => $page_config,
            'active_page'         => $slug,
            'seo_category'        => $seo_category,
            'calculator_type'     => $calculator_type,
            'calc_config'         => $calcDefaults,
            'show_lumpsum'        => $show_lumpsum,
            'faqs'                => $faqs,
            'all_posts'           => $all_posts,
            'related_calculators' => $related_calculators,
        ]);

        return [
            'layout' => $layout,
            'data'   => $data,
        ];
    }

    /**
     * Load contextual related calculators from content mapping.
     *
     * @return array<int, array{href: string, title: string, description: string}>
     */
    private function loadRelatedCalculators(string $slug): array
    {
        $linksPath = __DIR__ . '/../../content/calculator_links.json';
        if (!file_exists($linksPath)) {
            return [];
        }

        $raw = file_get_contents($linksPath);
        if ($raw === false) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded[$slug]) || !is_array($decoded[$slug])) {
            return [];
        }

        return $decoded[$slug];
    }
}
