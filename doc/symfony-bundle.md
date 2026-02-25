# Integration with Symfony

Nextras Migrations ships with a Symfony **Bundle** and Symfony **Console commands**.


## Installation

Enable `NextrasMigrationsBundle` in `config/bundles.php`

```php
return [
	...
	Nextras\Migrations\Bridges\SymfonyBundle\NextrasMigrationsBundle::class => ['all' => true],
];
```


## Configuration

Create file `config/packages/nextras_migrations.yaml` with the following content:

```yaml
nextras_migrations:
    dir: '%kernel.project_dir%/migrations' # migrations base directory
    driver: pgsql                          # pgsql or mysql
    dbal: nextras                          # nextras, nette, doctrine or dibi
    with_dummy_data: '%kernel.debug%'
```



## Usage

See [Symfony Commands](symfony-console.md).
