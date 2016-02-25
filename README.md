# IUCN Wildlife

#Installation#

##Pre-requisites##

1. Drush (8.0)
2. Virtual host for your Drupal instance that points to the docroot/ directory from this repo

##Quick start##

1. Create docroot/sites/default/settings.local.php file.
2. Configure your database within settings.local.php file. Example:
  ```php
      $databases['default']['default'] = array(
        'driver' => 'mysql',
        'database' => 'iucn_wildlife_db',
        'username' => 'username',
        'password' => 'password',
        'host' => 'localhost',
        'prefix' => '',
      );
      
  ```
3. Download drush aliases from Acquia. (https://docs.acquia.com/cloud/drush-aliases)
4. Run ```drush sql-sync @iucnwildlifed8.environment @self -y``` (Replace 'environment' with 'dev', 'test' or 'prod')
