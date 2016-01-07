<?php
elevationAPI();
/**
 * 標高API
 */
function elevationAPI() {
	if (preg_match('/\b(gzip|deflate)\b/i', $_SERVER['HTTP_ACCEPT_ENCODING'], $matches)) {
		// 圧縮可能であれば圧縮する
		ini_set("zlib.output_compression","On");
	}
	
	$result = array(
		"elevation" => "-----",
		"hsrc" => "-----"
	);
	$status = "OK";
	if (isset($_GET['lon'])){
		if (is_numeric($_GET['lon'])) {
			$lon = floatval($_GET['lon']);
		} else {
			$status = "ERROR";
		}	
	}
	if (isset( $_GET['lat'])) {
		if (is_numeric($_GET['lat'])) {
			$lat = floatval($_GET['lat']);
		} else {
			$status = "ERROR";
		}
	}
	$outtype = isset($_GET["outtype"])?$_GET["outtype"]:"";
	if ($outtype != "JSON") {
		$callback = isset($_GET["callback"])?$_GET["callback"]:"";
		if (!empty($callback)) {
			if (preg_match("/^([a-z]|[A-Z]|[_$])([a-z]|[A-Z]|[_$]|[0-9]){0,}$/",$callback) === 0) {
				$status = "ERROR";
				sendResult($result,"");
				return;
			}
		}
	}
	
	if (isset($lon) && isset($lat)) {
		$lngRad = deg2rad( $lon );
		$latRad = deg2rad( $lat );
		$R = 128 / M_PI;
		$worldCoordX = $R * ( $lngRad + M_PI );
		$worldCoordY = ( -1 ) * $R/2 * log( ( 1+sin( $latRad ) )/( 1-sin( $latRad ) ) ) + 128;
		
		$elevation = getElevation( $worldCoordX, $worldCoordY, 15,'dem5a', 1 );
		$hsrc = "5m（レーザ）";
		
		if ($elevation == NULL) {
			$elevation = getElevation( $worldCoordX,$worldCoordY,15,'dem5b',1 );
			$hsrc = "5m（写真測量）";
		}
		
		if ($elevation == NULL) {
			$elevation = getElevation($worldCoordX, $worldCoordY, 14, 'dem',0);
			$hsrc = "10m";
		}
		
		if ($elevation == NULL) {
			$elevation = "-----";
			$hsrc = "-----";
			$status = "NOTFOUND";
		}
		
		$data = array(
			"elevation" => $elevation,
			"hsrc" => $hsrc
		);
		$result = $data;
	} else {
		$status = "ERROR";
	}
	sendResult($result,$callback);
}
/**
 * JSONデータを送信
 * @param array $result 送信する処理結果
 * @param callback $callback JSONPで送信する際の関数名
 */
function sendResult(&$result, $callback) {
	header('Content-type: application/json; charset=utf-8');
	if (!empty($callback)) {
		echo $callback . "(";
	} else {
		header('Access-Control-Allow-Origin: *');		
	}
	if ($result != null) {
		$jsonData = json_encode($result);
		echo $jsonData;
	} else {
		echo "{}";
	}
	if (!empty($callback)) {
		echo ")";
	}
}
/**
 * 標高タイルから標高値を取得
 * @param float $worldCoordX X座標
 * @param float $worldCoordY Y座標
 * @param int $zoom ズームレベル
 * @param string $demSource データソース
 * @param int $dataRound 小数点以下の桁数
 * @return float 標高データ
 */
function getElevation($worldCoordX, $worldCoordY, $zoom, $demSource, $dataRound) {
	$pixelX = $worldCoordX * pow(2, $zoom);
	$tileX = floor($pixelX / 256);
	$pixelY = $worldCoordY * pow(2, $zoom);
	$tileY = floor($pixelY / 256);
	$px = floor($pixelX) % 256;
	$py = floor($pixelY) % 256;
	$serverUrl = "http://cyberjapandata.gsi.go.jp/xyz/".$demSource."/".$zoom."/".$tileX."/".$tileY.".txt";

	$context = stream_context_create(
			array(
				'http' => array('ignore_errors' => true)
			)
	);
	$receivedData = file_get_contents( $serverUrl, false, $context );

	if ( !$receivedData ) {
		return NULL;
	}

	preg_match('/HTTP\/1\.[0|1|x] ([0-9]{3})/', $http_response_header[0], $matches);
	$statusCode = $matches[1];

	switch ($statusCode) {
		case '200':
			break;
		case '404':
			return NULL;
		default:
			return NULL;
	}

	$receivedData = preg_replace("/\r\n|\r|\n/", "\n", $receivedData);
	$lines = explode( "\n",	$receivedData );
	if ( count( $lines ) < $py ) {
		return NULL;		
	}		
	$linePy = $lines[$py];
	$pxs = explode(",", $linePy);
	if (count($pxs) < $px) {
		return NULL;
	}
	$sPx = $pxs[$px];
	if ($sPx == "e") {
		return NULL;
	}

	$elevation = floatval( $sPx );
	$elevation = round( $elevation, $dataRound );
	if ( $elevation <- 500 ) {
		$elevation = NULL;
	}
	return $elevation;
}