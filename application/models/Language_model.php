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
    public function language_info($id){
		return $this->db->get_where('languages', array('id'=>$id))->row();
	}
	public function all_languages(){
		$temp = $this->db->order_by('sorting', 'ASC')->get('languages')->result();
		$a = array();
		foreach ($temp as $lang){
			$a[$lang->id] = $lang;
		}
		return $a;
	}
	public function all_languages_array(){
		$temp = $this->db->order_by('sorting', 'ASC')->get('languages')->result_array();
		$a = array();
		foreach ($temp as $lang){
			$a[$lang['id']] = $lang;
		}
		return $a;
	}
	public function first_language(){
		
		return $this->db->order_by('sorting', 'ASC')->get('languages')->row();;
	
	}
}

?>