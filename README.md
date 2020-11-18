# TrainControl

## Englisch (German version below)

### Summary

The Railway Operation and Experimental Field (EBuEf) of the Technical University of Berlin offers interested parties such as students the opportunity to learn how to control the railroad in a practical way, since the operation field with real interlockings is partly controlled in the operations center and this is not possible in a real operations center. In order to enable users* to work as practically as possible, the aim is to make EBuEf as realistic as possible.

The single-track part of the route network in EBuEf is currently only monitored in punctuality, which allows the control software to track which vehicle is in which section. Physically, however, the location can only be determined in punctiform areas.

The aim of the work is to develop a control software that continuously monitors the vehicles on the punctual monitored network sections in order to simulate new operating procedures such as moving block in EBuEf and to be able to control the vehicles more realistically (e.g. by stopping precisely at the platform depending on the train length).

In order for the trains to be continuously monitored and thus the position to be determined exactly, the system must know the current speed at all times. In addition to the speed during constant travel, the system also needs to know the speed during the start-up phase, when the trains are rolling out and when they are braking. For the system to know these speeds, it must know exactly how the trains behave during acceleration, coasting and braking. To enable the system to be calibrated, the existing punctual monitoring system can be used, which detects when a train enters a block section. In addition, the system must have accurate information about the rail network and the timetable, since the trains run on a timetable basis. These two pieces of information are stored in a database and can therefore also be read by the system. 

### Instructions

To use this code, you need to create a file called db_access.php in the config folder and specify the access to the database. The file should look like this:

```php
<?php

define("DB_STANDARD", 1);

$MySQL_config[1]['host'] = '127.0.0.1';
$MySQL_config[1]['benutzer'] = 'username';
$MySQL_config[1]['passwort'] = 'password';
$MySQL_config[1]['dbname'] = 'ebuef';

?>
```

### Used programs

* DataGrip (Jetbrains)
* PhpStorm (Jetbrains)
* yEd Graph Editor (yworks)

### Todo
* Declare functions
* Write own functions
  * Delay calculator
  * Acceleration computer
  * Current position of the train
* Send data to the trains

### Sources

* [Systemführung ZBMS - Bestimmung der dynamischen Bremskurven](https://www.google.com/url?sa=t&rct=j&q=&esrc=s&source=web&cd=&cad=rja&uact=8&ved=2ahUKEwjb6Kfj74rtAhXDx4UKHU5oDl8QFjADegQIBBAC&url=https%3A%2F%2Fwww.bav.admin.ch%2Fdam%2Fbav%2Fde%2Fdokumente%2Fthemen%2Fzugbeeinflussung%2Fzbms_dynamische_bremskurven.pdf.download.pdf%2F160707_Bestimmung_der_dynamischen_Bremskurven_V_11_d.pdf&usg=AOvVaw3ipZf7fEzocRlxqQNWxwOO)
* [Maschek, Ulrich - Zugbeeinflussung](https://link.springer.com/chapter/10.1007/978-3-8348-2654-1_7)
* [Wende, Dietrich - Fahrdynamik des Schienenverkehrs](https://www.springer.com/de/book/9783519004196)

## Deutsch

### Zusammenfassung

Das Eisenbahn-Betriebs- und Experimentierfeld (EBuEf) der Technischen Universität Berlin bietet Interessierten wie zum Beispiel Studenten die Möglichkeit, die Steuerung der Bahn auch praktisch zu lernen, da das Betriebsfeld mit echten Stellwerken zum Teil in der Betriebszentrale gesteuert wird und das in einer realen Betriebszentrale nicht möglich ist. Damit die Nutzer*innen möglichst praxisnah arbeiten können, ist es das Ziel das EBuEf so gut wie es geht realistisch zu gestalten.

Der eingleisige Teil des Streckennetzes im EBuEf wird aktuell nur punkförmig überwacht, wodurch die Steuerungssoftware verfolgen kann, welches Fahrzeug sich in welchem Abschnitt befindet. Die Ortung kann physisch jedoch nur punktförmig erfolgen.

Ziel der Arbeit ist die Entwicklung einer Steuerungssoftware, die auf dem punkförmig überwachten Netzabschnitten die Fahrzeuge kontinuierlich überwacht, um auch neue Betriebsverfahren wie Moving-Block im EBuEf zu simulieren sowie die Fahrzeuge realitätsnäher steuern zu können (beispielsweise durch punktgenaues Anhalten am Bahnsteig in Abhängigkeit der Zuglänge).

Damit die Züge kontinuierlich überwacht werden können und somit die Position exakt bestimmt werden kann, muss zu jedem Zeitpunkt dem System die aktuelle Geschwindigkeit bekannt sein. Neben der Geschwindigkeit bei Konstantfahrten gehört auch die Geschwindigkeit in der Anfahrphase, beim Ausrollen und beim Bremsen der Züge. Damit das System diese Geschwindigkeiten kennt, muss es exakt wissen, wie die Züge sich beim Beschleunigen, Ausrollen und Bremsen verhalten. Damit das System auch kalibriert werden kann, kann die schon vorhandene punkförmige Überwachung genutzt werden, welche die Einfahrt eines Zuges in einen Blockabschnitt erkennt. Zudem muss das System genaue Informationen über das Schienennetz und den Fahrplan haben, da die Züge fahrplanbasiert fahren. Diese beiden Informationen sind in einer Datenbank hinterlegt und somit auch für das System einlesbar. 

### Anleitung

Um diesen Code nutzen zu können, muss in dem config Ordner eine Datei namens db_access.php erstellen und den Zugang zu der Datenbank festlegen. Die Datei müsste so aussehen:

```php
<?php

define("DB_STANDARD", 1);

$MySQL_config[1]['host'] = '127.0.0.1';
$MySQL_config[1]['benutzer'] = 'username';
$MySQL_config[1]['passwort'] = 'password';
$MySQL_config[1]['dbname'] = 'ebuef';

?>
```

### Benutzte Programme

* DataGrip (Jetbrains)
* PhpStorm (Jetbrains)
* yEd Graph Editor (yworks)

### Aufgaben
* Funktionen deklarieren
* Eigene Funktionen schreiben
  * Verzögerungsrechner
  * Beschleunigungsrechner
  * Aktuelle Position des Zuges
* Daten an die Züge senden

### Quellen

* [Systemführung ZBMS - Bestimmung der dynamischen Bremskurven](https://www.google.com/url?sa=t&rct=j&q=&esrc=s&source=web&cd=&cad=rja&uact=8&ved=2ahUKEwjb6Kfj74rtAhXDx4UKHU5oDl8QFjADegQIBBAC&url=https%3A%2F%2Fwww.bav.admin.ch%2Fdam%2Fbav%2Fde%2Fdokumente%2Fthemen%2Fzugbeeinflussung%2Fzbms_dynamische_bremskurven.pdf.download.pdf%2F160707_Bestimmung_der_dynamischen_Bremskurven_V_11_d.pdf&usg=AOvVaw3ipZf7fEzocRlxqQNWxwOO)
* [Maschek, Ulrich - Zugbeeinflussung](https://link.springer.com/chapter/10.1007/978-3-8348-2654-1_7)
* [Wende, Dietrich - Fahrdynamik des Schienenverkehrs](https://www.springer.com/de/book/9783519004196)
