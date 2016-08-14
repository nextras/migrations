<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations;


interface IDiffGenerator
{

	/**
	 * @return string
	 */
	function getExtension();

	/**
	 * @return string SQL (semicolon-separated queries)
	 */
	function generateContent();

}
