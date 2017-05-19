# Testing

run test from console

``` bash
$ bin/phpunit
```

or you can setup vars for pdo driver like this

``` bash
export DB_NAME=acme && export DB_USER=acme && export DB_PASSWD=acme && export DB=mysql && export DB_HOST=acme && bin/phpunit 
```

according to default credentials for travis CI you must run

``` bash
export DB_NAME=orm_behaviors_test && export DB_USER=root && unset DB_PASSWD && unset DB && unset DB_HOST && bin/phpunit
```