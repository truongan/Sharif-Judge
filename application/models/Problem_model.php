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

	public function problem_info($id = NULL){
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

	public function get_directory_path($id = NULL){
		if ($id === NULL) return NULL;
		$assignments_root = rtrim($this->settings_model->get_setting('assignments_root'),'/');
		$problem_dir = $assignments_root . "/problems/".$id;
		return $problem_dir;
	}
	public function get_description($id = NULL){
		$problem_dir = $this->get_directory_path($id);
		$result =  array(
			'description' => '<p>Description not found</p>',
			'has_pdf' => glob("$problem_dir/*.pdf") != FALSE
			,'has_template' => glob("$problem_dir/template.cpp") != FALSE
		);
		
		$path = "$problem_dir/desc.html";

		if (file_exists($path))
			$result['description'] = file_get_contents($path);

		return $result;
	}

	public function get_template_path($problem_id = NULL){
		$pattern1 = rtrim($this->problem_model->get_directory_path($problem_id)
		."/template.public.cpp");

		$template_file = glob($pattern1);
		if ( ! $template_file ){
			$pattern = rtrim($this->problem_model->get_directory_path($problem_id)
						."/template.cpp");

			$template_file = glob($pattern);
		}
		return $template_file;
	}
	// ------------------------------------------------------------------------
	/**
	 * Save Problem Description
	 *
	 * Saves (Adds/Updates) problem description (html or markdown)
	 *
	 * @param $assignment_id
	 * @param $problem_id
	 * @param $text
	 * @param $type
	 */
	public function save_problem_description($problem_id, $text, $type = 'html')
	{
		$problem_dir = $this->get_directory_path($problem_id);

		if ($type === 'html')
		{
			if (file_put_contents("$problem_dir/desc.html", $text) ) {
				return true;
			} else return false;
		}
	}

	// ------------------------------------------------------------------------
	public function add_problem(){

		$id = $this->new_problem_id();
		//Now add new problems:
		$name = $this->input->post('problem_name');
		$admin_note = $this->input->post('admin_note');
		$dc = $this->input->post('diff_cmd');
		$da = $this->input->post('diff_arg');
		//$uo = $this->input->post('is_upload_only');

		$problem = array(
			'id' => $id,
			'name' => $name,
			//'is_upload_only' => $uo,
			'diff_cmd' => $dc[$i-1],
			'diff_arg' => $da[$i-1],
		);
		$this->db->insert('problems', $problem);
		
		for ($i=1; $i<=$this->input->post('language_id'); $i++)
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
		}
		

	}
}

?>