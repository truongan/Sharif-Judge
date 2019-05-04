<?php
/**
 * Sharif Judge online judge
 * @file Scoreboard.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Scoreboard extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		if ($this->input->is_cli_request())
			return;
		$this->user->login_redirect();
		$this->load->model('scoreboard_model');
	}


	// ------------------------------------------------------------------------

	public function index()
	{
		redirect('scoreboard/full/'.$this->user->selected_assignment['id']);
	}
	public function full($assignment_id){
		$assignment = $this->assignment_model->assignment_info($assignment_id);
		$data = array(
			'place' => 'full',
			'assignment' => $assignment,
			'scoreboard' => $this->scoreboard_model->get_scoreboard($assignment_id)
		);

		$this->twig->display('pages/scoreboard.twig', $data);

	}
	private function _strip_scoreboard($assignment_id){
		$a = $this->scoreboard_model->get_scoreboard($assignment_id);

		//Remove excess info
		$a = preg_replace('/[0-9]+:[0-9]+(\*\*)?/', '', $a);
		$a = preg_replace('/\B-\B/', '', $a);
		$a = preg_replace('/[0-9]+\*/', '0', $a);
		$a = preg_replace('/\n+/', "\n", $a);

		//Remove the legend
		$c = 0;
		$i = strlen($a) - 1;
		for(; $i > 0; $i--){
		    if($a[$i] == "\n") $c++;
		    if($c == 3) break;
		}
		$a = substr($a, 0, $i);

		return $a;
	}
	public function simplify($assignment_id){
		$assignment = $this->assignment_model->assignment_info($assignment_id);

		$data = array(
			'place' => 'simplify',
			'assignment' => $assignment,
			'scoreboard' => $this->_strip_scoreboard($assignment_id)
		);

		$this->twig->display('pages/scoreboard.twig', $data);
	}
	public function plain($assignment_id){
		$assignment = $this->assignment_model->assignment_info($assignment_id);

		$data = array(
			'place' => 'plain',
			'assignment' => $assignment,
			'scoreboard' => strip_tags( $this->_strip_scoreboard($assignment_id), "<table><thead><th><tbody><tr><td><br>")
		);

		$this->twig->display('pages/scoreboard.twig', $data);
	}
}
