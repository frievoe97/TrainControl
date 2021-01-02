<?php


// Ermittelt, welcher Fahrzeugdecoder in einem GBT-Feld steht

function getFahrzeugimAbschnitt($gbt_id)
{
    if (empty($gbt_id)) {
        return false;
    }

    $DB = new DB_MySQL();
    $fahrzeug = $DB->select("SELECT `" . DB_TABLE_FAHRZEUGE . "`.`id`
                                    FROM `" . DB_TABLE_ZN_GBT . "`
                                    LEFT JOIN `" . DB_TABLE_FMA_GBT . "`
                                     ON (`" . DB_TABLE_ZN_GBT . "`.`id` = `" . DB_TABLE_FMA_GBT . "`.`gbt_id`)
                                    LEFT JOIN `" . DB_TABLE_FMA . "`
                                     ON (`" . DB_TABLE_FMA_GBT . "`.`fma_id` = `" . DB_TABLE_FMA . "`.`fma_id`)
                                    LEFT JOIN `" . DB_TABLE_FAHRZEUGE . "`
                                     ON (`" . DB_TABLE_FMA . "`.`decoder_adresse` = `" . DB_TABLE_FAHRZEUGE . "`.`adresse`)
                                    WHERE `" . DB_TABLE_ZN_GBT . "`.`id` = '" . $gbt_id . "'
                                     AND `" . DB_TABLE_FMA . "`.`decoder_adresse` > 0
                                   ");
    unset($DB);

    if (count($fahrzeug) == 0) {
        return false;
    } else {
        return $fahrzeug[0]->id;
    }
}

// ------------------------------------------------------------------------------------------------
// Ermittelt, welcher Fahrzeugdecoder in einem Infra-Feld steht
function getFahrzeugimInfraAbschnitt($infra_id)
{
    if (empty($infra_id)) {
        return false;
    }
    $gbt_id = getGleisabschnitt($infra_id);
    if (!$gbt_id || empty($gbt_id->id)) {
        return false;
    }
    $fahrzeug = getFahrzeugimAbschnitt($gbt_id->id);
}

// ------------------------------------------------
// Ermittelt das in Gegenrichtung relevante Signal, wenn ein Zug wendet
function fzs_getGegensignal($signal_id)
{
    if (!isset($signal_id)) {
        return false;
    }
    $DB = new DB_MySQL();
    $gegensignal = $DB->select("SELECT `" . DB_TABLE_SIGNALE_STANDORTE . "`.`id`, `" . DB_TABLE_SIGNALE_STANDORTE . "`.`freimelde_id`
                             FROM `" . DB_TABLE_SIGNALE_STANDORTE . "`
                             LEFT JOIN `" . DB_TABLE_SIGNALE_WENDEN . "`
                              ON (`" . DB_TABLE_SIGNALE_STANDORTE . "`.`id` = `" . DB_TABLE_SIGNALE_WENDEN . "` .`gegensignal_id`)
                             WHERE `" . DB_TABLE_SIGNALE_WENDEN . "`.`signal_id` = '" . $signal_id . "'
                           ");
    unset($DB);

    if (count($gegensignal) == 0) {
        return false;
    } else {
        return $gegensignal[0];
    }
}




// Ermittelt den Signalbegriff für die Fahrzeugsteuerung
function fzs_getSignalbegriff(array $abschnittdaten) {
 // $abschnittdaten kommen aus der vorbelegung.php, relevant hier "haltfall_id" und als Pflichtfeld "signalstandortid".
return $signalbegriff; // darin [0]["geschwindigkeit"] relevant;
}



// Ermittelt die Ankunfts- und Abfahrtzeit eines Zuges an einer Betriebsstelle
function getFahrplanzeiten (string $betriebsstelle, int $zug_id, array $options = array ("id" => "zug_id", "art" => "wendepruefung", ) {

 return $fahrplandaten (array: abfahrt_soll, ankunft_soll, fahrtrichtung, ist_durchfahrt, uebergang_fahrtrichtung)
}

// Sendet eine Nachricht an ein konkretes Fahrzeug
function sendFahrzeugbefehl (int $fahrzeug_id, int $geschwindigkeit) { }

// Ermittelt aktuelle Daten eines konkreten Fahrzeugs
function getFahrzeugdaten (array $fahrzeugdaten, string $abfragetyp) {
 // Zu übergeben ist im Array $fahrzeugdaten einer der drei Filter
 // gbt_id, decoder_adresse, id (= fahrzeug_id)

 // Abfragetypen sind: (hinter dem Doppelpunkt die Felder, die zurückgegeben werden
 // dir_speed: `id`, `adresse`, `speed`, `dir`, `fzs`, `zugtyp` 
 // id: id, zugtyp
 // dir_zugtyp_speed: `id`, `speed`,`zugtyp`, `dir`, `fzs`
 // decoder_fzs: `id`, `adresse`, `speed`,`verzoegerung`, `dir`, `zuglaenge`, `zugtyp`, `fzs`, UNIX_TIMESTAMP(`timestamp`) AS `timestamp` 
 // speed_verzoegerung: `id`, `speed`,`verzoegerung`, `dir`, `fzs`, `zugtyp`

 return $fahrzeugdaten;
}


// Umrechnung von Zeiten zwischen Real- und Simulationszeit  (als Timestamp!)
function wandeleUhrzeit(int $inputzeit, String $zielart, $options = array()) {

    $exampleTimeshift = -42709560;

    if (strcmp($zielart, 'simulationszeit') != 0) {
        return $inputzeit + $exampleTimeshift;
    }

    if (strcmp($zielart, 'realzeit') != 0) {
        return $inputzeit - $exampleTimeshift;
    }

    if ($zielart != 'realzeit' || $zielart != 'simulationszeit') {
        return false;
    }
}


// Prüfung, ob die Fahrplansession noch aktuell ist, wenn nicht, dann wird das vorerst Skript beendet, damit es von SYSTEMD wieder neugestartet wird
function checkFahrplansession () {

    if (empty($gbt_id)) {
        return false;
    }





 return array("grund" => $grund, "u" => $u, "status" => $status, "id" => $id);
}


// Liefert Ausgaben im Debug-Modus
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
	
?>