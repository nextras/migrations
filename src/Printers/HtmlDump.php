<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Printers;

use Nextras\Migrations\Engine\Runner;
use Nextras\Migrations\Entities\File;
use Nextras\Migrations\Exception;
use Nextras\Migrations\IPrinter;


/**
 * @author Petr Procházka
 */
class HtmlDump implements IPrinter
{
	/** @var int number of migrations to be executed */
	private $count;

	/** @var int order of last executed migration */
	private $index;


	public function printIntro(string $mode): void
	{
		if ($mode === Runner::MODE_RESET) {
			$this->output('     RESET: All tables, views and data has been destroyed!');
		} else {
			$this->output('     CONTINUE: Running only new migrations.');
		}
	}


	public function printToExecute(array $toExecute): void
	{
		if ($toExecute) {
			$this->output('     ' . count($toExecute) . ' migrations need to be executed.');
		} else {
			$this->output('No migration needs to be executed.');
		}

		$this->count = count($toExecute);
		$this->index = 0;
	}


	public function printExecute(File $file, int $count, float $time): void
	{
		$format = '%0' . strlen((string) $this->count) . 'd';
		$name = htmlspecialchars($file->group->name . '/' . $file->name);
		$this->output(sprintf(
			$format . '/' . $format . ': <strong>%s</strong> (%d %s, %0.3f s)',
			++$this->index, $this->count, $name, $count, ($count === 1 ? 'query' : 'queries'), $time
		));
	}


	public function printDone(): void
	{
		$this->output('OK', 'success');
	}


	public function printError(Exception $e): void
	{
		$this->output('ERROR: ' . htmlspecialchars($e->getMessage()), 'error');
		throw $e;
	}


	public function printSource(string $code): void
	{
		$this->output($code);
	}


	protected function output(string $s, string $class = 'info'): void
	{
		echo "<div class=\"$class\">$s</div>\n";
	}
}
