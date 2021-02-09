<?php

require 'vorbelegung.php';
require 'functions.php';
require 'funktionen_abschnitte.php';
require 'prepare/prepare_fahrplan_session.php';
require 'prepare/prepare_fahrplan_aktuell.php';
require 'update/update_fahrzeuge_aktuell.php';
require 'init/init_fzg.php';
require 'init/update_fzg.php';

// Get the time from the database and the computer where the code is running on.
// TODO: How to deal with time change (1 hour)?
// unix timestamp from database and computer (int)
$DB = new DB_MySQL();
$databaseTime = strtotime($DB->select("SELECT CURRENT_TIMESTAMP")[0]->CURRENT_TIMESTAMP);
unset($DB);
$computerTime = time();

if (true) {
	deleteFahrzeugeAktuell();
	//insertFahrzeugeAktuell();
}

$amountOfTrains = 0;

// In php Datum nur in int!!!
//nextSpeedPositionFahrzeugeAktuell(0, 0, 3256, 532, 1610724600);
//nextSpeedPositionFahrzeugeAktuell(1, 0, 5335, 100, 1610724600);
//nextSpeedPositionFahrzeugeAktuell(2, 0, 1289, 700, 1610724600);
//nextSpeedPositionFahrzeugeAktuell(3, 0, 2946, 0, 1610724600);
//nextSpeedPositionFahrzeugeAktuell(4, 0, 1232, 413, 1610724600);
//initAllTrains(); // creats the array with all fzgs/trains
//updateAllTrains(); // update the allTrains array for the case taht something changed

######################## Grundlagen ########################

// Züge initialisieren
if (true) {
	$allTrains = array();
	//speed sec pos
	// Fahrzeuge initialisieren
	for ($i = 0; $i < 6; $i++) {
		$allTrains = initFzg($i, ($i+1)*1234, 1.4-($i*0.2), 200 - ($i*20), 7861, 0, $allTrains);
	}
}

// Abschnitte initialisieren (ID: 624 - 1362)
if (true) {
	$alleAbschnitte = array();
	for ($i = 0; $i < 739; $i++) {
		$alleAbschnitte = initAbschnitte($i+624, 1000, 160, $alleAbschnitte);
	}
}

######################## Aktuelle Daten ########################

foreach ($allTrains as $key => $value) {

	$allTrains[$key] = setCurrentValues($allTrains, $key, $value);

}

######################## Nächste Abschnitte ########################

// array $allTrains, int $key, array $sections, array $lenghts, array $v_max

$tempSections = array(1037, 1038, 1039, 1040, 1041);
$tempLenghts = array(1000, 1000, 1000, 1000, 1000);
$tempv_max = array(160, 160, 160, 160, 160);

foreach ($allTrains as $key => $value) {

	$allTrains[$key] = updateNextSections($allTrains, $key, $tempSections, $tempLenghts, $tempv_max);

}

//var_dump($allTrains);

######################## Nächster palnmäßiger Halt ########################

foreach ($allTrains as $key => $value) {

	$allTrains[$key] = setTargetSpeed($allTrains, $key, 0, 1036, 5000, 1612195200);

}

/*
$sections = array(89713, 789315, 37548, 315780, 8319576, 38153);
$lengths = array(1000, 1000, 50, 10, 10, 700);
$v_max = array(100, 200, 160, 50, 20, 150);

foreach ($allTrains as $key => $value) {
	$allTrains[$key] = updateNextSections($allTrains, $key, $sections, $lengths, $v_max);
}
*/





foreach ($allTrains as $key => $value) {

	$allTrains[$key] = updateNextSpeed($allTrains, $key, $value);

}

// TODO:
// Wenn ein Zug seine Geschwindigkeit ändert, muss fahrzeuge_aktuell auch geändert werden und aktuallisiert werden

foreach ($allTrains as $key => $value) {

//	var_dump("###############################");
//	var_dump($allTrains[$key]["verzoegerung"]);
//	var_dump($allTrains[$key]["speed"]);
//	var_dump($allTrains[$key]["next_position"][0]);
//	var_dump($allTrains[$key]["next_time"][0]);
//	var_dump("###############################");

}

?>