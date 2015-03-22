<?php

namespace NextrasTests\Migrations;

use Nextras\Migrations\Printers\Console;


class TestPrinter extends Console
{
	/** @var string[] output lines */
	public $lines = [];

	/** @var string whole output */
	public $out = '';


	/**
	 * Prints text to a console, optionally in a specific color.
	 * @param  string
	 * @param  string|NULL self::COLOR_*
	 */
	protected function output($s, $color = NULL)
	{
		$this->lines[] = $s;
		$this->out .= "$s\n";
	}
}
