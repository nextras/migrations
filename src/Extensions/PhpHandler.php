<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations\Extensions;

use Nextras\Migrations\Entities\File;
use Nextras\Migrations\IExtensionHandler;
use Nextras\Migrations\IOException;


/**
 * @author Petr Procházka
 * @author Jan Tvrdík
 */
class PhpHandler implements IExtensionHandler
{
	/** @var array<string, mixed> name => value */
	private $params;


	/**
	 * @param  array<string, mixed> $params (name => value)
	 */
	public function __construct(array $params = [])
	{
		$this->params = $params;
	}


	/**
	 * @param  mixed  $value
	 */
	public function addParameter(string $name, $value): self
	{
		$this->params[$name] = $value;
		return $this;
	}


	/**
	 * @return array<string, mixed> (name => value)
	 */
	public function getParameters(): array
	{
		return $this->params;
	}


	public function execute(File $file): int
	{
		extract($this->params, EXTR_SKIP);
		$count = @include $file->path;

		if ($count === false) {
			throw new IOException("Cannot include file '{$file->path}'.");
		}

		return $count;
	}
}
