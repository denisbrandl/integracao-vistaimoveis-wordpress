<?php

ini_set('set_time_limit', '300');
$call = $_POST;

if (!isset($_POST) || empty($_POST)) {
	die('acesso negado');
}

// print_r($call);
// exit;

$arrPesquisa = json_decode($call['pesquisa'], true);

// echo '<pre>';
// print_r($arrPesquisa);
// echo '<hr>';

if (isset($call['imovel'])) {
	$url = sprintf(
					'%s?key=%s&imovel=%s&pesquisa=%s',
					$call['url'],
					$call['key'],
					$call['imovel'],
					json_encode(
						[
							'fields' => $arrPesquisa['fields']
						]
					)
			);
} else {
	$url = sprintf(
					'%s?key=%s&showtotal=%s&pesquisa=%s',
					$call['url'],
					$call['key'],
					$arrPesquisa['showtotal'],
					json_encode(
						[
							'fields' => $arrPesquisa['fields'],
							'filter' => $arrPesquisa['filter'],
							'order' => $arrPesquisa['order'],
							'paginacao' => $arrPesquisa['paginacao']
						]
					)
			);
}

echo $response = callCurl($url, [], 'GET');

exit;

function callCurl($url, $post = [], $metodo = 'POST') {
	$curl = curl_init();
	
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
	curl_setopt($curl, CURLOPT_TIMEOUT, 5000);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $metodo);
	if ($metodo == 'POST') {
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
	}
	curl_setopt(
		$curl,
		CURLOPT_HTTPHEADER,
		array(
			"Content-Type: application/json",
			"Accept: application/json",
		)
	);
	
	$response = curl_exec($curl);
	
	
	if (curl_error($curl)) {
		$error_msg = curl_error($curl);
	}		
	
	if (isset($error_msg)) {
		print "Erro no curl";
		print $error_msg;
		exit;
	}
	
	curl_close($curl);
	
	
	return $response;
}