<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Extensions;

use Nextras\Migrations\Entities\File;
use Nextras\Migrations\IExtensionHandler;
use Nextras\Migrations\IOException;


/**
 * @author Petr Procházka
 * @author Jan Tvrdík
 */
class PhpHandler implements IExtensionHandler
{
	/** @var array name => value */
	private $params;


	/**
	 * @param array $params name => value
	 */
	public function __construct(array $params = [])
	{
		$this->params = $params;
	}


	/**
	 * @param  string $name
	 * @param  mixed  $value
	 * @return self
	 */
	public function addParameter($name, $value)
	{
		$this->params[$name] = $value;
		return $this;
	}


	/**
	 * @return array (name => value)
	 */
	public function getParameters()
	{
		return $this->params;
	}


	/**
	 * @param  File $file
	 * @return int number of queries
	 */
	public function execute(File $file)
	{
		extract($this->params, EXTR_SKIP);
		$count = @include $file->path;
		if ($count === FALSE) {
			throw new IOException("Cannot include file '{$file->path}'.");
		}
		return $count;
	}

}
