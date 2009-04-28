<?php

require_once('Attribute.php');

class Bean
{
	public $id;
	public $name;
	public $description;
	
	public $package_id;
	public $package;
	public $attributes;
	
	public $extend;
	public $extend_id;
	
	public function Bean() {
		$this->id = 0;
		$this->package_id = 0;
		$this->attributes = new ArrayObject();//array();
	}	
	
	public function makeAttribute($name, $type, $value = "") {
		$a = new Attribute();
		$a->setName($name);
		$a->setType($type);
		$a->setValue($value);
		$a->setBean($this);
		return $a;
	}
	
	public function addAttribute(Attribute $attribute) {
		//$this->attributes[$attribute->getId()] = $attribute;
		$this->attributes[] = $attribute;
	}
	
	public function removeAttribute(Attribute $attribute) {
		$aKey = -1;
		foreach ( $this->getAttributes() as $key => $att) {
			if ($att->getId() == $attribute->getId()) {
				$aKey = $key;
			}
		}
		
		if ($aKey == -1) {
			throw new ModelException('Attribute \''.$attribute->getName().'\' not found in this model : '.$this->getName() );
		}
		
		$o = $this->attributes[$aKey];
		$this->offsetUnset($aKey);
		return $o;		
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
    public function getName()
    {
        return $this->name;
    }
    public function setName($name)
    {
        $this->name = $name;
    }
    public function getPackage()
    {
        return $this->package;
    }
    public function setPackage(Package $package)
    {
        $this->package = $package;
		$this->package_id = $package->getId();
    }

    public function getAttributes()
    {
        return $this->attributes;
    }
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

    public function getExtend()
    {
        return $this->extend;
    }
    public function setExtend(Bean $extend)
    {
        $this->extend = $extend;
		$this->extend_id = $extend->getId();
    }

}

?>