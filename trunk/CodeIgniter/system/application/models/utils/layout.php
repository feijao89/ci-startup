<?php



class Layout extends Model
{
	private $header_data;
	private $template;
	private $messages;
	private $messages_skey;
	private $method;	
	private $errors;
	private $error_page;
	
	function __construct() {
		parent::Model();
		
		// se siamo in debug abilito il profiler
		if ( config_item('debug') ) {
			$this->output->enable_profiler(TRUE);
		}	
		$this->template = config_item('template');
		$this->template_data = config_item('template_default_data');
		$this->errors = array();
		$this->error_page = config_item('error_page');
		
		$this->messages = array();
		$this->method = 'html'; // or json or xml			
	}
	
	function addJs($name){
		if (!is_array($name)) {
			$name = array($name);
		}
		foreach($name as $js_filename) {
			$this->template_data['js'][] = $js_filename.'.js';
		}		
	}
	
	function addCss($name) {
		if (!is_array($name)) {
			$name = array($name);
		}
		foreach($name as $css_filename) {
			$this->template_data['css'][] = $css_filename.'.css';
		}
	}
	
	function setMethod($name) {
		$this->method = $name;
	}
	
	function setTemplate($name) {
		$this->template = $name;
	}
	
	function addError($field, $error, $page = false) {
		$error_string = $field.'_'.$error;
		$this->errors[] = $error_string;
		if ($page) {
			$this->error_page = $page;
		}
	}
	
	function addMessage($msg,$value,$in_session = false) {
		$this->messages[$msg] = $value;
		if ($in_session) {
			$messages = $this->session->userdata($this->messages_skey);
			if (!is_array($messages)) {
				$messages = array();
			}
			$messages[$msg] = $value;
			$this->session->set_userdata($this->messages_skey,$messages);
		}
	}
	
	function addSessionMessage($msg,$value) {
		$this->addMessage($msg,$value,TRUE);
	}
	
	function hasErrors() {
		return ( count($this->errors) > 0 );
	}
	
	function getErrors() {
		return $this->errors;
	}
	
	function show($view, $data = array(), $method = 'html' ) {
		if ( $view == 'json' )  {
			$this->output->enable_profiler(FALSE);
			$this->method = 'json';
		}
		elseif ($method == 'xml') {
			$this->output->enable_profiler(FALSE);
			$this->method = 'xml';
		}
		$data = (is_object($data)) ? get_object_vars($data) : $data;
		
		if ( $this->method == 'json' ) {
			$this->load->helper('json');
			
			$json_data = array(
				'result' => $data,
				'error' => false,
				'errors' => array()
			);
			if ( $this->hasErrors() ) {
				$json_data['result'] = '';
				$json_data['error'] = true;
				$json_data['errors'] = $this->getErrors();
			}
			echo json_encode($json_data);
			return ;
		}
		
		$template_data = $this->template_data;

		// costruisco il template_data del body
		$session_messages = $this->session->userdata($this->messages_skey);
		if ( $session_messages ) {
			$this->messages = array_merge($this->messages, $session_messages);	
		}
		foreach ( $this->messages as $msg => $value ) {
			$data[$msg] = $value;	
		}

		if ( $this->hasErrors()  ) {
			$template_data['error'] = $this->getErrors();
			$template_data['body'] = $this->load->view($this->error_page, $data, TRUE	);	
		}
		else {
			// carico il corpo della pagina con i dati ricevuti
			$template_data['body'] = $this->load->view($view, $data, TRUE	);	
		}		
		
		if ($this->template && ($this->method != 'xml') ) {
			// e lo visualizzo all'interno di un layout
			$this->load->view($this->template, $template_data);		
		}
		else {
			
			echo $template_data['body'];
		}
	}
	
	function redirect($url) {
		if ($this->hasErrors()) {
			$this->show('');
		}
		else {
			redirect($url);
		}
	}
	
	/*
	function addMessage($msg, $value, $in_session = false) {
		if ( $msg == MESSAGE_ERRORS ) {			
			if (!empty($value['page'])) {
				$this->error_page = $value['page'];
			}
			$error_string = $value['field'] .'_'.$value['error'];			
			$value = $error_string;	
		}
		
		if (array_key_exists($msg,$this->messages)) {
			$tmp = $this->messages[$msg];
			if ( !is_array($tmp) ) {
				$tmp = array($tmp);
			}
			$tmp[] = $value;
			$value = $tmp;
		}
		$this->messages[$msg] = $value;
		if ( $in_session ) {
			if (( $messages = $this->session->userdata(SESSION_KEY_MESSAGES) ) == FALSE) {
				$messages = array();
			}
			$messages[$msg]	= $value;
			$this->session->set_userdata('messages', $messages);
		}
	}
	*/
	/*
	function hasErrors() {
		if ( !array_key_exists(MESSAGE_ERRORS, $this->messages) ) {
			return FALSE;
		}
		return ( count($this->messages[MESSAGE_ERRORS]) > 0 );
	}
	
	function getErrors() {
		return $this->messages[MESSAGE_ERRORS];
	}
	*/
	

	
}

?>