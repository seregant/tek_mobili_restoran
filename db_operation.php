<?php

class db_operation {
	private $con;

	function __construct() {
		require_once dirname(__FILE__). '/db_connect.php';

		$db = new db_connect();
		$this->con = $db->connect();
	}

	public function getMenuData($dataKind){
		if ($dataKind == 'makanan') {
			$query = $this->con->prepare("SELECT * FROM res_makanan");
		} else {
			$query = $this->con->prepare("SELECT * FROM res_minuman");
		}

		$query->execute();
		$result = $query->get_result();
		return $result;
	}

	public function getPesananData(){
		$query = $this->con->prepare("SELECT * FROM res_pesanan");
		$query->execute();
		return $query->get_result();
	}

	public function getDetailPesan($id){
		$makanan = $this->con->prepare("
			SELECT
				`res_makanan`.`makanan_nama`,
				`res_pesan_makanan`.`jumlah`
			FROM `res_makanan`
			INNER JOIN `res_pesan_makanan` ON `res_pesan_makanan`.`makanan_id` = `res_makanan`.`makanan_id`
			WHERE `res_pesan_makanan`.`pesanan_id` = ?
		");

		$makanan->bind_param("s",$id);
		$makanan->execute();
		$dtMakanan = $makanan->get_result();
		$makanan->close();

		$minuman = $this->con->prepare("
			SELECT
				`res_minuman`.`minuman_nama`,
				`res_pesan_minuman`.`jumlah`
			FROM `res_minuman`
			INNER JOIN `res_pesan_minuman` ON `res_pesan_minuman`.`minuman_id` = `res_minuman`.`minuman_id`
			WHERE `res_pesan_minuman`.`pesanan_id` = ?
		");

		$minuman->bind_param("s",$id);
		$minuman->execute();
		$dtMinuman = $minuman->get_result();
		$minuman->close();

		$arr['makanan'] = "";
		$arr['jml_makanan'] = "";
		$arr['minuman'] = "";
		$arr['jml_minuman'] = "";

		while ($row = $dtMakanan->fetch_assoc()) {
			$arr['makanan'] .= $row['makanan_nama']."_";
			$arr['jml_makanan'] .= $row['jumlah']."_";
		}

		while ($row = $dtMinuman->fetch_assoc()) {
			$arr['minuman'] .= $row['minuman_nama']."_";
			$arr['jml_minuman'] .= $row['jumlah']."_";
		}

		return json_encode($arr);  

	}

	public function tambahPesanan(array $makanan, array $minuman, $noMeja){
		date_default_timezone_set('Asia/Jakarta');
		$hrgMakanan = 0;
		$hrgMinuman = 0;
		$dateTime = date('Y/m/d H:i:s', time());
		$psnRows = $this->con->prepare("SELECT * FROM res_pesanan");
		$psnRows->execute();
		$psnRows->store_result();
		$rows = $psnRows->affected_rows + 1;

		$insPesanan = $this->con->prepare("INSERT INTO res_pesanan (pesnan_id,pesanan_tgl,meja_no,pesanan_status) VALUES (?,?,?,?)");
		$insPesanan->bind_param("ssss",$rows,$dateTime,$noMeja,$a=0);
		
		if ($insPesanan->execute()) {
			foreach ($makanan as $list) {
				$query = $this->con->prepare("SELECT makanan_harga FROM res_makanan WHERE makanan_id = ?");
				$query->bind_param("s",$list['id']);
				$query->execute();
				$result = $query->get_result();
				$harga = $result->fetch_assoc();
				$hrgMakanan += $harga['makanan_harga']*$list['jumlah'];

				$query2 = $this->con->prepare("INSERT INTO res_pesan_makanan (pesanan_id, makanan_id, jumlah) VALUES (?,?,?)");
				$query2->bind_param("sss",$rows,$list['id'],$list['jumlah']);
				
				if (!$query2->execute()) {
					return 1; //it means failure
				}
			}

			foreach ($minuman as $list) {
				$query = $this->con->prepare("SELECT minuman_harga FROM res_minuman WHERE minuman_id = ?");
				$query->bind_param("s",$list['id']);
				$query->execute();
				$result = $query->get_result();
				$harga = $result->fetch_assoc();
				$hrgMinuman += $harga['minuman_harga']*$list['jumlah'];

				$query2 = $this->con->prepare("INSERT INTO res_pesan_minuman (pesanan_id, minuman_id, jumlah) VALUES (?,?,?)");
				$query2->bind_param("sss",$rows,$list['id'],$list['jumlah']);
				
				if (!$query2->execute()) {
					return 1; //it means failure
				}
			}

			$hrgTotal = $hrgMakanan + $hrgMinuman;
			
			$query = $this->con->prepare("UPDATE res_pesanan SET pesanan_jumlah = ? WHERE pesnan_id = ?");
			$query->bind_param("ss",$hrgTotal,$rows);
			if (!$query->execute()) {
				return 1; //it means failure		
			}	
		} else {
			return 1; //it means failure
		}

		return 0; //it means ok
	}

	public function updatePesanan($pesananId,$statusSet)
	//status 1 = lagi dimasak
	//status 2 = sudah siap
	//status 3 = pesanan batal
	{
		$query = $this->con->prepare("UPDATE res_pesanan SET pesanan_status = ? WHERE pesnan_id = ?");
		$query->bind_param("ss",$statusSet,$pesananId);
		if ($query->execute()) {
			$query->close();

			if ($statusSet == 1) {
				$jmlMinuman = $this->con->prepare("SELECT minuman_id, jumlah FROM res_pesan_minuman WHERE pesanan_id = ?");
				$jmlMinuman->bind_param("s",$pesananId);
				$jmlMinuman->execute();
				$resJminuman = $jmlMinuman->get_result();
				$jmlMinuman->close();

				while ($row = $resJminuman->fetch_assoc()) {
					$getCurStock = $this->con->prepare("SELECT minuman_stok FROM res_minuman WHERE minuman_id = ?");
					$getCurStock->bind_param("s",$row['minuman_id']);
					$getCurStock->execute();
					$curStock = $getCurStock->get_result();
					$getCurStock->close();

					$curStock = $curStock->fetch_assoc();

					$stock = $curStock['minuman_stok'] - $row['jumlah'];

					$updtMinuman = $this->con->prepare("UPDATE res_minuman SET minuman_stok = ? WHERE minuman_id = ?");
					$updtMinuman->bind_param("ss",$stock,$row['minuman_id']);
					if (!$updtMinuman->execute()) {
						return 1; // it means failure
					}
				}

				$jmlMakanan = $this->con->prepare("SELECT makanan_id, jumlah FROM res_pesan_makanan WHERE pesanan_id = ?");
				$jmlMakanan->bind_param("s",$pesananId);
				$jmlMakanan->execute();
				$resJmakanan = $jmlMakanan->get_result();
				$jmlMakanan->close();

				while ($row = $resJmakanan->fetch_assoc()) {
					$getCurStock = $this->con->prepare("SELECT makanan_stok FROM res_makanan WHERE makanan_id = ?");
					$getCurStock->bind_param("s",$row['makanan_id']);
					$getCurStock->execute();
					$curStock = $getCurStock->get_result();
					$getCurStock->close();

					$curStock = $curStock->fetch_assoc();

					$stock = $curStock['makanan_stok'] - $row['jumlah'];

					$updtMinuman = $this->con->prepare("UPDATE res_makanan SET makanan_stok = ? WHERE makanan_id = ?");
					$updtMinuman->bind_param("ss",$stock,$row['makanan_id']);
					if (!$updtMinuman->execute()) {
						return 1; // it means failure
					}
				}

				return 0; //it means ok
			}
			
		} else {
			return 1; //it means failure
		}
	}

	public function getMenuDetail($type, $id){
		$sql = "SELECT * FROM res_".$type." WHERE ".$type."_id = ?";
		$data = $this->con->prepare($sql);
		$data->bind_param("s",$id);
		$data->execute();
		$result = $data->get_result();
		$data->close();
		return $result;
	}
}

?>