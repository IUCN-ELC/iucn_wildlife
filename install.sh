#!/bin/bash

# Go to docroot/
cd docroot/
drush sql-drop -y

env="prod"
if [ ! -z "$1" ]; then
  env=$1
fi

echo "Getting '$env' environment database ..."
drush sql-sync "@$env" @self -y

echo "Running database pending updates ..."
drush updatedb -y

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
