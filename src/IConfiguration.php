<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations;


/**
 * @author Jan Tvrdík
 */
interface IConfiguration
{
	/**
	 * @return Group[]
	 */
	function getGroups();


	/**
	 * @return array (extension => IExtensionHandler)
	 */
	function getExtensionHandlers();

}
