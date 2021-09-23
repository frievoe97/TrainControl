<?php

// Ermittlung der exakten Ankunftszeit
$useSpeedFineTuning = true;
// Mindestzeit für Beharrungsfahrten verwenden
$useMinTimeOnSpeed = true;
// Fehlermeldung, wenn die Mindestzeit für
// Beharrungsfahrten nicht eingehalten werden kann
$errorMinTimeOnSpeed = false;
// Anpassung der Geschwindigkeit, wenn das Fahrzeug
// zu früh an der Ziel-Betriebsstellen ankommen würde
$slowDownIfTooEarly = true;
// Neukalibrierung der Position verrwenden
$useRecalibration = true;

// Bremsverzögerung bei einer Gefahrenbremsung [m/s^2]
$globalNotverzoegerung = 2;
// Maximale Geschwindigkeit, wenn keine vorgegeben ist [km/h]
$globalTrainVMax = 10;
// Maximale Geschwindigkeit im aktuellen Abschnitt [km/h]
$globalSpeedInCurrentSection = 60;
// Mindesthaltezeit am ersten fahrplanmäßigen Halt [s]
$globalFirstHaltMinTime = 20;
// Kulanzbereich beim Rechnen mit float-Werten
$globalFloatingPointNumbersRoundingError = 0.0000000001;
// Mindestzeit für Beharrungsfahrten [s]
$globalTimeOnOneSpeed = 20;
// Distanzintervall für Beharrungsfahrten [m]
$globalDistanceUpdateInterval = 1;