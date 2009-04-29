<?php 
 
require_once('Proxy.php');

class BeanProxy extends Bean implements Proxy {
	public $beanFactory;
	public function setBeanFactory(BeanFactory $beanFactory) { $this->beanFactory = $beanFactory; }


	public function getExtend() { 
		return $this->beanFactory->getDao('Bean')->getOne($this->extend_id);		
	}

	public function getAttributes() { 
		return $this->beanFactory->getDao('Bean')->getListByRelation('attributes',$this);		
	}
}
 
 ?>