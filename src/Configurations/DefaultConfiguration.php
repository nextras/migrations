<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Configurations;

use Nextras\Migrations\Entities\Group;
use Nextras\Migrations\Extensions\PhpHandler;
use Nextras\Migrations\Extensions\SqlHandler;
use Nextras\Migrations\IConfiguration;
use Nextras\Migrations\IDiffGenerator;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\IExtensionHandler;


/**
 * @author Jan Tvrdík
 * @deprecated
 */
class DefaultConfiguration implements IConfiguration
{
	/** @var string */
	protected $dir;

	/** @var IDriver */
	protected $driver;

	/** @var bool */
	protected $withDummyData;

	/** @var array<string, mixed> */
	protected $phpParams;

	/** @var list<Group> */
	protected $groups;

	/** @var array<string, IExtensionHandler> */
	protected $handlers;

	/** @var ?IDiffGenerator */
	protected $structureDiffGenerator;

	/** @var ?IDiffGenerator */
	protected $dummyDataDiffGenerator;


	/**
	 * @param  array<string, mixed> $phpParams
	 */
	public function __construct(string $dir, IDriver $driver, bool $withDummyData = true, array $phpParams = [])
	{
		$this->dir = $dir;
		$this->driver = $driver;
		$this->withDummyData = $withDummyData;
		$this->phpParams = $phpParams;
	}


	public function getGroups(): array
	{
		if ($this->groups === null) {
			$structures = new Group();
			$structures->enabled = true;
			$structures->name = 'structures';
			$structures->directory = $this->dir . '/structures';
			$structures->dependencies = [];
			$structures->generator = $this->structureDiffGenerator;

			$basicData = new Group();
			$basicData->enabled = true;
			$basicData->name = 'basic-data';
			$basicData->directory = $this->dir . '/basic-data';
			$basicData->dependencies = ['structures'];

			$dummyData = new Group();
			$dummyData->enabled = $this->withDummyData;
			$dummyData->name = 'dummy-data';
			$dummyData->directory = $this->dir . '/dummy-data';
			$dummyData->dependencies = ['structures', 'basic-data'];
			$dummyData->generator = $this->dummyDataDiffGenerator;

			$this->groups = [$structures, $basicData, $dummyData];
		}

		return $this->groups;
	}


	public function getExtensionHandlers(): array
	{
		if ($this->handlers === null) {
			$this->handlers = [
				'sql' => new SqlHandler($this->driver),
				'php' => new PhpHandler($this->phpParams),
			];
		}

		return $this->handlers;
	}


	public function setStructureDiffGenerator(?IDiffGenerator $generator = null): void
	{
		$this->structureDiffGenerator = $generator;
	}


	public function setDummyDataDiffGenerator(?IDiffGenerator $generator = null): void
	{
		$this->dummyDataDiffGenerator = $generator;
	}
}
