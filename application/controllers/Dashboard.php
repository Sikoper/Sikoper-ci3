<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Bunga_model');

        if (date('d') == '28') {
            $this->Bunga_model->checkAndRunBunga();
        }
    }
    public function index()
    {
        $parser = [
            'judul' => 'Selamat Datang!',
            'isi'   => $this->load->view('home/index', '', TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }
}
