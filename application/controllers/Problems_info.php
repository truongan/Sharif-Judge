<?php
/**
 * Sharif Judge online judge
 * @file Profile.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Problems_info extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
	}

	public function index($problems_id)
	{
		$data=array();
		$this->twig->display('pages/problems_info.twig', $data);
	}

}