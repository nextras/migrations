#!/usr/bin/env bash
PHP_VERSION_MIN="80000"
PHP_VERSION_MAX="80399"
COMPOSER_REQUIRE="$COMPOSER_REQUIRE nextras/dbal:4.0.*"
DBAL="nextras"
DBAL_DRIVERS="mysql pgsql" # Nextras DBAL has a SQLite driver since 6.0
