<?php declare(strict_types = 1);

namespace NextrasTests\Migrations;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Nextras\Migrations\Bridges\SymfonyBundle\NextrasMigrationsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Config\ContainerParametersResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;


class TestSymfonyKernel extends Kernel
{
	public function __construct(
		private string $configPath,
		private array $parameters,
	)
	{
		parent::__construct('dev', true);
	}


	public function getRootDir(): string
	{
		return TEMP_DIR . '/symfony-bundle';
	}


	public function getProjectDir(): string
	{
		return TEMP_DIR . '/symfony-bundle';
	}


	public function registerBundles(): iterable
	{
		return [
			new FrameworkBundle(),
			new DoctrineBundle(),
			new NextrasMigrationsBundle(),
		];
	}


	public function registerContainerConfiguration(LoaderInterface $loader): void
	{
		$loader->load(function (ContainerBuilder $container): void {
			$container->addResource(new ContainerParametersResource($this->parameters));
			foreach ($this->parameters as $key => $value) {
				$container->setParameter($key, $value);
			}
		});

		$loader->load($this->configPath);
	}
}
