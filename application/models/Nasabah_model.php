<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Nasabah_model extends CI_Model
{
    var $table = 'tbnasabah';
    var $column_order = array(null, 'nik', 'nama_lengkap', 'telp', 'alamat', null);
    var $column_search = array('nik', 'nama_lengkap');
    var $order = array('created_at' => 'DESC');

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

    public function count_all_nasabah()
    {
        return $this->db->count_all('tbnasabah');
    }

    public function insert_data($data)
    {
        return $this->db->insert('tbnasabah', $data);
    }

    public function delete_data($id)
    {
        // Check if the customer has any records in the 'tbsimpanan' (savings) table.
        // We assume the foreign key column is 'id_nasabah'.
        $this->db->where('nasabah_id', $id);
        $simpanan_exists = $this->db->get('tbsimpanan')->num_rows() > 0;

        // Check if the customer has any records in the 'tbdeposito' (deposits) table.
        $this->db->where('nasabah_id', $id);
        $deposito_exists = $this->db->get('tbdeposito')->num_rows() > 0;

        // If a record exists in EITHER the savings or deposits table, block the deletion.
        if ($simpanan_exists || $deposito_exists) {
            // Return false to indicate the deletion failed because of existing related records.
            return false;
        }

        // If no related records are found, it's safe to delete the customer.
        return $this->db->delete('tbnasabah', ['id' => $id]);
    }



    public function edit_data($id, $data)
    {
        return $this->db->where('id', $id)->update('tbnasabah', $data);
    }

    public function get_data_by_id($id)
    {
        return $this->db->get_where('tbnasabah', ['id' => $id])->row();
    }

    public function get_data()
    {
        return $this->db->get('tbnasabah')->result();
    }

    public function search_nasabah($keyword)
    {
        $this->db->like('nama_lengkap', $keyword);
        $this->db->select('id, nama_lengkap');
        $this->db->from('tbnasabah');
        $this->db->limit(50);
        $query = $this->db->get();
        return $query->result();
    }

    /**
     * Sync denormalized nama_nasabah fields when customer name changes
     * Call this after updating tbnasabah.nama_lengkap
     */
    public function update_nama_in_related_tables($nasabah_id, $new_name)
    {
        if (empty($nasabah_id) || empty($new_name)) {
            return false;
        }

        $this->db->trans_start();

        // Update tbsimpanan
        $this->db->where('nasabah_id', $nasabah_id);
        $this->db->update('tbsimpanan', ['nama_nasabah' => $new_name]);

        // Update tbdeposito
        $this->db->where('nasabah_id', $nasabah_id);
        $this->db->update('tbdeposito', ['nama_nasabah' => $new_name]);

        // Update tbtransaksi via JOIN through tbsimpanan
        $this->db->query("
            UPDATE tbtransaksi t
            JOIN tbsimpanan s ON s.id = t.simpanan_id
            SET t.nama_nasabah = ?
            WHERE s.nasabah_id = ?
        ", [$new_name, $nasabah_id]);

        // Update tb_bunga_deposito_log via JOIN through tbdeposito
        $this->db->query("
            UPDATE tb_bunga_deposito_log bl
            JOIN tbdeposito d ON d.id = bl.deposito_id
            SET bl.nama_nasabah = ?
            WHERE d.nasabah_id = ?
        ", [$new_name, $nasabah_id]);

        $this->db->trans_complete();

        return $this->db->trans_status();
    }
}
