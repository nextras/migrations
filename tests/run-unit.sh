#!/usr/bin/env bash
set -o errexit -o pipefail -o nounset
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$DIR")"

"$PROJECT_DIR/vendor/bin/tester" \
	-C \
	-o console \
	"$PROJECT_DIR/tests/cases/unit"
