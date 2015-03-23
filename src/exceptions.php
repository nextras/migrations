<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations;

interface Exception
{
}


abstract class RuntimeException extends \RuntimeException implements Exception
{
}


class LogicException extends \LogicException implements Exception
{
}


class ExecutionException extends RuntimeException
{
}


class IOException extends RuntimeException
{
}


class LockException extends RuntimeException
{
}
