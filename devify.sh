#!/bin/bash

# Go to web/
cd web/

echo "Enable devel module..."
drush en devel -y

echo "Resetting admin password..."
drush user-password iucn --password="password"

echo "Reindexing Solr ..."
drush sapi-c
drush sapi-i

drush cr
echo "Done"
