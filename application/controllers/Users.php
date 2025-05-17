<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Users extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Users_model');
        $allowed_roles = ['Admin'];
        $level = $this->session->userdata('level');
        if (!in_array($level, $allowed_roles)) {
            redirect('unauthorized_403');
        }
    }
    public function index()
    {
        $parser = [
            'judul' => "<i class='fa fa-user-lock'></i> User",
            'isi'   => $this->load->view('users/index', '', TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function fetchData()
    {
        if ($this->input->is_ajax_request() == true) {
            $list = $this->Users_model->get_datatables();
            $data = array();
            $no = $_POST['start'];
            $user_id = $this->session->userdata('id');

            foreach ($list as $field) {

                if ($field->level == 'Admin') {
                    continue;
                }

                $is_owner = ($user_id == $field->id);
                $no++;
                $row = array();

                $row[] = "<div class=\"text-center\">$no</div>";
                $row[] = $field->nama;
                $row[] = $field->username;
                $row[] = $field->level;
                $row[] = '
                <button type="button" class="btn btn-success" onclick="window.location=\'users/edit/' . $field->uuid . '\'"><i class="fa fa-edit"></i></button>
                <button class="btn btn-danger" onclick="deleteItem(\'' . $field->id . '\', \'' . $field->nama . '\')" ' . ($is_owner ? 'disabled' : '') . '><i class="fa fa-trash"></i></button>
            ';

                $data[] = $row;
            }

            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $this->Users_model->count_all(),
                "recordsFiltered" => $this->Users_model->count_filtered(),
                "data" => $data,
            );

            echo json_encode($output);
        } else {
            exit('Maaf data tidak bisa ditampilkan');
        }
    }

    public function add()
    {
        $parser = [
            'judul' => "<i class='fa fa-user-plus'></i> User",
            'isi'   => $this->load->view('users/addForm', '', TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function simpanData()
    {
        if ($this->input->is_ajax_request()) {
            $nama = $this->input->post('nama');
            $username = $this->input->post('username');
            $password = $this->input->post('password');
            $pegawai_id = $this->input->post('pegawai_id');

            $this->form_validation->set_rules('nama', 'Nama', 'required', [
                'required'     => 'Nama wajib diisi.'
            ]);
            $this->form_validation->set_rules('username', 'Username', 'required|regex_match[/^(?=.*[a-z])(?=.*\d)[a-z0-9_&.-]+$/]|is_unique[tbuser.username]', [
                'required'     => 'Username wajib diisi.',
                'regex_match'  => 'Username harus mengandung huruf kecil, angka, dan simbol opsional.',
                'is_unique'    => 'Username sudah digunakan.',
            ]);
            $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]|regex_match[/^(?=.*[A-Z])(?=.*\d).+$/]', [
                'required'     => 'Password wajib diisi.',
                'min_length'   => 'Password harus lebih dari 6 karakter.',
                'regex_match'  => 'Password harus mengandung setidaknya satu huruf kapital dan satu angka.'
            ]);

            if ($this->form_validation->run() == FALSE) {
                $msg = [
                    'error' => [
                        'errorNama' => form_error('nama'),
                        'errorUserName' => form_error('username'),
                        'errorPassword' => form_error('password'),
                    ]
                ];
            } else {
                $this->load->model('Pegawai_model');
                $pegawai = $this->Pegawai_model->update_user_token($pegawai_id, '0');
                if (!$pegawai) {
                    $msg = [
                        'error' => [
                            'errorPegawai' => 'Pegawai tidak ditemukan.'
                        ]
                    ];
                    echo json_encode($msg);
                    return;
                }
                $data = [
                    'uuid' => uniqid(),
                    'nama' => $nama,
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'level' => 'Pegawai',
                    'pegawai_id' => $pegawai_id,
                ];
                $this->Users_model->register($data);
                $msg = ['success' => 'Data berhasil ditambahkan.'];
            }

            echo json_encode($msg);
        }
    }

    public function delete()
    {
        if ($this->input->is_ajax_request()) {
            $id = $this->input->post('id');
            $user = $this->Users_model->get_data_by_id($id);
            $this->load->model('Pegawai_model');

            $pegawai = $this->Pegawai_model->update_user_token($user->pegawai_id, '1');
            if (!$pegawai) {
                $msg = [
                    'error' => [
                        'errorPegawai' => '-.'
                    ]
                ];
                echo json_encode($msg);
                return;
            }
            $this->Users_model->delete_data($id);

            $msg = [
                'success' => 'Data berhasil dihapus'
            ];

            echo json_encode($msg);
        }
    }

    public function edit($uuid = null)
    {
        if ($uuid === null) {
            show_custom_404();
            return;
        }

        $users = $this->Users_model->get_data_by_uuid($uuid);

        if (!$users) {
            show_custom_404();
            return;
        }

        $data = [
            'users' => $users,
        ];
        $parser = [
            'judul' => "<i class='fa fa-user-edit'></i> User",
            'isi'   => $this->load->view('users/editForm', $data, TRUE)
        ];
        $this->parser->parse('templates/main', $parser);
    }

    public function updateData()
    {
        if ($this->input->is_ajax_request()) {
            $id = $this->input->post('id');
            $nama = $this->input->post('nama');
            $username = $this->input->post('username');
            $password = $this->input->post('password');
            $level = $this->input->post('level');

            $user = $this->Users_model->get_data_by_id($id);

            $this->form_validation->set_rules('nama', 'Nama', 'required', [
                'required'     => 'Nama wajah wajib diisi.'
            ]);
            if ($user->username == $username) {
                $this->form_validation->set_rules('username', 'Username', 'required', [
                    'required'     => 'Username wajib diisi.',
                ]);
            } else {
                $this->form_validation->set_rules('username', 'Username', 'required|is_unique[tbuser.username]', [
                    'required'     => 'Username wajib diisi.',
                    'is_unique'    => 'Username sudah digunakan.',
                ]);
            };
            if (!empty($password)) {
                $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]|regex_match[/^(?=.*[A-Z])(?=.*\d).+$/]', [
                    'required'     => 'Password wajib diisi.',
                    'min_length'   => 'Password harus lebih dari 6 karakter.',
                    'regex_match'  => 'Password harus mengandung setidaknya satu huruf kapital dan satu angka.'
                ]);
            }

            if ($this->form_validation->run() == FALSE) {
                $msg = [
                    'error' => [
                        'errorNama' => form_error('nama'),
                        'errorEmail' => form_error('email'),
                        'errorPassword' => form_error('password'),
                    ]
                ];
            } else {
                if (!empty($password)) {
                    $data = [
                        'nama' => $nama,
                        'username' => $username,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                    ];
                    $this->Users_model->edit_data($id, $data);
                    $msg = ['success' => 'Data berhasil diperbarui.'];
                } else {
                    $data = [
                        'nama' => $nama,
                        'username' => $username,
                    ];
                    $this->Users_model->edit_data($id, $data);
                    $msg = ['success' => 'Data berhasil diperbarui.'];
                }
            }

            echo json_encode($msg);
        }
    }
}
