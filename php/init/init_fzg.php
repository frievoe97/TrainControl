<?php


function initFzg (int $id, int $adresse, float $verzoegerung, int $speed, int $section, int $position, array $pushArray) {

	$fzgTest = array(
		"id" => $id,
		"adresse" => $adresse,
		"verzoegerung" => $verzoegerung,
		"notverzoegerung" => 2,
		"speedConstant" => "", // true or false
		"laenge" => "",
		"section" => $section,
		"speed" => $speed,
		"position" => $position,
		"vmax" => 120,
		"next_speed" => array(),
		"next_time" => array(),
		"next_position" => array(),
		"next_sections" => array(),
		"next_lengths" => array(),
		"next_v_max" => array(),
		"next_timetable_change_speed" => "",
		"next_timetable_change_section" => "",
		"next_timetable_change_position" => "",
		"next_timetable_change_time" => "",
		"next_signal_change_speed" => "",
		"next_signal_change_section" => "",
		"next_signal_change_position" => "",
		"next_signal_change_time" => ""
	);

	/*
	$DB_insert = new DB_MySQL();
	$DB_insert->select("INSERT INTO `". DB_TABLE_FAHRZEUGE_AKTUELL."`
							VALUES ('".$id."','".$verzoegerung."','".$section."','".$speed."', '".$position."',
							CURRENT_TIMESTAMP, null, null, null, null)
                           	");

	unset($DB_insert);
	*/
	array_push($pushArray, $fzgTest);
	return $pushArray;
}

// Jeder Eintrag in dem Array sthet für EINEN Zug, deswegen wird auch eine ID für den Zug übergeben
function initAbschnitte (array $ID, array $LENGTH, array $VMAX, array $pushArray) {

	$fzgTest = array(
		"id" => $ID,
		"length" => $LENGTH,
		"v_max" => $VMAX
	);

	array_push($pushArray, $fzgTest);
	return $pushArray;
}

function setCurrentValues (array $allTrains, int $key, int $speed, int $section, int $position) {
	$allTrains[$key]["current_speed"] = $speed;
	$allTrains[$key]["current_infra_section"] = $section;
	$allTrains[$key]["current_position"] = $position;

	return $allTrains[$key];
}

// Create a new fzg
$fzgTest = array(
	"id" => 652,
	"adresse" => 7845,
	"verzoegerung" => 1,
	"section" => 100,
	"speed" => "",
	"position" => "",
	"next_speed" => array(),
	"next_time" => array(),
	"next_position" => array(),
	"next_sections" => "",
	"next_lengths" => "",
	"next_v_max" => "",
	"next_timetable_change_speed" => "",
	"next_timetable_change_section" => "",
	"next_timetable_change_position" => "",
	"next_timetable_change_time" => ""
);

function updateVMax (array $allTrains) {

	$returnTrains = array();

	$DB = new DB_MySQL();

	foreach ($allTrains as $train) {
		$adresse = $train["adresse"];
		$v_max = $DB->select("SELECT `".DB_TABLE_FAHRZEUGE_BAUREIHEN."`.`vmax`      
                            FROM `".DB_TABLE_FAHRZEUGE_BAUREIHEN."`
                            WHERE `".DB_TABLE_FAHRZEUGE_BAUREIHEN."`.`nummer` = $adresse
                           ");

		if (sizeof($v_max) != 0) {
			$train["v_max"] = get_object_vars($v_max[0])["vmax"];
			array_push($returnTrains, $train);
		}
	}
	return $returnTrains;
}

// return: true -> es muss nichts angepasst werden
function compareTwoNaechsteAbschnitte(int $id) {

	global $allTrains;
	global $allTimes;

	if ($allTrains[$id]["can_drive"]) {

		$newData = calculateNextSections($id, false);
		$newNextSection = $newData[0];
		$newNextLenghts = $newData[1];
		$newNextVMax = $newData[2];
		$oldNextSections = $allTrains[$id]["next_sections"];
		$oldLenghts = $allTrains[$id]["next_lenghts"];
		$oldNextVMax = $allTrains[$id]["next_v_max"];
		$currentSection = $allTrains[$id]["current_infra_section"];

		/*
		$currentSection = 1000;
		$newNextSection = array(1000, 1001, 1002, 1003, 1004, 1005);
		$newNextLenghts = array(100, 100, 100, 100, 100, 100);
		$newNextVMax = array(120, 120, 120, 120, 120, 120);
		$oldNextSections = array(999, 1000, 1001, 1002, 1003, 1004, 1005);
		$oldLenghts = array(100, 100, 100, 100, 100, 100, 100);
		$oldNextVMax = array(120, 120, 120, 110, 120, 120, 120);
		*/

		$keyCurrentSection = array_search($currentSection, $oldNextSections);
		$keyLatestSection = array_key_last($oldNextSections);
		$dataIsIdentical = true;
		$numberOfSection = $keyLatestSection - $keyCurrentSection + 1;

		$compareNextSections = array();
		$compareNextLenghts = array();
		$compareNextVMax = array();

		$keyCurrentOldSection = array_search($currentSection, $oldNextSections);
		$keyLastOldSection = array_key_last($oldNextSections);
		$oldNumber = $keyLastOldSection - $keyCurrentOldSection + 1;




		for($i = $keyCurrentSection; $i <= $keyLatestSection; $i++) {
			array_push($compareNextSections, $oldNextSections[$i]);
			array_push($compareNextLenghts, $oldLenghts[$i]);
			array_push($compareNextVMax, $oldNextVMax[$i]);
		}



		if (sizeof($newNextSection) != ($numberOfSection)) {
			$dataIsIdentical = false;
		} else {
			for ($i = 0; $i < $keyLatestSection - $keyCurrentSection; $i++) {
				if ($newNextSection[$i] != $compareNextSections[$i] || $newNextLenghts[$i] != $compareNextLenghts[$i]) { //|| $newNextVMax[$i] != $compareNextVMax[$i]
					//var_dump($i);
					$dataIsIdentical = false;
					break;
				}
			}
		}
		/*

		var_dump("################");
		var_dump($id);
		var_dump($oldNextSections);
		var_dump($newNextSection);
		var_dump($compareNextSections);
		var_dump($dataIsIdentical);
		var_dump("################");

		*/




		if (!$dataIsIdentical) {
			var_dump("Neue Berechnung");
			calculateNextSections($id);
			$adresse = $allTrains[$id]["adresse"];
			$allTimes[$adresse] = array();
			checkIfFahrstrasseIsCorrrect($id);
			calculateFahrverlauf($id);
			//var_dump($allTimes[6464]);
			//var_dump($allTimes[$adresse]);
		}
	}
}

function startMessage() {

	global $realStartTime;
	global $simulationEndTime;
	global $simulationDuration;
	global $realEndTime;
	global $simulationStartTime;

	$realStartTimeAsHHMMSS = getUhrzeit($realStartTime, "simulationszeit", null, array("outputtyp"=>"h:i:s"));
	$simulationEndTimeAsHHMMSS = getUhrzeit($simulationEndTime, "simulationszeit", null, array("outputtyp"=>"h:i:s"));
	$simulationDurationAsHHMMSS = toStd($simulationDuration);
	$realEndTimeAsHHMMSS = getUhrzeit($realEndTime, "simulationszeit", null, array("outputtyp"=>"h:i:s"));
	$simulationStartTimeAsHHMMSS = getUhrzeit($simulationStartTime, "simulationszeit", null, array("outputtyp"=>"h:i:s"));

	$fahrplanSessionkey = getDataFromFahrplanSession("sessionkey");
	$fahrplanSessionName = getDataFromFahrplanSession("name");

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
	echo "#\t Fahrplanname: \t\t\t\t\t", $fahrplanSessionName, "\t\t#\n";
	echo "#\t Sessionkey: \t\t\t\t\t", $fahrplanSessionkey, "\t\t\t\t\t#\n";
	echo $emptyLine;
	echo $hashtagLine, "\n\n";




	echo "simulationStartTime = getDataFromFahrplanSession('sim_startzeit') \n \n";
	echo getDataFromFahrplanSession("sim_startzeit"), "\n\n";
	echo "getUhrzeit('simulationStartTime', 'simulationszeit', null, array('outputtyp'=>'h:i:s'))\n\n";
	echo getUhrzeit(getDataFromFahrplanSession('sim_startzeit'), 'simulationszeit', null, array('outputtyp'=>'h:i:s')), "\n\n";




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

function getDataFromFahrplanSession(string $columnName) {

	$status = 1;

	$DB = new DB_MySQL();


	$simStartTime = $DB->select("SELECT `".DB_TABLE_FAHRPLAN_SESSION."`.`$columnName`    
                            FROM `".DB_TABLE_FAHRPLAN_SESSION."`
                            WHERE `".DB_TABLE_FAHRPLAN_SESSION."`.`status` = $status
                           ");

	unset($DB);

	if ($columnName == "sim_startzeit") {
		return $simStartTime[0]->sim_startzeit;
	} else if ($columnName == "real_startzeit"){
		return $simStartTime[0]->real_startzeit;
	} else if ($columnName == "sim_endzeit") {
		return $simStartTime[0]->sim_endzeit;
	} else if ($columnName == "name") {
		return $simStartTime[0]->name;
	} else if ($columnName == "sessionkey") {
		return $simStartTime[0]->sessionkey;
	} else {
		return false;
	}
}

function getFahrplanAndPosition () {

	global $fmaToInfra;
	global $infraToFma;
	global $cacheInfraLaenge;
	global $timeDifferenceGetUhrzeit;
	global $allTrains;
	global $cacheZwischenhaltepunkte;

	$returnArray = array();
	$checkAllTrains = true;

	$keysZwischenhalte = array_keys($cacheZwischenhaltepunkte);




	// Get all Zug IDs
	foreach ($allTrains as $trainIndex => $trainValue) {



		$allTrains[$trainIndex]["error"] = array();
		/*
		$allTrains[$trainIndex]["live_position"] = array();
		$allTrains[$trainIndex]["live_speed"] = array();
		$allTrains[$trainIndex]["live_time"] = array();
		$allTrains[$trainIndex]["live_relative_position"] = array();
		$allTrains[$trainIndex]["live_section"] = array();
		$allTrains[$trainIndex]["live_is_speed_change"] = array();
		$allTrains[$trainIndex]["live_target_reached"] = array();
		$allTrains[$trainIndex]["error"] = array();
		*/
		$allTrains[$trainIndex]["next_sections"] = array();
		$allTrains[$trainIndex]["next_lenghts"] = array();
		$allTrains[$trainIndex]["next_v_max"] = array();
		$allTrains[$trainIndex]["next_stop"] = array();
		$values = getFahrzeugZugIds(array($allTrains[$trainIndex]["id"]));
		if (sizeof($values) != 0) {
			$value = $values[array_key_first($values)];
			$allTrains[$trainIndex]["zug_id"] = intval($value["zug_id"]);
			$allTrains[$trainIndex]["operates_on_timetable"] = 1;

		} else {
			$allTrains[$trainIndex]["zug_id"] = null;
			$allTrains[$trainIndex]["operates_on_timetable"] = 0;
		}
	}

	//TODO: neu strukturieren, es wird schon überprüft, ob der Zug nach Fahrplan fährt... if statement nach operates_on_timetable aufbauen...

	// Get next Betriebsstellen
	foreach ($allTrains as $trainIndex => $trainValue) {
		$allTrains[$trainIndex]["next_betriebsstellen_data"] = array();
		$zug_id = intval($allTrains[$trainIndex]["zug_id"]);


		if ($zug_id != 0) {
			$nextBetriebsstellen = getNextBetriebsstellen($zug_id);
			if (sizeof($nextBetriebsstellen) != 0) {
				for ($i = 0; $i < sizeof($nextBetriebsstellen); $i++) {
					if (sizeof(explode("_", $nextBetriebsstellen[$i])) != 2) {
						$allTrains[$trainIndex]["next_betriebsstellen_data"][$i]["betriebstelle"] = $nextBetriebsstellen[$i];
						$allTrains[$trainIndex]["next_betriebsstellen_data"][$i]["zeiten"] = getFahrplanzeiten($nextBetriebsstellen[$i], $zug_id);
						$allTrains[$trainIndex]["next_betriebsstellen_data"][$i]["fahrplanhalt"] = true;
					} else if(in_array($nextBetriebsstellen[$i], $keysZwischenhalte)) {
						$allTrains[$trainIndex]["next_betriebsstellen_data"][$i]["betriebstelle"] = $nextBetriebsstellen[$i];
						$allTrains[$trainIndex]["next_betriebsstellen_data"][$i]["zeiten"] = getFahrplanzeiten($nextBetriebsstellen[$i], $zug_id);
						$allTrains[$trainIndex]["next_betriebsstellen_data"][$i]["fahrplanhalt"] = false;

					}
				}
				//$allTrains[$trainIndex]["next_betriebsstellen_name"] = $nextBetriebsstellen;
			} else {
				$allTrains[$trainIndex]["next_betriebsstellen_data"] = null;
				//$allTrains[$trainIndex]["next_betriebsstellen_name"] = null;
			}
		} else {
			$allTrains[$trainIndex]["next_betriebsstellen_data"] = array();

		}
		$allTrains[$trainIndex]["next_betriebsstellen_data"] = array_values($allTrains[$trainIndex]["next_betriebsstellen_data"]);
	}





	// Get the current position of all trains
	foreach ($allTrains as $trainIndex => $trainValue) {
		$fma = getPosition($trainValue["adresse"]);
		if (sizeof($fma) == 0) {
			$allTrains[$trainIndex]["current_fma_section"] = null;
			$allTrains[$trainIndex]["current_infra_section"] = null;
		} elseif (sizeof($fma) == 1) {
			$allTrains[$trainIndex]["current_fma_section"] = $fma[0];
			$allTrains[$trainIndex]["current_infra_section"] = $fmaToInfra[$fma[0]];
		} else {
			$infraArray = array();
			foreach ($fma as $value) {
				array_push($infraArray, $fmaToInfra[$value]);
			}
			$infra = getFrontPosition($infraArray, $allTrains[$trainIndex]["dir"]);
			$allTrains[$trainIndex]["current_fma_section"] = $infraToFma[$infra];
			$allTrains[$trainIndex]["current_infra_section"] = $infra;
		}
	}

	/*

	//check if Fahrstraße ist korrekt
	foreach ($allTrains as $trainIndex => $trainValue) {
		// get next betriebsstellen soll
		if ($allTrains[$trainIndex]["next_betriebsstellen_data"] != null) {
			$allTrains[$trainIndex]["next_betriebsstelle_soll"] = $allTrains[$trainIndex]["next_betriebsstellen_data"][0]["betriebstelle"];
		} else {
			$allTrains[$trainIndex]["next_betriebsstelle_soll"] = null;
		}
		// get next betriebsstelle ist
		if ($trainValue["current_infra_section"] != null) {
			$fahrstrassenData = getNaechsteAbschnitte($trainValue["current_infra_section"], $trainValue["dir"]);
			$allTrains[$trainIndex]["current_fahrstrasse_data"] = $fahrstrassenData;
			$nextIstBetriebsstellen = array();
			foreach ($fahrstrassenData as $data) {
				if ($data["signal_id"] != null) {
					array_push($nextIstBetriebsstellen, convertSignalIdToBetriebsstelle($data["signal_id"]));
				}
			}
			$allTrains[$trainIndex]["current_fahrstrasse_name"] = $nextIstBetriebsstellen;
		} else {
			$allTrains[$trainIndex]["current_fahrstrasse_name"][0] = null;
			$allTrains[$trainIndex]["current_fahrstrasse_data"][0] = null;
		}
	}

	foreach ($allTrains as $trainIndex => $trainValue) {
		if (($trainValue["current_fahrstrasse_name"][0] == $trainValue["next_betriebsstelle_soll"]) && $trainValue["current_fahrstrasse_name"][0] != null) {
			$allTrains[$trainIndex]["richtige_fahrstraße"] = 1;
		} else {
			$allTrains[$trainIndex]["richtige_fahrstraße"] = 0;
		}
	}
	*/

	// Remove trains, that are not on the tracks
	foreach ($allTrains as $train) {

		if ($train["current_fma_section"] != null) {
			$returnArray[intval($train["id"])] = $train;
		}

	}



	foreach ($returnArray as $trainIndex => $trainValue) {
		foreach ($trainValue["next_betriebsstellen_data"] as $betriebsstelleIndex => $betriebsstelleValue) {
			// bei getUhrzeit was vorher $timeDifferenceGetUhrzeit
			if ($betriebsstelleValue["zeiten"] != false) {
				if ($betriebsstelleValue["zeiten"]["abfahrt_soll"] != null) {
					$returnArray[$trainIndex]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["abfahrt_soll_timestamp"] = getUhrzeit($betriebsstelleValue["zeiten"]["abfahrt_soll"], "simulationszeit", null, array("inputtyp" => "h:i:s"));
				} else {
					$returnArray[$trainIndex]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["abfahrt_soll_timestamp"] = null;
				}

				if ($betriebsstelleValue["zeiten"]["ankunft_soll"] != null) {
					$returnArray[$trainIndex]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["ankunft_soll_timestamp"] = getUhrzeit($betriebsstelleValue["zeiten"]["ankunft_soll"], "simulationszeit", null, array("inputtyp" => "h:i:s"));
				} else {
					$returnArray[$trainIndex]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["ankunft_soll_timestamp"] = null;
				}

			}
		}
	}



	foreach ($returnArray as $trainIndex => $trainValue) {
		$returnArray[$trainIndex]["current_position"] = $cacheInfraLaenge[$trainValue["current_infra_section"]];
	}


	foreach ($returnArray as $trainIndex => $trainValue) {
		$returnArray[$trainIndex]["notverzoegerung"] = 2;
	}

	foreach ($returnArray as $trainIndex => $trainValue) {
		foreach ($trainValue["next_betriebsstellen_data"] as $betriebsstelleIndex => $betriebsstelleValue) {

			if ($betriebsstelleValue["zeiten"]["abfahrt_soll_timestamp"] != null && $betriebsstelleValue["zeiten"]["ankunft_soll_timestamp"] != null) {
				$returnArray[$trainIndex]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["haltezeit"] = $betriebsstelleValue["zeiten"]["abfahrt_soll_timestamp"] - $betriebsstelleValue["zeiten"]["ankunft_soll_timestamp"];
				if (($betriebsstelleValue["zeiten"]["abfahrt_soll_timestamp"] - $betriebsstelleValue["zeiten"]["ankunft_soll_timestamp"]) > 0) {
					$returnArray[$trainIndex]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["is_halt"] = true;
				} else {
					$returnArray[$trainIndex]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["is_halt"] = false;
				}
			} else {
				$returnArray[$trainIndex]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["haltezeit"] = 0;
				$returnArray[$trainIndex]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["is_halt"] = true;
			}
			$returnArray[$trainIndex]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["verspaetung"] = 0;

		}

	}

	$allTrains = $returnArray;

}

function getFahrplanAndPositionForOneTrain (int $id) {

	global $fmaToInfra;
	global $infraToFma;
	global $cacheInfraLaenge;
	global $timeDifferenceGetUhrzeit;
	global $allTrains;

	$returnArray = array();
	$checkAllTrains = true;




	$allTrains[$id]["error"] = array();
	/*
	$allTrains[$trainIndex]["live_position"] = array();
	$allTrains[$trainIndex]["live_speed"] = array();
	$allTrains[$trainIndex]["live_time"] = array();
	$allTrains[$trainIndex]["live_relative_position"] = array();
	$allTrains[$trainIndex]["live_section"] = array();
	$allTrains[$trainIndex]["live_is_speed_change"] = array();
	$allTrains[$trainIndex]["live_target_reached"] = array();
	$allTrains[$trainIndex]["error"] = array();
	*/
	$allTrains[$id]["next_sections"] = array();
	$allTrains[$id]["next_lenghts"] = array();
	$allTrains[$id]["next_v_max"] = array();
	$allTrains[$id]["next_stop"] = array();
	$values = getFahrzeugZugIds(array($allTrains[$id]["id"]));
	if (sizeof($values) != 0) {
		$value = $values[array_key_first($values)];
		$allTrains[$id]["zug_id"] = intval($value["zug_id"]);
		$allTrains[$id]["operates_on_timetable"] = 1;

	} else {
		$allTrains[$id]["zug_id"] = null;
		$allTrains[$id]["operates_on_timetable"] = 0;
	}

	// Get next Betriebsstellen
	$allTrains[$id]["next_betriebsstellen_data"] = array();
	$zug_id = intval($allTrains[$id]["zug_id"]);

	if ($zug_id != 0) {
		$nextBetriebsstellen = getNextBetriebsstellen($zug_id);
		if (sizeof($nextBetriebsstellen) != 0) {
			for ($i = 0; $i < sizeof($nextBetriebsstellen); $i++) {
				if (sizeof(explode("_", $nextBetriebsstellen[$i])) != 2) {
					$allTrains[$id]["next_betriebsstellen_data"][$i]["betriebstelle"] = $nextBetriebsstellen[$i];
					$allTrains[$id]["next_betriebsstellen_data"][$i]["zeiten"] = getFahrplanzeiten($nextBetriebsstellen[$i], $zug_id);
				}
			}
			//$allTrains[$trainIndex]["next_betriebsstellen_name"] = $nextBetriebsstellen;
		} else {
			$allTrains[$id]["next_betriebsstellen_data"] = null;
			//$allTrains[$trainIndex]["next_betriebsstellen_name"] = null;
		}
	} else {
		$allTrains[$id]["next_betriebsstellen_data"] = array();

	}
	$allTrains[$id]["next_betriebsstellen_data"] = array_values($allTrains[$id]["next_betriebsstellen_data"]);





	/*
	// Get the current position of all trains
	foreach ($allTrains as $trainIndex => $trainValue) {
		$fma = getPosition($trainValue["adresse"]);
		if (sizeof($fma) == 0) {
			$allTrains[$trainIndex]["current_fma_section"] = null;
			$allTrains[$trainIndex]["current_infra_section"] = null;
		} elseif (sizeof($fma) == 1) {
			$allTrains[$trainIndex]["current_fma_section"] = $fma[0];
			$allTrains[$trainIndex]["current_infra_section"] = $fmaToInfra[$fma[0]];
		} else {
			$infraArray = array();
			foreach ($fma as $value) {
				array_push($infraArray, $fmaToInfra[$value]);
			}
			$infra = getFrontPosition($infraArray, $allTrains[$trainIndex]["dir"]);
			$allTrains[$trainIndex]["current_fma_section"] = $infraToFma[$infra];
			$allTrains[$trainIndex]["current_infra_section"] = $infra;
		}
	}
	*/

	/*

	//check if Fahrstraße ist korrekt
	foreach ($allTrains as $trainIndex => $trainValue) {
		// get next betriebsstellen soll
		if ($allTrains[$trainIndex]["next_betriebsstellen_data"] != null) {
			$allTrains[$trainIndex]["next_betriebsstelle_soll"] = $allTrains[$trainIndex]["next_betriebsstellen_data"][0]["betriebstelle"];
		} else {
			$allTrains[$trainIndex]["next_betriebsstelle_soll"] = null;
		}
		// get next betriebsstelle ist
		if ($trainValue["current_infra_section"] != null) {
			$fahrstrassenData = getNaechsteAbschnitte($trainValue["current_infra_section"], $trainValue["dir"]);
			$allTrains[$trainIndex]["current_fahrstrasse_data"] = $fahrstrassenData;
			$nextIstBetriebsstellen = array();
			foreach ($fahrstrassenData as $data) {
				if ($data["signal_id"] != null) {
					array_push($nextIstBetriebsstellen, convertSignalIdToBetriebsstelle($data["signal_id"]));
				}
			}
			$allTrains[$trainIndex]["current_fahrstrasse_name"] = $nextIstBetriebsstellen;
		} else {
			$allTrains[$trainIndex]["current_fahrstrasse_name"][0] = null;
			$allTrains[$trainIndex]["current_fahrstrasse_data"][0] = null;
		}
	}

	foreach ($allTrains as $trainIndex => $trainValue) {
		if (($trainValue["current_fahrstrasse_name"][0] == $trainValue["next_betriebsstelle_soll"]) && $trainValue["current_fahrstrasse_name"][0] != null) {
			$allTrains[$trainIndex]["richtige_fahrstraße"] = 1;
		} else {
			$allTrains[$trainIndex]["richtige_fahrstraße"] = 0;
		}
	}
	*/

	/*
	// Remove trains, that are not on the tracks
	foreach ($allTrains as $train) {

		if ($train["current_fma_section"] != null) {
			$returnArray[intval($train["id"])] = $train;
		}

	}
	*/



	foreach ($allTrains[$id]["next_betriebsstellen_data"] as $betriebsstelleIndex => $betriebsstelleValue) {
		if ($betriebsstelleValue["zeiten"] != false) {
			if ($betriebsstelleValue["zeiten"]["abfahrt_soll"] != null) {
				$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["abfahrt_soll_timestamp"] = getUhrzeit($betriebsstelleValue["zeiten"]["abfahrt_soll"], "simulationszeit", $timeDifferenceGetUhrzeit, array("inputtyp" => "h:i:s"));
			} else {
				$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["abfahrt_soll_timestamp"] = null;
			}

			if ($betriebsstelleValue["zeiten"]["ankunft_soll"] != null) {
				$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["ankunft_soll_timestamp"] = getUhrzeit($betriebsstelleValue["zeiten"]["ankunft_soll"], "simulationszeit", $timeDifferenceGetUhrzeit, array("inputtyp" => "h:i:s"));
			} else {
				$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["ankunft_soll_timestamp"] = null;
			}

		}
	}



	/*
	foreach ($returnArray as $trainIndex => $trainValue) {
		$returnArray[$trainIndex]["current_position"] = $cacheInfraLaenge[$trainValue["current_infra_section"]];
	}
	*/


	foreach ($returnArray as $trainIndex => $trainValue) {
		$returnArray[$trainIndex]["notverzoegerung"] = 2;
	}

	foreach ($allTrains[$id]["next_betriebsstellen_data"] as $betriebsstelleIndex => $betriebsstelleValue) {
		if ($betriebsstelleValue["zeiten"]["abfahrt_soll_timestamp"] != null && $betriebsstelleValue["zeiten"]["ankunft_soll_timestamp"] != null) {
			$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["haltezeit"] = $betriebsstelleValue["zeiten"]["abfahrt_soll_timestamp"] - $betriebsstelleValue["zeiten"]["ankunft_soll_timestamp"];
			if (($betriebsstelleValue["zeiten"]["abfahrt_soll_timestamp"] - $betriebsstelleValue["zeiten"]["ankunft_soll_timestamp"]) > 0) {
				$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["is_halt"] = true;
			} else {
				$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["is_halt"] = false;
			}
		} else {
			$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["haltezeit"] = 0;
			$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["is_halt"] = true;
		}
		$allTrains[$id]["next_betriebsstellen_data"][$betriebsstelleIndex]["zeiten"]["verspaetung"] = 0;
	}



}

function compareFahrstrasse(array $arrayOne, array $arrayTwo) {



}

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
	} else {
		//$signal = getNaechsteAbschnitte($section, $dir, array("naechstessignal" => true));
		//$signal = $signal[array_key_last($signal)]["signal_id"];
	}

	return $signal;

}

function calculateNextSections($id = false, $writeResultToTrain = true) {

	global $allTrains;
	global $cacheInfraLaenge;
	global $realStartTime;

	$checkAllTrains = true;

	if ($id != false) {
		$checkAllTrains = false;
	}

	foreach ($allTrains as $trainIndex => $trainValue) {

		if ($checkAllTrains || $trainValue["id"] == $id) {

			if ($trainValue["can_drive"]) {
				$dir = $trainValue["dir"];
				$currentSection = $trainValue["current_infra_section"];
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
				$allTrains[$trainIndex]["last_get_naechste_abschnitte"] = $return;
				$currentVMax = 60; // max speed for a train in the current section

				array_push($nextSections, $currentSection);
				array_push($nextVMax, $currentVMax);
				array_push($nextLengths, $cacheInfraLaenge[$currentSection]);

				if (isset($nextSignalbegriff)) {
					$currentVMax = $nextSignalbegriff;
				}



				if ($currentVMax == 0) {
					if ($trainValue["id"] == 78) {
						/*
						if ($realStartTime + 2 < microtime(true) ) {
							$nextSections = array();
							$nextVMax = array();
							$nextLengths = array();

							array_push($nextSections, 1187);
							array_push($nextLengths, $cacheInfraLaenge[1187]);
							array_push($nextVMax, 120);
							array_push($nextSections, 1185);
							array_push($nextLengths, $cacheInfraLaenge[1185]);
							array_push($nextVMax, 120);
							array_push($nextSections, 1184);
							array_push($nextLengths, $cacheInfraLaenge[1184]);
							array_push($nextVMax, 120);
							array_push($nextSections, 1183);
							array_push($nextLengths, $cacheInfraLaenge[1183]);
							array_push($nextVMax, 120);
							array_push($nextSections, 1182);
							array_push($nextLengths, $cacheInfraLaenge[1182]);
							array_push($nextVMax, 120);
							array_push($nextSections, 1181);
							array_push($nextLengths, $cacheInfraLaenge[1181]);
							array_push($nextVMax, 120);
							array_push($nextSections, 1176);
							array_push($nextLengths, $cacheInfraLaenge[1176]);
							array_push($nextVMax, 120);
							array_push($nextSections, 1175);
							array_push($nextLengths, $cacheInfraLaenge[1175]);
							array_push($nextVMax, 120);
						}
						*/
					}
					if ($writeResultToTrain) {
						$allTrains[$trainIndex]["next_sections"] = $nextSections;
						$allTrains[$trainIndex]["next_lenghts"] = $nextLengths;
						$allTrains[$trainIndex]["next_v_max"] = $nextVMax;
					} else {
						return array($nextSections, $nextLengths, $nextVMax);
					}
				} else {
					if ($trainValue["id"] == 78) {
						/*
						if ($realStartTime + 2 < microtime(true)) {
							$nextSections = array();
							$nextVMax = array();
							$nextLengths = array();

							array_push($nextSections, 1187);
							array_push($nextLengths, $cacheInfraLaenge[1187]);
							array_push($nextVMax, 120);
							array_push($nextSections, 1185);
							array_push($nextLengths, $cacheInfraLaenge[1185]);
							array_push($nextVMax, 120);
							array_push($nextSections, 1184);
							array_push($nextLengths, $cacheInfraLaenge[1184]);
							array_push($nextVMax, 120);
							array_push($nextSections, 1183);
							array_push($nextLengths, $cacheInfraLaenge[1183]);
							array_push($nextVMax, 120);
							array_push($nextSections, 1182);
							array_push($nextLengths, $cacheInfraLaenge[1182]);
							array_push($nextVMax, 120);
							array_push($nextSections, 1181);
							array_push($nextLengths, $cacheInfraLaenge[1181]);
							array_push($nextVMax, 120);
							array_push($nextSections, 1176);
							array_push($nextLengths, $cacheInfraLaenge[1176]);
							array_push($nextVMax, 120);
							array_push($nextSections, 1175);
							array_push($nextLengths, $cacheInfraLaenge[1175]);
							array_push($nextVMax, 120);
						}
						*/
					}
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
						$allTrains[$trainIndex]["next_sections"] = $nextSections;
						$allTrains[$trainIndex]["next_lenghts"] = $nextLengths;
						$allTrains[$trainIndex]["next_v_max"] = $nextVMax;
					} else {
						return array($nextSections, $nextLengths, $nextVMax);
					}
				}
			}
		}
	}
}




function getAllTrains () : array {

	$allAdresses = getAllAdresses();
	$DB = new DB_MySQL();
	$allTrains = array();

	foreach ($allAdresses as $adress) {

		$train = get_object_vars($DB->select("SELECT `".DB_TABLE_FAHRZEUGE."`.`id`, 
							`".DB_TABLE_FAHRZEUGE."`.`adresse`, 
							`".DB_TABLE_FAHRZEUGE."`.`timestamp`, 
							`".DB_TABLE_FAHRZEUGE."`.`speed`, 
							`".DB_TABLE_FAHRZEUGE."`.`dir`, 
							`".DB_TABLE_FAHRZEUGE."`.`zugtyp`, 
							`".DB_TABLE_FAHRZEUGE."`.`zuglaenge`, 
							`".DB_TABLE_FAHRZEUGE."`.`prev_speed`, 
							`".DB_TABLE_FAHRZEUGE."`.`fzs`, 
							`".DB_TABLE_FAHRZEUGE."`.`verzoegerung`, 
							`".DB_TABLE_FAHRZEUGE."`.`zustand`        
                            FROM `".DB_TABLE_FAHRZEUGE."`
                            WHERE `".DB_TABLE_FAHRZEUGE."`.`adresse` = $adress
                           ")[0]);

		$trainTwo = $DB->select("SELECT `".DB_TABLE_FAHRZEUGE_BAUREIHEN."`.`bezeichnung`, 
							`".DB_TABLE_FAHRZEUGE_BAUREIHEN."`.`vmax`, 
							`".DB_TABLE_FAHRZEUGE_BAUREIHEN."`.`traktion`      
                            FROM `".DB_TABLE_FAHRZEUGE_BAUREIHEN."`
                            WHERE `".DB_TABLE_FAHRZEUGE_BAUREIHEN."`.`nummer` = $adress
                           ");

		if (sizeof($trainTwo) != 0) {
			$trainTwo = get_object_vars($trainTwo[0]);
		} else {
			$trainTwo["bezeichnung"] = null;
			$trainTwo["vmax"] = null;
			$trainTwo["traktion"] = null;
		}

		$returnArray = array_merge($train, $trainTwo);

		array_push($allTrains, $returnArray);
	}

	unset($DB);

	return $allTrains;
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

