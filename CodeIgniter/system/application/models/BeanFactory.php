<?php
require_once('dao/GenericDao.php');
require_once('bean/Package.php');
require_once('bean/Test1.php');

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
		if (array_key_exists('dao',$config)) {
			$class_name = $config['dao'];
			
			// Prepare path
			$path = APPPATH . 'models/dao';
			$file = $path . '/' . $class_name . EXT;		
			// Check if file exists, require_once if it does
			if (file_exists($file))			{
				require_once($file);				
			}
			else {
				throw new Exception($class_name.' not found. '. $file .' not exists');
			}
		}
		
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
}

?>