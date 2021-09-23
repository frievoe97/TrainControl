<?php

/*

DB_TABLE_FAHRZEUGE_AKTUELL

*/

function updateFahrzeugeAktuell() {

	$DB_update = new DB_MySQL();
	$count = $DB_update->select('SELECT COUNT(*)
										FROM `'. DB_TABLE_FAHRZEUGE_AKTUELL.'`');

	$countInt = (int) ((array) $count[0])['COUNT(*)'];

	for ($i = 0; $i < $countInt; $i++) {

		$data = $DB_update->select('SELECT `'.DB_TABLE_FAHRZEUGE_AKTUELL.'`.`position_0`,
										`'.DB_TABLE_FAHRZEUGE_AKTUELL.'`.`speed_0`,
										`'.DB_TABLE_FAHRZEUGE_AKTUELL.'`.`timestamp`
										FROM `'.DB_TABLE_FAHRZEUGE_AKTUELL.'`
										WHERE `'.DB_TABLE_FAHRZEUGE_AKTUELL."`.`id` = $i
										");

		$position = (int) ((array) $data[0])['position_0'];
		$speed = (int) ((array) $data[0])['speed_0'];
		$timestamp = strtotime(((array) $data[0])['timestamp']);

		$currentTime = strtotime(((array) $DB_update->select('SELECT CURRENT_TIMESTAMP')[0])['CURRENT_TIMESTAMP']);
		$currentTimeDate = date('Y-m-d H:i:s', $currentTime);
		$timeDiff = $currentTime - $timestamp;
		$newPosition = (($speed * ($timeDiff/3600))*1000 + $position);

		$DB_update->select('UPDATE `'. DB_TABLE_FAHRZEUGE_AKTUELL.'`
								SET `'.DB_TABLE_FAHRZEUGE_AKTUELL."`.`position_0` = $newPosition,
								`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`timestamp` = '$currentTimeDate'
								WHERE `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`id` = $i
								");

	}
	unset($DB_update);
}


function calibrateFahrzeugeAktuell() {

	//TODO

	$DB_calibrate = new DB_MySQL();

	$DB_calibrate->select('');

	unset($DB_calibrate);

}

function nextSpeedPositionFahrzeugeAktuell(int $id, int $speed, int $nextSection, int $position, int $timestamp) {

	if ($speed < 0 || $id < 0 || $position < 0) {
		return false;
	}

	$DB_update = new DB_MySQL();

	$data = ((array) ($DB_update->select('SELECT `'.DB_TABLE_FAHRZEUGE_AKTUELL.'`.`speed_0`,
																`'.DB_TABLE_FAHRZEUGE_AKTUELL.'`.`verzoegerung`
																FROM `'.DB_TABLE_FAHRZEUGE_AKTUELL.'`
																WHERE `'.DB_TABLE_FAHRZEUGE_AKTUELL."`.`id` = $id
																"))[0]);

	$currentSpeed = (int) $data['speed_0'];
	$verzoegerung = (float) $data['verzoegerung'];

	$distance = getBrakeDistance($currentSpeed, $speed, 0, $verzoegerung);

	$nextTime = date('Y-m-d H:i:s', $timestamp);

	$changePosition = $position - $distance;


	$DB_update->select('UPDATE `'. DB_TABLE_FAHRZEUGE_AKTUELL.'`
								SET `'.DB_TABLE_FAHRZEUGE_AKTUELL."`.`target_speed` = $speed,
								`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`target_time` = '$nextTime',
								`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`target_section` = '$nextSection',
								`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`target_position` = $position
								WHERE `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`id` = $id
								");

	unset($DB_update);

}

function checkChangeSpeed() {

	$DB_update = new DB_MySQL();

	$count = $DB_update->select('SELECT COUNT(*)
										FROM `'. DB_TABLE_FAHRZEUGE_AKTUELL.'`');

	$countInt = (int) ((array) $count[0])['COUNT(*)'];

	for ($i = 0; $i < $countInt; $i++) {

		$data = ((array) $DB_update->select('SELECT `'.DB_TABLE_FAHRZEUGE_AKTUELL.'`.`position_0`,
											`'.DB_TABLE_FAHRZEUGE_AKTUELL.'`.`position_1`,
											`'.DB_TABLE_FAHRZEUGE_AKTUELL.'`.`speed_1`,
											`'.DB_TABLE_FAHRZEUGE_AKTUELL.'`.`adresse`
											FROM `'.DB_TABLE_FAHRZEUGE_AKTUELL.'`
											WHERE `'.DB_TABLE_FAHRZEUGE_AKTUELL."`.`id` = $i
											")[0]);

		$position = (int) $data['position_0'];
		$newPosition = (int) $data['position_1'];
		$adresse = (int) $data['adresse'];
		$nextSpeed = (int) $data['speed_1'];

		if ($position >= $newPosition) {
			sendFahrzeugbefehl($adresse, $nextSpeed);
		}


	}

	unset($DB_update);

}


?>
