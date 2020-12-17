<?php

// Ermittelt, welcher Fahrzeugdecoder in einem GBT-Feld steht
function getFahrzeugimAbschnitt ($gbt_id) {
 if (empty($gbt_id)) { return false; }

 $DB = new DB_MySQL();
 $fahrzeug   = $DB->select("SELECT `".DB_TABLE_FAHRZEUGE."`.`id`
                                    FROM `".DB_TABLE_ZN_GBT."`
                                    LEFT JOIN `".DB_TABLE_FMA_GBT."`
                                     ON (`".DB_TABLE_ZN_GBT."`.`id` = `".DB_TABLE_FMA_GBT."`.`gbt_id`)
                                    LEFT JOIN `".DB_TABLE_FMA."`
                                     ON (`".DB_TABLE_FMA_GBT."`.`fma_id` = `".DB_TABLE_FMA."`.`fma_id`)
                                    LEFT JOIN `".DB_TABLE_FAHRZEUGE."`
                                     ON (`".DB_TABLE_FMA."`.`decoder_adresse` = `".DB_TABLE_FAHRZEUGE."`.`adresse`)
                                    WHERE `".DB_TABLE_ZN_GBT."`.`id` = '".$gbt_id."'
                                     AND `".DB_TABLE_FMA."`.`decoder_adresse` > 0
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
function getFahrzeugimInfraAbschnitt ($infra_id) {
 if (empty($infra_id)) { return false; }
 $gbt_id = getGleisabschnitt($infra_id);
 if (!$gbt_id || empty($gbt_id->id)) { return false;    }
 $fahrzeug = getFahrzeugimAbschnitt($gbt_id->id);
}
// ------------------------------------------------
// Ermittelt das in Gegenrichtung relevante Signal, wenn ein Zug wendet
function fzs_getGegensignal ($signal_id) {
 if (!isset($signal_id)) { return false; }
 $DB = new DB_MySQL();
 $gegensignal = $DB->select("SELECT `".DB_TABLE_SIGNALE_STANDORTE."`.`id`, `".DB_TABLE_SIGNALE_STANDORTE."`.`freimelde_id`
                             FROM `".DB_TABLE_SIGNALE_STANDORTE."`
                             LEFT JOIN `".DB_TABLE_SIGNALE_WENDEN."`
                              ON (`".DB_TABLE_SIGNALE_STANDORTE."`.`id` = `".DB_TABLE_SIGNALE_WENDEN."` .`gegensignal_id`)
                             WHERE `".DB_TABLE_SIGNALE_WENDEN."`.`signal_id` = '".$signal_id."'
                           ");
 unset($DB);

 if (count($gegensignal) == 0) {
  return false;
 } else {
  return $gegensignal[0];
 }
}

function wandeleUhrzeit(int $inputzeit, String $zielart, $options = array()) {

    $exampleTimeshift = -42709560;

    if (strcmp($zielart, 'simulationszeit') == 0) {
       return $inputzeit + $exampleTimeshift;
    }

    if (strcmp($zielart, 'realzeit') == 0) {
       return $inputzeit - $exampleTimeshift;
    }

    if ($zielart != 'realzeit' || $zielart != 'simulationszeit') {
       return false;
    }
}

function getFahrzeugdaten (array $fahrzeugdaten, string $abfragetyp) {

    $DB = new DB_MySQL();
    $fzgid = 1;

    $fahrzeugdaten   = $DB->select("SELECT `".DB_TABLE_FAHRZEUGE."`.`id`,
                                    `".DB_TABLE_FAHRZEUGE."`.`adresse`,
                                    `".DB_TABLE_FAHRZEUGE."`.`speed`,
                                    `".DB_TABLE_FAHRZEUGE."`.`dir`,
                                    `".DB_TABLE_FAHRZEUGE."`.`fzs`,
                                    `".DB_TABLE_FAHRZEUGE."`.`zugtyp`,
                                    `".DB_TABLE_FAHRZEUGE."`.`verzoegerung`,
                                    `".DB_TABLE_FAHRZEUGE."`.`zuglaenge`,
                                    `".DB_TABLE_FAHRZEUGE."`.`timestamp`
                                    FROM `".DB_TABLE_FAHRZEUGE."`
                                    WHERE `".DB_TABLE_FAHRZEUGE."`.`id` = $fzgid
                                    ");
    unset($DB);

    if (strcmp($abfragetyp, dir_speed) == 0) {
        $removeKeys = array(6, 7, 8);
        foreach($removeKeys as $key) {
            unset($fahrzeugdaten[$key]);
        }
        return $fahrzeugdaten;
        // dir_speed: `id`, `adresse`, `speed`, `dir`, `fzs`, `zugtyp`
    }

    if (strcmp($abfragetyp, id) == 0) {
        $removeKeys = array(1, 2, 3, 4, 6, 7, 8);
        foreach($removeKeys as $key) {
            unset($fahrzeugdaten[$key]);
        }
        return $fahrzeugdaten;
        // id: id, zugtyp
    }

    if (strcmp($abfragetyp, dir_zugtyp_speed) == 0) {
        $removeKeys = array(1, 6, 7, 8);
        foreach($removeKeys as $key) {
            unset($fahrzeugdaten[$key]);
        }
        return $fahrzeugdaten;
        // dir_zugtyp_speed: `id`, `speed`,`zugtyp`, `dir`, `fzs`
    }

    if (strcmp($abfragetyp, decoder_fzs) == 0) {
        return $fahrzeugdaten;
        // decoder_fzs: `id`, `adresse`, `speed`,`verzoegerung`, `dir`, `zuglaenge`, `zugtyp`, `fzs`, UNIX_TIMESTAMP(`timestamp`) AS `timestamp`
    }

    if (strcmp($abfragetyp, speed_verzoegerung) == 0) {
        $removeKeys = array(1, 7, 8);
        foreach($removeKeys as $key) {
            unset($fahrzeugdaten[$key]);
        }
        return $fahrzeugdaten;
        // speed_verzoegerung: `id`, `speed`,`verzoegerung`, `dir`, `fzs`, `zugtyp`
    }
}


/*


// Sendet eine Nachricht an ein konkretes Fahrzeug
function sendFahrzeugbefehl (int $fahrzeug_id, int $geschwindigkeit) {

}

// Ermittelt die Ankunfts- und Abfahrtzeit eines Zuges an einer Betriebsstelle
function getFahrplanzeiten (string $betriebsstelle, int $zug_id, array $options = array ("id" => "zug_id", "art" => "wendepruefung") {

    return $fahrplandaten (array: abfahrt_soll, ankunft_soll, fahrtrichtung, ist_durchfahrt, uebergang_fahrtrichtung);

}

*/

// Ermittelt den Signalbegriff für die Fahrzeugsteuerung
function fzs_getSignalbegriff(array $abschnittdaten) {
    // $abschnittdaten kommen aus der vorbelegung.php, relevant hier "haltfall_id" und als Pflichtfeld "signalstandortid".
    return $signalbegriff; // darin [0]["geschwindigkeit"] relevant;
}
































?>
