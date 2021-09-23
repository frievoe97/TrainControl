<?php

// Ermittelt die Längen aller Infra-Abschnitte
function createcacheInfraLaenge() {
	$DB = new DB_MySQL();
	$returnArray = array();
	$infralaenge = $DB->select('SELECT `'.DB_TABLE_INFRAZUSTAND.'`.`id`, `'.DB_TABLE_INFRAZUSTAND.'`.`laenge` FROM `'.DB_TABLE_INFRAZUSTAND.'` WHERE `'.DB_TABLE_INFRAZUSTAND."`.`type` = '".'gleis'."'");
	unset($DB);
	foreach ($infralaenge as $data) {
		if ($data->laenge != null) {
			$returnArray[$data->id] = intval($data->laenge);
		}
	}
	return $returnArray;
}

// Ermittelt die zugehörigen Infra-Abschnitte zu den Betriebsstellen
function createCacheHaltepunkte() {

	$DB = new DB_MySQL();
	$returnArray = array();

	$betriebsstellen = $DB->select('SELECT `'.DB_TABLE_BETRIEBSSTELLEN_DATEN.'`.`parent_kuerzel` FROM `'.DB_TABLE_BETRIEBSSTELLEN_DATEN.'` WHERE `'.DB_TABLE_BETRIEBSSTELLEN_DATEN.'`.`parent_kuerzel` IS NOT NULL');
	unset($DB);

	foreach ($betriebsstellen as $betriebsstelle) {
		$returnArray[$betriebsstelle->parent_kuerzel][0] = array();
		$returnArray[$betriebsstelle->parent_kuerzel][1] = array();
	}

	foreach ($returnArray as $betriebsstelleKey => $betriebsstelleValue) {
		$DB = new DB_MySQL();
		$name = $betriebsstelleKey;
		$name .= '%';
		$asig = 'ASig';
		$bksig = 'BkSig';
		$vsig = 'VSig';
		$ja = 'ja';
		if ($betriebsstelleKey == 'XAB' || $betriebsstelleKey == 'XBL') {
			$haltepunkte = $DB->select('SELECT `'.DB_TABLE_SIGNALE_STANDORTE.'`.`freimelde_id`, `'.DB_TABLE_SIGNALE_STANDORTE.'`.`wirkrichtung` FROM `'.DB_TABLE_SIGNALE_STANDORTE.'` WHERE `'.DB_TABLE_SIGNALE_STANDORTE."`.`betriebsstelle` LIKE '$name' AND `".DB_TABLE_SIGNALE_STANDORTE.'`.`freimelde_id` IS NOT NULL AND `'.DB_TABLE_SIGNALE_STANDORTE."`.`fahrplanhalt` = '$ja'");
			unset($DB);
		} else if ($betriebsstelleKey == 'XTS') {
			$haltepunkte = $DB->select('SELECT `'.DB_TABLE_SIGNALE_STANDORTE.'`.`freimelde_id`, `'.DB_TABLE_SIGNALE_STANDORTE.'`.`wirkrichtung` FROM `'.DB_TABLE_SIGNALE_STANDORTE.'` WHERE `'.DB_TABLE_SIGNALE_STANDORTE."`.`betriebsstelle` LIKE '$name' AND `".DB_TABLE_SIGNALE_STANDORTE.'`.`freimelde_id` IS NOT NULL AND `'. DB_TABLE_SIGNALE_STANDORTE . "`.`signaltyp` = '$bksig'");
			unset($DB);
		} else if ($betriebsstelleKey == 'XLG') {
			$haltepunkte = $DB->select('SELECT `'.DB_TABLE_SIGNALE_STANDORTE.'`.`freimelde_id`, `'.DB_TABLE_SIGNALE_STANDORTE.'`.`wirkrichtung` FROM `'.DB_TABLE_SIGNALE_STANDORTE.'` WHERE `'.DB_TABLE_SIGNALE_STANDORTE."`.`betriebsstelle` LIKE '$name' AND `".DB_TABLE_SIGNALE_STANDORTE.'`.`freimelde_id` IS NOT NULL AND `'. DB_TABLE_SIGNALE_STANDORTE . "`.`signaltyp` != '$vsig'");
			unset($DB);
		} else {
			$haltepunkte = $DB->select('SELECT `'. DB_TABLE_SIGNALE_STANDORTE .'`.`freimelde_id`, `'. DB_TABLE_SIGNALE_STANDORTE .'`.`wirkrichtung` FROM `'. DB_TABLE_SIGNALE_STANDORTE .'` WHERE `'. DB_TABLE_SIGNALE_STANDORTE . "`.`betriebsstelle` LIKE '$name' AND `" . DB_TABLE_SIGNALE_STANDORTE .'`.`freimelde_id` IS NOT NULL AND `'. DB_TABLE_SIGNALE_STANDORTE . "`.`signaltyp` = '$asig'");
			unset($DB);
		}

		foreach ($haltepunkte as $haltepunkt) {
			if ($haltepunkt->wirkrichtung == 0) {
				array_push($returnArray[$betriebsstelleKey][0], intval($haltepunkt->freimelde_id));
			} elseif ($haltepunkt->wirkrichtung == 1) {
				array_push($returnArray[$betriebsstelleKey][1], intval($haltepunkt->freimelde_id));
			}
		}
	}
	$returnArray['XSC'][1] = array(734, 732, 735, 733, 692); // In der Datenbank ist für Richtung 1 für diese Abschnitte fahrplanhalt auf nein eingestellt
	return $returnArray;
}

// Ermittelt die zugehörigen Infra-Abschnitte zu den Zwischen-Betriebsstellen
function createChacheZwischenhaltepunkte() {
	$DB = new DB_MySQL();
	$allZwischenhalte = array();
	$returnArray = array();
	$zwischenhalte = $DB->select('SELECT DISTINCT `'.DB_TABLE_SIGNALE_STANDORTE.'`.`betriebsstelle` FROM `'.DB_TABLE_SIGNALE_STANDORTE.'` WHERE `'.DB_TABLE_SIGNALE_STANDORTE.'`.`betriebsstelle` IS NOT NULL');
	unset($DB);
	foreach ($zwischenhalte as $halt) {
		array_push($allZwischenhalte, $halt->betriebsstelle);
	}
	foreach ($allZwischenhalte as $halt) {
		$DB = new DB_MySQL();
		$zwischenhalte = $DB->select('SELECT `'.DB_TABLE_SIGNALE_STANDORTE.'`.`freimelde_id` FROM `'.DB_TABLE_SIGNALE_STANDORTE.'` WHERE `'.DB_TABLE_SIGNALE_STANDORTE."`.`betriebsstelle` = '$halt' AND `".DB_TABLE_SIGNALE_STANDORTE.'`.`freimelde_id` IS NOT NULL');
		unset($DB);
		if (sizeof($zwischenhalte) == 1) {
			if (sizeof(explode('_', $halt)) == 2) {
				$returnArray[$halt] = intval($zwischenhalte[0]->freimelde_id);
			}
		}
	}
	return $returnArray;
}

function createCacheInfraToGbt () {
	$DB = new DB_MySQL();
	$infraArray = array();
	$returnArray = array();
	$allInfra = $DB->select('SELECT  `'.DB_TABLE_FMA_GBT.'`.`infra_id` FROM `'.DB_TABLE_FMA_GBT.'` WHERE `'.DB_TABLE_FMA_GBT.'`.`infra_id` IS NOT NULL');
	unset($DB);
	foreach ($allInfra as $infra) {
		array_push($infraArray, intval($infra->infra_id));
	}
	foreach ($infraArray as  $infra) {
		$DB = new DB_MySQL();
		$gbt = $DB->select('SELECT  `'.DB_TABLE_FMA_GBT.'`.`gbt_id` FROM `'.DB_TABLE_FMA_GBT.'` WHERE `'.DB_TABLE_FMA_GBT."`.`infra_id` = '$infra'")[0]->gbt_id;
		unset($DB);
		$returnArray[$infra] = intval($gbt);
	}
	return $returnArray;
}

function createCacheGbtToInfra () {

	$DB = new DB_MySQL();

	$returnArray = array();

	$allGbt = $DB->select('SELECT DISTINCT `'.DB_TABLE_FMA_GBT.'`.`gbt_id` FROM `'.DB_TABLE_FMA_GBT.'` WHERE `'.DB_TABLE_FMA_GBT.'`.`gbt_id` IS NOT NULL');
	unset($DB);

	foreach ($allGbt as  $gbt) {
		$DB = new DB_MySQL();
		$gbt = $gbt->gbt_id;
		$infras = $DB->select('SELECT  `'.DB_TABLE_FMA_GBT.'`.`infra_id` FROM `'.DB_TABLE_FMA_GBT.'` WHERE `'.DB_TABLE_FMA_GBT."`.`gbt_id` = '$gbt'");
		unset($DB);
		$returnArray[$gbt] = array();
		foreach ($infras as $infra) {
			array_push($returnArray[$gbt], intval($infra->infra_id));
		}
	}
	return $returnArray;
}

function createCacheFmaToInfra () {
	$DB = new DB_MySQL();
	$returnArray = array();
	$fmaToInfra = $DB->select('SELECT `'.DB_TABLE_FMA_GBT.'`.`infra_id`, `'.DB_TABLE_FMA_GBT.'`.`fma_id` FROM `'.DB_TABLE_FMA_GBT.'` WHERE `'.DB_TABLE_FMA_GBT.'`.`fma_id` IS NOT NULL');
	unset($DB);
	foreach ($fmaToInfra as $value) {
		$returnArray[intval($value->fma_id)] = intval($value->infra_id);
	}
	return $returnArray;
}

function createCacheToBetriebsstelle() {
	$DB = new DB_MySQL();
	$returnArray = array();
	$fmaToInfra = $DB->select('SELECT `'.DB_TABLE_SIGNALE_STANDORTE.'`.`id`, `'.DB_TABLE_SIGNALE_STANDORTE.'`.`betriebsstelle` FROM `'.DB_TABLE_SIGNALE_STANDORTE.'`');
	unset($DB);
	foreach ($fmaToInfra as $value) {
		$returnArray[intval($value->id)] = $value->betriebsstelle;
	}
	return $returnArray;
}

function createCacheFahrzeugeAbschnitte () {
	$DB = new DB_MySQL();
	$returnArray = array();
	$fahrzeugeAbschnitte = $DB->select('SELECT `'.DB_TABLE_FAHRZEUGE_ABSCHNITTE.'`.`fahrzeug_id`, `'.DB_TABLE_FAHRZEUGE_ABSCHNITTE.'`.`infra_id`, `'.DB_TABLE_FAHRZEUGE_ABSCHNITTE.'`.`unixtimestamp` FROM `'.DB_TABLE_FAHRZEUGE_ABSCHNITTE.'`');
	unset($DB);
	foreach ($fahrzeugeAbschnitte as $fahrzeug) {
		$returnArray[intval($fahrzeug->fahrzeug_id)]['infra_id'] = intval($fahrzeug->infra_id);
		$returnArray[intval($fahrzeug->fahrzeug_id)]['unixtimestamp'] = intval($fahrzeug->unixtimestamp);
	}
	return $returnArray;
}

function createCacheDecoderToAdresse () {
	$DB = new DB_MySQL();
	$returnArray = array();
	$decoderToAdresse = $DB->select('SELECT `'.DB_TABLE_FAHRZEUGE.'`.`id`, `'.DB_TABLE_FAHRZEUGE.'`.`adresse` FROM `'.DB_TABLE_FAHRZEUGE.'`');
	unset($DB);
	foreach ($decoderToAdresse as $fahrzeug) {
		$returnArray[intval($fahrzeug->id)] = intval($fahrzeug->adresse);
	}
	return $returnArray;
}

function createCacheFahrplanSession() {
	$DB = new DB_MySQL();
	$fahrplanData = $DB->select('SELECT * FROM `'.DB_TABLE_FAHRPLAN_SESSION.'` WHERE `'.DB_TABLE_FAHRPLAN_SESSION."`.`status` = '".'1'."'");
	unset($DB);

	return $fahrplanData[0];
}
