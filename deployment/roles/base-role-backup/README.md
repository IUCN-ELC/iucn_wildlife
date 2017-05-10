Role Name
=========

A role to run push ackups on Rsync and optionally Hetzner

Requirements
------------

For Rsync:

- Hostname, IP address, customer name, project name, web-site name, Rsync user name, public/private ssh key pair, Rsync host name. 
- public key added to Rsync's authorized_keys (see INSTALL.md)
- valid paths on Rsync home storage according to variables (see INSTALL.md)
```
ssh -i ssh_key rsyncuser@rsync.site 'mkdir -p Customer/project/site/logs'
```
For Hetzner:
- Hostname, IP address, customer name, project name, web-site name, Hetzner storage user name, Hetzner storage user passwd
- valid paths on a storage blob according to variables (see INSTALL.md)

Role Variables
--------------

```
# Decide which of the backup destinations should be activated
backup_on_rsync: True
backup_on_hetzner: Trues

# Hostname: to be used in email messages and path creation
server_hostname: 'Hostname'

# preferalbly the public IP of the server; will be used in email messages
server_ip: '192.38.28.1'

# email address to receive daily back-up reports
backup_emailrec: 'morespam@msn.com'

# List of back-up items; leave out the final "/" for folders; "exclude:" MUST be present even if it's empty. 
backup_paths:
  - path: "/etc"
    exclude:

  - path: "/var/log"
    exclude:
      - "/var/log/httpd"
      - "/var/log/secure"

  - path: "${TEMPLOCAL}/some_DB.sql.gz"
    exclude:

# Rsync specific

# a list of shell commands to be run before the main rsync ones; "${TEMPLOCAL}" is a shell variable and it'll be expanded to {{backup_rstemp}}; 
# NOTES:
# 	- ${TEMPLOCAL} is used during the prep stage and later on in {{backup_paths}}
# 	- so far there is no "post" so don't stop daemons in the backup_rsprep section. 
#	  - command results are captured in order to be sent via email so proper redirection is advised (see INSTALL.md)
backup_rsprep:
  - "mysqldump some_DB 2>&1 1>${TEMPLOCAL}/some_DB.sql"
  - "gzip ${TEMPLOCAL}/some_DB.sql  2>&1 1>/dev/null"

# project details; don't use spaces as variables will be used to build various paths and names. 
backup_rscust: 'rscust'
backup_rsproject: 'rsproject'
backup_rssite: 'rssite.example.com'

# how to access and identify yourself when talking to Rsync.net
backup_rsuser: 'rsuser'
# private key file used in pair with the public one added on Rsync's authorized_keys; should be placed in recipe's "files"
backup_rskey: 'rskey'
# remote rsync host 
backup_rshost: 'rshost'

# a folder used for temporary storage; each morning will contain logs (including the errors) and other archives (e.g. sql)
backup_rstemp: '/var/www/html/tmp/rstmp'


# Hetzner specific

# a list of shell commands to be run before the tar archives are built; "${TEMPLOCAL}" is a shell variable and it'll be expanded to {{backup_hztemp}}; 
# NOTES:
#   - ${TEMPLOCAL} is used during the prep stage and later on in {{backup_paths}}
#   - so far there is no "post" so don't stop daemons in the backup_rsprep section. 
#   - command results are captured in order to be sent via email so proper redirection is advised (see INSTALL.md)
backup_hzprep:
  - "mysqldump some_DB 2>&1 1>${TEMPLOCAL}/some_DB.sql"
  - "gzip ${TEMPLOCAL}/some_DB.sql  2>&1 1>/dev/null"


# Hetzner backup password (can be stored in vault as vault_backup_hzpwd)
backup_hzpwd: ''
backup_hzuser: ''

# by default only daily back-ups are installed
backup_want_monthly: False
backup_want_yearly: False

backup_hztemp: '/var/www/html/tmp/hztmp'


```


Dependencies
------------

- base-role-init


Example Playbook
----------------
```
    # base-role-backup
    backup_on_rsync: True
    backup_on_hetzner: True
    backup_emailrec: 'backup@eaudeweb.ro'
    backup_paths:
      - path: "/etc"
        exclude:

      - path: "/var/log"
        exclude:

      - path: "/run/log/journal"
        exclude:

      - path: "${TEMPLOCAL}/eholcim.sql.gz"
        exclude:

      - path: "/data"
        exclude:
          - "/data/tmp/"

    backup_rsprep:
      - "mysqldump -u root -p{{vault_database_root_password}} eholcim 2>&1 1>${TEMPLOCAL}/eholcim.sql"
      - "gzip ${TEMPLOCAL}/eholcim.sql  2>&1 1>/dev/null"

    backup_rstemp: '/var/www/html/tmp/rstmp'
    backup_rscust: 'Holcim'
    backup_rsproject: 'tempeholcim'
    backup_rssite: 'temp.eholcim.ro'
    backup_rsuser: '16496'
    backup_rskey: 'edw_id_rsa_eholcim_138.201.251.232'
    backup_rshost: 'ch-s010.rsync.net'
```


License
-------

BSD

Author Information
------------------

EDW SRL
