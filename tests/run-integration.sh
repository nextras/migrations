#!/usr/bin/env bash
set -o errexit -o pipefail -o nounset
shopt -s globstar
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$DIR")"
PHP_VERSION="$(php --run "echo PHP_VERSION_ID;")"


run()
{
	FILENAME="$1"
	INTEGRATION_GROUP="$(basename "$(dirname "$FILENAME")")"

	PHP_VERSION_MIN=""
	PHP_VERSION_MAX=""
	COMPOSER_REQUIRE=""
	DBAL=""

	source "$FILENAME"

	if [[ ! -z "$PHP_VERSION_MIN" ]] && [[ "$PHP_VERSION" -lt "$PHP_VERSION_MIN" ]]; then
		return 0
	fi

	if [[ ! -z "$PHP_VERSION_MAX" ]] && [[ "$PHP_VERSION" -gt "$PHP_VERSION_MAX" ]]; then
		return 0
	fi

	echo
	echo
	echo "# $FILENAME"
	echo

	create_dbals_ini "$DBAL"

	composer_prepare_dependencies "$COMPOSER_REQUIRE"
	tester_run_integration_group "$INTEGRATION_GROUP"
}


create_dbals_ini()
{
	DBAL="$1"
	INI_PATH="$PROJECT_DIR/tests/dbals.ini"

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
	echo "Composer: installing $COMPOSER_REQUIRE"

	if [[ ! -z "$COMPOSER_REQUIRE" ]]; then
		composer require \
			--no-interaction \
			--no-update \
			--dev \
			--quiet \
			--with-all-dependencies \
			$COMPOSER_REQUIRE
	fi

	composer update \
		--no-interaction \
		--no-progress \
		--quiet \
		--with-all-dependencies
}


tester_run_integration_group()
{
	INTEGRATION_GROUP="$1"

	"$PROJECT_DIR/vendor/bin/tester" \
		-C \
		"$PROJECT_DIR/tests/cases/integration/$INTEGRATION_GROUP"
}


sudo composer self-update --2


if [[ "$#" -eq 0 ]]; then
	for FILENAME in "$PROJECT_DIR/tests/matrix"/**/*.sh; do
		run "$FILENAME"
	done

else
	for ARG in "$@"; do
		if [[ -f "$ARG" ]]; then
			run "$ARG"

		elif [[ -d "$ARG" ]]; then
			for FILENAME in "$ARG"/**/*.sh; do
				run "$FILENAME"
			done

		else
			echo "Invalid argument, $ARG is neither file nor directory"
			exit 1
		fi
	done
fi
