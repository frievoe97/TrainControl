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

	$groupedSections = array();

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



	if (!checkIfTrainIsToFastInCertainSections()["failed"]) {
		var_dump("Fertig!");
	} else {
		$countIterationen = 1;
		while (checkIfTrainIsToFastInCertainSections()["failed"]) {
			$countIterationen++;
			$failedSections = checkIfTrainIsToFastInCertainSections()["failed_sections"];

			// create grouped Sections
			// Evtl. sort(failedSections)?!?!
			$groupedSections = createGroupedSections($failedSections);
			// create new keyPoints
			//var_dump($groupedSections);
			$testReturn = createKeyPointsFromGroupedSections($groupedSections);
			$trainPositionChange = $testReturn[0];
			$trainSpeedChange = $testReturn[1];

			// check if there is a KeyPoint Problem (Übverschneidung)
			checkKeyPointsOverlap();
			checkTrainChangeOverlap();
			safeTrainChangeToJSONFile();
		}
		var_dump($distanceToNextStop);
	}
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
		}
	}
	return $v_max;
}

function checkKeyPointsOverlap() {
	global $keyPoints;
	global $verzoegerung;

	for ($i = 0; $i < (sizeof($keyPoints) - 1); $i++) {
		if ($keyPoints[$i]["position_1"] > $keyPoints[$i + 1]["position_0"]) {
			$v_max = getVMaxBetweenTwoPoints(($keyPoints[$i + 1]["position_1"] - $keyPoints[$i]["position_0"]), $keyPoints[$i]["speed_0"], $keyPoints[$i + 1]["speed_1"]);
			$keyPoints[$i]["position_1"] = getBrakeDistance($keyPoints[$i]["speed_0"], $v_max, $verzoegerung);
			$keyPoints[$i + 1]["position_0"] = getBrakeDistance($v_max, $keyPoints[$i + 1]["speed_0"], $verzoegerung);
			$keyPoints[$i]["speed_1"] = $v_max;
			$keyPoints[$i]["speed_0"] = $v_max;
		}
	}

}

function createKeyPointsFromGroupedSections(array $groupedSections) : array {

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

	$newSpeedChange = array();
	$newPositionChange = array();

	foreach ($groupedSections as $groupKey => $groupValue) {
		//var_dump($groupValue);
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

		if (sizeof($newSpeedChange) == 0) {
			array_push($newSpeedChange, $v_0);
		}

		if (sizeof($newPositionChange) == 0) {
			array_push($newPositionChange, $absolutPositionStart);
		}

		/*
		if (sizeof($newTimeChange) == 0) {
			array_push($newTimeChange, $currentTime);
		} else {
			array_push($newTimeChange, end($newTimeChange));
		}
		*/

		$increaseKeyPoint = array();
		$decreaseKeyPoint = array();

		if ($v_0 < $v_max) {
			$increaseKeyPoint = ["position_0" => end($newPositionChange), "speed_0" => $v_0];
			if ($v_0 % 2 != 0) {
				$tempDistance = getBrakeDistance($v_0, ($v_0 + 1), $verzoegerung);
				//$tempTime = getBrakeTime($v_0, ($v_0 + 1), $verzoegerung);
				//array_push($newTimeChange, (end($newTimeChange) + $tempTime));
				array_push($newSpeedChange, ($v_0 + 1));
				array_push($newPositionChange, end($newPositionChange) + $tempDistance);
				$v_0 = $v_0 + 1;
			}
			for ($i = $v_0; $i <= ($v_max - 2); $i = $i + 2) {
				$tempDistance = getBrakeDistance($i, ($i + 2), $verzoegerung);
				//$tempTime = getBrakeTime($i, ($i + 2), $verzoegerung);
				//array_push($newTimeChange, (end($newTimeChange) + $tempTime));
				array_push($newSpeedChange, ($i + 2));
				array_push($newPositionChange, end($newPositionChange) + $tempDistance);
			}
			$increaseKeyPoint["position_1"] =  end($newPositionChange);
			$increaseKeyPoint["speed_1"] =  $v_max;
			//$increaseKeyPoint["time_1"] =   end($newTimeChange);
		} elseif ($v_0 > $v_max) {
			$increaseKeyPoint = ["position_0" => end($newPositionChange), "speed_0" => $v_0];
			if ($v_0 % 2 != 0) {
				$tempDistance = getBrakeDistance($v_0, ($v_0 - 1), $verzoegerung);
				//$tempTime = getBrakeTime($v_0, ($v_0 - 1), $verzoegerung);
				//array_push($newTimeChange, (end($newTimeChange) + $tempTime));
				array_push($newSpeedChange, ($v_0 - 1));
				array_push($newPositionChange, end($newPositionChange) + $tempDistance);
				$v_0 = $v_0 - 1;
			}
			for ($i = $v_0; $i >= ($v_max + 2); $i = $i - 2) {
				$tempDistance = getBrakeDistance($i, ($i - 2), $verzoegerung);
				//$tempTime = getBrakeTime($i, ($i - 2), $verzoegerung);
				//array_push($newTimeChange, (end($newTimeChange) + $tempTime));
				array_push($newSpeedChange, ($i - 2));
				array_push($newPositionChange, end($newPositionChange) + $tempDistance);
			}
			$increaseKeyPoint["position_1"] =  end($newPositionChange);
			$increaseKeyPoint["speed_1"] =  $v_max;
			//$increaseKeyPoint["time_1"] =   end($newTimeChange);
		}

		array_push($newSpeedChange, $v_max);
		array_push($newPositionChange, $absolutPositionEnd - getBrakeDistance($v_max, $v_1, $verzoegerung));
		//array_push($newTimeChange, end($newTimeChange) + (($absolutPositionEnd - getBrakeDistance($v_0, $v_max, $verzoegerung) - getBrakeDistance($v_max, $v_1, $verzoegerung))/$v_max));

		if ($v_1 > $v_max) {
			$decreaseKeyPoint = ["position_0" => end($newPositionChange), "speed_0" => $v_max, "time_0" => end($newTimeChange)];
			for ($i = $v_max; $i <= ($v_1 - 2); $i = $i + 2) {
				$tempDistance = getBrakeDistance($i, ($i + 2), $verzoegerung);
				$tempTime = getBrakeTime($i, ($i + 2), $verzoegerung);
				//array_push($newTimeChange, (end($newTimeChange) + $tempTime));
				array_push($newSpeedChange, ($i + 2));
				array_push($newPositionChange, end($newPositionChange) + $tempDistance);
			}
			$decreaseKeyPoint["position_1"] =  end($newPositionChange);
			$decreaseKeyPoint["speed_1"] =  $v_1;
			//$decreaseKeyPoint["time_1"] =   end($newTimeChange);
		} if ($v_1 < $v_max) {
			$decreaseKeyPoint = ["position_0" => end($newPositionChange), "speed_0" => $v_max];
			for ($i = $v_max; $i >= ($v_1 + 2); $i = $i - 2) {
				$tempDistance = getBrakeDistance($i, ($i - 2), $verzoegerung);
				//$tempTime = getBrakeTime($i, ($i - 2), $verzoegerung);
				//array_push($newTimeChange, (end($newTimeChange) + $tempTime));
				array_push($newSpeedChange, ($i - 2));
				array_push($newPositionChange, end($newPositionChange) + $tempDistance);
			}
			$decreaseKeyPoint["position_1"] =  end($newPositionChange);
			$decreaseKeyPoint["speed_1"] =  $v_1;
			//$decreaseKeyPoint["time_1"] =   end($newTimeChange);
		}

		// TODO: Was machen nochmal die?
		/*
		if (sizeof($increaseKeyPoint) != 0) {
			array_push($keyPointsReturn, $increaseKeyPoint);
		}
		if (sizeof($decreaseKeyPoint) != 0) {
			array_push($keyPointsReturn, $decreaseKeyPoint);
		}
		*/

		/*
		$speedOverPosition = array_map('toArr', $newPositionChange, $newSpeedChange);
		$speedOverPosition = json_encode($speedOverPosition);

		$fp = fopen('../json/speedOverPosition_v1.json', 'w');
		fwrite($fp, $speedOverPosition);
		fclose($fp);
		*/
	}

	return array($newPositionChange, $newSpeedChange);
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

	$index = 0;
	$groupedSections=array();

	$previous=NULL;
	foreach($failedSections as $key => $value) {
		$groupedSections[$index][]=$value;
		$index++;
	}

	$previous=NULL;
	foreach($succeedSections as $key => $value) {
		if($value>$previous + 1) {
			$index++;
		}
		$groupedSections[$index][]=$value;
		$previous=$value;
	}

	usort($groupedSections, 'sortKeyPoints');

	return $groupedSections;
}

function sortKeyPoints($a, $b) {
	return $a[0] - $b[0];
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

function createKeyPoint (float $position_0, float $position_1, int $speed_0, int $speed_1) : array {
	return array("position_0" => $position_0, "position_1" => $position_1, "speed_0" => $speed_0, "speed_1" => $speed_1);
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
	} if ($v_0 == $v_1) {
		return 0;
	}
}

function getBrakeTime (float $v_0, float $v_1, float $verzoegerung) : float  {

	$v_0 = $v_0 / 3.6;
	$v_1 = $v_1 / 3.6;

	if ($v_0 < $v_1) {
		return ($v_1/$verzoegerung) - ($v_0/$verzoegerung);
	}

	if ($v_0 > $v_1) {
		return ($v_0/$verzoegerung) - ($v_1/$verzoegerung);
	}

	if ($v_0 == $v_1) {
		return 0;
	}
}