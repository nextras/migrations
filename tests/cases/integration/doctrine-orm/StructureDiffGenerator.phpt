<?php declare(strict_types = 1);

/** @testCase */

namespace NextrasTests\Migrations\DoctrineOrm;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\NamedObject;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\Driver\StaticPHPDriver;
use Nextras\Migrations\Bridges\DoctrineDbal\DoctrineAdapter;
use Nextras\Migrations\Bridges\DoctrineOrm\StructureDiffGenerator;
use Nextras\Migrations\Drivers\PgSqlDriver;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../../bootstrap.php';
require __DIR__ . '/../../../fixtures/doctrine-orm/SchemaEntity.php';


class StructureDiffGeneratorTest extends Tester\TestCase
{
	private $connection;
	private $entityManager;
	private $schema;
	private $otherSchema;
	private $initialFilter;


	protected function setUp(): void
	{
		if (!is_file(__DIR__ . '/../../../drivers.ini')) {
			Tester\Environment::skip('Configure tests/drivers.ini with a disposable PostgreSQL database.');
		}
		$options = parse_ini_file(__DIR__ . '/../../../drivers.ini', true)['pgsql'];
		$this->connection = DriverManager::getConnection([
			'driver' => 'pdo_pgsql',
			'host' => $options['host'],
			'dbname' => $options['database'],
			'user' => $options['username'],
			'password' => $options['password'],
		]);
		$this->initialFilter = $this->connection->getConfiguration()->getSchemaAssetsFilter();
		// Everything created by a test, including its private schemas, is rolled back.
		$this->connection->beginTransaction();
		$this->schema = 'nextras_diff_' . bin2hex(random_bytes(6));
		$this->otherSchema = $this->schema . '_other';
		$this->exec('CREATE SCHEMA ' . $this->schema);
		$this->exec('CREATE SCHEMA ' . $this->otherSchema);
		$this->exec('SET LOCAL search_path TO ' . $this->schema);
		SchemaEntity::$schema = $this->schema;
		$config = new Configuration();
		$config->setProxyDir(TEMP_DIR);
		$config->setProxyNamespace('NextrasTestsProxies');
		$config->setMetadataDriverImpl(new StaticPHPDriver([__DIR__ . '/../../../fixtures/doctrine-orm']));
		$this->entityManager = method_exists(EntityManager::class, 'create')
			? EntityManager::create($this->connection, $config)
			: new EntityManager($this->connection, $config);

		$driver = new PgSqlDriver(new DoctrineAdapter($this->connection), 'migrations', $this->schema);
		$driver->setupConnection();
		$driver->createTable();
		$this->exec("CREATE TABLE sentinel (id INT PRIMARY KEY, value TEXT NOT NULL)");
		$this->exec("INSERT INTO sentinel VALUES (1, 'keep me')");
		$this->exec('CREATE SEQUENCE sentinel_seq START 41');
		$this->exec('CREATE TABLE ' . $this->otherSchema . '.sentinel (id INT PRIMARY KEY)');
		$this->exec('CREATE SEQUENCE ' . $this->otherSchema . '.sentinel_seq START 71');
		// A matching unqualified name in a different schema is still unmapped.
		$this->exec('CREATE TABLE ' . $this->otherSchema . '.managed (id INT PRIMARY KEY)');
		$this->exec('CREATE SEQUENCE ' . $this->otherSchema . '.managed_id_seq START 81');
	}


	protected function tearDown(): void
	{
		$this->connection->rollBack();
		$this->connection->close();
	}


	public function testManagedChangesAndUnmappedAssets(): void
	{
		$generator = new StructureDiffGenerator($this->entityManager);
		$sql = $generator->generateContent();
		Assert::contains('CREATE TABLE', $sql);
		$this->exec($sql);
		$this->assertUnmappedAssets();
		$this->exec("INSERT INTO managed (id, label) VALUES (1, 'managed row')");
		// Incoming and outgoing FKs of an unmapped table must both survive.
		$this->exec('ALTER TABLE sentinel ADD CONSTRAINT sentinel_managed_fk FOREIGN KEY (id) REFERENCES managed (id)');
		$this->exec('ALTER TABLE ' . $this->otherSchema . '.sentinel ADD CONSTRAINT sentinel_external_fk FOREIGN KEY (id) REFERENCES ' . $this->schema . '.sentinel (id)');
		$this->exec('ALTER TABLE managed DROP COLUMN added');
		$this->exec('ALTER TABLE managed ALTER COLUMN label TYPE VARCHAR(12)');
		$this->exec('ALTER TABLE managed ADD obsolete INT');
		$this->exec('ALTER SEQUENCE ' . $this->schema . '.managed_id_seq INCREMENT BY 2');
		// A removed FK between managed tables is intentionally destructive, even in safe mode.
		$this->exec('ALTER TABLE managed ADD CONSTRAINT managed_self_fk FOREIGN KEY (id) REFERENCES managed (id)');
		$sql = $generator->generateContent();
		Assert::contains('ALTER TABLE', $sql);
		$this->exec($sql);
		$table = $this->managedTable();
		Assert::true($table->hasColumn('added'));
		Assert::same(60, $table->getColumn('label')->getLength());
		Assert::false($table->hasColumn('obsolete'));
		Assert::count(0, $table->getForeignKeys());
		$foundSequence = false;
		foreach ($this->schemaManager()->listSequences() as $sequence) {
			$name = $sequence instanceof NamedObject ? $sequence->getObjectName()->toString() : $sequence->getName();
			if ($name === 'managed_id_seq' || $name === $this->schema . '.managed_id_seq') {
				Assert::same(1, $sequence->getAllocationSize());
				$foundSequence = true;
			}
		}
		Assert::true($foundSequence);
		Assert::same('managed row', $this->connection->fetchOne('SELECT label FROM managed WHERE id = 1'));
		Assert::count(1, $this->schemaManager()->listTableForeignKeys($this->schema . '.sentinel'));
		Assert::count(1, $this->schemaManager()->listTableForeignKeys($this->otherSchema . '.sentinel'));
		$this->assertUnmappedAssets();
		Assert::same('', $generator->generateContent());
		Assert::same('', $generator->generateContent());
		Assert::same($this->initialFilter, $this->connection->getConfiguration()->getSchemaAssetsFilter());
		// The protection is local to this generator, not a global validation filter.
		$rawDiff = (new SchemaTool($this->entityManager))->getUpdateSchemaSql($this->entityManager->getMetadataFactory()->getAllMetadata());
		Assert::contains('DROP TABLE sentinel', implode(";\n", $rawDiff));
	}


	public function testExistingFilterIsComposedAndRestored(): void
	{
		$seen = [];
		$filter = function ($asset) use (&$seen): bool {
			$name = $asset instanceof NamedObject ? $asset->getObjectName()->toString()
				: ($asset instanceof AbstractAsset ? $asset->getName() : $asset);
			$seen[] = $name;
			// SchemaTool has always whitelisted mapped assets even if this rejects them.
			return strpos($name, 'managed') === false && strpos($name, 'migrations') === false;
		};
		$config = $this->connection->getConfiguration();
		$config->setSchemaAssetsFilter($filter);
		$generator = new StructureDiffGenerator($this->entityManager);
		$this->exec($generator->generateContent());
		Assert::same($filter, $config->getSchemaAssetsFilter());
		Assert::true(count($seen) > 0);
		$this->exec('ALTER TABLE managed DROP COLUMN added');
		$this->exec($generator->generateContent());
		Assert::true($this->managedTable()->hasColumn('added'));
		Assert::same('', $generator->generateContent());
		Assert::same($filter, $config->getSchemaAssetsFilter());
		// Restored application introspection must still see allowed unmapped assets.
		Assert::contains('sentinel', implode(' ', $this->schemaManager()->listTableNames()));
		$config->setSchemaAssetsFilter($this->initialFilter);
		$this->assertUnmappedAssets();
	}


	public function testFilterRestoredAfterIntrospectionFailure(): void
	{
		$failure = new \RuntimeException('Introspection filter failed');
		$filter = function ($asset) use ($failure): bool {
			throw $failure;
		};
		$config = $this->connection->getConfiguration();
		$config->setSchemaAssetsFilter($filter);
		$generator = new StructureDiffGenerator($this->entityManager);
		Assert::exception(function () use ($generator): void {
			$generator->generateContent();
		}, \RuntimeException::class, $failure->getMessage());
		Assert::same($filter, $config->getSchemaAssetsFilter());
	}


	public function testFilterRestoredAfterGenerationFailure(): void
	{
		$filter = function ($asset): bool { return true; };
		$config = $this->connection->getConfiguration();
		$config->setSchemaAssetsFilter($filter);
		$metadata = $this->entityManager->getClassMetadata(SchemaEntity::class);
		if (is_array($metadata->fieldMappings['label'])) {
			$metadata->fieldMappings['label']['type'] = 'nextras_unknown_type';
		} else {
			$metadata->fieldMappings['label']->type = 'nextras_unknown_type';
		}
		$generator = new StructureDiffGenerator($this->entityManager);
		try {
			$generator->generateContent();
			Assert::fail('An unknown managed column type must fail generation.');
		} catch (\Exception $e) {
			Assert::contains('nextras_unknown_type', $e->getMessage());
		}
		Assert::same($filter, $config->getSchemaAssetsFilter());
	}


	public function testLegacySafeModeRetainsNativeForeignKeySemantics(): void
	{
		if ((new \ReflectionMethod(SchemaTool::class, 'getUpdateSchemaSql'))->getNumberOfParameters() === 1) {
			Tester\Environment::skip('ORM 2 native safe-mode compatibility check.');
		}
		$metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
		$tool = new SchemaTool($this->entityManager);
		$tool->createSchema($metadata);
		$this->exec('ALTER TABLE managed ADD CONSTRAINT managed_sentinel_fk FOREIGN KEY (id) REFERENCES sentinel (id)');
		$this->exec('ALTER TABLE managed ADD obsolete INT');
		$queries = $tool->getUpdateSchemaSql($metadata, true);
		$expected = $queries ? implode(";\n", $queries) . ";\n" : '';
		$generator = new StructureDiffGenerator($this->entityManager);
		Assert::same($expected, $generator->generateContent());
		$this->exec($expected);
		Assert::false($this->managedTable()->hasColumn('obsolete'));
		Assert::count(1, $this->managedTable()->getForeignKeys());
		$this->assertUnmappedAssets();
		Assert::same('', $generator->generateContent());
	}


	public function testEmptyMetadataPreservesUnmappedAssets(): void
	{
		$this->entityManager->getConfiguration()->setMetadataDriverImpl(new class([]) extends StaticPHPDriver {
			public function getAllClassNames(): array { return []; }
		});
		$generator = new StructureDiffGenerator($this->entityManager);
		Assert::same('', $generator->generateContent());
		Assert::same('', $generator->generateContent());
		$this->assertUnmappedAssets();
		Assert::same($this->initialFilter, $this->connection->getConfiguration()->getSchemaAssetsFilter());
	}


	private function assertUnmappedAssets(): void
	{
		Assert::same('keep me', $this->connection->fetchOne('SELECT value FROM sentinel WHERE id = 1'));
		Assert::same('41', (string) $this->connection->fetchOne('SELECT last_value FROM sentinel_seq'));
		Assert::same('71', (string) $this->connection->fetchOne('SELECT last_value FROM ' . $this->otherSchema . '.sentinel_seq'));
		Assert::same('81', (string) $this->connection->fetchOne('SELECT last_value FROM ' . $this->otherSchema . '.managed_id_seq'));
		Assert::true(count($this->schemaManager()->listTableColumns($this->schema . '.migrations')) > 0);
		Assert::true(count($this->schemaManager()->listTableColumns($this->otherSchema . '.sentinel')) > 0);
		Assert::true(count($this->schemaManager()->listTableColumns($this->otherSchema . '.managed')) > 0);
		Assert::true($this->connection->fetchOne("SELECT to_regclass('" . $this->schema . ".migrations_id_seq')") !== null);
	}


	private function schemaManager()
	{
		return method_exists($this->connection, 'createSchemaManager')
			? $this->connection->createSchemaManager() : $this->connection->getSchemaManager();
	}


	private function managedTable()
	{
		$manager = $this->schemaManager();
		return method_exists($manager, 'introspectTable')
			? $manager->introspectTable($this->schema . '.managed')
			: $manager->listTableDetails($this->schema . '.managed');
	}


	private function exec(string $sql): void
	{
		$this->connection->executeStatement($sql);
	}
}

(new StructureDiffGeneratorTest())->run();
