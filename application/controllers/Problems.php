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
			'all_problems' => $this->problem_model->all_problems_detailed(),
			'messages' => $this->messages,
		);

		// var_dump($data);die();
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
			'max_file_uploads' => ini_get('max_file_uploads'),
		);
		foreach($data['languages'] as $lang){
			$lang->time_limit = $data['all_languages'][$lang->id]->default_time_limit;
			$lang->memory_limit = $data['all_languages'][$lang->id]->default_memory_limit;
		}
		// var_dump($data['languages']); die();
		$this->twig->display('pages/admin/add_problem.twig', $data);
	}
	public function show_edit_form($problem_id)
	{
		$problem = $this->problem_model->problem_info($problem_id);
		if (!$problem) show_404();
		$root_path = $this->problem_model->get_directory_path($problem_id);
		// var_dump(("tree " . $root_path));die();
		$tree_dump = shell_exec("tree -h " . $root_path);
		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
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
			if ($this->problem_model->save_problem_description($problem_id, $this->input->post('content'))){
				echo "success";
				return ;
			}
			else show_error("Error saving", 501);
		}
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
			//var_dump($_FILES); die();
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
			
			$this->index();
		}
	}


	private function _take_test_file_upload($assignments_root, $problem_dir){
		$this->load->library('upload');
		$up_dir = $_FILES['tests_dir'];
		$up_zip = $_FILES['tests_zip'];

		//var_dump($_FILES); die();
		if ( $up_dir['error'][0] === UPLOAD_ERR_NO_FILE 
			&& $up_zip['error'] === UPLOAD_ERR_NO_FILE 
		){
			$this->messages[] = array(
				'type' => 'notice',
				'text' => "Notice: You did not upload test case and description. If needed, upload by editing assignment."
			);
			return;
		}

		if ($up_dir['error'][0] === UPLOAD_ERR_NO_FILE ) {
			// Upload Tests (zip file)
			shell_exec('rm -f '.$assignments_root.'/*.zip');
			$config = array(
				'upload_path' => $assignments_root,
				'allowed_types' => 'zip',
			);
			$this->upload->initialize($config);
			$zip_uploaded = $this->upload->do_upload('tests_zip');
			$u_data = $this->upload->data();
			
			if ( ! $zip_uploaded )
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

		} else {
			return $this->handle_test_dir_upload($up_dir, $problem_dir);
		}
	}
	private function handle_test_dir_upload($up_dir, $problem_dir){
		$in = array();
		$out = array();

		$files = array();
		foreach($up_dir['name'] as $i => $name){
			if ($up_dir['error'][$i] !== UPLOAD_ERR_OK){
				$this->messages[] = array(
					'type' => 'error',
					'text' => "Error {$up_dir['error'][$i]} when uploading file $name",
				);
			}
			if (substr($name, 0, 5) == 'input') {
				$in[$name] = $up_dir['tmp_name'][$i];
			} else if (substr($name, 0, 6) == 'output'){
				$out[$name] = $up_dir['tmp_name'][$i];
			} else {
				$files[$name] = $up_dir['tmp_name'][$i];
			}
		}
		if (!isset($files['desc.html'])){
			$this->messages[] = array('type' => 'error', 'text' => "Your test folder doesn't have desc.html file for problem description");
		}
		for($i = 1; $i < count($in); $i++){
			if (!isset($in["input$i.txt"])){
				$this->messages[] = array('type' => 'error', 'text' => "A file name input$i.txt seem to be missing in your folder");
			} else {
				if (!isset($out["output$i.txt"])){
					$this->messages[] = array('type' => 'error', 'text' => "A file name output$i.txt seem to be missing in your folder");
				}
			}
		}

		$this->clean_up_old_problem_dir($problem_dir);
		//var_dump($in); var_dump($out); var_dump($files); var_dump($this->messages);die();
		foreach($in as $name => $tmp_name ){
			move_uploaded_file($tmp_name, "$problem_dir/in/$name");
		}
		foreach($out as $name => $tmp_name ){
			move_uploaded_file($tmp_name, "$problem_dir/out/$name");
		}
		foreach($files as $name => $tmp_name ){
			move_uploaded_file($tmp_name, "$problem_dir/$name");
		}
	}
	private function clean_up_old_problem_dir($problem_dir){
		$remove = 
		" rm -rf $problem_dir/in $problem_dir/out $problem_dir/tester*"
			."  $problem_dir/template.* "
			."  $problem_dir/desc.*  $problem_dir/*.pdf; done";
		//echo "cp -R $tmp_dir/* $problem_dir;";			
		//echo $remove; die();			
		shell_exec($remove); 

		mkdir("$problem_dir/in", 0700, TRUE);
		mkdir("$problem_dir/out", 0700, TRUE);
			
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
			$this->clean_up_old_problem_dir($problem_dir);

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
