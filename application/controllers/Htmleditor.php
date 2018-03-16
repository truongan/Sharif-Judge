<?php
/**
 * Wecode Judge
 * @file Htmleditor.php
 * @author TruongAn PhamNguyen
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Htmleditor extends CI_Controller
{
    public function __construct()
	{
		parent::__construct();
		if ( ! $this->user->logged_in()) // if not logged in
			redirect('login');
		if ( $this->user->level <= 2) // permission denied
			show_404();
	}
    public function index(){
        $this->twig->display('pages/htmleditor.twig', array());
    }
}

?>
