<?php
/**
 * Sharif Judge online judge
 * @file Submit.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Submit extends CI_Controller
{

	private $data; //data sent to view
	private $assignment_root;
	private $problems;
	private $problem;//submitted problem id
	private $filetype; //type of submitted file
	private $ext; //uploaded file extension
	private $file_name; //uploaded file name without extension
	private $coefficient;

	private $language_to_ext = array(
		 'c' => 'c'
		, 'cpp' => 'cpp'
		, 'py2' => 'py'
		, 'py3' => 'py'
		, 'java' => 'java'
		, 'zip' => 'zip'
		, 'pdf' => 'pdf'
	);

	// ------------------------------------------------------------------------


	public function __construct()
	{
		parent::__construct();
		if ( ! $this->user->logged_in()) // if not logged in
			redirect('login');
		$this->load->library('upload')->model('queue_model');
		$this->load->model('submit_model');
	}


	// ------------------------------------------------------------------------


	public function _language_to_type($language)
	{
		$language = strtolower ($language);
		switch ($language) {
			case 'c': return 'c';
			case 'c++': return 'cpp';
			case 'python 2': return 'py2';
			case 'python 3': return 'py3';
			case 'java': return 'java';
			case 'zip': return 'zip';
			case 'pdf': return 'pdf';
			default: return FALSE;
		}
	}


	// ------------------------------------------------------------------------


	public function _match($type, $extension)
	{
		switch ($type) {
			case 'c': return ($extension==='c'?TRUE:FALSE);
			case 'cpp': return ($extension==='cpp'?TRUE:FALSE);
			case 'py2': return ($extension==='py'?TRUE:FALSE);
			case 'py3': return ($extension==='py'?TRUE:FALSE);
			case 'java': return ($extension==='java'?TRUE:FALSE);
			case 'zip': return ($extension==='zip'?TRUE:FALSE);
			case 'pdf': return ($extension==='pdf'?TRUE:FALSE);
		}
	}


	// ------------------------------------------------------------------------


	public function _check_language($str)
	{
		if ($str=='0')
			return FALSE;
		if (in_array( strtolower($str),array('c', 'c++', 'python 2', 'python 3', 'java', 'zip', 'pdf')))
			return TRUE;
		return FALSE;
	}

	//-----------------------------------------------------------------
	private function get_request_template_content(){
		if ( ! $this->input->is_ajax_request() )
			show_404();

		$this->form_validation->set_rules('assignment','assignment','integer|greater_than[0]');
		$this->form_validation->set_rules('problem','problem','integer|greater_than[0]');

		if($this->form_validation->run())
		{
			$assignment_id = $this->input->post('assignment');
			$problem_id = $this->input->post('problem');

			$template_file = $this->problem_model->get_template_path($problem_id);

			if(!$template_file){
				return NULL;
			} else {
				$filename = shj_basename($template_file[0]);
				$template = file_get_contents($template_file[0]);

				return $template;
			}
		}
		else {
			show_error("Invalid request assignment or problem", 400);
		}
	}
	/*
	* Return the template split into 3 variables 
	* to be displayed in submit page
	*/
	public function template(){
		// Find pdf file
		$template = $this->get_request_template_content();
		
		if ($template == NULL)
			$result = array('banned' => '', 'before'  => '', 'after' => '');

		preg_match("/(\/\*###Begin banned.*\n)((.*\n)*)(###End banned keyword\*\/)/"
			, $template, $matches
		);
	
		$set_or_empty = function($arr, $key){
			if(isset($arr[$key])) return $arr[$key];
			return "";
		};

		$banned = $set_or_empty($matches, 2);

		preg_match("/(###End banned keyword\*\/\n)((.*\n)*)\/\/###INSERT CODE HERE -\n?((.*\n?)*)/"
			, $template, $matches
		);

		$before = $set_or_empty($matches, 2);
		$after = $set_or_empty($matches, 4);

		$result = array('banned' => $banned, 'before'  => $before, 'after' => $after);

		$this->output->set_content_type('application/json')
			->set_output(json_encode($result));
	}

	// ------------------------------------------------------------------------


	public function index($problem = NULL, $assignment = NULL)
	{
		$this->form_validation->set_rules('assignment','assignment','integer|greater_than[-1]');
		$this->form_validation->set_rules('problem', 'problem', 'required|integer|greater_than[0]', array('greater_than' => 'Select a %s.'));
		$this->form_validation->set_rules('language', 'language', 'required|callback__check_language', array('_check_language' => 'Select a valid %s.'));

		if ($this->form_validation->run())
		{
			if ($this->_upload())
				redirect('submissions/all');
			else
				show_error('Error Uploading File: '.$this->upload->display_errors());
		}
		else {
			// If form not pass validation, we redirect to the editor page
			if ($problem == NULL && $assignment == NULL){
				$assignment = $this->user->selected_assignment['id']; 
				$problem = 0;//No problem should have id 0 so the editor will just select first prob in assigment
			}
			redirect ("submit/editor/$problem/$assignment");
		}
	}

	//------------------------------------------------------------------------
	/*
	* The editor will require valid problem_id always
	* only admin or head instructor can submit without assigment id
	*/
	public function editor($problem_id = NULL,$assignment_id = NULL){
		$this->data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),	
			'problems_js' => '',
			'error' => 'none',
		);

		$assignment = $this->assignment_model->assignment_info($assignment_id);
		if ($assignment['id'] == 0 && $this->user->level < 2){
			show_error('Only admin can submit without assignment', 403);
		} 
		
		if($assignment['id'] == 0)
			$this->data['problems'] = array($this->problem_model->problem_info($problem_id));
		else 
		{
			$this->data['problems'] = $this->assignment_model->all_problems($assignment_id);
			$this->data['error'] = $this->assignment_model->can_submit($assignment)['error_message'];
			$this->data['assignment'] = $assignment;
			/*
			* if $problem_id doesn't belong to this assignemnt
			* set it to the first problems in the assigment
			*/
			if (!isset($this->data['problems'][$problem_id]))
				$problem_id = key($this->data['problems']);
		}
		 
		foreach ($this->data['problems'] as $problem)
		{
			$items='';
			foreach ($this->problem_model->get_languages($problem['id']) as $language)
			{
				$items = $items."'".trim($language->name)."',";
			}
			$items = substr($items,0,strlen($items)-1);
			$this->data['problems_js'] .= "shj.p[{$problem['id']}]=[{$items}]; ";
		}

		$this->data['from'] = $problem_id;

		//var_dump($this->data); die();
		$this->twig->display('pages/submit.twig', $this->data);
	}
	// ------------------------------------------------------------------------

	/**
	 * Saves submitted code and adds it to queue for judging
	 */
	private function upload_post_code($assignment, $problem, $a,$user_dir, $submit_info){
		if (strlen($a) > $this->settings_model->get_setting('file_size_limit') * 1024 ){
			//string length larger tan file size limit
			show_error("Your submission is larger than system limited size");
		}
		$this->ext = $this->language_to_ext[$this->filetype];

		$file_name = "solution";
		file_put_contents("$user_dir/$file_name-"
							.($assignment['total_submits']+1)
							. "." . $this->ext, $a);

		$this->load->model('submit_model');

		$submit_info['submit_id'] = $this->assignment_model->increase_total_submits($assignment['id']);
		$submit_info['file_name'] = "$file_name-"
						.($assignment['total_submits']+1);
		$submit_info['main_file_name'] = "$file_name";

		if ($problem['is_upload_only'] == 0)
		{
			$this->queue_model->add_to_queue($submit_info);
			process_the_queue();
		}
		else
		{
			$this->submit_model->add_upload_only($submit_info);
		}

		return TRUE;
	}
	private function _upload_file_code($assignment, $problem, $user_dir, $submit_info){
		if (!isset($_FILES['userfile']) or $_FILES['userfile']['error'] == 4)
			show_error('No file chosen.');

		$this->ext = substr(strrchr($_FILES['userfile']['name'],'.'),1); // uploaded file extension
		$this->file_name = basename($_FILES['userfile']['name'], ".{$this->ext}"); // uploaded file name without extension
		if ( ! $this->_match($this->filetype, $this->ext) )
			show_error('This file type does not match your selected language.');
									  
		$this->file_name = preg_replace('/[^a-zA-Z0-9_\-()]+/', '', $this->file_name);
		
		$config['upload_path'] = $user_dir;
		$config['allowed_types'] = '*';
		$config['max_size']	= $this->settings_model->get_setting('file_size_limit');
		$config['file_name'] = $this->file_name."-".($assignment['total_submits']+1).".".$this->ext;
		$config['max_file_name'] = 200;
		$config['remove_spaces'] = TRUE;
		$this->upload->initialize($config);

		if ($this->upload->do_upload('userfile'))
		{
			$result = $this->upload->data();			

			$submit_info['submit_id'] = $this->assignment_model->increase_total_submits($assignment['id']);
			$submit_info['file_name'] = $result['raw_name'];
			$submit_info['main_file_name'] = $this->file_name;
			
			if ($problem['is_upload_only'] == 0)
			{
				$this->queue_model->add_to_queue($submit_info);
				process_the_queue();
			}
			else
			{
				$this->submit_model->add_upload_only($submit_info);
			}

			return TRUE;
		}

		return FALSE;
	}
	private function eval_coefficient($assignment){
		$extra_time = $assignment['extra_time'];
		$delay = shj_now()-strtotime($assignment['finish_time']);;
		ob_start();
		if ( eval($assignment['late_rule']) === FALSE )
			$coefficient = "error";
		if (!isset($coefficient))
			$coefficient = "error";
		ob_end_clean();
		$this->coefficient = $coefficient;
	}
	private function _upload(){
		$problem = $this->problem_model->problem_info($this->input->post('problem'));
		$assignment = $this->assignment_model->assignment_info($this->input->post('assignment'));
		$this->filetype = $this->_language_to_type(strtolower(trim($this->input->post('language'))));

		if ($assignment['id'] == NULL && $this->user->level < 2){
			show_error("Only admin can submit without assignment", 403);
		}

		$this->eval_coefficient($assignment);

		$a = $this->assignment_model->can_submit($assignment);
		if(! $a['can_submit'] ) show_error($a['error_message'], 403);

		if ( $this->queue_model->in_queue($this->user->username,$assignment['id'], $problem['id']) )
			show_error('You have already submitted for this problem. Your last submission is still in queue.');

		$wrong_language = true;
		foreach ($problem['languages'] as $lang)
		{
			if ($this->filetype == $lang['ext']){
				$wrong_language = false;
				break;
			}
		}
		if ( $wrong_language )
			show_error('This file type is not allowed for this problem.');

		//$user_dir = rtrim($this->assignment_root, '/').'/assignment_'.$assignment['id'].'/p'.$this->problem['id'].'/'.$this->user->username;
		$user_dir = $this->submit_model->directory($assignment['id'], $problem['id'], $this->user->username);
		if ( ! file_exists($user_dir))
			mkdir($user_dir, 0700);

		$submit_info = array(
			'username' => $this->user->username,
			'assignment' => $assignment['id'],
			'problem' => $problem['id'],
			'file_type' => $this->filetype,
			'coefficient' => $this->coefficient,
			'pre_score' => 0,
			'time' => shj_now_str(),
		);

		$a = $this->input->post('code');
		if ($a != NULL){
			return $this->upload_post_code($assignment, $problem, $a, $user_dir, $submit_info);
		} else {
			return $this->_upload_file_code($assignment, $problem, $user_dir, $submit_info);
		}
	}



}
