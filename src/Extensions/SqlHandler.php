<?php

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

	/** @var string */
	private $extension;


	/**
	 * @param IDriver $driver
	 * @param string  $extension
	 */
	public function __construct(IDriver $driver, $extension = 'sql')
	{
		$this->driver = $driver;
		$this->extension = $extension;
	}


	/**
	 * Unique extension name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->extension;
	}


	public function execute(File $sql)
	{
		$path = $sql->getPath();
		$count = $this->driver->loadFile($path);
		if ($count === 0) {
			throw new LogicException("$path is empty");
		}
		return $count;
	}

}
