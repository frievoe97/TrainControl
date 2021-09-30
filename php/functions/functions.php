<?php
// Zeigt beim Starten der Fahrzeugsteuerung eine Startmeldung im Terminal an,
// in der Informationen zur Session angezeigt werden.
function startMessage() {
	global $simulationStartTimeToday;
	global $simulationEndTimeToday;
	global $simulationDuration;
	global $realStartTime;
	global $realEndTime;
	global $cacheFahrplanSession;

	$realStartTimeAsHHMMSS = getUhrzeit($realStartTime, 'simulationszeit', null, array('outputtyp' => 'h:i:s'));
	$simulationEndTimeAsHHMMSS = getUhrzeit($simulationEndTimeToday, 'simulationszeit', null, array('outputtyp' => 'h:i:s'));
	$simulationDurationAsHHMMSS = toStd($simulationDuration);
	$realEndTimeAsHHMMSS = getUhrzeit($realEndTime, 'simulationszeit', null, array('outputtyp' => 'h:i:s'));
	$simulationStartTimeAsHHMMSS = getUhrzeit($simulationStartTimeToday, 'simulationszeit', null, array('outputtyp' => 'h:i:s'));
	$hashtagLine = "#####################################################################\n";
	$emptyLine = "#\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t#\n";

	echo $hashtagLine;
	echo $emptyLine;
	echo "#\t\t\t  Start der automatischen Zugbeeinflussung\t\t\t\t#\n";
	echo "#\t\tim Eisenbahnbetriebs- und Experimentierfeld (EBuEf) \t\t#\n";
	echo "#\t\t\t\t\t\t    der TU Berlin\t\t\t\t\t\t\t#\n";
	echo "#\t\t\t\t\t    im eingleisigen Netz \t\t\t\t\t\t#\n";
	echo $emptyLine;
	echo "#\t\t\t\t\t\t\t____\t\t\t\t\t\t\t\t\t#\n";
	echo "#\t\t\t\t\t\t\t|DD|____T_\t\t\t\t\t\t\t\t#\n";
	echo "#\t\t\t\t\t\t\t|_ |_____|<\t\t\t\t\t\t\t\t#\n";
	echo "#\t\t\t\t\t\t\t  @-@-@-oo\\\t\t\t\t\t\t\t\t#\n";
	echo "#\t\t\t\t\t=============================\t\t\t\t\t#\n";
	echo $emptyLine;
	echo "#\t Start der Simulation: \t\t\t\t\t\t\t\t\t\t\t#\n";
	echo "#\t\t Simulationszeit: \t\t\t", $simulationStartTimeAsHHMMSS, "\t\t\t\t\t\t#\n";
	echo "#\t\t Realzeit: \t\t\t\t\t", $realStartTimeAsHHMMSS, "\t\t\t\t\t\t#\n";
	echo $emptyLine;
	echo "#\t Ende der Simulation: \t\t\t\t\t\t\t\t\t\t\t#\n";
	echo "#\t\t Simulationszeit: \t\t\t", $simulationEndTimeAsHHMMSS, "\t\t\t\t\t\t#\n";
	echo "#\t\t Realzeit: \t\t\t\t\t", $realEndTimeAsHHMMSS, "\t\t\t\t\t\t#\n";
	echo $emptyLine;
	echo "#\t Dauer der Simulation: \t\t\t", $simulationDurationAsHHMMSS, "\t\t\t\t\t\t#\n";
	echo $emptyLine;
	echo "#\t Fahrplanname: \t\t\t\t\t", $cacheFahrplanSession->name, "\t\t#\n";
	echo "#\t Sessionkey: \t\t\t\t\t", $cacheFahrplanSession->sessionkey, "\t\t\t\t\t#\n";
	echo $emptyLine;
	echo $hashtagLine, "\n\n";
}

// Konvertiert Sekunden in das Format hh:mm:ss
function toStd(float $sekunden) {

	$stunden = floor($sekunden / 3600);
	$minuten = floor(($sekunden - ($stunden * 3600)) / 60);
	$sekunden = round($sekunden - ($stunden * 3600) - ($minuten * 60));

	if ($stunden <= 9) {
		$strStunden = '0'. $stunden;
	} else {
		$strStunden = $stunden;
	}

	if ($minuten <= 9) {
		$strMinuten = '0'. $minuten;
	} else {
		$strMinuten = $minuten;
	}

	if ($sekunden <= 9) {
		$strSekunden = '0'. $sekunden;
	} else {
		$strSekunden = $sekunden;
	}

	return "$strStunden:$strMinuten:$strSekunden";
}

// Fügt ein Fahrzeug zur Fahrzeugsteuerung ($allUsedTrains) über die Adresse
// hinzu und ermittelt die aktuelle Position und die Fahrplandaten
function prepareTrainForRide(int $adresse) {

	global $allUsedTrains;
	global $allTrains;
	global $cacheAdresseToID;
	global $cacheFmaToInfra;
	global $cacheInfraToFma;
	global $cacheZwischenhaltepunkte;
	global $cacheInfraLaenge;
	global $globalNotverzoegerung;

	$trainID = $cacheAdresseToID[$adresse];
	$zugID = null;
	$keysZwischenhalte = array_keys($cacheZwischenhaltepunkte);
	$allUsedTrains[$trainID]['id'] = $allTrains[$trainID]['id'];
	$allUsedTrains[$trainID]['adresse'] = $allTrains[$trainID]['adresse'];
	$allUsedTrains[$trainID]['zug_id'] = null;
	$allUsedTrains[$trainID]['verzoegerung'] = floatval($allTrains[$trainID]['verzoegerung']);
	$allUsedTrains[$trainID]['notverzoegerung'] = $globalNotverzoegerung;
	$allUsedTrains[$trainID]['zuglaenge'] = $allTrains[$trainID]['zuglaenge'];
	$allUsedTrains[$trainID]['v_max'] = $allTrains[$trainID]['v_max'];
	$allUsedTrains[$trainID]['dir'] = $allTrains[$trainID]['dir'];
	$allUsedTrains[$trainID]['error'] = array();
	$allUsedTrains[$trainID]['operates_on_timetable'] = false;
	$allUsedTrains[$trainID]['fahrstrasse_is_correct'] = false;
	$allUsedTrains[$trainID]['current_speed'] = intval($allTrains[$trainID]['speed']);
	$allUsedTrains[$trainID]['current_position'] = null;
	$allUsedTrains[$trainID]['current_section'] = null;
	$allUsedTrains[$trainID]['next_sections'] = array();
	$allUsedTrains[$trainID]['next_lenghts'] = array();
	$allUsedTrains[$trainID]['next_v_max'] = array();
	$allUsedTrains[$trainID]['next_betriebsstellen_data'] = array();
	$allUsedTrains[$trainID]['next_bs'] = '';
	$allUsedTrains[$trainID]['earliest_possible_start_time'] = null;
	$allUsedTrains[$trainID]['calibrate_section_one'] = null;
	$allUsedTrains[$trainID]['calibrate_section_two'] = null;

	// Fehlerüberprüfung
	if (!($allUsedTrains[$trainID]['zuglaenge'] > 0)) {
		array_push($allUsedTrains[$trainID]['error'], 1);
	}

	if (!isset($allUsedTrains[$trainID]['v_max'])) {
		array_push($allUsedTrains[$trainID]['error'], 2);
	}

	// Positionsermittlung
	$fma = getPosition($adresse);

	if (sizeof($fma) == 0) {
		$allUsedTrains[$trainID]['current_fma_section'] = null;
		$allUsedTrains[$trainID]['current_section'] = null;
	} elseif (sizeof($fma) == 1) {
		$allUsedTrains[$trainID]['current_fma_section'] = $fma[0];
		$allUsedTrains[$trainID]['current_section'] = $cacheFmaToInfra[$fma[0]];
	} else {
		$infraArray = array();
		foreach ($fma as $value) {
			array_push($infraArray, $cacheFmaToInfra[$value]);
		}
		$infra = getFrontPosition($infraArray, $allTrains[$trainID]['dir']);
		$allUsedTrains[$trainID]['current_fma_section'] = $cacheInfraToFma[$infra];
		$allUsedTrains[$trainID]['current_section'] = $infra;
	}

	$allUsedTrains[$trainID]['current_position'] = $cacheInfraLaenge[$allUsedTrains[$trainID]['current_section']];
	$timetableIDs = getFahrzeugZugIds(array($trainID));

	if (sizeof($timetableIDs) != 0) {
		$timetableID = $timetableIDs[array_key_first($timetableIDs)];
		$allUsedTrains[$trainID]['zug_id'] = intval($timetableID['zug_id']);
		$zugID = intval($timetableID['zug_id']);
		$allUsedTrains[$trainID]['operates_on_timetable'] = true;
	} else {
		$allUsedTrains[$trainID]['zug_id'] = null;
		$allUsedTrains[$trainID]['operates_on_timetable'] = false;
	}

	// Ermittlung der Fahrplaninformationen
	if (isset($zugID)) {
		$nextBetriebsstellen = getNextBetriebsstellen($zugID);
	}

	if ($zugID != null && sizeof($nextBetriebsstellen) != 0) {
		for ($i = 0; $i < sizeof($nextBetriebsstellen); $i++) {
			if (sizeof(explode('_', $nextBetriebsstellen[$i])) != 2) {
				$allUsedTrains[$trainID]['next_betriebsstellen_data'][$i]['is_on_fahrstrasse'] = false;
				$allUsedTrains[$trainID]['next_betriebsstellen_data'][$i]['betriebstelle'] = $nextBetriebsstellen[$i];
				$allUsedTrains[$trainID]['next_betriebsstellen_data'][$i]['zeiten'] = getFahrplanzeiten($nextBetriebsstellen[$i], $zugID);
				$allUsedTrains[$trainID]['next_betriebsstellen_data'][$i]['fahrplanhalt'] = true;
			} else if(in_array($nextBetriebsstellen[$i], $keysZwischenhalte)) {
				$allUsedTrains[$trainID]['next_betriebsstellen_data'][$i]['is_on_fahrstrasse'] = false;
				$allUsedTrains[$trainID]['next_betriebsstellen_data'][$i]['betriebstelle'] = $nextBetriebsstellen[$i];
				$allUsedTrains[$trainID]['next_betriebsstellen_data'][$i]['zeiten'] = getFahrplanzeiten($nextBetriebsstellen[$i], $zugID);
				$allUsedTrains[$trainID]['next_betriebsstellen_data'][$i]['fahrplanhalt'] = false;
			}
		}
		$allUsedTrains[$trainID]['next_betriebsstellen_data'] = array_values($allUsedTrains[$trainID]['next_betriebsstellen_data']);
	} else {
		$allUsedTrains[$trainID]['next_betriebsstellen_data'] = array();
	}

	foreach ($allUsedTrains[$trainID]['next_betriebsstellen_data'] as $betriebsstelleKey => $betriebsstelleValue) {
		if ($allUsedTrains[$trainID]['next_betriebsstellen_data'][$betriebsstelleKey]['zeiten']['abfahrt_soll'] != null) {
			$allUsedTrains[$trainID]['next_betriebsstellen_data'][$betriebsstelleKey]['zeiten']['abfahrt_soll_timestamp'] = getUhrzeit($betriebsstelleValue['zeiten']['abfahrt_soll'], 'simulationszeit', null, array('inputtyp' => 'h:i:s'));
		} else {
			$allUsedTrains[$trainID]['next_betriebsstellen_data'][$betriebsstelleKey]['zeiten']['abfahrt_soll_timestamp'] = null;
		}
		if ($allUsedTrains[$trainID]['next_betriebsstellen_data'][$betriebsstelleKey]['zeiten']['ankunft_soll'] != null) {
			$allUsedTrains[$trainID]['next_betriebsstellen_data'][$betriebsstelleKey]['zeiten']['ankunft_soll_timestamp'] = getUhrzeit($betriebsstelleValue['zeiten']['ankunft_soll'], 'simulationszeit', null, array('inputtyp' => 'h:i:s'));
		} else {
			$allUsedTrains[$trainID]['next_betriebsstellen_data'][$betriebsstelleKey]['zeiten']['ankunft_soll_timestamp'] = null;
		}
		$allUsedTrains[$trainID]['next_betriebsstellen_data'][$betriebsstelleKey]['zeiten']['verspaetung'] = 0;
	}
}

// Positionsermittlung einer Zuges, wenn das Fahrzeug mehrere
// Infrastrukturabschnitte belegt.
function getFrontPosition(array $infra, int $dir) {

	foreach ($infra as $section) {
		$nextSections = array();
		$test = getNaechsteAbschnitte($section, $dir);

		foreach ($test as $value) {
			array_push($nextSections, $value['infra_id']);
		}

		if (sizeof(array_intersect($infra, $nextSections)) == 0) {
			return $section;
		}
	}

	return false;
}

// Ermittelt für ein Fahrzeug und die zugehörige Zug-ID den Fahrplan
function getFahrplanAndPositionForOneTrain (int $trainID, int $zugID) {

	global $cacheZwischenhaltepunkte;
	global $allUsedTrains;

	$allUsedTrains[$trainID]['next_betriebsstellen_data'] = array();
	$keysZwischenhalte = array_keys($cacheZwischenhaltepunkte);

	$nextBetriebsstellen = getNextBetriebsstellen($zugID);

	if ($zugID != null && sizeof($nextBetriebsstellen) != 0) {
		for ($i = 0; $i < sizeof($nextBetriebsstellen); $i++) {
			if (sizeof(explode('_', $nextBetriebsstellen[$i])) != 2) {
				$allUsedTrains[$trainID]['next_betriebsstellen_data'][$i]['is_on_fahrstrasse'] = false;
				$allUsedTrains[$trainID]['next_betriebsstellen_data'][$i]['betriebstelle'] = $nextBetriebsstellen[$i];
				$allUsedTrains[$trainID]['next_betriebsstellen_data'][$i]['zeiten'] = getFahrplanzeiten($nextBetriebsstellen[$i], $zugID);
				$allUsedTrains[$trainID]['next_betriebsstellen_data'][$i]['fahrplanhalt'] = true;
			} else if(in_array($nextBetriebsstellen[$i], $keysZwischenhalte)) {
				$allUsedTrains[$trainID]['next_betriebsstellen_data'][$i]['is_on_fahrstrasse'] = false;
				$allUsedTrains[$trainID]['next_betriebsstellen_data'][$i]['betriebstelle'] = $nextBetriebsstellen[$i];
				$allUsedTrains[$trainID]['next_betriebsstellen_data'][$i]['zeiten'] = getFahrplanzeiten($nextBetriebsstellen[$i], $zugID);
				$allUsedTrains[$trainID]['next_betriebsstellen_data'][$i]['fahrplanhalt'] = false;
			}
		}
		$allUsedTrains[$trainID]['next_betriebsstellen_data'] = array_values($allUsedTrains[$trainID]['next_betriebsstellen_data']);
	} else {
		$allUsedTrains[$trainID]['next_betriebsstellen_data'] = array();
	}

	foreach ($allUsedTrains[$trainID]['next_betriebsstellen_data'] as $betriebsstelleKey => $betriebsstelleValue) {
		if ($allUsedTrains[$trainID]['next_betriebsstellen_data'][$betriebsstelleKey]['zeiten']['abfahrt_soll'] != null) {
			$allUsedTrains[$trainID]['next_betriebsstellen_data'][$betriebsstelleKey]['zeiten']['abfahrt_soll_timestamp'] = getUhrzeit($betriebsstelleValue['zeiten']['abfahrt_soll'], 'simulationszeit', null, array('inputtyp' => 'h:i:s'));
		} else {
			$allUsedTrains[$trainID]['next_betriebsstellen_data'][$betriebsstelleKey]['zeiten']['abfahrt_soll_timestamp'] = null;
		}

		if ($allUsedTrains[$trainID]['next_betriebsstellen_data'][$betriebsstelleKey]['zeiten']['ankunft_soll'] != null) {
			$allUsedTrains[$trainID]['next_betriebsstellen_data'][$betriebsstelleKey]['zeiten']['ankunft_soll_timestamp'] = getUhrzeit($betriebsstelleValue['zeiten']['ankunft_soll'], 'simulationszeit', null, array('inputtyp' => 'h:i:s'));
		} else {
			$allUsedTrains[$trainID]['next_betriebsstellen_data'][$betriebsstelleKey]['zeiten']['ankunft_soll_timestamp'] = null;
		}

		$allUsedTrains[$trainID]['next_betriebsstellen_data'][$betriebsstelleKey]['zeiten']['verspaetung'] = 0;
	}
}

// Gibt in der Konsole für alle Züge (oder nur einen,
// wenn eine ID übergeben wird) die aktuellen Daten
// (Adresse, ID, Zug ID, Position, Fahrplan vorhanden,
// Fehler vorhanden und die Fahrtrichtung) aus.
function consoleAllTrainsPositionAndFahrplan($id = false) {

	global $allUsedTrains;

	$checkAllTrains = true;

	if ($id != false) {
		$checkAllTrains = false;
	} else {
		echo "Alle vorhandenen Züge:\n\n";
	}

	foreach ($allUsedTrains as $train) {
		if ($checkAllTrains || $train['id'] == $id) {
			$fahrplan = null;
			$error = null;
			$zugId = null;
			if ($train['operates_on_timetable']) {
				$fahrplan = 'ja';
			} else {
				$fahrplan = 'nein';
			}

			if (sizeof($train['error']) != 0) {
				$error = 'ja';
			} else {
				$error = 'nein';
			}

			if (!isset($train['zug_id'])) {
				$zugId = '-----';
			} else {
				$zugId = $train['zug_id'];
			}

			echo 'Zug ID: ', $train['id'], ' (Adresse: ', $train['adresse'], ', Zug ID: ', $zugId, ")\t Fährt nach Fahrplan: ",
			$fahrplan, "\t Fahrtrichtung: ", $train['dir'], "\t Infra-Abschnitt: ", $train['current_section'],
			"\t\tAktuelle relative Position im Infra-Abschnitt: ", number_format($train['current_position'],2), "m\t\tFehler vorhanden:\t", $error, "\n";
		}
	}
	echo "\n";
}

// Zeigt für alle Züge, die nach Fahrplan fahren (oder nur für einen Zug,
// wenn eine ID übergeben wird) die zuletzt erreichte Betriebsstelle und
// die nächsten Betriebsstellen an.
function showFahrplan ($id = false) {

	global $allUsedTrains;

	$checkAllTrains = true;

	if ($id != false) {
		$checkAllTrains = false;
	} else {
		echo "Alle vorhandenen Fahrpläne:\n\n";
	}

	foreach ($allUsedTrains as $train) {
		if ($checkAllTrains || $train['id'] == $id) {
			$fahrplan = null;
			$error = null;
			$zugId = null;
			if ($train['operates_on_timetable']) {

				if (!isset($train['zug_id'])) {
					$zugId = '-----';
				} else {
					$zugId = $train['zug_id'];
				}

				$nextStations = '';
				$lastStation = '';

				foreach ($train['next_betriebsstellen_data'] as $bs) {
					if (!$bs['angekommen']) {
						$nextStations = $nextStations . $bs['betriebstelle'] . ' ';

					} else {
						$lastStation = $bs['betriebstelle'];
					}
				}

				if ($lastStation == '') {
					$lastStation = '---';
				}

				echo 'Zug ID: ', $train['id'], ' (Adresse: ', $train['adresse'], ', Zug ID: ', $zugId, ")\t Letzte Station: ", $lastStation, " \tNächste Stationen: ", $nextStations, "\n";
			}
		}
	}
	echo "\n";
}

// Über prüft für alle Fahrzeuge die nach Fahrplan fahren (oder nur für ein
// Fahrzeug, wenn eine ID übergeben wird), ob die Fahrtrichtung mit dem
// Fahrplan übereinstimmt, und ob diese geändert werden muss. Wenn die
// Fahrtrichtung geändert werden muss, wird die Funktion changeDirection()
// aufgerufen
function checkIfStartDirectionIsCorrect($id = false) {

	global $allUsedTrains;

	$checkAllTrains = true;

	if ($id != false) {
		$checkAllTrains = false;
		echo "Für den Fall, dass die Fahrtrichtung der Züge nicht mit dem Fahrplan übereinstimmt, wird die Richtung verändert:\n\n";
	} else {
		echo "Für den Fall, dass die Fahrtrichtung des Zuges nicht mit dem Fahrplan übereinstimmt, wird die Richtung verändert:\n\n";
	}

	foreach ($allUsedTrains as $train) {
		if ($checkAllTrains || $train['id'] == $id) {
			if ($train['operates_on_timetable']) {
				$endLoop = 0;
				for ($i = 0; $i < sizeof($train['next_betriebsstellen_data']); $i++) {
					if ($train['next_betriebsstellen_data'][$i]['angekommen']) {
						$endLoop = $i;
					}
				}

				if ($train['dir'] != $train['next_betriebsstellen_data'][$endLoop]['zeiten']['fahrtrichtung'][1]) {
					changeDirection($train['id']);
				}
			}
		}
	}
	echo "\n";
}

// Ändert die Fahrtrichtung eines Zuges, wenn das möglich ist. Sollte
// das Fahrzeug seine Richtung ändern müssen und ist dies nicht möglich,
// so wird dem Fahrzeug eine Fehlermeldung (Fehlerstatus = 0) hinzugefügt.
function changeDirection (int $id) {

	global $allUsedTrains;
	global $cacheInfraLaenge;
	global $timeDifference;
	global $allTrains;

	$section = $allUsedTrains[$id]['current_section'];
	$position = $allUsedTrains[$id]['current_position'];
	$direction = $allUsedTrains[$id]['dir'];
	$length = $allUsedTrains[$id]['zuglaenge'];
	$newTrainLength = $length + ($cacheInfraLaenge[$section] - $position);
	$newDirection = null;
	$newSection = null;
	$cumLength = 0;

	if ($direction == 0) {
		$newDirection = 1;
	} else {
		$newDirection = 0;
	}

	$newPosition = null;
	$nextSections = getNaechsteAbschnitte($section, $newDirection);
	$currentData = array(0 => array('laenge' => $cacheInfraLaenge[$section], 'infra_id' => $section));
	$mergedData = array_merge($currentData, $nextSections);

	foreach ($mergedData as $sectionValue) {
		$cumLength += $sectionValue['laenge'];

		if ($newTrainLength <= $cumLength) {
			$newSection = $sectionValue['infra_id'];
			$newPosition = $cacheInfraLaenge[$newSection] - ($cumLength - $newTrainLength);
			break;
		}
	}

	if ($newPosition == null) {
		echo 'Die Richtung des Zugs mit der ID ', $id, " lässt sich nicht ändern, weil das Zugende auf einem auf Halt stehenden Signal steht.\n";
		echo "\tDie Zuglänge beträgt:\t", $length, " m\n\tDie Distanz zwischen Zugende und dem auf Halt stehenden Signal beträgt:\t", ($cumLength - ($cacheInfraLaenge[$section] - $position)), " m\n\n";
		array_push($allUsedTrains[$id]['error'], 0);
	}  else {
		echo 'Die Richtung des Zugs mit der ID: ', $id, ' wurde auf ', $newDirection, " geändert.\n";
		$allUsedTrains[$id]['current_section'] = $newSection;
		$allUsedTrains[$id]['current_position'] = $newPosition;
		$allUsedTrains[$id]['dir'] = $newDirection;
		$allUsedTrains[$id]['earliest_possible_start_time'] = FZS_WARTEZEIT_WENDEN + time() + $timeDifference;
		$allTrains[$id]['dir'] = $newDirection;
		$DB = new DB_MySQL();
		$DB->select('UPDATE `'.DB_TABLE_FAHRZEUGE.'` SET `'.DB_TABLE_FAHRZEUGE."`.`dir` = $newDirection WHERE `".DB_TABLE_FAHRZEUGE."`.`id` = $id");
		unset($DB);
		sendFahrzeugbefehl($id, -4);
	}
}

// Gibt die vorhanden Fehlermeldungen für alle Fahrzeuge an.
function showErrors() {

	global $allUsedTrains;
	global $trainErrors;

	$foundError = false;
	echo "Hier werden für alle Züge mögliche Fehler angezeigt:\n\n";

	foreach ($allUsedTrains as $trainIndex => $trainValue) {
		if (sizeof($trainValue['error']) != 0) {
			$foundError = true;
			echo 'Zug ID: ', $trainValue['id'], "\n";
			$index = 1;

			foreach ($trainValue['error'] as $error) {
				echo "\t", $index, ". Fehler:\t", $trainErrors[$error], "\n";
				$index++;
			}

			echo "\n";
		}
	}

	if (!$foundError) {
		echo "Keiner der Züge hat eine Fehlermeldung.\n";
	}
}

// Fügt allen Fahrzeugen (oder nur einem Fahrzeug,
// wenn eine ID übergeben wird), die nach Fahrplan
// fahren, mögliche Halte-Infrastrukturabschnitte hinzu.
function addStopsectionsForTimetable($id = false) {

	global $allUsedTrains;
	global $cacheHaltepunkte;
	global $cacheZwischenhaltepunkte;

	$checkAllTrains = true;

	if ($id != false) {
		$checkAllTrains = false;
	}

	foreach ($allUsedTrains as $trainIndex => $trainValue) {
		if ($checkAllTrains || $trainValue['id'] == $id) {
			if (sizeof($trainValue['error']) == 0) {
				if ($trainValue['operates_on_timetable']) {
					foreach ($trainValue['next_betriebsstellen_data'] as $betriebsstelleKey => $betriebsstelleValue) {
						if (in_array($betriebsstelleValue['betriebstelle'], array_keys($cacheHaltepunkte))) {
							$allUsedTrains[$trainIndex]['next_betriebsstellen_data'][$betriebsstelleKey]['haltepunkte'] = $cacheHaltepunkte[$betriebsstelleValue['betriebstelle']][$trainValue['dir']];
						} else if (in_array($betriebsstelleValue['betriebstelle'], array_keys($cacheZwischenhaltepunkte))) {
							$allUsedTrains[$trainIndex]['next_betriebsstellen_data'][$betriebsstelleKey]['haltepunkte'] = array($cacheZwischenhaltepunkte[$betriebsstelleValue['betriebstelle']]);
						} else {
							$allUsedTrains[$trainIndex]['next_betriebsstellen_data'][$betriebsstelleKey]['haltepunkte'] = array();
						}
					}
				}
			}
		}
	}
}

// Ermittelt für alle Fahrzeuge (wenn keine ID übergeben wird) oder für ein
// Fahrzeug (wenn eine ID übergeben wird) die Fahrstraße inkl. der Längen,
// der zulässigen Höchstgeschwindigkeiten und der IDs der nächsten Abschnitte.
//
// Die Ergebnisse können direkt im Array $usedTrains gespeichert werden
// ($writeResultToTrain = true) oder als return zurückgegeben werden
// ($writeResultToTrain = false), so dass sie verglichen werden können
// mit den vorherigen Daten verglichen werden können.
function calculateNextSections($id = false, $writeResultToTrain = true) {

	global $allUsedTrains;
	global $cacheInfraLaenge;
	global $globalSpeedInCurrentSection;
	global $lastMaxSpeedForInfraAndDir;

	$checkAllTrains = true;

	if ($id != false) {
		$checkAllTrains = false;
	}

	foreach ($allUsedTrains as $trainIndex => $trainValue) {
		if (($checkAllTrains || $trainValue['id'] == $id) && sizeof($trainValue['error']) == 0) {
			$dir = $trainValue['dir'];
			$currentSectionComp = $trainValue['current_section'];
			$signal = getSignalForSectionAndDirection($currentSectionComp, $dir);
			$nextSectionsComp = array();
			$nextVMaxComp = array();
			$nextLengthsComp = array();
			$nextSignalbegriff = null;

			if ($signal != null) {
				$nextSignalbegriff = getSignalbegriff($signal);
				$nextSignalbegriff = $nextSignalbegriff[array_key_last($nextSignalbegriff)]['geschwindigkeit'];
				if ($nextSignalbegriff == -25) {
					$nextSignalbegriff = 25;
				} else if ($nextSignalbegriff <= 0) {
					$nextSignalbegriff = 0;
				}
			} else {
				$nextSignalbegriff = null;
			}

			$return = getNaechsteAbschnitte($currentSectionComp, $dir);
			$allUsedTrains[$trainIndex]['last_get_naechste_abschnitte'] = $return;

			if (isset($lastMaxSpeedForInfraAndDir[$trainValue['dir']][$trainValue['current_section']])) {
				$currentVMax = $lastMaxSpeedForInfraAndDir[$trainValue['dir']][$trainValue['current_section']];
			} else {
				$currentVMax = $globalSpeedInCurrentSection;
			}

			array_push($nextSectionsComp, $currentSectionComp);
			array_push($nextVMaxComp, $currentVMax);
			array_push($nextLengthsComp, $cacheInfraLaenge[$currentSectionComp]);

			if (isset($nextSignalbegriff)) {
				$currentVMax = $nextSignalbegriff;
			}

			if ($currentVMax == 0) {
				if ($writeResultToTrain) {
					$allUsedTrains[$trainIndex]['next_sections'] = $nextSectionsComp;
					$allUsedTrains[$trainIndex]['next_lenghts'] = $nextLengthsComp;
					$allUsedTrains[$trainIndex]['next_v_max'] = $nextVMaxComp;
				} else {
					return array($nextSectionsComp, $nextLengthsComp, $nextVMaxComp);
				}
			} else {
				foreach ($return as $section) {
					array_push($nextSectionsComp, $section['infra_id']);
					array_push($nextVMaxComp, $currentVMax);
					array_push($nextLengthsComp, $cacheInfraLaenge[$section['infra_id']]);
					$lastMaxSpeedForInfraAndDir[intval($trainValue['dir'])][intval($section['infra_id'])] = intval($currentVMax);
					if ($section['signal_id'] != null) {
						$signal = $section['signal_id'];
						$nextSignalbegriff = getSignalbegriff($signal);
						$nextSignalbegriff = $nextSignalbegriff[array_key_last($nextSignalbegriff)]['geschwindigkeit'];
						if ($nextSignalbegriff == -25) {
							$currentVMax = 25;
						} else if ($nextSignalbegriff < 0) {
							$currentVMax = 0;
						} else {
							$currentVMax = $nextSignalbegriff;
						}
					}
				}
				if ($writeResultToTrain) {
					$allUsedTrains[$trainIndex]['next_sections'] = $nextSectionsComp;
					$allUsedTrains[$trainIndex]['next_lenghts'] = $nextLengthsComp;
					$allUsedTrains[$trainIndex]['next_v_max'] = $nextVMaxComp;
				} else {
					return array($nextSectionsComp, $nextLengthsComp, $nextVMaxComp);
				}
			}
		}
	}
}

// Prüft für alle Fahrzeuge (falls keine ID übergeben wird)
// oder für ein Fahrzeug (falls eine ID übergeben wird),
// ob das Fahrzeug bereits am ersten fahrplanmäßigen
// Halt ist oder nicht.
function checkIfTrainReachedHaltepunkt ($id = false) {

	global $allUsedTrains;
	global $cacheInfraToGbt;
	global $cacheGbtToInfra;

	$checkAllTrains = true;

	if ($id != false) {
		$checkAllTrains = false;
	}

	foreach ($allUsedTrains as $trainIndex => $trainValue) {
		if ($checkAllTrains || $trainValue['id'] == $id) {
			$currentInfrasection = $trainValue['current_section'];
			$currentGbt = $cacheInfraToGbt[$currentInfrasection];
			$allInfraSections = $cacheGbtToInfra[$currentGbt];
			for ($i = 0; $i < sizeof($trainValue['next_betriebsstellen_data']); $i++) {
				if (sizeof(array_intersect($trainValue['next_betriebsstellen_data'][$i]['haltepunkte'], $allInfraSections)) != 0) {
					$allUsedTrains[$trainIndex]['next_betriebsstellen_data'][$i]['angekommen'] = true;
					for ($j = 0; $j < $i; $j++) {
						$allUsedTrains[$trainIndex]['next_betriebsstellen_data'][$j]['angekommen'] = true;
					}
				} else {
					$allUsedTrains[$trainIndex]['next_betriebsstellen_data'][$i]['angekommen'] = false;
				}
			}
		}
	}
}

// Prüft für alle Fahrzeuge (falls keine ID übergeben wird)
// oder für ein Fahrzeug (falls eine ID übergeben wird),
// ob die Fahrstraße aktuell richtig eingestellt ist,
// sodass die nächste Betriebsstelle laut Fahrplan
// erreicht werden kann.
//
// Für Züge ohne Fahrplan ist der Fahrweg immer korrekt.
function checkIfFahrstrasseIsCorrrect($id = false) {

	global $allUsedTrains;

	$checkAllTrains = true;

	if ($id != false) {
		$checkAllTrains = false;
	}

	foreach ($allUsedTrains as $trainIndex => $trainValue) {
		if (($checkAllTrains || $trainValue['id'] == $id) && sizeof($trainValue['error']) == 0) {
			if ($trainValue['operates_on_timetable']) {
				$allUsedTrains[$trainIndex]['fahrstrasse_is_correct'] = false;
				foreach ($trainValue['next_betriebsstellen_data'] as $stopIndex => $stopValue) {
					if (!$stopValue['angekommen']) {
						$allUsedTrains[$trainIndex]['next_betriebsstellen_data'][$stopIndex]['is_on_fahrstrasse'] = false;
						$allUsedTrains[$trainIndex]['next_betriebsstellen_data'][$stopIndex]['used_haltepunkt'] = array();
						$indexSection = 0;
						for ($i = 0; $i < sizeof($trainValue['next_sections']); $i++) {
							if ($stopValue['haltepunkte'] != null) {
								if (in_array($trainValue['next_sections'][$i], $stopValue['haltepunkte'])) {
									if ($i >= $indexSection) {
										$allUsedTrains[$trainIndex]['next_betriebsstellen_data'][$stopIndex]['is_on_fahrstrasse'] = true;
										$allUsedTrains[$trainIndex]['next_betriebsstellen_data'][$stopIndex]['used_haltepunkt'] = $trainValue['next_sections'][$i];
										$allUsedTrains[$trainIndex]['fahrstrasse_is_correct'] = true;
										$i = sizeof($trainValue['next_sections']);
										$indexSection = $i;
									}
								}
							}
						}
					} else {
						$allUsedTrains[$trainIndex]['next_betriebsstellen_data'][$stopIndex]['is_on_fahrstrasse'] = true;
					}
				}
			} else {
				$allUsedTrains[$trainIndex]['fahrstrasse_is_correct'] = true;
			}
		}
	}
}

// Berechnet die Beschleunigungs- und Bremskurven für alle Züge (wenn keine ID
// übergeben wird) oder für einen Zug (wenn eine ID übergeben wird). Für Züge -
// die nach Fahrplan fahren - für alle Betriebsstellen, die auf der aktuell
// eingestellten Strecke liegen und für Züge ohne Fahrplan bis zum nächsten
// roten Signal.
function calculateFahrtverlauf($id = false, $recalibrate = false) {

	global $allUsedTrains;
	global $cacheInfraLaenge;
	global $timeDifference;
	global $globalFirstHaltMinTime;

	$checkAllTrains = true;

	if ($id != false) {
		$checkAllTrains = false;
	}

	foreach ($allUsedTrains as $trainIndex => $trainValue) {
		$allPossibleStops = array();
		for($i = 0; $i < sizeof($trainValue['next_betriebsstellen_data']); $i++) {
			if ($trainValue['next_betriebsstellen_data'][$i]['fahrplanhalt']) {
				array_push($allPossibleStops, $i);
			}
		}
		if (sizeof($trainValue['error']) == 0 && $trainValue['fahrstrasse_is_correct']) {
			if ($checkAllTrains || $trainValue['id'] == $id) {
				if ($trainValue['operates_on_timetable']) {
					$nextBetriebsstelleIndex = null;
					$allreachedInfras = array();
					$wendet = false;
					for ($i = 0; $i < sizeof($trainValue['next_betriebsstellen_data']); $i++) {
						if (!$trainValue['next_betriebsstellen_data'][$i]['angekommen'] && $trainValue['next_betriebsstellen_data'][$i]['is_on_fahrstrasse'] && $trainValue['next_betriebsstellen_data'][$i]['fahrplanhalt']) {
							$nextBetriebsstelleIndex = $i;
							$allUsedTrains[$trainIndex]['next_bs'] = $i;
							break;
						}
					}
					if (!isset($nextBetriebsstelleIndex)) {
						for ($i = 0; $i < sizeof($trainValue['next_betriebsstellen_data']); $i++) {
							if (!$trainValue['next_betriebsstellen_data'][$i]['angekommen'] && $trainValue['next_betriebsstellen_data'][$i]['is_on_fahrstrasse']) {
								$nextBetriebsstelleIndex = $i;
								break;
							}
						}
					}
					if (isset($nextBetriebsstelleIndex)) {
						if ($allUsedTrains[$trainIndex]['next_bs'] != $trainValue['next_betriebsstellen_data'][$nextBetriebsstelleIndex]['betriebstelle'] || $recalibrate) {
							$allUsedTrains[$trainIndex]['next_bs'] = $trainValue['next_betriebsstellen_data'][$nextBetriebsstelleIndex]['betriebstelle'];
							if (intval($trainValue['next_betriebsstellen_data'][$nextBetriebsstelleIndex]['zeiten']['wendet']) == 1) {
								$wendet = true;
							}
							for ($i = 0; $i < sizeof($trainValue['next_betriebsstellen_data']); $i++) {
								if (!$trainValue['next_betriebsstellen_data'][$i]['angekommen'] && $trainValue['next_betriebsstellen_data'][$i]['is_on_fahrstrasse'] && $i <= $nextBetriebsstelleIndex) {
									array_push($allreachedInfras, array('index' => $i, 'infra' => $trainValue['next_betriebsstellen_data'][$i]['used_haltepunkt']));
								}
							}
							$targetSection = $trainValue['next_betriebsstellen_data'][$nextBetriebsstelleIndex]['used_haltepunkt'];
							$targetPosition = $cacheInfraLaenge[$targetSection];
							$startTime = null;
							$endTime = null;
							$prevBetriebsstelle = null;
							for ($i = 0; $i < sizeof($trainValue['next_betriebsstellen_data']); $i++) {
								if ($trainValue['next_betriebsstellen_data'][$i]['angekommen']) {
									$prevBetriebsstelle = $i;
									break;
								}
							}
							if ($nextBetriebsstelleIndex == 0) {
								$startTime = microtime(true) + $timeDifference;
								$endTime = $startTime;
							} else {
								$endTime = $trainValue['next_betriebsstellen_data'][$nextBetriebsstelleIndex]['zeiten']['ankunft_soll_timestamp'];
								if (isset($prevBetriebsstelle)) {
									if ($trainValue['next_betriebsstellen_data'][$prevBetriebsstelle]['zeiten']['verspaetung'] > 0) {
										$startTime = $trainValue['next_betriebsstellen_data'][$prevBetriebsstelle]['zeiten']['abfahrt_soll_timestamp'] + $trainValue['next_betriebsstellen_data'][$nextBetriebsstelleIndex - 1]['zeiten']['verspaetung'];
									} else {
										$startTime = $trainValue['next_betriebsstellen_data'][$prevBetriebsstelle]['zeiten']['abfahrt_soll_timestamp'];
									}
								} else {
									$startTime = microtime(true) + $timeDifference;
								}
							}
							$reachedBetriebsstele = true;

							if ($startTime < microtime(true) + $timeDifference) {
								$startTime = microtime(true) + $timeDifference;
							}

							if (isset($trainValue['earliest_possible_start_time'])) {
								if ($startTime < $trainValue['earliest_possible_start_time']) {
									$startTime = $trainValue['earliest_possible_start_time'];
								}
							}

							$verapetung = updateNextSpeed($trainValue, $startTime, $endTime, $targetSection, $targetPosition, $reachedBetriebsstele, $nextBetriebsstelleIndex, $wendet, false, $allreachedInfras);

							if ($nextBetriebsstelleIndex != 0) {
								$allUsedTrains[$trainIndex]['next_betriebsstellen_data'][$nextBetriebsstelleIndex]['zeiten']['verspaetung'] = $verapetung;
								$trainValue['next_betriebsstellen_data'][$nextBetriebsstelleIndex]['zeiten']['verspaetung'] = $verapetung;
							} else {
								$end = $allUsedTrains[$trainIndex]['next_betriebsstellen_data'][$nextBetriebsstelleIndex]['zeiten']['abfahrt_soll_timestamp'];
								$start = $startTime;
								if ($start + $verapetung + $globalFirstHaltMinTime < $end) {
									$allUsedTrains[$trainIndex]['next_betriebsstellen_data'][$nextBetriebsstelleIndex]['zeiten']['verspaetung'] = 0;
									$trainValue['next_betriebsstellen_data'][$nextBetriebsstelleIndex]['zeiten']['verspaetung'] = 0;
								} else {
									$allUsedTrains[$trainIndex]['next_betriebsstellen_data'][$nextBetriebsstelleIndex]['zeiten']['verspaetung'] = $start + $verapetung + $globalFirstHaltMinTime - $end;
									$trainValue['next_betriebsstellen_data'][$nextBetriebsstelleIndex]['zeiten']['verspaetung'] = $start + $verapetung + $globalFirstHaltMinTime - $end;
								}
							}
						}
					} else {
						if ($trainValue['current_speed'] > 0) {
							emergencyBreak($trainValue['id']);
						}
					}
				} else {
					$startTime = microtime(true) + $timeDifference;
					if (isset($trainValue['earliest_possible_start_time'])) {
						if ($startTime < $trainValue['earliest_possible_start_time']) {
							$startTime = $trainValue['earliest_possible_start_time'];
						}
					}
					$endTime = $startTime;
					$targetSection = null;
					$targetPosition = null;
					$reachedBetriebsstele = true;
					$wendet = false;
					$signalId = null;
					for ($i = 0; $i < sizeof($trainValue['last_get_naechste_abschnitte']); $i++) {
						if (isset($trainValue['last_get_naechste_abschnitte'][$i]['signal_id'])) {
							$signalId = $trainValue['last_get_naechste_abschnitte'][$i]['signal_id'];
							$targetSection = $trainValue['last_get_naechste_abschnitte'][$i]['infra_id'];
							$targetPosition = $cacheInfraLaenge[$targetSection];
						}
					}
					if (!isset($signalId)) {
						if ($trainValue['current_speed'] != 0) {
							emergencyBreak($trainValue['id']);
						}
					} else {
						$signal = getSignalbegriff($signalId)[0]['geschwindigkeit'];

						if ($signal > -25 && $signal < 0) {
							$wendet = true;
						}

						updateNextSpeed($trainValue, $startTime, $endTime, $targetSection, $targetPosition, $reachedBetriebsstele, $signalId, $wendet, true, array());
					}
				}
			}
		} else {
			if ($trainValue['current_speed'] != 0) {
				emergencyBreak($trainValue['id']);
			}
		}
	}
}

// Vergleicht für ein Fahrzeug die zuletzt ermittelte
// Fahrstraße mit der aktuellen Fahrstraße und berechnet
// den Fahrtverlauf neu, wenn das nötig ist.
function compareTwoNaechsteAbschnitte(int $id) {

	global $allUsedTrains;
	global $allTimes;

	if (sizeof($allUsedTrains[$id]['error']) == 0) {
		$newSections = calculateNextSections($id, false);
		$newNextSection = $newSections[0];
		$newNextLenghts = $newSections[1];
		$newNextVMax = $newSections[2];
		$oldNextSections = $allUsedTrains[$id]['next_sections'];
		$oldLenghts = $allUsedTrains[$id]['next_lenghts'];
		$oldNextVMax = $allUsedTrains[$id]['next_v_max'];
		$currentSectionOld = $allUsedTrains[$id]['current_section'];
		$keyCurrentSection = array_search($currentSectionOld, $oldNextSections);
		$keyLatestSection = array_key_last($oldNextSections);
		$dataIsIdentical = true;
		$numberOfSection = $keyLatestSection - $keyCurrentSection + 1;
		$compareNextSections = array();
		$compareNextLenghts = array();
		$compareNextVMax = array();

		for($i = $keyCurrentSection; $i <= $keyLatestSection; $i++) {
			array_push($compareNextSections, $oldNextSections[$i]);
			array_push($compareNextLenghts, $oldLenghts[$i]);
			array_push($compareNextVMax, $oldNextVMax[$i]);
		}

		if (sizeof($newNextSection) != ($numberOfSection)) {
			$dataIsIdentical = false;
		} else {
			for ($i = 0; $i < $keyLatestSection - $keyCurrentSection; $i++) {
				if ($newNextSection[$i] != $compareNextSections[$i] || $newNextLenghts[$i] != $compareNextLenghts[$i] || $newNextVMax[$i] != $compareNextVMax[$i]) {
					$dataIsIdentical = false;
					break;
				}
			}
		}

		if (!$dataIsIdentical) {
			echo 'Die Fahrstraße des Zuges mit der ID: ', $id, " hat sich geändert.\n";
			calculateNextSections($id);
			$adresse = $allUsedTrains[$id]['adresse'];
			$allTimes[$adresse] = array();
			checkIfFahrstrasseIsCorrrect($id);
			calculateFahrtverlauf($id);
		}
	}
}