<?php

require 'vorbelegung.php';
require 'functions.php';
require 'prepare/prepare_fahrplan_session.php';
require 'prepare/prepare_fahrplan_aktuell.php';
require 'update/update_fahrzeuge_aktuell.php';

$fahrzeugdaten = [
	"gbt_id" => "601",
	"decoder_adresse" => "",
	"id" => "",
];

// var_dump(getFahrzeugdaten($fahrzeugdaten, "id"));
// var_dump(getFahrzeugimInfraAbschnitt(1186));

//deleteFahrzeugeAktuell();
//insertFahrzeugeAktuell();

nextSpeedPositionFahrzeugeAktuell(67934, 0, 100000, 1610724600);
nextSpeedPositionFahrzeugeAktuell(89342, 0, 200000, 1610724600);
nextSpeedPositionFahrzeugeAktuell(92678, 0, 150000, 1610724600);

/*
while (1) {
	//checkChangeSpeed();
	//updateFahrzeugeAktuell();
}
*/





// TODO:
// Wenn ein Zug seine Geschwindigkeit ändert, muss fahrzeuge_aktuell auch geändert werden und aktuallisiert werden





?>