<?php

if ( ! defined('BASE_PATH')) exit('No direct script access allowed');

class StorageHandle{
	
	public $instance;
	
	public $headurl;
	
	public function __construct(){
		//$this->domain = DOMAIN;
		require dirname(__FILE__).'/gcs/GrandCloudStorage.php';
		$this->instance =  new GrandCloudStorage('http://storage.grandcloud.cn');
		$this->instance->set_key_secret(CS_AK, CS_SK);
		$this->instance->set_bucket(DOMAIN);
		$this->headurl = 'http://storage-'.
			$this->instance->head_bucket(DOMAIN).
			'.sdcloud.cn';
		$this->instance->set_host($this->headurl);
	}
	
	public function exists($filename){
		//GCS木有正常的检测文件是否存在的API囧，那就获取信息吧，失败就不存在
		try{
			$this->instance->head_object($this->get_file($filename));
			return true;
		}catch(Exception $e){
			return false;
		}
	}
	public function read($filename){
		//GCS你是要闹哪样啊摔
		$temp = tmpfile();
		$this->instance->get_object($this->get_file($filename), $temp);
		fseek($temp,0);
		$contents = "";
		while (!feof($temp)){
			$contents .= fread($temp,8192);
		}
		return $contents;
	}
	
	public function write($name,$content){
		//同上啊摔
		$temp = tmpfile();
		fwrite($temp,$content);
		fseek($temp,0);
		//$temp = tempnam(sys_get_temp_dir());
		//file_put_contents($temp,$content);
		$this->instance->put_object($this->get_file($name), $temp);
		//unlink($temp);
	}
	
	public function url($name){
		return $this->headurl.$this->instance->get_object_resource($this->get_file($name),30*24*60*60);
	}
	
	public function error(){
		return false;
	}
	
	public function delete($name){
		return $this->instance->delete_object($this->get_file($name));
	}
	
	private function get_file($name){
		return ltrim($name,'/');
	}
}