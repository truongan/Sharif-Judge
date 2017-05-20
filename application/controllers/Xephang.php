<?php

class Xephang extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
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

		$submission = $this->xephang_model->get_all_submission();
		$users = $this->user_model->get_all_users();
		foreach ($users as $value) {
			$this->xephang_model->save_db_xephang_for_all_problem($value['id'], $submission);
		}

		$this->db->select('*');
		$this->db->from('users');
		$this->db->join('xephang', 'users.id = xephang.user_id', 'left');
		$this->db->order_by('users.id');

		$query = $this->db->get();
		$data = array(
			'users' => $query->result()
		);
		$this->twig->display('pages/xephang.twig', $data);
	}

}