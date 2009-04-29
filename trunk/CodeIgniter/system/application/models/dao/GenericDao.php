
<?php

require_once('PersistenceException.php');
require_once('BeanQuery.php');

class GenericDao
{
	public $factory;
	public $db;
	public $beanName;
	public $table;
	public $fields;
	public $has_many;
	public $has_one;
	
	public $toProxify;
	public $completedBeans;
	
	public $allFields;
	public $allBeans;
	public $allRelations;
	public $allNotLazyRelations;
	
	public function GenericDao(BeanFactory $beanFactory, $name, $config ) {
		$this->db = $beanFactory->db;
		$this->factory = $beanFactory;
		$this->beanName = $name;
		
		if (array_key_exists('table', $config)) {
			$this->table = $config['table'];
		}
		else {
			$this->table = strtolower($name);
		}	
		
		$this->has_one = array_key_exists('has_one', $config) ? $config['has_one'] : array();
		$this->has_many = array_key_exists('has_many', $config) ? $config['has_many'] : array();
		
		$this->fields = $config['fields'];			
		$this->buildRelations();
		
		$this->allBeans = new ArrayObject();
		$this->cache_list = array();
		$this->completedBeans = array();
	}
	
	protected function buildRelations() {
		$this->allFields = array();
		$this->allRelations = array();
		$this->allNotLazyRelations = array();
		$this->toProxify = false;
		foreach(array('has_one','has_many') as $type) {
			$list = $this->{$type};
			if (empty($list)) {
				continue;
			}
			foreach($list as $relation => $config) {
				if (!array_key_exists('lazy',$config)) {
					$config['lazy'] = false;
				}
				$config['name'] = $relation;
				$config['type'] = $type;
				$this->allRelations[$relation] = $config;
				
				if (!$config['lazy']) {
					$this->allNotLazyRelations[$relation] = $config;
				}
				else {
					//echo '<br>'. $this->beanName .' is to Proxify for '. $relation;
					$this->toProxify = true;
				}
				if ($type == 'has_one') {
					$this->fields[$relation.'_id'] = 'int';
				}
			}
		}
		$this->allFields[$this->table] = $this->getFields();
		//$this->toProxify = true;
	}
	
	public function getFields($prefix = false) {
		$table = $prefix ? $prefix : $this->table;
		if (!array_key_exists($table,$this->allFields)) {
			$this->allFields[$table] =  array();
			foreach ($this->fields as $field => $type) {
				$field = $prefix ? $prefix . $field : $field;
				$this->allFields[$table][$field] = $type;
			}
			
			print_r($this->allFields[$table]);
		}
		return $this->allFields[$table];
	}
	
	public function injectRelations($bean) {
		foreach ( $this->allNotLazyRelations as $relation => $config ) {
			//$dao = $this->factory->getDao($config['bean']);
			echo '<br>relazione '. $config['type'] .' '. $relation;
			if ( $config['type'] == 'has_one') {
				echo '<br>inject '. $config['bean'] .' in '.$relation.' of '. $this->beanName .' '. ($bean->{$relation} ? $bean->{$relation}->id : 0);
				$relation_id = $relation .'_id';
				$bean->{$relation} = $this->factory->getDao($config['bean'])->getOne($bean->{$relation_id});
				
			}
			else {
				echo '<br>inject '. $config['bean'] .' list in '.$relation.' of '. $this->beanName .' ';
				$bean->{$relation} = $this->getListByRelation($relation,$bean);
			}
		}
		return $bean;
	}
	
	public function getOne($keys) {
		if (is_null($keys) || !$keys) { return NULL; }
		if (!is_array($keys)) { $keys = array('id' => $keys); }
		if (array_key_exists($keys['id'],$this->allBeans)) { return $this->allBeans[$keys['id']]; }
		
		foreach ( $keys as $key => $value) {
			$this->db->where($this->table . '.' . $key, $value);
		}
		$list = $this->getList(1);
		return empty($list) ? NULL : array_shift($list);
	}
	
	public function getList($limit = NULL, $offset = NULL) {
		//echo '-> '.$this->beanName.'Dao getList '. $this->allNotLazyRelations;
		$query = new BeanQuery($this);
		return $query->select()->results();
	}
	
	
	public $cache_list;
	public function getListByRelation($relation,$bean) {
		if (!(is_object($bean) && $bean)) { return array(); }
		if (!$bean->getId()) { return array(); }
		
		$config = $this->allRelations[$relation];
		
		$cache_key = $this->beanName . '_' . $config['name'] . '_' . $bean->getId();
		
		if ( ! array_key_exists($cache_key, $this->cache_list)  ) {
			echo '<br>Genero '. $cache_key .' list';
			$dao = $this->factory->getDao($config['bean']);
			$this->db->where($dao->table.'.'.$config['fkey'],$bean->getId(),false);
			$this->cache_list[$cache_key] = $dao->getList();
		}
		else {
			echo '<br>'.$cache_key.' esiste ';
		}
		return $this->cache_list[$cache_key];
	}
	
	public function makeBean($row = false, $relation = false) {
		if (!$row) { return $this->createInstance(); }
		
		$id_name = $relation ? $relation .'id' : 'id';
		$id_value = $row[$id_name];
		
		if ($id_value === NULL || $id_value == 0) { return NULL; }
		
		if (!array_key_exists($id_value,$this->allBeans)) { 
			$bean = $this->createInstance();
			
			foreach ($this->getFields() as $field => $type  ) {
				$column = $relation ? $relation . $field : $field;
				$this->setFieldValue($bean, $field, $row[$column], $type);
			}
			
			$this->allBeans[$id_value] = $bean;
		}
		
		
		
		return $this->allBeans[$id_value];
	}
	
	private function setFieldValue($object, $fieldName, $value, $type) {
		if ($type == 'int' || $type == 'smallint') {
			$value = (int) $value;
		}
		elseif( $type == 'timestamp') {
			$value = human_to_unix($value);
		}
		elseif ($type == 'boolean') {
			if (!is_null($value)) {
				if (is_string($value)) {
					$value = (boolean) (($value == 't') || ($value == 'T'));
				}
				else {
					$value = ($value == 1) ? TRUE : FALSE;
				}
			}
		}
		$object->{$fieldName} = $value;
	}
	
	private function createInstance() {
		//echo '<br>Creo instanza di '. $this->beanName.' '. ($this->toProxify ? 'Proxy' : '');
		if ($this->toProxify) {
			//echo '<br>creo il proxy '.$this->beanName;
			$o = $this->factory->getProxy($this->beanName);
		}
		else {

			$o = new $this->beanName();
		}
		return $o;
		
	}

	
	
}

?>