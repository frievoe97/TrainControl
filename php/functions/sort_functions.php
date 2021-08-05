<?php
ini_set('memory_limit', '1024M');

function startMessage() {
	global $simulationStartTimeToday;
	global $simulationEndTimeToday;
	global $simulationDuration;
	global $realStartTime;
	global $realEndTime;
	global $cacheFahrplanSession;

	$realStartTimeAsHHMMSS = getUhrzeit($realStartTime, "simulationszeit", null, array("outputtyp"=>"h:i:s"));
	$simulationEndTimeAsHHMMSS = getUhrzeit($simulationEndTimeToday, "simulationszeit", null, array("outputtyp"=>"h:i:s"));
	$simulationDurationAsHHMMSS = toStd($simulationDuration);
	$realEndTimeAsHHMMSS = getUhrzeit($realEndTime, "simulationszeit", null, array("outputtyp"=>"h:i:s"));
	$simulationStartTimeAsHHMMSS = getUhrzeit($simulationStartTimeToday, "simulationszeit", null, array("outputtyp"=>"h:i:s"));
	$hashtagLine = "#####################################################################\n";
	$emptyLine = "#\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t#\n";

	echo $hashtagLine;
	echo $emptyLine;
	echo "#\t\t\t  Start der automatischen Zugbeeinflussung\t\t\t\t#\n";
	echo "#\t\tim Eisenbahnbetriebs- und Experimentierfeld (EBuEf) \t\t#\n";
	echo "#\t\t\t\t\t\t    der TU Berlin\t\t\t\t\t\t\t#\n";
	echo "#\t\t\t\t\t    im eingleisigen Netz \t\t\t\t\t\t#\n";
	echo $emptyLine;
	echo "#\t\t\t\t\t\t\t____\t\t\t\t\t\t\t\t\t#\n";
	echo "#\t\t\t\t\t\t\t|DD|____T_\t\t\t\t\t\t\t\t#\n";
	echo "#\t\t\t\t\t\t\t|_ |_____|<\t\t\t\t\t\t\t\t#\n";
	echo "#\t\t\t\t\t\t\t  @-@-@-oo\\\t\t\t\t\t\t\t\t#\n";
	echo "#\t\t\t\t\t=============================\t\t\t\t\t#\n";
	echo $emptyLine;
	echo "#\t Start der Simulation: \t\t\t\t\t\t\t\t\t\t\t#\n";
	echo "#\t\t Simulationszeit: \t\t\t", $simulationStartTimeAsHHMMSS, "\t\t\t\t\t\t#\n";
	echo "#\t\t Realzeit: \t\t\t\t\t", $realStartTimeAsHHMMSS, "\t\t\t\t\t\t#\n";
	echo $emptyLine;
	echo "#\t Ende der Simulation: \t\t\t\t\t\t\t\t\t\t\t#\n";
	echo "#\t\t Simulationszeit: \t\t\t", $simulationEndTimeAsHHMMSS, "\t\t\t\t\t\t#\n";
	echo "#\t\t Realzeit: \t\t\t\t\t", $realEndTimeAsHHMMSS, "\t\t\t\t\t\t#\n";
	echo $emptyLine;
	echo "#\t Dauer der Simulation: \t\t\t", $simulationDurationAsHHMMSS, "\t\t\t\t\t\t#\n";
	echo $emptyLine;
	echo "#\t Fahrplanname: \t\t\t\t\t", $cacheFahrplanSession->name, "\t\t#\n";
	echo "#\t Sessionkey: \t\t\t\t\t", $cacheFahrplanSession->sessionkey, "\t\t\t\t\t#\n";
	echo $emptyLine;
	echo $hashtagLine, "\n\n";
}

function toStd(float $sekunden) {
	$stunden = floor($sekunden / 3600);
	$minuten = floor(($sekunden - ($stunden * 3600)) / 60);
	$sekunden = round($sekunden - ($stunden * 3600) - ($minuten * 60));

	if ($stunden <= 9) {
		$strStunden = "0" . $stunden;
	} else {
		$strStunden = $stunden;
	}

	if ($minuten <= 9) {
		$strMinuten = "0" . $minuten;
	} else {
		$strMinuten = $minuten;
	}

	if ($sekunden <= 9) {
		$strSekunden = "0" . $sekunden;
	} else {
		$strSekunden = $sekunden;
	}

	return "$strStunden:$strMinuten:$strSekunden";
}

function getAllAdresses () : array {

	$zustand = array("0", "1");
	//$zustand = array("0", "1", "2");
	echo "Alle Züge, die den Zustand ", implode(", ", $zustand), " haben, werden eingelesen.\n\n";
	$returnAdresses = array();
	$DB = new DB_MySQL();
	$adresses = $DB->select("SELECT `".DB_TABLE_FAHRZEUGE."`.`adresse`,
										`".DB_TABLE_FAHRZEUGE."`.`zustand`
                            FROM `".DB_TABLE_FAHRZEUGE."`
                           ");
	unset($DB);

	foreach ($adresses as $adressIndex => $adressValue) {
		if (in_array($adressValue->zustand, $zustand)) {
			array_push($returnAdresses, (int) $adressValue->adresse);
		}
	}

	return $returnAdresses;
}

function getAllTrains () : array {
	// TODO: v_max nicht angegeben...
	global $cacheAdresseToID;
	global $cacheIDToAdresse;
	global $globalMinSpeed;

	$allAdresses = getAllAdresses();
	$DB = new DB_MySQL();
	$allTrains = array();
	$id = null;

	// DB_TABLE_FAHRZEUGE_DATEN

	foreach ($allAdresses as $adress) {
		$train_fahrzeuge = get_object_vars($DB->select("SELECT `".DB_TABLE_FAHRZEUGE."`.`id`, 
							`".DB_TABLE_FAHRZEUGE."`.`adresse`,
							`".DB_TABLE_FAHRZEUGE."`.`speed`, 
							`".DB_TABLE_FAHRZEUGE."`.`dir`, 
							`".DB_TABLE_FAHRZEUGE."`.`zugtyp`, 
							`".DB_TABLE_FAHRZEUGE."`.`zuglaenge`,
							`".DB_TABLE_FAHRZEUGE."`.`verzoegerung`, 
							`".DB_TABLE_FAHRZEUGE."`.`zustand`        
                            FROM `".DB_TABLE_FAHRZEUGE."`
                            WHERE `".DB_TABLE_FAHRZEUGE."`.`adresse` = $adress
                           ")[0]);

		$id = $train_fahrzeuge["id"];


		$train_daten = $DB->select("SELECT `".DB_TABLE_FAHRZEUGE_DATEN."`.`baureihe`
                            FROM `".DB_TABLE_FAHRZEUGE_DATEN."`
                            WHERE `".DB_TABLE_FAHRZEUGE_DATEN."`.`id` = $id
                           ")[0]->baureihe;


		$train_baureihe = $DB->select("SELECT `".DB_TABLE_FAHRZEUGE_BAUREIHEN."`.`vmax`
                            FROM `".DB_TABLE_FAHRZEUGE_BAUREIHEN."`
                            WHERE `".DB_TABLE_FAHRZEUGE_BAUREIHEN."`.`nummer` = $train_daten
                           ");

		if (sizeof($train_baureihe) != 0) {
			$train_baureihe_return["v_max"] = intval($train_baureihe[0]->vmax);
		} else {
			$train_baureihe_return["v_max"] = $globalMinSpeed;
		}

		$id = intval($train_fahrzeuge["id"]);
		$cacheAdresseToID[intval($train_fahrzeuge["adresse"])] = intval($id);
		$returnArray = array_merge($train_fahrzeuge, $train_baureihe_return);
		$allTrains[$id] = $returnArray;
	}

	unset($DB);
	$cacheIDToAdresse = array_flip($cacheAdresseToID);
	return $allTrains;
}

function findTrainsOnTheTracks () {

	global $allTrainsOnTheTrack;

	$DB = new DB_MySQL();
	$foundTrains = $DB->select("SELECT DISTINCT `".DB_TABLE_FMA."`.`decoder_adresse`  
                            FROM `".DB_TABLE_FMA."`
                            WHERE `".DB_TABLE_FMA."`.`decoder_adresse` IS NOT NULL
                            AND `".DB_TABLE_FMA."`.`decoder_adresse` <> '"."0"."'
                           ");
	unset($DB);

	foreach ($foundTrains as $train) {
		if (!in_array($train->decoder_adresse, $allTrainsOnTheTrack)) {
			array_push($allTrainsOnTheTrack, intval($train->decoder_adresse));
			// Prepare train for the ride
			prepareTrainForRide($train->decoder_adresse);
		}
	}
}

function prepareTrainForRide(int $adresse) {

	global $allUsedTrains;
	global $allTrains;
	global $cacheAdresseToID;
	global $cacheFmaToInfra;
	global $cacheInfraToFma;
	global $cacheZwischenhaltepunkte;
	global $cacheInfraLaenge;
	global $globalNotverzoegerung;

	$trainID = $cacheAdresseToID[$adresse];
	$zugID = null;
	$keysZwischenhalte = array_keys($cacheZwischenhaltepunkte);
	$allUsedTrains[$trainID]["id"] = $allTrains[$trainID]["id"];
	$allUsedTrains[$trainID]["adresse"] = $allTrains[$trainID]["adresse"];
	$allUsedTrains[$trainID]["zug_id"] = null;
	$allUsedTrains[$trainID]["verzoegerung"] = floatval($allTrains[$trainID]['verzoegerung']);
	$allUsedTrains[$trainID]["notverzoegerung"] = $globalNotverzoegerung;
	$allUsedTrains[$trainID]["zuglaenge"] = $allTrains[$trainID]["zuglaenge"];
	$allUsedTrains[$trainID]["v_max"] = $allTrains[$trainID]["v_max"];
	$allUsedTrains[$trainID]["dir"] = $allTrains[$trainID]["dir"];
	$allUsedTrains[$trainID]["error"] = array();
	$allUsedTrains[$trainID]["operates_on_timetable"] = false;
	$allUsedTrains[$trainID]["fahrstrasse_is_correct"] = false;
	$allUsedTrains[$trainID]["current_speed"] = intval($allTrains[$trainID]["speed"]);
	$allUsedTrains[$trainID]["current_position"] = null;
	$allUsedTrains[$trainID]["current_section"] = null;
	$allUsedTrains[$trainID]["next_sections"] = array();
	$allUsedTrains[$trainID]["next_lenghts"] = array();
	$allUsedTrains[$trainID]["next_v_max"] = array();
	//$allUsedTrains[$trainID]["next_stop"] = array(); // TODO: Wird das benötigt?
	$allUsedTrains[$trainID]["next_betriebsstellen_data"] = array();
	$allUsedTrains[$trainID]["next_bs"] = '';
	$allUsedTrains[$trainID]["earliest_possible_start_time"] = null;

	// Check for errors
	if (!($allUsedTrains[$trainID]["zuglaenge"] > 0)) {
		array_push($allUsedTrains[$trainID]["error"], 1);
	}

	if (!isset($allUsedTrains[$trainID]["v_max"])) {
		array_push($allUsedTrains[$trainID]["error"], 2);
	}

	// Get position
	$fma = getPosition($adresse);
	if (sizeof($fma) == 0) {
		// TODO: Kann das weg?
		$allUsedTrains[$trainID]["current_fma_section"] = null;
		$allUsedTrains[$trainID]["current_section"] = null;
	} elseif (sizeof($fma) == 1) {
		$allUsedTrains[$trainID]["current_fma_section"] = $fma[0];
		$allUsedTrains[$trainID]["current_section"] = $cacheFmaToInfra[$fma[0]];
	} else {
		$infraArray = array();
		foreach ($fma as $value) {
			array_push($infraArray, $cacheFmaToInfra[$value]);
		}
		$infra = getFrontPosition($infraArray, $allTrains[$trainID]["dir"]);
		$allUsedTrains[$trainID]["current_fma_section"] = $cacheInfraToFma[$infra];
		$allUsedTrains[$trainID]["current_section"] = $infra;
	}

	$allUsedTrains[$trainID]["current_position"] = $cacheInfraLaenge[$allUsedTrains[$trainID]["current_section"]];
	// Get Zug ID/Check for timetable
	$timetableIDs = getFahrzeugZugIds(array($trainID));
	if (sizeof($timetableIDs) != 0) {
		$timetableID = $timetableIDs[array_key_first($timetableIDs)];
		$allUsedTrains[$trainID]["zug_id"] = intval($timetableID["zug_id"]);
		$zugID = intval($timetableID["zug_id"]);
		$allUsedTrains[$trainID]["operates_on_timetable"] = true;

	} else {
		$allUsedTrains[$trainID]["zug_id"] = null;
		$allUsedTrains[$trainID]["operates_on_timetable"] = false;
	}

	// Get timetable data
	if (isset($zugID)) {
		$nextBetriebsstellen = getNextBetriebsstellen($zugID);
	}


	if ($zugID != null && sizeof($nextBetriebsstellen) != 0) {
		for ($i = 0; $i < sizeof($nextBetriebsstellen); $i++) {
			if (sizeof(explode("_", $nextBetriebsstellen[$i])) != 2) {
				$allUsedTrains[$trainID]["next_betriebsstellen_data"][$i]["is_on_fahrstrasse"] = false;
				$allUsedTrains[$trainID]["next_betriebsstellen_data"][$i]["betriebstelle"] = $nextBetriebsstellen[$i];
				$allUsedTrains[$trainID]["next_betriebsstellen_data"][$i]["zeiten"] = getFahrplanzeiten($nextBetriebsstellen[$i], $zugID);
				$allUsedTrains[$trainID]["next_betriebsstellen_data"][$i]["fahrplanhalt"] = true;
			} else if(in_array($nextBetriebsstellen[$i], $keysZwischenhalte)) {
				$allUsedTrains[$trainID]["next_betriebsstellen_data"][$i]["is_on_fahrstrasse"] = false;
				$allUsedTrains[$trainID]["next_betriebsstellen_data"][$i]["betriebstelle"] = $nextBetriebsstellen[$i];
				$allUsedTrains[$trainID]["next_betriebsstellen_data"][$i]["zeiten"] = getFahrplanzeiten($nextBetriebsstellen[$i], $zugID);
				$allUsedTrains[$trainID]["next_betriebsstellen_data"][$i]["fahrplanhalt"] = false;
			}
		}
		$allUsedTrains[$trainID]["next_betriebsstellen_data"] = array_values($allUsedTrains[$trainID]["next_betriebsstellen_data"]);
	} else {
		$allUsedTrains[$trainID]["next_betriebsstellen_data"] = array();
	}

	foreach ($allUsedTrains[$trainID]["next_betriebsstellen_data"] as $betriebsstelleKey => $betriebsstelleValue) {
		if ($allUsedTrains[$trainID]["next_betriebsstellen_data"][$betriebsstelleKey]["zeiten"]["abfahrt_soll"] != null) {
			$allUsedTrains[$trainID]["next_betriebsstellen_data"][$betriebsstelleKey]["zeiten"]["abfahrt_soll_timestamp"] = getUhrzeit($betriebsstelleValue["zeiten"]["abfahrt_soll"], "simulationszeit", null, array("inputtyp" => "h:i:s"));
		} else {
			$allUsedTrains[$trainID]["next_betriebsstellen_data"][$betriebsstelleKey]["zeiten"]["abfahrt_soll_timestamp"] = null;
		}
		if ($allUsedTrains[$trainID]["next_betriebsstellen_data"][$betriebsstelleKey]["zeiten"]["ankunft_soll"] != null) {
			$allUsedTrains[$trainID]["next_betriebsstellen_data"][$betriebsstelleKey]["zeiten"]["ankunft_soll_timestamp"] = getUhrzeit($betriebsstelleValue["zeiten"]["ankunft_soll"], "simulationszeit", null, array("inputtyp" => "h:i:s"));
		} else {
			$allUsedTrains[$trainID]["next_betriebsstellen_data"][$betriebsstelleKey]["zeiten"]["ankunft_soll_timestamp"] = null;
		}
		$allUsedTrains[$trainID]["next_betriebsstellen_data"][$betriebsstelleKey]["zeiten"]["verspaetung"] = 0;
	}
}

function getPosition(int $adresse) {

	$returnPosition = array();
	$DB = new DB_MySQL();
	$position = $DB->select("SELECT `".DB_TABLE_FMA."`.`fma_id`
                                            FROM `".DB_TABLE_FMA."`
                                            WHERE `".DB_TABLE_FMA."`.`decoder_adresse` = $adresse
                                            ");
	unset($DB);

	if (sizeof($position) != 0) {
		for ($i = 0; $i < sizeof($position); $i++) {
			array_push($returnPosition, intval(get_object_vars($position[$i])["fma_id"]));
		}
	}

	return $returnPosition;
}

function getFrontPosition(array $infra, int $dir) : int {

	foreach ($infra as $section) {
		$nextSections = array();
		$test = getNaechsteAbschnitte($section, $dir);
		foreach ($test as $value) {
			array_push($nextSections, $value["infra_id"]);
		}
		if (sizeof(array_intersect($infra, $nextSections)) == 0) {
			return $section;
		}
	}

	return false;
}

function getNextBetriebsstellen (int $id) : array {

	$DB = new DB_MySQL();
	$returnBetriebsstellen = array();

	$betriebsstellen = $DB->select("SELECT `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`betriebsstelle`
                            FROM `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`
                            WHERE `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`zug_id` = $id
                            ORDER BY `".DB_TABLE_FAHRPLAN_SESSIONFAHRPLAN."`.`id` ASC
                           ");

	unset($DB);

	foreach ($betriebsstellen as $betriebsstellenIndex => $betriebsstellenValue) {
		array_push($returnBetriebsstellen, $betriebsstellenValue->betriebsstelle);
	}

	if (sizeof($betriebsstellen) == 0) {
		debugMessage("Zu dieser Zug ID sind keine nächsten Betriebsstellen im Fahrplan vorhanden.");
	}

	return $returnBetriebsstellen;
}

function consoleAllTrainsPositionAndFahrplan($id = false) {
	global $allUsedTrains;

	$checkAllTrains = true;

	if ($id != false) {
		$checkAllTrains = false;
	} else {
		echo "Alle vorhandenen Züge:\n\n";
	}

	foreach ($allUsedTrains as $train) {
		if ($checkAllTrains || $train["id"] == $id) {
			$fahrplan = null;
			$error = null;
			$zugId = null;
			if ($train["operates_on_timetable"]) {
				$fahrplan = "ja";
			} else {
				$fahrplan = "nein";
			}
			if (sizeof($train["error"]) != 0) {
				$error = "ja";
			} else {
				$error = "nein";
			}
			if (!isset($train["zug_id"])) {
				$zugId = '-----';
			} else {
				$zugId = $train["zug_id"];
			}
			echo "Zug ID: ", $train["id"], " (Adresse: ", $train["adresse"], ", Zug ID: ", $zugId, ")\t Fährt nach Fahrplan: ",
			$fahrplan, "\t Fahrtrichtung: ", $train["dir"], "\t Infra-Abschnitt: ", $train["current_section"],
			"\t\tAktuelle relative Position im Infra-Abschnitt: ", $train["current_position"], "m\t\tFehler vorhanden:\t", $error, "\n";
		}
	}

	echo "\n";
}

function consoleCheckIfStartDirectionIsCorrect($id = false) {
	global $allUsedTrains;

	$checkAllTrains = true;

	if ($id != false) {
		$checkAllTrains = false;
	}

	echo "Für den Fall, dass die Fahrtrichtung der Züge nicht mit dem Fahrplan übereinstimmt, wird die Richtung verändert:\n\n";
	foreach ($allUsedTrains as $train) {
		if ($checkAllTrains || $train["id"] == $id) {
			if ($train["operates_on_timetable"]) {
				if ($train["dir"] != $train["next_betriebsstellen_data"][0]["zeiten"]["fahrtrichtung"][1]) {
					changeDirection($train["id"]);
				}
			}
		}
	}
	echo "\n";
}

function changeDirection (int $id) {

	global $allUsedTrains;
	global $cacheInfraLaenge;
	global $timeDifference;

	$section = $allUsedTrains[$id]["current_section"];
	$position = $allUsedTrains[$id]["current_position"];
	$direction = $allUsedTrains[$id]["dir"];
	$length = $allUsedTrains[$id]["zuglaenge"];
	$adress = intval($allUsedTrains[$id]["adresse"]);
	$newTrainLength = $length + ($cacheInfraLaenge[$section] - $position);
	$newDirection = null;
	$newSection = null;
	$cumLength = 0;

	if ($direction == 0) {
		$newDirection = 1;
	} else {
		$newDirection = 0;
	}

	$newPosition = null;
	$nextSections = getNaechsteAbschnitte($section, $newDirection);
	$currentData = array(0 => array("laenge" => $cacheInfraLaenge[$section], "infra_id" => $section));
	$mergedData = array_merge($currentData, $nextSections);

	foreach ($mergedData as $sectionValue) {
		$cumLength += $sectionValue["laenge"];
		if ($newTrainLength <= $cumLength) {
			$newSection = $sectionValue["infra_id"];
			$newPosition = $cacheInfraLaenge[$newSection] - ($cumLength - $newTrainLength);
			break;
		}
	}

	if ($newPosition == null) {
		echo "Die Richtung des Zugs mit der ID ", $id, " lässt sich nicht ändern, weil das Zugende auf einem auf Halt stehenden Signal steht.\n";
		echo "\tDie Zuglänge beträgt:\t", $length, " m\n\tDie Distanz zwischen Zugende und dem auf Halt stehenden Signal beträgt:\t", ($cumLength - ($cacheInfraLaenge[$section] - $position)), " m\n\n";
		array_push($allUsedTrains[$id]["error"], 0);
	}  else {
		echo "Die Richtung des Zugs mit der ID: ", $id, " wurde geändert.\n";
		$allUsedTrains[$id]["current_section"] = $newSection;
		$allUsedTrains[$id]["current_position"] = $newPosition;
		$allUsedTrains[$id]["dir"] = $newDirection;
		$allUsedTrains[$id]["earliest_possible_start_time"] = FZS_WARTEZEIT_WENDEN + time() + $timeDifference;
		$DB = new DB_MySQL();
		$DB->select("UPDATE `".DB_TABLE_FAHRZEUGE."`
                            SET `".DB_TABLE_FAHRZEUGE."`.`dir` = $newDirection
                            WHERE `".DB_TABLE_FAHRZEUGE."`.`adresse` = $adress
                           ");
		unset($DB);
	}
}

function showErrors() {

	global $allUsedTrains;
	global $trainErrors;

	$foundError = false;
	echo "Hier werden für alle Züge mögliche Fehler angezeigt:\n\n";

	foreach ($allUsedTrains as $trainIndex => $trainValue) {
		if (sizeof($trainValue["error"]) != 0) {
			$foundError = true;
			echo "Zug ID: ", $trainValue["id"], "\n";
			$index = 1;
			foreach ($trainValue["error"] as $error) {
				echo "\t", $index, ". Fehler:\t", $trainErrors[$error], "\n";
				$index++;
			}
			echo "\n";
		}
	}

	if (!$foundError) {
		echo "Keiner der Züge hat eine Fehlermeldung.\n";
	}
}

// Adds for all trains (if no ID is passed) or for one train (if an ID is passed)
// the stops of the schedule (if the train runs according to schedule)
function addStopsectionsForTimetable($id = false) {

	global $allUsedTrains;
	global $cacheHaltepunkte;
	global $cacheZwischenhaltepunkte;

	$checkAllTrains = true;

	if ($id != false) {
		$checkAllTrains = false;
	}

	foreach ($allUsedTrains as $trainIndex => $trainValue) {
		if ($checkAllTrains || $trainValue["id"] == $id) {
			if (sizeof($trainValue["error"]) == 0) {
				if ($trainValue["operates_on_timetable"]) {
					foreach ($trainValue["next_betriebsstellen_data"] as $betriebsstelleKey => $betriebsstelleValue) {
						if (in_array($betriebsstelleValue["betriebstelle"], array_keys($cacheHaltepunkte))) {
							$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$betriebsstelleKey]["haltepunkte"] = $cacheHaltepunkte[$betriebsstelleValue["betriebstelle"]][$trainValue["dir"]];
						} else if (in_array($betriebsstelleValue["betriebstelle"], array_keys($cacheZwischenhaltepunkte))) {
							$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$betriebsstelleKey]["haltepunkte"] = array($cacheZwischenhaltepunkte[$betriebsstelleValue["betriebstelle"]]);
						} else {
							$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$betriebsstelleKey]["haltepunkte"] = array();
						}
					}
				}
			}
		}
	}
}

function initalFirstLiveData($id = false) {

	global $allUsedTrains;
	global $allTimes;

	$checkAllTrains = true;

	if ($id != false) {
		$checkAllTrains = false;
	}

	foreach ($allUsedTrains as $trainIndex => $trainValue) {
		if (($checkAllTrains || $trainValue["id"] == $id)) {
			$allTimes[$trainValue["adresse"]] = array();
		}
	}
}

// Determines for all trains (if no ID is passed) or for one train
// (if an ID is passed) the route including the lengths, maximum
// allowed speeds and IDs of the next sections.
//
// The results can be stored directly in the $usedTrains array
// ($writeResultToTrain = true) or returned as return
// ($writeResultToTrain = false) so that they can be compared
// with the previous data.
function calculateNextSections($id = false, $writeResultToTrain = true) {

	global $allUsedTrains;
	global $cacheInfraLaenge;
	global $globalSpeedInCurrentSection;
	global $lastMaxSpeedForInfraAndDir;

	$checkAllTrains = true;

	if ($id != false) {
		$checkAllTrains = false;
	}

	foreach ($allUsedTrains as $trainIndex => $trainValue) {
		if (($checkAllTrains || $trainValue["id"] == $id) && sizeof($trainValue["error"]) == 0) {
			$dir = $trainValue["dir"];
			$currentSectionComp = $trainValue["current_section"];
			$signal = getSignalForSectionAndDirection($currentSectionComp, $dir);
			$nextSectionsComp = array();
			$nextVMaxComp = array();
			$nextLengthsComp = array();
			$nextSignalbegriff = null;
			if ($signal != null) {
				$nextSignalbegriff = getSignalbegriff($signal);
				$nextSignalbegriff = $nextSignalbegriff[array_key_last($nextSignalbegriff)]["geschwindigkeit"];
				if ($nextSignalbegriff == -25) {
					$nextSignalbegriff = 25;
				} else if ($nextSignalbegriff < 0) {
					$nextSignalbegriff = 0;
				}
			} else {
				$nextSignalbegriff = null;
			}
			$return = getNaechsteAbschnitte($currentSectionComp, $dir);
			$allUsedTrains[$trainIndex]["last_get_naechste_abschnitte"] = $return;
			if (isset($lastMaxSpeedForInfraAndDir[$trainValue["dir"]][$trainValue["current_section"]])) {
				$currentVMax = $lastMaxSpeedForInfraAndDir[$trainValue["dir"]][$trainValue["current_section"]];
			} else {
				$currentVMax = $globalSpeedInCurrentSection;
			}
			array_push($nextSectionsComp, $currentSectionComp);
			array_push($nextVMaxComp, $currentVMax);
			array_push($nextLengthsComp, $cacheInfraLaenge[$currentSectionComp]);
			if (isset($nextSignalbegriff)) {
				$currentVMax = $nextSignalbegriff;
			}
			if ($currentVMax == 0) {
				if ($writeResultToTrain) {
					$allUsedTrains[$trainIndex]["next_sections"] = $nextSectionsComp;
					$allUsedTrains[$trainIndex]["next_lenghts"] = $nextLengthsComp;
					$allUsedTrains[$trainIndex]["next_v_max"] = $nextVMaxComp;
				} else {
					return array($nextSectionsComp, $nextLengthsComp, $nextVMaxComp);
				}
			} else {
				foreach ($return as $section) {
					array_push($nextSectionsComp, $section["infra_id"]);
					array_push($nextVMaxComp, $currentVMax);
					array_push($nextLengthsComp, $cacheInfraLaenge[$section["infra_id"]]);
					$lastMaxSpeedForInfraAndDir[intval($trainValue["dir"])][intval($section["infra_id"])] = intval($currentVMax);
					if ($section["signal_id"] != null) {
						$signal = $section["signal_id"];
						$nextSignalbegriff = getSignalbegriff($signal);
						$nextSignalbegriff = $nextSignalbegriff[array_key_last($nextSignalbegriff)]["geschwindigkeit"];
						if ($nextSignalbegriff == -25) {
							$currentVMax = 25;
						} else if ($nextSignalbegriff < 0) {
							$currentVMax = 0;
						} else {
							$currentVMax = $nextSignalbegriff;
						}
					}
				}
				if ($writeResultToTrain) {
					$allUsedTrains[$trainIndex]["next_sections"] = $nextSectionsComp;
					$allUsedTrains[$trainIndex]["next_lenghts"] = $nextLengthsComp;
					$allUsedTrains[$trainIndex]["next_v_max"] = $nextVMaxComp;
				} else {
					return array($nextSectionsComp, $nextLengthsComp, $nextVMaxComp);
				}
			}
		}
	}
}

// Determines the associated signal (if there is one) for a section and a direction.
function getSignalForSectionAndDirection(int $section, int $dir) {

	$DB = new DB_MySQL();
	$signal = $DB->select("SELECT `".DB_TABLE_SIGNALE_STANDORTE."`.`id`    
                            FROM `".DB_TABLE_SIGNALE_STANDORTE."`
                            WHERE `".DB_TABLE_SIGNALE_STANDORTE."`.`freimelde_id` = $section
                            AND `".DB_TABLE_SIGNALE_STANDORTE."`.`wirkrichtung` = $dir
                           ");

	unset($DB);
	if ($signal != null) {
		$signal = intval(get_object_vars($signal[0])["id"]);
	}
	return $signal;
}

// TODO: Wird die Funktion benötigt?
/*
function addNextStopForAllTrains($id = false) {
	global $allUsedTrains;
	// TODO: Wenn schon next stops vorhanden sind, müssen die entferrnt werden!
	$checkAllTrains = true;
	if ($id != false) {
		$checkAllTrains = false;
	}
	foreach ($allUsedTrains as $trainIndex => $trainValue) {
		if (($checkAllTrains || $trainValue["id"] == $id) && sizeof($trainValue["error"]) == 0 && $trainValue["operates_on_timetable"]) {
			$index = 0;
			$doThis = true;
			foreach ($trainValue["next_betriebsstellen_data"] as $betriebsstellenIndex => $betriebsstellenData) {
				$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$betriebsstellenIndex]["used_haltepunkt"] = null;
				$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$betriebsstellenIndex]["zeiten"]["verspaetung"] = 0;
				$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$betriebsstellenIndex]["angekommen"] = false;
				$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$betriebsstellenIndex]["is_on_fahrstrasse"] = false;
				$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$betriebsstellenIndex]["is_on_singletrack"] = true;
				if ($doThis) {
					$allUsedTrains[$trainIndex]["next_stop"][$index]["betriebstelle"] = $betriebsstellenData["betriebstelle"];
					$allUsedTrains[$trainIndex]["next_stop"][$index]["ankunft"] = $betriebsstellenData["zeiten"]["ankunft_soll_timestamp"];
					$allUsedTrains[$trainIndex]["next_stop"][$index]["abfahrt"] = $betriebsstellenData["zeiten"]["abfahrt_soll_timestamp"];
					$allUsedTrains[$trainIndex]["next_stop"][$index]["haltzeit"] = $betriebsstellenData["zeiten"]["haltezeit"];
					$allUsedTrains[$trainIndex]["next_stop"][$index]["infra_sections"] = $betriebsstellenData["haltepunkte"];
					$allUsedTrains[$trainIndex]["next_stop"][$index]["is_on_fahrstrasse"] = null;
				}
				if ($betriebsstellenData["zeiten"]["ist_durchfahrt"] == 0) {
					$doThis = false;
				}
				$index++;
			}
		}
	}
}
*/

// Checks for all trains (no ID passed) or for one train (one ID passed)
// whether the train is already at the first scheduled stop or not.
function checkIfTrainReachedHaltepunkt ($id = false) {

	global $allUsedTrains;
	global $cacheInfraToGbt;
	global $cacheGbtToInfra;

	$checkAllTrains = true;
	if ($id != false) {
		$checkAllTrains = false;
	}
	foreach ($allUsedTrains as $trainIndex => $trainValue) {
		if ($checkAllTrains || $trainValue["id"] == $id) {
			$currentInfrasection = $trainValue["current_section"];
			$currentGbt = $cacheInfraToGbt[$currentInfrasection];
			$allInfraSections = $cacheGbtToInfra[$currentGbt];
			if (sizeof(array_intersect($trainValue["next_betriebsstellen_data"][0]["haltepunkte"], $allInfraSections)) != 0) {
				$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][0]["angekommen"] = true;
			} else {
				$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][0]["angekommen"] = false;
			}
			for ($i = 1; $i < sizeof($trainValue["next_betriebsstellen_data"]); $i++) {
				$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$i]["angekommen"] = false;
			}
		}
	}
}

// Checks for all trains (no ID is passed) or for one train (one ID is passed)
// whether the route is currently set correctly so that the next operating
// point can be reached according to the timetable.
//
// For trains without timetable the route is always correct.
function checkIfFahrstrasseIsCorrrect($id = false) {

	global $allUsedTrains;

	$checkAllTrains = true;

	if ($id != false) {
		$checkAllTrains = false;
	}
	foreach ($allUsedTrains as $trainIndex => $trainValue) {
		if (($checkAllTrains || $trainValue["id"] == $id) && sizeof($trainValue["error"]) == 0) {
			if ($trainValue["operates_on_timetable"]) {
				$allUsedTrains[$trainIndex]["fahrstrasse_is_correct"] = false;
				foreach ($trainValue["next_betriebsstellen_data"] as $stopIndex => $stopValue) {
					if (!$stopValue["angekommen"]) {
						$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$stopIndex]["is_on_fahrstrasse"] = false;
						$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$stopIndex]["used_haltepunkt"] = array();
						$indexSection = 0;
						for ($i = 0; $i < sizeof($trainValue["next_sections"]); $i++) {
							if ($stopValue["haltepunkte"] != null) {
								if (in_array($trainValue["next_sections"][$i], $stopValue["haltepunkte"])) {
									if ($i >= $indexSection) {
										$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$stopIndex]["is_on_fahrstrasse"] = true;
										$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$stopIndex]["used_haltepunkt"] = $trainValue["next_sections"][$i];
										$allUsedTrains[$trainIndex]["fahrstrasse_is_correct"] = true;
										$i = sizeof($trainValue["next_sections"]);
										$indexSection = $i;
									}
								}
							}
						}
					} else {
						$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$stopIndex]["is_on_fahrstrasse"] = true;
					}
				}
			} else {
				$allUsedTrains[$trainIndex]["fahrstrasse_is_correct"] = true;
			}
		}
	}
}

// Calculates the acceleration and braking curves for all trains (if no ID is passed)
// or for one train (if an ID is passed). For trains running according to a timetable,
// for all operating points that lie on the currently set route and for trains without
// a timetable up to the next red signal.
function calculateFahrverlauf($id = false) {

	global $allUsedTrains;
	global $cacheInfraLaenge;
	global $timeDifference;
	global $simulationStartTimeToday;
	global $globalFirstHaltMinTime;
	$checkAllTrains = true;
	if ($id != false) {
		$checkAllTrains = false;
	}
	foreach ($allUsedTrains as $trainIndex => $trainValue) {
		$allPossibleStops = array();
		for($i = 0; $i < sizeof($trainValue["next_betriebsstellen_data"]); $i++) {
			if ($trainValue["next_betriebsstellen_data"][$i]["fahrplanhalt"]) {
				array_push($allPossibleStops, $i);
			}
		}
		if (sizeof($trainValue["error"]) == 0 && $trainValue["fahrstrasse_is_correct"]) {
			if ($checkAllTrains || $trainValue["id"] == $id) {
				if ($trainValue["operates_on_timetable"]) {
					$nextBetriebsstelleIndex = null;
					$allreachedInfras = array();
					$wendet = false;
					for ($i = 0; $i < sizeof($trainValue["next_betriebsstellen_data"]); $i++) {
						if (!$trainValue["next_betriebsstellen_data"][$i]["angekommen"] && $trainValue["next_betriebsstellen_data"][$i]["is_on_fahrstrasse"] && $trainValue["next_betriebsstellen_data"][$i]["fahrplanhalt"]) {
							$nextBetriebsstelleIndex = $i;
							$allUsedTrains[$trainIndex]["next_bs"] = $i;
							break;
						}
					}
					if (!isset($nextBetriebsstelleIndex)) {
						for ($i = 0; $i < sizeof($trainValue["next_betriebsstellen_data"]); $i++) {
							if (!$trainValue["next_betriebsstellen_data"][$i]["angekommen"] && $trainValue["next_betriebsstellen_data"][$i]["is_on_fahrstrasse"]) {
								$nextBetriebsstelleIndex = $i;
								break;
							}
						}
					}

					if (isset($nextBetriebsstelleIndex)) {
						if ($allUsedTrains[$trainIndex]["next_bs"] != $trainValue["next_betriebsstellen_data"][$nextBetriebsstelleIndex]["betriebstelle"]) {
							$allUsedTrains[$trainIndex]["next_bs"] = $trainValue["next_betriebsstellen_data"][$nextBetriebsstelleIndex]["betriebstelle"];
							if (intval($trainValue["next_betriebsstellen_data"][$nextBetriebsstelleIndex]["zeiten"]["wendet"]) == 1) {
								$wendet = true;
							}

							for ($i = 0; $i < sizeof($trainValue["next_betriebsstellen_data"]); $i++) {
								if (!$trainValue["next_betriebsstellen_data"][$i]["angekommen"] && $trainValue["next_betriebsstellen_data"][$i]["is_on_fahrstrasse"] && $i <= $nextBetriebsstelleIndex) {
									array_push($allreachedInfras, array("index" => $i, "infra" => $trainValue["next_betriebsstellen_data"][$i]["used_haltepunkt"]));
								}
							}
							$targetSection = $trainValue["next_betriebsstellen_data"][$nextBetriebsstelleIndex]["used_haltepunkt"];
							$targetPosition = $cacheInfraLaenge[$targetSection];
							$startTime = null;
							$endTime = null;

							$prevBetriebsstelle = null;

							for ($i = 0; $i < sizeof($trainValue["next_betriebsstellen_data"]); $i++) {
								if ($trainValue["next_betriebsstellen_data"][$i]["angekommen"]) {
									$prevBetriebsstelle = $i;
									break;
								}
							}

							if ($nextBetriebsstelleIndex == 0) {
								$startTime = microtime(true) + $timeDifference;
								$endTime = $startTime;
							} else {
								$endTime = $trainValue["next_betriebsstellen_data"][$nextBetriebsstelleIndex]["zeiten"]["ankunft_soll_timestamp"];
								if (isset($prevBetriebsstelle)) {
									if ($trainValue["next_betriebsstellen_data"][$prevBetriebsstelle]["zeiten"]["verspaetung"] > 0) {
										$startTime = $trainValue["next_betriebsstellen_data"][$prevBetriebsstelle]["zeiten"]["abfahrt_soll_timestamp"] + $trainValue["next_betriebsstellen_data"][$nextBetriebsstelleIndex - 1]["zeiten"]["verspaetung"];
									} else {
										$startTime = $trainValue["next_betriebsstellen_data"][$prevBetriebsstelle]["zeiten"]["abfahrt_soll_timestamp"];
									}
								} else {
									$startTime = microtime(true) + $timeDifference;
								}
							}
							$reachedBetriebsstele = true;
							if ($startTime < microtime(true) + $timeDifference) {
								$startTime = microtime(true) + $timeDifference;
							}
							if (isset($trainValue["earliest_possible_start_time"])) {
								if ($startTime < $trainValue["earliest_possible_start_time"]) {
									$startTime = $trainValue["earliest_possible_start_time"];
								}
							}
							$verapetung = updateNextSpeed($trainValue, $startTime, $endTime, $targetSection, $targetPosition, $reachedBetriebsstele, $nextBetriebsstelleIndex, $wendet, false, $allreachedInfras);
							if ($nextBetriebsstelleIndex != 0) {
								$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$nextBetriebsstelleIndex]["zeiten"]["verspaetung"] = $verapetung;
								$trainValue["next_betriebsstellen_data"][$nextBetriebsstelleIndex]["zeiten"]["verspaetung"] = $verapetung;
							} else {
								$end = $allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$nextBetriebsstelleIndex]["zeiten"]["abfahrt_soll_timestamp"];
								$start = $simulationStartTimeToday;
								if ($start + $verapetung + $globalFirstHaltMinTime < $end) {
									$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$nextBetriebsstelleIndex]["zeiten"]["verspaetung"] = 0;
									$trainValue["next_betriebsstellen_data"][$nextBetriebsstelleIndex]["zeiten"]["verspaetung"] = 0;
								} else {
									$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$nextBetriebsstelleIndex]["zeiten"]["verspaetung"] = $start + $verapetung + $globalFirstHaltMinTime - $end;
									$trainValue["next_betriebsstellen_data"][$nextBetriebsstelleIndex]["zeiten"]["verspaetung"] = $start + $verapetung + $globalFirstHaltMinTime - $end;
								}
							}
						}
					} else {
						if ($trainValue["current_speed"] > 0) {
							emergencyBreak($trainValue["id"]);
						}
					}


				} else {
					$startTime = microtime(true) + $timeDifference;
					$endTime = $startTime;
					if (isset($trainValue["earliest_possible_start_time"])) {
						if ($startTime < $trainValue["earliest_possible_start_time"]) {
							$startTime = $trainValue["earliest_possible_start_time"];
						}
					}
					$targetSection = null;
					$targetPosition = null;
					$reachedBetriebsstele = true;
					$wendet = false;
					$signalId = null;


					for ($i = 0; $i < sizeof($trainValue["last_get_naechste_abschnitte"]); $i++) {
						if (isset($trainValue["last_get_naechste_abschnitte"][$i]["signal_id"])) {
							$signalId = $trainValue["last_get_naechste_abschnitte"][$i]["signal_id"];
							$targetSection = $trainValue["last_get_naechste_abschnitte"][$i]["infra_id"];
							$targetPosition = $cacheInfraLaenge[$targetSection];
						}
					}

					if (!isset($signalId)) {
						// gibt kein nächstes Signal
						if ($trainValue["current_speed"] == 0) {
							//
						} else {
							// Notbremsung!
							emergencyBreak($trainValue["id"]);
						}

					} else {
						$signal = getSignalbegriff($signalId)[0]["geschwindigkeit"];

						if ($signal > -25 && $signal < 0) {
							$wendet = true;
						}
						updateNextSpeed($trainValue, $startTime, $endTime, $targetSection, $targetPosition, $reachedBetriebsstele, $signalId, $wendet, true, array());
					}
				}
			}
		}
	}
}



function compareTwoNaechsteAbschnitte(int $id) {

	//global $allTrains;
	global $allUsedTrains;
	global $allTimes;

	if (sizeof($allUsedTrains[$id]["error"]) == 0) {
		$newData = calculateNextSections($id, false);
		$newNextSection = $newData[0];
		$newNextLenghts = $newData[1];
		$oldNextSections = $allUsedTrains[$id]["next_sections"];
		$oldLenghts = $allUsedTrains[$id]["next_lenghts"];
		$oldNextVMax = $allUsedTrains[$id]["next_v_max"];
		$currentSectionOld = $allUsedTrains[$id]["current_section"];

		$keyCurrentSection = array_search($currentSectionOld, $oldNextSections);
		$keyLatestSection = array_key_last($oldNextSections);
		$dataIsIdentical = true;
		$numberOfSection = $keyLatestSection - $keyCurrentSection + 1;

		$compareNextSections = array();
		$compareNextLenghts = array();
		$compareNextVMax = array();

		for($i = $keyCurrentSection; $i <= $keyLatestSection; $i++) {
			array_push($compareNextSections, $oldNextSections[$i]);
			array_push($compareNextLenghts, $oldLenghts[$i]);
			array_push($compareNextVMax, $oldNextVMax[$i]);
		}
		if (sizeof($newNextSection) != ($numberOfSection)) {
			$dataIsIdentical = false;
		} else {
			for ($i = 0; $i < $keyLatestSection - $keyCurrentSection; $i++) {
				if ($newNextSection[$i] != $compareNextSections[$i] || $newNextLenghts[$i] != $compareNextLenghts[$i]) {
					$dataIsIdentical = false;
					break;
				}
			}
		}

		if (!$dataIsIdentical) {
			echo "Die Fahrstraße des Zuges mit der ID: ", $id, " hat sich geändert.\n";
			calculateNextSections($id);
			$adresse = $allUsedTrains[$id]["adresse"];
			$allTimes[$adresse] = array();
			checkIfFahrstrasseIsCorrrect($id);
			calculateFahrverlauf($id);
		}
	}
}