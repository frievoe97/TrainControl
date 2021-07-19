<?php

$globalNotverzoegerung = 2; // Bremsverzögerung bei einer Notbremsung
$globalMinSpeed = 60; // Maximale Geschwindigkeit, wenn keine vorgegeben ist
$globalSpeedInCurrentSection = 60; // Maximale Geschwindigkeit im aktuellen Abschnitt
$globalFirstHaltMinTime = 20; // calculateFahrverlauf -> Zeit fürs Wenden...
$globalIndexBetriebsstelleFreieFahrt = 9999999999999;
$globalFloatingPointNumbersRoundingError = 0.0000000001;
$globalTimeUpdateInterval = 1; // Defines the time intervals in which the current data is sent to the vehicle when it is not changing speed.
$globalTimeOnOneSpeed = 20;

$useSpeedFineTuning = true;