#!/bin/bash

# Go to web/
cd web/

echo "Running database pending updates ..."
drush updatedb -y

echo "Importing 'default' configuration..."
drush cim sync -y

drush cr
echo "Done"
