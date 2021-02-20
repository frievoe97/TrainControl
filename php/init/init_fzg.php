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

