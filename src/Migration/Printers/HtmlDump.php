<?php
namespace Migration\Printers;

use Migration\Exceptions\Exception;
use Migration;


/**
 * Echoje informace na vystup jako html.
 *
 * @author Petr Procházka
 */
class HtmlDump implements Migration\IPrinter
{

	/** @var int number of migrations to be executed */
	private $count;

	/** @var int order of last executed migration */
	private $index;

	/**
	 * @inheritdoc
	 */
	public function printReset()
	{
		$this->output('RESET: All tables, views and data has been destroyed!');
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

		$this->count = count($toExecute);
		$this->index = 0;
	}

	/**
	 * @inheritdoc
	 */
	public function printExecute(Migration\Entities\File $file, $count)
	{
		$format = '%0' . strlen($this->count) . 'd';
		$name = htmlspecialchars($file->group->name . '/' . $file->name);
		$this->output(sprintf(
			$format . '/' . $format . ': <strong>%s</strong> (%d %s)',
			++$this->index, $this->count, $name, $count, ($count === 1 ? 'query' : 'queries')
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
	 * @param  string HTML string
	 * @param  string
	 * @return void
	 */
	protected function output($s, $class = 'info')
	{
		echo "<div class=\"$class\">$s</div>\n";
	}

}
