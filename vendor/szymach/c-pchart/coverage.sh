#!/bin/bash
set -ev
if [ ! -f bin/ocular.phar ]; then
    wget -O bin/ocular.phar https://scrutinizer-ci.com/ocular.phar
fi

php bin/ocular.phar code-coverage:upload --format=php-clover tests/_output/coverage.xml
