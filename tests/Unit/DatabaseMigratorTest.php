<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\DatabaseMigrator;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseMigratorTest extends TestCase
{
    private PDO $pdo;
    private string $tempMigrationsDir;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->tempMigrationsDir = sys_get_temp_dir() . '/migrator_test_' . uniqid();
        mkdir($this->tempMigrationsDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempMigrationsDir . '/*');
        if ($files) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
        if (is_dir($this->tempMigrationsDir)) {
            rmdir($this->tempMigrationsDir);
        }
    }

    public function testBootstrapCreatesSchemaMigrationsTableIdempotently(): void
    {
        $migrator = new DatabaseMigrator($this->pdo, $this->tempMigrationsDir);
        $migrator->bootstrap();
        $migrator->bootstrap(); // Idempotency check

        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='schema_migrations'");
        $this->assertNotEmpty($stmt->fetchAll());
    }

    public function testMigrateRunsPendingMigrationFiles(): void
    {
        $migrationContent = <<<'PHP'
<?php
return new class {
    public function up(PDO $pdo, bool $isSqlite): void {
        $pdo->exec("CREATE TABLE dummy_items (id INTEGER PRIMARY KEY, title TEXT);");
    }
};
PHP;
        file_put_contents($this->tempMigrationsDir . '/20260101000000_create_dummy_table.php', $migrationContent);

        $migrator = new DatabaseMigrator($this->pdo, $this->tempMigrationsDir);
        $migrator->migrate();

        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='dummy_items'");
        $this->assertNotEmpty($stmt->fetchAll());

        $stmt = $this->pdo->query("SELECT migration FROM schema_migrations");
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $this->assertContains('20260101000000_create_dummy_table.php', $migrations);
    }

    public function testMigrateRollsBackTransactionOnFailure(): void
    {
        $migrationContent = <<<'PHP'
<?php
return new class {
    public function up(PDO $pdo, bool $isSqlite): void {
        $pdo->exec("CREATE TABLE failing_table (id INTEGER PRIMARY KEY);");
        throw new RuntimeException("Migration failed midway");
    }
};
PHP;
        file_put_contents($this->tempMigrationsDir . '/20260101000001_fail_table.php', $migrationContent);

        $migrator = new DatabaseMigrator($this->pdo, $this->tempMigrationsDir);

        try {
            $migrator->migrate();
            $this->fail("Expected RuntimeException was not thrown");
        } catch (RuntimeException $e) {
            $this->assertSame("Migration failed midway", $e->getMessage());
        }

        // Verify table was rolled back
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='failing_table'");
        $this->assertEmpty($stmt->fetchAll());

        // Verify migration was not recorded
        $stmt = $this->pdo->query("SELECT migration FROM schema_migrations");
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $this->assertNotContains('20260101000001_fail_table.php', $migrations);
    }

    public function testMigrateThrowsExceptionForInvalidMigrationObject(): void
    {
        $migrationContent = <<<'PHP'
<?php
// Returns a string instead of an object with up() method
return "INVALID_MIGRATION";
PHP;
        file_put_contents($this->tempMigrationsDir . '/20260101000002_invalid_structure.php', $migrationContent);

        $migrator = new DatabaseMigrator($this->pdo, $this->tempMigrationsDir);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("must implement Core\\Database\\MigrationInterface");
        $migrator->migrate();
    }
}
