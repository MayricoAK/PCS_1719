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

    //mengecek token
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

    //menampilkan admin
    public function admin_get(){   
        //mengecek token
        $this->cekToken();

        //memanggil data admin dari Model
        $data = $this->M_admin->getData();

        //menampilkan data
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
        //menangkap data
        $data = array(
            'email'     => $this    ->post('email'),
            'password'  => md5($this->post('password')), //hash password
            'nama'      => $this    ->post('nama')
        );

        //memproses add data dengan fungsi insertData di Madmin
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

        //jika tidak valid
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
        //menangkap data
        $data = array(
            "email"     => $this    ->put("email"),
            "password"  => md5($this->put("password")),
            "nama"      => $this    ->put("nama")
        );

        $id = $this->put("id");
        
        //memproses update data dengan fungsi updateAdmin di Madmin
        $result = $this->M_admin->updateAdmin($data, $id);

        //berhasil
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
        //mengecek token
        $this->cekToken();
        //menangkap id yang di hapus
        $id = $this->delete("id");
        //Proses hapus data admin dengan fungsi deleteAdmin di Madmin
        $result = $this->M_admin->deleteAdmin($id);

        //jika gagal
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

        //jika berhasil
        $data_json = array(
            "success"   => true,
            "message"   => "Delete Berhasil",
            "data"      => array(
            "admin"     => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //login admin
    public function login_post(){
        //tangkap data 
        $data = array(
            "email"     => $this    ->input->post("email"),
            "password"  => md5($this->input->post("password"))
        );
        //proses login
        $result = $this->M_admin->cekLoginAdmin($data);

        if (empty($result)) {
            //jika tidak valid
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
            //jika valid
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

    //produk

    //menampilkan produk
    public function produk_get(){
        $this->cekToken();
        //memanggil data produk 
        $result = $this->M_produk->getProduk();
        //menampilkan data produk
        $data_json = array(
            "success" => true,
            "message" => "Data found",
            "data" => array(
                "produk" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //ADD data produk POST
    public function produk_post(){   
        // cek token
        $this->cekToken();
        // validasi
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
        //jika tidak valid
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
        //valid
        //menangkap data
        $data = array(
            'admin_id'  => $this->input->post('admin_id'),
            'nama'      => $this->input->post('nama'),
            'harga'     => $this->input->post('harga'),
            'stok'      => $this->input->post('stok')
        );
        //memproses add data dengan fungsi insertProduk di Mproduk
        $result = $this->M_produk->insertProduk($data);
        //menampilkan 
        $data_json = array(
            "success" => true,
            "message" => "insert Berhasil",
            "data" => array(
                "produk" => $result
            )
        );
        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //mengedit produk
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

        //tidak valid
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

        //valid
        //menangkap data
        $data = array(
            'admin_id'  => $this->put('admin_id'),
            'nama'      => $this->put('nama'),
            'harga'     => $this->put('harga'),
            'stok'      => $this->put('stok')
        );

        $id = $this->put("id");

        //memproses update data dengan fungsi updateProduk di Mproduk
        $result = $this->M_produk->updateProduk($data, $id);

        //berhasil
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
        //mengecek token 
        $this->cekToken();
        //menangkap id produk yang ingin di hapus
        $id = $this->delete("id");
        //Proses hapus data produk dengan fungsi deleteProduk di Mproduk
        $result = $this->M_produk->deleteProduk($id);

        //jika gagal/tidak valid
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

        //jika berhasil
        $data_json = array(
            "success" => true,
            "message" => "Delete Berhasil",
            "data" => array(
                "produk" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //menampilkan transaksi
    public function transaksi_get(){   
        //mengecek token
        $this->cekToken();

        //memanggil data produk dari Model
        $data = $this->M_transaksi->getTransaksi();

        //menampilkan data
        $data_json = array(
            "success" => true,
            "message" => "Data found",
            "data" => $data
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //tambah transaksi
    public function transaksi_post(){   
        //mengecek token
        $this->cekToken();
        //validasi
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

        //jika tidak valid
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

        //menangkap data
        $data = array(
            'admin_id'  => $this->input->post('admin_id'),
            'total'     => $this->input->post('total'),
            'tanggal'   => date("Y-m-d H:i:s")
        );

        //insert produk dengan fungsi insertTransaksi di Mtransaksi
        $result = $this->M_transaksi->insertTransaksi($data);

        //show if data valid
        $data_json = array(
            "success"   => true,
            "message"   => "Insert Berhasil",
            "data"      => array(
            "transaksi" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //mengedit transaksi
    public function transaksi_put(){   
        //cektoken
        $this->cekToken();
        //validasi
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

        //jika tidak valid
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

        //tangkap data
        $data = array(
            'admin_id'  => $this->put("admin_id"),
            'total'     => $this->put("total"),
            'tanggal'   => date("Y-m-d H:i:s")
        );

        //update data transaksi dengan fungsi updateTransaksi di Mtransaksi
        $id = $this->put("id");
        $result = $this->M_transaksi->updateTransaksi($data, $id);
        
        //jika berhasil
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
        //mengecek token
        $this->cekToken();
        //menangkap id yang di hapus
        $id = $this->delete("id");
        //proses hapus data transaksi dengan fungsi deleteTransaksi di Mtransaksi
        $result = $this->M_transaksi->deleteTransaksi($id);

        //jika tidak valid
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

        //jika berhasil
        $data_json = array(
            "success" => true,
            "message" => "Delete Berhasil",
            "data" => array(
            "transaksi" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //memanggil data transaksi yang terjadi selama satu bulan terakhir 
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
   
    //item transaksi

    //menampilkan data item transaksi
    public function item_transaksi_get(){   
        //mengecek token
        $this->cekToken();

        //memanggil data item transaksi dari Model 
        $result = $this->M_item_transaksi->getitemtransaksi();

        //menampilkan data
        $data_json = array(
            "success" => true,
            "message" => "Data found",
            "data" => array(
                "item_transaksi" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //menampilkan data item transaksi berdasarkan id
    public function item_transaksi_by_transaksi_id_get(){   
        //mengecek token
        $this->cekToken();
        //memanggil data transaksi by id dari Model
        $result = $this->M_item_transaksi->getitemtransaksibytransaksiID($this->input->get('transaksi_id'));
        
        //menampilkan
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
        //cek token
        $this->cekToken();
        //validation
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

        //jika tidak valid
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
        //valid
        //menangkap data
        $data = array(
            'transaksi_id' => $this->input->post('transaksi_id'),
            'produk_id' => $this->input->post('produk_id'),
            'qty' => $this->input->post('qty'),
            'harga_saat_transaksi' => $this->input->post('harga_saat_transaksi'),
            'sub_total' => $this->input->post('qty') * $this->input->post('harga_saat_transaksi')
        );

        //add data item transaksi dengan fungsi insertitemtransaksi di Mitemtransaksi
        $result = $this->M_item_transaksi->insertitemtransaksi($data);

        //berhasil
        $data_json = array(
            "success" => true,
            "message" => "Insert Berhasil",
            "data" => array(
                "item_transaksi" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //mengedit data
    public function item_transaksi_put(){   
        //cektoken
        $this->cekToken();
        //validasi
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
        
        //jika tidak valid
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

        //valid
        //menangkap data
        $data = array(
            'transaksi_id' => $this->put('transaksi_id'),
            'produk_id' => $this->put('produk_id'),
            'qty' => $this->put('qty'),
            'harga_saat_transaksi' => $this->put('harga_saat_transaksi'),
            'sub_total' => $this->put('qty') * $this->put('harga_saat_transaksi')
        );

        $id = $this->put("id");
        //update data item transaksi
        $result = $this->M_item_transaksi->updateitem_transaksi($data, $id);
        
        //jika berhasil
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
        //cektoken
        $this->cekToken();
        //menangkap data id
        $id = $this->delete("id");

        //proses delete
        $result = $this->M_item_transaksi->deleteitem_transaksi($id);
        
        //jika gagal
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

        //jika berhasil
        $data_json = array(
            "success" => true,
            "message" => "Delete Berhasil",
            "data" => array(
                "item_transaksi" => $result
            )
        );

        $this->response($data_json, REST_Controller::HTTP_OK);
    }

    //delete item transaksi by transaksi id
    public function item_transaksi_by_transaksi_id_delete(){   
        //cektoken
        $this->cekToken();
        //tangkap data
        $transaksi_id = $this->delete("transaksi_id");

        //proses delete where transaksi id
        $result = $this->M_item_transaksi->deleteitem_transaksibytransaksiID($transaksi_id);
        
        //jika gagal
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

        //jika berhasil
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
