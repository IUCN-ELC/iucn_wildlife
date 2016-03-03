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
4. Run `drush sql-sync @iucnwildlifed8.environment @self -y` (replace `environment` with `dev`, `test` or `prod`).

### Theme development

CSS files (the `css` directory) are genereted from LESS sources (the `less` directory). Don't edit the CSS files directly, use `grunt` to recompile.

```
$ npm install grunt-cli -g # install grunt command line interface if not already installed
$ npm install
$ grunt build # or watch
```
