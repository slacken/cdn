<?php

if ( ! defined('BASE_PATH')) exit('No direct script access allowed');

class StorageHandle{
	
	public $instance;
	
	public function __construct(){
		//判断环境
		@include('BaeMemcache.class.php');
		if(class_exists('BaeMemcache')){
			$this->instance = new BaeMemcache();
		}elseif(class_exists('Memcache')){
			$this->instance = new Memcache();
			if(method_exists('Memcache','init')){
				$this->instance->init();
			}else{
				$this->instance->connect(defined(CS_AK)?CS_AK:'127.0.0.1',defined(CS_SK)?CS_SK:'11211');
			}
		}else{
			die('No memcache.');
		}
	}
	
	public function exists($filename){
		return $this->instance->get($this->get_file($filename));
	}
	public function read($filename){
		return $this->instance->get($this->get_file($filename));
	}
	
	public function write($name,$content){
		return $this->instance->set($this->get_file($name),$content);
	}
	
	public function url($name){
		return false;
	}
	
	public function error(){
		return false;
	}
	
	public function delete($name){
		return $this->instance->delete($this->get_file($name));
	}
	
	private function get_file($name){
		return md5($name);
	}
}