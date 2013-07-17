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
				return $content;
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
						curl_setopt($ch, CURLOPT_HTTPHEADER, 'Host: '.STATIC_HOST);
					}
					return curl_exec($ch);
				}else{
					//否则使用file_get_contents
					if(STATIC_HOST){
						$opt=array('http'=>array('header'=>'Host: '.STATIC_HOST));
						$context=stream_context_create($opt);
						return file_get_contents($url,false,$context);
					}else{
						return file_get_contents($url);
					}
				}
		}
	}

}