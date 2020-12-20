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
use Nette\Utils\Strings;
use Nette\Utils\Validators;
use Nextras;
use Symfony;


class MigrationsExtension extends Nette\DI\CompilerExtension
{
	/** attributes = ['for' => names of target migration extensions] */
	const TAG_GROUP = 'nextras.migrations.group';

	/** attributes = ['for' => names of target migration extensions, 'extension' => name of handled file extension] */
	const TAG_EXTENSION_HANDLER = 'nextras.migrations.extensionHandler';

	/** @var array */
	public $defaults = [
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

	/** @var array */
	protected $dbals = [
		'dibi' => Nextras\Migrations\Bridges\Dibi\DibiAdapter::class,
		'dibi3' => Nextras\Migrations\Bridges\Dibi\Dibi3Adapter::class,
		'dibi4' => Nextras\Migrations\Bridges\Dibi\Dibi3Adapter::class,
		'doctrine' => Nextras\Migrations\Bridges\DoctrineDbal\DoctrineAdapter::class,
		'nette' => Nextras\Migrations\Bridges\NetteDatabase\NetteAdapter::class,
		'nextras' => Nextras\Migrations\Bridges\NextrasDbal\NextrasAdapter::class,
	];

	/** @var array */
	protected $drivers = [
		'mysql' => Nextras\Migrations\Drivers\MySqlDriver::class,
		'pgsql' => Nextras\Migrations\Drivers\PgSqlDriver::class,
	];

	/** @var array */
	protected $printers = [
		'console' => Nextras\Migrations\Printers\Console::class,
		'psrLog' => Nextras\Migrations\Bridges\PsrLog\PsrLogPrinter::class,
	];


	public function loadConfiguration()
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


	public function beforeCompile()
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


	private function getDbalDefinition($dbal)
	{
		$factory = $this->getDbalFactory($dbal);

		if ($factory) {
			return $this->getContainerBuilder()
				->addDefinition($this->prefix('dbal'))
				->setClass(Nextras\Migrations\IDbal::class)
				->setFactory($factory);

		} elseif ($dbal === null) {
			return '@Nextras\Migrations\IDbal';

		} else {
			throw new Nextras\Migrations\LogicException('Invalid dbal value');
		}
	}


	private function getDbalFactory($dbal)
	{
		if ($dbal instanceof Nette\DI\Statement) {
			return $this->filterArguments([$dbal])[0];

		} elseif (is_string($dbal) && isset($this->dbals[$dbal])) {
			return $this->dbals[$dbal];

		} elseif (is_string($dbal) && Strings::startsWith($dbal, '@')) {
			return $dbal;

		} else {
			return null;
		}
	}


	private function getDriverDefinition($driver, $dbal)
	{
		$factory = $this->getDriverFactory($driver, $dbal);

		if ($factory) {
			return $this->getContainerBuilder()
				->addDefinition($this->prefix('driver'))
				->setClass(Nextras\Migrations\IDriver::class)
				->setFactory($factory);

		} elseif ($driver === null) {
			return '@Nextras\Migrations\IDriver';

		} else {
			throw new Nextras\Migrations\LogicException('Invalid driver value.');
		}
	}


	private function getDriverFactory($driver, $dbal)
	{
		if ($driver instanceof Nette\DI\Statement) {
			return $this->filterArguments([$driver])[0];

		} elseif (is_string($driver) && isset($this->drivers[$driver])) {
			return new Nette\DI\Statement($this->drivers[$driver], [$dbal]);

		} else {
			return null;
		}
	}


	private function getPrinterDefinition($printer)
	{
		$factory = $this->getPrinterFactory($printer);

		if ($factory) {
			return $this->getContainerBuilder()
				->addDefinition($this->prefix('printer'))
				->setClass(Nextras\Migrations\IPrinter::class)
				->setFactory($factory);

		} elseif ($printer === null) {
			return '@Nextras\Migrations\IPrinter';

		} else {
			throw new Nextras\Migrations\LogicException('Invalid printer value');
		}
	}


	private function getPrinterFactory($printer)
	{
		if ($printer instanceof Nette\DI\Statement) {
			return $this->filterArguments([$printer])[0];

		} elseif (is_string($printer) && isset($this->printers[$printer])) {
			return $this->printers[$printer];

		} elseif (is_string($printer) && Strings::startsWith($printer, '@')) {
			return $printer;

		} else {
			return null;
		}
	}


	private function createDefaultGroupConfiguration($dir, $withDummyData)
	{
		if ($dir instanceof Nette\PhpGenerator\PhpLiteral) {
			$append = function ($path) use ($dir) {
				return ContainerBuilder::literal('? . ?', [$dir, $path]);
			};

		} else {
			$append = function ($path) use ($dir) {
				return $dir . $path;
			};
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


	private function createGroupDefinitions(array $groups)
	{
		$builder = $this->getContainerBuilder();
		$groupDefinitions = [];

		foreach ($groups as $groupName => $groupConfig) {
			Validators::assertField($groupConfig, 'directory', 'string|Nette\PhpGenerator\PhpLiteral');

			$enabled = isset($groupConfig['enabled']) ? $groupConfig['enabled'] : true;
			$directory = $groupConfig['directory'];
			$dependencies = isset($groupConfig['dependencies']) ? $groupConfig['dependencies'] : [];
			$generator = isset($groupConfig['generator']) ? $groupConfig['generator'] : null;

			$serviceName = lcfirst(str_replace('-', '', ucwords($groupName, '-')));
			$groupDefinitions[] = $builder->addDefinition($this->prefix("group.$serviceName"))
				->addTag(self::TAG_GROUP, ['for' => [$this->name]])
				->setAutowired(false)
				->setClass(Nextras\Migrations\Entities\Group::class)
				->addSetup('$name', [$groupName])
				->addSetup('$enabled', [$enabled])
				->addSetup('$directory', [$directory])
				->addSetup('$dependencies', [$dependencies])
				->addSetup('$generator', [$generator]);
		}

		return $groupDefinitions;
	}


	private function createExtensionHandlerDefinitions($driver, $phpParams)
	{
		$builder = $this->getContainerBuilder();

		$sqlHandler = $builder->addDefinition($this->prefix('extensionHandler.sql'))
			->addTag(self::TAG_EXTENSION_HANDLER, ['for' => [$this->name], 'extension' => 'sql'])
			->setAutowired(false)
			->setClass(Nextras\Migrations\Extensions\SqlHandler::class)
			->setArguments([$driver]);

		$phpHandler = $builder->addDefinition($this->prefix('extensionHandler.php'))
			->addTag(self::TAG_EXTENSION_HANDLER, ['for' => [$this->name], 'extension' => 'php'])
			->setClass(Nextras\Migrations\Extensions\PhpHandler::class)
			->setAutowired(false)
			->setArguments([$phpParams]);

		return [$sqlHandler, $phpHandler];
	}


	private function createConfigurationDefinition()
	{
		return $this->getContainerBuilder()
			->addDefinition($this->prefix('configuration'))
			->setClass(Nextras\Migrations\IConfiguration::class)
			->setFactory(Nextras\Migrations\Configurations\Configuration::class);
	}


	private function createDoctrineStructureDiffGeneratorDefinition($ignoredQueriesFile)
	{
		$builder = $this->getContainerBuilder();

		return $builder->addDefinition($this->prefix('diffGenerator.structures'))
			->setAutowired(false)
			->setClass(Nextras\Migrations\IDiffGenerator::class)
			->setFactory(Nextras\Migrations\Bridges\DoctrineOrm\StructureDiffGenerator::class)
			->setArguments(['@Doctrine\ORM\EntityManagerInterface', $ignoredQueriesFile]);
	}


	private function createSymfonyCommandDefinitions($driver, $configuration, $printer)
	{
		$builder = $this->getContainerBuilder();
		$builder->addExcludedClasses([Nextras\Migrations\Bridges\SymfonyConsole\BaseCommand::class]);

		$builder->addDefinition($this->prefix('continueCommand'))
			->setClass(Nextras\Migrations\Bridges\SymfonyConsole\ContinueCommand::class)
			->setArguments([$driver, $configuration, $printer])
			->addTag('kdyby.console.command');

		$builder->addDefinition($this->prefix('createCommand'))
			->setClass(Nextras\Migrations\Bridges\SymfonyConsole\CreateCommand::class)
			->setArguments([$driver, $configuration, $printer])
			->addTag('kdyby.console.command');

		$builder->addDefinition($this->prefix('resetCommand'))
			->setClass(Nextras\Migrations\Bridges\SymfonyConsole\ResetCommand::class)
			->setArguments([$driver, $configuration, $printer])
			->addTag('kdyby.console.command');
	}


	private function filterArguments(array $arguments)
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
