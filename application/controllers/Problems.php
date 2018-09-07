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


	public function show_add_form()
	{
		$first_language = $this->language_model->first_language();
		
		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'all_languages' => $this->language_model->all_languages(),
			'languages' =>  array($first_language->id => $first_language),
		);
		
		$this->twig->display('pages/admin/add_problem.twig', $data);
	}
	public function show_edit_form($problem_id)
	{
		$problem = $this->problem_model->problem_info($problem_id);
		if (!$problem) show_404();
		
		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'edit_problem' => $problem,
			'all_languages' => $this->language_model->all_languages(),
			'languages' =>  $problem['languages'],
		);
		$this->twig->display('pages/admin/add_problem.twig', $data);
	}
	
	public function add(){
		return $this->edit(NULL);
	}
	public function edit($problem_id){
		if ( $this->user->level <=1) // permission denied
			show_404();

		$this->form_validation->set_rules('problem_name', 'Problem name', 'required|max_length[150]' );
		$this->form_validation->set_rules('diff_cmd', 'Problem name', 'required|max_length[20]' );
		$this->form_validation->set_rules('diff_arg', 'Problem name', 'required|max_length[20]' );
		$this->form_validation->set_rules('admin_note', 'Problem name', 'max_length[1500]' );
		
		if($this->form_validation->run() == FALSE){
			if ($problem_id != NULL)
				$this->show_edit_form($problem_id);
			else 
				$this->show_add_form();
		} else {
			if ($problem_id!= NULL) {
				$problem = $this->problem_model->problem_info($problem_id);
				if($problem == NULL) show_404();
			}
			$the_id = $this->problem_model->replace_problem($problem_id ? $problem_id : NULL);

			$assignments_root = rtrim($this->settings_model->get_setting('assignments_root'),'/');
			$problem_dir = $this->problem_model->get_directory_path($the_id);
			
			// Create assignment directory
			if ( ! file_exists($problem_dir) )
				mkdir($problem_dir, 0700, TRUE);
			
			$this->_take_test_file_upload($assignments_root, $problem_dir);
			
			redirect('problems');
		}
	}


	private function _take_test_file_upload($assignments_root, $problem_dir){
		$this->load->library('upload');
		
		// Upload Tests (zip file)
		shell_exec('rm -f '.$assignments_root.'/*.zip');
		$config = array(
			'upload_path' => $assignments_root,
			'allowed_types' => 'zip',
		);
		$this->upload->initialize($config);
		$zip_uploaded = $this->upload->do_upload('tests_desc');
		$u_data = $this->upload->data();
		if ( $_FILES['tests_desc']['error'] === UPLOAD_ERR_NO_FILE )
			$this->messages[] = array(
				'type' => 'notice',
				'text' => "Notice: You did not upload any zip file for tests. If needed, upload by editing assignment."
			);
		elseif ( ! $zip_uploaded )
			$this->messages[] = array(
				'type' => 'error',
				'text' => "Error: Error uploading tests zip file: ".$this->upload->display_errors('', '')
			);
		else
			$this->messages[] = array(
				'type' => 'success',
				'text' => "Tests (zip file) uploaded successfully."
			);

		if ($zip_uploaded) $this->unload_zip_test_file($assignments_root, $problem_dir, $u_data);
	}

	private function unload_zip_test_file($assignments_root, $problem_dir, $u_data){
		// Create a temp directory
		$tmp_dir_name = "shj_tmp_directory";
		$tmp_dir = "$assignments_root/$tmp_dir_name";
		shell_exec("rm -rf $tmp_dir; mkdir $tmp_dir;");

		// Extract new test cases and descriptions in temp directory
		$this->load->library('unzip');
		$this->unzip->allow(array('txt', 'cpp', 'html', 'md', 'pdf'));
		$extract_result = $this->unzip->extract($u_data['full_path'], $tmp_dir);

		// Remove the zip file
		unlink($u_data['full_path']);

		if ( $extract_result )
		{
			// Remove previous test cases and descriptions
			$remove = 
			" rm -rf $problem_dir/in $problem_dir/out $problem_dir/tester*"
				."  $problem_dir/template.* "
				."  $problem_dir/desc.*  $problem_dir/*.pdf; done";
			//echo "cp -R $tmp_dir/* $problem_dir;";			
			//echo $remove; die();			
			shell_exec($remove); 

			if (glob("$tmp_dir/*.pdf"))
				shell_exec("cd $problem_dir; rm -f *.pdf");
			// Copy new test cases from temp dir
			// echo $tmp_dir . "<br/>";
			// echo $problem_dir . "<br/>";
			// echo shell_exec("ls $tmp_dir/*");
			// echo "cp -R $tmp_dir/* $problem_dir;";
			//die();
			shell_exec("cp -R $tmp_dir/* $problem_dir;");
			$this->messages[] = array(
				'type' => 'success',
				'text' => 'Tests (zip file) extracted successfully.'
			);
		}
		else
		{
			$this->messages[] = array(
				'type' => 'error',
				'text' => 'Error: Error extracting zip archive.'
			);
			foreach($this->unzip->errors_array() as $msg)
				$this->messages[] = array(
					'type' => 'error',
					'text' => " Zip Extraction Error: ".$msg
				);
		}

		// Remove temp directory
		shell_exec("rm -rf $tmp_dir");
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

		$this->zip->download("problem{$problem_id}_tests_and_desccription_".date('Y-m-d_H-i', shj_now()).'.zip');
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
