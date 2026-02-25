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
	/** @var list<Group> */
	protected array $groups;

	/** @var array<string, IExtensionHandler> */
	protected array $handlers;

	protected ?IDiffGenerator $structureDiffGenerator = null;

	protected ?IDiffGenerator $dummyDataDiffGenerator = null;


	/**
	 * @param  array<string, mixed> $phpParams
	 */
	public function __construct(
		protected string $dir,
		protected IDriver $driver,
		protected bool $withDummyData = true,
		protected array $phpParams = [],
	)
	{
	}


	public function getGroups(): array
	{
		if (!isset($this->groups)) {
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
		if (!isset($this->handlers)) {
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
