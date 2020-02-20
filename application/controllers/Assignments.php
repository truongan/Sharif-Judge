<?php
/**
 * Sharif Judge online judge
 * @file Assignments.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Assignments extends CI_Controller
{

	private $messages;
	private $edit_assignment;
	private $edit;


	// ------------------------------------------------------------------------


	public function __construct()
	{
		parent::__construct();
		$this->user->login_redirect();

		
		$this->messages = array();
		$this->edit_assignment = array();
		$this->edit = FALSE;
	}


	// ------------------------------------------------------------------------
//#region

	public function index()
	{
		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'messages' => $this->messages,
		);

		foreach ($data['all_assignments'] as &$item)
		{
			$extra_time = $item['extra_time'];
			$delay = shj_now()-strtotime($item['finish_time']);;
			$submit_time = shj_now()-strtotime($item['start_time']);;
			ob_start();
			if ( eval($item['late_rule']) === FALSE )
				$coefficient = "error";
			if (!isset($coefficient))
				$coefficient = "error";
			ob_end_clean();
			$item['coefficient'] = $coefficient;
			$item['finished'] = ($item['start_time'] < $item['finish_time'] &&  $delay > $extra_time);
			$item['no_of_problems'] = $this->assignment_model->count_no_problems($item['id']);
		}
		// var_dump($item);die();
		$this->twig->display('pages/assignments.twig', $data);

	}



	public function scores($mode){
		$this->load->model('submit_model');

		$all_assignments = $this->assignment_model->all_assignments();
		
		$all_user = $this->user_model->get_all_users();
		foreach($all_user as $user){
			$user['assignments'] = array();
			foreach($all_assignments as $id => $ass){
				$user['assignments'][$id] = new class{public $accepted = ""; public $total = "";};
			}
			$tmp[$user['username']] = $user;
			// var_dump($user['assignments']);
		}
		$all_user = $tmp;
		// var_dump($all_user); die();

		foreach($all_assignments as  $ass){

			$all_sub = $this->submit_model->get_final_submissions($ass['id'], 4, NULL);
			$problems = $this->assignment_model->all_problems($ass['id']);
			foreach ($all_sub as &$item)
			{
				$item['fullmark'] = ($item['pre_score'] == 10000);
				$item['pre_score'] = ceil($item['pre_score']
				*($problems[$item['problem_id']]['score']?? 0)
				/10000);
				// var_dump($item); die();
				
				if ($item['coefficient'] === 'error')
				$item['final_score'] = 0;
				else
				$item['final_score'] = ceil($item['pre_score']*$item['coefficient']/100);
				
				$all_user[$item['username']]['assignments'][$item['assignment_id']]->total = (int)($all_user[$item['username']]['assignments'][$item['assignment_id']]->total) + $item['final_score'];
				
				$all_user[$item['username']]['assignments'][$item['assignment_id']]->accepted = (int)($all_user[$item['username']]['assignments'][$item['assignment_id']]->accepted) + ( ($item['fullmark'])  ?  $item['final_score'] : 0);
				// var_dump($item['pre_score']);
			}
		}
		// var_dump($all_user); die();

		// Sum and average
		foreach($all_user as &$user){
			$c = 0;
			$user['sum'] = new class{public $accepted = 0; public $total = 0;};
			$user['avga'] = new class{public $accepted = ""; public $total = "";};
			$user['avgn'] = new class{public $accepted = ""; public $total = "";};
			foreach($all_assignments as $id => $ass){
				if ($user['assignments'][$id]->total !== ''){
					// var_dump("shit");
					$c += 1;
					$user['sum']->accepted =  (int)$user['sum']->accepted + (int)$user['assignments'][$id]->accepted;
					$user['sum']->total = (int)$user['sum']->total + (int)($user['assignments'][$id]->total);
				}
			}
			
			$user['avga']->accepted = round( $user['sum']->accepted / max($c,1), 2);
			$user['avga']->total = round( $user['sum']->total / max($c, 1), 2);
			
			
			$user['avgn']->accepted = round( $user['sum']->accepted / count($all_assignments), 2);
			$user['avgn']->total = round( $user['sum']->total / count($all_assignments), 2);
			// var_dump($user);die();
		}
		// var_dump($all_user['truonganpn']); die();
		$data = array('all_user' => $all_user, 'all_assignments' => $all_assignments, 'mode' => $mode);
		$this->twig->display('pages/assignments_score.twig', $data);
	}


	// ------------------------------------------------------------------------


	/**
	 * Used by ajax request (for select assignment from top bar)
	 */
	public function select()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();

		$this->form_validation->set_rules('assignment_select', 'Assignment', 'required|integer|greater_than[0]');

		if ($this->form_validation->run())
		{
			$this->user->select_assignment($this->input->post('assignment_select'));
			$this->assignment = $this->assignment_model->assignment_info($this->input->post('assignment_select'));
			$json_result = array(
				'done' => 1,
				'finish_time' => $this->assignment['finish_time'],
				'extra_time' => $this->assignment['extra_time'],
			);
		}
		else
			$json_result = array('done' => 0, 'message' => 'Input Error');

		$this->output->set_header('Content-Type: application/json; charset=utf-8');
		echo json_encode($json_result);
	}

	public function pdf($assignment_id){
		if ($assignment_id === FALSE || ! is_numeric($assignment_id))
			show_404();		
		$assignment = $this->assignment_model->assignment_info($assignment_id);

		if ($this->user->level < 2){
			if(! $this->assignment_model->started($assignment) 
				|| ! $assignment['open']
			){
				show_404();
			}
		}

		$assignment_root = rtrim($this->settings_model->get_setting('assignments_root'),'/');

		$assignment_dir = $assignment_root . "/assignment_" . $assignment_id;
		
		$pdf_files = glob("$assignment_dir/*.pdf");

		if ($pdf_files == FALSE) show_404();

		$filename = shj_basename($pdf_files[0]);
		$this->load->helper('download')->helper('file');
		force_download($filename, file_get_contents($pdf_files[0]), TRUE);
	}
	
	public function download_all_submissions($assignment_id)
	{
		if ($assignment_id === FALSE || ! is_numeric($assignment_id))
			show_404();
		if ( $this->user->level == 0) // permission denied
			show_404();
		$this->load->model('submit_model');
		$this->load->library('zip');

		$assignment_root = rtrim($this->settings_model->get_setting('assignments_root'),'/');

		$this->zip->read_dir(
			$assignment_root . "/assignment_" . $assignment_id
			, FALSE
		);
		$this->zip->download("assignment{$assignment_id}.".date('Y-m-d_H-i',shj_now()).'.zip');
	}

	/**
	 * Compressing and downloading final codes of an assignment to the browser
	 */
	public function download_submissions($type = FALSE, $assignment_id = FALSE)
	{
		if ($type !== 'by_user' && $type !== 'by_problem')
			show_404();
		if ($assignment_id === FALSE || ! is_numeric($assignment_id))
			show_404();
		if ( $this->user->level == 0) // permission denied
			show_404();

		$this->load->model('submit_model');
		$items = $this->submit_model->get_final_submissions($assignment_id, $this->user->level, $this->user->username);

		$this->load->library('zip');

		$lang = $this->language_model->all_languages();
		
		foreach ($items as $item)
		{
			$file_path = $this->submit_model->get_path($item['username'], $item['assignment_id'], $item['problem_id']) 
			. "/{$item['file_name']}." 
			. $lang[$item['language_id']]->extension
			;
			// var_dump($file_path); die();
			// $file_path = $assignments_root.
			// 	"/assignment_{$item['assignment']}/p{$item['problem']}/{$item['username']}/{$item['file_name']}."
			// 	.filetype_to_extension($item['file_type']);
			if ( ! file_exists($file_path))
				continue;
			$file = file_get_contents($file_path);
			if ($type === 'by_user')
				$this->zip->add_data("{$item['username']}/problem_{$item['problem_id']}.".$lang[$item['language_id']]->extension, $file);
			elseif ($type === 'by_problem')
				$this->zip->add_data("problem_{$item['problem_id']}/{$item['username']}.".$lang[$item['language_id']]->extension, $file);
		}

		$this->zip->download("assignment{$assignment_id}_submissions_{$type}_".date('Y-m-d_H-i',shj_now()).'.zip');
	}


	// ------------------------------------------------------------------------


	/**
	 * Delete assignment
	 */
	public function delete($assignment_id = FALSE)
	{
		if ($assignment_id === FALSE)
			show_404();
		if ($this->user->level <= 1) // permission denied
			show_404();

		$assignment = $this->assignment_model->assignment_info($assignment_id);

		if ($assignment['id'] === 0)
			show_404();

		if ($this->input->post('delete') === 'delete')
		{
			$this->assignment_model->delete_assignment($assignment_id);
			redirect('assignments');
		}

		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'id' => $assignment_id,
			'name' => $assignment['name']
		);

		$this->twig->display('pages/admin/delete_assignment.twig', $data);

	}

	public function reload_scoreboard($assignment_id = FALSE)
	{
		if ($assignment_id === FALSE)
			show_404();
		if ($this->user->level <= 1) // permission denied
			show_404();
		
		$this->load->model('scoreboard_model');
		if ($this->scoreboard_model->update_scoreboard($assignment_id)){
			$this->messages[] = array(
				'type' => 'success',
				'text' => "Successfully reload scoreboard for assignment "  . $assignment_id
			);			

			$this->index();
		}
			//echo("Success");
	}



	// ------------------------------------------------------------------------
	/**
	 * This method gets inputs from user for adding/editing assignment
	 */
	public function add()
	{

		if ($this->user->level <= 1) // permission denied
			show_404();

		$this->load->library('upload');

		if ( ! empty($_POST) )
			if ($this->_add()) // add/edit assignment
			{
				$this->index();
				return;
			}

		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'all_problems' => $this->problem_model->all_problems(),
			'messages' => $this->messages,
			'edit' => $this->edit,
			'default_late_rule' => $this->settings_model->get_setting('default_late_rule'),
		);

		$data['problems'][-1] = array('id' => -1, 'name' => 'dummy', 'score'=>0);

		if ($this->edit)
		{
			$data['edit_assignment'] = $this->assignment_model->assignment_info($this->edit_assignment);
			if ($data['edit_assignment']['id'] === 0)
				show_404();
			$data['problems'] += $this->assignment_model->all_problems($this->edit_assignment) ;
		}
		else
		{
			$names = $this->input->post('problem_name[]');
			if ($names !== NULL)
			{
				$id = $this->input->post('problem_id[]');
				$names = $this->input->post('problem_name[]');
				$scores = $this->input->post('problem_score[]');

				for ($i=0; $i<count($names); $i++){
					$data['problems'][$id[$i]] = array(
						'id' => $id[$i],
						'name' => $names[$i],
						'score' => $scores[$i],		
					);
				}
			}
		}

		$this->twig->display('pages/admin/add_assignment.twig', $data);
	}


	// ------------------------------------------------------------------------


	/**
	 * Add/Edit assignment
	 */
	private function _add()
	{

		// Check permission
		if ($this->user->level <= 1) // permission denied
			show_404();

		$this->form_validation->set_rules('assignment_name', 'assignment name', 'required|max_length[150]');
		$this->form_validation->set_rules('start_time', 'start time', 'required');
		$this->form_validation->set_rules('finish_time', 'finish time', 'required');
		$this->form_validation->set_rules('extra_time', 'extra time', 'required');
		$this->form_validation->set_rules('participants', 'participants', '');
		$this->form_validation->set_rules('late_rule', 'coefficient rule', 'required');
		$this->form_validation->set_rules('name[]', 'problem name', 'required|max_length[150]');
		$this->form_validation->set_rules('score[]', 'problem score', 'required|integer');

		// Validate input data
		if ( ! $this->form_validation->run())
			return FALSE;

		// Preparing variables
		if ($this->edit)
			$the_id = $this->edit_assignment;
		else
			$the_id = $this->assignment_model->new_assignment_id();

		// Adding/Editing assignment in database
		if ( ! $this->assignment_model->add_assignment($the_id, $this->edit))
		{
			$this->messages[] = array(
				'type' => 'error',
				'text' => 'Error '.($this->edit?'updating':'adding').' assignment.'
			);
			return FALSE;
		}

		$this->messages[] = array(
			'type' => 'success',
			'text' => 'Assignment '.($this->edit?'updated':'added').' successfully.'
		);

		$assignments_root = rtrim($this->settings_model->get_setting('assignments_root'), '/');
		$assignment_dir = "$assignments_root/assignment_{$the_id}";
		
		// Create assignment directory
		if ( ! file_exists($assignment_dir) )
			mkdir($assignment_dir, 0700, TRUE);
		
		$this->_take_test_file_upload($assignments_root, $assignment_dir);

		return TRUE;
	}

	private function _take_test_file_upload($assignments_root, $assignment_dir){
		// Upload PDF File of Assignment
		$config = array(
			'upload_path' => $assignment_dir,
			'allowed_types' => 'pdf',
		);
		$this->upload->initialize($config);
		$old_pdf_files = glob("$assignment_dir/*.pdf");
		$pdf_uploaded = $this->upload->do_upload("pdf");
		if ($_FILES['pdf']['error'] === UPLOAD_ERR_NO_FILE)
			$this->messages[] = array(
				'type' => 'notice',
				'text' => "Notice: You did not upload any pdf file for assignment. If needed, upload by editing assignment."
			);
		elseif ( ! $pdf_uploaded)
			$this->messages[] = array(
				'type' => 'error',
				'text' => "Error: Error uploading pdf file of assignment: ".$this->upload->display_errors('', '')
			);
		else
		{
			foreach($old_pdf_files as $old_name)
				shell_exec("rm -f $old_name");
			$this->messages[] = array(
				'type' => 'success',
				'text' => 'PDF file uploaded successfully.'
			);
		}
	}


	public function edit($assignment_id)
	{

		if ($this->user->level <= 1) // permission denied
			show_404();

		$this->edit_assignment = $assignment_id;
		$this->edit = TRUE;

		// redirect to add function
		$this->add();
	}



}
