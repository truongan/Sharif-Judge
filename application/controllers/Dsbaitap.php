<?php

class Dsbaitap extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$user = $this->session->all_userdata();
		$this->load->model('user_model');
		$this->user_model->update_login_time($user['username']);	
		$this->load->model('xephang_model')->model('dsbaitap_model');
	}

	public function index()
	{
		$this->load->library('session');
		$user = $this->session->all_userdata();

		$this->db->select_max('pre_score');
		$this->db->select('problem');
        $this->db->where('username', $user['username']);
        $this->db->group_by('problem');
        $query = $this->db->get('submissions');
        $sub = $query->result_array();



		$this->db->select('*');
        $this->db->from('problems');
        $this->db->order_by('problems.id');         
        $query = $this->db->get();
        $baitaps = $query->result_array();

        foreach ($baitaps as $baitap => $key) {
        	foreach ($sub as $su) {
        		if($key['id'] == $su['problem']){
        			$baitaps[$baitap]['you_score'] = $su['pre_score'];
        		}
        	}
        }


        $this->db->select('*');
        $this->db->from('dsloaibt');        
        $query = $this->db->get();
        $loaibt = $query->result();

		$this->db->select('*');
		$this->db->from('problems');
		$this->db->join('baitaploai', 'problems.id = baitaploai.baitap_id', 'left');
		$this->db->join('dsloaibt', 'dsloaibt.loaibt_id = baitaploai.loaibt_id', 'left');
		$tables = $this->db->get()->result_array();

		foreach ($baitaps as $key => $value) {
			$baitaps[$key]['loaibt_name']='';
			foreach ($tables as $key1 => $value1) {
				if($value['id'] == $value1['id'])
					$baitaps[$key]['loaibt_name'] = $baitaps[$key]['loaibt_name'].','.$value1['loaibt_name'];
			}
		}

        $this->db->select('*');
        $this->db->from('baitaploai');        
        $query = $this->db->get();
        $baitaploai = $query->result();

		$data = array(
			'baitaps' => $baitaps,
			'loaibt'  => $loaibt,
			'baitaploai'  => $baitaploai
		);
		$this->twig->display('pages/dsbaitap.twig', $data); 
	}

	public function add_problem()
	{
		$data=array(
			'assignment' => '1',
			'name' => $this->input->post('name'),
			'difficulty' => $this->input->post('difficulty'),
			'score' => $this->input->post('score'),
			'time_competition' => $this->input->post('time_competition'),
			'c_time_limit' =>$this->input->post('c_time_limit'),
			'python_time_limit' =>$this->input->post('python_time_limit'),
			'java_time_limit' =>$this->input->post('java_time_limit'),
			'memory_limit' =>$this->input->post('memory_limit'),
			'allowed_languages' => implode(',', $this->input->post('select_languages'))
		);
		$this->dsbaitap_model->add_problems($data);

		$tag = $this->input->post('tag');
		$this->dsbaitap_model->add_tag_in_problem('0',$tag);
		// Preparing variables
		$assignments_root = $this->settings_model->get_setting('assignments_root').'/assignment_1';

		// Create assignment directory
		if ( ! file_exists($assignments_root) )
			mkdir($assignments_root, 0700);



		// Upload Tests (zip file)
		$this->load->library('upload');
		shell_exec('rm -f '.$assignments_root.'/*.zip');
		$config = array(
			'upload_path' => $assignments_root,
			'allowed_types' => 'zip',
		);
		$this->upload->initialize($config);
		$zip_uploaded = $this->upload->do_upload('tests_desc');

		$u_data = $this->upload->data();
		if ( $_FILES['tests_desc']['error'] === UPLOAD_ERR_NO_FILE )
			$this->messages[] = array(
				'type' => 'notice',
				'text' => "Notice: You did not upload any zip file for tests. If needed, upload by editing assignment."
			);
		elseif ( ! $zip_uploaded )
			$this->messages[] = array(
				'type' => 'error',
				'text' => "Error: Error uploading tests zip file: ".$this->upload->display_errors('', '')
			);
		else
			$this->messages[] = array(
				'type' => 'success',
				'text' => "Tests (zip file) uploaded successfully."
			);
		// Extract Tests (zip file)
		if ($zip_uploaded) // if zip file is uploaded
		{
			// Create a temp directory
			$tmp_dir_name = "tmp_directory";
			$tmp_dir = "$assignments_root/$tmp_dir_name";
			shell_exec("rm -rf $tmp_dir; mkdir $tmp_dir;");

			$query = $this->db->select_max('id')->get('problems')->row_array();
			$problem_id = $query['id'];
			// $problems_dir = "$assignments_root/p$problem_id";
			// shell_exec("rm -rf $problems_dir; mkdir $problems_dir;");

			// Extract new test cases and descriptions in temp directory
			$this->load->library('unzip');
			// $this->unzip->allow(array('txt', 'cpp', 'html', 'md', 'pdf', 'c', ));
			$extract_result = $this->unzip->extract($u_data['full_path'], $tmp_dir);


			shell_exec("cd $tmp_dir; mv * p$problem_id");
			// Remove the zip file
			unlink($u_data['full_path']);

			if ( $extract_result )
			{
				// Remove previous test cases and descriptions
				// shell_exec("cd $assignments_root;"
				// 	." rm -rf */in; rm -rf */out; rm -f */tester.cpp; rm -f */tester.executable;"
				// 	." rm -rf */template.*;"
				// 	." rm -f */desc.html; rm -f */desc.md; rm -f */*.pdf;");
				// if (glob("$tmp_dir/*.pdf"))
				// 	shell_exec("cd $assignments_root; rm -f *.pdf");
				// Copy new test cases from temp dir
				shell_exec("cd $assignments_root; cp -R $tmp_dir_name/* $assignments_root;");
				$this->messages[] = array(
					'type' => 'success',
					'text' => 'Tests (zip file) extracted successfully.'
				);
			}
			else
			{
				$this->messages[] = array(
					'type' => 'error',
					'text' => 'Error: Error extracting zip archive.'
				);
				foreach($this->unzip->errors_array() as $msg)
					$this->messages[] = array(
						'type' => 'error',
						'text' => " Zip Extraction Error: ".$msg
					);
			}
			// Remove temp directory
			shell_exec("rm -rf $tmp_dir");
		}



		// Create problem directories and parsing markdown files

		for ($i=1; $i <= $this->input->post('number_of_problems'); $i++)
		{
			if ( ! file_exists("$assignment_dir/p$i"))
				mkdir("$assignment_dir/p$i", 0700);
			elseif (file_exists("$assignment_dir/p$i/desc.md"))
			{
				$this->load->library('parsedown');
				$html = $this->parsedown->parse(file_get_contents("$assignment_dir/p$i/desc.md"));
				file_put_contents("$assignment_dir/p$i/desc.html", $html);
			}
		}



		redirect('dsbaitap/index'); 
	}
	public function edit_problems()
	{
		$id = $this->input->post('edit_problems_id');
		$data = array(
			'id' => $id,
			'assignment' => '1',
			'name' => $this->input->post('edit_problems_name'),
			'difficulty' => $this->input->post('edit_problems_difficulty'),
			'score' => $this->input->post('edit_problems_score'),
			'time_competition' => $this->input->post('edit_time_competition'),
			'c_time_limit' =>$this->input->post('edit_problems_c_time_limit'),
			'python_time_limit' =>$this->input->post('edit_problems_python_time_limit'),
			'java_time_limit' =>$this->input->post('edit_problems_java_time_limit'),
			'memory_limit' =>$this->input->post('edit_problems_memory_limit'),
			'allowed_languages' => implode(',', $this->input->post('edit_problems_select_languages'))
		);
		$this->dsbaitap_model->edit_problems($id, $data);
		$tag = $this->input->post('edit_problems_tag');
		$this->dsbaitap_model->add_tag_in_problem($id,$tag);

		// Preparing variables
		$assignments_root = $this->settings_model->get_setting('assignments_root').'/assignment_1';

		// Create assignment directory
		if ( ! file_exists($assignments_root) )
			mkdir($assignments_root, 0700);



		// Upload Tests (zip file)
		$this->load->library('upload');
		shell_exec('rm -f '.$assignments_root.'/*.zip');
		$config = array(
			'upload_path' => $assignments_root,
			'allowed_types' => 'zip',
		);
		$this->upload->initialize($config);
		$zip_uploaded = $this->upload->do_upload('tests_desc');

		$u_data = $this->upload->data();
		if ( $_FILES['tests_desc']['error'] === UPLOAD_ERR_NO_FILE )
			$this->messages[] = array(
				'type' => 'notice',
				'text' => "Notice: You did not upload any zip file for tests. If needed, upload by editing assignment."
			);
		elseif ( ! $zip_uploaded )
			$this->messages[] = array(
				'type' => 'error',
				'text' => "Error: Error uploading tests zip file: ".$this->upload->display_errors('', '')
			);
		else
			$this->messages[] = array(
				'type' => 'success',
				'text' => "Tests (zip file) uploaded successfully."
			);
		// Extract Tests (zip file)
		if ($zip_uploaded) // if zip file is uploaded
		{
			// Create a temp directory
			$tmp_dir_name = "tmp_directory";
			$tmp_dir = "$assignments_root/$tmp_dir_name";
			shell_exec("rm -rf $tmp_dir; mkdir $tmp_dir;");

			$problem_id = $id;
			// $problems_dir = "$assignments_root/p$problem_id";
			shell_exec("cd $assignments_root; rm -rf p$problem_id;");

			// Extract new test cases and descriptions in temp directory
			$this->load->library('unzip');
			// $this->unzip->allow(array('txt', 'cpp', 'html', 'md', 'pdf', 'c', ));
			$extract_result = $this->unzip->extract($u_data['full_path'], $tmp_dir);


			shell_exec("cd $tmp_dir; mv * p$problem_id");
			// Remove the zip file
			unlink($u_data['full_path']);

			if ( $extract_result )
			{
				// Remove previous test cases and descriptions
				// shell_exec("cd $assignments_root;"
				// 	." rm -rf */in; rm -rf */out; rm -f */tester.cpp; rm -f */tester.executable;"
				// 	." rm -rf */template.*;"
				// 	." rm -f */desc.html; rm -f */desc.md; rm -f */*.pdf;");
				// if (glob("$tmp_dir/*.pdf"))
				// 	shell_exec("cd $assignments_root; rm -f *.pdf");
				// Copy new test cases from temp dir
				shell_exec("cd $assignments_root; cp -R $tmp_dir_name/* $assignments_root;");
				$this->messages[] = array(
					'type' => 'success',
					'text' => 'Tests (zip file) extracted successfully.'
				);
			}
			else
			{
				$this->messages[] = array(
					'type' => 'error',
					'text' => 'Error: Error extracting zip archive.'
				);
				foreach($this->unzip->errors_array() as $msg)
					$this->messages[] = array(
						'type' => 'error',
						'text' => " Zip Extraction Error: ".$msg
					);
			}
			// Remove temp directory
			shell_exec("rm -rf $tmp_dir");
		}
		redirect('dsbaitap/index'); 
	}

	public function delete_problems()
	{
		$problems_id = $this->input->post('delete_problems_id');
		$this->dsbaitap_model->delete_problem($problems_id);
		redirect('dsbaitap/index');
	}

	public function add_tag()
	{
		$data=array(
			'loaibt_name' => $this->input->post('tagname')
		);
		$this->db->insert('dsloaibt',$data);
		redirect('dsbaitap/index');
	}
	public function edit_tag()
	{
		$data=array(
			'loaibt_id' => $this->input->post('edit_tag_id'),
			'loaibt_name' => $this->input->post('edit_tag_name')
		);
		$this->dsbaitap_model->edit_tag($data);
		redirect('dsbaitap/index');
	}
	public function delete_tag()
	{
		$tag_id = $this->input->post('delete_tag_id');
		$this->dsbaitap_model->delete_tag($tag_id);
		redirect('dsbaitap/index');
	}


	public function filter($id)
	{
		$this->db->select('*');
		$this->db->from('problems');
		$this->db->join('baitaploai', 'problems.id = baitaploai.baitap_id', 'left');
		$this->db->where('baitaploai.loaibt_id',$id);
		$this->db->order_by('problems.id');
		$query = $this->db->get();
		$baitaps = $query->result_array();
		$this->db->select('*');
        $this->db->from('dsloaibt');        
        $query = $this->db->get();
        $loaibt = $query->result();
		$data = array(
			'baitaps' => $baitaps,
			'loaibt'  => $loaibt
		);
		$this->twig->display('pages/dsbaitap.twig', $data);
	}

}