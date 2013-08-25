<?php
/**
 * =======================================================================
 * simple:
 *   require_once (dirname(__FILE__).'/GrandCloudStorage.php');
 *
 *   $client = new GrandCloudStorage($host);
 *   $client->set_key_secret($access_key, $access_secret);
 *
 *   $bucket_name = "test_bucket";
 *   $client->put_bucket($bucket_name); // create new bucket
 *
 *   $client->set_bucket($bucket_name); // set $bucket_name as default for follow ops
 *   $client->put_object("test.ext", "localfile.ext"); // upload localfile.ext file to $bucket_name and assign name as text.ext
 *   $client->head_object("test.ext"); // get test.ext object's meta
 *   $client->get_object("test.ext", "tmp.ext"); // download test.ext object as local tmp.ext file
 *   $client->delete_object("test.ext"); // delete test.ext object
 * =======================================================================
 */

// Errors
define('ERR_ACCESS_DENIED', 'AccessDenied');
define('ERR_UNSUPPORTED_TRANSFER_ENCODING', 'UnsupportedTransferEncoding');
define('ERR_BAD_DIGEST', 'BadDigest');
define('ERR_INCOMPLETE_BODY', 'IncompleteBody');
define('ERR_BUCKET_ACCESS_DENIED', 'BucketAccessDenied');
define('ERR_BUCKET_NOT_EMPTY', 'BucketNotEmpty');
define('ERR_BUCKET_UNEXIST', 'NoSuchBucket');
define('ERR_BUCKET_TOO_MANY', 'TooManyBuckets');
define('ERR_BUCKET_NAME_CONFLICT', 'BucketAlreadyExists');
define('ERR_BUCKET_NAME_INVALID', 'InvalidBucketName');
define('ERR_BUCKET_NOPOLICY', 'NotSuchBucketPolicy');
define('ERR_ENTITY_TOO_LARGE', 'EntityTooLarge');
define('ERR_OBJECT_KEY_TOO_LONG', 'KeyTooLong');
define('ERR_OBJECT_UNEXIST', 'NoSuchKey');
define('ERR_INVALID_ACCESS_KEY', 'InvalidAccessKeyId');
define('ERR_INVALID_CONTENT_LENGTH', 'InvalidContentLength');
define('ERR_INVALID_EXPIRES', 'InvalidExpires');
define('ERR_INVALID_RANGE', 'InvalidRange');
define('ERR_INVALID_REQUEST_TIME', 'InvalidRequestTime');
define('ERR_INVALID_USER_METADATA', 'InvalidUserMetadata');
define('ERR_MALFORMED_AUTHORIZATION', 'MalformedAuthorization');
define('ERR_MALFORMED_XML', 'MalformedXML');
define('ERR_METHOD_NOT_ALLOWED', 'MethodNotAllowed');
define('ERR_MISSING_CONTENT_LENGTH', 'MissingContentLength');
define('ERR_MISSING_SECURITY_HEADER', 'MissingSecurityHeader');
define('ERR_MULTIPLE_RANGE', 'MultipleRange');
define('ERR_PRECONDITION_FAILED', 'PreconditionFailed');
define('ERR_REQUEST_EXPIRED', 'RequestHasExpired');
define('ERR_REQUEST_TIMEOUT', 'RequestTimeout');
define('ERR_REQUEST_TIMESKEWED', 'RequestTimeTooSkewed');
define('ERR_SIGNATURE_UNMATCH', 'SignatureDoesNotMatch');


// Bucket对象
class GCBucket {
	protected $idc; // bucket所在IDC，可用值为：huadong-1，huabei-1
	protected $name; // bucket名称
	protected $ctime; // bucket创建时间，参见：date('r')

	public function __construct($idc, $name, $ctime) {
		$this->idc = $idc;
		$this->name = $name;
		$this->ctime = $ctime;

		return $this;
	}

	public function get_idc() {
		return $this->idc;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_ctime() {
		return $this->ctime;
	}

	public function to_array() {
		return array(
				'idc' => $this->idc,
				'name' => $this->name,
				'ctime' => $this->ctime
		);
	}
}

// Object对象
class GCObject {
	protected $key; // object key
	protected $size; // object size
	protected $etag; // object ETAG
	protected $last_modified; // object last modified time

	public function __construct($key, $size, $last_modified, $etag) {
		$this->key = $key;
		$this->size = $size;
		$this->etag = $etag;
		$this->last_modified = $last_modified;

		return $this;
	}

	public function get_key() {
		return $this->key;
	}

	public function get_size() {
		return $this->size;
	}

	public function get_etag() {
		return $this->etag;
	}

	public function get_last_modified() {
		return $this->last_modified;
	}

	public function to_array() {
		return array(
				'key' => $this->key,
				'size' => $this->size,
				'etag' => $this->etag,
				'last_modified' => $this->last_modified
		);
	}
}

class GCUploadPart extends  GCObject {
	protected $partnumber;// part number of this part
	
	/**Tags used in parse xml*/
	public static $partnumberTag = "PartNumber";
	public static $lastModifiedTag = "LastModified";
	public static $etagTag = "ETag";
	public static $sizeTag = "Size";
	public static $keyTag = "Key";
	public static $partTag = "Part";
	
    public function __construct($key, $size, $last_modified, $etag,$part_number) {
    	parent::__construct($key, $size, $last_modified, $etag);
    	$this->partnumber = $part_number;
    }
    
    public function get_partnumber() {
    	return $this->partnumber;
    }
    
    public function to_array() {
    	return array(
    			GCUploadPart::$keyTag => $this->key,
    			GCUploadPart::$partnumberTag => $this->partnumber,
    			GCUploadPart::$lastModifiedTag => $this->last_modified,
    			GCUploadPart::$etagTag => $this->etag,
    			GCUploadPart::$sizeTag => $this->size
    			);
    }
    
    public function to_xml_for_completemultipartupload() {
    	
    	$xml = "<PartNumber>{$this->partnumber}</PartNumber>";
        $xml .= "<ETag>{$this->etag}</ETag>";
       
        return $xml;
    }
}

/**
 * GCMUltipartUpload
 * @author fun
 *
 */
class GCMultipartUpload {
	protected $bucket;// bucket's name
	protected $key;//the object name of this Multipart Upload
	protected $uploadid;//id used to identify multipartupload
	protected $initiated; //when this upload initiated 

	/**Tags used in parse xml body*/
	public static $InitiateMultipartUploadResultTag = "InitiateMultipartUploadResult";
	public static $bucketTag = "Bucket";
	public static $keyTag = "Key";
	public static $uploadIdTag = "UploadId";
	public static $initiatedTag = "Initiated";

	public function __construct($bucket,$key,$uploadid,$initiated = '') {
		$this->bucket = $bucket;
		$this->key = $key;
		$this->uploadid = $uploadid;
		$this->initiated = $initiated;
	}


	public function get_bucket() {
		return $this->bucket;
	}

	public function get_key() {
		return $this->key;
	}

	public function get_uploadid() {
		return $this->uploadid;
	}
	
	public function get_initated() {
		return $this->initiated;
	}

	public function to_array() {
		$meta_data =array(
				GCMultipartUpload::$bucketTag => $this->bucket,
				GCMultipartUpload::$keyTag => $this->key,
				GCMultipartUpload::$uploadIdTag => $this->uploadid
		);
	    
		if (! empty($this->initiated)) {
			$meta_data[GCMultipartUpload::$initiatedTag] = $this->initiated;
		}
		return $meta_data;
	}
	
}


// Entity对象
class GCEntity {
	protected $bucket; // bucket名称
	protected $prefix = ''; // 获取对象时前缀过滤字符串
	protected $marker = ''; // 获取对象时偏移对象的名称
	protected $maxkeys; // 获取对象时返回的最大记录数
	protected $delimiter; // 获取对象时使用的分隔符
	protected $istruncated = false; // 返回结果是否经过截短？
	protected $objectarray = array(); // object list array

	public function __construct() {
		return $this;
	}

	public function set_bucket($bucket) {
		$this->bucket = $bucket;
	}

	public function get_bucket() {
		return $this->bucket;
	}

	public function set_prefix($prefix) {
		$this->prefix = $prefix;
	}

	public function get_prefix() {
		return $this->prefix;
	}

	public function set_marker($marker) {
		$this->marker = $marker;
	}

	public function get_marker() {
		return $this->marker;
	}

	public function set_maxkeys($maxkeys) {
		$this->maxkeys = $maxkeys;
	}

	public function get_maxkeys() {
		return $this->maxkeys;
	}

	public function set_delimiter($delimiter) {
		$this->delimiter = $delimiter;
	}

	public function get_delimiter() {
		return $this->delimiter;
	}

	public function set_istruncated($istruncated) {
		$this->istruncated = $istruncated;
	}

	public function get_istruncated() {
		return $this->istruncated;
	}

	public function add_object($object) {
		$this->objectarray[]= $object;
	}

	public function get_object($idx=null) {
		if ($idx === null) {
			return $this->objectarray;
		}

		$max = count($this->objectarray);

		$idx = intval($idx);
		if ($idx < 0) {
			$idx += $max;
		}

		if ($idx >= 0 && $idx < $max) {
			return $this->objectarray[$idx];
		}

		return null;
	}

	public function to_array() {
		return array(
				'bucket' => $this->bucket,
				'prefix' => $this->prefix,
				'marker' => $this->marker,
				'maxkeys' => $this->maxkeys,
				'delimiter' => $this->delimiter,
				'istruncated' => $this->istruncated,
				'object' => $this->objectarray
		);
	}
}

class GCMultipartUploadEntity extends GCEntity {
	protected $uploadidmarker = '';
	protected $nextkeymarker = '';
	protected $nextuploadidmarker = '';

	/**Tags of List Multipart Uploads*/
	public static $listMultipartUploadsResultTag = "ListMultipartUploadsResult";
	public static $bucketTag = "Bucket";
	public static $delimiterTag = "Delimiter";
	public static $prefixTag = "Prefix";
	public static $maxUploadsTag = "MaxUploads";
	public static $keyMarkerTag = "KeyMarker";
	public static $uploadIdMarker = "UploadIdMarker";
	public static $nextKeyMarkerTag = "NextKeyMarker";
	public static $nextUploadIdMarkerTag = "NextUploadIdMarker";
	public static $isTruncatedTag = "IsTruncated";
	public static $uploadTag = "Upload";
	public static $commonPrefixesTag = "CommonPrefixes";

	
	public function __construct() {
		parent::__construct();
		return $this;
	}
	
	public function set_maxUploads($maxuploads) {
		parent::set_maxkeys($maxuploads);
	}
	
	public function get_maxUploads() {
		return parent::get_maxkeys();
	}
	
	public function set_keyMarker( $keymarker ) {
		parent::set_marker($keymarker);
	}
	
	public function getKeyMarker() {
		return parent::get_marker();
	}
	
	public function set_uploadIdMarker($uploadIdMarker) {
		$this->uploadidmarker = $uploadIdMarker;
	}
	
	public function get_uploadIdMarker() {
		return $this->uploadidmarker;
	}
	
	public function set_nextKeyMarker( $nextKeyMarker ) {
		$this->nextkeymarker = $nextKeyMarker;
	}
	
	public function get_nextKeyMarker() {
		return $this->nextkeymarker;
	}
	
	public function set_nextUploadIdMarker( $nextuploadIdMarker ) {
		$this->nextuploadidmarker = $nextuploadIdMarker;
	}
	
	public function get_nextUploadIdMarker() {
		return $this->nextuploadidmarker;
	}
	
	public function addUpload( $upload ) {
		parent::add_object($upload);
	}
	
	public function get_upload($idx = null) {
		return parent::get_object($idx);
	}
	
	public function to_array(){
		return array(
				GCMultipartUploadEntity::$bucketTag => $this->bucket,
				GCMultipartUploadEntity::$prefixTag => $this->prefix,
				GCMultipartUploadEntity::$keyMarkerTag => $this->marker,
				GCMultipartUploadEntity::$maxUploadsTag => $this->maxkeys,
				GCMultipartUploadEntity::$delimiterTag => $this->delimiter,
				GCMultipartUploadEntity::$uploadIdMarker => $this->uploadidmarker,
				GCMultipartUploadEntity::$nextKeyMarkerTag => $this->nextkeymarker,
				GCMultipartUploadEntity::$nextUploadIdMarkerTag => $this->nextuploadidmarker,
				GCMultipartUploadEntity::$isTruncatedTag => $this->istruncated,
				GCMultipartUploadEntity::$uploadTag => $this->objectarray
		);
	}
}

//Entity used in list parts
class GCPartsEntity {
	protected $bucket = ""; // bucket's name
	protected $key = ""; // multipart upload's key
	protected $uploadid = "";
	protected $maxparts = ""; // the maximum number of parts returned in the response body
	protected $istruncated = "";//$part_number_marker,the part to start with
	protected $partnumbermarker = "";// part number to start with
	protected $nextpartnumbermarker = "";// next part number to start with
	protected $partsarray = array();
	
	/**Tags used in parse listpart result*/
	public static $listpartsresultTag = "ListPartsResult";
	public static $bucketTag = "Bucket";
	public static $keyTag = "Key";
	public static $uploadIdTag = "UploadId";
	public static $maxpartsTag = "MaxParts";
	public static $istruncatedTag = "IsTruncated";
	public static $partnumberMarkerTag = "PartNumberMarker";
	public static $nextpartnumbermarkerTag = "NextPartNumberMarker";
	public static $partTag = "Part";
	
	public function __construct() {
		return $this;
	}
	
	public function set_bucket($bucket) {
		$this->bucket = $bucket;
	}
	
	public function get_bucket() {
		return $this->bucket;
	}
	
	public function set_key ($key) {
		$this->key = $key;
	}
	
	
	public function get_key(){
		return $this->key;
	}
	
	public function set_uploadid($uploadid) {
		$this->uploadid = $uploadid;
	}
	
	
	public function get_uploadid() {
		return $this->uploadid;
	}
	
	public function set_maxparts( $maxparts ) {
	    $this->maxparts = $maxparts;	
	}
	
	public function get_maxparts() {
		return $this->maxparts;
	}
	
	public function set_istruncated( $istruncated ) {
	    	$this->istruncated = $istruncated;
	}
	
	public function get_istruncated () {
		return $this->istruncated;
	}
	
	public function set_partnumbermarker( $partnumbermarker ){
		$this->partnumbermarker = $partnumbermarker;
	}
	
	public function get_partnumbermarker() {
		return $this->partnumbermarker;
	}
	
	public function set_nextpartnumbermarker( $nextpartnumbermarker ) {
		$this->nextpartnumbermarker = $nextpartnumbermarker;
	}
	
	public function get_nextpartnumbermarker(){
		return $this->nextpartnumbermarker;
	}
	
	public function add_part( $part ) {
		$this->partsarray[] = $part;
	}
	
	public function get_part($idx = null) {
		if (null === $idx) {
		   return $this->partsarray;
		}
		$max = count($this->partsarray);
		
		$idx = intval($idx);
		if ($idx < 0) {
			$idx += $max;
		}
		
		if ($idx >= 0 && $idx < $max) {
			return $this->partsarray[$idx];
		}
		
		return null;
	}
	
	public function to_array() {
		return array(
				GCPartsEntity::$bucketTag => $this->bucket,
				GCPartsEntity::$keyTag => $this->key,
				GCPartsEntity::$uploadIdTag => $this->uploadid,
				GCPartsEntity::$maxpartsTag => $this->maxparts,
				GCPartsEntity::$istruncatedTag => $this->istruncated,
				GCPartsEntity::$partnumberMarkerTag => $this->partnumbermarker,
				GCPartsEntity::$nextpartnumbermarkerTag => $this->nextpartnumbermarker,
				GCPartsEntity::$partTag => $this->partsarray
				);
	}
	
	/**
	 * build complete multipart upload xml
	 */
	public function to_completemultipartuploadxml() {
		
		$parts_xml = '<?xml version="1.0" encoding="UTF-8"?>';
		$parts_xml .= "<CompleteMultipartUpload>";
		foreach($this->partsarray as $part) {
			$parts_xml .= "<Part>";
			$parts_xml .= $part->to_xml_for_completemultipartupload();
			$parts_xml .= "</Part>";
		}
		$parts_xml .= "</CompleteMultipartUpload>";
		return $parts_xml;
		
	}
	
	
}
// MIME对象
class GCMIME {
	/**
	 * MIME map of the file extensions.
	 */
	protected static $mime_maps = array(
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

			/**
			 * Get file MIME according to its extension.
			 *
			 * @param string $ext
			 * @return string
			 */
			public static function get_type($ext) {
				return isset(self::$mime_maps[$ext]) ? self::$mime_maps[$ext] : 'application/octet-stream';
			}
}

// Error对象
class GCError extends Exception {
	protected $requestId; // the request sign, used for error trace
	protected $requestResource; // the request target resource
	protected $errorCode; // error code
	protected $errorMessage; // error message

	public function __construct($response_code, $response_xml) {
		if (!empty($response_xml)) {
			$this->parse_errxml($response_xml);
		}

		parent::__construct($this->errorMessage, $response_code);
	}

	public function getId() {
		return $this->requestId;
	}

	public function getResource() {
		return $this->requestResource;
	}

	public function getErrorCode() {
		return $this->errorCode;
	}

	public function getErrorMessage() {
		return $this->errorMessage;
	}

	public function to_array() {
		return array(
				'code' => $this->code,
				'message' => $this->message,
				'errorCode' => $this->errorCode,
				'errorMessage' => $this->errorMessage,
				'requestId' => $this->requestId,
				'requestResource' => $this->requestResource
		);
	}

	protected function parse_errxml($error_xml) {
		$error_xml = $this->get_xmlpart($error_xml);
		$doc = new DOMDocument();
		$doc->loadXML($error_xml);

		$errorCode  = $doc->getElementsByTagName('Code')->item(0);
		$this->errorCode = empty($errorCode) ? 'UnknownErrorCode' : $errorCode->nodeValue;

		$errorMessage = $doc->getElementsByTagName('Message')->item(0);
		$this->errorMessage = empty($errorMessage) ? 'UnknownErrorMessage' : $errorMessage->nodeValue;

		$requestId = $doc->getElementsByTagName('RequestId')->item(0);
		$this->requestId = empty($requestId) ? 'UnknownRequestId' : $requestId->nodeValue;

		$requestResource = $doc->getElementsByTagName('Resource')->item(0);
		$this->requestResource = empty($requestResource) ? 'UnknownRequestResource' : $requestResource->nodeValue;
	}
	
	/**
	 * Get xml part from response body
	 */
	protected function get_xmlpart($response_body) {
       $tmparray = explode("\r\n\r\n", $response_body);
       $realbody = array();	
       for($i=0;$i<count($tmparray);$i++) {
       	$tmp = trim($tmparray[$i]);
       	//printf("\nvc".substr($tmp,0,strlen("<?xml"))."\n");
       	if(substr($tmp,0,strlen("<?xml")) === "<?xml") {
       		break;
       	}
       }
       for(;$i<count($tmparray);$i++) {
       	 $realbody[]=$tmparray[$i];
       }
       
       $realxml = implode("\r\n\r\n",$realbody);
      // printf("realxml:\n".$realxml."\n");
       return $realxml;
	}
}

class GrandCloudStorage {
	/**
	 * GrandCloud domain
	 * @access protected
	 */
	protected $host;

	/**
	 * access_key
	 * @access protected
	 */
	protected $access_key;

	/**
	 * access_secret
	 * @access protected
	 */
	protected $access_secret;

	/**
	 * bucket name
	 * @access protected
	 */
	protected $bucket;

	/**
	 * bucket cname
	 * this is used for public access control
	 *
	 * @access protected
	 */
	protected $bucket_cname;

	/**
	 * http headers, array
	 * @access protected
	 */
	protected $headers;

	/**
	 * http body, string
	 * @access protected
	 */
	protected $body;

	/**
	 * http response code
	 * @access protected
	 */
	protected $response_code;

	/**
	 * http response header
	 * @access protected
	 */
	protected $response_header;

	/**
	 * http response content length
	 * @access protected
	 */
	protected $response_length;

	/**
	 * http response content text
	 * @access protected
	 */
	protected $response_body;

	/**
	 * last curl error
	 * @access protected
	 */
	protected $last_curl_error;

	/**
	 * debug switch
	 * @access protected
	 */
	protected $debug = false;
	
	
	/**
	 * default location
	 */
	const DEFAULT_LOCATION = 'huadong-1';
	
   

	/**
	 * constructor
	 * @param string $host  storage host, no ending slash
	 * @param string $bucket  default bucket
	 * @return $this object
	 */
	public function __construct($host='', $bucket=null) {
		$this->host = $host;
		$this->bucket = $bucket;

		return $this;
	}

	/**
	 * set region host
	 * @param string $host
	 * @return $this object
	 */
	public function set_host($host) {
		$this->host = $host;

		return $this;
	}

	/**
	 * get current region host
	 * @param void
	 * @return string
	 */
	public function get_host() {
		return $this->host;
	}

	/**
	 * set access_key and access_secret
	 * @param string $access_key
	 * @param string $access_secret
	 * @return $this object
	 */
	public function set_key_secret($access_key, $access_secret) {
		$this->access_key = $access_key;
		$this->access_secret = $access_secret;

		return $this;
	}

	/**
	 * get current access_key
	 * @param void
	 * @return string
	 */
	public function get_access_key() {
		return $this->access_key;
	}

	/**
	 * get current access_secret
	 * @param void
	 * @return string
	 */
	public function get_access_secret() {
		return $this->access_secret;
	}

	/**
	 * set debug switch
	 * @param bool $flag  true/false
	 * @return $this object
	 */
	public function set_debug($flag) {
		$this->debug = ($flag === true);

		return $this;
	}

	/**
	 * set default bucket
	 * @param string $name  bucket's name
	 * @param [opt] string $cname  bucket's cname
	 * @return $this object
	 */
	public function set_bucket($name, $cname=null) {
		$this->bucket = $name;

		if (!empty($cname)) {
			$this->bucket_cname = $cname;
		}

		return $this;
	}

	/**
	 * get current bucket
	 * @param void
	 * @return string
	 */
	public function get_bucket() {
		return $this->bucket;
	}

	/**
	 * set default bucket cname
	 * @param string $cname  bucket's cname
	 * @return $this object
	 */
	public function set_bucket_cname($cname) {
		$this->bucket_cname = $cname;

		return $this;
	}

	/**
	 * get current bucket cname
	 * @param void
	 * @return string
	 */
	public function get_bucket_cname() {
		return $this->bucket_cname;
	}

	/**
	 * Set http request header fields
	 * @param string $field  http header field
	 * @param string $value  value of the field
	 * usually $field is a string without ":",and $value is not empty,
	 * example:$filed = "mykey1",$value = "myvalue1";
	 * meanwhile,$field can be like "key1:value1\nkey2:value2\n..",
	 * and $value will unused in this situation.
	 * 
	 * @return $this object
	 */
	public function set_header($field, $value=null) {
		$field = trim($field);
		$value = trim($value);

		if (empty($field)) {
			return $this;
		}

		if (strpos($field, ':')) {  //$field can be like "key1:value1\nkey2:value2\n..",$value will unused in this situation 
			foreach (explode("\n", $field) as $item) {
				$key = substr($item, 0, strpos($item, ':'));

				$this->headers[$key] = $item;
			}
		} else {
			$this->headers[$field] = "{$field}: {$value}";
		}

		return $this;
	}

	/**
	 * Remove http header field
	 * @param string $field
	 * @return $this object
	 */
	public function remove_header($field) {
		$field = trim($field);
		if (isset($this->headers[$field])) {
			unset($this->headers[$field]);
		}

		return $this;
	}

	/**
	 * Set http request body
	 * @param string $content  http request body
	 * @return $this object
	 */
	public function set_body($content) {
		$this->body = $content;

		return $this;
	}

	/**
	 * Get response code
	 * @param void
	 * @return integer
	 */
	public function get_response_code() {
		return $this->response_code;
	}

	/**
	 * get response header
	 * @param void
	 * @return string
	 */
	public function get_response_header() {
		return $this->response_header;
	}

	/**
	 * Get response content length
	 * @param void
	 * @return integer
	 */
	public function get_response_length() {
		return $this->response_length;
	}

	/**
	 * Get response content
	 * @param void
	 * @return string
	 */
	public function get_response_body() {
		return $this->response_body;
	}

	/**
	 * Get last curl error message
	 * @param void
	 * @return string
	 */
	public function get_curl_error() {
		return $this->last_curl_error;
	}

	/**
	 * Get all buckets,corresponds to "GET Service" in API
	 * @param void
	 * @return GCBucket objects list
	 * @exception see GCError
	 */
	public function get_allbuckets() {
		//$conn = $this->make_request('GET', '/');
		$conn = $this->make_request_with_path_and_params_split("GET",'/');
		$code = $this->exec_request($conn);

		if (200 != $code) {
			throw new Exception($this->response_body, $code);
		}

		return $this->parse_bucketsxml($this->response_body);
	}

	/**
	 * Get bucket's metas, now only return idc,
	 * corresponds to "GET Bucket Location" in API
	 * @param string $bucket  bucket's name
	 * @return string  bucket's idc info
	 * @exception see GCError
	 */
	public function head_bucket($bucket) {
		$bucket = trim($bucket, '/');
		$bucket = "/{$bucket}?location";

		//$conn = $this->make_request('GET', $bucket);
		$conn = $this->make_request_with_path_and_params_split("GET",$bucket);
		$code = $this->exec_request($conn);

		if (200 != $code) {
			throw new Exception($this->response_body, $code);
		}

		return $this->parse_localxml($this->response_body);
	}

	/**
	 * Create new bucket,corresponds to "PUT Bucket" in API
	 * @param string $name  bucket's name to create
	 * @param string $local  bucket's region, region of your bucket,
	 * region currently support "huadong-1", "huabei-1", default to huabei-1
	 * @return true on success
	 * @exception see GCError
	 */
	public function put_bucket($name, $local='huabei-1') {
		$local_xml = $this->make_bucket_local($local);
		$this->set_header('Content-Length', strlen($local_xml));
		$this->set_body($local_xml);

		//$conn = $this->make_request('PUT', $name, '', 'text/xml');
		$conn = $this->make_request_with_path_and_params_split("PUT",$name,array(),'','text/xml');
		$code = $this->exec_request($conn);

		// code: 204 = success
		if (204 != $code) {
			throw new Exception($this->response_body, $code);
		}

		return true;
	}

	/**
	 * Delete specified bucket,corresponds to "Delete Bucket" in API
	 * @param string $name  bucket's name to delete
	 * @return true on success
	 * @exception throw exception when bucket is not empty or response invalid
	 */
	public function delete_bucket($name) {
		$this->set_header('Content-Length', 0);
       
		//$conn = $this->make_request('DELETE', $name);
		$conn = $this->make_request_with_path_and_params_split("DELETE",$name);
		$code = $this->exec_request($conn);

		// code: 204 = success
		if (204 != $code) {
			throw new Exception($this->response_body, $code);
		}
		return true;
	}

	/**
	 * Get bucket policy,corresponds to "GET Bucket Policy" in API
	 * @param string $bucket  bucket's name
	 * @return string  bucket's policy json
	 * @exception see GCError
	 */
	public function get_bucket_policy($name) {
		$bucket = trim($name,'/');
		$path = "/{$bucket}?policy";

		$conn = $this->make_request_with_path_and_params_split("GET", $path);
		$code = $this->exec_request($conn);

		if (200 != $code) {
			throw new Exception($this->response_body, $code);
		}

		return $this->response_body;
	}

	/**
	 * Put bucket policy to storage,corresponds to "PUT Bucket Policy" in API
	 * @param string $bucket  bucket's name
	 * @param array $policy  bucket policy config,if $policy is null,delete old bucket policy
	 * @return true on success
	 * @exception see GCError
	 */
	public function put_bucket_policy($bucket, $policy) {
		
		if($policy === null) {  // if policy is null,delete bucket policy
			$is_old_policy = true;
			try {
				$old_policy = $this->get_bucket_policy($bucket);
			} catch (Exception $e) {
				if ($e->getErrorCode() != ERR_BUCKET_NOPOLICY) {
					throw $e;
				}
			
				$is_old_policy = false;
			}
			if(!$is_old_policy) {  // 之前服务器上并不存在bucket policy，直接返回
				return true;
			}

			return $this->delete_bucket_policy($bucket);
		}

		$policy_object = array(
				'Id' => $this->make_uuid(),
				'Statement' => $policy
		);

		$stream = fopen('data://text/plain,' . rawurlencode(json_encode($policy_object)), 'rb');
         
		$code = $this->post_or_put_request("PUT", "{$bucket}?policy", $stream);
		if($code !== 204) {
			throw new Exception($this->response_body,$code);
		}
		
		return true;
	}

	/**
	 * Delete bucket policy,corresponds to "DELETE Bucket Policy" in API
	 * @param $bucket bucket's name
	 * @return true on success
	 * @exception see GCError
	 */
	public function delete_bucket_policy($bucket) {
		$path = "{$bucket}?policy";

		return $this->delete_bucket($path);
	}

	/**
	 * Get all objects of specified bucket,corresponds to "GET Bucket" in API
	 * @param string $bucket  bucket's name
	 * @param integer $maxkeys  max response objects number of per-request
	 * @param string $marker  response objects offset
	 * @param string $delimiter  response objects name filter
	 * @param string $prefix  response objects name filter
	 * @return GCEntity object
	 * @exception see GCError
	 */
	public function get_allobjects($bucket, $maxkeys=null, $marker='', $delimiter='', $prefix='') {
		$bucket = trim($bucket, '/');
		$bucket = "/{$bucket}";
		$params = array();
		if (!empty($maxkeys)) {
			$maxkeys = intval($maxkeys);
			if ($maxkeys > 0) {
				$params['max-keys'] = $maxkeys;
			}
		}

		if ($marker !== '') {
			$params['marker'] = trim($marker);
		}
		

		if ($delimiter !== '') {
			$params['delimiter'] = trim($delimiter);
		}

		if ($prefix !== '') {
			$params['prefix'] = trim($prefix);
		}
         
        $conn = $this->make_request_with_path_and_params_split("GET",$bucket,$params);
		$code = $this->exec_request($conn);

		if (200 != $code) {
			throw new Exception($this->response_body, $code);
		}

		return $this->parse_objectsxml($this->response_body);
	}


	/**
	 * Get all multipart (corresponds to "List Multipart Upload" in API)
	 * @param string $bucket, your bucketname
	 * @param string $key_marker,the key to start with
	 * @param string $upload_id_marker,the uploadid to start with
	 * @param int $max_uploads,the maximum number of keys returned in the response body
	 * @param string $prefix,the prefix parameter to the key of the multipart upload you want to retrive
	 * @param char $delimiter,the param you use to group keys
	 * @return GCMultipartUploadEntity object on success
	 * @exception throw exception when response invalid
	 */
	public function get_all_multipart_upload($bucket,$key_marker='',$upload_id_marker='', $max_uploads=null, $prefix='', $delimiter='') {
		$bucket = trim($bucket, '/');
		$bucket = "/{$bucket}";
		
		$params = array();
		if ($max_uploads !== null) {
			$max_uploads = intval($max_uploads);
			if ($max_uploads > 0) {
				$params['max-uploads'] = $max_uploads;
			}
		}
		
		if ('' !== trim($key_marker)) {
			$params['key-marker'] = trim($key_marker);
		}
		
		if('' !== trim($upload_id_marker)) {
			$params['upload-id-marker'] = trim($upload_id_marker);
		}
		if ('' !== trim($delimiter)) {
			$params['delimiter'] = trim($delimiter);
		}
		
		if ('' !== trim($prefix)) {
			$params['prefix'] = trim($prefix);
		}

		$path = $bucket.'?uploads';		
		$conn = $this->make_request_with_path_and_params_split('GET',$path,$params);		
		$code = $this->exec_request($conn);
		
		if (200 != $code) {
			throw new Exception($this->response_body, $code);
		}
		
		return $this->parse_multipart_uploadsxml($this->response_body);
	}
	/**
	 * Get object's metas(corresponds to "HEAD Object" in API)
	 * @param string $name  object's name
	 * @return array('name'=>'?', 'meta'=>array(...), 'size'=>?) when success
	 * @exception see GCError
	 */
	public function head_object($name) {
		$conn = $this->make_request('HEAD', $name);
		$code = $this->exec_request($conn);

		if (200 != $code) {
			throw new Exception($this->response_body, $code);
		}
        $result = $this->parse_header($this->response_header);
        $result['name'] = $name;
        $result['size'] = $this->response_length;
		return $result;
	}

	/**
	 * Put object to storage(corresponds to "PUT Object" in API)
	 * @param string $name  object's name
	 * @param string $source  local file path(/path/to/filename.ext) or stream
	 * @param string $content_meta  see make_request()
	 * @param string $content_type  see make_request()
	 * @param string $content_md5  see make_request()
	 * @return true on success
	 * @exception see GCError
	 */
	public function put_object($name, $source, $content_meta='', $content_type='', $content_md5='') {
		if (is_resource($source)) { // stream upload
			if (empty($name)) {
				throw new Exception('$name must be supplied for resource type!', 500);
			}
            fseek($source,0,0);
		}
		elseif (is_string($source)) { // file upload			
			if (empty($name)) {
				$name = basename($source);
			}
		}
        
        if(empty($content_type)) {
			$pathinfo = pathinfo($name);
			$content_type = GCMIME::get_type(isset($pathinfo['extension']) ? $pathinfo['extension'] : '');
		}
		$code = $this->post_or_put_request("PUT", $name, $source,array(),$content_meta,$content_type,$content_md5);
		// code: 204 = success
		if (204 != $code) {
			throw new Exception($this->response_body, $code);
		}
		return true;
		
	}
    
    /**
     * Copy Object(corresponds to "PUT Object - Copy" in API)
     * @param $sbucket,name of source bucket
     * @param $skey,name of source object
     * @param $dbucket,name of destnation bucket
     * @param $dkey,name of destnation object
     * @param $content_meta,fileds will be sended as request headers, 
     *           like x-snda-meta-XXXX or those headers do not necessary 
     * return new object info on success
     * @exception see GCError
     */
    public function copy_object($sbucket,$skey,$dbucket,$dkey,$content_meta="",$content_type="") {
    	$path = "/{$dbucket}/{$dkey}";
    	$copy_source = "/{$sbucket}/{$skey}";
    	return $this->copy_from_path_to_path($copy_source,$path,$content_meta,$content_type);
    }
    
    /**
     * Copy from source path to destnation path, now it used in copy object and upload part copy
     * @param $from_path,source path
     * @param $to_path,destnation path
     * @param $content_meta,fileds will be sended as request headers, 
     *           like x-snda-meta-XXXX or those headers do not necessary 
     * return request info on success
     * @exception see GCError
     */
    public function copy_from_path_to_path($from_path,$to_path,$content_meta="",$content_type="") {
    	$content_meta = trim($content_meta);
    	if(!empty($content_meta)) {
    		$content_meta .= ",";
    	}
    	$content_meta .= "x-snda-copy-source:{$from_path}";
    	$conn = $this->make_request_with_path_and_params_split("PUT",$to_path,array(),$content_meta,$content_type);
    	$code = $this->exec_request($conn);
    	if($code != 200) {
    		throw Exception($this->response_body,$code);
    	} 
    	return $this->parse_copy_object_result($this->response_body);
    }
    
	/**
	 * Get object from storage(corresponds to "GET Object" in API)
	 * @param string $name  object's name
	 * @param string $target  write to local file path(/path/to/filename.ext) or stream
	 * @param boolean $auto_close  if auto close the $target passed when it is a stream?
	 * @return true on success
	 * @exception see GCError
	 */
	public function get_object($name, $target=null, $auto_close=false) {
		$this->head_object($name);//why? ask spring

		//$conn = $this->make_request('GET', $name);
        $conn = $this->make_request_with_path_and_params_split("GET",$name);
        
		$is_stream = false;
		if ($target !== null) {
			if (is_resource($target)) { // write to stream
				$is_stream = true;

				$target_stream = $target;
			}
			else if (is_string($target)) { // write to local file
				$target_stream = fopen($target, 'wb');
				if (!$target_stream) {
					curl_close($conn);

					throw new Exception("Unable to open {$target}", 500);
				}
			}

			if ($target_stream) {
				curl_setopt_array($conn, array(
						CURLOPT_HEADER    => false,
						CURLOPT_FILE      => $target_stream
				));
			}
		}

		$code = $this->exec_request($conn, true);

		if ($auto_close && $is_stream) {
			fclose($target_stream);
		}

		if (200 != $code) {
			throw new Exception($this->response_body, $code);
		}

		return true;
	}

	/**
	 * Get object resource from storage
	 * @param string $name  object's name
	 * @param integer $expire  expire of resource
	 * @return resource on success
	 */
	public function get_object_resource($name, $expire=300) {
		$this->head_object($name);

		$path = $this->get_abs_path($name);
		$expire = time() + $expire;

		$auth = "GET\n"                // HTTP Method
		."\n"                   // Content-MD5 Field
		."\n"                   // Content-Type Field
		."{$expire}\n"          // Date Field
		.''                     // Canonicalized SNDA Headers
		.$path;                 // Filepath

		$req_cname = $this->get_bucket_cname();
		$req_params = http_build_query(array(
				'SNDAAccessKeyId' => $this->get_access_key(),
				'Expires' => $expire,
				'Signature' => base64_encode(hash_hmac('sha1', $auth, $this->access_secret, true))
		));

		return "{$req_cname}{$path}?{$req_params}";
	}

	/**
	 * Delete object from storage (corresponds to "Delete Object" in API)
	 * @param string $name  object's name
	 * @return true on success
	 * @exception see GCError
	 */
	public function delete_object($name) {
		//$conn = $this->make_request('DELETE', $name);
		$conn = $this->make_request_with_path_and_params_split("DELETE",$name);
		$code = $this->exec_request($conn);

		// code: 204 = success
		if (204 != $code) {
			throw new Exception($this->response_body, $code);
		}

		return true;
	}

	/**
	 * Initiate multipart upload
	 * (corresponds to "Initiate multipart upload" in API)
	 * @param string $bucket,bucket's name
	 * @param string $key, object's name
	 * @param string $content_meta  see make_request()
	 * @param string $content_type  see make_request()
	 * @param string $content_md5  see make_request()
	 * @return $array,multipart upload info on success
	 * @exception throw exception when response invalid
	 */
	public function initiate_multipart_upload($bucket, $key, $content_meta='', $content_type='') {
		 
		$bucket = trim($bucket,"/");
		$path = "/{$bucket}/{$key}?uploads";
		if(empty($key)) {
			$pathinfo = pathinfo($key);
			$content_type = GCMIME::get_type(isset($pathinfo['extension']) ? $pathinfo['extension'] : '');

		}
		if(empty($content_type)) {
			$pathinfo = pathinfo($key);
			$content_type = GCMIME::get_type(isset($pathinfo['extension']) ? $pathinfo['extension'] : '');
		}
		 
		//$conn = $this->make_request("POST", $path,$content_meta,$content_type);
		$conn = $this->make_request_with_path_and_params_split("POST",$path,array(),$content_meta,$content_type);
		$code = $this->exec_request($conn);
		 
		if(200 != $code)  {
			throw new Exception($this->response_body,$code);
		}
		return $this->parse_initiate_multipart_upload_response($this->response_body);
	}
	

     
	/**
	 * Upload part to storage
	 * @param string $bucketname bucket's name
	 * @param string $key  object's name
	 * @param string $uploadid multipart upload's id
	 * @param int $partnumber of this part
	 * @param string $source  local file path(/path/to/filename.ext) or stream
	 * @param long $contentlength the length of this content
	 * @param string $content_md5  see make_request()
	 * @return true on success
	 * @exception throw exception when failed
	 */
	public function upload_part($bucketname, $key, $uploadid,$partnumber,$source, $contentlength = null,  $content_md5='') {
		
		if("" === $bucketname || "" === $key || (! is_numeric($partnumber))){
			throw new Exception('Illegal params');
		}
		$params = array(
				"partNumber" => $partnumber,
				"uploadId" => $uploadid
				);
		$path = "/{$bucketname}/{$key}?".http_build_query($params);
		
		$code = $this->post_or_put_request("PUT", $path, $source,array(),'','',$content_md5,$contentlength);
		
		if($code != 204) {
			throw new Exception($this->response_body, $code);
		}
		return true;
	}
	

    /**
     * Upload Part - Copy (corresponds to "Upload Part - Copy" in API)
     * @param $sbucket,name of source bucket
     * @param $skey,name of source object
     * @param $dbucket,name of destnation bucket
     * @param $dkey,name of destnation object
     * @param $uploadid,uploadid of the multipart upload
     * @param $partnumber,partnumber of the part to create
     * @param $content_meta,fileds will be sended as request headers, 
     *           like x-snda-meta-XXXX or those headers do not necessary 
     * return part info on success
     * @exception see GCError
     */
    public function upload_part_copy($sbucket,$skey,$dbucket,$dkey,$uploadid,$partnumber,$content_meta="") {
		$params = array(
				"partNumber" => $partnumber,
				"uploadId" => $uploadid
				);
		$path = "/{$dbucket}/{$dkey}?".http_build_query($params);
		$copy_source = "/{$sbucket}/{$skey}";
        return $this->copy_from_path_to_path($copy_source,$path,$content_meta);
	}
	
	/**
	 * Abort multipart upload
	 * @param string $bucket,bucket's name
	 * @param string $key,object's name
	 * @param string $uploadId, the uploadid of the multipart upload
	 * @throws Exception when failed
	 * @true on success
	 */
	public function abortMultipartUpload($bucket,$key,$uploadId){
		$path = "/{$bucket}/{$key}?uploadId={$uploadId}";
		$conn = $this->make_request_with_path_and_params_split('DELETE', $path);
		$code = $this->exec_request($conn);
		
		// code: 204 = success
		if (204 != $code) {
			throw new Exception($this->response_body, $code);
		}
		
		return true;
	}

	
	/**
	 * list parts
	 * @param string,$bucket,bucket's name
	 * @param string,$key,object's name
	 * @param string $uploadId,the uploadid of the multipart upload
	 * @param int $max_parts, the maximum number of parts returned in the response body
	 * @param string $part_number_marker,the part to start with
	 * @return GCPartsEntity on success
	 * @exception,throw exception when failed
	 */
	public function list_parts($bucket,$key,$uploadId,$max_parts = null,$part_number_marker = '') {
		if("" === $bucket || "" === $key || "" === $uploadId){
			throw new Exception('Illegal params');
		}
		$bucket = trim($bucket);
		$params = array();
		$path = "/{$bucket}/{$key}?uploadId={$uploadId}";
		if(is_numeric($max_parts)) {
			$params["max-parts"] = $max_parts;
		}
		if(is_numeric($part_number_marker)) {
			$params["part-number-marker"] = $part_number_marker;
		}
		$conn = $this->make_request_with_path_and_params_split("GET", $path,$params);
		$code = $this->exec_request($conn);
		if (200 != $code) {
			throw new Exception($this->response_body, $code);
		}
		
		return $this->parse_listspartxml($this->response_body);
		
	}
	
	/**
	 * complete multipartupload
	 * @param string $bucket
	 * @param string $key
	 * @param string $uploadid
	 * @param string $complete_xml,if is '',then we will get it by list_parts($uploadid) 
	 * @throws Exception when failed
	 * @return response body
	 */
	public function complete_multipartupload($bucket,$key,$uploadid,$complete_xml = ''){
		if(empty($complete_xml)) {
			try {
				$partEntity = $this->list_parts($bucket, $key, $uploadid);
				$complete_xml = $partEntity->to_completemultipartuploadxml();
			} catch(Exception $e) {
				throw $e;
			}
		}
		
		$path = "/{$bucket}/{$key}?uploadId={$uploadid}";
		$stream = fopen('data://text/plain,' . rawurlencode($complete_xml), 'rb');
		$code = $this->post_or_put_request("POST",$path,$stream);
		
		if ( 300 <= $code) {
			throw new Exception($this->response_body, $code);
		}
		return $this->parse_complete_multipart_uploadxml($this->response_body);
	}
	
	/**
	 * get resource abs path
	 * @param string $path
	 * @return string
	 */
	public function get_abs_path($path) {
		if ('/' != $path[0]) {
			$path = $this->bucket ? "/{$this->bucket}/{$path}" : "/{$path}";
		}

		$path = preg_replace('~/+~', '/', $path);

		return $path;
	}


	/**
	 * Execute curl request
	 * @param resource $ch  curl handle
	 * @param bool $close_request  whether call curl_close() after execute request
	 * @return http success status code or false
	 * @exception throw GCError when response code in 400~499 range
	 */
	public function exec_request($ch, $close_request=true) {
		if (!is_resource($ch)) {
			return false;
		}

		$response = curl_exec($ch);
		$this->last_curl_error = curl_error($ch);
		if (!empty($this->last_curl_error)) {
			throw new Exception($this->last_curl_error,0);
		}

		$this->response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->response_length = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		$tmparray = explode("\r\n\r\n", $response);
		if (isset($tmparray[1])) {
			$this->response_header = array_shift($tmparray);
			$this->response_body = implode("\r\n\r\n", $tmparray);
		} else {
			$this->response_body = $response;
		}

		if ($close_request) {
			curl_close($ch);
		}

		if ($this->response_code >= 400 && $this->response_code <= 499) {
			throw new GCError($this->response_code, $this->response_body);
		}

		return $this->response_code;
	}

	public function parse_header($header) {
		$tmparray = explode("\r\n", $header);

		$result = array();
		$others = array();
		foreach ($tmparray as $item) {
			$item = trim($item);
			if ('x-snda-meta-' === substr($item, 0, 12)) {
				$tmpitem = explode(':', $item);
				if (isset($tmpitem[1])) {
					$result[substr(trim($tmpitem[0]), 12)] = trim($tmpitem[1]);
				} else {
					$result[substr($item, 12)] = null;
				} 
			} else if('HTTP' === substr($item,0,strlen('HTTP'))) {
				continue;
			}else {
				$tmpitem = explode(':', $item);
				if(isset($tmpitem[1])) {
					$others[trim($tmpitem[0])] = trim($tmpitem[1]);
				} else {
					$others[trim($tmpitem[0])] = null;
				}
			}
		}

        $others['meta'] = $result;
		return $others;
	}

    
	public function parse_bucketsxml($bucketsxml) {
		
		$bucketsxml = $this->get_xmlpart($bucketsxml);
		$doc = new DOMDocument();
		$doc->loadXML($bucketsxml);

		$buckets = $doc->getElementsByTagName('Bucket');

		$bucketsarray = array();
		foreach($buckets as $xml) {
			$idc = self::DEFAULT_LOCATION;
			$name = $xml->getElementsByTagName('Name')->item(0)->nodeValue;
			$ctime = $xml->getElementsByTagName('CreationDate')->item(0)->nodeValue;

			$local = $xml->getElementsByTagName('Location')->item(0);
			if ($local && !empty($local->nodeValue)) {
				$idc = $local->nodeValue;
			}

			$bucketsarray[] = new GCBucket($idc, $name, $ctime);
		}

		return $bucketsarray;
	}

	public function parse_localxml($idcxml) {
		$idcxml = $this->get_xmlpart($idcxml);
		$doc = new DOMDocument();
		$doc->loadXML($idcxml);

		$local  = $doc->getElementsByTagName('LocationConstraint')->item(0);
		if (empty($local)) {
			return self::DEFAULT_LOCATION;
		}

		if (!empty($local->nodeValue)) {
			return $local->nodeValue;
		}

		return self::DEFAULT_LOCATION;
	}

    protected function parse_copy_object_result($body) {
    	$body_xml = $this->get_xmlpart($body);
    	$doc = new DOMDocument();
    	$doc->loadXML($body_xml);
    	$message = $doc->getElementsByTagName("CopyObjectResult");
    	if(empty($message)) {
    		throw new GCError($body,200);
    	} 
    	try{
	    	$result = array(
	    	"LastModified" =>  $doc->getElementsByTagName("LastModified")->item(0)->nodeValue,
	    	"ETag" =>  $doc->getElementsByTagName("ETag")->item(0)->nodeValue);
	    	return $result;
    	}catch(Exception $e ) {
    		throw new Exception($body,200);
    	}
    }
    
	public function parse_objectsxml($objectxml) {
		$objectxml = $this->get_xmlpart($objectxml);
		$doc = new DOMDocument();
		$doc->loadXML($objectxml);

		$xpath = new DOMXPath($doc);

		$entity = new GCEntity();

		$name = $xpath->query('/ListBucketResult/Name')->item(0);
		if ($name) {
			$entity->set_bucket($name->nodeValue);
		}

		$maxkeys = $xpath->query('/ListBucketResult/MaxKeys')->item(0);
		if ($maxkeys) {
			$entity->set_maxkeys($maxkeys->nodeValue);
		}

		$istruncated = $xpath->query('/ListBucketResult/IsTruncated')->item(0);
		if ($istruncated) {
			$entity->set_istruncated($istruncated->nodeValue === 'true');
		}

		$prefix = $xpath->query('/ListBucketResult/Prefix')->item(0);
		if ($prefix) {
			$entity->set_prefix($prefix->nodeValue);
		}

		$delimiter = $xpath->query('/ListBucketResult/Delimiter')->item(0);
		if ($delimiter) {
			$entity->set_delimiter($delimiter->nodeValue);
		}

		$marker = $xpath->query('/ListBucketResult/NextMarker')->item(0);
		if ($marker) {
			$entity->set_marker($marker->nodeValue);
		}

		$objects = $xpath->query('/ListBucketResult/Contents');
		foreach($objects as $xml) {
			$key           = $xml->getElementsByTagName('Key')->item(0)->nodeValue;
			$size          = $xml->getElementsByTagName('Size')->item(0)->nodeValue;
			$lastmodified  = $xml->getElementsByTagName('LastModified')->item(0)->nodeValue;
			$etag          = $xml->getElementsByTagName('ETag')->item(0)->nodeValue;

			$entity->add_object(new GCObject($key, $size, $lastmodified, $etag));
		}

		$common = $xpath->query('/ListBucketResult/CommonPrefixes');
		foreach ($common as $comxml) {
			$folders = $comxml->getElementsByTagName('Prefix');
			foreach ($folders as $xml) {
				$entity->add_object(new GCObject($xml->nodeValue, '-', '-', ''));
			}
		}

		// adjust marker when no delimiter
		if ($marker === null && $entity->get_istruncated()) {
			$last_object = $entity->get_object(-1);
            if(empty($last_object) === false) {
			   $entity->set_marker($last_object->get_key());
            }
		}

		return $entity;
	}

    
	protected function parse_initiate_multipart_upload_response($response_xml) {
		$response_xml = $this->get_xmlpart($response_xml);
		$doc = new DOMDocument();
		$doc->loadXML($response_xml);
		$multipart_upload_tree = $doc->getElementsByTagName(GCMultipartUpload::$InitiateMultipartUploadResultTag)->item(0);
		if($multipart_upload_tree->childNodes->length) {
			$bucket = $multipart_upload_tree->getElementsByTagName(GCMultipartUpload::$bucketTag)->item(0)->nodeValue;
			$key = $multipart_upload_tree->getElementsByTagName(GCMultipartUpload::$keyTag)->item(0)->nodeValue;
			$value = $multipart_upload_tree -> getElementsByTagName(GCMultipartUpload::$uploadIdTag)->item(0)->nodeValue;
			$result = new GCMultipartUpload($bucket,$key,$value);
			return $result->to_array();
		}
		return false;
	}
    
	protected function parse_multipart_uploadsxml($mulpart_uploads_xml) {
		$mulpart_uploads_xml = $this->get_xmlpart($mulpart_uploads_xml);
		$doc = new DOMDocument();
		$doc->loadXML($mulpart_uploads_xml);

		$xpath = new DOMXPath($doc);

		$entity = new GCMultipartUploadEntity();

		$bucketTag = '/'.GCMultipartUploadEntity::$listMultipartUploadsResultTag.'/'.GCMultipartUploadEntity::$bucketTag;
		$bucket = $xpath->query($bucketTag)->item(0);
		if ($bucket) {
			$entity->set_bucket($bucket->nodeValue);
		}

		$maxkeysTag = '/'.GCMultipartUploadEntity::$listMultipartUploadsResultTag.'/'.GCMultipartUploadEntity::$maxUploadsTag;
		$maxkeys = $xpath->query($maxkeysTag)->item(0);
		if ($maxkeys) {
			$entity->set_maxUploads($maxkeys->nodeValue);
		}

		$istruncatedTag = '/'.GCMultipartUploadEntity::$listMultipartUploadsResultTag.'/'.GCMultipartUploadEntity::$isTruncatedTag;
		$istruncated = $xpath->query($istruncatedTag)->item(0);
		if ($istruncated) {
			$entity->set_istruncated($istruncated->nodeValue === 'true');
		}

		$prefixTag = '/'.GCMultipartUploadEntity::$listMultipartUploadsResultTag.'/'.GCMultipartUploadEntity::$prefixTag;
		$prefix = $xpath->query($prefixTag)->item(0);
		if ($prefix) {
			$entity->set_prefix($prefix->nodeValue);
		}

		$delimiterTag = '/'.GCMultipartUploadEntity::$listMultipartUploadsResultTag.'/'.GCMultipartUploadEntity::$delimiterTag;
		$delimiter = $xpath->query($delimiterTag)->item(0);
		if ($delimiter) {
			$entity->set_delimiter($delimiter->nodeValue);
		}

		$keymarkerTag = '/'.GCMultipartUploadEntity::$listMultipartUploadsResultTag.'/'.GCMultipartUploadEntity::$keyMarkerTag;
		$marker = $xpath->query($keymarkerTag)->item(0);
		if ($marker) {
			$entity->set_keyMarker($marker->nodeValue);
		}

		$uploadidmarkerTag = '/'.GCMultipartUploadEntity::$listMultipartUploadsResultTag.'/'.GCMultipartUploadEntity::$uploadIdMarker;
		$uploadidmarker = $xpath->query($uploadidmarkerTag)->item(0);
		if($uploadidmarker) {
			$entity->set_uploadIdMarker($uploadidmarker->nodeValue);
		}
		
		$nextkeymarkerTag = '/'.GCMultipartUploadEntity::$listMultipartUploadsResultTag.'/'.GCMultipartUploadEntity::$nextKeyMarkerTag;
		$nextkeymarker = $xpath->query($nextkeymarkerTag)->item(0);
		if($nextkeymarker) {
			$entity -> set_nextKeyMarker($nextkeymarker->nodeValue);
		}
		
		$nextuploadidmarkerTag = '/'.GCMultipartUploadEntity::$listMultipartUploadsResultTag.'/'.GCMultipartUploadEntity::$nextUploadIdMarkerTag;
		$nextuploadidmarker = $xpath -> query($nextuploadidmarkerTag) -> item(0);
		if($nextuploadidmarker) {
			$entity -> set_nextUploadIdMarker($nextuploadidmarker->nodeValue);
		}
		
		$uploadTag = '/'.GCMultipartUploadEntity::$listMultipartUploadsResultTag.'/'.GCMultipartUploadEntity::$uploadTag;
		$uploads = $xpath->query($uploadTag);
		foreach($uploads as $xml) {
			$key           = $xml->getElementsByTagName(GCMultipartUpload::$keyTag)->item(0)->nodeValue;
			$uploadid          = $xml->getElementsByTagName(GCMultipartUpload::$uploadIdTag)->item(0)->nodeValue;
			$lastmodified  = $xml->getElementsByTagName(GCMultipartUpload::$initiatedTag)->item(0)->nodeValue;
			$entity->addUpload(new GCMultipartUpload($bucket->nodeValue,$key,$uploadid,$lastmodified));
		}

		$commonPrefixTag = '/'.GCMultipartUploadEntity::$listMultipartUploadsResultTag.'/'.GCMultipartUploadEntity::$commonPrefixesTag;
		$common = $xpath->query($commonPrefixTag);
		foreach ($common as $comxml) {
			$folders = $comxml->getElementsByTagName(GCMultipartUploadEntity::$prefixTag);
			foreach ($folders as $xml) {
				$entity->add_object(new GCMultipartUpload($bucket->nodeValue, $xml->nodeValue, '-', '-'));
			}
		}

		return $entity;
	}
	
	protected function parse_listspartxml($listparts_xml) {
		$listparts_xml = $this->get_xmlpart($listparts_xml);
		$doc = new DOMDocument();
		$doc->loadXML($listparts_xml);
	
		$xpath = new DOMXPath($doc);
	
		$entity = new GCPartsEntity();
	
		$bucketTag = '/'.GCPartsEntity::$listpartsresultTag.'/'.GCPartsEntity::$bucketTag;
		$bucket = $xpath->query($bucketTag)->item(0);
		if ($bucket) {
			$entity->set_bucket($bucket->nodeValue);
		}
	
		$keyTag =  '/'.GCPartsEntity::$listpartsresultTag.'/'.GCPartsEntity::$keyTag;
		$key = $xpath->query($keyTag)->item(0);
		if ($key) {
			$entity->set_key($key->nodeValue);
		}
	     
		$uploadidTag =  '/'.GCPartsEntity::$listpartsresultTag.'/'.GCPartsEntity::$uploadIdTag;
		$uploadid = $xpath->query($uploadidTag)->item(0);
		if($uploadid) {
		   $entity->set_uploadid($uploadid->nodeValue);	
		}
		
		$maxpartsTag =  '/'.GCPartsEntity::$listpartsresultTag.'/'.GCPartsEntity::$maxpartsTag;
		$maxparts = $xpath->query($maxpartsTag)->item(0);
		if($maxparts) {
			$entity->set_maxparts($maxparts->nodeType);
		}
		
		$istruncatedTag =  '/'.GCPartsEntity::$listpartsresultTag.'/'.GCPartsEntity::$istruncatedTag;
        $istruncated = $xpath->query($istruncatedTag)->item(0);
		if ($istruncated) {
			$entity->set_istruncated($istruncated->nodeValue === 'true');
		}
	
		$partnumbermarkerTag =  '/'.GCPartsEntity::$listpartsresultTag.'/'.GCPartsEntity::$partnumberMarkerTag;
		$partnumbermarker = $xpath->query($partnumbermarkerTag)->item(0);
		if($partnumbermarker) {
			$entity->set_partnumbermarker($partnumbermarker->nodeValue);
		}
		
		$nextpartnumbermarkerTag =  '/'.GCPartsEntity::$listpartsresultTag.'/'.GCPartsEntity::$nextpartnumbermarkerTag;
		$nextpartnumbermarker = $xpath->query($nextpartnumbermarkerTag)->item(0);
		if($nextpartnumbermarker) {
			$entity->set_nextpartnumbermarker($nextpartnumbermarker->nodeValue);
		}
		

		$partsTag =  '/'.GCPartsEntity::$listpartsresultTag.'/'.GCPartsEntity::$partTag;
		$parts = $xpath->query($partsTag);
		foreach($parts as $xml) {
			$partnumber           = $xml->getElementsByTagName(GCUploadPart::$partnumberTag)->item(0)->nodeValue;
			$etag          = $xml->getElementsByTagName(GCUploadPart::$etagTag)->item(0)->nodeValue;
			$lastmodified  = $xml->getElementsByTagName(GCUploadPart::$lastModifiedTag)->item(0)->nodeValue;
			$size 		   = $xml->getElementsByTagName(GCUploadPart::$sizeTag)->item(0)->nodeValue;
			$entity->add_part(new GCUploadPart($key->nodeValue,$size,$lastmodified,$etag,$partnumber));
		}
	
		return $entity;
	}
	
	protected function parse_complete_multipart_uploadxml($xml) {
		 $xml = $this->get_xmlpart($xml);
		 $completeMultipartUploadResultTag = "CompleteMultipartUploadResult";
		 $locationTag = "/".$completeMultipartUploadResultTag."/Location";
		 $bucketTag = "/".$completeMultipartUploadResultTag."/Bucket";
		 $keyTag = "/".$completeMultipartUploadResultTag."/Key";
		 $eTagTag = "/".$completeMultipartUploadResultTag."/ETag";
		 
		 $doc = new DOMDocument();
		 $doc->loadXML($xml);
		 
		 $result = array();
		 $xpath = new DOMXPath($doc);
		 $location = $xpath->query($locationTag)->item(0);
		 if($location) {
		 	$result["Location"] = $location->nodeValue;
		 }
		 
		 $bucket = $xpath->query($bucketTag)->item(0);
		 if($bucket) {
		 	$result["Bucket"] = $bucket->nodeValue;
		 }
		 
		 $key = $xpath->query($keyTag)->item(0);
		 if($key) {
		 	$result["Key"] = $key->nodeValue;
		 }
		 
		 $eTag = $xpath->query($eTagTag)->item(0);
		 if($eTag) {
		 	$result["ETag"] = $eTag->nodeValue;
		 }
		 
		 return $result;
	}
	
	/**
	 * Get xml part from response body
	 */
	protected function get_xmlpart($response_body) {
       $tmparray = explode("\r\n\r\n", $response_body);
       $realbody = array();	
       for($i=0;$i<count($tmparray);$i++) {
       	$tmp = trim($tmparray[$i]);
       	//printf("\nvc".substr($tmp,0,strlen("<?xml"))."\n");
       	if(substr($tmp,0,strlen("<?xml")) === "<?xml") {
       		break;
       	}
       }
       for(;$i<count($tmparray);$i++) {
       	 $realbody[]=$tmparray[$i];
       }
       
       $realxml = implode("\r\n\r\n",$realbody);
      // printf("realxml:\n".$realxml."\n");
       return $realxml;
	}
	/**
	 * generate uuid string
	 * @param string $prefix
	 * @return string
	 */
	protected function make_uuid($prefix='') {
		$chars = md5(uniqid(mt_rand(), true));
		$uuid = substr($chars,0,8) . '-';
		$uuid .= substr($chars,8,4) . '-';
		$uuid .= substr($chars,12,4) . '-';
		$uuid .= substr($chars,16,4) . '-';
		$uuid .= substr($chars,20,12);

		return $prefix . $uuid;
	}
     
	/**
	 * sign the data
	 * @param string $data
	 * @return string
	 */
	protected function make_sign($data) {
		return 'SNDA'.' '.$this->access_key.':'.base64_encode(hash_hmac('sha1', $data, $this->access_secret, true));
	}

	/**
	 * adjust the meta
	 * @param string $meta
	 * @return string
	 */
	protected function make_meta($meta) {
		/**
		 * compress
		 * x-snda-meta-row: abc, x-snda-meta-row: bcd
		 * to
		 * x-snda-meta-row:abc,bcd  // value have no lead space
		 */
		$tmparray = array();
		foreach (explode(',', trim($meta)) as $item) {
			$item = explode(':', $item);

			if (isset($item[1])) {
				$tmparray[trim($item[0])][] = trim($item[1]);
			}
		}

		$keys = array_keys($tmparray);
		sort($keys);

		$meta = '';
		foreach ($keys as $key) {
			$meta .= "{$key}:".join(',', $tmparray[$key])."\n";
		}

		return $meta;
	}

	
	/**
	 * Generate request handler
	 * @param string $method           GET, HEAD, PUT, DELETE
	 * @param string $path             resource $path,used in sign
	 * @param array $params            $query params
	 * @param string $content_meta     x-snda-meta-XXXX field
	 * @param string $content_type     Content-Type field
	 * @param string $content_md5      Content-MD5 field
	 * @return cURL handle on success, false if any error.
	 */
	protected function make_request_with_path_and_params_split($method, $path, $query_params = array(),$content_meta='', $content_type='', $content_md5='') {
		$path = $this->get_abs_path($path);
		if ($content_meta) {
			$content_meta = $this->make_meta($content_meta);
		
			$this->set_header($content_meta);
		}
		
		if ($content_type) {
			$this->set_header('Content-Type', $content_type);
		}
		
		if ($content_md5) {
			$this->set_header('Content-MD5', base64_encode($content_md5));
		}
		
		$conn = curl_init();
		if ($conn) {
			$url = "{$this->host}{$path}";
			if(!empty($query_params)) {
				$params_str = http_build_query($query_params);
				if(false === strstr($path, '?')) {
					$url .= "?";
				} else {
					$url .= "&";
				}
			   $url .= $params_str;
			}
			$date = date('r');
			$auth = "{$method}\n"          // HTTP Method
			."{$content_md5}\n"     // Content-MD5 Field
			."{$content_type}\n"    // Content-Type Field
			."{$date}\n"            // Date Field
			.$content_meta          // Canonicalized SNDA Headers
			.$path;                 // resource path
		    //print_r("string to sign:".$auth."\n");
			$this->set_header('Date', $date);
			$this->set_header('Authorization', $this->make_sign($auth));
			$this->set_header('Expect', '');
			 
			curl_setopt_array($conn, array(
					CURLOPT_URL             => $url,
					CURLOPT_VERBOSE         => $this->debug,
					CURLOPT_CUSTOMREQUEST   => $method,
					CURLOPT_CONNECTTIMEOUT  => 10,
					CURLOPT_FOLLOWLOCATION  => true,
					CURLOPT_HEADER          => true,
					CURLOPT_NOBODY          => 'HEAD' === $method,
					CURLOPT_RETURNTRANSFER  => true,
					CURLOPT_BINARYTRANSFER  => true,
					CURLOPT_HTTPHEADER      => $this->headers
					));
		
					if (strstr($this->host, ':')) {
						$tmparray = explode(':', $this->host);
						if (isset($tmparray[2])) {
						  curl_setopt($conn, CURLOPT_PORT, intval($tmparray[2]));
			            }
		            }
		
			if (!empty($this->body)) {
				if (is_array($this->body)) {
				 $this->body = http_build_query($this->body);
				}
			
				curl_setopt_array($conn, array(
				CURLOPT_POST          => 1,
				CURLOPT_POSTFIELDS    => $this->body
				));
			}

			$this->body = null;
			$this->headers = array();
		} else {
			throw new Exception('Failed to init curl, maybe it is not supported yet?');
		}
		
		return $conn;
	}
  
	/**
	 * generate request handler
	 * @param string $method           GET, HEAD, PUT, DELETE
	 * @param string $path             resource $path
	 * @param string $content_meta     fileds will be sended as request headers, 
	 *                            like x-snda-meta-XXXX or those headers do not necessary 
	 * @param string $content_type     Content-Type field
	 * @param string $content_md5      Content-MD5 field
	 * @return cURL handle on success, false if any error.
	 */
    protected function make_request($method, $path,$content_meta='', $content_type='', $content_md5='') {
        //$path = $this->get_abs_path($path);

        $params = array();
        if (strstr($path, '?')) {
            $tmparray = explode('?', $path);

            $path = array_shift($tmparray);
            $query_string = implode('?', $tmparray);
            parse_str($query_string,$params);
        }
        
        $params_array = array();
        if (!empty($params)) {
        	$isfirst = true;
        	
        	foreach ($params as $key=>$value) {
        		if($value === NULL || $value === '') {
        			if(true === $isfirst) {
        				$path .= "?".$key; // may have bug?
        				$isfirst = false;
        			} 
        		} else {
        			$params_array[$key] = $value;
        		}
        	}
        }
        return $this->make_request_with_path_and_params_split($method, $path, $params_array,$content_meta, $content_type, $content_md5);
      
    }
	
    /**
     * process post or put request
     * @param string $path,path need to sign
     * @param resource $source,source data to post
     * @param array $query_params,params in query
     * @param string $content_meta,fileds will be sended as request headers, 
	 *               like x-snda-meta-XXXX or those headers do not necessary.
     * @param string $content_type
     * @param string $content_md5
     * @throws Exception
     * @return code;
     */
    protected function post_or_put_request($method,$path, $source, $query_params=array(),$content_meta='', $content_type='', $content_md5='',$content_length ='') {
    	
    	
    	if (is_resource($source)) { // stream upload
    		$source_stream = $source;   	
    		$source_fstat = fstat($source);
    		$source_size = isset($source_fstat['size']) ? $source_fstat['size'] : 0;
    		
    	}
    	elseif (is_string($source)) { // file upload
    		clearstatcache();
    		if (!is_file($source)) {
    			throw new Exception("{$source} doesn't exist", 404);
    		}
    	
    		$source_stream = fopen($source, 'rb');
    		if (!$source_stream) {
    			throw new Exception("Unable to read {$source}", 500);
    		}  	
    		$source_size = filesize($source);
    	}
    	elseif ($source === null) { // no content
    		$source_stream = null;
    	    $source_size = 0;  
    	} else {
    		throw new Exception('Unsupported source type!', 500);
    	}
    	
    	if(is_numeric($content_length)) {   //if $content_length is set,check weather $content_length is illegal
    		$content_length = intval($content_length);
    		if($content_length > $source_size || $content_length < 0) {
    			throw new Exception("Content_length({$content_length}) is illegal",500);
    		} else {
    			$source_size = $content_length;
    		}
    	}
    	
    	$code = 200;
    	try {
    		if ($source_size === 0) {
    			$this->set_header('content-length', $source_size);
    		}
    	
    		$conn = $this->make_request_with_path_and_params_split($method, $path,$query_params,$content_meta, $content_type, $content_md5);    	
    		if ($source_size !== 0) {
    			curl_setopt_array($conn, array(
    					CURLOPT_PUT         => true,
    					CURLOPT_INFILE      => $source_stream,
    					CURLOPT_INFILESIZE  => $source_size
    			));
    		}
    	
    		$code = $this->exec_request($conn);
    		if (is_resource($source_stream)) {
    			fclose($source_stream);
    		}
    	
    	} catch (Exception $e){
    		if (is_resource($source_stream)) {
    			fclose($source_stream);
    		}
    	
    		throw $e;
    	}
    	
    	return $code;
    }

	/**
	 * build bucket local xml
	 * @param string $local
	 * @return string
	 */
	protected function make_bucket_local($local) {
		$local = strtolower($local);
		$template = '<?xml version="1.0" encoding="UTF-8"?><CreateBucketConfiguration><LocationConstraint>%s</LocationConstraint></CreateBucketConfiguration>';

		//若用户选择huadong-1，则默认不传location body
		$local_xml = '';
		$local = trim($local);
		switch ($local) {
			case 'huadong-1':
			    break;    
			default:
			    $local_xml = sprintf($template,$local);
				break;
		}
		return $local_xml;
	}

}
