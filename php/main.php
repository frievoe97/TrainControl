<?php

require 'vorbelegung.php';
require 'funktionen_abschnitte.php';
require 'init/init_abschnitte.php';
require 'init/init_fzg.php';
require 'functions.php';

require 'init/update_fzg_2.php';

$cacheInfranachbarn = createCacheInfranachbarn();
$cacheInfradaten = createCacheInfradaten();
$cacheSignaldaten = createCacheSignaldaten();

$cacheInfraLaenge = createcacheInfraLaenge();

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

// dump data
/*
$dummyData = array();
$dummyData[6466]["next_sections"] = [1000, 1001, 1002, 1003, 1004, 1005, 1006, 1007, 1008];
$dummyData[6464]["next_sections"] = [1000, 1001, 1002, 1003, 1004, 1005, 1006, 1007, 1008];
$dummyData[6462]["next_sections"] = [1000, 1001, 1002, 1003, 1004, 1005, 1006, 1007, 1008];
$dummyData[6461]["next_sections"] = [1000, 1001, 1002, 1003, 1004, 1005, 1006, 1007, 1008];

$dummyData[6466]["next_vmax"] = [120, 120, 120, 120, 120, 120, 80, 40, 50];
$dummyData[6464]["next_vmax"] = [120, 120, 120, 80, 80, 120, 80, 120, 80];
$dummyData[6462]["next_vmax"] = [120, 120, 120, 120, 80, 120, 80, 120, 80];
$dummyData[6461]["next_vmax"] = [120, 120, 80, 120, 120, 80, 80, 120, 80];

$dummyData[6466]["next_lengths"] = [100, 120, 120, 100, 120, 120, 80, 1000, 1000];
$dummyData[6464]["next_lengths"] = [120, 1000, 100, 80, 80, 120, 80, 120, 80];
$dummyData[6462]["next_lengths"] = [120, 70, 120, 70, 70, 120, 80, 120, 80];
$dummyData[6461]["next_lengths"] = [70, 120, 70, 120, 70, 80, 80, 120, 80];
*/


// Step 1: Initilize all trains (verzoegerung, laenge etc.) where zustand <= 1 gilt
$allTrains = getAllTrains();
$positionTrains = array();



// Get all Zug IDs
foreach ($allTrains as $trainIndex => $trainValue) {
	$values = getFahrzeugZugIds(array($allTrains[$trainIndex]["id"]));
	if (sizeof($values) != 0) {
		$value = $values[array_key_first($values)];
		$allTrains[$trainIndex]["zug_id"] = intval($value["zug_id"]);
		$allTrains[$trainIndex]["operates_on_timetable"] = 1;

	} else {
		$allTrains[$trainIndex]["zug_id"] = null;
		$allTrains[$trainIndex]["operates_on_timetable"] = 0;
	}
}









// Get next Betriebsstellen
foreach ($allTrains as $trainIndex => $trainValue) {
	$zug_id = intval($allTrains[$trainIndex]["zug_id"]);
	$nextBetriebsstellen = getNextBetriebsstellen($zug_id);
	if (sizeof($nextBetriebsstellen) != 0) {
		for ($i = 0; $i < sizeof($nextBetriebsstellen); $i++) {
			$allTrains[$trainIndex]["next_betriebsstellen_data"][$i]["betriebstelle"] = $nextBetriebsstellen[$i];
			$allTrains[$trainIndex]["next_betriebsstellen_data"][$i]["zeiten"] = getFahrplanzeiten($nextBetriebsstellen[$i], $zug_id);
		}
		$allTrains[$trainIndex]["next_betriebsstellen_name"] = $nextBetriebsstellen;
	} else {
		$allTrains[$trainIndex]["next_betriebsstellen_data"] = null;
		$allTrains[$trainIndex]["next_betriebsstellen_name"] = null;
	}


}

// TODO: fma -> infra

// Get the current position of all trains
foreach ($allTrains as $trainIndex => $trainValue) {
	$fma = getPosition($trainValue["adresse"]);
	if (sizeof($fma) == 0) {
		$allTrains[$trainIndex]["current_fma_section"] = null;
		$allTrains[$trainIndex]["current_infra_section"] = null;
	} elseif (sizeof($fma) == 1) {
		$allTrains[$trainIndex]["current_fma_section"] = $fma[0];
		$allTrains[$trainIndex]["current_infra_section"] = $fmaToInfra[$fma[0]];
	} else {
		$infraArray = array();
		foreach ($fma as $value) {
			array_push($infraArray, $fmaToInfra[$value]);
		}
		$infra = getFrontPosition($infraArray, $allTrains[$trainIndex]["dir"]);
		$allTrains[$trainIndex]["current_fma_section"] = $infraToFma[$infra];
		$allTrains[$trainIndex]["current_infra_section"] = $infra;
	}
}

//check if Fahrstraße ist korrekt

foreach ($allTrains as $trainIndex => $trainValue) {
	// get next betriebsstellen soll
	if ($allTrains[$trainIndex]["next_betriebsstellen_data"] != null) {
		$allTrains[$trainIndex]["next_betriebsstelle_soll"] = $allTrains[$trainIndex]["next_betriebsstellen_data"][0]["betriebstelle"];
	} else {
		$allTrains[$trainIndex]["next_betriebsstelle_soll"] = null;
	}
	// get next betriebsstelle ist
	if ($trainValue["current_infra_section"] != null) {
		$fahrstrassenData = getNaechsteAbschnitte($trainValue["current_infra_section"], $trainValue["dir"]);
		$allTrains[$trainIndex]["current_fahrstrasse_data"] = $fahrstrassenData;
		$nextIstBetriebsstellen = array();
		foreach ($fahrstrassenData as $data) {
			if ($data["signal_id"] != null) {
				array_push($nextIstBetriebsstellen, convertSignalIdToBetriebsstelle($data["signal_id"]));
			}
		}
		$allTrains[$trainIndex]["current_fahrstrasse_name"] = $nextIstBetriebsstellen;
	} else {
		$allTrains[$trainIndex]["current_fahrstrasse_name"][0] = null;
		$allTrains[$trainIndex]["current_fahrstrasse_data"][0] = null;
	}
}

foreach ($allTrains as $trainIndex => $trainValue) {
	if (($trainValue["current_fahrstrasse_name"][0] == $trainValue["next_betriebsstelle_soll"]) && $trainValue["current_fahrstrasse_name"][0] != null) {
		$allTrains[$trainIndex]["richtige_fahrstraße"] = 1;
	} else {
		$allTrains[$trainIndex]["richtige_fahrstraße"] = 0;
	}
}


$allTimes = array();


$usedTrains = array();

foreach ($allTrains as $train) {
	if ($train["current_fma_section"] != null) {
		array_push($usedTrains, $train);
	}
}


//var_dump($usedTrains[0]);

foreach ($usedTrains as $trainIndex => $trainValue) {
	foreach ($trainValue["next_betriebsstellen_data"] as $betriebsstelleIndex => $betriebsstelleValue) {
		if ($betriebsstelleValue["zeiten"] != false) {
			if ($betriebsstelleValue["zeiten"]["abfahrt_soll"] != null) {
				$usedTrains[$trainIndex]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["abfahrt_soll_timestamp"] = getUhrzeit($betriebsstelleValue["zeiten"]["abfahrt_soll"], "simulationszeit", $timeDifferenceGetUhrzeit, array("inputtyp" => "h:i:s"));
			} else {
				$usedTrains[$trainIndex]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["abfahrt_soll_timestamp"] = null;
			}

			if ($betriebsstelleValue["zeiten"]["ankunft_soll"] != null) {
				$usedTrains[$trainIndex]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["ankunft_soll_timestamp"] = getUhrzeit($betriebsstelleValue["zeiten"]["ankunft_soll"], "simulationszeit", $timeDifferenceGetUhrzeit, array("inputtyp" => "h:i:s"));
			} else {
				$usedTrains[$trainIndex]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["ankunft_soll_timestamp"] = null;
			}
		}
	}
}



//var_dump(getUhrzeit("10:07:17", "simulationszeit", $timeDifferenceGetUhrzeit, array("inputtyp" => "h:i:s")));

foreach ($usedTrains as $trainIndex => $trainValue) {
	$usedTrains[$trainIndex]["current_position"] = $cacheInfraLaenge[$trainValue["current_infra_section"]];
}

foreach ($usedTrains as $trainIndex => $trainValue) {
	$usedTrains[$trainIndex]["notverzoegerung"] = 2;
}



//var_dump($usedTrains[3]);

//var_dump(getSignalbegriff(182));



foreach ($usedTrains as $trainIndex => $trainValue) {
	$next_lenghts = array();
	$next_sections = array();
	$next_vmax = array();
	array_push($next_sections, $trainValue["current_infra_section"]);
	array_push($next_lenghts, $cacheInfraLaenge[$trainValue["current_infra_section"]]);
	array_push($next_vmax, 60);
	foreach ($trainValue["current_fahrstrasse_data"] as $data) {
		//var_dump($data);
		array_push($next_sections, intval($data["infra_id"]));
		array_push($next_lenghts, intval($data["laenge"]));
		array_push($next_vmax, 60);
	}
	$usedTrains[$trainIndex]["next_sections"] = $next_sections;
	$usedTrains[$trainIndex]["next_lenghts"] = $next_lenghts;
	$usedTrains[$trainIndex]["next_v_max"] = $next_vmax;

	$usedTrains[$trainIndex]["next_timetable_change_speed"] = intval(getSignalbegriff(intval($trainValue["current_fahrstrasse_data"][array_key_last($trainValue["current_fahrstrasse_data"])]["signal_id"]))[0]["geschwindigkeit"]);
	if ($usedTrains[$trainIndex]["next_timetable_change_speed"] < 0) {
		$usedTrains[$trainIndex]["next_timetable_change_speed"] = 0;
	}

	$usedTrains[$trainIndex]["next_timetable_change_section"] = intval($trainValue["current_fahrstrasse_data"][array_key_last($trainValue["current_fahrstrasse_data"])]["infra_id"]);
	$usedTrains[$trainIndex]["next_timetable_change_position"] = $cacheInfraLaenge[$usedTrains[$trainIndex]["next_timetable_change_section"]];
	$usedTrains[$trainIndex]["next_timetable_change_time"] = microtime(true) + 100;

	$usedTrains[$trainIndex]["current_speed"] = 0;







}

//var_dump($usedTrains[0]);
//var_dump($usedTrains[1]);

$allTimes = array();



foreach ($usedTrains as $trainIndex => $trainValue) {
	$returnData = updateNextSpeed($trainValue, microtime(true));
	$allTimes[$trainValue["adresse"]]["live_position"] = $returnData[0];
	$allTimes[$trainValue["adresse"]]["live_speed"] = $returnData[1];
	$allTimes[$trainValue["adresse"]]["live_time"] = $returnData[2];
}


foreach ($allTimes as $timeIndex => $timeValue) {
	$allTimes[$timeIndex]["id"] = getFahruegId($timeIndex);
}

$sleeptime = 0.1;
while (true) {
	foreach ($allTimes as $timeIndex => $timeValue) {
		if (sizeof($allTimes[$timeIndex]["live_time"]) > 0) {
			if (microtime(true) > $allTimes[$timeIndex]["live_time"][0]) {
				sendFahrzeugbefehl($timeValue["id"], intval($allTimes[$timeIndex]["live_speed"][0]));
				echo "Der Zug mit der Adresse ", $timeIndex, " hat auf der Fahrt nach ", $usedTrains[3]["next_betriebsstelle_soll"], " seine Geschwindigkeit auf ", $allTimes[$timeIndex]["live_speed"][0], " km/h angepasst.\n";
				array_shift($allTimes[$timeIndex]["live_time"]);
				array_shift($allTimes[$timeIndex]["live_speed"]);
				array_shift($allTimes[$timeIndex]["live_position"]);
			}
		}
	}
	sleep($sleeptime);
}


// BIG LOOP

	// Step 3: Loop to send the current speed to the train

	// Step 4: Check if the v_max of the next sections has changed

