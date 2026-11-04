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
		// schema introspection reads the catalog of the whole database, including schemas other tests are dropping
		Environment::lock('doctrine-orm', dirname(TEMP_DIR));

		parent::setUp();
		$this->driver->createTable();
		$this->entityManager = $this->createEntityManager();
		(new SchemaTool($this->entityManager))->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
		$this->createUnmappedAssets();
	}


	public function testSchemaInSync(): void
	{
		Assert::same('', $this->generateDiff());
	}


	public function testUnmappedAssetsAreNotDropped(): void
	{
		$this->connection->executeStatement('ALTER TABLE author DROP COLUMN name');
		$this->connection->executeStatement('DROP TABLE book');

		$diff = $this->generateDiff();
		Assert::contains('ALTER TABLE author ADD name VARCHAR(100) NOT NULL', $diff);
		Assert::contains('CREATE TABLE book', $diff);
		Assert::notContains('DROP', $diff);

		foreach (explode(";\n", rtrim($diff, ";\n")) as $query) {
			$this->connection->executeStatement($query);
		}

		Assert::same('', $this->generateDiff());
		Assert::true($this->createSchemaManager()->tablesExist(['m', 'legacy']));

		if ($this->connection->getDatabasePlatform()->supportsSequences()) {
			Assert::notNull($this->connection->fetchOne("SELECT to_regclass('legacy_seq')"));
		}
	}


	public function testExistingSchemaAssetsFilter(): void
	{
		$configuration = $this->connection->getConfiguration();
		$filter = static fn(): bool => false;
		$configuration->setSchemaAssetsFilter($filter);

		Assert::same('', $this->generateDiff());
		Assert::same($filter, $configuration->getSchemaAssetsFilter());
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
}


(new StructureDiffGeneratorTest)->run();
