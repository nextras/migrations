<?php declare(strict_types = 1);

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    New BSD License
 * @link       https://github.com/nextras/migrations
 */

namespace Nextras\Migrations;

use Throwable;


/**
 * Marker interface.
 */
interface Exception extends Throwable
{
}


/**
 * Error in usage or implementation.
 */
class LogicException extends \LogicException implements Exception
{
}


/**
 * Error during runtime.
 */
abstract class RuntimeException extends \RuntimeException implements Exception
{
}


/**
 * Executing migration has failed.
 */
class ExecutionException extends RuntimeException
{
}


/**
 * Permission denied, file not found...
 */
class IOException extends RuntimeException
{
}


/**
 * Lock cannot be released or acquired.
 */
class LockException extends RuntimeException
{
}
