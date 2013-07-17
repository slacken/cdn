<?php
@ob_start();

/**
 * 设置源静态文件的根目录的URL地址
 * */
define('STATIC_URL','http://bbs.its.csu.edu.cn/');
define('STATIC_HOST','');	//特殊应用下可以填写源站域名，会作为http头的hosts传递，正常情况请留空

//define('RUN_ENV', 'BAE');	//自定义环境（如不去掉前面的//则自动判断）可选：BAE/SAE/LOCAL 请大写

/**
 * SAE storage的domain，BAE的bucket，本地的存储路径（相对于index.php的相对目录，前无斜杠后有斜杠）
 * */
define('DOMAIN','cdn');	//SAE or BAE
//define('DOMAIN','data/cache/');	//本地

define('PURGE_KEY','purge');	//访问http://domain/PURGE_KEY/path/to/file来刷新缓存

define('ALLOW_REGX','.*');	//设置防盗链允许的[域名]正则表达式
//define('ALLOW_DOMAIN','^(best33\.com|.*\.best33\.com|)$');	//允许best33.com，*.best33.com，浏览器直接访问
//define('ALLOW_DOMAIN','^(best33\.com|.*\.best33\.com)$');	//允许best33.com，*.best33.com，不允许浏览器直接访问
//define('ALLOW_DOMAIN','^(.*)$');	//允许任意，允许浏览器访问
//define('ALLOW_DOMAIN','^(.+)$');	//允许任意，但不允许浏览器访问

define('MIME','text/html');	//默认MIME类型，可以设为application/octet-stream则对未知项目自动弹出下载
define('DIRECT_EXT','php|html');	//不进入缓存的扩展名

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

//define('IS_SAE', defined('SAE_SECRETKEY'));

//自定义环境
//define('RUN_ENV', 'BAE');	//可选：BAE/SAE/LOCAL 请大写

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

