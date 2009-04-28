<?php



class Welcome extends Controller {

	function Welcome()
	{
		parent::Controller();	
	}
	
	function index()
	{
		$this->output->enable_profiler(TRUE);
		//$this->_initialize();
		//$this->_create_package();
		
		$this->_show_bean();
		$this->load->view('welcome_message');
	}
	
	function _initialize() {
		$this->load->model('BeanFactory');
		$this->BeanFactory->getDao('package')->createTable();
		$this->BeanFactory->getDao('bean')->createTable();
		$this->BeanFactory->getDao('attribute')->createTable();
	}
	
	function _show_bean() {
		$this->load->model('BeanFactory');
		$o = $this->BeanFactory->getDao('package')->getOne( 9 );
		echo '<table>';
		foreach($o->getBeans() as $bean) {
			echo '<tr>';
			echo '<td>'.$bean->getName() . ($bean->getExtend() ? ' extends '. $bean->getExtend()->getName() : '') .'</td><td><table>';
				foreach ($bean->getAttributes() as $att) {
					echo '<tr>';
					echo '<td>'.$att->getName().'</td>';
					echo '</tr>';
				}
				
			echo '</table></td></tr>';
		}
		echo '</table>';
		
		
		
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