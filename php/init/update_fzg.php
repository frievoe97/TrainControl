<?php

function updateFzgNextSpeedChange (array $allTrains, int $key, int $nextSpeed, int $nextSection, int $nextPositiomn, int $nextTime) {

	$allTrains[$key]["next_speed_change_speed"] = $nextSpeed;
	$allTrains[$key]["next_speed_change_section"] = $nextSection;
	$allTrains[$key]["next_speed_change_position"] = $nextPositiomn;
	$allTrains[$key]["next_speed_change_time"] = $nextTime;

	return $allTrains[$key];
}


function updateNextSpeed (array $allTrains, int $key, array $value) {

	$verzoegerung = $value["verzoegerung"];
	$section = $value["section"];
	$position = $value["position"];
	$speed = $value["speed"];

	$nextSpeed = $value["next_speed_change_speed"];
	$nextSection = $value["next_speed_change_section"];
	$nextPosition = $value["next_speed_change_position"];
	$nextTime = $value["next_speed_change_time"];

	$v_2 = null;
	$time_2 = null;
	$position_2 = null;
	$section_2 = null;

	// Step 1: Get full distance, time, and length
	$totalLength = getBrakeDistance($speed, $nextSpeed);
	$totalTime = getBrakeTime($speed, $nextSpeed);

	$startPosition = $nextPosition - $totalLength;
	$startTime = $nextTime - $totalTime;



	for ($v_1 = $speed; $v_1 >= ($nextSpeed + 2); $v_1 = $v_1 - 2) {

	}




	// TODO: bremsweg, aktuelleGeschwindigkeit - nächste Geschwindigkeit / 2 => Anzahl an Werten
	// Gesamter Bremsweg, Ziel minus gesamter Bremsweg = Start
	// Beim Start beginnen
	// For-loop over start und ende in zweier Schritten
	// Funktion: v_1, v_2, position, Startzeit bzw. nächste Zeit
	// In Var abschpeichern, was im Schritt davor passiert ist (neue v_1, neue Zeit, neue Position, neuer Abschnitt

	//var_dump($value);

}

// Anpassen für viele Schritte => $a bleibt konstant?! => Eher nicht anpassen und allgemein halten
function getBrakeDistance (float $v_0, float $v_1) {

	$mu = 0.1;
	$g = 9.81;

	// v in km/h
	// a in m/s^2
	// return in m
	// TODO: Wie sieht es mit der Reaktionszeit aus? (Wenn ja, dann nur bei der Ersten 2 km/h_diff Bremsung

	//return $bremsweg = ((($v_0-$v_1)*3.6)*$t_reac)+((pow((($v_0*3.6)), 2)-pow((($v_1*3.6)), 2))/(2*($a+(9.81/1000))));
	//return $bremnsweg = ((pow(($v_0 * 3.6),2))/(2 * $a)) - ((pow(($v_1 * 3.6),2))/(2 * $a));

	return $bremsweg = 0.5 * $mu * $g * ((pow($v_0/3.6,2)-pow($v_1/3.6, 2))/(pow($mu, 2)*pow($g, 2)));


}

function getBrakeTime (float $v_0, float $v_1) {

	$mu = 0.1;
	$g = 9.81;

	return $breakTime = ($v_0-$v_1)/($mu*$g);

}