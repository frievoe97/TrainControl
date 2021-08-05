<?php
// TODO:
// - Rename TimeDifference ($newTimeDifference)
// - Train Errors hinzufügen

// Load all required external files
require 'vorbelegung.php';
require 'functions/sort_functions.php';
require 'functions/cache_functions.php';
require 'functions/cache_functions_own.php';
require 'functions/ebuef_functions.php';
require 'functions/fahrtverlauf_functions.php';
require 'define_multicast.php';
require 'globalVariables.php';

// Set timezone
date_default_timezone_set("Europe/Berlin");

// Set memory
//ini_set('memory_limit', '1024M');

// Reports only errors
error_reporting(1);

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

// Start Message
startMessage();

// Load all trains
// TODO: Funktion benötigt, die die Daten updatet...
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
$unusedTrains = array_keys($allTimes);
$timeCheckAllTrainsInterval = 3;
$timeCheckAllTrains = $timeCheckAllTrainsInterval + microtime(true);
$timeCheckAllTrainErrorsInterval = 30;
$timeCheckAllTrainErrors = $timeCheckAllTrainErrorsInterval + microtime(true);
$sleeptime = 0.03;
$sleeptime = 1;
while (true) {
	foreach ($allTimes as $timeIndex => $timeValue) {
		if (sizeof($timeValue) > 0) {
			$id = $timeValue[0]["id"];
			if ((microtime(true) + $timeDifference) > $timeValue[0]["live_time"]) {
				if ($timeValue[0]["live_is_speed_change"]) {
					if ($timeValue[0]["betriebsstelle"] == 'Notbremsung') {
						sendFahrzeugbefehl($timeValue[0]["id"], intval($timeValue[0]["live_speed"]));
						echo "Der Zug mit der Adresse ", $timeIndex, " leitet gerade eine Gefahrenbremsung ein und hat seine Geschwindigkeit auf ", $timeValue[0]["live_speed"], " km/h angepasst.\n";
					} else {
						sendFahrzeugbefehl($timeValue[0]["id"], intval($timeValue[0]["live_speed"]));
						echo "Der Zug mit der Adresse ", $timeIndex, " hat auf der Fahrt nach ", $timeValue[0]["betriebsstelle"],
						" seine Geschwindigkeit auf ", $timeValue[0]["live_speed"], " km/h angepasst.\n";
					}
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
				if (sizeof($timeValue) == 1 && !in_array($timeIndex, $unusedTrains)) {
					array_push($unusedTrains, $timeIndex);
				}
				array_shift($allTimes[$timeIndex]);
			}
		}
	}

	if (microtime(true) > $timeCheckAllTrains) {
		foreach ($unusedTrains as $unusedTrainsIndex => $unusedTrainsValue) {
			$id = $cacheAdresseToID[$unusedTrainsValue];
			if ($id == 65) {
				var_dump($allUsedTrains[78]["current_section"]);
			}
			compareTwoNaechsteAbschnitte($id);
			if ($id == 65) {
				var_dump($allUsedTrains[78]["current_section"]);
			}
		}
		// Search new trains.
		// added to allUsedTrains and to unusedTrains
		$prevTrains = array_keys($allUsedTrains);
		findTrainsOnTheTracks();
		$nextTrains = array_keys($allUsedTrains);
		if (sizeof($prevTrains) != sizeof($nextTrains)) {
			echo "Neu hinzugefügte Züge:\n\n";
		}
		foreach ($nextTrains as $nextTrainID) {
			if (!in_array($nextTrainID, $prevTrains)) {
				consoleCheckIfStartDirectionIsCorrect($nextTrainID);
				consoleAllTrainsPositionAndFahrplan($nextTrainID);
				addStopsectionsForTimetable($nextTrainID);
				initalFirstLiveData($nextTrainID);
				calculateNextSections($nextTrainID);
				checkIfTrainReachedHaltepunkt($nextTrainID);
				checkIfFahrstrasseIsCorrrect($nextTrainID);
				calculateFahrverlauf($nextTrainID);
				array_push($unusedTrains, $allUsedTrains[$nextTrainID]["adresse"]);
			}
		}
		$timeCheckAllTrains = $timeCheckAllTrains + $timeCheckAllTrainsInterval;
	}
	if (microtime(true) > $timeCheckAllTrainErrors) {
		//$timeCheckAllTrainErrors = $timeCheckAllTrainErrors + $timeCheckAllTrainErrorsInterval;
	}
	sleep($sleeptime);
}









