<?php
	//Layer CDN 设定
	
	/**********基本设定**********/
	define('STATIC_URL','http://www.baidu.com/');	//源站URL
	define('DOMAIN','layercdn');	//使用云存储时，填写存储空间的名字；使用本地存储时，填写存储的相对路径。
	define('WELCOME_DOC',TRUE);	//空请求时是否显示欢迎界面
	
	/*********防盗链设定*********/
	define('ALLOW_REGX','.*');	//设置防盗链允许的[域名]正则表达式，此表达式只匹配referer的域名部分
	//define('ALLOW_REGX','^(best33\.com|.*\.best33\.com|)$');	//允许best33.com，*.best33.com，浏览器直接访问
	//define('ALLOW_REGX','^(best33\.com|.*\.best33\.com)$');	//允许best33.com，*.best33.com，不允许浏览器直接访问
	//define('ALLOW_REGX','^(.*)$');	//允许任意，允许浏览器访问
	//define('ALLOW_REGX','^(.+)$');	//允许任意，但不允许浏览器访问
	
	/**********进阶设定**********/
	define('PURGE_KEY','purge');	//刷新缓存的密码，访问http://domain/PURGE_KEY/path/to/file来刷新缓存。
	define('MIME','text/html');	//默认MIME类型，可以设为application/octet-stream则对未知项目自动弹出下载。
	define('DIRECT_EXT','php|asp|htm|html');	//不进入缓存的扩展名，安全起见不要删除PHP。
	define('NO_LOCATE',false);	//设置后将不进行跳转而采用read读取方式，可能会降低速度并增加流量。仅当遇到问题时启用。
	define('NO_KEY',true);	//启用后将不再使用一串md5编码的key作为文件名，当想保持文件名一致时启用之。
	define('NO_SECOND_FLODER',true);	//启用后将不再使用两层文件夹存储缓存，仅在本地环境、NO_KEY为假时有效。
	define('STATIC_HOST','');	//可以留空，也可以在这里填写你的源站域名，而在STATIC_URL中填写IP，减少域名解析的时间。
	
	/**********高级设定**********/
	//define('RUN_ENV', 'GCS');	//自定义运行环境（如不去掉前面的//则自动判断）可选：BAE/SAE/GCS/LOCAL 请大写
	//define('CS_AK','dummy');	//自定义云存储空间的Access Token，通常不需要
	//define('CS_SK','dummy');	//自定义云存储空间的Secret Token，通常不需要