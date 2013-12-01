<?php
namespace Migration\Extensions;

use Migration;


/**
 * @author Petr Procházka
 */
class SimplePhp implements Migration\IExtensionHandler
{

	/** @var array name => value */
	private $parameters = array();

	/**
	 * @param array name => value
	 */
	public function __construct(array $parameters = array())
	{
		foreach ($parameters as $name => $value)
		{
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
	 * @param Migration\File
	 * @return int number of queries
	 */
	public function execute(Migration\File $sql)
	{
		extract($this->getParameters());
		return include $sql->path;
	}

}
