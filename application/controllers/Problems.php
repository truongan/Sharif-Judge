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

		$first_language = $this->language_model->first_language();
		
		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'all_languages' => $this->language_model->all_languages(),
			'languages' =>  array($first_language['id'] => $first_language)
		);


		$this->twig->display('pages/admin/add_problem.twig', $data);

	}

	public function put(){
		var_dump($this->input->post());

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


	/**
	 * Compressing and downloading test data and descriptions of an assignment to the browser
	 */
	public function downloadtestsdesc($problem_id = FALSE)
	{

		if ( $this->user->level <= 1) // permission denied
			show_403();
		$this->load->library('zip');

		$root_path = $this->problem_model->get_directory_path($problem_id);

		$path = "$root_path/in";
		$this->zip->read_dir($path, FALSE, $root_path);

		$path = "$root_path/out";
		$this->zip->read_dir($path, FALSE, $root_path);

		$path = "$root_path/tester.cpp";
		if (file_exists($path))
			$this->zip->add_data("/tester.cpp", file_get_contents($path));

		$pdf_files = glob("$root_path/*.pdf");
		if ($pdf_files)
		{
			$path = $pdf_files[0];
			$this->zip->add_data(shj_basename($path), file_get_contents($path));
		}

		$template_files = glob("$root_path/template.*");
		if ($template_files){
			foreach ($template_files as $file )
			{
				$this->zip->add_data(shj_basename($file), file_get_contents($file));
			}
		}

		$path = "$root_path/desc.html";
		if (file_exists($path))
			$this->zip->add_data("desc.html", file_get_contents($path));

		$this->zip->download("assignment{$assignment_id}_tests_desc_".date('Y-m-d_H-i', shj_now()).'.zip');
	}

		/**
	 * Download pdf file of an assignment (or problem) to browser
	 */
	public function pdf($problem_id)
	{
		// Find pdf file
		if ($problem_id === NULL)
			show_404();
		else
			$pattern = $this->problem_model->get_directory_path()."/*.pdf";
			// rtrim($this->settings_model->get_setting('assignments_root'),'/')."/assignment_{$assignment_id}/p{$problem_id}/*.pdf";
		
		$pdf_files = glob($pattern);
		if ( ! $pdf_files )
			show_error("File not found");

		// Download the file to browser
		$this->load->helper('download')->helper('file');
		$filename = shj_basename($pdf_files[0]);
		force_download($filename, file_get_contents($pdf_files[0]), TRUE);
	}


}
