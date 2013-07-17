<?php
if ( ! defined('BASE_PATH')) exit('No direct script access allowed');
/**
 * 公共函数
 * */
class lib{
    /**
     * 检查环境
     **/
	public static function check(){
        
    }
    /**
     * 是否为URL
     **/
    public static function is_url($url){

    }
	
	//抓取
	public static function fetch_url($url){
		switch(RUN_ENV){
			case 'SAE':	//使用SAE FetchURL服务
				$f = new SaeFetchurl();
				if(STATIC_HOST){
					$f->setHeader('Host',STATIC_HOST);
				}
				$content = $f->fetch($url);
				return array($f->HttpCode(),$content);
			break;
			case 'BAE':
			case 'LOCAL':
			default:
				if(function_exists('curl_init')){
					//BAE或普通平台下可使用curl
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
					if(!ini_get('safe_mode')){
						curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
					}
					if(STATIC_HOST){
						curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: '.STATIC_HOST));
					}
					$con = curl_exec($ch);
					$cod = curl_getinfo($ch,CURLINFO_HTTP_CODE);
					return array($cod,$con);
				}else{
					//否则使用file_get_contents
					$content = '';
					if(STATIC_HOST){
						$opt=array('http'=>array('header'=>'Host: '.STATIC_HOST));
						$context=stream_context_create($opt);
						$content = file_get_contents($url,false,$context);
					}else{
						$content = file_get_contents($url);
					}
					list($version,$status_code,$msg) = explode(' ',$http_response_header[0], 3);
					return array($status_code,$content);
				}
		}
	}

}