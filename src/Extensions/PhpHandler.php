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


/**
 * @author Petr Procházka
 * @author Jan Tvrdík
 */
class PhpHandler implements IExtensionHandler
{
	/** @var array name => value */
	private $params = [];

	/**
	 * @param array $params name => value
	 */
	public function __construct(array $params = [])
	{
		foreach ($params as $name => $value) {
			$this->addParameter($name, $value);
		}
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


	public function execute(File $sql)
	{
		extract($this->params);
		return include $sql->path;
	}

}
