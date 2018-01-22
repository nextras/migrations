<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\NetteDI;

use Nextras;
use Nextras\Migrations\Entities\Group;


interface IMigrationGroupsProvider
{
	/**
	 * @return Group[]
	 */
	public function getMigrationGroups(): array;
}
