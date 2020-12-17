<?php

require 'vorbelegung.php';
//require 'functions.php';
//require 'test_functions.php';
require 'funktionen_fahrzeug.php';




function getBremsweg(float $v_0, float $v_1, float $t_reac, float $a) {
    return $bremsweg = ((($v_0-$v_1)/3.6)*$t_reac)+((pow((($v_0)/3.6), 2)-pow((($v_1)/3.6), 2))/(2*($a+(9.81/1000))));
}

function getSpeedPerTime(float $v_0, float $v_1, float $t_reac, float $a, int $timeInter) {

}




function getCurrentPosition(float $v_0, int $time_0, int $time_1, float $pos_0) {
    $time_diff = $time_1 - $time_0;
    return ($v_0/3.6)*$time_diff+$pos_0;
    // What if a is not constant?
}



//echo getBremsweg(100, 50, 1, 0.8);

//echo "\n";

//echo getCurrentPosition(360, 100, 200, 0);

var_dump($alle_abschnitte);




//var_dump($alle_fahrzeuge);
//var_dump($alle_fahrzeuge);





?>