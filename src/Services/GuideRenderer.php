<?php

declare(strict_types=1);

namespace Services;

use Controllers\ErrorController;
use Core\ContentManager;
use Core\Factories\SchemaFactory;
use Core\FaqRepository;
use Core\BlogRepository;
use Core\Http\Response;
use Core\MetaManager;
use Core\Strategies\StrategyFactory;
use Core\ViewRenderer;

/**
 * GuideRenderer
 * Strategy pattern service to parse, build SEO metadata/schemas, and render educational guides.
 */
class GuideRenderer
{
    private ContentManager $contentManager;
    private MetaManager $metaManager;
    private SchemaFactory $schemaFactory;
    private FaqRepository $faqRepository;
    private BlogRepository $blogRepository;
    private StrategyFactory $strategyFactory;
    private ViewRenderer $viewRenderer;

    public function __construct(
        ContentManager $contentManager,
        MetaManager $metaManager,
        SchemaFactory $schemaFactory,
        FaqRepository $faqRepository,
        BlogRepository $blogRepository,
        StrategyFactory $strategyFactory,
        ViewRenderer $viewRenderer
    ) {
        $this->contentManager = $contentManager;
        $this->metaManager = $metaManager;
        $this->schemaFactory = $schemaFactory;
        $this->faqRepository = $faqRepository;
        $this->blogRepository = $blogRepository;
        $this->strategyFactory = $strategyFactory;
        $this->viewRenderer = $viewRenderer;
    }

    /**
     * Parse and render an educational guide template in a standard strategy flow.
     *
     * @param string $slug Guide URL path slug (e.g. 'sip-calculator')
     */
    public function render(string $slug): Response
    {
        $path = "/calculators/{$slug}";
        $content = $this->contentManager->getParsedContent($path);

        if (!$content) {
            return ErrorController::handle404();
        }

        $meta = $content['metadata'];
        $seo_category = $meta['seo_category'] ?? 'growth';
        $type = $meta['type'] ?? 'guide';
        $publishedDate = $meta['date'] ?? '2026-08-01';

        $page_config = $this->metaManager->buildFromMetadata($meta, $slug);

        $strategy = $this->strategyFactory->create($slug);
        $calculator_type = 'all';

        if ($type === 'calculator') {
            $calculator_type = $strategy->getType();
        }

        $faqs = $this->faqRepository->getByTag($slug);

        $page_config['additional_head'] = $this->schemaFactory->generateForPage(
            $slug,
            $seo_category,
            $page_config,
            $publishedDate,
            $faqs,
            $strategy
        );

        $initialInputs = $strategy->getInitialInputs();
        $sip           = $initialInputs->getSip();
        $years         = $initialInputs->getYears();
        $rate          = $initialInputs->getRate();
        $stepup        = $initialInputs->getStepup();
        $lumpsum       = $initialInputs->getLumpsum();

        $calcConfig = [
            'type'        => $calculator_type,
            'sip'         => $sip,
            'years'       => $years,
            'rate'        => $rate,
            'stepup'      => $stepup,
            'lumpsum'     => $lumpsum,
            'corpus'      => $lumpsum,
            'swp'         => $initialInputs->getSwpWithdrawal(),
            'swp_stepup'  => $initialInputs->getSwpStepup(),
            'swp_rate'    => $initialInputs->getSwpRate(),
            'inflation'   => $initialInputs->getInflation(),
        ];

        $content_html = $content['body'];
        $content_metadata = $meta;
        $active_page = $slug;
        $show_lumpsum = ($calculator_type === 'lumpsum');
        $layout = ($type === 'calculator') ? 'calculators/calculator-guide' : 'layouts/generic-post';

        // Fetch all posts for related resources / internal linking via injected BlogRepository
        $all_posts = $this->blogRepository->getAllPosts();

        return Response::html($this->viewRenderer->render($layout, [
            'content_html'        => $content_html,
            'content_metadata'    => $content_metadata,
            'page_config'         => $page_config,
            'active_page'         => $active_page,
            'category'            => $seo_category,
            'calculator_type'     => $calculator_type,
            'calc_config'         => $calcConfig,
            'show_lumpsum'        => $show_lumpsum,
            'faqs'                => $faqs,
            'combined'            => [],
            'sip'                 => $sip,
            'years'               => $years,
            'rate'                => $rate,
            'stepup'              => $stepup,
            'lumpsum'             => $lumpsum,
            'corpus'              => $lumpsum,
            'swp_withdrawal'      => $initialInputs->getSwpWithdrawal(),
            'swp_stepup'          => $initialInputs->getSwpStepup(),
            'swp_rate'            => $initialInputs->getSwpRate(),
            'inflation'           => $initialInputs->getInflation(),
            'all_posts'           => $all_posts,
        ]));
    }
}
