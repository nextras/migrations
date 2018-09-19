<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Configurations;

use Nette\Utils\Validators;
use Nextras\Migrations\Entities\Group;
use Nextras\Migrations\IConfiguration;
use Nextras\Migrations\IExtensionHandler;


/**
 * @author Jan Tvrdík
 */
class Configuration implements IConfiguration
{
	/** @var Group[] */
	private $groups;

	/** @var IExtensionHandler[] (extension => IExtensionHandler) */
	private $extensionHandlers;


	/**
	 * @param Group[]             $groups
	 * @param IExtensionHandler[] $extensionHandlers (extension => IExtensionHandler)
	 */
	public function __construct(array $groups, array $extensionHandlers)
	{
		$this->groups = $groups;
		$this->extensionHandlers = $extensionHandlers;
	}


	/**
	 * @return Group[]
	 */
	public function getGroups()
	{
		return $this->groups;
	}


	/**
	 * @return IExtensionHandler[] (extension => IExtensionHandler)
	 */
	public function getExtensionHandlers()
	{
		return $this->extensionHandlers;
	}
}
