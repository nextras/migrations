<?php
namespace Migration\Controllers;

use DibiConnection;
use Migration\Engine;
use Migration\Entities\Group;
use Migration\Printers;


class ConsoleController
{
	/** @var Engine\Runner */
	private $runner;

	/** @var string */
	private $mode;

	/** @var array (name => Group) */
	private $groups;

	public function __construct(DibiConnection $dibi)
	{
		$printer = new Printers\Console();
		$this->runner = new Engine\Runner($dibi, $printer);
		$this->mode = Engine\Runner::MODE_CONTINUE;
	}

	public function addGroup($name, $dir, array $dependencies = array())
	{
		$group = new Group();
		$group->name = $name;
		$group->directory = $dir;
		$group->dependencies = $dependencies;
		$group->enabled = FALSE;

		$this->groups[$name] = $group;
		return $this;
	}

	public function run()
	{
		$this->printHeader();
		$this->processArguments();
		$this->registerGroups();
		$this->runner->run($this->mode);
	}

	private function printHeader()
	{
		printf("Migrations\n");
		printf("------------------------------------------------------------\n");
	}

	private function processArguments()
	{
		$arguments = array_slice($_SERVER['argv'], 1);
		$help = (count($arguments) === 0);
		$groups = FALSE;
		$error = FALSE;

		foreach ($arguments as $argument)
		{
			if (strncmp($argument, '--', 2) === 0)
			{
				if ($argument === '--reset')
				{
					$this->mode = Engine\Runner::MODE_RESET;
				}
				else if ($argument === '--help')
				{
					$help = TRUE;
				}
				else
				{
					fprintf(STDERR, "Error: Unknown option '%s'\n", $argument);
					$error = TRUE;
				}
			}
			else
			{
				if (isset($this->groups[$argument]))
				{
					$this->groups[$argument]->enabled = TRUE;
					$groups = TRUE;
				}
				else
				{
					fprintf(STDERR, "Error: Unknown group '%s', the following groups are registered:\n", $argument);
					fprintf(STDERR, "       %s\n", implode(', ', array_keys($this->groups)));
					$error = TRUE;
				}
			}
		}

		if (!$groups && !$help)
		{
			fprintf(STDERR, "Error: At least one group must be enabled.\n");
			$error = TRUE;
		}

		if ($error)
		{
			printf("\n");
		}

		if ($help || $error)
		{
			printf("Usage: %s group1 [, group2, ...] [--reset] [--help]\n", basename($_SERVER['argv'][0]));
			printf("  --reset      drop all tables and views in database and start from scratch\n");
			printf("  --help       show this help\n");
			exit(intval($error));
		}
	}

	private function registerGroups()
	{
		foreach ($this->groups as $group)
		{
			$this->runner->addGroup($group);
		}
	}
}
