#!/usr/bin/env bash
set -o errexit -o pipefail -o nounset
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$DIR")"

"$PROJECT_DIR/vendor/bin/tester" \
	-p php \
	-c "$PROJECT_DIR/tests/php.ini" \
	-o console \
	"$PROJECT_DIR/tests/cases/unit"
