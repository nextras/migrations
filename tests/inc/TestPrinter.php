<?php

namespace NextrasTests\Migrations;

use Nextras\Migrations\Printers\Console;


class TestPrinter extends Console
{
	/** @var string[] output lines */
	public $lines = [];

	/** @var string whole output */
	public $out = '';


	public function __construct()
	{
		parent::__construct();
		$this->useColors = FALSE;
	}


	protected function output($s, $color = NULL)
	{
		$this->lines[] = preg_replace('#; \d+\.\d+ ms#', '; XX ms', $s);
		$this->out .= "$s\n";
	}
}
