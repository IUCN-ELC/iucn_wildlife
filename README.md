# IUCN WILDLEX 

www.wildlex.org

# Installation

## Pre-requisites

1. Docker installed and running on your machine
2. A snapshot of the staging data (db and sites/default/files)

## Quick start

1. Go to an empty directory and clone this repository:

    ```
    cd ~/Work
    git clone https://github.com/IUCN-ELC/iucn_wildlife
    ```

2. Edit your local `/etc/hosts`, and add the `server_name` from `./.docker/conf_nginx/project.conf` and db/solr6 containers:

    ```
    127.0.0.1 wildlex.local db solr6
    ```

    * On Linux/Mac use `sudo vim /etc/hosts`
    * On Windows right-click on `Notepad` and select `Run as Administrator` then edit `C:\WINDOWS\system32\drivers\etc\hosts`

2. `cp docker-compose.override.example-dev.yml docker-compose.override.yml` and customize (look for TODO).

3. Optional
   * Change the port mapping for service `nginx` if you already have a web server running on port `80`, example: `127.0.0.1:8080:80`
   * If you are going to access the mysql database directly, uncomment the ports mapping from service `db`.

4. `cp docroot/sites/default/settings.local.example-dev.php docroot/sites/default/settings.local.php` and customize (look for TODO). Remember the database settings must match that in `docker-compose.override.yml`)

5. run `docker-compose up` in the project directory and make sure that there are no errors in the console. On production use `docker-compose up -d` to start the daemons in background.

On Fedora Linux you must switch to `root` account before, for example: `sudo docker-compose up`

6. Open http://wildlex.local. You should see a default Drupal install.php


## Installing Drupal
Choose one of the two solutions.

### a. Use the install script (requires SSH access to the production server)
1. Run installation script: ```./install.sh prod```
2. Restore sites/default/files.
    ```
    $ cd docroot
    $ drush -v rsync @prod:%files @self:%files
    ```

### b. Retrieve the database using ```curl```
1. Restore database contents:
    ```
    $ curl -o db.sql.gz https://www.wildlex.org/sites/default/files/db.sql.gz
    $ docker cp db.sql.gz wl_db:/tmp
    $ docker exec -it wl_db bash
    $ gunzip -c db.sql.gz | mysql -u root -proot drupal
    ```

2. Update the instance

    ```
    $ ./update.sh
    ```
## Running drush


Below are some common Drupal commands to help you speed-up development:

```
drush uli <uid|username>                     # Get a one-time login link to reset password or log in as another user.
drush upwd --password=newpass <uid|username> # Reset the password for an user
drush rsync @ENV:%files @self:%files         # Sync the "files" folder with other instances (prod, test, staging etc.).
drush sql-sync @ENV @self                    # Sync database with another instance you have access to
```

## Create sql-dump

```
$ drush sql-dump --gzip --structure-tables-list=cache,cache_*,watchdog > db.sql.gz
```

## Configuration management

#### Export

```
$ drush config-export sync # shared between environments
$ drush config-get <config-name> > config/<env>/<config-name>.yml # environment-specific overrides
```

**config-name:** The config object name, for example "system.site".  
**env:** The environment name, for example "local", "dev", "prod".

#### Import

```
$ drush config-import sync # shared between environments
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