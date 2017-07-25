<?php
/**
 * Sharif Judge online judge
 * @file Submit_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Xephang_model extends CI_Model {

	public function __construct()
	{
		$this->load->model('user_model')->model('assignment_model')->model('submit_model');
		parent::__construct();
	}

	//tinh diem tat ca problem
	public function save_db_xephang_for_all_problem($id, $submission)
	{
		$prob = array();
		$sum = 0;
		$dtd = 0;
		$username = $this->user_model->user_id_to_username($id);
		//add score in array $prob
		foreach ($submission as $sub) {
			if($sub['username']==$username)
			{
				$score = $sub['pre_score'];
				if(array_key_exists($id."|".$sub['assignment']."|".$sub['problem'], $prob) == true)
				{
					if($prob[$id."|".$sub['assignment']."|".$sub['problem']] < $score)
						$prob[$id."|".$sub['assignment']."|".$sub['problem']] = $score;
				}
				else $prob[$id."|".$sub['assignment']."|".$sub['problem']] = $score;
			}
		}

		//tinh tong 
		foreach ($prob as $value) {
			$sum+=$value;
		}
		$competition = $this->db->select('*')->get('competition')->result_array();
		foreach ($competition as $com) {
			if($com['id_hero_a'] == $id)
			{
				$dtd+= $com['xp_hero_a'];
			}
			if($com['id_hero_b'] == $id)
			{
				$dtd+= $com['xp_hero_b'];
			}
		}
		//insert database;
		$arr = array(
			'user_id' => $id,
			'diembaitap' => $sum,
			'diemthidau' => $dtd,
			'tongdiem'   => $sum + $dtd
		);
		if(!$this->db->insert('xephang', $arr))
		{
			$this->db->where('user_id', $id);
			$this->db->update('xephang', $arr);
		}
		else
			$this->db->insert('xephang', $arr);
	}

	//tinh diem 1 problem
	public function save_db_xephang_for_one_problem($submission)
	{
		$this->db->select_max('submit_id');
		$this->db->from('submissions');
		$this->db->where('assignment', $submission['assignment']);
		$this->db->where('problem', $submission['problem']);
		$this->db->where('username', $submission['username']);
		$this->db->where('submit_id <', $submission['submit_id']); 
		$query = $this->db->get()->result_array();
		$id = $this->user_model->username_to_user_id($submission['username']);
		$score = $this->db->select('*')->get_where('xephang',array('user_id' => $id))->result_array();
		$problem = $this->assignment_model->problem_info($submission['assignment'], $submission['problem']);

		$sum = 0;
		$newscore = $submission['pre_score'];
		if($query['0']['submit_id'] != NULL)
        {
            $value = $this->db->get_where('submissions', array('submit_id' => $query['0']['submit_id']))->result_array();
            $old_score = $value['0']['pre_score'];
            $sum = $score['0']['diembaitap'] + $newscore - $old_score;
        }
        else $sum = $score['0']['diembaitap'] + $newscore;
		$arr = array(
				'user_id'    => $id,
				'diembaitap' => $sum,
				'diemthidau' => '0',
				'tongdiem'   => $sum + 0
			);
		$this->db->where('user_id', $id);
		$this->db->update('xephang', $arr);
	}

	public function get_all_submission()
	{
		return $this->db->order_by('submit_id')->get('submissions')->result_array();
	}

	public function get_all_diembaitap()
	{
		return $this->db->order_by('user_id')->get('xephang')->result_array();
	}


}