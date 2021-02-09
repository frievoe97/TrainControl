<?php

require 'vorbelegung.php';
require 'functions.php';
require 'funktionen_abschnitte.php';
require 'prepare/prepare_fahrplan_session.php';
require 'prepare/prepare_fahrplan_aktuell.php';
require 'update/update_fahrzeuge_aktuell.php';
require 'init/init_fzg.php';
require 'init/update_fzg.php';

// Zeit (Die Berechnung findet in Millisekunden als Nachkommastellen statt)
$DB = new DB_MySQL();
$databaseTime = (float) strtotime($DB->select("SELECT CURRENT_TIMESTAMP")[0]->CURRENT_TIMESTAMP);
unset($DB);
$computerTime = microtime(true);
$fixedTestTime = (float) 1612811700;

// Testabschnitt initialisieren
if (true) {
	$alleAbschnitte = array();
	$alleAbschnitte = initAbschnitte(700, 100, 60, $alleAbschnitte);
	$alleAbschnitte = initAbschnitte(701, 50, 60, $alleAbschnitte);
	$alleAbschnitte = initAbschnitte(702, 40, 60, $alleAbschnitte);
	$alleAbschnitte = initAbschnitte(703, 60, 60, $alleAbschnitte);
	$alleAbschnitte = initAbschnitte(704, 7, 40, $alleAbschnitte);
	$alleAbschnitte = initAbschnitte(705, 100, 60, $alleAbschnitte);
	$alleAbschnitte = initAbschnitte(706, 100, 60, $alleAbschnitte);
	$alleAbschnitte = initAbschnitte(707, 100, 60, $alleAbschnitte);
}

// Testfahrzeug initialisieren
if (true) {
	$allTrains = array();
	//speed sec pos
	$allTrains = initFzg(007, 1997, 0.8, 0, 7861, 0, $allTrains);
}
