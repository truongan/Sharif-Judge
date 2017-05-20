<?php

class Page extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
	}
	public function test()
	{
		$data = array();
		$this->twig->display('templates/base.twig', $data);
	}

	public function chuong()
	{
		$data = array();
		$this->twig->display('pages/chuong.twig', $data);
	}
	public function xephang()
	{
		$data = array();
		$this->twig->display('pages/xephang.twig', $data);
	}
	public function dsbaitap()
	{
		$data = array();
		$this->twig->display('pages/dsbaitap.twig', $data);
	}
	public function baitap()
	{
		$data = array();
		$this->twig->display('pages/baitap.twig', $data);
	}
	public function compete()
	{
		$data = array();
		$this->twig->display('pages/compete.twig', $data);
	}
	public function daubot()
	{
		$data = array();
		$this->twig->display('pages/daubot.twig', $data);
	}
	public function submit()
	{
		$data = array();
		$this->twig->display('pages/submit.twig', $data);
	}
	public function user()
	{
		$data = array();
		$this->twig->display('pages/admin/user.twig', $data);
	}


}
