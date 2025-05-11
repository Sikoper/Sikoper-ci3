<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kategori extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Kategori_model');
    }
    public function index()
    {
        $parser = [
            'judul' => "<i class='fa fa-list'></i> Jenis Tabungan",
            'isi'   => $this->load->view('kategori/index', '', TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function add()
    {
        $parser = [
            'judul' => "<i class='fa fa-list'></i> Jenis Tabungan",
            'isi'   => $this->load->view('kategori/addForm', '', TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }
}
