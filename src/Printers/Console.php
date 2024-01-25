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


	public function printIntro(string $mode): void
	{
		$this->output('Nextras Migrations');
		$this->output(strtoupper($mode), self::COLOR_INTRO);
	}


	public function printToExecute(array $toExecute): void
	{
		if ($toExecute) {
			$count = count($toExecute);
			$this->output($count . ' migration' . ($count > 1 ? 's' : '') . ' need' . ($count > 1 ? '' : 's') . ' to be executed.');
		} else {
			$this->output('No migration needs to be executed.');
		}
	}


	public function printExecute(File $file, int $count, float $time): void
	{
		$this->output(
			'- ' . $file->group->name . '/' . $file->name . '; '
			. $this->colorize((string) $count, self::COLOR_INFO) . ' queries; '
			. $this->colorize(sprintf('%0.3f', $time), self::COLOR_INFO) . ' s'
		);
	}


	public function printDone(): void
	{
		$this->output('OK', self::COLOR_SUCCESS);
	}


	public function printError(Exception $e): void
	{
		$this->output('ERROR: ' . $e->getMessage(), self::COLOR_ERROR);
		throw $e;
	}


	public function printSource(string $code): void
	{
		$this->output($code);
	}


	/**
	 * Prints text to a console, optionally in a specific color.
	 *
	 * @param  string|null $color self::COLOR_*
	 */
	protected function output(string $s, ?string $color = null): void
	{
		echo $this->colorize($s, $color) . "\n";
	}


	protected function colorize(string $s, ?string $color): string
	{
		return $this->useColors && $color !== null ? "\033[{$color}m$s\033[22;39m" : $s;
	}


	/**
	 * @return  bool true if terminal support colors, false otherwise
	 */
	protected function detectColorSupport(): bool
	{
		return defined('STDOUT') && ((function_exists('posix_isatty') && posix_isatty(STDOUT))
			|| (function_exists('stream_isatty') && stream_isatty(STDOUT))
			|| (function_exists('sapi_windows_vt100_support') && sapi_windows_vt100_support(STDOUT)));
	}
}
