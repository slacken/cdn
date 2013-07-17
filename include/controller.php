<?php

if ( ! defined('BASE_PATH')) exit('No direct script access allowed');

class controller{
	
	public $content_type;
	
	public $succeed ;
	
	public $error_type;
	
	private $hit = false;
	
	public function __construct($request = ''){
		
		$this->content_type = 'text/html';
		$this->error_type = 0;
		$this->succeed = TRUE;
		
		$request = ltrim($request,'/');
		
		//检测环境
		if(!RUN_ENV){
			$this->error_type = 'no_run_env';
			$this->succeed = FALSE;
		}
		
		//请求为空
		elseif($request === '' && WELCOME_DOC){
			//显示欢迎页面
			view::show('welcome');
			return ;
		}
		else{
			//检查防盗链
			$referer = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'';
			if(ALLOW_REGX && !preg_match('/'.ALLOW_REGX.'/i',$referer)){
				$this->error_type = 'not_allowed_domain';
				$this->succeed = FALSE;
			}else{
				//匹配文件后缀
//				$temp = array();
//				if(preg_match('/\.(jpg|jpeg|png|pdf|gif|css|js|zip)$/i', $request,$temp)===1){//暂时先就这几种
//					//http://en.wikipedia.org/wiki/Internet_media_type#List_of_common_media_types
//					switch($temp[1]){
//						case 'jpg':{$this->content_type="image/jpeg";}break;
//						case 'gif':{$this->content_type="image/gif";}break;
//						case 'png':{$this->content_type="image/png";}break;
//						case 'css':{$this->content_type="text/css";}break;
//						case 'js':{$this->content_type="text/javascript";}break;
//					}
//				}
				$mime_types = array(
					'jpg' => 'image/jpeg',
					'gif' => 'image/gif',
					'png' => 'image/png',
					'css' => 'text/css',
					'txt' => 'text/plain',
					'js' => 'text/javascript',
					'html' => 'text/html',
					'htm' => 'text/htm',
					'rss' => 'application/atom+xml',
					'json' => 'application/json',
					'ogg' => 'audio/ogg',
					'pdf' => 'application/pdf',
					'xml' => 'text/xml',
					'zip' => 'application/zip',
					'rar' => 'application/octet-stream',
					'gz' => 'application/gzip',
					'gzip' => 'application/gzip',
					'wav' => 'audio/vnd.wave',
					'mp3' => 'audio/mp3',
					'mp4' => 'video/mp4',
					'flv' => 'video/x-flv',
				);
				$basename = basename($request);
				$ext = strtolower(substr($basename,strrpos($basename,'.')+1));
				if(isset($mime_types[$ext])){
					$this->content_type=$mime_types[$ext];
				}
			}
		}
		
		//开始处理
		$delete = false;
		if(count($purge = explode(PURGE_KEY.'/',$request,2))>1){
			$delete = true;
			$request = $purge[1];
		}
		$key = md5($request).'_'.strlen($request).'.cache';
		$this->hit = $key;
		$this->handle($request,$key,$delete);
		
	}
	/**
	 * 获取内容并输出
	 * 如果stroage里面不存在，则从URL里面获取
	 * */
	private function handle($filename,$key,$delete = false){
		$content = '';
		if($this->succeed){
			$storage = storage::gethandle();
			if($delete){
				if(!$storage->exists($key)){
					die(json_encode(array('purge'=>$filename,'key'=>$key,'success'=>'not exists')));
				}
				$return = $storage->delete($key);
				die(json_encode(array('purge'=>$filename,'key'=>$key,'success'=>$return)));
			}
			if($storage->exists($key)){
				if($url = $storage->url($key)){
					$this->locate($url);
				}
				$content = $storage->read($key);
			}else{
				//$content = @file_get_contents(BASE_URL.$filename);
				$content = lib::fetch_url(BASE_URL.$filename);
				$storage->write($key, $content);
			}
			if(empty($content)){
				$this->error_type = 'empty_content';
				$this->succeed = FALSE;
			}else{
				//这里应该有更多的检查
			}
		}
		//显示内容
		$this->render($content);
	}
	
	
	/**
	 * 输出结果，包括缓存控制等
	 * */
	private function render($content=''){
		ob_end_clean();
		if(!$this->succeed){
			$this->error();
			return ;
		}else{
			if($this->hit){
				header('Layer-Cache: Hit;key='.$this->hit);
			}else{
				header('Layer-Cache: Miss');
			}
			header("Expires: " . date("D, j M Y H:i:s GMT", time()+2592000));//缓存一月
			header('Content-type: '.$this->content_type);
			echo $content;
		}
	}
	
	private function loacte($url){
		//302
		header("HTTP/1.1 302 Moved Temporarily");
		header("Location:".$url);
	}
	
	/**
	 * 处理错误
	 * */
	private function error(){
		$this->content_type = 'text/html';
		echo json_encode(array('error'=>$this->error_type));
	}
	
	
}