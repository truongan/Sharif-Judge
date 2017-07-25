<?php
/**
 * Sharif Judge online judge
 * @file User_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Dsbaitap_model extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
	}

	public function add_problems($data)
	{
		$this->db->insert('problems', $data);
		return true;
	}

	public function edit_problems($id, $data)
	{
		$this->db->where('id', $id)->update('problems', $data);
		// var_dump($this->db->last_query());die;
		return true;
	}

	public function delete_problem($problem_id)
	{
		$this->db->trans_start();
		$this->db->where('id', $problem_id)->delete('problems');
		$this->db->where('baitap_id', $problem_id)->delete('baitaploai');
		$this->db->delete('submissions', array('problem'=>$problem_id));
		$this->db->trans_complete();

		if ($this->db->trans_status())
		{
			// Phase 2: Delete assignment's folder (all test cases and submitted codes)
			$cmd = 'rm -rf '.rtrim($this->settings_model->get_setting('assignments_root'), '/').'/assignment_1/p'.$problem_id;
			shell_exec($cmd);
		}
		return true;
	}

	public function edit_tag($data)
	{
		$this->db->where('loaibt_id', $data['loaibt_id'])->update('dsloaibt', $data);

		return true;
	}

	public function delete_tag($tag_id)
	{
		$this->db->where('loaibt_id', $tag_id)->delete('dsloaibt');
		return true;
	}

	public function add_tag_in_problem($pro_id, $tags)
	{
		if($pro_id=='0')
		{
			$pro_id = $this->db->select_max('id')->get('problems')->row()->id;
		}
		else
		{
			$this->db->where('baitap_id', $pro_id)->delete('baitaploai');
		}
		foreach ($tags as $key) {
			$tagid = $this->db->select('loaibt_id')->get_where('dsloaibt', array('loaibt_name'=>$key))->row()->loaibt_id;
			$data=array(
				'baitap_id' => $pro_id,
				'loaibt_id' => $tagid
			);
			$this->db->insert('baitaploai', $data);
		}
		return true;
	}

	public function problem_info($problem_id)
	{
		return $this->db->get_where('problems', array('id'=>$problem_id))->row_array();
	}

}
