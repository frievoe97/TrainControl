<?php

function setTargetSpeed (array $allTrains, int $key, int $nextSpeed, int $nextSection, int $nextPosition, int $nextTime) {

	// TODO: Check for timetable, speed sign or maximum speed
	// 1.

	// Change position to section and position!!!

	$allTrains[$key]["next_timetable_change_speed"] = $nextSpeed;
	$allTrains[$key]["next_timetable_change_section"] = $nextSection;
	$allTrains[$key]["next_timetable_change_position"] = $nextPosition;
	$allTrains[$key]["next_timetable_change_time"] = $nextTime;

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

function updateNextSections (array $allTrains, int $key, array $sections, array $lenghts, array $v_max) {

	$allTrains[$key]["next_sections"] = $sections;
	$allTrains[$key]["next_lenghts"] = $lenghts;
	$allTrains[$key]["next_v_max"] = $v_max;

	return $allTrains[$key];
}

function whatIsTheNextSpped() {
	return false;
}


function updateNextSpeed (array $allTrains, int $key, array $value) {

	$next_sections = $value["next_sections"];
	$next_lengths = $value["next_lengths"];
	$next_v_max =$value["next_v_max"];

	$verzoegerung = $value["verzoegerung"];
	$section = $value["section"];
	$position = $value["position"];
	$speed = $value["speed"];

	$nextSpeed = $value["next_timetable_change_speed"];
	$nextSection = $value["next_timetable_change_section"];
	$nextPosition = $value["next_timetable_change_position"];
	$nextTime = $value["next_timetable_change_time"];

	$totalLength = getBrakeDistance($speed, $nextSpeed, $verzoegerung);
	$totalTime = getBrakeTime($totalLength, $verzoegerung);

	$startPosition = $nextPosition - $totalLength;
	$startTime = $nextTime - $totalTime;
	$startSpeed = $speed;

	// TODO: What is, when the train has to increase the speed?
	$allNextSpeeds = array();
	$allNextTimes = array();
	$allNextPositions = array();

	array_push($allNextTimes,$startTime);
	array_push($allNextSpeeds,$startSpeed);
	array_push($allNextPositions,$startPosition);

	for ($v_1 = $speed; $v_1 >= ($nextSpeed + 2); $v_1 = $v_1 - 2) {

		$distance = getBrakeDistance($v_1, $v_1 - 2, $verzoegerung);

		$startPosition = $startPosition + $distance;
		$startTime = $startTime + getBrakeTime($distance, $verzoegerung);
		$startSpeed = $v_1 - 2;

		array_push($allNextSpeeds, $startSpeed);
		array_push($allNextTimes, $startTime);
		array_push($allNextPositions, $startPosition);
	}

	$allTrains[$key]["next_speed"] = $allNextSpeeds;
	$allTrains[$key]["next_time"] = $allNextTimes;
	$allTrains[$key]["next_position"] = $allNextPositions;

	return $allTrains[$key];
}

// Anpassen für viele Schritte => $a bleibt konstant?! => Eher nicht anpassen und allgemein halten
function getBrakeDistance (float $v_0, float $v_1, float $verzoegerung) {
	// v in km/h
	// a in m/s^2
	// return in m
	// TODO: Wie sieht es mit der Reaktionszeit aus? (Wenn ja, dann nur bei der Ersten 2 km/h_diff Bremsung
	return $bremsweg = 0.5 * $verzoegerung * ((pow($v_0/3.6,2)-pow($v_1/3.6, 2))/(pow($verzoegerung, 2)));
}

function getBrakeTime (float $distance, float $verzoegerung) {
	return sqrt(2 * $distance/$verzoegerung);
}

// Input: aktuelle Geschwindigkeit, maximale
function emergencyBreak (float $maxBreakDistance, int $speed) {
	return (($speed / 3.6))/(2 * $maxBreakDistance);
}