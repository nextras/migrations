<?php declare(strict_types = 1);

namespace NextrasTests\Migrations;

use Nextras\Migrations\Printers\Console;


class TestPrinter extends Console
{
	/** @var list<string> output lines */
	public $lines = [];

	/** @var string whole output */
	public $out = '';


	public function __construct()
	{
		parent::__construct();
		$this->useColors = false;
	}


	protected function output(string $s, ?string $color = null): void
	{
		$this->lines[] = preg_replace('#; \d+\.\d+ s#', '; XX s', $s);
		$this->out .= "$s\n";
	}
}
