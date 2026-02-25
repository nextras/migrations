<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Configurations;

use Nextras\Migrations\Entities\Group;
use Nextras\Migrations\IConfiguration;
use Nextras\Migrations\IExtensionHandler;


/**
 * @author Jan Tvrdík
 */
class Configuration implements IConfiguration
{
	/**
	 * @param  list<Group>                      $groups
	 * @param  array<string, IExtensionHandler> $extensionHandlers (extension => IExtensionHandler)
	 */
	public function __construct(
		private array $groups,
		private array $extensionHandlers,
	)
	{
	}


	public function getGroups(): array
	{
		return $this->groups;
	}


	public function getExtensionHandlers(): array
	{
		return $this->extensionHandlers;
	}
}
