#!/bin/bash

# Go to docroot/
cd docroot/

echo "Running database pending updates ..."
drush updatedb -y

echo "Importing 'default' configuration..."
drush cim sync -y

drush cr
echo "Done"
