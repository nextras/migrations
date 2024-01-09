<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Engine;

use Nextras\Migrations\Entities\File;
use Nextras\Migrations\Entities\Group;
use Nextras\Migrations\Entities\Migration;
use Nextras\Migrations\LogicException;


class OrderResolver
{
	/**
	 * @param  list<Migration> $migrations
	 * @param  list<Group>     $groups
	 * @param  list<File>      $files
	 * @return list<File>
	 * @throws LogicException
	 */
	public function resolve(array $migrations, array $groups, array $files, string $mode): array
	{
		$groups = $this->getAssocGroups($groups);
		$this->validateGroups($groups);

		if ($mode === Runner::MODE_RESET) {
			return $this->sortFiles($files, $groups);
		} elseif ($mode !== Runner::MODE_CONTINUE) {
			throw new LogicException('Unsupported mode.');
		}

		$migrations = $this->getAssocMigrations($migrations);
		$files = $this->getAssocFiles($files);
		$lastMigrations = [];

		foreach ($migrations as $groupName => $mg) {
			if (!isset($groups[$groupName])) {
				throw new LogicException(sprintf(
					'Existing migrations depend on unknown group "%s".',
					$groupName
				));
			}

			$group = $groups[$groupName];
			foreach ($mg as $filename => $migration) {
				if (!$migration->completed) {
					throw new LogicException(sprintf(
						'Previously executed migration "%s/%s" did not succeed. Please fix this manually or reset the migrations.',
						$groupName, $filename
					));
				}

				if (isset($files[$groupName][$filename])) {
					$file = $files[$groupName][$filename];
					if ($migration->checksum !== $file->checksum) {
						throw new LogicException(sprintf(
							'Previously executed migration "%s/%s" has been changed. File checksum is "%s", but executed migration had checksum "%s".',
							$groupName, $filename, $file->checksum, $migration->checksum
						));
					}
					unset($files[$groupName][$filename]);

				} elseif ($group->enabled) {
					throw new LogicException(sprintf(
						'Previously executed migration "%s/%s" is missing.',
						$groupName, $filename
					));
				}

				if (!isset($lastMigrations[$groupName]) || strcmp($filename, $lastMigrations[$groupName]) > 0) {
					$lastMigrations[$groupName] = $filename;
				}
			}
		}

		$files = $this->getFlatFiles($files);
		$files = $this->sortFiles($files, $groups);

		foreach ($groups as $group) {
			if (!isset($lastMigrations[$group->name])) {
				continue;
			}

			foreach ($this->getFirstFiles($files) as $file) {
				if (strcmp($file->name, $lastMigrations[$group->name]) >= 0) {
					continue;
				}

				if ($this->isGroupDependentOn($groups, $file->group, $group) || $this->isGroupDependentOn($groups, $group, $file->group)) {
					throw new LogicException(sprintf(
						'New migration "%s/%s" must follow after the latest executed migration "%s/%s".',
						$file->group->name, $file->name, $group->name, $lastMigrations[$group->name]
					));
				}
			}
		}

		return $files;
	}


	/**
	 * @param  list<File> $files
	 * @param  array<string, Group>  $groups (name => Group)
	 * @return list<File> sorted
	 */
	protected function sortFiles(array $files, array $groups): array
	{
		usort($files, function (File $a, File $b) use ($groups): int {
			$cmp = strcmp($a->name, $b->name);
			if ($cmp === 0 && $a !== $b) {
				$cmpA = $this->isGroupDependentOn($groups, $a->group, $b->group);
				$cmpB = $this->isGroupDependentOn($groups, $b->group, $a->group);
				if ($cmpA xor $cmpB) {
					$cmp = ($cmpA ? -1 : 1);

				} else {
					$names = [
						"{$a->group->name}/{$a->name}",
						"{$b->group->name}/{$b->name}",
					];
					sort($names);
					throw new LogicException(sprintf(
						'Unable to determine order for migrations "%s" and "%s".',
						$names[0], $names[1]
					));
				}
			}

			return $cmp;
		});

		return $files;
	}


	/**
	 * Returns true if groupA depends on groupB.
	 *
	 * @param  array<string, Group> $groups (name => Group)
	 */
	protected function isGroupDependentOn(array $groups, Group $groupA, Group $groupB): bool
	{
		$visited = [];
		$queue = $groupB->dependencies;
		$queue[] = $groupB->name;

		while ($node = array_shift($queue)) {
			if (isset($visited[$node])) {
				continue;
			}

			if ($groupA->name === $node) {
				return true;
			}

			$visited[$node] = true;
			foreach ($groups[$node]->dependencies as $dep) {
				$queue[] = $dep;
			}
		}

		return false;
	}


    /**
     * @param  list<Migration> $migrations
     * @return array<string, array<string, Migration>> (group => (filename => Migration))
     */
	protected function getAssocMigrations(array $migrations): array
	{
		$assoc = [];

		foreach ($migrations as $migration) {
			$assoc[$migration->group][$migration->filename] = $migration;
		}

		return $assoc;
	}


    /**
     * @param  list<Group> $groups
     * @return array<string, Group> (name => Group)
     */
	protected function getAssocGroups(array $groups): array
	{
		$assoc = [];

		foreach ($groups as $group) {
			$assoc[$group->name] = $group;
		}

		return $assoc;
	}


    /**
     * @param  list<File> $files
     * @return array<string, array<string, File>> (group => (filename => File))
     */
	protected function getAssocFiles(array $files): array
	{
		$assoc = [];

		foreach ($files as $file) {
			$assoc[$file->group->name][$file->name] = $file;
		}

		return $assoc;
	}


    /**
     * @param  array<string, array<string, File>> $files
     * @return list<File>
     */
	protected function getFlatFiles(array $files): array
	{
		$flat = [];

		foreach ($files as $tmp) {
			foreach ($tmp as $file) {
				$flat[] = $file;
			}
		}

		return $flat;
	}


	/**
	 * @param  list<File> $files
	 * @return array<string, File> (group => File)
	 */
	protected function getFirstFiles(array $files): array
	{
		$firstFiles = [];

		foreach ($files as $file) {
			if (!isset($firstFiles[$file->group->name])) {
				$firstFiles[$file->group->name] = $file;
			}
		}

		return $firstFiles;
	}


	/**
	 * @param  array<Group> $groups
	 * @throws LogicException
	 */
	private function validateGroups(array $groups): void
	{
		foreach ($groups as $group) {
			foreach ($group->dependencies as $dependency) {
				if (!isset($groups[$dependency])) {
					throw new LogicException(sprintf(
						'Group "%s" depends on unknown group "%s".',
						$group->name, $dependency
					));

				} elseif ($group->enabled && !$groups[$dependency]->enabled) {
					throw new LogicException(sprintf(
						'Group "%s" depends on disabled group "%s". Please enable group "%s" to continue.',
						$group->name, $dependency, $dependency
					));
				}
			}
		}
	}
}
