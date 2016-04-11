#!/usr/bin/env bash

php=`which php`
what="$@"

$php docroot/core/scripts/run-tests.sh --non-html --color --verbose $what
if [ ! "$?" -eq 0 ]; then
  echo "Usage: ./test.sh --url URL <group GROUP | --class CLASS>"
  echo
  echo "Example 1 (test group): ./test.sh --url http://iucnwildlifed8.local.ro elis_consumer"
  echo "Example 2 (test class): ./test.sh --url http://iucnwildlifed8.local.ro --class Drupal\\iucn_search\\Tests\\SolrFacetTest"
  echo
  echo "Available GROUPs and CLASSes:"
  php docroot/core/scripts/run-tests.sh --list | grep -e "elis" -e "iucn"
  exit -1
fi
