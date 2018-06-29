<?php
/**
 * Sharif Judge online judge
 * @file Assignment_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Problem_model extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
	}

    public function new_problem_id(){
		$max = ($this->db->select_max('id', 'max_id')->get('problems')->row()->max_id) + 1;

		$assignments_root = rtrim($this->settings_model->get_setting('assignments_root'), '/');
		while (file_exists($assignments_root.'/problems/'.$max)){
			$max++;
		}

		return $max;
	}
	

	public function all_problems(){
		$result = $this->db->order_by('id', 'DESC')->get('problems')->result_array();
		$problems = array();
		foreach ($result as $item)
		{
			$problems[$item['id']] = $item;
		}

		return $problems;
	}

	public function get_problem($id = NULL){
		$a =  $this->db->get_where('problems', array('id' => $id))->row_array();
		$a['languages'] = $this->get_languages($id);
		return $a;
	}

	public function get_languages($id = NULL){
		return $this->db->from('languages')
						->join('problem_language', 'languages.id = problem_language.language_id')
						->where('problem_language.problem_id' , $id)
						->get()->result_array();
	}

	public function add_problem(){

		//Now add new problems:
		$names = $this->input->post('name');
		$scores = $this->input->post('score');
		$c_tl = $this->input->post('c_time_limit');
		$py_tl = $this->input->post('python_time_limit');
		$java_tl = $this->input->post('java_time_limit');
		$ml = $this->input->post('memory_limit');
		$ft = $this->input->post('languages');
		$dc = $this->input->post('diff_cmd');
		$da = $this->input->post('diff_arg');
		$uo = $this->input->post('is_upload_only');
		if ($uo === NULL)
			$uo = array();
		for ($i=1; $i<=$this->input->post('number_of_problems'); $i++)
		{
			$items = explode(',', $ft[$i-1]);
			$ft[$i-1] = '';
			foreach ($items as $item){
				$item = trim($item);
				$item2 = strtolower($item);
				$item = ucfirst($item2);
				if ($item2 === 'python2')
					$item = 'Python 2';
				elseif ($item2 === 'python3')
					$item = 'Python 3';
				elseif ($item2 === 'pdf')
					$item = 'PDF';
				$item2 = strtolower($item);
				if ( ! in_array($item2, array('c','c++','python 2','python 3','java','zip','pdf')))
					continue;
				// If the problem is not Upload-Only, its language should be one of {C,C++,Python 2, Python 3,Java}
				if ( ! in_array($i, $uo) && ! in_array($item2, array('c','c++','python 2','python 3','java')) )
					continue;
				$ft[$i-1] .= $item.",";
			}
			$ft[$i-1] = substr($ft[$i-1],0,strlen($ft[$i-1])-1); // remove last ','
			$problem = array(
				'assignment' => $id,
				'id' => $i,
				'name' => $names[$i-1],
				'score' => $scores[$i-1],
				'is_upload_only' => in_array($i,$uo)?1:0,
				'c_time_limit' => $c_tl[$i-1],
				'python_time_limit' => $py_tl[$i-1],
				'java_time_limit' => $java_tl[$i-1],
				'memory_limit' => $ml[$i-1],
				'allowed_languages' => $ft[$i-1],
				'diff_cmd' => $dc[$i-1],
				'diff_arg' => $da[$i-1],
			);
			$this->db->insert('problems', $problem);
		}

	}
}

?>