# IUCN Wildlife

# Installation

## Pre-requisites

1. Drush (8.0)
2. Virtual host for your Drupal instance that points to the `docroot` directory from this repo.

## Quick start

1. Copy and rename `docroot/sites/example.settings.local.php` to `docroot/sites/default/settings.local.php`.
2. Append your database configuration settings:

  ```php
  /**
   * Database settings.
   */
  $databases['default']['default'] = array(
    'database' => 'iucn_wildlife',
    'username' => 'username',
    'password' => 'password',
    'prefix' => '',
    'host' => 'localhost',
    'port' => '3306',
    'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
    'driver' => 'mysql',
  );
  ```

3. Download drush aliases from Acquia (https://docs.acquia.com/cloud/drush-aliases).
4. Run `./install.sh`

### Configuration management

#### Export

```
$ drush config-export vcs # shared between environments
$ drush config-get <config-name> > config/<env>/<config-name>.yml # environment-specific overrides
```

**config-name:** The config object name, for example "system.site".
**env:** The environment name, for example "local", "dev", "prod".

#### Import

```
$ drush config-import vcs # shared between environments
$ drush config-import <env> --partial # environment-specific overrides
```

**env:** The environment name, for example "local", "dev", "prod".

### Theme development

CSS files (the `css` directory) are generated from LESS sources (the `less` directory). Don't edit the CSS files directly, use `grunt` to recompile.

```
$ npm install grunt-cli -g # install grunt command line interface if not already installed
$ npm install
$ grunt build # or watch
```
