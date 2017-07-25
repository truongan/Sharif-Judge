<?php
/**
 * Sharif Judge online judge
 * @file User_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Main extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		if ( ! $this->db->table_exists('sessions'))
			redirect('install');
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');
		$this->load->library('session');
		$user = $this->session->all_userdata();
		$this->load->model('user_model');
		$this->user_model->update_login_time($user['username']);	
	}
	public function index()
	{
		$data = array();
		$this->twig->display('pages/main.twig', $data);
	}
}
