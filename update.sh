#!/bin/bash

# Go to docroot/
cd docroot/

echo "Running database pending updates ..."
drush updatedb

echo "Importing 'default' configuration..."
drush cim sync -y

echo "Enable devel module..."
drush en devel -y

echo "Importing 'local' configuration..."
drush cim local --partial -y

echo "Resetting admin password..."
drush user-password iucn --password="password"

echo "Reindexing Solr ..."
drush sapi-c
drush sapi-i

drush cr
echo "Done"
