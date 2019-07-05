<?php
/**
 * Sharif Judge online judge
 * @file Submissions.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Submissions extends CI_Controller
{

	private $problems;

	private $filter_user;
	private $filter_problem;
	private $page_number;

	private $pagination_config;
	// ------------------------------------------------------------------------


	public function __construct()
	{
		$this->pagination_config['per_page'] = 2;
		$this->pagination_config['uri_segment'] = 3;
		$this->pagination_config['num_links'] = 3;
		$this->pagination_config['use_page_numbers'] = TRUE;

		$this->pagination_config['full_tag_open'] 	= '<nav><ul class="pagination justify-content-center">';
		$this->pagination_config['full_tag_close'] 	= '</ul></nav>';

		$this->pagination_config['num_tag_open'] 	= '<li class="page-item">';
		$this->pagination_config['num_tag_close'] 	= '</li>';

		$this->pagination_config['cur_tag_open'] 	= '<li class="page-item active"><span class="page-link">';
		$this->pagination_config['cur_tag_close'] 	= '<span class="sr-only">(current)</span></span></li>';

		$this->pagination_config['next_tag_open'] 	= '<li class="page-item"><a ';
		$this->pagination_config['next_tagl_close'] 	= '<span aria-hidden="true">&raquo;</span></li>';

		$this->pagination_config['prev_tag_open'] 	= '<li class="page-item">';
		$this->pagination_config['prev_tagl_close'] 	= '</li>';

		$this->pagination_config['first_tag_open'] 	= '<li class="page-item">';
		$this->pagination_config['first_tagl_close'] = '</li>';

		$this->pagination_config['last_tag_open'] 	= '<li class="page-item">';
		$this->pagination_config['last_tagl_close'] 	= '</li>';

		parent::__construct();
		$this->user->login_redirect();
		$this->load->model('submit_model');
		

		$input = $this->uri->uri_to_assoc();

		$this->filter_user = $this->filter_problem = NULL;
		$this->assignment = 0;
		$this->page_number = 1;
		
		if (array_key_exists('user', $input) && $input['user'])
			if ($this->user->level > 0) {
				// Only non student user can use user filter
				$this->filter_user = isset($input['user'])?$input['user']:NULL;
			}
			
		if (array_key_exists('problem', $input) && $input['problem'])
			$this->filter_problem = is_numeric($input['problem'])?$input['problem']:NULL;
		
		if (array_key_exists('problem', $input) && $input['problem'])
			$this->filter_problem = is_numeric($input['problem'])?$input['problem']:NULL;
			
		if (array_key_exists('assignment', $input) && $input['assignment'])
			$this->assignment = is_numeric($input['assignment'])?$input['assignment']:0;

		$this->problems = $this->assignment_model->all_problems($this->assignment);
		
		// var_dump($this->db->last_query()); die();
		if (array_key_exists('page', $input) && $input['page'])
			$this->page_number = is_numeric($input['page'])?$input['page']:1;

	}

	private function _do_access_check($assignment_id){
		$assignment =  $this->assignment_model->assignment_info($assignment_id);
			
		if ($assignment['id'] == 0 && $this->user->level < 2) {
			show_error("Only admin can view submission without assignment", 403);
		}
		if ($assignment['open'] == 0  && $this->user->level < 2){
			show_error("assignment " . $assignment['id'] . " has ben closed. Only admin can view submission", 403);
		}
	}
	// ------------------------------------------------------------------------
	public function final()
	{
		return $this->_index("final");
	}


	public function index(){
		// $last_submission = $this->submit_model->find_last_submission($this->user->username);
		// if ($last_submission){
		// 	return redirect('submissions/all/assignment/' .  $last_submission->assignment_id);
		// }
		return redirect('submissions/all/assignment/'.$this->user->selected_assignment['id']);
	}

	private function _index($type = "all"){
		if ( ! is_numeric($this->page_number))
			show_404();

		if ($this->page_number < 1)
			show_404();
		
		$this->_do_access_check($this->assignment);
		$assignment =  $this->assignment_model->assignment_info($this->assignment);
		if ($assignment['id'] != 0){
			$this->user->select_assignment($assignment['id']);
		}
			// var_dump($this->assignment_model->assignment_info($this->assignment));die();
		
		$this->pagination_config['base_url'] = site_url("submissions/$type/assignment/".$assignment['id']."/".($this->filter_user?'/user/'.$this->filter_user:'').($this->filter_problem?'/problem/'.$this->filter_problem:'')) . "/page/";
		$this->pagination_config['cur_page'] = $this->page_number;
		$this->pagination_config['total_rows'] = $this->submit_model->count_all_submissions($this->assignment
						, $this->user->level, $this->user->username
						, $this->filter_user, $this->filter_problem);
		$this->pagination_config['per_page'] = $this->settings_model->get_setting('results_per_page_all');

		if ($this->pagination_config['per_page'] == 0)
			$this->pagination_config['per_page'] = $config['total_rows'];
		$this->load->library('pagination');
		$this->pagination->initialize($this->pagination_config);

		if ($type == "all") 
			$submissions = $this->submit_model->get_all_submissions($this->assignment, $this->user->level, $this->user->username, $this->page_number, $this->filter_user, $this->filter_problem);
		else if ($type == "final")
			$submissions = $this->submit_model->get_final_submissions($this->assignment, $this->user->level, $this->user->username, NULL, $this->filter_user, $this->filter_problem);
		else show_404();

		$names = $this->user_model->get_names();

		foreach ($submissions as &$item)
		{
			// var_dump($this->problems); die();
			$item['name'] = $names[$item['username']];
			$item['problem_name'] = ($this->problems[$item['problem_id']]['problem_name']??'');
			$item['fullmark'] = ($item['pre_score'] == 10000);
			$item['pre_score'] = ceil($item['pre_score']
				*($this->problems[$item['problem_id']]['score']?? 0)
				/10000);
			$item['delay'] = strtotime($item['time'])-strtotime($assignment['finish_time']);
			$item['language'] = $this->language_model->get_language($item['language_id'])->name;
			if ($item['coefficient'] === 'error')
				$item['final_score'] = 0;
			else
				$item['final_score'] = ceil($item['pre_score']*$item['coefficient']/100);
		}
		//var_dump($assignment); die();
		$data = array(
			'view' => $type,
			'all_assignments' => $this->assignment_model->all_assignments(),
			'assignment' => $assignment,
			'problems' => $this->problems,
			'submissions' => $submissions,
			// 'excel_link' => site_url('submissions/all_excel'.($this->filter_user?'/user/'.$this->filter_user:'').($this->filter_problem?'/problem/'.$this->filter_problem:'')),
			'filter_user' => $this->filter_user,
			'filter_problem' => $this->filter_problem,
			'pagination' => $this->pagination->create_links(),
		);

		$this->twig->display('pages/submissions.twig', $data);
	}
	// ------------------------------------------------------------------------
	public function all()
	{
		return $this->_index('all');
	}




	// ------------------------------------------------------------------------




	/**
	 * Used by ajax request (for selecting final submission)
	 */
	public function select()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
			
		$this->form_validation->set_rules('submit_id', 'Submit ID', 'integer|greater_than[0]');
		$this->form_validation->set_rules('problem', 'problem', 'integer|greater_than[0]');
		$this->form_validation->set_rules('assignment', 'problem', 'integer|required');
			
		if ($this->form_validation->run())
		{
			// Students can only change the final submission in assignment they can still submit.
			if ($this->user->level === 0){
				$assignment = $this->assignment_model->assignment_info($this->input->post('assignment'));
				// var_dump($this->assignment_model->can_submit($assignment));die();
	
				if ( ! $this->assignment_model->can_submit($assignment)['can_submit'])
				{
					$json_result = array(
						'done' => 0,
						'message' => 'You can only change final submission if when you can still submit.'
					);
					$this->output->set_header('Content-Type: application/json; charset=utf-8');
					echo json_encode($json_result);
					return;
				}
			}
			$username = $this->input->post('username');
			if ($this->user->level === 0)
			$username = $this->user->username;
			
			$res = $this->submit_model->set_final_submission(
				$username,
				$this->user->selected_assignment['id'],
				$this->input->post('problem'),
				$this->input->post('submit_id')
			);

			if ($res) {
				// each time a user changes final submission, we should update scoreboard of that assignment
				$this->load->model('scoreboard_model');
				$this->scoreboard_model->update_scoreboard($this->user->selected_assignment['id']);
				$json_result = array('done' => 1);
			}
			else
				$json_result = array('done' => 0, 'message' => 'Selecting Final Submission Failed');
		}
		else
			$json_result = array('done' => 0, 'message' => 'Input Error');

		$this->output->set_header('Content-Type: application/json; charset=utf-8');
		echo json_encode($json_result);
	}




	// ------------------------------------------------------------------------


	public function _check_type($type)
	{
		return ($type === 'code' || $type === 'result' || $type === 'log');
	}


	/**
	 * For "view code" or "view result" or "view log"
	 */
	public function view_code()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
		$this->form_validation->set_rules('type','type','callback__check_type');
		$this->form_validation->set_rules('assignment','assignment','integer');
		$this->form_validation->set_rules('problem','problem','integer|greater_than[0]');
		$this->form_validation->set_rules('submit_id','submit_id','integer|greater_than[0]');

		if($this->form_validation->run())
		{
			$this->_do_access_check($this->input->post('assignment'));

			$submission = $this->submit_model->get_submission(
				$this->input->post('assignment'),
				$this->input->post('submit_id')
			);
			if ($submission === FALSE)
				show_404();

			$type = $this->input->post('type'); // $type is 'code', 'result', or 'log'

			if ($this->user->level === 0 && $type === 'log')
				show_404();

			if ($this->user->level === 0 && $this->user->username != $submission['username'])
				exit('Don\'t try to see submitted codes :)');

			$submit_path = $this->submit_model->get_path($submission['username'], $submission['assignment_id'], $submission['problem_id']);
			$file_extension = $this->language_model->get_language($submission['language_id'])->extension;
			
			if ($type === 'result')
				$file_path = $submit_path . "/result-{$submission['submit_id']}.html";
			elseif ($type === 'code')
				$file_path = $submit_path . "/{$submission['file_name']}.". $file_extension;
			elseif ($type === 'log')
				$file_path = $submit_path . "/log-{$submission['submit_id']}";
			else
				$file_path = '/nowhere'; // This line should never be reached!
			
			$result = array(
				'file_name' => $submission['file_name'].'.'. $file_extension,
				'text' => file_exists($file_path)?file_get_contents($file_path):"File Not Found"
			);

			if ($type === 'code') {
				$result['lang'] = $file_extension;
				if ($result['lang'] == 'py2' || $result['lang'] == 'py3')
					$result['lang'] = 'python';
				else if ($result['lang'] == 'pas')
					$result['lang'] = 'pascal';
			}

			$this->output->set_content_type('application/json')->set_output(json_encode($result));

		}
		else
			exit('Are you trying to see other users\' codes? :)');
	}

	public function status(){
		if ( ! $this->input->is_ajax_request() )
			show_404();

		$this->form_validation->set_rules('assignment', 'assignment', 'required|integer');
		$this->form_validation->set_rules('problem', 'problem', 'required|integer');
		$this->form_validation->set_rules('submit_id','submit_id','integer|greater_than[0]');

		if($this->form_validation->run())
		{
			$this->_do_access_check($this->input->post('assignment'));
			
			$submission = $this->submit_model->get_submission(
						$this->input->post('assignment'),
						$this->input->post('submit_id')
				);

			$all_problems = $this->assignment_model->all_problems($this->input->post('assignment'));

			$submission['fullmark'] = ($submission['pre_score'] == 10000);
			$submission['pre_score'] = ceil($submission['pre_score']*
								($all_problems[$submission['problem_id']]['score']??0)
								/10000);
			if ($submission['coefficient'] === 'error')
				$submission['final_score'] = 0;
			else
				$submission['final_score'] = ceil($submission['pre_score']*$submission['coefficient']/100);
			echo json_encode($submission);
		} else {

		}
	}

}
