#!/usr/bin/env bash
set -o errexit -o pipefail -o nounset

export COMPOSER_HOME=${COMPOSER_HOME:-$HOME/.config/composer}
export COMPOSER_CACHE_DIR=${COMPOSER_CACHE_DIR:-$HOME/.cache/composer}

PHP_SERVICE="$1"
shift

docker-compose run --rm \
	-e COMPOSER_HOME \
	-e COMPOSER_CACHE_DIR \
	-v $COMPOSER_HOME:$COMPOSER_HOME \
	-v $COMPOSER_CACHE_DIR:$COMPOSER_CACHE_DIR \
	"$PHP_SERVICE" "$@"
