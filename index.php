<?php


/**
 * 设置源静态文件的根目录的URL地址
 * */
define('STATIC_URL','http://www.baidu.com/');

/**
 * SAE storage的domain
 * */
define('DOMAIN','cdn');

/**
 * 空请求时是否显示文档
 * */
define('WELCOME_DOC',TRUE);

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

define('IS_SAE', defined('SAE_SECRETKEY'));

require_once BASE_PATH.'include/start.php';

