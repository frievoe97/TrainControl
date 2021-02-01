<?php

require 'vorbelegung.php';
require 'functions.php';
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

deleteFahrzeugeAktuell();
//insertFahrzeugeAktuell();

$amountOfTrains = 0;

//nextSpeedPositionFahrzeugeAktuell(0, 0, 3256, 532, 1610724600);
//nextSpeedPositionFahrzeugeAktuell(1, 0, 5335, 100, 1610724600);
//nextSpeedPositionFahrzeugeAktuell(2, 0, 1289, 700, 1610724600);
//nextSpeedPositionFahrzeugeAktuell(3, 0, 2946, 0, 1610724600);
//nextSpeedPositionFahrzeugeAktuell(4, 0, 1232, 413, 1610724600);


//initAllTrains(); // creats the array with all fzgs/trains
//updateAllTrains(); // update the allTrains array for the case taht something changed


$allTrains = array();
//speed sec pos
// Fahrzeuge initialisieren
for ($i = 0; $i < 6; $i++) {
	$allTrains = initFzg($i, ($i+1)*1234, 1.4-($i*0.2), 200 - ($i*20), 7861, 0, $allTrains);
}

// 1611536400

// Anfangsparameter


// nächsten Halt festlegen
foreach ($allTrains as $key => $value) {

	//$allTrains[$key] = setCurrentValues($allTrains, $key, $value);

}


foreach ($allTrains as $key => $value) {

	$allTrains[$key] = setTargetSpeed($allTrains, $key, 0, 1342, 5000, 1612105200);

}


foreach ($allTrains as $key => $value) {

	updateNextSpeed($allTrains, $key, $value);

}






//var_dump($allTrains);










//checkChangeSpeed();
//updateFahrzeugeAktuell();







// TODO:
// Wenn ein Zug seine Geschwindigkeit ändert, muss fahrzeuge_aktuell auch geändert werden und aktuallisiert werden





?>