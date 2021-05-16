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

    $fahrplandaten = array();

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

    /*
    if(!isset($fahrplandaten_temp[0]->abfahrt_soll, $fahrplandaten_temp[0]->ankunft_soll, $fahrplandaten_temp[0]->fahrtrichtung, $fahrplandaten_temp[0]->ist_durchfahrt)) {
        return false;
    }
    */

    if(!isset($fahrplandaten_temp[0]->abfahrt_soll) && !isset($fahrplandaten_temp[0]->ankunft_soll) && !isset($fahrplandaten_temp[0]->fahrtrichtung) && !isset($fahrplandaten_temp[0]->ist_durchfahrt)) {
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

function getPosition(int $adresse) {

    $returnPosition = array();

    $DB = new DB_MySQL();
    $position = $DB->select("SELECT `".DB_TABLE_FMA."`.`fma_id`
                                            FROM `".DB_TABLE_FMA."`
                                            WHERE `".DB_TABLE_FMA."`.`decoder_adresse` = $adresse
                                            ");
    unset($DB);

    if (sizeof($position) != 0) {
        for ($i = 0; $i < sizeof($position); $i++) {
            array_push($returnPosition, intval(get_object_vars($position[$i])["fma_id"]));
        }

    }



    return $returnPosition;

}

function changeDirection (int $id) {

    // TODO: Add 60 sec sleeptime

    global $allTrains;
    global $cacheInfraLaenge;

    $section = $allTrains[$id]["current_infra_section"];
    $position = $allTrains[$id]["current_position"];
    $direction = $allTrains[$id]["dir"];
    $length = $allTrains[$id]["zuglaenge"];
    $newTrainLength = $length + ($cacheInfraLaenge[$section] - $position);

    $newDirection = null;
    $newSection = null;
    $cumLength = 0;

    if ($direction == 0) {
        $newDirection = 1;
    } else {
        $newDirection = 0;
    }

    $newPosition = null;
    $nextSections = getNaechsteAbschnitte($section, $newDirection);
    $currentData = array(0 => array("laenge" => $cacheInfraLaenge[$section], "infra_id" => $section));
    $mergedData = array_merge($currentData, $nextSections);

    foreach ($mergedData as $sectionValue) {
        $cumLength += $sectionValue["laenge"];
        if ($newTrainLength <= $cumLength) {
            $newSection = $sectionValue["infra_id"];
            $newPosition = $cacheInfraLaenge[$newSection] - ($cumLength - $newTrainLength);
            break;
        }
    }

    if ($newPosition == null) {
        echo "Die Richtung des Zugs mit der ID ", $id, " lässt sich nicht ändern, weil das Zugende auf einem auf Halt stehenden Signal steht.\n";
        echo "Die Zuglänge beträgt:\t", $length, " m\tDie Distanz zwischen Zugende und dem auf Halt stehenden Signal beträgt:\t", ($cumLength - ($cacheInfraLaenge[$section] - $position)), " m\n\n";
        array_push($allTrains[$id]["error"], 0);
        $allTrains[$id]["can_drive"] = false;
    }  else {
        echo "Die Richtung des Zugs mit der ID: ", $id, " wurde geändert.\n";
        $allTrains[$id]["current_infra_section"] = $newSection;
        $allTrains[$id]["current_position"] = $newPosition;
        $allTrains[$id]["dir"] = $newDirection;
        $allTrains[$id]["can_drive"] = true;
    }
}

function createcacheInfraLaenge() {
    $DB = new DB_MySQL();
    $returnArray = array();

    $infralaenge = $DB->select("SELECT `".DB_TABLE_INFRAZUSTAND."`.`id`,
                                `".DB_TABLE_INFRAZUSTAND."`.`laenge`
                                FROM `".DB_TABLE_INFRAZUSTAND."`
                                WHERE `".DB_TABLE_INFRAZUSTAND."`.`type` = '"."gleis"."'
                                ");
    unset($DB);

    foreach ($infralaenge as $data) {
        if ($data->laenge != null) {
            $returnArray[$data->id] = intval($data->laenge);
        }
    }
    return $returnArray;
}

function consoleAllTrainsPositionAndFahrplan() {
    global $allTrains;

    echo "Alle vorhandenen Züge:\n\n";
    foreach ($allTrains as $train) {
        $fahrplan = null;
        if ($train["operates_on_timetable"] == 1) {
            $fahrplan = "ja";
        } else {
            $fahrplan = "nein";
        }
        echo "Zug ID: ", $train["id"], " (Adresse: ", $train["adresse"], ")\t Fährt nach Fahrplan: ", $fahrplan, "\t Richtung: ", $train["dir"], "\t Infra-Abschnitt: ", $train["current_infra_section"], "\t Aktuelle relative Position im Infra-Abschnitt: ", $train["current_position"], "m\n";
    }
    echo "\n";
}

function consoleCheckIfStartDirectionIsCorrect() {
    global $allTrains;

    echo "Für den Fall, dass die Fahrtrichtung der Züge nicht mit dem Fahrplan übereinstimmt, wird die Richtung verändert:\n\n";
    foreach ($allTrains as $train) {
        if ($train["operates_on_timetable"] == 1) {
            if ($train["dir"] != $train["next_betriebsstellen_data"][0]["zeiten"]["fahrtrichtung"][1]) {
                changeDirection($train["id"]);
            } else {
                $allTrains[$train["id"]]["can_drive"] = true;
            }
        } else {
            $allTrains[$train["id"]]["can_drive"] = true;
        }
    }
    echo "\n";
}

function createCacheHaltepunkte() : array{

    $DB = new DB_MySQL();
    $returnArray = array();

    $betriebsstellen = $DB->select("SELECT `".DB_TABLE_BETRIEBSSTELLEN_DATEN."`.`parent_kuerzel`
                                FROM `".DB_TABLE_BETRIEBSSTELLEN_DATEN."`
                                WHERE `".DB_TABLE_BETRIEBSSTELLEN_DATEN."`.`parent_kuerzel` IS NOT NULL
                                ");
    unset($DB);

    foreach ($betriebsstellen as $betriebsstelle) {
        $returnArray[$betriebsstelle->parent_kuerzel][0] = array();
        $returnArray[$betriebsstelle->parent_kuerzel][1] = array();
    }

    foreach ($returnArray as $betriebsstelleKey => $betriebsstelleValue) {
        $DB = new DB_MySQL();
        $name = $betriebsstelleKey;
        $name .= "%";
        $asig = "ASig";
        $ja = "ja";

        if ($betriebsstelleKey == 'XAB' || $betriebsstelleKey == "XBL") {

            $haltepunkte = $DB->select("SELECT `".DB_TABLE_SIGNALE_STANDORTE."`.`freimelde_id`, 
                                `".DB_TABLE_SIGNALE_STANDORTE."`.`wirkrichtung`
                                FROM `".DB_TABLE_SIGNALE_STANDORTE."`
                                WHERE `".DB_TABLE_SIGNALE_STANDORTE."`.`betriebsstelle` LIKE '$name'
                                AND `".DB_TABLE_SIGNALE_STANDORTE."`.`freimelde_id` IS NOT NULL
                                AND `".DB_TABLE_SIGNALE_STANDORTE."`.`fahrplanhalt` = '$ja'
                                ");
            unset($DB);

        } else {

            $haltepunkte = $DB->select("SELECT `" . DB_TABLE_SIGNALE_STANDORTE . "`.`freimelde_id`, 
                                `" . DB_TABLE_SIGNALE_STANDORTE . "`.`wirkrichtung`
                                FROM `" . DB_TABLE_SIGNALE_STANDORTE . "`
                                WHERE `" . DB_TABLE_SIGNALE_STANDORTE . "`.`betriebsstelle` LIKE '$name'
                                AND `" . DB_TABLE_SIGNALE_STANDORTE . "`.`freimelde_id` IS NOT NULL
                                AND `" . DB_TABLE_SIGNALE_STANDORTE . "`.`signaltyp` = '$asig'
                                ");
            unset($DB);
        }

        foreach ($haltepunkte as $haltepunkt) {
            if ($haltepunkt->wirkrichtung == 0) {
                array_push($returnArray[$betriebsstelleKey][0], intval($haltepunkt->freimelde_id));
            } elseif ($haltepunkt->wirkrichtung == 1) {
                array_push($returnArray[$betriebsstelleKey][1], intval($haltepunkt->freimelde_id));
            }
        }
    }
    $returnArray["XSC"][1] = array(734, 732, 735, 733, 692); // In der Datenbank ist für Richtung 1 für diese Abschnitte fahrplanhalt auf nein eingestellt
    //var_dump($returnArray);
    //sleep(10);
    return $returnArray;
}

function addStopsectionsForTimetable() {

    global $allTrains;
    global $cacheHaltepunkte;

    foreach ($allTrains as $trainIndex => $trainValue) {
        if ($trainValue["can_drive"]) {
            if ($trainValue["operates_on_timetable"] != null) {
                foreach ($trainValue["next_betriebsstellen_data"] as $betriebsstelleKey => $betriebsstelleValue) {
                    if (in_array($betriebsstelleValue["betriebstelle"], array_keys($cacheHaltepunkte))) {
                        $allTrains[$trainIndex]["next_betriebsstellen_data"][$betriebsstelleKey]["haltepunkte"] = $cacheHaltepunkte[$betriebsstelleValue["betriebstelle"]][$trainValue["dir"]];
                    }
                }
            }
        }
    }
}

function addNextStopForAllTrains() {

    global $allTrains;
    // TODO: Wenn schon next stops vorhanden sind, müssen die entferrnt werden!

    foreach ($allTrains as $trainIndex => $trainValue) {
        if ($trainValue["operates_on_timetable"] != null) {
            $index = 0;
            foreach ($trainValue["next_betriebsstellen_data"] as $betriebsstellenIndex => $betriebsstellenData) {
                $allTrains[$trainIndex]["next_stop"][$index]["betriebstelle"] = $betriebsstellenData["betriebstelle"];
                $allTrains[$trainIndex]["next_stop"][$index]["ankunft"] = $betriebsstellenData["zeiten"]["ankunft_soll_timestamp"];
                $allTrains[$trainIndex]["next_stop"][$index]["abfahrt"] = $betriebsstellenData["zeiten"]["abfahrt_soll_timestamp"];
                $allTrains[$trainIndex]["next_stop"][$index]["haltzeit"] = $betriebsstellenData["zeiten"]["haltezeit"];
                $allTrains[$trainIndex]["next_stop"][$index]["infra_sections"] = $betriebsstellenData["haltepunkte"];
                $allTrains[$trainIndex]["next_stop"][$index]["is_on_fahrstrasse"] = null;
                if ($betriebsstellenData["zeiten"]["ist_durchfahrt"] == 0) {
                    break;
                }
                $index++;
            }
        }
    }
}

function initalFirstLiveData() {

    global $allTrains;
    global $allTimes;
    global $databaseTime;

    foreach ($allTrains as $trainIndex => $trainValue) {
        $allTimes[$trainValue["adresse"]][0]["live_position"] = $trainValue["current_position"];
        $allTimes[$trainValue["adresse"]][0]["live_speed"] = $trainValue["speed"];
        $allTimes[$trainValue["adresse"]][0]["live_time"] = $databaseTime;
        $allTimes[$trainValue["adresse"]][0]["live_relative_position"] = $trainValue["current_position"];
        $allTimes[$trainValue["adresse"]][0]["live_section"] = $trainValue["current_infra_section"];
        $allTimes[$trainValue["adresse"]][0]["live_is_speed_change"] = false;
        $allTimes[$trainValue["adresse"]][0]["live_target_reached"] = false;
    }
}

function calculateFahrverlauf() {
    global $allTrains;
    global $allTimes;
    global $cacheInfraLaenge;

    foreach ($allTrains as $trainIndex => $trainValue) {
        if ($trainValue["operates_on_timetable"] == 1) {
            $firstSection = $trainValue["current_infra_section"];
            $secondSection = null;
            $startTime = end($allTimes[$trainValue["adresse"]])["live_time"];
            $endTime = null;
            if ($trainValue["fahrstrasse_is_correct"]) {
                for($i = 0; $i < sizeof($trainValue["next_stop"]); $i++) {
                    //var_dump($trainValue["next_stop"][$i]);
                    $secondSection = $trainValue["next_stop"][$i]["infra_section"];
                    if ($trainValue["next_stop"][$i]["ankunft"] == null) {
                        $endTime = $startTime;
                    } else {
                        $endTime = $trainValue["next_stop"][$i]["ankunft"];
                    }
                    $targetSection = $trainValue["next_stop"][$i]["infra_section"];
                    $targetPosition = $cacheInfraLaenge[$trainValue["next_stop"][$i]["infra_section"]];
                    $targetSpeed = 0;
                    $reachedBetriebsstele = false;
                    if ($i + 1 == sizeof($trainValue["next_stop"])) {
                        $targetSpeed = 0;
                        $reachedBetriebsstele = true;
                    } elseif ($trainValue["next_stop"][$i]["haltzeit"] != 0) {
                        $targetSpeed = 0;
                        $reachedBetriebsstele = true;
                    } else {
                        $section = $trainValue["next_stop"][$i]["infra_section"];
                        $index = array_search($section, $trainValue["next_sections"]);
                        $targetSpeed = $trainValue["next_v_max"][$index];
                    }
                    updateNextSpeed($trainValue, $startTime, $endTime, $targetSection, $targetSpeed, $targetPosition, $reachedBetriebsstele);
                }
            }
        } else {

        }
    }
}

function checkIfFahrstrasseIsCorrrect() {

    global $allTrains;
    // 1 = ja

    $aa = array(1,2,3,4);
    $bb = array(4,5,6,7);

    foreach ($allTrains as $trainIndex => $trainValue) {
        if ($trainValue["can_drive"]) {
            if ($trainValue["operates_on_timetable"] == 1) {
                foreach ($trainValue["next_stop"] as $stopIndex => $stopValue) {
                    $allTrains[$trainIndex]["next_stop"][$stopIndex]["is_on_fahrstrasse"] = false;
                    $indexSection = 0;
                    for ($i = 0; $i < sizeof($trainValue["next_sections"]); $i++) {
                        if (in_array($trainValue["next_sections"][$i], $stopValue["infra_sections"])) {
                            if ($i >= $indexSection) {
                                $allTrains[$trainIndex]["next_stop"][$stopIndex]["is_on_fahrstrasse"] = true;
                                $allTrains[$trainIndex]["next_stop"][$stopIndex]["infra_section"] = $trainValue["next_sections"][$i];
                                $allTrains[$trainIndex]["fahrstrasse_is_correct"] = 1;
                                $i = sizeof($trainValue["next_sections"]);
                                $indexSection = $i;
                            }
                        }
                    }
                }
            }
        }
    }
}

function showErrors() {

    global $allTrains;
    global $trainErrors;

    echo "Hier werden für alle Züge mögliche Fehler angezeigt:\n\n";

    foreach ($allTrains as $trainIndex => $trainValue) {
        if (sizeof($trainValue["error"]) != 0) {
            echo "Zug ID: ", $trainValue["id"], "\n";
            $index = 1;
            foreach ($trainValue["error"] as $error) {
                echo "\t", $index, ". Fehler:\t", $trainErrors[$error], "\n";
            }
        }
    }

}

function createFmaToInfraData() {

    $DB = new DB_MySQL();
    $returnArray = array();

    $fmaToInfra = $DB->select("SELECT `".DB_TABLE_FMA_GBT."`.`infra_id`,
                                `".DB_TABLE_FMA_GBT."`.`fma_id`
                                FROM `".DB_TABLE_FMA_GBT."`
                                WHERE `".DB_TABLE_FMA_GBT."`.`fma_id` IS NOT NULL
                                ");
    unset($DB);

    //var_dump( $fmaToInfra);

    foreach ($fmaToInfra as $value) {
        $returnArray[intval($value->fma_id)] = intval($value->infra_id);
    }

    return $returnArray;
}

function getFahruegId(int $adresse) {

    $DB = new DB_MySQL();
    $id = $DB->select("SELECT `".DB_TABLE_FAHRZEUGE."`.`id`
                                FROM `".DB_TABLE_FAHRZEUGE."`
                                WHERE `".DB_TABLE_FAHRZEUGE."`.`adresse` = $adresse
                                ");
    unset($DB);
    return intval($id[0]->id);
}

function convertSignalIdToBetriebsstelle (int $signalId) : string {
    $DB = new DB_MySQL();
    $betriebsstellen = get_object_vars($DB->select("SELECT `".DB_TABLE_SIGNALE_STANDORTE."`.`betriebsstelle`
                                FROM `".DB_TABLE_SIGNALE_STANDORTE."`
                                WHERE `".DB_TABLE_SIGNALE_STANDORTE."`.`id` = $signalId
                                ")[0]);
    unset($DB);
    return $betriebsstellen["betriebsstelle"];
}





function getFrontPosition(array $infra, int $dir) : int {

    foreach ($infra as $section) {
        $nextSections = array();
        $test = getNaechsteAbschnitte($section, $dir);

        foreach ($test as $value) {
            array_push($nextSections, $value["infra_id"]);
        }

        if (sizeof(array_intersect($infra, $nextSections)) == 0) {
            return $section;
        }
    }
    return false;
}

function in_array_any($needles, $haystack) {
    return !empty(array_intersect($needles, $haystack));
}

function convertFmaToInfra (array $fma) {

    $returnFma = array();
    $DB = new DB_MySQL();

    foreach ($fma as $fma_section) {
        $infra = $DB->select("SELECT `".DB_TABLE_FMA_GBT."`.`infra_id`
                                            FROM `".DB_TABLE_FMA_GBT."`
                                            WHERE `".DB_TABLE_FMA_GBT."`.`fma_id` = $fma_section
                                            ");
        unset($DB);
        array_push($returnFma, intval($infra[0]->infra_id));
    }
    return $returnFma;
}




// ------------------------------------------------------------------------------------
// Funktion zur Ermittlung von Daten eines Zuges
function getZugdaten ($zugnummer, $options = array()) {
    // Vorbelegungen

    if (!isset($options["betriebsstelle"])) $options["betriebsstelle"] = "";
    if (!isset($options["betriebsstelle_aktuell"])) $options["betriebsstelle_aktuell"] = "";
    if (!isset($options["zeitformat"]))     $options["zeitformat"] = "hh:mm:ss";
    if (!isset($options["betriebsstellenfilter"])) { $options["betriebsstellenfilter"] = array(); }
    if (!isset($options["errorhandling"]))  { $options["errorhandling"] = "hide"; }
    if (!isset($options["action"]))         { $options["action"] = ""; }
    if (!isset($options["sortierzeit"]))    { $options["sortierzeit"] = ""; }
    if (!isset($options["rueckgabestyle"])) { $options["rueckgabestyle"] = ""; }

    $where_zusatz = "";
    $where_version = "";
    $where_zusatzarray = array();
    $feld_zusatzarray = array();
    $istfelder = "";
    $leftjoin_betriebsstellendaten = "";
    $leftjoin_uebergangsdaten = "";
    $limit = "";
    $sortierzeit_order = "ASC";

    switch ($options["id"])
    {
        default:
        case "zugnummer":
            {
                $idfeld = "zugnummer";
            }
            break;

        case "zug_id":
            {
                $idfeld = "id";
            }
            break;
    }

    $feld_zusatzarray[] = "`".$options["zugquelle"]."`.`vmax_ist`";
    $feld_zusatzarray[] = "`".$options["zugquelle"]."`.`triebfahrzeug_ist`";
    $feld_zusatzarray[] = "`".$options["fzmquelle"]."`.`abfahrt_soll`";
    $feld_zusatzarray[] = "`".$options["fzmquelle"]."`.`gleis_soll` ";

    $felder_anab = getDBFieldsAnAb ($options["fzmquelle"], array("zeitformat" => $options["zeitformat"]));

    if (!empty($options["betriebsstellenfilter"]) && count($options["betriebsstellenfilter"]) > 0) {
        $where_zusatzarray[] = "`".DB_TABLE_BETRIEBSSTELLEN_DATEN."`.`art` IN ('".implode("','",$options["betriebsstellenfilter"])."')";
        $leftjoin_betriebsstellendaten = "LEFT JOIN `".DB_TABLE_BETRIEBSSTELLEN_DATEN."` ON (".$options["fzmquelle"].".`betriebsstelle` = `".DB_TABLE_BETRIEBSSTELLEN_DATEN."`.`kuerzel`)";
    }

    if (isset($options["zuggattung"]) && !empty($options["zuggattung"])) {
        $where_zuggattung = "AND `".$options["zugquelle"]."`.`zuggattung` = '".$options["zuggattung"]."' ";
    } else {
        $where_zuggattung = "";
    }

    if (empty($zugnummer)) {
        //print ("Keine Zugnummer angegeben!");
        return false;
    } else {
        $DB = new DB_MySQL();

        // Ist eine Sortierzeit übergeben, wird diese verwendet
        if (!empty($options["sortierzeit"])) {
            $where_zusatz_vorher = "(`sortierzeit` < '".$options["sortierzeit"]."')";
            $where_zusatz_nachher = "(`sortierzeit` >= '".$options["sortierzeit"]."')";
        }

        // Ist eine aktuelle Betriebsstelle angegeben, wird zunächst die relevante Zeile im Zuglauf ermittelt, ab der der Zuglauf dann ausgegeben wird (wird ignoriert, wenn Sortierzeit übergeben wird!)
        if (!empty($options["betriebsstelle_aktuell"]) && empty($options["sortierzeit"])) {
            $aktbetriebsstelle = $DB->select("SELECT `sortierzeit`, `".$options["fzmquelle"]."`.`id` FROM `".$options["fzmquelle"]."`
                                    LEFT JOIN `".$options["zugquelle"]."`
                                     ON (`".$options["fzmquelle"]."`.`zug_id` = `".$options["zugquelle"]."`.`id`)
                                    WHERE `".$options["zugquelle"]."`.`".$idfeld."` = '".$zugnummer."' AND
                                          `betriebsstelle` = '".$options["betriebsstelle_aktuell"]."' 
                                          ".$where_version." ".$where_zuggattung."
                                    ORDER BY `sortierzeit` DESC LIMIT 0,1  
                                   ");
            if (count($aktbetriebsstelle) > 0) {
                $where_zusatz_vorher = "(`sortierzeit` < '".$aktbetriebsstelle[0]->sortierzeit."')";
                //$where_zusatz_nachher = "(`sortierzeit` >= '".$aktbetriebsstelle[0]->sortierzeit."' AND `".$options["fzmquelle"]."`.`id` > '".$aktbetriebsstelle[0]->id."')";
                $where_zusatz_nachher = "(`sortierzeit` >= '".$aktbetriebsstelle[0]->sortierzeit."')";
            }
        }

        switch ($options["action"]) {
            case "vorherige":
                {
                    $limit = "DESC LIMIT 1,1";
                    if (isset($where_zusatz_vorher)) {
                        $where_zusatzarray[] = $where_zusatz_vorher;
                    }

                    $options["rueckgabestyle"] = "single";
                }
                break;

            case "nächste":
                {
                    $limit = "ASC LIMIT 1,1";
                    if (isset($where_zusatz_vorher)) {
                        $where_zusatzarray[] = $where_zusatz_nachher;
                    }
                }
                break;

            case "uebernaechste":
                {
                    $limit = "ASC LIMIT 2,1";
                    if (isset($where_zusatz_vorher)) {
                        $where_zusatzarray[] = $where_zusatz_nachher;
                    }
                }
                break;

            case "erster":
                {
                    $limit = "LIMIT 0,1";
                    $sortierzeit_order = "ASC";
                }
                break;

            case "letzter":
                {
                    $limit = "LIMIT 0,1";
                    $sortierzeit_order = "DESC";
                }
                break;

            case "restlauf":
            default:
                {
                    if (isset ($where_zusatz_nachher)) {
                        $where_zusatzarray[] = $where_zusatz_nachher;
                    }
                }
                break;
        }

        // Ermitteln der Daten
        // Zuginfos für eine Betriebsstelle
        if (!empty($betriebsstelle)) {
            $where_zusatzarray[] = "(`betriebsstelle` = '".$options["betriebsstelle"]."')";
        }

        // Diverse Where-Zusätze werden ausgerollt
        if (count($where_zusatzarray) > 0) {
            $where_zusatz = implode(" AND ",$where_zusatzarray)." AND ";
        }

        // Diverse Feld-Zusätze werden ausgerollt
        if (count($feld_zusatzarray) > 0) {
            $feld_zusatz = ", ".implode(", ",$feld_zusatzarray);
        } else {
            $feld_zusatz = "";
        }

        $zugdaten  = $DB->select("SELECT `".$options["zugquelle"]."`.`id` AS `zug_id`,`".$options["zugquelle"]."`.`zugnummer`, `".$options["zugquelle"]."`.`zuggattung_id`, 
                                  CONCAT(`".DB_TABLE_ZUEGE_ZUGGATTUNGEN."`.`zuggattung`, ' ',`".$options["zugquelle"]."`.`zugnummer`) AS `zug`, 
                                  `".$options["zugquelle"]."`.`verkehrstage`, `".$options["zugquelle"]."`.`verkehrstage_bin`, `".$options["fzmquelle"]."`.`bemerkungen`, 
                                  `".DB_TABLE_ZUEGE_ZUGGATTUNGEN."`.`zuggattung`, 
                                  `".$options["zugquelle"]."`.`vmax`, `".$options["zugquelle"]."`.`triebfahrzeug`, `".$options["zugquelle"]."`.`bremssystem`, `".$options["zugquelle"]."`.`mbr`,
                                  `".$options["zugquelle"]."`.`wendezug`, `".$options["zugquelle"]."`.`uebergang_von_zug_id`, `".$options["zugquelle"]."`.`uebergang_nach_zug_id`,
                                   ".$felder_anab." `".$options["fzmquelle"]."`.`betriebsstelle`, 
                                  `".$options["fzmquelle"]."`.`ins_gegengleis`, `".$options["fzmquelle"]."`.`ist_durchfahrt`, `".$options["fzmquelle"]."`.`ist_kurzeinfahrt`, 
                                  `".$options["fzmquelle"]."`.`gleis_plan` as `gleis`, `".$options["fzmquelle"]."`.`gleis_plan`, `".$options["fzmquelle"]."`.`fahrtrichtung`,
                                   `".$options["fzmquelle"]."`.`id` AS `fzm_id`
                                  ".$feld_zusatz."
                           FROM `".$options["fzmquelle"]."`
                           ".$leftjoin_betriebsstellendaten."
                           LEFT JOIN `".$options["zugquelle"]."`
                            ON (`".$options["fzmquelle"]."`.`zug_id` = `".$options["zugquelle"]."`.`id`)
                           ".$leftjoin_uebergangsdaten."
                           LEFT JOIN `".DB_TABLE_ZUEGE_ZUGGATTUNGEN."`
                            ON (`".$options["zugquelle"]."`.`zuggattung_id` = `".DB_TABLE_ZUEGE_ZUGGATTUNGEN."`.`id`)
                           WHERE ".$where_zusatz." `".$options["zugquelle"]."`.`".$idfeld."` = '".$zugnummer."' ".$where_version." ".$where_zuggattung."
                           ORDER BY `".$options["fzmquelle"]."`.`sortierzeit` ".$sortierzeit_order.", `".$options["fzmquelle"]."`.`id` ".$limit);
        $zugcount = count($zugdaten);

        if ($zugcount == 0) {
            if ($options["errorhandling"] == "show") {
                print ("Die Zugnummer ".$zugnummer." konnte nicht gefunden werden.");
            }
        } else {
            switch ($options["rueckgabestyle"])
            {
                default:
                case "single":
                    {
                        $rueckgabewert = $zugdaten[0];
                    }
                    break;

                case "array":
                    {
                        $rueckgabewert = $zugdaten;
                    }
                    break;
            }
        }
    }

    unset($DB);

    if (isset($rueckgabewert)) {
        return $rueckgabewert;
    } else {
        return false;
    }
}


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




?>
