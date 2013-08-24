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
					'3gp' => 'video/3gpp',
					'ai' => 'application/postscript',
					'aif' => 'audio/x-aiff',
					'aifc' => 'audio/x-aiff',
					'aiff' => 'audio/x-aiff',
					'asc' => 'text/plain',
					'atom' => 'application/atom+xml',
					'au' => 'audio/basic',
					'avi' => 'video/x-msvideo',
					'bcpio' => 'application/x-bcpio',
					'bin' => 'application/octet-stream',
					'bmp' => 'image/bmp',
					'cdf' => 'application/x-netcdf',
					'cgm' => 'image/cgm',
					'class' => 'application/octet-stream',
					'cpio' => 'application/x-cpio',
					'cpt' => 'application/mac-compactpro',
					'csh' => 'application/x-csh',
					'css' => 'text/css',
					'dcr' => 'application/x-director',
					'dif' => 'video/x-dv',
					'dir' => 'application/x-director',
					'djv' => 'image/vnd.djvu',
					'djvu' => 'image/vnd.djvu',
					'dll' => 'application/octet-stream',
					'dmg' => 'application/octet-stream',
					'dms' => 'application/octet-stream',
					'doc' => 'application/msword',
					'dtd' => 'application/xml-dtd',
					'dv' => 'video/x-dv',
					'dvi' => 'application/x-dvi',
					'dxr' => 'application/x-director',
					'eps' => 'application/postscript',
					'etx' => 'text/x-setext',
					'exe' => 'application/octet-stream',
					'ez' => 'application/andrew-inset',
					'flv' => 'video/x-flv',
					'gif' => 'image/gif',
					'gram' => 'application/srgs',
					'grxml' => 'application/srgs+xml',
					'gtar' => 'application/x-gtar',
					'gz' => 'application/x-gzip',
					'hdf' => 'application/x-hdf',
					'hqx' => 'application/mac-binhex40',
					'htm' => 'text/html',
					'html' => 'text/html',
					'ice' => 'x-conference/x-cooltalk',
					'ico' => 'image/x-icon',
					'ics' => 'text/calendar',
					'ief' => 'image/ief',
					'ifb' => 'text/calendar',
					'iges' => 'model/iges',
					'igs' => 'model/iges',
					'jnlp' => 'application/x-java-jnlp-file',
					'jp2' => 'image/jp2',
					'jpe' => 'image/jpeg',
					'jpeg' => 'image/jpeg',
					'jpg' => 'image/jpeg',
					'js' => 'application/x-javascript',
					'kar' => 'audio/midi',
					'latex' => 'application/x-latex',
					'lha' => 'application/octet-stream',
					'lzh' => 'application/octet-stream',
					'm3u' => 'audio/x-mpegurl',
					'm4a' => 'audio/mp4a-latm',
					'm4p' => 'audio/mp4a-latm',
					'm4u' => 'video/vnd.mpegurl',
					'm4v' => 'video/x-m4v',
					'mac' => 'image/x-macpaint',
					'man' => 'application/x-troff-man',
					'mathml' => 'application/mathml+xml',
					'me' => 'application/x-troff-me',
					'mesh' => 'model/mesh',
					'mid' => 'audio/midi',
					'midi' => 'audio/midi',
					'mif' => 'application/vnd.mif',
					'mov' => 'video/quicktime',
					'movie' => 'video/x-sgi-movie',
					'mp2' => 'audio/mpeg',
					'mp3' => 'audio/mpeg',
					'mp4' => 'video/mp4',
					'mpe' => 'video/mpeg',
					'mpeg' => 'video/mpeg',
					'mpg' => 'video/mpeg',
					'mpga' => 'audio/mpeg',
					'ms' => 'application/x-troff-ms',
					'msh' => 'model/mesh',
					'mxu' => 'video/vnd.mpegurl',
					'nc' => 'application/x-netcdf',
					'oda' => 'application/oda',
					'ogg' => 'application/ogg',
					'ogv' => 'video/ogv',
					'pbm' => 'image/x-portable-bitmap',
					'pct' => 'image/pict',
					'pdb' => 'chemical/x-pdb',
					'pdf' => 'application/pdf',
					'pgm' => 'image/x-portable-graymap',
					'pgn' => 'application/x-chess-pgn',
					'pic' => 'image/pict',
					'pict' => 'image/pict',
					'png' => 'image/png',
					'pnm' => 'image/x-portable-anymap',
					'pnt' => 'image/x-macpaint',
					'pntg' => 'image/x-macpaint',
					'ppm' => 'image/x-portable-pixmap',
					'ppt' => 'application/vnd.ms-powerpoint',
					'ps' => 'application/postscript',
					'qt' => 'video/quicktime',
					'qti' => 'image/x-quicktime',
					'qtif' => 'image/x-quicktime',
					'ra' => 'audio/x-pn-realaudio',
					'ram' => 'audio/x-pn-realaudio',
					'ras' => 'image/x-cmu-raster',
					'rdf' => 'application/rdf+xml',
					'rgb' => 'image/x-rgb',
					'rm' => 'application/vnd.rn-realmedia',
					'roff' => 'application/x-troff',
					'rtf' => 'text/rtf',
					'rtx' => 'text/richtext',
					'sgm' => 'text/sgml',
					'sgml' => 'text/sgml',
					'sh' => 'application/x-sh',
					'shar' => 'application/x-shar',
					'silo' => 'model/mesh',
					'sit' => 'application/x-stuffit',
					'skd' => 'application/x-koan',
					'skm' => 'application/x-koan',
					'skp' => 'application/x-koan',
					'skt' => 'application/x-koan',
					'smi' => 'application/smil',
					'smil' => 'application/smil',
					'snd' => 'audio/basic',
					'so' => 'application/octet-stream',
					'spl' => 'application/x-futuresplash',
					'src' => 'application/x-wais-source',
					'sv4cpio' => 'application/x-sv4cpio',
					'sv4crc' => 'application/x-sv4crc',
					'svg' => 'image/svg+xml',
					'swf' => 'application/x-shockwave-flash',
					't' => 'application/x-troff',
					'tar' => 'application/x-tar',
					'tcl' => 'application/x-tcl',
					'tex' => 'application/x-tex',
					'texi' => 'application/x-texinfo',
					'texinfo' => 'application/x-texinfo',
					'tif' => 'image/tiff',
					'tiff' => 'image/tiff',
					'tr' => 'application/x-troff',
					'tsv' => 'text/tab-separated-values',
					'txt' => 'text/plain',
					'ustar' => 'application/x-ustar',
					'vcd' => 'application/x-cdlink',
					'vrml' => 'model/vrml',
					'vxml' => 'application/voicexml+xml',
					'wav' => 'audio/x-wav',
					'wbmp' => 'image/vnd.wap.wbmp',
					'wbxml' => 'application/vnd.wap.wbxml',
					'webm' => 'video/webm',
					'wml' => 'text/vnd.wap.wml',
					'wmlc' => 'application/vnd.wap.wmlc',
					'wmls' => 'text/vnd.wap.wmlscript',
					'wmlsc' => 'application/vnd.wap.wmlscriptc',
					'wmv' => 'video/x-ms-wmv',
					'wrl' => 'model/vrml',
					'xbm' => 'image/x-xbitmap',
					'xht' => 'application/xhtml+xml',
					'xhtml' => 'application/xhtml+xml',
					'xls' => 'application/vnd.ms-excel',
					'xml' => 'application/xml',
					'xpm' => 'image/x-xpixmap',
					'xsl' => 'application/xml',
					'xslt' => 'application/xslt+xml',
					'xul' => 'application/vnd.mozilla.xul+xml',
					'xwd' => 'image/x-xwindowdump',
					'xyz' => 'chemical/x-xyz',
					'zip' => 'application/zip'
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
		$this->hit = false;
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
				$this->hit = $key;
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