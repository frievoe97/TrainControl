<?php

// Ermittelt die Daten aller Fahrzeuge.
function getAllTrains () {

	global $cacheAdresseToID;
	global $cacheIDToAdresse;
	global $globalTrainVMax;

	$allAdresses = getAllAdresses();
	$DB = new DB_MySQL();
	$allTrains = array();
	$id = null;

	foreach ($allAdresses as $adress) {
		$train_fahrzeuge = get_object_vars($DB->select("SELECT `".DB_TABLE_FAHRZEUGE."`.`id`, `".DB_TABLE_FAHRZEUGE."`.`adresse`, `".DB_TABLE_FAHRZEUGE."`.`speed`, `".DB_TABLE_FAHRZEUGE."`.`dir`, `".DB_TABLE_FAHRZEUGE."`.`zugtyp`, `".DB_TABLE_FAHRZEUGE."`.`zuglaenge`, `".DB_TABLE_FAHRZEUGE."`.`verzoegerung`, `".DB_TABLE_FAHRZEUGE."`.`zustand` FROM `".DB_TABLE_FAHRZEUGE."` WHERE `".DB_TABLE_FAHRZEUGE."`.`adresse` = $adress")[0]);
		$id = $train_fahrzeuge["id"];
		$train_daten = $DB->select("SELECT `".DB_TABLE_FAHRZEUGE_DATEN."`.`baureihe` FROM `".DB_TABLE_FAHRZEUGE_DATEN."` WHERE `".DB_TABLE_FAHRZEUGE_DATEN."`.`id` = $id")[0]->baureihe;
		$train_baureihe = $DB->select("SELECT `".DB_TABLE_FAHRZEUGE_BAUREIHEN."`.`vmax` FROM `".DB_TABLE_FAHRZEUGE_BAUREIHEN."` WHERE `".DB_TABLE_FAHRZEUGE_BAUREIHEN."`.`nummer` = $train_daten");

		if (sizeof($train_baureihe) != 0) {
			$train_baureihe_return["v_max"] = intval($train_baureihe[0]->vmax);
		} else {
			$train_baureihe_return["v_max"] = $globalTrainVMax;
		}

		$id = intval($train_fahrzeuge["id"]);
		$cacheAdresseToID[intval($train_fahrzeuge["adresse"])] = intval($id);
		$returnArray = array_merge($train_fahrzeuge, $train_baureihe_return);
		$allTrains[$id] = $returnArray;
	}

	unset($DB);

	$cacheIDToAdresse = array_flip($cacheAdresseToID);

	return $allTrains;
}

// Ermittelt alle Fahrzeuge im eingleisigen Netz und gibt die neu hinzugefügten
// und entfernten Fahrzeuge getrennt zurück.
function updateAllTrainsOnTheTrack () {

	global $allTrainsOnTheTrack;
	$newTrains = array();
	$removedTrains = array();
	$allTrains = array();
	$DB = new DB_MySQL();
	$foundTrains = $DB->select("SELECT DISTINCT `".DB_TABLE_FMA."`.`decoder_adresse` FROM `".DB_TABLE_FMA."` WHERE `".DB_TABLE_FMA."`.`decoder_adresse` IS NOT NULL AND `".DB_TABLE_FMA."`.`decoder_adresse` <> '"."0"."'");
	unset($DB);

	foreach ($foundTrains as $train) {
		array_push($allTrains, intval($train->decoder_adresse));
		if (!in_array($train->decoder_adresse, $allTrainsOnTheTrack)) {
			array_push($newTrains, intval($train->decoder_adresse));
		}
	}

	foreach ($allTrainsOnTheTrack as $train) {
		if (!in_array($train, $allTrains)) {
			array_push($removedTrains, $train);
		}
	}

	$allTrainsOnTheTrack = $allTrains;
	return array("new"=>$newTrains, "removed"=>$removedTrains);
}

// Ermittelt alle Fahrzeuge im eingleisigen Netz.
function findTrainsOnTheTracks () {

	global $allTrainsOnTheTrack;

	$DB = new DB_MySQL();
	$foundTrains = $DB->select("SELECT DISTINCT `".DB_TABLE_FMA."`.`decoder_adresse` FROM `".DB_TABLE_FMA."` WHERE `".DB_TABLE_FMA."`.`decoder_adresse` IS NOT NULL AND `".DB_TABLE_FMA."`.`decoder_adresse` <> '"."0"."'");
	unset($DB);

	foreach ($foundTrains as $train) {
		if (!in_array($train->decoder_adresse, $allTrainsOnTheTrack)) {
			array_push($allTrainsOnTheTrack, intval($train->decoder_adresse));
			prepareTrainForRide($train->decoder_adresse);
		}
	}
}

// Bestimmung der Position eines Zuges über die Adresse.
function getPosition(int $adresse) {

	$returnPosition = array();
	$DB = new DB_MySQL();
	$position = $DB->select("SELECT `".DB_TABLE_FMA."`.`fma_id` FROM `".DB_TABLE_FMA."` WHERE `".DB_TABLE_FMA."`.`decoder_adresse` = $adresse");
	unset($DB);

	if (sizeof($position) != 0) {
		for ($i = 0; $i < sizeof($position); $i++) {
			array_push($returnPosition, intval(get_object_vars($position[$i])["fma_id"]));
		}
	}

	return $returnPosition;
}

// Ermittelt die Fahrplandaten eines Zuges
function getNextBetriebsstellen (int $id) {

	$DB = new DB_MySQL();
	$returnBetriebsstellen = array();
	$betriebsstellen = $DB->select("SELECT `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`betriebsstelle` FROM `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."` WHERE `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`zug_id` = $id ORDER BY `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`id` ASC");
	unset($DB);

	foreach ($betriebsstellen as $betriebsstellenIndex => $betriebsstellenValue) {
		array_push($returnBetriebsstellen, $betriebsstellenValue->betriebsstelle);
	}

	if (sizeof($betriebsstellen) == 0) {
		debugMessage("Zu dieser Zug ID sind keine nächsten Betriebsstellen im Fahrplan vorhanden.");
	}

	return $returnBetriebsstellen;
}

// Bestimmt das zugehörige Signal (falls vorhanden) für einen Abschnitt und eine
// Richtung.
function getSignalForSectionAndDirection(int $section, int $dir) {

	$DB = new DB_MySQL();
	$signal = $DB->select("SELECT `".DB_TABLE_SIGNALE_STANDORTE."`.`id` FROM `".DB_TABLE_SIGNALE_STANDORTE."` WHERE `".DB_TABLE_SIGNALE_STANDORTE."`.`freimelde_id` = $section AND `".DB_TABLE_SIGNALE_STANDORTE."`.`wirkrichtung` = $dir");
	unset($DB);

	if ($signal != null) {
		$signal = intval(get_object_vars($signal[0])["id"]);
	}

	return $signal;
}

// Kalibriert die Position des Fahrzeugs neu anhand der Daten in der Tabelle
// 'fahrzeuge_abschnitte'
function getCalibratedPosition ($id, $speed) {

	global $cacheFahrzeugeAbschnitte;

	$DB = new DB_MySQL();
	$positionReturn = $DB->select("SELECT `".DB_TABLE_FAHRZEUGE_ABSCHNITTE."`.`infra_id`,`".DB_TABLE_FAHRZEUGE_ABSCHNITTE."`.`unixtimestamp` FROM `".DB_TABLE_FAHRZEUGE_ABSCHNITTE."` WHERE `".DB_TABLE_FAHRZEUGE_ABSCHNITTE."`.`fahrzeug_id` = $id")[0];
	unset($DB);

	if (in_array($id, array_keys($cacheFahrzeugeAbschnitte))) {
		if ($positionReturn->unixtimestamp == $cacheFahrzeugeAbschnitte[$id]["unixtimestamp"]) {
			return array("possible" => false);
		}
	}

	$timeDiff = time() - $positionReturn->unixtimestamp;
	$position = ($speed / 3.6) * $timeDiff;

	return array("section" => $positionReturn->infra_id, "position" => $position);
}

// Liest die Adressen aller Fahrzeuge ein.
function getAllAdresses () : array {

	$zustand = array("0", "1");
	$returnAdresses = array();

	echo "Alle Züge, die den Zustand ", implode(", ", $zustand), " haben, werden eingelesen.\n\n";

	$DB = new DB_MySQL();
	$adresses = $DB->select("SELECT `".DB_TABLE_FAHRZEUGE."`.`adresse`, `".DB_TABLE_FAHRZEUGE."`.`zustand` FROM `".DB_TABLE_FAHRZEUGE."`");
	unset($DB);

	foreach ($adresses as $adressIndex => $adressValue) {
		if (in_array($adressValue->zustand, $zustand)) {
			array_push($returnAdresses, (int) $adressValue->adresse);
		}
	}

	return $returnAdresses;
}