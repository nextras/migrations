<?php
namespace Migration\Extensions;

use DibiConnection;
use Migration;
use Nette\Config\Configurator;
use Nette;
use Orm;


/**
 * @author Petr Procházka
 */
class OrmPhp extends SimplePhp
{
	/** @var Orm\IRepositoryContainer */
	private $orm;

	public function __construct(Configurator $configurator, Nette\DI\Container $context, DibiConnection $dibi)
	{
		parent::__construct();
		$this->addParameter('createOrm', function () use ($configurator, $context, $dibi) {
			$orm = $configurator->createServiceOrm($context);
			$orm->getContext()
				->removeService('performanceHelperCache')
				->removeService('dibiConnection')
				->addService('dibiConnection', $dibi)
			;
			return $orm;
		});
		$this->addParameter('context', $context);
		$this->addParameter('dibi', $dibi);
	}

	public function getParameters()
	{
		$parameters = parent::getParameters();
		if ($this->orm) $this->orm->flush();
		$this->orm = $parameters['createOrm']();
		$parameters['orm'] = $this->orm;
		return $parameters;
	}

	public function getName()
	{
		return 'orm.php';
	}

	/**
	 * @param Migration\File $sql
	 * @return int
	 */
	public function execute(Migration\File $sql)
	{
		$count = parent::execute($sql);
		$this->orm->flush();
		$this->orm = NULL;
		return $count;
	}
}
