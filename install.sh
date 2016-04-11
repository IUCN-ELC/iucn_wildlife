#!/bin/bash

# Go to docroot/
cd docroot/
drush sql-drop -y

env="test"
if [ ! -z "$1" ]; then
  env=$1
fi

echo "Getting '$env' environment database ..."
drush sql-sync "@iucnwildlifed8.$env" @self -y

echo "Importing 'default' configuration..."
drush cim vcs -y

echo "Importing 'local' configuration..."
drush cim local --partial -y

echo "Running database pending updates ..."
drush updatedb

echo "Resetting admin password..."
drush user-password admin --password="password"

echo "Enabling simpletest module..."
drush en -y simpletest

drush cr
echo "Done"
