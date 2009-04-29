<?php 
 
class Knight {
	public $id;
	public $name;
	public $username;
	public $password;
	public $email;

	public function __construct() {
		$this->id = 0;
	}
	public function setId($value) { $this->id = $value ; } 
	public function getId() { return $this->id ; }

	public function setName($value) { $this->name = $value ; } 
	public function getName() { return $this->name ; }

	public function setUsername($value) { $this->username = $value ; } 
	public function getUsername() { return $this->username ; }

	public function setPassword($value) { $this->password = $value ; } 
	public function getPassword() { return $this->password ; }

	public function setEmail($value) { $this->email = $value ; } 
	public function getEmail() { return $this->email ; }

} 
 
 ?>