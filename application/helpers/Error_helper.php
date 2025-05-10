<?php

function show_custom_404()
{
    $CI = &get_instance();
    $CI->output->set_status_header(404);
    $CI->load->view('errors/error_404');
}

function show_403()
{
    $CI = &get_instance();
    $CI->output->set_status_header(403);
    $CI->load->view('errors/error_403');
}

