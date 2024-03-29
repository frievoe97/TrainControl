<?php

// Klasse nach http://www.it-academy.cc/article/1466/PHP:+eine+einfache+mySQL+Klasse.html

class DB_MySQL
{

	public $connected = FALSE; // besteht eine Verbindung?
	public $host;              // unser mySQL Server
	public $benutzer;          // der mySQL-Benutzer
	public $passwort;          // das zugehörige Passwort
	public $dbname;            // der Name der Datenbank
	public $verbindung;        // die Kennung der Verbindung
	public $sql_query;         // die zuletzt gestellte Anfrage
	public $query_counter;     // wie viele Anfragen wurden schon gemacht?

	// Konstruktor, erstellt die Verbindung
	public function __construct ($ID = DB_STANDARD)
	{
		global $MySQL_config;

		$this->host = $MySQL_config[$ID]['host'];
		$this->benutzer = $MySQL_config[$ID]['benutzer'];
		$this->passwort = $MySQL_config[$ID]['passwort'];
		$this->dbname = $MySQL_config[$ID]['dbname'];

		$this->verbindung = new mysqli('p:'.$this->host, $this->benutzer, $this->passwort, $this->dbname);

		if ($this->verbindung->connect_error) {
			$this->error('CONN.OPEN');
			$this->error('DB.SELECT');
			die('Connect Error ('.$this->verbindung->connect_errno.') '.$this->verbindung->connect_error);
		} else {
			$this->connected = TRUE;
			//echo 'Success... ' . mysqli_get_host_info($this->verbindung) . "\n";

			/* change character set to utf8 */
			if (!$this->verbindung->set_charset('utf8')) {
				$this->error('Zeichensatz: '.$mysqli->error);
			}
		}
	}

	// sendet einen Befehl an die Datenbank
	public function query ($sql)
	{
		if (!$this->connected) {
			$this->error('NO CONN');
		} else {
			$this->sql_query = $sql;
			$result = $this->verbindung->query($this->sql_query);
			if ($result === FALSE) {
				$this->error('QUERY FAILED');
			} else {
				return $result;
			}
		}
	}

	// holt Datensätze aus der Datenbank
	public function select ($sql, &$anzahl = NULL)
	{
		$array = array();

		if (!$this->connected) {
			$this->error('NO CONN');
		} else {
			$result = $this->query($sql);
			if ($result !== FALSE) {
				$anzahl1 = @mysqli_num_rows($result);
				$anzahl = $anzahl1;
				for ($i = 0; $i < $anzahl; $i++) {
					$array[$i] = @mysqli_fetch_object($result);
				}
			}
		}
		return $array;
	}

	// gibt die Fehlermeldungen aus
	public function error ($error_type)
	{
		switch ($error_type) {
			case 'CONN.OPEN':
				{
					$text = 'Beim Öffnen der Verbindung ist ein Fehler aufgetreten.';
				}
				break;

			case 'CONN.CLOSE':
				{
					$text = 'Beim Schließen der Verbindung ist ein Fehler aufgetreten.';
				}
				break;

			case 'NO CONN':
				{
					$text = 'Die angeforderte Aktion konnte nicht durchgeführt werden, da keine Verbindung zur Datenbank besteht!';
				}
				break;

			case 'DB.SELECT':
				{
					$text = 'Beim Auswählen der Datenbank ist ein Fehler aufgetreten. Eventuell ist die Datenbank nicht vorhanden.';
				}
				break;

			case 'QUERY FAILED':
				{
					$text = "Während folgender Abfrage ist ein Fehler aufgetreten:\n\n".$this->sql_query;
				}
				break;

			default:
				{
					$text = 'Es ist folgender, unbekannter Fehler aufgetreten: '.$error_type;
				}
				break;
		}

		if ($this->connected) {
			$text .= "\n\nMySQL meldet:\nFehler-Nummer: ".@mysqli_errno($this->verbindung)."\nFehler-Beschreibung: ".@mysqli_error($this->verbindung);
		}
		debug_print_backtrace();
		echo $text."\n";
	}

	// beendet die Verbindung
	public function __destruct ()
	{
		if (!$this->connected) {
			$this->error('NO CONN');
		} else {
			if (@mysqli_close($this->verbindung) === FALSE) {
				$this->error('CONN.CLOSE');
			}
		}
	}
}
// -----------------------------------------------------------------------------------------------------------------------

?>
