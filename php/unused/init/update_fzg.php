
<?php
/*
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

	$next_sections = $value["next_sections"];
	$next_lengths = $value["next_lenghts"];
	$next_v_max =$value["next_v_max"];

	$verzoegerung = $value["verzoegerung"];
	$notverzoegerung = $value["notverzoegerung"];
	$section = $value["section"];
	$position = $value["position"];
	$speed = $value["speed"];

	$nextSpeed = $value["next_timetable_change_speed"];
	$nextSection = $value["next_timetable_change_section"];
	$nextPosition = $value["next_timetable_change_position"];
	$nextTime = $value["next_timetable_change_time"];

	// abrunden (durch int eh automatisch)
	$distanceToTheNextScheduledStop = getCompleteDistancToTheNextScheduledStop($next_sections, $next_lengths, $section, $position, $nextSection, $nextPosition);

	$speedOnFreeTrack = getMinimumSpeedToArriveOnTime($distanceToTheNextScheduledStop, $currentTime, $nextTime, $speed, $nextSpeed, $verzoegerung, $notverzoegerung, $section);

	// TODO: Check if emergency brake is needed


	if (getBrakeDistance($speed, $nextSpeed, $verzoegerung) <= $distanceToTheNextScheduledStop) {
		$tempAllChanges = getSpeedChange($speed, $speedOnFreeTrack, $nextSpeed, $next_sections, $next_lengths, $next_v_max, $position, $distanceToTheNextScheduledStop, $verzoegerung, $currentTime, $section, $nextSection);
	} else {
		echo "Notbremsung!\n";
	}

	$tempTimesChanges = $tempAllChanges[0];
	$tempSpeedChanges = $tempAllChanges[1];
	$tempPositionChanges = $tempAllChanges[2];
	$tempCumulativeSections = $tempAllChanges[3];
	$tempNext_v_max = $tempAllChanges[4];
	$tempKeyPoints = $tempAllChanges[5];

	$newKeyPoints = checkIfTrainIsToFastInSection($tempTimesChanges, $tempSpeedChanges, $tempPositionChanges, $tempCumulativeSections, $tempNext_v_max, $position, $nextPosition, $speed, $nextSpeed, $tempKeyPoints, $next_lengths, $next_v_max, $next_sections, $section, $nextSection, $position, $nextPosition, $verzoegerung, $currentTime, $nextTime);

	algorithmStepThree($newKeyPoints, $next_sections, $next_v_max, $next_lengths, $section, $position, $speed, $nextSection, $nextPosition, $nextSpeed, $verzoegerung);

	// TODO: What is, when the train has to increase the speed?

	return $allTrains[$key];

}

function algorithmStepThree(array $keyPoints, array $sections, array $v_max, array $lengths, int $currentSection, int $currentPosition, int $currentSpeed, int $targetSection, int $targetPosition, int $targetSpeed, float $verzoegerung) {

	$positionDiff = null;

	foreach ($sections as $sectionKey => $sectionValue) {
		if ($sectionValue == $currentSection) {
			break;
		}
		$positionDiff = $positionDiff + $lengths[$sectionKey];
	}

	$positionDiff = $positionDiff + $currentPosition;
	$intervals = array();

	foreach ($keyPoints as $keyPointKey => $keyPointValue) {
		if ($keyPointKey < (sizeof($keyPoints) - 1)) {
			array_push($intervals, array("position_0" => $keyPointValue["position_1"], "position_1" => $keyPoints[$keyPointKey + 1]["position_0"], "time_0" => $keyPointValue["time_1"], "time_1" => $keyPoints[$keyPointKey + 1]["time_0"], "speed" => $keyPointValue["speed_1"]));
		}
	}

	$cumulativeSectionLength = array();
	$sum = null;
	foreach ($lengths as $lengthKey => $lengthValue) {
		$sum = $sum + $lengthValue;
		$cumulativeSectionLength[$lengthKey] = $sum;
	}

	foreach ($intervals as $intervalKey => $intervalValue) {
		$intervalSections = array();
		foreach ($cumulativeSectionLength as $cumulativeSectionLengthKey => $cumulativeSectionLengthValue) {
			if (($intervalValue["position_0"] + $positionDiff) < $cumulativeSectionLengthValue && sizeof($intervalSections) == 0) {
				array_push($intervalSections, $cumulativeSectionLengthKey);
			}
			if (($intervalValue["position_1"] + $positionDiff) <= $cumulativeSectionLengthValue && sizeof($intervalSections) == 1) {
				array_push($intervalSections, $cumulativeSectionLengthKey);
			}
		}
		$startSection = $intervalSections[0];
		$endSection = $intervalSections[1];
		$intervalSections = array();
		$intervalSections["position_0"] = $intervalValue["position_0"];
		$intervalSections["position_1"] = $intervalValue["position_1"];
		$intervalSections["time_0"] = $intervalValue["time_0"];
		$intervalSections["time_1"] = $intervalValue["time_1"];
		$intervalSections["speed"] = $intervalValue["speed"];
		$intervalSections["sections"] = array();
		for ($i = $startSection; $i <= $endSection; $i++) {
			if ($i == 0) {
				array_push($intervalSections["sections"], array("section_index" => $i, "v_max" => $v_max[$i], "position_0" => 0, "position_1" => $cumulativeSectionLength[$i]));
			} else {
				array_push($intervalSections["sections"], array("section_index" => $i, "v_max" => $v_max[$i], "position_0" => $cumulativeSectionLength[$i - 1], "position_1" => $cumulativeSectionLength[$i]));
			}
		}
		$intervals[$intervalKey] = $intervalSections;
	}

	foreach ($intervals as $intervalKey => $intervalValue) {

		$startIntervalSection = $sections[$intervalValue["sections"][0]["section_index"]];
		$endIntervalSection = $sections[end($intervalValue["sections"])["section_index"]];
		$startPosition = $intervalValue["position_0"];
		$endPosition = $intervalValue["position_1"];
		$intervalSpeed = $intervalValue["speed"];
		$intervalDistance = $endPosition - $startPosition;
		$intervalMaxSpeed = null;

		for ($i = $intervalSpeed; $i <= 120; $i = $i + 10) {
			if ((getBrakeDistance($intervalSpeed, $i, $verzoegerung) * 2) > $intervalDistance) {
				$intervalMaxSpeed = $i - 10;
				break;
			}
		}
	}
}

function newAlgorithm(int $speed, int $speedOnFreeTrack, int $nextSpeed, array $nextSection, array $next_lengths, array $next_v_max, int $position, int $nextPosition, int $distanceToTheNextScheduledStop, float $verzoegerung, float $currentTime, int $currentSection, int $targetSection, float $nextTime) {

	$cumulativeSections = array();
	array_push($cumulativeSections, 0);
	$startKey = null;
	$targetKey = null;
	$keyPoints = array();

	foreach ($nextSection as $key => $value) {
		if ($value == $currentSection) {
			$startKey = $key;
		}
		if ($value == $targetSection) {
			$targetKey = $key;
		}
	}

	for ($i = $startKey; $i <= $targetKey; $i++) {
		if (count($cumulativeSections) == 1) {
			array_push($cumulativeSections, $next_lengths[$i] - $position);
		} else {
			array_push($cumulativeSections, end($cumulativeSections) + $next_lengths[$i]);
		}
	}

	if ($startKey != $targetKey) {
		for ($i = $startKey; $i <= $targetKey; $i++) {
			if ($i == $startKey) {
				$keyPoints = sectionCalculator($position, $next_lengths[$i], $speed, $next_v_max[$i + 1], $verzoegerung, $keyPoints);
			} elseif ($i == $targetKey) {
				$keyPoints = sectionCalculator(0, $nextPosition, $next_v_max[$i], $nextSpeed, $verzoegerung, $keyPoints);
			} elseif ($i != $startKey && $i != $targetKey) {
				$keyPoints = sectionCalculator(0, $next_lengths[$i], $next_v_max[$i], $next_v_max[$i + 1], $verzoegerung, $keyPoints);
			}
		}
	} elseif ($startKey == $targetKey) {

	} else {
		debugMessage("Error with the start and target key from the upcoming sections");
	}
}

function sectionCalculator(int $startPosition, int $endPosition, int $startSpeed, int $endSpeed, float $verzoegerung, array $keyPoints) {
	//var_dump($startPosition, $endPosition, $startSpeed, $endSpeed, "########");
	echo "##########\n";
	echo $startPosition, "  ", $endPosition, "  ", $startSpeed, "  ", $endSpeed;
	echo "\n##########\n";
	if ($startSpeed == $endSpeed) {
		array_push($keyPoints, array("position_0" => $startPosition, "speed_0" => $startSpeed, "position_1" => $endPosition, "speed_1" => $endSpeed));
	} else {
		array_push($keyPoints, calculateKeyPoints($startPosition, $endPosition, $startSpeed, $endSpeed, $verzoegerung));
	}
	return $keyPoints;
}

function calculateKeyPoints(int $startPosition, int $endPosition, int $startSpeed, int $endSpeed, float $verzoegerung) : array {
	$length = $endPosition - $startPosition;
	$time = getBrakeTime($startSpeed, $endSpeed, $verzoegerung);

	return array("test");
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

function getMinimumSpeedToArriveOnTime(int $distanceToTheNextScheduledStop, float $currentTime, float $nextTime, int $currentSpeed, int $targetSpeed, float $verzoegerung, float $notverzoegerung, int $currentSection): int {

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

	$possibleSpeed = end($allPossibleSpeeds);

	return $possibleSpeed;
}

function getSpeedChangePartTwo(int $speed, int $speedOnFreeTrack, int $nextSpeed, array $nextSection, array $next_lengths, array $next_v_max, int $position, int $distanceToTheNextScheduledStop, float $verzoegerung, int $currentSection, int $targetSection) {

	$startKey = null;
	$targetKey = null;
	$keyPoints = array();
	$accelerationDistance = getBrakeDistance($speed, $speedOnFreeTrack, $verzoegerung);
	$brakeDistance = getBrakeDistance($speedOnFreeTrack, $nextSpeed, $verzoegerung);

	$cumulativeSections = array();
	array_push($cumulativeSections, 0);

	foreach ($nextSection as $key => $value) {
		if ($value == $currentSection) {
			$startKey = $key;
		}
		if ($value == $targetSection) {
			$targetKey = $key;
		}
	}

	for ($i = $startKey; $i <= $targetKey; $i++) {
		if (count($cumulativeSections) == 1) {
			array_push($cumulativeSections, $next_lengths[$i] - $position);
		} else {
			array_push($cumulativeSections, end($cumulativeSections) + $next_lengths[$i]);
		}
	}

	//$allTimes = array();
	$allSpeeds = array();
	$allSectionsChange = array();
	$allPositionsChange = array();

	// 0 km/h und aktuelle Zeit
	//array_push($allTimes, $currentTime);
	array_push($allSpeeds, $speed);
	array_push($allPositionsChange, 0);
	array_push($allSectionsChange, $currentSection);

	// Time and Speed for Start
	if ($speed < $speedOnFreeTrack) {
		if ($speed % 2 != 0) {
			$tempDistance = getBrakeDistance($speed, ($speed + 1), $verzoegerung);

			array_push($allSpeeds, ($speed + 1));
			array_push($allPositionsChange, end($allPositionsChange) + $tempDistance);

			$speed = $speed + 1;
		}
		for ($i = $speed; $i <= ($speedOnFreeTrack - 2); $i = $i + 2) {
			$tempDistance = getBrakeDistance($i, ($i + 2), $verzoegerung);

			array_push($allSpeeds, ($i + 2));
			array_push($allPositionsChange, end($allPositionsChange) + $tempDistance);
		}
	} elseif ($speed > $speedOnFreeTrack) {
		if ($speed % 2 != 0) {
			$tempDistance = getBrakeDistance($speed, ($speed - 1), $verzoegerung);

			array_push($allSpeeds, ($speed - 1));
			array_push($allPositionsChange, end($allPositionsChange) + $tempDistance);

			$speed = $speed - 1;
		}
		for ($i = $speed; $i >= ($speedOnFreeTrack + 2); $i = $i - 2) {
			$tempDistance = getBrakeDistance($i, ($i - 2), $verzoegerung);

			array_push($allSpeeds, ($i - 2));
			array_push($allPositionsChange, end($allPositionsChange) + $tempDistance);
		}
	}

	array_push($keyPoints, array("position_0" => $allPositionsChange[0], "position_1" => end($allPositionsChange), "speed_0" => $allSpeeds[0], "speed_1" => end($allSpeeds), "time_0" => $allTimes[0], "time_1" => end($allTimes)));
	array_push($keyPoints, array("position_0" => end($allPositionsChange), "position_1" => ($distanceToTheNextScheduledStop - $brakeDistance), "speed_0" => end($allSpeeds), "speed_1" => $speedOnFreeTrack, "time_0" =>  end($allTimes), "time_1" => (($distanceToTheNextScheduledStop - $brakeDistance - $accelerationDistance)/$speedOnFreeTrack)));
	array_push($allTimes, (end($allTimes) + (end($cumulativeSections) - $accelerationDistance - $brakeDistance)/($speedOnFreeTrack/3.6)));
	array_push($allSpeeds, $speedOnFreeTrack);
	array_push($allPositionsChange, end($cumulativeSections) - $brakeDistance);
	array_push($allSectionsChange, $currentSection);

	// Time and Speed for End
	if ($nextSpeed > $speedOnFreeTrack) {
		for ($i = $speedOnFreeTrack; $i <= ($nextSpeed - 2); $i = $i + 2) {
			$tempDistance = getBrakeDistance($i, ($i + 2), $verzoegerung);
			$tempTime = getBrakeTime($i, ($i + 2), $verzoegerung);

			array_push($allTimes, (end($allTimes) + $tempTime));
			array_push($allSpeeds, ($i + 2));
			array_push($allPositionsChange, end($allPositionsChange) + $tempDistance);
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

	array_push($keyPoints, array("position_0" => ($distanceToTheNextScheduledStop - $accelerationDistance - $brakeDistance), "position_1" => end($allPositionsChange), "speed_0" => $speedOnFreeTrack, "speed_1" => end($allSpeeds), "time_0" =>  (($distanceToTheNextScheduledStop - $brakeDistance - $accelerationDistance)/$speedOnFreeTrack), "time_1" => end($allTimes)));

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

	$VMaxOverCumulativeSections = array_map('toArr', $cumulativeSections, $next_v_max);
	$VMaxOverPositionsJSon = json_encode($VMaxOverCumulativeSections);

	$fp = fopen('../json/VMaxOverCumulativeSections.json', 'w');
	fwrite($fp, $VMaxOverPositionsJSon);
	fclose($fp);

	return [$allTimes, $allSpeeds, $allPositionsChange, $cumulativeSections, $next_v_max, $keyPoints];
}

function getSpeedChange(int $speed, int $speedOnFreeTrack, int $nextSpeed, array $nextSection, array $next_lengths, array $next_v_max, int $position, int $distanceToTheNextScheduledStop, float $verzoegerung, float $currentTime, int $currentSection, int $targetSection) {

	$startKey = null;
	$targetKey = null;
	$keyPoints = array();
	$accelerationDistance = getBrakeDistance($speed, $speedOnFreeTrack, $verzoegerung);
	$brakeDistance = getBrakeDistance($speedOnFreeTrack, $nextSpeed, $verzoegerung);
	$cumulativeSections = array();
	array_push($cumulativeSections, 0);

	foreach ($nextSection as $key => $value) {
		if ($value == $currentSection) {
			$startKey = $key;
		}
		if ($value == $targetSection) {
			$targetKey = $key;
		}
	}

	for ($i = $startKey; $i <= $targetKey; $i++) {
		if (count($cumulativeSections) == 1) {
			array_push($cumulativeSections, $next_lengths[$i] - $position);
		} else {
			array_push($cumulativeSections, end($cumulativeSections) + $next_lengths[$i]);
		}
	}

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
		if ($speed % 2 != 0) {
			$tempDistance = getBrakeDistance($speed, ($speed + 1), $verzoegerung);
			$tempTime = getBrakeTime($speed, ($speed + 1), $verzoegerung);

			array_push($allTimes, (end($allTimes) + $tempTime));
			array_push($allSpeeds, ($speed + 1));
			array_push($allPositionsChange, end($allPositionsChange) + $tempDistance);

			$speed = $speed + 1;
		}
		for ($i = $speed; $i <= ($speedOnFreeTrack - 2); $i = $i + 2) {
			$tempDistance = getBrakeDistance($i, ($i + 2), $verzoegerung);
			$tempTime = getBrakeTime($i, ($i + 2), $verzoegerung);

			array_push($allTimes, (end($allTimes) + $tempTime));
			array_push($allSpeeds, ($i + 2));
			array_push($allPositionsChange, end($allPositionsChange) + $tempDistance);
		}
	} elseif ($speed > $speedOnFreeTrack) {
		if ($speed % 2 != 0) {
			$tempDistance = getBrakeDistance($speed, ($speed - 1), $verzoegerung);
			$tempTime = getBrakeTime($speed, ($speed - 1), $verzoegerung);

			array_push($allTimes, (end($allTimes) + $tempTime));
			array_push($allSpeeds, ($speed - 1));
			array_push($allPositionsChange, end($allPositionsChange) + $tempDistance);

			$speed = $speed - 1;
		}
		for ($i = $speed; $i >= ($speedOnFreeTrack + 2); $i = $i - 2) {
			$tempDistance = getBrakeDistance($i, ($i - 2), $verzoegerung);
			$tempTime = getBrakeTime($i, ($i - 2), $verzoegerung);

			array_push($allTimes, (end($allTimes) + $tempTime));
			array_push($allSpeeds, ($i - 2));
			array_push($allPositionsChange, end($allPositionsChange) + $tempDistance);
		}
	}

	array_push($keyPoints, array("position_0" => $allPositionsChange[0], "position_1" => end($allPositionsChange), "speed_0" => $allSpeeds[0], "speed_1" => end($allSpeeds), "time_0" => $allTimes[0], "time_1" => end($allTimes)));
	array_push($keyPoints, array("position_0" => end($allPositionsChange), "position_1" => ($distanceToTheNextScheduledStop - $brakeDistance), "speed_0" => end($allSpeeds), "speed_1" => $speedOnFreeTrack, "time_0" =>  end($allTimes), "time_1" => (($distanceToTheNextScheduledStop - $brakeDistance - $accelerationDistance)/$speedOnFreeTrack)));
	array_push($allTimes, (end($allTimes) + (end($cumulativeSections) - $accelerationDistance - $brakeDistance)/($speedOnFreeTrack/3.6)));
	array_push($allSpeeds, $speedOnFreeTrack);
	array_push($allPositionsChange, end($cumulativeSections) - $brakeDistance);
	array_push($allSectionsChange, $currentSection);

	// Time and Speed for End
	if ($nextSpeed > $speedOnFreeTrack) {
		for ($i = $speedOnFreeTrack; $i <= ($nextSpeed - 2); $i = $i + 2) {
			$tempDistance = getBrakeDistance($i, ($i + 2), $verzoegerung);
			$tempTime = getBrakeTime($i, ($i + 2), $verzoegerung);

			array_push($allTimes, (end($allTimes) + $tempTime));
			array_push($allSpeeds, ($i + 2));
			array_push($allPositionsChange, end($allPositionsChange) + $tempDistance);
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

	array_push($keyPoints, array("position_0" => ($distanceToTheNextScheduledStop - $accelerationDistance - $brakeDistance), "position_1" => end($allPositionsChange), "speed_0" => $speedOnFreeTrack, "speed_1" => end($allSpeeds), "time_0" =>  (($distanceToTheNextScheduledStop - $brakeDistance - $accelerationDistance)/$speedOnFreeTrack), "time_1" => end($allTimes)));

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

	$VMaxOverCumulativeSections = array_map('toArr', $cumulativeSections, $next_v_max);
	$VMaxOverPositionsJSon = json_encode($VMaxOverCumulativeSections);

	$fp = fopen('../json/VMaxOverCumulativeSections.json', 'w');
	fwrite($fp, $VMaxOverPositionsJSon);
	fclose($fp);

	return [$allTimes, $allSpeeds, $allPositionsChange, $cumulativeSections, $next_v_max, $keyPoints];
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

// Input: aktuelle Geschwindigkeit, maximale
function emergencyBreak (float $currentTime, int $currentSpeed, int $currentSection, float $notverzoegerung, int $nextSpeed) {

	$allTimes = array();
	$allSpeeds = array();
	$allSectionsChange = array();
	$allPositionsChange = array();

	// 0 km/h und aktuelle Zeit
	array_push($allTimes, $currentTime);
	array_push($allSpeeds, $currentSpeed);
	array_push($allPositionsChange, 0);
	array_push($allSectionsChange, $currentSection);

	for ($i = $currentSpeed; $i >= ($nextSpeed + 2); $i = $i - 2) {
		$tempDistance = getBrakeDistance($i, ($i - 2), $notverzoegerung);
		$tempTime = getBrakeTime($i, ($i - 2), $notverzoegerung);

		array_push($allTimes, (end($allTimes) + $tempTime));
		array_push($allSpeeds, ($i - 2));
		array_push($allPositionsChange, end($allPositionsChange) + $tempDistance);
	}

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

*/

function checkIfTrainIsToFastInSection(array $tempTimesChanges, array $tempSpeedChanges, array $tempPositionChanges, array $tempCumulativeSections, array $tempNext_v_max, int $position, int $nextPosition, int $currentSpeed, int $nextSpeed, array $keyPoints, array $next_lengths, array $next_v_max, array $next_sections, int $currentSection, int $targetSection, int $startPosition, int $targetPosition, float $verzoegerung, float $currentTime, float $nextTime) : array {

	$faildSections = array();
	$firstSection = null;
	$lastSection = null;

	$currentSectionKey = array_search($currentSection, $next_sections);
	$nextSectionKey = array_search($targetSection, $next_sections);

	$keyPointsReturn = array();

	$newSpeedChange = array();
	$newPositionChange = array();
	$newTimeChange = array();

	// TODO: Funktion die testet, ob es so aktuell Probleme gibt

	foreach ($tempPositionChanges as $positionIndex => $positionLoop) {
		// TODO: Was ist, wenn position == 0?
		foreach ($tempCumulativeSections as $cumPositionIndex => $cumPosition) {
			if ($positionLoop < $cumPosition && $positionLoop != 0) {
				if ($tempSpeedChanges[$positionIndex] > $tempNext_v_max[$cumPositionIndex - 1]) {
					array_push($faildSections, $cumPositionIndex - 1);
				}
				break;
			}
		}
		// TODO: Was, wenn nur eine falsch ist?
		if (sizeof($faildSections) >= 2) {
			$firstSection = $faildSections[0];
			$lastSection = end($faildSections);
		}
	}

	foreach ($keyPoints as $keyPointIndex => $keyPointValue) {
		if ($keyPointValue['speed_0'] == $keyPointValue['speed_1']) {
			$startPosition = $keyPointValue['position_0'];
			$endPosition = $keyPointValue['position_1'];
			$speed = $keyPointValue['speed_0'];

			$startSection = null;
			$endSection = null;

			foreach ($tempCumulativeSections as $cumPositionIndex => $cumPosition) {
				if ($startPosition < $cumPosition && $startSection == null) {
					$startSection = $cumPositionIndex;
				}

				if ($endPosition < $cumPosition && $endSection == null) {
					$endSection = $cumPositionIndex;
				}
			}
			for ($i = $startSection; $i <= $endSection; $i++) {
				if ($tempNext_v_max[$i] < $speed) {
					array_push($faildSections, $i);
				}
			}
		}
	}

	$splitSections = $next_sections;

	foreach ($splitSections as $splitSectionsKey => $splitSectionsValue) {
		if ($splitSectionsValue == $currentSection) {
			for ($i = array_key_first($splitSections); $i < $splitSectionsKey; $i++) {
				unset($splitSections[$i]);
			}
		}
		if ($splitSectionsValue == $targetSection) {
			for ($i = ($splitSectionsKey + 1); $i <= array_key_last($splitSections); $i++) {
				unset($splitSections[$i]);
			}
		}
	}

	$splitSections = array_keys($splitSections);

	$faildSections = array_unique($faildSections);
	sort($faildSections);

	$i=0;
	$groupedFailedSections=array();
	$previous=NULL;
	foreach($faildSections as $key => $value)
	{
		if($value>$previous+1) {
			$i++;
		}
		$groupedFailedSections[$i][]=$value;
		$previous=$value;
	}

	//var_dump($groupedFailedSections);

	$pushArray = array();

	foreach ($faildSections as $fails) {
		array_push($pushArray, $splitSections[$fails]);
		unset($splitSections[$fails]);
	}

	$i=0;
	$groupedSections=array();
	$previous=NULL;
	foreach($splitSections as $key => $value)
	{
		if($value>$previous+1) {
			$i++;
		}
		$groupedSections[$i][]=$value;
		$previous=$value;
	}




	foreach ($groupedFailedSections as $fails) {
		array_push($groupedSections, $fails);
	}



	function sortKeyPoints($a, $b) {
		return $a[0] - $b[0];
	}



	usort($groupedSections, 'sortKeyPoints');

	var_dump($groupedSections);

	// Goes through all groups
	foreach ($groupedSections as $groupKey => $groupValue) {
		$distance = 0;
		$v_max = null;
		$v_0 = null;
		$v_1 = null;
		$absolutPositionStart = $tempCumulativeSections[$groupValue[0]] - $position;
		$absolutPositionEnd = $tempCumulativeSections[end($groupValue)] - $position + $next_lengths[end($groupValue)];
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

		if (in_array($currentSectionKey, $groupValue)) {
			$distance = $distance - $position;
		}

		if (in_array($nextSectionKey, $groupValue)) {
			$distance = $distance + $targetPosition - $next_lengths[$nextSectionKey];
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
			$v_1 = $nextSpeed;
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

		if (sizeof($newTimeChange) == 0) {
			array_push($newTimeChange, $currentTime);
		} else {
			array_push($newTimeChange, end($newTimeChange));
		}

		$increaseKeyPoint = array();
		$decreaseKeyPoint = array();

		if ($v_0 < $v_max) {
			$increaseKeyPoint = ['position_0' => end($newPositionChange), 'speed_0' => $v_0, 'time_0' => end($newTimeChange)];
			if ($v_0 % 2 != 0) {
				$tempDistance = getBrakeDistance($v_0, ($v_0 + 1), $verzoegerung);
				$tempTime = getBrakeTime($v_0, ($v_0 + 1), $verzoegerung);
				array_push($newTimeChange, (end($newTimeChange) + $tempTime));
				array_push($newSpeedChange, ($v_0 + 1));
				array_push($newPositionChange, end($newPositionChange) + $tempDistance);
				$v_0 = $v_0 + 1;
			}
			for ($i = $v_0; $i <= ($v_max - 2); $i = $i + 2) {
				$tempDistance = getBrakeDistance($i, ($i + 2), $verzoegerung);
				$tempTime = getBrakeTime($i, ($i + 2), $verzoegerung);
				array_push($newTimeChange, (end($newTimeChange) + $tempTime));
				array_push($newSpeedChange, ($i + 2));
				array_push($newPositionChange, end($newPositionChange) + $tempDistance);
			}
			$increaseKeyPoint['position_1'] =  end($newPositionChange);
			$increaseKeyPoint['speed_1'] =  $v_max;
			$increaseKeyPoint['time_1'] =   end($newTimeChange);
		} elseif ($v_0 > $v_max) {
			$increaseKeyPoint = ['position_0' => end($newPositionChange), 'speed_0' => $v_0, 'time_0' => end($newTimeChange)];
			if ($v_0 % 2 != 0) {
				$tempDistance = getBrakeDistance($v_0, ($v_0 - 1), $verzoegerung);
				$tempTime = getBrakeTime($v_0, ($v_0 - 1), $verzoegerung);
				array_push($newTimeChange, (end($newTimeChange) + $tempTime));
				array_push($newSpeedChange, ($v_0 - 1));
				array_push($newPositionChange, end($newPositionChange) + $tempDistance);
				$v_0 = $v_0 - 1;
			}
			for ($i = $v_0; $i >= ($v_max + 2); $i = $i - 2) {
				$tempDistance = getBrakeDistance($i, ($i - 2), $verzoegerung);
				$tempTime = getBrakeTime($i, ($i - 2), $verzoegerung);
				array_push($newTimeChange, (end($newTimeChange) + $tempTime));
				array_push($newSpeedChange, ($i - 2));
				array_push($newPositionChange, end($newPositionChange) + $tempDistance);
			}
			$increaseKeyPoint['position_1'] =  end($newPositionChange);
			$increaseKeyPoint['speed_1'] =  $v_max;
			$increaseKeyPoint['time_1'] =   end($newTimeChange);
		}

		array_push($newSpeedChange, $v_max);
		array_push($newPositionChange, $absolutPositionEnd - getBrakeDistance($v_max, $v_1, $verzoegerung));
		array_push($newTimeChange, end($newTimeChange) + (($absolutPositionEnd - getBrakeDistance($v_0, $v_max, $verzoegerung) - getBrakeDistance($v_max, $v_1, $verzoegerung))/$v_max));

		if ($v_1 > $v_max) {
			$decreaseKeyPoint = ['position_0' => end($newPositionChange), 'speed_0' => $v_max, 'time_0' => end($newTimeChange)];
			for ($i = $v_max; $i <= ($v_1 - 2); $i = $i + 2) {
				$tempDistance = getBrakeDistance($i, ($i + 2), $verzoegerung);
				$tempTime = getBrakeTime($i, ($i + 2), $verzoegerung);
				array_push($newTimeChange, (end($newTimeChange) + $tempTime));
				array_push($newSpeedChange, ($i + 2));
				array_push($newPositionChange, end($newPositionChange) + $tempDistance);
			}
			$decreaseKeyPoint['position_1'] =  end($newPositionChange);
			$decreaseKeyPoint['speed_1'] =  $v_1;
			$decreaseKeyPoint['time_1'] =   end($newTimeChange);
		} if ($v_1 < $v_max) {
			$decreaseKeyPoint = ['position_0' => end($newPositionChange), 'speed_0' => $v_max, 'time_0' => end($newTimeChange)];
			for ($i = $v_max; $i >= ($v_1 + 2); $i = $i - 2) {
				$tempDistance = getBrakeDistance($i, ($i - 2), $verzoegerung);
				$tempTime = getBrakeTime($i, ($i - 2), $verzoegerung);
				array_push($newTimeChange, (end($newTimeChange) + $tempTime));
				array_push($newSpeedChange, ($i - 2));
				array_push($newPositionChange, end($newPositionChange) + $tempDistance);
			}
			$decreaseKeyPoint['position_1'] =  end($newPositionChange);
			$decreaseKeyPoint['speed_1'] =  $v_1;
			$decreaseKeyPoint['time_1'] =   end($newTimeChange);
		}

		if (sizeof($increaseKeyPoint) != 0) {
			array_push($keyPointsReturn, $increaseKeyPoint);
		}
		if (sizeof($decreaseKeyPoint) != 0) {
			array_push($keyPointsReturn, $decreaseKeyPoint);
		}

		$speedOverPosition = array_map('toArr', $newPositionChange, $newSpeedChange);
		$speedOverPosition = json_encode($speedOverPosition);

		$fp = fopen('../json/speedOverPosition.json', 'w');
		fwrite($fp, $speedOverPosition);
		fclose($fp);
	}

	foreach ($faildSections as $faildSectionsKey => $faildSectionsValue) {
		$startSpeed = null;
		$endSpeed = null;
		$startPosition = null;
		$endPosition = null;
		$startTime = null;
		$endTime = null;
	}
	return $keyPointsReturn;
}

