# Wildlex deployment

1. Ensure a decent value for `max_allowed_packet`, for instance: `max_allowed_packet=768M`


## Installation

1. Archive the prod files from Acquia (do a rsync on local and archive): cd sites/default/files && tar cvfz wildlex-public-files.tar.gz .
2. Copy to ecolex.org:/root/wildlex-public-files.tar.gz
3. MySQL dump the database and bzip2
4. Copy the database to ecolex.org:/root/wildlex-database.sql.bz2
5. Execute the recipe: ansible-playbook -b -u cristiroma --ask-vault-pass -i hosts.prod install.yml
6. Few customizations:
    - drush cim vcs -y
    - drush pmu composer_manager acquia_search acquia_connector -y
    - drush updatedb -y
7. Rerun the migrations and reindex the website

## Edit the vault

ansible-vault edit roles/wildlex/defaults/main_vault.yml