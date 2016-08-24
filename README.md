# backupdbcloud


## Parameters
You must set in the bash script the pwd, user, host SQL

You must set the following parameters :

dropbox_api_key:
dropbox_api_secret: 
dropbox_api_access_token:  
backup_db_dropbox_folder: /dropbox_folder
backup_db_prod_folder: /local_db_prod_folder
backup_db_dev_folder:  /local_db_dev_folder
# keep 1 week of file - 4 backups by day (check cron)
backup_db_prod_filetokeep: 28
backup_db_dev_filetokeep: 4
backup_db_prod_databases: [DB_NAME]
backup_db_dev_databases: [DB_NAME]


## Cron

You can add the task as a cron such as :
0 0   1 * * php bin/console script:backup_db dev

0 */6 * * * php bin/console script:backup_db prod

Backup for prod are done every 6 hours
Backup for dev are done daily
