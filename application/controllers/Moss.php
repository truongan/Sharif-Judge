<?php
/**
 * Sharif Judge online judge
 * @file Moss.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Moss extends CI_Controller
{


	public function __construct()
	{
		parent::__construct();
		$this->user->login_redirect();
		if ($this->user->level <= 1) // permission denied
			show_404();

		$this->load->model('submit_model');
	}


	// ------------------------------------------------------------------------
	
	
	public function index($assignment_id = FALSE)
	{
		if ($assignment_id === FALSE)
		show_404();
		
		$this->form_validation->set_rules('detect', 'detect', 'required');
		
		if ($this->form_validation->run() /// Don't know why form submit from update mosss ID can pass this check
										/// So I add the below check just to be sure.
			&& $this->input->post('detect') == 'detect'
		)
		{
			$this->_detect($assignment_id);
		}

		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'moss_userid' => $this->settings_model->get_setting('moss_userid'),
			'moss_assignment' => $this->assignment_model->assignment_info($assignment_id),
			'update_time' => $this->assignment_model->get_moss_time($assignment_id)
		);

		$data['moss_problems'] = array();

		foreach ($this->assignment_model->all_problems($assignment_id) as $pid => $problemas){
			$data['moss_problems'][$pid] = NULL;
			
			$path = $this->submit_model->get_path('', $assignment_id, $pid) .'/' ;
			// var_dump($path . "moss_link.txt"); die();
			if (file_exists($path . "moss_link.txt") && file_get_contents($path . "moss_link.txt") != ''){
				$data['moss_problems'][$pid] = shell_exec("tail -n1 $path/moss_link.txt");
				shell_exec("rm $path/moss_running");
			} else if (file_exists($path . "moss_running")){
				$data['moss_problems'][$pid] = "submission submitted to moss, awaiting respone, please be patience";
			}
		}
		$this->twig->display('pages/admin/moss.twig', $data);
	}


	// ------------------------------------------------------------------------


	public function update($assignment_id = FALSE)
	{
		if ($assignment_id === FALSE)
			show_404();
		$userid = $this->input->post('moss_userid');
		$this->settings_model->set_setting('moss_userid', $userid);
		$moss_original = trim( file_get_contents(rtrim($this->settings_model->get_setting('tester_path'), '/').'/moss_original') );
		$moss_path = rtrim($this->settings_model->get_setting('tester_path'), '/').'/moss';
		file_put_contents($moss_path, str_replace('MOSS_USER_ID', $userid, $moss_original));
		echo(shell_exec("chmod +x {$moss_path}"));
		
		$this->index($assignment_id);
	}


	// ------------------------------------------------------------------------


	private function _detect($assignment_id = FALSE)
	{
		if ($assignment_id === FALSE)
			show_404();

		$lang = $this->language_model->all_languages();

		$this->load->model('submit_model');
		$assignments_path = rtrim($this->settings_model->get_setting('assignments_root'), '/');
		$tester_path = rtrim($this->settings_model->get_setting('tester_path'), '/');
		shell_exec("chmod +x {$tester_path}/moss");
		$items = $this->submit_model->get_final_submissions($assignment_id, $this->user->level, $this->user->username);
		$groups = array();
		foreach ($items as $item) {
			if (!isset($groups[$item['problem_id']]))
				$groups[$item['problem_id']] = array($item);
			else
				array_push($groups[$item['problem_id']], $item);
		}
		foreach ($groups as $problem_id => $group) {
			$list = '';
			$assignment_path = $assignments_path."/assignment_{$assignment_id}";
			foreach ($group as $item){
				$list .= "problem_{$problem_id}/{$item['username']}/{$item['file_name']}." .$lang[$item['language_id']]->extension . " ";
			}
			// echo "list='$list'; cd $assignment_path; $tester_path/moss \$list 2>&1 >p{$problem_id}/moss_link.txt &"; 			die();

			exec("list='$list'; cd $assignment_path; $tester_path/moss \$list > problem_{$problem_id}/moss_link.txt  2>&1 &");
			exec("cd $assignment_path/problem_{$problem_id}; touch moss_running");

		}
		$this->assignment_model->set_moss_time($assignment_id);
	}


}
