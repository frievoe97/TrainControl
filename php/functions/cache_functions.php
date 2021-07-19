<?php

// Caching der Nachbardaten
function createCacheInfranachbarn () {

	$DB = new DB_MySQL();
	$infraliste = $DB->select("SELECT `id`, `infra_id`, `laenge`, `weiche_id`, `nachbar0_0`,`nachbar0_1`,`nachbar1_0`,`nachbar1_1`
FROM `".DB_TABLE_INFRA_NACHBARN."`
");
	unset($DB);

	$nachbarn = array();

	if ($infraliste && count($infraliste) > 0) {
		foreach ($infraliste as $nachbar) {
			$nachbarn[$nachbar->id] = array("id" => $nachbar->id,
				"weiche_id" => $nachbar->weiche_id,
				"infra_id" => $nachbar->infra_id,
				"laenge" => $nachbar->laenge,
				"nachbar0_0" => $nachbar->nachbar0_0,
				"nachbar0_1" => $nachbar->nachbar0_1,
				"nachbar1_0" => $nachbar->nachbar1_0,
				"nachbar1_1" => $nachbar->nachbar1_1);
		}
	}

	return $nachbarn;
}

// Caching von Infrazustand-Daten
function createCacheInfradaten($id = false) {
	$cacheInfradaten = array();

// Wenn ein Array von IDs mitgeliefert wird, wird auf diese gefiltert (genutzt, wenn der Cache aus der GUI heraus aufgebaut wird)
	if (isset($id) && is_array($id)) {
		$where = "WHERE `id` IN (".implode(",",$id).")";
	} else {
		$where = "";
	}

	$DB = new DB_MySQL();
	$infraliste = $DB->select("SELECT `id`, `address`, `type`, `wrm_aktiv`, `laenge`,
`betriebsstelle`, `signalstandort_id`, `freimeldeabschnitt_id`,
`weichenabhaengigkeit_id`, `kurzbezeichnung`
FROM `".DB_TABLE_INFRAZUSTAND."`
".$where."
");
	unset($DB);

	if ($infraliste && count($infraliste) > 0) {
		foreach ($infraliste as $element) {
			$cacheInfradaten[$element->id] = array("address" => $element->address,
				"betriebsstelle" => $element->betriebsstelle,
				"freimeldeabschnitt_id" => $element->freimeldeabschnitt_id,
				"type" => $element->type, "laenge" => $element->laenge
			);

// Gleisabschnitte haben weitere Eigenschaften
			if ($element->type == "gleis") {
// Ermittele die Nachbarn dieses Elementes (sofern es ein Freimeldeabschnitt ist)
				$nachbarn = getInfraNachbarn($element->id,"infra_id");

				if ($nachbarn) {
					$cacheInfradaten[$element->id]["nachbarn"] = $nachbarn;
				}

// Ermittele Signale, die an diesem Abschnitt stehen


			}
		}
	}

	return $cacheInfradaten;
}

// Caching der Signaldaten
// Die einzelnen gecachten Daten liegen im Array $cacheSignaldaten["standorte"] und ["begriffe"] und ["rotlampen"] (für ein Array, aufgebaut nach der für die ZN entscheidenden Signallampe)
// ------------------------------------------------------------------------------------------------
function createCacheSignaldaten() {
	$cacheSignaldaten = array();
	$cacheSignaldaten["standorte"] = array();
	$cacheSignaldaten["begriffe"]  = array();
	$cacheSignaldaten["elemente"]  = array();
	$cacheSignaldaten["rotlampen"] = array();
	$cacheSignaldaten["befehlslampen"] = array();

	$DB = new DB_MySQL();

// Signalbegriffe
	$signalbegriffdaten = $DB->select("SELECT `".DB_TABLE_SIGNALE_BEGRIFFE."`.`id` AS `signalbegriff_id`,
`".DB_TABLE_SIGNALE_BEGRIFFE."`.`signal_id` AS `sid`,
`".DB_TABLE_SIGNALE_BEGRIFFE."`.`geschwindigkeit`,
`".DB_TABLE_SIGNALE_BEGRIFFE."`.`begriff`,
`".DB_TABLE_SIGNALE_BEGRIFFE."`.`adresse`,
`".DB_TABLE_SIGNALE_BEGRIFFE."`.`webstw_farbe`,
`".DB_TABLE_SIGNALE_BEGRIFFE."`.`is_zugfahrtbegriff`,
`".DB_TABLE_SIGNALE_BEGRIFFE."`.`zielentfernung`,
`".DB_TABLE_SIGNALE_BEGRIFFE."`.`zielgeschwindigkeit`,
`".DB_TABLE_SIGNALE_BEGRIFFE."`.`original_begriff_id`
FROM `".DB_TABLE_SIGNALE_BEGRIFFE."`
ORDER BY `".DB_TABLE_SIGNALE_BEGRIFFE."`.`id`
");

// Rot-Lampen (zu ermitteln über die Infra-ID mit Typ "Startsignal")
	$fahrstrassensignalbegriffdaten = $DB->select("SELECT `".DB_TABLE_FAHRSTRASSEN_ELEMENTE."`.`infra_id`,
`".DB_TABLE_SIGNALE_STANDORTE."`.`betriebsstelle`,
`".DB_TABLE_SIGNALE_STANDORTE."`.`signaltyp`,
`".DB_TABLE_SIGNALE_STANDORTE."`.`id` AS `signalstandort_id`
FROM `".DB_TABLE_FAHRSTRASSEN_ELEMENTE."`
LEFT JOIN `".DB_TABLE_INFRAZUSTAND."`
ON (  `".DB_TABLE_FAHRSTRASSEN_ELEMENTE."`.`infra_id` = `".DB_TABLE_INFRAZUSTAND."`.`id`)
LEFT JOIN `".DB_TABLE_SIGNALE_STANDORTE."`
ON (  `".DB_TABLE_INFRAZUSTAND."`.`signalstandort_id` = `".DB_TABLE_SIGNALE_STANDORTE."`.`id`)
WHERE `".DB_TABLE_FAHRSTRASSEN_ELEMENTE."`.`dir` = 9
GROUP BY `".DB_TABLE_FAHRSTRASSEN_ELEMENTE."`.`infra_id`
ORDER BY `".DB_TABLE_FAHRSTRASSEN_ELEMENTE."`.`infra_id`
");

	foreach ($fahrstrassensignalbegriffdaten AS $fahrstrassensignalbegriff) {
		$fahrstrassendaten = $DB->select("SELECT `".DB_TABLE_FAHRSTRASSEN_ELEMENTE."`.`fahrstrassen_id` FROM `".DB_TABLE_FAHRSTRASSEN_ELEMENTE."` WHERE `".DB_TABLE_FAHRSTRASSEN_ELEMENTE."`.`infra_id` = '".$fahrstrassensignalbegriff->infra_id."'");
		$fahrstrassenliste = array();

		foreach ($fahrstrassendaten as $fahrstrasse) {
			$fahrstrassenliste[] = $fahrstrasse->fahrstrassen_id;
		}

		$cacheSignaldaten["rotlampen"][$fahrstrassensignalbegriff->infra_id] = array("betriebsstelle" => $fahrstrassensignalbegriff->betriebsstelle,
			"signalstandort_id" => $fahrstrassensignalbegriff->signalstandort_id,
			"signaltyp" => $fahrstrassensignalbegriff->signaltyp,
			"fahrstrassen_id_liste" => $fahrstrassenliste);

		$befehlsdaten = $DB->select("SELECT `id` FROM `".DB_TABLE_INFRAZUSTAND."` WHERE `".DB_TABLE_INFRAZUSTAND."`.`signalstandort_id` = '".$fahrstrassensignalbegriff->signalstandort_id."' AND `".DB_TABLE_INFRAZUSTAND."`.`type` = 'befehlssignal'");

		if ($befehlsdaten && count($befehlsdaten) > 0) {
// Die Befehlslampen werden für Zs1, Zs7, Zs8 und schriftliche Befehle (u.a. in der ZNS) verwendet
			$cacheSignaldaten["befehlslampen"][$befehlsdaten[0]->id] = array("betriebsstelle" => $fahrstrassensignalbegriff->betriebsstelle,
				"signalstandort_id" => $fahrstrassensignalbegriff->signalstandort_id,
				"signaltyp" => $fahrstrassensignalbegriff->signaltyp,
				"fahrstrassen_id_liste" => $fahrstrassenliste);
			unset ($befehlsdaten);
		}

		unset($fahrstrassenliste);
	}

// Signalstandorte
	$signalstandortliste = $DB->select("SELECT `".DB_TABLE_SIGNALE_STANDORTE."`.`id`,
`".DB_TABLE_SIGNALE_STANDORTE."`.`haltfall_id`,
`".DB_TABLE_SIGNALE_STANDORTE."`.`haltbegriff_id`,
`".DB_TABLE_SIGNALE_STANDORTE."`.`freimelde_id`,
`".DB_TABLE_SIGNALE_STANDORTE."`.`signaltyp`,
`".DB_TABLE_SIGNALE_STANDORTE."`.`haltabschnitt_id`,
`".DB_TABLE_SIGNALE_STANDORTE."`.`wirkrichtung`,
`".DB_TABLE_SIGNALE_STANDORTE."`.`fahrplanhalt`,
`".DB_TABLE_BETRIEBSSTELLEN_DATEN."`.`kuerzel`,
`".DB_TABLE_BETRIEBSSTELLEN_DATEN."`.`parent_kuerzel`
FROM `".DB_TABLE_SIGNALE_STANDORTE."`
LEFT JOIN `".DB_TABLE_BETRIEBSSTELLEN_DATEN."` ON (`".DB_TABLE_SIGNALE_STANDORTE."`.`betriebsstelle` = `".DB_TABLE_BETRIEBSSTELLEN_DATEN."`.`kuerzel`) ");

	foreach ($signalbegriffdaten as $signalbegriff) {
		$cacheSignaldaten["begriffe"][$signalbegriff->signalbegriff_id] = array ("geschwindigkeit" => $signalbegriff->geschwindigkeit,
			"sid" => $signalbegriff->sid,
			"begriff" => $signalbegriff->begriff,
			"webstw_farbe" => $signalbegriff->webstw_farbe,
			"zielentfernung" => $signalbegriff->zielentfernung,
			"zielgeschwindigkeit" => $signalbegriff->zielgeschwindigkeit,
			"is_zugfahrtbegriff" =>  $signalbegriff->is_zugfahrtbegriff,
			"original_begriff_id" => $signalbegriff->original_begriff_id,
			"adresse" => $signalbegriff->adresse);

		$cacheSignaldaten["standorte"][$signalbegriff->sid]["begriffe_id"][] = $signalbegriff->signalbegriff_id;

		unset($signalelemente_array);
	}

// Abruf der Daten über $cacheSignaldaten["begriffe"]["signalbegriff_id"]: alle Elemente liegen in type / adresse / dir

	foreach ($signalstandortliste as $signalstandorteintrag) {
		$cacheSignaldaten["standorte"][$signalstandorteintrag->id]["haltfall_id"]      = $signalstandorteintrag->haltfall_id;
		$cacheSignaldaten["standorte"][$signalstandorteintrag->id]["freimelde_id"]     = $signalstandorteintrag->freimelde_id;
		$cacheSignaldaten["standorte"][$signalstandorteintrag->id]["signaltyp"]        = $signalstandorteintrag->signaltyp;
		$cacheSignaldaten["standorte"][$signalstandorteintrag->id]["haltbegriff_id"]   = $signalstandorteintrag->haltbegriff_id;
		$cacheSignaldaten["standorte"][$signalstandorteintrag->id]["haltabschnitt_id"] = $signalstandorteintrag->haltabschnitt_id;
		$cacheSignaldaten["standorte"][$signalstandorteintrag->id]["fahrplanhalt"]     = $signalstandorteintrag->fahrplanhalt;

		if ($signalstandorteintrag->parent_kuerzel != NULL) {
			$cacheSignaldaten["standorte"][$signalstandorteintrag->id]["betriebsstelle"] = $signalstandorteintrag->parent_kuerzel;
		} else {
			$cacheSignaldaten["standorte"][$signalstandorteintrag->id]["betriebsstelle"] = $signalstandorteintrag->kuerzel;
		}
		$cacheSignaldaten["standorte"][$signalstandorteintrag->id]["signalbetriebsstelle"] = $signalstandorteintrag->kuerzel;

		$cacheSignaldaten["freimeldeabschnitte"][$signalstandorteintrag->freimelde_id][$signalstandorteintrag->wirkrichtung]["signalstandort_id"] = $signalstandorteintrag->id;
	}

// Signallampen
	$signallampenliste = $DB->select("SELECT `".DB_TABLE_SIGNALE_ELEMENTE."`.`signal_id` AS `begriff_id`,
`".DB_TABLE_SIGNALE_ELEMENTE."`.`infra_id`,
`".DB_TABLE_SIGNALE_ELEMENTE."`.`dir`,
`".DB_TABLE_INFRAZUSTAND."`.`address` AS `infra_adresse`,
`".DB_TABLE_INFRAZUSTAND."`.`type` AS `infra_type`,
`".DB_TABLE_INFRADATEN."`.`wert` AS `infra_zusatzwert`
FROM `".DB_TABLE_SIGNALE_ELEMENTE."`
LEFT JOIN `".DB_TABLE_INFRAZUSTAND."`
ON (`".DB_TABLE_SIGNALE_ELEMENTE."`.`infra_id` = `".DB_TABLE_INFRAZUSTAND."`.`id`)
LEFT JOIN `".DB_TABLE_INFRADATEN."`
ON (`".DB_TABLE_SIGNALE_ELEMENTE."`.`infra_id` = `".DB_TABLE_INFRADATEN."`.`infra_id`)
");

	foreach ($signallampenliste as $signalelement) {
		$cacheSignaldaten["elemente"][$signalelement->begriff_id][] = array("infra_id" => $signalelement->infra_id, "dir" => $signalelement->dir,
			"infra_adresse" => $signalelement->infra_adresse,
			"type" => $signalelement->infra_type,
			"infra_zusatzwert" => $signalelement->infra_zusatzwert);
	}

	unset($DB);

	return $cacheSignaldaten;
}

// Ermittele die IDs der direkten Nachbarn eines Infrastrukturelementes
function getInfraNachbarn ($id, $id_typ = "infra_id") {

	$DB = new DB_MySQL();
	$infraliste = $DB->select("SELECT `id`, `weiche_id`, `nachbar0_0`,`nachbar0_1`,`nachbar1_0`,`nachbar1_1`
FROM `".DB_TABLE_INFRA_NACHBARN."`
WHERE `".$id_typ."` = '".$id."'
");
	unset($DB);

	$nachbarn = array();

	if ($infraliste && count($infraliste) > 0) {
		foreach ($infraliste as $nachbar) {
			$nachbarn[] = array("id" => $nachbar->id,
				"weiche_id" => $nachbar->weiche_id,
				"nachbar0_0" => $nachbar->nachbar0_0,
				"nachbar0_1" => $nachbar->nachbar0_1,
				"nachbar1_0" => $nachbar->nachbar1_0,
				"nachbar1_1" => $nachbar->nachbar1_1);
		}

		return $nachbarn;
	} else {
		return false;
	}
}

// ------------------------------------------------------------------ //
// Eigene Funktionen
// ------------------------------------------------------------------ //

// Reads in the length for all infrastructure sections for which a length is stored.
function createcacheInfraLaenge() {
	$DB = new DB_MySQL();
	$returnArray = array();

	$infralaenge = $DB->select("SELECT `".DB_TABLE_INFRAZUSTAND."`.`id`,
                                `".DB_TABLE_INFRAZUSTAND."`.`laenge`
                                FROM `".DB_TABLE_INFRAZUSTAND."`
                                WHERE `".DB_TABLE_INFRAZUSTAND."`.`type` = '"."gleis"."'
                                ");
	unset($DB);

	foreach ($infralaenge as $data) {
		if ($data->laenge != null) {
			$returnArray[$data->id] = intval($data->laenge);
		}
	}
	return $returnArray;
}

function createCacheHaltepunkte() : array{

	$DB = new DB_MySQL();
	$returnArray = array();

	$betriebsstellen = $DB->select("SELECT `".DB_TABLE_BETRIEBSSTELLEN_DATEN."`.`parent_kuerzel`
                                FROM `".DB_TABLE_BETRIEBSSTELLEN_DATEN."`
                                WHERE `".DB_TABLE_BETRIEBSSTELLEN_DATEN."`.`parent_kuerzel` IS NOT NULL
                                ");
	unset($DB);

	foreach ($betriebsstellen as $betriebsstelle) {
		$returnArray[$betriebsstelle->parent_kuerzel][0] = array();
		$returnArray[$betriebsstelle->parent_kuerzel][1] = array();
	}

	foreach ($returnArray as $betriebsstelleKey => $betriebsstelleValue) {
		$DB = new DB_MySQL();
		$name = $betriebsstelleKey;
		$name .= "%";
		$asig = "ASig";
		$bksig = "BkSig";
		$vsig = "VSig";
		$ja = "ja";

		if ($betriebsstelleKey == 'XAB' || $betriebsstelleKey == "XBL") {

			$haltepunkte = $DB->select("SELECT `".DB_TABLE_SIGNALE_STANDORTE."`.`freimelde_id`, 
                                `".DB_TABLE_SIGNALE_STANDORTE."`.`wirkrichtung`
                                FROM `".DB_TABLE_SIGNALE_STANDORTE."`
                                WHERE `".DB_TABLE_SIGNALE_STANDORTE."`.`betriebsstelle` LIKE '$name'
                                AND `".DB_TABLE_SIGNALE_STANDORTE."`.`freimelde_id` IS NOT NULL
                                AND `".DB_TABLE_SIGNALE_STANDORTE."`.`fahrplanhalt` = '$ja'
                                ");
			unset($DB);

		} else if ($betriebsstelleKey == 'XTS') {

			$haltepunkte = $DB->select("SELECT `".DB_TABLE_SIGNALE_STANDORTE."`.`freimelde_id`, 
                                `".DB_TABLE_SIGNALE_STANDORTE."`.`wirkrichtung`
                                FROM `".DB_TABLE_SIGNALE_STANDORTE."`
                                WHERE `".DB_TABLE_SIGNALE_STANDORTE."`.`betriebsstelle` LIKE '$name'
                                AND `".DB_TABLE_SIGNALE_STANDORTE."`.`freimelde_id` IS NOT NULL
                                AND `" . DB_TABLE_SIGNALE_STANDORTE . "`.`signaltyp` = '$bksig'
                                ");
			unset($DB);

		} else if ($betriebsstelleKey == 'XLG') {

			$haltepunkte = $DB->select("SELECT `".DB_TABLE_SIGNALE_STANDORTE."`.`freimelde_id`, 
                                `".DB_TABLE_SIGNALE_STANDORTE."`.`wirkrichtung`
                                FROM `".DB_TABLE_SIGNALE_STANDORTE."`
                                WHERE `".DB_TABLE_SIGNALE_STANDORTE."`.`betriebsstelle` LIKE '$name'
                                AND `".DB_TABLE_SIGNALE_STANDORTE."`.`freimelde_id` IS NOT NULL
                                AND `" . DB_TABLE_SIGNALE_STANDORTE . "`.`signaltyp` != '$vsig'
                                ");
			unset($DB);

		} else {

			$haltepunkte = $DB->select("SELECT `" . DB_TABLE_SIGNALE_STANDORTE . "`.`freimelde_id`, 
                                `" . DB_TABLE_SIGNALE_STANDORTE . "`.`wirkrichtung`
                                FROM `" . DB_TABLE_SIGNALE_STANDORTE . "`
                                WHERE `" . DB_TABLE_SIGNALE_STANDORTE . "`.`betriebsstelle` LIKE '$name'
                                AND `" . DB_TABLE_SIGNALE_STANDORTE . "`.`freimelde_id` IS NOT NULL
                                AND `" . DB_TABLE_SIGNALE_STANDORTE . "`.`signaltyp` = '$asig'
                                ");
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
	$returnArray["XSC"][1] = array(734, 732, 735, 733, 692); // In der Datenbank ist für Richtung 1 für diese Abschnitte fahrplanhalt auf nein eingestellt
	//var_dump($returnArray);
	//sleep(10);
	return $returnArray;
}

function createChacheZwischenhaltepunkte() {

	$DB = new DB_MySQL();
	$allZwischenhalte = array();
	$returnArray = array();

	$zwischenhalte = $DB->select("SELECT DISTINCT `".DB_TABLE_SIGNALE_STANDORTE."`.`betriebsstelle`
                                FROM `".DB_TABLE_SIGNALE_STANDORTE."`
                                WHERE `".DB_TABLE_SIGNALE_STANDORTE."`.`betriebsstelle` IS NOT NULL
                                ");
	unset($DB);



	foreach ($zwischenhalte as $halt) {
		array_push($allZwischenhalte, $halt->betriebsstelle);
	}




	foreach ($allZwischenhalte as $halt) {

		$DB = new DB_MySQL();

		$zwischenhalte = $DB->select("SELECT `".DB_TABLE_SIGNALE_STANDORTE."`.`freimelde_id`
                                FROM `".DB_TABLE_SIGNALE_STANDORTE."`
                                WHERE `".DB_TABLE_SIGNALE_STANDORTE."`.`betriebsstelle` = '$halt'
                                AND `".DB_TABLE_SIGNALE_STANDORTE."`.`freimelde_id` IS NOT NULL
                                ");
		unset($DB);



		if (sizeof($zwischenhalte) == 1) {
			$returnArray[$halt] = intval($zwischenhalte[0]->freimelde_id);
		}

	}

	return $returnArray;





}

function createCacheInfraToGbt () {

	$DB = new DB_MySQL();

	$infraArray = array();
	$returnArray = array();

	$allInfra = $DB->select("SELECT  `".DB_TABLE_FMA_GBT."`.`infra_id`
                                FROM `".DB_TABLE_FMA_GBT."`
                                WHERE `".DB_TABLE_FMA_GBT."`.`infra_id` IS NOT NULL
                                ");
	unset($DB);



	foreach ($allInfra as $infra) {
		array_push($infraArray, intval($infra->infra_id));
	}



	foreach ($infraArray as  $infra) {
		$DB = new DB_MySQL();

		$gbt = $DB->select("SELECT  `".DB_TABLE_FMA_GBT."`.`gbt_id`
                                FROM `".DB_TABLE_FMA_GBT."`
                                WHERE `".DB_TABLE_FMA_GBT."`.`infra_id` = '$infra'
                                ")[0]->gbt_id;
		unset($DB);

		$returnArray[$infra] = intval($gbt);



	}


	return $returnArray;

}

function createCacheGbtToInfra () {

	$DB = new DB_MySQL();

	$returnArray = array();

	$allGbt = $DB->select("SELECT DISTINCT `".DB_TABLE_FMA_GBT."`.`gbt_id`
                                FROM `".DB_TABLE_FMA_GBT."`
                                WHERE `".DB_TABLE_FMA_GBT."`.`gbt_id` IS NOT NULL
                                ");
	unset($DB);



	foreach ($allGbt as  $gbt) {
		$DB = new DB_MySQL();

		$gbt = $gbt->gbt_id;

		$infras = $DB->select("SELECT  `".DB_TABLE_FMA_GBT."`.`infra_id`
                                FROM `".DB_TABLE_FMA_GBT."`
                                WHERE `".DB_TABLE_FMA_GBT."`.`gbt_id` = '$gbt'
                                ");
		unset($DB);

		$returnArray[$gbt] = array();

		foreach ($infras as $infra) {
			array_push($returnArray[$gbt], intval($infra->infra_id));
		}





	}

	return $returnArray;
}

function createCacheFmaToInfra() {

	$DB = new DB_MySQL();
	$returnArray = array();

	$fmaToInfra = $DB->select("SELECT `".DB_TABLE_FMA_GBT."`.`infra_id`,
                                `".DB_TABLE_FMA_GBT."`.`fma_id`
                                FROM `".DB_TABLE_FMA_GBT."`
                                WHERE `".DB_TABLE_FMA_GBT."`.`fma_id` IS NOT NULL
                                ");
	unset($DB);

	foreach ($fmaToInfra as $value) {
		$returnArray[intval($value->fma_id)] = intval($value->infra_id);
	}

	return $returnArray;
}

function createCacheToBetriebsstelle() {

	$DB = new DB_MySQL();
	$returnArray = array();

	$fmaToInfra = $DB->select("SELECT `".DB_TABLE_SIGNALE_STANDORTE."`.`id`,
                                `".DB_TABLE_SIGNALE_STANDORTE."`.`betriebsstelle`
                                FROM `".DB_TABLE_SIGNALE_STANDORTE."`
                                ");
	unset($DB);

	foreach ($fmaToInfra as $value) {
		$returnArray[intval($value->id)] = $value->betriebsstelle;
	}

	return $returnArray;
}

function createCacheFahrplanSession() {
	$DB = new DB_MySQL();

	$fahrplanData = $DB->select("SELECT *
                            FROM `".DB_TABLE_FAHRPLAN_SESSION."`
                            WHERE `".DB_TABLE_FAHRPLAN_SESSION."`.`status` = '"."1"."'
                           ");
	unset($DB);

	return $fahrplanData[0];
}