<?php
/**
 * Sharif Judge online judge
 * @file Dashboard.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller
{


	public function __construct()
	{
		parent::__construct();
		if ( ! $this->db->table_exists('sessions'))
			redirect('install');
		$this->user->login_redirect();
		$this->load->model('notifications_model')->helper('text');
	}


	// ------------------------------------------------------------------------


	public function index()
	{
		$data = array(
			'all_assignments'=>$this->assignment_model->all_assignments(),
			'week_start'=>$this->settings_model->get_setting('week_start'),
			'notifications' => $this->notifications_model->get_latest_notifications()
		);

		// detecting errors:
		$data['errors'] = array();
		if($this->user->level === 3){
			$path = $this->settings_model->get_setting('assignments_root');
			if ( ! file_exists($path))
				array_push($data['errors'], 'The path to folder "assignments" is not set correctly. Move this folder somewhere not publicly accessible, and set its full path in Settings.');
			elseif ( ! is_writable($path))
				array_push($data['errors'], 'The folder <code>"'.$path.'"</code> is not writable by PHP. Make it writable. But make sure that this folder is only accessible by you. Codes will be saved in this folder!');

			$path = $this->settings_model->get_setting('tester_path');
			if ( ! file_exists($path))
				array_push($data['errors'], 'The path to folder "tester" is not set correctly. Move this folder somewhere not publicly accessible, and set its full path in Settings.');
			elseif ( ! is_writable($path))
				array_push($data['errors'], 'The folder <code>"'.$path.'"</code> is not writable by PHP. Make it writable. But make sure that this folder is only accessible by you.');
		}

		$this->twig->display('pages/dashboard.twig', $data);
	}


}
