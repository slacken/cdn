<?php

if ( ! defined('BASE_PATH')) exit('No direct script access allowed');

class StorageHandle{
	
	public $instance;
	
	public $domain;
	
	public function __construct(){
		$this->domain = DOMAIN;
		require dirname(__FILE__).'/bcs/bcs.class.php';
		$this->instance = new BaiduBCS();
	}
	
	public function exists($filename){
		return $this->instance->is_object_exist($this->domain,$this->get_file($filename));
	}
	//这里是效率瓶颈啊！！
	public function read($filename){
		return $this->instance->get_object($this->domain,$this->get_file($filename));
	}
	
	public function write($name,$content){
		return $this->instance->create_object_by_content($this->domain,$this->get_file($name),$content);
	}
	
	public function url($name){
		//return $this->instance->getUrl($this->domain,$this->get_file($name));
		return 'http://bcs.duapp.com/'.$this->domain.$this->get_file($name);
	}
	
	public function error(){
		return false;
	}
	
	public function delete($name){
		return $this->instance->delete_object($this->domain,$this->get_file($name));
	}
	
	private function get_file($name){
		return '/'.ltrim($name,'/');
	}
}