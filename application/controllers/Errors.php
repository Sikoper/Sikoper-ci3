<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Errors extends CI_Controller
{
    public function error_404()
    {
        $this->output->set_status_header('404');
        $this->load->view('errors/error_404');
        return;
    }

    public function error_403()
    {
        $this->output->set_status_header('403');
        $this->load->view('errors/error_403');
        return;
    }
}
