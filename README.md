# TrainControl

## Englisch

### Summary

The goal of the work is the development of a control software that continuously monitors the vehicles on the punctual monitored network sections in order to simulate new operating procedures such as moving block in the railroad operation and experiment field (EBuEf) of the Technical University Berlin and to be able to control the vehicles more realistically (e.g. by stopping at the platform with pinpoint accuracy depending on the train length).

### Instructions

To use this code you have to create a file called db_access.php in the config folder and specify the access to the database. The file should look like this:

```php
<?php

define("DB_STANDARD", 1);

$MySQL_config[1]['host'] = '127.0.0.1';
$MySQL_config[1]['benutzer'] = 'username';
$MySQL_config[1]['passwort'] = 'password';
$MySQL_config[1]['dbname'] = 'ebuef';
```

### Todo
* Declare functions
* Write own functions
* Send data to the trains

## Deutsch

### Zusammenfassung

Das Ziel der Arbeit ist die Entwicklung einer Steuerungssoftware, die auf dem punkförmig überwachten Netzabschnitten die Fahrzeuge kontinuierlich überwacht, um auch neue Betriebsverfahren wie Moving-Block im Eisenbahn-Betriebs- und Experimentierfeld (EBuEf) der Technischen Universität Berlin zu simulieren sowie die Fahrzeuge realitätsnäher steuern zu können (beispielsweise durch punktgenaues Anhalten am Bahnsteig in Abhängigkeit der Zuglänge).

### Anleitung

Um diesen Code nutzen zu können, musst du in dem config Ordner eine Datei namens db_access.php erstellen und den Zugang zu der Datenbank festlegen. Die Datei müsste so aussehen:

```php
<?php

define("DB_STANDARD", 1);

$MySQL_config[1]['host'] = '127.0.0.1';
$MySQL_config[1]['benutzer'] = 'username';
$MySQL_config[1]['passwort'] = 'password';
$MySQL_config[1]['dbname'] = 'ebuef';
```

### Todo
* Funktionen deklarieren
* Eigene Funktionen schreiben
* Daten an die Züge senden



