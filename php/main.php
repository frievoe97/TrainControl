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
$databaseTime = 1619164800;
$simulationTime = (float) getUhrzeit();
$timeDifference = $databaseTime - $simulationTime;
$timeDifferenceGetUhrzeit = $simulationTime - $databaseTime;


$timeStart = microtime(true);
$sessionIsActive = true;
$trainErrors = array(0 => "Zug stand falsch herum und war zu lang um die Richtung zu ändern");

// Step 1: Initilize all trains (verzoegerung, laenge etc.) where zustand <= 1 gilt
$allTrains = getAllTrains();
getFahrplanAndPosition();
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

	$allTrains[51]["current_infra_section"] = 1167;
	$allTrains[57]["current_infra_section"] = 1169;

	$allTrains[51]["current_position"] = $cacheInfraLaenge[$allTrains[51]["current_infra_section"]];
	$allTrains[57]["current_position"] = 1;
	$allTrains[65]["current_position"] = $cacheInfraLaenge[$allTrains[65]["current_infra_section"]];
	$allTrains[78]["current_position"] = $cacheInfraLaenge[$allTrains[78]["current_infra_section"]];

}
consoleCheckIfStartDirectionIsCorrect();
consoleAllTrainsPositionAndFahrplan();




// Fügt für alle Züge die möglichen Haltepunkte hinzu
addStopsectionsForTimetable();

initalFirstLiveData();
$aa = array(0,1,2,3,4,5,6);
$bb = array(24,25,26,27);

if (false) {
	$aa = array();
	$aa[0]["bs"] = "XWF";
	$aa[0]["laenge"] = 100;

	$bb = array();
	$bb[2]["bs"] = "XWF";
	$bb[2]["laenge"] = 100;
}

showErrors();


// Add next_sections, next_lengths, next_v_max
calculateNextSections();





addNextStopForAllTrains();




checkIfFahrstrasseIsCorrrect();
calculateFahrverlauf();



$sleeptime = 0.1;
while (true) {
	foreach ($allTimes as $timeIndex => $timeValue) {
		if (sizeof($timeValue) > 0) {
			if ((microtime(true) + $timeDifference) > $timeValue[0]["live_time"]) {

				if ($timeValue[0]["live_is_speed_change"]) {
					sendFahrzeugbefehl($timeValue[0]["id"], intval($timeValue[0]["live_speed"]));
					echo "Der Zug mit der Adresse ", $timeIndex, " hat auf der Fahrt nach ??? seine Geschwindigkeit auf ", $timeValue[0]["live_speed"], " km/h angepasst.\n";

				}

				if ($timeValue[0]["live_target_reached"]) {
					// TODO
					$oldZugId = $allTrains[$timeValue[0]["id"]]["zug_id"];
					$newZugId = getFahrzeugZugIds(array($timeValue[0]["id"]));
					$newZugId = intval($newZugId[array_key_first($newZugId)]["zug_id"]);

					if ($oldZugId != $newZugId) {
						// TODO: Times löschen
					}

					$allTrains[$timeValue[0]["id"]]["next_betriebsstellen_data"][$timeValue[0]["betriebsstelle_index"]]["angekommen"] = true;
				}

				if ($timeValue[0]["wendet"]) {
					// TODO
				}
				array_shift($allTimes[$timeIndex]);
			}
		}
	}
	sleep($sleeptime);
}


// BIG LOOP

	// Step 3: Loop to send the current speed to the train

	// Step 4: Check if the v_max of the next sections has changed

