<?php

// DATABASE CONFIGURATION

function defineDatabaseConfig(string $domain)
{
	$production = [
		// DEVELOPMENT CONFIG
		'localhost' => [
			'DB_HOST' => 'localhost',
			'DB_NAME' => 'masterdb',
			'DB_USERNAME' => 'root',
			'DB_PASSWORD' => '',
			'DB_PORT' => '3306',
		],

		// PRODUCTION CONFIG
		'mumsystem.com' => [
			'DB_HOST' => 'localhost',
			'DB_NAME' => 'u547529777_mumdb',
			'DB_USERNAME' => 'u547529777_mum_user',
			'DB_PASSWORD' => 'MargaUtama__20240911',
			'DB_PORT' => '3306',
		],

		'ptmargautama.kesug.com' => [
			'DB_HOST' => 'awl.h.filess.io',
			'DB_NAME' => 'ptmargautama_noseglass',
			'DB_USERNAME' => 'ptmargautama_noseglass',
			'DB_PASSWORD' => 'f1966534e3efbb5f6ef11edccd3f89459d691180',
			'DB_PORT' => '3305',
		],
	];

	if (isset($production[$domain])) {
		return $production[$domain];
	} else {
		return false;
	}
}


$config = defineDatabaseConfig(preg_replace('/^www./', '', $_SERVER['SERVER_NAME']));

if ($config) {
	// DB_HOST
	define('DB_HOST', $config['DB_HOST']);
	// DB_NAME
	define('DB_NAME', $config['DB_NAME']);

	try {
		$db = new PDO("mysql:host={$config['DB_HOST']};port={$config['DB_PORT']}; dbname={$config['DB_NAME']}", $config['DB_USERNAME'], $config['DB_PASSWORD']);
	} catch (PDOException $e) {
		echo $e->getMessage();
		$db = null;
	}
}
