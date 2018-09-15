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
		if ( ! $this->user->logged_in()) // if not logged in
			redirect('login');

		$this->all_assignments = $this->assignment_model->all_assignments();
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
			'all_assignments' => $this->all_assignments,
			'can_submit' => TRUE,
		);
		while (1){
			$assignment = $this->assignment_model->assignment_info($assignment_id);

			if($assignment['id'] == 0){
				//show_error('Can not find your assignment', 404);				die();
				if ($this->user->level > 1 && $problem_id != 0) redirect('problems/show/'.$problem_id);
				$data['error'] = "There is nothing to submit to. Please select assignment and problem.";
				break;
			}

			if (! $this->assignment_model->started($assignment)){
				$data['error'] = "selected assignment hasn't started yet";
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


			if ($problem_id == NULL)
				$problem_id = array_keys($data['all_problems'])[0];
			else if ( ! isset($data['all_problems'][$problem_id]))
				show_404();

			$data['problem'] = $this->problem_model->problem_info($problem_id);
			$data['problem'] = array_merge($data['problem'], $this->problem_model->get_description($problem_id));

			$data['error'] = 'none';
			break;
		}
		$this->twig->display('pages/view_problems.twig', $data);
	}


	// ------------------------------------------------------------------------





}
