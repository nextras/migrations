# Symfony Console Commands

Nextras Migrations comes with predefined Symfony Console commands.


## Creating New Migration (`migrations:create`)

Creates a new migration file in a given **group** with a proper name based on the current time and a given **label** (e.g. `2015-03-14-130836-create-users.sql`) and prints its path to standard output.

```
php bin/console migrations:create <group> <label>
```

Instead of writing the full group name (e.g. `structures`), you can write any uniquely identifying prefix (e.g. `str` or `s`).

If you use Doctrine ORM and Doctrine DBAL, the content of the migration file in the `structures` group will automatically be **generated with Doctrine SchemaTool**.


## Executing Migrations (`migrations:continue`)

Updates database schema by running all new migrations. If the table `migrations` does not exist in the current database, it is created automatically.

```
php bin/console migrations:continue
```


## Resetting Migrations (`migrations:reset`)

Drops current database and recreates it from scratch by running all migrations. This should only be used in development.

```
php bin/console migrations:reset
```
