#!/usr/bin/env bash

php=`which php`
what="$@"

$php docroot/core/scripts/run-tests.sh --non-html --color --verbose $what
if [ ! "$?" -eq 0 ]; then
  echo "Usage: ./test.sh --url URL <group GROUP | --class CLASS >, example: ./test.sh --url http://iucnwildlifed8.local.ro elis_consumer"
  echo
  echo "Available GROUPs and CLASSes:"
  php docroot/core/scripts/run-tests.sh --list | grep -e "elis" -e "iucn"
  exit -1
fi
