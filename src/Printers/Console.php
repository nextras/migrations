<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Printers;

use Nextras\Migrations\Entities\File;
use Nextras\Migrations\Entities\Migration;
use Nextras\Migrations\Exception;
use Nextras\Migrations\IPrinter;


/**
 * @author Mikulas Dite
 * @author Jan Tvrdik
 */
class Console implements IPrinter
{
	/** @const console colors */
	const COLOR_ERROR = '1;31';
	const COLOR_SUCCESS = '1;32';
	const COLOR_INTRO = '1;35';
	const COLOR_INFO = '1;36';

	/** @var bool */
	protected $useColors;


	public function __construct()
	{
		$this->useColors = $this->detectColorSupport();
	}
	
	/**
	 * @inheritdoc
	 */
	public function printIntro($mode)
	{
		$this->output('Nextras Migrations');
		$this->output(strtoupper($mode), self::COLOR_INTRO);
	}
	
	/**
	 * @inheritdoc
	 */
	public function printExecutedMigrations(array $migrations)
	{
		if ($migrations) {
			$this->output('Executed migrations:');
			/** @var Migration $migration */
			foreach ($migrations as $migration) {
				$this->output('- ' . $migration->group . '/' . $migration->filename . ' OK', self::COLOR_SUCCESS);
			}
			$this->output(' ');
		} else {
			$this->output('No migrations has executed yet.');
		}
	}
	
	/**
	 * @inheritdoc
	 */
	public function printToExecute(array $toExecute, $withFileList = FALSE)
	{
		if ($toExecute) {
			$count = count($toExecute);
			$this->output(
				sprintf(
					'%s migration%s need%s to be executed%s',
					$count, $count > 1 ? 's' : '', $count > 1 ? '' : 's', ($withFileList ? ':' : '.')
				)
			);
			if ($withFileList) {
				/** @var File $file */
				foreach ($toExecute as $file) {
					$this->output('- ' . $file->group->name . '/' . $file->name, self::COLOR_INFO);
				}
			}
		} else {
			$this->output('No migration needs to be executed.');
		}
	}
	
	/**
	 * @inheritdoc
	 */
	public function printExecute(File $file, $count, $time)
	{
		$this->output(
			'- ' . $file->group->name . '/' . $file->name . '; '
			. $this->color($count, self::COLOR_INFO) . ' queries; '
			. $this->color(sprintf('%0.3f', $time), self::COLOR_INFO) . ' s'
		);
	}
	
	/**
	 * @inheritdoc
	 */
	public function printDone()
	{
		$this->output('OK', self::COLOR_SUCCESS);
	}
	
	/**
	 * @inheritdoc
	 */
	public function printError(Exception $e)
	{
		$this->output('ERROR: ' . $e->getMessage(), self::COLOR_ERROR);
		throw $e;
	}


	public function printSource($code)
	{
		$this->output($code);
	}


	/**
	 * Prints text to a console, optionally in a specific color.
	 * @param  string      $s
	 * @param  string|NULL $color self::COLOR_*
	 */
	protected function output($s, $color = NULL)
	{
		if ($color === NULL || !$this->useColors) {
			echo "$s\n";
		} else {
			echo $this->color($s, $color) . "\n";
		}
	}


	/**
	 * @param  string $s
	 * @param  string $color
	 * @return string
	 */
	protected function color($s, $color)
	{
		if (!$this->useColors) {
			return $s;
		}
		return "\033[{$color}m$s\033[22;39m";
	}


	/**
	 * @author  David Grudl
	 * @license New BSD License
	 * @return  bool TRUE if terminal support colors, FALSE otherwise
	 */
	protected function detectColorSupport()
	{
		return (getenv('ConEmuANSI') === 'ON' || getenv('ANSICON') !== FALSE
			|| (defined('STDOUT') && function_exists('posix_isatty') && posix_isatty(STDOUT)));
	}

}
