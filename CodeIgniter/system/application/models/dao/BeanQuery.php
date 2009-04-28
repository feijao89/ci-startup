<?php


class BeanQuery
{
	public $db;
	public $dao;
	public function BeanQuery(GenericDao $dao) {
		$this->dao = $dao;
		$this->db = $dao->db;
		foreach ($this->dao->hasOne as $relation => $config) {
			
			$this->relations[$relation] = $config;
		}
	}
	
	public $relations;
	public function select() {
		
	}
	
}

?>