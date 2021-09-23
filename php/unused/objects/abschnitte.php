<?php

class Abschnitte
{

	public $id;
	public $length;
	public $v_max;

	// Methods
	public function set_name ($name)
	{
		$this->name = $name;
	}

	public function get_name ()
	{
		return $this->name;
	}
}
?>