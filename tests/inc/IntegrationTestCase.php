<?php

namespace NextrasTests\Migrations;

use Dibi;
use DibiConnection;
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


	protected function setUp()
	{
		parent::setUp();

		$options = Environment::loadData();
		$driversConfig = parse_ini_file(__DIR__ . '/../drivers.ini', TRUE);
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


	protected function tearDown()
	{
		parent::tearDown();
		$cleanupDb = require $this->fixtureDir . '/cleanup.php';
		$cleanupDb = \Closure::bind($cleanupDb, $this);
		$cleanupDb();
	}


	protected function getGroups($dir)
	{
		$structures = new Group();
		$structures->enabled = TRUE;
		$structures->name = 'structures';
		$structures->directory = $dir . '/structures';
		$structures->dependencies = [];

		$basicData = new Group();
		$basicData->enabled = TRUE;
		$basicData->name = 'basic-data';
		$basicData->directory = $dir . '/basic-data';
		$basicData->dependencies = ['structures'];

		$dummyData = new Group();
		$dummyData->enabled = TRUE;
		$dummyData->name = 'dummy-data';
		$dummyData->directory = $dir . '/dummy-data';
		$dummyData->dependencies = ['structures', 'basic-data'];

		return [$structures, $basicData, $dummyData];
	}


	/**
	 * @return array (extension => IExtensionHandler)
	 */
	protected function getExtensionHandlers()
	{
		return [
			'sql' => new Nextras\Migrations\Extensions\SqlHandler($this->driver),
		];
	}


	/**
	 * @param  array $options
	 * @return IDbal
	 * @throws \Exception
	 */
	protected function createDbal($options)
	{
		switch ($options['dbal']) {
			case 'dibi':
				$drivers = [
					'mysql' => 'mysqli',
					'pgsql' => 'postgre',
				];

				$dibiConnectionClass = class_exists('Dibi\Connection') ? 'Dibi\Connection' : 'DibiConnection';
				return new DibiAdapter(new $dibiConnectionClass([
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


	/**
	 * @param  array $name
	 * @param  IDbal $dbal
	 * @return IDriver
	 */
	protected function createDriver($name, IDbal $dbal)
	{
		switch ($name) {
			case 'mysql':
				return new Nextras\Migrations\Drivers\MySqlDriver($dbal, 'm');

			case 'pgsql':
				return new Nextras\Migrations\Drivers\PgSqlDriver($dbal, 'm', $this->dbName);
		}
	}


	/**
	 * @return IPrinter
	 */
	protected function createPrinter()
	{
		return new TestPrinter();
	}

}
