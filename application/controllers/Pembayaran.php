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
        // $this->load->model('DataModel');
        $this->load->model('DataModel');
    }

    public function index()
    {
        $data['title'] = 'Data Pembayaran';
        $data['user'] = $this->db->get_where('tb_user', ['email' =>
        $this->session->userdata('email')])->row_array();
        $data['seri'] = $this->DataModel->get_data('tb_seri')->result();
        $data['masyarakat'] = $this->DataModel->get_data('tb_masyarakat')->result();
        // $data['pembayaran'] = $this->DataModel->get_data('tb_transaksi')->result();

        if ((isset($_GET['bulan']) && $_GET['bulan'] != '') && (isset($_GET['tahun']) && $_GET['tahun'] != '')) {
            $bulan = $_GET['bulan'];
            $tahun = $_GET['tahun'];
            $bulantahun = $bulan . $tahun;
        } else {
            $bulan = date('m');
            $tahun = date('Y');
            $bulantahun = $bulan . $tahun;
        }

        // $bulan = $this->input->post('bulan');
        // $tahun = $this->input->post('tahun');
        $data['semuaMasyarakat'] = $this->db->query("SELECT * FROM tb_masyarakat")->result();
        $data['bulantahun'] = $bulan.$tahun;
        $data['transaksi'] = $this->db->query("SELECT * FROM tb_transaksi WHERE bulan='$bulan$tahun'")->result();

        $data['pembayaran'] = $this->db->query("SELECT tb_transaksi.*, 
        tb_masyarakat.nama_lengkap, tb_masyarakat.alamat, 
        tb_masyarakat.kelurahan, tb_masyarakat.kecamatan, tb_masyarakat.seri
        FROM tb_transaksi 
        INNER JOIN tb_masyarakat ON tb_transaksi.nik=tb_masyarakat.nik
        INNER JOIN tb_seri ON tb_masyarakat.seri=tb_seri.seri
        WHERE tb_transaksi.bulan='$bulantahun' 
        ORDER BY tb_masyarakat.nama_lengkap ASC")->result();

        // var_dump($query);
        // die();

        $results = [];

        foreach($data['semuaMasyarakat'] as $masyarakat){
            array_push($results, (object) [
                'nik' => $masyarakat->nik,
                'nama_lengkap' => $masyarakat->nama_lengkap,
                'alamat' => $masyarakat->alamat,
                'kelurahan' => $masyarakat->kelurahan,
                'seri' => $masyarakat->seri,
                'status' => false
            ]);
        }

        foreach($results as $result){
            foreach ($data['transaksi'] as $transaksi) {
                if($result->nik === $transaksi->nik){
                    $result->status = true;
                }
            }
        }

        $data['cetakPembayaran'] = [];
        foreach ($results as $belum) {
            if($belum->status == false){
                array_push($data['cetakPembayaran'] , $belum);
            }
        }


        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('pembayaran/index', $data);
        $this->load->view('templates/footer');
    }
    
    public function cetakPembayaran()
    {
        $data['title'] = 'Laporan Data Pembayaran Berdasarkan Bulan';
        $data['user'] = $this->db->get_where('tb_user', ['email' => $this->session->userdata('email')])->row_array();
        $data['seri'] = $this->DataModel->get_data('tb_seri')->result();
        $data['masyarakat'] = $this->DataModel->get_data('tb_masyarakat')->result();

        if ((isset($_GET['bulan']) && $_GET['bulan'] != '') && (isset($_GET['tahun']) && $_GET['tahun'] != '')) {
            $bulan = $_GET['bulan'];
            $tahun = $_GET['tahun'];
            $bulantahun = $bulan . $tahun;
        } else {
            $bulan = date('m');
            $tahun = date('Y');
            $bulantahun = $bulan . $tahun;
        }

        $data['cetakPembayaran'] = $this->db->query("SELECT tb_transaksi.*, 
        tb_masyarakat.nama_lengkap, tb_masyarakat.alamat,  tb_masyarakat.rt, tb_masyarakat.rw,
        tb_masyarakat.kelurahan, tb_masyarakat.kecamatan
        FROM tb_transaksi 
        INNER JOIN tb_masyarakat ON tb_transaksi.nik=tb_masyarakat.nik
        INNER JOIN tb_seri ON tb_masyarakat.seri=tb_seri.seri
        WHERE tb_transaksi.bulan='$bulantahun' 
        ORDER BY tb_masyarakat.nama_lengkap ASC")->result();

        $this->load->library('pdf');
        $html = $this->load->view('pembayaran/cetakPemb', $data, true);
        $this->pdf->createPdf($html, 'Laporan Data Pembayaran PerBulan', false);

        // $this->load->view('templates/header', $data);
        // $this->load->view('pembayaran/cetakPemb', $data);
    }

    public function deletePemb($id)
    {
        $where = array('id_transaksi' => $id);

        $this->DataModel->delete_data($where, 'tb_transaksi');
        $this->session->set_flashdata('pesan', '<div class="alert alert-danger alert-dismissible show fade">
                          <div class="alert-body">
                            <button class="close" data-dismiss="alert">
                              <span>??</span>
                            </button>
                            data pembayaran berhasil dihapus!
                          </div>
                        </div>');
        redirect('pembayaran');
    }
    
    public function showTransaksi($id)
    {
    $data['title'] = 'Detail Transaksi';
    $data['user'] = $this->db->get_where('tb_user', ['email' =>
    $this->session->userdata('email')])->row_array();

    // $data['seri'] = $this->DataModel->get_data('tb_seri')->result();
    $detailTransaksi = $this->DataModel->detail_transaksi($id);
    $data['detailTransaksi'] = $detailTransaksi;

    $this->load->view('templates/header', $data);
    $this->load->view('templates/sidebar', $data);
    $this->load->view('templates/topbar', $data);
    $this->load->view('pembayaran/showTransaksi', $data);
    $this->load->view('templates/footer');
    }

    public function filterLaporanPemb(){
        $data['title'] = 'Laporan Data Pembayaran';
        $data['user'] = $this->db->get_where('tb_user', ['email' =>
        $this->session->userdata('email')])->row_array();
        $data['seri'] = $this->DataModel->get_data('tb_seri')->result();
        $data['masyarakat'] = $this->DataModel->get_data('tb_masyarakat')->result();
        // $data['sumtotal'] = $this->DataModel->hitung();
        // $data['cetakFilter'] = $this->DataModel->get_data('tb_masyarakat')->result();
    
        // $data['cetakFilter'] = $this->db->query("SELECT tb_masyarakat.*, WHERE tb_masyarakat.kelurahan")->result();
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('pembayaran/filterLaporanPemb', $data);
        $this->load->view('templates/footer');   
    }

    //filter Kelurahan
    public function printLaporanByKelurahan(){
        $data['title'] = 'Laporan Data Pembayaran Berdasarkan Kelurahan';
        $data['masyarakat'] = $this->DataModel->get_data('tb_masyarakat')->result();
        $data['user'] = $this->db->get_where('tb_user', ['email' =>
        $this->session->userdata('email')])->row_array();
        $key = $this->input->post('kelurahan');
        $data['cetakPembayaran'] = $this->db->query("SELECT tb_transaksi.*, 
            tb_masyarakat.nama_lengkap, tb_masyarakat.alamat, tb_masyarakat.rt, tb_masyarakat.rw,
            tb_masyarakat.kelurahan, tb_masyarakat.kecamatan
            FROM tb_transaksi 
            INNER JOIN tb_masyarakat ON tb_transaksi.nik=tb_masyarakat.nik
            INNER JOIN tb_seri ON tb_masyarakat.seri=tb_seri.seri 
            where tb_transaksi.kelurahan = '$key'
            ORDER BY tb_masyarakat.nama_lengkap ASC")->result();
        $this->load->library('pdf');
        $html = $this->load->view('pembayaran/cetakByKelurahan', $data, true);
        $this->pdf->createPdf($html, 'Laporan Data Pembayaran Kelurahan', false);
    }

    //filter Seri
    public function printLaporanBySeri(){
        $data['title'] = 'Laporan Data Pembayaran Berdasarkan Seri';
        $data['masyarakat'] = $this->DataModel->get_data('tb_masyarakat')->result();
        $data['user'] = $this->db->get_where('tb_user', ['email' =>
        $this->session->userdata('email')])->row_array();
        $key = $this->input->post('seri');
        $data['cetakPembayaran'] = $this->db->query("SELECT tb_transaksi.*, 
            tb_masyarakat.nama_lengkap, tb_masyarakat.alamat, tb_masyarakat.rt, tb_masyarakat.rw,
            tb_masyarakat.kelurahan, tb_masyarakat.kecamatan
            FROM tb_transaksi 
            INNER JOIN tb_masyarakat ON tb_transaksi.nik=tb_masyarakat.nik
            INNER JOIN tb_seri ON tb_masyarakat.seri=tb_seri.seri 
            where tb_transaksi.seri = '$key'
            ORDER BY tb_masyarakat.nama_lengkap ASC")->result();
        $this->load->library('pdf');
        $html = $this->load->view('pembayaran/cetakBySeri', $data, true);
        $this->pdf->createPdf($html, 'Laporan Data Pembayaran Seri', false);
    }

    public function printLaporanByBelum(){
        $data['title'] = 'Laporan Data Pembayaran Masyarakat';
        $data['user'] = $this->db->get_where('tb_user', ['email' =>
            $this->session->userdata('email')])->row_array();
        // $bulan = $this->input->post('bulan');
        // $tahun = $this->input->post('tahun');
        if ((isset($_GET['bulan']) && $_GET['bulan'] != '') && (isset($_GET['tahun']) && $_GET['tahun'] != '')) {
            $bulan = $_GET['bulan'];
            $tahun = $_GET['tahun'];
            $bulantahun = $bulan . $tahun;
        } else {
            $bulan = date('m');
            $tahun = date('Y');
            $bulantahun = $bulan . $tahun;
        }

        $data['bulantahun'] = $bulan.$tahun;
        $data['semuaMasyarakat'] = $this->db->query("SELECT * FROM tb_masyarakat")->result();
        $data['transaksi'] = $this->db->query("SELECT * FROM tb_transaksi WHERE bulan='$bulan$tahun'")->result();

        $data['cetakPembayaran'] = $this->db->query("SELECT tb_transaksi.*, 
        tb_masyarakat.nama_lengkap, tb_masyarakat.alamat, 
        tb_masyarakat.kelurahan, tb_masyarakat.kecamatan, tb_masyarakat.seri
        FROM tb_transaksi 
        INNER JOIN tb_masyarakat ON tb_transaksi.nik=tb_masyarakat.nik
        INNER JOIN tb_seri ON tb_masyarakat.seri=tb_seri.seri
        WHERE tb_transaksi.bulan='$bulantahun' 
        ORDER BY tb_masyarakat.nama_lengkap ASC")->result();

        $results = [];

        foreach($data['semuaMasyarakat'] as $masyarakat){
            array_push($results, (object) [
                'nik' => $masyarakat->nik,
                'nama_lengkap' => $masyarakat->nama_lengkap,
                'alamat' => $masyarakat->alamat,
                'kelurahan' => $masyarakat->kelurahan,
                'seri' => $masyarakat->seri,
                'status' => false
            ]);
        }

        foreach($results as $result){
            foreach ($data['transaksi'] as $transaksi) {
                if($result->nik === $transaksi->nik){
                    $result->status = true;
                }
            }
        }

        $data['cetakPembayaran'] = [];
        foreach ($results as $belum) {
            if($belum->status == false){
                array_push($data['cetakPembayaran'] , $belum);
            }
        }

        $this->load->library('pdf');
        $html = $this->load->view('pembayaran/cetakByBelum', $data, true);
        $this->pdf->createPdf($html, 'Laporan Data Pembayaran Masyarakat', false);
    }
}
