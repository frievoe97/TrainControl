<?php


function initFzg (int $id, int $adresse, float $verzoegerung, int $speed, int $section, int $position, array $pushArray) {

	$fzgTest = array(
		"id" => $id,
		"adresse" => $adresse,
		"verzoegerung" => $verzoegerung,
		"laenge" => "",
		"section" => $section,
		"speed" => $speed,
		"position" => $position,
		"next_speed" => array(),
		"next_time" => array(),
		"next_position" => array(),
		"next_sections" => "",
		"next_lengths" => "",
		"next_v_max" => "",
		"next_speed_change_speed" => "",
		"next_speed_change_section" => "",
		"next_speed_change_position" => "",
		"next_speed_change_time" => ""
	);

	$DB_insert = new DB_MySQL();

	$DB_insert->select("INSERT INTO `". DB_TABLE_FAHRZEUGE_AKTUELL."`
							VALUES ('".$id."','".$verzoegerung."','".$section."','".$speed."', '".$position."',
							CURRENT_TIMESTAMP, null, null, null, null)
                           	");

	unset($DB_insert);

	array_push($pushArray, $fzgTest);

	return $pushArray;

}


function setCurrentValues (array $allTrains, int $key, array $value) {

	$allTrains[$key]["speed"] = 160;
	$allTrains[$key]["section"] = 0;
	$allTrains[$key]["position"] = 0;

	return $allTrains[$key];

}


//$allTrains = array();




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
	"next_speed_change_speed" => "",
	"next_speed_change_section" => "",
	"next_speed_change_position" => "",
	"next_speed_change_time" => ""
);

