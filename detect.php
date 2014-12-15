<?php

$arrData = array(
	'cmdCode' => '!xECN',//$_GET['cmd'],
	'cmdParam' => preg_replace('/[^-a-zA-Z0-9_]/', '', $_GET['param']),
	'errCode' => 0,
	'errMessage' => '',
	);

$fp = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP); 
socket_set_option($fp, SOL_SOCKET, SO_BROADCAST, 1); 

if (sendISCP($fp, $arrData['cmdCode'] . $arrData['cmdParam']) == true) {
	$arrData['errMessage'] = 'Success';
}
else {
	$arrData['errMessage'] = 'Error';
}

@fclose($fp);

echo json_encode($arrData);

function sendISCP($fp, $request) {
	global $arrData;

	$length	= strlen($request) + 1;

	$command  = "ISCP\x00\x00\x00\x10\x00\x00\x00" . chr($length) . "\x01\x00\x00\x00" . $request . "\x0D";
	
	socket_sendto($fp, $command, $length + 16, 0, '255.255.255.255', 60128); 
	$from = '';
	$port = 0;
	$tries = 10;

	while ($tries--) {
		$fRecv = socket_recvfrom($fp, $buff, 64, 0, $from, $port);
		if ($fRecv > 0) { break; }
		sleep(1);
	}
	$reply_len = ord($buff[11]);
	$arrData['statusReply'] = substr($buff, 21, $reply_len);
	$info = explode('/', $arrData['statusReply']);
	$arrData['modelName'] = $info[0];
	$arrData['modelIP'] = $from;
	$arrData['modelPort'] = $info[1];
	$arrData['modelCountry'] = Code2Country($info[2]);
	$arrData['modelMAC'] = join(':', str_split(substr($info[3], 0, 12), 2));

	return $fRecv > 0 ? true : false;
}

function Code2Country($code) {
	switch ($code) {
		case 'DX':
			return 'North America';
		case 'XX':
			return 'Europe/Asia';
		case 'JJ':
			return 'Japan';
		default:
			return 'Unknown';
	}
}

?>