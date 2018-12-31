<?php
	require_once 'db_operation.php';
	$opr = new db_operation();
	$res = array();

	if ($_SERVER['REQUEST_METHOD']=='POST') {
		$idPesan = $_POST['id_pesan'];
		$statusPesan = $_POST['status_pesan'];

		$result = $opr->updatePesanan($idPesan, $statusPesan);

		if ($result == 0) {
			$res['error'] = false;
			$res['message'] = 'Order upadated successfully!';
		} elseif ($result == 1) {
			$res['error'] = true;
			$res['message'] = 'Update order : failure!';
		}

		header('Content-Type: application/json');
		echo json_encode($res);
	} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
		$arr = [];
		$index = 0;

		if ($_GET['get'] == 'pesanan') {
			$data = $opr->getPesananData();
			while ($row = $data->fetch_assoc()) {
				$arrayObject = (array('pesanan_id' => $row['pesnan_id'], 'no_meja' => $row['meja_no'],'pesanan_tgl'  => $row['pesanan_tgl'], 'pesanan_total'  => $row['pesanan_jumlah'], 'pesanan_status'  => $row['pesanan_status']));
				$arr[$index] = $arrayObject;
				$index++;

			}
			header('Content-Type: application/json');
			echo json_encode($arr);
		} elseif ($_GET['get'] == 'detailpesan') {
			$data = $opr->getDetailPesan($_GET['id']);
			header('Content-Type: application/json');
			echo $data;
		}
	}
?>