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
	/**
	 * Delete Problems
	 *
	 * Deletes a user with given user id
	 * Returns TRUE (success) or FALSE (failure)
	 *
	 * @param $user_id
	 * @return bool
	 */
	public function delete_problem($problem_id)
	{
		$this->db->trans_start();
		$this->db->delete('problems', array('id'=>$problem_id));
		$this->db->trans_complete();
		return true;
	}

}
