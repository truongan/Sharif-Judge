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

        //return;
		// if ( ! $this->migration->version(20180611171404))
		if ( ! $this->migration->latest())
		{
			show_error($this->migration->error_string());
		}


    }
}
