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
		$ext = 'cache';
		
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
			@$referer = parse_url($referer);
			$referer = isset($referer['host'])?$referer['host']:'';
			if(ALLOW_REGX && !preg_match('/'.ALLOW_REGX.'/i',$referer)){
				$this->error_type = 'not_allowed_domain';
				$this->succeed = FALSE;
			}else{
				//匹配文件后缀
				$mime_types = array(
					'jpg' => 'image/jpeg',
					'jpeg' => 'image/jpeg',
					'gif' => 'image/gif',
					'png' => 'image/png',
					'ico' => 'image/jpeg',
					'css' => 'text/css',
					'txt' => 'text/plain',
					'js' => 'text/javascript',
					'html' => 'text/html',
					'htm' => 'text/html',
					'php' => 'text/html',
					'asp' => 'text/html',
					'rss' => 'application/atom+xml',
					'json' => 'application/json',
					'ogg' => 'audio/ogg',
					'pdf' => 'application/pdf',
					'xml' => 'text/xml',
					'zip' => 'application/zip',
					'rar' => 'application/octet-stream',
					'exe' => 'application/octet-stream',
					'chm' => 'application/octet-stream',
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
				$direct = false;
				if(in_array($ext,explode('|',strtolower(DIRECT_EXT)))){
					$direct = true;
				}
			}
		}
		
		//开始处理
		$delete = false;
		if(count($purge = explode(PURGE_KEY.'/',$request,2))>1){
			$delete = true;
			$request = $purge[1];
		}
		$key = (NO_KEY)?$request:md5($request).'_'.strlen($request).'.'.$ext;
		$this->hit = $key;
		$this->handle($request,$key,$delete,$direct);
		
	}
	/**
	 * 获取内容并输出
	 * 如果stroage里面不存在，则从URL里面获取
	 * */
	private function handle($filename,$key,$delete = false,$direct = false){
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
			if($storage->exists($key) && !$direct){
				if(!NO_LOCATE && $url = $storage->url($key)){
					$this->locate($url);
				}
				$content = $storage->read($key);
				if(empty($content)){
					$this->succeed = false;
					$this->error_type = 'empty_conent';
				}
			}else{
				//$content = @file_get_contents(BASE_URL.$filename);
				$content = lib::fetch_url(BASE_URL.$filename);
				if(!is_array($content) || count($content)<2){
					$this->succeed = false;
					$this->error_type = 'fetch_error';
				}elseif($content[0]==200){
					//返回200，才写入
					if(!$direct) $storage->write($key, $content[1]);
				}else{
					header('HTTP/1.1 '.$content[0]);
				}
				$content = $content[1];
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
				header('Layer-Cache: Hit;key='.$this->hit.';ENV='.RUN_ENV);
			}else{
				header('Layer-Cache: Miss;ENV='.RUN_ENV);
			}
			header("Expires: " . date("D, j M Y H:i:s GMT", time()+2592000));//缓存一月
			header('Content-type: '.$this->content_type);
			echo $content;
		}
	}
	
	private function locate($url){
		//302
		header("HTTP/1.1 302 Moved Temporarily");
		header("Location:".$url);
		die();
	}
	
	/**
	 * 处理错误
	 * */
	private function error(){
		$this->content_type = 'text/html';
		echo json_encode(array('error'=>$this->error_type));
	}
	
	
}