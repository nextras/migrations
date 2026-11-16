#!/usr/bin/env bash
PHP_VERSION_MIN="80000"
PHP_VERSION_MAX="80699"
COMPOSER_REQUIRE="$COMPOSER_REQUIRE dibi/dibi:4.2.*"
DBAL="dibi"

# dibi 4.2 SqliteResult reports no column types, Result::normalize() then uses null as an array offset (E_DEPRECATED on PHP 8.5)
if [[ "$PHP_VERSION" -ge 80500 ]]; then
	DBAL_DRIVERS="mysql pgsql"
fi
