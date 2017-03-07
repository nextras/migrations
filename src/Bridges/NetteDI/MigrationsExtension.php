<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\NetteDI;

use Nette;
use Nette\Utils\Validators;
use Nextras;


class MigrationsExtension extends Nette\DI\CompilerExtension
{
	/** @var array */
	public $defaults = [
		'dir' => NULL,
		'phpParams' => [],
		'driver' => NULL,
		'dbal' => NULL,
		'diffGenerator' => TRUE, // false|doctrine
		'withDummyData' => FALSE,
		'contentSource' => NULL, // CreateCommand::CONTENT_SOURCE_*
		'ignoredQueriesFile' => NULL,
	];

	/** @var array */
	protected $dbals = [
		'dibi' => 'Nextras\Migrations\Bridges\Dibi\DibiAdapter',
		'dibi2' => 'Nextras\Migrations\Bridges\Dibi\Dibi2Adapter',
		'dibi3' => 'Nextras\Migrations\Bridges\Dibi\Dibi3Adapter',
		'doctrine' => 'Nextras\Migrations\Bridges\DoctrineDbal\DoctrineAdapter',
		'nette' => 'Nextras\Migrations\Bridges\NetteDatabase\NetteAdapter',
		'nextras' => 'Nextras\Migrations\Bridges\NextrasDbal\NextrasAdapter',
	];

	/** @var array */
	protected $drivers = [
		'mysql' => 'Nextras\Migrations\Drivers\MySqlDriver',
		'pgsql' => 'Nextras\Migrations\Drivers\PgSqlDriver',
	];

	/**
	 * Processes configuration data. Intended to be overridden by descendant.
	 * @return void
	 */
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);
		Validators::assertField($config, 'dir', 'string|Nette\PhpGenerator\PhpLiteral');
		Validators::assertField($config, 'phpParams', 'array');
		Validators::assertField($config, 'contentSource', 'string|null');
		Validators::assertField($config, 'ignoredQueriesFile', 'string|null');

		$dbal = $this->getDbal($config['dbal']);
		$driver = $this->getDriver($config['driver'], $dbal);

		$configuration = $builder->addDefinition($this->prefix('configuration'))
			->setClass('Nextras\Migrations\Configurations\DefaultConfiguration')
			->setArguments([$config['dir'], $driver, $config['withDummyData'], $config['phpParams']]);

		$builder->addExcludedClasses(['Nextras\Migrations\Bridges\SymfonyConsole\BaseCommand']);
		$builder->addDefinition($this->prefix('continueCommand'))
			->setClass('Nextras\Migrations\Bridges\SymfonyConsole\ContinueCommand')
			->setArguments([$driver, $configuration])
			->addTag('kdyby.console.command');
		$builder->addDefinition($this->prefix('createCommand'))
			->setClass('Nextras\Migrations\Bridges\SymfonyConsole\CreateCommand')
			->setArguments([$driver, $configuration])
			->addTag('kdyby.console.command');
		$builder->addDefinition($this->prefix('resetCommand'))
			->setClass('Nextras\Migrations\Bridges\SymfonyConsole\ResetCommand')
			->setArguments([$driver, $configuration])
			->addTag('kdyby.console.command');

		if ($config['diffGenerator'] !== FALSE) {
			$builder->addDefinition($this->prefix('structureDiffGenerator'))
				->setClass('Nextras\Migrations\IDiffGenerator')
				->setDynamic(); // hack to suppress "Class Nextras\Migrations\IDiffGenerator (...) not found"

			if ($config['diffGenerator'] === 'doctrine') {
				$this->configureDoctrineStructureDiffGenerator();
			}
		}
	}


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		// dbal
		foreach ($builder->findByType('Nextras\Migrations\IDbal') as $def) {
			$factory = $def->getFactory();
			if ($factory->getEntity() !== 'Nextras\Migrations\Bridges\Dibi\DibiAdapter') {
				continue;
			}

			$conn = $builder->getByType('Dibi\Connection') ?: $builder->getByType('DibiConnection');
			if (!$conn) {
				continue;
			}

			$factory->arguments = ["@$conn"];
		}

		// diff generators
		if ($config['diffGenerator'] === TRUE && $builder->findByType('Doctrine\ORM\EntityManager')) {
			$this->configureDoctrineStructureDiffGenerator();
		}
	}


	private function getDriver($driver, $dbal)
	{
		$factory = $this->getDriverFactory($driver, $dbal);

		if ($factory) {
			return $this->getContainerBuilder()
				->addDefinition($this->prefix('driver'))
				->setClass('Nextras\Migrations\IDriver')
				->setFactory($factory);

		} elseif ($driver === NULL) {
			return '@Nextras\Migrations\IDriver';

		} else {
			throw new Nextras\Migrations\LogicException('Invalid driver value.');
		}
	}


	private function getDriverFactory($driver, $dbal)
	{
		if ($driver instanceof Nette\DI\Statement) {
			return Nette\DI\Compiler::filterArguments([$driver])[0];

		} elseif (is_string($driver) && isset($this->drivers[$driver])) {
			return new Nette\DI\Statement($this->drivers[$driver], [$dbal]);

		} else {
			return NULL;
		}
	}


	private function getDbal($dbal)
	{
		$factory = $this->getDbalFactory($dbal);

		if ($factory) {
			return $this->getContainerBuilder()
				->addDefinition($this->prefix('dbal'))
				->setClass('Nextras\Migrations\IDbal')
				->setFactory($factory);

		} elseif ($dbal === NULL) {
			return '@Nextras\Migrations\IDbal';

		} else {
			throw new Nextras\Migrations\LogicException('Invalid dbal value');
		}
	}


	private function getDbalFactory($dbal)
	{
		if ($dbal instanceof Nette\DI\Statement) {
			return Nette\DI\Compiler::filterArguments([$dbal])[0];

		} elseif (is_string($dbal) && isset($this->dbals[$dbal])) {
			return $this->dbals[$dbal];

		} else {
			return NULL;
		}
	}


	private function configureDoctrineStructureDiffGenerator()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		$structureDiffGenerator = $builder->getDefinition($this->prefix('structureDiffGenerator'))
			->setDynamic(FALSE)
			->setFactory('Nextras\Migrations\Bridges\DoctrineOrm\StructureDiffGenerator')
			->setArguments([
				'@Doctrine\ORM\EntityManager',
				$config['ignoredQueriesFile']
			]);

		$configuration = $builder->getDefinition($this->prefix('configuration'));
		$configuration->addSetup('setStructureDiffGenerator', [$structureDiffGenerator]);
	}

}
