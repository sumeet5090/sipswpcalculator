<?php

declare(strict_types=1);

namespace Core\Factories;

use Core\SchemaHelper;
use Core\SiteConfig;

/**
 * HomeSchemaBuilder
 * Dedicated builder to construct structured JSON-LD schemas specifically for the Home Page
 * (SoftwareApplication, FinancialProduct, WebSite, Organization, Person, HowTo).
 */
class HomeSchemaBuilder
{
    private SiteConfig $siteConfig;

    public function __construct(SiteConfig $siteConfig)
    {
        $this->siteConfig = $siteConfig;
    }

    /**
     * Builds all Home JSON-LD structured schemas.
     *
     * @param array $page_config
     * @param array $homeFaqs
     * @param string $siteModified
     * @return array<string> Array of JSON-encoded schema strings
     */
    public function build(array $page_config, array $homeFaqs = [], string $siteModified = '2026-08-02'): array
    {
        $baseUrl = rtrim($this->siteConfig->getUrl('/'), '/');
        $schemas = [];

        // 1. SoftwareApplication Schema
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

        // 2. FinancialProduct Schema
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

        // 3. WebSite Schema
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

        // 4. Organization Schema
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

        // 5. Person Schema
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

        // 6. HowTo Schema
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

        return $schemas;
    }
}
