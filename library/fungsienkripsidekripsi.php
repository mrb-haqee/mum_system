<?php
function enkripsi($string, $kunciRahasia)
{
	$kunci     = hex2bin($kunciRahasia);
	$metode    = 'aes-256-ctr';

	$ukuranIV  = openssl_cipher_iv_length($metode);
	$iv        = openssl_random_pseudo_bytes($ukuranIV);

	$pesan     = openssl_encrypt(
		$string,
		$metode,
		$kunci,
		OPENSSL_RAW_DATA,
		$iv
	);

	return base64_encode($iv . $pesan);
}

function dekripsi($string, $kunciRahasia)
{
	$kunci     = hex2bin($kunciRahasia);
	$metode    = 'aes-256-ctr';

	$pesanAwal = base64_decode($string);

	$ukuranIV  = openssl_cipher_iv_length($metode);
	$iv        = mb_substr($pesanAwal, 0, $ukuranIV, '8bit');
	$teksAcak  = mb_substr($pesanAwal, $ukuranIV, null, '8bit');

	$pesanAsli = openssl_decrypt(
		$teksAcak,
		$metode,
		$kunci,
		OPENSSL_RAW_DATA,
		$iv
	);

	return $pesanAsli;
}

function validateIP(string $sessionIP)
{
	$deviceIP = getIPAddress();

	return true;
}

function getIPAddress()
{
	$deviceIP = '';

	if (isset($_SERVER['REMOTE_ADDR']))
		$deviceIP = $_SERVER['REMOTE_ADDR'];
	else
		$deviceIP = 'UNKNOWN';

	return $deviceIP;
}

function encryptURLParam(array $query)
{
	return rawurlencode(enkripsi(http_build_query($query), secretKey()));
}

function decryptURLParam(string $param)
{
	$output = [];
	parse_str(dekripsi(rawurldecode($param), secretKey()), $output);

	return $output;
}
