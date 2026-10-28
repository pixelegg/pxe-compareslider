#!/bin/sh
# Runs a command inside the TYPO3 core-testing image ($IMAGE) with the repository
# mounted at /app. Used by .github/workflows/ci.yml.
set -e
mkdir -p "${HOME}/.cache/composer"
exec docker run --rm \
    -v "${GITHUB_WORKSPACE:-$PWD}":/app -w /app \
    -v "${HOME}/.cache/composer":/tmp/composer-cache \
    -e COMPOSER_CACHE_DIR=/tmp/composer-cache \
    -e COMPOSER_ALLOW_SUPERUSER=1 \
    -e typo3DatabaseDriver=pdo_sqlite \
    "${IMAGE:?IMAGE is not set}" "$@"
