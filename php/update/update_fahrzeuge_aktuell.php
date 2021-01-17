<?php

/*

DB_TABLE_FAHRZEUGE_AKTUELL

*/

function updateFahrzeugeAktuell() {

	$DB_update = new DB_MySQL();
	$count = $DB_update->select("SELECT COUNT(*)
										FROM `". DB_TABLE_FAHRZEUGE_AKTUELL."`");

	$countInt = (int) ((array) $count[0])['COUNT(*)'];

	for ($i = 0; $i < $countInt; $i++) {

		$data = $DB_update->select("SELECT `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`position`,
										`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`speed`,
										`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`timestamp`
										FROM `".DB_TABLE_FAHRZEUGE_AKTUELL."`
										WHERE `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`id` = $i
										");

		$position = (int) ((array) $data[0])['position'];
		$speed = (int) ((array) $data[0])['speed'];
		$timestamp = strtotime(((array) $data[0])['timestamp']);

		$currentTime = strtotime(((array) $DB_update->select("SELECT CURRENT_TIMESTAMP")[0])['CURRENT_TIMESTAMP']);
		$currentTimeDate = date("Y-m-d H:i:s", $currentTime);
		$timeDiff = $currentTime - $timestamp;
		$newPosition = (($speed * ($timeDiff/3600))*1000 + $position);

		$DB_update->select("UPDATE `". DB_TABLE_FAHRZEUGE_AKTUELL."`
								SET `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`position` = $newPosition,
								`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`timestamp` = '$currentTimeDate'
								WHERE `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`id` = $i
								");

	}
	unset($DB_update);
}


function calibrateFahrzeugeAktuell() {

	//TODO

	$DB_calibrate = new DB_MySQL();

	$DB_calibrate->select("");

	unset($DB_calibrate);

}

function nextSpeedPositionFahrzeugeAktuell(int $id, int $speed, int $position, int $timestamp) {

	if ($speed < 0 || $id < 0 || $position < 0) {
		return false;
	}

	$DB_update = new DB_MySQL();

	$data = ((array) ($DB_update->select("SELECT `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`speed`,
																`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`verzoegerung`
																FROM `".DB_TABLE_FAHRZEUGE_AKTUELL."`
																WHERE `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`id` = $id
																"))[0]);

	$currentSpeed = (int) $data['speed'];
	$verzoegerung = (float) $data['verzoegerung'];

	$distance = getBrakeDistance($currentSpeed, $speed, 0, $verzoegerung);

	$nextTime = date("Y-m-d H:i:s", $timestamp);

	$changePosition = $position - $distance;


	$DB_update->select("UPDATE `". DB_TABLE_FAHRZEUGE_AKTUELL."`
								SET `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`next_speed` = $speed,
								`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`next_position` = $position,
								`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`next_time` = '$nextTime',
								`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`change_speed_position` = $changePosition
								WHERE `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`id` = $id
								");

	unset($DB_update);

}

function checkChangeSpeed() {

	$DB_update = new DB_MySQL();

	$count = $DB_update->select("SELECT COUNT(*)
										FROM `". DB_TABLE_FAHRZEUGE_AKTUELL."`");

	$countInt = (int) ((array) $count[0])['COUNT(*)'];

	for ($i = 0; $i < $countInt; $i++) {

		$data = ((array) $DB_update->select("SELECT `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`position`,
											`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`next_position`,
											`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`next_speed`,
											`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`adresse`
											FROM `".DB_TABLE_FAHRZEUGE_AKTUELL."`
											WHERE `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`id` = $i
											")[0]);

		$position = (int) $data['position'];
		$newPosition = (int) $data['next_position'];
		$adresse = (int) $data['adresse'];
		$nextSpeed = (int) $data['next_speed'];

		if ($position >= $newPosition) {
			sendFahrzeugbefehl($adresse, $nextSpeed);
		}


	}

	unset($DB_update);

}


?>
