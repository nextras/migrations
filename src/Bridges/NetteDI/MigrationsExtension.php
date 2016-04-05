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
		'handlers' => [],
		'configuration' => 'Nextras\Migrations\Configurations\DefaultConfiguration',
		'withDummyData' => FALSE,
		'commands' => [
			'continue' => 'Nextras\Migrations\Bridges\SymfonyConsole\ContinueCommand',
			'create' => 'Nextras\Migrations\Bridges\SymfonyConsole\CreateCommand',
			'reset' => 'Nextras\Migrations\Bridges\SymfonyConsole\ResetCommand',
		],
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
		Validators::assertField($config, 'dir', 'string');
		Validators::assertField($config, 'phpParams', 'array');
		Validators::assertField($config, 'handlers', 'array');

		$dbal = $this->getDbal($config['dbal']);
		$driver = $this->getDriver($config['driver'], $dbal);


		$handlers = [];
		$handlers['sql'] = $builder->addDefinition($this->prefix('sqlHandler'))
			->setClass('Nextras\Migrations\Extensions\SqlHandler')
			->setArguments([$driver]);
		$handlers['php'] = $builder->addDefinition($this->prefix('phpHandler'))
			->setClass('Nextras\Migrations\Extensions\PhpHandler')
			->setArguments($config['phpParams']);

		foreach ($config['handlers'] as $extension => $handler) {
			$handlers[$extension] = $handler;
		}

		$configuration = $builder->addDefinition($this->prefix('configuration'))
			->setClass($config['configuration'])
			->setArguments([$config['dir'], $handlers, $config['withDummyData']]);


		$params = [$driver, $configuration];
		$builder->addExcludedClasses(['Nextras\Migrations\Bridges\SymfonyConsole\BaseCommand']);

		foreach (array_filter($config['commands']) as $name => $commandClass) {
			// filter NULLed command classes to enable disabling default command completely
			$builder->addDefinition($this->prefix("{$name}Command"))
				->setClass($commandClass)
				->setArguments($params)
				->addTag('kdyby.console.command');
		}
	}


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
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

}
