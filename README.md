# MyPhp

Simple mysql client on php. Lightweight replacement for the classic client.

## Installation

Just clone it. And don't forget make this script executable.

```
chmod u+x myphp
```

## Usage

```
./myphp --help
Usage: php ./MyPhp.php [OPTIONS] [database]
  --help Display this help and exit.
  -e     Execute command and quit.
  -h     Connect to host.
  -p     Password to use when connecting to server. If password is
         not given it's asked from the tty.
  -P     Port number to use for connection. Default is 3306.
  -u     User for login.
```  

```
./myphp -h HOST -pPASSWORD -e "SHOW TABLES" -u USER -P 3306 DATABASE
+--------------------+
| Tables_in_DATABASE |
+--------------------+
| table_1            |
| table_2            |
+--------------------+
```

or more interactively way

```
./myphp -p -P 3306
host: HOST
username: USERNAME
password: PASSWORD
> USE DATABASE
Empty result
> SHOW TABLES
+--------------------+
| Tables_in_DATABASE |
+--------------------+
| table_1            |
| table_2            |
+--------------------+
> WRONG QUERY
Error: You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the 
right syntax to use near 'WRONG QUERY' at line 1
> quit
```

Also you can upload the `MyPhp.php` from your machine on any host via ssh. Really the script `myphp` is just a pretty 
look alias.

```
[username@localhost ~]$ scp MyPhp.php username@remotehost:~
[username@localhost ~]$ ssh username@remotehost
[username@remotehost ~]$ php MyPhp.php #and so on
```
