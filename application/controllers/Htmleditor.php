<?php
/**
 * Wecode Judge
 * @file Htmleditor.php
 * @author TruongAn PhamNguyen
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Htmleditor extends CI_Controller
{
    public function index(){
        $this->twig->display('pages/htmleditor.twig', array());
    }

}

?>
