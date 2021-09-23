<?php

// Dieses Skript wird in der Abschnitts�berwachung und im Steuerungsprozess eingebunden

require 'db_tables.php';
require 'mysqli.php';
require  'config/config.php';
require 'config/db_access.php';

// --------------------------------------------------------------------------------------------------------
// Vorabfrage von Daten zur Ablage in Arrays
// --------------------------------------------------------------------------------------------------------
$DB = new DB_MySQL();

// Ermittlung der relevanten Freimeldeabschnitte, deren Belegung ein Event ausl�st:
$alle_abschnitte = $DB->select('SELECT `'.DB_TABLE_SIGNALE_STANDORTE.'`.`freimelde_id`,
                                       `'.DB_TABLE_SIGNALE_STANDORTE.'`.`id` AS `signalstandortid`, 
                                       `'.DB_TABLE_SIGNALE_STANDORTE.'`.`wirkrichtung`,
                                       `'.DB_TABLE_SIGNALE_STANDORTE.'`.`wirkart`,
                                       `'.DB_TABLE_SIGNALE_STANDORTE.'`.`betriebsstelle`,
                                       `'.DB_TABLE_SIGNALE_STANDORTE.'`.`folgebetriebsstelle`,
                                       `'.DB_TABLE_SIGNALE_STANDORTE.'`.`fahrplanhalt`,
                                       `'.DB_TABLE_SIGNALE_STANDORTE.'`.`anhalte_id`,
                                       `'.DB_TABLE_SIGNALE_STANDORTE.'`.`signaltyp`,
                                       `infra_anhalte_id`.`laenge` AS `anhalte_laenge`,
                                       `'.DB_TABLE_SIGNALE_STANDORTE.'`.`freifahrt_id`,
                                       `'.DB_TABLE_SIGNALE_STANDORTE.'`.`haltfall_id`,
                                       `'.DB_TABLE_ZN_GBT.'`.`zugnummer`,
                                       `'.DB_TABLE_ZN_GBT.'`.`id` AS `gbt_id`,
                                       `'.DB_TABLE_FMA_GBT.'`.`fma_id`
                                FROM `'.DB_TABLE_SIGNALE_STANDORTE.'`
                                LEFT JOIN `'.DB_TABLE_ZN_GBT.'`
                                 ON (`'.DB_TABLE_SIGNALE_STANDORTE.'`.`gbt_id` = `'.DB_TABLE_ZN_GBT.'`.`id`)
                                LEFT JOIN `'.DB_TABLE_FMA_GBT.'`
                                 ON (`'.DB_TABLE_SIGNALE_STANDORTE.'`.`gbt_id` = `'.DB_TABLE_FMA_GBT.'`.`gbt_id`)
                                LEFT JOIN `'.DB_TABLE_FMA.'`
                                 ON (`'.DB_TABLE_FMA_GBT.'`.`fma_id` = `'.DB_TABLE_FMA.'`.`fma_id`)                                   
                                LEFT JOIN `'.DB_TABLE_INFRAZUSTAND.'` AS `infra_anhalte_id`
                                 ON (`'.DB_TABLE_SIGNALE_STANDORTE.'`.`anhalte_id` = `infra_anhalte_id`.`id`) 
                                WHERE `'.DB_TABLE_SIGNALE_STANDORTE.'`.`freimelde_id` IS NOT NULL
                                GROUP BY `'.DB_TABLE_SIGNALE_STANDORTE.'`.`id`
                               ');
                               
// Ermittlung der Signalstandorte:
$alle_signalstandorte = $DB->select('SELECT `'.DB_TABLE_SIGNALE_STANDORTE.'`.`freimelde_id`,
                                            `'.DB_TABLE_SIGNALE_STANDORTE.'`.`id` AS `signalstandortid`, 
                                            `'.DB_TABLE_SIGNALE_STANDORTE.'`.`betriebsstelle`, 
                                            `'.DB_TABLE_SIGNALE_STANDORTE.'`.`folgebetriebsstelle`,
                                            `'.DB_TABLE_SIGNALE_STANDORTE.'`.`gbt_id`,
                                            `'.DB_TABLE_SIGNALE_STANDORTE.'`.`signaltyp`,
                                            `'.DB_TABLE_SIGNALE_STANDORTE.'`.`fahrplanhalt`,
                                            `'.DB_TABLE_SIGNALE_STANDORTE.'`.`bezeichnung`,
                                            `'.DB_TABLE_SIGNALE_STANDORTE.'`.`wirkrichtung`
                                FROM `'.DB_TABLE_SIGNALE_STANDORTE.'`
                                WHERE `'.DB_TABLE_SIGNALE_STANDORTE.'`.`freimelde_id` IS NOT NULL
                               ');
                                         
$alle_fahrzeuge = $DB->select('SELECT `'.DB_TABLE_FAHRZEUGE.'`.`adresse`
                               FROM `'.DB_TABLE_FAHRZEUGE.'`
                               LEFT JOIN `'.DB_TABLE_FAHRZEUGE_DATEN.'`
                                ON (`'.DB_TABLE_FAHRZEUGE.'`.`id` = `'.DB_TABLE_FAHRZEUGE_DATEN."`.`id`)                               
                               WHERE (`zustand` <= '".FZS_FZGZUSTAND_NUTZBAR."')
                                AND `".DB_TABLE_FAHRZEUGE_DATEN.'`.`railcom` = 1
                              ');
                              
$alle_fahrzeugdaten = $DB->select('SELECT `'.DB_TABLE_FAHRZEUGE.'`.`adresse`,
                                          `'.DB_TABLE_FAHRZEUGE_DATEN.'`.`name`,
                                          `'.DB_TABLE_FAHRZEUGE_DATEN.'`.`railcom`,
                                          `'.DB_TABLE_FAHRZEUGE_BAUREIHEN.'`.`traktion`,
                                          `'.DB_TABLE_FAHRZEUGE_BAUREIHEN.'`.`vmax`,
                                          `'.DB_TABLE_FAHRZEUGE_BAUREIHEN.'`.`laenge`
                                   FROM `'.DB_TABLE_FAHRZEUGE.'`
                                   LEFT JOIN `'.DB_TABLE_FAHRZEUGE_DATEN.'`
                                    ON (`'.DB_TABLE_FAHRZEUGE.'`.`id` = `'.DB_TABLE_FAHRZEUGE_DATEN.'`.`id`)
                                   LEFT JOIN `'.DB_TABLE_FAHRZEUGE_BAUREIHEN.'`
                                    ON (`'.DB_TABLE_FAHRZEUGE_DATEN.'`.`baureihe` = `'.DB_TABLE_FAHRZEUGE_BAUREIHEN."`.`nummer`)                                    
                                   WHERE (`zustand` <= '".FZS_FZGZUSTAND_NUTZBAR."')
                                    AND `".DB_TABLE_FAHRZEUGE_DATEN.'`.`railcom` = 1
                                  ');
                                  
unset ($DB);                                          

// Abschnitte liegen in $alle_abschnitte[]->freimelde_id und $alle_abschnitte[]->freimelde_id2
$abschnitte = array();
$freifahrtabschnitte = array();
$abschnittdaten = array();
$anhalteabschnitte = array();

foreach ($alle_abschnitte as $abschnitt) {
 $abschnitte[] = $abschnitt->freimelde_id;

 $abschnittdaten[$abschnitt->freimelde_id][] = array ('wirkrichtung' => $abschnitt->wirkrichtung,
                                                      'wirkart' => $abschnitt->wirkart,
                                                      'freimelde_id' => $abschnitt->freimelde_id,
                                                      'betriebsstelle' => $abschnitt->betriebsstelle,
                                                      'folgebetriebsstelle' => $abschnitt->folgebetriebsstelle,
                                                      'signalstandortid' => $abschnitt->signalstandortid,
                                                      'signaltyp' => $abschnitt->signaltyp,
                                                      'fahrplanhalt' => $abschnitt->fahrplanhalt,
                                                      'anhalte_id' => $abschnitt->anhalte_id,
                                                      'anhalte_laenge' => $abschnitt->anhalte_laenge,
                                                      'freifahrt_id' => $abschnitt->freifahrt_id,
                                                      'gbt_id' => $abschnitt->gbt_id,
                                                      'haltfall_id' => $abschnitt->haltfall_id,
                                                      'fma_id' => $abschnitt->fma_id
                                                     );
                                                    
 if ($abschnitt->freifahrt_id > 0) {
  $freifahrtabschnitte[] = $abschnitt->freifahrt_id;
  $freifahrtabschnittreferenz[$abschnitt->freifahrt_id] = $abschnitt->freimelde_id;
 }        
   
 if ($abschnitt->anhalte_id > 0) {
  $anhalteabschnitte[] = $abschnitt->anhalte_id;
  $anhalteabschnittereferenz[$abschnitt->anhalte_id] = $abschnitt->anhalte_id;
 }          
}

// Alle Freimeldeabschnitte liegen jetzt als ID im Array $abschnitte; 
// Array aufr�umen und doppelte Eintr�ge entfernen
//$abschnitte = array_values(array_unique($abschnitte)); 
  
// Alle Signalstandorte in ein Array legen
// !!! To-Do: Umstellen auf zentrale Cache-Funktionen !!!
foreach ($alle_signalstandorte as $signalstandort) {
 $signalstandorte[$signalstandort->signalstandortid] = array ('gbt_id' => $signalstandort->gbt_id, 'betriebsstelle' => $signalstandort->betriebsstelle, 'signaltyp' => $signalstandort->signaltyp, 'fahrplanhalt' => $signalstandort->fahrplanhalt, 'folgebetriebsstelle' => $signalstandort->folgebetriebsstelle);
}
foreach ($alle_signalstandorte as $signalstandort) {
 $signale_perinfraid[$signalstandort->freimelde_id][$signalstandort->wirkrichtung] = array ('id' => $signalstandort->signalstandortid, 'gbt_id' => $signalstandort->gbt_id, 'bezeichnung' => $signalstandort->bezeichnung);
}
  
// Alle Fahrzeug-Adressen in ein Array legen
foreach ($alle_fahrzeuge as $einzelfahrzeug) {
 $fahrzeuge[] = $einzelfahrzeug->adresse;
}
 
// Alle statischen Fahrzeugdaten in ein Array legen
foreach ($alle_fahrzeugdaten as $einzelfahrzeugdaten) {
 $fahrzeugdaten[$einzelfahrzeugdaten->adresse] = $einzelfahrzeugdaten;
} 

/*
 
// Alle Fahrplandaten in ein Array legen
try {
 $fahrplancacheinstance = new cacheFahrplan();
 $fahrplandaten = $fahrplancacheinstance->getAll (array());
 unset($fahrplancacheinstance);
}
catch (Exception $e) {
 echo $e . "\n";
}

unset ($fahrplancacheinstance);

*/

?>
