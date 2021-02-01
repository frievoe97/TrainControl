<?php

function setTargetSpeed (array $allTrains, int $key, int $nextSpeed, int $nextSection, int $nextPosition, int $nextTime) {

	$allTrains[$key]["next_speed_change_speed"] = $nextSpeed;
	$allTrains[$key]["next_speed_change_section"] = $nextSection;
	$allTrains[$key]["next_speed_change_position"] = $nextPosition;
	$allTrains[$key]["next_speed_change_time"] = $nextTime;

	$id = $allTrains[$key]["id"];

	$nextTimeDB = date("Y-m-d H:i:s", $nextTime);

	$DB_insert = new DB_MySQL();

	$DB_insert->select("UPDATE `". DB_TABLE_FAHRZEUGE_AKTUELL."`
								SET `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`target_speed` = $nextSpeed,
								`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`target_time` = '$nextTimeDB',
								`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`target_section` = '$nextSection',
								`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`target_position` = $nextPosition
								WHERE `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`id` = $id
								");

	return $allTrains[$key];
}


function updateNextSpeed (array $allTrains, int $key, array $value) {

	// TODO: These array have to be filled:
	/*	"next_speed" => array(),
		"next_time" => array(),
		"next_position" => array(),
	 */

	$verzoegerung = $value["verzoegerung"];
	$section = $value["section"];
	$position = $value["position"];
	$speed = $value["speed"];

	$nextSpeed = $value["next_speed_change_speed"];
	$nextSection = $value["next_speed_change_section"];
	$nextPosition = $value["next_speed_change_position"];
	$nextTime = $value["next_speed_change_time"];

	$v_2 = null;
	$time_2 = null;
	$position_2 = null;
	$section_2 = null;

	// Step 1: Get full distance, time, and length
	$totalLength = getBrakeDistance($speed, $nextSpeed, $verzoegerung);
	$totalTime = getBrakeTime($speed, $nextSpeed, $verzoegerung);

	/*
	var_dump("Verzögerung:", $verzoegerung);
	var_dump("Strecke:", $totalLength);
	var_dump("Zeit:", $totalTime);
	var_dump("Speed:", $speed);
	var_dump("############################");
	*/

	$startPosition = $nextPosition - $totalLength;
	$startTime = $nextTime - $totalTime;

	// TODO: What is, when the train has to increase the speed?

	$allNextSpeeds = array();
	$allNextTimes = arrayU();
	$allNextPositions = array();

	var_dump("New Train");
	for ($v_1 = $speed; $v_1 >= ($nextSpeed + 2); $v_1 = $v_1 - 2) {
		var_dump($v_1);
	}




	// TODO: bremsweg, aktuelleGeschwindigkeit - nächste Geschwindigkeit / 2 => Anzahl an Werten
	// Gesamter Bremsweg, Ziel minus gesamter Bremsweg = Start
	// Beim Start beginnen
	// For-loop over start und ende in zweier Schritten
	// Funktion: v_1, v_2, position, Startzeit bzw. nächste Zeit
	// In Var abschpeichern, was im Schritt davor passiert ist (neue v_1, neue Zeit, neue Position, neuer Abschnitt

	//var_dump($value);

}

// Anpassen für viele Schritte => $a bleibt konstant?! => Eher nicht anpassen und allgemein halten
function getBrakeDistance (float $v_0, float $v_1, float $verzoegerung) {

	$mu = 0.1;
	$g = 9.81;

	// v in km/h
	// a in m/s^2
	// return in m
	// TODO: Wie sieht es mit der Reaktionszeit aus? (Wenn ja, dann nur bei der Ersten 2 km/h_diff Bremsung

	//return $bremsweg = ((($v_0-$v_1)*3.6)*$t_reac)+((pow((($v_0*3.6)), 2)-pow((($v_1*3.6)), 2))/(2*($a+(9.81/1000))));
	//return $bremnsweg = ((pow(($v_0 * 3.6),2))/(2 * $a)) - ((pow(($v_1 * 3.6),2))/(2 * $a));

	return $bremsweg = 0.5 * $verzoegerung * ((pow($v_0/3.6,2)-pow($v_1/3.6, 2))/(pow($verzoegerung, 2)));


}

function getBrakeTime (float $v_0, float $v_1, float $verzoegerung) {

	$mu = 0.1;
	$g = 9.81;

	return $breakTime = ($v_0-$v_1)/($verzoegerung);

}