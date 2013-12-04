<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Engine;

use Nextras\Migrations\Entities\Group;
use Nextras\Migrations\Entities\File;
use Nextras\Migrations\Exception;
use Nextras\Migrations\IOException;
use Nextras\Migrations\LogicException;


class Finder
{

	/**
	 * Finds files.
	 *
	 * @param  Group[]
	 * @param  string[]
	 * @return File[]
	 * @throws Exception
	 */
	public function find(array $groups, array $extensions)
	{
		$files = array();
		foreach ($groups as $group) {
			if (!$group->enabled) {
				continue;
			}
			$items = @scandir($group->directory); // directory may not exist
			if ($items === FALSE) {
				throw new IOException(sprintf('Finder: Directory "%s" does not exist.', $group->directory));
			}

			foreach ($items as $fileName) {
				if ($fileName[0] === '.') {
					continue; // skip '.', '..' and hidden files
				}

				$file = new File;
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
	 * @param  File
	 * @param  string[]
	 * @return string
	 * @throws Exception
	 */
	private function getExtension(File $file, array $extensions)
	{
		$fileExt = NULL;

		foreach ($extensions as $extension) {
			if (substr($file->name, -strlen($extension)) === $extension) {
				if ($fileExt !== NULL) {
					throw new LogicException(sprintf(
						'Finder: Extension of "%s" is ambiguous, both "%s" and "%s" can be used.',
						$file->group->directory . '/' . $file->name, $fileExt, $extension
					));

				} else {
					$fileExt = $extension;
				}
			}
		}

		if ($fileExt === NULL) {
			throw new LogicException(sprintf(
				'Finder: No extension matched "%s". Supported extensions are %s.',
				$file->group->directory . '/' . $file->name, '"' . implode('", "', $extensions) . '"'
			));
		}

		return $fileExt;
	}

}
