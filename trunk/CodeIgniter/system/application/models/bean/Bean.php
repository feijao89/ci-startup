<?php 
 
class Bean {
	public $id;
	public $name;
	public $description;
	public $package_id;
	public $extend_id;
	public $attributes;

	public function __construct() {
		$this->id = 0;
		$this->package_id = 0;
		$this->extend_id = 0;
		$this->package = NULL;
		$this->extend = NULL;
		$this->attributes = array();
	}
	public function setId($value) { $this->id = $value ; } 
	public function getId() { return $this->id ; }

	public function setName($value) { $this->name = $value ; } 
	public function getName() { return $this->name ; }

	public function setDescription($value) { $this->description = $value ; } 
	public function getDescription() { return $this->description ; }

	public function setPackage($value) { $this->package = $value ; } 
	public function getPackage() { return $this->package ; }

	public function setExtend($value) { $this->extend = $value ; } 
	public function getExtend() { return $this->extend ; }

	public function setAttributes($value) { $this->attributes = $value ; } 
	public function getAttributes() { return $this->attributes ; }

} 
 
 ?>