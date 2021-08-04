<?php

$globalNotverzoegerung = 2; // Bremsverzögerung bei einer Notbremsung
$globalMinSpeed = 10; // Maximale Geschwindigkeit, wenn keine vorgegeben ist
$globalSpeedInCurrentSection = 60; // Maximale Geschwindigkeit im aktuellen Abschnitt
$globalFirstHaltMinTime = 20; // calculateFahrverlauf -> Zeit fürs Wenden...
$globalIndexBetriebsstelleFreieFahrt = 9999999999999;
$globalFloatingPointNumbersRoundingError = 0.0000000001;
$globalTimeOnOneSpeed = 10;

$useSpeedFineTuning = true;

$useMinTimeOnSpeed = true;
$errorMinTimeOnSpeed = false;

$slowDownIfTooEarly = true;