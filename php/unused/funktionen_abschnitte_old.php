<?php

// ------------------------------------------------------------------------------------------------
// Ermittele die nächsten Freimeldeabschnitte
function getNaechsteAbschnitte($start_infra_id, $fahrtrichtung) {
 
 global $cacheInfradaten, $cacheInfranachbarn, $cacheSignaldaten;
 
 if (!isset($cacheSignaldaten) || !isset( $cacheInfradaten) || !isset($cacheInfranachbarn) ) {
  die('cacheSignaldaten, cacheInfradaten und cacheInfranachbarn müssen gefüllt sein.');
 }
 if (!isset($cacheInfradaten[$start_infra_id]['nachbarn'])) {
  die('Unbekannte Infra-ID '.$start_infra_id);
 }
 
 debugMessage('Ermittele nächste Abschnitte ausgehend von Infra-ID '.$start_infra_id.' in Richtung '.$fahrtrichtung);
 $nachbarn = $cacheInfradaten[$start_infra_id]['nachbarn'];
 
 // Wenn es mehrere Abschnitte gibt, dann sollte in Fahrtrichtung der letzte genommen (zur sicheren Seite) werden
 if (count($nachbarn) > 1) {
  $aktuell = $nachbarn[0];  
  debugMessage('Es gibt mehrere Abschnitte zu dieser Infra-ID.');
 } else {
  $aktuell = $nachbarn[0];
 }
 
 $weitermachen = true;
 
 $abschnitte = array();
 
 do {
  
 // Ermittele die Nachbarn bis zum nächsten Zielpunkt
  // Wenn Weiche vorhanden, muss deren Stellung ermittelt werden, sonst gilt 0
  if (isset($aktuell['weiche_id']) && !is_null($aktuell['weiche_id'])) {
   $dir = getDir($aktuell['weiche_id']);
   debugMessage('Weiche '.$aktuell['weiche_id'].' steht in Stellung '.$dir.'.');
  } else {
   $dir = 0;
  }
  
  // Dann nachbar$fahrtrichtung_$dir nehmen und für den weitersuchen
  if (!is_null($cacheInfranachbarn[$aktuell['id']]['nachbar'.$fahrtrichtung.'_'.$dir])) {

   $naechster_id = $cacheInfranachbarn[$aktuell['id']]['nachbar'.$fahrtrichtung.'_'.$dir];
   debugMessage ('Es gibt einen Nachbarabschnitt '.$naechster_id.' (Infra-ID '.$cacheInfranachbarn[$naechster_id]['infra_id'].')');
   // Wenn am neuen letzten Abschnitt ein Halt zeigendes Signal steht oder dieser belegt ist,
   // wird abgebrochen
   if (isset($cacheSignaldaten['freimeldeabschnitte'][$cacheInfranachbarn[$aktuell['id']]['infra_id']][$fahrtrichtung]['signalstandort_id'])) {
    debugMessage('Ermittele Signalbegriff an Signalstandort '.$cacheSignaldaten['freimeldeabschnitte'][$cacheInfranachbarn[$aktuell['id']]['infra_id']][$fahrtrichtung]['signalstandort_id']);
    $signalbegriff = getSignalbegriff($cacheSignaldaten['freimeldeabschnitte'][$cacheInfranachbarn[$aktuell['id']]['infra_id']][$fahrtrichtung]['signalstandort_id']);
    
    if ($signalbegriff && $signalbegriff[0]['geschwindigkeit'] == 0 && $signalbegriff[0]['id'] != 548) {
     debugMessage('Signalbegriff ist '.$signalbegriff[0]['begriff'].'.');
     $weitermachen = false;
    } else {
     debugMessage ('Signal zeigt keinen Haltbegriff');
    }
   }  

   //Der Abschnitt wird im Array gesammelt
   $abschnitte[] = array ('nachbar_id' => $naechster_id,
                          'infra_id' => $cacheInfranachbarn[$naechster_id]['infra_id'],
                          'laenge' =>$cacheInfranachbarn[$naechster_id]['laenge']);
  
   $aktuell = $cacheInfranachbarn[$naechster_id];
  } else {
   // Wenn es keinen Nachbarn dort gibt => abbruch
   debugMessage('Es gibt keinen weiteren Nachbarn. Offenbar ist hier ein Gleisende.');
   $weitermachen = false;
  }
 } while ($weitermachen);
 
 // Die gesammelten Abschnitte werden aufbereitet (alle Teilabschnitte einer Infra-ID zusammengefasst)
 $anzahl_abschnitte = count($abschnitte);
 if ($anzahl_abschnitte > 0) {
  $vorgaenger = 0;
  for ($a = 1; $a < $anzahl_abschnitte; $a++) {
   if ($abschnitte[$vorgaenger]['infra_id'] == $abschnitte[$a]['infra_id']) {
    $abschnitte[$vorgaenger]['laenge'] = $abschnitte[$vorgaenger]['laenge']+$abschnitte[$a]['laenge'];
    unset($abschnitte[$a]);
   } else {
    $vorgaenger = $a;
   }
  }
 }
 echo "<br />\n";
 print_r ($abschnitte);
 return true;
}



// ------------------------------------------------------------------------------------------------
// Ermittelt die aktuelle Richtung eines Infrastrukturelements
function getDir ($id) {
 if (empty($id) || $id == 0) {
  return false;
 } else {
  $DB = new DB_MySQL();
  $daten = $DB->select('SELECT `dir` FROM `'.DB_TABLE_INFRAZUSTAND."` WHERE `id` = '".$id."' ");
  unset ($DB); 
  
  if (count($daten) == 0) {
   return false; }
  else {
   return $daten[0]->dir;       
  }
 }   
} 



// ------------------------------------------------------------------------------------------------
// Ermittele die IDs der direkten Nachbarn eines Infrastrukturelementes
function getInfraNachbarn ($id, $id_typ = 'infra_id') {
 
 $DB = new DB_MySQL();
 $infraliste = $DB->select('SELECT `id`, `weiche_id`, `nachbar0_0`,`nachbar0_1`,`nachbar1_0`,`nachbar1_1`
                            FROM `'.DB_TABLE_INFRA_NACHBARN.'`
                            WHERE `'.$id_typ."` = '".$id."'
                           ");
 unset($DB); 
 
 $nachbarn = array();
 
 if ($infraliste && count($infraliste) > 0) {
  foreach ($infraliste as $nachbar) {
   $nachbarn[] = array('id' => $nachbar->id,
                       'weiche_id' => $nachbar->weiche_id,
                       'nachbar0_0' => $nachbar->nachbar0_0,
                       'nachbar0_1' => $nachbar->nachbar0_1,
                       'nachbar1_0' => $nachbar->nachbar1_0,
                       'nachbar1_1' => $nachbar->nachbar1_1);
  }
  
  return $nachbarn;
 } else {
  return false;
 }
}
// ------------------------------------------------------------------------------------------------
// Caching der Nachbardaten
function createCacheInfranachbarn () {

 $DB = new DB_MySQL();
 $infraliste = $DB->select('SELECT `id`, `infra_id`, `laenge`, `weiche_id`, `nachbar0_0`,`nachbar0_1`,`nachbar1_0`,`nachbar1_1`
                            FROM `'.DB_TABLE_INFRA_NACHBARN.'`
                           ');
 unset($DB); 
 
 $nachbarn = array();
 
 if ($infraliste && count($infraliste) > 0) {
  foreach ($infraliste as $nachbar) {
   $nachbarn[$nachbar->id] = array('id' => $nachbar->id,
                                   'weiche_id' => $nachbar->weiche_id,
                                   'infra_id' => $nachbar->infra_id,
                                   'laenge' => $nachbar->laenge,
                                   'nachbar0_0' => $nachbar->nachbar0_0,
                                   'nachbar0_1' => $nachbar->nachbar0_1,
                                   'nachbar1_0' => $nachbar->nachbar1_0,
                                   'nachbar1_1' => $nachbar->nachbar1_1);
  }
 }
  
 return $nachbarn;
}
// ------------------------------------------------------------------------------------------------
// Caching von Infrazustand-Daten
// ------------------------------------------------------------------------------------------------
function createCacheInfradaten($id = false) {
 $cacheInfradaten = array();

 // Wenn ein Array von IDs mitgeliefert wird, wird auf diese gefiltert (genutzt, wenn der Cache aus der GUI heraus aufgebaut wird)
 if (isset($id) && is_array($id)) {
  $where = 'WHERE `id` IN ('.implode(',',$id).')';
 } else {
  $where = '';
 }
 
 $DB = new DB_MySQL();
 $infraliste = $DB->select('SELECT `id`, `address`, `type`, `wrm_aktiv`, `laenge`,
                                   `betriebsstelle`, `signalstandort_id`, `freimeldeabschnitt_id`,
                                   `weichenabhaengigkeit_id`, `kurzbezeichnung`
                            FROM `'.DB_TABLE_INFRAZUSTAND.'`
                            '.$where.'
                           ');
 unset($DB);

 if ($infraliste && count($infraliste) > 0) {
  foreach ($infraliste as $element) {   
   $cacheInfradaten[$element->id] = array('address' => $element->address,
                                          'betriebsstelle' => $element->betriebsstelle,
                                          'freimeldeabschnitt_id' => $element->freimeldeabschnitt_id,
                                          'type' => $element->type
                                         );                                         
                                         
   // Gleisabschnitte haben weitere Eigenschaften
   if ($element->type == 'gleis') {
    // Ermittele die Nachbarn dieses Elementes (sofern es ein Freimeldeabschnitt ist)
    $nachbarn = getInfraNachbarn($element->id, 'infra_id');
    
    if ($nachbarn) {
     $cacheInfradaten[$element->id]['nachbarn'] = $nachbarn;
    }
    
    // Ermittele Signale, die an diesem Abschnitt stehen
    
    
   }                                         
  }
 }

 return $cacheInfradaten;
}



// ------------------------------------------------------------------------------------------------
// Caching der Signaldaten 
// Die einzelnen gecachten Daten liegen im Array $cacheSignaldaten["standorte"] und ["begriffe"] und ["rotlampen"] (für ein Array, aufgebaut nach der für die ZN entscheidenden Signallampe)
// ------------------------------------------------------------------------------------------------
function createCacheSignaldaten() {
 $cacheSignaldaten = array();
 $cacheSignaldaten['standorte'] = array();
 $cacheSignaldaten['begriffe']  = array();
 $cacheSignaldaten['elemente']  = array();
 $cacheSignaldaten['rotlampen'] = array();
 $cacheSignaldaten['befehlslampen'] = array();

 $DB = new DB_MySQL();
 
 // Signalbegriffe
 $signalbegriffdaten = $DB->select('SELECT `'.DB_TABLE_SIGNALE_BEGRIFFE.'`.`id` AS `signalbegriff_id`,
                                           `'.DB_TABLE_SIGNALE_BEGRIFFE.'`.`signal_id` AS `sid`,
                                           `'.DB_TABLE_SIGNALE_BEGRIFFE.'`.`geschwindigkeit`,
                                           `'.DB_TABLE_SIGNALE_BEGRIFFE.'`.`begriff`,
                                           `'.DB_TABLE_SIGNALE_BEGRIFFE.'`.`adresse`,
                                           `'.DB_TABLE_SIGNALE_BEGRIFFE.'`.`webstw_farbe`,
                                           `'.DB_TABLE_SIGNALE_BEGRIFFE.'`.`is_zugfahrtbegriff`,
                                           `'.DB_TABLE_SIGNALE_BEGRIFFE.'`.`zielentfernung`,
                                           `'.DB_TABLE_SIGNALE_BEGRIFFE.'`.`zielgeschwindigkeit`,
                                           `'.DB_TABLE_SIGNALE_BEGRIFFE.'`.`original_begriff_id`
                                          FROM `'.DB_TABLE_SIGNALE_BEGRIFFE.'`                                          
                                          ORDER BY `'.DB_TABLE_SIGNALE_BEGRIFFE.'`.`id`
                                         ');

 // Rot-Lampen (zu ermitteln über die Infra-ID mit Typ "Startsignal")
 $fahrstrassensignalbegriffdaten = $DB->select('SELECT `'.DB_TABLE_FAHRSTRASSEN_ELEMENTE.'`.`infra_id`,
                                                       `'.DB_TABLE_SIGNALE_STANDORTE.'`.`betriebsstelle`,
                                                       `'.DB_TABLE_SIGNALE_STANDORTE.'`.`signaltyp`,
                                                       `'.DB_TABLE_SIGNALE_STANDORTE.'`.`id` AS `signalstandort_id`
                                                FROM `'.DB_TABLE_FAHRSTRASSEN_ELEMENTE.'`       
                                                LEFT JOIN `'.DB_TABLE_INFRAZUSTAND.'`
                                                 ON (  `'.DB_TABLE_FAHRSTRASSEN_ELEMENTE.'`.`infra_id` = `'.DB_TABLE_INFRAZUSTAND.'`.`id`)     
                                                LEFT JOIN `'.DB_TABLE_SIGNALE_STANDORTE.'`
                                                 ON (  `'.DB_TABLE_INFRAZUSTAND.'`.`signalstandort_id` = `'.DB_TABLE_SIGNALE_STANDORTE.'`.`id`)     
                                                WHERE `'.DB_TABLE_FAHRSTRASSEN_ELEMENTE.'`.`dir` = 9
                                                GROUP BY `'.DB_TABLE_FAHRSTRASSEN_ELEMENTE.'`.`infra_id`   
                                                ORDER BY `'.DB_TABLE_FAHRSTRASSEN_ELEMENTE.'`.`infra_id`                                                
                                               ');

 foreach ($fahrstrassensignalbegriffdaten AS $fahrstrassensignalbegriff) {
  $fahrstrassendaten = $DB->select('SELECT `'.DB_TABLE_FAHRSTRASSEN_ELEMENTE.'`.`fahrstrassen_id` FROM `'.DB_TABLE_FAHRSTRASSEN_ELEMENTE.'` WHERE `'.DB_TABLE_FAHRSTRASSEN_ELEMENTE."`.`infra_id` = '".$fahrstrassensignalbegriff->infra_id."'");
  $fahrstrassenliste = array();

  foreach ($fahrstrassendaten as $fahrstrasse) {
   $fahrstrassenliste[] = $fahrstrasse->fahrstrassen_id;
  } 

  $cacheSignaldaten['rotlampen'][$fahrstrassensignalbegriff->infra_id] = array('betriebsstelle' => $fahrstrassensignalbegriff->betriebsstelle,
                                                                               'signalstandort_id' => $fahrstrassensignalbegriff->signalstandort_id,
                                                                               'signaltyp' => $fahrstrassensignalbegriff->signaltyp,
                                                                               'fahrstrassen_id_liste' => $fahrstrassenliste);
                                                                               
  $befehlsdaten = $DB->select('SELECT `id` FROM `'.DB_TABLE_INFRAZUSTAND.'` WHERE `'.DB_TABLE_INFRAZUSTAND."`.`signalstandort_id` = '".$fahrstrassensignalbegriff->signalstandort_id."' AND `".DB_TABLE_INFRAZUSTAND."`.`type` = 'befehlssignal'");

  if ($befehlsdaten && count($befehlsdaten) > 0) {
   // Die Befehlslampen werden für Zs1, Zs7, Zs8 und schriftliche Befehle (u.a. in der ZNS) verwendet                                                                               
   $cacheSignaldaten['befehlslampen'][$befehlsdaten[0]->id] = array('betriebsstelle' => $fahrstrassensignalbegriff->betriebsstelle,
                                                                    'signalstandort_id' => $fahrstrassensignalbegriff->signalstandort_id,
                                                                    'signaltyp' => $fahrstrassensignalbegriff->signaltyp,
                                                                    'fahrstrassen_id_liste' => $fahrstrassenliste);
   unset ($befehlsdaten);                                                                                    
  }                                                                                    
  
  unset($fahrstrassenliste);                                                                               
 }

 // Signalstandorte
 $signalstandortliste = $DB->select('SELECT `'.DB_TABLE_SIGNALE_STANDORTE.'`.`id`,  
                                            `'.DB_TABLE_SIGNALE_STANDORTE.'`.`haltfall_id`, 
                                            `'.DB_TABLE_SIGNALE_STANDORTE.'`.`haltbegriff_id`, 
                                            `'.DB_TABLE_SIGNALE_STANDORTE.'`.`freimelde_id`, 
                                            `'.DB_TABLE_SIGNALE_STANDORTE.'`.`signaltyp`, 
                                            `'.DB_TABLE_SIGNALE_STANDORTE.'`.`haltabschnitt_id`, 
                                            `'.DB_TABLE_SIGNALE_STANDORTE.'`.`wirkrichtung`, 
                                            `'.DB_TABLE_SIGNALE_STANDORTE.'`.`fahrplanhalt`, 
                                            `'.DB_TABLE_BETRIEBSSTELLEN_DATEN.'`.`kuerzel`, 
                                            `'.DB_TABLE_BETRIEBSSTELLEN_DATEN.'`.`parent_kuerzel`
                                     FROM `'.DB_TABLE_SIGNALE_STANDORTE.'`
                                     LEFT JOIN `'.DB_TABLE_BETRIEBSSTELLEN_DATEN.'` ON (`'.DB_TABLE_SIGNALE_STANDORTE.'`.`betriebsstelle` = `'.DB_TABLE_BETRIEBSSTELLEN_DATEN.'`.`kuerzel`) ');
 
 foreach ($signalbegriffdaten as $signalbegriff) {
  $cacheSignaldaten['begriffe'][$signalbegriff->signalbegriff_id] = array ('geschwindigkeit' => $signalbegriff->geschwindigkeit,
                                                                           'sid' => $signalbegriff->sid,
                                                                           'begriff' => $signalbegriff->begriff,
                                                                           'webstw_farbe' => $signalbegriff->webstw_farbe,
                                                                           'zielentfernung' => $signalbegriff->zielentfernung,
                                                                           'zielgeschwindigkeit' => $signalbegriff->zielgeschwindigkeit,
                                                                           'is_zugfahrtbegriff' =>  $signalbegriff->is_zugfahrtbegriff,
                                                                           'original_begriff_id' => $signalbegriff->original_begriff_id,
                                                                           'adresse' => $signalbegriff->adresse);

  $cacheSignaldaten['standorte'][$signalbegriff->sid]['begriffe_id'][] = $signalbegriff->signalbegriff_id;

  unset($signalelemente_array);
 }

 // Abruf der Daten über $cacheSignaldaten["begriffe"]["signalbegriff_id"]: alle Elemente liegen in type / adresse / dir
 
 foreach ($signalstandortliste as $signalstandorteintrag) {
  $cacheSignaldaten['standorte'][$signalstandorteintrag->id]['haltfall_id']      = $signalstandorteintrag->haltfall_id;
  $cacheSignaldaten['standorte'][$signalstandorteintrag->id]['freimelde_id']     = $signalstandorteintrag->freimelde_id;
  $cacheSignaldaten['standorte'][$signalstandorteintrag->id]['signaltyp']        = $signalstandorteintrag->signaltyp;
  $cacheSignaldaten['standorte'][$signalstandorteintrag->id]['haltbegriff_id']   = $signalstandorteintrag->haltbegriff_id;
  $cacheSignaldaten['standorte'][$signalstandorteintrag->id]['haltabschnitt_id'] = $signalstandorteintrag->haltabschnitt_id;
  $cacheSignaldaten['standorte'][$signalstandorteintrag->id]['fahrplanhalt']     = $signalstandorteintrag->fahrplanhalt;

  if ($signalstandorteintrag->parent_kuerzel != NULL) {
   $cacheSignaldaten['standorte'][$signalstandorteintrag->id]['betriebsstelle'] = $signalstandorteintrag->parent_kuerzel;
  } else {
   $cacheSignaldaten['standorte'][$signalstandorteintrag->id]['betriebsstelle'] = $signalstandorteintrag->kuerzel;
  }
  $cacheSignaldaten['standorte'][$signalstandorteintrag->id]['signalbetriebsstelle'] = $signalstandorteintrag->kuerzel;
  
  $cacheSignaldaten['freimeldeabschnitte'][$signalstandorteintrag->freimelde_id][$signalstandorteintrag->wirkrichtung]['signalstandort_id'] = $signalstandorteintrag->id;
 }

 // Signallampen
 $signallampenliste = $DB->select('SELECT `'.DB_TABLE_SIGNALE_ELEMENTE.'`.`signal_id` AS `begriff_id`,
                                          `'.DB_TABLE_SIGNALE_ELEMENTE.'`.`infra_id`,
                                          `'.DB_TABLE_SIGNALE_ELEMENTE.'`.`dir`,
                                          `'.DB_TABLE_INFRAZUSTAND.'`.`address` AS `infra_adresse`,
                                          `'.DB_TABLE_INFRAZUSTAND.'`.`type` AS `infra_type`,
                                          `'.DB_TABLE_INFRADATEN.'`.`wert` AS `infra_zusatzwert`
                                   FROM `'.DB_TABLE_SIGNALE_ELEMENTE.'`
                                   LEFT JOIN `'.DB_TABLE_INFRAZUSTAND.'`
                                    ON (`'.DB_TABLE_SIGNALE_ELEMENTE.'`.`infra_id` = `'.DB_TABLE_INFRAZUSTAND.'`.`id`)
                                   LEFT JOIN `'.DB_TABLE_INFRADATEN.'`
                                    ON (`'.DB_TABLE_SIGNALE_ELEMENTE.'`.`infra_id` = `'.DB_TABLE_INFRADATEN.'`.`infra_id`)
                                  ');
                                   
 foreach ($signallampenliste as $signalelement) {
  $cacheSignaldaten['elemente'][$signalelement->begriff_id][] = array('infra_id' => $signalelement->infra_id, 'dir' => $signalelement->dir,
                                                                      'infra_adresse' => $signalelement->infra_adresse,
                                                                      'type' => $signalelement->infra_type,
                                                                      'infra_zusatzwert' => $signalelement->infra_zusatzwert);
 }

 unset($DB);

 return $cacheSignaldaten;
}


// ------------------------------------------------------------------------------------------------
// Ermittlung des Signalbegriffs eines Signals(tandorts)
// ------------------------------------------------------------------------------------------------
function getSignalbegriff ($signal_id, $optionen = array()) {

 global $cacheSignaldaten;
 
  if (isset($optionen['getSignaltyp']) && $optionen['getSignaltyp']) {
   $felder = ', `'.DB_TABLE_SIGNALE_STANDORTE.'`.`signaltyp`, `'.DB_TABLE_SIGNALE_STANDORTE.'`.`id` AS `signalstandort_id` ';
   $join   = 'LEFT JOIN `'.DB_TABLE_SIGNALE_STANDORTE.'` ON (`'.DB_TABLE_SIGNALE_BEGRIFFE.'`.`signal_id` = `'.DB_TABLE_SIGNALE_STANDORTE.'`.`id` )';
  } else {
   $felder = '';
   $join = '';
  }

  $DB = new DB_MySQL();

  $signale = array();
   
  // Ermittlung der in Frage kommenden Signalbegriffe
  // Wenn das global-Array $cacheSignaldaten["begriffe"] nicht existiert, müssen sie gesucht werden
  if (isset($cacheSignaldaten) && isset($cacheSignaldaten['standorte']) && isset($cacheSignaldaten['standorte'][$signal_id]['begriffe_id']) && isset($cacheSignaldaten['begriffe'])) {
   foreach ($cacheSignaldaten['standorte'][$signal_id]['begriffe_id'] as $begriff_key => $signalstandorteintrag) {
    $signale[] = array('id' => $signalstandorteintrag, 'geschwindigkeit' => $cacheSignaldaten['begriffe'][$signalstandorteintrag]['geschwindigkeit'],
                                                       'begriff' => $cacheSignaldaten['begriffe'][$signalstandorteintrag]['begriff'],
                                                       'webstw_farbe' => $cacheSignaldaten['begriffe'][$signalstandorteintrag]['webstw_farbe'],
                                                       'zielentfernung' => $cacheSignaldaten['begriffe'][$signalstandorteintrag]['zielentfernung'],
                                                       'zielgeschwindigkeit' => $cacheSignaldaten['begriffe'][$signalstandorteintrag]['zielgeschwindigkeit'],
                                                       'is_zugfahrtbegriff' => $cacheSignaldaten['begriffe'][$signalstandorteintrag]['is_zugfahrtbegriff'],
                                                       'original_begriff_id' => $cacheSignaldaten['begriffe'][$signalstandorteintrag]['original_begriff_id'],
                                                       'signaltyp' => $cacheSignaldaten['standorte'][$signal_id]['signaltyp'],
                                                       'signalstandort_id' => $signal_id);
   }
  } else {  
   $signale_ergebnis = $DB->select('SELECT `'.DB_TABLE_SIGNALE_BEGRIFFE.'`.`id`, `geschwindigkeit`, `begriff`, `webstw_farbe`, `is_zugfahrtbegriff`, `zielentfernung`, `zielgeschwindigkeit`, `original_begriff_id` '.$felder.'
                                    FROM `'.DB_TABLE_SIGNALE_BEGRIFFE.'` 
                                    '.$join."
                                    WHERE `signal_id` = '".$signal_id."'
                                   ");
   $signale = json_decode(json_encode($signale_ergebnis), true);
  }

  $anzahlsignale = count($signale);
  debugMessage('Es gibt '.$anzahlsignale.' Signalbegriffe für Signal '.$signal_id.'.');
                                     
  for ($d = 0; $d < $anzahlsignale; $d++) {    
   // Wenn eine Original-Begriff-ID gesetzt wird, dann werden die Elemente dieses Begriffs gesucht, da sie nicht zweimal definiert wurden
   if ($signale[$d]['original_begriff_id'] > 0) {
    $begriff_id = $signale[$d]['original_begriff_id'];
   } else {
    $begriff_id = $signale[$d]['id'];
   }

   debugMessage ('Prüfe Signalbegriff '.$begriff_id.'.');
   
   // Ermittlung der in Frage kommenden Elemente
   // Wenn das global-Array $cacheSignaldaten["elemente"] nicht existiert, müssen sie gesucht werden
   if (isset($cacheSignaldaten['elemente']) && isset($cacheSignaldaten['elemente'][$begriff_id])) {
    $signalbegriff = $cacheSignaldaten['elemente'][$begriff_id];
   } else {
    $signalbegriff_ergebnis = $DB->select('SELECT `infra_id`, `dir` FROM `'.DB_TABLE_SIGNALE_ELEMENTE."` WHERE `signal_id` = '".$begriff_id."'");
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
     $signallampen = $DB->select('SELECT `id`, UNIX_TIMESTAMP(`timestamp`) AS `einstellung_timestamp`
                                  FROM `'.DB_TABLE_INFRAZUSTAND."`
                                  WHERE `id` = '".$signalbegriff[$c]['infra_id']."' AND `dir` = '".$signalbegriff[$c]['dir']."'");

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

     if (!isset($signale[$d]['signaltyp'])) { $signale[$d]['signaltyp'] = false; }

     // Ersetzung der Farben
     switch ($signale[$d]['webstw_farbe']) {
      DEFAULT:
       {
        $webstw_farbe = $signale[$d]['webstw_farbe'];
        $webstw_farbe_fuss = $webstw_farbe;
        $webstw_farbe_rangiersignal = $webstw_farbe;
       }
       break;
       
      CASE 'gelb':
       {
        $webstw_farbe = '#FFCC00';
        $webstw_farbe_fuss = $webstw_farbe;
        $webstw_farbe_rangiersignal = $webstw_farbe;
       } 
       break;   
       
      CASE 'gruen':
       {
        $webstw_farbe = 'green';
        $webstw_farbe_fuss = $webstw_farbe;
        $webstw_farbe_rangiersignal = $webstw_farbe;
       } 
       break;  
       
      CASE 'ke':
       {
        $webstw_farbe      = 'white';
        $webstw_farbe_fuss = 'green';
        $webstw_farbe_rangiersignal = $webstw_farbe;
       }                 
       break;         
  
      CASE 'rot':
      CASE 'zs1':
      CASE 'zs7':
       {
        $webstw_farbe = 'red';
        $webstw_farbe_fuss = 'red';
        $webstw_farbe_rangiersignal = $webstw_farbe;
       }                 
       break;
       
      CASE 'ra12':
      CASE 'sh1':
       {
        $webstw_farbe = 'red';
        $webstw_farbe_fuss = 'red';
        $webstw_farbe_rangiersignal = 'white';
       }                 
       break;         
     }      
     $signalbegriff_ausgabe[] = array ('id' => $signale[$d]['id'],
                                       'geschwindigkeit' => $signale[$d]['geschwindigkeit'],
                                       'zielgeschwindigkeit' => $signale[$d]['zielgeschwindigkeit'],
                                       'begriff' => $signale[$d]['begriff'],
                                       'signaltyp' => $signale[$d]['signaltyp'],
                                       'zielentfernung' => $signale[$d]['zielentfernung'],
                                       'is_zugfahrtbegriff' => $signale[$d]['is_zugfahrtbegriff'],
                                       'webstw_farbe' => $webstw_farbe,
                                       'webstw_farbe_fuss' => $webstw_farbe_fuss,
                                       'webstw_farbe_rangiersignal' => $webstw_farbe_rangiersignal,
                                       'einstellung_timestamp' => $einstellungszeit);
    }
   }
   unset ($signalbegriff);
  }
  unset ($DB);

  if (isset($signalbegriff_ausgabe) && count($signalbegriff_ausgabe) > 1) { debugMessage('Zweifelhafter Signalbegriff ('.count($signalbegriff_ausgabe).')'); }

  if (empty($signalbegriff_ausgabe)) {
   debugMessage ('Kein Signalbegriff zur Ausgabe gefunden für Signal '.$signal_id.'. Setze Dummywerte!');
    $signalbegriff_ausgabe[] = array ('id' => 0,
                                      'geschwindigkeit' => -9,
                                      'begriff' => 'Hp00',
                                      'webstw_farbe' => '#303030',
                                      'webstw_farbe_fuss' => '#303030',
                                      'is_zugfahrtbegriff' => 0,
                                      'signaltyp' => false,
                                      'error' => true,
                                      'einstellung_timestamp' => 0);
  }
  return $signalbegriff_ausgabe;
 }


 ?>