## AllTrains (Alle Züge)

| name        | content     | 
| -------------: |:-------------|
| id      | ID |
| adresse      | Adresse |
| timestamp      | ~~Zeitstempel~~ |
| speed      | Geschwindigkeit |
| dir      | Zugrichtung |
| zugtyp      | Zugtyp |
| zuglaenge      | Zuglänge |
| prev_speed      | vorherige Geschwindigkeit |
| fzs      | Anzahl der Fahrzeuge |
| verzoegerung      | Bremsverzögerung |
| notverzoegerung      |  Bremsverzögerung im Falle einer Notbremsung |
| zusatnd      | Zustand |
| bezeichnung      | Bezeichnung |
| vmax      | maximal zulässige Geschwindigkeit |
| next_betriebsstellen      | Nächste Betriebsstellen |
| next_sections      | Nächste Abschnitte |
| next_lenghts      | Längen der nächsten Abschnitte |
| next_v_max      | Zulässige Höchstgeschwindigkeit auf den nächsten Abschnitten |
| section      | Aktueller Abschnitt |
| position      | Aktuelle Position (absolut im Abschnitt) |
| next_timetable_change_speed      | Zielgewschwindigkeit |
| next_timetable_change_section      | Zielabschnitt |
| next_timetable_change_position      | Zielposition im Zielabschnitt |
| next_timetable_change_time      | Ankunftszeit |
| position_change      | Positionen der nächsten Geschwindigkeitsänderung |
| speed_change      | Geschwindigkeiten der nächsten Geschwindigkeitsänderung |
| time_change      | Zeiten der nächsten Geschwindigkeitsänderung |
| next_betriebsstelle_soll      | Nächste Soll-Betriebsstelle |
| next_betriebsstelle_ist      | Nächste Ist-Betriebsstelle |
| prev_betriebsstelle      | vorherige Betriebsstelle |
| current_delay      | Aktuelle Verspätung (neg. Zahlenwerte &rarr; zu früh) |
| operates_on_timetable      | Fährt aktuell nach Fahrplan (1 &rarr; ja, 0 &rarr; nein) |
| operates_in_singletrack_network | Befindet sich im eingleisigen Netz (1 &rarr; ja, 0 &rarr; nein) |
| faehrt_nach_fahrplan | Fährt der Zug aktuell nach Fahrplan (1 &rarr; ja, 0 &rarr; nein) |
| error | Anzeige der Fehler (0 &rarr; Zug stand falsch herum und war zu lang um die Richtung zu ändern) |
| can_drive | Kann der Zug fahren (true &rarr; ja, false &rarr; nein) |
| next_stop | Daten für den nächsten Haltepunkt |
| fahrstrasse_is_correct | Fahrstraße ist so eingestellt, dass der nächste Haltepunkt erreicht werden kann (1 &rarr; ja, 0 &rarr; nein)|




## KeyPoints

| name        | content     | 
| -------------: |:-------------|
| speed_0      | Startgeschwindigkeit |
| speed_1      | Zielgeschwindigkeit |
| position_0      | Startposition |
| position_1      | Zielposition |
| time_0      | Startzeit |
| time_1      | Zielzeit |
| max_speed      | maximale Geschwindigkeit |


