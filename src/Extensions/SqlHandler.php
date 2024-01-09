<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Extensions;

use Nextras\Migrations\Entities\File;
use Nextras\Migrations\IDriver;
use Nextras\Migrations\IExtensionHandler;
use Nextras\Migrations\LogicException;


/**
 * @author Jan Tvrdík
 */
class SqlHandler implements IExtensionHandler
{
	/** @var IDriver */
	private $driver;


	public function __construct(IDriver $driver)
	{
		$this->driver = $driver;
	}


	public function execute(File $file): int
	{
		$count = $this->driver->loadFile($file->path);

		if ($count === 0) {
			throw new LogicException("{$file->path} is empty");
		}

		return $count;
	}
}
