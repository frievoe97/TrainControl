<?php
/*
function initAbschnitte() {

	$infra_id = 1;

	$DB = new DB_MySQL();

	$abschnittDB   = $DB->select("SELECT `". DB_TABLE_SIGNALE_STANDORTE."`.`id`,
									`". DB_TABLE_SIGNALE_STANDORTE."`.`signaltyp`,
									`". DB_TABLE_SIGNALE_STANDORTE."`.`freimelde_id`,
									`". DB_TABLE_SIGNALE_STANDORTE."`.`gbt_id`,
									`". DB_TABLE_SIGNALE_STANDORTE."`.`wirkrichtung`,
									`".DB_TABLE_SIGNALE_TYPEN."`.`is_hauptsignal`
                                    FROM `".DB_TABLE_SIGNALE_STANDORTE."`
                                    LEFT JOIN `".DB_TABLE_SIGNALE_TYPEN."`
                                    ON `". DB_TABLE_SIGNALE_STANDORTE."`.`signaltyp` = `".DB_TABLE_SIGNALE_TYPEN."`.`signaltyp`
                                    WHERE `".DB_TABLE_SIGNALE_TYPEN."`.`signaltyp` = '".$infra_id."'
                                    ");
	unset($DB);

	var_dump($abschnittDB);

	$abschnitt = array(
		"infra_id" => "",
		"gbt_id" => array(),
		"direction" => "",
		"laenge" => "",
		"v_max" => "",
		"signal_id" => "",
		"signal_anzeige" => ""
	);

	return true;
}
*/