<?php declare(strict_types = 1);

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
	 * @return string file extension
	 */
	public function getExtension(): string;


	/**
	 * @return string SQL (semicolon-separated queries)
	 */
	public function generateContent(): string;
}
