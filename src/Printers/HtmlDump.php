<?php

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


	public function printIntro($mode)
	{
		if ($mode === Runner::MODE_RESET) {
			$this->output('     RESET: All tables, views and data has been destroyed!');
		} else {
			$this->output('     CONTINUE: Running only new migrations.');
		}
	}


	public function printToExecute(array $toExecute)
	{
		if ($toExecute) {
			$this->output('     ' . count($toExecute) . ' migrations need to be executed.');
		} else {
			$this->output('No migration needs to be executed.');
		}

		$this->count = count($toExecute);
		$this->index = 0;
	}


	public function printExecute(File $file, $count, $time)
	{
		$format = '%0' . strlen($this->count) . 'd';
		$name = htmlspecialchars($file->group->name . '/' . $file->name);
		$this->output(sprintf(
			$format . '/' . $format . ': <strong>%s</strong> (%d %s, %0.3f s)',
			++$this->index, $this->count, $name, $count, ($count === 1 ? 'query' : 'queries'), $time
		));
	}


	public function printDone()
	{
		$this->output('OK', 'success');
	}


	public function printError(Exception $e)
	{
		$this->output('ERROR: ' . htmlspecialchars($e->getMessage()), 'error');
		throw $e;
	}


	public function printSource($code)
	{
		$this->output($code);
	}


	/**
	 * @param  string $s     HTML string
	 * @param  string $class
	 * @return void
	 */
	protected function output($s, $class = 'info')
	{
		echo "<div class=\"$class\">$s</div>\n";
	}

}
