<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\CurrencyFormatterInterface;
use Core\Twig\AppTwigExtension;
use Core\ViteHelper;
use PHPUnit\Framework\TestCase;

class AppTwigExtensionTest extends TestCase
{
    private AppTwigExtension $extension;
    private ViteHelper $viteHelper;
    private CurrencyFormatterInterface $currencyFormatter;

    protected function setUp(): void
    {
        $this->viteHelper = $this->createStub(ViteHelper::class);
        $this->currencyFormatter = new class implements CurrencyFormatterInterface {
            public function format(float|int $num): string
            {
                return '₹' . number_format($num);
            }

            public function formatDynamic(float|int $amount, string $currency = 'INR'): string
            {
                return '₹' . number_format($amount);
            }

            public function getSymbol(string $currency): string
            {
                return '₹';
            }
        };

        $this->extension = new AppTwigExtension($this->viteHelper, $this->currencyFormatter);
    }

    public function testGetFiltersContainsExpectedFilters(): void
    {
        $filters = $this->extension->getFilters();
        $names = array_map(fn($f) => $f->getName(), $filters);

        $this->assertContains('formatInr', $names);
        $this->assertContains('array_values', $names);
        $this->assertContains('json_island', $names);
    }

    public function testGetFunctionsContainsExpectedFunctions(): void
    {
        $functions = $this->extension->getFunctions();
        $names = array_map(fn($f) => $f->getName(), $functions);

        $this->assertContains('vite_asset', $names);
        $this->assertContains('vite_client', $names);
        $this->assertContains('vite_css', $names);
    }

    public function testJsonIslandFilterEscapesScriptTagsAndAmpersands(): void
    {
        $filters = $this->extension->getFilters();
        $jsonFilter = null;
        foreach ($filters as $filter) {
            if ($filter->getName() === 'json_island') {
                $jsonFilter = $filter;
                break;
            }
        }

        $this->assertNotNull($jsonFilter);
        $callable = $jsonFilter->getCallable();
        $this->assertIsCallable($callable);

        $payload = ['xss' => '</script><script>alert("1")</script>', 'amp' => 'A & B'];
        $json = $callable($payload);

        $this->assertStringNotContainsString('</script>', $json);
        $this->assertStringContainsString('\u003C\/script\u003E', $json);
        $this->assertStringContainsString('\u0026', $json);
    }

    public function testFormatInrFilterCleansStringAndFormats(): void
    {
        $filters = $this->extension->getFilters();
        $inrFilter = null;
        foreach ($filters as $filter) {
            if ($filter->getName() === 'formatInr') {
                $inrFilter = $filter;
                break;
            }
        }

        $this->assertNotNull($inrFilter);
        $callable = $inrFilter->getCallable();
        $this->assertIsCallable($callable);

        $this->assertSame('₹10,000', $callable('10,000'));
        $this->assertSame('₹500', $callable(500));
    }

    public function testArrayValuesFilterReindexesAssociativeArray(): void
    {
        $filters = $this->extension->getFilters();
        $arrayValuesFilter = null;
        foreach ($filters as $filter) {
            if ($filter->getName() === 'array_values') {
                $arrayValuesFilter = $filter;
                break;
            }
        }

        $this->assertNotNull($arrayValuesFilter);
        $callable = $arrayValuesFilter->getCallable();
        $this->assertIsCallable($callable);

        $associative = ['a' => 'apple', 'b' => 'banana'];
        $this->assertSame(['apple', 'banana'], $callable($associative));
        $this->assertSame('non_array', $callable('non_array'));
    }

    public function testCalculatorRegistryFunctionsReturnLiveCountsAndRouteDetection(): void
    {
        $functionsByName = [];
        foreach ($this->extension->getFunctions() as $fn) {
            $functionsByName[$fn->getName()] = $fn->getCallable();
        }

        $this->assertArrayHasKey('calculator_route_count', $functionsByName);
        $this->assertArrayHasKey('specialized_calculator_count', $functionsByName);
        $this->assertArrayHasKey('is_calculator_route', $functionsByName);

        $routeCountFn = $functionsByName['calculator_route_count'];
        $specCountFn = $functionsByName['specialized_calculator_count'];
        $isCalcRouteFn = $functionsByName['is_calculator_route'];

        $this->assertIsCallable($routeCountFn);
        $this->assertIsCallable($specCountFn);
        $this->assertIsCallable($isCalcRouteFn);

        $this->assertGreaterThanOrEqual(34, $routeCountFn());
        $this->assertGreaterThanOrEqual(12, $specCountFn());
        $this->assertTrue($isCalcRouteFn('home'));
        $this->assertTrue($isCalcRouteFn('sip-calculator'));
        $this->assertTrue($isCalcRouteFn('gratuity-calculator'));
        $this->assertTrue($isCalcRouteFn('income-tax-calculator'));
        $this->assertFalse($isCalcRouteFn('about'));
        $this->assertFalse($isCalcRouteFn(''));

        $taxWaterfallFn = $functionsByName['supports_tax_waterfall'];
        $postTaxFn = $functionsByName['supports_post_tax_toggle'];
        $this->assertIsCallable($taxWaterfallFn);
        $this->assertIsCallable($postTaxFn);
        $this->assertTrue($taxWaterfallFn('sip'));
        $this->assertFalse($taxWaterfallFn('inflation'));
        $this->assertTrue($postTaxFn('inflation'));
        $this->assertFalse($postTaxFn('emi'));
    }
}
