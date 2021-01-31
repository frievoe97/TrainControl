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

    if (empty($infra_id)) {
        return false;
    }

    $gbt_id = getGleisabschnitt($infra_id);

    if (!$gbt_id || empty($gbt_id)) {
        return false;
    }
    return getFahrzeugimAbschnitt($gbt_id);
}


function getGleisabschnitt($infra_id) {

    $DB = new DB_MySQL();
    $gbt_id   = $DB->select("SELECT `". DB_TABLE_FMA_GBT."`.`gbt_id`
                                    FROM `".DB_TABLE_FMA_GBT."`
                                    WHERE `".DB_TABLE_FMA_GBT."`.`infra_id` = '".$infra_id."'
                                   ");
    unset($DB);

    return $gbt_id[0]->gbt_id;
}



// ------------------------------------------------



// Ermittelt das in Gegenrichtung relevante Signal, wenn ein Zug wendet
function fzs_getGegensignal ($signal_id) {

    if (!isset($signal_id)) {
        return false;
    }

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



// ------------------------------------------------



function wandeleUhrzeit(int $inputzeit, String $zielart, $options = array()) {

    //TODO getTimeshift
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





// ------------------------------------------------



// Prüfung, ob die Fahrplansession noch aktuell ist, wenn nicht, dann wird das vorerst Skript beendet, damit es von SYSTEMD wieder neugestartet wird
function checkFahrplansession () {
    //TODO
    //return array("grund" => $grund, "u" => $u, "status" => $status, "id" => $id);
}



// ------------------------------------------------



function getFahrzeugdaten (array $fahrzeugdaten, string $abfragetyp) {

    $DB = new DB_MySQL();

    if (!empty($fahrzeugdaten["id"])) {
        $fzgid = (int) $fahrzeugdaten["id"];
    } elseif (!empty($fahrzeugdaten["decoder_adresse"])) {
        $temp = (int) $fahrzeugdaten["decoder_adresse"];
        $fzgid = $DB->select("SELECT `".DB_TABLE_FAHRZEUGE."`.`id`
                                    FROM `".DB_TABLE_FAHRZEUGE."`
                                    WHERE `".DB_TABLE_FAHRZEUGE."`.`adresse` = $temp
                                    ");
        $fzgid = (int) $fzgid[0]->id;

    } elseif (!empty($fahrzeugdaten["gbt_id"])) {
        $temp = (int) $fahrzeugdaten["gbt_id"];
        $fzgid = $DB->select("SELECT `".DB_TABLE_FMA_GBT."`.`gbt_id`, 
                                    `".DB_TABLE_FMA_GBT."`.`fma_id`,
                                    `".DB_TABLE_FAHRZEUGE."`.`id` 
                                    FROM `".DB_TABLE_FMA_GBT."`
                                    LEFT JOIN `".DB_TABLE_FMA."`
                                    ON `".DB_TABLE_FMA_GBT."`.`fma_id` = `".DB_TABLE_FMA."`.`fma_id`
                                    LEFT JOIN `".DB_TABLE_FAHRZEUGE."`
                                    ON `".DB_TABLE_FAHRZEUGE."`.`adresse` = `".DB_TABLE_FMA."`.`decoder_adresse`
                                    WHERE `".DB_TABLE_FMA_GBT."`.`gbt_id` = $temp
                                    AND `".DB_TABLE_FAHRZEUGE."`.`id` IS NOT NULL
                                    ");


        $temp2= [];
        foreach ($fzgid as $item) {
            array_push($temp2, $item->id);
        }

        if (!(count(array_unique($temp2)) === 1 && end($temp2) === $fzgid[0]->id)) {
            return false;
        }

        $fzgid = (int) $fzgid[0]->id;

    } else {
        return false;
    }


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

    $fahrzeugdaten = (array) $fahrzeugdaten[0];

    if (strcmp($abfragetyp, "dir_speed") == 0) {
        $removeKeys = array("verzoegerung", "zuglaenge", "timestamp");
        foreach($removeKeys as $key) {
            unset($fahrzeugdaten[$key]);
        }
        return $fahrzeugdaten;
    }

    if (strcmp($abfragetyp, "id") == 0) {

        $removeKeys = array("adresse", "speed", "dir", "fzs", "verzoegerung", "zuglaenge", "timestamp");
        foreach($removeKeys as $key) {
            unset($fahrzeugdaten[$key]);
        }

        return $fahrzeugdaten;
    }

    if (strcmp($abfragetyp, "dir_zugtyp_speed") == 0) {
        $removeKeys = array("adresse", "verzoegerung", "zuglaenge", "timestamp");
        foreach($removeKeys as $key) {
            unset($fahrzeugdaten[$key]);
        }
        return $fahrzeugdaten;
    }

    if (strcmp($abfragetyp, "decoder_fzs") == 0) {
        return $fahrzeugdaten;
    }

    if (strcmp($abfragetyp, "speed_verzoegerung") == 0) {
        $removeKeys = array("adresse", "zuglaenge", "timestamp");
        foreach($removeKeys as $key) {
            unset($fahrzeugdaten[$key]);
        }
        return $fahrzeugdaten;
    }
}





// Sendet eine Nachricht an ein konkretes Fahrzeug
function sendFahrzeugbefehl (int $fahrzeug_id, int $geschwindigkeit) {

    $DB = new DB_MySQL();
    $DB->select("UPDATE `".DB_TABLE_FAHRZEUGE."`
                        SET `".DB_TABLE_FAHRZEUGE."`.`speed`=$geschwindigkeit
                        WHERE `".DB_TABLE_FAHRZEUGE."`.`id` = $fahrzeug_id
                        ");
    unset($DB);

}



// Ermittelt die Ankunfts- und Abfahrtzeit eines Zuges an einer Betriebsstelle
function getFahrplanzeiten (string $betriebsstelle, int $zug_id, array $options = array ("id" => "zug_id", "art" => "wendepruefung")) {

    if(!isset($betriebsstelle, $zug_id)) {
        return false;
    }

    $DB = new DB_MySQL();
    $fahrplandaten_temp = $DB->select("SELECT `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`ankunft_soll`,
                        `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`abfahrt_soll`,
                        `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`fahrtrichtung`,
                        `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`ist_durchfahrt`
                        FROM `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`
                        WHERE `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`betriebsstelle` = '".$betriebsstelle."'
                        AND `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`zug_id` = $zug_id
                        ");
    unset($DB);

    if(!isset($fahrplandaten_temp[0]->abfahrt_soll, $fahrplandaten_temp[0]->ankunft_soll, $fahrplandaten_temp[0]->fahrtrichtung, $fahrplandaten_temp[0]->ist_durchfahrt)) {
        return false;
    }

    $fahrplandaten = [
        "abfahrt_soll" => $fahrplandaten_temp[0]->abfahrt_soll,
        "ankunft_soll" => $fahrplandaten_temp[0]->ankunft_soll,
        "fahrtrichtung" => $fahrplandaten_temp[0]->fahrtrichtung,
        "ist_durchfahrt" => $fahrplandaten_temp[0]->ist_durchfahrt,
    ];

    return $fahrplandaten /*(array: abfahrt_soll, ankunft_soll, fahrtrichtung, ist_durchfahrt, uebergang_fahrtrichtung)*/;

}



// ------------------------------------------------



// Ermittelt den Signalbegriff für die Fahrzeugsteuerung
function fzs_getSignalbegriff(array $abschnittdaten) {

    $id = (int) $abschnittdaten["signalstandortid"];

    $DB = new DB_MySQL();
    $signalbegriff_temp = $DB->select("SELECT `".DB_TABLE_SIGNALE_BEGRIFFE."`.`geschwindigkeit`
                                            FROM `".DB_TABLE_SIGNALE_BEGRIFFE."`
                                            WHERE `".DB_TABLE_SIGNALE_BEGRIFFE."`.`id` = $id
                                            ");
    unset($DB);


    // $signalbegriff = $abschnittdaten->


    // $abschnittdaten kommen aus der vorbelegung.php, relevant hier "haltfall_id" und als Pflichtfeld "signalstandortid".
    return $signalbegriff_temp[0]->geschwindigkeit; // darin [0]["geschwindigkeit"] relevant;
}



// ------------------------------------------------





function getSpeedPerTime(float $v_0, float $v_1, float $t_reac, float $a, int $timeInter) {

}

function getCurrentPosition(float $v_0, int $time_0, int $time_1, float $pos_0) {
    $time_diff = $time_1 - $time_0;
    return ($v_0)*$time_diff+$pos_0;
}

?>
