<?php

require 'vorbelegung.php';
require 'functions/funktionen_abschnitte.php';
require 'init/init_abschnitte.php';
require 'init/init_fzg.php';
require 'functions/functions.php';
require 'functions/functions_fahrtverlauf.php';

$cacheInfranachbarn = createCacheInfranachbarn();
$cacheInfradaten = createCacheInfradaten();
$cacheSignaldaten = createCacheSignaldaten();
$cacheInfraLaenge = createcacheInfraLaenge();
$cacheHaltepunkte = createCacheHaltepunkte();

$fmaToInfra = createFmaToInfraData();
$infraToFma = array_flip($fmaToInfra);

// Zeit:
$DB = new DB_MySQL();
$databaseTime = (float) strtotime($DB->select("SELECT CURRENT_TIMESTAMP")[0]->CURRENT_TIMESTAMP);
unset($DB);
//$databaseTime = 1619164800;
$simulationTime = (float) getUhrzeit();
$timeDifference = $databaseTime - $simulationTime;
$timeDifferenceGetUhrzeit = $simulationTime - $databaseTime;

$timeStart = microtime(true);
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
if (false) {
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

}

consoleCheckIfStartDirectionIsCorrect();
consoleAllTrainsPositionAndFahrplan();

addStopsectionsForTimetable();
initalFirstLiveData();
showErrors();
calculateNextSections();
addNextStopForAllTrains();
checkIfFahrstrasseIsCorrrect();

foreach ($allTrains as $trainIndex => $trainValue) {
	$allTrains[$trainIndex]["last_get_naechste_abschnitte"] = getNaechsteAbschnitte($trainValue["current_infra_section"], $trainValue["dir"]);
}


calculateFahrverlauf();




$timeCheckAllTrainsInterval = 30;
$timeCheckAllTrains = 30 + microtime(true);
$sleeptime = 0.3;
while (true) {
	foreach ($allTimes as $timeIndex => $timeValue) {
		if (sizeof($timeValue) > 0) {
			$id = $timeValue[0]["id"];
			if ((microtime(true) + $timeDifference) > $timeValue[0]["live_time"]) {

				if ($timeValue[0]["live_is_speed_change"]) {
					sendFahrzeugbefehl($timeValue[0]["id"], intval($timeValue[0]["live_speed"]));
					echo "Der Zug mit der Adresse ", $timeIndex, " hat auf der Fahrt nach ??? seine Geschwindigkeit auf ", $timeValue[0]["live_speed"], " km/h angepasst.\n";

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
					}

					$currentZugId = $allTrains[$id]["zug_id"];
					$newZugId = getFahrzeugZugIds(array($id));

					if (sizeof($newZugId) == 0) {
						$newZugId = null;
					} else {
						$newZugId = getFahrzeugZugIds(array($timeValue[0]["id"]));
						$allTrains[$id]["zug_id"] = intval($newZugId);
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
				array_shift($allTimes[$timeIndex]);
			}
		}
	}

	if (microtime(true) > $timeCheckAllTrains) {
		foreach ($allTimes as $timeIndex => $timeValue) {
			$id = $adresseToID[$timeIndex];
			compareTwoNaechsteAbschnitte($id, $allTrains[$id]["last_get_naechste_abschnitte"], getNaechsteAbschnitte($allTrains[$id]["current_infra_section"], $allTrains[$id]["dir"]));
			$timeCheckAllTrains += $timeCheckAllTrainsInterval;
		}
		$timeCheckAllTrains = $timeCheckAllTrains + $timeCheckAllTrainsInterval;
	}
	sleep($sleeptime);
}
