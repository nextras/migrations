<?php

/**
 * @testCase
 * @dataProvider ../../../dbals.ini
 */

namespace NextrasTests\Migrations;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Tester\Assert;
use Tester\Environment;
use Tester\TestCase;


require __DIR__ . '/../../../bootstrap.php';


class SymfonyBundleTest extends TestCase
{
	/** @var KernelInterface */
	private $symfonyKernel;


	protected function setUp()
	{
		parent::setUp();

		Environment::lock(__CLASS__, __DIR__ . '/../../../temp');

		$options = Environment::loadData();
		$driversConfig = parse_ini_file(__DIR__ . '/../../../drivers.ini', true);
		$dbalOptions = $driversConfig[$options['driver']];

		$doctrineDriver = $options['driver'] === 'mysql' ? 'pdo_mysql' : 'pdo_pgsql';
		$className = PHP_VERSION_ID >= 70100 ? 'NextrasTests\Migrations\TestSymfonyKernel6' : 'NextrasTests\Migrations\TestSymfonyKernel';
		$this->symfonyKernel = new $className(__DIR__ . '/SymfonyBundleTest.yaml', [
			'doctrine_dbal_driver' => $doctrineDriver,
			'doctrine_dbal_host' => $dbalOptions['host'],
			'doctrine_dbal_database' => $dbalOptions['database'],
			'doctrine_dbal_username' => $dbalOptions['username'],
			'doctrine_dbal_password' => $dbalOptions['password'],
			'nextras_migrations_driver' => $options['driver'],
			'nextras_migrations_dir' => __DIR__ . "/../../../fixtures/$options[driver]",
		]);
	}


	public function testMigrationsReset()
	{
		$application = new Application($this->symfonyKernel);

		$command = $application->find('migrations:reset');
		$commandTester = new CommandTester($command);
		Assert::same(0, $commandTester->execute([]));
	}


	public function testMigrationsContinue()
	{
		$application = new Application($this->symfonyKernel);

		$command = $application->find('migrations:continue');
		$commandTester = new CommandTester($command);
		Assert::same(0, $commandTester->execute([]));
	}
}


(new SymfonyBundleTest)->run();
