<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Users_model extends CI_Model
{
    var $table = 'tbuser';
    var $column_order = array(null, 'nama', 'username', 'level', null);
    var $column_search = array('nama', 'username', 'level');
    var $order = array('created_at' => 'ASC');

    public function __construct()
    {
        parent::__construct();
    }

    private function _get_datatables_query()
    {
        $this->db->from($this->table);

        $i = 0;

        foreach ($this->column_search as $item) {
            if ($_POST['search']['value']) {

                if ($i === 0) {
                    $this->db->group_start();
                    $this->db->like($item, $_POST['search']['value']);
                } else {
                    $this->db->or_like($item, $_POST['search']['value']);
                }

                if (count($this->column_search) - 1 == $i)
                    $this->db->group_end();
            }
            $i++;
        }

        if (isset($_POST['order'])) {
            $this->db->order_by($this->column_order[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
        } else if (isset($this->order)) {
            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }

    function get_datatables()
    {
        $this->_get_datatables_query();
        if ($_POST['length'] != -1)
            $this->db->limit($_POST['length'], $_POST['start']);
        $query = $this->db->get();
        return $query->result();
    }

    function count_filtered()
    {
        $this->_get_datatables_query();
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function count_all()
    {
        $this->db->from($this->table);
        return $this->db->count_all_results();
    }

    public function register($data)
    {
        return $this->db->insert('tbuser', $data);
    }

    public function login($username, $password)
    {
        $user = $this->db->get_where('tbuser', ['username' => $username])->row();

        if ($user && password_verify($password, $user->password)) {
            return $user;
        }

        return false;
    }

    public function get_data_by_id($id)
    {
        return $this->db->get_where('tbuser', ['id' => $id])->row();
    }

    public function get_data_by_uuid($uuid)
    {
        return $this->db->get_where('tbuser', ['uuid' => $uuid])->row();
    }

    public function delete_data($id)
    {
        return $this->db->delete('tbuser', ['id' => $id]);
    }

    public function edit_data($id, $data)
    {
        return $this->db->where('id', $id)->update('tbuser', $data);
    }
}
