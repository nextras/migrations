<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Controllers;

use Nextras\Migrations\Engine;
use Nextras\Migrations\IPrinter;
use Nextras\Migrations\Printers;


class ConsoleController extends BaseController
{
	public function run(): void
	{
		$this->processArguments();
		$this->printHeader();
		$this->registerGroups();
		$this->runner->run($this->mode);
	}


	private function printHeader(): void
	{
		if ($this->mode === Engine\Runner::MODE_INIT) {
			printf("-- Migrations init\n");
		} else {
			printf("Migrations\n");
			printf("------------------------------------------------------------\n");
		}
	}


	private function processArguments(): void
	{
		$arguments = array_slice($_SERVER['argv'], 1);
		$help = count($arguments) === 0;
		$groups = $error = false;

		foreach ($arguments as $argument) {
			if (strncmp($argument, '--', 2) === 0) {
				if ($argument === '--reset') {
					$this->mode = Engine\Runner::MODE_RESET;
				} elseif ($argument === '--init-sql') {
					$this->mode = Engine\Runner::MODE_INIT;
				} elseif ($argument === '--help') {
					$help = true;
				} else {
					fprintf(STDERR, "Warning: Unknown option '%s'\n", $argument);
					continue;
				}
			} else {
				if (isset($this->groups[$argument])) {
					$this->groups[$argument]->enabled = true;
					$groups = true;
				} else {
					fprintf(STDERR, "Error: Unknown group '%s'\n", $argument);
					$error = true;
				}
			}
		}

		if (!$groups && !$help) {
			fprintf(STDERR, "Error: At least one group must be enabled.\n");
			$error = true;
		}

		if ($error) {
			printf("\n");
		}

		if ($help || $error) {
			printf("Usage: %s group1 [, group2, ...] [--reset] [--help]\n", basename($_SERVER['argv'][0]));
			printf("Registered groups:\n");
			foreach (array_keys($this->groups) as $group) {
				printf("  %s\n", $group);
			}
			printf("\nSwitches:\n");
			printf("  --reset      drop all tables and views in database and start from scratch\n");
			printf("  --init-sql   prints initialization sql for all present migrations\n");
			printf("  --help       show this help\n");
			exit(intval($error));
		}
	}


	protected function createPrinter(): IPrinter
	{
		return new Printers\Console();
	}
}
