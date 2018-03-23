# IUCN WILDLEX 

(www.wildlex.org)

# Installation

## Pre-requisites

1. Docker installed and running on your machine
2. A snapshot of the staging data (db and sites/default/files)

## Quick start

1. cp docker-compose.override.template.yml docker-compose.override.yml
2. Optional
   * If you have SSH access to the staging/production server, edit docker-compose.override.yml and uncomment the php71 volume containing id_rsa and also `cp drush/aliases/example.aliases.local.php drush/aliases/aliases.local.php`, then edit aliases.local.php and set your own user
   * Change the ports mapping for service nginx if you already have a web server running on port 80.
   * If you are going to access the mysql database directly, uncomment the ports mapping from service db.

3. `cp docroot/sites/default/example.settings.local.php docroot/sites/default/settings.local.php`
(remember the database settings must match docker-compose.override.yml)

4. Edit your local /etc/hosts and add the server_name from ./.docker/conf_nginx/project.conf:

    127.0.0.1 wildlex.local

5. run `docker-compose up`, make sure that there are no errors in the console. If everything looks ok, CTRL+C and `docker-compose up -d`

6. Open http://wildlex.local. You should see a default Drupal install.php

7. Restore database contents:

  $ curl -o db.sql.gz https://www.wildlex.org/sites/default/files/db.sql.gz
  $ gunzip db.sql.gz
  $ docker cp db.sql wl_db:/tmp
  $ docker exec -it wl_db bash
  $ mysql -u root -proot drupal < /tmp/db.sql

8. Restore sites/default/files. If you have SSH access to @prod (see step 2):

  $ docker exec -it wl_php bash
  $ cd /var/www/html/docroot
  $ drush -v rsync @prod:%files @self:%files

or 

  $ cd docroot/sites/default
  $ rsync -varzh php@www.wildlex.org:/var/www/html/wildlex/docroot/sites/default/files .

9. Reset admin password
  $ docker exec -it wl_php bash
  $ cd docroot; drush upwd --password=secret 1


### Running drush

TODO: Cristi

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


## Running tests

Use the `test.sh` script to run project related test. Example:

* `./test.sh` - Run all tests from the iucn_search group
* `./test.sh FacetTest` - Run a single test