This directory contains the production configuration overrides

# How it works

When you create a new configuration on your local machine, export that
configuration using `drush cex vcs -y`.

Make sure you copy/update devel & production specific settings in their respective directories

# Deployment

Production deployment is a two-step process:

1. `drush cim vcs -y` - This will update the configuration with development configuration
2. `drush cim --partial prod -y` - This will override configuration with production values
