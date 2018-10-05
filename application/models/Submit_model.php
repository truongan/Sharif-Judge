<?php
/**
 * Sharif Judge online judge
 * @file Submit_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Submit_model extends CI_Model {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('language_model');
	}


	// ------------------------------------------------------------------------


	/**
	 * Returns table row for a specific submission
	 */
	public function get_submission($assignment, $submit_id)
	{
		$query = $this->db->get_where('submissions',
			array(
				'assignment_id'=>$assignment,
				'submit_id'=>$submit_id
			)
		);
		if($query->num_rows()!=1)
			return FALSE;
		return $query->row_array();
	}



	// ------------------------------------------------------------------------


	public function get_final_submissions($assignment_id, $user_level, $username, $page_number = NULL, $filter_user = NULL, $filter_problem = NULL)
	{
		$arr['assignment_id'] = $assignment_id;
		$arr['is_final'] = 1;
		if ($user_level === 0)// students can only get final submissions of themselves
			$arr['username']=$username;
		elseif ($filter_user !== NULL)
			$arr['username'] = $filter_user;
		if ($filter_problem !== NULL)
			$arr['problem_id'] = $filter_problem;
		if ($page_number === NULL)
			return $this->db->order_by('username asc, problem_id asc')->get_where('submissions', $arr)->result_array();
		else
		{
			$per_page = $this->settings_model->get_setting('results_per_page_final');
			if ($per_page == 0)
				return $this->db->order_by('username asc, problem_id asc')->get_where('submissions', $arr)->result_array();
			else
				return $this->db->order_by('username asc, problem_id asc')->limit($per_page,($page_number-1)*$per_page)->get_where('submissions', $arr)->result_array();
		}

	}

	// ------------------------------------------------------------------------


	public function get_all_submissions($assignment_id, $user_level, $username, $page_number = NULL, $filter_user = NULL, $filter_problem = NULL)
	{
		$kq = NULL;
		$arr['assignment_id']=$assignment_id;
		if ($user_level === 0)
			$arr['username']=$username;
		elseif ($filter_user !== NULL)
			$arr['username'] = $filter_user;
		if ($filter_problem !== NULL)
			$arr['problem_id'] = $filter_problem;
		
		if ($page_number === NULL)
			$kq =  $this->db->order_by('submit_id','desc')->get_where('submissions', $arr)->result_array();
		else
		{
			$per_page = $this->settings_model->get_setting('results_per_page_all');
			if ($per_page == 0)
				$kq =  $this->db->order_by('submit_id','desc')->get_where('submissions', $arr)->result_array();
			else
				$kq =  $this->db->order_by('submit_id','desc')->limit($per_page,($page_number-1)*$per_page)->get_where('submissions', $arr)->result_array();
		}

//		$kq['languages'] = $this->language_model->get_language($item['language_id']);
		return $kq;
	}


	// ------------------------------------------------------------------------


	public function count_final_submissions($assignment_id, $user_level, $username, $filter_user = NULL, $filter_problem = NULL)
	{
		$arr['assignment_id'] = $assignment_id;
		$arr['is_final'] = 1;
		if ($user_level === 0)
			$arr['username']=$username;
		elseif ($filter_user !== NULL)
			$arr['username'] = $filter_user;
		if ($filter_problem !== NULL)
			$arr['problem_id'] = $filter_problem;
		return $this->db->where($arr)->count_all_results('submissions');
	}


	// ------------------------------------------------------------------------


	public function count_all_submissions($assignment_id, $user_level, $username, $filter_user = NULL, $filter_problem = NULL)
	{
		$arr['assignment_id']=$assignment_id;
		if ($user_level === 0)
			$arr['username']=$username;
		elseif ($filter_user !== NULL)
			$arr['username'] = $filter_user;
		if ($filter_problem !== NULL)
			$arr['problem_id'] = $filter_problem;
		return $this->db->where($arr)->count_all_results('submissions');
	}


	// ------------------------------------------------------------------------


	public function set_final_submission($username, $assignment, $problem_id, $submit_id)
	{
		if ($username != $this->user->username && $this->user->level < 1){
			show_error("Only instructor can change final submission of other users", 403);
		}
		$this->db->where(array(
			'is_final' => 1,
			'username' => $username,
			'assignment_id' => $assignment,
			'problem_id' => $problem_id,
		))->update('submissions', array('is_final'=>0));

		$this->db->where(array(
			'username' => $username,
			'assignment_id' => $assignment,
			'problem_id' => $problem_id,
			'submit_id' => $submit_id,
		))->update('submissions', array('is_final'=>1));

		return TRUE;
	}

	public function get_final_submission($username, $assignment, $problem_id)
	{
		return $this->db->where(array(
			'is_final' => 1,
			'username' => $username,
			'assignment_id' => $assignment,
			'problem_id' => $problem_id,
		))->get('submissions')->row();


	}

	public function get_path($username, $assignment, $problem){
		$assignment_root = rtrim($this->settings_model->get_setting('assignments_root'),'/');

		if ($assignment == 0){
			return $assignment_root . "/problems/$problem/admin_test_submit/$username";
		} else {
			return $assignment_root . "/assignment_$assignment/problem_$problem/$username";
		}
	}

	public function find_last_submission($username){
		return $this->db->order_by('submit_id','desc')
			->get_where('submissions', array('username' =>  $username))
			->row();
	}

}