<?php
class AuthMiddleware
{
    public function check_login()
    {
        $CI = &get_instance();
        if (!$CI->session->userdata('isLoggedIn') && $CI->router->fetch_class() != 'auth') {
            redirect('/login');
        }
    }
}
