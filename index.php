<?php

require 'vorbelegung.php';
require 'functions.php';


//fzs_getSignalbegriff((array) $alle_abschnitte[0]);

//var_dump(fzs_getSignalbegriff((array) $alle_abschnitte[1]));

//var_dump($alle_abschnitte[1]);

$fahrzeugdaten = [
	"gbt_id" => "601",
	"decoder_adresse" => "",
	"id" => "",
];

var_dump(getFahrzeugdaten($fahrzeugdaten, "dir_speed"));

?>