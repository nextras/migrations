#!/usr/bin/env bash
PHP_VERSION_MIN="80100"
PHP_VERSION_MAX="80699"
COMPOSER_REQUIRE="$COMPOSER_REQUIRE nextras/dbal:5.0.*"
DBAL="nextras"
DBAL_DRIVERS="mysql pgsql" # Nextras DBAL has a SQLite driver since 6.0
