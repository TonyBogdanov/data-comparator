#!/usr/bin/env bash

cd "$( dirname "$( dirname "$( realpath "${0}" )" )" )"

rm -rf ./coverage
./vendor/bin/phpunit
