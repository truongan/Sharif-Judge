<?php
/**
 * Sharif Judge online judge
 * @file Submit.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Test extends CI_Controller
{

	public function __construct()
	{
        parent::__construct();
		
        if (!is_cli()) {
            show_error("This function is meant to be called from cli only");
            trigger_error("Can only run test in commandline", E_USER_ERROR); 
		}
    }

    public function migrate(){

		$this->load->library('migration');

		//Update the path, should not be necessary on production site.
		$path = dirname(__DIR__, 2);
		$this->settings_model->set_setting('tester_path', $path . '/tester');
		$this->settings_model->set_setting('assignments_root', $path . '/assignments');
		
		//return;
		// if ( ! $this->migration->version(20180611171404))
		if ( ! $this->migration->latest())
		{
			show_error($this->migration->error_string());
		}

	}
	
	public function add_test_user(){
		$this->user_model->add_user(
			'abc',
			'def@fde.def',
			'1234567890',
			'admin'
		);
	}
	public function ad_hoc(){
		$this->load->model('language_model');
		var_dump($this->language_model->all_languages());
	}
}
