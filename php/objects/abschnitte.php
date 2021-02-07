<?php
class Abschnitte {

	public $id;
	public $length;
	public $v_max;

	// Methods
	function set_name($name) {
		$this->name = $name;
	}
	function get_name() {
		return $this->name;
	}
}
?>