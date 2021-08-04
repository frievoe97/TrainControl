<?php
	// Debug-Modus aktivieren (= true)
	$debug = false;

	// Wartezeit für HHVM/PHP-Skripte bis zum nächsten Versuch, eine Fahrplansession zu ermitteln
	define ('FAHRPLANSESSION_WARTEZEIT',30);

	// Status der Fahrplan-Session
	define ('FAHRPLANSESSION_STATUS_ANGELEGT', 0);
	define ('FAHRPLANSESSION_STATUS_VORBEREITUNG', 1);
	define ('FAHRPLANSESSION_STATUS_AKTIV', 2);
	define ('FAHRPLANSESSION_STATUS_ANGEHALTEN', 3);
	define ('FAHRPLANSESSION_STATUS_BEENDET', 4);
	define ('FAHRPLANSESSION_STATUS_PAUSE', 5);

	// Fahrzeugsteuerung
	define ('FZS_REAKTIONSZEIT_Tf', 2);
	define ('FZS_BREMSPUFFERWEG',15);
	define ('FZS_INTERVALL_BREMSEN',2);
	define ('FZS_FAHRZEUGE_WAGENDECODER',99);
	define ('FZS_WARTEZEIT_FREIFAHRT',2);
	define ('FZS_WARTEZEIT_WENDEN',30);
	define ('FZS_MINDESTHALTEZEIT_P',60);
	define ('FZS_MINDESTHALTEZEIT_G',180);

	define ('FZS_V_SKALIERUNG',2.0);              // Faktor zwischen Geschwindigkeit und Fahrstufe => Fahrstufe * Faktor = Geschwindigkeit [km/h]
	define ('FZS_DAUER_WENDEFAHRT_XWS',7);        // Zeit in Sekunden zur Wendefahrt in XWS
	define ('FZS_ABSCHNITT_WENDEFAHRT_XWS',152);  // Abschnitt in XWS, der für eine Wendefahrt belegt sein muss
	define ('FZS_RICHTUNG_WENDEFAHRT_XWS',1);     // Richtung, in die das Fahrzeug fahren muss für eine Wendefahrt in XWS

	define ('FZS_VERZOEGERUNG_PZ',0.8);
	define ('FZS_VERZOEGERUNG_GZ',0.4);
	define ('FZS_VERZOEGERUNG_RA',1.0);
	define ('FZS_VERZOEGERUNG_TZ',2.0);

	// Zustände von Fahrzeugen
	define ('FZS_FZGZUSTAND', serialize(array(0 => "im Einsatz", 1 => "aufgerüstet", 2 => "einsatzbereit", 3 => "in der Werkstatt", 4 => "defekt", 5 => "ausgemustert")));
	define ('FZS_FZGZUSTAND_NUTZBAR', 2); // höchster Wert, bis zu dem ein Fahrzeug auf der Anlage steht
	define ('FZS_FZGZUSTAND_AUFGERUESTET', 1); // höchster Wert, bis zu dem ein Fahrzeug in der laufenden Session aktiviert ist
	define ('FZS_FZGZUSTAND_AKTIV', 0); // aktiv
?>