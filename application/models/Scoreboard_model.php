<?php
/**
 * Sharif Judge online judge
 * @file Scoreboard_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Scoreboard_model extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
	}


	/**
	 * Generate Scoreboard
	 *
	 * Generates scoreboard for given assignment, from Final Submissions.
	 * This function is called by update_scoreboard
	 *
	 * @param int $assignment_id
	 * @return array
	 */
	private function _generate_scoreboard($assignment_id)
	{
		$assignment = $this->assignment_model->assignment_info($assignment_id);
		$submissions = $this->db->get_where('submissions', array('is_final' => 1 , 'assignment_id' => $assignment_id))->result_array();
		$total_score = array();
		$total_accepted_score = array();
		$solved = array();
		$tried_to_solve = array();
		$penalty = array();
		$users = array();
		$start = strtotime($assignment['start_time']);
		$end = strtotime($assignment['finish_time']);
		$submit_penalty = $this->settings_model->get_setting('submit_penalty');
		$scores = array();

		$problems = $this->assignment_model->all_problems($assignment_id);
		/* An: 2018-08-26
			calculate number of submission for each problems
			to calculate wrong submission penalty
			in a better way
		*/
		$tmp = $this->db->select('username, problem_id, count(*) as subcount')
				->group_by(array('username', 'problem_id'))
				->where('assignment_id', $assignment_id)
				->get('submissions')
				->result();
		// var_dump($this->db->last_query());
		foreach($tmp as $x){
			$number_of_submissions[$x->username][$x->problem_id] = $x->subcount;
		}

		foreach ($submissions as $submission){

			$pre_score = ceil($submission['pre_score']*($problems[$submission['problem_id']]['score'] ?? 0 )/10000);
			if ($submission['coefficient'] === 'error')
				$final_score = 0;
			else
				$final_score = ceil($pre_score*$submission['coefficient']/100);
			$fullmark = ($submission['pre_score'] == 10000);
			$delay = strtotime($submission['time'])-$start;
			$late = strtotime($submission['time'])-$end;
			$scores[$submission['username']][$submission['problem_id']]['score'] = $final_score;
			$scores[$submission['username']][$submission['problem_id']]['time'] = $delay;
			$scores[$submission['username']][$submission['problem_id']]['late'] = $late;
			$scores[$submission['username']][$submission['problem_id']]['fullmark'] = $fullmark;

			if ( ! isset($total_score[$submission['username']])){
				$total_score[$submission['username']] = 0;
				$total_accepted_score[$submission['username']] = 0;
			}
			if ( !isset($solved[$submission['username']])){
				$solved[$submission['username']] = 0;
				$tried_to_solve[$submission['username']] = 0;
			}
			if ( ! isset($penalty[$submission['username']]))
				$penalty[$submission['username']] = 0;

			$solved[$submission['username']] += $fullmark;
			$tried_to_solve[$submission['username']] += 1;
			$total_score[$submission['username']] += $final_score;
			if ($fullmark) $total_accepted_score[$submission['username']] += $final_score;

			if($fullmark) $penalty[$submission['username']] += $delay 
					+ $number_of_submissions[$submission['username']][$submission['problem_id']]
						*$submit_penalty;
			$users[] = $submission['username'];
		}
		$scoreboard = array(
			'username' => array(),
			'score' => array(),
			'accepted_score' => array(),
			'submit_penalty' => array()
			,'solved' => array()
			,'tried_to_solve' => array()
		);
		$users = array_unique($users);
		foreach($users as $username){
			array_push($scoreboard['username'], $username);
			array_push($scoreboard['score'], $total_score[$username]);
			array_push($scoreboard['accepted_score'], $total_accepted_score[$username]);
			array_push($scoreboard['submit_penalty'], $penalty[$username]);
			array_push($scoreboard['solved'], $solved[$username]);
			array_push($scoreboard['tried_to_solve'], $tried_to_solve[$username]);
		}
		array_multisort(
			$scoreboard['solved'], SORT_NUMERIC, SORT_DESC,
			$scoreboard['accepted_score'], SORT_NUMERIC, SORT_DESC,
			$scoreboard['score'], SORT_NUMERIC, SORT_DESC,
			$scoreboard['submit_penalty'], SORT_NUMERIC, SORT_ASC,
			$scoreboard['username']
			,$scoreboard['tried_to_solve']
		);
		return array($scores, $scoreboard);
	}


	// ------------------------------------------------------------------------


	/**
	 * Update All Scoreboards
	 *
	 * Updates the cached scoreboard of all assignments.
	 * This function is called each time a user is deleted, or all submissions
	 * of a user is deleted.
	 */
	public function update_scoreboards()
	{
		$assignments = $this->db->select('id')->get('assignments')->result_array();
		foreach ($assignments as $assignment){
			$this->update_scoreboard($assignment['id']);
		}
	}


	// ------------------------------------------------------------------------


	/**
	 * Update Scoreboard
	 *
	 * Updates the cached scoreboard of given assignment. Saves the html code of
	 * scoreboard table in database.
	 *
	 * This function is called after judging/rejudging a submission, and when one
	 * of these settings is changed for an assignment:
	 *
	 * TODO: Better Implementation (faster and with less db queries)
	 *
	 *   - Extra Time
	 *   - Start Time
	 *   - Finish Time
	 *   - Coefficient's Rule
	 *   - Enable/Disable Scoreboard
	 *
	 * @param int $assignment_id
	 */
	public function update_scoreboard($assignment_id)
	{

		// If scoreboard in not enabled, do nothing
		//$scoreboard_enabled = $this->db->select('scoreboard')->get_where('assignments', array('id'=>$assignment_id))->row()->scoreboard;
		if ($assignment_id == 0
			//OR  ! $scoreboard_enabled  /* An: 2017-10-07: Always update scoreboard, enable scoreboard option only hide it
		)
			return false;

		// Generate the scoreboard
		list ($scores, $scoreboard) = $this->_generate_scoreboard($assignment_id);

		// Generate the scoreboard's html code
		// todo: Save Scoreboard as json (generate html at client side)
		$all_problems = $this->assignment_model->all_problems($assignment_id);
		$total_score = 0;
		foreach($all_problems as $i)
			$total_score += $i['score'];
		$data = array(
			'assignment_id' => $assignment_id,
			'problems' => $all_problems,
			'total_score' => $total_score,
			'scores' => $scores,
			'scoreboard' => $scoreboard,
			'names' => $this->user_model->get_names()
		);

		$scoreboard_table = $this->twig->render('pages/scoreboard_table.twig', $data);

		// Minify the scoreboard's html code
		// $scoreboard_table = $this->output->minify($scoreboard_table, 'text/html');

		// Save the scoreboard's html code in Database
		$query = $this->db->select('assignment')->get_where('scoreboard', array('assignment'=>$assignment_id));
		if ($query->num_rows()==0)
			$this->db->insert('scoreboard', array('assignment'=>$assignment_id, 'scoreboard'=>$scoreboard_table));
		else
			$this->db->where('assignment', $assignment_id)->update('scoreboard', array('scoreboard'=>$scoreboard_table));
		
		return true;
	}


	// ------------------------------------------------------------------------


	/**
	 * Get Cached Scoreboard
	 *
	 * Returns the cached scoreboard of given assignment as a html text
	 *
	 * @param int $assignment_id
	 * @return string
	 */
	public function get_scoreboard($assignment_id)
	{
		$query =  $this->db->select('scoreboard')->get_where('scoreboard', array('assignment'=>$assignment_id));
		if ($query->num_rows() != 1)
			return 'Scoreboard not found';
		else
			return $query->row()->scoreboard;
	}

}
