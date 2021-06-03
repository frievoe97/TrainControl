<?php



function signalSetellen(int $signalbegriff_id) {

	$DB = new DB_MySQL();

	$signal = $DB->select("SELECT `".DB_TABLE_SIGNALE_ELEMENTE."`.`infra_id`, `".DB_TABLE_SIGNALE_ELEMENTE."`.`dir`, `".DB_TABLE_INFRAZUSTAND."`.`type`
                       	FROM `".DB_TABLE_SIGNALE_ELEMENTE."`
                       	LEFT JOIN `".DB_TABLE_INFRAZUSTAND."`
                      	ON (`".DB_TABLE_SIGNALE_ELEMENTE."`.`infra_id` = `".DB_TABLE_INFRAZUSTAND."`.`id`)
                       	WHERE `signal_id` = '".$signalbegriff_id."' ");








	foreach ($signal AS $element) {
		$daten = $DB->query("UPDATE `".DB_TABLE_INFRAZUSTAND."`
                 			SET `dir` = '".$element->dir."'
                      		WHERE `id` = '".$element->infra_id."'
                     		");
	}



}