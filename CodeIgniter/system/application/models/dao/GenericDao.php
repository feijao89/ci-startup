<?php

require_once('PersistenceException.php');

class GenericDao
{
	protected $factory;
	public $db;
	protected $beanName;
	protected $table;
	protected $fields;
	protected $hasMany;
	protected $hasOne;
	
	protected $all;
	
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
		$this->hasOne = 	array_key_exists('has_one', $config) ? $config['has_one'] : array();
		$this->hasMany = 	array_key_exists('has_many', $config) ? $config['has_many'] : array();
		
		$this->fields = array($this->table => $config['fields']);	
		$this->all = new ArrayObject();//array();
	}
	
	public function getOne($keys) {
		if (!is_array($keys)) {
			if (!$keys) {
				return NULL;
			}
			$keys = array('id' => (int) $keys);
		}
		if (array_key_exists($keys['id'],$this->all)) {
			return $this->all[$keys['id']];
		}
		foreach($keys as $key => $value) {
			$this->db->where($this->table.'.'.$key,$value,false);
		}
		$list = $this->getList(1);		
		return empty($list) ? NULL : $list[0];
	}
	
	public function getList($limit=NULL, $offset=NULL) {
		$this->selectBean();
		foreach ($this->getRelations() as $relation => $config) {
			$dao = $this->factory->getDao($config['bean']);
			$dao->selectBean($relation);
			if (array_key_exists($relation,$this->hasOne)) {
				$this->db->join($dao->table .' AS '.$relation,$this->table.'.'.$relation.'_id = '.$relation.'.id','left');
			}
			else {
				$this->db->join($dao->table .' AS '.$relation,$this->table.'.id = '.$relation.'.'.$config['fkey'],'left');
			}
			
		}
		
		$query = $this->db->get($this->table);
		$list = array();
		foreach($query->result_array() as $row) {
			$o = $this->makeBean($row);
			$list[] = $o;
		}
		return $list;
	}
	
	protected function selectBean($prefix = false) {
		//$table = $table ? $table : $this->table;
		
		if (!$prefix) {			
			return $this->db->select(array_keys($this->getFields($this->table.'.')) );
		}
		//$table = $prefix ? $prefix : $this->table;
		foreach( $this->getFields() as $field => $config  ) {
			$this->db->select($prefix.'.'.$field .' AS '. $prefix. $field, false);
		}	
		foreach ($this->getRelations() as $relation => $config) {
			if ($config['type'] == 'has_one') {
				$field = $relation.'_id';
				
				$this->db->select($prefix.'.'.$field.' AS '. $prefix. $field, false);
			}			
		}
	}
	
	protected function makeBean($row = array(), $field_prefix = false) {
		//$name = $this->toProxify ? $this->beanName.'Proxy' : $this->beanName;
		if (!$row) {
			return $this->createInstance();
		}
		$id_name = $field_prefix ? $field_prefix .'id' : 'id';
		$id_value = $row[$id_name];
		$o = NULL;
		//echo '<br>'.$id_name .' = '. $id_value.'<br>';
		if ($id_value === NULL || $id_value == 0) {
			return $o;
		}
		
		if (!array_key_exists($id_value,$this->all)) {
			$object = $this->createInstance();
			
			foreach ($this->getFields() as $field => $type  ) {
				$column = $field_prefix ? $field_prefix . $field : $field;
				$this->setFieldValue($object, $field, $row[$column], $type);
			}
			
			$this->all[$id_value] = $object;
		}
		
		$o = $this->all[$id_value];
			
		foreach ($this->getRelations() as $relation => $config) {
			$dao = $this->factory->getDao($config['bean']);
			if (array_key_exists($relation,$this->hasOne)) {
				
				$o->{$relation} = $field_prefix ? $dao->getOne($row[$field_prefix.$relation.'_id']) : $dao->makeBean($row,$relation);
				$o->{$relation.'_id'} = ($o->{$relation}) ? $o->{$relation}->getId() : 0;
			}
			else {
				
				if ( $field_prefix ) {
					//echo '<br>-----relationlist<br>';
					$o->{$relation} = $this->getListByRelation($o,$config);
					//echo '<br>-----end relationlist';
				}
				else {
					$listEntry = $dao->makeBean($row,$relation);
					if ($listEntry) {
						$o->{$relation}[] = $listEntry;
						$key = $this->beanName.'_'.$relation .'_'.$o->getId();
						if (!array_key_exists($key,$this->_list_relations_cache)) {
							$this->_list_relations_cache[$key] = array();
						}
						//echo '<br>set '. $key .'';
						$this->_list_relations_cache[$key][] = $listEntry;
					}					
				}
			}
		}
		
		/*
		echo '<hr>Make Bean '. $this->beanName .'<br><pre>';
		print_r($o);
		echo '</pre>';
		*/
		return $o;
	}
	
	private function createInstance() {
		//echo '<br>Creo instanza di '. $this->beanName.' '. ($this->toProxify ? 'Proxy' : '');
		if ($this->toProxify) {
			//echo 'creo il proxy '.$this->beanName;
			$o = $this->factory->getProxy($this->beanName);
		}
		else {
			$o = new $this->beanName();
		}
		return $o;
		
	}
	
	public $_list_relations_cache = array();
	public function getListByRelation($object, $relationConfig ) {
		if (!$object->getId()) {
			return array();
		}
		if (is_string($relationConfig)) {
			$relationConfig = $this->getRelation($relationConfig);
		}
		$key = $this->beanName.'_'.$relationConfig['name'] .'_'.$object->getId();
		//echo '<br>get '.$key .' ';
		if (!array_key_exists($key,$this->_list_relations_cache)) {
			//echo 'non esiste';
			$this->_list_relations_cache[$key] = array();			
			
			$dao = $this->factory->getDao($relationConfig['bean']);
		
			$this->db->where($dao->table.'.'.$relationConfig['fkey'],$object->getId(),false);
			
			$this->_list_relations_cache[$key] = $dao->getList();
		}

		return $this->_list_relations_cache[$key];
		
	}

	
	
	private $_relations_chaching = NULL;
	protected $toProxify = false;
	protected function getRelations() {
		if (is_null($this->_relations_chaching)) {
			$this->_relations_chaching = array();
			foreach (array_merge($this->hasOne,$this->hasMany) as $relation => $config) {
				$config['name'] = $relation;
				$config['type'] = array_key_exists($relation,$this->hasOne) ? 'has_one' :'has_many';
				if (array_key_exists('lazy',$config)) {
					if ($config['lazy']) {
						$this->toProxify = true;
					}
					else {
						$this->_relations_chaching[$relation] = $config;
					}
				}
				else {
					$this->_relations_chaching[$relation] = $config;
				}
			}
			echo $this->beanName.' set proxify : '.$this->toProxify.'<br>';
		}
		
		return $this->_relations_chaching;
	}
	
	protected function getRelation($name) {
		$config = array(
			'name' => $name
		);
		if (array_key_exists($name, $this->hasOne)) {
			$config = array_merge($this->hasOne[$name],$config);
			$config['type'] = 'has_one';
		}
		else {
			$config = array_merge($this->hasMany[$name],$config);
			$config['type'] = 'has_many';
		}
		return $config;
	}

	private function getFields($prefix = false) {
		$table = $prefix ? $prefix : $this->table;
		if (!array_key_exists($table,$this->fields)) {
			$this->fields[$table] =  array();
			foreach ($this->fields[$this->table] as $field => $type) {
				$field = $prefix ? $prefix . $field : $field;
				$this->fields[$table][$field] = $type;
			}
		}
		return $this->fields[$table];
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
	
	/*
	 * Rende persistente un bean
	 */
	public function save($object) {
		if ($object->id == 0) {
			$this->create($object);
		}
		else {
			$this->update($object);
		}
	}
	
	/*
	 * Prenota un nuovo ID e inserisce un bean
	 */
	protected function create($object) {
		$object->id = $this->nextID();
		
		if ($this->prepare($object)) {
			$this->db->insert($this->table);
		}
	}
	
	/*
	 * Esegue un update
	 */
	protected function update($object) {
		$this->db->where('id',$object->id);
		if ($this->prepare($object)) {
			$this->db->update($this->table);
		}
	}
	
	/*
	 * Prepara i parametri SET per salvare un oggetto
	 */
	protected function prepare($object) {
		foreach($this->getFields() as $field => $type) {
			if ($type == 'timestamp') {
				$value = $time_str = (empty($object->{$field})) ? 'CURRENT_TIMESTAMP' : 'TIMESTAMP WITH TIME ZONE \'epoch\' + '.$object->{$field}.' * INTERVAL \'1 second\'';
				$this->db->set($field,$time_str,false);
			}
			elseif ($type == 'boolean') {
				$this->db->set($field, $object->{$field} ? 'TRUE' : 'FALSE',false);	
			}
			else {
				$this->db->set($field, $object->{$field});
			}
			
		}
		return true;
	}
	
	/*
	 * Ritorna un nuovo id per un oggetto
	 */
	protected function nextID() {
		$query = $this->db->query('SELECT nextval(\''.$this->table.'_id_seq\') as new_id');
		$row = $query->row();
		return $row->new_id;
	}
	
	/*
	 * Cancella un Bean dal database
	 */
	public function delete($object) {
		$this->db->where('id',$object->id);
		return $this->db->delete($this->table);
	}

	/*
	 * Crea la tabella relativa nel database
	 */
	public function createTable($configTest = false) {
		if ($this->db->table_exists($this->table)) {
			return;
		}
		$this->db->trans_begin();
		
		$sql = 'CREATE TABLE '. $this->table .' (';
		$att = array();
		foreach ( $this->fields[$this->table] as $field => $type ) {
			if ($field == 'id') $type = 'pkey';
			$att[] = ' '. $field .' '. $this->getFieldType($type);
		}
		$sql .= implode(',',$att);
		$sql .= ');';
		$seq = $this->table.'_id_seq';
		$sql .= 'CREATE SEQUENCE '.$seq.';';
		$sql .= 'ALTER TABLE '.$this->table.' ALTER COLUMN id SET DEFAULT nextval(\''.$seq.'\');';
		foreach ($this->hasOne as $field => $bean) {
			$sql .= 'CREATE INDEX '. $this->table .'_'. $field .'_index ON '. $this->table.' ('. $field.'_id);';
		}
		
		$query = $this->db->query($sql);
		if ($configTest || ($this->db->trans_status() === FALSE)) {
			$this->db->trans_rollback();
		}
		else {
			$this->db->trans_commit();
		}		
		echo $query;
	}
	
	private function getFieldType($type) {
		if ($type == 'pkey') { return 'INTEGER PRIMARY KEY'; } elseif($type == 'int' ) {return 'INTEGER';} elseif($type == 'timestamp') {return 'TIMESTAMP WITHOUT TIME ZONE';}	elseif($type == 'text') {return 'TEXT';	}elseif(strpos($type,'varchar') !== FALSE) {return str_replace('varchar','CHARACTER VARYING',$type);}elseif(strpos($type,'char') !== FALSE) {return str_replace('char','CHARACTER',$type);}
		return (strtoupper($type));
	}
	
}

?>