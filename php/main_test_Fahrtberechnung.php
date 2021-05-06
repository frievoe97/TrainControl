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
$timeDiff = $computerTime - $fixedTestTime;

// Testabschnitt initialisieren




$naechsteAbschnitteID = array(700, 701, 702, 703, 704, 705, 706, 707, 708);
$naechsteAbschnitteLENGTH = array(50, 100, 40, 240, 600, 200, 20, 140, 360);
$naechsteAbschnitteV_MAX = array(120, 20, 120, 10, 50, 50, 120, 120, 70);


if (true) {
	$alleAbschnitte = array();
	$alleAbschnitte = initAbschnitte($naechsteAbschnitteID, $naechsteAbschnitteLENGTH, $naechsteAbschnitteV_MAX, $alleAbschnitte);
}

// Testfahrzeug initialisieren
if (true) {
	$allTrains = array();
	//speed sec pos
	$allTrains = initFzg(007, 78, 0.8, 0, 7861, 0, $allTrains);
	//$allTrains = initFzg(007, 65, 0.8, 0, 7861, 0, $allTrains);
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
		$allTrains[$key] = setTargetSpeed($allTrains, $key, 0, 707, 10, $fixedTestTime + 0);
	}
}

// Fahrtverlauf berechnen:
if (true) {
	foreach ($allTrains as $key => $value) {
		$returnValue = updateNextSpeed($value, $fixedTestTime);
		$allTrains[$key]['position_change'] = $returnValue[0];
		$allTrains[$key]['speed_change'] = $returnValue[1];
		$allTrains[$key]['time_change'] = $returnValue[2];
	}
}

$allTimes = array();



foreach ($allTrains as $key => $value) {
	foreach ($value['time_change'] as $index => $time) {
		array_push($allTimes, array('adresse' => $value['adresse'], 'speed_change' => $value['speed_change'][$index], 'time_change' =>$time));
	}
}




while (sizeof($allTimes) == 0) {
	if ((microtime(true) - $timeDiff) > $allTimes[0]["time_change"]) {
		sendFahrzeugbefehl($allTimes[0]["adresse"], $allTimes[0]["speed_change"]);
		var_dump($allTimes[0]["speed_change"]);
		array_shift($allTimes);
	}
}