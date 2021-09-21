<?php

// Liest alle benötigten Dateien ein
require 'config/multicast.php';
require 'vorbelegung.php';
require 'functions/functions.php';
require 'functions/functions_cache.php';
require 'functions/functions_db.php';
require 'functions/functions_math.php';
require 'functions/functions_ebuef.php';
require 'functions/functions_fahrtverlauf.php';
require 'global_variables.php';

// Zeitzone setzen
date_default_timezone_set("Europe/Berlin");

// PHP-Fehlermeldungen
error_reporting(1);

// Globale Variablen
global $useRecalibration;

// Fahrzeugfehlermeldungen definieren
$trainErrors = array();
$trainErrors[0] = "Fahrtrichtung des Fahrzeugs musste geändert werden und die Positionsbestimmung war nicht möglich.";
$trainErrors[1] = "In der Datenbank ist für das Fahrzeug keine Zuglänge angegeben.";
$trainErrors[2] = "In der Datenbank ist für das Fahrzeug keine v_max angegeben.";
$trainErrors[3] = "Das Fahrzeug musste eine Notbremsung durchführen.";

// Statische Daten einlesen
$cacheInfranachbarn = createCacheInfranachbarn();
$cacheInfradaten = createCacheInfradaten();
$cacheSignaldaten = createCacheSignaldaten();
$cacheInfraLaenge = createcacheInfraLaenge();
$cacheHaltepunkte = createCacheHaltepunkte();
$cacheZwischenhaltepunkte = createChacheZwischenhaltepunkte();
$cacheInfraToGbt = createCacheInfraToGbt();
$cacheGbtToInfra = createCacheGbtToInfra();
$cacheFmaToInfra = createCacheFmaToInfra();
$cacheInfraToFma = array_flip($cacheFmaToInfra);
$cacheFahrplanSession = createCacheFahrplanSession();
$cacheSignalIDToBetriebsstelle = createCacheToBetriebsstelle();
$cacheFahrzeugeAbschnitte = createCacheFahrzeugeAbschnitte();
$cacheIDTDecoder = createCacheDecoderToAdresse();
$cacheDecoderToID = array_flip($cacheIDTDecoder);
$cacheAdresseToID = array();		// Filled with data in getAllTrains()
$cacheIDToAdresse = array();		// Filled with data in getAllTrains()

// Variablendeklaration
$allTrainsOnTheTrack = array();
$allTrains = array();
$allUsedTrains = array();
$allTimes = array();
$lastMaxSpeedForInfraAndDir = array();

// Real- und Simulationszeit ermitteln
$simulationStartTimeToday = getUhrzeit(getUhrzeit($cacheFahrplanSession->sim_startzeit, "simulationszeit", null, array("outputtyp"=>"h:i:s")), "simulationszeit", null, array("inputtyp"=>"h:i:s"));
$simulationEndTimeToday = getUhrzeit(getUhrzeit($cacheFahrplanSession->sim_endzeit, "simulationszeit", null, array("outputtyp"=>"h:i:s")), "simulationszeit", null, array("inputtyp"=>"h:i:s"));
$simulationDuration = $cacheFahrplanSession->sim_endzeit - $cacheFahrplanSession->sim_startzeit;
$realStartTime = time();
$realEndTime = $realStartTime + $simulationDuration;
$timeDifference = $simulationStartTimeToday - $realStartTime;

// Startmeldung
startMessage();

// Ermittlung aller Fahrzeuge
$allTrains = getAllTrains();

// Ermittlung der Fahrzeuge im eingleisigen Netz
findTrainsOnTheTracks();

// Ermittlung der Fahrpläne der Fahrzeuge
addStopsectionsForTimetable();

// Überprüfung, ob die Fahrzeuge schon an einer Betriebsstelle des Fahrplans stehen
checkIfTrainReachedHaltepunkt();

// Überprüfung, ob die Fahrtrichtung der Fahrzeuge mit dem
// Fahrplan übereinstimmt. Falls die Richtung nicht übereinstimmt,
// wird die Fahrtrichtung der Fahrzeuge geändert
checkIfStartDirectionIsCorrect();
consoleAllTrainsPositionAndFahrplan();
showErrors();

// Ermittlung der Fahrstraßen aller Fahrzeuge
calculateNextSections();

// Überprüfung, ob die Fahrstraße für die Fahrzeuge mit Fahrplan
// richtig eingestellt ist
checkIfFahrstrasseIsCorrrect();

// Ermittlung der Fahrtverläufe aller Fahrzeuge
calculateFahrtverlauf();

// Übermittlung der Echtzeitdaten an die Fahrzeuge
// $timeCheckFahrstrasseInterval => Überprüfung von Fahrstraßenänderungen
// $timeCheckAllTrainErrorsInterval => Ausgabe der aktuellen Positionen und Fahrplänen
// $timeCheckCalibrationInterval => Neukalibrierung der POsition
$timeCheckFahrstrasseInterval = 3;
$timeCheckFahrstrasse = $timeCheckFahrstrasseInterval + microtime(true);
$timeCheckAllTrainStatusInterval = 30;
$timeCheckAllTrainStatus = $timeCheckAllTrainStatusInterval + microtime(true);
$timeCheckCalibrationInterval = 3;
$timeCheckCalibration = $timeCheckCalibrationInterval + microtime(true);

// Zeitintervall, in dem überprüft wird, ob neue Echtzeitdaten vorliegen
$sleeptime = 0.03;
while (true) {

	// Iteration über alle Fahrzeuge
	foreach ($allTimes as $timeIndex => $timeValue) {
		if (sizeof($timeValue) > 0) {
			$id = $timeValue[0]["id"];

			// Überprüfung, ob der erste Eintrag der Echtzeitdaten in der
			// Vergangenheit liegt
			if ((microtime(true) + $timeDifference) > $timeValue[0]["live_time"]) {

				// Überprüfung, ob der Eintrag der Echtzeitdaten eine
				// Geschwindigkeitsveränderung beinhaltet
				if ($timeValue[0]["live_is_speed_change"]) {
					$allUsedTrains[$id]["calibrate_section_one"] = null;
					$allUsedTrains[$id]["calibrate_section_two"] = null;

					// Übermittlung der Echtzeitdaten bei einer Gefahrenbremsung
					if ($timeValue[0]["betriebsstelle"] == 'Notbremsung') {
						sendFahrzeugbefehl($timeValue[0]["id"], intval($timeValue[0]["live_speed"]));
						$allTrains[$id]["speed"] = intval($timeValue[0]["live_speed"]);
						echo "Der Zug mit der Adresse ", $timeIndex, " leitet gerade eine Gefahrenbremsung ein und hat seine Geschwindigkeit auf ", $timeValue[0]["live_speed"], " km/h angepasst.\n";
					} else {

						// Übermittlung der neuen Geschwindigkeit an das Fahrzeug
						sendFahrzeugbefehl($timeValue[0]["id"], intval($timeValue[0]["live_speed"]));
						$allTrains[$id]["speed"] = intval($timeValue[0]["live_speed"]);
						echo "Der Zug mit der Adresse ", $timeIndex, " hat auf der Fahrt nach ", $timeValue[0]["betriebsstelle"],
						" seine Geschwindigkeit auf ", $timeValue[0]["live_speed"], " km/h angepasst.\n";
					}
				} else {
					if (isset($allUsedTrains[$id]["calibrate_section_one"])) {
						if ($allUsedTrains[$id]["calibrate_section_one"] != $timeValue[0]["live_section"]) {
							$allUsedTrains[$id]["calibrate_section_two"] = $timeValue[0]["live_section"];
						}
					}
					$allUsedTrains[$id]["calibrate_section_one"] = $timeValue[0]["live_section"];
				}

				// Aktualisierung der Position im $allUsedTrains-Array
				$allUsedTrains[$id]["current_position"] = $timeValue[0]["live_relative_position"];
				$allUsedTrains[$id]["current_speed"] = $timeValue[0]["live_speed"];
				$allUsedTrains[$id]["current_section"] = $timeValue[0]["live_section"];

				// Überprüfung, ob die Fahrtrichtung geändert werden muss
				if ($timeValue[0]["wendet"]) {
					changeDirection($timeValue[0]["id"]);
				}

				// Überprüfung, ob das Fahrzeug eine Betriebsstelle erreicht hat
				if (isset($timeValue[0]["live_all_targets_reached"])) {
					$allUsedTrains[$id]["next_betriebsstellen_data"][$timeValue[0]["live_all_targets_reached"]]["angekommen"] = true;
					echo "Der Zug mit der Adresse ", $timeIndex, " hat den Halt ", $allUsedTrains[$id]["next_betriebsstellen_data"][$timeValue[0]["live_all_targets_reached"]]["betriebstelle"], " erreicht.\n";
				}

				// Überprüfung, ob ein (neuer) Fahrplan für das Fahrzeug
				// vorliegt, wenn das ermittelte Ziel erreicht wurde
				if ($timeValue[0]["live_target_reached"]) {

					$currentZugId = $allUsedTrains[$id]["zug_id"];
					$newZugId = getFahrzeugZugIds(array($id));

					if (sizeof($newZugId) == 0) {
						$newZugId = null;
					} else {
						$newZugId = getFahrzeugZugIds(array($timeValue[0]["id"]));
						$newZugId = $newZugId[array_key_first($newZugId)]["zug_id"];
					}

					if (!($currentZugId == $newZugId && $currentZugId != null)) {
						if ($currentZugId != null && $newZugId != null) {
							// Das Fahrzeug hat einen neuen Fahrplan
							$allUsedTrains[$id]["zug_id"] = $newZugId;
							$allUsedTrains[$id]["operates_on_timetable"] = true;
							getFahrplanAndPositionForOneTrain($id, $newZugId);
							addStopsectionsForTimetable($id);
							checkIfTrainReachedHaltepunkt($id);
							checkIfStartDirectionIsCorrect($id);
							calculateNextSections($id);
							checkIfFahrstrasseIsCorrrect($id);
							calculateFahrtverlauf($id);
						} else if ($currentZugId == null && $newZugId != null) {
							// Das Fahrzeug hat jetzt einen Fahrplan und
							// hatte davor keinen
							$allUsedTrains[$id]["zug_id"] = $newZugId;
							$allUsedTrains[$id]["operates_on_timetable"] = true;
							getFahrplanAndPositionForOneTrain($id);
							addStopsectionsForTimetable($id);
							checkIfTrainReachedHaltepunkt($id);
							checkIfStartDirectionIsCorrect($id);
							calculateNextSections($id);
							checkIfFahrstrasseIsCorrrect($id);
							calculateFahrtverlauf($id);
						} else if ($currentZugId != null && $newZugId == null) {
							// Das Fahrzeug fährt ab jetzt ohne Fahrplan
							$allUsedTrains[$id]["operates_on_timetable"] = false;
							calculateNextSections($id);
							calculateFahrtverlauf($id);
						}
					}
				}
				array_shift($allTimes[$timeIndex]);
			}
		}
	}

	// Neukalibrierung der Position
	if ($useRecalibration) {
		if (microtime(true) > $timeCheckCalibration) {
			foreach ($allUsedTrains as $trainKey => $trainValue) {
				if (isset($allUsedTrains[$trainKey]["calibrate_section_two"])) {
					$newPosition = getCalibratedPosition($trainKey, $allUsedTrains[$trainKey]["current_speed"]);
					if ($newPosition["possible"]) {
						echo "Die Position des Fahrzeugs mit der ID: ", $trainKey, " wird neu ermittelt.\n";
						$position = $newPosition["position"];
						$section = $newPosition["section"];
						echo "Die alte Position war Abschnitt: ", $allUsedTrains[$trainKey]["current_section"], " (", number_format($allUsedTrains[$trainKey]["current_position"], 2), " m) und die neue Position ist Abschnitt: ", $section, " (", number_format($position, 2), " m).\n";
						if ($position > $cacheInfraLaenge[$section]) {
							echo "Die Position konnte nicht neu kalibriert werden, da die aktuelle Position im Abschnitt größer ist, als die Länge des Abschnitts.\n";
						} else {
							$allUsedTrains[$trainKey]["current_section"] = $section;
							$allUsedTrains[$trainKey]["current_position"] = $position;
							calculateNextSections($trainKey);
							checkIfFahrstrasseIsCorrrect($trainKey);
							calculateFahrtverlauf($trainKey, true);
							echo "Die Position des Fahrzeugs mit der ID: ", $trainKey, " wurde neu ermittelt.\n";
						}
					}
				}
			}
			$timeCheckCalibration = $timeCheckCalibration + $timeCheckCalibrationInterval;
		}
	}

	// Überprüfung, ob die Fahrstraße der einzelnen Fahrzeuge sich geändert hat
	if (microtime(true) > $timeCheckFahrstrasse) {
		foreach ($allUsedTrains as $trainID => $trainValue) {
			compareTwoNaechsteAbschnitte($trainID);
		}

		$returnUpdate = updateAllTrainsOnTheTrack();
		$newTrains = $returnUpdate["new"];
		$removeTrains = $returnUpdate["removed"];

		if (sizeof($newTrains) > 0) {
			echo "Neu hinzugefügte Züge: \n";
			foreach ($newTrains as $newTrain) {
				$id = $cacheDecoderToID[$newTrain];
				echo "\tID:\t", $id, "\tAdresse:\t", $newTrain;
			}
			echo "\n";
		}

		foreach ($newTrains as $newTrain) {
			$id = $cacheDecoderToID[$newTrain];
			prepareTrainForRide($newTrain);
			addStopsectionsForTimetable($id);
			checkIfTrainReachedHaltepunkt($id);
			checkIfStartDirectionIsCorrect($id);
			consoleAllTrainsPositionAndFahrplan($id);
			calculateNextSections($id);
			checkIfFahrstrasseIsCorrrect($id);
			calculateFahrtverlauf($id);
		}

		if (sizeof($removeTrains) > 0) {
			echo "Entfernte Züge:\n";

			foreach ($removeTrains as $removeTrain) {
				$id = $cacheDecoderToID[$removeTrain];
				unset($allUsedTrains[$id]);
				echo "\tID:\t", $id, "\tAdresse:\t", $removeTrain;
			}

			echo "\n";
		}
		$timeCheckFahrstrasse = $timeCheckFahrstrasse + $timeCheckFahrstrasseInterval;
	}

	// Ausgabe der aktuellen Positionen, Fahrplänen und Fehlermeldungen aller Fahrzeuge
	if (microtime(true) > $timeCheckAllTrainStatus) {
		consoleAllTrainsPositionAndFahrplan();
		showFahrplan();
		showErrors();
		$timeCheckAllTrainStatus = $timeCheckAllTrainStatus + $timeCheckAllTrainStatusInterval;
	}

	sleep($sleeptime);
}









