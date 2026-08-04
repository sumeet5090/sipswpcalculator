<?php

declare(strict_types=1);

namespace Services;

use Controllers\ErrorController;
use Core\BlogRepository;
use Core\ContentManager;
use Core\Factories\SchemaFactory;
use Core\FaqRepository;
use Core\Http\Response;
use Core\MetaManager;
use Core\View;

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
    private ConfigService $configService;

    public function __construct(
        ContentManager $contentManager,
        MetaManager $metaManager,
        SchemaFactory $schemaFactory,
        FaqRepository $faqRepository,
        BlogRepository $blogRepository,
        ConfigService $configService
    ) {
        $this->contentManager = $contentManager;
        $this->metaManager = $metaManager;
        $this->schemaFactory = $schemaFactory;
        $this->faqRepository = $faqRepository;
        $this->blogRepository = $blogRepository;
        $this->configService = $configService;
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

        $strategy = \Core\Strategies\StrategyFactory::create($slug);
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
            $faqs,
            $strategy
        );

        // Add custom JS script if it matches specific naming/file templates
        $possibleJsPath = '/assets/js/calculators/' . $slug . '.js';
        $fullJsPath = __DIR__ . '/../../' . $possibleJsPath;
        if (file_exists($fullJsPath)) {
            $page_config['scripts'] = [$possibleJsPath];
        }

        $content_html = $content['html'];
        $content_metadata = $content['metadata'];
        $active_page = $slug . '.php';

        // Load central calc config via ConfigService
        $calcConfig = $this->configService->getCalculatorDefaults();

        // Build InvestmentInputs from defaults
        $inputs = $strategy->getInitialInputs();

        // Extract per-field defaults for form pre-population.
        $sip             = $inputs->getSip();
        $years           = $inputs->getYears();
        $rate            = $inputs->getRate();
        $stepup          = $inputs->getStepup();
        $lumpsum         = $inputs->getLumpsum();
        $corpus          = $strategy->getCorpus($inputs);
        $swp_withdrawal  = $inputs->getSwpWithdrawal();
        $swp_years_input = $inputs->getSwpYears();
        $swp_stepup      = $inputs->getSwpStepup();
        $swp_rate        = $inputs->getSwpRate();

        $show_lumpsum = false;

        $layout = ($type === 'calculator') ? 'calculators/calculator-guide' : 'layouts/generic-post';

        // Fetch all posts for related resources / internal linking via injected BlogRepository
        $all_posts = $this->blogRepository->getAllPosts();

        return Response::html(View::render($layout, [
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
            'corpus'              => $corpus,
            'swp_withdrawal'      => $swp_withdrawal,
            'swp_years_input'     => $swp_years_input,
            'swp_stepup'          => $swp_stepup,
            'swp_rate'            => $swp_rate,
            'all_posts'           => $all_posts,
        ]));
    }
}
