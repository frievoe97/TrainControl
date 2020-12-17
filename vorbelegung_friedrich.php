<?php

// Dieses Skript wird in der Abschnitts�berwachung und im Steuerungsprozess eingebunden

require 'db_tables.php';
require 'mysqli.php';
require 'config/config.php';
require 'config/db_access.php';

// --------------------------------------------------------------------------------------------------------
// Vorabfrage von Daten zur Ablage in Arrays
// --------------------------------------------------------------------------------------------------------
$DB = new DB_MySQL();

// Ermittlung der relevanten Freimeldeabschnitte, deren Belegung ein Event ausl�st:
$alle_fahrzeuge = $DB->select("SELECT `".DB_TABLE_FAHRZEUGE."`.`adresse`
                               FROM `".DB_TABLE_FAHRZEUGE."`
                               LEFT JOIN `".DB_TABLE_FAHRZEUGE_DATEN."`
                                ON (`".DB_TABLE_FAHRZEUGE."`.`id` = `".DB_TABLE_FAHRZEUGE_DATEN."`.`id`)                               
                               WHERE (`zustand` <= '".FZS_FZGZUSTAND_NUTZBAR."')
                                AND `".DB_TABLE_FAHRZEUGE_DATEN."`.`railcom` = 1
                              ");

$

unset ($DB);

?>
