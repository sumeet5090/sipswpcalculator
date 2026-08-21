<?php

declare(strict_types=1);

namespace Core\Providers;

use Core\AnonymizedInsightLogger;
use Core\BlogRepository;
use Core\Container;
use Core\ContentManager;
use Core\Env;
use Core\Factories\HomeSchemaBuilder;
use Core\Factories\SchemaFactory;
use Core\FaqRepository;
use Core\GlossaryRepository;
use Core\InsightRepository;
use Core\MetaManager;
use Core\SchemaHelper;
use Core\SiteConfig;
use PDO;
use Services\ConfigService;
use Services\TelemetryPruningService;

class RepositoryServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container, array $config = []): void
    {
        $container->singleton(MetaManager::class, function (Container $c) {
            return new MetaManager(
                $c->get(SiteConfig::class),
                'content/meta_pages.json',
                $c->get(ConfigService::class)
            );
        });

        $container->singleton(SchemaHelper::class, function (Container $c) {
            return new SchemaHelper(
                $c->get(SiteConfig::class),
                (string) Env::get('SITE_AUTHOR_NAME', 'Sumeet Boga'),
                (string) Env::get('SITE_ORG_NAME', 'SIP SWP Calculator')
            );
        });

        $container->singleton(FaqRepository::class, function (Container $c) {
            return new FaqRepository(
                'content/faqs.json',
                [],
                $c->get(ConfigService::class)
            );
        });

        $container->singleton(GlossaryRepository::class, function (Container $c) {
            return new GlossaryRepository(
                'content/glossary.json',
                $c->get(ConfigService::class)
            );
        });

        $container->singleton(BlogRepository::class, function (Container $c) {
            return new BlogRepository($c->get(ContentManager::class), null, $c->get(ConfigService::class));
        });

        $container->singleton(InsightRepository::class, function (Container $c) {
            /** @var ConfigService $configService */
            $configService = $c->get(ConfigService::class);
            $bucketConfig = $configService->getJsonConfig('content/dashboard_buckets.json');
            return new InsightRepository($c->get(PDO::class), $bucketConfig);
        });

        $container->singleton(TelemetryPruningService::class, function (Container $c) {
            return new TelemetryPruningService($c->get(PDO::class));
        });

        $container->singleton(AnonymizedInsightLogger::class, function (Container $c) {
            return new AnonymizedInsightLogger(
                $c->get(PDO::class),
                $c->get(TelemetryPruningService::class)
            );
        });

        $container->singleton(HomeSchemaBuilder::class, function (Container $c) {
            return new HomeSchemaBuilder(
                $c->get(SiteConfig::class)
            );
        });

        $container->singleton(SchemaFactory::class, function (Container $c) {
            return new SchemaFactory(
                $c->get(SchemaHelper::class),
                $c->get(SiteConfig::class),
                $c->get(BlogRepository::class),
                $c->get(ContentManager::class),
                $c->get(HomeSchemaBuilder::class)
            );
        });
    }
}
