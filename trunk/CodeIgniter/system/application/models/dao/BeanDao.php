<?php

/*
 * Classe che restituisce Bean(s)
 */

class BeanDao
{
	public $query;
	public $relations;
	public $name;
	public $isMain;
	
	public $query;
	
	public function BeanDao(QueryDao $query, GenericDao $dao) {
		$this->query = $query;
		
		foreach ($dao->hasOne as $relation => $config) {
			
			if (array_key_exists(($relation)))
			$this->relations[$relation] = new BeanRelation($query,$relation,$config);
		}
		foreach ($dao->hasMany as $relation => $config) {
			
			$this->relations[$relation] = new BeanRelation($query,$relation,$config);
		}
	}
	
}

?>