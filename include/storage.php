<?php

if ( ! defined('BASE_PATH')) exit('No direct script access allowed');

/**
 * 封装storage
 * */
class storage{
	public static function gethandle(){
		$include_dir = dirname(__FILE__).'/storages/';
		require($include_dir.ucfirst(strtolower(RUN_ENV.'.php')));
		return new StorageHandle();
		//$this->instance = new SaeStorage(SAE_ACCESSKEY,SAE_SECRETKEY);
	}
	
}