# Backups

Run ```docker-compose up -d ``` to start containers and ```docker exec -it l20-php-1 php index.php``` to insert initial dataset. 

## Full backup
Create:
```shell
docker exec l20-db-1 mysqldump -uroot -proot123 --all-databases --source-data --single-transaction > ./backup/full.sql   
```
Time 32s \
Size 26mb

Recover
```shell
docker exec l20-db-1 mysqldump -uroot -proot123 mysql -uroot -proot123 < ./backup/full.sql
```

Time 6s

## Differential 

Create full backup 

```shell
docker run --rm --name percona-xtrabackup --network l20_p20 --volumes-from l20-db-1 percona/percona-xtrabackup \
xtrabackup --backup --datadir=/var/lib/mysql --target-dir=/backup/base --user=root --password=root123
```
Time 11s
Size 106M

Create incremental backup. 

```shell
docker run --rm --name percona-xtrabackup --network l20_p20 --volumes-from l20-db-1 percona/percona-xtrabackup \
xtrabackup --backup --target-dir=/backup/inc1 --incremental-basedir=/backup/base --user=root --password=root123
```
Time 9s \
Size 1.8M

#### Recover
prepare base backup

```shell
docker run --rm --name percona-xtrabackup --network l20_p20 --volumes-from l20-db-1 percona/percona-xtrabackup \
xtrabackup --prepare --apply-log-only --target-dir=/backup/base
```
Time 4s

prepare incremental backup
```shell
docker run --rm --name percona-xtrabackup --network l20_p20 --volumes-from l20-db-1 percona/percona-xtrabackup \
xtrabackup --prepare --apply-log-only --target-dir=/backup/base --incremental-dir=/backup/inc1
```
Time 6s

prepare without --apply-log-only
```shell
docker run --rm --name percona-xtrabackup --network l20_p20 --volumes-from l20-db-1 percona/percona-xtrabackup \
xtrabackup --prepare --target-dir=/backup/base 
```
Time 11s

Restore database using rsync
```shell
rsync -avrP ./backup/base ./data/db/
```
Time 1s


## Incremental

The procedure is the same as when creating differential backups but the backup should contain the difference not with the base backup but with the previous incremental one. 

Create incremental backup where `--target-dir=/backup/inc2` and `--incremental-basedir=/backup/inc1`

```shell
docker run --rm --name percona-xtrabackup --network l20_p20 --volumes-from l20-db-1 percona/percona-xtrabackup \
xtrabackup --backup --target-dir=/backup/inc2 --incremental-basedir=/backup/inc1 --user=root --password=root123
```

Time 8s \
Size 14M

#### Recover

Prepare base dump
```shell
docker run --rm --name percona-xtrabackup --network l20_p20 --volumes-from l20-db-1 percona/percona-xtrabackup \
xtrabackup --prepare --apply-log-only --target-dir=/backup/base --user=root --password=root123
```
Time 4s

Prepare first incremental dump
```shell
docker run --rm --name percona-xtrabackup --network l20_p20 --volumes-from l20-db-1 percona/percona-xtrabackup \
xtrabackup --prepare --apply-log-only --target-dir=/backup/base \
--incremental-dir=/backup/inc1 --user=root --password=root123
```
Time 22s

Prepare second incremental dump

```shell
docker run --rm --name percona-xtrabackup --network l20_p20 --volumes-from l20-db-1 percona/percona-xtrabackup \
xtrabackup --prepare --target-dir=/backup/base \
--incremental-dir=/backup/inc2 --user=root --password=root123
```
Time 12s

#### Restore

Prepare without --apply-log-only

```shell
docker run --rm --name percona-xtrabackup --network l20_p20 --volumes-from l20-db-1 percona/percona-xtrabackup \                                            
xtrabackup --prepare --target-dir=/backup/base --user=root --password=root123
```
Time 8s

Copy back

```shell
docker run --rm --name percona-xtrabackup --network l20_p20 --volumes-from l20-db-1 percona/percona-xtrabackup \
xtrabackup --copy-back --target-dir=/backup/base --user=root --password=root123
```
Time 5s

## Continuous Data Protection (replication)

Continuous Data Protection can be implemented using the replication. In this case additonal space is required to store the database and binlogs. The usage of the binary logs will allow point in time recovery. 


## Summary


| Model        | Size       | Specific time                         | Recovery Time | Cost                                                                                  |
|:-------------|:-----------|:--------------------------------------|:--------------|:--------------------------------------------------------------------------------------|
| Full         | same as DB | no                                    | 6s            | Most easy to implement                                                                |
| Differential | 1.8M       | yes                                   | 1s            | Much more additional actions at the recovery stage.                                   |
| Incremental  | 14M        | yes                                   | 5s            | Even more actions that differential to prepare recovery. Possible consistency issues. |
| CDP          | same as DB | yes (with binary logs) |               | Additional storage required to store replica. Replication configuration.              |