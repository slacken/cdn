<?php

if ( ! defined('BASE_PATH')) exit('No direct script access allowed');

class view{
	
    public function __construct(){
        ob_start();
    }
    
    public function render(){}
    
    public function finish(){
        $content = ob_get_clean();
        return $content;
    }
    
    /**
     * 直接显示
     * */
	public static function show($page, $data = array()){
		$page = BASE_PATH.'views/'.trim($page,'/').'.php';
		if(is_readable($page)){
			ob_start();
	    	include $page;
	    	ob_end_flush();
		}
	}
	
}
?>