<?php
@ob_start();
if(is_file('config.inc.php')){
	require 'config.inc.php';
}elseif(is_file('config.sample.inc.php')){
	require 'config.sample.inc.php';
}else{
	die('Missing Config File.');
}
/**
 * 运行环境:development/testing/production
 * */
define('ENVIRONMENT','development');

//========================================================


if (defined('ENVIRONMENT'))
{
	switch (ENVIRONMENT)
	{
		case 'development':
			error_reporting(E_ALL);
		break;
	
		case 'testing':
		case 'production':
			error_reporting(0);
		break;

		default:
			exit('The application environment is not set correctly.');
	}
}

//本地根目录
define('BASE_PATH',dirname(__FILE__).'/');

define('BASE_URL', rtrim(STATIC_URL,'/').'/');

//自动判断环境
if(!defined('RUN_ENV')){
	if(defined('SAE_SECRETKEY')){
		define('RUN_ENV','SAE');
	}elseif(getenv('HTTP_BAE_ENV_SK')){
		define('RUN_ENV','BAE');
	}else{
		define('RUN_ENV','LOCAL');
	}
}

require_once BASE_PATH.'include/start.php';

