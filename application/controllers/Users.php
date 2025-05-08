<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller {
	public function index()
	{
        $parser = [
            'judul' => "<i class='fa fa-users'></i> User",
            'isi'   => $this->load->view('users/index', '', TRUE)
        ];
		$this->parser->parse('templates/main', $parser);
	}

    public function add()
    {
        $parser = [
            'judul' => "<i class='fa fa-user-plus'></i> User",
            'isi'   => $this->load->view('users/addForm', '', TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }
}
