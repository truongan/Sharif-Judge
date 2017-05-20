<?php

class Test extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
	}
	public function call()
	{
		$this->load->model('xephang_model');
		$submission = array(
			'username'=>'thiennh', 
			'submit_id'=>'1', 
			'assignment'=> '2',
			'pre_score'=>'1000');
		$this->xephang_model->save_db($submission);
	}

	
}