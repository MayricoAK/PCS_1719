<?php
defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

require APPPATH . '/libraries/Firebase/JWT/JWT.php';
use \Firebase\JWT\JWT;

class Api_pcs extends REST_Controller{
    private $secret_key = "krisdiansyah";

    function __construct(){
        parent::__construct();
        $this->load->model('M_admin');
        $this->load->model('M_produk');
        $this->load->model('M_transaksi');
        $this->load->model('M_item_transaksi');
    }

    //check token
    public function cekToken(){
        try {
            $token = $this->input->get_request_header('Authorization');

            if (!empty($token)) {
                $token = explode(' ', $token)[1];
            }

            $token_decode = JWT::decode($token, $this->secret_key, array('HS256'));
        } catch (Exception $e) {
            $data_json = array(
                "success"       => false,
                "message"       => "Token tidak valid",
                "error_code"    => 1204,
                "data"          => null
            );

            $this->response($data_json, REST_Controller::HTTP_OK);
            $this->output->_display();
            exit();
        }
    }

    //login admin
    public function login_post(){
        $data = array(
            "email"     => $this    ->input->post("email"),
            "password"  => md5($this->input->post("password"))
        );

        //proses login
        $result = $this->M_admin->cekLoginAdmin($data);

        if (empty($result)) {
            $data_json = array(
                "success"    => false,
                "message"    => "Email dan Password tidak valid",
                "error_code" => 1308,
                "data"       => null
            );

            $this->response($data_json, REST_Controller::HTTP_OK);
            $this->output->_display();
            exit();
        } else {
            $date = new Datetime();

            $payload["id"]      = $result["id"];
            $payload["email"]   = $result["email"];
            $payload["iat"]     = $date->getTimestamp();
            $payload["exp"]     = $date->getTimestamp() + 3600;

            $data_json = array(
                "success" => true,
                "message" => "Otentikasi Berhasil",
                "data"    => array(
                    "admin" => $result,
                    "token" => JWT::encode($payload, $this->secret_key))
            );

            $this->response($data_json, REST_Controller::HTTP_OK);
        }
    }

    //tampil admin
    public function admin_get(){   
        $this->cekToken();

        //proses get admin
        $data = $this->M_admin->getData();

        $result = array(
            "success"   => true,
            "message"   => "Data found",
            "data"      => $data
        );

        echo json_encode($result);
    }

    //tambah admin
    public function admin_post(){   
        $this->cekToken();
        $data = array(
            'email'     => $this    ->post('email'),
            'password'  => md5($this->post('password')),
            'nama'      => $this    ->post('nama')
        );

        //proses
        $insert = $this->M_admin->insertData($data);

        if ($insert) {
            $this->response($data, 200);
        } else {
            $this->response($data, 502);
        }
    }

    //edit admin
    public function admin_put(){   
        $this->cekToken();
        $validation_message = [];

        if ($this->put("email") == "") {
            array_push($validation_message, "Email tidak boleh kosong");
        }

        if ($this->put("email") != "" && !filter_var($this->put("email"), FILTER_VALIDATE_EMAIL)) {
            array_push($validation_message, "Format Email tidak valid");
        }

        if ($this->put("password") == "") {
            array_push($validation_message, "Password tidak boleh kosong");
        }

        if ($this->put("nama") == "") {
            array_push($validation_message, "Nama tidak boleh kosong");
        }

        if (count($validation_message) > 0) {
            $data_json = array(
                "success"   => false,
                "message"   => "Data tidak valid",
                "data"      => $validation_message
            );

            $this->response($data_json, REST_Controller::HTTP_OK);
            $this->output->_display();
            exit();
        }

        //valid
        $data = array(
            "email"     => $this    ->put("email"),
            "password"  => md5($this->put("password")),
            "nama"      => $this    ->put("nama")
        );

        $id = $this->put("id");
        
        //proses
        $result = $this->M_admin->updateAdmin($data, $id);

        $data_json = array(
            "success"   => true,
            "message"   => "Update Berhasil",
            "data"      => array(
            "admin"     => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //hapus admin
    public function admin_delete(){   
        $this->cekToken();
        $id = $this->delete("id");

        //Proses
        $result = $this->M_admin->deleteAdmin($id);

        if (empty($result)) {
            $data_json = array(
                "success" => false,
                "message" => "Id tidak valid",
                "data" => null
            );

            $this->response($data_json, REST_Controller::HTTP_OK);
            $this->output->_display();
            exit();
        }

        $data_json = array(
            "success"   => true,
            "message"   => "Delete Berhasil",
            "data"      => array(
            "admin"     => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //produk

    //tampil produk
    public function produk_get(){
        $this->cekToken();

        //proses
        $result = $this->M_produk->getProduk();
        $data_json = array(
            "success" => true,
            "message" => "Data found",
            "data" => array(
                "produk" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //tambah data
    public function produk_post(){   
        $this->cekToken();
        $validation_message = [];

        if ($this->post("admin_id") == "") {
            array_push($validation_message, "Admin ID tidak boleh kosong");
        }
        if ($this->post("admin_id") == "" && !$this->M_admin->cekAdminExist($this->input->post("admin_id"))) {
            array_push($validation_message, "Admin ID tidak ditemukan");
        }
        if ($this->post("nama") == "") {
            array_push($validation_message, "Nama tidak boleh kosong");
        }
        if ($this->post("harga") == "") {
            array_push($validation_message, "Harga tidak boleh kosong");
        }
        if ($this->post("harga") == "" && !is_numeric($this->input->post("harga"))) {
            array_push($validation_message, "Harga harus di isi angka");
        }
        if ($this->post("stok") == "") {
            array_push($validation_message, "Stok tidak boleh kosong");
        }
        if ($this->post("stok") == "" && !is_numeric($this->input->post("stok"))) {
            array_push($validation_message, "Stok harus di isi angka");
        }
        if (count($validation_message) > 0) {
            $data_json = array(
                "success" => false,
                "message" => "Data tidak valid",
                "data" => $validation_message
            );

            $this->response($data_json, REST_Controller::HTTP_OK);
            $this->output->_display();
            exit();
        }
        $data = array(
            'admin_id'  => $this->input->post('admin_id'),
            'nama'      => $this->input->post('nama'),
            'harga'     => $this->input->post('harga'),
            'stok'      => $this->input->post('stok')
        );
        $result = $this->M_produk->insertProduk($data);
        $data_json = array(
            "success" => true,
            "message" => "insert Berhasil",
            "data" => array(
                "produk" => $result
            )
        );
        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //edit produk
    public function produk_put(){   
        $this->cekToken();
        $validation_message = [];

        if ($this->put("id") == "") {
            array_push($validation_message, "ID tidak boleh kosong");
        }
        if ($this->put("admin_id") == "") {
            array_push($validation_message, "Admin ID tidak boleh kosong");
        }
        if ($this->put("nama") == "") {
            array_push($validation_message, "Nama tidak boleh kosong");
        }
        if ($this->put("harga") == "") {
            array_push($validation_message, "Harga tidak boleh kosong");
        }
        if ($this->put("harga") == "" && !is_numeric($this->put("harga"))) {
            array_push($validation_message, "Harga harus di isi angka");
        }
        if ($this->put("stok") == "") {
            array_push($validation_message, "Stok tidak boleh kosong");
        }
        if ($this->put("stok") == "" && !is_numeric($this->put("stok"))) {
            array_push($validation_message, "stok harus di isi angka");
        }

        if (count($validation_message) > 0) {
            $data_json = array(
                "success" => false,
                "message" => "Data tidak valid",
                "data"    => $validation_message
            );

            $this->response($data_json, REST_Controller::HTTP_OK);
            $this->output->_display();
            exit();
        }

        $data = array(
            'admin_id'  => $this->put('admin_id'),
            'nama'      => $this->put('nama'),
            'harga'     => $this->put('harga'),
            'stok'      => $this->put('stok')
        );

        $id = $this->put("id");

        //proses update
        $result = $this->M_produk->updateProduk($data, $id);

        $data_json = array(
            "success" => true,
            "message" => "Update Berhasil",
            "data" => array(
                "produk" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //hapus produk
    public function produk_delete(){   
        $this->cekToken();

        $id = $this->delete("id");
        //Proses
        $result = $this->M_produk->deleteProduk($id);

        if (empty($result)) {
            $data_json = array(
                "success" => false,
                "message" => "Id tidak valid",
                "data" => null
            );

            $this->response($data_json, REST_Controller::HTTP_OK);
            $this->output->_display();
            exit();
        }

        $data_json = array(
            "success" => true,
            "message" => "Delete Berhasil",
            "data" => array(
                "produk" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //tampil transaksi
    public function transaksi_get(){   
        $this->cekToken();

        //proses get
        $data = $this->M_transaksi->getTransaksi();

        $data_json = array(
            "success" => true,
            "message" => "Data found",
            "data" => $data
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //tampil transaksi bulan ini
    public function transaksi_bulan_ini_get(){   
        //cek token
        $this->cekToken();
        //proses panggil data dengan fungsi getTransaksiBulanIni
        $data = $this->M_transaksi->getTransaksiBulanIni();

        //tampil data 
        $result = array(
            "success" => true,
            "message" => "Data found",
            "data" => $data
        );

        echo json_encode($result);
    }

    //tambah transaksi
    public function transaksi_post(){   
        $this->cekToken();
        $validation_message = [];

        if ($this->input->post("admin_id") == "") {
            array_push($validation_message, "Admin ID tidak boleh kosong");
        }
        if ($this->input->post("admin_id") == "" && !$this->M_admin->cekAdminExist($this->input->post("admin_id"))) {
            array_push($validation_message, "Admin ID tidak ditemukan");
        }
        if ($this->input->post("total") == "") {
            array_push($validation_message, "total tidak boleh kosong");
        }
        if ($this->input->post("total") == "" && !is_numeric($this->input->post("total"))) {
            array_push($validation_message, "total harus di isi angka");
        }

        if (count($validation_message) > 0) {
            $data_json = array(
                "success" => false,
                "message" => "Data tidak valid",
                "data" => $validation_message
            );

            $this->response($data_json, REST_Controller::HTTP_OK);
            $this->output->_display();
            exit();
        }

        $data = array(
            'admin_id'  => $this->input->post('admin_id'),
            'total'     => $this->input->post('total'),
            'tanggal'   => date("Y-m-d H:i:s")
        );

        $result = $this->M_transaksi->insertTransaksi($data);

        $data_json = array(
            "success"   => true,
            "message"   => "Insert Berhasil",
            "data"      => array(
            "transaksi" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //edit transaksi
    public function transaksi_put(){   
        $this->cekToken();
        $validation_message = [];

        if ($this->put("id") == "") {
            array_push($validation_message, "ID tidak boleh kosong");
        }
        if ($this->put("admin_id") == "") {
            array_push($validation_message, "Admin ID tidak boleh kosong");
        }
        if ($this->put("admin_id") == "" && !$this->M_admin->cekAdminExist($this->put("admin_id"))) {
            array_push($validation_message, "Admin ID tidak ditemukan");
        }
        if ($this->put("total") == "") {
            array_push($validation_message, "total tidak boleh kosong");
        }
        if ($this->put("total") == "" && !is_numeric($this->put("total"))) {
            array_push($validation_message, "total harus di isi angka");
        }

        if (count($validation_message) > 0) {
            $data_json = array(
                "success" => false,
                "message" => "Data tidak valid",
                "data"    => $validation_message
            );

            $this->response($data_json, REST_Controller::HTTP_OK);
            $this->output->_display();
            exit();
        }

        $data = array(
            'admin_id'  => $this->put("admin_id"),
            'total'     => $this->put("total"),
            'tanggal'   => date("Y-m-d H:i:s")
        );

        $id = $this->put("id");
        $result = $this->M_transaksi->updateTransaksi($data, $id);
        
        $data_json = array(
            "success" => true,
            "message" => "Update Berhasil",
            "data" => array(
            "transaksi" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //hapus transaksi
    public function transaksi_delete(){   
        $this->cekToken();

        $id = $this->delete("id");
        //proses delte
        $result = $this->M_transaksi->deleteTransaksi($id);

        if (empty($result)) {
            $data_json = array(
                "success" => false,
                "message" => "Id tidak valid",
                "data" => null
            );

            $this->response($data_json, REST_Controller::HTTP_OK);
            $this->output->_display();
            exit();
        }

        $data_json = array(
            "success" => true,
            "message" => "Delete Berhasil",
            "data" => array(
            "transaksi" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //tampil item transaksi
    public function item_transaksi_get(){   
        //mengecek token
        $this->cekToken();

        //proses get
        $result = $this->M_item_transaksi->getitemtransaksi();

        $data_json = array(
            "success" => true,
            "message" => "Data found",
            "data" => array(
                "item_transaksi" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //tambah item transaksi
    public function item_transaksi_post(){   
        $this->cekToken();
        $validation_message = [];

        if ($this->input->post("transaksi_id") == "") {
            array_push($validation_message, "transaksi_id tidak boleh kosong");
        }
        if ($this->input->post("transaksi_id") == "" && !$this->M_transaksi->cektransaksiExist($this->input->post("transaksi_id"))) {
            array_push($validation_message, "transaksi_id tidak ditemukan");
        }
        if ($this->input->post("produk_id") == "") {
            array_push($validation_message, "produk_id tidak boleh kosong");
        }
        if ($this->input->post("produk_id") == "" && !$this->M_produk->cekprodukExist($this->input->post("produk_id"))) {
            array_push($validation_message, "produk_id tidak ditemukan");
        }
        if ($this->input->post("qty") == "") {
            array_push($validation_message, "qty tidak boleh kosong");
        }
        if ($this->input->post("qty") == "" && !is_numeric($this->input->post("qty"))) {
            array_push($validation_message, "qty harus di isi angka");
        }
        if ($this->input->post("harga_saat_transaksi") == "") {
            array_push($validation_message, "harga_saat_transaksi tidak boleh kosong");
        }
        if ($this->input->post("harga_saat_transaksi") == "" && !is_numeric($this->input->post("harga_saat_transaksi"))) {
            array_push($validation_message, "harga_saat_transaksi harus di isi angka");
        }

        if (count($validation_message) > 0) {
            $data_json = array(
                "success" => false,
                "message" => "Data tidak valid",
                "data"    => $validation_message
            );

            $this->response($data_json, REST_Controller::HTTP_OK);
            $this->output->_display();
            exit();
        }

        $data = array(
            'transaksi_id' => $this->input->post('transaksi_id'),
            'produk_id' => $this->input->post('produk_id'),
            'qty' => $this->input->post('qty'),
            'harga_saat_transaksi' => $this->input->post('harga_saat_transaksi'),
            'sub_total' => $this->input->post('qty') * $this->input->post('harga_saat_transaksi')
        );

        //proses
        $result = $this->M_item_transaksi->insertitemtransaksi($data);

        $data_json = array(
            "success" => true,
            "message" => "Insert Berhasil",
            "data" => array(
                "item_transaksi" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //edit item transaksi
    public function item_transaksi_put(){   
        $this->cekToken();
        $validation_message = [];

        if ($this->put("id") == "") {
            array_push($validation_message, "id tidak boleh kosong");
        }
        if ($this->put("transaksi_id") == "") {
            array_push($validation_message, "transaksi_id tidak boleh kosong");
        }
        if ($this->put("transaksi_id") == "" && !$this->M_transaksi->cektransaksiExist($this->put("transaksi_id"))) {
            array_push($validation_message, "transaksi_id tidak ditemukan");
        }
        if ($this->put("produk_id") == "") {
            array_push($validation_message, "produk_id tidak boleh kosong");
        }
        if ($this->put("produk_id") == "" && !$this->M_produk->cekprodukExist($this->put("produk_id"))) {
            array_push($validation_message, "produk_id tidak ditemukan");
        }
        if ($this->put("qty") == "") {
            array_push($validation_message, "qty tidak boleh kosong");
        }
        if ($this->put("qty") == "" && !is_numeric($this->put("qty"))) {
            array_push($validation_message, "qty harus di isi angka");
        }
        if ($this->put("harga_saat_transaksi") == "") {
            array_push($validation_message, "harga_saat_transaksi tidak boleh kosong");
        }
        if ($this->put("harga_saat_transaksi") == "" && !is_numeric($this->put("harga_saat_transaksi"))) {
            array_push($validation_message, "harga_saat_transaksi harus di isi angka");
        }
        
        if (count($validation_message) > 0) {
            $data_json = array(
                "success" => false,
                "message" => "Data tidak valid",
                "data"    => $validation_message
            );

            $this->response($data_json, REST_Controller::HTTP_OK);
            $this->output->_display();
            exit();
        }

        $data = array(
            'transaksi_id' => $this->put('transaksi_id'),
            'produk_id' => $this->put('produk_id'),
            'qty' => $this->put('qty'),
            'harga_saat_transaksi' => $this->put('harga_saat_transaksi'),
            'sub_total' => $this->put('qty') * $this->put('harga_saat_transaksi')
        );

        $id = $this->put("id");
        //proses update
        $result = $this->M_item_transaksi->updateitem_transaksi($data, $id);
        
        $data_json = array(
            "success" => true,
            "message" => "Update Berhasil",
            "data" => array(
                "item_transaksi" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //delete item transaksi
    public function item_transaksi_delete(){ 
        $this->cekToken();

        $id = $this->delete("id");

        //proses delete
        $result = $this->M_item_transaksi->deleteitem_transaksi($id);

        if (empty($result)) {
            $data_json = array(
                "success" => false,
                "message" => "Id tidak valid",
                "data" => null
            );

            $this->response($data_json, REST_Controller::HTTP_OK);
            $this->output->_display();
            exit();
        }

        $data_json = array(
            "success" => true,
            "message" => "Delete Berhasil",
            "data" => array(
                "item_transaksi" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

     //tampil transaksi by transaksi id
     public function item_transaksi_by_transaksi_id_get(){   
        $this->cekToken();

        //proses get
        $result = $this->M_item_transaksi->getitemtransaksibytransaksiID($this->input->get('transaksi_id'));
        
        $data_json = array(
            "success" => true,
            "message" => "Data found",
            "data" => array(
                "item_transaksi" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //delete item transaksi by transaksi id
    public function item_transaksi_by_transaksi_id_delete(){   
        $this->cekToken();

        $transaksi_id = $this->delete("transaksi_id");

        //proses delete
        $result = $this->M_item_transaksi->deleteitem_transaksibytransaksiID($transaksi_id);
        
        if (empty($result)) {
            $data_json = array(
                "success" => false,
                "message" => "Id tidak valid",
                "data" => null
            );

            $this->response($data_json, REST_Controller::HTTP_OK);
            $this->output->_display();
            exit();
        }

        $data_json = array(
            "success" => true,
            "message" => "Delete Berhasil",
            "data" => array(
                "item_transaksi" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }
    
}
