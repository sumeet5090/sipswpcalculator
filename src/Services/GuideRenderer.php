<?php

declare(strict_types=1);

namespace Services;

use Core\ContentManager;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;
use Core\MetaManager;
use Core\SchemaHelper;
use Core\View;

/**
 * GuideRenderer
 * Strategy pattern service to parse, build SEO metadata/schemas, and render educational guides.
 */
class GuideRenderer
{
    private ContentManager $contentManager;
    private MetaManager $metaManager;
    private \Core\Factories\SchemaFactory $schemaFactory;

    public function __construct(
        ContentManager $contentManager,
        MetaManager $metaManager,
        \Core\Factories\SchemaFactory $schemaFactory
    ) {
        $this->contentManager = $contentManager;
        $this->metaManager = $metaManager;
        $this->schemaFactory = $schemaFactory;
    }

    /**
     * Parse and render an educational guide template in a standard strategy flow.
     *
     * @param string $slug Guide URL path slug (e.g. 'sip-calculator')
     */
    public function render(string $slug): void
    {
        $path = "/calculators/{$slug}";
        $content = $this->contentManager->getParsedContent($path);

        if (!$content) {
            \Controllers\ErrorController::handle404();
            return;
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

        $faqRepository = new \Core\FaqRepository();
        $faqs = $faqRepository->getByTag($slug);

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

        // Load central calc config — single source of truth for all field bounds/defaults.
        $calcConfig = require __DIR__ . '/../Core/Config/calculator_defaults.php';

        // Build InvestmentInputs from defaults and run the calculator so the chart
        // and table are pre-populated on first load (no user interaction required).
        $inputs = $strategy->getInitialInputs();

        $calculator = new InvestmentCalculator();
        $combined = $calculator->calculate($inputs);

        // Extract chart-ready data arrays from the pre-calculated result.
        $years_data         = array_column($combined, 'year');
        $cumulative_numbers = array_column($combined, 'cumulative_invested');
        $combined_numbers   = array_column($combined, 'combined_total');
        $swp_numbers        = array_map(fn ($v) => $v ?? 0.0, array_column($combined, 'annual_withdrawal'));

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

        // Lumpsum shown only on home page (RenderHomeAction).
        // SWP page uses dedicated corpus field; SIP-only pages hide lumpsum entirely.
        $show_lumpsum = false;

        $layout = ($type === 'calculator') ? 'calculators/calculator-guide' : 'layouts/generic-post';

        // Fetch all posts for related resources / internal linking
        $all_posts = \Core\BlogRepository::getAllPosts();

        View::render($layout, [
            'content_html'        => $content_html,
            'content_metadata'    => $content_metadata,
            'page_config'         => $page_config,
            'active_page'         => $active_page,
            'category'            => $seo_category,
            'calculator_type'     => $calculator_type,
            'calc_config'         => $calcConfig,
            'show_lumpsum'        => $show_lumpsum,
            'faqs'                => $faqs,
            'combined'            => $combined,
            'years_data'          => $years_data,
            'cumulative_numbers'  => $cumulative_numbers,
            'combined_numbers'    => $combined_numbers,
            'swp_numbers'         => $swp_numbers,
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
        ]);
    }
}
