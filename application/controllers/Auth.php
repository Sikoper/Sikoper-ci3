<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Users_model');
    }
    public function index()
    {
        $rememberToken = get_cookie('remember_token');
        if ($rememberToken) {
            $user = $this->db->get_where('tbuser', ['remember_token' => $rememberToken])->row();
            if ($user) {
                $this->session->set_userdata([
                    'isLoggedIn' => true,
                    'nama'       => $user->nama,
                    'uuid'       => $user->uuid,
                    'level'      => $user->level,
                ]);
            }
        }
        if ($this->session->userdata('isLoggedIn')) {
            redirect('/');
            exit;
        }
        $this->load->view('auth/login');
    }

    public function login()
    {
        if ($this->input->is_ajax_request()) {
            $username = $this->input->post('username');
            $password = $this->input->post('password');
            $remember_me = $this->input->post('remember_me');

            $this->form_validation->set_rules('username', 'Username', 'required');
            $this->form_validation->set_rules('password', 'Password', 'required');

            if ($this->form_validation->run() == FALSE) {
                $msg = [
                    'error' => [
                        'errorUserName' => form_error('username'),
                        'errorPassword' => form_error('password'),
                    ]
                ];
            } else {
                $auth = $this->Users_model->login($username, $password);

                if ($auth) {
                    $this->session->set_userdata([
                        'isLoggedIn' => true,
                        'id'       => $auth->id,
                        'nama'       => $auth->nama,
                        'uuid'       => $auth->uuid,
                        'level'       => $auth->level,
                    ]);

                    if ($remember_me == 1) {
                        $randomBytes = random_bytes(32);
                        $token = hash('sha256', $randomBytes);

                        $user_id = $auth->id;
                        $this->db->where('id', $user_id);
                        $this->db->update('tbuser', ['remember_token' => $token]);

                        set_cookie('remember_token', $token, 86400 * 30);
                    }

                    $msg = ['success' => 'Login successful'];
                } else {
                    $msg = ['failed' => 'Username or password is incorrect'];
                }
            }

            echo json_encode($msg);
        }
    }

    public function logout()
    {
        $uuid = $this->session->userdata('uuid');

        if ($uuid) {
            $this->db->where('uuid', $uuid);
            $this->db->update('tbuser', ['remember_token' => NULL]);
            $this->session->set_userdata('isLoggedIn' == false);
        }

        $this->session->sess_destroy();
        delete_cookie('remember_token');

        redirect('/login');
    }
}
