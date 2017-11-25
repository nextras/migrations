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
	 * @param  Group[]  $groups
	 * @param  string[] $extensions
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

			foreach ($this->getFilesRecursive($group->directory) as $path) {
				$file = new File;
				$file->group = $group;
				$file->name = $this->getName($path);
				$file->path = $group->directory . '/' . $path;
				$file->extension = $this->getExtension($file, $extensions);
				$file->checksum = $this->getChecksum($file);

				$files[] = $file;
			}
		}
		return $files;
	}


	/**
	 * Returns logical name of migration file.
	 * @param  string $path relative path to group directory
	 * @return string
	 */
	protected function getName($path)
	{
		$parts = explode('/', $path);
		$dirName = implode('-', array_slice($parts, 0, -1));
		$fileName = implode('-', array_slice($parts, -1));
		$isPrefix = strncmp($fileName, $dirName, strlen($dirName)) === 0;
		return ($isPrefix ? $fileName : "$dirName-$fileName");
	}


	/**
	 * Returns file extension.
	 * @param  File     $file
	 * @param  string[] $extensions
	 * @return string
	 * @throws Exception
	 */
	protected function getExtension(File $file, array $extensions)
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


	/**
	 * @param  File $file
	 * @return string
	 */
	protected function getChecksum(File $file)
	{
		$content = @file_get_contents($file->path);
		if ($content === FALSE) {
			throw new IOException("Unable to read '$file->path'.");
		}

		return md5(str_replace(["\r\n", "\r"], "\n", $content));
	}


	/**
	 * @param  string  $dir
	 * @return string[]
	 * @throws IOException
	 */
	protected function getFilesRecursive($dir)
	{
		$items = $this->getItems($dir);
		foreach ($items as $i => $item) {
			// skip '.', '..' and hidden files
			if ($item[0] === '.') {
				unset($items[$i]);

			// year or month
			} elseif (ctype_digit($item) /*&& is_dir($item)*/) {
				unset($items[$i]);
				foreach ($this->getFilesRecursive("$dir/$item") as $subItem) {
					$items[] = "$item/$subItem";
				}
			}
		}

		return array_values($items);
	}


	/**
	 * @param  string $dir
	 * @return array
	 */
	protected function getItems($dir)
	{
		return @scandir($dir) ?: []; // directory may not exist
	}

}
