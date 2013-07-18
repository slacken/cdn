<?php
//AK 公钥
define ( 'BCS_AK', defined('CS_AK')?CS_AK:getenv('HTTP_BAE_ENV_AK') );
//SK 私钥
define ( 'BCS_SK', defined('CS_SK')?CS_SK:getenv('HTTP_BAE_ENV_SK') );
//superfile 每个object分片后缀
define ( 'BCS_SUPERFILE_POSTFIX', '_bcs_superfile_' );
//sdk superfile分片大小 ，单位 B（字节）
define ( 'BCS_SUPERFILE_SLICE_SIZE', 1024 * 1024 );
