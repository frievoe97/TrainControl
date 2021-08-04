<?php

// Load all required external files
require 'vorbelegung.php';
require 'functions/funktionen_abschnitte.php';
require 'init/init_abschnitte.php';
require 'init/init_fzg.php';
require 'functions/functions.php';
require 'functions/functions_fahrtverlauf.php';
require 'functions/signale_stellen.php';

// Reports only errors
error_reporting(1);


// Set timezone
date_default_timezone_set("Europe/Berlin");

// Load static data from the databse into the cache
$cacheInfranachbarn = createCacheInfranachbarn();
$cacheInfradaten = createCacheInfradaten();
$cacheSignaldaten = createCacheSignaldaten();
$cacheInfraLaenge = createcacheInfraLaenge();
$cacheHaltepunkte = createCacheHaltepunkte();
$cacheZwischenhaltepunkte = createChacheZwischenhaltepunkte();
$cacheInfraToGbt = createCacheInfraToGbt();
$cacheGbtToInfra = createCacheGbtToInfra();


// TODO: Rename as cache... vars
$fmaToInfra = createFmaToInfraData();
$infraToFma = array_flip($fmaToInfra);

// Time:
$DB = new DB_MySQL();
$databaseTime = (float) strtotime($DB->select("SELECT CURRENT_TIMESTAMP")[0]->CURRENT_TIMESTAMP);
unset($DB);
//$databaseTime = 1619164800;
$simulationTime = (float) getUhrzeit();
$timeDifference = $databaseTime - $simulationTime;
$timeDifferenceGetUhrzeit = $simulationTime - $databaseTime;

// Get simulation time, real time and simulation duration
$simulationStartTime = getDataFromFahrplanSession("sim_startzeit");
$simulationEndTime = getDataFromFahrplanSession("sim_endzeit");
$simulationDuration = $simulationEndTime - $simulationStartTime;
$simulationStartTimeToday = getUhrzeit(getUhrzeit($simulationStartTime, "simulationszeit", null, array("outputtyp"=>"h:i:s")), "simulationszeit", null, array("inputtyp"=>"h:i:s"));
$simulationEndTimeToday = getUhrzeit(getUhrzeit($simulationEndTime, "simulationszeit", null, array("outputtyp"=>"h:i:s")), "simulationszeit", null, array("inputtyp"=>"h:i:s"));
$realStartTime = time();
$realEndTime = $realStartTime + $simulationDuration;
$newTimeDifference = $simulationStartTimeToday - $realStartTime;


// setting the startscreen
startMessage();



$sessionIsActive = true;
$trainErrors = array(0 => "Zug stand falsch herum und war zu lang um die Richtung zu ändern");

// Step 1: Initilize all trains (verzoegerung, laenge etc.) where zustand <= 1 gilt
$allTrains = getAllTrains();

getFahrplanAndPosition();


$idToAdresse = array();
foreach ($allTrains as $index => $value) {
	$idToAdresse[$index] = $value["adresse"];
}
$adresseToID = array_flip($idToAdresse);
$allTimes = array();

consoleAllTrainsPositionAndFahrplan();
if (true) {
	$allTrains[51]["dir"] = 1;
	$allTrains[57]["dir"] = 1;
	$allTrains[65]["dir"] = 1;
	$allTrains[78]["dir"] = 1;

	$allTrains[51]["laenge"] = 50;
	$allTrains[57]["laenge"] = 50;
	$allTrains[65]["laenge"] = 50;
	$allTrains[78]["laenge"] = 50;

	$allTrains[51]["current_infra_section"] = 1166;
	$allTrains[57]["current_infra_section"] = 1169;

	$allTrains[51]["current_position"] = 1;
	$allTrains[57]["current_position"] = 1;
	$allTrains[65]["current_position"] = $cacheInfraLaenge[$allTrains[65]["current_infra_section"]];
	$allTrains[78]["current_position"] = $cacheInfraLaenge[$allTrains[78]["current_infra_section"]];

	$allTrains[51]["speed"] = 0;
	$allTrains[57]["speed"] = 0;
	$allTrains[65]["speed"] = 0;
	$allTrains[78]["speed"] = 0;

}


consoleCheckIfStartDirectionIsCorrect();
consoleAllTrainsPositionAndFahrplan();



addStopsectionsForTimetable();
initalFirstLiveData();
showErrors();
calculateNextSections();
addNextStopForAllTrains();
checkIfTrainReachedHaltepunkt();
checkIfFahrstrasseIsCorrrect();


foreach ($allTrains as $trainIndex => $trainValue) {
	$allTrains[$trainIndex]["last_get_naechste_abschnitte"] = getNaechsteAbschnitte($trainValue["current_infra_section"], $trainValue["dir"]);
}

calculateFahrverlauf();





$unusedTrains = array_keys($allTimes);
$timeCheckAllTrainsInterval = 3;
$timeCheckAllTrains = $timeCheckAllTrainsInterval + microtime(true);
$sleeptime = 0.3;
while (true) {
	foreach ($allTimes as $timeIndex => $timeValue) {
		if (sizeof($timeValue) > 0) {
			$id = $timeValue[0]["id"];
			if ((microtime(true) + $newTimeDifference) > $timeValue[0]["live_time"]) {

				if ($timeValue[0]["live_is_speed_change"]) {
					sendFahrzeugbefehl($timeValue[0]["id"], intval($timeValue[0]["live_speed"]));
					$bsIndex = $timeValue[0]["betriebsstelle_index"];
					if ($bsIndex == 99999999) {
						echo "Der Zug mit der Adresse ", $timeIndex, " hat auf der freien Fahrt seine Geschwindigkeit auf ", $timeValue[0]["live_speed"], " km/h angepasst.\n";
					} else {
						echo "Der Zug mit der Adresse ", $timeIndex, " hat auf der Fahrt nach ", $allTrains[$id]["next_betriebsstellen_data"][$bsIndex]["betriebstelle"]," seine Geschwindigkeit auf ", $timeValue[0]["live_speed"], " km/h angepasst.\n";
					}
				}

				$allTrains[$id]["current_position"] = $timeValue[0]["live_relative_position"];
				$allTrains[$id]["speed"] = $timeValue[0]["live_speed"];
				$allTrains[$id]["current_infra_section"] = $timeValue[0]["live_section"];

				if ($timeValue[0]["wendet"]) {
					$id = $timeValue[0]["id"];
					$currentDirection = $allTrains[$id]["dir"];
					$allTimes[$timeIndex] = array();
					changeDirection($id);
					if ($currentDirection == 0) {
						$allTrains[$id]["dir"] = 1;
					} else {
						$allTrains[$id]["dir"] = 0;
					}
				}

				if ($timeValue[0]["live_target_reached"]) {

					if ($timeValue[0]["betriebsstelle_index"] >= 0) {
						$allTrains[$id]["next_betriebsstellen_data"][$timeValue[0]["betriebsstelle_index"]]["angekommen"] = true;
						$bsIndex = $timeValue[0]["betriebsstelle_index"];
						if ($bsIndex == 99999999) {
							//echo "Der Zug mit der Adresse ", $timeIndex, " hat den Halt ", $allTrains[$id]["next_betriebsstellen_data"][$bsIndex]["betriebstelle"], " mit einer Verspätung von ", number_format($allTrains[$id]["next_betriebsstellen_data"][$bsIndex]["zeiten"]["verspätung"], 2), " Sekunden erreicht.\n";
						} else {
							echo "Der Zug mit der Adresse ", $timeIndex, " hat den Halt ", $allTrains[$id]["next_betriebsstellen_data"][$bsIndex]["betriebstelle"], " mit einer Verspätung von ", number_format($allTrains[$id]["next_betriebsstellen_data"][$bsIndex]["zeiten"]["verspaetung"], 2), " Sekunden erreicht.\n";
						}
					}

					$currentZugId = $allTrains[$id]["zug_id"];
					$newZugId = getFahrzeugZugIds(array($id));

					if (sizeof($newZugId) == 0) {
						$newZugId = null;
					} else {
						$newZugId = getFahrzeugZugIds(array($timeValue[0]["id"]));
						$newZugId = $newZugId[array_key_first($newZugId)]["zug_id"];
					}

					if (!($currentZugId == $newZugId && $currentZugId != null)) {

						if ($currentZugId != null && $newZugId != null) {
							// neuer fahrplan
							$allTrains[$id]["operates_on_timetable"] = 1;
							getFahrplanAndPositionForOneTrain($id);
							addStopsectionsForTimetable($id);
							calculateNextSections($id);
							addNextStopForAllTrains($id);
							checkIfFahrstrasseIsCorrrect($id);
							calculateFahrverlauf($id);

						} elseif ($currentZugId == null && $newZugId != null) {
							// fährt jetzt nach fahrplan
							$allTrains[$id]["operates_on_timetable"] = 1;
							getFahrplanAndPositionForOneTrain($id);
							addStopsectionsForTimetable($id);
							calculateNextSections($id);
							addNextStopForAllTrains($id);
							checkIfFahrstrasseIsCorrrect($id);
							calculateFahrverlauf($id);

						} elseif ($currentZugId != null && $newZugId == null) {
							// fährt jetzt auf freier strecke
							$allTrains[$id]["operates_on_timetable"] = 0;
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
			$id = $adresseToID[$unusedTrainsValue];
			compareTwoNaechsteAbschnitte($id);
		}
		$timeCheckAllTrains = $timeCheckAllTrains + $timeCheckAllTrainsInterval;
	}
	sleep($sleeptime);
}
