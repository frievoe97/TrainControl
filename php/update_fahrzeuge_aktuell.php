<?php

/*

DB_TABLE_FAHRZEUGE_AKTUELL


 SELECT `".DB_TABLE_SIGNALE_STANDORTE."`.`id`, `".DB_TABLE_SIGNALE_STANDORTE."`.`freimelde_id`
                                    FROM `".DB_TABLE_SIGNALE_STANDORTE."`
                                    LEFT JOIN `".DB_TABLE_SIGNALE_WENDEN."`
                                    ON (`".DB_TABLE_SIGNALE_STANDORTE."`.`id` = `".DB_TABLE_SIGNALE_WENDEN."` .`gegensignal_id`)
                                    WHERE `".DB_TABLE_SIGNALE_WENDEN."`.`signal_id` = '".$signal_id."'


*/

function updateFahrzeugeAktuell() {

	$DB_update = new DB_MySQL();

	$count = $DB_update->select("SELECT COUNT(*)
										FROM `". DB_TABLE_FAHRZEUGE_AKTUELL."`");

	$countInt = (int) ((array) $count[0])['COUNT(*)'];

	for ($i = 0; $i < $countInt; $i++) {

		$test = $DB_update->select("SELECT `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`position`, 
										`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`speed`, 
										`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`adresse`, 
										`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`timestamp`
								FROM `".DB_TABLE_FAHRZEUGE_AKTUELL."`
								WHERE `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`id` = $i
								");

		$position = (int) ((array) $test[0])['position'];
		$adresse = (int) ((array) $test[0])['adresse'];
		$speed = (int) ((array) $test[0])['speed'];
		$timestamp = strtotime(((array) $test[0])['timestamp']);

		$currentTime = strtotime(((array) $DB_update->select("SELECT CURRENT_TIMESTAMP")[0])['CURRENT_TIMESTAMP']);

		$currentTimeDate = date("Y-m-d H:i:s", $currentTime);

		$timeDiff = $currentTime - $timestamp;

		$newPosition = $speed * ($timeDiff/3600) + $position;

		$DB_update->select("UPDATE `". DB_TABLE_FAHRZEUGE_AKTUELL."`
								SET `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`position` = $newPosition,
								`".DB_TABLE_FAHRZEUGE_AKTUELL."`.`timestamp` = '$currentTimeDate'
								WHERE `".DB_TABLE_FAHRZEUGE_AKTUELL."`.`id` = $i
								");


	}

}



?>
