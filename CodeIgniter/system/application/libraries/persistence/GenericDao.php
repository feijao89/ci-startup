<?php

require_once('PersistenceException.php');
require_once('BeanQuery.php');
require_once('Proxy.php');



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
        public $isReady = true;
        //public $orderBy = array();
       
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
               
        }
       
        public function getFields($prefix = false) {
                $table = $prefix ? $prefix : $this->table;
                if (!array_key_exists($table,$this->allFields)) {
                        $this->allFields[$table] =  array();
                        foreach ($this->fields as $field => $type) {
                                $field = $prefix ? $prefix . $field : $field;
                                $this->allFields[$table][$field] = $type;
                        }
                }
                return $this->allFields[$table];
        }
       
        public function injectRelations($bean) {
                foreach ( $this->allNotLazyRelations as $relation => $config ) {
                        //echo '<br>relazione '. $config['type'] .' '. $relation;
                        if ( $config['type'] == 'has_one') {
                                //echo '<br>inject '. $config['bean'] .' in '.$relation.' of '. $this->beanName .' '. ($bean->{$relation} ? $bean->{$relation}->id : 0);
                                $relation_id = $relation .'_id';
                                $bean->{$relation} = $this->factory->getDao($config['bean'])->getOne($bean->{$relation_id});
                        }
                        else {
                                $bean->{$relation} = $this->getListByRelation($relation,$bean);
                                /*
                                if ( $relation == 'attributes' ) {
                                        $cache = $config['bean'] . '_' . $config['name'] . '_' . $bean->getId();
                                        echo '<br>inject '. $this->beanName .'.'.$relation.'['.$bean->id.'] = '.$config['bean'].' count : '. count($bean->{$relation}) ;
                                        if ( array_key_exists($cache,$this->cache_list))echo implode(',', array_keys($this->cache_list[$cache]));
                                }
                                */
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
                $query->select();
                //$this->orderBy[$this->table.'.id'] = 'DESC';
                return $query->results();
        }
       
       
        public $cache_list;
        public function getListByRelation($relation,$bean) {
                //echo '<br>getListByRelation('.$relation.','.$bean->getId().')';
                if (!(is_object($bean) && $bean)) { return array(); }
                if (!$bean->getId()) { return array(); }
               
                $config = $this->allRelations[$relation];
               
                $cache_key = $this->beanName . '_' . $config['name'] . '_' . $bean->getId();

                if ( ! array_key_exists($cache_key, $this->cache_list)  ) {
                        //echo '<br>Genero '. $cache_key .' list '. count($this->cache_list);
                       
                        $dao = $this->factory->getDao($config['bean']);
                        $this->db->where($dao->table.'.'.$config['fkey'],$bean->getId(),false);
                        $this->cache_list[$cache_key] = $dao->getList();
                        //echo '<br> set cache '.$cache_key . '  = '. implode(',',array_keys($this->cache_list[$cache_key]));
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
                if ($this->toProxify) {
                        $o = $this->factory->getProxy($this->beanName);
                }
                else {
                        $o = new $this->beanName();
                }
                return $o;
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
                return $object;
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
                $this->db->delete($this->table);
                return $object;
        }


       
       
       
}

?>