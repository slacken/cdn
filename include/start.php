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

new controller(isset($_GET['q'])?$_GET['q']:'');