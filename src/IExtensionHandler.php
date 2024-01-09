<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations;

use Nextras\Migrations\Entities\File;


/**
 * @author Petr Procházka
 * @author Jan Tvrdík
 */
interface IExtensionHandler
{
	/**
	 * @return int number of queries
	 */
	public function execute(File $file): int;
}
