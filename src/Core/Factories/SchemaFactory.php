<?php

declare(strict_types=1);

namespace Core\Factories;

use Core\ContentManager;
use Core\SchemaHelper;
use Core\SiteConfig;

class SchemaFactory
{
    private SchemaHelper $schemaHelper;
    private SiteConfig $siteConfig;
    private ?\Core\BlogRepository $blogRepository;
    private ?ContentManager $contentManager;

    public function __construct(
        SchemaHelper $schemaHelper,
        SiteConfig $siteConfig,
        ?\Core\BlogRepository $blogRepository = null,
        ?ContentManager $contentManager = null
    ) {
        $this->schemaHelper = $schemaHelper;
        $this->siteConfig = $siteConfig;
        $this->blogRepository = $blogRepository;
        $this->contentManager = $contentManager;
    }

    /**
     * Generates home page structured schemas.
     */
    public function generateForHome(array $page_config, array $homeFaqs = [], string $siteModified = '2026-08-02'): string
    {
        $baseUrl = rtrim($this->siteConfig->getUrl('/'), '/');
        $schemas = [];

        // 1. FAQ Schema
        if (!empty($homeFaqs)) {
            $faqData = [];
            foreach ($homeFaqs as $faq) {
                if (isset($faq['q'], $faq['a'])) {
                    $faqData[$faq['q']] = $faq['a'];
                }
            }
            if (!empty($faqData)) {
                $schemas[] = $this->schemaHelper->getFAQ($faqData);
            }
        }

        // 2. SoftwareApplication Schema
        $schemas[] = json_encode([
            "@context" => "https://schema.org",
            "@id" => $baseUrl . "/#calculator",
            "@type" => "SoftwareApplication",
            "name" => $page_config['schema_name'] ?? "Advanced SIP & SWP Calculator",
            "alternateName" => ["SIP Calculator", "SWP Calculator", "Step-Up SIP Calculator", "Mutual Fund SIP Calculator", "SIP Return Calculator", "SWP Retirement Planner"],
            "url" => $baseUrl . "/",
            "applicationCategory" => "FinanceApplication",
            "applicationSubCategory" => "Investment Calculator",
            "operatingSystem" => "Web",
            "availableOnDevice" => ["Desktop", "Mobile", "Tablet"],
            "inLanguage" => ["en-IN"],
            "isAccessibleForFree" => true,
            "offers" => [
                [
                    "@type" => "Offer",
                    "price" => "0",
                    "priceCurrency" => "INR",
                    "availability" => "https://schema.org/InStock"
                ]
            ],
            "description" => $page_config['meta_desc'] ?? "Advanced SIP & SWP Calculator with step-up (top-up) compounding for Indian mutual fund investment planning.",
            "featureList" => [
                "Core Attribute: Step-up compounding (annual top-up 0-50%)",
                "SWP Retirement Planner with step-up withdrawals",
                "Month-by-month simulation",
                "Interactive Chart.js growth visualization",
                "Yearly breakdown table",
                "INR (₹) formatting for Indian mutual funds",
                "CSV export",
                "Branded PDF report generation"
            ],
            "screenshot" => $baseUrl . "/assets/og-image-main.jpg",
            "image" => $baseUrl . "/assets/og-image-main.jpg",
            "datePublished" => "2024-12-01",
            "dateModified" => $siteModified,
            "softwareVersion" => "3.0",
            "author" => ["@id" => $baseUrl . "/#author"],
            "publisher" => ["@id" => $baseUrl . "/#organization"],
            "sameAs" => [
                "https://en.wikipedia.org/wiki/Systematic_investment_plan",
                "https://www.wikidata.org/wiki/Q7662882"
            ]
        ], SchemaHelper::JSON_FLAGS);

        // 3. FinancialProduct Schema
        $schemas[] = json_encode([
            "@context" => "https://schema.org",
            "@id" => $baseUrl . "/#financialproduct",
            "@type" => "FinancialProduct",
            "name" => "SIP & SWP Investment Planning Tool",
            "alternateName" => "Mutual Fund SIP Return Calculator",
            "description" => "Free financial planning tool for calculating Systematic Investment Plan (SIP) returns with annual step-up compounding and Systematic Withdrawal Plan (SWP) retirement income projections.",
            "url" => $baseUrl . "/",
            "provider" => ["@id" => $baseUrl . "/#organization"],
            "category" => "Investment Planning Tool",
            "feesAndCommissionsSpecification" => "Completely free — no fees, commissions, or registration required",
            "areaServed" => ["@type" => "Place", "name" => "Worldwide"],
            "availableChannel" => [
                "@type" => "ServiceChannel",
                "serviceUrl" => $baseUrl . "/",
                "availableLanguage" => "English"
            ],
            "termsOfService" => $baseUrl . "/terms",
            "broker" => ["@id" => $baseUrl . "/#author"],
            "currenciesAccepted" => "INR",
            "sameAs" => [
                "https://en.wikipedia.org/wiki/Systematic_investment_plan",
                "https://en.wikipedia.org/wiki/Systematic_withdrawal_plan"
            ]
        ], SchemaHelper::JSON_FLAGS);

        // 4. WebSite Schema
        $schemas[] = json_encode([
            "@context" => "https://schema.org",
            "@id" => $baseUrl . "/#website",
            "@type" => "WebSite",
            "name" => "Advanced SIP & SWP Calculator",
            "alternateName" => "sipswpcalculator.com",
            "url" => $baseUrl . "/",
            "description" => "Free online SIP calculator with step-up compounding and SWP retirement planner.",
            "inLanguage" => "en",
            "publisher" => ["@id" => $baseUrl . "/#organization"],
            "creator" => ["@id" => $baseUrl . "/#author"],
            "datePublished" => "2024-12-01",
            "dateModified" => $siteModified,
            "copyrightYear" => 2024,
            "copyrightHolder" => ["@id" => $baseUrl . "/#author"],
            "potentialAction" => [
                "@type" => "SearchAction",
                "target" => [
                    "@type" => "EntryPoint",
                    "urlTemplate" => $baseUrl . "/?sip={sip_amount}"
                ],
                "query-input" => "required name=sip_amount"
            ]
        ], SchemaHelper::JSON_FLAGS);

        // 5. Organization Schema
        $schemas[] = json_encode([
            "@context" => "https://schema.org",
            "@id" => $baseUrl . "/#organization",
            "@type" => "Organization",
            "name" => "SIP & SWP Calculator",
            "legalName" => "SIP SWP Calculator",
            "url" => $baseUrl . "/",
            "logo" => [
                "@type" => "ImageObject",
                "url" => $baseUrl . "/assets/favicon.svg",
                "width" => 512,
                "height" => 512
            ],
            "description" => "Publisher of free, open-access financial planning tools for SIP and SWP calculations.",
            "foundingDate" => "2024-12-01",
            "founder" => ["@id" => $baseUrl . "/#author"],
            "contactPoint" => [
                "@type" => "ContactPoint",
                "email" => "help@sipswpcalculator.com",
                "contactType" => "customer service",
                "availableLanguage" => "English"
            ],
            "sameAs" => ["https://www.linkedin.com/in/sumeet-boga/"]
        ], SchemaHelper::JSON_FLAGS);

        // 6. Person Schema
        $schemas[] = json_encode([
            "@context" => "https://schema.org",
            "@id" => $baseUrl . "/#author",
            "@type" => "Person",
            "name" => "Sumeet Boga",
            "url" => $baseUrl . "/about",
            "image" => $baseUrl . "/assets/sumeet-boga-56.jpg",
            "jobTitle" => "Software Engineer & Finance Enthusiast",
            "description" => "Creator of the Advanced SIP & SWP Calculator.",
            "sameAs" => ["https://www.linkedin.com/in/sumeet-boga/"],
            "worksFor" => ["@id" => $baseUrl . "/#organization"]
        ], SchemaHelper::JSON_FLAGS);

        // 7. HowTo Schema
        $schemas[] = json_encode([
            "@context" => "https://schema.org",
            "@id" => $baseUrl . "/#howto",
            "@type" => "HowTo",
            "name" => "How to Calculate SIP Returns with Step-Up Compounding",
            "description" => "Step-by-step guide to using the Advanced SIP & SWP Calculator.",
            "totalTime" => "PT2M",
            "tool" => ["@id" => $baseUrl . "/#calculator"],
            "step" => [
                [
                    "@type" => "HowToStep",
                    "position" => 1,
                    "name" => "Enter SIP Investment Details",
                    "text" => "Set your monthly SIP amount, investment period, expected annual return rate, and optional annual step-up percentage.",
                    "url" => $baseUrl . "/#calculator-heading"
                ],
                [
                    "@type" => "HowToStep",
                    "position" => 2,
                    "name" => "Configure SWP Retirement Withdrawals (Optional)",
                    "text" => "Enable the SWP toggle to plan systematic withdrawals.",
                    "url" => $baseUrl . "/#calculator-heading"
                ],
                [
                    "@type" => "HowToStep",
                    "position" => 3,
                    "name" => "Analyze Results and Export Reports",
                    "text" => "View the interactive growth chart and export results.",
                    "url" => $baseUrl . "/#yearly-breakdown"
                ]
            ]
        ], SchemaHelper::JSON_FLAGS);

        $html = '';
        foreach ($schemas as $schema) {
            $html .= '<script type="application/ld+json">' . $schema . '</script>' . "\n";
        }

        return $html;
    }

    /**
     * Generates all necessary schemas for a page.
     */
    public function generateForPage(
        string $slug,
        string $type,
        array $page_config,
        string $publishedDate,
        array $faqs = [],
        array $customBreadcrumbs = [],
        string $customUrl = ''
    ): string {
        $url = $customUrl ?: $this->siteConfig->getUrl('/' . ltrim($slug, '/'));
        if (empty($page_config['title'])) {
            throw new \Core\Exceptions\ConfigurationException("Missing required 'title' in page configuration for slug: '{$slug}'");
        }
        $title = (string) $page_config['title'];
        $description = $page_config['meta_desc'] ?? $title;
        $imageUrl = $page_config['og_image'] ?? $this->siteConfig->getUrl('/assets/og-image-main.jpg');

        $actualModifiedDate = $publishedDate;
        if ($type === 'blog' && $this->blogRepository !== null) {
            $parts = explode('/', ltrim($slug, '/'));
            if (count($parts) === 2) {
                $actualModifiedDate = $this->blogRepository->getPostModifiedDate($parts[0], $parts[1]);
            }
        } elseif ($this->contentManager !== null) {
            $actualModifiedDate = $this->contentManager->getFileModifiedDate('calculators/' . ltrim($slug, '/'));
        }

        $schemas = [];

        // 1. Breadcrumbs
        $breadcrumbs = $customBreadcrumbs ?: [
            'Home' => '/',
            $title => '/' . ltrim($slug, '/')
        ];
        $schemas[] = $this->schemaHelper->getBreadcrumbs($breadcrumbs);

        // 2. Article Schema
        $schemas[] = $this->schemaHelper->getArticle(
            $title,
            $description,
            $url,
            $imageUrl,
            $publishedDate,
            $actualModifiedDate
        );

        // 3. WebPage Schema
        $schemas[] = $this->schemaHelper->getWebPage(
            $title,
            $description,
            $url
        );

        // 4. FAQ Schema
        if (!empty($faqs)) {
            $faqData = [];
            foreach ($faqs as $faq) {
                if (isset($faq['q']) && isset($faq['a'])) {
                    $faqData[$faq['q']] = $faq['a'];
                }
            }
            if (!empty($faqData)) {
                $schemas[] = $this->schemaHelper->getFAQ($faqData);
            }
        }

        // 5. Software Application Schema (if Calculator)
        if ($type === 'calculator') {
            $schemaName = (string) ($page_config['schema_name'] ?? $title);
            $software_schema = $this->schemaHelper->getSoftwareApplication(
                $schemaName,
                $description,
                $url,
                "FinanceApplication"
            );
            $schemas[] = $software_schema;
        }

        $html = '';
        foreach ($schemas as $schema) {
            $html .= '<script type="application/ld+json">' . $schema . '</script>' . "\n";
        }

        return $html;
    }
}
