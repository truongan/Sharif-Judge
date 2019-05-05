<?php
/**
 * Sharif Judge online judge
 * @file Settings_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * This model deals with global settings
 */

class Settings_model extends CI_Model
{


	public function __construct()
	{
		parent::__construct();
	}


	// ------------------------------------------------------------------------


	public function get_setting($key, $default = NULL)
	{
		$a = $this->db->select('shj_value')->get_where('settings', array('shj_key'=>$key));
		if ($a->num_rows() == 0 && $default != NULL)
			return $default;

		return $a->row()->shj_value;
	}


	// ------------------------------------------------------------------------


	public function set_setting($key, $value)
	{
		// if ($key == 'theme'){
		// 	//Check if theme settings is already in database
		// 	//This is temporary for upgrade purpose, shall be remove in the future
		// 	if ($this->db->get_where('settings', array('shj_key' => $key))->num_rows() == 0){
		// 		$this->db->insert('settings', array('shj_key' => $key, 'shj_value' => 'value'));
		// 	}
		// }

		$this->db->where('shj_key', $key)->update('settings', array('shj_value'=>$value));
		$this->user->update_theme();
	}


	// ------------------------------------------------------------------------


	public function get_all_settings()
	{
		$result = $this->db->get('settings')->result_array();
		$settings = array();
		foreach($result as $item)
		{
			$settings[$item['shj_key']] = $item['shj_value'];
		}
		return $settings;
	}


	// ------------------------------------------------------------------------


	public function set_settings($settings)
	{
		foreach ($settings as $key => $value)
		{
			$this->set_setting($key, $value);
		}
	}



}
