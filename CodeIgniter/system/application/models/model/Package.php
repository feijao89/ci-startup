<?php 
 
class Package {
	public $id;
	public $name;
	public $description;
	public $beans;

	public function __construct() {
		$this->id = 0;
		$this->beans = array();
	}
	public function setId($value) { $this->id = $value ; } 
	public function getId() { return $this->id ; }

	public function setName($value) { $this->name = $value ; } 
	public function getName() { return $this->name ; }

	public function setDescription($value) { $this->description = $value ; } 
	public function getDescription() { return $this->description ; }

	public function setBeans($value) { $this->beans = $value ; } 
	public function getBeans() { return $this->beans ; }

} 
 
 ?>