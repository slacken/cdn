<?php

if ( ! defined('BASE_PATH')) exit('No direct script access allowed');

class StorageHandle{
	//本地存储
	var $data_dir;
	public function __construct(){
		$data_dir = BASE_PATH.DOMAIN;
		$data_dir = rtrim($data_dir,'/');
		$this->data_dir = $data_dir.'/';
	}
	public function exists($filename){
		return is_file($this->get_file($filename));
	}
	public function read($filename){
		return file_get_contents($this->get_file($filename));
	}
	public function write($filename,$content){
		return file_put_contents($this->get_file($filename),$content);
	}
	public function url($filename){
		//return false;	//不提供URL方式读取
		return rtrim(DOMAIN,'/').'/'.$this->get_file($filename,false);
	}
	public function delete($filename){
		return unlink($this->get_file($filename));
	}
	public function error(){
		return false;
	}
	private function get_file($key,$pre = true){
		if(NO_KEY || NO_SECOND_FLODER){
			$dir = dirname($this->data_dir.$key);
			if(!is_dir($dir)){
				if(!mkdir($dir,0777,true)) die(json_encode(array('error'=>'cannot_make_dir')));
			}
			if(!$pre) return $key;
			return $dir.'/'.$key;
		}
		$letter1 = substr($key,0,1);
		$letter2 = substr($key,0,2);
		$dir = $this->data_dir.$letter1.'/'.$letter2;
		if(!is_dir($dir)){
			if(!mkdir($dir,0777,true)){
				if(!$pre) return $key;
				return $this->data_dir.$key;
			}
		}
		if(!$pre) return $letter1.'/'.$letter2.'/'.$key;
		return $dir.'/'.$key;
	}
}