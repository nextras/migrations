<?php declare(strict_types = 1);

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


	public function printIntro(string $mode): void
	{
		$this->logger->info("Nextras Migrations: started in $mode mode");
	}


	public function printToExecute(array $toExecute): void
	{
		$count = count($toExecute);

		if ($count > 0) {
			$this->logger->info('Nextras Migrations: ' . $count . ' migration' . ($count > 1 ? 's' : '') . ' need' . ($count > 1 ? '' : 's') . ' to be executed');

		} else {
			$this->logger->info('Nextras Migrations: no migration needs to be executed');
		}
	}


	public function printExecute(File $file, int $count, float $time): void
	{
		$this->logger->info("Nextras Migrations: {$file->group->name}/{$file->name} successfully executed", [
			'queryCount' => $count,
			'timeMs' => round($time * 1000),
		]);
	}


	public function printDone(): void
	{
		$this->logger->info('Nextras Migrations: done');
	}


	public function printError(Exception $e): void
	{
		throw $e;
	}


	public function printSource(string $code): void
	{
		$this->logger->debug("Nextras Migrations: init source:\n\n$code");
	}
}
