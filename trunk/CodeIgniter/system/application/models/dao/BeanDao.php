<?php

/*
 * Classe che restituisce Bean(s)
 */

class BeanDao
{
	public function getOne($beanId) {
		if(!$beanId) { return NULL;	}
		
		if (!is_array($beanId)) {
			$beanId = array('id' => $beanId);
		}
		
		$this->selectBean()->whereBeanHasKey($beanId);
		
		$list = $this->getList(1);
		
		if (!$list || empty($list)) {
			return NULL;
		}
		
		return array_shift($list);
		
	}
	
	public function selectBean() {
		$this->db->select(array_keys($this->getFields($this->table.'.')) );
	}
	
	public function whereBeanHasKey($keys)  {
		foreach ($keys as $key => $value) {
			if (is_int($value)) {
				$this->db->where($key,$value,false);
			}
			else {
				$this->db->where($key,$value);
			}			
		}
	}
}

?>