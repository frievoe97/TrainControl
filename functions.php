<?php

// Ermittelt, welcher Fahrzeugdecoder in einem GBT-Feld steht
function getFahrzeugimAbschnitt (int $gbt_id) {
    return int $fahrzeug_id;
}

// Ermittelt, welcher Fahrzeugdecoder in einem Infra-Feld steht
function getFahrzeugimInfraAbschnitt (int $infra_id) {
    return int $fahrzeug_id;
}

// Ermittelt den Signalbegriff für die Fahrzeugsteuerung
function fzs_getSignalbegriff(array $abschnittdaten) {
 // $abschnittdaten kommen aus der vorbelegung.php, relevant hier "haltfall_id" und als Pflichtfeld "signalstandortid".
return $signalbegriff; // darin [0]["geschwindigkeit"] relevant;
}

// Ermittelt das in Gegenrichtung relevante Signal, wenn ein Zug wendet
function fzs_getGegensignal ($signal_id) {
 return $gegensignal; // als Array: id, freimelde_id
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
function wandeleUhrzeit(timestamp $inputzeit,$zielart = "simulationszeit" oder "realzeit", $options = array()) {
 return timestamp $output;
}


// Prüfung, ob die Fahrplansession noch aktuell ist, wenn nicht, dann wird das vorerst Skript beendet, damit es von SYSTEMD wieder neugestartet wird
function checkFahrplansession () {
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