<?php

// Berechnet den Fahrtverlauf eines Fahrzeugs
function updateNextSpeed (array $train, float $startTime, float $endTime, int $targetSectionPara, int $targetPositionPara, bool $reachedBetriebsstelle, string $indexReachedBetriebsstelle, bool $wendet, bool $freieFahrt, array $allreachedInfras) {

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
	$next_sections = $train['next_sections'];
	$next_lengths = $train['next_lenghts'];
	$next_v_max = $train['next_v_max'];
	$verzoegerung = $train['verzoegerung'];
	$notverzoegerung = $train['notverzoegerung'];
	$train_v_max = $train['v_max'];
	$currentSection = $train['current_section'];
	$currentPosition = $train['current_position'];
	$currentSpeed = $train['current_speed'];
	$train_length = $train['zuglaenge'];
	$targetSection = $targetSectionPara;
	$targetPosition = $targetPositionPara;
	$targetSpeed = 0;
	$targetTime = $endTime;
	$indexCurrentSection = null;
	$indexTargetSection = null;
	$timeToNextStop = null;
	$maxTimeToNextStop = $targetTime - $startTime;
	$maxSpeedNextSections = 120;

	if (!$freieFahrt) {
		$targetBetriebsstelle = $train['next_betriebsstellen_data'][$indexReachedBetriebsstelle]['betriebstelle'];
	} else {
		$targetBetriebsstelle = $cacheSignalIDToBetriebsstelle[intval($indexReachedBetriebsstelle)];
	}

	// Überprüfung, ob das Fahrzeug bereits am Ziel steht
	if ($targetSection == $currentSection && $targetPosition == $currentPosition) {
		if ($currentSpeed > 0) {
			emergencyBreak($train['id']);
		} else {
			$allTimes[$train['adresse']] = array();
			return 0;
		}
	}

	// Wenn ein Infra-Abschnitt eine Geschwindigkeit zulässt,
	// die größer als die zulässige Höchstgeschwindigkeit des
	// Fahrzeugs ist, wird die Geschwindigkeit des
	// Infra-Abschnitts reduziert.
	if ($train_v_max != null) {
		foreach ($next_sections as $sectionKey => $sectionValue) {
			if ($next_v_max[$sectionKey] > $train_v_max) {
				$next_v_max[$sectionKey] = $train_v_max;
			}
		}
	}

	// Ermittlung der Indexe des Start- und Zielabschnitts
	foreach ($next_sections as $sectionKey => $sectionValue) {
		if ($sectionValue == $currentSection) {
			$indexCurrentSection = $sectionKey;
		}

		if ($sectionValue == $targetSection) {
			$indexTargetSection = $sectionKey;
		}
	}

	// Berechnet die kumulierten Abstände jedes Infra-Abschnitts für den Anfang
	// und das Ende der Infra-Abschnitt von der aktuellen Fahrzeugposition
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

	// Ermittlung der Distanz bis zum Ziel
	$distanceToNextStop = $cumulativeSectionLengthEnd[$indexTargetSection];
	if (getBrakeDistance($currentSpeed, $targetSpeed, $verzoegerung)> $distanceToNextStop && $currentSpeed != 0) {
		if (!isset($distanceToNextStop)) {
			emergencyBreak($train['id']);

			return 0;
		} else {
			emergencyBreak($train['id'], $distanceToNextStop);

			return 0;
		}
	}

	// Ermittlung der Längen und zulässigen Höchstgeschwindigkeiten der
	// Infra-Abschnitte inkl. Zuglänge
	global $next_v_max_mod;
	global $next_lengths_mod;
	global $indexCurrentSectionMod;
	global $indexTargetSectionMod;

	$next_v_max_mod = array();
	$next_lengths_mod = array();
	$indexCurrentSectionMod = null;
	$indexTargetSectionMod = null;

	if ($indexCurrentSection == $indexTargetSection) {
		$next_lengths_mod = $next_lengths;
		$next_v_max_mod = $next_v_max;
		$indexCurrentSectionMod = $indexCurrentSection;
		$indexTargetSectionMod = $indexTargetSection;
		$next_lengths_mod[$indexTargetSectionMod] = $targetPosition;
	} else {
		$startPosition = 0;
		$indexStartPosition = null;
		$indexEndPosition = null;

		do {
			$reachedTargetSection = false;

			for ($j = $indexCurrentSection; $j <= $indexTargetSection; $j++) {
				if ($startPosition >= $cumLengthStart[$j] && $startPosition < $cumLengthEnd[$j]) {
					$indexStartPosition = $j;
				}
			}

			$endPosition = $cumLengthEnd[$indexStartPosition] + $train_length;
			$current_v_max = $next_v_max[$indexStartPosition];

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

		$indexCurrentSectionMod = array_key_first($next_lengths_mod);
		$indexTargetSectionMod = array_key_last($next_lengths_mod);
	}

	// Berechnet die kumulierten Abstände jedes Infra-Abschnitts für den Anfang
	// und das Ende der Infra-Abschnitt von der aktuellen Fahrzeugposition
	// inkl. der Fahrzeuglänge
	$returnCumulativeSectionsMod = createCumulativeSections($indexCurrentSectionMod, $indexTargetSectionMod, $currentPosition, $next_lengths_mod[$indexTargetSectionMod], $next_lengths_mod);

	global $cumulativeSectionLengthStartMod;
	global $cumulativeSectionLengthEndMod;

	$cumulativeSectionLengthStartMod = $returnCumulativeSectionsMod[0];
	$cumulativeSectionLengthEndMod = $returnCumulativeSectionsMod[1];
	$minTimeOnSpeedIsPossible = checkIfItsPossible($train['id']);
	$v_maxFirstIteration = getVMaxBetweenTwoPoints($distanceToNextStop, $currentSpeed, $targetSpeed, $train['id']);

	// Anpassung an die maximale Geschwindigkeit auf der Strecke
	for ($i = $indexCurrentSection; $i <= $indexTargetSection; $i++) {
		if ($next_v_max[$i] < $maxSpeedNextSections) {
			$maxSpeedNextSections = $next_v_max[$i];
		}
	}
	if ($maxSpeedNextSections < $v_maxFirstIteration) {
		$v_maxFirstIteration = $maxSpeedNextSections;
	}

	// Key Points für die erste Iteration erstellen.
	array_push($keyPoints, createKeyPoint(0, getBrakeDistance($currentSpeed, $v_maxFirstIteration, $verzoegerung), $currentSpeed, $v_maxFirstIteration));
	array_push($keyPoints, createKeyPoint(($distanceToNextStop - getBrakeDistance($v_maxFirstIteration, $targetSpeed, $verzoegerung)), $distanceToNextStop, $v_maxFirstIteration, $targetSpeed));

	//$trainChange = convertKeyPointsToTrainChangeArray($keyPoints);
	$trainChange = createTrainChanges(true);
	$trainPositionChange = $trainChange[0];
	$trainSpeedChange = $trainChange[1];
	$speedOverPositionAllIterations = array();

	// Überprüfung, ob das Fahrzeug in Infra-Abschnitten zu schnell ist
	while (checkIfTrainIsToFastInCertainSections()['failed']) {
		$tempKeyPoints = $keyPoints;

		// Berechnung der Echtzeitdaten
		$trainChange = createTrainChanges(true);
		$trainPositionChange = $trainChange[0];
		$trainSpeedChange = $trainChange[1];

		// Hinzufügen der Echtzeitdaten des vorherigen Iterationsschritt
		// für die Visualisierung
		array_push($speedOverPositionAllIterations, array($trainPositionChange, $trainSpeedChange));

		// Überprüfung, ob durch den Fahrtverlauf zulässige Höchst-
		// geschwindigkeiten überschritten werden
		$keyPoints = recalculateKeyPoints($tempKeyPoints, $train['id']);
		$localKeyPointsTwo = array();

		// Entfernen von doppelten $keyPoints
		for ($i = 0; $i < sizeof($keyPoints); $i++) {
			if ($i < sizeof($keyPoints) - 1) {
				if (!($keyPoints[$i]['speed_0'] == $keyPoints[$i]['speed_1'] && $keyPoints[$i]['speed_0'] == $keyPoints[$i + 1]['speed_0'] && $keyPoints[$i]['speed_0'] == $keyPoints[$i + 1]['speed_1'])) {
					array_push($localKeyPointsTwo, $keyPoints[$i]);
				} else {
					$i++;
				}
			} else {
				array_push($localKeyPointsTwo, $keyPoints[$i]);
			}
		}

		// Berechnung der Echtzeitdaten nach der Neukalibrierung
		$keyPoints = $localKeyPointsTwo;
		$trainChange = createTrainChanges(true);
		$trainPositionChange = $trainChange[0];
		$trainSpeedChange = $trainChange[1];
	}

	// Fügt die aktuelle Zeit zum ersten $keyPoint hinzu
	$keyPoints[0]['time_0'] = $startTime;
	$keyPoints = deleteDoubledKeyPoints($keyPoints);
	$keyPoints = calculateTimeFromKeyPoints();

	if ($useMinTimeOnSpeed && $minTimeOnSpeedIsPossible) {
		array_push($speedOverPositionAllIterations, array($trainPositionChange, $trainSpeedChange));
		toShortOnOneSpeed();
	}

	// Ermittlung der Echtzeitdaten
	$trainChange = createTrainChanges(true);
	$trainPositionChange = $trainChange[0];
	$trainSpeedChange = $trainChange[1];
	$timeToNextStop = end($keyPoints)['time_1'] - $keyPoints[0]['time_0'];

	// Überprüfung, ob das Fahrzeug mit einer Verspätung am Ziel ankommt.
	// Fahrzeuge, die ohne Fahrplan fahren, werden nicht betrachtet.
	if (!$freieFahrt) {
		if ($timeToNextStop > $maxTimeToNextStop) {
			echo 'Der Zug mit der Adresse ', $train['adresse'], ' wird mit einer Verspätung von ', number_format($timeToNextStop - $maxTimeToNextStop, 2), ' Sekunden im nächsten planmäßigen Halt (', $targetBetriebsstelle,") ankommen.\n";
		} else {
			echo 'Aktuell benötigt der Zug mit der Adresse ', $train['adresse'], ' ', number_format($timeToNextStop, 2), ' Sekunden, obwohl er ', number_format($maxTimeToNextStop, 2), " Sekunden zur Verfügung hat.\n";

			if ($slowDownIfTooEarly) {
				echo 'Evtl. könnte der Zug zwischendurch die Geschwindigkeit verringern, um Energie zu sparen.';

				array_push($speedOverPositionAllIterations, array($trainPositionChange, $trainSpeedChange));
				$keyPointsPreviousStep = array();
				$finish = false;
				$possibleSpeedRange = null;
				$returnSpeedDecrease = checkIfTheSpeedCanBeDecreased();

				while ($returnSpeedDecrease['possible'] && !$finish) {
					$possibleSpeedRange = findMaxSpeed($returnSpeedDecrease);

					if ($possibleSpeedRange['min_speed'] == $possibleSpeedRange['max_speed']) {
						break;
					}

					$localKeyPoints = $keyPoints;
					$newCalculatedTime = null;
					$newKeyPoints = null;

					for ($i = $possibleSpeedRange['max_speed']; $i >= $possibleSpeedRange['min_speed']; $i = $i - 10) {
						$localKeyPoints[$possibleSpeedRange['first_key_point_index']]['speed_1'] = $i;
						$localKeyPoints[$possibleSpeedRange['first_key_point_index'] + 1]['speed_0'] = $i;
						$localKeyPoints[$possibleSpeedRange['first_key_point_index']]['position_1'] = (getBrakeDistance($localKeyPoints[$possibleSpeedRange['first_key_point_index']]['speed_0'], $i, $verzoegerung) + $localKeyPoints[$possibleSpeedRange['first_key_point_index']]['position_0']);
						$localKeyPoints[$possibleSpeedRange['first_key_point_index'] + 1]['position_0'] = ($localKeyPoints[$possibleSpeedRange['first_key_point_index'] + 1]['position_1'] - getBrakeDistance($i, $localKeyPoints[$possibleSpeedRange['first_key_point_index'] + 1]['speed_1'], $verzoegerung));
						$localKeyPoints = calculateTimeFromKeyPoints($localKeyPoints);
						$newCalculatedTime = $localKeyPoints[array_key_last($localKeyPoints)]['time_1'];

						if ($i == 10) {
							if ($newCalculatedTime > $maxTimeToNextStop) {
								$localKeyPoints[$possibleSpeedRange['first_key_point_index']]['speed_1'] = $i + 10;
								$localKeyPoints[$possibleSpeedRange['first_key_point_index'] + 1]['speed_0'] = $i + 10;
								$localKeyPoints[$possibleSpeedRange['first_key_point_index']]['position_1'] = (getBrakeDistance($localKeyPoints[$possibleSpeedRange['first_key_point_index']]['speed_0'], ($i + 10), $verzoegerung) + $localKeyPoints[$possibleSpeedRange['first_key_point_index']]['position_0']);
								$localKeyPoints[$possibleSpeedRange['first_key_point_index'] + 1]['position_0'] = ($localKeyPoints[$possibleSpeedRange['first_key_point_index'] + 1]['position_1'] - getBrakeDistance(($i + 10), $localKeyPoints[$possibleSpeedRange['first_key_point_index'] + 1]['speed_1'], $verzoegerung));
							}

							$finish = true;
							$newKeyPoints = $localKeyPoints;
							break;
						}
						if (($newCalculatedTime - $startTime) > $maxTimeToNextStop) {
							if ($i == $possibleSpeedRange['max_speed']) {
								$localKeyPoints = $keyPointsPreviousStep;
								$localKeyPoints = deleteDoubledKeyPoints($localKeyPoints);
								$keyPoints = $localKeyPoints;
								$finish = true;
								break;
							}
							$localKeyPoints[$possibleSpeedRange['first_key_point_index']]['speed_1'] = $i + 10;
							$localKeyPoints[$possibleSpeedRange['first_key_point_index'] + 1]['speed_0'] = $i + 10;
							$localKeyPoints[$possibleSpeedRange['first_key_point_index']]['position_1'] = (getBrakeDistance($localKeyPoints[$possibleSpeedRange['first_key_point_index']]['speed_0'], ($i + 10), $verzoegerung) + $localKeyPoints[$possibleSpeedRange['first_key_point_index']]['position_0']);
							$localKeyPoints[$possibleSpeedRange['first_key_point_index'] + 1]['position_0'] = ($localKeyPoints[$possibleSpeedRange['first_key_point_index'] + 1]['position_1'] - getBrakeDistance(($i + 10), $localKeyPoints[$possibleSpeedRange['first_key_point_index'] + 1]['speed_1'], $verzoegerung));
							$newKeyPoints = $localKeyPoints;
							$finish = true;
							$keyPoints = $localKeyPoints;

							break;
						}
						if ($i == $possibleSpeedRange['min_speed']) {
							$newKeyPoints = $localKeyPoints;
							$newKeyPoints = deleteDoubledKeyPoints($newKeyPoints);
							$keyPoints = $newKeyPoints;
							break;
						}
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

				if ($useSpeedFineTuning && $returnSpeedDecrease['possible']) {
					$trainChangeReturn = createTrainChanges(true);
					$trainPositionChange = $trainChangeReturn[0];
					$trainSpeedChange = $trainChangeReturn[1];
					$newCalculatedTime = $keyPoints[array_key_last($keyPoints)]['time_1'];
					speedFineTuning(($maxTimeToNextStop - ($newCalculatedTime - $startTime)), $returnSpeedDecrease['range'][array_key_last($returnSpeedDecrease['range'])]['KeyPoint_index']);
				}

				$keyPoints = calculateTimeFromKeyPoints();
				$timeToNextStop = end($keyPoints)['time_1'] - $keyPoints[0]['time_0'];

				echo "\nDurch die Anpassung der Geschwindigkeit benötigt der Zug mit der Adresse ", $train['adresse'], ' jetzt ', number_format($timeToNextStop, 2), " Sekunden bis\n";

				if (abs($timeToNextStop - $maxTimeToNextStop) < $globalFloatingPointNumbersRoundingError) {
					echo 'zum nächsten planmäßigen Halt (', $targetBetriebsstelle, ") und wird diesen genau pünktlich erreichen.\n";
				} else if (($timeToNextStop - $maxTimeToNextStop) > 0) {
					echo 'zum nächsten planmäßigen Halt (', $targetBetriebsstelle, ') und wird diesen mit einer Verspätung von ', number_format($timeToNextStop - $maxTimeToNextStop, 2), " Sekunden erreichen.\n";
				} else {
					echo 'zum nächsten planmäßigen Halt (', $targetBetriebsstelle, ') und wird diesen ', number_format($timeToNextStop - $maxTimeToNextStop, 2), " Sekunden zu früh erreichen.\n";
				}
			} else {
				echo "Dadurch, dass \$slowDownIfTooEarly = true ist, wird das Fahrzeug ", number_format($maxTimeToNextStop - $timeToNextStop, 2), ' Sekunden zu früh am Ziel ankommen.';
			}
		}
	} else {
		echo 'Der Zug mit der Adresse ', $train['adresse'], ' fährt aktuell ohne Fahrplan bis zum nächsten auf Halt stehendem Signal (Signal ID: ', $indexReachedBetriebsstelle, ', Betriebsstelle: ', $targetBetriebsstelle,").\n";
	}

	// Berechnung der Echtzeitdaten
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
		array_push($allreachedInfrasIndex, $value['index']);
		array_push($allreachedInfrasID, $value['infra']);
	}

	foreach ($trainPositionChange as $key => $value) {
		$trainBetriebsstelleName[$key] = $targetBetriebsstelle;
		if (array_key_last($trainPositionChange) != $key) {
			$trainTargetReached[$key] = false;
			$trainWendet[$key] = false;
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
	$adress = $train['adresse'];
	$trainID = array();
	$id = $train['id'];

	foreach ($trainPositionChange as $key => $value) {
		$trainID[$key] = $id;
	}

	foreach ($trainPositionChange as $trainPositionChangeIndex => $trainPositionChangeValue) {
		array_push($returnArray, array('live_position' => $trainPositionChangeValue,
			'live_speed' => $trainSpeedChange[$trainPositionChangeIndex],
			'live_time' => $trainTimeChange[$trainPositionChangeIndex],
			'live_relative_position' => $trainRelativePosition[$trainPositionChangeIndex],
			'live_section' => $trainSection[$trainPositionChangeIndex],
			'live_is_speed_change' => $trainIsSpeedChange[$trainPositionChangeIndex],
			'live_target_reached' => $trainTargetReached[$trainPositionChangeIndex],
			'id' => $trainID[$trainPositionChangeIndex],
			'wendet' => $trainWendet[$trainPositionChangeIndex],
			'betriebsstelle' => $trainBetriebsstelleName[$trainPositionChangeIndex],
			'live_all_targets_reached' => $allReachedTargets[$trainPositionChangeIndex]));
	}

	$allTimes[$adress] = $returnArray;
	safeTrainChangeToJSONFile($indexCurrentSection, $indexTargetSection, $indexCurrentSectionMod, $indexTargetSectionMod, $speedOverPositionAllIterations);

	return (end($trainTimeChange) - $trainTimeChange[0]) - ($endTime - $startTime);
}

// Ermittelt die maximale Geschwindigkeit zwischen zwei Punkten
function getVMaxBetweenTwoPoints(float $distance, int $v_0, int $v_1, int  $id) {

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
			echo 'Der zug müsste langsamer als 10 km/h fahren, um das Ziel zu erreichen.';
		} else {
			emergencyBreak($id);
		}
	} else {
		if ($v_0 == $v_1 && max($v_max) < $v_0) {
			$v_max = array($v_0);
		}
	}

	return max($v_max);
}

// Erstellt einen $keyPoint
function createKeyPoint (float $position_0, float $position_1, int $speed_0, int $speed_1) {
	return array('position_0' => $position_0, 'position_1' => $position_1, 'speed_0' => $speed_0, 'speed_1' => $speed_1);
}

// Ermittelt aus den $keyPoint die Echtzeitdaten
// (nur Geschwindigkeit und Position)
function convertKeyPointsToTrainChangeArray (array $keyPoints) {

	global $verzoegerung;

	$trainSpeedChangeReturn = array();
	$trainPositionChnageReturn = array();
	array_push($trainPositionChnageReturn, $keyPoints[0]['position_0']);
	array_push($trainSpeedChangeReturn, $keyPoints[0]['speed_0']);

	for ($i = 0; $i <= (sizeof($keyPoints) - 2); $i++) {
		if ($keyPoints[$i]['speed_0'] < $keyPoints[$i]['speed_1']) {
			for ($j = $keyPoints[$i]['speed_0']; $j < $keyPoints[$i]['speed_1']; $j = $j + 2) {
				array_push($trainPositionChnageReturn, (end($trainPositionChnageReturn) + getBrakeDistance($j, ($j + 2), $verzoegerung)));
				array_push($trainSpeedChangeReturn, ($j + 2));
			}
		} elseif ($keyPoints[$i]['speed_0'] > $keyPoints[$i]['speed_1']) {
			for ($j = $keyPoints[$i]['speed_0']; $j > $keyPoints[$i]['speed_1']; $j = $j - 2) {
				array_push($trainPositionChnageReturn, (end($trainPositionChnageReturn) + getBrakeDistance($j, ($j - 2), $verzoegerung)));
				array_push($trainSpeedChangeReturn, ($j - 2));
			}
		}
		array_push($trainPositionChnageReturn, $keyPoints[$i + 1]['position_0']);
		array_push($trainSpeedChangeReturn, $keyPoints[$i + 1]['speed_0']);
	}

	if (end($keyPoints)['speed_0'] < end($keyPoints)['speed_1']) {
		for ($j = end($keyPoints)['speed_0']; $j < end($keyPoints)['speed_1']; $j = $j + 2) {
			array_push($trainPositionChnageReturn, (end($trainPositionChnageReturn) + getBrakeDistance($j, ($j + 2), $verzoegerung)));
			array_push($trainSpeedChangeReturn, ($j + 2));
		}
	} else if (end($keyPoints)['speed_0'] > end($keyPoints)['speed_1']) {
		for ($j = end($keyPoints)['speed_0']; $j > end($keyPoints)['speed_1']; $j = $j - 2) {
			array_push($trainPositionChnageReturn, (end($trainPositionChnageReturn) + getBrakeDistance($j, ($j - 2), $verzoegerung)));
			array_push($trainSpeedChangeReturn, ($j - 2));
		}
	}

	return array($trainPositionChnageReturn, $trainSpeedChangeReturn);
}

// Wandelt die Daten der Infra-Abschnitte und der Iterationsschritte der
// Fahrtverlaufsberechnung in JSON-Dateien um, damit die Fahrtverläufe
// visuell dargestellt werden können.
function safeTrainChangeToJSONFile(int $indexCurrentSection, int $indexTargetSection, int $indexCurrentSectionMod, int $indexTargetSectionMod, array $speedOverPositionAllIterations) {

	global $trainPositionChange;
	global $trainSpeedChange;
	global $next_v_max;
	global $cumulativeSectionLengthEnd;
	global $next_v_max_mod;
	global $cumulativeSectionLengthEndMod;

	$speedOverPosition = array_map('toArr', $trainPositionChange, $trainSpeedChange);
	$speedOverPosition = json_encode($speedOverPosition);
	$fp = fopen('../json/speedOverPosition.json', 'w');
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

// Überprüft, ob das Fahrzeug in Infra-Abschnitten die zulässige
// Höchstgeschwindigkeit überschreitet
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
		return array('failed' => false);
	} else {
		return array('failed' => true, 'failed_sections' => array_unique($faildSections));
	}
}

// Löscht $keyPoint, bei denen Start- und Zielgeschwindigkeit identisch ist.
// Der erste $keyPoint wird dabei nicht betrachtet.
function deleteDoubledKeyPoints($temporaryKeyPoints) {
	do {
		$foundDoubledKeyPoints = false;
		$doubledIndex = array();

		for ($i = 1; $i < (sizeof($temporaryKeyPoints) - 1); $i++) {
			if ($temporaryKeyPoints[$i]['speed_0'] == $temporaryKeyPoints[$i]['speed_1']) {
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

// Ermittelt die Zeiten der $keyPoint ausgehend vom ersten $keyPoint. Mit dem
// Parameter $inputKeyPoints können $keyPoints übergeben werden, bei den die
// Zeit ermittelt wird. Wenn keine $keyPoints übergeben werden, werden die
// globalen $keyPoints verwendet. Mit dem Parameter $skippingKeys können
// $keyPoints übersprungen werden. Das auslassen von $keyPoints ist für die
// Funktion postponeSubsection() relevant.
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
		$localKeyPoints[$keys[$i]]['time_1'] = getBrakeTime($localKeyPoints[$keys[$i]]['speed_0'], $localKeyPoints[$keys[$i]]['speed_1'], $verzoegerung) + $localKeyPoints[$keys[$i]]['time_0'];
		$localKeyPoints[$keys[$i] + 1]['time_0'] = distanceWithSpeedToTime($localKeyPoints[$keys[$i]]['speed_1'], ($localKeyPoints[$keys[$i] + 1]['position_0']) - $localKeyPoints[$keys[$i]]['position_1']) + $localKeyPoints[$keys[$i]]['time_1'];
	}

	$localKeyPoints[end($keys)]['time_1'] = getBrakeTime($localKeyPoints[end($keys)]['speed_0'], $localKeyPoints[end($keys)]['speed_1'], $verzoegerung) + $localKeyPoints[end($keys)]['time_0'];

	return $localKeyPoints;
}

// Echtzeitdatenermittlung eines Fahrtverlaufs auf Grundlage der $keyPoints.
// Mit dem Parameter $onlyPositionAndSpeed kann festgelegt werden, ob nur die
// Position und Geschwindigkeit berechnet werden soll.
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
	global $globalDistanceUpdateInterval;

	$returnTrainSpeedChange = array();
	$returnTrainTimeChange = array();
	$returnTrainPositionChange = array();
	$returnTrainRelativePosition = array();
	$returnTrainSection = array();
	$returnIsSpeedChange = array();

	// Ermittelt für alle bis auf den letzten $keyPoint die Echtzeitdaten der
	// Zeit, Geschwindigkeit und Position
	for ($i = 0; $i < sizeof($keyPoints); $i++) {
		array_push($returnTrainTimeChange, $keyPoints[$i]['time_0']);
		array_push($returnTrainSpeedChange, $keyPoints[$i]['speed_0']);
		array_push($returnTrainPositionChange, $keyPoints[$i]['position_0']);
		array_push($returnIsSpeedChange, true);

		$itDir = ($keyPoints[$i]['speed_0'] < $keyPoints[$i]['speed_1']) ? 2 : -2;

		for ($j = ($keyPoints[$i]['speed_0'] + $itDir); $j <= $keyPoints[$i]['speed_1']; $j = $j + $itDir) {
			array_push($returnTrainPositionChange, (end($returnTrainPositionChange) + getBrakeDistance(($j - $itDir), $j, $verzoegerung)));
			array_push($returnTrainSpeedChange, $j);
			array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + (getBrakeTime(($j - $itDir), $j, $verzoegerung))));
			array_push($returnIsSpeedChange, true);
		}

		// Überprüft, ob nach dem $keyPoint eine Beharrungsfahrt stattfindet
		if ($i != array_key_last($keyPoints)) {
			// Ermittelt für die Strecke zwischen zwei $keyPoints die Echtzeitdaten
			// der Zeit, Geschwindigkeit und Position
			$startPosition = $keyPoints[$i]['position_1'];
			$endPosition =  $keyPoints[$i + 1]['position_0'];
			$speedToNextKeyPoint = $keyPoints[$i]['speed_1'];
			$timeForOneTimeInterval = distanceWithSpeedToTime($speedToNextKeyPoint, $globalDistanceUpdateInterval);

			for ($position = $startPosition + $globalDistanceUpdateInterval; $position < $endPosition; $position = $position + $globalDistanceUpdateInterval) {
				array_push($returnTrainPositionChange, $position);
				array_push($returnTrainSpeedChange, $speedToNextKeyPoint);
				array_push($returnTrainTimeChange, end($returnTrainTimeChange) + $timeForOneTimeInterval);
				array_push($returnIsSpeedChange, false);
			}
		}
	}
	array_push($returnTrainPositionChange, $keyPoints[array_key_last($keyPoints)]['position_1'] - getBrakeDistance($keyPoints[array_key_last($keyPoints)]['speed_0'],$keyPoints[array_key_last($keyPoints)]['speed_1'],$verzoegerung));
	array_push($returnTrainSpeedChange, $keyPoints[array_key_last($keyPoints)]['speed_0']);
	array_push($returnTrainTimeChange, $keyPoints[array_key_last($keyPoints)]['time_0']);
	array_push($returnIsSpeedChange, true);

	if ($onlyPositionAndSpeed) {
		return array($returnTrainPositionChange, $returnTrainSpeedChange);
	} else {
		// Ermittelt die relativen Positionen innerhalb der Infra-Abschnitte
		// zu den absoluten Positionen
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
				} else if ($absolutPositionKey == array_key_last($returnTrainPositionChange) && abs($absolutPositionValue - floatval($cumulativeSectionLengthEnd[$sectionStartKey])) < $globalFloatingPointNumbersRoundingError) {
					$returnTrainRelativePosition[$absolutPositionKey] = $cumulativeSectionLengthEnd[$sectionStartKey] - $sectionStartValue;
					$returnTrainSection[$absolutPositionKey] = $next_sections[$sectionStartKey];
					break;
				} else {
					debugMessage('Einer absoluten Position konnte kein Infra-Abschnitt und keine relative Position in einem Infra-Abschnitt zugeordnet werden.');
				}
			}
		}

		return array($returnTrainPositionChange, $returnTrainSpeedChange, $returnTrainTimeChange, $returnTrainRelativePosition, $returnTrainSection, $returnIsSpeedChange);
	}
}

// Überprüft, ob es zwischen zwei benachbarten $keyPoints
// zu einer Geschwindigkeitsüberschreitung kommt.
function recalculateKeyPoints(array $tempKeyPoints, int $id) {

	$returnKeyPoints = array();
	$numberOfPairs = sizeof($tempKeyPoints) / 2;

	for($j = 0; $j < $numberOfPairs; $j++) {
		$i = $j * 2;
		$return = checkBetweenTwoKeyPoints($tempKeyPoints, $i, $id);

		foreach ($return as $keyPoint) {
			array_push($returnKeyPoints, $keyPoint);
		}
	}

	return $returnKeyPoints;
}

// Überprüft, ob zwischen zwei $keyPoints die zulässige Höchstgeschwindigkeit
// überschritten wird
function checkBetweenTwoKeyPoints(array $temKeyPoints, int $keyPointIndex, int $id) {

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
		if ($trainPositionChangeValue >= $temKeyPoints[$keyPointIndex]['position_0'] && $trainPositionChangeValue <= $temKeyPoints[$keyPointIndex + 1]['position_1']) {
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

	// Alle Infra_abschnitte zwischen denn beiden KeyPoints, bei denen die
	// zulässige Höchstgeschwindigkeit überschritten wird
	$failedSections = array_unique($failedSections);

	// Wenn es kein Fehler gibt, werden die beiden KeyPoints zurückgegeben und
	// wen es einen Fehler gibt, wird der erste der beiden KeyPoints im
	// $returnKeyPoints gespeichert
	if (sizeof($failedSections) == 0) {
		return array($temKeyPoints[$keyPointIndex], $temKeyPoints[$keyPointIndex + 1]);
	} else {
		$returnKeyPoints[0]['speed_0'] = $temKeyPoints[$keyPointIndex]['speed_0'];
		$returnKeyPoints[0]['position_0'] = $temKeyPoints[$keyPointIndex]['position_0'];
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

	// Iteration über die zusammenhängenden $failedSections
	foreach ($groupedFailedSections as $groupSectionsIndex => $groupSectionsValue) {
		$firstFailedPositionIndex = null;
		$lastFailedPositionIndex = null;
		$firstFailedPosition = null;
		$lastFailedPosition = null;
		$lastElement = array_key_last($returnKeyPoints);
		$failedSection = null;

		// Ermittlung der Section mit der kleinsten v_max von allen $failedSections
		// in der Gruppe
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

		// Bestimmung der ersten und letzten Position, in der es in der $failedSection
		// zu einer Geschwindigkeitsüberschreitung kommt
		foreach ($failedPositions as $failPositionIndex => $failPositionValue) {
			if ($failPositionValue > $failedSectionStart && $failPositionValue < $failedSectionEnd) {
				if ($firstFailedPositionIndex == null) {
					$firstFailedPositionIndex = $failPositionIndex;
				}
				$lastFailedPositionIndex = $failPositionIndex;
			}
		}

		// Bestimmung des letzten Punktes, bei dem die Geschwindigkeit noch
		// nicht zu schnell war
		//
		// Wenn der Punkt davor außerhalb der failedSection liegt
		// => Startpunkt = Anfang der Section
		// Wenn der Punkt davor innerhalb der failed Section liegt
		// => Startpunkt = der Punkt davor
		if ($firstFailedPositionIndex != 0) {
			if ($trainPositionChange[$firstFailedPositionIndex - 1] < $failedSectionStart) {
				$firstFailedPosition = $failedSectionStart;
			} else {
				$firstFailedPosition = $trainPositionChange[$firstFailedPositionIndex - 1];
			}
		} else {
			$firstFailedPosition = $failedSectionStart;
		}

		// Bestimmung der ersten Position, bei dem die Geschwindigkeit nicht
		// mehr zu hoch war.
		if ($lastFailedPositionIndex != array_key_last($trainPositionChange)) {
			if ($trainPositionChange[$lastFailedPositionIndex + 1] > $failedSectionEnd) {
				$lastFailedPosition = $failedSectionEnd;
			} else {
				$lastFailedPosition = $trainPositionChange[$lastFailedPositionIndex + 1];
			}
		} else {
			$lastFailedPosition = $failedSectionEnd;
		}

		$returnKeyPoints[$lastElement + 1]['position_1'] = $firstFailedPosition;
		$returnKeyPoints[$lastElement + 1]['speed_1'] = $next_v_max_mod[$failedSection];
		$returnKeyPoints[$lastElement + 2]['position_0'] = $lastFailedPosition;
		$returnKeyPoints[$lastElement + 2]['speed_0'] = $next_v_max_mod[$failedSection];
	}

	// Zielwerte des letzten $keyPoint vom zweiten $keyPoint übernehmen
	$returnKeyPoints[array_key_last($returnKeyPoints) + 1]['position_1'] = $temKeyPoints[$keyPointIndex + 1]['position_1'];
	$returnKeyPoints[array_key_last($returnKeyPoints)]['speed_1'] = $temKeyPoints[$keyPointIndex + 1]['speed_1'];
	$numberOfPairs = sizeof($returnKeyPoints) / 2;

	for($j = 0; $j < $numberOfPairs; $j++) {
		$i = $j * 2;
		$distance = $returnKeyPoints[$i + 1]['position_1'] - $returnKeyPoints[$i]['position_0'];
		$vMax = getVMaxBetweenTwoPoints($distance, $returnKeyPoints[$i]['speed_0'], $returnKeyPoints[$i + 1]['speed_1'], $id);
		$returnKeyPoints[$i]['speed_1'] = $vMax;
		$returnKeyPoints[$i]['position_1'] = $returnKeyPoints[$i]['position_0'] + getBrakeDistance($returnKeyPoints[$i]['speed_0'], $vMax, $verzoegerung);
		$returnKeyPoints[$i + 1]['speed_0'] = $vMax;
		$returnKeyPoints[$i + 1]['position_0'] = $returnKeyPoints[$i + 1]['position_1'] - getBrakeDistance($vMax, $returnKeyPoints[$i + 1]['speed_1'], $verzoegerung);
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
		$v_maxBetweenKeyPoints = $keyPoints[$i]['speed_1'];
		$v_minBetweenKeyPoints = null;

		if ($keyPoints[$i]['speed_0'] < $v_maxBetweenKeyPoints && $keyPoints[$i + 1]['speed_1'] < $v_maxBetweenKeyPoints) {
			$v_minBetweenKeyPoints = $keyPoints[$i]['speed_0'];
			if ($keyPoints[$i + 1]['speed_1'] > $v_minBetweenKeyPoints) {
				$v_minBetweenKeyPoints = $keyPoints[$i + 1]['speed_1'];
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
			if ($v_minBetweenKeyPoints % 10 != 0) {
				$rest = $v_minBetweenKeyPoints % 10;
				$v_minBetweenKeyPoints = $v_minBetweenKeyPoints - $rest + 10;
			}

			array_push($returnPossibleSpeed, array('KeyPoint_index' => $i, 'values' => range($v_minBetweenKeyPoints, $v_maxBetweenKeyPoints, 10)));
		}
	}
	if (sizeof($returnPossibleSpeed) > 0) {
		return array('possible' => true, 'range' => $returnPossibleSpeed);
	} else {
		return array('possible' => false, 'range' => array());
	}
}

// Wenn in 'global_variables.php' der Variablen $useSpeedFineTuning
// der Wert 'true' zugewiesen ist und das Fahrzeug zu früh an der
// nächsten Betriebsstelle ankommt, wird überprüft, ob durch eine
// vorzeitige Einleitung einer Verzögerung die exakte Ankunftszeit
// eingehalten werden kann.
function speedFineTuning(float $timeDiff, int $index) {

	global $keyPoints;
	global $verzoegerung;
	global $globalTimeOnOneSpeed;
	global $useMinTimeOnSpeed;

	$speed_0 = $keyPoints[$index]['speed_1'];
	$speed_1 = null;
	$availableDistance = $keyPoints[$index + 1]['position_0'] - $keyPoints[$index]['position_1'];
	$timeBetweenKeyPoints = $keyPoints[$index + 1]['time_0'] - $keyPoints[$index]['time_1'];
	$availableTime = $timeBetweenKeyPoints + $timeDiff;

	if ($keyPoints[$index + 1]['speed_1'] != 0) {
		$speed_1 = $keyPoints[$index + 1]['speed_1'];
		$lengthDifference = calculateDistanceforSpeedFineTuning($keyPoints[$index + 1]['speed_0'], $keyPoints[$index + 1]['speed_1'], $availableDistance, $availableTime);

		if ($useMinTimeOnSpeed) {
			if (distanceWithSpeedToTime($speed_0, $availableDistance - $lengthDifference) > $globalTimeOnOneSpeed && distanceWithSpeedToTime($speed_1, $lengthDifference) > $globalTimeOnOneSpeed) {
				$keyPoints[$index + 1]['position_0'] = $keyPoints[$index + 1]['position_0'] - $lengthDifference;
				$keyPoints[$index + 1]['position_1'] = $keyPoints[$index + 1]['position_1'] - $lengthDifference;
			}
		} else {
			$keyPoints[$index + 1]['position_0'] = $keyPoints[$index + 1]['position_0'] - $lengthDifference;
			$keyPoints[$index + 1]['position_1'] = $keyPoints[$index + 1]['position_1'] - $lengthDifference;
		}
	} else if ($keyPoints[$index + 1]['speed_0'] > 10) {
		$speed_1 = 10;
		$lengthDifference = calculateDistanceforSpeedFineTuning($keyPoints[$index + 1]['speed_0'],10, $availableDistance, $availableTime);

		if ($useMinTimeOnSpeed) {
			if (distanceWithSpeedToTime($speed_0, $availableDistance - $lengthDifference) > $globalTimeOnOneSpeed && distanceWithSpeedToTime($speed_1, $lengthDifference) > $globalTimeOnOneSpeed) {
				$firstKeyPoint = createKeyPoint(($keyPoints[$index + 1]['position_0'] - $lengthDifference),($keyPoints[$index + 1]['position_0'] - $lengthDifference + getBrakeDistance($keyPoints[$index + 1]['speed_0'],10, $verzoegerung)),$keyPoints[$index + 1]['speed_0'],10);
				$secondKeyPoint = createKeyPoint(($keyPoints[$index + 1]['position_1'] - getBrakeDistance(10, 0, $verzoegerung)),$keyPoints[$index + 1]['position_1'],10,$keyPoints[$index + 1]['speed_1']);
				$keyPoints[$index + 1] = $secondKeyPoint;
				array_splice( $keyPoints, ($index + 1), 0, array($firstKeyPoint));
			}
		} else {
			$firstKeyPoint = createKeyPoint(($keyPoints[$index + 1]['position_0'] - $lengthDifference),($keyPoints[$index + 1]['position_0'] - $lengthDifference + getBrakeDistance($keyPoints[$index + 1]['speed_0'],10, $verzoegerung)),$keyPoints[$index + 1]['speed_0'],10);
			$secondKeyPoint = createKeyPoint(($keyPoints[$index + 1]['position_1'] - getBrakeDistance(10, 0, $verzoegerung)),$keyPoints[$index + 1]['position_1'],10,$keyPoints[$index + 1]['speed_1']);
			$keyPoints[$index + 1] = $secondKeyPoint;
			array_splice( $keyPoints, ($index + 1), 0, array($firstKeyPoint));
		}
	}
}

// Sucht den KeyPoint der zu maximalen Geschwindigkeit beschleunigt
// Wenn die maximale Geschwindigkeit mehrfach erreciht wird, wird
// der letzte dieser KeyPoints genommen
//
// Zu dem Index wird auch die Speed Range abgespeichert wie bei
// checkIfTheSpeedCanBeDecreased()
function findMaxSpeed(array $speedDecrease) {
	$maxSpeed = 0;
	$minSpeed = 0;
	$keyPointIndex = null;

	for ($i = 0; $i < sizeof($speedDecrease['range']); $i++) {
		if (max($speedDecrease['range'][$i]['values']) >= $maxSpeed) {
			$maxSpeed = max($speedDecrease['range'][$i]['values']);
			$minSpeed = min($speedDecrease['range'][$i]['values']);
			$keyPointIndex = $speedDecrease['range'][$i]['KeyPoint_index'];
		}
	}
	return array('min_speed' => $minSpeed, 'max_speed' => $maxSpeed, 'first_key_point_index' => $keyPointIndex);
}

// Überprüft beim Start der Fahrtverlaufsberechnung,
// ob es möglich ist einen Fahrtverlauf zu ermitteln
function checkIfItsPossible(int $id) {

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
				emergencyBreak($id);
			}

			echo "Der Zug schafft es ohne eine Gefahrenbremsung am Ziel anzukommen, kann aber nicht die mind. Zeit einhalten.\n";
		}
	} else {
		if (getBrakeDistance($currentSpeed, 0, $verzoegerung) != $distanceToNextStop) {
			$distance_0 = getBrakeDistance($currentSpeed, 10, $verzoegerung);
			$distance_1 = getBrakeDistance(10, 0, $verzoegerung);
			$time = distanceWithSpeedToTime(10, $distanceToNextStop - $distance_0 - $distance_1);

			if ($time < $globalTimeOnOneSpeed) {
				$minTimeIsPossible = false;

				if ($errorMinTimeOnSpeed) {
					emergencyBreak($id);
				}

				echo "Der Zug schafft es, ohne eine Gefahrenbremsung am Ziel anzukommen.\n";
			}
		}
	}
	return $minTimeIsPossible;
}

// Überprüft, ob der vorgeschriebene Wert aus der Variablen
// $globalTimeOnOneSpeed eingehalten wird, falls die
// Variable $useMinTimeOnSpeed den Wert 'true' hat
function toShortOnOneSpeed () {

	global $keyPoints;
	global $verzoegerung;

	$localKeyPoints = $keyPoints;
	$subsections = createSubsections($localKeyPoints);

	while (toShortInSubsection($subsections)) {
		$breakesOnly = true;
		foreach ($subsections as $sectionKey => $sectionValue) {
			if ($sectionValue['failed']) {
				if (!$sectionValue['brakes_only']) {
					$breakesOnly = false;
				}

				$return = postponeSubsection($localKeyPoints, $sectionValue);

				if (!$return['fail']) {
					$localKeyPoints = $return['keyPoints'];
				} else {
					if (!$sectionValue['brakes_only']) {
						$localKeyPoints[$sectionValue['max_index']]['speed_1'] -= 10;
						$localKeyPoints[$sectionValue['max_index'] + 1]['speed_0'] -= 10;
						$localKeyPoints[$sectionValue['max_index']]['position_1'] = $localKeyPoints[$sectionValue['max_index']]['position_0'] + getBrakeDistance($localKeyPoints[$sectionValue['max_index']]['speed_0'], $localKeyPoints[$sectionValue['max_index']]['speed_1'], $verzoegerung);
						$localKeyPoints[$sectionValue['max_index'] + 1]['position_0'] = $localKeyPoints[$sectionValue['max_index'] + 1]['position_1'] - getBrakeDistance($localKeyPoints[$sectionValue['max_index'] + 1]['speed_0'], $localKeyPoints[$sectionValue['max_index'] + 1]['speed_1'], $verzoegerung);
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

// Überprüft, ob innerhalb einer $subsection Beschleuniguns- und
// Bremsvorgänge später bzw. früher eingeleitet werden können.
function postponeSubsection (array $localKeyPoints, array $subsection) {

	global $globalTimeOnOneSpeed;
	global $verzoegerung;

	$deletedKeyPoints = array();
	$numberOfKeyPoints = sizeof($subsection['indexes']);
	$indexMaxSection = array_search($subsection['max_index'], $subsection['indexes']);
	$indexLastKeyPoint = array_key_last($subsection['indexes']);

	if ($subsection['is_prev_section']) {
		$timeDiff = $localKeyPoints[$subsection['indexes'][0]]['time_0'] - $localKeyPoints[$subsection['indexes'][0] - 1]['time_1'] - $globalTimeOnOneSpeed;
		if ($timeDiff < 0) {
			$positionDiff = abs($timeDiff) * $localKeyPoints[$subsection['indexes'][0]]['speed_0'] / 3.6;
			if (!($localKeyPoints[$subsection['indexes'][0]]['position_1'] + $positionDiff > $localKeyPoints[$subsection['indexes'][$indexMaxSection + 1]]['position_0'])) {
				$localKeyPoints[$subsection['indexes'][0]]['position_0'] += $positionDiff;
				$localKeyPoints[$subsection['indexes'][0]]['position_1'] += $positionDiff;
				if ($localKeyPoints[$subsection['indexes'][0]]['position_1'] > $localKeyPoints[$subsection['indexes'][0] + 1]['position_0']) {
					array_push($deletedKeyPoints, $subsection['indexes'][0] + 1);
					$numberOfKeyPoints -= 1;
					$v_0 = $localKeyPoints[$subsection['indexes'][0]]['speed_0'];
					$v_1 = $localKeyPoints[$subsection['indexes'][0] + 1]['speed_1'];
					$localKeyPoints[$subsection['indexes'][0]]['position_1'] = $localKeyPoints[$subsection['indexes'][0]]['position_0'] + getBrakeDistance($v_0, $v_1, $verzoegerung);
					$localKeyPoints[$subsection['indexes'][0]]['speed_1'] = $v_1;
				}
				$localKeyPoints = calculateTimeFromKeyPoints($localKeyPoints, $deletedKeyPoints);
			}
		}
	}

	for ($i = 1; $i <= $indexMaxSection; $i++) {
		if (!in_array($subsection['indexes'][$i], $deletedKeyPoints)) {
			$timeDiff = $localKeyPoints[$subsection['indexes'][$i]]['time_0'] - $localKeyPoints[$subsection['indexes'][$i] - 1]['time_1'] - $globalTimeOnOneSpeed;
			if ($timeDiff < 0) {
				$positionDiff = abs($timeDiff) * $localKeyPoints[$subsection['indexes'][$i]]['speed_0'] / 3.6;
				if (!($localKeyPoints[$subsection['indexes'][$i]]['position_1'] + $positionDiff > $localKeyPoints[$subsection['indexes'][$indexMaxSection + 1]]['position_0'])) {
					$localKeyPoints[$subsection['indexes'][$i]]['position_0'] += $positionDiff;
					$localKeyPoints[$subsection['indexes'][$i]]['position_1'] += $positionDiff;
					if ($i < $indexMaxSection && $localKeyPoints[$subsection['indexes'][$i]]['position_1'] > $localKeyPoints[$subsection['indexes'][$i] + 1]['position_0']) {
						array_push($deletedKeyPoints, ($subsection['indexes'][$i] + 1));
						$numberOfKeyPoints -= 1;
						$v_0 = $localKeyPoints[$subsection['indexes'][$i]]['speed_0'];
						$v_1 = $localKeyPoints[$subsection['indexes'][$i] + 1]['speed_1'];
						$localKeyPoints[$subsection['indexes'][$i]]['position_1'] = $localKeyPoints[$subsection['indexes'][$i]]['position_0'] + getBrakeDistance($v_0, $v_1, $verzoegerung);
						$localKeyPoints[$subsection['indexes'][$i]]['speed_1'] = $v_1;
					}
					$localKeyPoints = calculateTimeFromKeyPoints($localKeyPoints, $deletedKeyPoints);
				}
			}
		}
	}

	if ($subsection['is_next_section']) {
		$timeDiff = $localKeyPoints[$subsection['indexes'][$indexLastKeyPoint] + 1]['time_0'] - $localKeyPoints[$subsection['indexes'][$indexLastKeyPoint]]['time_1'] - $globalTimeOnOneSpeed;
		if ($timeDiff < 0) {
			$positionDiff = abs($timeDiff) * $localKeyPoints[$indexLastKeyPoint]['speed_1'] / 3.6;
			if (!($localKeyPoints[$subsection['indexes'][$indexLastKeyPoint]]['position_0'] - $positionDiff < $localKeyPoints[$subsection['indexes'][$indexMaxSection]]['position_0'])) {
				$localKeyPoints[$subsection['indexes'][$indexLastKeyPoint]]['position_0'] -= $positionDiff;
				$localKeyPoints[$subsection['indexes'][$indexLastKeyPoint]]['position_1'] -= $positionDiff;
				if ($localKeyPoints[$subsection['indexes'][$indexLastKeyPoint]]['position_0'] < $localKeyPoints[$subsection['indexes'][$indexLastKeyPoint] - 1]['position_1']) {
					array_push($deletedKeyPoints, ($subsection['indexes'][$indexLastKeyPoint] - 1));
					$numberOfKeyPoints -= 1;
					$v_0 = $localKeyPoints[$subsection['indexes'][$indexLastKeyPoint] - 1]['speed_0'];
					$v_1 = $localKeyPoints[$subsection['indexes'][$indexLastKeyPoint]]['speed_1'];
					$localKeyPoints[$subsection['indexes'][$indexLastKeyPoint]]['position_0'] = $localKeyPoints[$subsection['indexes'][$indexLastKeyPoint]]['position_1'] - getBrakeDistance($v_0, $v_1, $verzoegerung);
					$localKeyPoints[$subsection['indexes'][$indexLastKeyPoint]]['speed_0'] = $v_0;
				}
				$localKeyPoints = calculateTimeFromKeyPoints($localKeyPoints, $deletedKeyPoints);
			}
		}
	}

	for ($i = $indexLastKeyPoint - 1; $i > $indexMaxSection; $i--) {
		if (!in_array($i, $deletedKeyPoints)) {
			$timeDiff = $localKeyPoints[$subsection['indexes'][$i + 1]]['time_0'] - $localKeyPoints[$subsection['indexes'][$i]]['time_1'] - $globalTimeOnOneSpeed;
			if ($timeDiff < 0) {
				$positionDiff = abs($timeDiff) * $localKeyPoints[$indexLastKeyPoint]['speed_1'] / 3.6;
				if (!($localKeyPoints[$subsection['indexes'][$i]]['position_0'] - $positionDiff < $localKeyPoints[$subsection['indexes'][$indexMaxSection]]['position_0'])) {
					$localKeyPoints[$subsection['indexes'][$i]]['position_0'] -= $positionDiff;
					$localKeyPoints[$subsection['indexes'][$i]]['position_1'] -= $positionDiff;
					if ($i > ($indexMaxSection + 1) && $localKeyPoints[$subsection['indexes'][$i]]['position_0'] < $localKeyPoints[$subsection['indexes'][$i] - 1]['position_1']) {
						array_push($deletedKeyPoints, ($subsection['indexes'][$i] - 1));
						$numberOfKeyPoints -= 1;
						$v_0 = $localKeyPoints[$subsection['indexes'][$i] - 1]['speed_0'];
						$v_1 = $localKeyPoints[$subsection['indexes'][$i]]['speed_1'];
						$localKeyPoints[$subsection['indexes'][$i]]['position_0'] = $localKeyPoints[$subsection['indexes'][$i]]['position_1'] - getBrakeDistance($v_0, $v_1, $verzoegerung);
						$localKeyPoints[$subsection['indexes'][$i]]['speed_0'] = $v_0;
					}
					$localKeyPoints = calculateTimeFromKeyPoints($localKeyPoints, $deletedKeyPoints);
				}
			}
		}
	}

	$keys = $subsection['indexes'];

	foreach ($deletedKeyPoints as $index) {
		unset($keys[array_search($index, $keys)]);
	}

	// Ordnet die Abschnitte zwischen zwei $subsection den $subsections zu
	$keys = array_values($keys);
	$failed = false;

	if ($subsection['is_prev_section']) {
		if ($localKeyPoints[$keys[0]]['time_0'] - $localKeyPoints[$keys[0] - 1]['time_1'] < $globalTimeOnOneSpeed) {
			$failed = true;
		}
	}

	if ($subsection['is_next_section']) {
		if ($localKeyPoints[end($keys) + 1]['time_0'] - $localKeyPoints[end($keys)]['time_1'] < $globalTimeOnOneSpeed) {
			$failed = true;
		}
	}

	for ($i = 1; $i < sizeof($keys); $i++)  {
		if ($localKeyPoints[$keys[$i]]['time_0'] - $localKeyPoints[$keys[$i - 1]]['time_1'] < $globalTimeOnOneSpeed) {
			$failed = true;
			break;
		}
	}

	if ($failed) {
		return array('fail' => true, 'keyPoints' => array());
	} else {
		foreach ($deletedKeyPoints as $index) {
			unset($localKeyPoints[$index]);
		}

		return array('fail' => false, 'keyPoints' => $localKeyPoints);
	}
}

// Erstellt mittels der $keyPoints die $subsections
function createSubsections (array $localKeyPoints) {

	global $globalTimeOnOneSpeed;

	$keyPoints = $localKeyPoints;
	$subsections = array();
	$subsection = array('max_index' => null, 'indexes' => array(), 'is_prev_section' => false, 'is_next_section' => false);
	$maxIndex = null;

	for($i = 0; $i < sizeof($keyPoints); $i++) {
		if ($i > 0) {
			if ($keyPoints[$i]['speed_0'] < $keyPoints[$i]['speed_1'] && $keyPoints[$i - 1]['speed_0'] > $keyPoints[$i - 1]['speed_1'] || $i == sizeof($keyPoints) - 1) {
				if ($i == sizeof($keyPoints) - 1) {
					array_push($subsection['indexes'], $i);
				}

				array_push($subsections, $subsection);
				$subsection['indexes'] = array();
			}
		}

		if ($keyPoints[$i]['speed_0'] < $keyPoints[$i]['speed_1']) {
			$subsection['max_index'] = $i;
		}

		array_push($subsection['indexes'], $i);
	}

	// Überprüfung der Abschnitte zwischen zwei $subsections
	for ($i = 1; $i < sizeof($subsections); $i++) {
		$firstIndex = $subsections[$i]['indexes'][array_key_first($subsections[$i]['indexes'])];

		if ($keyPoints[$firstIndex]['time_0'] - $keyPoints[$firstIndex - 1]['time_1'] < $globalTimeOnOneSpeed) {
			$subsections[$i]['is_prev_section'] = true;
			$subsections[$i]['failed'] = true;
		} else {
			$subsections[$i]['is_prev_section'] = false;
			$subsections[$i]['failed'] = false;
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

		if ($subsections[$i]['failed'] || failOnSubsection($keyPoints, $subsections[$i])) {
			$subsections[$i]['failed'] = true;

			if (!$isFirstSubsection) {
				$subsections[$i]['is_prev_section'] = true;
			}

			if (!$isLastSubsection) {
				if (!$subsections[$i + 1]['is_prev_section']) {
					$subsections[$i]['is_next_section'] = true;
				}
			}
		} else {
			$subsections[$i]['failed'] = false;
		}
	}

	for ($i = 0; $i < sizeof($subsections); $i++) {
		if (!isset($subsections[$i]['max_index'])) {
			$subsections[$i]['brakes_only'] = true;
			$subsections[$i]['max_index'] = $subsections[$i]['indexes'][0];
		} else {
			$subsections[$i]['brakes_only'] = false;
		}
	}

	$subsections = array_values($subsections);

	return array_reverse($subsections);
}

// Überprüfung, ob es in einer $subsection zu einer Unterschreitung
// der Mindestzeit kommt
function failOnSubsection(array $keyPoints, array $subsection) {

	global $globalTimeOnOneSpeed;

	$failed = false;

	for ($i = 1; $i < sizeof($subsection['indexes']); $i++)  {
		if ($keyPoints[$subsection['indexes'][$i]]['time_0'] - $keyPoints[$subsection['indexes'][$i] - 1]['time_1'] < $globalTimeOnOneSpeed) {
			$failed = true;
			break;
		}
	}

	return $failed;
}

function toShortInSubsection (array $subsections) {

	$foundError = false;

	foreach ($subsections as $subsection) {
		if ($subsection['failed']) {
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
	for ($i = $indexCurrentSection; $i <= $indexTargetSection; $i++) {
		if ($indexCurrentSection == $indexTargetSection) {
			$cumulativeSectionLengthStart[$i] = 0;
			$cumulativeSectionLengthEnd[$i] = intval($targetPosition - $currentPosition);
		} else {
			if ($i == $indexCurrentSection) {
				$cumulativeSectionLengthStart[$i] = 0;
				$cumulativeSectionLengthEnd[$i] = intval($cumLength[$i] - $currentPosition);
			} else if ($i == $indexTargetSection) {
				$cumulativeSectionLengthStart[$i] = intval($cumLength[$i - 1] - $currentPosition);
				$cumulativeSectionLengthEnd[$i] = intval($cumLength[$i - 1] + $targetPosition - $currentPosition);
			} else {
				$cumulativeSectionLengthStart[$i] = intval($cumLength[$i - 1] - $currentPosition);
				$cumulativeSectionLengthEnd[$i] = intval($cumLength[$i] - $currentPosition);
			}
		}
	}

	return array($cumulativeSectionLengthStart, $cumulativeSectionLengthEnd);
}

function toArr(){
	return func_get_args();
}

// Ermittelt die Echtzeitdaten für eine Gefahrenbremsung
function emergencyBreak ($id, $distanceToNextStop = 0) {

	global $allUsedTrains;
	global $timeDifference;
	global $allTimes;

	$targetSpeed = 0;
	$returnArray = array();
	$time = microtime(true) + $timeDifference;
	$currentSpeed = $allUsedTrains[$id]['current_speed'];
	$notverzoegerung = $allUsedTrains[$id]['notverzoegerung'];
	$currentSection = $allUsedTrains[$id]['current_section'];

	echo 'Der Zug mit der Adresse: ', $allUsedTrains[$id]['adresse'], " leitet jetzt eine Gefahrenbremsung ein.\n";

	if (getBrakeDistance($currentSpeed, $targetSpeed, $notverzoegerung) <= $distanceToNextStop) {
		for ($i = $currentSpeed; $i >= 0; $i = $i - 2) {
			array_push($returnArray, array('live_position' => 0, 'live_speed' => $i, 'live_time' => $time, 'live_relative_position' => 0, 'live_section' => $currentSection, 'live_is_speed_change' => true, 'live_target_reached' => false, 'id' => $id, 'wendet' => false, 'betriebsstelle' => 'Notbremsung', 'live_all_targets_reached' => null));
			$time =  $time + getBrakeTime($i, $i - 1, $notverzoegerung);
		}
	} else {
		$targetSpeedNotbremsung =  getTargetBrakeSpeedWithDistanceAndStartSpeed($distanceToNextStop, $notverzoegerung, $currentSpeed);
		$speedBeforeStop = intval($targetSpeedNotbremsung / 2) * 2;

		if ($speedBeforeStop >= 10) {
			for ($i = $currentSpeed; $i >= 10; $i = $i - 2) {
				array_push($returnArray, array('live_position' => 0, 'live_speed' => $i, 'live_time' => $time, 'live_relative_position' => 0, 'live_section' => $currentSection, 'live_is_speed_change' => true, 'live_target_reached' => false, 'id' => $id, 'wendet' => false, 'betriebsstelle' => 'Notbremsung', 'live_all_targets_reached' => null));
				$time =  $time + getBrakeTime($i, $i - 1, $notverzoegerung);
			}
			array_push($returnArray, array('live_position' => 0, 'live_speed' => 0, 'live_time' => $time, 'live_relative_position' => 0, 'live_section' => $currentSection, 'live_is_speed_change' => true, 'live_target_reached' => false, 'id' => $id, 'wendet' => false, 'betriebsstelle' => 'Notbremsung', 'live_all_targets_reached' => null));
		} else {
			array_push($returnArray, array('live_position' => 0, 'live_speed' => $currentSpeed, 'live_time' => $time, 'live_relative_position' => 0, 'live_section' => $currentSection, 'live_is_speed_change' => true, 'live_target_reached' => false, 'id' => $id, 'wendet' => false, 'betriebsstelle' => 'Notbremsung', 'live_all_targets_reached' => null));
			$time =  $time + getBrakeTime($currentSpeed, $currentSpeed - 1, $notverzoegerung);
			array_push($returnArray, array('live_position' => 0, 'live_speed' => 0, 'live_time' => $time, 'live_relative_position' => 0, 'live_section' => $currentSection, 'live_is_speed_change' => true, 'live_target_reached' => false, 'id' => $id, 'wendet' => false, 'betriebsstelle' => 'Notbremsung', 'live_all_targets_reached' => null));
		}
	}

	$allTimes[$allUsedTrains[$id]['adresse']] = $returnArray;
	array_push($allUsedTrains[$id]['error'], 3);

	return 0;
}