<?php
/**
 * Sharif Judge online judge
 * @file Notifications.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Problems extends CI_Controller
{



	// ------------------------------------------------------------------------

	private $messages;

	public function __construct()
	{
		parent::__construct();

		$this->messages = array();

		$this->user->login_redirect();
		$this->load->model('language_model');
		$this->load->model('problem_files_model');
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
			'all_problems' => $this->problem_model->all_problems_detailed(),
			'messages' => $this->messages,
		);

		// var_dump($data['all_problems']);die();
		$this->twig->display('pages/admin/list_problem.twig', $data);

	}

	public function show($id){

		$data=array(
			'problem' => $this->problem_model->problem_info($id),
			'can_submit' => TRUE,
			'assignment' => NULL,
		);

		$data['problem'] = array_merge($data['problem'], $this->problem_files_model->get_description($id));

		$data['error'] = 'none';

		$this->twig->display('pages/view_problems.twig', $data);
	}
	// ------------------------------------------------------------------------


	public function show_add_form()
	{
		$default_language = $this->language_model->default_language($this->settings_model->get_setting('default_language_number',1));

		$data = array(
			'all_languages' => $this->language_model->all_languages(),
			'languages' =>  $default_language,
			'max_file_uploads' => ini_get('max_file_uploads'),
		);
		foreach($data['languages'] as $lang){
			$lang->time_limit = $data['all_languages'][$lang->id]->default_time_limit;
			$lang->memory_limit = $data['all_languages'][$lang->id]->default_memory_limit;
		}

		$this->twig->display('pages/admin/add_problem.twig', $data);
	}
	public function show_edit_form($problem_id)
	{
		$problem = $this->problem_model->problem_info($problem_id);
		if (!$problem) show_404();
		$root_path = $this->problem_files_model->get_directory_path($problem_id);
		
		$tree_dump = shell_exec("tree -h " . $root_path);
		$data = array(
			'edit_problem' => $problem,
			'all_languages' => $this->language_model->all_languages(),
			'languages' =>  $problem['languages'],
			'max_file_uploads' => ini_get('max_file_uploads'),
			'tree_dump' => $tree_dump,
		);
		$this->twig->display('pages/admin/add_problem.twig', $data);
	}

	public function destroy($id = FALSE)
	{
		if ($this->user->level <= 1) // permission denied
			show_404();

		$problem = $this->problem_model->problem_info_detailed($id);

		if ($problem == NULL)
			show_404();
		
		if ($problem['no_of_ass'] != 0 & $problem['no_of_sub'] != 0){
			show_error("Problem already appear in assignments and got some submission should not be delete", 403);
		}

		if ($this->input->post('delete') === 'delete')
		{
			$this->problem_model->delete_problem($id);
			redirect('problems');
		}

		$data = array(
			'problem' => $problem
		);

		$this->twig->display('pages/admin/delete_problem.twig', $data);

	}

	public function edit_description($problem_id){
		if ( ! $this->input->is_ajax_request() )
			show_404();
		if ( $this->user->level <=1) // permission denied
			show_404();
		
		$this->load->library('form_validation');
		$this->form_validation->set_rules('content', 'text' ,'required'); /* todo: xss clean */
		if ($this->form_validation->run())
		{
			if ($this->problem_files_model->save_problem_description($problem_id, $this->input->post('content'))){
				echo "success";
				return ;
			}
			else show_error("Error saving", 501);
		} else {
			show_error(validation_errors(), 501);
		}
	}
	
	public function add(){
		return $this->edit(NULL);
	}
	public function edit($problem_id){
		if ( $this->user->level <=1) // permission denied
			show_404();

		$this->form_validation->set_rules('problem_name', 'Problem name', 'required|max_length[150]' );
		$this->form_validation->set_rules('diff_cmd', 'Diff command', 'required|max_length[200]' );
		$this->form_validation->set_rules('admin_note', 'Problem name', 'max_length[1500]' );
		
		if($this->form_validation->run() == FALSE){
			if ($problem_id != NULL)
				$this->show_edit_form($problem_id);
			else 
				$this->show_add_form();
		} else {
			//var_dump($_FILES); die();
			if ($problem_id!= NULL) {
				$problem = $this->problem_model->problem_info($problem_id);
				if($problem == NULL) show_404();
			}
			$the_id = $this->problem_model->replace_problem($problem_id ? $problem_id : NULL);
			
			$this->problem_files_model->_take_test_file_upload($the_id, $this->messages);
			
			$this->index();
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

		$pdf_files = $this->problem_files_model->get_template_path($problem_id);
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

		$root_path = $this->problem_files_model->get_directory_path($problem_id);

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

		$this->zip->download("problem{$problem_id}_tests_and_desccription_".date('Y-m-d_H-i', shj_now()).'.zip');
	}

	public function download_all(){
		if ( $this->user->level <= 1) // permission denied
			show_403();
		$this->load->library('zip');
		
		$root_path = $assignments_root = rtrim($this->settings_model->get_setting('assignments_root'),'/') . '/problems/';
		// var_dump($root_path);die();
		$this->zip->read_dir($root_path, FALSE, $root_path);

	
		$this->zip->download($this->user->site_name . "_tests_and_desccription_".date('Y-m-d_H-i', shj_now()).'.zip');	}
	/**
	 * Download pdf file of an assignment (or problem) to browser
	 */
	public function pdf($problem_id)
	{
		$this->problem_files_model->download_pdf($problem_id);
	}


}
