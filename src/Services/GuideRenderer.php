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
    private SchemaHelper $schemaHelper;

    public function __construct(
        ContentManager $contentManager,
        MetaManager $metaManager,
        SchemaHelper $schemaHelper
    ) {
        $this->contentManager = $contentManager;
        $this->metaManager = $metaManager;
        $this->schemaHelper = $schemaHelper;
    }

    /**
     * Parse and render an educational guide template in a standard strategy flow.
     *
     * @param string $slug Guide URL path slug (e.g. 'sip-calculator')
     * @param string $seo_category Category folder name (e.g. 'growth', 'retirement', 'comparison')
     * @param string $publishedDate Meta publication date
     * @param string $type The structural type of the page (e.g. 'calculator', 'guide')
     */
    public function render(string $slug, string $seo_category, string $publishedDate, string $type = 'guide'): void
    {
        $path = "/calculators/{$slug}";
        $content = $this->contentManager->getParsedContent($path);

        if (!$content) {
            http_response_code(404);
            echo "404 Guide Not Found";
            return;
        }

        $page_config = $this->metaManager->getMeta($slug);
        if (!empty($content['metadata']['title'])) {
            $page_config = $this->metaManager->setDynamicMeta(
                $content['metadata']['title'],
                $content['metadata']['subtitle'] ?: "Read our guide on " . str_replace('-', ' ', $slug)
            );
        }

        // Generate breadcrumbs schema
        $breadcrumbs_schema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            $page_config['title'] ?? ucfirst(str_replace('-', ' ', $slug)) => '/' . $slug
        ]);

        $url = 'https://sipswpcalculator.com/' . $slug;
        $description = $page_config['meta_desc'] ?? $page_config['title'] ?? '';
        $imageUrl = $page_config['og_image'] ?? 'https://sipswpcalculator.com/assets/og-image-main.jpg';

        $mdFile = __DIR__ . '/../../content/calculators/' . $slug . '.md';
        $actualModifiedDate = file_exists($mdFile) ? date('Y-m-d', filemtime($mdFile)) : $publishedDate;

        // Generate Article schema
        $article_schema = $this->schemaHelper->getArticle(
            $page_config['title'] ?? '',
            $description,
            $url,
            $imageUrl,
            $publishedDate,
            $actualModifiedDate
        );

        $faqRepository = new \Core\FaqRepository();
        $faqs = $faqRepository->getByTag($slug);

        $faq_schema = '';
        if (!empty($faqs)) {
            $faqData = [];
            foreach ($faqs as $faq) {
                $faqData[$faq['q']] = $faq['a'];
            }
            $faq_schema = $this->schemaHelper->getFAQ($faqData);
        }

        // Generate WebPage schema
        $webpage_schema = $this->schemaHelper->getWebPage(
            $page_config['title'] ?? '',
            $description,
            $url
        );

        $additional_schemas = '';
        $calculator_type = 'all';

        if ($type === 'calculator') {
            $calcTitle = $page_config['title'] ?? 'Mutual Fund Calculator';

            if (strpos($slug, 'sip') !== false && strpos($slug, 'swp') === false) {
                $calculator_type = 'sip';
            } elseif (strpos($slug, 'swp') !== false) {
                $calculator_type = 'swp';
            }

            $software_schema = $this->schemaHelper->getSoftwareApplication(
                $calcTitle,
                $description,
                $url,
                "FinanceApplication"
            );
            $additional_schemas .= '<script type="application/ld+json">' . $software_schema . '</script>';
        }

        if ($faq_schema) {
            $additional_schemas .= "\n" . '            <script type="application/ld+json">' . $faq_schema . '</script>';
        }

        $page_config['additional_head'] = '
            <script type="application/ld+json">' . $breadcrumbs_schema . '</script>
            <script type="application/ld+json">' . $article_schema . '</script>
            <script type="application/ld+json">' . $webpage_schema . '</script>
            ' . $additional_schemas . '
        ';

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
        $inputs = ($calculator_type === 'swp')
            ? InvestmentInputs::fromSwpRequest([])
            : InvestmentInputs::fromRequest([]);

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
        $corpus          = ($calculator_type === 'swp') ? $inputs->getLumpsum() : 0.0;
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
