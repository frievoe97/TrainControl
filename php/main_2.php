<?php
// TODO:
// - Rename TimeDifference ($newTimeDifference)
// - Train Errors hinzufügen

// Load all required external files
require 'vorbelegung.php';
require 'functions/sort_functions.php';
require 'functions/cache_functions.php';
require 'functions/ebuef.php';
require 'globalVariables.php';

// Set timezone
date_default_timezone_set("Europe/Berlin");

// Reports only errors
error_reporting(1);

// Define own train errors
$trainErrors = array();
$trainErrors[0] = "Zug stand falsch herum und war zu lang um die Richtung zu ändern.";
$trainErrors[1] = "In der Datenbank ist für den Zug keine Zuglänge angegeben.";
$trainErrors[2] = "In der Datenbank ist für den Zug keine v_max angegeben.";
$trainErrors[3] = "Zug musste eine Notbremsung durchführen.";

// Load static data from the databse into the cache
$cacheInfranachbarn = createCacheInfranachbarn();
$cacheInfradaten = createCacheInfradaten();
$cacheSignaldaten = createCacheSignaldaten();
$cacheInfraLaenge = createcacheInfraLaenge();
$cacheHaltepunkte = createCacheHaltepunkte();
$cacheZwischenhaltepunkte = createChacheZwischenhaltepunkte();
$cacheInfraToGbt = createCacheInfraToGbt();
$cacheGbtToInfra = createCacheGbtToInfra();
$cacheFmaToInfra = createCacheFmaToInfra();
$cacheInfraToFma = array_flip($cacheFmaToInfra);
$cacheFahrplanSession = createCacheFahrplanSession();
$cacheSignalIDToBetriebsstelle = createCacheToBetriebsstelle();
$cacheAdresseToID = array();		// Filled with data in getAllTrains()
$cacheIDToAdresse = array();		// Filled with data in getAllTrains()

// Global variables
$allTrainsOnTheTrack = array();		// All adresses found on the tracks
$allTrains = array();				// All trains with the status 1 or 2
$allUsedTrains = array();			// All trains with the status 1 or 2 that are standing on the tracks
$allTimes = array();

// Get simulation and real time
$simulationStartTimeToday = getUhrzeit(getUhrzeit($cacheFahrplanSession->sim_startzeit, "simulationszeit", null, array("outputtyp"=>"h:i:s")), "simulationszeit", null, array("inputtyp"=>"h:i:s"));
$simulationEndTimeToday = getUhrzeit(getUhrzeit($cacheFahrplanSession->sim_endzeit, "simulationszeit", null, array("outputtyp"=>"h:i:s")), "simulationszeit", null, array("inputtyp"=>"h:i:s"));
$simulationDuration = $cacheFahrplanSession->sim_endzeit - $cacheFahrplanSession->sim_startzeit;
$realStartTime = time();
$realEndTime = $realStartTime + $simulationDuration;
$newTimeDifference = $simulationStartTimeToday - $realStartTime;

// Start Message
startMessage();

// Load all trains
// TODO: Funktion benötigt, die die Daten updatet...
$allTrains = getAllTrains();

// Loads all trains that are in the rail network and prepares everything for the start
findTrainsOnTheTracks();

// Checks if the trains are in the right direction and turns them if it is necessary and possible.
consoleCheckIfStartDirectionIsCorrect();
consoleAllTrainsPositionAndFahrplan();
showErrors();

// Adds all the stops of the trains.
addStopsectionsForTimetable();

// Adds an index (address) to the $allTimes array for each train.
// TODO: Ist diese Funktion notwendig?
initalFirstLiveData();

// Determination of the current routes of all trains.
calculateNextSections();

// TODO: Wird die Funktion benötigt?
//addNextStopForAllTrains();

// Checks whether the trains are already at the first scheduled stop or not.
checkIfTrainReachedHaltepunkt();

// Checks whether the routes are set correctly.
checkIfFahrstrasseIsCorrrect();

calculateFahrverlauf();

//var_dump($allTimes);









