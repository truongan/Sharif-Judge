<?php
/**
 * Sharif Judge online judge
 * @file Notifications.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Problems extends CI_Controller
{

	private $notif_edit;


	// ------------------------------------------------------------------------


	public function __construct()
	{
		parent::__construct();
		if ( ! $this->user->logged_in()) // if not logged in
			redirect('login');
		$this->load->model('language_model');
        $this->notif_edit = FALSE;

        if ($this->user->level <= 1) {
            // permission denied
            show_403();
            die();
		}
		
		
	}


	// ------------------------------------------------------------------------


	public function index()
	{
		$data = array(
			'all_problems' => $this->problem_model->all_problems(),
		);

		$this->twig->display('pages/admin/list_problem.twig', $data);

	}

	public function show($id){

		$data=array(
			'problem' => $this->problem_model->problem_info($id),
			'can_submit' => TRUE,
			'assignment' => NULL,
		);

		$data['problem'] = array_merge($data['problem'], $this->problem_model->get_description($id));

		$data['error'] = 'none';

		$this->twig->display('pages/view_problems.twig', $data);
	}
	// ------------------------------------------------------------------------


	public function add()
	{
		if ( $this->user->level <=1) // permission denied
			show_404();
	
		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'languages' => $this->language_model->all_languages()
		);


		$this->twig->display('pages/admin/add_problem.twig', $data);

	}


	// ------------------------------------------------------------------------
	/**
	 * Edit problem description as html/markdown
	 *
	 * $type can be 'md', 'html', or 'plain'
	 *
	 * @param string $type
	 * @param int $assignment_id
	 * @param int $problem_id
	 */
	public function edit($problem_id)
	{
		if ($this->user->level <= 1)
			show_404();

		$ext = 'html';

		$this->load->library('form_validation');
		$this->form_validation->set_rules('text', 'text' ,'required'); /* todo: xss clean */
		if ($this->form_validation->run())
		{
			if ($this->problem_model->save_problem_description($problem_id, $this->input->post('content'))){
				echo "success";
				return ;
			}
			else show_error("Error saving", 501);
		}
	}
	
	// ------------------------------------------------------------------------
	public function delete()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
		if ($this->user->level <= 1) // permission denied
			$json_result = array('done' => 0, 'message' => 'Permission Denied');
		elseif ($this->input->post('id') === NULL)
			$json_result = array('done' => 0, 'message' => 'Input Error');
		else
		{
			$this->notifications_model->delete_notification($this->input->post('id'));
			$json_result = array('done' => 1);
		}

		$this->output->set_header('Content-Type: application/json; charset=utf-8');
		echo json_encode($json_result);
	}


	// ------------------------------------------------------------------------
	public function check()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
		$time  = $this->input->post('time');
		if ($time === NULL)
			exit('error');
		if ($this->notifications_model->have_new_notification(strtotime($time)))
			exit('new_notification');
		exit('no_new_notification');
	}

	/**
	* Download problem's template
	**/
	public function template($problem_id = 1,$assignment_id = NULL){
		// Find pdf file
		if ($assignment_id == NULL && $this->user->level < 2)
			show_error("Only admin can view template without assignment", 403);

		$pdf_files = $this->problem_model->get_template_path($problem_id);
		if(!$pdf_files)
			show_error("File not found");

		// Download the file to browser
		$this->load->helper('download')->helper('file');
		$filename = shj_basename($pdf_files[0]);
		force_download($filename, file_get_contents($pdf_files[0]), TRUE);
	}

}
