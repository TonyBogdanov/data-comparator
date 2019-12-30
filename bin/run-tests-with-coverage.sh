#!/usr/bin/env bash

cd "$( dirname "$( dirname "$( realpath "${0}" )" )" )"

rm -rf ./coverage
./vendor/bin/phpunit --coverage-html=./coverage --coverage-clover ./coverage/coverage.xml

RESULT=$?

if [ $RESULT -eq 0 ]; then
  wget https://img.shields.io/badge/test-passing-green -O ./coverage/status.svg
else
  wget https://img.shields.io/badge/test-failing-red -O ./coverage/status.svg
fi

./vendor/bin/php-coverage-badger ./coverage/coverage.xml ./coverage/coverage.svg
