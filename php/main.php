<?php
// Load all required external files
require 'config/multicast.php';
require 'vorbelegung.php';
require 'functions/sort_functions.php';
require 'functions/cache_functions_own.php';
require 'functions/ebuef_functions.php';
require 'functions/fahrtverlauf_functions.php';
require 'globalVariables.php';


// Set timezone
date_default_timezone_set("Europe/Berlin");

// Set memory
//ini_set('memory_limit', '1024M');

// Reports only errors
error_reporting(1);

// Global Variables
global $useRecalibration;

// Define own train errors
$trainErrors = array();
$trainErrors[0] = "Zug stand falsch herum und war zu lang um die Richtung zu ändern.";
$trainErrors[1] = "In der Datenbank ist für den Zug keine Zuglänge angegeben.";
$trainErrors[2] = "In der Datenbank ist für den Zug keine v_max angegeben.";
$trainErrors[3] = "Zug musste eine Notbremsung durchführen.";

// Load static data from the databse into the cache
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

// Global variables
$allTrainsOnTheTrack = array();		// All adresses found on the tracks
$allTrains = array();				// All trains with the status 1 or 2
$allUsedTrains = array();			// All trains with the status 1 or 2 that are standing on the tracks
$allTimes = array();
$lastMaxSpeedForInfraAndDir = array();

// Get simulation and real time
$simulationStartTimeToday = getUhrzeit(getUhrzeit($cacheFahrplanSession->sim_startzeit, "simulationszeit", null, array("outputtyp"=>"h:i:s")), "simulationszeit", null, array("inputtyp"=>"h:i:s"));
$simulationEndTimeToday = getUhrzeit(getUhrzeit($cacheFahrplanSession->sim_endzeit, "simulationszeit", null, array("outputtyp"=>"h:i:s")), "simulationszeit", null, array("inputtyp"=>"h:i:s"));
$simulationDuration = $cacheFahrplanSession->sim_endzeit - $cacheFahrplanSession->sim_startzeit;
$realStartTime = time();
$realEndTime = $realStartTime + $simulationDuration;
$timeDifference = $simulationStartTimeToday - $realStartTime;

var_dump($cacheInfraLaenge);

// Start Message
startMessage();

// Load all trains
$allTrains = getAllTrains();

// Loads all trains that are in the rail network and prepares everything for the start
findTrainsOnTheTracks();

// Checks if the trains are in the right direction and turns them if it is necessary and possible.
consoleCheckIfStartDirectionIsCorrect();
consoleAllTrainsPositionAndFahrplan();
showErrors();

// Adds all the stops of the trains.
addStopsectionsForTimetable();

// Adds an index (address) to the $allTimes array for each train.
initalFirstLiveData();

// Determination of the current routes of all trains.
calculateNextSections();

// Checks whether the trains are already at the first scheduled stop or not.
checkIfTrainReachedHaltepunkt();

// Checks whether the routes are set correctly.
checkIfFahrstrasseIsCorrrect();

// Calculate driving curve
calculateFahrverlauf();

//$unusedTrains = array_keys($allTimes);
$timeCheckAllTrainsInterval = 3;
$timeCheckAllTrains = $timeCheckAllTrainsInterval + microtime(true);
$timeCheckAllTrainErrorsInterval = 30;
$timeCheckAllTrainErrors = $timeCheckAllTrainErrorsInterval + microtime(true);
$timeCheckCalibrationInterval = 3;
$timeCheckCalibration = $timeCheckCalibrationInterval + microtime(true);
$sleeptime = 0.03;
while (true) {
	foreach ($allTimes as $timeIndex => $timeValue) {
		if (sizeof($timeValue) > 0) {
			$id = $timeValue[0]["id"];
			if ((microtime(true) + $timeDifference) > $timeValue[0]["live_time"]) {
				if ($timeValue[0]["live_is_speed_change"]) {
					$allUsedTrains[$id]["calibrate_section_one"] = null;
					$allUsedTrains[$id]["calibrate_section_two"] = null;
					if ($timeValue[0]["betriebsstelle"] == 'Notbremsung') {
						sendFahrzeugbefehl($timeValue[0]["id"], intval($timeValue[0]["live_speed"]));
						echo "Der Zug mit der Adresse ", $timeIndex, " leitet gerade eine Gefahrenbremsung ein und hat seine Geschwindigkeit auf ", $timeValue[0]["live_speed"], " km/h angepasst.\n";
					} else {
						sendFahrzeugbefehl($timeValue[0]["id"], intval($timeValue[0]["live_speed"]));
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

				$allUsedTrains[$id]["current_position"] = $timeValue[0]["live_relative_position"];
				$allUsedTrains[$id]["current_speed"] = $timeValue[0]["live_speed"];
				$allUsedTrains[$id]["current_section"] = $timeValue[0]["live_section"];

				if ($timeValue[0]["wendet"]) {
					//$allTimes[$timeIndex] = array();
					changeDirection($timeValue[0]["id"]);
				}

				if (isset($timeValue[0]["live_all_targets_reached"])) {
					$allUsedTrains[$id]["next_betriebsstellen_data"][$timeValue[0]["live_all_targets_reached"]]["angekommen"] = true;
					echo "Der Zug mit der Adresse ", $timeIndex, " hat den Halt ", $allUsedTrains[$id]["next_betriebsstellen_data"][$timeValue[0]["live_all_targets_reached"]]["betriebstelle"], " erreicht.\n";
				}

				if ($timeValue[0]["live_target_reached"]) {

					$currentZugId = $allUsedTrains[$id]["zug_id"];
					$newZugId = getFahrzeugZugIds(array($id));

					if (sizeof($newZugId) == 0) {
						$newZugId = null;
					} else {
						$newZugId = getFahrzeugZugIds(array($timeValue[0]["id"]));
						$newZugId = $newZugId[array_key_first($newZugId)]["zug_id"];
					}

					// Fährt nach Fahrplan und hat keine neue Zug ID bekommen
					if (!($currentZugId == $newZugId && $currentZugId != null)) {
						if ($currentZugId != null && $newZugId != null) {
							// neuer fahrplan
							$allUsedTrains[$id]["operates_on_timetable"] = true;
							getFahrplanAndPositionForOneTrain($id, $newZugId);
							addStopsectionsForTimetable($id);
							calculateNextSections($id);
							//addNextStopForAllTrains($id);
							checkIfFahrstrasseIsCorrrect($id);
							calculateFahrverlauf($id);

						} else if ($currentZugId == null && $newZugId != null) {
							// fährt jetzt nach fahrplan
							$allUsedTrains[$id]["operates_on_timetable"] = true;
							getFahrplanAndPositionForOneTrain($id);
							addStopsectionsForTimetable($id);
							calculateNextSections($id);
							//addNextStopForAllTrains($id);
							checkIfFahrstrasseIsCorrrect($id);
							calculateFahrverlauf($id);

						} else if ($currentZugId != null && $newZugId == null) {
							// fährt jetzt auf freier strecke
							$allUsedTrains[$id]["operates_on_timetable"] = false;
							calculateNextSections($id);
							calculateFahrverlauf($id);
						}
					}
				}
				/*
				if (sizeof($timeValue) == 1 && !in_array($timeIndex, $unusedTrains)) {
					array_push($unusedTrains, $timeIndex);
				}
				*/
				array_shift($allTimes[$timeIndex]);
			}
		}
	}
	// Neukalibrierung
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
							calculateFahrverlauf($trainKey, true);
							echo "Die Position des Fahrzeugs mit der ID: ", $trainKey, " wurde neu ermittelt.\n";
						}
					} else {
						//echo "Die Position des Fahrzeugs mit der ID: ", $trainKey, " konnte nicht neu ermittelt werden.\n";
					}
				}
			}
			$timeCheckCalibration = $timeCheckCalibration + $timeCheckCalibrationInterval;
		}
	}
	// Überprüfung, ob die Fahrstraße sich geändert hat und ob neue Fahrzeuge auf das Netz gesetzt wurden
	if (microtime(true) > $timeCheckAllTrains) {
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
			consoleCheckIfStartDirectionIsCorrect($id);
			consoleAllTrainsPositionAndFahrplan($id);
			addStopsectionsForTimetable($id);
			initalFirstLiveData($id);
			calculateNextSections($id);
			checkIfTrainReachedHaltepunkt($id);
			checkIfFahrstrasseIsCorrrect($id);
			calculateFahrverlauf($id);
		}
		if (sizeof($removeTrains) > 0) {
			echo "Entfernte  Züge: \n";
			foreach ($removeTrains as $removeTrain) {
				$id = $cacheDecoderToID[$removeTrain];
				unset($allUsedTrains[$id]);
				echo "\tID:\t", $id, "\tAdresse:\t", $removeTrain;
			}
			echo "\n";
		}
		$timeCheckAllTrains = $timeCheckAllTrains + $timeCheckAllTrainsInterval;
	}
	// Ausgabe aller aktuellen Daten der Fahrzeuge
	if (microtime(true) > $timeCheckAllTrainErrors) {
		consoleAllTrainsPositionAndFahrplan();
		showFahrplan();
		$timeCheckAllTrainErrors = $timeCheckAllTrainErrors + $timeCheckAllTrainErrorsInterval;
	}
	sleep($sleeptime);
}









