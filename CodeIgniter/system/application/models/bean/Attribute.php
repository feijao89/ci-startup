<?php



class Attribute
{
    



	public $id;
	public $name;
	public $type;
	public $value;
	
	public $bean;
	public $bean_id;
	
	
	public function getBean()
    {
        return $this->bean;
    }

    public function setBean($bean)
    {
        $this->bean = $bean;
		$this->bean_id = $bean->getId();
    }
	
    public function getValue()
    {
        return $this->value;
    }
    

    public function setValue($value)
    {
        $this->value = $value;
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
    public function getType()
    {
        return $this->type;
    }
    public function setType($type)
    {
        $this->type = $type;
    }

}

?>