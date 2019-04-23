<?php
/**
 * Sharif Judge online judge
 * @file Queueprocess.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Queueprocess extends CI_Controller
{


	public function __construct()
	{
		parent::__construct();
		// This controller should not be called from a browser
		if ( ! is_cli() )
			show_404();
		$this->load->model('queue_model')->model('submit_model');
	}




	// ------------------------------------------------------------------------




	/**
	 * This is the main function for processing the queue
	 * This function judges queue items one by one
	 */
	public function run()
	{

		// Set correct base_url
		// Because we are in cli mode, base_url is not available, and we get
		// it from an environment variable that we have set in shj_helper.php
		$this->config->set_item('base_url', getenv('SHJ_BASE_URL'));
	
		$queue_item = $this->queue_model->get_first_item();
		if ($queue_item === NULL) {
			$this->settings_model->set_setting('queue_is_working', '0');
			exit;
		}

		//To pause the queue when debugging, just exit here
		// exit;

		if ($this->settings_model->get_setting('queue_is_working'))
		 	exit;

		$this->settings_model->set_setting('queue_is_working', '1');



		do { // loop over queue items

			$submit_id = $queue_item['submit_id'];
			$username = $queue_item['username'];
			$assignment = $queue_item['assignment'];
			$problem = $this->problem_model->problem_info($queue_item['problem']);

			
			$type = $queue_item['type'];  // $type can be 'judge' or 'rejudge'
			
			$submission = $this->submit_model->get_submission( $assignment, $submit_id);

			$language = $this->problem_model->all_languages($problem['id'])[$submission['language_id']];

			$file_extension = $language->extension;

			$raw_filename = $submission['file_name'];

			$tester_path = rtrim($this->settings_model->get_setting('tester_path'), '/');
			
			$problemdir = $this->problem_model->get_directory_path($problem['id']);
			$userdir = $this->submit_model->get_path($username, $assignment, $problem['id']);
			

			$op1 = $this->settings_model->get_setting('enable_log');


			$time_limit = $language->time_limit/1000;
			$time_limit = round($time_limit, 3);
			$time_limit_int = floor($time_limit) + 1;
			$memory_limit = $language->memory_limit;
			$diff_cmd = $problem['diff_cmd'];
			$diff_arg = $problem['diff_arg'];
			$output_size_limit = $this->settings_model->get_setting('output_size_limit') * 1024;

			$cmd = "bash && cd $tester_path;\n./tester.sh $problemdir $userdir ".escapeshellarg($raw_filename)." $file_extension $time_limit $time_limit_int $memory_limit $output_size_limit $diff_cmd '$diff_arg' $op1 ";

			file_put_contents($userdir.'/log', $cmd);

			///////////////////////////////////////
			// Running tester (judging the code) //
			///////////////////////////////////////
			putenv('LANG=en_US.UTF-8');
			putenv('PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/games:/usr/local/games');
			putenv('APP_ENV=local');
			$output = trim(shell_exec($cmd));


			// Deleting the jail folder, if still exists
			//shell_exec("cd $tester_path; rm -rf jail*");

			echo $output;

			// Saving judge result

			shell_exec("mv $userdir/result.html $userdir/result-{$submit_id}.html");
			shell_exec("mv $userdir/log $userdir/log-{$submit_id}");

			if (is_numeric($output)) {
				$submission['pre_score'] = $output;
				$submission['status'] = 'SCORE';
			}
			else {
				$submission['pre_score'] = 0;
				$submission['status'] = $output;
			}

			//reconnect to database incase we have run test for a long time.
			$this->db->reconnect();

			// Save the result
			$this->queue_model->save_judge_result_in_db($submission, $type);

			// Remove the judged item from queue
			$this->queue_model->remove_item($username, $assignment, $problem['id'], $submit_id);

			// Get next item from queue
			$queue_item = $this->queue_model->get_first_item();

		}while($queue_item !== NULL && $this->settings_model->get_setting('queue_is_working'));

		$this->settings_model->set_setting('queue_is_working', '0');

	}

}
