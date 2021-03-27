<?php


function initFzg (int $id, int $adresse, float $verzoegerung, int $speed, int $section, int $position, array $pushArray) {

	$fzgTest = array(
		"id" => $id,
		"adresse" => $adresse,
		"verzoegerung" => $verzoegerung,
		"notverzoegerung" => 2,
		"speedConstant" => "", // true or false
		"laenge" => "",
		"section" => $section,
		"speed" => $speed,
		"position" => $position,
		"next_speed" => array(),
		"next_time" => array(),
		"next_position" => array(),
		"next_sections" => array(),
		"next_lengths" => array(),
		"next_v_max" => array(),
		"next_timetable_change_speed" => "",
		"next_timetable_change_section" => "",
		"next_timetable_change_position" => "",
		"next_timetable_change_time" => "",
		"next_signal_change_speed" => "",
		"next_signal_change_section" => "",
		"next_signal_change_position" => "",
		"next_signal_change_time" => ""
	);

	/*
	$DB_insert = new DB_MySQL();
	$DB_insert->select("INSERT INTO `". DB_TABLE_FAHRZEUGE_AKTUELL."`
							VALUES ('".$id."','".$verzoegerung."','".$section."','".$speed."', '".$position."',
							CURRENT_TIMESTAMP, null, null, null, null)
                           	");

	unset($DB_insert);
	*/
	array_push($pushArray, $fzgTest);
	return $pushArray;
}

// Jeder Eintrag in dem Array sthet für EINEN Zug, deswegen wird auch eine ID für den Zug übergeben
function initAbschnitte (array $ID, array $LENGTH, array $VMAX, array $pushArray) {

	$fzgTest = array(
		"id" => $ID,
		"length" => $LENGTH,
		"v_max" => $VMAX
	);

	array_push($pushArray, $fzgTest);
	return $pushArray;
}


function setCurrentValues (array $allTrains, int $key, int $speed, int $section, int $position) {

	$allTrains[$key]["speed"] = $speed;
	$allTrains[$key]["section"] = $section;
	$allTrains[$key]["position"] = $position;

	return $allTrains[$key];

}

// Create a new fzg
$fzgTest = array(
	"id" => 652,
	"adresse" => 7845,
	"verzoegerung" => 1,
	"section" => 100,
	"speed" => "",
	"position" => "",
	"next_speed" => array(),
	"next_time" => array(),
	"next_position" => array(),
	"next_sections" => "",
	"next_lengths" => "",
	"next_v_max" => "",
	"next_timetable_change_speed" => "",
	"next_timetable_change_section" => "",
	"next_timetable_change_position" => "",
	"next_timetable_change_time" => ""
);




function getAllTrains () : array {

	$allAdresses = getAllAdresses();
	$DB = new DB_MySQL();
	$allTrains = array();

	foreach ($allAdresses as $adress) {

		$train = get_object_vars($DB->select("SELECT `".DB_TABLE_FAHRZEUGE."`.`id`, 
							`".DB_TABLE_FAHRZEUGE."`.`adresse`, 
							`".DB_TABLE_FAHRZEUGE."`.`timestamp`, 
							`".DB_TABLE_FAHRZEUGE."`.`speed`, 
							`".DB_TABLE_FAHRZEUGE."`.`dir`, 
							`".DB_TABLE_FAHRZEUGE."`.`zugtyp`, 
							`".DB_TABLE_FAHRZEUGE."`.`zuglaenge`, 
							`".DB_TABLE_FAHRZEUGE."`.`prev_speed`, 
							`".DB_TABLE_FAHRZEUGE."`.`fzs`, 
							`".DB_TABLE_FAHRZEUGE."`.`verzoegerung`, 
							`".DB_TABLE_FAHRZEUGE."`.`zustand`        
                            FROM `".DB_TABLE_FAHRZEUGE."`
                            WHERE `".DB_TABLE_FAHRZEUGE."`.`adresse` = $adress
                           ")[0]);

		$trainTwo = $DB->select("SELECT `".DB_TABLE_FAHRZEUGE_BAUREIHEN."`.`bezeichnung`, 
							`".DB_TABLE_FAHRZEUGE_BAUREIHEN."`.`vmax`, 
							`".DB_TABLE_FAHRZEUGE_BAUREIHEN."`.`traktion`      
                            FROM `".DB_TABLE_FAHRZEUGE_BAUREIHEN."`
                            WHERE `".DB_TABLE_FAHRZEUGE_BAUREIHEN."`.`nummer` = $adress
                           ");

		if (sizeof($trainTwo) != 0) {
			$trainTwo = get_object_vars($trainTwo[0]);
		} else {
			$trainTwo["bezeichnung"] = null;
			$trainTwo["vmax"] = null;
			$trainTwo["traktion"] = null;
		}

		$returnArray = array_merge($train, $trainTwo);

		array_push($allTrains, $returnArray);
	}

	unset($DB);

	return $allTrains;
}

function getAllAdresses () : array {

	$returnAdresses = array();
	$DB = new DB_MySQL();

	$adresses = $DB->select("SELECT DISTINCT `".DB_TABLE_FAHRZEUGE."`.`adresse`
                            FROM `".DB_TABLE_FAHRZEUGE."`
                           ");

	unset($DB);

	foreach ($adresses as $adressIndex => $adressValue) {
		array_push($returnAdresses, (int) $adressValue->adresse);
	}

	return $returnAdresses;
}

function getNextBetriebsstellen (int $id) : array {

	$DB = new DB_MySQL();
	$returnBetriebsstellen = array();

	$betriebsstellen = $DB->select("SELECT `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`betriebsstelle`
                            FROM `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`
                            WHERE `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`zug_id` = $id
                            ORDER BY `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`id` ASC
                           ");

	unset($DB);

	foreach ($betriebsstellen as $betriebsstellenIndex => $betriebsstellenValue) {
		array_push($returnBetriebsstellen, $betriebsstellenValue->betriebsstelle);
	}

	if (sizeof($betriebsstellen) == 0) {
		debugMessage("Zu dieser Zug ID sind keine nächsten Betriebsstellen im Fahrplan vorhanden.");
	}

	return $returnBetriebsstellen;
}

