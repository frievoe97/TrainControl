<?php




function getFahrzeugimAbschnitt_test (array $data, int $gbt_id) {
    for ($i = 0; $i < count($data); $i++) {
        if ($data[$i]->gbt_id == $gbt_id)
            $ret = intval($data[$i]->zugnummer);
    }
    return $ret;
}



// Ermittelt den Signalbegriff für die Fahrzeugsteuerung
function fzs_getSignalbegriff_test(array $abschnittdaten) {

    // $haltfall_id = "T";
    // $signalstandortid = $abschnittdaten->signalstandortid;

    var_dump($abschnittdaten);



    // $abschnittdaten kommen aus der vorbelegung.php, relevant hier "haltfall_id" und als Pflichtfeld "signalstandortid".
    // return $signalbegriff; // darin [0]["geschwindigkeit"] relevant;
}


// Ermittelt den Signalbegriff für die Fahrzeugsteuerung
function testi() {
    // $abschnittdaten kommen aus der vorbelegung.php, relevant hier "haltfall_id" und als Pflichtfeld "signalstandortid".

    echo "Test";



}
