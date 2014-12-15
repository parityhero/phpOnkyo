<?php

$arrData = array(
	'cmdCode' => preg_replace('/[^-a-zA-Z0-9_]/', '', $_GET['cmd']),
	'cmdParam' => preg_replace('/[^-a-zA-Z0-9_]/', '', $_GET['param']),
	'ipAddress' => $_GET['ip'],
	'portNumber' => (int)$_GET['port'],
	'errCode' => 0,
	'errMessage' => '',
	);

if (($fp = @stream_socket_client('tcp://' . $arrData['ipAddress'] . ':' . $arrData['portNumber'], $arrData['errCode'], $arrData['errMessage'], 10)) === false) {
	echo json_encode($arrData);
	die();
}

if ($arrData['cmdCode'] == 'MVL') {
	$arrData['cmdParam'] = min($arrData['cmdParam'], 64);
	$arrData['cmdParam'] = str_pad($arrData['cmdParam'], 2, '0', STR_PAD_LEFT);
}

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

	$length	= strlen($request) + 3;
	//$total	= $length + 16;

	$command  = "ISCP\x00\x00\x00\x10\x00\x00\x00" . chr($length) . "\x01\x00\x00\x00!1" . $request . "\x0D";
	$fSent = @fwrite($fp, $command) > 0 ? true : false;
	$reply = @fread($fp, 100);
	$reply_len = ord($reply[11]) - 5;
	
	$arrData['statusReply'] = ltrim(substr($reply, 18, $reply_len), $arrData['cmdCode']);

	switch ($arrData['cmdCode']) {
		case 'SLI':
			$arrData['statusReply'] = SLICode2String($arrData['statusReply']);
			break;
		case 'LMD':
			$arrData['statusReply'] = LMDCode2String($arrData['statusReply']);
			break;
		default:
			break;
	}

	return $fSent;
}

function SLICode2String($code) {
	$aInputs = array('00' =>'STB/DVR', '01' => 'CBL/SAT', '02' => 'GAME',  '03' => 'AUX',  '04' => 'AUX2',  '05' => 'PC',  '06' => 'VIDEO7',  '07' => 'EXTRA1',  '08' => 'EXTRA2',  '09' => 'EXTRA3',  '10' => 'BD/DVD',  '20' => 'TAPE',  '21' => 'TAPE2',  '22' => 'PHONO',  '23' => 'TV/CD',  '24' => 'FM RADIO',  '25' => 'AM RADIO',  '26' => 'TUNER',  '27' => 'DLNA',  '28' => 'iRadio',  '29' => 'USB/USB(front)',  '2A' => 'USB(rear)',  '2B' => 'NETWORK',  '2C' => 'USB(toggle)',  '2D' => 'AIRPLAY',  '40' => 'UNIVERSAL PORT',  '30' => 'MULTI CH',  '31' => 'XM RADIO',  '32' => 'SIRIUS RADIO',  '33' => 'DAB');
	
	return $aInputs[$code];
}


function LMDCode2String($code) {
	$aInputs = array('00' =>'STEREO', '01' => 'DIRECT', '02' => 'SURROUND',  '03' => 'FILM',  '04' => 'THX',  '05' => 'ACTION',  '06' => 'MUSICAL',  '07' => 'MONO MOVIE',  '08' => 'ORCHESTRA',  '09' => 'UNPLUGGED',  '0A' => 'STUDIO-MIX',  '0B' => 'TV LOGIC',  '0C' => 'ALL CH STEREO',  '0D' => 'THEATER-DIMENSIONAL',  '0E' => 'ENHANCED',  '0F' => 'MONO',  '11' => 'PURE AUDIO',  '12' => 'MULTIPLEX',  '13' => 'FULL MONO',  '14' => 'DOLBY VIRTUAL',  '15' => 'DTS SURROUND SENSATION',  '16' => 'AUDYSSEY DSX',  '1F' => 'WHOLE HOUSE MODE',  '23' => 'STAGE',  '25' => 'ACTION',  '26' => 'MUSIC',  '2E' => 'SPORTS', '40' => 'STRAIGHT DECODE', '41' => 'DOLBY EX', '42' => 'THX CINEMA', '43' => 'THX SURROUND EX', '44' => 'THX MUSIC', '45' => 'THX GAMES', '50' => 'THX S CINEMA2', '51' => 'THX S MUSIC', '52' => 'THX S GAMES', '80' => 'PLII MOVIE', '81' => 'PLII MUSIC', '82' => 'NEO:6 CINEMA', '83' => 'NEO:6 MUSIC', '84' => 'PLII THX CINEMA', '85' => 'NEO:6 THX CINEMA', '86' => 'PLII GAME', '89' => 'PLII/THX GAME', '8A' => 'NEO:6/NEO:THX GAME', '8B' => 'PLII THX MUSIC');
	
	return $aInputs[$code];
}

?>