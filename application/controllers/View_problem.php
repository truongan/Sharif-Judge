<?php
/**
 * Sharif Judge online judge
 * @file Problems.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class View_problem extends CI_Controller
{

	private $all_assignments;


	// ------------------------------------------------------------------------
	public function __construct()
	{
		parent::__construct();
		$this->user->login_redirect();
		$this->load->model('problem_files_model');
	}


	// ------------------------------------------------------------------------
	/**
	 * Displays detail description of given problem
	 *
	 * @param int $problem_id
	 * @param int $assignment_id
	 */
	public function index( $assignment_id = NULL, $problem_id = NULL)
	{
		// If no assignment is given, use selected assignment
		if ($assignment_id === NULL){
			redirect('view_problem/'.$this->user->selected_assignment['id']);
		}
		
		$data=array(
			'can_submit' => TRUE,
		);
		while (1){
			$assignment = $this->assignment_model->assignment_info($assignment_id);

			if($assignment['id'] == 0){
				if ($this->user->level > 1 && $problem_id != 0) redirect('problems/show/'.$problem_id);
				$data['error'] = "There is nothing to submit to. Please select assignment and problem.";
				break;
			}

			if (! $this->assignment_model->started($assignment)){
				$data['error'] = "selected assignment hasn't started yet";
				break;
			}
			
			if ($assignment['open'] == 0  && $this->user->level < 2){
				$data['error'] =("assignment " . $assignment['id'] . " has ben closed");
				break;
			}

			if (! $this->assignment_model->is_participant($assignment,$this->user->username)){
				$data['error'] = "You are not registered to participate in this assignment";
				break;
			}
			$data = array_merge($data, array(
				'all_problems' => $this->assignment_model->all_problems($assignment_id),
				'assignment' => $assignment,
			));

			$a = $this->assignment_model->can_submit($assignment);
			$data['can_submit'] = $a['can_submit'];
			$data['sum_score'] = array_reduce($data['all_problems'], function($carry, $item){
				return $carry + $item['score'];
			});

			if ($problem_id == NULL){
				if (count($data['all_problems']) > 0) $problem_id = array_keys($data['all_problems'])[0];
				else {
					$data['error'] = "No problem in this assignment";
					break;
				}
			}
			else if ( ! isset($data['all_problems'][$problem_id]))
				show_404();

			$data['problem'] = $data['all_problems'][$problem_id];
			$data['problem'] = array_merge($data['problem'], $this->problem_model->problem_info($problem_id));
			$data['problem'] = array_merge($data['problem'], $this->problem_files_model->get_description($problem_id));
			$data['error'] = 'none';
			// var_dump($data);die();

			//Select that assignment
			$this->user->select_assignment($assignment['id']);
			
			/// Problems status
			$this->load->model('submit_model');
			$subs = $this->submit_model->get_final_submissions($assignment['id'], 0, $this->user->username);
			// var_dump($subs);die();
			$probs = array();
			foreach($subs as $sub){
				$class = "";
				if($sub['status'] != 'PENDING'){
					if ($sub['pre_score'] == 10000) $class = 'text-light bg-success';
					else $class = "text-light bg-danger";
				} else $class = "text-light bg-secondary";
				$probs[$sub['problem_id']] = $class;
			}
			$data['problem_status'] = $probs;
			// var_dump($data);die();
			break;

		}
		$this->twig->display('pages/view_problems.twig', $data);
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

	public function pdf($problem_id, $assignment_id){
		$assignment = $this->assignment_model->assignment_info($assignment_id);
		if ($this->assignment_model->can_view($assignment)){
			$all_prob = $this->assignment_model->all_problems($assignment_id);
			$name = $all_prob[$problem_id]['problem_name'];

			$name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $name) . '.pdf';

			$this->problem_files_model->download_pdf($problem_id, $name);
		} else {
			show_404();
		}
	}

	// ------------------------------------------------------------------------





}
