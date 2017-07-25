<?php

class Xephang extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$user = $this->session->all_userdata();
		$this->load->model('user_model');
		$this->user_model->update_login_time($user['username']);	
		$this->load->model('xephang_model');
	}

	public function index()
	{	
		// // bo test submission
		// $submission['assignment']='3';
		// $submission['problem']='3';
		// $submission['username']='thiennh';
		// $submission['pre_score'] = '500';
		// $this->xephang_model->save_db_xephang_for_one_problem($submission);


		// insert database xep hang

		$submission = $this->db->select('*')->get_where('submissions', array('check_com' => '0'))->result_array();
		$users = $this->user_model->get_all_users();
		foreach ($users as $value) {
			$this->xephang_model->save_db_xephang_for_all_problem($value['id'], $submission);
		}

		$this->db->select('*');
		$this->db->from('users');
		$this->db->join('xephang', 'users.id = xephang.user_id', 'left');
		$this->db->order_by('tongdiem',"desc");

		$query = $this->db->get();
		$data = array(
			'users' => $query->result()
		);
		// var_dump($data);die;

		$this->twig->display('pages/xephang.twig', $data);
	}

}