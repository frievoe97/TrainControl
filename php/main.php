<?php

require 'vorbelegung.php';
require 'functions.php';
require 'funktionen_abschnitte.php';
require 'prepare/prepare_fahrplan_session.php';
require 'prepare/prepare_fahrplan_aktuell.php';
require 'update/update_fahrzeuge_aktuell.php';
require 'init/init_fzg.php';
require 'init/update_fzg.php';
require 'init/update_fzg_2.php';

// Zeit (Die Berechnung findet in Millisekunden als Nachkommastellen statt)
$DB = new DB_MySQL();
$databaseTime = (float) strtotime($DB->select("SELECT CURRENT_TIMESTAMP")[0]->CURRENT_TIMESTAMP);
unset($DB);
$computerTime = microtime(true);
$fixedTestTime = (float) 1612811700;

// Testabschnitt initialisieren

$naechsteAbschnitteID = array(700, 701, 702, 703, 704, 705, 706, 707, 708);
$naechsteAbschnitteLENGTH = array(50, 100, 40, 240, 7, 100, 20, 140, 360);
$naechsteAbschnitteV_MAX = array(20, 60, 60, 40, 40, 40, 60, 60, 70);

if (true) {
	$alleAbschnitte = array();
	$alleAbschnitte = initAbschnitte($naechsteAbschnitteID, $naechsteAbschnitteLENGTH, $naechsteAbschnitteV_MAX, $alleAbschnitte);
}

// Testfahrzeug initialisieren
if (true) {
	$allTrains = array();
	//speed sec pos
	$allTrains = initFzg(007, 1997, 0.8, 0, 7861, 0, $allTrains);
}

// Aktuelle Position festlegen
if (true) {
	foreach ($allTrains as $key => $value) {
		$allTrains[$key] = setCurrentValues($allTrains, $key, 0, 700, 0);
	}
}

// Nächste Abschnitte für den Zug festlegen
if (true) {
	foreach ($allTrains as $key => $value) {
		$allTrains[$key] = updateNextSections($allTrains, $key, $alleAbschnitte[0]['id'], $alleAbschnitte[0]['length'], $alleAbschnitte[0]['v_max']);
	}
}

// Nächsten Halt festlegen
if (true) {
	foreach ($allTrains as $key => $value) {
		$allTrains[$key] = setTargetSpeed($allTrains, $key, 0, 707, 10, $fixedTestTime + 88);
	}
}

// Fahrtverlauf berechnen:
if (true) {
	foreach ($allTrains as $key => $value) {
		$allTrains[$key] = updateNextSpeed($allTrains, $key, $value, $fixedTestTime);
	}
}