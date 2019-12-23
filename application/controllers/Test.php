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
		// if ( ! $this->migration->version(20180615113739))
		// if ( ! $this->migration->version(20180927200459))
		// if ( ! $this->migration->version(20190907150459))
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
	public function add_user($name, $email, $password, $role){
		$this->user_model->add_user(
			$name,
			$email,
			$password,
			$role
		);
	}
	public function remove_duplicate_theme_settings(){
		$a = $this->settings_model->get_setting('theme', 'default');
		$this->db->delete('settings', array('shj_key' => 'theme'));
		$this->db->insert('settings', array('shj_key' => 'theme', 'shj_value' => $a));
	}
	public function adhoc(){
		$this->load->model('settings_model');
		echo ("abc");
		echo ( $this->db->get_where('settings', array('shj_key' => "theme"))->num_rows()  );
		die();
		
	}
}
