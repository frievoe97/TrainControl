<?php

// General Functions



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

function toStd($sekunden)
{
	$stunden = floor($sekunden / 3600);
	$minuten = floor(($sekunden - ($stunden * 3600)) / 60);
	$sekunden = round($sekunden - ($stunden * 3600) - ($minuten * 60), 0);

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

		$train_baureihe = $DB->select("SELECT `".DB_TABLE_FAHRZEUGE_BAUREIHEN."`.`vmax`
                            FROM `".DB_TABLE_FAHRZEUGE_BAUREIHEN."`
                            WHERE `".DB_TABLE_FAHRZEUGE_BAUREIHEN."`.`nummer` = $adress
                           ");




		if (sizeof($train_baureihe) != 0) {
			$train_baureihe_return["v_max"] = intval($train_baureihe[0]->vmax);
		} else {
			$train_baureihe_return["v_max"] = $globalMinSpeed;
			//$train_baureihe_return["v_max"] = null;
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

function findTrainsOnTheTracks() {

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
	$allUsedTrains[$trainID]["operates_on_timetable"] = null;
	$allUsedTrains[$trainID]["fahrstrasse_is_correct"] = false;

	$allUsedTrains[$trainID]["current_speed"] = intval($allTrains[$trainID]["speed"]);
	$allUsedTrains[$trainID]["current_position"] = null;
	$allUsedTrains[$trainID]["current_section"] = null;

	$allUsedTrains[$trainID]["next_sections"] = array();
	$allUsedTrains[$trainID]["next_lenghts"] = array();
	$allUsedTrains[$trainID]["next_v_max"] = array();
	$allUsedTrains[$trainID]["next_stop"] = array(); // TODO: Wird das benötigt?
	$allUsedTrains[$trainID]["next_betriebsstellen_data"] = array();

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
	$nextBetriebsstellen = getNextBetriebsstellen($zugID);
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

function consoleAllTrainsPositionAndFahrplan() {
	global $allUsedTrains;

	echo "Alle vorhandenen Züge:\n\n";
	foreach ($allUsedTrains as $train) {
		$fahrplan = null;
		$error = null;
		if ($train["operates_on_timetable"] == 1) {
			$fahrplan = "ja";
		} else {
			$fahrplan = "nein";
		}
		if (sizeof($train["error"]) != 0) {
			$error = "ja";
		} else {
			$error = "nein";
		}
		echo "Zug ID: ", $train["id"], " (Adresse: ", $train["adresse"], ", Zug ID:", $train["zug_id"], ")\t Fährt nach Fahrplan: ", $fahrplan, "\t Fahrtrichtung: ", $train["dir"], "\t Infra-Abschnitt: ", $train["current_section"], "\t\tAktuelle relative Position im Infra-Abschnitt: ", $train["current_position"], "m\t\tFehler vorhanden:\t", $error,"\n";
	}
	echo "\n";
}

function consoleCheckIfStartDirectionIsCorrect() {
	global $allUsedTrains;

	echo "Für den Fall, dass die Fahrtrichtung der Züge nicht mit dem Fahrplan übereinstimmt, wird die Richtung verändert:\n\n";
	foreach ($allUsedTrains as $train) {
		if ($train["operates_on_timetable"]) {
			if ($train["dir"] != $train["next_betriebsstellen_data"][0]["zeiten"]["fahrtrichtung"][1]) {
				changeDirection($train["id"]);
			}
		}
	}
	echo "\n";
}

function changeDirection (int $id) {

	// TODO: Add 60 sec sleeptime

	global $allUsedTrains;
	global $cacheInfraLaenge;

	$section = $allUsedTrains[$id]["current_section"];
	$position = $allUsedTrains[$id]["current_position"];
	$direction = $allUsedTrains[$id]["dir"];
	$length = $allUsedTrains[$id]["zuglaenge"];
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
		if (($checkAllTrains || $trainValue["id"] == $id) && sizeof($trainValue["error"]) == 0) {
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

	$checkAllTrains = true;

	if ($id != false) {
		$checkAllTrains = false;
	}

	foreach ($allUsedTrains as $trainIndex => $trainValue) {

		if (($checkAllTrains || $trainValue["id"] == $id) && sizeof($trainValue["error"]) == 0) {
			$dir = $trainValue["dir"];
			$currentSection = $trainValue["current_section"];
			$signal = getSignalForSectionAndDirection($currentSection, $dir);
			$nextSections = array();
			$nextVMax = array();
			$nextLengths = array();
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

			$return = getNaechsteAbschnitte($currentSection, $dir);
			$allUsedTrains[$trainIndex]["last_get_naechste_abschnitte"] = $return;
			$currentVMax = $globalSpeedInCurrentSection; // max speed for a train in the current section

			array_push($nextSections, $currentSection);
			array_push($nextVMax, $currentVMax);
			array_push($nextLengths, $cacheInfraLaenge[$currentSection]);

			if (isset($nextSignalbegriff)) {
				$currentVMax = $nextSignalbegriff;
			}

			if ($currentVMax == 0) {
				if ($writeResultToTrain) {
					$allUsedTrains[$trainIndex]["next_sections"] = $nextSections;
					$allUsedTrains[$trainIndex]["next_lenghts"] = $nextLengths;
					$allUsedTrains[$trainIndex]["next_v_max"] = $nextVMax;
				} else {
					return array($nextSections, $nextLengths, $nextVMax);
				}
			} else {
				foreach ($return as $section) {
					array_push($nextSections, $section["infra_id"]);
					array_push($nextVMax, $currentVMax);
					array_push($nextLengths, $cacheInfraLaenge[$section["infra_id"]]);
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
					$allUsedTrains[$trainIndex]["next_sections"] = $nextSections;
					$allUsedTrains[$trainIndex]["next_lenghts"] = $nextLengths;
					$allUsedTrains[$trainIndex]["next_v_max"] = $nextVMax;
				} else {
					return array($nextSections, $nextLengths, $nextVMax);
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
	global $newTimeDifference;
	global $simulationStartTimeToday;
	global $globalFirstHaltMinTime;
	global $globalIndexBetriebsstelleFreieFahrt;
	global $cacheSignalIDToBetriebsstelle;

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
				/*
				if ($trainValue["id"] == 66) {
					$trainValue["operates_on_timetable"] = false;
				}
				*/
				if ($trainValue["operates_on_timetable"]) {
					$endTime = null;
					$firstBetriebsstelleIndex = null;
					$lastBetriebsstelleIndex = null;
					$nextBetriebsstelleIndex = null;
					$wendet = false;
					$wendetIndex = null;

					for ($i = 0; $i < sizeof($trainValue["next_betriebsstellen_data"]); $i++) {
						if (!$trainValue["next_betriebsstellen_data"][$i]["angekommen"] && $trainValue["next_betriebsstellen_data"][$i]["is_on_fahrstrasse"] && $trainValue["next_betriebsstellen_data"][$i]["fahrplanhalt"]) {
							$nextBetriebsstelleIndex = $i;
							break;
						}
					}

					if ($nextBetriebsstelleIndex == null) {
						for ($i = 0; $i < sizeof($trainValue["next_betriebsstellen_data"]); $i++) {
							if (!$trainValue["next_betriebsstellen_data"][$i]["angekommen"] && $trainValue["next_betriebsstellen_data"][$i]["is_on_fahrstrasse"]) {
								$nextBetriebsstelleIndex = $i;
								break;
							}
						}
					}

					if (intval($trainValue["next_betriebsstellen_data"][$nextBetriebsstelleIndex]["zeiten"]["wendet"]) == 1) {
						$wendet = true;
					}

					$targetSection = $trainValue["next_betriebsstellen_data"][$nextBetriebsstelleIndex]["used_haltepunkt"];
					$targetPosition = $cacheInfraLaenge[$trainValue["next_betriebsstellen_data"][$nextBetriebsstelleIndex]["used_haltepunkt"]];

					/*
					$currentSection = $trainValue["current_section"];
					$currentPosition = $trainValue["current_position"];
					$currentSpeed = $trainValue["current_speed"];
					*/

					$startTime = null;
					$endTime = null;

					if ($nextBetriebsstelleIndex == 0) {
						$startTime = microtime(true) + $newTimeDifference;
						$endTime = $startTime;
					} else {
						$endTime = $trainValue["next_betriebsstellen_data"][$nextBetriebsstelleIndex]["zeiten"]["ankunft_soll_timestamp"];
						if ($trainValue["next_betriebsstellen_data"][$nextBetriebsstelleIndex - 1]["zeiten"]["verspaetung"] > 0) {
							$startTime = $trainValue["next_betriebsstellen_data"][$nextBetriebsstelleIndex - 1]["zeiten"]["abfahrt_soll_timestamp"] + $trainValue["next_betriebsstellen_data"][$nextBetriebsstelleIndex - 1]["zeiten"]["verspaetung"];
						} else {
							$startTime = $trainValue["next_betriebsstellen_data"][$nextBetriebsstelleIndex - 1]["zeiten"]["abfahrt_soll_timestamp"];
						}
					}

					$reachedBetriebsstele = true;

					if ($startTime < microtime(true) + $newTimeDifference) {
						$startTime = microtime(true) + $newTimeDifference;
					}

					//$freieFahrt = false;



					$verapetung = updateNextSpeed($trainValue, $startTime, $endTime, $targetSection, $targetPosition, $reachedBetriebsstele, $nextBetriebsstelleIndex, $wendet, false);

					if ($nextBetriebsstelleIndex != 0) {
						$allUsedTrains[$trainIndex]["next_betriebsstellen_data"][$nextBetriebsstelleIndex]["zeiten"]["verspaetung"] = $verapetung;
						$trainValue["next_betriebsstellen_data"][$nextBetriebsstelleIndex]["zeiten"]["verspaetung"] = $verapetung;
					} else if ($nextBetriebsstelleIndex == 0) {
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
				} else {
					$startTime = microtime(true) + $newTimeDifference;
					$endTime = $startTime;
					//$freieFahrt = true;

					/*
					$currentSection = $trainValue["current_section"];
					$currentPosition = $currentPosition = $trainValue["current_position"];
					$currentSpeed = $trainValue["current_speed"];
					*/

					$targetSection = end($trainValue["next_sections"]);
					$targetPosition = $cacheInfraLaenge[end($trainValue["next_sections"])];

					$reachedBetriebsstele = true;
					$wendet = false;
					// TODO: Get Nächste Abachnitte am besten neu berechnen!
					$signalId = end($trainValue["last_get_naechste_abschnitte"])["signal_id"];
					$signal = getSignalbegriff($signalId)[0]["geschwindigkeit"];
					//$betriebsstelle

					if ($signal > -25 && $signal < 0) {
						$wendet = true;
					}
					updateNextSpeed($trainValue, $startTime, $endTime, $targetSection, $targetPosition, $reachedBetriebsstele, $signalId, $wendet, true);
				}
			}
		}
	}
}

// TODO: current_speed = 0
// TODO: Mindestzeit auf einer Geschwindigkeit bleiben!
function updateNextSpeed (array $train, float $startTime, float $endTime, int $targetSectionPara, int $targetPositionPara, bool $reachedBetriebsstelle, string $targetSignal, bool $wendet, bool $freieFahrt) {

	global $useSpeedFineTuning;

	global $next_sections;
	global $next_lengths;
	global $next_v_max;
	global $allTimes;
	global $verzoegerung;
	global $notverzoegerung;
	global $currentSection;
	global $currentPosition;
	global $currentSpeed;
	global $targetSpeed;
	global $targetSection;
	global $targetPosition;
	global $targetTime;
	global $indexCurrentSection;
	global $indexTargetSection;
	global $distanceToNextStop;
	global $trainSpeedChange;
	global $trainPositionChange;
	global $trainTimeChange;
	global $cumulativeSectionLengthEnd;
	global $cumulativeSectionLengthStart;
	global $keyPoints;
	//global $minTimeForSpeed;
	global $allUsedTrains;
	global $globalIndexBetriebsstelleFreieFahrt;

	global $cacheSignalIDToBetriebsstelle;


	$id_train = $train["id"];

	$emptyArray = array();
	$keyPoints = $emptyArray;
	$cumulativeSectionLengthStart = $emptyArray;
	$cumulativeSectionLengthEnd = $emptyArray;

	$next_sections = $train["next_sections"];
	$next_lengths = $train["next_lenghts"];
	$next_v_max = $train["next_v_max"];

	$verzoegerung = $train["verzoegerung"];
	$notverzoegerung = $train["notverzoegerung"];
	$train_v_max = $train["v_max"];

	$currentSection = $train["current_section"]; //$currentSectionPara;
	$currentPosition = $train["current_position"]; //$currentPositionPara;
	$currentSpeed = $train["current_speed"]; //$currentSpeedPara;

	$targetSpeed = 0;
	// TODO: Diese Zuweisung ist unnötig (oder?)
	$targetSection = $targetSectionPara;
	$targetPosition = $targetPositionPara;
	$targetTime = $endTime;

	$indexCurrentSection = null;
	$indexTargetSection = null;

	$timeToNextStop = null;
	$maxTimeToNextStop = $targetTime - $startTime;

	$maxSpeedNextSections = 120;

	//$freieFahrt = null;
	$targetBetriebsstelle = null;

	$indexReachedBetriebsstelle = $targetSignal;

	if (!$freieFahrt) {
		$targetBetriebsstelle = $train["next_betriebsstellen_data"][$indexReachedBetriebsstelle]["betriebstelle"];
	} else {
		$targetBetriebsstelle = $cacheSignalIDToBetriebsstelle[intval($targetSignal)];
	}

	// Überprüft, ob der Zug bei den Gegebenheiten überhaupt die Mindestzeit auf einem Abschnitt halten kann


	//var_dump($targetBetriebsstelle, $freieFahrt);

	/*

	if ($indexReachedBetriebsstelle == 99999999) {
		$freieFahrt = true;
	} else {
		$freieFahrt = false;
		$targetBetriebsstelle = $train["next_betriebsstellen_data"][$indexReachedBetriebsstelle]["betriebstelle"];
	}
	*/


	// $indexReachedBetriebsstelle = Index der Betriebsstelle


	// TODO: Zug steht schon am Ziel (ist das nötig?)
	if ($targetSection == $currentSection && $targetPosition == $currentPosition) {
		// Freie Fahrt
		if ($indexReachedBetriebsstelle == $globalIndexBetriebsstelleFreieFahrt) {
			$indexReachedBetriebsstelle = -1;
		}
		$adress = $train["adresse"];
		$return = array(array("live_position" => $targetPosition, "live_speed" => $targetSpeed, "live_time" => $endTime, "live_relative_position" => $targetPosition, "live_section" => $targetSection, "live_is_speed_change" => false, "live_target_reached" => $reachedBetriebsstelle, "wendet" => $wendet, "id" => $train["id"], "betriebsstelle_name" => $targetBetriebsstelle));
		$allTimes[$adress] = array_merge($allTimes[$adress], $return);
		return 0;
	}

	// Wenn ein Abschnitt eine Geschwindigkeit zulässt, die größer als die v_max des Zugs ist, wird die Geschwindigkeit auf die v_max des Zuges beschränkt
	if ($train_v_max != null) {
		foreach ($next_sections as $sectionKey => $sectionValue) {
			if ($next_v_max[$sectionKey] > $train_v_max) {
				$next_v_max[$sectionKey] = $train_v_max;
			}
		}
	}

	// Index des Start- und Zielabschnitts
	foreach ($next_sections as $sectionKey => $sectionValue) {
		if ($sectionValue == $currentSection) {
			$indexCurrentSection = $sectionKey;
		}
		if ($sectionValue == $targetSection) {
			$indexTargetSection = $sectionKey;
		}
	}

	$cumLength = array();
	$sum = 0;

	foreach ($next_lengths as $index => $value) {
		$sum += $value;
		$cumLength[$index] = $sum;
	}

	// Berechnung der kummulierten Start- und Endlängen der Abschnitte
	// TODO: Geht das auch, wenn Start- und Zielabschnitt der selbe sind?
	for ($i = $indexCurrentSection; $i <= $indexTargetSection; $i++) {
		if ($indexCurrentSection == $indexTargetSection) {
			$cumulativeSectionLengthStart[$i] = 0;
			$cumulativeSectionLengthEnd[$i] = $targetPosition - $currentPosition;
		} else {
			if ($i == $indexCurrentSection) {
				$cumulativeSectionLengthStart[$i] = 0;
				$cumulativeSectionLengthEnd[$i] = $cumLength[$i] - $currentPosition;
			} else if ($i == $indexTargetSection) {
				$cumulativeSectionLengthStart[$i] = $cumLength[$i - 1] - $currentPosition;
				$cumulativeSectionLengthEnd[$i] = $cumLength[$i - 1] + $targetPosition - $currentPosition;
			} else {
				$cumulativeSectionLengthStart[$i] = $cumLength[$i - 1] - $currentPosition;
				$cumulativeSectionLengthEnd[$i] = $cumLength[$i] - $currentPosition;
			}
		}
	}


	$distanceToNextStop = $cumulativeSectionLengthEnd[$indexTargetSection];


	checkIfItsPossible();




	// Emergency Brake
	// TODO: Wo muss die Notbremsung durchgeführt werden?
	if (getBrakeDistance($currentSpeed, $targetSpeed, $verzoegerung) > $distanceToNextStop && $currentSpeed != 0) {
		echo "Der Zug mit der Adresse: ", $train["adresse"], " leitet jetzt eine Notbremsung ein.\n";
		$returnArray = array();
		$time = $startTime;
		if (getBrakeDistance($currentSpeed, $targetSpeed, $notverzoegerung) <= $distanceToNextStop) {
			for ($i = $currentSpeed; $i >= 0; $i = $i - 2) {
				array_push($returnArray, array("live_position" => 0, "live_speed" => $i, "live_time" => $time, "live_relative_position" => 0, "live_section" => $currentSection, "live_is_speed_change" => true, "live_target_reached" => false, "id" => $train["id"], "wendet" => false, "betriebsstelle_index" => null));
				$time =  $time + getBrakeTime($i, $i - 1, $notverzoegerung);
			}
		} else {
			$targetSpeedNotbremsung =  getTargetBrakeSpeedWithDistanceAndStartSpeed($distanceToNextStop, $notverzoegerung, $currentSpeed);
			$speedBeforeStop = intval($targetSpeedNotbremsung / 2) * 2;
			if ($speedBeforeStop >= 10) {
				for ($i = $currentSpeed; $i >= 10; $i = $i - 2) {
					array_push($returnArray, array("live_position" => 0, "live_speed" => $i, "live_time" => $time, "live_relative_position" => 0, "live_section" => $currentSection, "live_is_speed_change" => true, "live_target_reached" => false, "id" => $train["id"], "wendet" => false, "betriebsstelle_index" => null));
					$time =  $time + getBrakeTime($i, $i - 1, $notverzoegerung);
				}
				array_push($returnArray, array("live_position" => 0, "live_speed" => 0, "live_time" => $time, "live_relative_position" => 0, "live_section" => $currentSection, "live_is_speed_change" => true, "live_target_reached" => false, "id" => $train["id"], "wendet" => false, "betriebsstelle_index" => null));
			} else {
				array_push($returnArray, array("live_position" => 0, "live_speed" => $currentSpeed, "live_time" => $time, "live_relative_position" => 0, "live_section" => $currentSection, "live_is_speed_change" => true, "live_target_reached" => false, "id" => $train["id"], "wendet" => false, "betriebsstelle_index" => null));
				$time =  $time + getBrakeTime($currentSpeed, $currentSpeed - 1, $notverzoegerung);
				array_push($returnArray, array("live_position" => 0, "live_speed" => 0, "live_time" => $time, "live_relative_position" => 0, "live_section" => $currentSection, "live_is_speed_change" => true, "live_target_reached" => false, "id" => $train["id"], "wendet" => false, "betriebsstelle_index" => null));
			}
		}

		$allTimes[$train["adresse"]] = $returnArray;
		$allUsedTrains[$train["id"]]["can_drive"] = false;




		return 0;

	}

	//var_dump(max(array()));

	//$v_maxFirstIteration = null;
	$v_maxFirstIteration = getVMaxBetweenTwoPoints($distanceToNextStop, $currentSpeed, $targetSpeed);

	// Anpassung an die maximale Geschwindigkeit auf der Strecke
	for ($i = $indexCurrentSection; $i <= $indexTargetSection; $i++) {
		if ($next_v_max[$i] < $maxSpeedNextSections) {
			$maxSpeedNextSections = $next_v_max[$i];
		}
	}

	if ($maxSpeedNextSections < $v_maxFirstIteration) {
		$v_maxFirstIteration = $maxSpeedNextSections;
	}

	// Key Points für die erste Iteration erstellen.
	array_push($keyPoints, createKeyPoint(0, getBrakeDistance($currentSpeed, $v_maxFirstIteration, $verzoegerung), $currentSpeed, $v_maxFirstIteration));
	array_push($keyPoints, createKeyPoint(($distanceToNextStop - getBrakeDistance($v_maxFirstIteration, $targetSpeed, $verzoegerung)), $distanceToNextStop, $v_maxFirstIteration, $targetSpeed));

	//function keyPoints => trainChangeArrays
	$trainChange = convertKeyPointsToTrainChangeArray($keyPoints);
	$trainPositionChange = $trainChange[0];
	$trainSpeedChange = $trainChange[1];

	/*



	//function: trainChangeData => JSON file
	// TODO: JSON Funktion erst am Ende einmalig aufrufen
	safeTrainChangeToJSONFile();
	//safe $cumulativeSectionLength Data to JSON file
	$v_maxFromUsedSections = array();
	for ($i = $indexCurrentSection; $i <= $indexTargetSection; $i++) {
		array_push($v_maxFromUsedSections, $next_v_max[$i]);
	}
	$VMaxOverCumulativeSections = array_map('toArr', $cumulativeSectionLengthEnd, $v_maxFromUsedSections);
	$VMaxOverPositionsJSon = json_encode($VMaxOverCumulativeSections);

	$fp = fopen('../json/VMaxOverCumulativeSections.json', 'w');
	fwrite($fp, $VMaxOverPositionsJSon);
	fclose($fp);
	*/

	//$previousFailedSections = array();

	while (checkIfTrainIsToFastInCertainSections()["failed"]) {
		// saves the keyPoints local
		$tempKeyPoints = $keyPoints;
		// berechnet die "live Daten"
		$trainChange = createTrainChanges($startTime);
		$trainPositionChange = $trainChange[0];
		$trainSpeedChange = $trainChange[1];
		//$trainTimeChange = $trainChange[2];

		// suche nach Fehlern und neuberechnung...
		$keyPoints = recalculateKeyPoints($tempKeyPoints);

		// Berechnung der neuen Live Daten
		$trainChange = createTrainChanges($startTime);
		$trainPositionChange = $trainChange[0];
		$trainSpeedChange = $trainChange[1];
		//$trainTimeChange = $trainChange[2];

		// TODO: Wird diese Funktion benötigt?
		checkTrainChangeOverlap();
		// TODO: Am Ende einmal die toJSON Funktion aufrufen...
		//safeTrainChangeToJSONFile();
	}



	/*
	if (checkIfTrainIsToFastInCertainSections()["failed"]) {
		while (checkIfTrainIsToFastInCertainSections()["failed"]) {
			$tempKeyPoints = $keyPoints;
			$trainChange = createTrainChanges($startTime);
			$trainPositionChange = $trainChange[0];
			$trainSpeedChange = $trainChange[1];
			$trainTimeChange = $trainChange[2];
			$keyPoints = recalculateKeyPoints($tempKeyPoints);
			$trainChange = createTrainChanges($startTime);
			$trainPositionChange = $trainChange[0];
			$trainSpeedChange = $trainChange[1];
			$trainTimeChange = $trainChange[2];
			checkTrainChangeOverlap();
			safeTrainChangeToJSONFile();
		}
	}
	*/



	// Adding time to first KeyPoint
	$keyPoints[0]["time_0"] = $startTime;
	// TODO: WIrd diese Funktion benötigt?
	$keyPoints = deleteDoubledKeyPoints($keyPoints);

	// TODO: Evtl. $timeToNextStop über $trainChangeTime errechnen und nicht über eine eigene Funktion
	// Berechnet die Zeiten für die Train Change Daten
	//$trainTimeChange = calculateTrainTimeChange();
	// Berechnet die Zeiten für die KeyPoints
	$keyPoints = calculateTimeFromKeyPoints();



	toShortOnOneSpeed();



	$timeToNextStop = end($keyPoints)["time_1"] - $keyPoints[0]["time_0"];

	/*
	if (sizeof($keyPoints) > 1) {
		$timeToNextStop = end($keyPoints)["time_1"] - $keyPoints[0]["time_0"];
	} else {
		$timeToNextStop = $keyPoints[0]["time_1"] - $keyPoints[0]["time_0"];
	}
	*/

	// Zug kommt zu spät an...
	if (!$freieFahrt) {
		if ($timeToNextStop > $maxTimeToNextStop) {
			/*
			// Do nothing, schneller kann der Zug eh nicht ankommen

			//$keyPoints = calculateTimeFromKeyPoints();
			//$timeToNextStop = $keyPoints[array_key_last($keyPoints)]["time_1"];


			foreach ($trainPositionChange as $key => $value) {
				if (array_key_last($trainPositionChange) != $key) {
					$trainBetriebsstelleIndex[$key] = null;
				} else {
					if ($indexReachedBetriebsstelle == 99999999) {
						$trainBetriebsstelleIndex[$key] = -1;
					} else {
						$trainBetriebsstelleIndex[$key] = $indexReachedBetriebsstelle;
					}
				}
			}

			// $reachedBetriebsstelle ist immer true
			if ($reachedBetriebsstelle) {
				foreach ($trainPositionChange as $key => $value) {
					if (array_key_last($trainPositionChange) != $key) {
						$trainTargetReached[$key] = false;
						$trainBetriebsstelleIndex[$key] = $indexReachedBetriebsstelle;
					} else {
						$trainTargetReached[$key] = true;
						$trainBetriebsstelleIndex[$key] = $indexReachedBetriebsstelle;
					}
				}
			} else {
				foreach ($trainPositionChange as $key => $value) {
					$trainTargetReached[$key] = false;
					$trainBetriebsstelleIndex[$key] = false;
				}
			}

			if ($wendet) {
				foreach ($trainPositionChange as $key => $value) {
					if (array_key_last($trainPositionChange) != $key) {
						$trainWendet[$key] = false;
					} else {
						$trainWendet[$key] = true;
					}
				}
			} else {
				foreach ($trainPositionChange as $key => $value) {
					$trainWendet[$key] = false;
				}
			}


			//betriebsstelle_index

			//safeTrainChangeToJSONFile();

			*/
			// TODO: Als allgemeine Info am Anfang der Funktion...
			//$nextBetriebsstelle = $train["next_betriebsstellen_data"][$indexReachedBetriebsstelle]["betriebstelle"];
			echo "Der Zug mit der Adresse ", $train["adresse"], " wird mit einer Verspätung von ", number_format($timeToNextStop - ($endTime - $startTime), 2), " Sekunden im nächsten planmäßigen Halt (", $targetBetriebsstelle,") ankommen.\n";
		} else {
			echo "Aktuell benötigt der Zug mit der Adresse ", $train["adresse"], " ", number_format($timeToNextStop, 2), " Sekunden, obwohl er ", number_format($endTime - $startTime, 2), " Sekunden zur Verfügung hat\n";
			echo "Evtl. könnte der Zug zwischendurch die Geschwindigkeit verringern, um Energie zu sparen.";

			$keyPointsPreviousStep = array();
			$finish = false;

			// checkIfTheSpeedCanBeDecreased() => sucht zwei benachbarte KeyPOPints die erst beschleunigen und dann abbremsen
			// und speichert zu dem Index alle möglichen Anpassungen

			while (checkIfTheSpeedCanBeDecreased()["possible"] && !$finish) {

				$possibleSpeedRange = findMaxSpeed();
				if ($possibleSpeedRange["min_speed"] == 10 && $possibleSpeedRange["max_speed"] == 10) {
					break;
				}
				$localKeyPoints = $keyPoints;	//lokale Kopie der KeyPoints
				$newCalculatedTime = null; 		//Zeit bis zum Ziel
				$newKeyPoints = null;

				for ($i = $possibleSpeedRange["max_speed"]; $i >= $possibleSpeedRange["min_speed"]; $i = $i - 10) {

					$localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["speed_1"] = $i;
					$localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["speed_0"] = $i;
					$localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["position_1"] = (getBrakeDistance($localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["speed_0"], $i, $verzoegerung) + $localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["position_0"]);
					$localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["position_0"] = ($localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["position_1"] - getBrakeDistance($i, $localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["speed_1"], $verzoegerung));

					$localKeyPoints = calculateTimeFromKeyPoints($localKeyPoints);
					$newCalculatedTime = $localKeyPoints[array_key_last($localKeyPoints)]["time_1"];
					if ($i == 10)  {
						if ($newCalculatedTime > $maxTimeToNextStop) {
							$localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["speed_1"] = $i + 10;
							$localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["speed_0"] = $i + 10;
							$localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["position_1"] = (getBrakeDistance($localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["speed_0"], ($i + 10), $verzoegerung) + $localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["position_0"]);
							$localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["position_0"] = ($localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["position_1"] - getBrakeDistance(($i + 10), $localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["speed_1"], $verzoegerung));
						}
						$finish = true;
						$newKeyPoints = $localKeyPoints;
						break;
					}

					if (($newCalculatedTime - $startTime) > $maxTimeToNextStop) {
						if ($i == $possibleSpeedRange["max_speed"]) {

							$localKeyPoints = $keyPointsPreviousStep;
							$localKeyPoints = deleteDoubledKeyPoints($localKeyPoints);
							$keyPoints = $localKeyPoints;
							$finish = true;

							break;
						}
						$localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["speed_1"] = $i + 10;
						$localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["speed_0"] = $i + 10;
						$localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["position_1"] = (getBrakeDistance($localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["speed_0"], ($i + 10), $verzoegerung) + $localKeyPoints[$possibleSpeedRange["first_key_point_index"]]["position_0"]);
						$localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["position_0"] = ($localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["position_1"] - getBrakeDistance(($i + 10), $localKeyPoints[$possibleSpeedRange["first_key_point_index"] + 1]["speed_1"], $verzoegerung));
						$newKeyPoints = $localKeyPoints;
						$finish = true;
						$keyPoints = $localKeyPoints;

						break;
					}

					if ($i == $possibleSpeedRange["min_speed"]) {
						$newKeyPoints = $localKeyPoints;
						$newKeyPoints = deleteDoubledKeyPoints($newKeyPoints);
						$keyPoints = $newKeyPoints;

						break;

					}
					// TODO: KeyPoints löschen, bei denen speed_0 == speed_1 gilt
					$newKeyPoints = $localKeyPoints;

				}

				$keyPointsPreviousStep = $localKeyPoints;
				if ($newKeyPoints != null) {
					$keyPoints = $newKeyPoints;
				}

				$keyPoints = deleteDoubledKeyPoints($keyPoints);

			}

			$keyPoints = calculateTimeFromKeyPoints();


			if ($useSpeedFineTuning) {
				$newCalculatedTime = $keyPoints[array_key_last($keyPoints)]["time_1"];
				speedFineTuning(($maxTimeToNextStop - ($newCalculatedTime - $startTime)), $possibleSpeedRange["first_key_point_index"]);
			}
			// TODO: $currentTime global verfügbar machen

			$keyPoints = calculateTimeFromKeyPoints();

			//var_dump($keyPoints);



			//$keyPoints = calculateTimeFromKeyPoints();
			$timeToNextStop = end($keyPoints)["time_1"] - $keyPoints[0]["time_0"];

			/*
			$returnTrainChanges = createTrainChanges($startTime);
			$trainPositionChange = $returnTrainChanges[0];
			$trainSpeedChange = $returnTrainChanges[1];
			$trainTimeChange = $returnTrainChanges[2];
			$trainRelativePosition = $returnTrainChanges[3];
			$trainSection = $returnTrainChanges[4];
			$trainIsSpeedChange = $returnTrainChanges[5];
			//safeTrainChangeToJSONFile();

			$trainTargetReached = array();
			$trainBetriebsstelleIndex = array();
			$trainWendet = array();

			if ($wendet) {
				foreach ($trainPositionChange as $key => $value) {
					if (array_key_last($trainPositionChange) != $key) {
						$trainWendet[$key] = false;
					} else {
						$trainWendet[$key] = true;
					}
				}
			} else {
				foreach ($trainPositionChange as $key => $value) {
					$trainWendet[$key] = false;
				}
			}

			foreach ($trainPositionChange as $key => $value) {
				if (array_key_last($trainPositionChange) != $key) {
					$trainBetriebsstelleIndex[$key] = null;
				} else {
					if ($indexReachedBetriebsstelle == 99999999) {
						$trainBetriebsstelleIndex[$key] = -1;
					} else {
						$trainBetriebsstelleIndex[$key] = $indexReachedBetriebsstelle;
					}
				}
			}

			if ($reachedBetriebsstelle) {
				foreach ($trainPositionChange as $key => $value) {
					if (array_key_last($trainPositionChange) != $key) {
						$trainTargetReached[$key] = false;
						$trainBetriebsstelleIndex[$key] = $indexReachedBetriebsstelle;
					} else {
						$trainTargetReached[$key] = true;
						$trainBetriebsstelleIndex[$key] = $indexReachedBetriebsstelle;
					}
				}
			} else {
				foreach ($trainPositionChange as $key => $value) {
					$trainTargetReached[$key] = false;
					$trainBetriebsstelleIndex[$key] = false;
				}
			}
			*/



			echo "\nDurch die Anpassung der Geschwindigkeit benötigt der Zug mit der Adresse ", $train["adresse"], " jetzt ", number_format($timeToNextStop, 2), " Sekunden bis\n";
			echo "zum nächsten planmäßigen Halt (", $targetBetriebsstelle,") und wird diesen mit einer Verspätung von ", number_format($timeToNextStop - ($endTime - $startTime), 2), " Sekunden erreichen.\n";
		}
	} else {
		echo "Der Zug mit der Adresse ", $train["adresse"], " fährt aktuell ohne Fahrplan bis zum nächsten auf Halt stehendem Signal (Signal ID: ", $targetSignal, ", Betriebsstelle: ", $targetBetriebsstelle,").";
	}



	$returnTrainChanges = createTrainChanges($startTime);
	$trainPositionChange = $returnTrainChanges[0];
	$trainSpeedChange = $returnTrainChanges[1];
	$trainTimeChange = $returnTrainChanges[2];
	$trainRelativePosition = $returnTrainChanges[3];
	$trainSection = $returnTrainChanges[4];
	$trainIsSpeedChange = $returnTrainChanges[5];

	$trainTargetReached = array();
	$trainBetriebsstelleName = array();
	$trainWendet = array();

	// TODO: Evtl. erst vor dem Return?
	foreach ($trainPositionChange as $key => $value) {
		$trainBetriebsstelleName[$key] = $targetBetriebsstelle;
		// Nicht das letzte Element
		if (array_key_last($trainPositionChange) != $key) {
			$trainTargetReached[$key] = false;
			$trainWendet[$key] = false;
			// Das letzte Element
		} else {
			if ($wendet) {
				$trainWendet[$key] = true;
			} else {
				$trainWendet[$key] = false;
			}
			if ($reachedBetriebsstelle) {
				$trainTargetReached[$key] = true;
			} else {
				$trainTargetReached[$key] = false;
			}
		}
	}

	$returnArray = array();
	$adress = $train["adresse"];

	$trainID = array();
	$id = $train["id"];

	foreach ($trainPositionChange as $key => $value) {
		$trainID[$key] = $id;
	}

	//var_dump($trainRelativePosition);

	/*
	var_dump(sizeof($trainPositionChange));
	var_dump(sizeof($trainSpeedChange));
	var_dump(sizeof($trainTimeChange));
	var_dump(sizeof($trainRelativePosition));
	var_dump(sizeof($trainSection));
	var_dump(sizeof($trainIsSpeedChange));
	var_dump(sizeof($trainTargetReached));
	var_dump(sizeof($trainID));
	var_dump(sizeof($trainWendet));
	var_dump(sizeof($trainBetriebsstelleIndex));
	*/

	foreach ($trainPositionChange as $trainPositionChangeIndex => $trainPositionChangeValue) {
		array_push($returnArray, array("live_position" => $trainPositionChangeValue, "live_speed" => $trainSpeedChange[$trainPositionChangeIndex], "live_time" => $trainTimeChange[$trainPositionChangeIndex], "live_relative_position" => $trainRelativePosition[$trainPositionChangeIndex], "live_section" => $trainSection[$trainPositionChangeIndex], "live_is_speed_change" => $trainIsSpeedChange[$trainPositionChangeIndex], "live_target_reached" => $trainTargetReached[$trainPositionChangeIndex], "id" => $trainID[$trainPositionChangeIndex], "wendet" => $trainWendet[$trainPositionChangeIndex], "betriebsstelle_index" => $trainBetriebsstelleName[$trainPositionChangeIndex]));
	}
	$allTimes[$adress] = array_merge($allTimes[$adress], $returnArray);
	//$allTimes[$adress] = $returnArray;



	return (end($trainTimeChange) - $trainTimeChange[0]) - ($endTime - $startTime);

}

// Anpassen für viele Schritte => $a bleibt konstant?! => Eher nicht anpassen und allgemein halten
function getBrakeDistance (float $v_0, float $v_1, float $verzoegerung) {
	// v in km/h
	// a in m/s^2
	// return in m
	// TODO: Wie sieht es mit der Reaktionszeit aus? (Wenn ja, dann nur bei der Ersten 2 km/h_diff Bremsung
	if ($v_0 > $v_1) {
		return $bremsweg = 0.5 * 1 * ((pow($v_0/3.6,2)-pow($v_1/3.6, 2))/(pow($verzoegerung, 1)));
	} if ($v_0 < $v_1) {
		return $bremsweg = -0.5 * 1 * ((pow($v_0/3.6,2)-pow($v_1/3.6, 2))/(pow($verzoegerung, 1)));
	} else {
		return 0;
	}
}

// TODO: Überarbeitung
function getVMaxBetweenTwoPoints(float $distance, int $v_0, int $v_1) {

	global $verzoegerung;
	global $globalFloatingPointNumbersRoundingError;

	$v_max = array();

	for ($i = 0; $i <= 120; $i = $i + 10) {
		if ((getBrakeDistance($v_0, $i, $verzoegerung) + getBrakeDistance($i, $v_1, $verzoegerung)) < ($distance + $globalFloatingPointNumbersRoundingError)) {
			array_push($v_max, $i);
		}
	}

	if (sizeof($v_max) == 0) {
		if ($v_0 == 0 && $v_1 == 0 && $distance > 0) {
			echo "Der zug müsste langsamer als 10 km/h fahren, um das Ziel zu erreichen.";
		} else {
			// TODO: Notbremsung
		}
	} else {
		if ($v_0 == $v_1 && max($v_max) < $v_0) {
			$v_max = array($v_0);
		}
	}
	return max($v_max);
}

function createKeyPoint (float $position_0, float $position_1, int $speed_0, int $speed_1) {
	return array("position_0" => $position_0, "position_1" => $position_1, "speed_0" => $speed_0, "speed_1" => $speed_1);
}

// TODO: Funktion doppelt sich... (evtl. hilfreich, weil diese Funktion nur Geschwindigkeit und Position zurückgibt)
function convertKeyPointsToTrainChangeArray (array $keyPoints) {
	global $verzoegerung;

	$trainSpeedChangeReturn = array();
	$trainPositionChnageReturn = array();

	array_push($trainPositionChnageReturn, $keyPoints[0]["position_0"]);
	array_push($trainSpeedChangeReturn, $keyPoints[0]["speed_0"]);

	for ($i = 0; $i <= (sizeof($keyPoints) - 2); $i++) {
		if ($keyPoints[$i]["speed_0"] < $keyPoints[$i]["speed_1"]) {
			for ($j = $keyPoints[$i]["speed_0"]; $j < $keyPoints[$i]["speed_1"]; $j = $j + 2) {
				array_push($trainPositionChnageReturn, (end($trainPositionChnageReturn) + getBrakeDistance($j, ($j + 2), $verzoegerung)));
				array_push($trainSpeedChangeReturn, ($j + 2));
			}
		} elseif ($keyPoints[$i]["speed_0"] > $keyPoints[$i]["speed_1"]) {
			for ($j = $keyPoints[$i]["speed_0"]; $j > $keyPoints[$i]["speed_1"]; $j = $j - 2) {
				array_push($trainPositionChnageReturn, (end($trainPositionChnageReturn) + getBrakeDistance($j, ($j - 2), $verzoegerung)));
				array_push($trainSpeedChangeReturn, ($j - 2));
			}
		}
		array_push($trainPositionChnageReturn, $keyPoints[$i + 1]["position_0"]);
		array_push($trainSpeedChangeReturn, $keyPoints[$i + 1]["speed_0"]);
	}

	if (end($keyPoints)["speed_0"] < end($keyPoints)["speed_1"]) {
		for ($j = end($keyPoints)["speed_0"]; $j < end($keyPoints)["speed_1"]; $j = $j + 2) {
			array_push($trainPositionChnageReturn, (end($trainPositionChnageReturn) + getBrakeDistance($j, ($j + 2), $verzoegerung)));
			array_push($trainSpeedChangeReturn, ($j + 2));
		}
	} else if (end($keyPoints)["speed_0"] > end($keyPoints)["speed_1"]) {
		for ($j = end($keyPoints)["speed_0"]; $j > end($keyPoints)["speed_1"]; $j = $j - 2) {
			array_push($trainPositionChnageReturn, (end($trainPositionChnageReturn) + getBrakeDistance($j, ($j - 2), $verzoegerung)));
			array_push($trainSpeedChangeReturn, ($j - 2));
		}
	}
	return array($trainPositionChnageReturn, $trainSpeedChangeReturn);
}

function safeTrainChangeToJSONFile() {
	global $trainPositionChange;
	global $trainSpeedChange;

	$speedOverPosition = array_map('toArr', $trainPositionChange, $trainSpeedChange);
	$speedOverPosition = json_encode($speedOverPosition);
	$fp = fopen('../json/speedOverPosition_v1.json', 'w');
	fwrite($fp, $speedOverPosition);
	fclose($fp);
}

function checkIfTrainIsToFastInCertainSections() {

	global $trainPositionChange;
	global $trainSpeedChange;
	global $cumulativeSectionLengthStart;
	global $next_v_max;

	$faildSections = array();

	foreach ($trainPositionChange as $trainPositionChangeKey => $trainPositionChangeValue) {
		foreach ($cumulativeSectionLengthStart as $cumulativeSectionLengthStartKey => $cumulativeSectionLengthStartValue) {
			if ($trainPositionChangeValue < $cumulativeSectionLengthStartValue) {
				if ($trainSpeedChange[$trainPositionChangeKey] > $next_v_max[$cumulativeSectionLengthStartKey - 1]) {
					array_push($faildSections, ($cumulativeSectionLengthStartKey -1));
				}
				break;
			}
		}
	}

	if (sizeof($faildSections) == 0) {
		return array("failed" => false);
	} else {
		return array("failed" => true, "failed_sections" => array_unique($faildSections));
	}
}

function deleteDoubledKeyPoints($temporaryKeyPoints) {


	do {
		$foundDoubledKeyPoints = false;
		$doubledIndex = array();
		for ($i = 1; $i < (sizeof($temporaryKeyPoints) - 1); $i++) {
			if ($temporaryKeyPoints[$i]["speed_0"] == $temporaryKeyPoints[$i]["speed_1"]) {
				$foundDoubledKeyPoints = true;
				array_push($doubledIndex, $i);
			}
		}

		foreach ($doubledIndex as $index) {
			unset($temporaryKeyPoints[$index]);
		}

		$temporaryKeyPoints = array_values($temporaryKeyPoints);

	} while ($foundDoubledKeyPoints);


	return $temporaryKeyPoints;
}

function calculateTrainTimeChange() {

	global $keyPoints;
	global $verzoegerung;

	$returnAllTimes = array();
	$returnAllTimes[0] = $keyPoints[0]["time_0"];

	for ($i = 0; $i < (sizeof($keyPoints) - 1); $i++) {

		if ($keyPoints[$i]["speed_0"] < $keyPoints[$i]["speed_1"]) {
			for ($j = $keyPoints[$i]["speed_0"] + 2; $j <= $keyPoints[$i]["speed_1"]; $j = $j + 2) {
				array_push($returnAllTimes, (end($returnAllTimes) + (getBrakeTime($j - 2, $j, $verzoegerung))));

			}
			array_push($returnAllTimes, (end($returnAllTimes) + distanceWithSpeedToTime($keyPoints[$i]["speed_1"], ($keyPoints[$i + 1]["position_0"] - $keyPoints[$i]["position_1"]))));

		} else if ($keyPoints[$i]["speed_0"] > $keyPoints[$i]["speed_1"]) {
			for ($j = $keyPoints[$i]["speed_0"] - 2; $j >= $keyPoints[$i]["speed_1"]; $j = $j - 2) {
				array_push($returnAllTimes, (end($returnAllTimes) + (getBrakeTime($j + 2, $j, $verzoegerung))));

			}
			array_push($returnAllTimes, (end($returnAllTimes) + distanceWithSpeedToTime($keyPoints[$i]["speed_1"], ($keyPoints[$i + 1]["position_0"] - $keyPoints[$i]["position_1"]))));
		}
	}

	if ($keyPoints[array_key_last($keyPoints)]["speed_0"] < $keyPoints[array_key_last($keyPoints)]["speed_1"]) {
		for ($i = ($keyPoints[array_key_last($keyPoints)]["speed_0"] + 2); $i <= $keyPoints[array_key_last($keyPoints)]["speed_1"]; $i = $i + 2) {
			array_push($returnAllTimes, (end($returnAllTimes) + (getBrakeTime($i - 2, $i, $verzoegerung))));
		}
	} else if ($keyPoints[array_key_last($keyPoints)]["speed_0"] > $keyPoints[array_key_last($keyPoints)]["speed_1"]) {
		for ($i = ($keyPoints[array_key_last($keyPoints)]["speed_0"] - 2); $i >= $keyPoints[array_key_last($keyPoints)]["speed_1"]; $i = $i - 2) {
			array_push($returnAllTimes, (end($returnAllTimes) + (getBrakeTime($i + 2, $i, $verzoegerung))));
		}
	}

	return $returnAllTimes;
}

function getBrakeTime (float $v_0, float $v_1, float $verzoegerung) {
	$v_0 = $v_0 / 3.6;
	$v_1 = $v_1 / 3.6;

	if ($v_0 < $v_1) {
		return ($v_1/$verzoegerung) - ($v_0/$verzoegerung);
	} elseif ($v_0 > $v_1) {
		return ($v_0/$verzoegerung) - ($v_1/$verzoegerung);
	} elseif ($v_0 == $v_1) {
		return 0;
	} else {
		return false;
	}
}

// TODO: Darf nichts negatives zurückgeben!
// TODO: Muss auch was neg. zurück geben für  minTimeOnOneSPeed!
function distanceWithSpeedToTime (int $v, float $distance) {
	if ($distance == 0) {
		return 0;
	}
	return (($distance)/($v / 3.6));
}



function calculateTimeFromKeyPoints($inputKeyPoints = null) {

	global $keyPoints;
	global $verzoegerung;

	if ($inputKeyPoints == null) {
		$localKeyPoints = $keyPoints;
	} else {
		$localKeyPoints = $inputKeyPoints;
	}

	for ($i = 0; $i < (sizeof($localKeyPoints) - 1); $i++) {
		$localKeyPoints[$i]["time_1"] = getBrakeTime($localKeyPoints[$i]["speed_0"], $localKeyPoints[$i]["speed_1"], $verzoegerung) + $localKeyPoints[$i]["time_0"];
		$localKeyPoints[$i + 1]["time_0"] = distanceWithSpeedToTime($localKeyPoints[$i]["speed_1"], ($localKeyPoints[$i + 1]["position_0"]) - $localKeyPoints[$i]["position_1"]) + $localKeyPoints[$i]["time_1"];
	}

	$localKeyPoints[array_key_last($localKeyPoints)]["time_1"] = getBrakeTime($localKeyPoints[array_key_last($localKeyPoints)]["speed_0"], $localKeyPoints[array_key_last($localKeyPoints)]["speed_1"], $verzoegerung) + $localKeyPoints[array_key_last($localKeyPoints)]["time_0"];

	return $localKeyPoints;
}

function createTrainChanges(float $currentTime) {

	global $keyPoints;
	global $verzoegerung;
	global $cumulativeSectionLengthStart;
	global $cumulativeSectionLengthEnd;
	global $next_sections;
	global $indexCurrentSection;
	global $indexTargetSection;
	global $currentPosition;
	//global $targetPosition;
	global $globalTimeUpdateInterval;
	global $globalFloatingPointNumbersRoundingError;

	$returnTrainSpeedChange = array();
	$returnTrainTimeChange = array();
	$returnTrainPositionChange = array();
	$returnTrainRelativePosition = array();
	$returnTrainSection = array();
	$returnIsSpeedChange = array();

	//var_dump($keyPoints);

	// Alle bis auf den letzten Key Point
	// Erstellt immer alle Daten zwischen KeyPoint Anfang und dem letzten Wert vor dem nächsten KeyPoint
	for ($i = 0; $i < (sizeof($keyPoints) - 1); $i++) {

		// Startdaten
		array_push($returnTrainTimeChange, $currentTime);
		array_push($returnTrainSpeedChange, $keyPoints[$i]["speed_0"]);
		array_push($returnTrainPositionChange, $keyPoints[$i]["position_0"]);
		array_push($returnIsSpeedChange, true);

		if ($keyPoints[$i]["speed_0"] < $keyPoints[$i]["speed_1"]) {
			// Speichert alle ab dem zweiten Wert bis zum letzten Wert
			for ($j = ($keyPoints[$i]["speed_0"] + 2); $j <= $keyPoints[$i]["speed_1"]; $j = $j + 2) {
				array_push($returnTrainPositionChange, (end($returnTrainPositionChange) + getBrakeDistance(($j - 2), $j, $verzoegerung)));
				array_push($returnTrainSpeedChange, $j);
				array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + (getBrakeTime(($j - 2), $j, $verzoegerung))));
				array_push($returnIsSpeedChange, true);
			}
		} else {
			// TODO: Möglichst spät!
			/*
			array_push($returnTrainPositionChange, $keyPoints[$i]["position_1"] - getBrakeDistance($keyPoints[$i]["speed_0"],$keyPoints[$i]["speed_1"],$verzoegerung));
			array_push($returnTrainSpeedChange, $keyPoints[$i]["speed_0"]);
			array_push($returnTrainTimeChange, (end($returnTrainPositionChange) + distanceWithSpeedToTime($keyPoints[$i]["speed_0"], ($keyPoints[$i]["position_1"] - $keyPoints[$i]["position_0"] - getBrakeDistance($keyPoints[$i]["speed_0"], $keyPoints[$i]["speed_1"], $verzoegerung)))));
			array_push($returnIsSpeedChange, true);
			*/
			for ($j = ($keyPoints[$i]["speed_0"] - 2); $j >= $keyPoints[$i]["speed_1"]; $j = $j - 2) {
				array_push($returnTrainPositionChange, (end($returnTrainPositionChange) + getBrakeDistance(($j + 2), $j, $verzoegerung)));
				array_push($returnTrainSpeedChange, $j);
				array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + (getBrakeTime(($j + 2), $j, $verzoegerung))));
				array_push($returnIsSpeedChange, true);
			}
		}

		$startPosition = $keyPoints[$i]["position_1"];
		$endPosition =  $keyPoints[$i + 1]["position_0"];
		//$distanceToNextKeyPoint = $endPosition - $startPosition;
		$speedToNextKeyPoint = $keyPoints[$i]["speed_1"];
		//$timeUpdateInterval = 1; // TODO: Define global
		$distanceForOneTimeInterval = ($speedToNextKeyPoint / 3.6) * $globalTimeUpdateInterval;

		for ($position = $startPosition + $distanceForOneTimeInterval; $position < $endPosition; $position = $position + $distanceForOneTimeInterval) {
			//$relativePosition = $position - $startPosition;
			array_push($returnTrainPositionChange, $position);
			array_push($returnTrainSpeedChange, $speedToNextKeyPoint);
			//array_push($returnTrainTimeChange, end($returnTrainTimeChange) + ($relativePosition / ($speedToNextKeyPoint / 3.6)));
			array_push($returnTrainTimeChange, end($returnTrainTimeChange) + $globalTimeUpdateInterval);
			array_push($returnIsSpeedChange, false);
		}
		/*
		array_push($returnTrainPositionChange, $keyPoints[$i + 1]["position_0"]);
		array_push($returnTrainSpeedChange, $keyPoints[$i + 1]["speed_0"]);
		array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + distanceWithSpeedToTime($keyPoints[$i]["speed_1"], ($keyPoints[$i + 1]["position_0"] - $keyPoints[$i]["position_1"]))));
		array_push($returnIsSpeedChange, true);
		*/
	}

	array_push($returnTrainPositionChange, $keyPoints[array_key_last($keyPoints)]["position_1"] - getBrakeDistance($keyPoints[array_key_last($keyPoints)]["speed_0"],$keyPoints[array_key_last($keyPoints)]["speed_1"],$verzoegerung));
	array_push($returnTrainSpeedChange, $keyPoints[array_key_last($keyPoints)]["speed_0"]);
	array_push($returnTrainTimeChange, $keyPoints[array_key_last($keyPoints)]["time_0"]);
	//array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + distanceWithSpeedToTime($keyPoints[array_key_last($keyPoints)]["speed_0"], ($keyPoints[array_key_last($keyPoints)]["position_0"] - $keyPoints[array_key_last($keyPoints) - 1]["position_1"]))));
	array_push($returnIsSpeedChange, true);

	// letzter KeyPoint
	if ($keyPoints[array_key_last($keyPoints)]["speed_0"] < $keyPoints[array_key_last($keyPoints)]["speed_1"]) {
		for ($j = ($keyPoints[array_key_last($keyPoints)]["speed_0"] + 2); $j <= $keyPoints[array_key_last($keyPoints)]["speed_1"]; $j = $j + 2) {
			array_push($returnTrainPositionChange, (end($returnTrainPositionChange) + getBrakeDistance(($j - 2), $j, $verzoegerung)));
			array_push($returnTrainSpeedChange, $j);
			array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + (getBrakeTime(($j - 2), $j, $verzoegerung))));
			array_push($returnIsSpeedChange, true);
		}
	} else {
		//TODO: KANN DAS WEG?
		/*
		//array_push($returnTrainPositionChange, $keyPoints[array_key_last($keyPoints)]["position_1"] - getBrakeDistance($keyPoints[array_key_last($keyPoints)]["speed_0"],$keyPoints[array_key_last($keyPoints)]["speed_1"],$verzoegerung));
		//array_push($returnTrainSpeedChange, $keyPoints[array_key_last($keyPoints)]["speed_0"]);
		//array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + distanceWithSpeedToTime($keyPoints[array_key_last($keyPoints)]["speed_0"], ($keyPoints[array_key_last($keyPoints)]["position_0"] - $keyPoints[array_key_last($keyPoints) - 1]["position_1"]))));
		//array_push($returnIsSpeedChange, true);
		*/
		for ($j = ($keyPoints[array_key_last($keyPoints)]["speed_0"] - 2); $j >= $keyPoints[array_key_last($keyPoints)]["speed_1"]; $j = $j - 2) {

			array_push($returnTrainPositionChange, (end($returnTrainPositionChange) + getBrakeDistance(($j + 2), $j, $verzoegerung)));
			array_push($returnTrainSpeedChange, $j);
			array_push($returnTrainTimeChange, (end($returnTrainTimeChange) + (getBrakeTime(($j + 2), $j, $verzoegerung))));
			array_push($returnIsSpeedChange, true);
		}
	}

	//$count = 0;

	// Erstellt die relativen Positionen und Abschnitte zu den absoluten Werten.
	foreach ($returnTrainPositionChange as $absolutPositionKey => $absolutPositionValue) {
		foreach ($cumulativeSectionLengthStart as $sectionStartKey => $sectionStartValue) {
			if ($absolutPositionValue >= $sectionStartValue && $absolutPositionValue < $cumulativeSectionLengthEnd[$sectionStartKey]) {
				if ($sectionStartKey == $indexCurrentSection && $sectionStartKey == $indexTargetSection) {
					$returnTrainRelativePosition[$absolutPositionKey] = $absolutPositionValue + $currentPosition;
					$returnTrainSection[$absolutPositionKey] = $next_sections[$sectionStartKey];
				} else if ($sectionStartKey == $indexCurrentSection) {
					$returnTrainRelativePosition[$absolutPositionKey] = $absolutPositionValue + $currentPosition;
					$returnTrainSection[$absolutPositionKey] = $next_sections[$sectionStartKey];
				} else if ($sectionStartKey == $indexTargetSection) {
					$returnTrainRelativePosition[$absolutPositionKey] = $absolutPositionValue - $sectionStartValue;
					$returnTrainSection[$absolutPositionKey] = $next_sections[$sectionStartKey];
				} else {
					$returnTrainRelativePosition[$absolutPositionKey] = $absolutPositionValue - $sectionStartValue;
					$returnTrainSection[$absolutPositionKey] = $next_sections[$sectionStartKey];
				}
				break;
			} else if ($absolutPositionKey == array_key_last($returnTrainPositionChange) && abs($absolutPositionValue - floatval($cumulativeSectionLengthEnd[$sectionStartKey])) < $globalFloatingPointNumbersRoundingError) {
				$returnTrainRelativePosition[$absolutPositionKey] = $cumulativeSectionLengthEnd[$sectionStartKey] - $sectionStartValue;
				$returnTrainSection[$absolutPositionKey] = $next_sections[$sectionStartKey];
				break;
			} else {
				debugMessage("Eine absolute Position konnte keine relativen Position in einem Abschnitt zugeordnet werden!");
			}
		}
	}
	return array($returnTrainPositionChange, $returnTrainSpeedChange, $returnTrainTimeChange, $returnTrainRelativePosition, $returnTrainSection, $returnIsSpeedChange);
}

// Überprüft immer zwei benachbarte KeyPoints (0+1, 2+3, 4+5, 6+7 etc.)
// TODO: schöner schreiben die Funktion... sieht hässlich aus!
// TODO: Sollte auch bei einer ungeraden Anzahl an KeyPoints funktionieren, bitte aber nochmal überprüfen
function recalculateKeyPoints(array $tempKeyPoints) {

	$returnKeyPoints = array();
	// Anzahl an ganzer Paare
	$numberOfPairs = (sizeof($tempKeyPoints) - ((sizeof($tempKeyPoints)) % 2)) / 2;

	for($j = 0; $j < $numberOfPairs; $j++) {
		$i = $j * 2;
		$return = checkBetweenTwoKeyPoints($tempKeyPoints, $i);
		foreach ($return as $keyPoint) {
			array_push($returnKeyPoints, $keyPoint);
		}
	}

	return $returnKeyPoints;
}

function checkBetweenTwoKeyPoints(array $temKeyPoints, int $index) {

	global $trainPositionChange;
	global $trainSpeedChange;
	global $cumulativeSectionLengthStart;
	global $cumulativeSectionLengthEnd;
	global $next_v_max;
	global $verzoegerung;

	$failedSections = array();
	$groupedFailedSections = array();
	$returnKeyPoints = array();
	$failedPositions = array();
	$failedSpeeds = array();

	// Ermittlung aller Abschnitte, in denen die Geschwindigkeit überschritten wird
	foreach ($trainPositionChange as $trainPositionChangeKey => $trainPositionChangeValue) {
		if ($trainPositionChangeValue >= $temKeyPoints[$index]["position_0"] && $trainPositionChangeValue <= $temKeyPoints[$index + 1]["position_1"]) {
			foreach ($cumulativeSectionLengthStart as $cumulativeSectionLengthStartKey => $cumulativeSectionLengthStartValue) {
				if ($trainPositionChangeValue < $cumulativeSectionLengthStartValue) {
					// jetzt den davor überprüfen
					if ($trainSpeedChange[$trainPositionChangeKey] > $next_v_max[$cumulativeSectionLengthStartKey - 1]) {
						array_push($failedSections, ($cumulativeSectionLengthStartKey - 1));
						//array_push($failedPositions, $trainPositionChange[$trainPositionChangeKey]);
						$failedPositions[$trainPositionChangeKey] = $trainPositionChange[$trainPositionChangeKey];
						array_push($failedSpeeds, $trainSpeedChange[$trainPositionChangeKey]);
					}
					break;
				}
			}
		}
	}

	$failedSections = array_unique($failedSections);

	if (sizeof($failedSections) == 0) {
		return array($temKeyPoints[$index], $temKeyPoints[$index + 1]);
	} else {
		$returnKeyPoints[0]["speed_0"] = $temKeyPoints[$index]["speed_0"];
		$returnKeyPoints[0]["position_0"] = $temKeyPoints[$index]["position_0"];
	}

	$previous = NULL;
	foreach($failedSections as $key => $value) {
		if($value > $previous + 1) {
			$index++;
		}
		$groupedFailedSections[$index][] = $value;
		$previous = $value;
	}

	foreach ($groupedFailedSections as $groupSectionsIndex => $groupSectionsValue) {
		$firstFailedPositionIndex = null;
		$lastFailedPositionIndex = null;
		$firstFailedPosition = null;
		$lastFailedPosition = null;
		$lastElement = array_key_last($returnKeyPoints);
		$failedSection = null;

		if (sizeof($groupSectionsValue) == 1) {
			$failedSection = $groupSectionsValue[0];
		} else {
			$slowestSpeed = 200;
			for ($i = 0; $i <= (sizeof($groupSectionsValue) - 1); $i++) {
				if ($next_v_max[$groupSectionsValue[$i]] < $slowestSpeed) {
					$slowestSpeed = $next_v_max[$groupSectionsValue[$i]];
					$failedSection = $groupSectionsValue[$i];
				}
			}
		}

		$failedSectionStart = $cumulativeSectionLengthStart[$failedSection];
		$failedSectionEnd = $cumulativeSectionLengthEnd[$failedSection];
		//$vMaxInFailedSection = $next_v_max[$failedSection];

		foreach ($failedPositions as $failPositionIndex => $failPositionValue) {
			if ($failPositionValue > $failedSectionStart && $failPositionValue < $failedSectionEnd) {
				if ($firstFailedPositionIndex == null) {
					$firstFailedPositionIndex = $failPositionIndex;
				}
				$lastFailedPositionIndex = $failPositionIndex;
			}
		}

		if ($firstFailedPositionIndex != 0) {
			//var_dump($firstFailedPositionIndex);
			if ($trainPositionChange[$firstFailedPositionIndex - 1] < $failedSectionStart) {
				$firstFailedPosition = $failedSectionStart;
			} else {
				$firstFailedPosition = $trainPositionChange[$firstFailedPositionIndex - 1];
			}
		} else {
			$firstFailedPosition = $failedSectionStart;
		}

		if ($lastFailedPositionIndex != array_key_last($trainPositionChange)) {
			if ($trainPositionChange[$lastFailedPositionIndex + 1] > $failedSectionEnd) {
				$lastFailedPosition = $failedSectionEnd;
			} else {
				$lastFailedPosition = $trainPositionChange[$lastFailedPositionIndex + 1];
			}
		} else {
			$lastFailedPosition = $failedSectionEnd;
		}
		/*
		if ($failedPositions[0] > $cumulativeSectionLengthStart[$failedSection]) {
			$returnKeyPoints[$lastElement + 1]["position_1"] = $failedPositions[0];
		} else {
			$returnKeyPoints[$lastElement + 1]["position_1"] = $cumulativeSectionLengthStart[$failedSection];
		}
		*/
		$returnKeyPoints[$lastElement + 1]["position_1"] = $firstFailedPosition;
		$returnKeyPoints[$lastElement + 1]["speed_1"] = $next_v_max[$failedSection];
		$returnKeyPoints[$lastElement + 2]["position_0"] = $lastFailedPosition;
		/*
		if (end($failedPositions) < $cumulativeSectionLengthEnd[$failedSection]) {
			$returnKeyPoints[$lastElement + 2]["position_0"] = end($failedPositions);
		} else {
			$returnKeyPoints[$lastElement + 2]["position_0"] = $cumulativeSectionLengthEnd[$failedSection];
		}
		*/
		$returnKeyPoints[$lastElement + 2]["speed_0"] = $next_v_max[$failedSection];
	}

	$returnKeyPoints[array_key_last($returnKeyPoints) + 1]["position_1"] = $temKeyPoints[$index]["position_1"];
	$returnKeyPoints[array_key_last($returnKeyPoints)]["speed_1"] = $temKeyPoints[$index]["speed_1"]; //
	$numberOfPairs = (sizeof($returnKeyPoints) - ((sizeof($returnKeyPoints)) % 2)) / 2;
	for($j = 0; $j < $numberOfPairs; $j++) {
		$i = $j * 2;
		$distance = $returnKeyPoints[$i + 1]["position_1"] - $returnKeyPoints[$i]["position_0"];
		$vMax = getVMaxBetweenTwoPoints($distance, $returnKeyPoints[$i]["speed_0"], $returnKeyPoints[$i + 1]["speed_1"]);
		if ($vMax == -10) {
			$returnKeyPoints[$i]["position_0"] = $returnKeyPoints[$i + 1]["position_1"] - (getBrakeDistance($returnKeyPoints[$i]["speed_0"], $returnKeyPoints[$i + 1]["speed_1"], $verzoegerung));
			$distance = $returnKeyPoints[$i + 1]["position_1"] - $returnKeyPoints[$i]["position_0"];
			$vMax = getVMaxBetweenTwoPoints($distance, $returnKeyPoints[$i]["speed_0"], $returnKeyPoints[$i + 1]["speed_1"]);
			/*
			//var_dump($distance, $returnKeyPoints[$i]["speed_0"], $returnKeyPoints[$i + 1]["speed_1"]);
			//var_dump(getVMaxBetweenTwoPoints($distance, $returnKeyPoints[$i]["speed_0"], $returnKeyPoints[$i + 1]["speed_1"]));
			*/
		}
		$returnKeyPoints[$i]["speed_1"] = $vMax; //TODO
		$returnKeyPoints[$i]["position_1"] = $returnKeyPoints[$i]["position_0"] + getBrakeDistance($returnKeyPoints[$i]["speed_0"], $vMax, $verzoegerung);
		$returnKeyPoints[$i + 1]["speed_0"] = $vMax;
		$returnKeyPoints[$i + 1]["position_0"] = $returnKeyPoints[$i + 1]["position_1"] - getBrakeDistance($vMax, $returnKeyPoints[$i + 1]["speed_1"], $verzoegerung);
	}
	return $returnKeyPoints;
}

// Wenn ein Key Point beschleunigt und der nächste Key Point abbremst, wird
// die Geschwindigkeit zwischen den beiden KeyPoints als $v_maxBetweenKeyPoints
// gespeichert und als $v_minBetweenKeyPoints der größere Wert von
// $keyPoints[$i]["speed_0"] und $keyPoints[$i + 1]["speed_1"]
function checkIfTheSpeedCanBeDecreased() {

	global $keyPoints;
	global $returnPossibleSpeed;

	$returnPossibleSpeed = array();

	for ($i = 0; $i < (sizeof($keyPoints) - 1); $i++) {

		$v_maxBetweenKeyPoints = $keyPoints[$i]["speed_1"];
		$v_minBetweenKeyPoints = null;

		if ($keyPoints[$i]["speed_0"] < $v_maxBetweenKeyPoints && $keyPoints[$i + 1]["speed_1"] < $v_maxBetweenKeyPoints) {
			$v_minBetweenKeyPoints = $keyPoints[$i]["speed_0"];
			if ($keyPoints[$i + 1]["speed_1"] > $v_minBetweenKeyPoints) {
				$v_minBetweenKeyPoints = $keyPoints[$i + 1]["speed_1"];
			}
		}

		// TODO: Ist das sinnvoll?
		if ($v_minBetweenKeyPoints == 0 && $v_maxBetweenKeyPoints >= 10) {
			$v_minBetweenKeyPoints = 10;
		}

		if ($v_minBetweenKeyPoints != null) {
			// Der KeyPoint_indexn beschreibt den ersten der beiden KeyPoints
			array_push($returnPossibleSpeed, array("KeyPoint_index" => $i, "values" => range($v_minBetweenKeyPoints, $v_maxBetweenKeyPoints, 10)));
		}
	}

	if (sizeof($returnPossibleSpeed) > 0) {
		return array("possible" => true, "range" => $returnPossibleSpeed);
	} else {
		return array("possible" => false);
	}
}

function speedFineTuning(float $timeDiff, int $index) {

	global $keyPoints;
	global $verzoegerung;

	// array_splice( $original, 3, 0, $inserted ); at pos = 3

	$availableDistance = $keyPoints[$index + 1]["position_0"] - $keyPoints[$index]["position_1"];
	$timeBetweenKeyPoints = $keyPoints[$index + 1]["time_0"] - $keyPoints[$index]["time_1"];
	$availableTime = $timeBetweenKeyPoints + $timeDiff;

	/*
	if ($keyPoints[$index]["speed_0"] == 0 && $keyPoints[$index + 1]["speed_1"] == 0) {
		return;
	}
	*/
	if ($keyPoints[$index + 1]["speed_1"] != 0) {
		$lengthDifference = calculateDistanceforSpeedFineTuning($keyPoints[$index + 1]["speed_0"], $keyPoints[$index + 1]["speed_1"], $availableDistance, $availableTime);
		$keyPoints[$index + 1]["position_0"] = $keyPoints[$index + 1]["position_0"] - $lengthDifference;
		$keyPoints[$index + 1]["position_1"] = $keyPoints[$index + 1]["position_1"] - $lengthDifference;
	} else if ($keyPoints[$index + 1]["speed_0"] > 10) {
		//var_dump("Moin");
		$lengthDifference = calculateDistanceforSpeedFineTuning($keyPoints[$index + 1]["speed_0"],10, $availableDistance, $availableTime);

		$firstKeyPoint = createKeyPoint(($keyPoints[$index + 1]["position_0"] - $lengthDifference),($keyPoints[$index + 1]["position_0"] - $lengthDifference + getBrakeDistance($keyPoints[$index + 1]["speed_0"],10, $verzoegerung)),$keyPoints[$index + 1]["speed_0"],10);
		$secondKeyPoint = createKeyPoint(($keyPoints[$index + 1]["position_1"] - getBrakeDistance(10, 0, $verzoegerung)),$keyPoints[$index + 1]["position_1"],10,$keyPoints[$index + 1]["speed_1"]);

		$keyPoints[$index + 1] = $secondKeyPoint;
		array_splice( $keyPoints, ($index + 1), 0, array($firstKeyPoint));



		//$keyPoints[$index]["position_0"] = $keyPoints[$index]["position_0"] - $lengthDifference;
		//$keyPoints[$index]["position_1"] = $keyPoints[$index]["position_1"] - $lengthDifference;
	}
}

function calculateDistanceforSpeedFineTuning(int $v_0, int $v_1, float $distance, float $time) : float {
	return $distance - (($distance - $time * $v_1 / 3.6)/($v_0 / 3.6 - $v_1 / 3.6)) * ($v_0 / 3.6);
}

// Sucht den KeyPoint der zu maximalen Geschwindigkeit beschleunigt
// Wenn die maximale Geschwindigkeit mehrfach erreciht wird, wird
// der letzte dieser KeyPoints genommen
//
// Zu dem Index wird auch noch die Speed Range abgespeichert wie bei
// checkIfTheSpeedCanBeDecreased()
// TODO: Kann man diese beiden Funktionen kombinieren?
function findMaxSpeed() {

	global $keyPoints;

	$maxSpeed = null;
	$minSpeed = null;
	$keyPointIndex = null;

	for ($i = 0; $i < (sizeof($keyPoints) - 1); $i++) {
		if ($maxSpeed <= $keyPoints[$i]["speed_1"]) {
			$maxSpeed = $keyPoints[$i]["speed_1"];
			$keyPointIndex = $i;
		}
	}

	if ($keyPoints[$keyPointIndex]["speed_0"] < $keyPoints[$keyPointIndex + 1]["speed_1"]) {
		$minSpeed = $keyPoints[$keyPointIndex + 1]["speed_1"];
	} else {
		$minSpeed = $keyPoints[$keyPointIndex]["speed_0"];
	}

	// TODO: Überprüfen, ob das gelöschtz werden kann...
	if ($minSpeed < 10) {
		$minSpeed = 10;
	}

	return array("min_speed" => $minSpeed, "max_speed" => $maxSpeed, "first_key_point_index" => $keyPointIndex);
}

// Beim Start der Berechnung
function checkIfItsPossible() {
	global $currentSpeed;
	global $distanceToNextStop;
	global $verzoegerung;
	global $globalTimeOnOneSpeed;



	if ($currentSpeed == 0) {
		$distance_0 = getBrakeDistance(0, 10, $verzoegerung);
		$distance_1 = getBrakeDistance(10, 0, $verzoegerung);
		$time = distanceWithSpeedToTime(10, $distanceToNextStop - $distance_0 - $distance_1);
		if ($time < $globalTimeOnOneSpeed) {
			var_dump("ERROR!!!");
		}
	} else {
		if (getBrakeDistance($currentSpeed, 0, $verzoegerung) != $distanceToNextStop) {
			$distance_0 = getBrakeDistance($currentSpeed, 10, $verzoegerung);
			$distance_1 = getBrakeDistance(10, 0, $verzoegerung);
			$time = distanceWithSpeedToTime(10, $distanceToNextStop - $distance_0 - $distance_1);
			if ($time < $globalTimeOnOneSpeed) {
				var_dump("ERROR!!!");
			}
		}
	}
}

function toShortOnOneSpeed () {

	global $keyPoints;
	global $verzoegerung;

	// Sobald in einer Section die Geschwindigkeit verändert werden müsste, wird erstmal die Geschwindigkeit angepasst und dann neu berechnet...
	while (toSHortInSection($keyPoints)) {

		// Subsection erstellen
		$subsections = createSubsections();

		foreach ($subsections as $sectionKey => $sectionValue) {

			if ($sectionKey == 0) {
				if (checkForPostponement($sectionValue)) {
					// postpone speed
					postponeSubsection($sectionValue);
				} else {
					// reduce speed
					$keyPoints[$sectionValue["max_index"]]["speed_1"] -= 10;
					$keyPoints[$sectionValue["max_index"] + 1]["speed_0"] -= 10;
					$keyPoints[$sectionValue["max_index"]]["position_1"] = $keyPoints[$sectionValue["max_index"]]["position_0"] + getBrakeDistance($keyPoints[$sectionValue["max_index"]]["speed_0"], $keyPoints[$sectionValue["max_index"]]["speed_1"], $verzoegerung);
					$keyPoints[$sectionValue["max_index"] + 1]["position_0"] = $keyPoints[$sectionValue["max_index"] + 1]["position_1"] - getBrakeDistance($keyPoints[$sectionValue["max_index"] + 1]["speed_0"], $keyPoints[$sectionValue["max_index"] + 1]["speed_1"], $verzoegerung);;
					$keyPoints = calculateTimeFromKeyPoints();
					break;
				}
			}
		}
	}
}

function postponeSubsection(array $subsection) {

	global $keyPoints;
	global $globalTimeOnOneSpeed;

	$indexMaxSection = array_search($subsection["max_index"], $subsection["indexes"]);
	$indexLastKeyPoint = array_key_last($subsection["indexes"]);

	if ($subsection["is_prev_section"]) {
		$timeDiff = $keyPoints[$subsection["indexes"][0]]["time_0"] - $keyPoints[$subsection["indexes"][0] - 1]["time_1"] - $globalTimeOnOneSpeed;
		if ($timeDiff < 0) {
			$positionDiff = abs($timeDiff) * $keyPoints[$subsection["indexes"][0]]["speed_0"] / 3.6;
			//$keyPoints[$subsection["indexes"][0]]["time_0"] -= $timeDiff;
			//$keyPoints[$subsection["indexes"][0]]["time_1"] -= $timeDiff;
			$keyPoints[$subsection["indexes"][0]]["position_0"] += $positionDiff;
			$keyPoints[$subsection["indexes"][0]]["position_1"] += $positionDiff;
			$keyPoints = calculateTimeFromKeyPoints();

		}
	}

	for ($i = 1; $i <= $indexMaxSection; $i++) {
		$timeDiff = $keyPoints[$subsection["indexes"][$i]]["time_0"] - $keyPoints[$subsection["indexes"][$i] - 1]["time_1"] - $globalTimeOnOneSpeed;
		if ($timeDiff < 0) {
			$positionDiff = abs($timeDiff) * $keyPoints[$subsection["indexes"][$i]]["speed_0"] / 3.6;
			//$keyPoints[$subsection["indexes"][$i]]["time_0"] -= $timeDiff;
			//$keyPoints[$subsection["indexes"][$i]]["time_1"] -= $timeDiff;
			$keyPoints[$subsection["indexes"][$i]]["position_0"] += $positionDiff;
			$keyPoints[$subsection["indexes"][$i]]["position_1"] += $positionDiff;
			$keyPoints = calculateTimeFromKeyPoints();
		}
	}

	if ($subsection["is_next_section"]) {
		$timeDiff = $keyPoints[$indexLastKeyPoint + 1]["time_0"] - $keyPoints[$indexLastKeyPoint]["time_1"] - $globalTimeOnOneSpeed;
		if ($timeDiff < 0) {
			$positionDiff = abs($timeDiff) * $keyPoints[$indexLastKeyPoint]["speed_1"] / 3.6;
			//$keyPoints[$indexLastKeyPoint]["time_0"] += $timeDiff;
			//$keyPoints[$indexLastKeyPoint]["time_1"] += $timeDiff;
			$keyPoints[$indexLastKeyPoint]["position_0"] -= $positionDiff;
			$keyPoints[$indexLastKeyPoint]["position_1"] -= $positionDiff;
			$keyPoints = calculateTimeFromKeyPoints();
		}
	}

	for ($i = $indexLastKeyPoint - 1; $i > $indexMaxSection; $i--) {
		$timeDiff = $keyPoints[$subsection["indexes"][$i + 1]]["time_0"] - $keyPoints[$subsection["indexes"][$i]]["time_1"] - $globalTimeOnOneSpeed;
		if ($timeDiff < 0) {
			$positionDiff = abs($timeDiff) * $keyPoints[$indexLastKeyPoint]["speed_1"] / 3.6;
			//$keyPoints[$subsection["indexes"][$i]]["time_0"] += $timeDiff;
			//$keyPoints[$subsection["indexes"][$i]]["time_1"] += $timeDiff;
			$keyPoints[$subsection["indexes"][$i]]["position_0"] -= $positionDiff;
			$keyPoints[$subsection["indexes"][$i]]["position_1"] -= $positionDiff;
			$keyPoints = calculateTimeFromKeyPoints();
		}
	}
}



// TODO: Strecke zwischen zwei Subsections berücksichtigen..
function createSubsections () {
	global $keyPoints;
	global $currentSpeed;
	global $globalTimeOnOneSpeed;
	$currentSpeed = 40;

	$subsections = array();
	$subsection = array("max_index" => null, "indexes" => array(), "is_prev_section" => false, "is_next_section" => false);
	$foundMax = false;
	$maxIndex = null;

	// Wenn die erste Geschwindigkeit die maximale Geschwindigkeit der ersten Subsection ist.
	// TODO: Bei v_0 != 0 hat der erste KeyPoint v_0 == v_1... Kommt es da zu Konflikten?
	if ($currentSpeed != 0 && ($keyPoints[0]["speed_0"] == $keyPoints[0]["speed_1"])) {
		$foundMax = true;
		$subsection["max_index"] = 0;

	}

	for($i = 0; $i < sizeof($keyPoints); $i++) {
		// Subsection zu Ende
		if (($i == sizeof($keyPoints) - 1) || ($foundMax && $keyPoints[$i]["speed_0"] < $keyPoints[$i]["speed_1"])) {
			if ($i == sizeof($keyPoints) - 1) {
				array_push($subsection["indexes"], $i);
				array_push($subsections, $subsection);
			} else {
				array_push($subsections, $subsection);
				$subsection = array("max_index" => null, "indexes" => array(), "is_prev_section" => false, "is_next_section" => false, "failed" => false);
				array_push($subsection["indexes"], $i);
				$foundMax = false;
			}
		} else {
			// max erreicht?
			if ($keyPoints[$i]["speed_0"] < $keyPoints[$i]["speed_1"] && !$foundMax && $keyPoints[$i + 1]["speed_0"] > $keyPoints[$i + 1]["speed_1"] && !$foundMax) {
				$foundMax = true;
				$subsection["max_index"] = $i;
			}
			array_push($subsection["indexes"], $i);
		}
	}

	// Check if middle section failed
	for ($i = 1; $i < sizeof($subsections); $i++) {
		$firstIndex = $subsections[$i]["indexes"][array_key_first($subsections[$i]["indexes"])];
		if ($keyPoints[$firstIndex]["time_0"] - $keyPoints[$firstIndex - 1]["time_1"] < $globalTimeOnOneSpeed) {
			$subsections[$i]["is_prev_section"] = true;
			$subsections[$i]["failed"] = true;
		} else {
			$subsections[$i]["is_prev_section"] = false;
			$subsections[$i]["failed"] = false;
		}
	}



	for ($i = sizeof($subsections) - 1; $i >= 0; $i--) {
		$isFirstSubsection = false;
		$isLastSubsection = false;

		if ($i == 0) {
			$isFirstSubsection = true;
		}
		if ($i == sizeof($subsections) - 1) {
			$isLastSubsection = true;
		}



		if ($subsections[$i]["failed"] || failOnSubsection($subsections[$i])) {
			$subsections[$i]["failed"] = true;

			if (!$isFirstSubsection) {
				$subsections[$i]["is_prev_section"] = true;
			}
			// next hinzufügen
			if (!$isLastSubsection) {
				if (!$subsections[$i + 1]["is_prev_section"]) {
					$subsections[$i]["is_next_section"] = true;
				}
			}
		} else {
			$subsections[$i]["failed"] = false;
		}
	}

	return array_reverse($subsections);
}

function failOnSubsection(array $subsection) {

	global $keyPoints;
	global $globalTimeOnOneSpeed;



	$failed = false;

	for ($i = 1; $i < sizeof($subsection["indexes"]); $i++)  {

		if ($keyPoints[$subsection["indexes"][$i]]["time_0"] - $keyPoints[$subsection["indexes"][$i] - 1]["time_1"] < $globalTimeOnOneSpeed) {
			$failed = true;
			break;
		}
	}



	return $failed;
}

function checkForPostponement(array $subsection) {
	global $keyPoints;
	global $globalTimeOnOneSpeed;

	//$timeOnMax = 0;
	$timeBeforeMax = 0;
	$timeAfterMax = 0;
	$foundShortSectionBeforeMax = false;
	$foundShortSectionAfterMax = false;
	$indexMaxSection = array_search($subsection["max_index"], $subsection["indexes"]);
	$indexLastKeyPoint = array_key_last($subsection["indexes"]);

	$timeOnMax = $keyPoints[$subsection["max_index"] + 1]["time_0"] - $keyPoints[$subsection["max_index"]]["time_1"] - $globalTimeOnOneSpeed;

	if ($timeOnMax < 0) {
		return false;
	}

	if ($subsection["is_prev_section"]) {
		$timeDiff = $keyPoints[$subsection["indexes"][0]]["time_0"] - $keyPoints[$subsection["indexes"][0] - 1]["time_1"] - $globalTimeOnOneSpeed;
		if ($timeDiff < 0) {
			$timeBeforeMax += $timeDiff;
			$foundShortSectionBeforeMax = true;
		}
		//$timeBeforeMax += $keyPoints[$subsection["indexes"][0]]["time_0"] - $keyPoints[$subsection["indexes"][0] - 1]["time_1"] - $globalTimeOnOneSpeed;
	}



	if ($subsection["is_next_section"]) {
		$timeDiff = $keyPoints[$subsection["indexes"][array_key_last($subsection["indexes"])] + 1]["time_0"] - $keyPoints[$subsection["indexes"][array_key_last($subsection["indexes"])]]["time_1"] - $globalTimeOnOneSpeed;
		if ($timeDiff < 0) {
			$timeAfterMax += $timeDiff;
			$foundShortSectionAfterMax = true;
		}
		//$timeAfterMax += $keyPoints[$subsection["indexes"][array_key_last($subsection["indexes"])] + 1]["time_0"] - $keyPoints[$subsection["indexes"][array_key_last($subsection["indexes"])]]["time_1"] - $globalTimeOnOneSpeed;
	}



	for ($i = 1; $i <= $indexMaxSection; $i++) {
		if ($keyPoints[$subsection["indexes"][$i]]["time_0"] - $keyPoints[$subsection["indexes"][$i] - 1]["time_1"] < $globalTimeOnOneSpeed || $foundShortSectionBeforeMax) {

			$foundShortSectionBeforeMax = true;
			$timeBeforeMax += $keyPoints[$subsection["indexes"][$i]]["time_0"] - $keyPoints[$subsection["indexes"][$i] - 1]["time_1"] - $globalTimeOnOneSpeed;

		}
	}


	for ($i = $indexLastKeyPoint; $i > $indexMaxSection + 1; $i--) {
		if ($keyPoints[$subsection["indexes"][$i]]["time_0"] - $keyPoints[$subsection["indexes"][$i] - 1]["time_1"] < $globalTimeOnOneSpeed || $foundShortSectionAfterMax) {
			$foundShortSectionAfterMax = true;
			$timeAfterMax += $keyPoints[$subsection["indexes"][$i]]["time_0"] - $keyPoints[$subsection["indexes"][$i] - 1]["time_1"] - $globalTimeOnOneSpeed;
		}
	}



	/*
	foreach ($subsection["indexes"] as $index) {
		if ($index < $subsection["max_index"]) {
			$timeBeforeMax += $keyPoints[$index + 1]["time_0"] - $keyPoints[$index]["time_1"] - $globalTimeOnOneSpeed;
		} else if ($index > $subsection["max_index"] && $index != $subsection["indexes"][array_key_last($subsection["indexes"])]) {
			$timeAfterMax += $keyPoints[$index + 1]["time_0"] - $keyPoints[$index]["time_1"] - $globalTimeOnOneSpeed;
		}
	}
	*/




	if ($timeBeforeMax > 0) {
		$timeBeforeMax = 0;
	}

	if ($timeAfterMax > 0) {
		$timeAfterMax = 0;
	}



	// true = kann verschoben werden...
	if ($timeOnMax + $timeBeforeMax + $timeAfterMax >= 0) {
		return true;
	} else {
		return false;
	}
}



function toSHortInSection (array $keyPoints) {
	global $globalTimeOnOneSpeed;
	$foundError = false;
	for ($i = 0; $i < sizeof($keyPoints) - 1; $i++) {
		if (($keyPoints[$i + 1]["time_0"] - $keyPoints[$i]["time_1"]) < $globalTimeOnOneSpeed) {
			$foundError = true;
			break;
		}
	}
	return $foundError;
}








































