<?php

require "functions.php";

/*

// ------------------------------------------------------------------------------------
// Ermittelt die Ankunfts- und Abfahrtzeit eines Zuges an einer Betriebsstelle
function getFahrplanzeiten ($betriebsstelle, $zugnummer, $options = array()) {
	if (!isset($betriebsstelle)) { return false; }
	if (!isset($options["art"])) { $options["art"] = "hauptbetriebsstelle"; }
	if (!isset($options["id"])) { $options["id"] = "zugnummer"; }
	if (!isset($options["fzmquelle"])) { $options["fzmquelle"] = DB_TABLE_FAHRPLAN; }

	if ($options["fzmquelle"] == DB_TABLE_FAHRPLAN) {
		$where_version = " AND `".$options["fzmquelle"]."`.`version` = '".FAHRPLAN_VERSION_NAME."'";
		$uebergang_fahrtrichtung = ",`fahrplan_uebergang`.`fahrtrichtung` AS `uebergang_fahrtrichtung` ";
		$uebergang_nach = "LEFT JOIN `".$options["fzmquelle"]."` AS `fahrplan_uebergang`
                                ON (`".$options["fzmquelle"]."`.`uebergang_nach` = `fahrplan_uebergang`.`zugnummer` AND  
                                    `".$options["fzmquelle"]."`.`betriebsstelle` = `fahrplan_uebergang`.`betriebsstelle` 
                                    AND `".$options["fzmquelle"]."`.`version` = `fahrplan_uebergang`.`version`)";
	} else {
		$where_version = "";
		$uebergang_fahrtrichtung = "";
		$uebergang_nach = "";
	}

	$felder_anab = getDBFieldsAnAb ($options["fzmquelle"]);

	if ($options["art"] == "wendepruefung") {
		if (isset($options["signaltyp"]) && (in_array($options["signaltyp"],array("ESig","BkSig")))) {
			$options["art"] = "bst";
		} else {
			$options["art"] = "hauptbetriebsstelle";
		}
	}

	// Entweder per Zugnummer oder (besser!) per zug_id abfragen
	switch ($options["id"]) {
		default:
		case "zugnummer":
			{
				$zugschluessel = "zugnummer";
			}
			break;

		case "zug_id":
			{
				$zugschluessel = "zug_id";
			}
			break;
	}

	// Abfrageart
	switch ($options["art"]) {
		default:
		case "hauptbetriebsstelle":
			{
				$betriebsstelle_zerlegt = explode ("_",$betriebsstelle);
				$hauptbetriebsstelle = $betriebsstelle_zerlegt[0];
			}
			break;

		case "bst":
			{
				$hauptbetriebsstelle = $betriebsstelle;
			}
			break;
	}

	$DB   = new DB_MySQL();
	$fahrplandaten = $DB->select("SELECT ".$felder_anab." `".$options["fzmquelle"]."`.`fahrtrichtung` ,
                                      `".$options["fzmquelle"]."`.`ist_durchfahrt`, `".$options["fzmquelle"]."`.`sortierzeit`
                                     ".$uebergang_fahrtrichtung."
                               FROM `".$options["fzmquelle"]."`
                               ".$uebergang_nach."                                
                               WHERE `".$options["fzmquelle"]."`.`betriebsstelle` = '".$hauptbetriebsstelle."'
                                AND  `".$options["fzmquelle"]."`.`".$zugschluessel."` = '".$zugnummer."'
                               ".$where_version);
	unset ($DB);

	if (count($fahrplandaten) == 0) {
		return false;
	} else {
		return $fahrplandaten[0];
	}
}


// ------------------------------------------------------------------------------------
// Wandele eine Realzeit (vom Server) in eine Simulationszeit oder umgekehrt (als Timestamp)
function getUhrzeit ($inputzeit = NULL, $zielart = "simulationszeit", $timeshift = NULL, $options = array()) {
	if (!isset($inputzeit) || is_null($inputzeit)) { $inputzeit = time(); }
	if (!isset($timeshift) || is_null($timeshift) || !is_numeric($timeshift)) { $timeshift = getTimeshift(); }

	// Wenn als Inputtyp "h:m" übergeben wird, muss die Zeit zunächst umformatiert werden auf einen heutigen Timestamp
	if (!isset($options["inputtyp"])) { $options["inputtyp"] = "timestamp"; }

	// Ausgabetyp
	if (!isset($options["outputtyp"])) { $options["outputtyp"] = "timestamp"; }

	if (!defined('FAHRPLAN_SESSION_SIM_IVUTAG')) {
		define('FAHRPLAN_SESSION_SIM_IVUTAG',date("Y-m-d"));
	}
	$sim_datum = explode("-",FAHRPLAN_SESSION_SIM_IVUTAG);

	if (in_array($options["inputtyp"],array("h:i","h:i:s")) && (substr_count($inputzeit,":") == 0)) { $options["inputtyp"] = "timestamp"; }

	switch ($options["inputtyp"]) {
		default:
		case "timestamp":
			{
				$zeit = $inputzeit;
			}
			break;

		case "h:i":
		case "h:i:s":
			{
				$zeitdaten = explode (":",$inputzeit);
				if (!isset($zeitdaten[2])) { $zeitdaten[2] = 0; } // Wenn es keine Sekunden gibt

				$zeit = mktime($zeitdaten[0],$zeitdaten[1],$zeitdaten[2],$sim_datum[1],$sim_datum[2],$sim_datum[0]);
			}
			break;
	}

	switch ($zielart) {
		case "simulationszeit":
			{
				// Simulationszeit = Realzeit + Timeshift
				$outputzeit = $zeit + $timeshift;
			}
			break;

		case "realzeit":
			{
				// Realzeit = Simulationszeit - Timeshift
				$outputzeit = $zeit - $timeshift;
			}
			break;
	}

	switch ($options["outputtyp"]) {
		default:
		case "timestamp":
			{
				// $outputzeit = $outputzeit;
			}
			break;

		case "h:i:s":
			{
				$outputzeit = date("H:i:s",$outputzeit);
			}
			break;

		case "H:i":
			{
				$outputzeit = date("H:i",$outputzeit);
			}
			break;

		case "Y-m-d H:i:s":
			{
				$zeit = mktime(date("H",$outputzeit),date("i",$outputzeit),date("s",$outputzeit),$sim_datum[1],$sim_datum[2],$sim_datum[0]);
				$outputzeit = date("Y-m-d H:i:s",$zeit);
			}
			break;
	}

	return $outputzeit;
}

// ------------------------------------------------------------------------------------
// Liefert die aktuelle Zeitverschiebung
function getTimeshift() {
	$DB = new DB_MySQL();
	$sessionDB = $DB->select("SELECT `timeshift`
                           FROM `".DB_TABLE_FAHRPLAN_SESSION."`
                           WHERE `".DB_TABLE_FAHRPLAN_SESSION."`.`status` IN (1,2,5)
                           ORDER BY `status` DESC LIMIT 0,1
                          ");
	unset ($DB);

	if (count($sessionDB) == 0 || !is_numeric($sessionDB[0]->timeshift) || is_null($sessionDB[0]->timeshift)) {
		return 0;
	}

	return $sessionDB[0]->timeshift;
}

// ------------------------------------------------------------------------------------
function getDBFieldsAnAb ($quelle, $options = array()) {
	if (!isset($options["zeitformat"])) { $options["zeitformat"] = "hh:mm:ss" ; }

	switch ($options["zeitformat"]) {
		default:
		case "hh:mm:ss":
			{
				$zeitformat = "%H:%i:%s";
			}
			break;
		case "hh:mm":
			{
				$zeitformat = "%H:%i";
			}
			break;
	}
	$felder_anab = "DATE_FORMAT(`".$quelle."`.`abfahrt_plan`,'%H:%i') AS `abfahrt`,
                 DATE_FORMAT(`".$quelle."`.`ankunft_plan`,'%H:%i') AS `ankunft`,
                 DATE_FORMAT(`".$quelle."`.`ankunft_plan`,'".$zeitformat."') AS `ankunft_plan`,
                 DATE_FORMAT(`".$quelle."`.`abfahrt_plan`,'".$zeitformat."') AS `abfahrt_plan`,
                 `".$quelle."`.`abfahrt_plan` AS `abfahrt_exakt`,
                 `".$quelle."`.`ankunft_plan` AS `ankunft_exakt`,";
	$felder_anab .= "`".$quelle."`.`gleis_plan`, ";

	if ($quelle == DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN) {
		$felder_anab .= "`".$quelle."`.`abfahrt_soll`, 
                   `".$quelle."`.`ankunft_soll`, ";
		$felder_anab .= "DATE_FORMAT(`".$quelle."`.`abfahrt_ist`,'%H:%i') AS `abfahrt_ist`,
                   DATE_FORMAT(`".$quelle."`.`ankunft_ist`,'%H:%i') AS `ankunft_ist`,";
		$felder_anab .= "DATE_FORMAT(`".$quelle."`.`abfahrt_soll`,'%H:%i') AS `abfahrt_soll`,
                   DATE_FORMAT(`".$quelle."`.`ankunft_soll`,'%H:%i') AS `ankunft_soll`,";
		$felder_anab .= "DATE_FORMAT(`".$quelle."`.`abfahrt_prognose`,'%H:%i') AS `abfahrt_prognose`,
                   DATE_FORMAT(`".$quelle."`.`ankunft_prognose`,'%H:%i') AS `ankunft_prognose`,";
		$felder_anab .= "`".$quelle."`.`gleis_soll`, `".$quelle."`.`gleis_ist`, ";
	}

	return $felder_anab;
}

// -------------
// Ermittelt zweistufig die Zug-ID zu Fahrzeug-IDs
function getFahrzeugZugIds ($fahrzeug_ids = array())  {

	$zug_ids = array();

	// Wenn nichts übergeben wird, werden alle Zuordnungen Fahrzeug-ID <-> [Zug-ID,position] zurückgegeben
	if (!isset($fahrzeug_ids) || count($fahrzeug_ids) == 0) {
		debugMessage("Es wurden keine Fahrzeug-IDs übergeben. Ermittele alle Fahrzeuge, die im Einsatzstehen (Zustand <= ".FZS_FZGZUSTAND_AUFGERUESTET.").");
		$DB = new DB_MySQL();
		$fahrzeug_ids_select = $DB->select("SELECT `id` FROM `".DB_TABLE_FAHRZEUGE."` WHERE `zustand` <= ".FZS_FZGZUSTAND_AUFGERUESTET." ");
		unset($DB);

		if ($fahrzeug_ids_select && count($fahrzeug_ids_select) > 0) {
			foreach ($fahrzeug_ids_select as $fahrzeug_id) {
				$fahrzeug_ids[] = $fahrzeug_id->id;
			}
		}
	}

	// Ermittele die Fahrzeug-ID aus der Zugtabelle (sofern diese nur bei einem Zug aktuell gesetzt ist
	$DB = new DB_MySQL();
	$zug_ids_select = $DB->select("SELECT `id` as `zug_id`, `fahrzeug_id` FROM `".DB_TABLE_FAHRPLAN_SESSIONZUEGE."` WHERE `fahrzeug_id` IN ('".implode("','",$fahrzeug_ids)."')");
	unset($DB);

	if ($zug_ids_select && count($zug_ids_select) > 0) {
		foreach ($zug_ids_select as $zug_id) {

			if (!isset($zug_ids[$zug_id->fahrzeug_id])) {
				$zug_ids[$zug_id->fahrzeug_id] = array("zug_id" => $zug_id->zug_id, "position" => 1);
			} else {
				// Es gibt schon eine Zug-ID für dieses Fahrzeug => es muss weitergesucht werden
				$fahrzeug_pruefung[] = $zug_id->fahrzeug_id;
			}
		}
	}

	// In $zug_ids sind nun die eindeutigen Zuordnungen enthalten
	// $fahrzeug_pruefung enthält die Fahrzeuge mit mehreren Zug-ID-Treffern

	if (isset($fahrzeug_pruefung) && count($fahrzeug_pruefung) > 0) {
		debugMessage("Bei ".count($fahrzeug_pruefung)." Fahrzeugen wurden mehrere Zug-IDs gefunden.");
		foreach ($fahrzeug_pruefung as $key => $fahrzeug_id) {

			if (isset($zug_ids[$fahrzeug_id])) {
				unset($zug_ids[$fahrzeug_id]);
			}
			// Ermittele die Decoder-Adresse aus der Fahrzeug-ID
			$fahrzeugdaten = getFahrzeugdaten(array("id" => $fahrzeug_id), "adresse");

			if ($fahrzeugdaten) {
				debugMessage ("Suche Daten für Fahrzeug ".$fahrzeug_id.".");
				// Relevant ist nur die Zug-ID des Zuges, der aktuell auch im Netz unterwegs ist
				// !!! To-Do: Bei Zug- und Rangierfahrten mit derselben Nummer kann das hier noch fehlerhaft sein, da die Betriebsstelle nicht genutzt wird !!!
				$DB = new DB_MySQL();
				$zug_id_suche = $DB->select("SELECT `".DB_TABLE_FAHRPLAN_SESSIONZUEGE."`.`id` as `zug_id`
                                 FROM `".DB_TABLE_FMA."`
                                 LEFT JOIN `".DB_TABLE_FMA_GBT."` 
                                  ON (`".DB_TABLE_FMA."`.`fma_id` = `".DB_TABLE_FMA_GBT."`.`fma_id`) 
                                 LEFT JOIN `".DB_TABLE_ZN_GBT."` 
                                  ON (`".DB_TABLE_FMA_GBT."`.`gbt_id` = `".DB_TABLE_ZN_GBT."`.`id`)
                                 LEFT JOIN `".DB_TABLE_FAHRPLAN_SESSIONZUEGE."` 
                                  ON (`".DB_TABLE_ZN_GBT."`.`zugnummer` = `".DB_TABLE_FAHRPLAN_SESSIONZUEGE."`.`zugnummer`)
                                 WHERE `".DB_TABLE_FMA."`.`decoder_adresse` = '".$fahrzeugdaten->adresse."' AND `".DB_TABLE_ZN_GBT."`.`zugnummer` > 0 AND `".DB_TABLE_ZN_GBT."`.`id` IS NOT NULL
                                 ORDER BY `".DB_TABLE_FMA."`.`timestamp` ");

				unset($DB);

				if ($zug_id_suche) {
					foreach ($zug_id_suche as $zug_id) {
						if (!is_null($zug_id->zug_id)) {
							$zug_ids[$fahrzeug_id] = array("zug_id" => $zug_id->zug_id, "position" => 1);
						}
					}
					debugMessage ("Keine zug_id gefunden für Fahrzeug ".$fahrzeug_id."!");
				}
			}
		}
	}

	debugMessage ("Es wurden ".count($zug_ids)." Zuordnungen von Zug-IDs zu Fahrzeugen gefunden.");
	return $zug_ids;
}

*/

