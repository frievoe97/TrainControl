<?php

// Prozess zur Überwachung der Gleisfreimeldeabschnitte für die Fahrzeugsteuerung
/*
define ("PRAEFIX",dirname(__FILE__).'/../../');
@session_start();

require_once PRAEFIX.'config/server_ebuef.php';
require_once PRAEFIX.'config/db_tables.php';
require_once PRAEFIX.'config/config.php';
require_once PRAEFIX.'includes/classes/mysql.php';

require_once PRAEFIX.'config/multicast.php';

$u = 0;
*/

require 'vorbelegung.php';
require 'functions/sort_functions.php';
require 'functions/cache_functions.php';
require 'functions/cache_functions_own.php';
require 'functions/ebuef_functions.php';
require 'functions/fahrtverlauf_functions.php';
require 'globalVariables.php';
require 'config/multicast.php';



// --------------------------------------------------------------------------------------------------------
// Vorabfrage von Daten zur Ablage in Arrays / EBuEf-Funktionen
// --------------------------------------------------------------------------------------------------------

//define ('FZS_FAHRZEUGE_WAGENDECODER',99);

$DB = new DB_MySQL();


// Ermittlung der relevanten Freimeldeabschnitte, deren Belegung ein Event auslöst:

$alle_fma_abschnitte = $DB->select("SELECT `".DB_TABLE_FMA."`.`fma_id`, `".DB_TABLE_FMA_GBT."`.`infra_id`
                                    FROM `".DB_TABLE_FMA_GBT."`
                                    LEFT JOIN `".DB_TABLE_FMA."`
                                     ON (`".DB_TABLE_FMA_GBT."`.`fma_id` = `".DB_TABLE_FMA."`.`fma_id`)
                                    WHERE `".DB_TABLE_FMA."`.`type` IS NOT NULL
                                   ");
                                   
$fma_abschnitte = array();
if ($alle_fma_abschnitte && count($alle_fma_abschnitte) > 0) { 
 foreach ($alle_fma_abschnitte  as $abschnitt) {
  $fma_abschnitte[$abschnitt->fma_id] = $abschnitt->infra_id;    
 }
}    

// Alle Fahrzeug-Adressen in ein Array legen
$alle_fahrzeuge = $DB->select("SELECT `".DB_TABLE_FAHRZEUGE."`.`id`, `".DB_TABLE_FAHRZEUGE."`.`adresse`
                               FROM `".DB_TABLE_FAHRZEUGE."`
                               LEFT JOIN `".DB_TABLE_FAHRZEUGE_DATEN."`
                                ON (`".DB_TABLE_FAHRZEUGE."`.`id` = `".DB_TABLE_FAHRZEUGE_DATEN."`.`id`)                               
                               WHERE (`zustand` <= '".FZS_FZGZUSTAND_NUTZBAR."')
                                AND `".DB_TABLE_FAHRZEUGE_DATEN."`.`railcom` = 1
                              ");     
                              
foreach ($alle_fahrzeuge as $einzelfahrzeug) {
 $fahrzeuge[$einzelfahrzeug->id] = $einzelfahrzeug->adresse;
}


// --------------------------------------------------------------------------------------------------------

function addFahrzeugbelegung(int $infra_id, int $decoder_adresse = NULL) {
  
 if (!isset($infra_id)) { return false; }
 
 global $fahrzeuge;

 // Sofern keine Decoder-Adresse aus $fahrzeuge übergeben wird, muss diese noch ermittelt werden
 // Handelt es sich um die fiktive Adresse für Wagen, wird diese verwendet
 if (!in_array($decoder_adresse,$fahrzeuge) && $decoder_adresse != FZS_FAHRZEUGE_WAGENDECODER) {
  //echo "Ermittele Fahrzeug im Abschnitt ".$infra_id."...\n";
  $decoder_adresse = "";



  // Ermittele die Fahrzeug-ID
  $fahrzeug_id = getFahrzeugimInfraAbschnitt($infra_id);    
 } else {
  $fahrzeug_id = array_keys($fahrzeuge,$decoder_adresse);
  
  if (!$fahrzeug_id) { return false; }
 }



 // Wenn es ein Fahrzeug gibt, wird weitergemacht
 if (isset($fahrzeug_id)) {
  if (!$fahrzeug_id) {

  } else {
   $DB = new DB_MySQL();
   $DB->query("INSERT INTO `".DB_TABLE_FAHRZEUGE_ABSCHNITTE."` (`fahrzeug_id`, `infra_id`, `unixtimestamp`) 
               VALUES ('".$fahrzeug_id."', '".$infra_id."', '".time()."') 
               ON DUPLICATE KEY UPDATE `infra_id` = '".$infra_id."', `unixtimestamp` = '".time()."' ");
   unset($DB);
  }
 }
}

// ------------------------------------------------------------------------------------------------
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


 if (!$gbt_id || empty($gbt_id)) { return false;	}  
 $fahrzeug = getFahrzeugimAbschnitt($gbt_id);

 
 return $fahrzeug;
} 

// ------------------------------------------------------------------------------------------------
// Ermittelt, zu welchem GBT-Feld ein Gleisabschnitt (Infra-ID) gehört
function getGleisabschnitt ($infra_id) {
 if (empty($infra_id)) { return false; }

 $DB = new DB_MySQL();
 $gbtfeld   = $DB->select("SELECT `".DB_TABLE_ZN_GBT."`.`id`  
                           FROM `".DB_TABLE_FMA_GBT."`
                           LEFT JOIN `".DB_TABLE_ZN_GBT."`
                            ON (`".DB_TABLE_FMA_GBT."`.`gbt_id` = `".DB_TABLE_ZN_GBT."`.`id`)
                           WHERE `".DB_TABLE_FMA_GBT."`.`infra_id` = '".$infra_id."'
                          "); 
 unset($DB);
 
 if (count($gbtfeld) == 0) {
  return false;   
 } else {                          
  return $gbtfeld[0]->id;
 }
}  



// -------------------------------------------------------------------------------------------------------- 
echo "---------------------------------------------- \n";
echo "EBuEf-Fahrzeugsteuerung | Abschnittueberwachung\n";

// Prüfung PHP-Version
if (version_compare(PHP_VERSION, '5.4.0') < 0) {
  die("PHP >= 5.4 ist hier erforderlich! Version ".PHP_VERSION." ist installiert.");
 }

echo "Verbindung ueber " . MULTICAST_FZS_IP . ":". MULTICAST_FZS_PORT . ".\n";
flush();

$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP); 
$binded = socket_bind($socket, '0.0.0.0', MULTICAST_FZS_PORT); 
// braucht PHP >= 5.4
$rval = socket_set_option($socket, getprotobyname('ip'), MCAST_JOIN_GROUP, array("group"=>MULTICAST_FZS_IP,"interface"=>0)); 

if (!$socket || !$binded || !$rval) {
    die("Fehler beim Aufbau des Sockets: $errstr ($errno)!");
}                                                                                           

// Beim Start wird für alle zu diesem Zeitpunkt belegten, überwachten Gleisabschnitte ein Eintrag geschrieben
foreach (array_unique($fma_abschnitte) as $abschnitt) {
 if (getDir($abschnitt) == 1) {
  debugMessage(date("H:i").": INIT: Abschnitt $abschnitt belegt.");
  addFahrzeugbelegung ($abschnitt, NULL);
 }  
}

// Endlosschleife
do {
    $fromip = ''; 
    $fromport = 0; 
    // Pakete mit mehr als 50 Zeichen gibt es derzeit nicht 
    socket_recvfrom($socket, $message, 50, MSG_WAITALL, $fromip, $fromport); 
    
    // Erwartetes Format ist fma infra_id decoder_adresse dir
    $element = explode (" ",$message);
        
    // Initialisierung
    $infra_id        = $element[1];
    $decoder_adresse = $element[2];      // bei Nicht-Railcom-Abschnitten wird hier "offline" gesendet
    $infra_dir       = $element[3];

  
    // Es sind nur Messages relevant, bei denen Gleise belegt werden (dir = 1), die Infra_ID im Abschnitte-Array stehen
        
    // Ein Element wird belegt
    if ($infra_dir == 1) {
     debugMessage (date("H:i:s").": Abschnitt ".$infra_id.", Fahrzeug ".$decoder_adresse.", Belegung ".$infra_dir.".");
     if ($infra_id > 0 && in_array($infra_id,$abschnitte)) {
      addFahrzeugbelegung ($infra_id, $decoder_adresse);
     } else {
      debugMessage ("Abschnitt ".$infra_id." ist nicht im Array!");
     }
    }     
    
// Ende Endlosschleife
} while (true);

echo "Fahrzeugsteuerung (Abschnittueberwachung) wurde beendet!";

close ($socket);
?>
