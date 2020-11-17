# TrainControl

## Englisch

### Summary

The goal of the work is the development of a control software that continuously monitors the vehicles on the punctual monitored network sections in order to simulate new operating procedures such as moving block in the railroad operation and experiment field (EBuEf) of the Technical University Berlin and to be able to control the vehicles more realistically (e.g. by stopping at the platform with pinpoint accuracy depending on the train length).

In order for the trains to be continuously monitored and thus for the position to be determined exactly, the system must know the current speed at all times. In addition to the speed during constant travel, the system also needs to know the speed during the start-up phase, when the trains are coasting and when they are braking. For the system to know these speeds, it must know exactly how the trains behave during acceleration, coasting and braking. To enable the system to be calibrated, the existing punctual monitoring system can be used, which detects when a train enters a block section. In addition, the system must have accurate information about the rail network and the timetable, since the trains run on a timetable basis. These two pieces of information are stored in a database and can therefore also be read by the system.

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

Damit die Züge kontinuierlich überwacht werden können und somit die Position exakt bestimmt werden kann, muss zu jedem Zeitpunkt dem System die aktuelle Geschwindigkeit bekannt sein. Neben der Geschwindigkeit bei Konstantfahrten gehört auch die Geschwindigkeit in der Anfahrphase, beim Ausrollen und beim Bremsen der Züge. Damit das System diese Geschwindigkeiten kennt, muss es exakt wissen, wie die Züge sich beim Beschleunigen, Ausrollen und Bremsen verhalten. Damit das System auch kalibriert werden kann, kann die schon vorhandene punkförmige Überwachung genutzt werden, welche die Einfahrt eines Zuges in einen Blockabschnitt erkennt. Zudem muss das System genaue Informationen über das Schienennetz und den Fahrplan haben, da die Züge fahrplanbasiert fahren. Diese beiden Informationen sind in einer Datenbank hinterlegt und somit auch für das System einlesbar.

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



