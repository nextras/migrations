<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\PsrLog;

use Nextras\Migrations\Entities\File;
use Nextras\Migrations\Exception;
use Nextras\Migrations\IPrinter;
use Psr\Log\LoggerInterface;


class PsrLogPrinter implements IPrinter
{
	/** @var LoggerInterface */
	private $logger;


	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}


	public function printIntro($mode)
	{
		$this->logger->info("Nextras Migrations: started in $mode mode");
	}


	public function printToExecute(array $toExecute)
	{
		$count = count($toExecute);

		if ($count > 0) {
			$this->logger->info('Nextras Migrations: ' . $count . ' migration' . ($count > 1 ? 's' : '') . ' need' . ($count > 1 ? '' : 's') . ' to be executed');

		} else {
			$this->logger->info('Nextras Migrations: no migration needs to be executed');
		}
	}


	public function printExecute(File $file, $count, $time)
	{
		$this->logger->info("Nextras Migrations: {$file->group->name}/{$file->name} successfully executed", [
			'queryCount' => $count,
			'timeMs' => round($time * 1000),
		]);
	}


	public function printDone()
	{
		$this->logger->info('Nextras Migrations: done');
	}


	public function printError(Exception $e)
	{
		throw $e;
	}


	public function printSource($code)
	{
		$this->logger->debug("Nextras Migrations: init source:\n\n$code");
	}
}
