<?php
/**
 * Sharif Judge online judge
 * @file Problems.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Problems extends CI_Controller
{

	private $all_assignments;


	// ------------------------------------------------------------------------


	public function __construct()
	{
		parent::__construct();
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');
		$this->load->library('session');
		$user = $this->session->all_userdata();
		$this->load->model('user_model');
		$this->user_model->update_login_time($user['username']);	
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
	 * @param int $assignment_id
	 * @param int $problem_id
	 */
	public function index($assignment_id = NULL, $problem_id = 1)
	{
		$this->db->select('*');
        $this->db->from('problems');
        $this->db->where('id',$problem_id);         
        $query = $this->db->get()->row_array();
        // var_dump($query);die;
		$assignments_root = rtrim($this->settings_model->get_setting('assignments_root'),'/');
		$problem_dir = "$assignments_root/assignment_1/p{$problem_id}";
		$data['problem'] = array(
			'id' => $query['id'],
			'description' => '<p>Description not found</p>',
			'allowed_languages' => explode(",", $query['allowed_languages']),
			'has_pdf' => glob("$problem_dir/*.pdf") != FALSE,
			'has_template' => glob("$problem_dir/template.cpp") != FALSE,
			'time_competition' => $query['time_competition'],
		);

		$path = "$problem_dir/desc.html";
		if (file_exists($path))
			$data['problem']['description'] = file_get_contents($path);

		$this->twig->display('pages/baitap.twig', $data);
	}

	public function pdf($assignment_id, $problem_id = NULL)
	{
		// Find pdf file
		if ($problem_id === NULL)
			$pattern = rtrim($this->settings_model->get_setting('assignments_root'),'/')."/assignment_{$assignment_id}/*.pdf";
		else
			$pattern = rtrim($this->settings_model->get_setting('assignments_root'),'/')."/assignment_{$assignment_id}/p{$problem_id}/*.pdf";
		$pdf_files = glob($pattern);
		if ( ! $pdf_files )
			show_error("File not found");

		// Download the file to browser
		$this->load->helper('download')->helper('file');
		$filename = shj_basename($pdf_files[0]);
		force_download($filename, file_get_contents($pdf_files[0]), TRUE);
	}
	
	public function edit($type = 'md', $assignment_id = NULL, $problem_id = 1)
	{
		if ($type !== 'html' && $type !== 'md' && $type !== 'plain')
			show_404();
		if ($this->user->level <= 1)
			show_404();
		switch($type)
		{
			case 'html':
				$ext = 'html'; break;
			case 'md':
				$ext = 'md'; break;
			case 'plain':
				$ext = 'html'; break;
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
		$this->form_validation->set_rules('text', 'text' ,''); /* todo: xss clean */
		if ($this->form_validation->run())
		{
			$this->assignment_model->save_problem_description($assignment_id, $problem_id, $this->input->post('text'), $ext);
			redirect('problems/'.$assignment_id.'/'.$problem_id);
		}
		$data['problem'] = array(
			'id' => $problem_id,
			'description' => ''
		);
		$path = rtrim($this->settings_model->get_setting('assignments_root'),'/')."/assignment_{$assignment_id}/p{$problem_id}/desc.".$ext;
		if (file_exists($path))
			$data['problem']['description'] = file_get_contents($path);
		$this->twig->display('pages/admin/edit_problem_'.$type.'.twig', $data);
	}


	public function testcase(){
		$input = $this->input->post('input_testcase');
		$problemid = $this->input->post('problemid');

		$assignments_root = rtrim($this->settings_model->get_setting('assignments_root'),'/');
		file_put_contents($assignments_root.'/input.txt', $input);

		putenv('LANG=en_US.UTF-8');
		putenv('PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/games:/usr/local/games');
		if(!glob($assignments_root.'/assignment_1/p'.$problemid.'/solution'))
		{
			$cmd = 'g++ '.$assignments_root.'/assignment_1/p'.$problemid.'/solution.cpp -o '.$assignments_root.'/assignment_1/p'.$problemid.'/solution 2>err;'.$assignments_root.'/assignment_1/p'.$problemid.'/solution<'.$assignments_root.'/input.txt';
		}
		else
			$cmd = $assignments_root.'/assignment_1/p'.$problemid.'/solution<'.$assignments_root.'/input.txt';
		$output = trim(shell_exec($cmd));
		echo $output;
	}

}
