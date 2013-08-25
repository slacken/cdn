<?php

if ( ! defined('BASE_PATH')) exit('No direct script access allowed');

/**
 * 封装SAE storage
 * */
class StorageHandle{
	
	public $instance;
	
	public $domain;
	
	public function __construct(){
		$this->domain = DOMAIN;
		$this->instance = new SaeStorage(SAE_ACCESSKEY,SAE_SECRETKEY);
	}
	
	public function exists($filename){
		return $this->instance->fileExists($this->domain,$filename);
	}
	//这里是效率瓶颈啊！！
	public function read($filename){
		return $this->instance->read($this->domain,$filename);
	}
	
	public function write($name,$content){
		return $this->instance->write($this->domain,$name,$content);
	}
	
	public function url($name){
		return $this->instance->getUrl($this->domain,$name);
	}
	
	public function error(){
		return $this->instance->error();
	}
	
	public function delete($name){
		return $this->instance->delete($this->domain,$name);
	}
	
}