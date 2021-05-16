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
$simulationTime = (float) getUhrzeit();
$timeDifference = $databaseTime - $simulationTime;
$timeDifferenceGetUhrzeit = $simulationTime - $databaseTime;

$databaseTime = 1619164800;
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



//var_dump($allTrains[57]);
checkIfFahrstrasseIsCorrrect();
calculateFahrverlauf();


sleep(5);

/*
foreach ($allTrains as $trainIndex => $trainValue) {
	$next_lenghts = array();
	$next_sections = array();
	$next_vmax = array();
	array_push($next_sections, $trainValue["current_infra_section"]);
	array_push($next_lenghts, $cacheInfraLaenge[$trainValue["current_infra_section"]]);
	array_push($next_vmax, 60);
	foreach ($trainValue["current_fahrstrasse_data"] as $data) {
		array_push($next_sections, intval($data["infra_id"]));
		array_push($next_lenghts, intval($data["laenge"]));
		array_push($next_vmax, 60);
	}
	$allTrains[$trainIndex]["next_sections"] = $next_sections;
	$allTrains[$trainIndex]["next_lenghts"] = $next_lenghts;
	$allTrains[$trainIndex]["next_v_max"] = $next_vmax;

	$allTrains[$trainIndex]["next_timetable_change_speed"] = intval(getSignalbegriff(intval($trainValue["current_fahrstrasse_data"][array_key_last($trainValue["current_fahrstrasse_data"])]["signal_id"]))[0]["geschwindigkeit"]);
	if ($allTrains[$trainIndex]["next_timetable_change_speed"] < 0) {
		$allTrains[$trainIndex]["next_timetable_change_speed"] = 0;
	}

	$allTrains[$trainIndex]["next_timetable_change_section"] = intval($trainValue["current_fahrstrasse_data"][array_key_last($trainValue["current_fahrstrasse_data"])]["infra_id"]);
	$allTrains[$trainIndex]["next_timetable_change_position"] = $cacheInfraLaenge[$allTrains[$trainIndex]["next_timetable_change_section"]];
	$allTrains[$trainIndex]["next_timetable_change_time"] = microtime(true) + 100;

	$allTrains[$trainIndex]["current_speed"] = 0;
}


foreach ($allTrains as $trainIndex => $trainValue) {
	$returnData = updateNextSpeed($trainValue, microtime(true), microtime(true) + 10, "TEST_BETRIEBSSTELLE");
	$allTimes[$trainValue["adresse"]]["live_position"] = $returnData[0];
	$allTimes[$trainValue["adresse"]]["live_speed"] = $returnData[1];
	$allTimes[$trainValue["adresse"]]["live_time"] = $returnData[2];
	$allTimes[$trainValue["adresse"]]["live_relative_position"] = $returnData[3];
	$allTimes[$trainValue["adresse"]]["live_section"] = $returnData[4];
	$allTimes[$trainValue["adresse"]]["live_is_speed_change"] = $returnData[5];
	$allTimes[$trainValue["adresse"]]["live_target_reached"] = $returnData[6];
}
*/

foreach ($allTimes as $timeIndex => $timeValue) {
	$allTimes[$timeIndex]["id"] = getFahruegId($timeIndex);
}



$sleeptime = 0.1;
while (true) {
	foreach ($allTimes as $timeIndex => $timeValue) {
		if (sizeof($allTimes[$timeIndex]["live_time"]) > 0) {
			if (microtime(true) > $allTimes[$timeIndex]["live_time"][0]) {

				if ($allTimes[$timeIndex]["live_is_speed_change"]) {
					sendFahrzeugbefehl($timeValue["id"], intval($allTimes[$timeIndex]["live_speed"][0]));
					echo "Der Zug mit der Adresse ", $timeIndex, " hat auf der Fahrt nach ", $allTrains[$timeValue["id"]]["next_betriebsstelle_soll"], " seine Geschwindigkeit auf ", $allTimes[$timeIndex]["live_speed"][0], " km/h angepasst.\n";

				}

				if ($allTimes[$timeIndex]["live_target_reached"] != null) {
					$allTrains[$timeValue["id"]]["current_betriebsstelle"] = $allTimes[$timeIndex]["live_target_reached"][0];
				}

				$allTrains[$timeValue["id"]]["current_position"] = $allTimes[$timeIndex]["live_relative_position"][0];
				$allTrains[$timeValue["id"]]["current_infra_section"] = $allTimes[$timeIndex]["live_section"][0];
				$allTrains[$timeValue["id"]]["current_speed"] = $allTimes[$timeIndex]["live_speed"][0];
				array_shift($allTimes[$timeIndex]["live_time"]);
				array_shift($allTimes[$timeIndex]["live_speed"]);
				array_shift($allTimes[$timeIndex]["live_position"]);
				array_shift($allTimes[$timeIndex]["live_relative_position"]);
				array_shift($allTimes[$timeIndex]["live_section"]);
				array_shift($allTimes[$timeIndex]["live_is_speed_change"]);
			}
		}
	}
	sleep($sleeptime);
}


// BIG LOOP

	// Step 3: Loop to send the current speed to the train

	// Step 4: Check if the v_max of the next sections has changed

