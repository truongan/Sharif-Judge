<?php
/**
 * Sharif Judge online judge
 * @file Assignment_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Problem_files_model extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
	}

   

	public function all_languages($id = NULL){
		$query =  $this->db->from('languages')
						->join('problem_language', 'languages.id = problem_language.language_id')
						->where('problem_language.problem_id' , $id)
						->get()->result();
		$a = array();
		foreach($query as $lang){
			$a[$lang->id] = $lang;
		}
		return $a;
	}

	public function get_directory_path($id = NULL){
		if ($id === NULL) return NULL;
		$assignments_root = rtrim($this->settings_model->get_setting('assignments_root'),'/');
		$problem_dir = $assignments_root . "/problems/".$id;
		return $problem_dir;
	}
	public function get_description($id = NULL){
		$problem_dir = $this->get_directory_path($id);
		$result =  array(
			'description' => '<p>Description not found</p>',
			'has_pdf' => glob("$problem_dir/*.pdf") != FALSE
			,'has_template' => glob("$problem_dir/template.cpp") != FALSE
		);
		
		$path = "$problem_dir/desc.html";

		if (file_exists($path))
			$result['description'] = file_get_contents($path);

		return $result;
	}

	public function delete_problem($id){
		$cmd = 'rm -rf '.$this->get_directory_path($id);
		//var_dump($cmd);die();
		$this->db->trans_start();

		// Phase 1: Delete this assignment and its submissions from database
		$this->db->delete('problems', array('id'=>$id));
		$this->db->delete('problem_language', array('problem_id'=>$id));
		$this->db->delete('problem_assignment', array('problem_id'=>$id));
		$this->db->delete('submissions', array('problem_id'=>$id));

		$this->db->trans_complete();

		if ($this->db->trans_status())
		{
			// Phase 2: Delete assignment's folder (all test cases and submitted codes)
			$cmd = 'rm -rf '.$this->get_directory_path($id);

			shell_exec($cmd);
		}
	}

	public function get_template_path($problem_id = NULL){
		$pattern1 = rtrim($this->problem_model->get_directory_path($problem_id)
		."/template.public.cpp");

		$template_file = glob($pattern1);
		if ( ! $template_file ){
			$pattern = rtrim($this->problem_model->get_directory_path($problem_id)
						."/template.cpp");

			$template_file = glob($pattern);
		}
		return $template_file;
	}
	// ------------------------------------------------------------------------
	/**
	 * Save Problem Description
	 *
	 * Saves (Adds/Updates) problem description (html)
	 * @param $problem_id
	 * @param $text
	 * @param $type
	 */
	public function save_problem_description($problem_id, $text, $type = 'html')
	{
		$problem_dir = $this->get_directory_path($problem_id);
		if (file_put_contents("$problem_dir/desc.html", $text) ) 
			return true;
		else return false;
	}

	// ------------------------------------------------------------------------
	public function replace_problem($problem_id = NULL){
		$this->db->trans_start();
		$id = $problem_id ? $problem_id : $this->new_problem_id();;
		
		//Now add new problems:
		$name = $this->input->post('problem_name');
		$admin_note = $this->input->post('admin_note');
		$dc = $this->input->post('diff_cmd');
		$da = $this->input->post('diff_arg');

		$problem = array(
			'id' => $id,
			'name' => $name,
			'admin_note' => $admin_note,
			'diff_cmd' => $dc,
			'diff_arg' => $da,
		);
		$this->db->replace('problems', $problem);
		
		$this->db->where('problem_id', $id)->delete('problem_language');

		$enable = $this->input->post('enable');
		$time_limit = $this->input->post('time_limit');
		$memory_limit = $this->input->post('memory_limit');
		foreach($this->input->post('language_id') as $i => $lang_id){
			if($enable[$i]){
				$this->db->insert('problem_language', array(
					'language_id' => $lang_id,
					'problem_id' => $id,
					'time_limit' => $time_limit[$i],
					'memory_limit' => $memory_limit[$i],
				));
			}
		}
		$this->db->trans_complete();
		return $id;
	}


//#region bring from old problem controller
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


//#endregion
}

?>