<?php
namespace Migration\Engine;

use Migration;
use Migration\Entities\Group;
use Migration\Entities\File;


class Finder
{

	/**
	 * Finds files.
	 *
	 * @param  Group[]
	 * @param  string[]
	 * @return File[]
	 * @throws \Migration\Exceptions\Exception
	 */
	public function find(array $groups, array $extensions)
	{
		$files = array();
		foreach ($groups as $group)
		{
			if (!$group->enabled) continue;
			$items = @scandir($group->directory); // directory may not exist
			if ($items === FALSE)
			{
				throw new Migration\Exceptions\IOException(sprintf('Finder: Directory "%s" does not exist.', $group->directory));
			}

			foreach ($items as $fileName)
			{
				if ($fileName[0] === '.') continue; // skip '.', '..' and hidden files

				$file = new File();
				$file->group = $group;
				$file->name = $fileName;
				$file->extension = $this->getExtension($file, $extensions);
				$file->checksum = md5_file($group->directory . '/' . $file->name);

				$files[] = $file;
			}
		}
		return $files;
	}

	/**
	 * Returns file extension.
	 *
	 * @param  File
	 * @param  string[]
	 * @return string
	 * @throws \Migration\Exceptions\Exception
	 */
	private function getExtension(File $file, array $extensions)
	{
		$fileExt = NULL;

		foreach ($extensions as $extension)
		{
			if (substr($file->name, -strlen($extension)) === $extension)
			{
				if ($fileExt !== NULL)
				{
					throw new Migration\Exceptions\LoginException(sprintf(
						'Finder: Extension of "%s" is ambiguous, both "%s" and "%s" can be used.',
						$file->group->directory . '/' . $file->name, $fileExt, $extension
					));
				}
				else
				{
					$fileExt = $extension;
				}
			}
		}

		if ($fileExt === NULL)
		{
			throw new Migration\Exceptions\LoginException(sprintf(
				'Finder: No extension matched "%s". Supported extensions are %s.',
				$file->group->directory . '/' . $file->name, '"' . implode('", "', $extensions) . '"'
			));
		}

		return $fileExt;
	}

}
