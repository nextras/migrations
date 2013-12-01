<?php
namespace Migration;


/**
 * @author Petr Procházka
 * @author Jan Tvrdík
 */
interface IExtensionHandler
{

	/**
	 * @param  Entities\File
	 * @return int number of queries
	 */
	public function execute(Entities\File $file);

}
