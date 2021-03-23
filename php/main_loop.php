<?php

// Info zum Programmstart (landet auch später im Logfile)
echo "EBuEf-Fahrzeugsteuerung | Steuerungsprozess\n";
flush();

$u = 0;
$grund = "";

// Endlosschleife
do {

// ...
// Aufruf der einzelnen Unterprozesse
// ...

	// Prüfung, ob die Fahrplansession noch aktuell ist, wenn nicht, dann wird das Skript beendet, damit es von SYSTEMD wieder neugestartet wird
	$pruefergebnis = checkFahrplansession();
	$u = $pruefergebnis["u"];
	$grund = $pruefergebnis["grund"];
} while ($u == 0); // Endlosschleife

echo "FZS-Steuerungsprozess beendet. ";
echo $grund;


//Und die verkürzte Funktion, die da aufgerufen wird zur Prüfung:

function checkFahrplansession () {
	$ergebnis = array("grund" => "", "u" => 0);
	return $ergebnis;
}
