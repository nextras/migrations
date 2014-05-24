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
 */
class SimplePhp implements IExtensionHandler
{
	/** @var array name => value */
	private $parameters = array();


	/**
	 * @param array name => value
	 */
	public function __construct(array $parameters = array())
	{
		foreach ($parameters as $name => $value) {
			$this->addParameter($name, $value);
		}
	}


	/**
	 * @param string
	 * @param mixed
	 * @return SimplePhp $this
	 */
	public function addParameter($name, $value)
	{
		$this->parameters[$name] = $value;
		return $this;
	}


	/**
	 * @return array name => value
	 */
	public function getParameters()
	{
		return $this->parameters;
	}


	/**
	 * Unique extension name.
	 * @return string
	 */
	public function getName()
	{
		return 'simple.php';
	}


	/**
	 * @param  File
	 * @return int number of queries
	 */
	public function execute(File $sql)
	{
		extract($this->getParameters());
		return include $sql->getPath();
	}

}
