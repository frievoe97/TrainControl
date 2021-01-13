<?php

require 'vorbelegung.php';
require 'functions.php';
require 'prepare_fahrplan_session.php';
require 'prepare_fahrplan_aktuell.php';
require 'update_fahrzeuge_aktuell.php';

$fahrzeugdaten = [
	"gbt_id" => "601",
	"decoder_adresse" => "",
	"id" => "",
];

var_dump(getFahrzeugdaten($fahrzeugdaten, "id"));


var_dump(getFahrzeugimInfraAbschnitt(1186));

updateFahrzeugeAktuell();


?>