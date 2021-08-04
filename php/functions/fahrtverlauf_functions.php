<?php

// TODO: current_speed = 0
function updateNextSpeed (array $train, float $startTime, float $endTime, int $targetSectionPara, int $targetPositionPara, bool $reachedBetriebsstelle, string $targetSignal, bool $wendet, bool $freieFahrt, array $allreachedInfras) {

	global $useSpeedFineTuning;
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
	global $allUsedTrains;
	global $globalIndexBetriebsstelleFreieFahrt;
	global $cacheSignalIDToBetriebsstelle;
	global $useMinTimeOnSpeed;
	global $slowDownIfTooEarly;
	global $globalFloatingPointNumbersRoundingError;

	$emptyArray = array();
	$keyPoints = $emptyArray;
	$cumulativeSectionLengthStart = $emptyArray;
	$cumulativeSectionLengthEnd = $emptyArray;
	$next_sections = $train["next_sections"];
	$next_lengths = $train["next_lenghts"];
	$next_v_max = $train["next_v_max"];
	$verzoegerung = $train["verzoegerung"];
	$notverzoegerung = $train["notverzoegerung"];
	$train_v_max = $train["v_max"];
	$currentSection = $train["current_section"];
	$currentPosition = $train["current_position"];
	$currentSpeed = $train["current_speed"];
	$train_length = $train["zuglaenge"];

	$targetSection = $targetSectionPara;
	$targetPosition = $targetPositionPara;

	$targetSpeed = 0;
	$targetTime = $endTime;
	//$targetTime = $startTime + 210;
	$indexCurrentSection = null;
	$indexTargetSection = null;
	$timeToNextStop = null;
	$maxTimeToNextStop = $targetTime - $startTime;
	$maxSpeedNextSections = 120;
	$targetBetriebsstelle = null;
	$indexReachedBetriebsstelle = $targetSignal;

	/*
	$next_sections = array(100, 1189, 101, 102, 103, 104, 105, 106, 107, 108, 1182, 107);
	//$next_lengths = array(4000, 190, 210, 200, 200, 200, 200, 200, 200, 200);
	$next_lengths = array(300, 400, 300, 400, 300, 200, 400, 500, 300, 400, 300, 300);
	$next_v_max = array(120, 120, 120, 90, 60, 60, 90, 120, 120, 100, 60, 40);
	//$next_v_max = array(100, 90, 80, 70, 60, 50, 40, 40, 80, 100);
	$currentPosition = 10;
	$targetPosition = 290;
	$train_v_max = 120;
	$train_length = 50;
	*/

	// TODO: Zug länger als bis zum nächsten ZIEL?! Was dann?

	if (!$freieFahrt) {
		$targetBetriebsstelle = $train["next_betriebsstellen_data"][$indexReachedBetriebsstelle]["betriebstelle"];
	} else {
		$targetBetriebsstelle = $cacheSignalIDToBetriebsstelle[intval($targetSignal)];
	}

	// TODO: Zug steht schon am Ziel (ist das nötig?)
	if ($targetSection == $currentSection && $targetPosition == $currentPosition) {
		// Freie Fahrt
		if ($indexReachedBetriebsstelle == $globalIndexBetriebsstelleFreieFahrt) {
			$indexReachedBetriebsstelle = -1;
		}
		$adress = $train["adresse"];
		$return = array(array("live_position" => $targetPosition, "live_speed" => $targetSpeed, "live_time" => $endTime, "live_relative_position" => $targetPosition, "live_section" => $targetSection, "live_is_speed_change" => false, "live_target_reached" => $reachedBetriebsstelle, "wendet" => $wendet, "id" => $train["id"], "betriebsstelle_name" => $targetBetriebsstelle));
		$allTimes[$adress] = array_merge($allTimes[$adress], $return);
		return 0;
	}

	// Wenn ein Abschnitt eine Geschwindigkeit zulässt, die größer als die v_max des Zugs ist, wird die Geschwindigkeit auf die v_max des Zuges beschränkt
	if ($train_v_max != null) {
		foreach ($next_sections as $sectionKey => $sectionValue) {
			if ($next_v_max[$sectionKey] > $train_v_max) {
				$next_v_max[$sectionKey] = $train_v_max;
			}
		}
	}

	// Index des Start- und Zielabschnitts
	foreach ($next_sections as $sectionKey => $sectionValue) {
		if ($sectionValue == $currentSection) {
			$indexCurrentSection = $sectionKey;
		}
		if ($sectionValue == $targetSection) {
			$indexTargetSection = $sectionKey;
		}
	}

	$returnCumulativeSections = createCumulativeSections($indexCurrentSection, $indexTargetSection, $currentPosition, $targetPosition, $next_lengths);
	$cumulativeSectionLengthStart = $returnCumulativeSections[0];
	$cumulativeSectionLengthEnd = $returnCumulativeSections[1];
	$cumLengthEnd = array();
	$cumLengthStart = array();
	$sum = 0;

	foreach ($next_lengths as $index => $value) {
		if ($index >= $indexCurrentSection) {
			$cumLengthStart[$index] = $sum;
			$sum += $value;
			$cumLengthEnd[$index] = $sum;
		}
	}

	$distanceToNextStop = $cumulativeSectionLengthEnd[$indexTargetSection];

	global $next_v_max_mod;
	global $next_lengths_mod;
	$next_v_max_mod = array();
	$next_lengths_mod = array();

	global $indexCurrentSectionMod;
	global $indexTargetSectionMod;
	$indexCurrentSectionMod = null;
	$indexTargetSectionMod = null;

	if ($indexCurrentSection == $indexTargetSection) {
		$next_lengths_mod = $next_lengths;
		$next_v_max_mod = $next_v_max;
		$indexCurrentSectionMod = $indexCurrentSection;
		$indexTargetSectionMod = $indexTargetSection;
	} else {
		$startPosition = 0;
		$indexStartPosition = null;
		$indexEndPosition = null;

		do {
			$reachedTargetSection = false;
			// Find out the index from the start
			for ($j = $indexCurrentSection; $j <= $indexTargetSection; $j++) {
				if ($startPosition >= $cumLengthStart[$j] && $startPosition < $cumLengthEnd[$j]) {
					$indexStartPosition = $j;
				}
			}

			$endPosition = $cumLengthEnd[$indexStartPosition] + $train_length;
			$current_v_max = $next_v_max[$indexStartPosition];

			// Find out the index from the start
			if ($endPosition >= $cumLengthEnd[$indexTargetSection]) {
				$indexEndPosition = $indexTargetSection;
				$endPosition = $cumLengthEnd[$indexTargetSection - 1] + $targetPosition;
				$reachedTargetSection = true;
			} else {
				for ($j = $indexCurrentSection; $j <= $indexTargetSection; $j++) {
					if ($endPosition >= $cumLengthStart[$j] && $endPosition < $cumLengthEnd[$j]) {
						$indexEndPosition = $j;
					}
				}
			}

			for ($j = $indexStartPosition + 1; $j <= $indexEndPosition; $j++) {
				if ($next_v_max[$j] < $current_v_max) {
					$endPosition = $cumLengthStart[$j];
					$indexEndPosition = $j - 1;
				}
			}

			if ($reachedTargetSection) {
				if (!($endPosition >= $distanceToNextStop)) {
					$reachedTargetSection = false;
				}
			}

			array_push($next_lengths_mod, ($endPosition - $startPosition));
			array_push($next_v_max_mod, $current_v_max);

			$startPosition = $endPosition;
		} while (!$reachedTargetSection);
	}

	$indexCurrentSectionMod = array_key_first($next_lengths_mod);
	$indexTargetSectionMod = array_key_last($next_lengths_mod);

	$returnCumulativeSectionsMod = createCumulativeSections($indexCurrentSectionMod,$indexTargetSectionMod,$currentPosition, $next_lengths_mod[$indexTargetSectionMod], $next_lengths_mod);

	global $cumulativeSectionLengthStartMod;
	global $cumulativeSectionLengthEndMod;
	$cumulativeSectionLengthStartMod = $returnCumulativeSectionsMod[0];
	$cumulativeSectionLengthEndMod = $returnCumulativeSectionsMod[1];

	$minTimeOnSpeedIsPossible = checkIfItsPossible();

	// Emergency Brake
	// TODO: Wo muss die Notbremsung durchgeführt werden?
	if (getBrakeDistance($currentSpeed, $targetSpeed, $verzoegerung) > $distanceToNextStop && $currentSpeed != 0) {
		echo "Der Zug mit der Adresse: ", $train["adresse"], " leitet jetzt eine Notbremsung ein.\n";
		$returnArray = array();
		$time = $startTime;
		if (getBrakeDistance($currentSpeed, $targetSpeed, $notverzoegerung) <= $distanceToNextStop) {
			for ($i = $currentSpeed; $i >= 0; $i = $i - 2) {
				array_push($returnArray, array("live_position" => 0, "live_speed" => $i, "live_time" => $time, "live_relative_position" => 0, "live_section" => $currentSection, "live_is_speed_change" => true, "live_target_reached" => false, "id" => $train["id"], "wendet" => false, "betriebsstelle" => null));
				$time =  $time + getBrakeTime($i, $i - 1, $notverzoegerung);
			}
		} else {
			$targetSpeedNotbremsung =  getTargetBrakeSpeedWithDistanceAndStartSpeed($distanceToNextStop, $notverzoegerung, $currentSpeed);
			$speedBeforeStop = intval($targetSpeedNotbremsung / 2) * 2;
			if ($speedBeforeStop >= 10) {
				for ($i = $currentSpeed; $i >= 10; $i = $i - 2) {
					array_push($returnArray, array("live_position" => 0, "live_speed" => $i, "live_time" => $time, "live_relative_position" => 0, "live_section" => $currentSection, "live_is_speed_change" => true, "live_target_reached" => false, "id" => $train["id"], "wendet" => false, "betriebsstelle" => null));
					$time =  $time + getBrakeTime($i, $i - 1, $notverzoegerung);
				}
				array_push($returnArray, array("live_position" => 0, "live_speed" => 0, "live_time" => $time, "live_relative_position" => 0, "live_section" => $currentSection, "live_is_speed_change" => true, "live_target_reached" => false, "id" => $train["id"], "wendet" => false, "betriebsstelle" => null));
			} else {
				array_push($returnArray, array("live_position" => 0, "live_speed" => $currentSpeed, "live_time" => $time, "live_relative_position" => 0, "live_section" => $currentSection, "live_is_speed_change" => true, "live_target_reached" => false, "id" => $train["id"], "wendet" => false, "betriebsstelle" => null));
				$time =  $time + getBrakeTime($currentSpeed, $currentSpeed - 1, $notverzoegerung);
				array_push($returnArray, array("live_position" => 0, "live_speed" => 0, "live_time" => $time, "live_relative_position" => 0, "live_section" => $currentSection, "live_is_speed_change" => true, "live_target_reached" => false, "id" => $train["id"], "wendet" => false, "betriebsstelle" => null));
			}
		}
		$allTimes[$train["adresse"]] = $returnArray;
		$allUsedTrains[$train["id"]]["can_drive"] = false;
		return 0;
	}

	$v_maxFirstIteration = getVMaxBetweenTwoPoints($distanceToNextStop, $currentSpeed, $targetSpeed);

	// Anpassung an die maximale Geschwindigkeit auf der Strecke
	for ($i = $indexCurrentSection; $i <= $indexTargetSection; $i++) {
		if ($next_v_max[$i] > $maxSpeedNextSections) {
			$maxSpeedNextSections = $next_v_max[$i];
		}
	}

	if ($maxSpeedNextSections < $v_maxFirstIteration) {
		$v_maxFirstIteration = $maxSpeedNextSections;
	}

	// Key Points für die erste Iteration erstellen.
	array_push($keyPoints, createKeyPoint(0, getBrakeDistance($currentSpeed, $v_maxFirstIteration, $verzoegerung), $currentSpeed, $v_maxFirstIteration));
	array_push($keyPoints, createKeyPoint(($distanceToNextStop - getBrakeDistance($v_maxFirstIteration, $targetSpeed, $verzoegerung)), $distanceToNextStop, $v_maxFirstIteration, $targetSpeed));

	//function keyPoints => trainChangeArrays
	$trainChange = convertKeyPointsToTrainChangeArray($keyPoints);
	$trainPositionChange = $trainChange[0];
	$trainSpeedChange = $trainChange[1];

	$speedOverPositionAllIterations = array();

	while (checkIfTrainIsToFastInCertainSections()["failed"]) {
		// saves the keyPoints local
		$tempKeyPoints = $keyPoints;

		// berechnet die "live Daten"
		$trainChange = createTrainChanges(true);
		$trainPositionChange = $trainChange[0];
		$trainSpeedChange = $trainChange[1];

		array_push($speedOverPositionAllIterations, array($trainPositionChange, $trainSpeedChange));

		// suche nach Fehlern und neuberechnung...
		$keyPoints = recalculateKeyPoints($tempKeyPoints);

		$localKeyPointsTwo = array();

		// Löschung von zwei aufernanderfolgenden "unnötigen" KeyPoints
		for ($i = 0; $i < sizeof($keyPoints); $i++) {
			if ($i < sizeof($keyPoints) - 1) {
				if (!($keyPoints[$i]["speed_0"] == $keyPoints[$i]["speed_1"] && $keyPoints[$i]["speed_0"] == $keyPoints[$i + 1]["speed_0"] && $keyPoints[$i]["speed_0"] == $keyPoints[$i + 1]["speed_1"])) {
					array_push($localKeyPointsTwo, $keyPoints[$i]);
				} else {
					$i++;
				}
			} else {
				array_push($localKeyPointsTwo, $keyPoints[$i]);
			}
		}

		$keyPoints = $localKeyPointsTwo;
		$trainChange = createTrainChanges(true);
		$trainPositionChange = $trainChange[0];
		$trainSpeedChange = $trainChange[1];
	}

	// Adding time to first KeyPoint
	$keyPoints[0]["time_0"] = $startTime;
	// TODO: WIrd diese Funktion benötigt?
	$keyPoints = deleteDoubledKeyPoints($keyPoints);

	// TODO: Evtl. $timeToNextStop über $trainChangeTime errechnen und nicht über eine eigene Funktion
	// Berechnet die Zeiten für die Train Change Daten
	//$trainTimeChange = calculateTrainTimeChange();
	// Berechnet die Zeiten für die KeyPoints
	$keyPoints = calculateTimeFromKeyPoints();
	if ($useMinTimeOnSpeed && $minTimeOnSpeedIsPossible) {
		array_push($speedOverPositionAllIterations, array($trainPositionChange, $trainSpeedChange));
		toShortOnOneSpeed();
	}
	$trainChange = createTrainChanges(true);
	$trainPositionChange = $trainChange[0];
	$trainSpeedChange = $trainChange[1];
	$timeToNextStop = end($keyPoints)["time_1"] - $keyPoints[0]["time_0"];

	// Zug kommt zu spät an...
	if (!$freieFahrt) {
		if ($timeToNextStop > $maxTimeToNextStop) {
			// TODO: Als allgemeine Info am Anfang der Funktion...
			echo "Der Zug mit der Adresse ", $train["adresse"], " wird mit einer Verspätung von ", number_format($timeToNextStop - $maxTimeToNextStop, 2), " Sekunden im nächsten planmäßigen Halt (", $targetBetriebsstelle,") ankommen.\n";
		} else {
			echo "Aktuell benötigt der Zug mit der Adresse ", $train["adresse"], " ", number_format($timeToNextStop, 2), " Sekunden, obwohl er ", number_format($maxTimeToNextStop, 2), " Sekunden zur Verfügung hat.\n";
			if ($slowDownIfTooEarly) {
				echo "Evtl. könnte der Zug zwischendurch die Geschwindigkeit verringern, um Energie zu sparen.";
				array_push($speedOverPositionAllIterations, array($trainPositionChange, $trainSpeedChange));
				$keyPointsPreviousStep = array();
				$finish = false;
				// checkIfTheSpeedCanBeDecreased() => sucht zwei benachbarte KeyPOPints die erst beschleunigen und dann abbremsen
				// und speichert zu dem Index alle möglichen Anpassungen
				$possibleSpeedRange = null;
				$returnSpeedDecrease = checkIfTheSpeedCanBeDecreased();
				while ($returnSpeedDecrease["possible"] && !$finish) {
					$possibleSpeedRange = findMaxSpeed($returnSpeedDecrease);
					if ($possibleSpeedRange["min_speed"] == 10 && $possibleSpeedRange["max_speed"] == 10) {
						break;
					}
					$localKeyPoints = $keyPoints;    //lokale Kopie der KeyPoints
					$newCalculatedTime = null;        //Zeit bis zum Ziel
					$newKeyPoints = null;
					for ($i = $possibleSpeedRange["max_speed"]; $i >= $possibleSpeedRange["min_speed"]; $i = $i - 10) {
						$localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["speed_1"] = $i;
						$localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["speed_0"] = $i;
						$localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["position_1"] = (getBrakeDistance($localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["speed_0"], $i, $verzoegerung) + $localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["position_0"]);
						$localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["position_0"] = ($localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["position_1"] - getBrakeDistance($i, $localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["speed_1"], $verzoegerung));
						$localKeyPoints = calculateTimeFromKeyPoints($localKeyPoints);
						$newCalculatedTime = $localKeyPoints[array_key_last($localKeyPoints)]["time_1"];
						if ($i == 10) {
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
					$returnSpeedDecrease = checkIfTheSpeedCanBeDecreased();
				}
				$keyPoints = calculateTimeFromKeyPoints();
				if ($useSpeedFineTuning) {
					array_push($speedOverPositionAllIterations, array($trainPositionChange, $trainSpeedChange));
					$newCalculatedTime = $keyPoints[array_key_last($keyPoints)]["time_1"];
					speedFineTuning(($maxTimeToNextStop - ($newCalculatedTime - $startTime)), $possibleSpeedRange["first_key_point_index"]);
				}
				// TODO: $currentTime global verfügbar machen
				$keyPoints = calculateTimeFromKeyPoints();
				$timeToNextStop = end($keyPoints)["time_1"] - $keyPoints[0]["time_0"];
				echo "\nDurch die Anpassung der Geschwindigkeit benötigt der Zug mit der Adresse ", $train["adresse"], " jetzt ", number_format($timeToNextStop, 2), " Sekunden bis\n";
				if (abs($timeToNextStop - $maxTimeToNextStop) < $globalFloatingPointNumbersRoundingError) {
					echo "zum nächsten planmäßigen Halt (", $targetBetriebsstelle, ") und wird diesen genau pünktlich erreichen.\n";
				} else if (($timeToNextStop - $maxTimeToNextStop) > 0) {
					echo "zum nächsten planmäßigen Halt (", $targetBetriebsstelle, ") und wird diesen mit einer Verspätung von ", number_format($timeToNextStop - $maxTimeToNextStop, 2), " Sekunden erreichen.\n";
				} else {
					echo "zum nächsten planmäßigen Halt (", $targetBetriebsstelle, ") und wird diesen ", number_format($timeToNextStop - $maxTimeToNextStop, 2), " Sekunden zu früh erreichen.\n";
				}
			} else {
				echo "Dadurch, dass \$slowDownIfTooEarly = true ist, wird das Fahrzeug ", number_format($maxTimeToNextStop - $timeToNextStop, 2), " Sekunden zu früh am Ziel ankommen.";
			}
		}
	} else {
		echo "Der Zug mit der Adresse ", $train["adresse"], " fährt aktuell ohne Fahrplan bis zum nächsten auf Halt stehendem Signal (Signal ID: ", $targetSignal, ", Betriebsstelle: ", $targetBetriebsstelle,").";
	}

	$returnTrainChanges = createTrainChanges(false);
	$trainPositionChange = $returnTrainChanges[0];
	$trainSpeedChange = $returnTrainChanges[1];
	$trainTimeChange = $returnTrainChanges[2];
	$trainRelativePosition = $returnTrainChanges[3];
	$trainSection = $returnTrainChanges[4];
	$trainIsSpeedChange = $returnTrainChanges[5];
	$trainTargetReached = array();
	$trainBetriebsstelleName = array();
	$trainWendet = array();
	$allReachedTargets = array();
	$allreachedInfrasIndex = array();
	$allreachedInfrasID = array();
	$allreachedInfrasUsed = array();
	foreach ($allreachedInfras as $value) {
		array_push($allreachedInfrasIndex, $value["index"]);
		array_push($allreachedInfrasID, $value["infra"]);
	}

	foreach ($trainPositionChange as $key => $value) {
		$trainBetriebsstelleName[$key] = $targetBetriebsstelle;
		// Nicht das letzte Element
		if (array_key_last($trainPositionChange) != $key) {
			$trainTargetReached[$key] = false;
			$trainWendet[$key] = false;
			// Das letzte Element
		} else {
			if ($wendet) {
				$trainWendet[$key] = true;
			} else {
				$trainWendet[$key] = false;
			}
			if ($reachedBetriebsstelle) {
				$trainTargetReached[$key] = true;
			} else {
				$trainTargetReached[$key] = false;
			}
		}
	}

	for($i = sizeof($trainSection) - 1; $i >= 0; $i--) {
		if (in_array($trainSection[$i], $allreachedInfrasID) && !in_array($trainSection[$i], $allreachedInfrasUsed)) {
			array_push($allreachedInfrasUsed, $trainSection[$i]);
			$Infraindex = array_search($trainSection[$i], $allreachedInfrasID);
			$allReachedTargets[$i] = $allreachedInfrasIndex[$Infraindex];
		} else {
			$allReachedTargets[$i] = null;
		}
	}
	ksort($allReachedTargets);
	$returnArray = array();
	$adress = $train["adresse"];
	$trainID = array();
	$id = $train["id"];
	foreach ($trainPositionChange as $key => $value) {
		$trainID[$key] = $id;
	}
	foreach ($trainPositionChange as $trainPositionChangeIndex => $trainPositionChangeValue) {
		array_push($returnArray, array("live_position" => $trainPositionChangeValue,
			"live_speed" => $trainSpeedChange[$trainPositionChangeIndex],
			"live_time" => $trainTimeChange[$trainPositionChangeIndex],
			"live_relative_position" => $trainRelativePosition[$trainPositionChangeIndex],
			"live_section" => $trainSection[$trainPositionChangeIndex],
			"live_is_speed_change" => $trainIsSpeedChange[$trainPositionChangeIndex],
			"live_target_reached" => $trainTargetReached[$trainPositionChangeIndex],
			"id" => $trainID[$trainPositionChangeIndex],
			"wendet" => $trainWendet[$trainPositionChangeIndex],
			"betriebsstelle" => $trainBetriebsstelleName[$trainPositionChangeIndex],
			"live_all_targets_reached" => $allReachedTargets[$trainPositionChangeIndex]));
	}
	$allTimes[$adress] = $returnArray;
	safeTrainChangeToJSONFile($indexCurrentSection, $indexTargetSection, $indexCurrentSectionMod, $indexTargetSectionMod, $speedOverPositionAllIterations);
	return (end($trainTimeChange) - $trainTimeChange[0]) - ($endTime - $startTime);
}

// Anpassen für viele Schritte => $a bleibt konstant?! => Eher nicht anpassen und allgemein halten
function getBrakeDistance (float $v_0, float $v_1, float $verzoegerung) {
	if ($v_0 > $v_1) {
		return $bremsweg = 0.5 * ((pow($v_0/3.6,2)-pow($v_1/3.6, 2))/($verzoegerung));
	} if ($v_0 < $v_1) {
		return $bremsweg = -0.5 * ((pow($v_0/3.6,2)-pow($v_1/3.6, 2))/($verzoegerung));
	} else {
		return 0;
	}
}

// TODO: Überarbeitung
function getVMaxBetweenTwoPoints(float $distance, int $v_0, int $v_1) {
	global $verzoegerung;
	global $globalFloatingPointNumbersRoundingError;

	$v_max = array();
	for ($i = 0; $i <= 120; $i = $i + 10) {
		if ((getBrakeDistance($v_0, $i, $verzoegerung) + getBrakeDistance($i, $v_1, $verzoegerung)) < ($distance + $globalFloatingPointNumbersRoundingError)) {
			array_push($v_max, $i);
		}
	}
	if (sizeof($v_max) == 0) {
		if ($v_0 == 0 && $v_1 == 0 && $distance > 0) {
			echo "Der zug müsste langsamer als 10 km/h fahren, um das Ziel zu erreichen.";
		} else {
			// TODO: Notbremsung
		}
	} else {
		if ($v_0 == $v_1 && max($v_max) < $v_0) {
			$v_max = array($v_0);
		}
	}
	return max($v_max);
}

function createKeyPoint (float $position_0, float $position_1, int $speed_0, int $speed_1) {
	return array("position_0" => $position_0, "position_1" => $position_1, "speed_0" => $speed_0, "speed_1" => $speed_1);
}

// TODO: Funktion doppelt sich... (evtl. hilfreich, weil diese Funktion nur Geschwindigkeit und Position zurückgibt)
function convertKeyPointsToTrainChangeArray (array $keyPoints) {
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
	} else if (end($keyPoints)["speed_0"] > end($keyPoints)["speed_1"]) {
		for ($j = end($keyPoints)["speed_0"]; $j > end($keyPoints)["speed_1"]; $j = $j - 2) {
			array_push($trainPositionChnageReturn, (end($trainPositionChnageReturn) + getBrakeDistance($j, ($j - 2), $verzoegerung)));
			array_push($trainSpeedChangeReturn, ($j - 2));
		}
	}
	return array($trainPositionChnageReturn, $trainSpeedChangeReturn);
}

function safeTrainChangeToJSONFile(int $indexCurrentSection, int $indexTargetSection, int $indexCurrentSectionMod, int $indexTargetSectionMod, array $speedOverPositionAllIterations) {
	global $trainPositionChange;
	global $trainSpeedChange;
	global $next_v_max;
	global $cumulativeSectionLengthEnd;
	global $next_v_max_mod;
	global $cumulativeSectionLengthEndMod;

	$speedOverPosition = array_map('toArr', $trainPositionChange, $trainSpeedChange);
	$speedOverPosition = json_encode($speedOverPosition);
	$fp = fopen('../json/speedOverPosition_v1.json', 'w');
	fwrite($fp, $speedOverPosition);
	fclose($fp);

	$v_maxFromUsedSections = array();
	for ($i = $indexCurrentSection; $i <= $indexTargetSection; $i++) {
		array_push($v_maxFromUsedSections, $next_v_max[$i]);
	}
	$VMaxOverCumulativeSections = array_map('toArr', $cumulativeSectionLengthEnd, $v_maxFromUsedSections);
	$VMaxOverPositionsJSon = json_encode($VMaxOverCumulativeSections);
	$fp = fopen('../json/VMaxOverCumulativeSections.json', 'w');
	fwrite($fp, $VMaxOverPositionsJSon);
	fclose($fp);

	$v_maxFromUsedSections = array();
	for ($i = $indexCurrentSectionMod; $i <= $indexTargetSectionMod; $i++) {
		array_push($v_maxFromUsedSections, $next_v_max_mod[$i]);
	}
	$VMaxOverCumulativeSectionsMod = array_map('toArr', $cumulativeSectionLengthEndMod, $v_maxFromUsedSections);
	$VMaxOverPositionsJSon = json_encode($VMaxOverCumulativeSectionsMod);
	$fp = fopen('../json/VMaxOverCumulativeSectionsMod.json', 'w');
	fwrite($fp, $VMaxOverPositionsJSon);
	fclose($fp);

	$jsonReturn = array();
	for ($i = 0; $i < sizeof($speedOverPositionAllIterations); $i++) {
		$iteration = array_map('toArr', $speedOverPositionAllIterations[$i][0], $speedOverPositionAllIterations[$i][1]);
		array_push($jsonReturn, $iteration);
	}
	$speedOverPosition = json_encode($jsonReturn);
	$fp = fopen('../json/speedOverPosition_prevIterations.json', 'w');
	fwrite($fp, $speedOverPosition);
	fclose($fp);
}

function checkIfTrainIsToFastInCertainSections() {

	global $trainPositionChange;
	global $trainSpeedChange;
	global $cumulativeSectionLengthStartMod;
	global $next_v_max_mod;
	global $indexTargetSectionMod;

	$faildSections = array();

	foreach ($trainPositionChange as $trainPositionChangeKey => $trainPositionChangeValue) {
		foreach ($cumulativeSectionLengthStartMod as $cumulativeSectionLengthStartKey => $cumulativeSectionLengthStartValue) {
			if ($trainPositionChangeValue < $cumulativeSectionLengthStartValue) {
				if ($trainSpeedChange[$trainPositionChangeKey] > $next_v_max_mod[$cumulativeSectionLengthStartKey - 1]) {
					array_push($faildSections, ($cumulativeSectionLengthStartKey -1));
				}
				break;
			} else if ($cumulativeSectionLengthStartKey == $indexTargetSectionMod) {
				if ($trainPositionChangeValue > $cumulativeSectionLengthStartValue) {
					if ($trainSpeedChange[$trainPositionChangeKey] > $next_v_max_mod[$cumulativeSectionLengthStartKey]) {
						array_push($faildSections, $cumulativeSectionLengthStartKey);
					}
					break;
				}
			}
		}
	}

	if (sizeof($faildSections) == 0) {
		return array("failed" => false);
	} else {
		return array("failed" => true, "failed_sections" => array_unique($faildSections));
	}
}

function deleteDoubledKeyPoints($temporaryKeyPoints) {

	do {
		$foundDoubledKeyPoints = false;
		$doubledIndex = array();
		for ($i = 1; $i < (sizeof($temporaryKeyPoints) - 1); $i++) {
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

function calculateTrainTimeChange() {

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

function getBrakeTime (float $v_0, float $v_1, float $verzoegerung) {
	if ($v_0 < $v_1) {
		return (($v_1/3.6)/$verzoegerung) - (($v_0/3.6)/$verzoegerung);
	} elseif ($v_0 > $v_1) {
		return (($v_0/3.6)/$verzoegerung) - (($v_1/3.6)/$verzoegerung);
	} else {
		return 0;
	}
}

// TODO: Darf nichts negatives zurückgeben!
// TODO: Muss auch was neg. zurück geben für  minTimeOnOneSPeed!
function distanceWithSpeedToTime (int $v, float $distance) {
	if ($distance == 0) {
		return 0;
	}
	return (($distance)/($v / 3.6));
}

function calculateTimeFromKeyPoints($inputKeyPoints = null, $skippingKeys = null) {

	global $keyPoints;
	global $verzoegerung;

	if ($inputKeyPoints == null) {
		$localKeyPoints = $keyPoints;
	} else {
		$localKeyPoints = $inputKeyPoints;
	}
	$keys = array_keys($localKeyPoints);
	if ($skippingKeys != null) {
		foreach ($skippingKeys as $skip) {
			unset($keys[array_search($skip, $keys)]);
		}
	}
	$keys = array_values($keys);

	for ($i = 0; $i < (sizeof($keys) - 1); $i++) {
		$localKeyPoints[$keys[$i]]["time_1"] = getBrakeTime($localKeyPoints[$keys[$i]]["speed_0"], $localKeyPoints[$keys[$i]]["speed_1"], $verzoegerung) + $localKeyPoints[$keys[$i]]["time_0"];
		$localKeyPoints[$keys[$i] + 1]["time_0"] = distanceWithSpeedToTime($localKeyPoints[$keys[$i]]["speed_1"], ($localKeyPoints[$keys[$i] + 1]["position_0"]) - $localKeyPoints[$keys[$i]]["position_1"]) + $localKeyPoints[$keys[$i]]["time_1"];
	}

	$localKeyPoints[end($keys)]["time_1"] = getBrakeTime($localKeyPoints[end($keys)]["speed_0"], $localKeyPoints[end($keys)]["speed_1"], $verzoegerung) + $localKeyPoints[end($keys)]["time_0"];
	return $localKeyPoints;
}

// TODO: Only Position and Speed
function createTrainChanges(bool $onlyPositionAndSpeed) {

	global $keyPoints;
	global $verzoegerung;
	global $cumulativeSectionLengthStart;
	global $cumulativeSectionLengthEnd;
	global $next_sections;
	global $indexCurrentSection;
	global $indexTargetSection;
	global $currentPosition;
	global $globalFloatingPointNumbersRoundingError;

	$returnTrainSpeedChange = array();
	$returnTrainTimeChange = array();
	$returnTrainPositionChange = array();
	$returnTrainRelativePosition = array();
	$returnTrainSection = array();
	$returnIsSpeedChange = array();

	// Alle bis auf den letzten Key Point
	// Erstellt immer alle Daten zwischen KeyPoint Anfang und dem letzten Wert vor dem nächsten KeyPoint
	for ($i = 0; $i < (sizeof($keyPoints) - 1); $i++) {
		array_push($returnTrainTimeChange, $keyPoints[$i]["time_0"]);
		array_push($returnTrainSpeedChange, $keyPoints[$i]["speed_0"]);
		array_push($returnTrainPositionChange, $keyPoints[$i]["position_0"]);
		array_push($returnIsSpeedChange, true);
		if ($keyPoints[$i]["speed_0"] < $keyPoints[$i]["speed_1"]) {
			// Speichert alle ab dem zweiten Wert bis zum letzten Wert
			for ($j = ($keyPoints[$i]["speed_0"] + 2); $j <= $keyPoints[$i]["speed_1"]; $j = $j + 2) {
				array_push($returnTrainPositionChange, (end($returnTrainPositionChange) + getBrakeDistance(($j - 2), $j, $verzoegerung)));
				array_push($returnTrainSpeedChange, $j);
				array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + (getBrakeTime(($j - 2), $j, $verzoegerung))));
				array_push($returnIsSpeedChange, true);
			}
		} else {
			for ($j = ($keyPoints[$i]["speed_0"] - 2); $j >= $keyPoints[$i]["speed_1"]; $j = $j - 2) {
				array_push($returnTrainPositionChange, (end($returnTrainPositionChange) + getBrakeDistance(($j + 2), $j, $verzoegerung)));
				array_push($returnTrainSpeedChange, $j);
				array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + (getBrakeTime(($j + 2), $j, $verzoegerung))));
				array_push($returnIsSpeedChange, true);
			}
		}
		$startPosition = $keyPoints[$i]["position_1"];
		$endPosition =  $keyPoints[$i + 1]["position_0"];
		$speedToNextKeyPoint = $keyPoints[$i]["speed_1"];
		$distanceForOneTimeInterval = 1;
		$timeUpdateInterval = distanceWithSpeedToTime($speedToNextKeyPoint, 1);
		for ($position = $startPosition + $distanceForOneTimeInterval; $position < $endPosition; $position = $position + $distanceForOneTimeInterval) {
			array_push($returnTrainPositionChange, $position);
			array_push($returnTrainSpeedChange, $speedToNextKeyPoint);
			array_push($returnTrainTimeChange, end($returnTrainTimeChange) + $timeUpdateInterval);
			array_push($returnIsSpeedChange, false);
		}
	}
	array_push($returnTrainPositionChange, $keyPoints[array_key_last($keyPoints)]["position_1"] - getBrakeDistance($keyPoints[array_key_last($keyPoints)]["speed_0"],$keyPoints[array_key_last($keyPoints)]["speed_1"],$verzoegerung));
	array_push($returnTrainSpeedChange, $keyPoints[array_key_last($keyPoints)]["speed_0"]);
	array_push($returnTrainTimeChange, $keyPoints[array_key_last($keyPoints)]["time_0"]);
	array_push($returnIsSpeedChange, true);
	// letzter KeyPoint
	if ($keyPoints[array_key_last($keyPoints)]["speed_0"] < $keyPoints[array_key_last($keyPoints)]["speed_1"]) {
		for ($j = ($keyPoints[array_key_last($keyPoints)]["speed_0"] + 2); $j <= $keyPoints[array_key_last($keyPoints)]["speed_1"]; $j = $j + 2) {
			array_push($returnTrainPositionChange, (end($returnTrainPositionChange) + getBrakeDistance(($j - 2), $j, $verzoegerung)));
			array_push($returnTrainSpeedChange, $j);
			array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + (getBrakeTime(($j - 2), $j, $verzoegerung))));
			array_push($returnIsSpeedChange, true);
		}
	} else {
		for ($j = ($keyPoints[array_key_last($keyPoints)]["speed_0"] - 2); $j >= $keyPoints[array_key_last($keyPoints)]["speed_1"]; $j = $j - 2) {
			array_push($returnTrainPositionChange, (end($returnTrainPositionChange) + getBrakeDistance(($j + 2), $j, $verzoegerung)));
			array_push($returnTrainSpeedChange, $j);
			array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + (getBrakeTime(($j + 2), $j, $verzoegerung))));
			array_push($returnIsSpeedChange, true);
		}
	}
	if ($onlyPositionAndSpeed) {
		return array($returnTrainPositionChange, $returnTrainSpeedChange);
	} else {
		// Erstellt die relativen Positionen und Abschnitte zu den absoluten Werten. position
		foreach ($returnTrainPositionChange as $absolutPositionKey => $absolutPositionValue) {
			foreach ($cumulativeSectionLengthStart as $sectionStartKey => $sectionStartValue) {
				if ($absolutPositionValue >= $sectionStartValue && $absolutPositionValue < $cumulativeSectionLengthEnd[$sectionStartKey]) {
					if ($sectionStartKey == $indexCurrentSection && $sectionStartKey == $indexTargetSection) {
						$returnTrainRelativePosition[$absolutPositionKey] = $absolutPositionValue + $currentPosition;
					} else if ($sectionStartKey == $indexCurrentSection) {
						$returnTrainRelativePosition[$absolutPositionKey] = $absolutPositionValue + $currentPosition;
					} else if ($sectionStartKey == $indexCurrentSection) {
						$returnTrainRelativePosition[$absolutPositionKey] = $absolutPositionValue - $sectionStartValue;
					} else {
						$returnTrainRelativePosition[$absolutPositionKey] = $absolutPositionValue - $sectionStartValue;
					}
					break;
				} else if ($absolutPositionKey == array_key_last($returnTrainPositionChange) && abs($absolutPositionValue - floatval($cumulativeSectionLengthEnd[$sectionStartKey])) < $globalFloatingPointNumbersRoundingError) {
					$returnTrainRelativePosition[$absolutPositionKey] = $cumulativeSectionLengthEnd[$sectionStartKey] - $sectionStartValue;
					break;
				} else {
					debugMessage("Eine absolute Position konnte keine relativen Position in einem Abschnitt zugeordnet werden!");
				}
			}
		}

		// Erstellt die relativen Positionen und Abschnitte zu den absoluten Werten. section
		foreach ($returnTrainPositionChange as $absolutPositionKey => $absolutPositionValue) {
			foreach ($cumulativeSectionLengthStart as $sectionStartKey => $sectionStartValue) {
				if ($absolutPositionValue >= $sectionStartValue && $absolutPositionValue < $cumulativeSectionLengthEnd[$sectionStartKey]) {
					if ($sectionStartKey == $indexCurrentSection && $sectionStartKey == $indexTargetSection) {
						$returnTrainSection[$absolutPositionKey] = $next_sections[$sectionStartKey];
					} else if ($sectionStartKey == $indexCurrentSection) {
						$returnTrainSection[$absolutPositionKey] = $next_sections[$sectionStartKey];
					} else if ($sectionStartKey == $indexTargetSection) {
						$returnTrainSection[$absolutPositionKey] = $next_sections[$sectionStartKey];
					} else {
						$returnTrainSection[$absolutPositionKey] = $next_sections[$sectionStartKey];
					}
					break;
				} else if ($absolutPositionKey == array_key_last($returnTrainPositionChange) && abs($absolutPositionValue - floatval($cumulativeSectionLengthEnd[$sectionStartKey])) < $globalFloatingPointNumbersRoundingError) {
					$returnTrainSection[$absolutPositionKey] = $next_sections[$sectionStartKey];
					break;
				} else {
					debugMessage("Eine absolute Position konnte keine relativen Position in einem Abschnitt zugeordnet werden!");
				}
			}
		}
		return array($returnTrainPositionChange, $returnTrainSpeedChange, $returnTrainTimeChange, $returnTrainRelativePosition, $returnTrainSection, $returnIsSpeedChange);
	}
}

// Überprüft immer zwei benachbarte KeyPoints (0+1, 2+3, 4+5, 6+7 etc.)
// TODO: schöner schreiben die Funktion... sieht hässlich aus!
// TODO: Sollte auch bei einer ungeraden Anzahl an KeyPoints funktionieren, bitte aber nochmal überprüfen
function recalculateKeyPoints(array $tempKeyPoints) {
	$returnKeyPoints = array();
	$numberOfPairs = sizeof($tempKeyPoints) / 2;
	for($j = 0; $j < $numberOfPairs; $j++) {
		$i = $j * 2;
		$return = checkBetweenTwoKeyPoints($tempKeyPoints, $i);
		foreach ($return as $keyPoint) {
			array_push($returnKeyPoints, $keyPoint);
		}
	}
	return $returnKeyPoints;
}

function checkBetweenTwoKeyPoints(array $temKeyPoints, int $keyPointIndex) {

	global $trainPositionChange;
	global $trainSpeedChange;
	global $cumulativeSectionLengthStartMod;
	global $cumulativeSectionLengthEndMod;
	global $next_v_max_mod;
	global $verzoegerung;
	global $indexTargetSectionMod;

	$failedSections = array();
	$groupedFailedSections = array();
	$returnKeyPoints = array();
	$failedPositions = array();
	$failedSpeeds = array();

	foreach ($trainPositionChange as $trainPositionChangeKey => $trainPositionChangeValue) {
		if ($trainPositionChangeValue >= $temKeyPoints[$keyPointIndex]["position_0"] && $trainPositionChangeValue <= $temKeyPoints[$keyPointIndex + 1]["position_1"]) {
			foreach ($cumulativeSectionLengthStartMod as $cumulativeSectionLengthStartKey => $cumulativeSectionLengthStartValue) {
				if ($trainPositionChangeValue < $cumulativeSectionLengthStartValue) {
					if ($trainSpeedChange[$trainPositionChangeKey] > $next_v_max_mod[$cumulativeSectionLengthStartKey - 1]) {
						array_push($failedSections, ($cumulativeSectionLengthStartKey - 1));
						array_push($failedSpeeds, $trainSpeedChange[$trainPositionChangeKey]);
						$failedPositions[$trainPositionChangeKey] = $trainPositionChange[$trainPositionChangeKey];
					}
					break;
				} else if ($cumulativeSectionLengthStartKey == $indexTargetSectionMod) {
					if ($trainPositionChangeValue > $cumulativeSectionLengthStartValue) {
						if ($trainSpeedChange[$trainPositionChangeKey] > $next_v_max_mod[$cumulativeSectionLengthStartKey]) {
							array_push($failedSections, $cumulativeSectionLengthStartKey);
							array_push($failedSpeeds, $trainSpeedChange[$trainPositionChangeKey]);
							$failedPositions[$trainPositionChangeKey] = $trainPositionChange[$trainPositionChangeKey];
						}
						break;
					}
				}
			}
		}
	}

	// Alle Sections zwischen denn beiden KeyPoints, bei denen die v_max überschritten wird
	$failedSections = array_unique($failedSections);

	// Info: Der Index der failedPositions entspricht dem Index der trainChanges

	// Wenn es kein Fehler gibt, werden die beiden KeyPoints zurückgegeben und wen es einen Fehler gibt, wird der
	// erste der beiden KeyPoints im $returnKeyPoints gespeichert
	if (sizeof($failedSections) == 0) {
		return array($temKeyPoints[$keyPointIndex], $temKeyPoints[$keyPointIndex + 1]);
	} else {
		$returnKeyPoints[0]["speed_0"] = $temKeyPoints[$keyPointIndex]["speed_0"];
		$returnKeyPoints[0]["position_0"] = $temKeyPoints[$keyPointIndex]["position_0"];
	}

	// Einteilung der benachbarten failedSections in zusammenhängende Gruppen
	$previous = NULL;
	$index = 0;
	foreach($failedSections as $key => $value) {
		if($value > $previous + 1) {
			$index++;
		}
		$groupedFailedSections[$index][] = $value;
		$previous = $value;
	}

	// Durch alle Gruppen gehen
	foreach ($groupedFailedSections as $groupSectionsIndex => $groupSectionsValue) {
		$firstFailedPositionIndex = null;
		$lastFailedPositionIndex = null;
		$firstFailedPosition = null;
		$lastFailedPosition = null;
		$lastElement = array_key_last($returnKeyPoints);
		$failedSection = null;

		// Ermittlung der Section mit der kleinsten v_max von allen failed Sections in der Gruppe
		if (sizeof($groupSectionsValue) == 1) {
			$failedSection = $groupSectionsValue[0];
		} else {
			$slowestSpeed = 200;
			for ($i = 0; $i <= (sizeof($groupSectionsValue) - 1); $i++) {
				if ($next_v_max_mod[$groupSectionsValue[$i]] < $slowestSpeed) {
					$slowestSpeed = $next_v_max_mod[$groupSectionsValue[$i]];
					$failedSection = $groupSectionsValue[$i];
				}
			}
		}

		// Start- und Endposition der $failedSection
		$failedSectionStart = $cumulativeSectionLengthStartMod[$failedSection];
		$failedSectionEnd = $cumulativeSectionLengthEndMod[$failedSection];
		//$vMaxInFailedSection = $next_v_max[$failedSection];

		// Bestimmung der ersten und letzten Position, in der es in der failed Section
		// zu einer Geschwindigkeitsüberschreitung kommt
		foreach ($failedPositions as $failPositionIndex => $failPositionValue) {
			if ($failPositionValue > $failedSectionStart && $failPositionValue < $failedSectionEnd) {
				if ($firstFailedPositionIndex == null) {
					$firstFailedPositionIndex = $failPositionIndex;
				}
				$lastFailedPositionIndex = $failPositionIndex;
			}
		}

		// Bestimmung des letzten Punktes, bei dem die Geschwindigkeit noch nicht zu schnell war
		// Wenn der Punkt davor außerhalb der failedSection liegt => Startpunkt = Anfang der Section
		// Wenn der Punkt davor innnerhalb der failed Section liegt => Startpunkt = der Punkt davor
		if ($firstFailedPositionIndex != 0) {
			if ($trainPositionChange[$firstFailedPositionIndex - 1] < $failedSectionStart) {
				$firstFailedPosition = $failedSectionStart;
			} else {
				$firstFailedPosition = $trainPositionChange[$firstFailedPositionIndex - 1];
			}
		} else {
			$firstFailedPosition = $failedSectionStart;
		}

		// Bestimmung des ersten Punktes, bei dem die Geschwindigkeit nicht mehr zu schnell war
		// Beschreibung: siehe $firstFailedPosition Berechnung
		if ($lastFailedPositionIndex != array_key_last($trainPositionChange)) {
			if ($trainPositionChange[$lastFailedPositionIndex + 1] > $failedSectionEnd) {
				$lastFailedPosition = $failedSectionEnd;
			} else {
				$lastFailedPosition = $trainPositionChange[$lastFailedPositionIndex + 1];
			}
		} else {
			$lastFailedPosition = $failedSectionEnd;
		}
		$returnKeyPoints[$lastElement + 1]["position_1"] = $firstFailedPosition;
		$returnKeyPoints[$lastElement + 1]["speed_1"] = $next_v_max_mod[$failedSection];
		$returnKeyPoints[$lastElement + 2]["position_0"] = $lastFailedPosition;
		$returnKeyPoints[$lastElement + 2]["speed_0"] = $next_v_max_mod[$failedSection];
	}

	// Hinzufügen von dem "Ende"
	$returnKeyPoints[array_key_last($returnKeyPoints) + 1]["position_1"] = $temKeyPoints[$keyPointIndex + 1]["position_1"];
	$returnKeyPoints[array_key_last($returnKeyPoints)]["speed_1"] = $temKeyPoints[$keyPointIndex + 1]["speed_1"];
	$numberOfPairs = sizeof($returnKeyPoints) / 2;
	for($j = 0; $j < $numberOfPairs; $j++) {
		$i = $j * 2;
		$distance = $returnKeyPoints[$i + 1]["position_1"] - $returnKeyPoints[$i]["position_0"];
		$vMax = getVMaxBetweenTwoPoints($distance, $returnKeyPoints[$i]["speed_0"], $returnKeyPoints[$i + 1]["speed_1"]);
		// TODO: Der Teil kann weg, getVMaxBetweenTwoPoints() kann nicht -10 zurückgeben
		if ($vMax == -10) {
			$returnKeyPoints[$i]["position_0"] = $returnKeyPoints[$i + 1]["position_1"] - (getBrakeDistance($returnKeyPoints[$i]["speed_0"], $returnKeyPoints[$i + 1]["speed_1"], $verzoegerung));
			$distance = $returnKeyPoints[$i + 1]["position_1"] - $returnKeyPoints[$i]["position_0"];
			$vMax = getVMaxBetweenTwoPoints($distance, $returnKeyPoints[$i]["speed_0"], $returnKeyPoints[$i + 1]["speed_1"]);
		}
		$returnKeyPoints[$i]["speed_1"] = $vMax; //TODO
		$returnKeyPoints[$i]["position_1"] = $returnKeyPoints[$i]["position_0"] + getBrakeDistance($returnKeyPoints[$i]["speed_0"], $vMax, $verzoegerung);
		$returnKeyPoints[$i + 1]["speed_0"] = $vMax;
		$returnKeyPoints[$i + 1]["position_0"] = $returnKeyPoints[$i + 1]["position_1"] - getBrakeDistance($vMax, $returnKeyPoints[$i + 1]["speed_1"], $verzoegerung);
	}
	return $returnKeyPoints;
}

// Wenn ein Key Point beschleunigt und der nächste Key Point abbremst, wird
// die Geschwindigkeit zwischen den beiden KeyPoints als $v_maxBetweenKeyPoints
// gespeichert und als $v_minBetweenKeyPoints der größere Wert von
// $keyPoints[$i]["speed_0"] und $keyPoints[$i + 1]["speed_1"]
function checkIfTheSpeedCanBeDecreased() {

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
		if (isset($v_minBetweenKeyPoints)) {
			if ($v_minBetweenKeyPoints == 0 && $v_maxBetweenKeyPoints >= 10) {
				$v_minBetweenKeyPoints = 10;
			} else if ($v_minBetweenKeyPoints == 0 && $v_maxBetweenKeyPoints == 10) {
				$v_minBetweenKeyPoints = null;
			}
		}
		if ($v_minBetweenKeyPoints != null) {
			// Der KeyPoint_indexn beschreibt den ersten der beiden KeyPoints
			if ($v_minBetweenKeyPoints % 10 != 0) {
				$rest = $v_minBetweenKeyPoints % 10;
				$v_minBetweenKeyPoints = $v_minBetweenKeyPoints - $rest + 10;
			}
			array_push($returnPossibleSpeed, array("KeyPoint_index" => $i, "values" => range($v_minBetweenKeyPoints, $v_maxBetweenKeyPoints, 10)));
		}
	}

	if (sizeof($returnPossibleSpeed) > 0) {
		return array("possible" => true, "range" => $returnPossibleSpeed);
	} else {
		return array("possible" => false);
	}
}

function speedFineTuning(float $timeDiff, int $index) {

	global $keyPoints;
	global $verzoegerung;
	global $globalTimeOnOneSpeed;
	global $useMinTimeOnSpeed;

	$speed_0 = $keyPoints[$index]["speed_1"];
	$speed_1 = null;
	$availableDistance = $keyPoints[$index + 1]["position_0"] - $keyPoints[$index]["position_1"];
	$timeBetweenKeyPoints = $keyPoints[$index + 1]["time_0"] - $keyPoints[$index]["time_1"];
	$availableTime = $timeBetweenKeyPoints + $timeDiff;

	if ($keyPoints[$index + 1]["speed_1"] != 0) {
		$speed_1 = $keyPoints[$index + 1]["speed_1"];
		$lengthDifference = calculateDistanceforSpeedFineTuning($keyPoints[$index + 1]["speed_0"], $keyPoints[$index + 1]["speed_1"], $availableDistance, $availableTime);

		if ($useMinTimeOnSpeed) {
			if (distanceWithSpeedToTime($speed_0, $availableDistance - $lengthDifference) > $globalTimeOnOneSpeed && distanceWithSpeedToTime($speed_1, $lengthDifference) > $globalTimeOnOneSpeed) {
				$keyPoints[$index + 1]["position_0"] = $keyPoints[$index + 1]["position_0"] - $lengthDifference;
				$keyPoints[$index + 1]["position_1"] = $keyPoints[$index + 1]["position_1"] - $lengthDifference;
			}
		} else {
			$keyPoints[$index + 1]["position_0"] = $keyPoints[$index + 1]["position_0"] - $lengthDifference;
			$keyPoints[$index + 1]["position_1"] = $keyPoints[$index + 1]["position_1"] - $lengthDifference;
		}

	} else if ($keyPoints[$index + 1]["speed_0"] > 10) {
		$speed_1 = 10;
		$lengthDifference = calculateDistanceforSpeedFineTuning($keyPoints[$index + 1]["speed_0"],10, $availableDistance, $availableTime);
		if ($useMinTimeOnSpeed) {
			if (distanceWithSpeedToTime($speed_0, $availableDistance - $lengthDifference) > $globalTimeOnOneSpeed && distanceWithSpeedToTime($speed_1, $lengthDifference) > $globalTimeOnOneSpeed) {
				$firstKeyPoint = createKeyPoint(($keyPoints[$index + 1]["position_0"] - $lengthDifference),($keyPoints[$index + 1]["position_0"] - $lengthDifference + getBrakeDistance($keyPoints[$index + 1]["speed_0"],10, $verzoegerung)),$keyPoints[$index + 1]["speed_0"],10);
				$secondKeyPoint = createKeyPoint(($keyPoints[$index + 1]["position_1"] - getBrakeDistance(10, 0, $verzoegerung)),$keyPoints[$index + 1]["position_1"],10,$keyPoints[$index + 1]["speed_1"]);
				$keyPoints[$index + 1] = $secondKeyPoint;
				array_splice( $keyPoints, ($index + 1), 0, array($firstKeyPoint));
			}
		} else {
			$firstKeyPoint = createKeyPoint(($keyPoints[$index + 1]["position_0"] - $lengthDifference),($keyPoints[$index + 1]["position_0"] - $lengthDifference + getBrakeDistance($keyPoints[$index + 1]["speed_0"],10, $verzoegerung)),$keyPoints[$index + 1]["speed_0"],10);
			$secondKeyPoint = createKeyPoint(($keyPoints[$index + 1]["position_1"] - getBrakeDistance(10, 0, $verzoegerung)),$keyPoints[$index + 1]["position_1"],10,$keyPoints[$index + 1]["speed_1"]);
			$keyPoints[$index + 1] = $secondKeyPoint;
			array_splice( $keyPoints, ($index + 1), 0, array($firstKeyPoint));
		}
	}
}

function calculateDistanceforSpeedFineTuning(int $v_0, int $v_1, float $distance, float $time) : float {
	return $distance - (($distance - $time * $v_1 / 3.6)/($v_0 / 3.6 - $v_1 / 3.6)) * ($v_0 / 3.6);
}

// Sucht den KeyPoint der zu maximalen Geschwindigkeit beschleunigt
// Wenn die maximale Geschwindigkeit mehrfach erreciht wird, wird
// der letzte dieser KeyPoints genommen
//
// Zu dem Index wird auch noch die Speed Range abgespeichert wie bei
// checkIfTheSpeedCanBeDecreased()
// TODO: Kann man diese beiden Funktionen kombinieren?
function findMaxSpeed(array $speedDecrease) {
	$maxSpeed = 0;
	$minSpeed = 0;
	$keyPointIndex = null;

	for ($i = 0; $i < sizeof($speedDecrease["range"]); $i++) {
		if (max($speedDecrease["range"][$i]["values"]) >= $maxSpeed) {
			$maxSpeed = max($speedDecrease["range"][$i]["values"]);
			$minSpeed = min($speedDecrease["range"][$i]["values"]);
			$keyPointIndex = $speedDecrease["range"][$i]["KeyPoint_index"];
		}
	}
	return array("min_speed" => $minSpeed, "max_speed" => $maxSpeed, "first_key_point_index" => $keyPointIndex);
}

// Beim Start der Berechnung
function checkIfItsPossible() {
	global $currentSpeed;
	global $distanceToNextStop;
	global $verzoegerung;
	global $globalTimeOnOneSpeed;
	global $errorMinTimeOnSpeed;

	$minTimeIsPossible = true;
	if ($currentSpeed == 0) {
		$distance_0 = getBrakeDistance(0, 10, $verzoegerung);
		$distance_1 = getBrakeDistance(10, 0, $verzoegerung);
		$time = distanceWithSpeedToTime(10, $distanceToNextStop - $distance_0 - $distance_1);
		if ($time < $globalTimeOnOneSpeed) {
			$minTimeIsPossible = false;
			if ($errorMinTimeOnSpeed) {
				// TODO: Notbremsung einleiten/Zug bekommt Fehler
			}
			echo "Der Zug schafft es ohne Notbremsung am Ziel anzukommen, kann aber nicht die mind. Zeit einhalten.\n";
		}
		// Sollte egal sein, da der Zug schon vor der Berechnung auf v_0 war und somit die Zeit unbekannt ist.
	} else {
		if (getBrakeDistance($currentSpeed, 0, $verzoegerung) != $distanceToNextStop) {
			$distance_0 = getBrakeDistance($currentSpeed, 10, $verzoegerung);
			$distance_1 = getBrakeDistance(10, 0, $verzoegerung);
			$time = distanceWithSpeedToTime(10, $distanceToNextStop - $distance_0 - $distance_1);
			if ($time < $globalTimeOnOneSpeed) {
				$minTimeIsPossible = false;
				if ($errorMinTimeOnSpeed) {
					// TODO: Notbremsung einleiten/Zug bekommt Fehler
				}
				echo "Der Zug schafft es, ohne Notbremsung am Ziel anzukommen.\n";
			}
		}
	}
	return $minTimeIsPossible;
}

// TODO: General Options:
// 1. Soll generell überprüft werden, ob der Zug die mind. Zeit auf einer Geschwindigkeit einhält
// 2. Soll es zu einem Fehler kommen, wenn der Zug diese Bedingung nicht erfüllen kann?
//	  => Der Zug könnte ein Error-Statement bekommen
function toShortOnOneSpeed () {

	global $keyPoints;
	global $verzoegerung;

	$index = 0;
	$localKeyPoints = $keyPoints;
	$subsections = createSubsections($localKeyPoints);
	$breakesOnly = false;

	// Sobald in einer Section die Geschwindigkeit verändert werden müsste, wird erstmal die Geschwindigkeit angepasst und dann neu berechnet...
	while (toShortInSubsection($subsections)) {
		$breakesOnly = true;
		foreach ($subsections as $sectionKey => $sectionValue) {
			if ($sectionValue["failed"]) {
				if (!$sectionValue["brakes_only"]) {
					$breakesOnly = false;
				}
				$return = postponeSubsection($localKeyPoints, $sectionValue);
				if (!$return["fail"]) {
					$localKeyPoints = $return["keyPoints"];
				} else {
					if (!$sectionValue["brakes_only"]) {
						$localKeyPoints[$sectionValue["max_index"]]["speed_1"] -= 10;
						$localKeyPoints[$sectionValue["max_index"] + 1]["speed_0"] -= 10;
						$localKeyPoints[$sectionValue["max_index"]]["position_1"] = $localKeyPoints[$sectionValue["max_index"]]["position_0"] + getBrakeDistance($localKeyPoints[$sectionValue["max_index"]]["speed_0"], $localKeyPoints[$sectionValue["max_index"]]["speed_1"], $verzoegerung);
						$localKeyPoints[$sectionValue["max_index"] + 1]["position_0"] = $localKeyPoints[$sectionValue["max_index"] + 1]["position_1"] - getBrakeDistance($localKeyPoints[$sectionValue["max_index"] + 1]["speed_0"], $localKeyPoints[$sectionValue["max_index"] + 1]["speed_1"], $verzoegerung);
						$localKeyPoints = calculateTimeFromKeyPoints($localKeyPoints);
						$localKeyPoints = deleteDoubledKeyPoints($localKeyPoints);
						break;
					}
				}
			}
		}
		$localKeyPoints = calculateTimeFromKeyPoints($localKeyPoints);
		$localKeyPoints = array_values($localKeyPoints);
		$subsections = createSubsections($localKeyPoints);
		if ($breakesOnly) {
			break;
		}
	}
	$localKeyPoints = calculateTimeFromKeyPoints($localKeyPoints);
	$keyPoints = $localKeyPoints;
}


function postponeSubsectionOld (array $localKeyPoints, array $subsection) {

	//global $keyPoints;
	global $globalTimeOnOneSpeed;

	$keyPoints = $localKeyPoints;

	$indexMaxSection = array_search($subsection["max_index"], $subsection["indexes"]);
	$indexLastKeyPoint = array_key_last($subsection["indexes"]);

	if ($subsection["is_prev_section"]) {
		$timeDiff = $keyPoints[$subsection["indexes"][0]]["time_0"] - $keyPoints[$subsection["indexes"][0] - 1]["time_1"] - $globalTimeOnOneSpeed;
		if ($timeDiff < 0) {
			$positionDiff = abs($timeDiff) * $keyPoints[$subsection["indexes"][0]]["speed_0"] / 3.6;
			//$keyPoints[$subsection["indexes"][0]]["time_0"] -= $timeDiff;
			//$keyPoints[$subsection["indexes"][0]]["time_1"] -= $timeDiff;
			$keyPoints[$subsection["indexes"][0]]["position_0"] += $positionDiff;
			$keyPoints[$subsection["indexes"][0]]["position_1"] += $positionDiff;
			$keyPoints = calculateTimeFromKeyPoints();

		}
	}

	for ($i = 1; $i <= $indexMaxSection; $i++) {
		$timeDiff = $keyPoints[$subsection["indexes"][$i]]["time_0"] - $keyPoints[$subsection["indexes"][$i] - 1]["time_1"] - $globalTimeOnOneSpeed;
		if ($timeDiff < 0) {
			$positionDiff = abs($timeDiff) * $keyPoints[$subsection["indexes"][$i]]["speed_0"] / 3.6;
			//$keyPoints[$subsection["indexes"][$i]]["time_0"] -= $timeDiff;
			//$keyPoints[$subsection["indexes"][$i]]["time_1"] -= $timeDiff;
			$keyPoints[$subsection["indexes"][$i]]["position_0"] += $positionDiff;
			$keyPoints[$subsection["indexes"][$i]]["position_1"] += $positionDiff;
			$keyPoints = calculateTimeFromKeyPoints();
		}
	}

	if ($subsection["is_next_section"]) {
		$timeDiff = $keyPoints[$indexLastKeyPoint + 1]["time_0"] - $keyPoints[$indexLastKeyPoint]["time_1"] - $globalTimeOnOneSpeed;
		if ($timeDiff < 0) {
			$positionDiff = abs($timeDiff) * $keyPoints[$indexLastKeyPoint]["speed_1"] / 3.6;
			//$keyPoints[$indexLastKeyPoint]["time_0"] += $timeDiff;
			//$keyPoints[$indexLastKeyPoint]["time_1"] += $timeDiff;
			$keyPoints[$indexLastKeyPoint]["position_0"] -= $positionDiff;
			$keyPoints[$indexLastKeyPoint]["position_1"] -= $positionDiff;
			$keyPoints = calculateTimeFromKeyPoints();
		}
	}

	for ($i = $indexLastKeyPoint - 1; $i > $indexMaxSection; $i--) {
		$timeDiff = $keyPoints[$subsection["indexes"][$i + 1]]["time_0"] - $keyPoints[$subsection["indexes"][$i]]["time_1"] - $globalTimeOnOneSpeed;
		if ($timeDiff < 0) {
			$positionDiff = abs($timeDiff) * $keyPoints[$indexLastKeyPoint]["speed_1"] / 3.6;
			//$keyPoints[$subsection["indexes"][$i]]["time_0"] += $timeDiff;
			//$keyPoints[$subsection["indexes"][$i]]["time_1"] += $timeDiff;
			$keyPoints[$subsection["indexes"][$i]]["position_0"] -= $positionDiff;
			$keyPoints[$subsection["indexes"][$i]]["position_1"] -= $positionDiff;
			$keyPoints = calculateTimeFromKeyPoints();
		}
	}


	return $keyPoints;
}

function postponeSubsection (array $localKeyPoints, array $subsection) {

	global $globalTimeOnOneSpeed;
	global $verzoegerung;

	$deletedKeyPoints = array();
	$numberOfKeyPoints = sizeof($subsection["indexes"]);
	$indexMaxSection = array_search($subsection["max_index"], $subsection["indexes"]);
	$indexLastKeyPoint = array_key_last($subsection["indexes"]);

	if ($subsection["is_prev_section"]) {
		$timeDiff = $localKeyPoints[$subsection["indexes"][0]]["time_0"] - $localKeyPoints[$subsection["indexes"][0] - 1]["time_1"] - $globalTimeOnOneSpeed;
		if ($timeDiff < 0) {

			$positionDiff = abs($timeDiff) * $localKeyPoints[$subsection["indexes"][0]]["speed_0"] / 3.6;
			if (!($localKeyPoints[$subsection["indexes"][0]]["position_1"] + $positionDiff > $localKeyPoints[$subsection["indexes"][$indexMaxSection + 1]]["position_0"])) {
				$localKeyPoints[$subsection["indexes"][0]]["position_0"] += $positionDiff;
				$localKeyPoints[$subsection["indexes"][0]]["position_1"] += $positionDiff;
				// Es muss einen nächsten KeyPoint geben, da der Zug hier beschleunigt und er Ende der Strecke gilt v = 0
				if ($localKeyPoints[$subsection["indexes"][0]]["position_1"] > $localKeyPoints[$subsection["indexes"][0] + 1]["position_0"]) {
					array_push($deletedKeyPoints, $subsection["indexes"][0] + 1);
					$numberOfKeyPoints -= 1;
					$v_0 = $localKeyPoints[$subsection["indexes"][0]]["speed_0"];
					$v_1 = $localKeyPoints[$subsection["indexes"][0] + 1]["speed_1"];
					$localKeyPoints[$subsection["indexes"][0]]["position_1"] = $localKeyPoints[$subsection["indexes"][0]]["position_0"] + getBrakeDistance($v_0, $v_1, $verzoegerung);
					$localKeyPoints[$subsection["indexes"][0]]["speed_1"] = $v_1;
				}
				$localKeyPoints = calculateTimeFromKeyPoints($localKeyPoints, $deletedKeyPoints);
			}
		}
	}

	for ($i = 1; $i <= $indexMaxSection; $i++) {
		if (!in_array($subsection["indexes"][$i], $deletedKeyPoints)) {
			$timeDiff = $localKeyPoints[$subsection["indexes"][$i]]["time_0"] - $localKeyPoints[$subsection["indexes"][$i] - 1]["time_1"] - $globalTimeOnOneSpeed;
			if ($timeDiff < 0) {
				$positionDiff = abs($timeDiff) * $localKeyPoints[$subsection["indexes"][$i]]["speed_0"] / 3.6;
				if (!($localKeyPoints[$subsection["indexes"][$i]]["position_1"] + $positionDiff > $localKeyPoints[$subsection["indexes"][$indexMaxSection + 1]]["position_0"])) {
					$localKeyPoints[$subsection["indexes"][$i]]["position_0"] += $positionDiff;
					$localKeyPoints[$subsection["indexes"][$i]]["position_1"] += $positionDiff;
					if ($i < $indexMaxSection && $localKeyPoints[$subsection["indexes"][$i]]["position_1"] > $localKeyPoints[$subsection["indexes"][$i] + 1]["position_0"]) {
						array_push($deletedKeyPoints, ($subsection["indexes"][$i] + 1));
						$numberOfKeyPoints -= 1;
						$v_0 = $localKeyPoints[$subsection["indexes"][$i]]["speed_0"];
						$v_1 = $localKeyPoints[$subsection["indexes"][$i] + 1]["speed_1"];
						$localKeyPoints[$subsection["indexes"][$i]]["position_1"] = $localKeyPoints[$subsection["indexes"][$i]]["position_0"] + getBrakeDistance($v_0, $v_1, $verzoegerung);
						$localKeyPoints[$subsection["indexes"][$i]]["speed_1"] = $v_1;
					}
					$localKeyPoints = calculateTimeFromKeyPoints($localKeyPoints, $deletedKeyPoints);
				}
			}
		}
	}
	if ($subsection["is_next_section"]) {
		$timeDiff = $localKeyPoints[$subsection["indexes"][$indexLastKeyPoint] + 1]["time_0"] - $localKeyPoints[$subsection["indexes"][$indexLastKeyPoint]]["time_1"] - $globalTimeOnOneSpeed;
		if ($timeDiff < 0) {
			$positionDiff = abs($timeDiff) * $localKeyPoints[$indexLastKeyPoint]["speed_1"] / 3.6;
			if (!($localKeyPoints[$subsection["indexes"][$indexLastKeyPoint]]["position_0"] - $positionDiff < $localKeyPoints[$subsection["indexes"][$indexMaxSection]]["position_0"])) {
				$localKeyPoints[$subsection["indexes"][$indexLastKeyPoint]]["position_0"] -= $positionDiff;
				$localKeyPoints[$subsection["indexes"][$indexLastKeyPoint]]["position_1"] -= $positionDiff;
				if ($localKeyPoints[$subsection["indexes"][$indexLastKeyPoint]]["position_0"] < $localKeyPoints[$subsection["indexes"][$indexLastKeyPoint] - 1]["position_1"]) {
					array_push($deletedKeyPoints, ($subsection["indexes"][$indexLastKeyPoint] - 1));
					$numberOfKeyPoints -= 1;
					$v_0 = $localKeyPoints[$subsection["indexes"][$indexLastKeyPoint] - 1]["speed_0"];
					$v_1 = $localKeyPoints[$subsection["indexes"][$indexLastKeyPoint]]["speed_1"];
					$localKeyPoints[$subsection["indexes"][$indexLastKeyPoint]]["position_0"] = $localKeyPoints[$subsection["indexes"][$indexLastKeyPoint]]["position_1"] - getBrakeDistance($v_0, $v_1, $verzoegerung);
					$localKeyPoints[$subsection["indexes"][$indexLastKeyPoint]]["speed_0"] = $v_0;
				}
				$localKeyPoints = calculateTimeFromKeyPoints($localKeyPoints, $deletedKeyPoints);
			}
		}
	}

	for ($i = $indexLastKeyPoint - 1; $i > $indexMaxSection; $i--) {
		if (!in_array($i, $deletedKeyPoints)) {
			$timeDiff = $localKeyPoints[$subsection["indexes"][$i + 1]]["time_0"] - $localKeyPoints[$subsection["indexes"][$i]]["time_1"] - $globalTimeOnOneSpeed;
			if ($timeDiff < 0) {
				$positionDiff = abs($timeDiff) * $localKeyPoints[$indexLastKeyPoint]["speed_1"] / 3.6;
				if (!($localKeyPoints[$subsection["indexes"][$i]]["position_0"] - $positionDiff < $localKeyPoints[$subsection["indexes"][$indexMaxSection]]["position_0"])) {
					$localKeyPoints[$subsection["indexes"][$i]]["position_0"] -= $positionDiff;
					$localKeyPoints[$subsection["indexes"][$i]]["position_1"] -= $positionDiff;
					if ($i > ($indexMaxSection + 1) && $localKeyPoints[$subsection["indexes"][$i]]["position_0"] < $localKeyPoints[$subsection["indexes"][$i] - 1]["position_1"]) {
						array_push($deletedKeyPoints, ($subsection["indexes"][$i] - 1));
						$numberOfKeyPoints -= 1;
						$v_0 = $localKeyPoints[$subsection["indexes"][$i] - 1]["speed_0"];
						$v_1 = $localKeyPoints[$subsection["indexes"][$i]]["speed_1"];
						$localKeyPoints[$subsection["indexes"][$i]]["position_0"] = $localKeyPoints[$subsection["indexes"][$i]]["position_1"] - getBrakeDistance($v_0, $v_1, $verzoegerung);
						$localKeyPoints[$subsection["indexes"][$i]]["speed_0"] = $v_0;
					}
					$localKeyPoints = calculateTimeFromKeyPoints($localKeyPoints, $deletedKeyPoints);
				}
			}
		}
	}
	// Info: Erster und letzter KeyPoint aus $subsection["indexes"] sind auf jedenfall noch vorhanden
	$keys = $subsection["indexes"];
	foreach ($deletedKeyPoints as $index) {
		unset($keys[array_search($index, $keys)]);
	}
	$keys = array_values($keys);
	$failed = false;
	if ($subsection["is_prev_section"]) {
		// Geht, weil die subsections von hinten nach vorne kontrolliert werden.
		// Und weil Start- und End-KeyPoint immmer gleich bleiben.
		if ($localKeyPoints[$keys[0]]["time_0"] - $localKeyPoints[$keys[0] - 1]["time_1"] < $globalTimeOnOneSpeed) {
			$failed = true;
		}
	}
	if ($subsection["is_next_section"]) {
		// Geht, weil die subsections von hinten nach vorne kontrolliert werden.
		// Und weil Start- und End-KeyPoint immmer gleich bleiben.
		if ($localKeyPoints[end($keys) + 1]["time_0"] - $localKeyPoints[end($keys)]["time_1"] < $globalTimeOnOneSpeed) {
			$failed = true;
		}
	}
	for ($i = 1; $i < sizeof($keys); $i++)  {
		if ($localKeyPoints[$keys[$i]]["time_0"] - $localKeyPoints[$keys[$i - 1]]["time_1"] < $globalTimeOnOneSpeed) {
			$failed = true;
			break;
		}
	}
	if ($failed) {
		return array("fail" => true, "keyPoints" => array());
	} else {
		foreach ($deletedKeyPoints as $index) {
			unset($localKeyPoints[$index]);
		}
		return array("fail" => false, "keyPoints" => $localKeyPoints);
	}
}

// Es werden nur "komplette" Subsectionsbetrachtet. Der Zug MUSS beschleunigen und abbremsen!
function createSubsections (array $localKeyPoints) {
	global $globalTimeOnOneSpeed;

	$keyPoints = $localKeyPoints;
	$subsections = array();
	$subsection = array("max_index" => null, "indexes" => array(), "is_prev_section" => false, "is_next_section" => false);
	$maxIndex = null;

	// Wenn die erste Geschwindigkeit die maximale Geschwindigkeit der ersten Subsection ist.
	// TODO: Bei v_0 != 0 hat der erste KeyPoint v_0 == v_1... Kommt es da zu Konflikten?
	for($i = 0; $i < sizeof($keyPoints); $i++) {
		// subsection zu ende
		if ($i > 0) {
			if ($keyPoints[$i]["speed_0"] < $keyPoints[$i]["speed_1"] && $keyPoints[$i - 1]["speed_0"] > $keyPoints[$i - 1]["speed_1"] || $i == sizeof($keyPoints) - 1) {
				if ($i == sizeof($keyPoints) - 1) {
					array_push($subsection["indexes"], $i);
				}
				array_push($subsections, $subsection);
				$subsection["indexes"] = array();
			}
		}
		if ($keyPoints[$i]["speed_0"] < $keyPoints[$i]["speed_1"]) {
			$subsection["max_index"] = $i;
		}
		array_push($subsection["indexes"], $i);
	}

	// Check if middle section failed
	for ($i = 1; $i < sizeof($subsections); $i++) {
		//$firstIndex = $subsections[$i]["max_index"] + 1;
		$firstIndex = $subsections[$i]["indexes"][array_key_first($subsections[$i]["indexes"])];
		if ($keyPoints[$firstIndex]["time_0"] - $keyPoints[$firstIndex - 1]["time_1"] < $globalTimeOnOneSpeed) {
			$subsections[$i]["is_prev_section"] = true;
			$subsections[$i]["failed"] = true;
		} else {
			$subsections[$i]["is_prev_section"] = false;
			$subsections[$i]["failed"] = false;
		}
	}

	for ($i = sizeof($subsections) - 1; $i >= 0; $i--) {
		$isFirstSubsection = false;
		$isLastSubsection = false;
		if ($i == 0) {
			$isFirstSubsection = true;
		}
		if ($i == sizeof($subsections) - 1) {
			$isLastSubsection = true;
		}
		if ($subsections[$i]["failed"] || failOnSubsection($keyPoints, $subsections[$i])) {
			$subsections[$i]["failed"] = true;
			if (!$isFirstSubsection) {
				$subsections[$i]["is_prev_section"] = true;
			}
			if (!$isLastSubsection) {
				if (!$subsections[$i + 1]["is_prev_section"]) {
					$subsections[$i]["is_next_section"] = true;
				}
			}
		} else {
			$subsections[$i]["failed"] = false;
		}
	}

	// $subsections[$i]["max_index"] = null heißt, dass der Zug auf einer subsection nicht beschleunigt!
	for ($i = 0; $i < sizeof($subsections); $i++) {
		if (!isset($subsections[$i]["max_index"])) {
			$subsections[$i]["brakes_only"] = true;
			$subsections[$i]["max_index"] = $subsections[$i]["indexes"][0];
		} else {
			$subsections[$i]["brakes_only"] = false;
		}
	}
	$subsections = array_values($subsections);
	return array_reverse($subsections);
}

function failOnSubsection(array $keyPoints, array $subsection) {
	global $globalTimeOnOneSpeed;
	$failed = false;
	for ($i = 1; $i < sizeof($subsection["indexes"]); $i++)  {
		if ($keyPoints[$subsection["indexes"][$i]]["time_0"] - $keyPoints[$subsection["indexes"][$i] - 1]["time_1"] < $globalTimeOnOneSpeed) {
			$failed = true;
			break;
		}
	}
	return $failed;
}

function checkForPostponement(array $localKeyPoints, array $subsection) {
	global $globalTimeOnOneSpeed;

	$keyPoints = $localKeyPoints;
	$timeBeforeMax = 0;
	$timeAfterMax = 0;
	$foundShortSectionBeforeMax = false;
	$foundShortSectionAfterMax = false;
	$indexMaxSection = array_search($subsection["max_index"], $subsection["indexes"]);
	$indexLastKeyPoint = array_key_last($subsection["indexes"]);
	$timeOnMax = $keyPoints[$subsection["max_index"] + 1]["time_0"] - $keyPoints[$subsection["max_index"]]["time_1"] - $globalTimeOnOneSpeed;
	if ($timeOnMax < 0) {
		return false;
	}
	if ($subsection["is_prev_section"]) {
		$timeDiff = $keyPoints[$subsection["indexes"][0]]["time_0"] - $keyPoints[$subsection["indexes"][0] - 1]["time_1"] - $globalTimeOnOneSpeed;
		if ($timeDiff < 0) {
			$timeBeforeMax += $timeDiff;
			$foundShortSectionBeforeMax = true;
		}
	}

	if ($subsection["is_next_section"]) {
		$timeDiff = $keyPoints[$subsection["indexes"][array_key_last($subsection["indexes"])] + 1]["time_0"] - $keyPoints[$subsection["indexes"][array_key_last($subsection["indexes"])]]["time_1"] - $globalTimeOnOneSpeed;
		if ($timeDiff < 0) {
			$timeAfterMax += $timeDiff;
			$foundShortSectionAfterMax = true;
		}
	}

	for ($i = 1; $i <= $indexMaxSection; $i++) {
		if ($keyPoints[$subsection["indexes"][$i]]["time_0"] - $keyPoints[$subsection["indexes"][$i] - 1]["time_1"] < $globalTimeOnOneSpeed || $foundShortSectionBeforeMax) {
			$foundShortSectionBeforeMax = true;
			$timeBeforeMax += $keyPoints[$subsection["indexes"][$i]]["time_0"] - $keyPoints[$subsection["indexes"][$i] - 1]["time_1"] - $globalTimeOnOneSpeed;
		}
	}

	for ($i = $indexLastKeyPoint; $i > $indexMaxSection + 1; $i--) {
		if ($keyPoints[$subsection["indexes"][$i]]["time_0"] - $keyPoints[$subsection["indexes"][$i] - 1]["time_1"] < $globalTimeOnOneSpeed || $foundShortSectionAfterMax) {
			$foundShortSectionAfterMax = true;
			$timeAfterMax += $keyPoints[$subsection["indexes"][$i]]["time_0"] - $keyPoints[$subsection["indexes"][$i] - 1]["time_1"] - $globalTimeOnOneSpeed;
		}
	}

	if ($timeBeforeMax > 0) {
		$timeBeforeMax = 0;
	}

	if ($timeAfterMax > 0) {
		$timeAfterMax = 0;
	}

	// true = kann verschoben werden...
	if ($timeOnMax + $timeBeforeMax + $timeAfterMax >= 0) {
		return true;
	} else {
		return false;
	}
}

function toShortInSubsection (array $subsections) {
	$foundError = false;
	foreach ($subsections as $subsection) {
		if ($subsection["failed"]) {
			$foundError = true;
			break;
		}
	}
	return $foundError;
}

function createCumulativeSections ($indexCurrentSection, $indexTargetSection, $currentPosition, $targetPosition, $next_lengths) {
	$cumLength = array();
	$sum = 0;

	foreach ($next_lengths as $index => $value) {
		if ($index >= $indexCurrentSection) {
			$sum += $value;
			$cumLength[$index] = $sum;
		}
	}
	// Berechnung der kummulierten Start- und Endlängen der Abschnitte
	// TODO: Geht das auch, wenn Start- und Zielabschnitt der selbe sind?
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
	return array($cumulativeSectionLengthStart, $cumulativeSectionLengthEnd);
}

function getFahrplanAndPositionForOneTrain (int $trainID, int $zugID) {

	global $cacheZwischenhaltepunkte;
	global $allUsedTrains;

	$allUsedTrains[$trainID]["next_betriebsstellen_data"] = array();
	$keysZwischenhalte = array_keys($cacheZwischenhaltepunkte);

	// Get timetable data
	$nextBetriebsstellen = getNextBetriebsstellen($zugID);

	if ($zugID != null && sizeof($nextBetriebsstellen) != 0) {
		for ($i = 0; $i < sizeof($nextBetriebsstellen); $i++) {
			if (sizeof(explode("_", $nextBetriebsstellen[$i])) != 2) {
				$allUsedTrains[$trainID]["next_betriebsstellen_data"][$i]["is_on_fahrstrasse"] = false;
				$allUsedTrains[$trainID]["next_betriebsstellen_data"][$i]["betriebstelle"] = $nextBetriebsstellen[$i];
				$allUsedTrains[$trainID]["next_betriebsstellen_data"][$i]["zeiten"] = getFahrplanzeiten($nextBetriebsstellen[$i], $zugID);
				$allUsedTrains[$trainID]["next_betriebsstellen_data"][$i]["fahrplanhalt"] = true;
			} else if(in_array($nextBetriebsstellen[$i], $keysZwischenhalte)) {
				$allUsedTrains[$trainID]["next_betriebsstellen_data"][$i]["is_on_fahrstrasse"] = false;
				$allUsedTrains[$trainID]["next_betriebsstellen_data"][$i]["betriebstelle"] = $nextBetriebsstellen[$i];
				$allUsedTrains[$trainID]["next_betriebsstellen_data"][$i]["zeiten"] = getFahrplanzeiten($nextBetriebsstellen[$i], $zugID);
				$allUsedTrains[$trainID]["next_betriebsstellen_data"][$i]["fahrplanhalt"] = false;
			}
		}
		$allUsedTrains[$trainID]["next_betriebsstellen_data"] = array_values($allUsedTrains[$trainID]["next_betriebsstellen_data"]);
	} else {
		$allUsedTrains[$trainID]["next_betriebsstellen_data"] = array();
	}

	foreach ($allUsedTrains[$trainID]["next_betriebsstellen_data"] as $betriebsstelleKey => $betriebsstelleValue) {
		if ($allUsedTrains[$trainID]["next_betriebsstellen_data"][$betriebsstelleKey]["zeiten"]["abfahrt_soll"] != null) {
			$allUsedTrains[$trainID]["next_betriebsstellen_data"][$betriebsstelleKey]["zeiten"]["abfahrt_soll_timestamp"] = getUhrzeit($betriebsstelleValue["zeiten"]["abfahrt_soll"], "simulationszeit", null, array("inputtyp" => "h:i:s"));
		} else {
			$allUsedTrains[$trainID]["next_betriebsstellen_data"][$betriebsstelleKey]["zeiten"]["abfahrt_soll_timestamp"] = null;
		}
		if ($allUsedTrains[$trainID]["next_betriebsstellen_data"][$betriebsstelleKey]["zeiten"]["ankunft_soll"] != null) {
			$allUsedTrains[$trainID]["next_betriebsstellen_data"][$betriebsstelleKey]["zeiten"]["ankunft_soll_timestamp"] = getUhrzeit($betriebsstelleValue["zeiten"]["ankunft_soll"], "simulationszeit", null, array("inputtyp" => "h:i:s"));
		} else {
			$allUsedTrains[$trainID]["next_betriebsstellen_data"][$betriebsstelleKey]["zeiten"]["ankunft_soll_timestamp"] = null;
		}
		$allUsedTrains[$trainID]["next_betriebsstellen_data"][$betriebsstelleKey]["zeiten"]["verspaetung"] = 0;
	}
	/*
	global $fmaToInfra;
	global $infraToFma;
	global $cacheInfraLaenge;
	global $timeDifferenceGetUhrzeit;
	global $allTrains;

	$returnArray = array();
	$checkAllTrains = true;

	$allTrains[$id]["error"] = array();
	$allTrains[$id]["next_sections"] = array();
	$allTrains[$id]["next_lenghts"] = array();
	$allTrains[$id]["next_v_max"] = array();
	$allTrains[$id]["next_stop"] = array();
	$values = getFahrzeugZugIds(array($allTrains[$id]["id"]));
	if (sizeof($values) != 0) {
		$value = $values[array_key_first($values)];
		$allTrains[$id]["zug_id"] = intval($value["zug_id"]);
		$allTrains[$id]["operates_on_timetable"] = 1;

	} else {
		$allTrains[$id]["zug_id"] = null;
		$allTrains[$id]["operates_on_timetable"] = 0;
	}

	// Get next Betriebsstellen
	$allTrains[$id]["next_betriebsstellen_data"] = array();
	$zug_id = intval($allTrains[$id]["zug_id"]);

	if ($zug_id != 0) {
		$nextBetriebsstellen = getNextBetriebsstellen($zug_id);
		if (sizeof($nextBetriebsstellen) != 0) {
			for ($i = 0; $i < sizeof($nextBetriebsstellen); $i++) {
				if (sizeof(explode("_", $nextBetriebsstellen[$i])) != 2) {
					$allTrains[$id]["next_betriebsstellen_data"][$i]["betriebstelle"] = $nextBetriebsstellen[$i];
					$allTrains[$id]["next_betriebsstellen_data"][$i]["zeiten"] = getFahrplanzeiten($nextBetriebsstellen[$i], $zug_id);
				}
			}
		} else {
			$allTrains[$id]["next_betriebsstellen_data"] = null;
		}
	} else {
		$allTrains[$id]["next_betriebsstellen_data"] = array();

	}
	$allTrains[$id]["next_betriebsstellen_data"] = array_values($allTrains[$id]["next_betriebsstellen_data"]);

	foreach ($allTrains[$id]["next_betriebsstellen_data"] as $betriebsstelleIndex => $betriebsstelleValue) {
		if ($betriebsstelleValue["zeiten"] != false) {
			if ($betriebsstelleValue["zeiten"]["abfahrt_soll"] != null) {
				$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["abfahrt_soll_timestamp"] = getUhrzeit($betriebsstelleValue["zeiten"]["abfahrt_soll"], "simulationszeit", $timeDifferenceGetUhrzeit, array("inputtyp" => "h:i:s"));
			} else {
				$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["abfahrt_soll_timestamp"] = null;
			}

			if ($betriebsstelleValue["zeiten"]["ankunft_soll"] != null) {
				$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["ankunft_soll_timestamp"] = getUhrzeit($betriebsstelleValue["zeiten"]["ankunft_soll"], "simulationszeit", $timeDifferenceGetUhrzeit, array("inputtyp" => "h:i:s"));
			} else {
				$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["ankunft_soll_timestamp"] = null;
			}

		}
	}

	foreach ($returnArray as $trainIndex => $trainValue) {
		$returnArray[$trainIndex]["notverzoegerung"] = 2;
	}

	foreach ($allTrains[$id]["next_betriebsstellen_data"] as $betriebsstelleIndex => $betriebsstelleValue) {
		if ($betriebsstelleValue["zeiten"]["abfahrt_soll_timestamp"] != null && $betriebsstelleValue["zeiten"]["ankunft_soll_timestamp"] != null) {
			$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["haltezeit"] = $betriebsstelleValue["zeiten"]["abfahrt_soll_timestamp"] - $betriebsstelleValue["zeiten"]["ankunft_soll_timestamp"];
			if (($betriebsstelleValue["zeiten"]["abfahrt_soll_timestamp"] - $betriebsstelleValue["zeiten"]["ankunft_soll_timestamp"]) > 0) {
				$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["is_halt"] = true;
			} else {
				$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["is_halt"] = false;
			}
		} else {
			$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["haltezeit"] = 0;
			$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["is_halt"] = true;
		}
		$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["verspaetung"] = 0;
	}
	*/
}

function toArr(){
	return func_get_args();
}
