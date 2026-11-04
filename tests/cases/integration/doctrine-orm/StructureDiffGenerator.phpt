<?php declare(strict_types = 1);

/**
 * @testCase
 * @dataProvider ../../../dbals.ini
 */

namespace NextrasTests\Migrations;

use Doctrine;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Nextras\Migrations\Bridges\DoctrineDbal\DoctrineAdapter;
use Nextras\Migrations\Bridges\DoctrineOrm\StructureDiffGenerator;
use Nextras\Migrations\IDbal;
use Tester\Assert;
use Tester\Environment;

require __DIR__ . '/../../../bootstrap.php';


class StructureDiffGeneratorTest extends IntegrationTestCase
{
	private Doctrine\DBAL\Connection $connection;

	private EntityManager $entityManager;


	protected function setUp(): void
	{
		// test cases run in parallel, each in its own PostgreSQL schema; introspection reads the catalog of the whole
		// database and fails when another test case drops its schema at the same time
		Environment::lock(__CLASS__, __DIR__ . '/../../../temp');

		parent::setUp();
		$this->driver->createTable();
		$this->entityManager = $this->createEntityManager();
		(new SchemaTool($this->entityManager))->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
		$this->createUnmappedAssets();
	}


	public function testSchemaInSync(): void
	{
		if ($this->connection->getDatabasePlatform()->supportsSequences()) {
			Assert::notNull($this->connection->fetchOne("SELECT to_regclass('book_id_seq')")); // mapped sequence
		}

		Assert::same('', $this->generateDiff());
	}


	public function testUnmappedAssetsAreNotDropped(): void
	{
		$this->connection->executeStatement('ALTER TABLE author DROP COLUMN name');
		$this->connection->executeStatement('DROP TABLE book');

		$diff = $this->generateDiff();
		Assert::match('~ALTER TABLE \W?author\W? ADD \W?name\W?~', $diff);
		Assert::match('~CREATE TABLE \W?book\W?~', $diff);
		Assert::notContains('DROP', $diff);

		$this->executeDiff($diff);
		Assert::same('', $this->generateDiff());
		Assert::true($this->createSchemaManager()->tablesExist(['m', 'legacy']));

		if ($this->connection->getDatabasePlatform()->supportsSequences()) {
			Assert::notNull($this->connection->fetchOne("SELECT to_regclass('legacy_seq')"));
		}
	}


	public function testSchemaAssetsFilterIsRestored(): void
	{
		$configuration = $this->connection->getConfiguration();
		$filter = static fn(): bool => true;
		$configuration->setSchemaAssetsFilter($filter);

		Assert::same('', $this->generateDiff());
		Assert::same($filter, $configuration->getSchemaAssetsFilter());
	}


	public function testSchemaListenerSeesExistingTables(): void
	{
		$this->connection->executeStatement('CREATE TABLE feature_flag (id INT PRIMARY KEY)');
		$this->entityManager->getEventManager()->addEventListener([Doctrine\ORM\Tools\ToolEvents::postGenerateSchema], new class {
			public function postGenerateSchema(Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs $args): void
			{
				$connection = $args->getEntityManager()->getConnection();
				$schemaManager = method_exists($connection, 'createSchemaManager')
					? $connection->createSchemaManager()
					: $connection->getSchemaManager();

				if ($schemaManager->tablesExist(['feature_flag'])) {
					$args->getSchema()->createTable('audit_log')->addColumn('id', 'integer');
				}
			}
		});

		$diff = $this->generateDiff();
		Assert::match('~CREATE TABLE \W?audit_log\W?~', $diff);
		Assert::notContains('DROP', $diff);

		$this->executeDiff($diff);
		Assert::same('', $this->generateDiff());
	}


	protected function createDbal(array $options): IDbal
	{
		$this->connection = $this->createDoctrineConnection($options);
		return new DoctrineAdapter($this->connection);
	}


	private function createEntityManager(): EntityManager
	{
		$configuration = new Doctrine\ORM\Configuration();
		$configuration->setMetadataDriverImpl(new Doctrine\ORM\Mapping\Driver\AttributeDriver([__DIR__ . '/../../../fixtures/doctrine-orm']));
		$configuration->setProxyDir(TEMP_DIR);
		$configuration->setProxyNamespace('NextrasTests\Migrations\Proxies');

		// AUTO ids use a sequence on PostgreSQL (ORM 3 defaults to identity columns) and AUTO_INCREMENT on MySQL
		$configuration->setIdentityGenerationPreferences([
			Doctrine\DBAL\Platforms\PostgreSQLPlatform::class => Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_SEQUENCE,
		]);

		if (PHP_VERSION_ID >= 80400 && method_exists($configuration, 'enableNativeLazyObjects')) {
			$configuration->enableNativeLazyObjects(true);
		}

		return method_exists(EntityManager::class, 'create')
			? EntityManager::create($this->connection, $configuration)
			: new EntityManager($this->connection, $configuration);
	}


	private function createUnmappedAssets(): void
	{
		$this->connection->executeStatement('CREATE TABLE legacy (id INT PRIMARY KEY, author_id INT, FOREIGN KEY (author_id) REFERENCES author (id))');

		if ($this->connection->getDatabasePlatform()->supportsSequences()) {
			$this->connection->executeStatement('CREATE SEQUENCE legacy_seq');
		}
	}


	private function createSchemaManager(): Doctrine\DBAL\Schema\AbstractSchemaManager
	{
		return method_exists($this->connection, 'createSchemaManager')
			? $this->connection->createSchemaManager()
			: $this->connection->getSchemaManager();
	}


	private function generateDiff(): string
	{
		return (new StructureDiffGenerator($this->entityManager))->generateContent();
	}


	private function executeDiff(string $diff): void
	{
		foreach (explode(";\n", rtrim($diff, ";\n")) as $query) {
			$this->connection->executeStatement($query);
		}
	}
}


(new StructureDiffGeneratorTest)->run();
