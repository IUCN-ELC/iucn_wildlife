This directory contains the dev & testing configuration overrides

# How it works

When you create a new configuration on your local machine, export that
configuration using `drush cex vcs -y`.

Make sure you copy/update devel & production specific settings in their respective directories

# Deployment

Devel/Testing deployment is a two-step process:

1. `drush cim vcs -y` - This will update the configuration with development configuration
2. `drush cim --partial dev -y` - This will override configuration with development values
