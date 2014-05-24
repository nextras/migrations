<?php

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
use Nextras\Migrations\Exception;
use Nextras\Migrations\LogicException;


class OrderResolver
{

	/**
	 * @param  Migration[]
	 * @param  Group[]
	 * @param  File[]
	 * @param  string
	 * @return File[]
	 * @throws Exception
	 */
	public function resolve(array $migrations, array $groups, array $files, $mode)
	{
		$groups = $this->getAssocGroups($groups);
		$this->validateGroups($groups);

		if ($mode === Runner::MODE_RESET) {
			return $this->sortFiles($files);
		} elseif ($mode !== Runner::MODE_CONTINUE) {
			throw new LogicException('Unsupported mode.');
		}

		$migrations = $this->getAssocMigrations($migrations);
		$files = $this->getAssocFiles($files);

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
							'Previously executed migration "%s/%s" has been changed.',
							$groupName, $filename
						));
					}
					unset($files[$groupName][$filename]);

				} elseif ($group->enabled) {
					throw new LogicException(sprintf(
						'Previously executed migration "%s/%s" is missing.',
						$groupName, $filename
					));
				}
			}
		}

		$files = $this->getFlatFiles($files);
		$files = $this->sortFiles($files);
		return $files;
	}


	/**
	 * @param  File[]
	 * @return File[] sorted
	 */
	protected function sortFiles(array $files)
	{
		usort($files, function (File $a, File $b) {
			return strcmp($a->name, $b->name);
		});

		return $files;
	}


	protected function getAssocMigrations(array $migrations)
	{
		$assoc = array();
		foreach ($migrations as $migration) {
			$assoc[$migration->group][$migration->filename] = $migration;
		}
		return $assoc;
	}


	protected function getAssocGroups(array $groups)
	{
		$assoc = array();
		foreach ($groups as $group) {
			$assoc[$group->name] = $group;
		}
		return $assoc;
	}


	protected function getAssocFiles(array $files)
	{
		$assoc = array();
		foreach ($files as $file) {
			$assoc[$file->group->name][$file->name] = $file;
		}
		return $assoc;
	}


	protected function getFlatFiles(array $files)
	{
		$flat = array();
		foreach ($files as $tmp) {
			foreach ($tmp as $file) {
				$flat[] = $file;
			}
		}
		return $flat;
	}


	/**
	 * @param  Group[]
	 * @return void
	 * @throws Exception
	 */
	private function validateGroups(array $groups)
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
