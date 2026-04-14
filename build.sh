#!/bin/bash
set -e

export PHPRC="$(pwd)"
export COMPOSER_MEMORY_LIMIT=-1

composer install --no-dev --optimize-autoloader
yarn build
