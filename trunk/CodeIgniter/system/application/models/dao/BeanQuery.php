<?php

class BeanQuery
{
	public $db;
	public $dao;
	
	public $rowObservers;
	
	public function BeanQuery(GenericDao $dao) {
		$this->dao = $dao;
		$this->db = $dao->db;
		$this->rowObservers = array();
		/*
		$this->rowObservers = array(
			$dao->beanName => $dao
		);
		*/

		foreach ( $dao->allNotLazyRelations as $relation => $config ) {
			$this->rowObservers[$relation] = $dao->factory->getDao($config['bean']);
			//echo '<br>Aggiungo Observer : '. $relation .' => '. $this->rowObservers[$relation]->beanName .'Dao';
		}
		if ($dao->factory->_query_count >= 10) {
			throw new PersistenceException('Excute too query (10) (last : '. $this->db->last_query());
		}
		
	}	
	
	public function select() {
		//echo '<br>Seleziono : '. $this->dao->beanName ;
		
		foreach($this->dao->getFields() as $field => $type) {
			$this->db->select($this->dao->table . '.' . $field);
		}
		
		$this->db->from($this->dao->table);
		$this->db->order_by($this->dao->table . '.id');
		foreach ( $this->rowObservers as $relation => $dao) {
			//echo '<br>Seleziono Relazione : '. $relation .' con '. $dao->beanName .'Dao' ;
			//print_r(array_keys($this->dao->allRelations));
			$config = $this->dao->allRelations[$relation];
			//print_r ($config);
			if ( $config['type'] == 'has_one') {
				$relation_id = $relation .'_id';
				//$this->db->select($this->dao->table . '.' . $relation_id);
				$this->db->join($dao->table .' AS '.$relation,$this->dao->table.'.'.$relation_id .' = '.$relation.'.id','left');
				
			}
			else {
				$this->db->order_by($relation . '.id');
				$this->db->join($dao->table .' AS '.$relation,$this->dao->table.'.id = '.$relation.'.'.$config['fkey'],'left');
			}
			foreach( $dao->getFields() as $field => $type  ) {
				$this->db->select($relation.'.'.$field .' AS '. $relation. $field, false);
			}
		}
		
		
		return $this;
	}
	
	public function results($tmp =false) {
		$list = array();
		
		$query = $this->db->get();
		$this->dao->factory->_query_count++;
		// bean build
		foreach($query->result_array() as $row) {
			$bean = $this->dao->makeBean($row);
			foreach ($this->rowObservers as $relation => $dao) {
				$config = $this->dao->allRelations[$relation];
				$joinedBean = $dao->makeBean($row,$relation);
				
				if ( $config['type'] == 'has_many' && $joinedBean) {
					$cache_key = $this->dao->beanName . '_' . $config['name'] . '_' . $bean->getId();
					if (!array_key_exists($cache_key,$this->dao->cache_list)) {
						$this->dao->cache_list[$cache_key] = array();
					}
					$this->dao->cache_list[$cache_key][$joinedBean->getId()] = $joinedBean; 
				}
				
			}
			$list[$bean->getId()] = $bean;
		}
		
		$results = array();
		
		// bean tree build
		foreach ( $list as $id => $bean) {
			$results[] = $this->dao->injectRelations($bean);	
			$this->dao->completedBeans[$id] = $bean;
			foreach ($this->rowObservers as $relation => $dao) {
				//echo '<br>-Nel '.$dao->beanName.'Dao ci sono '. count($dao->allBeans). ' beans di cui '. count ($dao->completedBeans) .' completi' ;
				
				foreach ( $dao->allBeans as $b ) {
					if ( array_key_exists($b->getId() ,$dao->completedBeans)) {
						continue;
					}
					
					$dao->completedBeans[$b->getId()] = $b;
					$dao->injectRelations($b,true);
					
					//echo '<br>--completato '. $dao->beanName .' '. $b->getId();
				}
				
			}		
		}
		
		return $results;		
	}
	
	
	
	
	

	
}

?>