<?php declare(strict_types = 1);

namespace NextrasTests\Migrations;

use Dibi;
use Doctrine;
use Nette;
use Nextras;
use Nextras\Migrations\Bridges\Dibi\DibiAdapter;
use Nextras\Migrations\Bridges\DoctrineDbal\DoctrineAdapter;
use Nextras\Migrations\Bridges\NetteDatabase\NetteAdapter;
use Nextras\Migrations\Bridges\NextrasDbal\NextrasAdapter;
use Nextras\Migrations\Engine\Runner;
use Nextras\Migrations\Entities\Group;
use Nextras\Migrations\IDbal;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\IPrinter;
use Tester\Environment;
use Tester\TestCase;


abstract class IntegrationTestCase extends TestCase
{
	/** @var IDbal */
	protected $dbal;

	/** @var IDriver */
	protected $driver;

	/** @var IPrinter|TestPrinter */
	protected $printer;

	/** @var Runner */
	protected $runner;

	/** @var string */
	protected $dbName;

	/** @var string */
	protected $fixtureDir;


	protected function setUp(): void
	{
		parent::setUp();

		$options = Environment::loadData();
		$driversConfig = parse_ini_file(__DIR__ . '/../drivers.ini', true);
		$dbalOptions = $driversConfig[$options['driver']] + $options;

		$this->fixtureDir = __DIR__ . '/../fixtures/' . $options['driver'];
		$this->dbName = $dbalOptions['database'] . '_' . bin2hex(openssl_random_pseudo_bytes(4));
		$this->dbal = $this->createDbal($dbalOptions);

		$initDb = require $this->fixtureDir . '/init.php';
		$initDb = \Closure::bind($initDb, $this);
		$initDb();

		$this->driver = $this->createDriver($options['driver'], $this->dbal);
		$this->driver->setupConnection();

		$this->printer = $this->createPrinter();
		$this->runner = new Runner($this->driver, $this->printer);

		foreach ($this->getGroups($this->fixtureDir) as $group) {
			$this->runner->addGroup($group);
		}

		foreach ($this->getExtensionHandlers() as $ext => $handler) {
			$this->runner->addExtensionHandler($ext, $handler);
		}
	}


	protected function tearDown(): void
	{
		parent::tearDown();
		$cleanupDb = require $this->fixtureDir . '/cleanup.php';
		$cleanupDb = \Closure::bind($cleanupDb, $this);
		$cleanupDb();
	}


	/**
	 * @return list<Group>
	 */
	protected function getGroups(string $dir): array
	{
		$structures = new Group();
		$structures->enabled = true;
		$structures->name = 'structures';
		$structures->directory = $dir . '/structures';
		$structures->dependencies = [];

		$basicData = new Group();
		$basicData->enabled = true;
		$basicData->name = 'basic-data';
		$basicData->directory = $dir . '/basic-data';
		$basicData->dependencies = ['structures'];

		$dummyData = new Group();
		$dummyData->enabled = true;
		$dummyData->name = 'dummy-data';
		$dummyData->directory = $dir . '/dummy-data';
		$dummyData->dependencies = ['structures', 'basic-data'];

		return [$structures, $basicData, $dummyData];
	}


	/**
	 * @return array<string, Nextras\Migrations\IExtensionHandler> (extension => IExtensionHandler)
	 */
	protected function getExtensionHandlers(): array
	{
		return [
			'sql' => new Nextras\Migrations\Extensions\SqlHandler($this->driver),
		];
	}


	/**
	 * @throws \Exception
	 */
	protected function createDbal(array $options): IDbal
	{
		switch ($options['dbal']) {
			case 'dibi':
				$drivers = [
					'mysql' => 'mysqli',
					'pgsql' => 'postgre',
				];

				return new DibiAdapter(new Dibi\Connection([
					'host' => $options['host'],
					'username' => $options['username'],
					'password' => $options['password'],
					'database' => $options['database'],
					'driver' => $drivers[$options['driver']],
				]));


			case 'doctrine':
				$drivers = [
					'mysql' => 'mysqli',
					'pgsql' => 'pdo_pgsql',
				];
				return new DoctrineAdapter(Doctrine\DBAL\DriverManager::getConnection([
					'host' => $options['host'],
					'user' => $options['username'],
					'password' => $options['password'],
					'database' => $options['database'],
					'driver' => $drivers[$options['driver']],
				]));

			case 'nette':
				return new NetteAdapter(new Nette\Database\Connection(
					"$options[driver]:host=$options[host];dbname=$options[database]",
					$options['username'],
					$options['password']
				));

			case 'nextras':
				$drivers = [
					'mysql' => 'mysqli',
					'pgsql' => 'pgsql',
				];
				return new NextrasAdapter(new Nextras\Dbal\Connection([
					'host' => $options['host'],
					'username' => $options['username'],
					'password' => $options['password'],
					'database' => $options['database'],
					'driver' => $drivers[$options['driver']],
				]));

			default:
				throw new \Exception("Unknown DBAL '$options[dbal]'.");
		}
	}


	protected function createDriver(string $name, IDbal $dbal): IDriver
	{
		switch ($name) {
			case 'mysql':
				return new Nextras\Migrations\Drivers\MySqlDriver($dbal, 'm');

			case 'pgsql':
				return new Nextras\Migrations\Drivers\PgSqlDriver($dbal, 'm', $this->dbName);

			default:
				throw new \Exception("Unknown driver '$name'.");
		}
	}


	protected function createPrinter(): IPrinter
	{
		return new TestPrinter();
	}

}
