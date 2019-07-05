<?php
/**
 * Sharif Judge online judge
 * @file User_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Model
{

	public $username;
	public $selected_assignment;
	public $level;
	public $email;

	/* We put site's name in here because this data is given in every pages
	*  to display username.
	*/
	public $site_name;
	public $theme;

	private function _update_selected_assignment($assignment_id){
		$query = $this->db->get_where('assignments', array('id' => $assignment_id));
		if ($query->num_rows() != 1)
			$this->selected_assignment = array(
				'id' => 0,
				'name' => 'Not Selected',
				'finish_time' => 0,
				'extra_time' => 0,
				'problems' => 0
			);
		else
			$this->selected_assignment = $query->row_array();
	}
	public function __construct()
	{
		parent::__construct();
		$this->username = $this->session->userdata('username');
		if ($this->username === NULL)
			return;

		$a = $this->db->get_where('users', array('username'=>$this->username));

		if ($a == FALSE || $a->num_rows() == 0 ){
			$this->username = NULL;
			return;
		}

		$user = $this->db
			->select('selected_assignment, role, email')
			->get_where('users', array('username' => $this->username))
			->row();

		$this->email = $user->email;

		$this->_update_selected_assignment($user->selected_assignment);

		switch ($user->role)
		{
			case 'admin': $this->level = 3; break;
			case 'head_instructor': $this->level = 2; break;
			case 'instructor': $this->level = 1; break;
			case 'student': $this->level = 0; break;
		}
		$this->load->model('settings_model');
		$this->site_name = $this->settings_model->get_setting('site_name');
		$this->theme = $this->settings_model->get_setting('theme', 'default');
	}

	// ----------------
	public function logged_in(){
		if (! $this->username != NULL) // if logged in
			return false;
		else {
			return $this->user_model->have_user($this->username);
		}
		return false;
	}

	public function login_redirect(){
		if (! $this->logged_in()){
			$redirect_to = uri_string();
			redirect('login?redirect=' . urldecode($redirect_to));
		}
	}

	// ------------------------------------------------------------------------

	public function update_theme(){
		$this->theme = $this->settings_model->get_setting('theme', 'default');
	}

	/**
	 * Select Assignment
	 *
	 * Sets selected assignment for $username
	 *
	 * @param $assignment_id
	 */
	public function select_assignment($assignment_id)
	{
		$this->db->where('username', $this->username)->update('users', array('selected_assignment'=>$assignment_id));
		$this->_update_selected_assignment($assignment_id);
	}


	// ------------------------------------------------------------------------


}
