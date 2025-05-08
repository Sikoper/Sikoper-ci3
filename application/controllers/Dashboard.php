<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {
	public function index()
	{
        $parser = [
            'judul' => 'Selamat Datang!',
            'isi'   => $this->load->view('home/index', '', TRUE)
        ];
		$this->parser->parse('templates/main', $parser);
	}
}
