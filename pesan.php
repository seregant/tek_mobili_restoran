<?php 
	require_once 'db_operation.php';
	
	$opr = new db_operation();

	$makanan = array();
	$minuman = array();
	$meja;
	$res = array();

	if ($_SERVER['REQUEST_METHOD']=='POST') {
		$makanan = json_decode($_POST['makanan'], true);
		$minuman = json_decode($_POST['minuman'], true);
		$meja = $_POST['meja'];

		$result = $opr->tambahPesanan($makanan,$minuman,$meja);

		if ($result == 0) {
			$res['error'] = false;
			$res['message'] = 'Order added successfully!';
		} elseif ($result == 1) {
			$res['error'] = true;
			$res['message'] = 'Adding order : failure!';
		}

		header('Content-Type: application/json');
		echo json_encode($res);
	} elseif ($_SERVER['REQUEST_METHOD']=='GET') {
		$data = $opr->getMenuData($_GET['menu']);
		$arr = [];
		$index = 0;
		while ($row = $data->fetch_assoc()) {
			$arrayObject = (array('item_id' => $row[$_GET['menu'].'_id'], 'item_nama' => $row[$_GET['menu'].'_nama'], 'item_harga' => $row[$_GET['menu'].'_harga'], 'item_stok' => $row[$_GET['menu'].'_stok']));
			$arr[$index] = $arrayObject;
			$index++;
		}

		header('Content-Type: application/json');
		echo json_encode($arr);
	}
?>