<?php

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
	 * @return Group[]
	 */
	public function getGroups();


	/**
	 * @return array (extension => IExtensionHandler)
	 */
	public function getExtensionHandlers();

}
