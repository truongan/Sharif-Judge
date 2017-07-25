<?php
/**
 * Sharif Judge online judge
 * @file User_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Competition extends CI_Controller
{
	public $user_hero_a;
	public $user_hero_b;
	public function __construct()
	{
		parent::__construct();
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');
		$this->load->library('session');
		$user = $this->session->all_userdata();
		$this->load->model('user_model');
		$this->user_model->update_login_time($user['username']);			
		$this->load->model('competition_model');
		$this->load->library('session');
		$this->user_hero_a = $this->session->all_userdata();

	}
	public function index()
	{
		$this->load->library('session');
		$user = $this->session->all_userdata();
		$query = $this->db->select('*')->get_where('competition', array('id_hero_a' => $this->user_model->username_to_user_id($user['username'])))->result_array();

		$data = array();
		$dem = 0;
		$won = 0;
		$drawn = 0;
		$lost = 0;
		foreach ($query as $key => $value) {
			$user_b = $this->user_model->user_id_to_username($value['id_hero_b']);
			if($value['tiso'] == '0')
				$status = 'Fighting';
			else if($value['tiso'] == '10')
			{
				$won++;
				$status = 'WON';
			}
			else if($value['tiso'] == '11')
			{
				$drawn++;
				$status = 'DRAWN';
			}
			else
			{
				$lost++;
				$status = 'LOST';
			}
			$data['coms'][$dem]['opponent'] = $user_b;
			$data['coms'][$dem]['status'] = $status;
			$data['coms'][$dem]['xp'] = $value['xp_hero_a'];
			$dem++;
		}

		$query = $this->db->select('*')->get_where('competition', array('id_hero_b' => $this->user_model->username_to_user_id($user['username'])))->result_array();
		
		foreach ($query as $key => $value) {
			$user_b = $this->user_model->user_id_to_username($value['id_hero_a']);
			if($value['tiso'] == '0')
				$status = 'Fighting';
			else if($value['tiso'] == '10')
			{
				$lost++;
				$status = 'LOST';
			}
			else if($value['tiso'] == '11')
			{
				$drawn++;
				$status = 'DRAWN';
			}
			else
			{
				$won++;
				$status = 'WON';
			}
			$data['coms'][$dem]['opponent'] = $user_b;
			$data['coms'][$dem]['status'] = $status;
			$data['coms'][$dem]['xp'] = $value['xp_hero_b'];
			$dem++;
		}
		$data['won'] = $won;
		$data['drawn'] = $drawn;
		$data['lost'] = $lost;
		$data['total'] = $dem;
		$this->twig->display('pages/competition.twig', $data);
	}
	public function get_current_controller()
	{
		var_dump($this->uri->segment(1));
	}
	public function validate_user(){
		$this->db->select('*');
		$this->db->from('users');
		$this->db->where('username',$this->input->post('name_user'));
		$this->user_hero_b = $this->db->get()->row_array();

		$this->load->library('session');
		$user = $this->session->all_userdata();
		$userid = $this->user_model->username_to_user_id($user['username']);

		$users = $this->user_model->get_all_users();
		$now = shj_now_str();
		foreach ($users as $key => $value) {
			if( strtotime($now) - strtotime($value['last_login_time']) < 7200)
				$users[$key]['status'] = '1';
			else
				$users[$key]['status'] = '0';
		}
		if($this->user_hero_b)
		{
			if($this->user_hero_a['username']==$this->user_hero_b['username'])
			{
				echo "you";
				return true;
			}
			elseif($this->user_hero_b['status']=='0')
			{
				echo 'off';
				return true;
			}
			$check_user = $this->db->select('*')->get('competition')->result_array();
			foreach ($check_user as $key) {
				if(($key['tiso']=='0' and ($key['id_hero_a'] == $userid or $key['id_hero_b'] == $userid)) 
				or ($key['tiso']=='0' and ($key['id_hero_a'] == $this->user_hero_b['id'] or $key['id_hero_b'] == $this->user_hero_b['id'])))
				{
					echo 'exist';
					return true;
				}
			}
			echo 'onl';
		}
		else
			echo 'no';
	}

	public function insert_fight_database()
	{
		$this->db->select('*');
		$this->db->from('users');
		$this->db->where('username',$this->input->post('fight_username'));
		$this->user_hero_b = $this->db->get()->row_array();

		$now = shj_now_str();
		$data = array(
			'id_hero_a' => $this->user_model->username_to_user_id($this->user_hero_a['username']),
			'id_hero_b' => $this->user_hero_b['id'],
			'time' 		=> $now
		);
		$this->competition_model->add_com($data);
	}

	public function message_fight()
	{	
		if ( ! $this->input->is_ajax_request() )
			show_404();
		$time  = $this->input->post('time');
		if ($time === NULL)
			echo 'no';
		$this->load->library('session');
		$user = $this->session->all_userdata();
		$com = $this->competition_model->have_new_competition(strtotime($time));
		if($user['username'] == $this->user_model->user_id_to_username($com['id_hero_b']))
			echo $this->user_model->user_id_to_username($com['id_hero_a']);
		else
			echo 'no';
	}

	public function check_ket_qua()
	{

		$this->db->select('*');
		$this->db->from('submissions');
		$this->db->join('competition', 'submissions.problem = competition.problem_id');
		$this->db->where(array('tiso'=>'0', 'check_com'=>'1'));
		$query = $this->db->get()->result_array();
		foreach ($query as $key => $value) {
			if($value['problem_score'] == $value['pre_score'])
			{
				if($this->user_model->username_to_user_id($value['username']) == $value['id_hero_a'])
				{
					$data=array(
						'xp_hero_a' => $value['pre_score'] * 1.5,
						'tiso' => '10',
					);
					$this->db->where(array('tiso'=>'0', 'id_hero_a'=>$value['id_hero_a']))->update('competition',$data);
					echo $value['username'];
					return true;
				}
				if($this->user_model->username_to_user_id($value['username']) == $value['id_hero_b'])
				{
					$data=array(
						'xp_hero_b' => $value['pre_score'] * 1.5,
						'tiso' => '10',
					);
					$this->db->where(array('tiso'=>'0', 'id_hero_b'=>$value['id_hero_b']))->update('competition',$data);
					echo $value['username'];
					return true;
				}
			}
		}
		echo "no";
	}

	// public function check_ket_qua_hoa()
	// {
	// 	$data=array(
	// 		'tiso' => '111',
	// 	);
	// 	$this->db->where(array('tiso'=>'0', 'id_hero_a'=>$value['id_hero_a']))->update('competition',$data);
	// }
	
	public function insert_answer_yes(){
		$this->db->select('id');
		$this->db->from('problems');
		$problems = $this->db->get()->result_array();
		$section = array_rand($problems);
		$problemsid = $problems[$section]['id'];

		$user_a = $this->user_model->username_to_user_id($this->input->post('username_fight_yes_no'));
		$data = array(
			'answer_b'   => '1',
			'problem_id' => $problemsid,
		);
		$this->db->where(array('id_hero_a'=>$user_a, 'answer_b'=>'null'))->update('competition', $data);
		echo 'problems/index/1/'.$problemsid;
	}

	public function delete_com(){
		$user_a = $this->input->post('userid');

		$data_com = $this->db->get_where('competition', array('id_hero_a'=>$user_a))->row_array();
		$user_b = $this->user_model->user_id_to_username($data_com['id_hero_b']);
		$this->db->where(array('id_hero_a'=>$user_a, 'answer_b'=>'null'))->delete('competition');
		echo $user_b;
	}

	public function check_yes_no()
	{
		$this->load->library('session');
		$user = $this->session->all_userdata();
		$userid = $this->user_model->username_to_user_id($user['username']);
		$user_com = $this->db->get_where('competition', array('id_hero_a'=>$userid, 'tiso'=>'0'))->row_array();
		if($user_com['answer_b'] == '1')
		{
			echo 'problems/index/1/'.$user_com['problem_id'];
		}
		else 
			echo $userid;
	}

}
