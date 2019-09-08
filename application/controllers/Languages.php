<?php
/**
 * Sharif Judge online judge
 * @file Users.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Languages extends CI_Controller
{


	public function __construct()
	{
		parent::__construct();
		$this->user->login_redirect();
		if ( $this->user->level <= 2) // permission denied
			show_404();
		$this->load->model('language_model');
	}

	// ------------------------------------------------------------------------
	public function index()
	{

		$data = array(
			'all_languages' => $this->language_model->all_languages_array(),
		);
		$this->twig->display('pages/admin/languages.twig', $data);
	}

	// ------------------------------------------------------------------------

	public function edit($language_id)
	{
		$this->form_validation->set_rules('name', 'name', 'required|max_length[45]');
		$this->form_validation->set_rules('default_time_limit', 'default_time_limit', 'required|greater_than[100]');
		$this->form_validation->set_rules('default_memory_limit', 'default_memory_limit', 'required|greater_than[100]');
		$this->form_validation->set_rules('sorting', 'sorting', 'required|greater_than[0]');

		if ($this->form_validation->run() == FALSE){
			$lang = $this->language_model->get_language_array($language_id);
			// var_dump($lang);
			// var_dump($language_id); die();
			if ($lang == null) show_404();
			unset($lang['id']);unset($lang['extension']);
			$data = array(
				'id' => $language_id,
				'lang' => $lang,
				'error' => validation_errors(),
			);

			$this->twig->display('pages/admin/edit_language.twig', $data);
		} else {
			$this->language_model->edit_language($language_id, $this->input->post());
			redirect(base_url('languages'));
		}
	}
	// ------------------------------------------------------------------------

	/**
	 * Controller for deleting a user
	 * Called by ajax request
	 */
	public function delete()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
		$user_id = $this->input->post('user_id');
		if ( ! is_numeric($user_id) )
			$json_result = array('done' => 0, 'message' => 'Input Error');
		elseif ($this->user_model->delete_user($user_id))
			$json_result = array('done' => 1);
		else
			$json_result = array('done' => 0, 'message' => 'Deleting User Failed');

		$this->output->set_header('Content-Type: application/json; charset=utf-8');
		echo json_encode($json_result);
	}
	// ------------------------------------------------------------------------
}
