<?php
/**
 * Sharif Judge online judge
 * @file Notifications_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Competition_model extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
	}


	/**
	 * Add a new notification
	 */
	public function add_com($data)
	{
		$this->db->insert('competition',$data);
	}

	public function delete_com($id)
	{
		$this->db->delete('competition', array('id' => $id));
	}


	// ------------------------------------------------------------------------


	/**
	 * Returns a notification
	 */
	public function get_com($id)
	{
		$query = $this->db->get_where('competition', array('id' => $id));
		if ($query->num_rows() != 1)
			return FALSE;
		return $query->row_array();
	}


	// ------------------------------------------------------------------------


	/**
	 * Returns true if there is a notification after $time
	 * @todo optimize: check the ">" condition in sql query
	 */
	public function have_new_competition($time)
	{
		$coms = $this->db->select('*')->get('competition')->result_array();
		foreach ($coms as $com) {
			if (strtotime($com['time']) > $time)
				return $com;
		}
		return FALSE;
	}

}