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
	* Download problem's template
	**/
	public function template($assignment_id = NULL, $problem_id = 1){
		// Find pdf file
		if ($assignment_id === NULL)
			$assignment_id = $this->user->selected_assignment['id'];

		if ($assignment_id == 0){
			show_error("Pleas select an assignment first");
		}
		if ($problem_id === NULL)
			show_error("File not found");

		$pattern1 = rtrim($this->settings_model->get_setting('assignments_root'),'/')
					."/assignment_{$assignment_id}/p{$problem_id}/template.public.cpp";

		$pdf_files = glob($pattern1);
		if ( ! $pdf_files ){
			$pattern = rtrim($this->settings_model->get_setting('assignments_root'),'/')
						."/assignment_{$assignment_id}/p{$problem_id}/template.cpp";

			$pdf_files = glob($pattern);
			if(!$pdf_files)
				show_error("File not found");
		}

		// Download the file to browser
		$this->load->helper('download')->helper('file');
		$filename = shj_basename($pdf_files[0]);
		force_download($filename, file_get_contents($pdf_files[0]), TRUE);

	}

	/**
	 * Displays detail description of given problem
	 *
	 * @param int $problem_id
	 * @param int $assignment_id
	 */
	public function index( $assignment_id = NULL, $problem_id = NULL)
	{
		// If no assignment is given, use selected assignment
		if ($assignment_id === NULL)
			$assignment_id = $this->user->selected_assignment['id'];
		
		$data=array('all_assignments' => $this->all_assignments,
			'can_submit' => TRUE,
		);
		while (1){

			$assignment = $this->assignment_model->assignment_info($assignment_id);

			if($assignment['id'] == 0){
				show_error('Can not find your assignment', 404);
				die();
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

			if ( $assignment_id != $this->user->selected_assignment['id'])
				$data['can_submit'] = FALSE;
			else {
				$a = $this->assignment_model->can_submit($assignment);
				$data['can_submit'] = $a['can_submit'];
			}

			if ($problem_id == NULL)
				$problem_id = array_keys($data['all_problems'])[0];
			else if ( ! isset($data['all_problems'][$problem_id]))
				show_404();

			$data['problem'] = $this->problem_model->get_problem($problem_id);
			$data['problem'] = array_merge($data['problem'], $this->problem_model->get_description($problem_id));

			$data['error'] = 'none';
			break;
		}
		$this->twig->display('pages/view_problems.twig', $data);
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
	public function edit($type = 'md', $assignment_id = NULL, $problem_id = 1)
	{
		if ($type !== 'html' && $type !== 'md' && $type !== 'plain')
			show_404();

		if ($this->user->level <= 1)
			show_404();


		$ext = 'html';

		$this->load->library('form_validation');
		$this->form_validation->set_rules('text', 'text' ,'required'); /* todo: xss clean */
		if ($this->form_validation->run())
		{
			if ($this->assignment_model->save_problem_description($assignment_id, $problem_id, $this->input->post('content'), $ext)){
				echo "success";
				return ;
			}
			else show_error("Error saving", 501);
		}


		if ($assignment_id === NULL)
			$assignment_id = $this->user->selected_assignment['id'];
		if ($assignment_id == 0)
			show_error('No assignment selected.');

		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'description_assignment' => $this->assignment_model->assignment_info($assignment_id),
		);

		if ( ! is_numeric($problem_id) || $problem_id < 1 || $problem_id > $data['description_assignment']['problems'])
			show_404();

		$data['problem'] = array(
			'id' => $problem_id,
			'description' => ''
		);

		$path = rtrim($this->settings_model->get_setting('assignments_root'),'/')."/assignment_{$assignment_id}/p{$problem_id}/desc.".$ext;
		if (file_exists($path))
			$data['problem']['description'] = file_get_contents($path);


		$this->twig->display('pages/admin/edit_problem_'.$type.'.twig', $data);

	}


}
