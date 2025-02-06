Nextras Migrations
==================

[![Build Status](https://github.com/nextras/migrations/actions/workflows/qa.yaml/badge.svg)](https://github.com/nextras/migrations/actions/workflows/qa.yaml)
[![Downloads this Month](https://img.shields.io/packagist/dm/nextras/migrations.svg?style=flat)](https://packagist.org/packages/nextras/migrations)
[![Stable Version](https://img.shields.io/packagist/v/nextras/migrations.svg?style=flat)](https://packagist.org/packages/nextras/migrations)

For more information read **[documentation](https://nextras.org/migrations/docs)**.

**Supported databases:**
* PostgreSQL
* MySQL

**Supported DBALs:**
* [Nextras DBAL](https://github.com/nextras/dbal)
* [Nette Database](https://github.com/nette/database)
* [Doctrine DBAL](https://github.com/doctrine/dbal)
* [dibi](https://github.com/dg/dibi)


Development & Running Integration Tests in Docker
------------------------------------------------

1. Create `./tests/*.ini` files
   ```bash
   cp tests/php.docker.ini tests/php.ini
   cp tests/drivers.docker.ini tests/drivers.ini
   ```
2. Start containers
    ```bash
    docker-compose up --detach
    ```
3. Run tests
    ```bash
    tests/run-in-docker.sh php84 tests/run-integration.sh
    ```

License
-------

*Based on [Clevis\Migration](https://github.com/Clevis/Migration) by Petr Procházka and further improved.*

New BSD License. See full [license](license.md).
