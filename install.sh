#!/bin/bash

# Go to docroot/
cd docroot/

drush sql-drop -y

drush sql-sync @iucnwildlifed8.prod @self -y

echo "Importing configuration..."
drush cim vcs -y

echo "Updating database..."
drush updatedb

drush cr

echo "Done"
