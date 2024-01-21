<?php declare(strict_types = 1);

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
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Translation\LocaleAwareInterface;


class CreateCommand extends BaseCommand
{
	/** content source options */
	const CONTENT_SOURCE_DIFF = 'diff';
	const CONTENT_SOURCE_STDIN = 'stdin';
	const CONTENT_SOURCE_EMPTY = 'empty';

	/** @var string */
	protected $defaultContentSource = self::CONTENT_SOURCE_DIFF;


	public static function getDefaultName(): string
	{
		return 'migrations:create';
	}


	public static function getDefaultDescription(): string
	{
		return 'Creates new migration file with proper name (e.g. 2015-03-14-130836-label.sql)';
	}


	public function setDefaultContentSource(string $defaultContentSource): void
	{
		$this->defaultContentSource = $defaultContentSource;
	}


	protected function configure(): void
	{
		$this->setHelp('Prints path of the created file to standard output.');

		$this->addArgument('type', InputArgument::REQUIRED, $this->getTypeArgDescription());
		$this->addArgument('label', InputArgument::REQUIRED, 'short description');

		$this->addOption('empty', null, InputOption::VALUE_NONE, 'create empty file');
		$this->addOption('diff', null, InputOption::VALUE_NONE, 'use schema diff as file content');
		$this->addOption('stdin', null, InputOption::VALUE_NONE, 'use stdin as file content');
	}


	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$group = $this->getGroup($input->getArgument('type'));
		$path = $this->getPath($group, $input->getArgument('label'));
		$content = $this->getFileContent($group, $this->getFileContentSource($input));

		$this->createFile($path, $content, $output);
		$output->writeln($path);

		return 0;
	}


	protected function getPath(Group $group, string $label): string
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


	protected function getGroup(string $type): Group
	{
		$groupNamePattern = preg_quote($type, '~');
		$groupNamePattern = str_replace('\\-', '\\w*+\\-', $groupNamePattern);
		$groupNamePattern = "~^$groupNamePattern~";

		$matchedGroups = [];
		foreach ($this->config->getGroups() as $group) {
			if (preg_match($groupNamePattern, $group->name)) {
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


	protected function getFileName(string $label, string $extension): string
	{
		if (preg_match('#^[a-z0-9.-]++$#i', $label)) {
			$slug = strtolower($label);

		} elseif (class_exists(Strings::class)) {
			$slug = Strings::webalize($label, '.');

		} elseif (
			interface_exists(LocaleAwareInterface::class) &&
			class_exists(AsciiSlugger::class)
		) {
			$slugger = new AsciiSlugger('en');
			$slug = $slugger->slug($label)->toString();

		} else {
			throw new Nextras\Migrations\LogicException("Provided label '$label' contains invalid characters.");
		}

		return date('Y-m-d-His-') . $slug . '.' . $extension;
	}


	protected function hasNumericSubdirectory(string $dir, ?string &$found): bool
	{
		$items = @scandir($dir); // directory may not exist

		if ($items) {
			foreach ($items as $item) {
				if ($item !== '.' && $item !== '..' && is_dir($dir . '/' . $item)) {
					$found = $dir . '/' . $item;
					return true;
				}
			}
		}

		return false;
	}


	protected function getTypeArgDescription(): string
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


	protected function getFileContentSource(InputInterface $input): string
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


	protected function getFileContent(Group $group, string $source): string
	{
		if ($source === self::CONTENT_SOURCE_DIFF && $group->generator !== null) {
			return $group->generator->generateContent();

		} elseif ($source === self::CONTENT_SOURCE_STDIN) {
			return stream_get_contents(STDIN);

		} else {
			return '';
		}
	}


	protected function createFile(string $path, string $content, OutputInterface $output): void
	{
		@mkdir(dirname($path), 0777, true); // directory may already exist

		if (file_put_contents("$path.tmp", $content) !== strlen($content) || !rename("$path.tmp", $path)) {
			$output->writeln("Unable to write to '$path'.");
			exit(1);
		}
	}
}
