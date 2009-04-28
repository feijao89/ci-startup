<?php

require_once('Bean.php');

class Package
{

   	public $id;
	public $name;
	public $description;
	
	public $beans;
	
	public function Package() {
		$this->beans = new ArrayObject();//array();
	}
	
	public function makeBean($name, $description) {
		$bean = new Bean();
		$bean->setName($name);
		$bean->setDescription($description);
		$bean->setPackage($this);
		return $bean;
	}
	
	public function addBean(Bean $bean) {
		$this->beans[$bean->getId()] = $bean;
	}
	
	public function removeBean(Bean $bean) {
		if (!array_key_exists($bean->getId(),$this->models)) {
			throw new ModelException('Bean not found in this package : '.$this->getName() );
		}
		
		$p = $this->beans[$bean->getId()];
		
		unset($this->beans[$bean->getId()]);
		
		return $p;		
	}
	
	
    public function getDescription()
    {
        return $this->description;
    }
    public function setDescription($description)
    {
        $this->description = $description;
    }
    public function getId()
    {
        return $this->id;
    }
    public function setId($id)
    {
        $this->id = $id;
    }
    public function getBeans()
    {
        return $this->beans;
    }
    public function setBeans($beans)
    {
        $this->beans = $beans;
    }
    public function getName()
    {
        return $this->name;
    }
    public function setName($name)
    {
        $this->name = $name;
    }

}

?>