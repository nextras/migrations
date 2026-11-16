#!/usr/bin/env bash
PHP_VERSION_MIN="80000"
PHP_VERSION_MAX="80699"
COMPOSER_REQUIRE="$COMPOSER_REQUIRE doctrine/orm:^2.20"
COMPOSER_REQUIRE="$COMPOSER_REQUIRE doctrine/dbal:^3.10"
DBAL="doctrine"
DBAL_DRIVERS="mysql pgsql" # the Doctrine schema comparator reports false differences on SQLite
