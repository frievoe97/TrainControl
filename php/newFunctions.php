<?php

require 'vorbelegung.php';
require 'funktionen_abschnitte.php';
require 'init/init_abschnitte.php';
require 'init/init_fzg.php';
require 'functions.php';

$cacheInfranachbarn = createCacheInfranachbarn();
$cacheInfradaten = createCacheInfradaten();
$cacheSignaldaten = createCacheSignaldaten();

// Zeit:
$DB = new DB_MySQL();
$databaseTime = (float) strtotime($DB->select("SELECT CURRENT_TIMESTAMP")[0]->CURRENT_TIMESTAMP);
unset($DB);
$simulationTime = (float) getUhrzeit();
$timeDifference = $databaseTime - $simulationTime;



// Step 1: Initilize all trains (verzoegerung, laenge etc.)
$allTrains = getAllTrains();

// delete trains with no v_max
$newAllTrains = array();
foreach ($allTrains as $train) {
		if ($train["vmax"] != null) {
			array_push($newAllTrains, $train);
		}
	}
$allTrains = $newAllTrains;

// Get all Zug IDs
foreach ($allTrains as $trainIndex => $trainValue) {
	$allTrains[$trainIndex]["zug_id"] = getFahrzeugZugIds(array($allTrains[$trainIndex]["id"]));
}

var_dump(getNextBetriebsstellen(20513));

// Get first Betriebsstelle
foreach ($allTrains as $trainIndex => $trainValue) {
	$zug_id = 20513;//intval($allTrains[$trainIndex]["zug_id"]);
	$allTrains[$trainIndex]["next_betriebsstellen"] = getNextBetriebsstellen($zug_id);

	/*
	foreach ($allTrains[$trainIndex]["next_betriebsstellen"] as $betriebsstelleIndex => $betriebsstelleValue) {
		$allTrains[$trainIndex]["next_betriebsstellen"][$betriebsstelleIndex]["zeiten"] = getFahrplanzeiten($betriebsstelleValue["betriebsstelle"], $zug_id);
	}
	*/
}

var_dump($allTrains);

// BIG LOOP

	// Step 3: Loop to send the current speed to the train

	// Step 4: Check if the v_max of the next sections has changed

