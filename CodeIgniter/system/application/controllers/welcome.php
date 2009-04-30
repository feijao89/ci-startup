<?php



class Welcome extends Controller {

	function Welcome()
	{
		parent::Controller();	
	}
	
	function index()
	{
		$this->output->enable_profiler(TRUE);

		/*
		$list = array();
		$list[] = $this->_create_knight('joke2k','Joke');
		$list[] = $this->_create_knight('mekhet','Mekhet sotto terra');
		$list[] = $this->_create_knight('grety','Dolce Grety');
		
		foreach ($list as $k ) {
			echo '<br>'. $k->getUsername();
		}*/
		
		$this->_show_bean();
		
		
		//$this->BeanFactory->initialize();
		$this->load->view('welcome_message');
	}
	
	
	function _create_knight($username,$name) {
		$this->load->model('BeanFactory');
		//$this->BeanFactory->getDao('knight')->createTable();
		$dao = $this->BeanFactory->getDao('knight');
		//echo 'qui';
		$k = new Knight();
		$k->setName($name);
		$k->setUsername($username);
		$k->setEmail($username.'@email.it');
		$k->setPassword(md5($name.$username));
		return $dao->save($k);
	}
	
	function _show_bean() {
		$this->load->model('BeanFactory');
		
		$o = $this->BeanFactory->getDao('package')->getOne( 9 );
		
		echo '<h1>Package : '. $o->getName() .'</h1>';
		echo '<table>';
		//$o->getBeans();
		//print_r($o);
		foreach($o->getBeans() as $bean) {
			
			echo '<tr>';
			echo '<td>'.$bean->getId().') '.$bean->getName();
			if ($bean->getExtend()) {
				echo ' extends '. $bean->getExtend()->getId() .') '. $bean->getExtend()->getPackage()->getName() .':'.$bean->getExtend()->getName() ;
				if ( $bean->getExtend()->getExtend() ) {
					echo ' extends '. $bean->getExtend()->getExtend()->getId() .') '. $bean->getExtend()->getExtend()->getPackage()->getName() .':'.$bean->getExtend()->getExtend()->getName() ;
				}
			}
			echo '</td><td><table>';
			
			
			$b = $bean;
			$i = 0;
			
			do {
				foreach ($b->getAttributes() as $att) {
					echo '<tr>';
					echo '<td>'.$att->getId() .' '.$att->getName() .' -> '. $att->external_id;
					if ( $att->getExternal() ) {
						echo ' '. $att->getExternal()->getName();
					}
					echo '</td>';
					echo '</tr>';
				}
				
				$b = $b->getExtend();
			}
			while ($b );
			
			echo '</table></td></tr>';
		}
		echo '</table>';

		//print_r($this->BeanFactory->getDao('package')->cache_list);
		
		
		
	}
	
	function _create_bean() {
		$this->load->model('BeanFactory');
		$t1 = new Bean();
		$t1->name = 'Main Bean';
		$t1->description = 'sic est';
		$t1->setPackage( $this->BeanFactory->getDao('package')->getOne(9));
		$t1->setExtend( $this->BeanFactory->getDao('bean')->getOne(29) );
		$this->BeanFactory->getDao('bean')->save($t1);
		$list = array(
			'id' => array('type' => 'int', 'value' => 'PRIMARY KEY'),
			'name' => array('type' => 'varchar(30)', 'value' => 'NOT NULL'),
			'description' => array('type' => 'text', 'value' => '')
		);
		foreach ($list as $name => $values) {
			$tn = $t1->makeAttribute($name, $values['type'], $values['value']);
			$this->BeanFactory->getDao('attribute')->save($tn);
			$t1->addAttribute($tn);
		}
		
		
		$o = $this->BeanFactory->getDao('bean')->getOne($t1->getId() );
		echo '<pre>';
		print_r($o);
		echo '</pre>';
	}
	
	function _get_test1() {
		$this->load->model('BeanFactory');
		$o = $this->BeanFactory->getDao('test1')->getOne('15');
		echo $o->b .' => '. ($o->a ? 'SI' : 'NO') .'<br>';
	}
	
	function _create_test1() {
		$this->load->model('BeanFactory');
		$this->BeanFactory->getDao('test1')->createTable();
		$t1 = new Test1();
		$t1->a = TRUE;
		$t1->b = 'sic est';
		$this->BeanFactory->getDao('test1')->save($t1);
		$t2 = new Test1();
		$t2->a = FALSE;
		$t2->b = 'non est';
		$this->BeanFactory->getDao('test1')->save($t2);
		echo '<table>';
		foreach($this->BeanFactory->getDao('test1')->getList() as $row) {
			echo '<tr><td>'. $row->id .'</td><td>'. ($row->a ? 'SI' : 'NO').'</td><td>'. $row->b .'</td></tr>';
		}
		echo '</table>';
	}
	
	function _create_package() {
		$this->load->model('BeanFactory');
		$p = new Package();
		$p->name = 'Utils';
		$p->description = 'Utilità varie';
		$this->BeanFactory->getDao('package')->save($p);
	}
	
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */