<?php
require_once('dao/GenericDao.php');


class BeanFactory extends Model
{
	public $daos;
	public $proxies;
	public function BeanFactory() {
		parent::Model();
		$this->load->config('beans');
		$this->daos = array();
		$this->proxies = array();
	}
	
	public function getProxy($beanName) {
		$beanName = ucfirst($beanName).'Proxy';
		if (!array_key_exists($beanName,$this->proxies)) {
			$path = APPPATH . 'models/proxy/';
			$file = $path . '/' . $beanName . EXT;		
			// Check if file exists, require_once if it does
			if (file_exists($file))			{
				require_once($file);				
			}
			else {
				throw new Exception($beanName.' not found. '. $file .' not exists');
			}
			$this->proxies[$beanName] = $beanName;
		}
		$bean = new $beanName();
		$bean->setBeanFactory($this);
		return $bean;
	}
	
	public function getDao($daoName) {
		$daoName = ucfirst($daoName);
		if(!array_key_exists($daoName,$this->daos)) {
			$this->daos[$daoName] = $this->createDao($daoName);
		}
		return $this->daos[$daoName];
	}
	private function createDao($daoName) {
		$config = $this->getConfig($daoName);
		$class_name = 'GenericDao';
		// Prepare path
		$path = APPPATH . 'models/';
		
		if (array_key_exists('dao',$config)) {

			$class_name = $config['dao'];
			// Require BeanDao
			$file = $path . 'dao/' . $class_name . EXT;		
			// Check if file exists, require_once if it does
			if (file_exists($file))			{
				require_once($file);				
			}
			else {
				throw new Exception($class_name.' not found. '. $file .' not exists');
			}
			
		}
		// Import model
		$file = $path . 'bean/' . $daoName . EXT;
		if (file_exists($file)){
			//echo $file;
			require_once($file);
				
		}
		/*
		else {
			throw new Exception($daoName.' not found. '. $file .' not exists');
		}
		*/
		
		//echo 'new Dao '. $daoName;
		$dao = new $class_name($this,$daoName,$config);
		
		
		
		return $dao;
	}
	private function getConfig($bean) {
		$config = config_item('beans');
		if (!($config && array_key_exists($bean,$config))) {
			throw new Exception('Model not found '. $bean);
		}
		
		return $config[$bean];
	}
	
	public function initialize() {
		$this->load->helper('file');
		foreach(config_item('beans') as $bean => $config) {
			$dao = $this->getDao($bean);
			$this->createTable($dao);
			$this->createBeanClass($dao);
		}
	}
	
	/*
	 * Crea la tabella relativa nel database
	 */
	private function createTable($dao) {
		$br = "\n";
		$dao->db->trans_begin();
		
		$sql = 'CREATE TABLE '. $dao->table .' ('.$br;
		$att = array();
		foreach ( $dao->fields as $field => $type ) {
			if ($field == 'id') $type = 'pkey';
			$att[] = ' '. $field .' '. $this->getFieldType($type);
		}
		$sql .= implode(','.$br,$att);
		$sql .= $br.');'.$br;
		$seq = $dao->table.'_id_seq';
		$sql .= $br.'CREATE SEQUENCE '.$seq.';';
		$sql .= $br.'ALTER TABLE '.$dao->table.' ALTER COLUMN id SET DEFAULT nextval(\''.$seq.'\');';
		foreach ($dao->has_one as $field => $bean) {
			$sql .= $br.'CREATE INDEX '. $dao->table .'_'. $field .'_index ON '. $dao->table.' ('. $field.'_id);';
		}
		echo '<pre>'.$sql.'</pre>';
		if ($dao->db->table_exists($dao->table)) {
			echo '<h3>Table Already Exists</h3>';
			return;
		}
		$query = $dao->db->query($sql);
		if (($dao->db->trans_status() === FALSE)) {
			$dao->db->trans_rollback();
		}
		else {
			$dao->db->trans_commit();
			echo '<h3>Table Created</h3>';
		}		
		//return $this;
	}
	
	private function getFieldType($type) {
		if ($type == 'pkey') { return 'INTEGER PRIMARY KEY'; } elseif($type == 'int' ) {return 'INTEGER';} elseif($type == 'timestamp') {return 'TIMESTAMP WITHOUT TIME ZONE';}	elseif($type == 'text') {return 'TEXT';	}elseif(strpos($type,'varchar') !== FALSE) {return str_replace('varchar','CHARACTER VARYING',$type);}elseif(strpos($type,'char') !== FALSE) {return str_replace('char','CHARACTER',$type);}
		return (strtoupper($type));
	}
	
	private function createBeanClass($dao) {
		$br = "\n";
		$tab = "\t";
		
		//$str = ''. $br;
		$str = $br .'class '. $dao->beanName .' {'. $br;
		$str_atts = array();
		$str_construct = array();
		foreach ( $dao->fields as $field => $type) {
			$str .= $tab . 'public $'. $field .';' . $br;
			//$str_att = 'public function set'. ucfirst($field) .'($value) { $dao->'. $field .' = $value ; } '.$br;
			//$str_att .= 'public function get'. ucfirst($field) .'() { return $dao->'. $field .' ; } '.$br;
			$str_atts[$field] = $this->getMethodString($field,$tab,$br);			
			if ($type == 'int') {
				$str_construct[] = $tab.$tab.'$this->'.$field.' = 0;';
			}
		}
		foreach ($dao->allRelations as $relation => $config ) {
			if ($config['type'] == 'has_one') {
				unset($str_atts[$relation.'_id']);
				$str_atts[$relation] = $this->getMethodString($relation,$tab,$br);
				$str_construct[] = $tab.$tab.'$this->'.$relation.' = NULL;';
			}
			else {
				$str .= $tab .'public $'. $relation .';'. $br;
				$str_atts[$relation] = $this->getMethodString($relation,$tab,$br);	
				$str_construct[] = $tab.$tab.'$this->'.$relation.' = array();';
			}
		}
		$str .= $br. $tab.'public function __construct() {'.$br;
		$str .= implode($br, $str_construct);
		$str .= $br.$tab.'}'.$br;
		$str .= implode($br,$str_atts);
		$str .= $br.'} '.$br;
		echo '<pre>&lt;?php' . $str .'?&gt;</pre>';
		$str = "<?php \n $str \n ?>";
		$path = APPPATH . 'models/bean';
		$file = $path . '/' . $dao->beanName . EXT;		
		// Check if file exists, require_once if it does
		if (!file_exists($file))			{
			if ( ! write_file($file, $str))
			{
			       echo '<h3>Unable to write the file</h3>';;
			}
			else
			{
			     echo '<h3>File written!</h3>';
			}
		}
		else {
			echo '<h3>File Already Created</h3>';
		}
		
		if ( $dao->toProxify ) {
			$str_proxy =  $br .'require_once(\'Proxy.php\');'.$br.$br;
			//$str_proxy = 'require_once(\'Proxy.php\');'.$br.$br;
			$str_proxy .= 'class '. $dao->beanName .'Proxy extends '.$dao->beanName.' implements Proxy {'. $br;
			$str_proxy .= $tab.'public $beanFactory;'. $br;
			$str_proxy .= $tab.'public function setBeanFactory(BeanFactory $beanFactory) { $this->beanFactory = $beanFactory; }'. $br;
			foreach($dao->allRelations as $relation => $config ) {
				$str_lazy = array();
				if (!$config['lazy']) { continue; }
				$str_lazy [] = $br.$tab.'public function get'. ucfirst($relation) .'() { '. $br;
				//$str_lazy .= $tab.$tab;
				if ( $config['type'] == 'has_one') {
					$str_lazy [] = 'return $this->beanFactory->getDao(\''.$config['bean'].'\')->getOne($this->'.$relation.'_id);';
				}
				else {
					$str_lazy [] = 'return $this->beanFactory->getDao(\''.$dao->beanName.'\')->getListByRelation(\''.$relation.'\',$this);';
					/*
					$str_lazy [] = '$dao = $this->factory->getDao(\''.$config['bean'].'\');'.$br;
					$str_lazy [] = '$dao->db->where(\''.$config['fkey'].'\',$this->id);'.$br;
					$str_lazy [] = 'return $dao->getListByRelation(\''.$relation.'\',$this);';
					*/
				}
				$str_lazy [] = $br.$tab.'}';
				$str_proxy .= $br .implode($tab.$tab,$str_lazy) ;
			}
			$str_proxy .= $br.'}'.$br ;
			echo '<pre>&lt;?php' . $str_proxy .'?&gt;</pre>';
			$str_proxy = "<?php \n $str_proxy \n ?>";
			//echo '<pre>' . $str_proxy .'</pre>';
			
			$path = APPPATH . 'models/proxy';
			$file = $path . '/' . $dao->beanName.'Proxy' . EXT;		
			// Check if file exists, require_once if it does
			if (!file_exists($file))			{
				if ( ! write_file($file, $str_proxy))
				{
				       echo '<h3>Unable to write the file</h3>';;
				}
				else
				{
				     echo '<h3>File written!</h3>';
				}
			}
			else {
				echo '<h3>File Already Created</h3>';
			}
		}
	}
	
	private function getMethodString($field, $prefix, $postfix) {
		$str_att = $prefix.'public function set'. ucfirst($field) .'($value) { $this->'. $field .' = $value ; } '.$postfix;
		$str_att .= $prefix.'public function get'. ucfirst($field) .'() { return $this->'. $field .' ; }'.$postfix;
		return $str_att;
	}
}

?>