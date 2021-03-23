<?php

require 'vorbelegung.php';
require 'funktionen_abschnitte.php';
require 'init/init_abschnitte.php';

$cacheInfranachbarn = createCacheInfranachbarn();
$cacheInfradaten = createCacheInfradaten();
$cacheSignaldaten = createCacheSignaldaten();

// ERROR
//var_dump(getNaechsteAbschnitte(153, 1));
var_dump(getNaechsteAbschnitte(354, 1));

//var_dump(getSignalbegriff(89));
//var_dump(getSignalbegriff(91));



// Step 1: Initilize all trains (verzoegerung, laenge etc.)

// Steop 2: Next stop and the sections between the current position and the next stop

// BIG LOOP

	// Step 3: Loop to send the current speed to the train

	// Step 4: Check if the v_max of the next sections has changed