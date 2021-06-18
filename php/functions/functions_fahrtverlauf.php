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

$minTimeForSpeed = 10;


$keyPoints = array();

function setTargetSpeed (array $allTrains, int $key, int $nextSpeed, int $nextSection, int $nextPosition, int $nextTime) {

	// TODO: Check for timetable, speed sign or maximum speed
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

/*

function createKeyPoint (float $position_0, float $position_1, int $speed_0, int $speed_1, int $maxSpeed = NULL) : array {
	return array("position_0" => $position_0, "position_1" => $position_1, "speed_0" => $speed_0, "speed_1" => $speed_1, "max_speed" => $maxSpeed);
}

 */

function updateNextSpeed (array $train, float $startTime, float $endTime, int $currentSectionPara, int $currentSpeedPara, int $currentPositionPara, int $targetSectionPara, int $targetSpeedPara, int $targetPositionPara, bool $reachedBetriebsstelle, int $indexReachedBetriebsstelle, bool $wendet) {

	global $next_sections;
	global $next_lengths;
	global $next_v_max;

	global $allTimes;

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
	global $minTimeForSpeed;

	global $allTrains;

	$emptyArray = array();
	$keyPoints = $emptyArray;
	$cumulativeSectionLengthStart = $emptyArray;
	$cumulativeSectionLengthEnd = $emptyArray;

	$next_sections = $train["next_sections"];
	$next_lengths = $train["next_lenghts"];
	$next_v_max = $train["next_v_max"];

	//var_dump($next_v_max, $next_sections);

	$verzoegerung = $train["verzoegerung"];
	$notverzoegerung = $train["notverzoegerung"];
	$train_v_max = $train["vmax"];
	//$currentSection = $train["current_infra_section"];
	//$currentPosition = $train["current_position"];
	//$currentSpeed = $train["speed"];

	$currentSection = $currentSectionPara;
	$currentPosition = $currentPositionPara;
	$currentSpeed = $currentSpeedPara;

	$targetSpeed = $targetSpeedPara;
	$targetSection = $targetSectionPara;
	$targetPosition = $targetPositionPara;
	//$targetTime = $train["next_timetable_change_time"];
	$targetTime = $endTime;

	$indexCurrentSection = null;
	$indexTargetSection = null;

	$timeToNextStop = null;
	$maxTimeToNextStop = $targetTime - $startTime;

	$maxSpeedNextSections = 120;











	//var_dump($currentSection, $targetSection, $currentPosition, $targetPosition);


	if ($targetSection == $currentSection && $targetPosition == $currentPosition) {
		if ($indexReachedBetriebsstelle == 99999999) {
			$indexReachedBetriebsstelle = -1;
		}
		$adress = $train["adresse"];
		$return = array(array("live_position" => $targetPosition, "live_speed" => $targetSpeed, "live_time" => $endTime, "live_relative_position" => $targetPosition, "live_section" => $targetSection, "live_is_speed_change" => false, "live_target_reached" => $reachedBetriebsstelle, "wendet" => $wendet, "id" => $train["id"], "betriebsstelle_index" => $indexReachedBetriebsstelle));
		$allTimes[$adress] = array_merge($allTimes[$adress], $return);
		return 0;
	}



	if ($train_v_max != null) {
		foreach ($next_sections as $sectionKey => $sectionValue) {
			if ($next_v_max[$sectionKey] > $train_v_max) {
				$next_v_max[$sectionKey] = $train_v_max;
			}
		}
	}

	foreach ($next_sections as $sectionKey => $sectionValue) {
		if ($sectionValue == $currentSection) {
			$indexCurrentSection = $sectionKey;
		}
		if ($sectionValue == $targetSection) {
			$indexTargetSection = $sectionKey;
		}
	}

	$cumLength = array();
	$sum = 0;

	foreach ($next_lengths as $index => $value) {
		$sum += $value;
		$cumLength[$index] = $sum;
	}


	for ($i = $indexCurrentSection; $i <= $indexTargetSection; $i++) {
		if ($indexCurrentSection == $indexTargetSection) {
			$cumulativeSectionLengthStart[$i] = 0;
			$cumulativeSectionLengthEnd[$i] = $targetPosition - $currentPosition;
		} else {
			if ($i == $indexCurrentSection) {
				$cumulativeSectionLengthStart[$i] = 0;
				$cumulativeSectionLengthEnd[$i] = $cumLength[$i] - $currentPosition;
			} else if ($i == $indexTargetSection) {
				$cumulativeSectionLengthStart[$i] = $cumLength[$i - 1] - $currentPosition;
				$cumulativeSectionLengthEnd[$i] = $cumLength[$i - 1] + $targetPosition - $currentPosition;
			} else {
				$cumulativeSectionLengthStart[$i] = $cumLength[$i - 1] - $currentPosition;
				$cumulativeSectionLengthEnd[$i] = $cumLength[$i] - $currentPosition;
			}
		}
	}


	/*
	// TODO: verbessern, was ist, wenn start und end section die selbe sind?
	$cumulativeSectionLengthSum = - $currentPosition;
	$cumulativeSectionLengthStart[$indexCurrentSection] = 0;
	$cumulativeSectionLengthStart[$indexCurrentSection + 1] = $next_lengths[$indexCurrentSection] - $currentPosition;

	for ($i = ($indexCurrentSection + 2); $i <= $indexTargetSection; $i++) {
		array_push($cumulativeSectionLengthStart, (end($cumulativeSectionLengthStart) + $next_lengths[$i - 1]));
	}

	for ($i = $indexCurrentSection; $i <= $indexTargetSection; $i++) {
		$cumulativeSectionLengthSum = $cumulativeSectionLengthSum + $next_lengths[$i];
		//$distanceToNextStop = $distanceToNextStop + $next_lengths[$i];
		if ($i == $indexTargetSection && $i = $indexTargetSection) {
			$cumulativeSectionLengthEnd[$i] = $next_sections[$i] - $currentPosition;
		} else if ($i != $indexTargetSection) {
			$cumulativeSectionLengthEnd[$i] = $cumulativeSectionLengthSum;
		} else {
			$cumulativeSectionLengthEnd[$i] = end($cumulativeSectionLengthEnd) + $targetPosition;
		}
	}
	*/

	//$distanceToNextStop = $distanceToNextStop - $currentPosition - $next_lengths[$indexTargetSection] + $targetPosition;

	$distanceToNextStop = $cumulativeSectionLengthEnd[$indexTargetSection];




	// Emergency Brake
	if (getBrakeDistance($currentSpeed, $targetSpeed, $verzoegerung)> $distanceToNextStop && $currentSpeed != 0) {
		echo "Der Zug mit der Adresse: ", $train["adresse"], " leitet jetzt eine Notbremsung ein.\n";
		$returnArray = array();
		$time = $startTime;
		if (getBrakeDistance($currentSpeed, $targetSpeed, $notverzoegerung) <= $distanceToNextStop) {
			for ($i = $currentSpeed; $i >= 0; $i = $i - 2) {
				array_push($returnArray, array("live_position" => 0, "live_speed" => $i, "live_time" => $time, "live_relative_position" => 0, "live_section" => $currentSection, "live_is_speed_change" => true, "live_target_reached" => false, "id" => $train["id"], "wendet" => false, "betriebsstelle_index" => null));
				$time =  $time + getBrakeTime($i, $i - 1, $notverzoegerung);
			}
		} else {
			$targetSpeedNotbremsung =  getTargetBrakeSpeedWithDistanceAndStartSpeed($distanceToNextStop, $notverzoegerung, $currentSpeed);
			$speedBeforeStop = intval($targetSpeedNotbremsung / 2) * 2;
			if ($speedBeforeStop >= 10) {
				for ($i = $currentSpeed; $i >= 10; $i = $i - 2) {
					array_push($returnArray, array("live_position" => 0, "live_speed" => $i, "live_time" => $time, "live_relative_position" => 0, "live_section" => $currentSection, "live_is_speed_change" => true, "live_target_reached" => false, "id" => $train["id"], "wendet" => false, "betriebsstelle_index" => null));
					$time =  $time + getBrakeTime($i, $i - 1, $notverzoegerung);
				}
				array_push($returnArray, array("live_position" => 0, "live_speed" => 0, "live_time" => $time, "live_relative_position" => 0, "live_section" => $currentSection, "live_is_speed_change" => true, "live_target_reached" => false, "id" => $train["id"], "wendet" => false, "betriebsstelle_index" => null));
			} else {
				array_push($returnArray, array("live_position" => 0, "live_speed" => $currentSpeed, "live_time" => $time, "live_relative_position" => 0, "live_section" => $currentSection, "live_is_speed_change" => true, "live_target_reached" => false, "id" => $train["id"], "wendet" => false, "betriebsstelle_index" => null));
				$time =  $time + getBrakeTime($currentSpeed, $currentSpeed - 1, $notverzoegerung);
				array_push($returnArray, array("live_position" => 0, "live_speed" => 0, "live_time" => $time, "live_relative_position" => 0, "live_section" => $currentSection, "live_is_speed_change" => true, "live_target_reached" => false, "id" => $train["id"], "wendet" => false, "betriebsstelle_index" => null));
			}
		}

		$allTimes[$train["adresse"]] = $returnArray;
		$allTrains[$train["id"]]["can_drive"] = false;




		return 0;

	}

	//maximale geschwindigkeit zwischen zwei punkten...
	// TODO: Was ist, wenn die aktuelle Geschwindigkeit nicht ohne Rest durch 10 teilbar ist?
	$v_maxFirstIteration = null;
	/*
	for ($i = $currentSpeed; $i <= 120; $i = $i + 10) {
		if ((getBrakeDistance($currentSpeed, $i, $verzoegerung) + getBrakeDistance($i, $targetSpeed, $verzoegerung)) > $distanceToNextStop) {
			$v_maxFirstIteration = $i - 10;
			break;
		}
	}
	*/

	$v_maxFirstIteration = getVMaxBetweenTwoPoints($distanceToNextStop, $currentSpeed, $targetSpeed);

	if ($v_maxFirstIteration == 0) {
		// TODO:
		echo "Bis zum nächsten Halt müsste Der Zug mit der Adresse ", $train["adresse"], " langsamer als 10 km/h fahren.";
		return false;
	}

	for ($i = $indexCurrentSection; $i <= $indexTargetSection; $i++) {
		if ($next_v_max[$i] < $maxSpeedNextSections) {
			$maxSpeedNextSections = $next_v_max[$i];
		}
	}


	if ($maxSpeedNextSections < $v_maxFirstIteration) {
		$v_maxFirstIteration = $maxSpeedNextSections;
	}


	//var_dump($maxSpeedNextSections, $next_v_max, $indexCurrentSection, $indexTargetSection);

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
			$tempKeyPoints = $keyPoints;
			$trainChange = createTrainChanges($startTime);
			$trainPositionChange = $trainChange[0];
			$trainSpeedChange = $trainChange[1];
			$trainTimeChange = $trainChange[2];
			$keyPoints = recalculateKeyPoints($tempKeyPoints);
			$trainChange = createTrainChanges($startTime);
			$trainPositionChange = $trainChange[0];
			$trainSpeedChange = $trainChange[1];
			$trainTimeChange = $trainChange[2];
			checkTrainChangeOverlap();
			safeTrainChangeToJSONFile();
		}
	}


	//Adding time to first KeyPoint
	$keyPoints[0]["time_0"] = $startTime;
	$keyPoints = deleteDoubledKeyPoints($keyPoints);
	$trainTimeChange = calculateTrainTimeChange();

	$keyPoints = calculateTimeFromKeyPoints();





	for($i = 0; $i < (sizeof($keyPoints) - 1); $i++) {
		if (($keyPoints[$i + 1]["time_0"] - $keyPoints[$i]["time_1"]) < $minTimeForSpeed) {
			//TODO: zu kurz auf einer Geschwindigkeit
		}
	}

	// TODO: Was ist, wenn es nur einen KeyPoint gibt?



	if (sizeof($keyPoints) > 1) {
		$timeToNextStop = end($keyPoints)["time_1"] - $keyPoints[0]["time_0"];
	} else {
		$timeToNextStop = $keyPoints[0]["time_1"] - $keyPoints[0]["time_0"];
	}


	// TODO: Evtl. $timeToNextStop über $trainChangeTime errechnen und nicht über eine eigene Funktion





	if ($timeToNextStop > $maxTimeToNextStop) {
		// Do nothing, schneller kann der Zug eh nicht ankommen

		//$timeToNextStop = $keyPoints[array_key_last($keyPoints)]["time_1"];
		$returnTrainChanges = createTrainChanges($startTime);
		$trainPositionChange = $returnTrainChanges[0];
		$trainSpeedChange = $returnTrainChanges[1];
		$trainTimeChange = $returnTrainChanges[2];
		$trainRelativePosition = $returnTrainChanges[3];
		$trainSection = $returnTrainChanges[4];
		$trainIsSpeedChange = $returnTrainChanges[5];

		$trainTargetReached = array();
		$trainBetriebsstelleIndex = array();
		$trainWendet = array();



		foreach ($trainPositionChange as $key => $value) {
			if (array_key_last($trainPositionChange) != $key) {
				$trainBetriebsstelleIndex[$key] = null;
			} else {
				if ($indexReachedBetriebsstelle == 99999999) {
					$trainBetriebsstelleIndex[$key] = -1;
				} else {
					$trainBetriebsstelleIndex[$key] = $indexReachedBetriebsstelle;
				}
			}
		}

		if ($reachedBetriebsstelle) {
			foreach ($trainPositionChange as $key => $value) {
				if (array_key_last($trainPositionChange) != $key) {
					$trainTargetReached[$key] = false;
					$trainBetriebsstelleIndex[$key] = $indexReachedBetriebsstelle;
				} else {
					$trainTargetReached[$key] = true;
					$trainBetriebsstelleIndex[$key] = $indexReachedBetriebsstelle;
				}
			}
		} else {
			foreach ($trainPositionChange as $key => $value) {
				$trainTargetReached[$key] = false;
				$trainBetriebsstelleIndex[$key] = false;
			}
		}

		if ($wendet) {
			foreach ($trainPositionChange as $key => $value) {
				if (array_key_last($trainPositionChange) != $key) {
					$trainWendet[$key] = false;
				} else {
					$trainWendet[$key] = true;
				}
			}
		} else {
			foreach ($trainPositionChange as $key => $value) {
				$trainWendet[$key] = false;
			}
		}

		//betriebsstelle_index

		safeTrainChangeToJSONFile();

		echo "Der Zug mit der Adresse ", $train["adresse"], " wird mit einer Verspätung von ", number_format((end($trainTimeChange) - $trainTimeChange[0]) - ($endTime - $startTime), 2), " Sekunden im nächsten planmäßigen Halt ankommen.\n";
	} else {
		echo "Aktuell benötigt der Zug mit der Adresse ", $train["adresse"], " ", (end($trainTimeChange) - $trainTimeChange[0]), " Sekunden, obwohl er ", ($endTime - $startTime), " Sekunden zur Verfügung hat\n";
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

				$localKeyPoints = calculateTimeFromKeyPoints($localKeyPoints);
				$newCalculatedTime = $localKeyPoints[array_key_last($localKeyPoints)]["time_1"];
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

				if (($newCalculatedTime - $startTime) > $maxTimeToNextStop) {
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

		$keyPoints = calculateTimeFromKeyPoints();
		$newCalculatedTime = $keyPoints[array_key_last($keyPoints)]["time_1"];
		speedFineTuning(($maxTimeToNextStop - ($newCalculatedTime - $startTime)), $possibleSpeedRange["first_key_point_index"]);
		// TODO: $currentTime global verfügbar machen


		$keyPoints = calculateTimeFromKeyPoints();
		$timeToNextStop = $keyPoints[array_key_last($keyPoints)]["time_1"];
		$returnTrainChanges = createTrainChanges($startTime);
		$trainPositionChange = $returnTrainChanges[0];
		$trainSpeedChange = $returnTrainChanges[1];
		$trainTimeChange = $returnTrainChanges[2];
		$trainRelativePosition = $returnTrainChanges[3];
		$trainSection = $returnTrainChanges[4];
		$trainIsSpeedChange = $returnTrainChanges[5];
		safeTrainChangeToJSONFile();

		$trainTargetReached = array();
		$trainBetriebsstelleIndex = array();
		$trainWendet = array();

		if ($wendet) {
			foreach ($trainPositionChange as $key => $value) {
				if (array_key_last($trainPositionChange) != $key) {
					$trainWendet[$key] = false;
				} else {
					$trainWendet[$key] = true;
				}
			}
		} else {
			foreach ($trainPositionChange as $key => $value) {
				$trainWendet[$key] = false;
			}
		}

		foreach ($trainPositionChange as $key => $value) {
			if (array_key_last($trainPositionChange) != $key) {
				$trainBetriebsstelleIndex[$key] = null;
			} else {
				if ($indexReachedBetriebsstelle == 99999999) {
					$trainBetriebsstelleIndex[$key] = -1;
				} else {
					$trainBetriebsstelleIndex[$key] = $indexReachedBetriebsstelle;
				}
			}
		}

		if ($reachedBetriebsstelle) {
			foreach ($trainPositionChange as $key => $value) {
				if (array_key_last($trainPositionChange) != $key) {
					$trainTargetReached[$key] = false;
					$trainBetriebsstelleIndex[$key] = $indexReachedBetriebsstelle;
				} else {
					$trainTargetReached[$key] = true;
					$trainBetriebsstelleIndex[$key] = $indexReachedBetriebsstelle;
				}
			}
		} else {
			foreach ($trainPositionChange as $key => $value) {
				$trainTargetReached[$key] = false;
				$trainBetriebsstelleIndex[$key] = false;
			}
		}

		echo "\nDurch die Anpassung der Geschwindigkeit benötigt der Zug mit der Adresse ", $train["adresse"], " jetzt ", $timeToNextStop - $startTime, " Sekunden,\nda er ", $maxTimeToNextStop, " Sekunden zur Verfügung hat\n";
	}

	$returnArray = array();
	$adress = $train["adresse"];

	$trainID = array();
	$id = $train["id"];

	foreach ($trainPositionChange as $key => $value) {
		$trainID[$key] = $id;
	}

	//var_dump($trainRelativePosition);

	/*
	var_dump(sizeof($trainPositionChange));
	var_dump(sizeof($trainSpeedChange));
	var_dump(sizeof($trainTimeChange));
	var_dump(sizeof($trainRelativePosition));
	var_dump(sizeof($trainSection));
	var_dump(sizeof($trainIsSpeedChange));
	var_dump(sizeof($trainTargetReached));
	var_dump(sizeof($trainID));
	var_dump(sizeof($trainWendet));
	var_dump(sizeof($trainBetriebsstelleIndex));
	*/

	foreach ($trainPositionChange as $trainPositionChangeIndex => $trainPositionChangeValue) {
		array_push($returnArray, array("live_position" => $trainPositionChangeValue, "live_speed" => $trainSpeedChange[$trainPositionChangeIndex], "live_time" => $trainTimeChange[$trainPositionChangeIndex], "live_relative_position" => $trainRelativePosition[$trainPositionChangeIndex], "live_section" => $trainSection[$trainPositionChangeIndex], "live_is_speed_change" => $trainIsSpeedChange[$trainPositionChangeIndex], "live_target_reached" => $trainTargetReached[$trainPositionChangeIndex], "id" => $trainID[$trainPositionChangeIndex], "wendet" => $trainWendet[$trainPositionChangeIndex], "betriebsstelle_index" => $trainBetriebsstelleIndex[$trainPositionChangeIndex]));
	}
	$allTimes[$adress] = array_merge($allTimes[$adress], $returnArray);
	//$allTimes[$adress] = $returnArray;

	return (end($trainTimeChange) - $trainTimeChange[0]) - ($endTime - $startTime);

}

function checkBetweenTwoKeyPoints(array $temKeyPoints, int $index) {

	global $trainPositionChange;
	global $trainSpeedChange;
	global $cumulativeSectionLengthStart;
	global $cumulativeSectionLengthEnd;
	global $next_v_max;
	global $verzoegerung;


	$failedSections = array();
	$groupedFailedSections = array();
	$returnKeyPoints = array();
	$failedPositions = array();
	$failedSpeeds = array();

	foreach ($trainPositionChange as $trainPositionChangeKey => $trainPositionChangeValue) {
		if ($trainPositionChangeValue >= $temKeyPoints[$index]["position_0"] && $trainPositionChangeValue <= $temKeyPoints[$index + 1]["position_1"]) {
			foreach ($cumulativeSectionLengthStart as $cumulativeSectionLengthStartKey => $cumulativeSectionLengthStartValue) {
				if ($trainPositionChangeValue < $cumulativeSectionLengthStartValue) {
					// jetzt den davor überprüfen
					if ($trainSpeedChange[$trainPositionChangeKey] > $next_v_max[$cumulativeSectionLengthStartKey - 1]) {
						array_push($failedSections, ($cumulativeSectionLengthStartKey - 1));
						//array_push($failedPositions, $trainPositionChange[$trainPositionChangeKey]);
						$failedPositions[$trainPositionChangeKey] = $trainPositionChange[$trainPositionChangeKey];
						array_push($failedSpeeds, $trainSpeedChange[$trainPositionChangeKey]);
					}
					break;
				}
			}
		}
	}

	//var_dump($failedPositions);
	//var_dump($trainPositionChange);
	//sleep(5);

	$failedSections = array_unique($failedSections);

	if (sizeof($failedSections) == 0) {
		return array($temKeyPoints[$index], $temKeyPoints[$index + 1]);
	} else {
		$returnKeyPoints[0]["speed_0"] = $temKeyPoints[$index]["speed_0"];
		$returnKeyPoints[0]["position_0"] = $temKeyPoints[$index]["position_0"];
	}

	$previous = NULL;
	foreach($failedSections as $key => $value) {
		if($value > $previous + 1) {
			$index++;
		}
		$groupedFailedSections[$index][] = $value;
		$previous = $value;
	}

	foreach ($groupedFailedSections as $groupSectionsIndex => $groupSectionsValue) {
		$firstFailedPositionIndex = null;
		$lastFailedPositionIndex = null;
		$firstFailedPosition = null;
		$lastFailedPosition = null;
		$lastElement = array_key_last($returnKeyPoints);
		$failedSection = null;




		if (sizeof($groupSectionsValue) == 1) {
			$failedSection = $groupSectionsValue[0];
		} else {
			$slowestSpeed = 200;
			for ($i = 0; $i <= (sizeof($groupSectionsValue) - 1); $i++) {
				if ($next_v_max[$groupSectionsValue[$i]] < $slowestSpeed) {
					$slowestSpeed = $next_v_max[$groupSectionsValue[$i]];
					$failedSection = $groupSectionsValue[$i];
				}
			}
		}

		$failedSectionStart = $cumulativeSectionLengthStart[$failedSection];
		$failedSectionEnd = $cumulativeSectionLengthEnd[$failedSection];
		$vMaxInFailedSection = $next_v_max[$failedSection];


		foreach ($failedPositions as $failPositionIndex => $failPositionValue) {
			if ($failPositionValue > $failedSectionStart && $failPositionValue < $failedSectionEnd) {
				if ($firstFailedPositionIndex == null) {
					$firstFailedPositionIndex = $failPositionIndex;
				}
				$lastFailedPositionIndex = $failPositionIndex;
			}
		}



		// TODO: Was ist, wenn der Zug in der Mitte eines Abschnitts anhalten muss?
		if ($firstFailedPositionIndex != 0) {
			//var_dump($firstFailedPositionIndex);
			if ($trainPositionChange[$firstFailedPositionIndex - 1] < $failedSectionStart) {
				$firstFailedPosition = $failedSectionStart;
			} else {
				$firstFailedPosition = $trainPositionChange[$firstFailedPositionIndex - 1];
			}
		} else {
			$firstFailedPosition = $failedSectionStart;
		}

		if ($lastFailedPositionIndex != array_key_last($trainPositionChange)) {
			if ($trainPositionChange[$lastFailedPositionIndex + 1] > $failedSectionEnd) {
				$lastFailedPosition = $failedSectionEnd;
			} else {
				$lastFailedPosition = $trainPositionChange[$lastFailedPositionIndex + 1];
			}
		} else {
			$lastFailedPosition = $failedSectionEnd;
		}






		/*
		if ($failedPositions[0] > $cumulativeSectionLengthStart[$failedSection]) {
			$returnKeyPoints[$lastElement + 1]["position_1"] = $failedPositions[0];
		} else {
			$returnKeyPoints[$lastElement + 1]["position_1"] = $cumulativeSectionLengthStart[$failedSection];
		}
		*/
		$returnKeyPoints[$lastElement + 1]["position_1"] = $firstFailedPosition;
		$returnKeyPoints[$lastElement + 1]["speed_1"] = $next_v_max[$failedSection];
		$returnKeyPoints[$lastElement + 2]["position_0"] = $lastFailedPosition;
		/*
		if (end($failedPositions) < $cumulativeSectionLengthEnd[$failedSection]) {
			$returnKeyPoints[$lastElement + 2]["position_0"] = end($failedPositions);
		} else {
			$returnKeyPoints[$lastElement + 2]["position_0"] = $cumulativeSectionLengthEnd[$failedSection];
		}
		*/
		$returnKeyPoints[$lastElement + 2]["speed_0"] = $next_v_max[$failedSection];
	}






	$returnKeyPoints[array_key_last($returnKeyPoints) + 1]["position_1"] = $temKeyPoints[$index]["position_1"];
	$returnKeyPoints[array_key_last($returnKeyPoints)]["speed_1"] = $temKeyPoints[$index]["speed_1"]; //
	$numberOfPairs = (sizeof($returnKeyPoints) - ((sizeof($returnKeyPoints)) % 2)) / 2;
	for($j = 0; $j < $numberOfPairs; $j++) {
		$i = $j * 2;
		$distance = $returnKeyPoints[$i + 1]["position_1"] - $returnKeyPoints[$i]["position_0"];
		$vMax = getVMaxBetweenTwoPoints($distance, $returnKeyPoints[$i]["speed_0"], $returnKeyPoints[$i + 1]["speed_1"]);
		if ($vMax == -10) {
			$returnKeyPoints[$i]["position_0"] = $returnKeyPoints[$i + 1]["position_1"] - (getBrakeDistance($returnKeyPoints[$i]["speed_0"], $returnKeyPoints[$i + 1]["speed_1"], $verzoegerung));
			$distance = $returnKeyPoints[$i + 1]["position_1"] - $returnKeyPoints[$i]["position_0"];
			$vMax = getVMaxBetweenTwoPoints($distance, $returnKeyPoints[$i]["speed_0"], $returnKeyPoints[$i + 1]["speed_1"]);
			//var_dump($distance, $returnKeyPoints[$i]["speed_0"], $returnKeyPoints[$i + 1]["speed_1"]);
			//var_dump(getVMaxBetweenTwoPoints($distance, $returnKeyPoints[$i]["speed_0"], $returnKeyPoints[$i + 1]["speed_1"]));
		}




		$returnKeyPoints[$i]["speed_1"] = $vMax; //TODO
		$returnKeyPoints[$i]["position_1"] = $returnKeyPoints[$i]["position_0"] + getBrakeDistance($returnKeyPoints[$i]["speed_0"], $vMax, $verzoegerung);
		$returnKeyPoints[$i + 1]["speed_0"] = $vMax;
		$returnKeyPoints[$i + 1]["position_0"] = $returnKeyPoints[$i + 1]["position_1"] - getBrakeDistance($vMax, $returnKeyPoints[$i + 1]["speed_1"], $verzoegerung);
	}



	return $returnKeyPoints;
}

function recalculateKeyPoints(array $tempKeyPoints) {

	$returnKeyPoints = array();
	$numberOfPairs = (sizeof($tempKeyPoints) - ((sizeof($tempKeyPoints)) % 2)) / 2;

	for($j = 0; $j < $numberOfPairs; $j++) {
		$i = $j * 2;
		$return = checkBetweenTwoKeyPoints($tempKeyPoints, $i);
		foreach ($return as $keyPoint) {
			array_push($returnKeyPoints, $keyPoint);
		}
	}

	return $returnKeyPoints;
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

	//var_dump($keyPoints);


	$availableDistance = $keyPoints[$index + 1]["position_0"] - $keyPoints[$index]["position_1"];
	$timeBetweenKeyPoints = $keyPoints[$index + 1]["time_0"] - $keyPoints[$index]["time_1"];
	$availableTime = $timeBetweenKeyPoints + $timeDiff;

	//var_dump($keyPoints);





	if ($keyPoints[$index]["speed_0"] == 0 && $keyPoints[$index + 1]["speed_1"] == 0) {
		return;
	}
	if ($keyPoints[$index + 1]["speed_1"] != 0) {
		$lengthDifference = calculateDistanceforSpeedFineTuning($keyPoints[$index + 1]["speed_0"], $keyPoints[$index + 1]["speed_1"], $availableDistance, $availableTime);
		$keyPoints[$index + 1]["position_0"] = $keyPoints[$index + 1]["position_0"] - $lengthDifference;
		$keyPoints[$index + 1]["position_1"] = $keyPoints[$index + 1]["position_1"] - $lengthDifference;
	} else {
		$lengthDifference = calculateDistanceforSpeedFineTuning($keyPoints[$index]["speed_0"], $keyPoints[$index]["speed_1"], $availableDistance, $availableTime);
		$keyPoints[$index]["position_0"] = $keyPoints[$index]["position_0"] - $lengthDifference;
		$keyPoints[$index]["position_1"] = $keyPoints[$index]["position_1"] - $lengthDifference;
	}


}

function calculateDistanceforSpeedFineTuning(int $v_0, int $v_1, float $distance, float $time) : float {
	return $distance - (($distance - $time * $v_1 / 3.6)/($v_0 / 3.6 - $v_1 / 3.6)) * ($v_0 / 3.6);
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
	global $cumulativeSectionLengthStart;
	global $cumulativeSectionLengthEnd;
	global $next_sections;
	global $indexCurrentSection;
	global $indexTargetSection;
	global $currentPosition;
	global $targetPosition;


	$returnTrainSpeedChange = array();
	$returnTrainTimeChange = array();
	$returnTrainPositionChange = array();
	$returnTrainRelativePosition = array();
	$returnTrainSection = array();
	$returnIsSpeedChange = array();

	for ($i = 0; $i < (sizeof($keyPoints) - 1); $i++) {

		array_push($returnTrainTimeChange, $currentTime);
		array_push($returnTrainSpeedChange, $keyPoints[$i]["speed_0"]);
		array_push($returnTrainPositionChange, $keyPoints[$i]["position_0"]);
		array_push($returnIsSpeedChange, true);

		if ($keyPoints[$i]["speed_0"] < $keyPoints[$i]["speed_1"]) {

			for ($j = ($keyPoints[$i]["speed_0"] + 2); $j <= $keyPoints[$i]["speed_1"]; $j = $j + 2) {
				array_push($returnTrainPositionChange, (end($returnTrainPositionChange) + getBrakeDistance(($j - 2), $j, $verzoegerung)));
				array_push($returnTrainSpeedChange, $j);
				array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + (getBrakeTime(($j - 2), $j, $verzoegerung))));
				array_push($returnIsSpeedChange, true);
			}


		} else {

			// TODO: Möglichst spät!
			array_push($returnTrainPositionChange, $keyPoints[$i]["position_1"] - getBrakeDistance($keyPoints[$i]["speed_0"],$keyPoints[$i]["speed_1"],$verzoegerung));
			array_push($returnTrainSpeedChange, $keyPoints[$i]["speed_0"]);
			array_push($returnTrainTimeChange, (end($returnTrainPositionChange) + distanceWithSpeedToTime($keyPoints[$i]["speed_0"], ($keyPoints[$i]["position_1"] - $keyPoints[$i]["position_0"] - getBrakeDistance($keyPoints[$i]["speed_0"], $keyPoints[$i]["speed_1"], $verzoegerung)))));
			array_push($returnIsSpeedChange, true);
			for ($j = ($keyPoints[$i]["speed_0"] - 2); $j >= $keyPoints[$i]["speed_1"]; $j = $j - 2) {
				array_push($returnTrainPositionChange, (end($returnTrainPositionChange) + getBrakeDistance(($j + 2), $j, $verzoegerung)));
				array_push($returnTrainSpeedChange, $j);
				array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + (getBrakeTime(($j + 2), $j, $verzoegerung))));
				array_push($returnIsSpeedChange, true);
			}
		}

		$startPosition = $keyPoints[$i]["position_1"];
		$endPosition =  $keyPoints[$i + 1]["position_0"];
		$distanceToNextKeyPoint = $endPosition - $startPosition;
		$speedToNextKeyPoint = $keyPoints[$i]["speed_1"];
		$timeUpdateInterval = 1; // TODO: Define global
		$distanceForOneTimeInterval = ($speedToNextKeyPoint / 3.6) * $timeUpdateInterval;

		for ($position = $startPosition + $distanceForOneTimeInterval; $position < $endPosition; $position = $position + $distanceForOneTimeInterval) {
			$relativePosition = $position - $startPosition;
			array_push($returnTrainPositionChange, $position);
			array_push($returnTrainSpeedChange, $speedToNextKeyPoint);
			//array_push($returnTrainTimeChange, end($returnTrainTimeChange) + ($relativePosition / ($speedToNextKeyPoint / 3.6)));
			array_push($returnTrainTimeChange, end($returnTrainTimeChange) + $timeUpdateInterval);
			array_push($returnIsSpeedChange, false);
		}


		array_push($returnTrainPositionChange, $keyPoints[$i + 1]["position_0"]);
		array_push($returnTrainSpeedChange, $keyPoints[$i + 1]["speed_0"]);
		array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + distanceWithSpeedToTime($keyPoints[$i]["speed_1"], ($keyPoints[$i + 1]["position_0"] - $keyPoints[$i]["position_1"]))));
		array_push($returnIsSpeedChange, true);


	}

	if ($keyPoints[array_key_last($keyPoints)]["speed_0"] < $keyPoints[array_key_last($keyPoints)]["speed_1"]) {
		for ($j = ($keyPoints[array_key_last($keyPoints)]["speed_0"] + 2); $j <= $keyPoints[array_key_last($keyPoints)]["speed_1"]; $j = $j + 2) {
			array_push($returnTrainPositionChange, (end($returnTrainPositionChange) + getBrakeDistance(($j - 2), $j, $verzoegerung)));
			array_push($returnTrainSpeedChange, $j);
			array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + (getBrakeTime(($j - 2), $j, $verzoegerung))));
			array_push($returnIsSpeedChange, true);
		}
	} else {



		//TODO: KANN DAS WEG?
		//array_push($returnTrainPositionChange, $keyPoints[array_key_last($keyPoints)]["position_1"] - getBrakeDistance($keyPoints[array_key_last($keyPoints)]["speed_0"],$keyPoints[array_key_last($keyPoints)]["speed_1"],$verzoegerung));
		//array_push($returnTrainSpeedChange, $keyPoints[array_key_last($keyPoints)]["speed_0"]);
		//array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + distanceWithSpeedToTime($keyPoints[array_key_last($keyPoints)]["speed_0"], ($keyPoints[array_key_last($keyPoints)]["position_0"] - $keyPoints[array_key_last($keyPoints) - 1]["position_1"]))));
		//array_push($returnIsSpeedChange, true);
		for ($j = ($keyPoints[array_key_last($keyPoints)]["speed_0"] - 2); $j >= $keyPoints[array_key_last($keyPoints)]["speed_1"]; $j = $j - 2) {

			array_push($returnTrainPositionChange, (end($returnTrainPositionChange) + getBrakeDistance(($j + 2), $j, $verzoegerung)));
			array_push($returnTrainSpeedChange, $j);
			array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + (getBrakeTime(($j + 2), $j, $verzoegerung))));
			array_push($returnIsSpeedChange, true);
		}
	}



	foreach ($returnTrainPositionChange as $absolutPositionKey => $absolutPositionValue) {
		foreach ($cumulativeSectionLengthStart as $sectionStartKey => $sectionStartValue) {
			if ($absolutPositionValue >= $sectionStartValue && $absolutPositionValue < $cumulativeSectionLengthEnd[$sectionStartKey]) {
				if ($sectionStartKey == $indexCurrentSection && $sectionStartKey == $indexTargetSection) {
					$returnTrainRelativePosition[$absolutPositionKey] = $absolutPositionValue + $currentPosition;
					$returnTrainSection[$absolutPositionKey] = $next_sections[$sectionStartKey];
				} else if ($sectionStartKey == $indexCurrentSection) {
					$returnTrainRelativePosition[$absolutPositionKey] = $absolutPositionValue + $currentPosition;
					$returnTrainSection[$absolutPositionKey] = $next_sections[$sectionStartKey];
				} else if ($sectionStartKey == $indexTargetSection) {
					$returnTrainRelativePosition[$absolutPositionKey] = $absolutPositionValue - $sectionStartValue;
					$returnTrainSection[$absolutPositionKey] = $next_sections[$sectionStartKey];
				} else {
					$returnTrainRelativePosition[$absolutPositionKey] = $absolutPositionValue - $sectionStartValue;
					$returnTrainSection[$absolutPositionKey] = $next_sections[$sectionStartKey];
				}
				break;
			} else if ($absolutPositionKey == array_key_last($returnTrainPositionChange) && $absolutPositionValue == $cumulativeSectionLengthEnd[$sectionStartKey]) {
				$returnTrainRelativePosition[$absolutPositionKey] = $cumulativeSectionLengthEnd[$sectionStartKey] - $sectionStartValue;
				$returnTrainSection[$absolutPositionKey] = $next_sections[$sectionStartKey];
				break;
			} else {
				debugMessage("Eine absolute Position konnte keine relativen Position in einem Abschnitt zugeordnet werden!");
			}

			/*
			if ($absolutPositionValue < $sectionStartValue) {
				//$sectionIndex = $sectionStartKey - 1;
				$returnTrainRelativePosition[$absolutPositionKey] = $cumulativeSectionLengthStart[$sectionStartKey - 1] + $startPosition;
				$returnTrainSection[$absolutPositionKey] = $next_sections[$sectionStartKey - 1];
				break;
			} elseif ($absolutPositionKey == array_key_last($returnTrainPositionChange)) {
				$returnTrainRelativePosition[$absolutPositionKey] = $cumulativeSectionLengthStart[array_key_last($cumulativeSectionLengthStart)];
				$returnTrainSection[$absolutPositionKey] = $next_sections[array_key_last($cumulativeSectionLengthStart) - 1];
			}

			if ($absolutPositionValue < $sectionStartValue && array_key_first($cumulativeSectionLengthStart) != $sectionStartKey) {
				$returnTrainRelativePosition[$absolutPositionKey] = $absolutPositionValue - $cumulativeSectionLengthStart[$sectionStartKey - 1];
				$returnTrainSection[$absolutPositionKey] = $next_sections[$sectionStartKey - 1];
				break;
			} else if (array_key_last($cumulativeSectionLengthStart) == $sectionStartKey && $absolutPositionValue >= $cumulativeSectionLengthStart[array_key_last($cumulativeSectionLengthStart)]) {
				$returnTrainRelativePosition[$absolutPositionKey] = $absolutPositionValue - $cumulativeSectionLengthStart[array_key_last($cumulativeSectionLengthStart)];
				$returnTrainSection[$absolutPositionKey] = $next_sections[array_key_last($cumulativeSectionLengthStart)];
				break;
			} else {
				//var_dump("ERROR!!!");
			}
			*/
		}
	}
	return array($returnTrainPositionChange, $returnTrainSpeedChange, $returnTrainTimeChange, $returnTrainRelativePosition, $returnTrainSection, $returnIsSpeedChange);
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

	for ($i = 0; $i < (sizeof($keyPoints) - 1); $i++) {

		if ($keyPoints[$i]["speed_0"] < $keyPoints[$i]["speed_1"]) {
			for ($j = $keyPoints[$i]["speed_0"] + 2; $j <= $keyPoints[$i]["speed_1"]; $j = $j + 2) {
				array_push($returnAllTimes, (end($returnAllTimes) + (getBrakeTime($j - 2, $j, $verzoegerung))));

			}
			array_push($returnAllTimes, (end($returnAllTimes) + distanceWithSpeedToTime($keyPoints[$i]["speed_1"], ($keyPoints[$i + 1]["position_0"] - $keyPoints[$i]["position_1"]))));

		} else if ($keyPoints[$i]["speed_0"] > $keyPoints[$i]["speed_1"]) {
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
	} else if ($keyPoints[array_key_last($keyPoints)]["speed_0"] > $keyPoints[array_key_last($keyPoints)]["speed_1"]) {
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

	return $localKeyPoints;

}

function distanceWithSpeedToTime (int $v, float $distance) : float {
	if ($distance == 0) {
		return 0;
	}
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

	$floatingPointNumbersRoundingError = 0.0000000001;
	$v_max = array();

	for ($i = 0; $i <= 120; $i = $i + 10) {
		if ((getBrakeDistance($v_0, $i, $verzoegerung) + getBrakeDistance($i, $v_1, $verzoegerung)) < ($distance + $floatingPointNumbersRoundingError)) {
			array_push($v_max, $i);
		}
	}

	if ($v_0 == $v_1 && max($v_max) < $v_0) {
		return $v_0;
	}
	if (sizeof($v_max) == 0) {
		if ($distance == 0 && $v_0 == $v_1) {
			return $v_0;
		} else {
			return false;
		}
	}

	return max($v_max);
}

function checkKeyPointsOverlap() {
	global $keyPoints;
	global $verzoegerung;

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

			$v_maxFirstIteration = null;
			for ($i = $currentSpeed; $i <= 120; $i = $i + 10) {
				if ((getBrakeDistance($currentSpeed, $i, $verzoegerung) + getBrakeDistance($i, $targetSpeed, $verzoegerung)) > $distance) {
					$v_maxFirstIteration = $i - 10;
					$v_max = $v_maxFirstIteration;
					break;
				}
			}
		} else {
			array_push($previousFailedSections, $groupValue[0]);
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

function getTargetBrakeSpeedWithDistanceAndStartSpeed (float $distance, float $verzoegerung, int $speed) {
	//var_dump($distance, $verzoegerung, $speed);
	//var_dump($distance, $verzoegerung, $speed);
	return sqrt((-2 * $verzoegerung * $distance) + (pow(($speed / 3.6), 2)))*3.6;
}

// Anpassen für viele Schritte => $a bleibt konstant?! => Eher nicht anpassen und allgemein halten
function getBrakeDistance (float $v_0, float $v_1, float $verzoegerung) : float {
	// v in km/h
	// a in m/s^2
	// return in m
	// TODO: Wie sieht es mit der Reaktionszeit aus? (Wenn ja, dann nur bei der Ersten 2 km/h_diff Bremsung
	if ($v_0 > $v_1) {
		return $bremsweg = 0.5 * 1 * ((pow($v_0/3.6,2)-pow($v_1/3.6, 2))/(pow($verzoegerung, 1)));
	} if ($v_0 < $v_1) {
		return $bremsweg = -0.5 * 1 * ((pow($v_0/3.6,2)-pow($v_1/3.6, 2))/(pow($verzoegerung, 1)));
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
