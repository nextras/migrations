<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Printers;

use Nextras\Migrations\Entities\File;
use Nextras\Migrations\Exception;
use Nextras\Migrations\IPrinter;


/**
 * /dev/null printer
 *
 * @author Petr Procházka
 */
class DevNull implements IPrinter
{
	public function printIntro(string $mode): void
	{
	}


	public function printToExecute(array $toExecute): void
	{
	}


	public function printExecute(File $file, int $count, float $time): void
	{
	}


	public function printDone(): void
	{
	}


	public function printError(Exception $e): void
	{
	}


	public function printSource(string $code): void
	{
	}
}
