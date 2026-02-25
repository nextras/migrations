<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\NetteDI;

use Doctrine;
use Dibi;
use Nette;
use Nette\DI\ContainerBuilder;
use Nette\DI\ServiceDefinition;
use Nette\DI\Statement;
use Nette\Utils\Validators;
use Nextras;
use Symfony;


class MigrationsExtension extends Nette\DI\CompilerExtension
{
	/** attributes = ['for' => names of target migration extensions] */
	const TAG_GROUP = 'nextras.migrations.group';

	/** attributes = ['for' => names of target migration extensions, 'extension' => name of handled file extension] */
	const TAG_EXTENSION_HANDLER = 'nextras.migrations.extensionHandler';

	/** @var array<string, mixed> */
	public array $defaults = [
		'dir' => null,
		'phpParams' => [],
		'driver' => null,
		'dbal' => null,
		'printer' => 'console',
		'groups' => null,        // null|array
		'diffGenerator' => true, // false|doctrine
		'withDummyData' => false,
		'ignoredQueriesFile' => null,
	];

	/** @var array<string, class-string> */
	protected array $dbals = [
		'dibi' => Nextras\Migrations\Bridges\Dibi\DibiAdapter::class,
		'doctrine' => Nextras\Migrations\Bridges\DoctrineDbal\DoctrineAdapter::class,
		'nette' => Nextras\Migrations\Bridges\NetteDatabase\NetteAdapter::class,
		'nextras' => Nextras\Migrations\Bridges\NextrasDbal\NextrasAdapter::class,
	];

	/** @var array<string, class-string> */
	protected array $drivers = [
		'mysql' => Nextras\Migrations\Drivers\MySqlDriver::class,
		'pgsql' => Nextras\Migrations\Drivers\PgSqlDriver::class,
	];

	/** @var array<string, class-string> */
	protected array $printers = [
		'console' => Nextras\Migrations\Printers\Console::class,
		'psrLog' => Nextras\Migrations\Bridges\PsrLog\PsrLogPrinter::class,
	];


	public function loadConfiguration(): void
	{
		$config = $this->validateConfig($this->defaults);

		// dbal
		Validators::assertField($config, 'dbal', 'null|string|Nette\DI\Statement');
		$dbal = $this->getDbalDefinition($config['dbal']);

		// driver
		Validators::assertField($config, 'driver', 'null|string|Nette\DI\Statement');
		$driver = $this->getDriverDefinition($config['driver'], $dbal);

		// printer
		Validators::assertField($config, 'printer', 'null|string|Nette\DI\Statement');
		$printer = $this->getPrinterDefinition($config['printer']);

		// diffGenerator
		if ($config['diffGenerator'] === 'doctrine') {
			Validators::assertField($config, 'ignoredQueriesFile', 'null|string');
			$this->createDoctrineStructureDiffGeneratorDefinition($config['ignoredQueriesFile']);
		}

		// groups
		if ($config['groups'] === null) {
			Validators::assertField($config, 'dir', 'string|Nette\PhpGenerator\PhpLiteral');
			Validators::assertField($config, 'withDummyData', 'bool');
			$config['groups'] = $this->createDefaultGroupConfiguration($config['dir'], $config['withDummyData']);
		}

		Validators::assertField($config, 'groups', 'array');
		$groups = $this->createGroupDefinitions($config['groups']);

		// extensionHandlers
		Validators::assertField($config, 'phpParams', 'array');
		$extensionHandlers = $this->createExtensionHandlerDefinitions($driver, $config['phpParams']);

		// configuration
		$configuration = $this->createConfigurationDefinition();

		// commands
		if (class_exists(Symfony\Component\Console\Command\Command::class)) {
			$this->createSymfonyCommandDefinitions($driver, $configuration, $printer);
		}
	}


	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		// dbal
		foreach ($builder->findByType(Nextras\Migrations\IDbal::class) as $def) {
			$factory = $def->getFactory();
			if ($factory->getEntity() !== Nextras\Migrations\Bridges\Dibi\DibiAdapter::class) {
				continue;
			}

			$conn = $builder->getByType(Dibi\Connection::class);
			if (!$conn) {
				continue;
			}

			$factory->arguments = ["@$conn"];
		}

		// diff generator
		if ($config['diffGenerator'] === true) {
			if ($builder->findByType(Doctrine\ORM\EntityManagerInterface::class) && $builder->hasDefinition($this->prefix('group.structures'))) {
				Validators::assertField($config, 'ignoredQueriesFile', 'null|string');
				$diffGenerator = $this->createDoctrineStructureDiffGeneratorDefinition($config['ignoredQueriesFile']);
				$builder->getDefinition($this->prefix('group.structures'))
					->addSetup('$generator', [$diffGenerator]);
			}
		}

		// configuration
		$groups = [];
		foreach ($builder->findByTag(self::TAG_GROUP) as $serviceName => $tagAttributes) {
			if (!isset($tagAttributes['for']) || in_array($this->name, $tagAttributes['for'], true)) {
				$groups[] = $builder->getDefinition($serviceName);
			}
		}

		$extensionHandlers = [];
		foreach ($builder->findByTag(self::TAG_EXTENSION_HANDLER) as $serviceName => $tagAttributes) {
			if (!isset($tagAttributes['for']) || in_array($this->name, $tagAttributes['for'], true)) {
				$extensionName = is_string($tagAttributes) ? $tagAttributes : $tagAttributes['extension'];
				$extensionHandlers[$extensionName] = $builder->getDefinition($serviceName);
			}
		}

		$builder->getDefinition($this->prefix('configuration'))
			->setArguments([$groups, $extensionHandlers]);
	}


	private function getDbalDefinition(null|string|Statement $dbal): string|ServiceDefinition
	{
		$factory = $this->getDbalFactory($dbal);

		if ($factory) {
			return $this->getContainerBuilder()
				->addDefinition($this->prefix('dbal'))
				->setType(Nextras\Migrations\IDbal::class)
				->setFactory($factory);

		} elseif ($dbal === null) {
			return '@Nextras\Migrations\IDbal';

		} else {
			throw new Nextras\Migrations\LogicException('Invalid dbal value');
		}
	}


	private function getDbalFactory(null|string|Statement $dbal): string|Statement|null
	{
		if ($dbal instanceof Statement) {
			return $this->filterArguments([$dbal])[0];

		} elseif (is_string($dbal) && isset($this->dbals[$dbal])) {
			return $this->dbals[$dbal];

		} elseif (is_string($dbal) && str_starts_with($dbal, '@')) {
			return $dbal;

		} else {
			return null;
		}
	}


	private function getDriverDefinition(null|string|Statement $driver, string|ServiceDefinition $dbal): string|ServiceDefinition
	{
		$factory = $this->getDriverFactory($driver, $dbal);

		if ($factory) {
			return $this->getContainerBuilder()
				->addDefinition($this->prefix('driver'))
				->setType(Nextras\Migrations\IDriver::class)
				->setFactory($factory);

		} elseif ($driver === null) {
			return '@Nextras\Migrations\IDriver';

		} else {
			throw new Nextras\Migrations\LogicException('Invalid driver value.');
		}
	}


	private function getDriverFactory(null|string|Statement $driver, string|ServiceDefinition $dbal): string|Statement|null
	{
		if ($driver instanceof Statement) {
			return $this->filterArguments([$driver])[0];

		} elseif (is_string($driver) && isset($this->drivers[$driver])) {
			return new Statement($this->drivers[$driver], [$dbal]);

		} else {
			return null;
		}
	}


	private function getPrinterDefinition(null|string|Statement $printer): string|ServiceDefinition
	{
		$factory = $this->getPrinterFactory($printer);

		if ($factory) {
			return $this->getContainerBuilder()
				->addDefinition($this->prefix('printer'))
				->setType(Nextras\Migrations\IPrinter::class)
				->setFactory($factory);

		} elseif ($printer === null) {
			return '@Nextras\Migrations\IPrinter';

		} else {
			throw new Nextras\Migrations\LogicException('Invalid printer value');
		}
	}


	private function getPrinterFactory(null|string|Statement $printer): string|Statement|null
	{
		if ($printer instanceof Statement) {
			return $this->filterArguments([$printer])[0];

		} elseif (is_string($printer) && isset($this->printers[$printer])) {
			return $this->printers[$printer];

		} elseif (is_string($printer) && str_starts_with($printer, '@')) {
			return $printer;

		} else {
			return null;
		}
	}


	/**
	 * @return array<string, array{enabled?: bool, directory: string, dependencies?: list<string>, generator?: ServiceDefinition|null}>
	 */
	private function createDefaultGroupConfiguration(string|Nette\PhpGenerator\PhpLiteral $dir, bool $withDummyData): array
	{
		if ($dir instanceof Nette\PhpGenerator\PhpLiteral) {
			$append = fn(string $path): Nette\PhpGenerator\PhpLiteral => ContainerBuilder::literal('? . ?', [$dir, $path]);

		} else {
			$append = fn(string $path): string => $dir . $path;
		}

		$builder = $this->getContainerBuilder();

		$groups = [
			'structures' => [
				'directory' => $append('/structures'),
			],
			'basic-data' => [
				'directory' => $append('/basic-data'),
				'dependencies' => ['structures'],
			],
			'dummy-data' => [
				'enabled' => $withDummyData,
				'directory' => $append('/dummy-data'),
				'dependencies' => ['structures', 'basic-data'],
			],
		];

		foreach ($groups as $groupName => $groupConfig) {
			$serviceName = $this->prefix("diffGenerator.$groupName");
			$diffGenerator = $builder->hasDefinition($serviceName) ? $builder->getDefinition($serviceName) : null;
			$groups[$groupName]['generator'] = $diffGenerator;
		}

		return $groups;
	}


	/**
	 * @param  array<string, array{enabled?: bool, directory: string, dependencies?: list<string>, generator?: ServiceDefinition}> $groups
	 * @return list<ServiceDefinition>
	 */
	private function createGroupDefinitions(array $groups): array
	{
		$builder = $this->getContainerBuilder();
		$groupDefinitions = [];

		foreach ($groups as $groupName => $groupConfig) {
			Validators::assertField($groupConfig, 'directory', 'string|Nette\PhpGenerator\PhpLiteral');

			$enabled = $groupConfig['enabled'] ?? true;
			$directory = $groupConfig['directory'];
			$dependencies = $groupConfig['dependencies'] ?? [];
			$generator = $groupConfig['generator'] ?? null;

			$serviceName = lcfirst(str_replace('-', '', ucwords($groupName, '-')));
			$groupDefinitions[] = $builder->addDefinition($this->prefix("group.$serviceName"))
				->addTag(self::TAG_GROUP, ['for' => [$this->name]])
				->setAutowired(false)
				->setType(Nextras\Migrations\Entities\Group::class)
				->addSetup('$name', [$groupName])
				->addSetup('$enabled', [$enabled])
				->addSetup('$directory', [$directory])
				->addSetup('$dependencies', [$dependencies])
				->addSetup('$generator', [$generator]);
		}

		return $groupDefinitions;
	}


	/**
	 * @param  array<string, mixed>     $phpParams
	 * @return list<ServiceDefinition>
	 */
	private function createExtensionHandlerDefinitions(string|ServiceDefinition $driver, array $phpParams): array
	{
		$builder = $this->getContainerBuilder();

		$sqlHandler = $builder->addDefinition($this->prefix('extensionHandler.sql'))
			->addTag(self::TAG_EXTENSION_HANDLER, ['for' => [$this->name], 'extension' => 'sql'])
			->setAutowired(false)
			->setType(Nextras\Migrations\Extensions\SqlHandler::class)
			->setArguments([$driver]);

		$phpHandler = $builder->addDefinition($this->prefix('extensionHandler.php'))
			->addTag(self::TAG_EXTENSION_HANDLER, ['for' => [$this->name], 'extension' => 'php'])
			->setType(Nextras\Migrations\Extensions\PhpHandler::class)
			->setAutowired(false)
			->setArguments([$phpParams]);

		return [$sqlHandler, $phpHandler];
	}


	private function createConfigurationDefinition(): ServiceDefinition
	{
		return $this->getContainerBuilder()
			->addDefinition($this->prefix('configuration'))
			->setType(Nextras\Migrations\IConfiguration::class)
			->setFactory(Nextras\Migrations\Configurations\Configuration::class);
	}


	private function createDoctrineStructureDiffGeneratorDefinition(?string $ignoredQueriesFile): ServiceDefinition
	{
		$builder = $this->getContainerBuilder();

		return $builder->addDefinition($this->prefix('diffGenerator.structures'))
			->setAutowired(false)
			->setType(Nextras\Migrations\IDiffGenerator::class)
			->setFactory(Nextras\Migrations\Bridges\DoctrineOrm\StructureDiffGenerator::class)
			->setArguments(['@Doctrine\ORM\EntityManagerInterface', $ignoredQueriesFile]);
	}


	private function createSymfonyCommandDefinitions(string|ServiceDefinition $driver, string|ServiceDefinition $configuration, string|ServiceDefinition $printer): void
	{
		$builder = $this->getContainerBuilder();
		$builder->addExcludedClasses([Nextras\Migrations\Bridges\SymfonyConsole\BaseCommand::class]);

		$builder->addDefinition($this->prefix('continueCommand'))
			->setType(Nextras\Migrations\Bridges\SymfonyConsole\ContinueCommand::class)
			->setArguments([$driver, $configuration, $printer])
			->addTag('kdyby.console.command');

		$builder->addDefinition($this->prefix('createCommand'))
			->setType(Nextras\Migrations\Bridges\SymfonyConsole\CreateCommand::class)
			->setArguments([$driver, $configuration, $printer])
			->addTag('kdyby.console.command');

		$builder->addDefinition($this->prefix('resetCommand'))
			->setType(Nextras\Migrations\Bridges\SymfonyConsole\ResetCommand::class)
			->setArguments([$driver, $configuration, $printer])
			->addTag('kdyby.console.command');
	}


	private function filterArguments(array $arguments): array
	{
		if (method_exists(Nette\DI\Helpers::class, 'filterArguments')) {
			return Nette\DI\Helpers::filterArguments($arguments);

		} elseif (method_exists(Nette\DI\Compiler::class, 'filterArguments')) {
			return Nette\DI\Compiler::filterArguments($arguments);

		} else {
			throw new Nextras\Migrations\LogicException();
		}
	}
}
