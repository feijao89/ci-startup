<?php 
 
require_once('Proxy.php');

class PackageProxy extends Package implements Proxy {
	public $beanFactory;
	public function setBeanFactory(BeanFactory $beanFactory) { $this->beanFactory = $beanFactory; }


	public function getBeans() { 
	
		return array_values($this->beanFactory->getDao('Package')->getListByRelation('beans',$this));		
	}
}
 
 ?>