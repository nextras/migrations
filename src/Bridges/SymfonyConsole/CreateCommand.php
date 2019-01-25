<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Bridges\SymfonyConsole;

use Nette\Utils\Strings;
use Nextras;
use Nextras\Migrations\Entities\Group;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class CreateCommand extends BaseCommand
{
	/** content source options */
	const CONTENT_SOURCE_DIFF = 'diff';
	const CONTENT_SOURCE_STDIN = 'stdin';
	const CONTENT_SOURCE_EMPTY = 'empty';

	/** @var string */
	protected static $defaultName = 'migrations:create';

	/** @var string */
	protected $defaultContentSource = self::CONTENT_SOURCE_DIFF;


	/**
	 * @param  string $defaultContentSource
	 * @return void
	 */
	public function setDefaultContentSource($defaultContentSource)
	{
		$this->defaultContentSource = $defaultContentSource;
	}


	/**
	 * @return void
	 */
	protected function configure()
	{
		$this->setName(self::$defaultName);
		$this->setDescription('Creates new migration file with proper name (e.g. 2015-03-14-130836-label.sql)');
		$this->setHelp('Prints path of the created file to standard output.');

		$this->addArgument('type', InputArgument::REQUIRED, $this->getTypeArgDescription());
		$this->addArgument('label', InputArgument::REQUIRED, 'short description');

		$this->addOption('empty', NULL, InputOption::VALUE_NONE, 'create empty file');
		$this->addOption('diff', NULL, InputOption::VALUE_NONE, 'use schema diff as file content');
		$this->addOption('stdin', NULL, InputOption::VALUE_NONE, 'use stdin as file content');
	}


	/**
	 * @param  InputInterface  $input
	 * @param  OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$group = $this->getGroup($input->getArgument('type'));
		$path = $this->getPath($group, $input->getArgument('label'));
		$content = $this->getFileContent($group, $this->getFileContentSource($input));

		$this->createFile($path, $content, $output);
		$output->writeln($path);

		return 0;
	}


	/**
	 * @param  Group  $group
	 * @param  string $label
	 * @return string
	 */
	protected function getPath(Group $group, $label)
	{
		$dir = $group->directory;
		$extension = $group->generator ? $group->generator->getExtension() : 'sql';
		$name = $this->getFileName($label, $extension);

		if ($this->hasNumericSubdirectory($dir, $foundYear)) {
			if ($this->hasNumericSubdirectory($foundYear, $foundMonth)) {
				return $dir . date('/Y/m/') . $name;

			} else {
				return $dir . date('/Y/') . $name;
			}

		} else {
			return "$dir/$name";
		}
	}


	/**
	 * @param  string $type
	 * @return Group
	 */
	protected function getGroup($type)
	{
		$groupNamePattern = preg_quote($type, '~');
		$groupNamePattern = str_replace('\\-', '\\w*+\\-', $groupNamePattern);
		$groupNamePattern = "~^$groupNamePattern~";

		$matchedGroups = [];
		foreach ($this->config->getGroups() as $group) {
			if (Strings::match($group->name, $groupNamePattern)) {
				$matchedGroups[] = $group;
			}
		}

		if (count($matchedGroups) === 1) {
			return $matchedGroups[0];
		}

		if (count($matchedGroups) > 1) {
			$groupNames = [];
			foreach ($matchedGroups as $matchedGroup) {
				$groupNames[] = $matchedGroup->name;
			}

			throw new Nextras\Migrations\LogicException("Type '$type' is ambiguous.\nDid you mean one of these?\n  - " . implode("\n  - ", $groupNames) . "\n");
		}

		$types = $this->getTypeArgDescription();
		throw new Nextras\Migrations\LogicException("Unknown type '$type' given, expected one of $types.");
	}


	/**
	 * @param  string $label
	 * @param  string $extension
	 * @return string
	 */
	protected function getFileName($label, $extension)
	{
		return date('Y-m-d-His-') . Strings::webalize($label, '.') . '.' . $extension;
	}


	/**
	 * @param  string $dir
	 * @param  string|NULL $found
	 * @return bool
	 */
	protected function hasNumericSubdirectory($dir, & $found)
	{
		$items = @scandir($dir); // directory may not exist

		if ($items) {
			foreach ($items as $item) {
				if ($item !== '.' && $item !== '..' && is_dir($dir . '/' . $item)) {
					$found = $dir . '/' . $item;
					return TRUE;
				}
			}
		}

		return FALSE;
	}


	/**
	 * @return string
	 */
	protected function getTypeArgDescription()
	{
		$options = [];
		$groups = $this->config->getGroups();
		usort($groups, function (Group $a, Group $b) {
			return strcmp($a->name, $b->name);
		});

		foreach ($groups as $i => $group) {
			for ($j = 1; $j < strlen($group->name); $j++) {
				$doesCollideWithPrevious = isset($groups[$i - 1]) && strncmp($group->name, $groups[$i - 1]->name, $j) === 0;
				$doesCollideWithNext = isset($groups[$i + 1]) && strncmp($group->name, $groups[$i + 1]->name, $j) === 0;
				if (!$doesCollideWithPrevious && !$doesCollideWithNext) {
					$options[] = substr($group->name, 0, $j) . '(' . substr($group->name, $j) . ')';
					break;
				}
			}
		}

		return implode(' or ', array_filter([
			implode(', ', array_slice($options, 0, -1)),
			array_slice($options, -1)[0],
		]));
	}


	/**
	 * @param  InputInterface $input
	 * @return string
	 */
	protected function getFileContentSource(InputInterface $input)
	{
		if ($input->getOption('diff')) {
			return self::CONTENT_SOURCE_DIFF;

		} elseif ($input->getOption('stdin')) {
			return self::CONTENT_SOURCE_STDIN;

		} elseif ($input->getOption('empty')) {
			return self::CONTENT_SOURCE_EMPTY;

		} else {
			return $this->defaultContentSource;
		}
	}


	/**
	 * @param  Group  $group
	 * @param  string $source
	 * @return string
	 */
	protected function getFileContent(Group $group, $source)
	{
		if ($source === self::CONTENT_SOURCE_DIFF && $group->generator !== NULL) {
			return $group->generator->generateContent();

		} elseif ($source === self::CONTENT_SOURCE_STDIN) {
			return stream_get_contents(STDIN);

		} else {
			return '';
		}
	}


	/**
	 * @param  string          $path
	 * @param  string          $content
	 * @param  OutputInterface $output
	 * @return void
	 */
	protected function createFile($path, $content, OutputInterface $output)
	{
		@mkdir(dirname($path), 0777, TRUE); // directory may already exist

		if (file_put_contents("$path.tmp", $content) !== strlen($content) || !rename("$path.tmp", $path)) {
			$output->writeln("Unable to write to '$path'.");
			exit(1);
		}
	}

}
