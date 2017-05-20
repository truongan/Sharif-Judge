<?php

class Dsbaitap extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('xephang_model')->model('dsbaitap_model');
	}

	public function index()
	{
		$this->db->select('*');
        $this->db->from('baitaploai'); 
        $this->db->join('problems', 'problems.id=baitaploai.baitap_id', 'left');
        $this->db->join('dsloaibt', 'dsloaibt.loaibt_id=baitaploai.loaibt_id', 'left');
        $this->db->order_by('problems.id');         
        $query = $this->db->get(); 
		$data = array(
			'baitaps' => $query->result()
		);
		$this->twig->display('pages/dsbaitap.twig', $data);
 
	}
	public function add()
	{
		$data = array();
		$this->twig->display('pages/admin/add_problems.twig', $data);
		
	}
	public function edit($id)
	{
		$this->db->select('*');
		$this->db->from('problems');
		$this->db->where('id', $id);
		$query = $this->db->get();
		
		$data=array(
			'baitaps' => $query->result()
		);
		$this->twig->display('pages/admin/edit_problems.twig', $data);
	}

	/**
	 * Controller for deleting a problem
	 * Called by ajax request
	 */
	public function delete()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
		$problem_id = $this->input->post('problem_id');
		if ( ! is_numeric($problem_id) )
			$json_result = array('done' => 0, 'message' => 'Input Error');
		elseif ($this->dsbaitap_model->delete_problem($problem_id))
			$json_result = array('done' => 1);
		else
			$json_result = array('done' => 0, 'message' => 'Deleting Problem Failed');

		$this->output->set_header('Content-Type: application/json; charset=utf-8');
		echo json_encode($json_result);
	}

}