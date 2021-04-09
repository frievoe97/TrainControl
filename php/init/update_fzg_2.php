<?php

$next_sections = null;
$next_lengths = null;
$next_v_max = null;

$verzoegerung = null;
$notverzoegerung = null;
$currentSection = null;
$currentPosition = null;
$currentSpeed = null;

$targetSpeed = null;
$targetSection = null;
$targetPosition = null;
$targetTime = null;

$indexCurrentSection = null;
$indexTargetSection = null;

$distanceToNextStop = null;

$trainSpeedChange = array();
$trainPositionChange = array();
$trainTimeChange = array();
// Gibt immer die Distanz bis zum Ende des Abschnitts an, von der aktuellen Position
$cumulativeSectionLengthEnd = array();
$cumulativeSectionLengthStart = array();


$keyPoints = array();

function setTargetSpeed (array $allTrains, int $key, int $nextSpeed, int $nextSection, int $nextPosition, int $nextTime) {

	// TODO: Check for timetable, speed sign or maximum speed
	// 1.

	// Change position to section and position!!!

	$allTrains[$key]["next_timetable_change_speed"] = $nextSpeed;
	$allTrains[$key]["next_timetable_change_section"] = $nextSection;
	$allTrains[$key]["next_timetable_change_position"] = $nextPosition;
	$allTrains[$key]["next_timetable_change_time"] = $nextTime;

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

	global $next_sections;
	global $next_lengths;
	global $next_v_max;

	global $verzoegerung;
	global $notverzoegerung;
	global $currentSection;
	global $currentPosition;
	global $currentSpeed;

	global $targetSpeed;
	global $targetSection;
	global $targetPosition;
	global $targetTime;

	global $indexCurrentSection;
	global $indexTargetSection;

	global $distanceToNextStop;

	global $trainSpeedChange;
	global $trainPositionChange;
	global $trainTimeChange;

	global $cumulativeSectionLengthEnd;
	global $cumulativeSectionLengthStart;

	global $keyPoints;

	$next_sections = $value["next_sections"];
	$next_lengths = $value["next_lenghts"];
	$next_v_max = $value["next_v_max"];

	$verzoegerung = $value["verzoegerung"];
	$notverzoegerung = $value["notverzoegerung"];
	$currentSection = $value["section"];
	$currentPosition = $value["position"];
	$currentSpeed = $value["speed"];

	$targetSpeed = $value["next_timetable_change_speed"];
	$targetSection = $value["next_timetable_change_section"];
	$targetPosition = $value["next_timetable_change_position"];
	$targetTime = $value["next_timetable_change_time"];

	$indexCurrentSection = null;
	$indexTargetSection = null;

	$timeToNextStop = null;
	$maxTimeToNextStop = $targetTime - $currentTime;

	$maxSpeedNextSections = null;

	foreach ($next_sections as $key => $value) {
		if ($value == $currentSection) {
			$indexCurrentSection = $key;
		}
		if ($value == $targetSection) {
			$indexTargetSection = $key;
		}
	}

	// TODO: verbessern, was ist, wenn start und end section die selbe sind?
	$cumulativeSectionLengthSum = - $currentPosition;
	$cumulativeSectionLengthStart[$indexCurrentSection] = 0;
	$cumulativeSectionLengthStart[$indexCurrentSection + 1] = $next_lengths[$indexCurrentSection] - $currentPosition;

	for ($i = ($indexCurrentSection + 2); $i <= $indexTargetSection; $i++) {
		array_push($cumulativeSectionLengthStart, (end($cumulativeSectionLengthStart) + $next_lengths[$i - 1]));
	}

	for ($i = $indexCurrentSection; $i <= $indexTargetSection; $i++) {
		$cumulativeSectionLengthSum = $cumulativeSectionLengthSum + $next_lengths[$i];
		$distanceToNextStop = $distanceToNextStop + $next_lengths[$i];
		if ($i != $indexTargetSection) {
			$cumulativeSectionLengthEnd[$i] = $cumulativeSectionLengthSum;
		} else {
			$cumulativeSectionLengthEnd[$i] = end($cumulativeSectionLengthEnd) + $targetPosition;
		}
	}

	$distanceToNextStop = $distanceToNextStop - $currentPosition - $next_lengths[$indexTargetSection] + $targetPosition;

	//maximale geschwindigkeit zwischen zwei punkten...
	// TODO: Was ist, wenn die aktuelle Geschwindigkeit nicht ohne Rest durch 10 teilbar ist?
	$v_maxFirstIteration = null;
	for ($i = $currentSpeed; $i <= 120; $i = $i + 10) {
		if ((getBrakeDistance($currentSpeed, $i, $verzoegerung) + getBrakeDistance($i, $targetSpeed, $verzoegerung)) > $distanceToNextStop) {
			$v_maxFirstIteration = $i - 10;
			break;
		}
	}

	for ($i = $indexCurrentSection; $i <= $indexTargetSection; $i++) {
		if ($next_v_max[$i] > $maxSpeedNextSections) {
			$maxSpeedNextSections = $next_v_max[$i];
		}
	}

	if ($maxSpeedNextSections < $v_maxFirstIteration) {
		$v_maxFirstIteration = $maxSpeedNextSections;
	}

	array_push($keyPoints, createKeyPoint(0, getBrakeDistance($currentSpeed, $v_maxFirstIteration, $verzoegerung), $currentSpeed, $v_maxFirstIteration));
	array_push($keyPoints, createKeyPoint(($distanceToNextStop - getBrakeDistance($v_maxFirstIteration, $targetSpeed, $verzoegerung)), $distanceToNextStop, $v_maxFirstIteration, $targetSpeed));

	//function keyPoints => trainChangeArrays
	$trainChange = convertKeyPointsToTrainChangeArray($keyPoints);
	$trainPositionChange = $trainChange[0];
	$trainSpeedChange = $trainChange[1];

	//function: trainChangeData => JSON file
	// TODO
	safeTrainChangeToJSONFile();
	//safe $cumulativeSectionLength Data to JSON file
	$v_maxFromUsedSections = array();
	for ($i = $indexCurrentSection; $i <= $indexTargetSection; $i++) {
		array_push($v_maxFromUsedSections, $next_v_max[$i]);
	}
	$VMaxOverCumulativeSections = array_map('toArr', $cumulativeSectionLengthEnd, $v_maxFromUsedSections);
	$VMaxOverPositionsJSon = json_encode($VMaxOverCumulativeSections);

	$fp = fopen('../json/VMaxOverCumulativeSections.json', 'w');
	fwrite($fp, $VMaxOverPositionsJSon);
	fclose($fp);

	$previousFailedSections = array();

	if (checkIfTrainIsToFastInCertainSections()["failed"]) {
		while (checkIfTrainIsToFastInCertainSections()["failed"]) {

			$failedSections = checkIfTrainIsToFastInCertainSections()["failed_sections"];

			$failedSections = array_unique(array_merge($failedSections, $previousFailedSections));
			asort($failedSections);

			$groupedSections = createGroupedSections($failedSections);


			// create new keyPoints
			$keyPointsReturn = createKeyPointFromFailedSections($groupedSections, $failedSections);

			var_dump($keyPointsReturn);

			$keyPoints = tuneKeyPoints($keyPointsReturn);




			// check if there is a KeyPoint Problem (Übverschneidung)
			//checkDoubleKeyPoints();
			checkKeyPointsOverlap();

			$trainChange = createTrainChanges($currentTime);

			$trainPositionChange = $trainChange[0];
			$trainSpeedChange = $trainChange[1];
			$trainTimeChange = $trainChange[2];



			// return array($returnTrainPositionChange, $returnTrainSpeedChange, $returnTrainTimeChange);


			checkTrainChangeOverlap();
			safeTrainChangeToJSONFile();

			sleep(5);















			if (false) {
				$failedSections = checkIfTrainIsToFastInCertainSections()["failed_sections"];


				$test = array_unique(array_merge($failedSections, $previousFailedSections));

				$failedSections = $test;

				asort($failedSections);



				$groupedSections = createGroupedSections($failedSections);

				//var_dump($groupedSections);

				// create new keyPoints
				$keyPointsReturn = createKeyPointsFromGroupedSections($groupedSections, $failedSections);
				$trainPositionChange = $keyPointsReturn[0];
				$trainSpeedChange = $keyPointsReturn[1];
				$keyPoints = $keyPointsReturn[2];
				$previousFailedSections = $keyPointsReturn[3];

				// check if there is a KeyPoint Problem (Übverschneidung)
				checkKeyPointsOverlap();
				checkTrainChangeOverlap();
				safeTrainChangeToJSONFile();
			}
		}
	}

	//Adding time to first KeyPoint
	$keyPoints[0]["time_0"] = $currentTime;
	//Zeit berechnen
	$trainTimeChange = calculateTrainTimeChange();
	// TODO: Evtl. $timeToNextStop über $trainChangeTime errechnen und nicht über eine eigene Funktion
	$timeToNextStop = calculateTimeFromKeyPoints();

	if ($timeToNextStop > $maxTimeToNextStop) {
		// Do nothing, schneller kann der Zug eh nicht ankommen
		echo "Der Zug wird mit einer Verspätung von ", ($timeToNextStop - $maxTimeToNextStop), " im nächsten planmäßigen Halt ankommen.\n";
	} else {
		echo "Aktuell benötigt er ", $timeToNextStop, " Sekunden, obwohl er ", $maxTimeToNextStop, " Sekunden zur Verfügung hat\n";
		echo "Evtl. könnte der Zug zwischendurch die Geschwindigkeit verringern, um Energie zu sparen.\n";

		$keyPointsPreviousStep = array();
		$finish = false;

		while (checkIfTheSpeedCanBeDecreased()["possible"] && !$finish) {

			$possibleSpeedRange = findMaxSpeed();

			if ($possibleSpeedRange["min_speed"] == 10 && $possibleSpeedRange["max_speed"] == 10) {
				break;
			}

			$localKeyPoints = $keyPoints; //lokale Kopie der KeyPoints
			$newCalculatedTime = null; //Zeit bis zum Ziel
			$newKeyPoints = null;

			for ($i = $possibleSpeedRange["max_speed"]; $i >= $possibleSpeedRange["min_speed"]; $i = $i - 10) {

				$localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["speed_1"] = $i;
				$localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["speed_0"] = $i;
				$localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["position_1"] = (getBrakeDistance($localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["speed_0"], $i, $verzoegerung) + $localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["position_0"]);
				$localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["position_0"] = ($localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["position_1"] - getBrakeDistance($i, $localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["speed_1"], $verzoegerung));

				$newCalculatedTime = calculateTimeFromKeyPoints($localKeyPoints);
				if ($i == 10)  {
					if ($newCalculatedTime > $maxTimeToNextStop) {
						$localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["speed_1"] = $i + 10;
						$localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["speed_0"] = $i + 10;
						$localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["position_1"] = (getBrakeDistance($localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["speed_0"], ($i + 10), $verzoegerung) + $localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["position_0"]);
						$localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["position_0"] = ($localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["position_1"] - getBrakeDistance(($i + 10), $localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["speed_1"], $verzoegerung));
					}
					$finish = true;
					$newKeyPoints = $localKeyPoints;
					break;
				}
				if ($newCalculatedTime > $maxTimeToNextStop) {
					if ($i == $possibleSpeedRange["max_speed"]) {
						$localKeyPoints = $keyPointsPreviousStep;
						$localKeyPoints = deleteDoubledKeyPoints($localKeyPoints);
						$keyPoints = $localKeyPoints;
						$finish = true;
						break;
					}
					$localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["speed_1"] = $i + 10;
					$localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["speed_0"] = $i + 10;
					$localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["position_1"] = (getBrakeDistance($localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["speed_0"], ($i + 10), $verzoegerung) + $localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["position_0"]);
					$localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["position_0"] = ($localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["position_1"] - getBrakeDistance(($i + 10), $localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["speed_1"], $verzoegerung));
					$newKeyPoints = $localKeyPoints;
					$finish = true;
					$keyPoints = $localKeyPoints;
					break;
				}
				if ($i == $possibleSpeedRange["min_speed"]) {
					$newKeyPoints = $localKeyPoints;
					$newKeyPoints = deleteDoubledKeyPoints($newKeyPoints);
					$keyPoints = $newKeyPoints;
					break;

				}
				// TODO: KeyPoints löschen, bei denen speed_0 == speed_1 gilt
				$newKeyPoints = $localKeyPoints;
			}
			$keyPointsPreviousStep = $localKeyPoints;
			if ($newKeyPoints != null) {
				$keyPoints = $newKeyPoints;
			}
			$keyPoints = deleteDoubledKeyPoints($keyPoints);
		}

		$newCalculatedTime = calculateTimeFromKeyPoints();
		speedFineTuning(($maxTimeToNextStop - $newCalculatedTime), $possibleSpeedRange["first_key_point_index"]);

		// TODO: $currentTime global verfügbar machen
		$timeToNextStop = calculateTimeFromKeyPoints();
		$returnTrainChanges = createTrainChanges($currentTime);
		$trainPositionChange = $returnTrainChanges[0];
		$trainSpeedChange = $returnTrainChanges[1];
		$trainTimeChange = $returnTrainChanges[2];
		safeTrainChangeToJSONFile();

		echo "\nDurch die Anpassung der Geschwindigkeit benötigt der Zug jetzt ", $timeToNextStop, ",\nda er ", $maxTimeToNextStop, " Sekunden zur Verfügung hat\n";
	}
}

function tuneKeyPoints(array $keyPoints) : array {

	global $verzoegerung;

	// TODO: Beim letzten KeyPoint kann pos_1 nicht nach hinten verschoben werden!

	for ($i = 0; $i <= sizeof($keyPoints) - 1; $i++) {
		$distance = $keyPoints[$i]["position_1"] - $keyPoints[$i]["position_0"];
		$requiredDistance = getBrakeDistance($keyPoints[$i]["speed_0"], $keyPoints[$i]["speed_1"], $verzoegerung);

		if ($requiredDistance > $distance) {
			if ($keyPoints[$i]["speed_0"] < $keyPoints[$i]["speed_1"]) {
				$keyPoints[$i]["position_1"] = $keyPoints[$i]["position_0"] + $requiredDistance;
			} elseif ($keyPoints[$i]["speed_0"] > $keyPoints[$i]["speed_1"]) {
				$keyPoints[$i]["position_0"] = $keyPoints[$i]["position_1"] - $requiredDistance;
			}
		}
	}

	return $keyPoints;


}

function checkDoubleKeyPoints () {

	global $keyPoints;

	$found = false;

	do {

		$found = false;
		for ($i = 1; $i < sizeof($keyPoints) - 1; $i++) {
			if ($keyPoints[$i]["speed_0"] == $keyPoints[$i]["speed_1"]) {
				$found = true;
				$keyPoints[$i - 1]["position_1"] = $keyPoints[$i]["position_1"];
				unset($keyPoints[$i]);
				$keyPoints = array_values($keyPoints);
			}
		}
	} while($found);

}

function createKeyPointFromFailedSections (array $groupedSections, array $failedSections) : array {
	//sleep(5);
	global $next_v_max;
	global $next_lengths;
	global $indexCurrentSection;
	global $indexTargetSection;
	global $currentSpeed;
	global $targetSpeed;
	global $targetPosition;
	global $currentPosition;
	global $verzoegerung;
	global $cumulativeSectionLengthEnd;
	global $cumulativeSectionLengthStart;
	global $keyPoints;
	//global $previousFailedSections;

	$previousFailedSections = array();

	$newSpeedChange = array();
	$newPositionChange = array();
	$returnKeyPoints = array();

	$increaseKeyPoint = array();
	$decreaseKeyPoint = array();


	foreach ($groupedSections as $groupKey => $groupValue) {
		$distance = 0;
		$v_max = null;
		$v_0 = null;
		$v_1 = null;
		$absolutPositionStart = $cumulativeSectionLengthStart[$groupValue[0]];
		$absolutPositionEnd = $cumulativeSectionLengthEnd[end($groupValue)];

		for ($i = $groupValue[0]; $i <= end($groupValue); $i++) {
			$distance = $distance + $next_lengths[$i];
			if ($v_max == null) {
				$v_max = $next_v_max[$i];
			} else {
				if ($v_max > $next_v_max[$i]) {
					$v_max = $next_v_max[$i];
				}
			}
		}

		if (in_array($indexCurrentSection, $groupValue)) {
			$distance = $distance - $currentPosition;
		}

		if (in_array($indexTargetSection, $groupValue)) {
			$distance = $distance + $targetPosition - $next_lengths[$indexTargetSection];
		}

		if ($groupKey == 0) {
			$v_0 = $currentSpeed;
		} else {
			if ($next_v_max[$groupValue[0] - 1] < $next_v_max[$groupValue[0]]) {
				$v_0 = $next_v_max[$groupValue[0] - 1];
			} else {
				$v_0 = $next_v_max[$groupValue[0]];
			}
		}

		if ($groupKey == sizeof($groupedSections) - 1) {
			$v_1 = $targetSpeed;
		} else {
			if ($next_v_max[end($groupValue) + 1] < $next_v_max[end($groupValue)]) {
				$v_1 = $next_v_max[end($groupValue) + 1];
			} else {
				$v_1 = $next_v_max[end($groupValue)];
			}
		}

		$new_v_max = null;

		for ($i = 0; $i <= $v_max; $i = $i + 10) {
			if ((getBrakeDistance($v_0, $i, $verzoegerung) + getBrakeDistance($i, $v_0, $verzoegerung)) < $distance) {
				$new_v_max = $i;
			}
		}

		$v_max = $new_v_max;

		if (!(sizeof($groupValue) == 1 && in_array($groupValue[0], $failedSections))) {

			//var_dump(gettype($previousFailedSections));
			$v_maxFirstIteration = null;
			for ($i = $currentSpeed; $i <= 120; $i = $i + 10) {
				if ((getBrakeDistance($currentSpeed, $i, $verzoegerung) + getBrakeDistance($i, $targetSpeed, $verzoegerung)) > $distance) {
					$v_maxFirstIteration = $i - 10;
					$v_max = $v_maxFirstIteration;
					break;
				}
			}
		} else {
			//var_dump($groupValue);
			array_push($previousFailedSections, $groupValue[0]);
			//array_push($previousFailedSections, $groupValue);
		}



		array_push($returnKeyPoints, createKeyPoint($absolutPositionStart, $absolutPositionEnd, $v_0, $v_1, $v_max));


	}
	return $returnKeyPoints;
}



function splitGroupedSections(array $groupedSections) : array {
	foreach ($groupedSections as $groupedSectionsKey => $groupedSectionsValue) {
		//var_dump($groupedSectionsValue);
	}
	return $groupedSections;
}

function speedFineTuning(float $timeDiff, int $index) {

	global $keyPoints;

	$availableDistance = $keyPoints[$index + 1]["position_0"] - $keyPoints[$index]["position_1"];
	$timeBetweenKeyPoints = $keyPoints[$index + 1]["time_0"] - $keyPoints[$index]["time_1"];
	$availableTime = $timeBetweenKeyPoints + $timeDiff;

	if ($keyPoints[$index]["speed_0"] == 0 && $keyPoints[$index + 1]["speed_1"] == 0) {
		return;
	}
	if ($keyPoints[$index + 1]["speed_1"] != 0) {
		$lengthDifference = calculateDistanceforSpeedFineTuning($keyPoints[$index + 1]["speed_0"], $keyPoints[$index + 1]["speed_1"], $availableDistance, $availableTime);
		$keyPoints[$index + 1]["position_0"] = $keyPoints[$index + 1]["position_0"] + $lengthDifference;
		$keyPoints[$index + 1]["position_1"] = $keyPoints[$index + 1]["position_1"] + $lengthDifference;
	} else {
		$lengthDifference = calculateDistanceforSpeedFineTuning($keyPoints[$index]["speed_0"], $keyPoints[$index]["speed_1"], $availableDistance, $availableTime);
		$keyPoints[$index]["position_0"] = $keyPoints[$index]["position_0"] + $lengthDifference;
		$keyPoints[$index]["position_1"] = $keyPoints[$index]["position_1"] + $lengthDifference;
	}
}

function calculateDistanceforSpeedFineTuning(int $v_0, int $v_1, float $distance, float $time) : float {

	$firstSecondsPerMeter = distanceWithSpeedToTime($v_0, 1);
	$secondSecondsPerMeter = distanceWithSpeedToTime($v_1, 1);

	$lengthDifference = $distance - (($time - ($distance * $firstSecondsPerMeter))/($secondSecondsPerMeter - $firstSecondsPerMeter));

	return $lengthDifference;
}

function deleteDoubledKeyPoints($temporaryKeyPoints) {

	do {
		$foundDoubledKeyPoints = false;
		$doubledIndex = array();
		for ($i = 0; $i < (sizeof($temporaryKeyPoints) - 1); $i++) {
			if ($temporaryKeyPoints[$i]["speed_0"] == $temporaryKeyPoints[$i]["speed_1"]) {
				$foundDoubledKeyPoints = true;
				array_push($doubledIndex, $i);
			}
		}

		foreach ($doubledIndex as $index) {
			unset($temporaryKeyPoints[$index]);
		}

		$temporaryKeyPoints = array_values($temporaryKeyPoints);

	} while ($foundDoubledKeyPoints);

	return $temporaryKeyPoints;
}

function createTrainChanges(float $currentTime) : array {

	global $keyPoints;
	global $verzoegerung;

	$returnTrainSpeedChange = array();
	$returnTrainTimeChange = array();
	$returnTrainPositionChange = array();

	array_push($returnTrainTimeChange, $currentTime);
	array_push($returnTrainSpeedChange, $keyPoints[0]["speed_0"]);
	array_push($returnTrainPositionChange, 0);

	for ($i = 0; $i < (sizeof($keyPoints) - 1); $i++) {

		if ($keyPoints[$i]["speed_0"] < $keyPoints[$i]["speed_1"]) {

			for ($j = ($keyPoints[$i]["speed_0"] + 2); $j <= $keyPoints[$i]["speed_1"]; $j = $j + 2) {
				array_push($returnTrainPositionChange, (end($returnTrainPositionChange) + getBrakeDistance(($j - 2), $j, $verzoegerung)));
				array_push($returnTrainSpeedChange, $j);
				array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + (getBrakeTime(($j - 2), $j, $verzoegerung))));
			}

			array_push($returnTrainPositionChange, $keyPoints[$i + 1]["position_0"]);
			array_push($returnTrainSpeedChange, $keyPoints[$i + 1]["speed_0"]);
			array_push($returnTrainTimeChange, (end($returnTrainPositionChange) + distanceWithSpeedToTime($keyPoints[$i]["speed_1"], ($keyPoints[$i + 1]["position_0"] - $keyPoints[$i]["position_1"]))));

		} else {

			// TODO: Möglichst spät!


			array_push($returnTrainPositionChange, $keyPoints[$i]["position_1"] - getBrakeDistance($keyPoints[$i]["speed_0"],$keyPoints[$i]["speed_1"],$verzoegerung));
			array_push($returnTrainSpeedChange, $keyPoints[$i]["speed_0"]);
			array_push($returnTrainTimeChange, (end($returnTrainPositionChange) + distanceWithSpeedToTime($keyPoints[$i]["speed_0"], ($keyPoints[$i]["position_1"] - $keyPoints[$i]["position_0"] - getBrakeDistance($keyPoints[$i]["speed_0"], $keyPoints[$i]["speed_1"], $verzoegerung)))));

			for ($j = ($keyPoints[$i]["speed_0"] - 2); $j >= $keyPoints[$i]["speed_1"]; $j = $j - 2) {
				array_push($returnTrainPositionChange, (end($returnTrainPositionChange) + getBrakeDistance(($j + 2), $j, $verzoegerung)));
				array_push($returnTrainSpeedChange, $j);
				array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + (getBrakeTime(($j + 2), $j, $verzoegerung))));
			}




		}
	}

	if ($keyPoints[array_key_last($keyPoints)]["speed_0"] < $keyPoints[array_key_last($keyPoints)]["speed_1"]) {
		for ($j = ($keyPoints[array_key_last($keyPoints)]["speed_0"] + 2); $j <= $keyPoints[array_key_last($keyPoints)]["speed_1"]; $j = $j + 2) {
			array_push($returnTrainPositionChange, (end($returnTrainPositionChange) + getBrakeDistance(($j - 2), $j, $verzoegerung)));
			array_push($returnTrainSpeedChange, $j);
			array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + (getBrakeTime(($j - 2), $j, $verzoegerung))));
		}
	} else {
		array_push($returnTrainPositionChange, $keyPoints[array_key_last($keyPoints)]["position_1"] - getBrakeDistance($keyPoints[array_key_last($keyPoints)]["speed_0"],$keyPoints[array_key_last($keyPoints)]["speed_1"],$verzoegerung));
		array_push($returnTrainSpeedChange, $keyPoints[array_key_last($keyPoints)]["speed_0"]);
		array_push($returnTrainTimeChange, (end($returnTrainPositionChange) + distanceWithSpeedToTime($keyPoints[array_key_last($keyPoints)]["speed_0"], ($keyPoints[array_key_last($keyPoints)]["position_1"] - $keyPoints[array_key_last($keyPoints)]["position_0"] - getBrakeDistance($keyPoints[array_key_last($keyPoints)]["speed_0"], $keyPoints[array_key_last($keyPoints)]["speed_1"], $verzoegerung)))));
		for ($j = ($keyPoints[array_key_last($keyPoints)]["speed_0"] - 2); $j >= $keyPoints[array_key_last($keyPoints)]["speed_1"]; $j = $j - 2) {
			array_push($returnTrainPositionChange, (end($returnTrainPositionChange) + getBrakeDistance(($j + 2), $j, $verzoegerung)));
			array_push($returnTrainSpeedChange, $j);
			array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + (getBrakeTime(($j + 2), $j, $verzoegerung))));
		}
	}

	return array($returnTrainPositionChange, $returnTrainSpeedChange, $returnTrainTimeChange);
}

function findMaxSpeed() : array {

	global $keyPoints;

	$maxSpeed = null;
	$minSpeed = null;
	$keyPointIndex = null;

	for ($i = 0; $i < (sizeof($keyPoints) - 1); $i++) {
		if ($maxSpeed <= $keyPoints[$i]["speed_1"]) {
			$maxSpeed = $keyPoints[$i]["speed_1"];
			$keyPointIndex = $i;
		}
	}

	if ($keyPoints[$keyPointIndex]["speed_0"] < $keyPoints[$keyPointIndex + 1]["speed_1"]) {
		$minSpeed = $keyPoints[$keyPointIndex + 1]["speed_1"];
	} else {
		$minSpeed = $keyPoints[$keyPointIndex]["speed_0"];
	}

	// TODO: Überprüfen, ob das gelöschtz werden kann...
	if ($minSpeed < 10) {
		$minSpeed = 10;
	}

	return array("min_speed" => $minSpeed, "max_speed" => $maxSpeed, "first_key_point_index" => $keyPointIndex);
}

function calculateTrainTimeChange() : array {

	global $keyPoints;
	global $verzoegerung;

	$returnAllTimes = array();
	$returnAllTimes[0] = $keyPoints[0]["time_0"];

	// TODO: Was ist , wenn die Startgeschwindigkeit ungerade ist?
	for ($i = 0; $i < (sizeof($keyPoints) - 1); $i++) {
		if ($keyPoints[$i]["speed_0"] < $keyPoints[$i]["speed_1"]) {
			for ($j = $keyPoints[$i]["speed_0"] + 2; $j <= $keyPoints[$i]["speed_1"]; $j = $j + 2) {
				array_push($returnAllTimes, (end($returnAllTimes) + (getBrakeTime($j - 2, $j, $verzoegerung))));
			}
			array_push($returnAllTimes, (end($returnAllTimes) + distanceWithSpeedToTime($keyPoints[$i]["speed_1"], ($keyPoints[$i + 1]["position_0"] - $keyPoints[$i]["position_1"]))));
		} else {
			for ($j = $keyPoints[$i]["speed_0"] - 2; $j >= $keyPoints[$i]["speed_1"]; $j = $j - 2) {
				array_push($returnAllTimes, (end($returnAllTimes) + (getBrakeTime($j + 2, $j, $verzoegerung))));
			}
			array_push($returnAllTimes, (end($returnAllTimes) + distanceWithSpeedToTime($keyPoints[$i]["speed_1"], ($keyPoints[$i + 1]["position_0"] - $keyPoints[$i]["position_1"]))));
		}
	}

	if ($keyPoints[array_key_last($keyPoints)]["speed_0"] < $keyPoints[array_key_last($keyPoints)]["speed_1"]) {
		for ($i = ($keyPoints[array_key_last($keyPoints)]["speed_0"] + 2); $i <= $keyPoints[array_key_last($keyPoints)]["speed_1"]; $i = $i + 2) {
			array_push($returnAllTimes, (end($returnAllTimes) + (getBrakeTime($i - 2, $i, $verzoegerung))));
		}
	} else {
		for ($i = ($keyPoints[array_key_last($keyPoints)]["speed_0"] - 2); $i >= $keyPoints[array_key_last($keyPoints)]["speed_1"]; $i = $i - 2) {
			array_push($returnAllTimes, (end($returnAllTimes) + (getBrakeTime($i + 2, $i, $verzoegerung))));
		}
	}

	return $returnAllTimes;
}

function checkIfTheSpeedCanBeDecreased() : array {

	global $keyPoints;
	global $returnPossibleSpeed;

	$returnPossibleSpeed = array();

	for ($i = 0; $i < (sizeof($keyPoints) - 1); $i++) {

		$v_maxBetweenKeyPoints = $keyPoints[$i]["speed_1"];
		$v_minBetweenKeyPoints = null;

		if ($keyPoints[$i]["speed_0"] < $v_maxBetweenKeyPoints && $keyPoints[$i + 1]["speed_1"] < $v_maxBetweenKeyPoints) {
			$v_minBetweenKeyPoints = $keyPoints[$i]["speed_0"];
			if ($keyPoints[$i + 1]["speed_1"] > $v_minBetweenKeyPoints) {
				$v_minBetweenKeyPoints = $keyPoints[$i + 1]["speed_1"];
			}
		}

		if ($v_minBetweenKeyPoints == 0 && $v_maxBetweenKeyPoints >= 10) {
			$v_minBetweenKeyPoints = 10;
		}

		if ($v_minBetweenKeyPoints != null) {
			// Der KeyPoint_indexn beschreibt den ersten der beiden KeyPoints
			array_push($returnPossibleSpeed, array("KeyPoint_index" => $i, "values" => range($v_minBetweenKeyPoints, $v_maxBetweenKeyPoints, 10)));
		}
	}

	if (sizeof($returnPossibleSpeed) > 0) {
		return array("possible" => true, "range" => $returnPossibleSpeed);
	} else {
		return array("possible" => false);
	}
}

function calculateTimeFromKeyPoints($inputKeyPoints = null) {

	global $keyPoints;
	global $verzoegerung;

	if ($inputKeyPoints == null) {
		$localKeyPoints = $keyPoints;
	} else {
		$localKeyPoints = $inputKeyPoints;
	}

	for ($i = 0; $i < (sizeof($localKeyPoints) - 1); $i++) {
		$localKeyPoints[$i]["time_1"] = getBrakeTime($localKeyPoints[$i]["speed_0"], $localKeyPoints[$i]["speed_1"], $verzoegerung) + $localKeyPoints[$i]["time_0"];
		$localKeyPoints[$i + 1]["time_0"] = distanceWithSpeedToTime($localKeyPoints[$i]["speed_1"], ($localKeyPoints[$i + 1]["position_0"]) - $localKeyPoints[$i]["position_1"]) + $localKeyPoints[$i]["time_1"];
	}

	$localKeyPoints[array_key_last($localKeyPoints)]["time_1"] = getBrakeTime($localKeyPoints[array_key_last($localKeyPoints)]["speed_0"], $localKeyPoints[array_key_last($localKeyPoints)]["speed_1"], $verzoegerung) + $localKeyPoints[array_key_last($localKeyPoints)]["time_0"];
	$keyPoints = $localKeyPoints;

	return end($keyPoints)["time_1"] - $keyPoints[0]["time_0"];
}

function distanceWithSpeedToTime (int $v, float $distance) : float {
	return (($distance)/($v / 3.6));
}

function checkTrainChangeOverlap() {
	global $trainSpeedChange;
	global $trainPositionChange;

	do {
		$foundOverlap = false;
		for ($i = 0; $i < (sizeof($trainPositionChange) - 1); $i++) {
			if ($trainPositionChange[$i] >= $trainPositionChange[$i + 1]) {
				$foundOverlap = true;
				unset($trainPositionChange[$i]);
				unset($trainSpeedChange[$i]);
			}
			$trainPositionChange = array_values($trainPositionChange);
			$trainSpeedChange = array_values($trainSpeedChange);
		}
	} while($foundOverlap);
}

function getVMaxBetweenTwoPoints(float $distance, int $v_0, int $v_1) : int {
	global $verzoegerung;



	$v_max = null;

	for ($i = 0; $i <= 120; $i = $i + 10) {
		if ((getBrakeDistance($v_0, $i, $verzoegerung) + getBrakeDistance($i, $v_1, $verzoegerung)) > $distance) {
			$v_max = $i - 10;
			break;
		}
	}
	return $v_max;
}

function checkKeyPointsOverlap() {
	global $keyPoints;
	global $verzoegerung;

	//var_dump($keyPoints);

	for ($i = 0; $i < (sizeof($keyPoints) - 1); $i++) {
		if ($keyPoints[$i]["position_1"] > $keyPoints[$i + 1]["position_0"]) {
			$v_max = getVMaxBetweenTwoPoints(($keyPoints[$i + 1]["position_1"] - $keyPoints[$i]["position_0"]), $keyPoints[$i]["speed_0"], $keyPoints[$i + 1]["speed_1"]);
			$keyPoints[$i]["position_1"] = getBrakeDistance($keyPoints[$i]["speed_0"], $v_max, $verzoegerung) + $keyPoints[$i]["position_0"];
			$keyPoints[$i + 1]["position_0"] = $keyPoints[$i + 1]["position_1"] - getBrakeDistance($v_max, $keyPoints[$i + 1]["speed_0"], $verzoegerung);
			$keyPoints[$i]["speed_1"] = $v_max;
			$keyPoints[$i + 1]["speed_0"] = $v_max;
			$keyPoints[$i]["max_speed"] = $v_max;
			$keyPoints[$i + 1]["max_speed"] = $v_max;
		}
	}
}

function createKeyPointsFromGroupedSections (array $groupedSections, array $failedSections) : array {

	//sleep(5);
	global $next_v_max;
	global $next_lengths;
	global $indexCurrentSection;
	global $indexTargetSection;
	global $currentSpeed;
	global $targetSpeed;
	global $targetPosition;
	global $currentPosition;
	global $verzoegerung;
	global $cumulativeSectionLengthEnd;
	global $cumulativeSectionLengthStart;
	//global $previousFailedSections;

	$previousFailedSections = array();

	$newSpeedChange = array();
	$newPositionChange = array();
	$returnKeyPoints = array();

	$increaseKeyPoint = array();
	$decreaseKeyPoint = array();


	foreach ($groupedSections as $groupKey => $groupValue) {
		$distance = 0;
		$v_max = null;
		$v_0 = null;
		$v_1 = null;
		$absolutPositionStart = $cumulativeSectionLengthStart[$groupValue[0]];
		$absolutPositionEnd = $cumulativeSectionLengthEnd[end($groupValue)];

		for ($i = $groupValue[0]; $i <= end($groupValue); $i++) {
			$distance = $distance + $next_lengths[$i];
			if ($v_max == null) {
				$v_max = $next_v_max[$i];
			} else {
				if ($v_max > $next_v_max[$i]) {
					$v_max = $next_v_max[$i];
				}
			}
		}

		if (in_array($indexCurrentSection, $groupValue)) {
			$distance = $distance - $currentPosition;
		}

		if (in_array($indexTargetSection, $groupValue)) {
			$distance = $distance + $targetPosition - $next_lengths[$indexTargetSection];
		}

		if ($groupKey == 0) {
			$v_0 = $currentSpeed;
		} else {
			if ($next_v_max[$groupValue[0] - 1] < $next_v_max[$groupValue[0]]) {
				$v_0 = $next_v_max[$groupValue[0] - 1];
			} else {
				$v_0 = $next_v_max[$groupValue[0]];
			}
		}

		if ($groupKey == sizeof($groupedSections) - 1) {
			$v_1 = $targetSpeed;
		} else {
			if ($next_v_max[end($groupValue) + 1] < $next_v_max[end($groupValue)]) {
				$v_1 = $next_v_max[end($groupValue) + 1];
			} else {
				$v_1 = $next_v_max[end($groupValue)];
			}
		}

		$new_v_max = null;

		for ($i = 0; $i <= $v_max; $i = $i + 10) {
			if ((getBrakeDistance($v_0, $i, $verzoegerung) + getBrakeDistance($i, $v_0, $verzoegerung)) < $distance) {
				$new_v_max = $i;
			}
		}

		$v_max = $new_v_max;

		if (!(sizeof($groupValue) == 1 && in_array($groupValue[0], $failedSections))) {

			//var_dump(gettype($previousFailedSections));
			$v_maxFirstIteration = null;
			for ($i = $currentSpeed; $i <= 120; $i = $i + 10) {
				if ((getBrakeDistance($currentSpeed, $i, $verzoegerung) + getBrakeDistance($i, $targetSpeed, $verzoegerung)) > $distance) {
					$v_maxFirstIteration = $i - 10;
					$v_max = $v_maxFirstIteration;
					break;
				}
			}
		} else {
			//var_dump($groupValue);
			array_push($previousFailedSections, $groupValue[0]);
			//array_push($previousFailedSections, $groupValue);
		}

		if (sizeof($newSpeedChange) == 0) {
			array_push($newSpeedChange, $v_0);
		}

		if (sizeof($newPositionChange) == 0) {
			array_push($newPositionChange, $absolutPositionStart);
		}

		if ($v_0 < $v_max) {
			$increaseKeyPoint = ["position_0" => end($newPositionChange), "speed_0" => $v_0];
			if ($v_0 % 2 != 0) {
				$tempDistance = getBrakeDistance($v_0, ($v_0 + 1), $verzoegerung);
				array_push($newSpeedChange, ($v_0 + 1));
				array_push($newPositionChange, end($newPositionChange) + $tempDistance);
				$v_0 = $v_0 + 1;
			}
			for ($i = $v_0; $i <= ($v_max - 2); $i = $i + 2) {
				$tempDistance = getBrakeDistance($i, ($i + 2), $verzoegerung);
				array_push($newSpeedChange, ($i + 2));
				array_push($newPositionChange, end($newPositionChange) + $tempDistance);
			}
			$increaseKeyPoint["position_1"] =  end($newPositionChange);
			$increaseKeyPoint["speed_1"] =  $v_max;
		} elseif ($v_0 > $v_max) {
			$increaseKeyPoint = ["position_0" => end($newPositionChange), "speed_0" => $v_0];
			if ($v_0 % 2 != 0) {
				$tempDistance = getBrakeDistance($v_0, ($v_0 - 1), $verzoegerung);
				array_push($newSpeedChange, ($v_0 - 1));
				array_push($newPositionChange, end($newPositionChange) + $tempDistance);
				$v_0 = $v_0 - 1;
			}
			for ($i = $v_0; $i >= ($v_max + 2); $i = $i - 2) {
				$tempDistance = getBrakeDistance($i, ($i - 2), $verzoegerung);
				array_push($newSpeedChange, ($i - 2));
				array_push($newPositionChange, end($newPositionChange) + $tempDistance);
			}
			$increaseKeyPoint["position_1"] =  end($newPositionChange);
			$increaseKeyPoint["speed_1"] =  $v_max;
		}

		array_push($newSpeedChange, $v_max);
		array_push($newPositionChange, $absolutPositionEnd - getBrakeDistance($v_max, $v_1, $verzoegerung));

		if ($v_1 > $v_max) {
			$decreaseKeyPoint = ["position_0" => end($newPositionChange), "speed_0" => $v_max/*, "time_0" => end($newTimeChange)*/];
			for ($i = $v_max; $i <= ($v_1 - 2); $i = $i + 2) {
				$tempDistance = getBrakeDistance($i, ($i + 2), $verzoegerung);
				array_push($newSpeedChange, ($i + 2));
				array_push($newPositionChange, end($newPositionChange) + $tempDistance);
			}
			$decreaseKeyPoint["position_1"] =  end($newPositionChange);
			$decreaseKeyPoint["speed_1"] =  $v_1;
		} if ($v_1 < $v_max) {
			$decreaseKeyPoint = ["position_0" => end($newPositionChange), "speed_0" => $v_max];
			for ($i = $v_max; $i >= ($v_1 + 2); $i = $i - 2) {
				$tempDistance = getBrakeDistance($i, ($i - 2), $verzoegerung);
				array_push($newSpeedChange, ($i - 2));
				array_push($newPositionChange, end($newPositionChange) + $tempDistance);
			}
			$decreaseKeyPoint["position_1"] =  end($newPositionChange);
			$decreaseKeyPoint["speed_1"] =  $v_1;
		}

		if (!in_array($increaseKeyPoint, $returnKeyPoints) && sizeof($increaseKeyPoint) != 0) {
			array_push($returnKeyPoints, $increaseKeyPoint);
		}
		if (!in_array($decreaseKeyPoint, $returnKeyPoints) && sizeof($decreaseKeyPoint) != 0) {
			array_push($returnKeyPoints, $decreaseKeyPoint);
		}
	}


	//var_dump($previousFailedSections);

	return array($newPositionChange, $newSpeedChange, $returnKeyPoints, $previousFailedSections);
}

function createGroupedSections(array $failedSections) : array {
	global $indexCurrentSection;
	global $indexTargetSection;





	$succeedSections = array();

	for ($i = $indexCurrentSection; $i <= $indexTargetSection; $i++) {
		array_push($succeedSections, $i);
	}





	foreach ($failedSections as $failedSectionsKey => $failedSectionsValue) {
		unset($succeedSections[array_search($failedSectionsValue, $succeedSections)]);
	}

	//var_dump($succeedSections);

	$index = 0;
	$groupedSections=array();


	//var_dump($failedSections);



	$previous=NULL;
	foreach($failedSections as $key => $value) {
		//$groupedSections[$index]["fail"] = true;
		$groupedSections[$index][]=$value;
		$index++;
	}

	$previous=NULL;
	foreach($succeedSections as $key => $value) {
		if($value>$previous + 1) {
			$index++;
		}
		//$groupedSections[$index]["fail"] = false;
		$groupedSections[$index][]=$value;
		$previous=$value;
	}

	usort($groupedSections, 'sortKeyPoints');

	//var_dump($groupedSections);
	//sleep(10);

	return $groupedSections;
}

function sortKeyPoints($a, $b) {
	return $a[0] - $b[0];
}

function sortKeyPointsTwo($a, $b) {
	return $a["sections"][0] - $b["sections"][0];
}

function checkIfTrainIsToFastInCertainSections() : array {

	global $trainPositionChange;
	global $trainSpeedChange;
	global $cumulativeSectionLengthStart;
	global $next_v_max;


	$faildSections = array();

	foreach ($trainPositionChange as $trainPositionChangeKey => $trainPositionChangeValue) {
		foreach ($cumulativeSectionLengthStart as $cumulativeSectionLengthStartKey => $cumulativeSectionLengthStartValue) {
			if ($trainPositionChangeValue < $cumulativeSectionLengthStartValue) {
				// jetzt den davor überprüfen
				if ($trainSpeedChange[$trainPositionChangeKey] > $next_v_max[$cumulativeSectionLengthStartKey - 1]) {

					//var_dump($trainPositionChangeValue);

					array_push($faildSections, ($cumulativeSectionLengthStartKey -1));
				}
				break;
			}
		}
	}




	if (sizeof($faildSections) == 0) {
		return array("failed" => false);
	} else {
		return array("failed" => true, "failed_sections" => array_unique($faildSections));
	}
}

function safeTrainChangeToJSONFile() {

	global $trainPositionChange;
	global $trainSpeedChange;

	$speedOverPosition = array_map('toArr', $trainPositionChange, $trainSpeedChange);
	$speedOverPosition = json_encode($speedOverPosition);

	$fp = fopen('../json/speedOverPosition_v1.json', 'w');
	fwrite($fp, $speedOverPosition);
	fclose($fp);


}

function toArr(){
	return func_get_args();
}

function convertKeyPointsToTrainChangeArray (array $keyPoints) : array {

	global $verzoegerung;

	$trainSpeedChangeReturn = array();
	$trainPositionChnageReturn = array();

	array_push($trainPositionChnageReturn, $keyPoints[0]["position_0"]);
	array_push($trainSpeedChangeReturn, $keyPoints[0]["speed_0"]);

	for ($i = 0; $i <= (sizeof($keyPoints) - 2); $i++) {
		if ($keyPoints[$i]["speed_0"] < $keyPoints[$i]["speed_1"]) {
			for ($j = $keyPoints[$i]["speed_0"]; $j < $keyPoints[$i]["speed_1"]; $j = $j + 2) {
				array_push($trainPositionChnageReturn, (end($trainPositionChnageReturn) + getBrakeDistance($j, ($j + 2), $verzoegerung)));
				array_push($trainSpeedChangeReturn, ($j + 2));
			}
		} elseif ($keyPoints[$i]["speed_0"] > $keyPoints[$i]["speed_1"]) {
			for ($j = $keyPoints[$i]["speed_0"]; $j > $keyPoints[$i]["speed_1"]; $j = $j - 2) {
				array_push($trainPositionChnageReturn, (end($trainPositionChnageReturn) + getBrakeDistance($j, ($j - 2), $verzoegerung)));
				array_push($trainSpeedChangeReturn, ($j - 2));
			}
		}
		array_push($trainPositionChnageReturn, $keyPoints[$i + 1]["position_0"]);
		array_push($trainSpeedChangeReturn, $keyPoints[$i + 1]["speed_0"]);
	}

	//Für den letzten KeyPoint
	if (end($keyPoints)["speed_0"] < end($keyPoints)["speed_1"]) {
		for ($j = end($keyPoints)["speed_0"]; $j < end($keyPoints)["speed_1"]; $j = $j + 2) {
			array_push($trainPositionChnageReturn, (end($trainPositionChnageReturn) + getBrakeDistance($j, ($j + 2), $verzoegerung)));
			array_push($trainSpeedChangeReturn, ($j + 2));
		}
	} elseif (end($keyPoints)["speed_0"] > end($keyPoints)["speed_1"]) {
		for ($j = end($keyPoints)["speed_0"]; $j > end($keyPoints)["speed_1"]; $j = $j - 2) {
			array_push($trainPositionChnageReturn, (end($trainPositionChnageReturn) + getBrakeDistance($j, ($j - 2), $verzoegerung)));
			array_push($trainSpeedChangeReturn, ($j - 2));
		}
	}

	return array($trainPositionChnageReturn, $trainSpeedChangeReturn);
}

function createKeyPoint (float $position_0, float $position_1, int $speed_0, int $speed_1, int $maxSpeed = NULL) : array {
	return array("position_0" => $position_0, "position_1" => $position_1, "speed_0" => $speed_0, "speed_1" => $speed_1, "max_speed" => $maxSpeed);
}

// Anpassen für viele Schritte => $a bleibt konstant?! => Eher nicht anpassen und allgemein halten
function getBrakeDistance (float $v_0, float $v_1, float $verzoegerung) : float {
	// v in km/h
	// a in m/s^2
	// return in m
	// TODO: Wie sieht es mit der Reaktionszeit aus? (Wenn ja, dann nur bei der Ersten 2 km/h_diff Bremsung
	if ($v_0 > $v_1) {
		return $bremsweg = 0.5 * $verzoegerung * ((pow($v_0/3.6,2)-pow($v_1/3.6, 2))/(pow($verzoegerung, 2)));
	} if ($v_0 < $v_1) {
		return $bremsweg = -0.5 * $verzoegerung * ((pow($v_0/3.6,2)-pow($v_1/3.6, 2))/(pow($verzoegerung, 2)));
	} else {
		return 0;
	}
}

// TODO: Catch false return
function getBrakeTime (float $v_0, float $v_1, float $verzoegerung) : float  {

	$v_0 = $v_0 / 3.6;
	$v_1 = $v_1 / 3.6;

	if ($v_0 < $v_1) {
		return ($v_1/$verzoegerung) - ($v_0/$verzoegerung);
	} elseif ($v_0 > $v_1) {
		return ($v_0/$verzoegerung) - ($v_1/$verzoegerung);
	} elseif ($v_0 == $v_1) {
		return 0;
	} else {
		return false;
	}
}

/*   Probecode für weitere files   */

