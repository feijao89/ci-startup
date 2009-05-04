<?php 
 
class Test1 {
	public $id;
	public $a;
	public $b;

	public function __construct() {
		$this->id = 0;
	}
	public function setId($value) { $this->id = $value ; } 
	public function getId() { return $this->id ; }

	public function setA($value) { $this->a = $value ; } 
	public function getA() { return $this->a ; }

	public function setB($value) { $this->b = $value ; } 
	public function getB() { return $this->b ; }

} 
 
 ?>