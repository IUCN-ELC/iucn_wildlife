#!/bin/bash

# Go to docroot/
cd docroot/

drush sql-drop -y

drush sql-sync @iucnwildlifed8.dev @self -y

drush cim vcs -y

drush updatedb

drush cr

echo "Done..."
