<?php declare(strict_types = 1);

/**
 * @testCase
 */

namespace NextrasTests\Migrations;

use Nette;
use Nextras;
use Symfony;
use Tester\Assert;
use Tester\Environment;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';


class MigrationsExtensionTest extends TestCase
{
	/**
	 * @dataProvider provideCommandsData
	 */
	public function testCommands(string $config): void
	{
		$dic = $this->createContainer($config);

		Assert::type(Nextras\Migrations\Drivers\MySqlDriver::class, $dic->getByType(Nextras\Migrations\IDriver::class));
		Assert::count(3, $dic->findByType(Symfony\Component\Console\Command\Command::class));
		Assert::count(3, $dic->findByTag('kdyby.console.command'));
	}


	public function provideCommandsData(): array
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
	public function testDoctrineDiffGenerator(string $config): void
	{
		$dic = $this->createContainer($config);

		$configuration = $dic->getByType(Nextras\Migrations\IConfiguration::class);
		Assert::type(Nextras\Migrations\Configurations\Configuration::class, $configuration);

		$groups = $configuration->getGroups();
		Assert::count(3, $groups);
		Assert::type(Nextras\Migrations\Bridges\DoctrineOrm\StructureDiffGenerator::class, $groups[0]->generator);
		Assert::null($groups[1]->generator);
		Assert::null($groups[2]->generator);
	}


	public function provideDiffGeneratorData(): array
	{
		return [
			['diffGenerator.configA'],
			['diffGenerator.configB'],
		];
	}


	public function testDynamicContainerParameters(): void
	{
		$container = $this->createContainer('dynamicParameters', [
			'rootDir' => '__rootDir__',
		]);

		$config = $container->getService('migrations.configuration');
		$groups = $config->getGroups();
		Assert::same('__rootDir__/migrations/structures', $groups[0]->directory);
		Assert::same('__rootDir__/migrations/basic-data', $groups[1]->directory);
		Assert::same('__rootDir__/migrations/dummy-data', $groups[2]->directory);
	}


	public function testOptionsAsService(): void
	{
		$container = $this->createContainer('optionsAsService');

		Assert::same(
			$container->getService('dibiAdapter'),
			$container->getService('migrations.dbal')
		);
	}


	public function testMultipleRegistrations(): void
	{
		$container = $this->createContainer('multipleRegistrations');

		$configA = $container->getService('migrationsA.configuration');
		$configB = $container->getService('migrationsB.configuration');

		Assert::notSame($configA, $configB);
		Assert::count(3, $configA->getGroups());
		Assert::count(3, $configB->getGroups());
	}


	protected function createContainer(string $config, ?array $dynamicParameters = null): Nette\DI\Container
	{
		$options = parse_ini_file(__DIR__ . '/../../../drivers.ini', true)['mysql'];

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
			function (Nette\DI\Compiler $compiler) use ($config, $dibiConfig, $doctrineConfig, $dynamicParameters) {
				$compiler->addExtension('extensions', new Nette\DI\Extensions\ExtensionsExtension());
				$compiler->addConfig([
					'parameters' => [
						'dibiConfig' => $dibiConfig,
						'doctrineConfig' => $doctrineConfig,
						'doctrineDir' => __DIR__ . '/../../../fixtures/doctrine',
					]
				]);
				$compiler->loadConfig(__DIR__ . "/MigrationsExtension.$config.neon");
				if ($dynamicParameters !== null) {
					$compiler->setDynamicParameterNames(array_keys($dynamicParameters));
				}
			},
			$key
		);

		return new $className($dynamicParameters ?: []);
	}
}

(new MigrationsExtensionTest)->run();
