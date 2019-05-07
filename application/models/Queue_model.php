<?php
/**
 * Sharif Judge online judge
 * @file Queue_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Queue_model extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
	}


	// ------------------------------------------------------------------------


	/**
	 * Returns TRUE if one submission with $username, $assignment and $problem
	 * is already in queue (for preventing multiple submission)
	 */
	public function in_queue ($username, $assignment, $problem)
	{
		$query = $this->db->get_where('queue', array('username'=>$username, 'assignment'=>$assignment, 'problem'=>$problem));
		return ($query->num_rows() > 0);
	}
	/**
	 * Returns TRUE if the submission with $assignment and $submit_id
	 * is already in queue (for preventing multiple submission)
	 */
	public function submission_in_queue($submit_id, $assignment)
	{
		$query = $this->db->get_where('queue', array('submit_id'=> $submit_id, 'assignment'=>$assignment));
		return ($query->num_rows() > 0);
	}


	// ------------------------------------------------------------------------


	/**
	 * Returns all the submission queue
	 */
	public function get_queue ()
	{
		return $this->db->get('queue')->result_array();
	}


	// ------------------------------------------------------------------------


	/**
	 * Empties the queue
	 */
	public function empty_queue ()
	{
		return $this->db->empty_table('queue');
	}


	// ------------------------------------------------------------------------


	public function add_to_queue($submit_info)
	{

		$submit_info['is_final'] = 0;
		$submit_info['status'] = 'PENDING';

		$this->db->insert('submissions', $submit_info);
		//var_dump($this->db->last_query());die();
		$this->db->insert('queue', array(
			'submit_id' => $submit_info['submit_id'],
			'username' => $submit_info['username'],
			'assignment' => $submit_info['assignment_id'],
			'problem' => $submit_info['problem_id'],
			'type' => 'judge'
		));
	}


	// ------------------------------------------------------------------------


	/**
	 * Adds submissions of a problem to queue for rejudge
	 */
	public function rejudge($assignment_id, $problem_id)
	{
		// Changing the status of all submissions of selected problem to PENDING
		$this->db->where(
			array(
				'assignment_id' => $assignment_id,
				'problem_id' => $problem_id
			)
		)->update('submissions', array('pre_score' => 0, 'status' => 'PENDING'));

		// Adding submissions to queue:
		$submissions = $this->db
			->select('submit_id, username, assignment_id, problem_id')
			->order_by('submit_id')
			->get_where('submissions', array('assignment_id'=>$assignment_id, 'problem_id'=>$problem_id))
			->result_array();

		foreach($submissions as $submission)
		{
			$this->db->insert('queue',
				array(
					'submit_id' => $submission['submit_id'],
					'username' => $submission['username'],
					'assignment' => $submission['assignment_id'],
					'problem' => $submission['problem_id'],
					'type' => 'rejudge'
				)
			);
		}
		// Now ready for rejudge
	}


	// ------------------------------------------------------------------------


	/**
	 * Adds a single submission to queue for rejudge
	 */
	public function rejudge_single($submission)
	{
		// Changing the status of submission to PENDING
		$this->db->where(array(
			'submit_id' => $submission['submit_id'],
			'username' => $submission['username'],
			'assignment_id' => $submission['assignment'],
			'problem_id' => $submission['problem']
		))->update('submissions', array('pre_score'=>0, 'status'=>'PENDING'));

		// Adding Submission to Queue
		$this->db->insert('queue', array(
			'submit_id' => $submission['submit_id'],
			'username' => $submission['username'],
			'assignment' => $submission['assignment'],
			'problem' => $submission['problem'],
			'type' => 'rejudge'
		));
		// Now ready for rejudge
	}


	// ------------------------------------------------------------------------


	/**
	 * If the number of queue item being processed is less than limit
	 * Returns the first item of the queue that are not being processed
	 */
	public function acquire($limit)
	{
		$this->db->trans_start(); // We use the queue table as a mutex, so this function must be atomic
		$result = NULL;
		// var_dump($limit);
		if ($this->db->where('process_id is not NULL')->get('queue')->num_rows() < $limit){
			//The number of item being process is below limit
			
			$query = $this->db->where('process_id is NULL')
						->order_by('id')->limit(1)->get('queue');
			if ($query->num_rows() == 1){
				//We found a new item to process
				//Mark it as being process
				$queue = $query->row_array();
				$queue['process_id'] = getmypid();
				$this->db->where('id', $queue['id'])->update('queue', array('process_id'=>$queue['process_id']));

				$result = $queue;
			}

		}
		var_dump($result);
		$this->db->trans_complete();
		return $result;
	}

	/*
	* 	Permanent pending submission are PENDING submissions
	*	that are not inlcude in the queue. Perma pending should
	*	only appear when admin manually
	*/
	public function perma_pending(){

	}

	// ------------------------------------------------------------------------


	/**
	 * Removes an item from the queue
	 */
	public function remove_item($id)
	{
		$this->db->delete('queue', array(
			'id' => $id,
		));
	}

	public function unlock($id){
		$this->db->where('id', $id)->update('queue', array('process_id'=> null));
	}

	// ------------------------------------------------------------------------


	/**
	 * Saves the result of judge in database
	 * This function is called from Queueprocess controller
	 */
	public function save_judge_result_in_db ($submission, $type)
	{

		$arr = array(
			'status' => $submission['status'],
			'pre_score' => $submission['pre_score'],
		);

		// if ($type === 'judge')
		// {

		// }

		$final_sub = $this->submit_model->get_final_submission(
			$submission['username'], $submission['assignment_id'], $submission['problem_id']
		);

		if (
			$final_sub == NULL 
			|| 
			(	$final_sub->pre_score < $submission['pre_score']
				|| $final_sub->pre_score * $final_sub->coefficient < $submission['pre_score'] * $submission['coefficient']
			)
		){
			$this->db->where(array(
				'is_final' => 1,
				'username' => $submission['username'],
				'assignment_id' => $submission['assignment_id'],
				'problem_id' => $submission['problem_id'],
			))->update('submissions', array('is_final'=>0));

			$arr['is_final'] = 1;
		}

		$this->db->where(array(
			'submit_id' => $submission['submit_id'],
			'username' => $submission['username'],
			'assignment_id' => $submission['assignment_id'],
			'problem_id' => $submission['problem_id']
		))->update('submissions', $arr);

		// update scoreboard:
		$this->load->model('scoreboard_model');
		$this->scoreboard_model->update_scoreboard($submission['assignment_id']);
	}

}