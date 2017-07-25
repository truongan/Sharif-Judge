<?php
/**
 * Sharif Judge online judge
 * @file Users.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller
{


	public function __construct()
	{
		parent::__construct();
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');
		if ( $this->user->level <= 2) // permission denied
			show_404();
		$this->load->library('session');
		$user = $this->session->all_userdata();
		$this->load->model('user_model');
		$this->user_model->update_login_time($user['username']);	
	}

	public function index()
	{

		$data = array(
			'all_assignments' => $this->assignment_model->all_assignments(),
			'users' => $this->user_model->get_all_users()
		);
		$now = shj_now_str();
		foreach ($data['users'] as $key => $value) {
			if( strtotime($now) - strtotime($value['last_login_time']) < 7200 && $value['status'] == '1')
				$data['users'][$key]['status'] = '1';
			else
				$data['users'][$key]['status'] = '0';
		}

		$this->twig->display('pages/admin/users.twig', $data);
	}

	public function add_users()
	{
		$this->load->library('password_hash', array(8, FALSE));
		$data=array(
			'username' => $this->input->post('username'),
			'class' => $this->input->post('class'),
			'display_name' => $this->input->post('display_name'),
			'password' => $this->password_hash->HashPassword($this->input->post('password')),
			'email' =>$this->input->post('email'),
			'role' =>$this->input->post('role'),
		);
		$this->user_model->add_user($data);
		redirect('users/index');
	}
	public function edit_users()
	{
		$data=array(
			'username' => $this->input->post('edit_username'),
			'class' => $this->input->post('edit_class'),
			'display_name' => $this->input->post('edit_display_name'),
			'email' =>$this->input->post('edit_email'),
			'role' =>$this->input->post('edit_role'),
		);
		$this->user_model->edit_user($data);
		redirect('users/index');
	}

	public function delete_users()
	{
		$user_id = $this->input->post('delete_users_id');
		$this->user_model->delete_user($user_id);
		redirect('users/index');
	}

	/**
	 * Controller for deleting a user's submissions
	 * Called by ajax request
	 */
	public function delete_submissions()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
		$user_id = $this->input->post('user_id');
		if ( ! is_numeric($user_id) )
			$json_result = array('done' => 0, 'message' => 'Input Error');
		elseif ($this->user_model->delete_submissions($user_id))
			$json_result = array('done' => 1);
		else
			$json_result = array('done' => 0, 'message' => 'Deleting Submissions Failed');

		$this->output->set_header('Content-Type: application/json; charset=utf-8');
		echo json_encode($json_result);
	}




	// ------------------------------------------------------------------------




	/**
	 * Uses PHPExcel library to generate excel file of users list
	 */
	public function list_excel()
	{

		$now = shj_now_str(); // current time

		// Load PHPExcel library
		$this->load->library('phpexcel');

		// Set document properties
		$this->phpexcel->getProperties()->setCreator('Sharif Judge')
			->setLastModifiedBy('Sharif Judge')
			->setTitle('Sharif Judge Users')
			->setSubject('Sharif Judge Users')
			->setDescription('List of Sharif Judge users ('.$now.')');

		// Name of the file sent to browser
		$output_filename = 'sharifjudge_users';

		// Set active sheet
		$this->phpexcel->setActiveSheetIndex(0);
		$sheet = $this->phpexcel->getActiveSheet();

		// Add current time to document
		$sheet->fromArray(array('Time:',$now), null, 'A1', true);

		// Add header to document
		$header=array('#','User ID','Username','Display Name','Email','Role','First Login','Last Login');
		$sheet->fromArray($header, null, 'A3', true);
		$highest_column = $sheet->getHighestColumn();

		// Set custom style for header
		$sheet->getStyle('A3:'.$highest_column.'3')->applyFromArray(
			array(
				'fill' => array(
					'type' => PHPExcel_Style_Fill::FILL_SOLID,
					'color' => array('rgb' => '173C45')
				),
				'font'  => array(
					'bold'  => true,
					'color' => array('rgb' => 'FFFFFF'),
					//'size'  => 14
				)
			)
		);

		// Prepare user data (in $rows array)
		$users = $this->user_model->get_all_users();
		$i=0;
		$rows = array();
		foreach ($users as $user){
			array_push($rows, array(
				++$i,
				$user['id'],
				$user['username'],
				$user['display_name'],
				$user['email'],
				$user['role'],
				$user['first_login_time']===NULL?'Never':$user['first_login_time'],
				$user['last_login_time']===NULL?'Never':$user['last_login_time']
			));
		}

		// Add rows to document and set a background color of #7BD1BE
		$sheet->fromArray($rows, null, 'A4', true);
		// Add alternative colors to rows
		for ($i=4; $i<count($rows)+4; $i++){
			$sheet->getStyle('A'.$i.':'.$highest_column.$i)->applyFromArray(
				array(
					'fill' => array(
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => (($i%2)?'F0F0F0':'FAFAFA'))
					)
				)
			);
		}

		// Set text align to center
		$sheet->getStyle( $sheet->calculateWorksheetDimension() )
			->getAlignment()
			->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

		// Making columns autosize
		for ($i=2;$i<count($header);$i++)
			$sheet->getColumnDimension(chr(65+$i))->setAutoSize(true);

		// Set Border
		$sheet->getStyle('A4:'.$highest_column.$sheet->getHighestRow())->applyFromArray(
			array(
				'borders' => array(
					'outline' => array(
						'style' => PHPExcel_Style_Border::BORDER_THIN,
						'color' => array('rgb' => '444444'),
					),
				)
			)
		);

		// Send the file to browser

		// If class ZipArchive exists, export to excel2007, otherwise export to excel5
		if ( class_exists('ZipArchive') )
			$ext = 'xlsx';
		else
			$ext = 'xls';

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="'.$output_filename.'.'.$ext.'"');
		header('Cache-Control: max-age=0');
		$objWriter = PHPExcel_IOFactory::createWriter($this->phpexcel, ($ext==='xlsx'?'Excel2007':'Excel5'));
		$objWriter->save('php://output');
	}


}