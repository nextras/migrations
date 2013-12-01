<?php
namespace Migration\Engine;

use Migration\Entities\File;
use Migration\Entities\Group;
use Migration\Entities\Migration;
use Migration\Exceptions\LogicException;


class OrderResolver
{

	const MODE_CONTINUE = 'continue';
	const MODE_RESET = 'reset';

	/**
	 * @param  Migration[]
	 * @param  Group[]
	 * @param  File[]
	 * @param  string
	 * @return File[]
	 * @throws \Migration\Exceptions\Exception
	 */
	public function resolve(array $migrations, array $groups, array $files, $mode)
	{
		$groups = $this->getAssocGroups($groups);
		$this->validateGroups($groups);

		if ($mode === self::MODE_RESET) return $this->sortFiles($files);

		$migrations = $this->getAssocMigrations($migrations);
		$files = $this->getAssocFiles($files);
		$lastMigration = NULL;

		foreach ($migrations as $groupName => $mg)
		{
			if (!isset($groups[$groupName]))
			{
				throw new LogicException(sprintf(
					'Existing migrations depend on unknown group "%s".',
					$groupName
				));
			}

			$group = $groups[$groupName];
			foreach ($mg as $filename => $migration)
			{
				if (!$migration->completed)
				{
					throw new LogicException(sprintf(
						'Previously executed migration "%s/%s" did not succeed. Please fix this manually or reset the migrations.',
						$groupName, $filename
					));
				}

				if (isset($files[$groupName][$filename]))
				{
					$file = $files[$groupName][$filename];
					if ($migration->checksum !== $file->checksum)
					{
						throw new LogicException(sprintf(
							'Previously executed migration "%s/%s" has been changed.',
							$groupName, $filename
						));
					}
					unset($files[$groupName][$filename]);
				}
				elseif ($group->enabled)
				{
					throw new LogicException(sprintf(
						'Previously executed migration "%s/%s" is missing.',
						$groupName, $filename
					));
				}

				if ($lastMigration === NULL || strcmp($migration->filename, $lastMigration->filename) > 0)
				{
					$lastMigration = $migration;
				}
			}
		}

		$files = $this->getFlatFiles($files);
		$files = $this->sortFiles($files);
		if ($files && $lastMigration)
		{
			$firstFile = reset($files);
			if (strcmp($firstFile->name, $lastMigration->filename) < 0)
			{
				throw new LogicException(sprintf(
					'New migration "%s/%s" must follow after the latest executed migration "%s/%s".',
					$firstFile->group->name, $firstFile->name, $lastMigration->group, $lastMigration->filename
				));
			}
		}

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
		foreach ($migrations as $migration) $assoc[$migration->group][$migration->filename] = $migration;
		return $assoc;
	}

	protected function getAssocGroups(array $groups)
	{
		$assoc = array();
		foreach ($groups as $group) $assoc[$group->name] = $group;
		return $assoc;
	}

	protected function getAssocFiles(array $files)
	{
		$assoc = array();
		foreach ($files as $file) $assoc[$file->group->name][$file->name] = $file;
		return $assoc;
	}

	protected function getFlatFiles(array $files)
	{
		$flat = array();
		foreach ($files as $tmp) foreach ($tmp as $file) $flat[] = $file;
		return $flat;
	}

	/**
	 * @param  Groups[]
	 * @return void
	 * @throws \Migration\Exception
	 */
	private function validateGroups(array $groups)
	{
		foreach ($groups as $group)
		{
			foreach ($group->dependencies as $dependency)
			{
				if (!isset($groups[$dependency]))
				{
					throw new LogicException(sprintf(
						'Group "%s" depends on unknown group "%s".',
						$group->name, $dependency
					));
				}
				elseif (!$groups[$dependency]->enabled)
				{
					throw new LogicException(sprintf(
						'Group "%s" depends on disabled group "%s". Please enable group "%s" to continue.',
						$group->name, $dependency, $dependency
					));
				}
			}
		}
	}

}
