#!/usr/bin/env bash

cd "$( dirname "$( dirname "$( realpath "${0}" )" )" )"

rm -rf ./coverage
./vendor/bin/phpunit --coverage-html=./coverage --coverage-clover ./coverage/coverage.xml
php ./bin/fix-coverage.php
