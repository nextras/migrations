<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations;

use Nextras\Migrations\Entities\Group;


/**
 * @author Jan Tvrdík
 */
interface IConfiguration
{
	/**
	 * @return list<Group>
	 */
	public function getGroups(): array;


	/**
	 * @return array<string, IExtensionHandler> (extension => IExtensionHandler)
	 */
	public function getExtensionHandlers(): array;
}
