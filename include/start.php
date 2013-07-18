<?php
//
if ( ! defined('BASE_PATH')) exit('No direct script access allowed');

/**
 * 自动加载
 * */
function includeloader($class){
	$path = BASE_PATH."include/{$class}.php";
	if (is_readable($path)) require $path;
}

spl_autoload_register('includeloader');
//die($_SERVER['QUERY_STRING']);
new controller(isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'');
//print_r($_SERVER);die();
//$url = $_SERVER['DOCUMENT_ROOT'].(isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'');
//$url = str_ireplace('\\','/',$url);
//$base = str_ireplace('\\','/',BASE_PATH);
//$test = (explode($base,$url));
//new controller($test[1]);	//做path_info兼容