<?php
/**
 * Sharif Judge online judge
 * @file Install.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Install extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('string');
	}


	// ------------------------------------------------------------------------


	public function index()
	{
		if ($this->db->table_exists('sessions'))
			show_error('Sharif Judge is already installed.');

		$this->form_validation->set_rules('username', 'username', 'required|min_length[3]|max_length[20]|alpha_numeric|lowercase');
		$this->form_validation->set_rules('email', 'email', 'required|max_length[40]|valid_email|lowercase');
		$this->form_validation->set_rules('password', 'password', 'required|min_length[6]|max_length[200]');
		$this->form_validation->set_rules('password_again', 'password confirmation', 'required|matches[password]');

		$data['installed'] = FALSE;

		if ($this->form_validation->run()) {

			$this->setup_database();

			// add admin user
			$this->user_model->add_user(
				$this->input->post('username'),
				$this->input->post('email'),
				$this->input->post('password'),
				'admin'
			);

			$data = $this->setup_encryption_key();
		}

		$this->twig->display('pages/admin/install.twig', $data);
	}

	public function cli_install($username, $email, $password, $random_password = false){

		if (!is_cli()) {
			show_error("This function is meant to be called from cli only");
			die();
			return ;
		}

		if ($this->db->table_exists('sessions'))
		{
			show_error('Sharif Judge is already installed.');
			return ;
		}

		$this->setup_database();

		if($random_password) $password = random_string('alnum', 50);

		// add admin user
		$this->user_model->add_user(
			$username,
			$email,
			$password,
			'admin'
		);

		$this->setup_encryption_key();
	}

	private function setup_encryption_key(){
		// Using a random string as encryption key
		$config_path = rtrim(APPPATH,'/').'/config/config.php';
		$config_content = file_get_contents($config_path);
		$random_key = random_string('alnum', 32);
		$res = @file_put_contents($config_path, str_replace($this->config->item('encryption_key'), $random_key, $config_content));
		if ($res === FALSE)
			$data['key_changed'] = FALSE;
		else
			$data['key_changed'] = TRUE;

		$data['installed'] = TRUE;
		$data['enc_key'] = $this->config->item('encryption_key');
		$data['random_key'] = random_string('alnum', 32);

		return $data;
	}
	private function setup_database(){
		// Creating Tables:
		// sessions, submissions, assignments, notifications, problems, queue, scoreboard, settings, users

		$this->load->library('migration');

		//if ( ! $this->migration->version(20180611171404))
		if ( ! $this->migration->latest())
		{
			show_error($this->migration->error_string());
		}


		// insert default settings to table 'settings'
		$result = $this->db->insert_batch('settings', array(
			array('shj_key' => 'site_name',               'shj_value' => '_sitenametobereplace_'),
			array('shj_key' => 'timezone',               'shj_value' => 'Asia/Ho_Chi_Minh'),
			array('shj_key' => 'tester_path',            'shj_value' => dirname(BASEPATH) . '/tester'),
			array('shj_key' => 'assignments_root',       'shj_value' => dirname(BASEPATH) . '/assignments'),
			array('shj_key' => 'file_size_limit',        'shj_value' => '50'),
			array('shj_key' => 'output_size_limit',      'shj_value' => '1024'),
			array('shj_key' => 'queue_is_working',       'shj_value' => '0'),
			array('shj_key' => 'default_late_rule',      'shj_value' => "/* \n * Put coefficient (from 100) in variable \$coefficient.\n * You can use variables \$extra_time and \$delay.\n * \$extra_time is the total extra time given to users\n * (in seconds) and \$delay is number of seconds passed\n * from finish time (can be negative).\n *  In this example, \$extra_time is 172800 (2 days):\n */\n\nif (\$delay<=0)\n  // no delay\n  \$coefficient = 100;\n\nelseif (\$delay<=3600)\n  // delay less than 1 hour\n  \$coefficient = ceil(100-((30*\$delay)/3600));\n\nelseif (\$delay<=86400)\n  // delay more than 1 hour and less than 1 day\n  \$coefficient = 70;\n\nelseif ((\$delay-86400)<=3600)\n  // delay less than 1 hour in second day\n  \$coefficient = ceil(70-((20*(\$delay-86400))/3600));\n\nelseif ((\$delay-86400)<=86400)\n  // delay more than 1 hour in second day\n  \$coefficient = 50;\n\nelseif (\$delay > \$extra_time)\n  // too late\n  \$coefficient = 0;"),
			array('shj_key' => 'enable_c_shield',        'shj_value' => '0'),
			array('shj_key' => 'enable_cpp_shield',      'shj_value' => '0'),
			array('shj_key' => 'enable_py2_shield',      'shj_value' => '0'),
			array('shj_key' => 'enable_py3_shield',      'shj_value' => '0'),
			array('shj_key' => 'enable_java_policy',     'shj_value' => '0'),
			array('shj_key' => 'enable_log',             'shj_value' => '1'),
			array('shj_key' => 'submit_penalty',         'shj_value' => '300'),
			array('shj_key' => 'enable_registration',    'shj_value' => '0'),
			array('shj_key' => 'registration_code',      'shj_value' => '0'),
			array('shj_key' => 'mail_from',              'shj_value' => 'wcj@example.com'),
			array('shj_key' => 'mail_from_name',         'shj_value' => 'Wecode Judge'),
			array('shj_key' => 'reset_password_mail',    'shj_value' => "<p>\nSomeone requested a password reset for your {SITE_NAME} Wecode Judge account at {SITE_URL}.\n</p>\n<p>\nTo change your password, visit this link:\n</p>\n<p>\n<a href=\"{RESET_LINK}\">Reset Password</a>\n</p>\n<p>\nThe link is valid for {VALID_TIME}. If you don't want to change your password, just ignore this email.\n</p>"),
			array('shj_key' => 'add_user_mail',          'shj_value' => "<p>\nHello! You are registered in {SITE_NAME} Wecode Judge at {SITE_URL} as {ROLE}.\n</p>\n<p>\nYour username: {USERNAME}\n</p>\n<p>\nYour password: {PASSWORD}\n</p>\n<p>\nYou can log in at <a href=\"{LOGIN_URL}\">{LOGIN_URL}</a>\n</p>"),
			array('shj_key' => 'moss_userid',            'shj_value' => ''),
			array('shj_key' => 'results_per_page_all',   'shj_value' => '40'),
			array('shj_key' => 'results_per_page_final', 'shj_value' => '80'),
			array('shj_key' => 'week_start',             'shj_value' => '1'),
			array('shj_key' => 'theme',             'shj_value' => 'default'),
		));
		if ( ! $result)
			show_error("Error adding data to table ".$this->db->dbprefix('settings'));
	}
}
