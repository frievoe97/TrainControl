<?php

require 'vorbelegung.php';
require 'functions.php';

$fahrzeugdaten = [
	"gbt_id" => "601",
	"decoder_adresse" => "",
	"id" => "",
];

var_dump(getFahrzeugdaten($fahrzeugdaten, "id"));


var_dump(getFahrzeugimInfraAbschnitt(1186))

?>