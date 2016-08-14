<?php

/**
 * @testCase
 */

namespace NextrasTests\Migrations;

use Nette;
use Nextras\Migrations\Bridges\NetteDI\MigrationsExtension;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';


class MigrationsExtensionTest extends TestCase
{
	/**
	 * @dataProvider provideCommandsData
	 */
	public function testCommands($config)
	{
		$dic = $this->createContainer($config);

		Assert::type('Nextras\Migrations\Drivers\MySqlDriver', $dic->getByType('Nextras\Migrations\IDriver'));
		Assert::count(3, $dic->findByType('Symfony\Component\Console\Command\Command'));
		Assert::count(3, $dic->findByTag('kdyby.console.command'));
	}


	public function provideCommandsData()
	{
		return [
			['configA'],
			['configB'],
			['configC'],
			['configD'],
			['configE'],
			['configF'],
		];
	}


	/**
	 * @dataProvider provideDiffGeneratorData
	 */
	public function testDoctrineDiffGenerator($config)
	{
		$dic = $this->createContainer($config);

		$configuration = $dic->getByType('Nextras\Migrations\IConfiguration');
		Assert::type('Nextras\Migrations\Configurations\DefaultConfiguration', $configuration);

		$groups = $configuration->getGroups();
		Assert::count(3, $groups);
		Assert::type('Nextras\Migrations\Bridges\DoctrineOrm\StructureDiffGenerator', $groups[0]->generator);
		Assert::null($groups[1]->generator);
		Assert::null($groups[2]->generator);
	}


	public function provideDiffGeneratorData()
	{
		return [
			['diffGenerator.configA'],
			['diffGenerator.configB'],
		];
	}


	/**
	 * @param  string $config
	 * @return Nette\DI\Container
	 */
	protected function createContainer($config)
	{
		$options = parse_ini_file(__DIR__ . '/../../drivers.ini', TRUE)['mysql'];

		$dibiConfig = [
			'host' => $options['host'],
			'username' => $options['username'],
			'password' => $options['password'],
			'database' => $options['database'],
			'driver' => 'mysqli',
		];

		$doctrineConfig = [
			'host' => $options['host'],
			'user' => $options['username'],
			'password' => $options['password'],
			'dbname' => $options['database'],
			'driver' => 'pdo_mysql',
		];

		$loader = new Nette\DI\ContainerLoader(TEMP_DIR);
		$key = __FILE__ . ':' . __LINE__ . ':' . $config;
		$className = $loader->load(
			function (Nette\DI\Compiler $compiler) use ($config, $dibiConfig, $doctrineConfig) {
				$compiler->addExtension('migrations', new MigrationsExtension());
				$compiler->addConfig([
					'parameters' => [
						'dibiConfig' => $dibiConfig,
						'doctrineConfig' => $doctrineConfig,
						'doctrineDir' => __DIR__ . '/../../fixtures/doctrine',
					]
				]);
				$compiler->loadConfig(__DIR__ . "/MigrationsExtension.$config.neon");
			},
			$key
		);

		return new $className;
	}
}

(new MigrationsExtensionTest)->run();
