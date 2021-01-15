<?php

/*

DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN

DB_TABLE_FAHRZEUGE_AKTUELL

 */

function insertSessionFahrplan() {

	/*
	$id = 2;
	$adresse = 6783;
	$position = 1000;
	$speed = 40;

	$DB_insert = new DB_MySQL();

	$DB_insert->select("INSERT INTO `". DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`
									VALUES ('".$id."','".$adresse."','".$position."','".$speed."')
                                   ");

	unset($DB_insert);
	*/

}

function deleteSessionFahrplan() {
	$DB_insert = new DB_MySQL();
	$DB_insert->select("DELETE FROM`". DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`");
	unset($DB_insert);
}

?>
