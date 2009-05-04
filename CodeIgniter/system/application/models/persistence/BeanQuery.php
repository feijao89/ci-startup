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

		foreach ( $dao->allNotLazyRelations as $relation => $config ) {
			$this->rowObservers[$relation] = $dao->factory->getDao($config['bean']);
			//echo '<br>Aggiungo Observer : '. $relation .' => '. $this->rowObservers[$relation]->beanName .'Dao';
		}
		if ($dao->factory->_query_count >= config_item('max_query')) {
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
		//$this->dao->orderBy[$this->dao->table . '.id'] = 'ASC';
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
				//$this->dao->orderBy[$relation . '.id'] = 'ASC';
				$this->db->join($dao->table .' AS '.$relation,$this->dao->table.'.id = '.$relation.'.'.$config['fkey'],'left');
			}
			foreach( $dao->getFields() as $field => $type  ) {
				$this->db->select($relation.'.'.$field .' AS '. $relation. $field, false);
			}
		}
		
		
		return $this;
	}
	
	public function results() {
		$list = array();
		/*foreach ($this->dao->orderBy as $field => $direction) {
			$this->db->order_by($field, $direction);
		}*/
		$query = $this->db->get();
		$this->dao->factory->_query_count++;
		// bean build
		//echo '<br><b>'. $this->db->last_query() .'</b>';
		foreach($query->result_array() as $row) {
			
			$bean = $this->dao->makeBean($row);
			
			foreach ($this->rowObservers as $relation => $dao) {
				$config = $this->dao->allRelations[$relation];
				$joinedBean = $dao->makeBean($row,$relation);
				
				if ( $config['type'] == 'has_many' && $joinedBean) {
					$cache_key = $this->dao->beanName . '_' . $config['name'] . '_' . $bean->getId();
					//echo '<br>'.$cache_key;
					if (!array_key_exists($cache_key,$this->dao->cache_list)) {
						$this->dao->cache_list[$cache_key] = array();
					}
					/*
					$tmp = array();
					foreach ( $this->dao->cache_list[$cache_key] as $key => $o ) {
						$tmp[] = $key .'=='.$o->id;
					}
					echo '<br>'. implode(',',$tmp);
					if (!array_key_exists($joinedBean->getId(),$this->dao->cache_list[$cache_key])) {
						echo '<br>add '. get_class($joinedBean) .$joinedBean->getId().' in '. $cache_key;
					}
					*/
					$this->dao->cache_list[$cache_key][$joinedBean->getId()] = $joinedBean; 
					//echo '<br>'. implode(',',array_keys($this->dao->cache_list[$cache_key]));
				}
				
			}
			
			if (!(is_null($bean) || $bean->id == 0)) {
				//echo '<br>add '. get_class($bean) .' in '. $this->dao->beanName .'Dao '.$bean->getId();
				$list[$bean->getId()] = $bean;
			}
			
		}
		
		$results = array();
		
		// bean tree build
		foreach ( $list as $id => $bean) {
			
			$results[$id] = $this->dao->injectRelations($bean);	
			$this->dao->completedBeans[$id] = $bean;
			foreach ($this->rowObservers as $relation => $dao) {
				//echo '<br>-Nel '.$dao->beanName.'Dao ci sono '. count($dao->allBeans). ' beans di cui '. count ($dao->completedBeans) .' completi' ;
				if ( $dao->isReady ) {
					$dao->isReady = false;
					foreach ( $dao->allBeans as $b ) {
						if ( array_key_exists($b->getId() ,$dao->completedBeans)) {
							continue;
						}
						
						$dao->completedBeans[$b->getId()] = $b;
						$dao->injectRelations($b);
						
						//echo '<br>--completato '. $dao->beanName .' '. $b->getId();
					}
					$dao->isReady = true;
				}
				
				
			}		
		}
		
		return $results;		
	}
	
	
	
	
	

	
}

?>