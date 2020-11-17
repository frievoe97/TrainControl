# TrainControl

To use this code you have to create a file called db_access.php in the config folder and specify the access to the database. The file should look like this:

```php
<?php

define("DB_STANDARD", 1);
$MySQL_config[1]['host'] = '127.0.0.1';
$MySQL_config[1]['benutzer'] = 'username';
$MySQL_config[1]['passwort'] = 'password';
$MySQL_config[1]['dbname'] = 'ebuef';

```
