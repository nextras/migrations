<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\SymfonyConsole;

use Nette;
use Nette\Utils\Strings;
use Nextras;
use Nextras\Migrations\Extensions;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class CreateCommand extends BaseCommand
{
	protected function configure()
	{
		$this->setName('migrations:create');
		$this->setDescription('Creates new migration file with proper name (e.g. 2015-03-14-130836-label.sql)');
		$this->addArgument('type', InputArgument::REQUIRED, 's(tructures), b(asic-data) or d(ummy-data');
		$this->addArgument('label', InputArgument::REQUIRED, 'short description');
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$dir = $this->getDirectory($input->getArgument('type'));
		$name = $this->getFileName($input->getArgument('label'));
		@mkdir($dir, 0777, TRUE); // directory may already exist
		touch("$dir/$name");
	}


	/**
	 * @param  string $type
	 * @return string
	 */
	private function getDirectory($type)
	{
		foreach ($this->getGroups(TRUE) as $group) {
			if (Strings::startsWith($group->name, $type)) {
				return $group->directory;
			}
		}

		throw new Nextras\Migrations\LogicException("Unknown type '$type' given, expected on of 's', 'b' or 'd'.");
	}


	/**
	 * @param  string $label
	 * @return string
	 */
	private function getFileName($label)
	{
		return date('Y-m-d-His-') . Strings::webalize($label, '.') . '.sql';
	}

}
