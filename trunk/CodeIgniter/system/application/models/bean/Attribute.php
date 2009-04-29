<?php 
 
class Attribute {
	public $id;
	public $name;
	public $type;
	public $value;
	public $bean_id;
	public $external_id;

	public function __construct() {
		$this->id = 0;
		$this->bean_id = 0;
		$this->external_id = 0;
		$this->external = NULL;
	}
	public function setId($value) { $this->id = $value ; } 
	public function getId() { return $this->id ; }

	public function setName($value) { $this->name = $value ; } 
	public function getName() { return $this->name ; }

	public function setType($value) { $this->type = $value ; } 
	public function getType() { return $this->type ; }

	public function setValue($value) { $this->value = $value ; } 
	public function getValue() { return $this->value ; }

	public function setBean_id($value) { $this->bean_id = $value ; } 
	public function getBean_id() { return $this->bean_id ; }

	public function setExternal($value) { $this->external = $value ; } 
	public function getExternal() { return $this->external ; }

} 
 
 ?>