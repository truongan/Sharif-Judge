<?php
/**
 * Sharif Judge online judge
 * @file Assignment_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Language_model extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
	}

    public function get_language($id){
		return $this->db->get_where('languages', array('id'=>$id))->row();
	}
	public function get_all_languages(){
		return $this->db->get_where('languages')->result_array();;
	}
}

?>