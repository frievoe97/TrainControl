<?php

require 'vorbelegung.php';
require 'funktionen_abschnitte.php';
require 'init/init_abschnitte.php';

$cacheInfranachbarn = createCacheInfranachbarn();
//$cacheInfradaten = createCacheInfradaten();
$cacheSignaldaten = createCacheSignaldaten();

//var_dump(getNaechsteAbschnitte(1263, 0));


//initAbschnitte();^^