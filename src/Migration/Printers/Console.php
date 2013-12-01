<?php
namespace Migration\Printers;

use Migration\Exceptions\Exception;
use Migration;


/**
 * @author Mikulas Dite, Jan Tvrdik
 */
class Console implements Migration\IPrinter
{

	/** console colors */
	const COLOR_ERROR = '1;31';
	const COLOR_NOTICE = '1;34';
	const COLOR_SUCCESS = '1;32';

	/** @var bool */
	private $useColors;

	public function __construct()
	{
		$this->useColors = $this->detectColorSupport();
	}

	/**
	 * @inheritdoc
	 */
	public function printReset()
	{
		$this->output('RESET', self::COLOR_NOTICE);
	}

	/**
	 * @inheritdoc
	 */
	public function printToExecute(array $toExecute)
	{
		if ($toExecute)
		{
			$this->output(count($toExecute) . ' migrations need to be executed.');
		}
		else
		{
			$this->output('No migration needs to be executed.');
		}
	}

	/**
	 * @inheritdoc
	 */
	public function printExecute(Migration\Entities\File $file, $count)
	{
		$this->output($file->group->name . '/' . $file->name . '; ' . $count . ' queries');
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

	/**
	 * Prints text to a console, optionally in a specific color.
	 *
	 * @param  string
	 * @param  string|NULL self::COLOR_*
	 * @return void
	 */
	protected function output($s, $color = NULL)
	{
		if ($color === NULL || !$this->useColors)
		{
			echo "$s\n";
		}
		else
		{
			echo "\033[{$color}m$s\033[0m\n";
		}
	}

	/**
	 * @return bool TRUE if terminal support colors, FALSE otherwise
	 */
	protected function detectColorSupport()
	{
		return (bool) preg_match('#^xterm|^screen|^cygwin#', getenv('TERM'));
	}

}
