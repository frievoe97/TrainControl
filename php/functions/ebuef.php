<?php

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

// Ermittele die nächsten Freimeldeabschnitte
function getNaechsteAbschnitte($start_infra_id, $fahrtrichtung, $zugtyp = "pz", $optional = array()) {

	// optional["naechstessignal"] = true liefert den Abschnitt des nächsten Signals in Fahrtrichtung
	global $cacheInfradaten, $cacheInfranachbarn, $cacheSignaldaten;

	$weitermachen = true;
	$abschnitte = array();

	if (!isset($cacheSignaldaten) || !isset( $cacheInfradaten) || !isset($cacheInfranachbarn) ) {
		die("cacheSignaldaten, cacheInfradaten und cacheInfranachbarn müssen gefüllt sein.");
	}
	if (!isset($cacheInfradaten[$start_infra_id]["nachbarn"])) {
		die("Unbekannte Infra-ID ".$start_infra_id);
	}

	debugMessage("Ermittele nächste Abschnitte ausgehend von Infra-ID ".$start_infra_id." in Richtung ".$fahrtrichtung);
	$nachbarn = $cacheInfradaten[$start_infra_id]["nachbarn"];

	// Wenn es mehrere Abschnitte gibt, dann sollte in Fahrtrichtung der letzte genommen (zur sicheren Seite) werden
	if (count($nachbarn) > 1) {
		$aktuell = $nachbarn[0];
		debugMessage("Es gibt mehrere Abschnitte zu dieser Infra-ID.");
	} else {
		$aktuell = $nachbarn[0];
	}

	// Wenn nach Gegensignalen gesucht wird, wird diese Prüfung hier ausgeführt, da das Signal am selben Abschnitt stehen könnte
	if (isset($optional["naechstessignal"]) && $optional["naechstessignal"]) {
		if (isset($cacheSignaldaten["freimeldeabschnitte"][$start_infra_id][$fahrtrichtung]["signalstandort_id"])) {
			$signalstandort_id = $cacheSignaldaten["freimeldeabschnitte"][$start_infra_id][$fahrtrichtung]["signalstandort_id"];
			debugMessage("Am Startabschnitt ".$start_infra_id." steht in Fahrtrichtung ".$fahrtrichtung." das Signal ".$signalstandort_id.".");
			$weitermachen = false;

			//Der Abschnitt wird im Array gesammelt
			$abschnitte[] = array ("nachbar_id" => null,
				"infra_id" => $start_infra_id,
				"laenge" => 0,
				"signal_id" => $signalstandort_id);
		}
	}

	if ($weitermachen) {
		do {
			$signalstandort_id = null;

			// Ermittele die Nachbarn bis zum nächsten Zielpunkt
			// Wenn Weiche vorhanden, muss deren Stellung ermittelt werden, sonst gilt 0
			if (isset($aktuell["weiche_id"]) && !is_null($aktuell["weiche_id"])) {
				$dir = getDir($aktuell["weiche_id"]);
				debugMessage("Weiche ".$aktuell["weiche_id"]." steht in Stellung ".$dir.".");
			} else {
				$dir = 0;
			}

			// Dann nachbar$fahrtrichtung_$dir nehmen und für den weitersuchen
			if (!is_null($cacheInfranachbarn[$aktuell["id"]]["nachbar".$fahrtrichtung."_".$dir]) && $weitermachen) {

				$naechster_id = $cacheInfranachbarn[$aktuell["id"]]["nachbar".$fahrtrichtung."_".$dir];
				debugMessage ("Es gibt einen Nachbarabschnitt ".$naechster_id." (Infra-ID ".$cacheInfranachbarn[$naechster_id]["infra_id"].")");
				// Wenn am neuen letzten Abschnitt ein Halt zeigendes Signal steht oder dieser belegt ist,
				// wird abgebrochen
				if (isset($cacheSignaldaten["freimeldeabschnitte"][$cacheInfranachbarn[$naechster_id]["infra_id"]][$fahrtrichtung]["signalstandort_id"])) {
					$signalstandort_id = $cacheSignaldaten["freimeldeabschnitte"][$cacheInfranachbarn[$naechster_id]["infra_id"]][$fahrtrichtung]["signalstandort_id"];

					// Wenn das nächste Signal gesucht ist, spielt der Begriff keine Rolle
					if (isset($optional["naechstessignal"]) && $optional["naechstessignal"]) {
						debugMessage ("Nächstes Signal gefunden!");
						$weitermachen = false;
					} else {
						debugMessage("Ermittele Signalbegriff an Signalstandort ".$signalstandort_id);
						$signalbegriff = getSignalbegriff($signalstandort_id);

						if ($signalbegriff && $signalbegriff[0]["geschwindigkeit"] == 0 && $signalbegriff[0]["id"] != 548) {
							debugMessage("Signalbegriff ist ".$signalbegriff[0]["begriff"].".");
							$weitermachen = false;
						} else {
							debugMessage ("Signal zeigt keinen Haltbegriff");
						}
					}
				}

				//Der Abschnitt wird im Array gesammelt
				$abschnitte[] = array ("nachbar_id" => $naechster_id,
					"infra_id" => $cacheInfranachbarn[$naechster_id]["infra_id"],
					"laenge" => $cacheInfradaten[$cacheInfranachbarn[$naechster_id]["infra_id"]]["laenge"],
					"signal_id" => $signalstandort_id);

				$aktuell = $cacheInfranachbarn[$naechster_id];
			} else {
				// Wenn es keinen Nachbarn dort gibt (oder wenn vorher schon abgebrochen wurde) => abbruch

				if ($weitermachen) {
					debugMessage("Es gibt keinen weiteren Nachbarn. Offenbar ist hier ein Gleisende.");
					$weitermachen = false;
				} else {
					// Es wurde vorher schon abgebrochen
				}
			}
		} while ($weitermachen);
	}
	// Die gesammelten Abschnitte werden aufbereitet (alle Teilabschnitte einer Infra-ID zusammengefasst)
	$anzahl_abschnitte = count($abschnitte);
	if ($anzahl_abschnitte > 0) {
		$vorgaenger = 0;
		for ($a = 1; $a < $anzahl_abschnitte; $a++) {
			if ($abschnitte[$vorgaenger]["infra_id"] == $abschnitte[$a]["infra_id"]) {
				$abschnitte[$vorgaenger]["laenge"] = $abschnitte[$vorgaenger]["laenge"]+$abschnitte[$a]["laenge"];
				unset($abschnitte[$a]);
			} else {
				$vorgaenger = $a;
			}
		}
	}
	return $abschnitte;
}

// ------------------------------------------------------------------------------------------------
// Ermittelt die aktuelle Richtung eines Infrastrukturelements
function getDir ($id) {
	if (empty($id) || $id == 0) {
		return false;
	} else {
		$DB = new DB_MySQL();
		$daten = $DB->select("SELECT `dir` FROM `".DB_TABLE_INFRAZUSTAND."` WHERE `id` = '".$id."' ");
		unset ($DB);

		if (count($daten) == 0) {
			return false; }
		else {
			return $daten[0]->dir;
		}
	}
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

// Ermittelt die Ankunfts- und Abfahrtzeit eines Zuges an einer Betriebsstelle
function getFahrplanzeiten (string $betriebsstelle, int $zug_id, array $options = array ("id" => "zug_id", "art" => "wendepruefung")) {

	if(!isset($betriebsstelle, $zug_id)) {
		return false;
	}

	$fahrplandaten = array();

	$DB = new DB_MySQL();
	$fahrplandaten_temp = $DB->select("SELECT `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`ankunft_soll`,
                        `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`abfahrt_soll`,
                        `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`fahrtrichtung`,
                        `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`ist_durchfahrt`,
                        `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`wendet`
                        FROM `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`
                        WHERE `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`betriebsstelle` = '".$betriebsstelle."'
                        AND `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`zug_id` = $zug_id
                        ");
	unset($DB);

	/*
	if(!isset($fahrplandaten_temp[0]->abfahrt_soll, $fahrplandaten_temp[0]->ankunft_soll, $fahrplandaten_temp[0]->fahrtrichtung, $fahrplandaten_temp[0]->ist_durchfahrt)) {
		return false;
	}
	*/

	if(!isset($fahrplandaten_temp[0]->abfahrt_soll) && !isset($fahrplandaten_temp[0]->ankunft_soll) && !isset($fahrplandaten_temp[0]->fahrtrichtung) && !isset($fahrplandaten_temp[0]->ist_durchfahrt) && !isset($fahrplandaten_temp[0]->wendet)) {
		return false;
	} else {
		if(isset($fahrplandaten_temp[0]->abfahrt_soll)) {
			$fahrplandaten["abfahrt_soll"] = $fahrplandaten_temp[0]->abfahrt_soll;
		} else {
			$fahrplandaten["abfahrt_soll"] = null;
		}
		if(isset($fahrplandaten_temp[0]->ankunft_soll)) {
			$fahrplandaten["ankunft_soll"] = $fahrplandaten_temp[0]->ankunft_soll;
		} else {
			$fahrplandaten["ankunft_soll"] = null;
		}
		if(isset($fahrplandaten_temp[0]->fahrtrichtung)) {
			$fahrplandaten["fahrtrichtung"] = $fahrplandaten_temp[0]->fahrtrichtung;
		} else {
			$fahrplandaten["fahrtrichtung"] = null;
		}
		if(isset($fahrplandaten_temp[0]->ist_durchfahrt)) {
			$fahrplandaten["ist_durchfahrt"] = $fahrplandaten_temp[0]->ist_durchfahrt;
		} else {
			$fahrplandaten["ist_durchfahrt"] = null;
		}
		if(isset($fahrplandaten_temp[0]->wendet)) {
			$fahrplandaten["wendet"] = $fahrplandaten_temp[0]->wendet;
		} else {
			$fahrplandaten["wendet"] = null;
		}
	}

	/*
	$fahrplandaten = [
		"abfahrt_soll" => $fahrplandaten_temp[0]->abfahrt_soll,
		"ankunft_soll" => $fahrplandaten_temp[0]->ankunft_soll,
		"fahrtrichtung" => $fahrplandaten_temp[0]->fahrtrichtung,
		"ist_durchfahrt" => $fahrplandaten_temp[0]->ist_durchfahrt,
	];
	*/

	return $fahrplandaten /*(array: abfahrt_soll, ankunft_soll, fahrtrichtung, ist_durchfahrt, uebergang_fahrtrichtung)*/;

}

// ------------------------------------------------------------------------------------------------
// Ermittlung des Signalbegriffs eines Signals(tandorts)
// ------------------------------------------------------------------------------------------------
function getSignalbegriff ($signal_id, $optionen = array()) {

	global $cacheSignaldaten;

	if (isset($optionen["getSignaltyp"]) && $optionen["getSignaltyp"]) {
		$felder = ", `".DB_TABLE_SIGNALE_STANDORTE."`.`signaltyp`, `".DB_TABLE_SIGNALE_STANDORTE."`.`id` AS `signalstandort_id` ";
		$join   = "LEFT JOIN `".DB_TABLE_SIGNALE_STANDORTE."` ON (`".DB_TABLE_SIGNALE_BEGRIFFE."`.`signal_id` = `".DB_TABLE_SIGNALE_STANDORTE."`.`id` )";
	} else {
		$felder = "";
		$join = "";
	}

	$DB = new DB_MySQL();

	$signale = array();

// Ermittlung der in Frage kommenden Signalbegriffe
// Wenn das global-Array $cacheSignaldaten["begriffe"] nicht existiert, müssen sie gesucht werden
	if (isset($cacheSignaldaten) && isset($cacheSignaldaten["standorte"]) && isset($cacheSignaldaten["standorte"][$signal_id]["begriffe_id"]) && isset($cacheSignaldaten["begriffe"])) {
		foreach ($cacheSignaldaten["standorte"][$signal_id]["begriffe_id"] as $begriff_key => $signalstandorteintrag) {
			$signale[] = array("id" => $signalstandorteintrag, "geschwindigkeit" => $cacheSignaldaten["begriffe"][$signalstandorteintrag]["geschwindigkeit"],
				"begriff" => $cacheSignaldaten["begriffe"][$signalstandorteintrag]["begriff"],
				"webstw_farbe" => $cacheSignaldaten["begriffe"][$signalstandorteintrag]["webstw_farbe"],
				"zielentfernung" => $cacheSignaldaten["begriffe"][$signalstandorteintrag]["zielentfernung"],
				"zielgeschwindigkeit" => $cacheSignaldaten["begriffe"][$signalstandorteintrag]["zielgeschwindigkeit"],
				"is_zugfahrtbegriff" => $cacheSignaldaten["begriffe"][$signalstandorteintrag]["is_zugfahrtbegriff"],
				"original_begriff_id" => $cacheSignaldaten["begriffe"][$signalstandorteintrag]["original_begriff_id"],
				"signaltyp" => $cacheSignaldaten["standorte"][$signal_id]["signaltyp"],
				"signalstandort_id" => $signal_id);
		}
	} else {
		$signale_ergebnis = $DB->select("SELECT `".DB_TABLE_SIGNALE_BEGRIFFE."`.`id`, `geschwindigkeit`, `begriff`, `webstw_farbe`, `is_zugfahrtbegriff`, `zielentfernung`, `zielgeschwindigkeit`, `original_begriff_id` ".$felder."
FROM `".DB_TABLE_SIGNALE_BEGRIFFE."`
".$join."
WHERE `signal_id` = '".$signal_id."'
");
		$signale = json_decode(json_encode($signale_ergebnis), true);
	}

	$anzahlsignale = count($signale);
	debugMessage("Es gibt ".$anzahlsignale." Signalbegriffe für Signal ".$signal_id.".");

	for ($d = 0; $d < $anzahlsignale; $d++) {
// Wenn eine Original-Begriff-ID gesetzt wird, dann werden die Elemente dieses Begriffs gesucht, da sie nicht zweimal definiert wurden
		if ($signale[$d]["original_begriff_id"] > 0) {
			$begriff_id = $signale[$d]["original_begriff_id"];
		} else {
			$begriff_id = $signale[$d]["id"];
		}

		debugMessage ("Prüfe Signalbegriff ".$begriff_id.".");

// Ermittlung der in Frage kommenden Elemente
// Wenn das global-Array $cacheSignaldaten["elemente"] nicht existiert, müssen sie gesucht werden
		if (isset($cacheSignaldaten["elemente"]) && isset($cacheSignaldaten["elemente"][$begriff_id])) {
			$signalbegriff = $cacheSignaldaten["elemente"][$begriff_id];
		} else {
			$signalbegriff_ergebnis = $DB->select("SELECT `infra_id`, `dir` FROM `".DB_TABLE_SIGNALE_ELEMENTE."` WHERE `signal_id` = '".$begriff_id."'");
			$signalbegriff = json_decode(json_encode($signalbegriff_ergebnis), True);
		}

		$c = 0;
		$route_ok = 0;
		$anzahlbegriffe = count($signalbegriff);
//debugMessage ('Fuer den Signalbegriff ' . $begriff_id . ' sind ' . $anzahlbegriffe . ' Begriffe zu pruefen');

		if ($anzahlbegriffe > 0) {
			$route_ok = 1;
			$einstellungszeit = 0;

			while ($c < $anzahlbegriffe && $route_ok) {
				$signallampen = $DB->select("SELECT `id`, UNIX_TIMESTAMP(`timestamp`) AS `einstellung_timestamp`
FROM `".DB_TABLE_INFRAZUSTAND."`
WHERE `id` = '".$signalbegriff[$c]["infra_id"]."' AND `dir` = '".$signalbegriff[$c]["dir"]."'");

				if (count ($signallampen) == 0) {
					$route_ok = 0;
//echo 'Error';
				} else {
					if ($signallampen[0]->einstellung_timestamp > $einstellungszeit) {
						$einstellungszeit = $signallampen[0]->einstellung_timestamp;
					}
					$route_ok = 1;
//echo 'Ok';
				}
				unset ($signallampen);
				$c++;
			}

			if ($route_ok) {
//debugMessage ('Begriff ist ' . $signale[$d]->begriff .' mit ' . $signale[$d]->geschwindigkeit);

				if (!isset($signale[$d]["signaltyp"])) { $signale[$d]["signaltyp"] = false; }

// Ersetzung der Farben
				switch ($signale[$d]["webstw_farbe"]) {
					DEFAULT:
						{
							$webstw_farbe = $signale[$d]["webstw_farbe"];
							$webstw_farbe_fuss = $webstw_farbe;
							$webstw_farbe_rangiersignal = $webstw_farbe;
						}
						break;

					CASE "gelb":
						{
							$webstw_farbe = "#FFCC00";
							$webstw_farbe_fuss = $webstw_farbe;
							$webstw_farbe_rangiersignal = $webstw_farbe;
						}
						break;

					CASE "gruen":
						{
							$webstw_farbe = "green";
							$webstw_farbe_fuss = $webstw_farbe;
							$webstw_farbe_rangiersignal = $webstw_farbe;
						}
						break;

					CASE "ke":
						{
							$webstw_farbe      = "white";
							$webstw_farbe_fuss = "green";
							$webstw_farbe_rangiersignal = $webstw_farbe;
						}
						break;

					CASE "rot":
					CASE "zs1":
					CASE "zs7":
						{
							$webstw_farbe = "red";
							$webstw_farbe_fuss = "red";
							$webstw_farbe_rangiersignal = $webstw_farbe;
						}
						break;

					CASE "ra12":
					CASE "sh1":
						{
							$webstw_farbe = "red";
							$webstw_farbe_fuss = "red";
							$webstw_farbe_rangiersignal = "white";
						}
						break;
				}
				$signalbegriff_ausgabe[] = array ("id" => $signale[$d]["id"],
					"geschwindigkeit" => $signale[$d]["geschwindigkeit"],
					"zielgeschwindigkeit" => $signale[$d]["zielgeschwindigkeit"],
					"begriff" => $signale[$d]["begriff"],
					"signaltyp" => $signale[$d]["signaltyp"],
					"zielentfernung" => $signale[$d]["zielentfernung"],
					"is_zugfahrtbegriff" => $signale[$d]["is_zugfahrtbegriff"],
					"webstw_farbe" => $webstw_farbe,
					"webstw_farbe_fuss" => $webstw_farbe_fuss,
					"webstw_farbe_rangiersignal" => $webstw_farbe_rangiersignal,
					"einstellung_timestamp" => $einstellungszeit);
			}
		}
		unset ($signalbegriff);
	}
	unset ($DB);

	if (isset($signalbegriff_ausgabe) && count($signalbegriff_ausgabe) > 1) { debugMessage("Zweifelhafter Signalbegriff (".count($signalbegriff_ausgabe).")"); }

	if (empty($signalbegriff_ausgabe)) {
		debugMessage ("Kein Signalbegriff zur Ausgabe gefunden für Signal ".$signal_id.". Setze Dummywerte!");
		$signalbegriff_ausgabe[] = array ("id" => 0,
			"geschwindigkeit" => -9,
			"begriff" => "Hp00",
			"webstw_farbe" => "#303030",
			"webstw_farbe_fuss" => "#303030",
			"is_zugfahrtbegriff" => 0,
			"signaltyp" => false,
			"error" => true,
			"einstellung_timestamp" => 0);
	}
	return $signalbegriff_ausgabe;
}

function debugMessage($message) {
	global $debug;

	if ($debug) {
		if (is_array($message)) {
			echo implode(" # ",$message)."\n";
		} else {
			echo $message."\n";
		}
	}
}








// Not working...
/*
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
*/