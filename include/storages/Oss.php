<?php

if ( ! defined('BASE_PATH')) exit('No direct script access allowed');

class StorageHandle{
	
	public $instance;
	
	public $headurl;
	
	public $domain;
	
	public function __construct(){
		$this->domain = DOMAIN;
		require dirname(__FILE__).'/oss/sdk.class.php';
		$this->instance =  new ALIOSS();
		$this->headurl = 'http://oss.aliyuncs.com/'.DOMAIN.'/';
	}
	
	public function exists($filename){
		$res=$this->instance->is_object_exist($this->domain,$this->get_file($filename));
		if($res->status==404){
			return false;
		}
		return true;
	}
	public function read($filename){
		$bucket = $this->domain;
		$object = $this->get_file($filename);
		
		$options = array(
			//ALIOSS::OSS_FILE_DOWNLOAD => "d:\\cccccccccc.sh",
			//ALIOSS::OSS_CONTENT_TYPE => 'txt/html',
		);	
		
		$response = $this->instance->get_object($bucket,$object,$options);
		return $contents;
	}
	
	public function write($name,$content){
		$object = $this->get_file($name);
		$upload_file_options = array(
			'content' => $content,
			'length' => strlen($content),
			ALIOSS::OSS_HEADERS => array(
				//'Expires' => '2012-10-01 08:00:00',
			),
		);
		
		$response = $this->instance->upload_file_by_content($this->domain,$object,$upload_file_options);
	}
	
	public function url($name){
		return $this->headurl.$this->get_file($name);
	}
	
	public function error(){
		return false;
	}
	
	public function delete($name){
		return $this->instance->delete_object($this->domain,$this->get_file($name));
	}
	
	private function get_file($name){
		return ltrim($name,'/');
	}
}