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
use Nextras\Migrations\Entities\Migration;
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
	
	/**
	 * @inheritdoc
	 */
	public function printIntro($mode)
	{
		if ($mode === Runner::MODE_RESET) {
			$this->output('     RESET: All tables, views and data has been destroyed!');
		}
		if ($mode === Runner::MODE_CONTINUE) {
			$this->output('     CONTINUE: Running only new migrations.');
		}
		if($mode === Runner::MODE_STATUS) {
			$this->output('     STATUS: Show lists of completed or waiting migrations');
		}
	}
	
	public function printExecutedMigrations(array $migrations)
	{
		if ($migrations) {
			$this->output('Executed migrations:');
			/** @var Migration $migration */
			foreach ($migrations as $migration) {
				$this->output('- ' . $migration->group . '/' . $migration->filename . ' OK', 'success');
			}
			$this->output(' ');
		} else {
			$this->output('No migrations has executed yet');
		}
	}
	
	/**
	 * @inheritdoc
	 */
	public function printToExecute(array $toExecute, $withFileList = false)
	{
		$count = 0;
		if ($toExecute) {
			$count = count($toExecute);
			$this->output(sprintf(
				'%s migration%s need%s to be executed%s',
				$count,$$count > 1 ? 's' : '', $count > 1 ? '' : 's', ($withFileList ? ':' : '.'))
			);
			if ($withFileList) {
				/** @var File $file */
				foreach ($toExecute as $file) {
					$this->output('     - ' . $file->group->name . '/' . $file->name);
				}
			}
		} else {
			$this->output('No migration needs to be executed.');
		}

		$this->count = $count;
		$this->index = 0;
	}
	
	/**
	 * @inheritdoc
	 */
	public function printExecute(File $file, $count, $time)
	{
		$format = '%0' . strlen($this->count) . 'd';
		$name = htmlspecialchars($file->group->name . '/' . $file->name);
		$this->output(sprintf(
			$format . '/' . $format . ': <strong>%s</strong> (%d %s, %0.3f s)',
			++$this->index, $this->count, $name, $count, ($count === 1 ? 'query' : 'queries'), $time
		));
	}
	
	/**
	 * @inheritdoc
	 */
	public function printDone()
	{
		$this->output('OK', 'success');
	}
	
	/**
	 * @inheritdoc
	 */
	public function printError(Exception $e)
	{
		$this->output('ERROR: ' . htmlspecialchars($e->getMessage()), 'error');
		throw $e;
	}
	
	/**
	 * @inheritdoc
	 */
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
