<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pembayaran extends CI_Controller
{

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     * 		http://example.com/index.php/welcome
     *	- or -
     * 		http://example.com/index.php/welcome/index
     *	- or -
     * Since this controller is set as the default controller in
     * config/routes.php, it's displayed at http://example.com/
     *
     * So any other public methods not prefixed with an underscore will
     * map to /index.php/welcome/<method_name>
     * @see https://codeigniter.com/user_guide/general/urls.html
     */
    public function __construct()
    {
        parent::__construct();
        // $this->load->model('dataModel');
        $this->load->model('dataModel');
    }

    public function index()
    {
        $data['title'] = 'Data Pembayaran';
        $data['user'] = $this->db->get_where('tb_user', ['email' =>
        $this->session->userdata('email')])->row_array();
        $data['seri'] = $this->dataModel->get_data('tb_seri')->result();
        $data['masyarakat'] = $this->dataModel->get_data('tb_masyarakat')->result();
        // $data['pembayaran'] = $this->dataModel->get_data('tb_transaksi')->result();

        if ((isset($_GET['bulan']) && $_GET['bulan'] != '') && (isset($_GET['tahun']) && $_GET['tahun'] != '')) {
            $bulan = $_GET['bulan'];
            $tahun = $_GET['tahun'];
            $bulantahun = $bulan . $tahun;
        } else {
            $bulan = date('m');
            $tahun = date('Y');
            $bulantahun = $bulan . $tahun;
        }

        $data['pembayaran'] = $this->db->query("SELECT tb_transaksi.*, 
        tb_masyarakat.nama_lengkap, tb_masyarakat.alamat, 
        tb_masyarakat.kelurahan, tb_masyarakat.kecamatan
        FROM tb_transaksi 
        INNER JOIN tb_masyarakat ON tb_transaksi.nik=tb_masyarakat.nik
        INNER JOIN tb_seri ON tb_masyarakat.seri=tb_seri.seri
        WHERE tb_transaksi.bulan='$bulantahun' 
        ORDER BY tb_masyarakat.nama_lengkap ASC")->result();

        // var_dump($query);
        // die();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('pembayaran/index', $data);
        $this->load->view('templates/footer');
    }
}
