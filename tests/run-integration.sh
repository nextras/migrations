#!/usr/bin/env bash
set -o errexit -o pipefail -o nounset
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$DIR")"
PHP_VERSION=$(php -r "echo PHP_VERSION_ID;")


run()
{
	FILENAME="$1"
	INTEGRATION_GROUP="$(basename "$(dirname "$FILENAME")")"

	PHP_VERSION_MIN=""
	PHP_VERSION_MAX=""
	COMPOSER_REQUIRE=""
	DBAL=""

	echo
	echo
	echo "# $FILENAME"
	echo

	source "$FILENAME"

	if [[ ! -z "$PHP_VERSION_MIN" ]] && [[ "$PHP_VERSION" -lt "$PHP_VERSION_MIN" ]]; then
		echo "SKIPPED because current PHP version $PHP_VERSION < $PHP_VERSION_MIN"
		return 0
	fi

	if [[ ! -z "$PHP_VERSION_MAX" ]] && [[ "$PHP_VERSION" -gt "$PHP_VERSION_MAX" ]]; then
		echo "SKIPPED because current PHP version $PHP_VERSION > $PHP_VERSION_MAX"
		return 0
	fi

	create_dbals_ini "$DBAL"
	composer_prepare_dependencies "$COMPOSER_REQUIRE"
	tester_run_integration_group "$INTEGRATION_GROUP"
}


create_dbals_ini()
{
	DBAL="$1"
	INI_PATH="$DIR/dbals.ini"

	rm --force "$INI_PATH"
	if [[ ! -z "$DBAL" ]]; then
		echo "[$DBAL.mysql]" >> "$INI_PATH"
		echo "dbal = $DBAL" >> "$INI_PATH"
		echo "driver = mysql" >> "$INI_PATH"
		echo >> "$INI_PATH"
		echo "[$DBAL.pgsql]" >> "$INI_PATH"
		echo "dbal = $DBAL" >> "$INI_PATH"
		echo "driver = pgsql" >> "$INI_PATH"
	fi
}


composer_prepare_dependencies()
{
	COMPOSER_REQUIRE="$1"

	cp "$PROJECT_DIR/composer.bridgeless.json" "$PROJECT_DIR/composer.json"

	if [[ ! -z "$COMPOSER_REQUIRE" ]]; then
		composer require \
			--no-interaction \
			--no-update \
			--quiet \
			--dev \
			$COMPOSER_REQUIRE
	fi

	composer update \
		--no-interaction \
		--no-progress \
		--quiet
}


tester_run_integration_group()
{
	INTEGRATION_GROUP="$1"

	"$PROJECT_DIR/vendor/bin/tester" \
		-p php \
		-c "$PROJECT_DIR/tests/php.ini" \
		-o console \
		"$PROJECT_DIR/tests/cases/integration/$INTEGRATION_GROUP"
}


if [[ "$#" -gt 0 ]]; then
	run "$1"
	exit $?
fi


for INTEGRATION_GROUP in "$PROJECT_DIR/tests/cases/integration"/*; do
	for FILENAME in "$PROJECT_DIR/tests/matrix/$(basename "$INTEGRATION_GROUP")"/*.sh; do
		run "$FILENAME"
	done
done
