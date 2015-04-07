<?php

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
 * @author Mikulas Dite
 * @author Jan Tvrdik
 */
class Console implements IPrinter
{
	/** @const console colors */
	const COLOR_ERROR = '1;31';
	const COLOR_NOTICE = '1;34';
	const COLOR_SUCCESS = '1;32';

	/** @var bool */
	private $useColors;


	public function __construct()
	{
		$this->useColors = $this->detectColorSupport();
	}


	public function printReset()
	{
		$this->output('RESET', self::COLOR_NOTICE);
	}


	public function printToExecute(array $toExecute)
	{
		if ($toExecute) {
			$count = count($toExecute);
			$this->output($count . ' migration' . ($count > 1 ? 's' : '') . ' need' . ($count > 1 ? '' : 's') . ' to be executed.');
		} else {
			$this->output('No migration needs to be executed.');
		}
	}


	public function printExecute(File $file, $count)
	{
		$this->output($file->group->name . '/' . $file->name . '; ' . $count . ' queries');

	}

	public function printDone()
	{
		$this->output('OK', self::COLOR_SUCCESS);
	}


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
			echo "\033[{$color}m$s\033[0m\n";
		}
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
