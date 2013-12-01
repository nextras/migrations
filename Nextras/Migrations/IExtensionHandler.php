<?php
namespace Nextras\Migrations;

use Nextras\Migrations\Entities\File;


/**
 * @author Petr Procházka
 * @author Jan Tvrdík
 */
interface IExtensionHandler
{

	/**
	 * @param  File
	 * @return int number of queries
	 */
	public function execute(File $file);

}
