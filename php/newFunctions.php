<?php

require 'vorbelegung.php';
require 'funktionen_abschnitte.php';
require 'init/init_abschnitte.php';
require 'init/init_fzg.php';
require 'fahrplan_functions.php';

$cacheInfranachbarn = createCacheInfranachbarn();
$cacheInfradaten = createCacheInfradaten();
$cacheSignaldaten = createCacheSignaldaten();

// ERROR
//var_dump(getNaechsteAbschnitte(153, 1));
//var_dump(getNaechsteAbschnitte(354, 1));

//var_dump(getSignalbegriff(89));
//var_dump(getSignalbegriff(91));

// Zeit:
$DB = new DB_MySQL();
$databaseTime = (float) strtotime($DB->select("SELECT CURRENT_TIMESTAMP")[0]->CURRENT_TIMESTAMP);
unset($DB);
$simulationTime = (float) getUhrzeit();
$timeDifference = $databaseTime - $simulationTime;


// Step 1: Initilize all trains (verzoegerung, laenge etc.)

$allTrains = getAllTrains();


foreach ($allTrains as $trainIndex => $trainValue) {
	$allTrains[$trainIndex]["nextBetriebsstellen"] = getNextBetriebsstellen($trainValue["id"]);
}

// v_max, Bezeichnung, Baureihe, evu

// Steop 2: Next stop and the sections between the current position and the next stop
foreach ($allTrains as $trainIndex => $trainValue) {
	$allTrains[$trainIndex]["nextBetriebsstellen"] = getNextBetriebsstellen($trainValue["id"]);
	//$allTrains[$trainIndex]["nextSections"] = getNaechsteAbschnitte($trainValue["id"], $trainValue["dir"]);
}

var_dump($allTrains);

// BIG LOOP

	// Step 3: Loop to send the current speed to the train

	// Step 4: Check if the v_max of the next sections has changed