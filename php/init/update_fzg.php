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

	/*
	$DB_insert = new DB_MySQL();

	$DB_insert->select("UPDATE `". DB_TABLE_FAHRZEUGE_AKTUELL."`
								SET `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`target_speed` = $nextSpeed,
								`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`target_time` = '$nextTimeDB',
								`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`target_section` = '$nextSection',
								`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`target_position` = $nextPosition
								WHERE `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`id` = $id
								");

	*/

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


function updateNextSpeed (array $allTrains, int $key, array $value, float $currentTime) {

	$next_sections = $value["next_sections"];
	$next_lengths = $value["next_lenghts"];
	$next_v_max =$value["next_v_max"];

	//var_dump($value);



	$verzoegerung = $value["verzoegerung"];
	$section = $value["section"];
	$position = $value["position"];
	$speed = $value["speed"];

	$nextSpeed = $value["next_timetable_change_speed"];
	$nextSection = $value["next_timetable_change_section"];
	$nextPosition = $value["next_timetable_change_position"];
	$nextTime = $value["next_timetable_change_time"];

	// abrunden (durch int eh automatisch)
	$distanceToTheNextScheduledStop = getCompleteDistancToTheNextScheduledStop($next_sections, $next_lengths, $section, $position, $nextSection, $nextPosition);

	$speedOnFreeTrack = getMinimumSpeedToArriveOnTime($distanceToTheNextScheduledStop, $currentTime, $nextTime, $speed, $nextSpeed, $verzoegerung);

	getSpeedChange($speed, $speedOnFreeTrack, $nextSpeed, $next_sections, $next_lengths, $position, $nextPosition, $distanceToTheNextScheduledStop, $verzoegerung, $currentTime, $section, $nextSection, $nextTime);



	/*

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

	*/

	return $allTrains[$key];



}



function getCompleteDistancToTheNextScheduledStop(array $next_sections, array $next_lenghts, int $section, int $position, int $nextSection, int $nextPosition) : int {

	$startKey = null;
	$targetKey = null;
	$totalLength = 0;

	if (!in_array($section, $next_sections) || !in_array($section, $next_sections)) {
		if (!in_array($section, $next_sections)) {
			debugMessage("Der aktuelle Abschnitt befindet sich nicht im Array mit den nächsten Abschnitten.");
		}
		if (!in_array($nextSection, $next_sections)) {
			debugMessage("Der Zielabschnitt befindet sich nicht im Array mit den nächsten Abschnitten.");
		}
		return false;
	}

	foreach ($next_sections as $key => $value) {
		if ($value == $section) {
			$startKey = $key;
		}
		if ($value == $nextSection) {
			$targetKey = $key;
		}
	}

	if ($targetKey < $startKey) {
		debugMessage("Der Zeilabschnitt wurde schon durchfahren");
		return false;
	}

	for ($i = $startKey; $i <= $targetKey; $i++) {
		$totalLength = $totalLength + $next_lenghts[$i];
	}

	return $totalLength - $position - $next_lenghts[$targetKey] + $nextPosition;

}

function getMinimumSpeedToArriveOnTime(int $distanceToTheNextScheduledStop, float $currentTime, float $nextTime, int $currentSpeed, int $targetSpeed, float $verzoegerung): int {

	$availableTime = $nextTime - $currentTime;
	$possibleSpeed = null;
	$foundSpeed = false;
	$allPossibleSpeeds = array();

	for ($i = 10; $i <= 120; $i = $i + 10) {

		if (getBrakeDistance($currentSpeed, $i, $verzoegerung) + getBrakeDistance($i, $targetSpeed, $verzoegerung) < $distanceToTheNextScheduledStop) {

			array_push($allPossibleSpeeds, $i);

			$accelerationDistance = getBrakeDistance($currentSpeed, $i, $verzoegerung);
			$brakeDistance = getBrakeDistance($i, $targetSpeed, $verzoegerung);

			if (getBrakeTime($currentSpeed, $i, $verzoegerung) + getBrakeTime($i, $targetSpeed, $verzoegerung) + (($distanceToTheNextScheduledStop - $accelerationDistance - $brakeDistance)/($i/3.6)) < $availableTime) {
				if ($possibleSpeed == null) {
					$possibleSpeed = $i;
				}
				$foundSpeed = true;
			}
		}
	}

	if (!$foundSpeed) {
		$possibleSpeed = end($allPossibleSpeeds);
	}
	return $possibleSpeed;
}

function getSpeedChange(int $speed, int $speedOnFreeTrack, int $nextSpeed, array $nextSection, array $next_lengths, int $position, int $nextPosition, int $distanceToTheNextScheduledStop, float $verzoegerung, float $currentTime, int $currentSection, int $targetSection, float $nextTime) {

	$startKey = null;
	$targetKey = null;

	$accelerationDistance = getBrakeDistance($speed, $speedOnFreeTrack, $verzoegerung);
	$brakeDistance = getBrakeDistance($speedOnFreeTrack, $nextSpeed, $verzoegerung);

	$accelerationTime = getBrakeTime($speed, $speedOnFreeTrack, $verzoegerung);
	$brakeTime = getBrakeTime($speedOnFreeTrack, $nextSpeed, $verzoegerung);

	$cumulativeSections = array();

	foreach ($nextSection as $key => $value) {
		if ($value == $currentSection) {
			$startKey = $key;
		}
		if ($value == $targetSection) {
			$targetKey = $key;
		}
	}

	for ($i = $startKey; $i <= $targetKey; $i++) {
		if (!end($cumulativeSections)) {
			array_push($cumulativeSections, $next_lengths[$i]);
		} else {
			array_push($cumulativeSections, end($cumulativeSections) + $next_lengths[$i]);
		}
	}

	/*
	if ($targetKey < $startKey) {
		debugMessage("Der Zeilabschnitt wurde schon durchfahren");
		return false;
	}

	if ($targetKey == null || $startKey == null) {
		debugMessage("Der aktulle Abschnitt und/oder der Zielabschnitt befinden sich nicht in dem Array \"next sections\"");
		return false;
	}
	*/



	$allTimes = array();
	$allSpeeds = array();
	$allSectionsChange = array();
	$allPositionsChange = array();

	// 0 km/h und aktuelle Zeit
	array_push($allTimes, $currentTime);
	array_push($allSpeeds, $speed);
	array_push($allPositionsChange, 0);
	array_push($allSectionsChange, $currentSection);


	// Time and Speed for Start
	if ($speed < $speedOnFreeTrack) {
		for ($i = $speed; $i <= ($speedOnFreeTrack - 2); $i = $i + 2) {
			$tempDistance = getBrakeDistance($i, ($i + 2), $verzoegerung);
			$tempTime = getBrakeTime($i, ($i + 2), $verzoegerung);

			array_push($allTimes, (end($allTimes) + $tempTime));
			array_push($allSpeeds, ($i + 2));
			array_push($allPositionsChange, end($allPositionsChange) + $tempDistance);
		}
	} if ($speed > $speedOnFreeTrack) {
		for ($i = $speed; $i >= ($speedOnFreeTrack + 2); $i = $i - 2) {
			$tempDistance = getBrakeDistance($i, ($i - 2), $verzoegerung);
			$tempTime = getBrakeTime($i, ($i - 2), $verzoegerung);

			array_push($allTimes, (end($allTimes) + $tempTime));
			array_push($allSpeeds, ($i - 2));
		}
	}

	//var_dump($allTimes);

	array_push($allTimes, ($nextTime - $brakeTime));
	array_push($allSpeeds, $speedOnFreeTrack);
	array_push($allPositionsChange, end($cumulativeSections) - $brakeDistance);
	array_push($allSectionsChange, $currentSection);

	//var_dump($allTimes);

	// Time and Speed for End
	if ($nextSpeed > $speedOnFreeTrack) {
		for ($i = $nextSpeed; $i <= ($speedOnFreeTrack - 2); $i = $i + 2) {
			$tempDistance = getBrakeDistance($nextSpeed, ($nextSpeed + 2), $verzoegerung);
			$tempTime = getBrakeTime($nextSpeed, ($nextSpeed + 2), $verzoegerung);

			array_push($allTimes, (end($allTimes) + $tempTime));
			array_push($allSpeeds, ($i + 2));


		}
	} if ($nextSpeed < $speedOnFreeTrack) {
		for ($i = $speedOnFreeTrack; $i >= ($nextSpeed + 2); $i = $i - 2) {
			$tempDistance = getBrakeDistance($i, ($i - 2), $verzoegerung);
			$tempTime = getBrakeTime($i, ($i - 2), $verzoegerung);

			array_push($allTimes, (end($allTimes) + $tempTime));
			array_push($allSpeeds, ($i - 2));
			array_push($allPositionsChange, end($allPositionsChange) + $tempDistance);

		}
	}


	var_dump($allPositionsChange);

	$allSpeedsJSon = json_encode($allSpeeds);
	$allTimesJSon = json_encode($allTimes);

	function toArr(){
		return func_get_args();
	}

	$speedOverTime = array_map('toArr', $allTimes, $allSpeeds);

	$speedOverTime = json_encode($speedOverTime);

	$fp = fopen('../json/speedOverTime.json', 'w');
	fwrite($fp, $speedOverTime);
	fclose($fp);

	$speedOverPosition = array_map('toArr', $allPositionsChange, $allSpeeds);

	$speedOverPosition = json_encode($speedOverPosition);

	$fp = fopen('../json/speedOverPosition.json', 'w');
	fwrite($fp, $speedOverPosition);
	fclose($fp);



}

function getBrakeTime (float $v_0, float $v_1, float $verzoegerung)  {

	$v_0 = $v_0 / 3.6;
	$v_1 = $v_1 / 3.6;

	if ($v_0 < $v_1) {
		return ($v_1/$verzoegerung) - ($v_0/$verzoegerung);
	}

	if ($v_0 > $v_1) {
		return ($v_0/$verzoegerung) - ($v_1/$verzoegerung);
	}




	//return sqrt((2 * getBrakeDistance($v_0, $v_1, $verzoegerung))/$verzoegerung);
}

// Anpassen für viele Schritte => $a bleibt konstant?! => Eher nicht anpassen und allgemein halten
function getBrakeDistance (float $v_0, float $v_1, float $verzoegerung) {
	// v in km/h
	// a in m/s^2
	// return in m
	// TODO: Wie sieht es mit der Reaktionszeit aus? (Wenn ja, dann nur bei der Ersten 2 km/h_diff Bremsung


	if ($v_0 > $v_1) {

		return $bremsweg = 0.5 * $verzoegerung * ((pow($v_0/3.6,2)-pow($v_1/3.6, 2))/(pow($verzoegerung, 2)));

	} if ($v_0 < $v_1) {

		return $bremsweg = -0.5 * $verzoegerung * ((pow($v_0/3.6,2)-pow($v_1/3.6, 2))/(pow($verzoegerung, 2)));

	} if ($v_0 = $v_1) {

		return 0;

	}



}

// Input: aktuelle Geschwindigkeit, maximale
function emergencyBreak (float $maxBreakDistance, int $speed) {
	return (($speed / 3.6))/(2 * $maxBreakDistance);
}