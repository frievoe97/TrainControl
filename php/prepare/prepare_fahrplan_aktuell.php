<?php

/*

DB_TABLE_FAHRZEUGE_AKTUELL

*/


function deleteFahrzeugeAktuell() {

	$DB_delete = new DB_MySQL();
	$DB_delete->select("DELETE FROM`". DB_TABLE_FAHRZEUGE_AKTUELL."`");
	unset($DB_delete);

}

function insertFahrzeugeAktuell() {

	$id = [
		0 => 0,
		1 => 1,
		2 => 2,
		3 => 3,
		4 => 4,
	];

	$adresse = [
		0 => 67934,
		1 => 12894,
		2 => 89342,
		3 => 17892,
		4 => 92678,
	];

	$verzoegerung = [
		0 => 1,
		1 => 0.8,
		2 => 0.5,
		3 => 1,
		4 => 0.8,
	];

	$position = [
		0 => 0,
		1 => 0,
		2 => 0,
		3 => 0,
		4 => 0,
	];

	$speed = [
		0 => 60,
		1 => 40,
		2 => 100,
		3 => 80,
		4 => 100,
	];

	$DB_insert = new DB_MySQL();

	for ($i = 0; $i <= 4; $i++) {

		$DB_insert->select("INSERT INTO `". DB_TABLE_FAHRZEUGE_AKTUELL."`
									VALUES ('".$id[$i]."','".$adresse[$i]."','".$verzoegerung[$i]."','".$position[$i]."','".$speed[$i]."',CURRENT_TIMESTAMP,null,null,null,null)
                                   ");

	}

	unset($DB_insert);

}

?>